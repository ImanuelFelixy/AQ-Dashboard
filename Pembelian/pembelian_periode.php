<?php
/**
 * pembelian_periode.php
 * Modul "Pembelian per Periode" (grafik garis harian + modal detail per tanggal).
 * Dipanggil dari main_pembelian.php.
 *
 * Tabel sumber:
 *  - mutasi_pembelian : baris pembelian per barang per NB
 *  - payable_aging    : sumber tanggal NB (lihat pembelian_tgl_nb_sql() di main_pembelian.php)
 *  - items            : kode_barang (dicocokkan lewat nama_barang)
 *  - kartu_stok       : jumlah stok (SUM sisa seluruh lokasi)
 */

/**
 * Ambil data grafik harian + detail pembelian pada rentang tanggal.
 *
 * @return array{labels: array, keys: array, data: array, detail: array}
 */
function getPurchasePeriodData(?mysqli $db, string $start, string $end): array
{
    $tglExpr = pembelian_tgl_nb_sql();

    $rows = pembelian_query(
        $db,
        "SELECT x.*
         FROM (
            SELECT m.no_trans,
                   m.nama_barang,
                   m.qty,
                   m.total_harga_rp,
                   {$tglExpr} AS tgl,
                   (SELECT MIN(i.kode_barang)
                      FROM items i
                     WHERE i.nama_barang = m.nama_barang) AS kode_barang,
                   (m.qty + (SELECT COALESCE(SUM(k.sisa), 0)
                      FROM kartu_stok k
                     WHERE k.kode_barang = (SELECT MIN(i2.kode_barang)
                                             FROM items i2
                                            WHERE i2.nama_barang = m.nama_barang))) AS jumlah_stok
            FROM mutasi_pembelian m
            LEFT JOIN payable_aging p
                ON REPLACE(m.no_trans, '/', '-') = p.no_pembelian
         ) x
         WHERE x.tgl BETWEEN ? AND ?
         ORDER BY x.tgl, x.no_trans, x.nama_barang",
        'ss',
        [$start, $end]
    );

    // Kerangka sumbu-X: satu titik per tanggal pada rentang filter (maks. 366 hari)
    $keys = [];
    try {
        $dStart = new DateTime($start);
        $dEnd   = new DateTime($end);
        $dEnd->modify('+1 day');
        $period = new DatePeriod($dStart, new DateInterval('P1D'), $dEnd);
        foreach ($period as $d) {
            $keys[] = $d->format('Y-m-d');
            if (count($keys) >= 366) {
                break;
            }
        }
    } catch (Exception $e) {
        $keys = [];
    }

    $sameMonth = $keys && substr($keys[0], 0, 7) === substr($keys[count($keys) - 1], 0, 7);
    $labels = [];
    foreach ($keys as $k) {
        $labels[] = $sameMonth ? (int) substr($k, 8, 2) : substr($k, 8, 2) . '/' . substr($k, 5, 2);
    }

    // Detail + jumlah transaksi (NB unik) per tanggal
    $detail   = [];
    $nbPerHari = [];
    foreach ($rows as $r) {
        $tgl = $r['tgl'];
        $nbPerHari[$tgl][$r['no_trans']] = true;

        $detail[] = [
            'tgl'           => $tgl,
            'kode_barang'   => $r['kode_barang'] !== null ? $r['kode_barang'] : '-',
            'nama_barang'   => $r['nama_barang'],
            'no_nb'         => $r['no_trans'],
            'tgl_pembelian' => date('d/m/Y', strtotime($tgl)),
            'nominal_nb'    => (float) $r['total_harga_rp'],
            'qty'           => (int) $r['qty'],
            'jumlah_stok'   => (int) $r['jumlah_stok'],
        ];
    }

    $data = [];
    foreach ($keys as $k) {
        $data[] = isset($nbPerHari[$k]) ? count($nbPerHari[$k]) : 0;
    }

    return [
        'labels' => $labels,
        'keys'   => $keys,
        'data'   => $data,
        'detail' => $detail,
    ];
}

