
<?php
/* =========================================================
   POPCARD 3 - RASIO PENGGUNAAN BAHAN
   Membutuhkan koneksi PDO: $conn
   Membutuhkan filter: $startDate dan $endDate
========================================================= */

$daftarKapasitas = require __DIR__ . '/kapasitas-mesin.php';

$kapasitasHarian = (float) (
    $daftarKapasitas['CUT']['kapasitas'] ?? 0
);

/* =========================================================
   TAB 1 - TREN PRODUKSI HARIAN MESIN CUT
========================================================= */

// Mengambil total m2_ok mesin CUT per tanggal
$sqlTrenCUT = "
    SELECT
        DATE(tgl_update) AS tanggal,
        COALESCE(SUM(m2_ok), 0) AS total_m2
    FROM produksi
    WHERE UPPER(TRIM(nama_mesin)) = 'CUT'
      AND DATE(tgl_update) BETWEEN :tanggal_awal AND :tanggal_akhir
    GROUP BY DATE(tgl_update)
    ORDER BY DATE(tgl_update)
";

$stmtTrenCUT = $conn->prepare($sqlTrenCUT);
$stmtTrenCUT->execute([
    ':tanggal_awal' => $startDate,
    ':tanggal_akhir' => $endDate
]);

$dataTrenCUT = $stmtTrenCUT->fetchAll(PDO::FETCH_KEY_PAIR);

/* =========================================================
   DETAIL PRODUKSI PER WO DAN TANGGAL
========================================================= */

$sqlDetailProduksi = "
    SELECT
        no_wo,
        DATE(tgl_update) AS tanggal,
        COALESCE(m2_ok, 0) AS luas
    FROM produksi
    WHERE UPPER(TRIM(nama_mesin)) = 'CUT'
      AND DATE(tgl_update) BETWEEN :tanggal_awal AND :tanggal_akhir
    ORDER BY DATE(tgl_update), no_wo
";

$stmtDetailProduksi = $conn->prepare($sqlDetailProduksi);
$stmtDetailProduksi->execute([
    ':tanggal_awal' => $startDate,
    ':tanggal_akhir' => $endDate
]);

$dataDetailProduksi = $stmtDetailProduksi->fetchAll(PDO::FETCH_ASSOC);

// Membentuk seluruh tanggal dalam periode,
// termasuk tanggal yang tidak memiliki produksi.
$labelsTren = [];
$nilaiTren = [];
$targetTren = [];

$tanggalMulai = new DateTimeImmutable($startDate);
$tanggalSelesai = new DateTimeImmutable($endDate);

$periode = new DatePeriod(
    $tanggalMulai,
    new DateInterval('P1D'),
    $tanggalSelesai->modify('+1 day')
);

$tanggalKeys = [];
foreach ($periode as $tanggal) {
    $tanggalSQL = $tanggal->format('Y-m-d');
    $tanggalKeys[] = $tanggalSQL;

    $labelsTren[] = $tanggal->format('j');
    $nilaiTren[] = (float) ($dataTrenCUT[$tanggalSQL] ?? 0);
    $targetTren[] = $kapasitasHarian;
}

/* =========================================================
   TAB 2 - PENGGUNAAN BAHAN PER NAMA BARANG
========================================================= */

$sqlPenggunaanBahan = "
    SELECT
        bp.id_barang,
        mb.nama_barang,
        COALESCE(SUM(bp.keluar), 0) AS total_keluar,
        COALESCE(mb.m2, 0) AS luas_per_barang,
        COALESCE(SUM(bp.keluar * COALESCE(mb.m2, 0)), 0)
            AS total_luas
    FROM bahan_potong bp
    INNER JOIN master_barang mb
        ON mb.id_barang = bp.id_barang
    WHERE DATE(bp.tgl_trans) BETWEEN :tanggal_awal AND :tanggal_akhir
      AND bp.id_barang <> 'KACASISA'
      AND LOWER(mb.nama_barang) NOT LIKE '%kaca sisa%'
    GROUP BY
        bp.id_barang,
        mb.nama_barang,
        mb.m2
    ORDER BY total_luas DESC
";

$stmtPenggunaanBahan = $conn->prepare($sqlPenggunaanBahan);
$stmtPenggunaanBahan->execute([
    ':tanggal_awal' => $startDate,
    ':tanggal_akhir' => $endDate
]);

$dataPenggunaanBahan = $stmtPenggunaanBahan->fetchAll(PDO::FETCH_ASSOC);

