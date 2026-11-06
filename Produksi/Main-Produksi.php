<?php
$pageTitle = 'Dashboard Produksi';
$activePage = 'produksi';
$basePath = '../';

require_once __DIR__ . '/data-produksi.php';
require_once __DIR__ . '/../includes/header.php';

// Filter tanggal
$startDate = $_GET['tanggal_awal'] ?? date('Y-m-01');
$endDate = $_GET['tanggal_akhir'] ?? date('Y-m-d');

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    $startDate = date('Y-m-01');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $endDate = date('Y-m-d');
}
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- CSS Dashboard Produksi -->
<link rel="stylesheet" href="prod.css">

<main class="production-dashboard">

    <!-- ==================================================
         HEADER DASHBOARD
    =================================================== -->

    <section class="production-heading">

        <div class="production-heading-info">
            <div class="production-title">

                <div class="heading-icon">
                    <i class="bi bi-clipboard2-data-fill"></i>
                </div>

                <h1>Dashboard Laporan Produksi — AQUAMATE</h1>

            </div>
        </div>

        <div class="production-actions">

            <button
                type="button"
                class="production-btn"
                onclick="location.reload()"
            >
                <i class="bi bi-arrow-clockwise"></i>
                Refresh Data
            </button>

            <button
                type="button"
                class="production-btn"
                onclick="exportExcel()"
            >
                <i class="bi bi-file-earmark-excel"></i>
                Export XLS
            </button>

            <button
                type="button"
                class="production-btn primary"
                onclick="window.print()"
            >
                <i class="bi bi-printer"></i>
                Print Laporan
            </button>

        </div>

    </section>


    <!-- ==================================================
         FILTER PERIODE
    =================================================== -->

    <section class="production-filter">

        <div class="production-filter-title">
            <i class="bi bi-calendar3"></i>
            Periode Tanggal Transaksi
        </div>

        <form method="GET" class="production-filter-form">

            <input
                type="date"
                name="tanggal_awal"
                id="tanggalAwal"
                value="<?= htmlspecialchars($startDate) ?>"
            >

            <span class="separator">s/d</span>

            <input
                type="date"
                name="tanggal_akhir"
                id="tanggalAkhir"
                value="<?= htmlspecialchars($endDate) ?>"
            >

            <button
                type="submit"
                class="production-filter-submit"
            >
                Tampilkan
            </button>

            <label class="production-today">

                <input
                    type="checkbox"
                    id="todayOnly"
                    <?= (
                        $startDate === date('Y-m-d') &&
                        $endDate === date('Y-m-d')
                    ) ? 'checked' : '' ?>
                >

                <i class="bi bi-calendar-check"></i>
                Hari Ini Saja

            </label>

        </form>

    </section>


    <!-- ==================================================
         GRID KARTU DASHBOARD
    =================================================== -->

    <section class="production-grid">

        <!-- ==============================================
             1. WO BELUM DIOPTIMASI
        =============================================== -->

        <div class="production-card production-summary-card blue-outline">

            <div class="production-card-heading">

                <h3>
                    <i class="bi bi-clipboard-data"></i>
                    WO BELUM DIOPTIMASI
                </h3>

                <span class="production-total">
                    Total:
                    <?= number_format($woTotal, 0, ',', '.') ?>
                    WO
                </span>

            </div>

            <div class="production-donut-content">

                <div class="production-donut-wrap">

                    <canvas id="woChart"></canvas>

                    <div class="production-donut-center">

                        <strong>
                            <?= number_format($woTotal, 0, ',', '.') ?>
                        </strong>

                        <span>WO</span>

                    </div>

                </div>

                <div class="production-legend">

                    <?php foreach ($woData as $item): ?>

                        <?php
                        $percentage = $woTotal > 0
                            ? round(($item['value'] / $woTotal) * 100)
                            : 0;
                        ?>

                        <div class="production-legend-item">

                            <span
                                class="production-legend-dot"
                                style="background: <?= htmlspecialchars($item['color']) ?>"
                            ></span>

                            <span class="production-legend-label">
                                <?= htmlspecialchars($item['label']) ?>
                            </span>

                            <span class="production-legend-value">
                                <?= number_format($item['value'], 0, ',', '.') ?>
                            </span>

                            <span class="production-legend-percent">
                                (<?= $percentage ?>%)
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>


        <!-- ==============================================
             2. PERMINTAAN DAN PENYERAHAN BAHAN
        =============================================== -->

        <div class="production-card production-summary-card green-outline">

            <div class="production-card-heading">

                <h3>
                    <i class="bi bi-box-seam"></i>
                    PERMINTAAN & PENYERAHAN BAHAN
                </h3>

                <span class="production-total green">
                    Total:
                    <?= number_format($pbTotal, 0, ',', '.') ?>
                    PB
                </span>

            </div>

            <div class="production-donut-content">

                <div class="production-donut-wrap">

                    <canvas id="pbChart"></canvas>

                    <div class="production-donut-center">

                        <strong>
                            <?= number_format($pbTotal, 0, ',', '.') ?>
                        </strong>

                        <span>PB</span>

                    </div>

                </div>

                <div class="production-legend">

                    <?php foreach ($pbData as $item): ?>

                        <?php
                        $percentage = $pbTotal > 0
                            ? round(($item['value'] / $pbTotal) * 100)
                            : 0;
                        ?>

                        <div class="production-legend-item">

                            <span
                                class="production-legend-dot"
                                style="background: <?= htmlspecialchars($item['color']) ?>"
                            ></span>

                            <span class="production-legend-label">
                                <?= htmlspecialchars($item['label']) ?>
                            </span>

                            <span class="production-legend-value">
                                <?= number_format($item['value'], 0, ',', '.') ?>
                            </span>

                            <span class="production-legend-percent">
                                (<?= $percentage ?>%)
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>


        <!-- ==============================================
             3. RASIO PENGGUNAAN BAHAN
        =============================================== -->

        <div class="production-card production-ratio-card orange-outline">

            <div class="production-card-heading">
                <h3>
                    <i class="bi bi-calculator"></i>
                    RASIO PENGGUNAAN BAHAN
                </h3>

                <span class="production-target">
                    Target ≤ 1.20
                </span>
            </div>

            <div class="production-ratio-content">

                <div>

                    <div class="production-ratio-main">

                        <span class="production-ratio-number">
                            <?= number_format($rasio, 2, ',', '.') ?>
                        </span>

                        <span class="production-efficiency">
                            <?php if ($statusRasio === 'Efisien'): ?>
                                <i class="bi bi-check-circle"></i>
                            <?php else: ?>
                                <i class="bi bi-exclamation-circle"></i>
                            <?php endif; ?>

                            <?= htmlspecialchars($statusRasio) ?>
                            (Yield <?= number_format($yield, 1, ',', '.') ?>%)
                        </span>

                    </div>

                    <div class="production-ratio-description">
                        Rasio = Luas Bahan (m²) ÷ Luas Potong (m²)
                    </div>

                </div>

            </div>

            <div class="production-ratio-detail">

                <div>
                    <span>Jml Bahan:</span>
                    <strong>
                        <?= number_format($luasBahan, 2, ',', '.') ?> m²
                    </strong>
                </div>

                <div>
                    <span>Jml Potong:</span>
                    <strong>
                        <?= number_format($luasPotong, 2, ',', '.') ?> m²
                    </strong>
                </div>

                <div>
                    <span>Offcut/Trim:</span>
                    <strong>
                        <?= number_format($offcut, 2, ',', '.') ?> m²
                    </strong>
                </div>

            </div>

        </div>


        <!-- ==============================================
             4. GRAFIK PENCAPAIAN PRODUKSI
        =============================================== -->

        <div class="production-card production-chart-card">

            <div class="production-card-heading">

                <h3 class="production-chart-title">

                    <span class="production-chart-icon">
                        <i class="bi bi-bar-chart-fill"></i>
                    </span>

                    Grafik % Pencapaian Produksi / Kapasitas

                </h3>

                <span class="production-average">
                    Rata-rata: <?= number_format($pencapaianAvg, 1, ',', '.') ?>%
                </span>

            </div>

            <div class="production-chart-container">
                <canvas id="productionChart"></canvas>
            </div>

            <?php if (!$pencapaianSiap): ?>
                <div style="margin-top:10px;font-size:12px;color:#8a6d00;">
                    Kapasitas mesin belum diisi (file kapasitas-mesin.php).
                </div>
            <?php elseif ($kapasitasPerkiraan): ?>
                <div style="margin-top:10px;font-size:12px;color:#8a6d00;">
                    Kapasitas masih perkiraan (puncak harian historis), bukan kapasitas resmi.
                    Dihitung atas <?= (int)$hariKerja ?> hari kerja (Senin–Sabtu).
                </div>
            <?php endif; ?>

        </div>


        <!-- ==============================================
             5. GRAFIK REJECT MESIN
        =============================================== -->

        <div class="production-card production-chart-card">

            <div class="production-card-heading">

                <h3 class="production-chart-title">

                    <span class="production-chart-icon danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </span>

                    Grafik % Reject Mesin

                </h3>

                <span class="production-average danger">
                    Rata-rata: <?= number_format($rejectAvg, 1, ',', '.') ?>%
                </span>

            </div>

            <div class="production-chart-container">
                <canvas id="rejectChart"></canvas>
            </div>

        </div>

    </section>