/** Section grafik garis "Pembelian per Periode" */
function renderPembelianPeriodeChartSection(): void
{
    ?>
    <!-- Style khusus agar card melebar penuh (full width) mengikuti layout dashboard -->
    <style>
    .purchase-linechart-card {
        grid-column: 1 / -1 !important; /* Membentang penuh jika dashboard menggunakan CSS Grid */
        width: 100% !important;
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
        box-sizing: border-box;
    }
    .purchase-line-container {
        position: relative;
        width: 100%;
        height: 380px; /* Tinggi chart diatur agar proporsional */
    }
    </style>

    <!-- ======================================
         BOTTOM: LINE CHART PEMBELIAN PER PERIODE
    ======================================= -->
    <section class="purchase-linechart-card">
        <div class="purchase-linechart-header">
            <h3 class="purchase-linechart-title">Pembelian per Periode</h3>
        </div>

        <div class="purchase-line-container">
            <canvas id="purchasePeriodChart"></canvas>
        </div>

        <div class="purchase-line-legend">
            <span class="purchase-legend-symbol">
                <span class="line"></span>
                <span class="dot"></span>
            </span>
            Total Pembelian
        </div>
    </section>
    <?php
}

/** Modal detail pembelian per periode */
function renderPembelianPeriodeModal(array $periodDetailList): void
{
    ?>
    <!-- ==========================================
         MODAL POPUP: DETAIL PEMBELIAN PER PERIODE
    ========================================== -->
    <div class="period-modal-overlay" id="periodDetailModal" onclick="handlePeriodBackdropClick(event)">
        <div class="period-modal-window" onclick="event.stopPropagation()">

            <!-- Header Modal -->
            <div class="period-modal-header">
                <div class="period-header-top">
                    <div class="period-header-left">
                        <div class="period-header-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="period-header-title-wrap">
                            <h3>
                                Detail Pembelian per Periode
                                <span class="period-badge-count" id="periodHeaderBadge"><?= count($periodDetailList) ?> Data</span>
                            </h3>
                            <p id="periodHeaderSubtitle">Daftar rincian pembelian barang berdasarkan nota beli (NB) pada periode yang dipilih</p>
                        </div>
                    </div>

                    <div class="period-header-summary-card">
                        <span>TOTAL NOMINAL</span>
                        <strong id="periodSummaryTotal">Rp <?= number_format(array_sum(array_column($periodDetailList, 'nominal_nb')), 0, ',', '.') ?>,00</strong>
                    </div>
                </div>

                <div class="period-header-controls">
                    <div class="period-controls-left">
                        <div class="period-search-wrap">
                            <i class="bi bi-search"></i>
                            <input
                                type="text"
                                id="periodSearchInput"
                                placeholder="Cari Kode Barang, Nama Barang, No. NB..."
                                oninput="filterPeriodList()"
                            >
                        </div>
                        <!-- Tombol reset hari, hanya muncul saat filter hari aktif -->
                        <button type="button" id="periodResetHariBtn" onclick="resetPeriodHariFilter()" style="display:none; height:38px; padding:0 14px; background:#ffffff; border:none; border-radius:8px; font-size:12px; font-weight:700; color:#334155; gap:6px; cursor:pointer; align-items:center; transition:background-color .2s;">
                            <i class="bi bi-x-circle" style="color:#ef4444;"></i>
                            Reset Hari
                        </button>
                    </div>

                    <div class="period-controls-right">
                        <button type="button" class="period-btn-export" onclick="exportPeriodList()">
                            <i class="bi bi-box-arrow-up"></i>
                            Export
                        </button>

                        <button type="button" class="period-btn-close-x" onclick="closePeriodDetailModal()" title="Tutup Modal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Body Modal: Tabel Full Width -->
            <div class="period-modal-body">
                <table class="period-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>No. NB</th>
                            <th>Tanggal Pembelian</th>
                            <th style="text-align: right;">Nominal NB</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: center;">Jumlah Stok</th>
                        </tr>
                    </thead>
                    <tbody id="periodTableBody">
                        <!-- Rendered by JavaScript -->
                    </tbody>
                </table>
                <div id="periodEmptyMessage" style="display: none; padding: 40px 20px; text-align: center; color: #94a3b8;">
                    <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                    Tidak ada data pembelian yang sesuai dengan pencarian.
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="period-modal-bottom">
                <div class="period-bottom-info" id="periodFooterCount">
                    Menampilkan <?= count($periodDetailList) ?> dari <?= count($periodDetailList) ?> total data pembelian
                </div>

                <div class="period-bottom-controls">
                    <button type="button" class="po-page-btn disabled">&lsaquo; Sebelumnya</button>
                    <button type="button" class="po-page-btn active">1</button>
                    <button type="button" class="po-page-btn disabled">Selanjutnya &rsaquo;</button>
                    <button type="button" class="period-btn-tutup" onclick="closePeriodDetailModal()">Tutup</button>
                </div>
            </div>

        </div>
    </div>
    <?php
}