$totalKeluarBahan = 0;
$totalLuasBahan = 0;

foreach ($dataPenggunaanBahan as $bahan) {
    $totalKeluarBahan += (float) $bahan['total_keluar'];
    $totalLuasBahan += (float) $bahan['total_luas'];
}

/* =========================================================
   DETAIL PENGGUNAAN BAHAN PER HARI
========================================================= */

$sqlDetailBahan = "
    SELECT
        bp.id_barang,
        DATE(bp.tgl_trans) AS tanggal,
        COALESCE(SUM(bp.keluar), 0) AS total_keluar,
        COALESCE(
            SUM(bp.keluar * COALESCE(mb.m2, 0)),
            0
        ) AS total_luas
    FROM bahan_potong bp
    INNER JOIN master_barang mb
        ON mb.id_barang = bp.id_barang
    WHERE DATE(bp.tgl_trans) BETWEEN :tanggal_awal AND :tanggal_akhir
      AND bp.id_barang <> 'KACASISA'
      AND LOWER(mb.nama_barang) NOT LIKE '%kaca sisa%'
    GROUP BY bp.id_barang, DATE(bp.tgl_trans)
    ORDER BY bp.id_barang, DATE(bp.tgl_trans)
";

$stmtDetailBahan = $conn->prepare($sqlDetailBahan);
$stmtDetailBahan->execute([
    ':tanggal_awal' => $startDate,
    ':tanggal_akhir' => $endDate
]);

$dataDetailBahan = $stmtDetailBahan->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- =====================================================
     POPUP CARD 3
===================================================== -->