</main>


<!-- ==================================================
     POPUP CARD 
================================================== -->

<?php
require_once __DIR__ . '/POPCARD1.php';
require_once __DIR__ . '/POPCARD2.php';
require_once __DIR__ . '/PopCard4.php';
require_once __DIR__ . '/PopCard5.php';
?>



<!-- ==================================================
     JAVASCRIPT DASHBOARD PRODUKSI
================================================== -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ======================================
    // DATA DARI PHP
    // ======================================

    // Kode mesin di database, urutannya sama dengan label grafik (dipakai popup klik bar)
    const mesinKode  = <?= json_encode($mesinKode) ?>;
    const mesinLabel = <?= json_encode($mesinLabel) ?>;

    const woValues = <?= json_encode(array_column($woData, 'value')) ?>;
    const woColors = <?= json_encode(array_column($woData, 'color')) ?>;
    const woLabels = <?= json_encode(array_column($woData, 'label')) ?>;

    const pbValues = <?= json_encode(array_column($pbData, 'value')) ?>;
    const pbColors = <?= json_encode(array_column($pbData, 'color')) ?>;
    const pbLabels = <?= json_encode(array_column($pbData, 'label')) ?>;


    // ======================================
    // KONFIGURASI UMUM CHART
    // ======================================

    Chart.defaults.font.family = "'DM Sans', sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#65738a';


    // ======================================
    // FUNGSI DONUT CHART
    // ======================================
    // onSliceClick hanya dijalankan jika
    // callback diberikan pada chart tersebut.
    // Jadi PB tidak membuka popup WO.

    function createDonutChart(
        elementId,
        labels,
        values,
        colors,
        onSliceClick = null
    ) {

        const canvas = document.getElementById(elementId);

        if (!canvas) {
            return;
        }

        return new Chart(canvas, {

            type: 'doughnut',

            data: {
                labels: labels,

                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 7
                }]
            },

            options: {

                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',

                onClick: function (event, elements, chart) {

                    if (
                        elements.length > 0 &&
                        typeof onSliceClick === 'function'
                    ) {

                        const index = elements[0].index;
                        const kategori = chart.data.labels[index];

                        onSliceClick(kategori);
                    }

                },

                plugins: {

                    legend: {
                        display: false
                    },

                    tooltip: {
                        enabled: true
                    }

                }

            }

        });

    }


    // ======================================
    // JALANKAN DONUT CHART
    // ======================================

    // Card 1: klik irisan membuka popup detail WO
    createDonutChart(
        'woChart',
        woLabels,
        woValues,
        woColors,
        openWODetail
    );

    // Card 2: klik irisan membuka popup detail PB-SB
    createDonutChart(
        'pbChart',
        pbLabels,
        pbValues,
        pbColors,
        openPBDetail
    );


    // ======================================
    // GRAFIK PENCAPAIAN PRODUKSI
    // ======================================

    const productionCanvas = document.getElementById('productionChart');

    if (productionCanvas) {

        new Chart(productionCanvas, {

            type: 'bar',

            data: {

                labels: <?= json_encode($mesinLabel) ?>,

                datasets: [{

                    label: 'Pencapaian Produksi',

                    data: <?= json_encode($pencapaianValues) ?>,

                    backgroundColor: '#0866c6',

                    borderRadius: 4,

                    barPercentage: 0.58

                }]

            },

            options: {

                responsive: true,
                maintainAspectRatio: false,

                // Klik di mana saja pada kolom mesin (termasuk bar bernilai 0)
                interaction: {
                    mode: 'index',
                    intersect: false
                },

                onClick: function (event, elements) {

                    if (elements.length > 0) {

                        const i = elements[0].index;

                        openCPDetail(mesinKode[i], mesinLabel[i]);
                    }

                },

                onHover: function (event, elements) {

                    event.native.target.style.cursor =
                        elements.length > 0 ? 'pointer' : 'default';

                },

                plugins: {

                    legend: {
                        display: false
                    },

                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.parsed.y + '%';
                            }
                        }
                    }

                },

                scales: {

                    y: {

                        min: 0,
                        max: <?= (int)$pencapaianMax ?>,

                        ticks: {

                            callback: function (value) {
                                return value + '%';
                            }

                        },

                        grid: {
                            color: '#e6edf6',
                            borderDash: [3, 3]
                        }

                    },

                    x: {

                        grid: {
                            display: false
                        }

                    }

                }

            }

        });

    }


    // ======================================
    // GRAFIK REJECT MESIN
    // ======================================

    const rejectCanvas = document.getElementById('rejectChart');

    if (rejectCanvas) {

        new Chart(rejectCanvas, {

            type: 'bar',

            data: {

                labels: <?= json_encode($mesinLabel) ?>,

                datasets: [{

                    label: 'Persentase Reject',

                    data: <?= json_encode($rejectValues) ?>,

                    backgroundColor: <?= json_encode($rejectColors) ?>,

                    borderRadius: 4,

                    barPercentage: 0.58

                }]

            },

            options: {

                responsive: true,
                maintainAspectRatio: false,

                // Klik di mana saja pada kolom mesin (termasuk bar bernilai 0)
                interaction: {
                    mode: 'index',
                    intersect: false
                },

                onClick: function (event, elements) {

                    if (elements.length > 0) {

                        const i = elements[0].index;

                        openRJDetail(mesinKode[i], mesinLabel[i]);
                    }

                },

                onHover: function (event, elements) {

                    event.native.target.style.cursor =
                        elements.length > 0 ? 'pointer' : 'default';

                },

                plugins: {

                    legend: {
                        display: false
                    },

                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.parsed.y + '%';
                            }
                        }
                    }

                },

                scales: {

                    y: {

                        min: 0,
                        max: <?= (int)$rejectMax ?>,

                        ticks: {

                            callback: function (value) {
                                return value + '%';
                            }

                        },

                        grid: {
                            color: '#e6edf6',
                            borderDash: [3, 3]
                        }

                    },

                    x: {

                        grid: {
                            display: false
                        }

                    }

                }

            }

        });

    }


    // ======================================
    // FILTER HARI INI
    // ======================================

    const todayCheckbox = document.getElementById('todayOnly');

    if (todayCheckbox) {

        todayCheckbox.addEventListener('change', function () {

            if (this.checked) {

                const today = new Date();

                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');

                const formattedDate = `${year}-${month}-${day}`;

                document.getElementById('tanggalAwal').value = formattedDate;
                document.getElementById('tanggalAkhir').value = formattedDate;
                document.getElementById('tanggalAwal').form.submit();

            }

        });

    }

});


// ======================================
// EXPORT EXCEL SEMENTARA
// ======================================

function exportExcel() {

    alert(
        'Fitur Export Excel akan diaktifkan setelah data produksi terhubung dengan database.'
    );

}
</script>


<?php
require_once __DIR__ . '/../includes/footer.php';
?>