/** JavaScript grafik garis + modal detail pembelian per periode */
function renderPembelianPeriodeScript(array $periodChart): void
{
    ?>
<script>
// ======================================
// LINE CHART: PEMBELIAN PER PERIODE
// ======================================
const periodLabels = <?= pembelian_json($periodChart['labels']) ?>;
const periodKeys = <?= pembelian_json($periodChart['keys']) ?>;
const periodData = <?= pembelian_json($periodChart['data']) ?>;

const bulanNamaIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function formatTanggalIndo(key) {
    const p = String(key).split('-');
    if (p.length !== 3) return key;
    return `${p[2]} ${bulanNamaIndo[parseInt(p[1], 10) - 1]} ${p[0]}`;
}

const periodEl = document.getElementById('purchasePeriodChart');
if (periodEl) {
    const ctxPeriod = periodEl.getContext('2d');
    new Chart(ctxPeriod, {
        type: 'line',
        data: {
            labels: periodLabels,
            datasets: [{
                label: 'Total Pembelian',
                data: periodData,
                borderColor: '#0084ff',
                backgroundColor: 'transparent',
                borderWidth: 2,
                tension: 0.35,
                fill: false,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#0084ff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#0084ff',
                pointHoverBorderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            onHover: function(event, elements) {
                event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
            },
            onClick: function(event, elements) {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    openPeriodDetailModal(periodKeys[index]);
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { weight: 'bold', size: 12 },
                    bodyFont: { size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        title: function(context) {
                            return 'Hari ke-' + context[0].label;
                        },
                        label: function(context) {
                            return ' Total: ' + context.parsed.y + ' Transaksi';
                        },
                        afterLabel: function(context) {
                            return ' 🔍 Klik untuk lihat detail';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: { size: 10, weight: '600' },
                        color: '#64748b'
                    },
                    grid: {
                        color: '#edf2f7',
                        borderDash: [3, 3],
                        drawBorder: false
                    }
                },
                x: {
                    ticks: {
                        font: { size: 10, weight: '600' },
                        color: '#64748b'
                    },
                    grid: {
                        color: '#edf2f7',
                        borderDash: [3, 3],
                        drawBorder: false
                    }
                }
            }
        }
    });
}

// ======================================
// MODAL DETAIL PEMBELIAN PER PERIODE
// ======================================
const periodDetailData = <?= pembelian_json($periodChart['detail']) ?>;
let filteredPeriodIndices = periodDetailData.map((_, i) => i);
let activeHariFilter = null;

function openPeriodDetailModal(hariKe) {
    const modal = document.getElementById('periodDetailModal');
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    activeHariFilter = hariKe || null;

    const searchInput = document.getElementById('periodSearchInput');
    if (searchInput) searchInput.value = '';

    const subtitle = document.getElementById('periodHeaderSubtitle');
    if (subtitle) {
        if (hariKe) {
            subtitle.innerHTML = `Pembelian pada <strong>Tanggal ${formatTanggalIndo(hariKe)}</strong> &mdash; klik <em>Reset Hari</em> untuk tampilkan semua`;
        } else {
            subtitle.innerText = 'Daftar rincian pembelian barang berdasarkan nota beli (NB) pada periode yang dipilih';
        }
    }

    const resetBtn = document.getElementById('periodResetHariBtn');
    if (resetBtn) {
        resetBtn.style.display = hariKe ? 'inline-flex' : 'none';
    }

    filterPeriodList();
}

function closePeriodDetailModal() {
    const modal = document.getElementById('periodDetailModal');
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
    activeHariFilter = null;
}

function handlePeriodBackdropClick(e) {
    if (e.target.id === 'periodDetailModal') {
        closePeriodDetailModal();
    }
}

function resetPeriodHariFilter() {
    activeHariFilter = null;
    const subtitle = document.getElementById('periodHeaderSubtitle');
    if (subtitle) subtitle.innerText = 'Daftar rincian pembelian barang berdasarkan nota beli (NB) pada periode yang dipilih';
    const resetBtn = document.getElementById('periodResetHariBtn');
    if (resetBtn) resetBtn.style.display = 'none';
    const searchInput = document.getElementById('periodSearchInput');
    if (searchInput) searchInput.value = '';
    filterPeriodList();
}

function renderPeriodTable() {
    const tbody = document.getElementById('periodTableBody');
    const emptyMsg = document.getElementById('periodEmptyMessage');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (filteredPeriodIndices.length === 0) {
        if (emptyMsg) emptyMsg.style.display = 'block';
        return;
    }

    if (emptyMsg) emptyMsg.style.display = 'none';

    filteredPeriodIndices.forEach((dataIndex, loopIdx) => {
        const item = periodDetailData[dataIndex];
        const tr = document.createElement('tr');

        const rowNum = '#' + String(loopIdx + 1).padStart(2, '0');

        tr.innerHTML = `
            <td class="period-row-num">${rowNum}</td>
            <td><span class="period-kode-badge">${item.kode_barang}</span></td>
            <td class="period-barang-text">${item.nama_barang}</td>
            <td><span class="period-nb-badge">${item.no_nb}</span></td>
            <td class="period-date-text">${item.tgl_pembelian}</td>
            <td class="period-nominal-text" style="text-align: right;">${formatRupiah(item.nominal_nb)}</td>
            <td class="period-qty-text">${item.qty}</td>
            <td class="period-stok-text" style="text-align: center;">${item.jumlah_stok}</td>
        `;

        tbody.appendChild(tr);
    });

    const totalData = periodDetailData.length;
    const footerCount = document.getElementById('periodFooterCount');
    if (footerCount) {
        const hariLabel = activeHariFilter ? ` (${formatTanggalIndo(activeHariFilter)})` : '';
        footerCount.innerText = `Menampilkan ${filteredPeriodIndices.length} dari ${totalData} total data pembelian${hariLabel}`;
    }

    const currentNominal = filteredPeriodIndices.reduce((sum, idx) => sum + periodDetailData[idx].nominal_nb, 0);
    const summaryTotal = document.getElementById('periodSummaryTotal');
    if (summaryTotal) summaryTotal.innerText = formatRupiah(currentNominal) + ',00';

    const headerBadge = document.getElementById('periodHeaderBadge');
    if (headerBadge) headerBadge.innerText = filteredPeriodIndices.length + ' Data';
}

function filterPeriodList() {
    const searchInput = document.getElementById('periodSearchInput');
    const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';

    filteredPeriodIndices = [];

    periodDetailData.forEach((item, index) => {
        const matchHari = activeHariFilter === null || item.tgl === activeHariFilter;

        const matchSearch = !searchVal ||
            item.kode_barang.toLowerCase().includes(searchVal) ||
            item.nama_barang.toLowerCase().includes(searchVal) ||
            item.no_nb.toLowerCase().includes(searchVal) ||
            item.tgl_pembelian.toLowerCase().includes(searchVal);

        if (matchHari && matchSearch) {
            filteredPeriodIndices.push(index);
        }
    });

    renderPeriodTable();
}

function exportPeriodList() {
    if (filteredPeriodIndices.length === 0) {
        alert('Tidak ada data untuk diexport!');
        return;
    }

    // Gunakan BOM (\uFEFF) agar karakter khusus / Excel membaca format UTF-8 dengan benar
    let csvContent = "data:text/csv;charset=utf-8,\uFEFF"; 

    // 1. Buat Header Kolom Excel
    csvContent += "No;Kode Barang;Nama Barang;No. NB;Tanggal Pembelian;Nominal (Rp);Qty;Jumlah Stok\r\n";

    // 2. Masukkan Baris Data yang sedang ditampilkan/difilter
    filteredPeriodIndices.forEach((dataIndex, loopIdx) => {
        const item = periodDetailData[dataIndex];
        const rowNum = loopIdx + 1;
        
        // Bungkus teks dengan tanda petik ganda antisipasi jika ada koma di nama barang
        const namaBarang = `"${item.nama_barang.replace(/"/g, '""')}"`;
        const noNb = `"${item.no_nb.replace(/"/g, '""')}"`;

        const row = [
            rowNum,
            item.kode_barang,
            namaBarang,
            noNb,
            item.tgl_pembelian,
            item.nominal_nb,
            item.qty,
            item.jumlah_stok
        ].join(";");

        csvContent += row + "\r\n";
    });

    // 3. Buat elemen link virtual untuk mendownload file CSV
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "Laporan_Pembelian_Periode.csv");
    document.body.appendChild(link);
    
    // Jalankan perintah klik otomatis untuk mendownload
    link.click();
    document.body.removeChild(link);
}
</script>
    <?php
}