<div id="ratioModal" class="ratio-modal">
    <div class="ratio-modal-backdrop" onclick="closeRatioModal()"></div>

    <div class="ratio-modal-content">

        <!-- HEADER -->
        <div class="ratio-modal-header">
            <div>
                <h3>
                    <i class="bi bi-calculator"></i>
                    Rasio Penggunaan Bahan
                </h3>
                <p>
                    Periode:
                    <?= htmlspecialchars($startDate) ?>
                    s.d.
                    <?= htmlspecialchars($endDate) ?>
                </p>
            </div>

            <button type="button"
                    class="ratio-modal-close"
                    onclick="closeRatioModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- TAB NAVIGATION -->
        <div class="ratio-tabs">
            <button type="button"
                    class="ratio-tab active"
                    onclick="switchRatioTab('tren', this)">
                <i class="bi bi-graph-up"></i>
                Tren Produksi Harian
            </button>

            <button type="button"
                    class="ratio-tab"
                    onclick="switchRatioTab('bahan', this)">
                <i class="bi bi-box-seam"></i>
                Penggunaan Bahan
            </button>
        </div>

        <!-- TAB 1 -->
        <div id="ratioTabTren" class="ratio-tab-content active">

            <div class="ratio-summary">
                <div>
                    <span class="ratio-summary-label">
                        Kapasitas Target Harian
                    </span>
                    <strong><?= number_format($kapasitasHarian, 0, ',', '.') ?> m²</strong>
                </div>

                <div>
                    <span class="ratio-summary-label">
                        Total Produksi CUT
                    </span>
                    <strong>
                        <?= number_format(
                            array_sum($nilaiTren),
                            2,
                            ',',
                            '.'
                        ) ?> m²
                    </strong>
                </div>
            </div>

            <div class="ratio-chart-container">
                <canvas id="ratioTrendChart"></canvas>
            </div>

            <!-- DETAIL PRODUKSI BERDASARKAN TITIK GRAFIK -->
            <div class="ratio-detail-section" id="ratioProductionDetail">

                <div class="ratio-detail-heading">
                    <h4>Detail Produksi Harian</h4>
                    <span id="ratioSelectedDate">
                        Klik titik grafik untuk melihat detail
                    </span>
                </div>

                <div class="ratio-table-container">
                    <table class="ratio-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>No. WO</th>
                                <th>Tanggal</th>
                                <th>Luas Produksi</th>
                            </tr>
                        </thead>

                        <tbody id="ratioProductionDetailBody">
                            <tr>
                                <td colspan="4" class="ratio-empty">
                                    Pilih salah satu titik pada grafik.
                                </td>
                            </tr>
                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="3">TOTAL LUAS</th>
                                <th id="ratioProductionTotal">0,00 m²</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>

            <div class="ratio-chart-note">
                <span class="ratio-legend actual"></span>
                Produksi Aktual

                <span class="ratio-legend target"></span>
                Target Kapasitas <?= number_format($kapasitasHarian, 0, ',', '.') ?> m²/hari
            </div>

        </div>

        <!-- TAB 2 -->
        <div id="ratioTabBahan" class="ratio-tab-content">

            <div class="ratio-summary">
                <div>
                    <span class="ratio-summary-label">
                        Total Barang Keluar
                    </span>
                    <strong>
                        <?= number_format($totalKeluarBahan, 0, ',', '.') ?>
                    </strong>
                </div>

                <div>
                    <span class="ratio-summary-label">
                        Total Luas Bahan
                    </span>
                    <strong>
                        <?= number_format($totalLuasBahan, 2, ',', '.') ?>
                        m²
                    </strong>
                </div>
            </div>

            <div class="ratio-table-container">
                <table class="ratio-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>ID Barang</th>
                            <th>Nama Barang</th>
                            <th>Jumlah Keluar</th>
                            <th>Luas / Barang</th>
                            <th>Total Luas</th>
                            <th>Detail</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($dataPenggunaanBahan)): ?>

                            <tr>
                                <td colspan="7" class="ratio-empty">
                                    Tidak ada data penggunaan bahan.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($dataPenggunaanBahan as $i => $bahan): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>

                                    <td>
                                        <?= htmlspecialchars($bahan['id_barang']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($bahan['nama_barang']) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $bahan['total_keluar'],
                                            0,
                                            ',',
                                            '.'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $bahan['luas_per_barang'],
                                            4,
                                            ',',
                                            '.'
                                        ) ?> m²
                                    </td>

                                    <td>
                                        <strong>
                                            <?= number_format(
                                                (float) $bahan['total_luas'],
                                                2,
                                                ',',
                                                '.'
                                            ) ?> m²
                                        </strong>
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="ratio-material-detail-btn"
                                            onclick='toggleMaterialDetail(<?= json_encode(
                                                (string) $bahan['id_barang'],
                                                JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP
                                            ) ?>)'>
                                            <i class="bi bi-eye"></i> Detail
                                        </button>
                                    </td>
                                </tr>

                                <tr id="materialDetailRow_<?= htmlspecialchars($bahan['id_barang'], ENT_QUOTES) ?>"
                                    class="ratio-material-detail-row"
                                    style="display: none;">

                                    <td colspan="7">
                                        <div class="ratio-material-detail-content">
                                            <h4>
                                                <i class="bi bi-calendar3"></i>
                                                Riwayat Penggunaan:
                                                <?= htmlspecialchars($bahan['nama_barang']) ?>
                                            </h4>

                                            <div class="ratio-table-container">
                                                <table class="ratio-table">
                                                    <thead>
                                                        <tr>
                                                            <th>No.</th>
                                                            <th>Tanggal</th>
                                                            <th>Jumlah Keluar</th>
                                                            <th>Total Luas</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody id="materialDetailBody_<?= htmlspecialchars($bahan['id_barang'], ENT_QUOTES) ?>">
                                                        <tr>
                                                            <td colspan="4" class="ratio-empty">
                                                                Memuat detail...
                                                            </td>
                                                        </tr>
                                                    </tbody>

                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="2">TOTAL</th>
                                                            <th id="materialTotalKeluar_<?= htmlspecialchars($bahan['id_barang'], ENT_QUOTES) ?>">0</th>
                                                            <th id="materialTotalLuas_<?= htmlspecialchars($bahan['id_barang'], ENT_QUOTES) ?>">0,00 m²</th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>
                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="3">TOTAL</th>
                            <th><?= number_format($totalKeluarBahan, 0, ',', '.') ?></th>
                            <th>-</th>
                            <th><?= number_format($totalLuasBahan, 2, ',', '.') ?> m²</th>
                            <th>-</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>

    </div>
</div>

<script>
/* =========================================================
   DATA GRAFIK DARI PHP
========================================================= */

const ratioTrendLabels = <?= json_encode($labelsTren) ?>;
const ratioTrendValues = <?= json_encode($nilaiTren) ?>;
const ratioTargetValues = <?= json_encode($targetTren) ?>;

let ratioTrendChart = null;

/* =========================================================
   MEMBUKA DAN MENUTUP POPUP
========================================================= */

function openRatioModal() {
    const modal = document.getElementById('ratioModal');

    if (!modal) return;

    modal.classList.add('show');
    document.body.style.overflow = 'hidden';

    // Render setelah modal tampil agar ukuran canvas terbaca dengan benar.
    requestAnimationFrame(() => {
        renderRatioTrendChart();
    });
}

function closeRatioModal() {
    const modal = document.getElementById('ratioModal');

    if (!modal) return;

    modal.classList.remove('show');
    document.body.style.overflow = '';
}

/* =========================================================
   PERPINDAHAN TAB
========================================================= */

function switchRatioTab(tab, button) {
    document.querySelectorAll('.ratio-tab').forEach(el => {
        el.classList.remove('active');
    });

    document.querySelectorAll('.ratio-tab-content').forEach(el => {
        el.classList.remove('active');
    });

    button.classList.add('active');

    if (tab === 'tren') {
        document.getElementById('ratioTabTren').classList.add('active');

        setTimeout(() => {
            renderRatioTrendChart();
        }, 100);
    } else {
        document.getElementById('ratioTabBahan').classList.add('active');
    }
}

/* =========================================================
   GRAFIK TREN PRODUKSI CUT
========================================================= */

function renderRatioTrendChart() {
    const canvas = document.getElementById('ratioTrendChart');

    if (!canvas) return;

    if (ratioTrendChart) {
        ratioTrendChart.destroy();
    }

    ratioTrendChart = new Chart(canvas, {
        type: 'line',

        data: {
            labels: ratioTrendLabels,

            datasets: [
                {
                    label: 'Produksi Aktual',
                    data: ratioTrendValues,

                    borderColor: '#0878df',
                    backgroundColor: 'rgba(8, 120, 223, 0.08)',

                    borderWidth: 2.5,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointHitRadius: 14,

                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#0878df',
                    pointBorderWidth: 2,

                    fill: true,
                    tension: 0.25
                },
                {
                    label: 'Target Kapasitas',
                    data: ratioTargetValues,

                    borderColor: '#f59e0b',
                    borderWidth: 2,
                    borderDash: [7, 5],

                    pointRadius: 0,
                    pointHoverRadius: 0,

                    fill: false,
                    tension: 0
                }
            ]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            interaction: {
                intersect: true,
                mode: 'nearest'
            },

            // Klik titik pada dataset Produksi Aktual untuk menampilkan detail.
            onClick: function(event, elements) {
                if (!elements || elements.length === 0) return;

                const titik = elements.find(item => item.datasetIndex === 0);
                if (!titik) return;

                showProductionDetail(titik.index);
            },

            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },

                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' +
                                Number(context.raw || 0).toLocaleString('id-ID', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }) + ' m²';
                        }
                    }
                }
            },

            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Tanggal'
                    },

                    grid: {
                        color: '#edf1f7'
                    }
                },

                y: {
                    beginAtZero: true,

                    title: {
                        display: true,
                        text: 'Kapasitas (m²)'
                    },

                    grid: {
                        color: '#e6edf6'
                    },

                    ticks: {
                        callback: function(value) {
                            return Number(value).toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeRatioModal();
    }
});

/* =========================================================
   DATA DETAIL PRODUKSI DAN BAHAN
========================================================= */

const ratioTanggalKeys = <?= json_encode(
    $tanggalKeys,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const ratioDetailProduksi = <?= json_encode(
    $dataDetailProduksi,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const ratioDetailBahan = <?= json_encode(
    $dataDetailBahan,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;


/* =========================================================
   FORMAT ANGKA
========================================================= */

function ratioFormatNumber(value, decimal = 2) {
    return Number(value || 0).toLocaleString('id-ID', {
        minimumFractionDigits: decimal,
        maximumFractionDigits: decimal
    });
}

function ratioEscapeHTML(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);
}

function ratioFormatDate(date) {
    if (!date) return '-';

    const parts = date.split('-');

    if (parts.length !== 3) return date;

    return new Date(
        Number(parts[0]),
        Number(parts[1]) - 1,
        Number(parts[2])
    ).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
}


/* =========================================================
   DETAIL PRODUKSI SAAT TITIK GRAFIK DIKLIK
========================================================= */

function showProductionDetail(index) {
    const tanggal = ratioTanggalKeys[index];

    if (!tanggal) return;

    const data = ratioDetailProduksi.filter(row =>
        row.tanggal === tanggal
    );

    const tbody = document.getElementById(
        'ratioProductionDetailBody'
    );

    const tanggalLabel = document.getElementById(
        'ratioSelectedDate'
    );

    const totalElement = document.getElementById(
        'ratioProductionTotal'
    );

    tanggalLabel.textContent = ratioFormatDate(tanggal);

    let total = 0;

    if (data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="ratio-empty">
                    Tidak ada data produksi pada tanggal ini.
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML = data.map((row, i) => {
            const luas = Number(row.luas || 0);
            total += luas;

            return `
                <tr>
                    <td>${i + 1}</td>
                    <td>${ratioEscapeHTML(row.no_wo)}</td>
                    <td>${ratioFormatDate(row.tanggal)}</td>
                    <td>
                        <strong>${ratioFormatNumber(luas)} m²</strong>
                    </td>
                </tr>
            `;
        }).join('');
    }

    totalElement.textContent =
        ratioFormatNumber(total) + ' m²';
}

/* =========================================================
   DETAIL PENGGUNAAN BAHAN PER BARANG
========================================================= */

function toggleMaterialDetail(idBarang) {
    const row = document.getElementById(
        'materialDetailRow_' + idBarang
    );

    if (!row) return;

    const isOpen = row.style.display !== 'none';

    // Tutup detail yang sedang terbuka
    document.querySelectorAll('.ratio-material-detail-row')
        .forEach(item => {
            item.style.display = 'none';
        });

    if (isOpen) return;

    row.style.display = 'table-row';

    const data = ratioDetailBahan.filter(item =>
        String(item.id_barang) === String(idBarang)
    );

    const tbody = document.getElementById(
        'materialDetailBody_' + idBarang
    );

    const totalKeluarElement = document.getElementById(
        'materialTotalKeluar_' + idBarang
    );

    const totalLuasElement = document.getElementById(
        'materialTotalLuas_' + idBarang
    );

    let totalKeluar = 0;
    let totalLuas = 0;

    if (data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="ratio-empty">
                    Tidak ada detail penggunaan bahan.
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML = data.map((item, index) => {
            const keluar = Number(item.total_keluar || 0);
            const luas = Number(item.total_luas || 0);

            totalKeluar += keluar;
            totalLuas += luas;

            return `
                <tr>
                    <td>${index + 1}</td>
                    <td>${ratioFormatDate(item.tanggal)}</td>
                    <td>${ratioFormatNumber(keluar, 0)}</td>
                    <td>
                        <strong>${ratioFormatNumber(luas)} m²</strong>
                    </td>
                </tr>
            `;
        }).join('');
    }

    totalKeluarElement.textContent =
        ratioFormatNumber(totalKeluar, 0);

    totalLuasElement.textContent =
        ratioFormatNumber(totalLuas) + ' m²';
}
</script>

<style>
/* =========================================================
   POPUP CARD 3
========================================================= */

.ratio-modal {
    position: fixed;
    inset: 0;
    z-index: 10001;

    display: none;
    align-items: center;
    justify-content: center;

    padding: 24px;
}

.ratio-modal.show {
    display: flex;
}

.ratio-modal-backdrop {
    position: absolute;
    inset: 0;

    background: rgba(15, 23, 42, 0.58);
    backdrop-filter: blur(4px);
}

.ratio-modal-content {
    position: relative;
    z-index: 1;

    display: flex;
    flex-direction: column;

    width: 100%;
    max-width: 1150px;
    height: 88vh;
    max-height: 850px;
    min-height: 0;

    background: #ffffff;
    border-radius: 16px;

    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

/* HEADER */

.ratio-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    padding: 22px 26px;
    border-bottom: 1px solid #e8edf4;
}

.ratio-modal-header h3 {
    margin: 0;
    color: #172b4d;
    font-size: 19px;
    font-weight: 700;
}

.ratio-modal-header h3 i {
    color: #0878df;
    margin-right: 7px;
}

.ratio-modal-header p {
    margin: 6px 0 0;
    color: #718096;
    font-size: 13px;
}

.ratio-modal-close {
    width: 36px;
    height: 36px;

    border: 0;
    border-radius: 9px;

    background: #f1f5f9;
    color: #475569;

    cursor: pointer;
}

/* TABS */

.ratio-tabs {
    display: flex;
    gap: 8px;

    padding: 14px 26px 0;
    border-bottom: 1px solid #e8edf4;
}

.ratio-tab {
    padding: 12px 18px;

    border: 0;
    border-bottom: 3px solid transparent;

    background: transparent;
    color: #718096;

    font-size: 13px;
    font-weight: 600;

    cursor: pointer;
}

.ratio-tab.active {
    color: #0878df;
    border-bottom-color: #0878df;
}

.ratio-tab i {
    margin-right: 6px;
}

.ratio-tab-content {
    display: none;
    flex: 1;
    min-height: 0;

    padding: 22px 26px;
    overflow-y: auto;
}

.ratio-tab-content.active {
    display: block;
}

/* SUMMARY */

.ratio-summary {
    display: flex;
    gap: 16px;
    margin-bottom: 22px;
}

.ratio-summary > div {
    flex: 1;

    padding: 16px 18px;

    background: #f8fafc;
    border: 1px solid #e8edf4;
    border-radius: 12px;
}

.ratio-summary-label {
    display: block;
    margin-bottom: 7px;

    color: #718096;
    font-size: 12px;
}

.ratio-summary strong {
    color: #172b4d;
    font-size: 20px;
}

/* CHART */

.ratio-chart-container {
    position: relative;
    width: 100%;
    height: 380px;
}

.ratio-chart-note {
    display: flex;
    align-items: center;
    gap: 8px;

    margin-top: 14px;

    color: #718096;
    font-size: 12px;
}

.ratio-legend {
    width: 22px;
    height: 3px;
    display: inline-block;
}

.ratio-legend.actual {
    background: #0878df;
}

.ratio-legend.target {
    margin-left: 16px;
    background: #f59e0b;
}

/* TABLE */

.ratio-table-container {
    width: 100%;
    overflow-x: auto;
}

.ratio-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.ratio-table th,
.ratio-table td {
    padding: 13px 14px;
    border-bottom: 1px solid #e8edf4;
    text-align: left;
    white-space: nowrap;
}

.ratio-table thead th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
}

.ratio-table tbody tr:hover {
    background: #f8fafc;
}

.ratio-table td strong {
    color: #0878df;
}

.ratio-table tfoot th {
    background: #f1f5f9;
    color: #172b4d;
}

.ratio-empty {
    padding: 35px !important;
    text-align: center !important;
    color: #718096;
}

/* RESPONSIVE */

@media (max-width: 768px) {
    .ratio-modal {
        padding: 10px;
    }

    .ratio-modal-content {
        height: 94vh;
    }

    .ratio-modal-header {
        padding: 17px;
    }

    .ratio-tabs {
        padding-left: 14px;
        padding-right: 14px;
    }

    .ratio-tab {
        padding: 11px 9px;
        font-size: 11px;
    }

    .ratio-tab-content {
        padding: 16px;
    }

    .ratio-summary {
        flex-direction: column;
    }

    .ratio-chart-container {
        height: 300px;
    }
}

/* DETAIL PRODUKSI DI BAWAH GRAFIK */

.ratio-detail-section {
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid #e8edf4;
}

.ratio-detail-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}

.ratio-detail-heading h4 {
    margin: 0;
    color: #172b4d;
    font-size: 15px;
    font-weight: 700;
}

.ratio-detail-heading span {
    color: #0878df;
    font-size: 12px;
    font-weight: 600;
}

/* BUTTON DETAIL BAHAN */

.ratio-material-detail-btn {
    padding: 7px 11px;
    border: 1px solid #0878df;
    border-radius: 7px;
    background: #eff6ff;
    color: #0878df;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.ratio-material-detail-btn:hover {
    background: #0878df;
    color: white;
}

/* ROW DETAIL BAHAN */

.ratio-material-detail-row > td {
    padding: 16px !important;
    background: #f8fafc;
}

.ratio-material-detail-content {
    padding: 16px;
    background: white;
    border: 1px solid #e8edf4;
    border-radius: 10px;
}

.ratio-material-detail-content h4 {
    margin: 0 0 14px;
    color: #334155;
    font-size: 13px;
    font-weight: 700;
}
</style>

/* AREA KOTAK ORANYE PEMBUKA POPUP */
.production-ratio-detail {
    cursor: pointer;
    position: relative;
    z-index: 2;
    pointer-events: auto;
    transition: background-color .2s ease, border-color .2s ease, transform .2s ease;
}
.production-ratio-detail:hover {
    background-color: #ffdfc7 !important;
    border-color: #fb923c !important;
}
.production-ratio-detail:focus-visible {
    outline: 3px solid rgba(251, 146, 60, .45);
    outline-offset: 3px;
}