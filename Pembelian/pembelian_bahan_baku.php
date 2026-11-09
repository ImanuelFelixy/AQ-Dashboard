<?php
/**
 * pembelian_bahan_baku.php
 * Modul "Pembelian Bahan Baku Kaca" (grafik batang Tonase & Luas per ketebalan
 * + modal detail tonase & luasan per Nota Beli).
 * Dipanggil dari main_pembelian.php.
 *
 * Tabel sumber: mutasi_pembelian (ketebalan, luas_m2, berat, total_harga_rp)
 * Tanggal NB diturunkan lewat pembelian_tgl_nb_sql() di main_pembelian.php.
 */

/**
 * Ambil data grafik batang (per ketebalan) + detail per NB pada rentang tanggal.
 *
 *  - tonase pada grafik dalam satuan TON (berat Kg / 1000)
 *  - luas pada grafik dalam satuan m²
 *  - tonase pada tabel detail dalam Kg
 *
 * @return array{labels: array, tonase: array, luas: array, detail: array}
 */
function getBahanBakuData(?mysqli $db, string $start, string $end): array
{
    $tglExpr = pembelian_tgl_nb_sql();

    $rows = pembelian_query(
        $db,
        "SELECT x.*
         FROM (
            SELECT m.no_trans,
                   m.nama_barang,
                   m.ketebalan,
                   m.luas_m2,
                   m.berat,
                   m.total_harga_rp,
                   {$tglExpr} AS tgl
            FROM mutasi_pembelian m
            LEFT JOIN payable_aging p
                   ON REPLACE(m.no_trans, '/', '-') = p.no_pembelian
         ) x
         WHERE x.tgl BETWEEN ? AND ?
         ORDER BY x.ketebalan, x.tgl, x.no_trans, x.nama_barang",
        'ss',
        [$start, $end]
    );

    $perTebal = [];
    $detail   = [];

    foreach ($rows as $r) {
        $tebal = (string) ((int) $r['ketebalan']);
        $berat = (float) $r['berat'];
        $luas  = (float) $r['luas_m2'];

        if (!isset($perTebal[$tebal])) {
            $perTebal[$tebal] = ['berat' => 0.0, 'luas' => 0.0];
        }
        $perTebal[$tebal]['berat'] += $berat;
        $perTebal[$tebal]['luas']  += $luas;

        $detail[] = [
            'tebal'       => $tebal,
            'no_nb'       => $r['no_trans'],
            'nama_barang' => $r['nama_barang'],
            'tonase'      => $berat,
            'luas'        => $luas,
            'nominal'     => (float) $r['total_harga_rp'],
        ];
    }

    $labels = [];
    $tonase = [];
    $luas   = [];
    foreach ($perTebal as $tebal => $v) {
        $labels[] = (string) $tebal;
        $tonase[] = round($v['berat'] / 1000, 2);
        $luas[]   = round($v['luas'], 2);
    }

    return [
        'labels' => $labels,
        'tonase' => $tonase,
        'luas'   => $luas,
        'detail' => $detail,
    ];
}

/** Section grafik batang "Pembelian Bahan Baku Kaca" */
function renderBahanBakuChartSection(): void
{
    ?>
    <!-- Right: Bar Chart (Tonase & Luas per Ketebalan) -->
    <section class="purchase-barchart-card">
        <div class="purchase-barchart-header">
            <h3 class="purchase-barchart-title">Pembelian Bahan Baku Kaca</h3>
        </div>
        <div class="purchase-barchart-card">
            <div class="purchase-barchart-container">
                <canvas id="monthlyBarChart"></canvas>
            </div>
        </div>
    </section>
    <?php
}

/** Modal detail tonase & luas kaca */
function renderBahanBakuModal(array $monthlyDetailList): void
{
    ?>
    <!-- ==========================================
         MODAL POPUP: DETAIL TONASE & LUAS KACA
    ========================================== -->
    <div class="period-modal-overlay" id="monthlyDetailModal" onclick="handleMonthlyBackdropClick(event)">
        <div class="period-modal-window" onclick="event.stopPropagation()">

            <!-- Header Modal -->
            <div class="period-modal-header">
                <div class="period-header-top">
                    <div class="period-header-left">
                        <div class="period-header-icon">
                            <i class="bi bi-bar-chart-fill"></i>
                        </div>
                        <div class="period-header-title-wrap">
                            <h3>
                                Detail Tonase &amp; Luas Kaca
                                <span class="period-badge-count" id="monthlyHeaderBadge"><?= count($monthlyDetailList) ?> Data</span>
                            </h3>
                            <p id="monthlyHeaderSubtitle">Rincian tonase dan luasan berdasarkan Nota Beli (NB)</p>
                        </div>
                    </div>

                    <div class="period-header-summary-card">
                        <span>TOTAL NOMINAL</span>
                        <strong id="monthlySummaryTotal">Rp <?= number_format(array_sum(array_column($monthlyDetailList, 'nominal')), 0, ',', '.') ?>,00</strong>
                    </div>
                </div>

                <div class="period-header-controls">
                    <div class="period-controls-left">
                        <div class="period-search-wrap">
                            <i class="bi bi-search"></i>
                            <input
                                type="text"
                                id="monthlySearchInput"
                                placeholder="Cari No. NB, Nama Barang..."
                                oninput="filterMonthlyList()"
                            >
                        </div>
                        <button type="button" id="monthlyResetBtn" onclick="resetMonthlyFilter()" style="display:none; height:38px; padding:0 14px; background:#ffffff; border:none; border-radius:8px; font-size:12px; font-weight:700; color:#334155; gap:6px; cursor:pointer; align-items:center; transition:background-color .2s;">
                            <i class="bi bi-x-circle" style="color:#ef4444;"></i>
                            Reset Filter
                        </button>
                    </div>

                    <div class="period-controls-right">
                        <button type="button" class="period-btn-export" onclick="exportMonthlyList()">
                            <i class="bi bi-box-arrow-up"></i>
                            Export
                        </button>

                        <button type="button" class="period-btn-close-x" onclick="closeMonthlyDetailModal()" title="Tutup Modal">
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
                            <th>No. NB</th>
                            <th>Nama Barang</th>
                            <th style="text-align: right;">Tonase (Kg)</th>
                            <th style="text-align: right;">Luas (m²)</th>
                            <th style="text-align: right;">Nominal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody id="monthlyTableBody">
                        <!-- Rendered by JavaScript -->
                    </tbody>
                </table>
                <div id="monthlyEmptyMessage" style="display: none; padding: 40px 20px; text-align: center; color: #94a3b8;">
                    <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                    Tidak ada data yang sesuai dengan pencarian.
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="period-modal-bottom">
                <div class="period-bottom-info" id="monthlyFooterCount">
                    Menampilkan <?= count($monthlyDetailList) ?> data
                </div>

                <div class="period-bottom-controls">
                    <button type="button" class="po-page-btn disabled">&lsaquo; Sebelumnya</button>
                    <button type="button" class="po-page-btn active">1</button>
                    <button type="button" class="po-page-btn disabled">Selanjutnya &rsaquo;</button>
                    <button type="button" class="period-btn-tutup" onclick="closeMonthlyDetailModal()">Tutup</button>
                </div>
            </div>

        </div>
    </div>
    <?php
}

/** JavaScript grafik batang + modal detail */
function renderBahanBakuScript(array $bahanBaku): void
{
    ?>
<script>
// ======================================
// BAR CHART: TONASE & LUAS PER KETEBALAN (+ KLIK POPUP)
// ======================================
const monthlyLabels = <?= pembelian_json($bahanBaku['labels']) ?>;
const monthlyTonase = <?= pembelian_json($bahanBaku['tonase']) ?>;
const monthlyLuas   = <?= pembelian_json($bahanBaku['luas']) ?>;

const barEl = document.getElementById('monthlyBarChart');
if (barEl) {
    const ctxBar = barEl.getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: monthlyLabels,
            datasets: [
                {
                    label: 'Tonase (Ton)',
                    data: monthlyTonase,
                    backgroundColor: '#7b77eb',
                    borderRadius: 3,
                    barPercentage: 0.65,
                    categoryPercentage: 0.6,
                    yAxisID: 'yTonase'
                },
                {
                    label: 'Luas (m²)',
                    data: monthlyLuas,
                    backgroundColor: '#5ad8b9',
                    borderRadius: 3,
                    barPercentage: 0.65,
                    categoryPercentage: 0.6,
                    yAxisID: 'yLuas'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onHover: function(event, elements) {
                event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
            },
            onClick: function(event, elements) {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const tebalKaca = monthlyLabels[index];
                    openMonthlyDetailModal(tebalKaca);
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        boxHeight: 12,
                        usePointStyle: false,
                        padding: 16,
                        font: { size: 12, weight: '700' },
                        color: '#64748b'
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { weight: 'bold', size: 12 },
                    bodyFont: { size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        title: function(context) {
                            return 'Kaca Ketebalan: ' + context[0].label + ' mm';
                        },
                        afterLabel: function() {
                            return ' 🔍 Klik grafik untuk melihat rincian barang';
                        }
                    }
                }
            },
            scales: {
                yTonase: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    ticks: {
                        font: { size: 11, weight: '600' },
                        color: '#7b77eb'
                    },
                    grid: {
                        color: '#eef2f6',
                        borderDash: [3, 3],
                        drawBorder: false
                    }
                },
                yLuas: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    ticks: {
                        font: { size: 11, weight: '600' },
                        color: '#2fb394',
                        display: false // <--- Angka/skala di sisi kanan dihilangkan di sini
                    },
                    grid: {
                        drawOnChartArea: false,
                        drawBorder: false
                    }
                },
                x: {
                    ticks: {
                        font: { size: 11, weight: '600' },
                        color: '#64748b'
                    },
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                }
            }
        }
    });
}

// ======================================
// LOGIKA MODAL DETAIL
// ======================================
const monthlyDetailData = <?= pembelian_json($bahanBaku['detail']) ?>;
let filteredMonthlyIndices = monthlyDetailData.map((_, i) => i);
let activeTebalFilter = null;

function openMonthlyDetailModal(tebal) {
    const modal = document.getElementById('monthlyDetailModal');
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    activeTebalFilter = tebal || null;

    const searchInput = document.getElementById('monthlySearchInput');
    if (searchInput) searchInput.value = '';

    const subtitle = document.getElementById('monthlyHeaderSubtitle');
    if (subtitle) {
        if (tebal) {
            subtitle.innerHTML = `Rincian barang ketebalan <strong>${tebal} mm</strong> &mdash; klik <em>Reset Filter</em> untuk tampilkan seluruh ukuran`;
        } else {
            subtitle.innerText = 'Rincian tonase dan luasan berdasarkan Nota Beli (NB)';
        }
    }

    const resetBtn = document.getElementById('monthlyResetBtn');
    if (resetBtn) resetBtn.style.display = tebal ? 'inline-flex' : 'none';

    filterMonthlyList();
}

function closeMonthlyDetailModal() {
    const modal = document.getElementById('monthlyDetailModal');
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
    activeTebalFilter = null;
}

function handleMonthlyBackdropClick(e) {
    if (e.target.id === 'monthlyDetailModal') {
        closeMonthlyDetailModal();
    }
}

function resetMonthlyFilter() {
    activeTebalFilter = null;
    const subtitle = document.getElementById('monthlyHeaderSubtitle');
    if (subtitle) subtitle.innerText = 'Rincian tonase dan luasan berdasarkan Nota Beli (NB)';
    const resetBtn = document.getElementById('monthlyResetBtn');
    if (resetBtn) resetBtn.style.display = 'none';
    const searchInput = document.getElementById('monthlySearchInput');
    if (searchInput) searchInput.value = '';
    filterMonthlyList();
}

function renderMonthlyTable() {
    const tbody = document.getElementById('monthlyTableBody');
    const emptyMsg = document.getElementById('monthlyEmptyMessage');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (filteredMonthlyIndices.length === 0) {
        if (emptyMsg) emptyMsg.style.display = 'block';
        return;
    }

    if (emptyMsg) emptyMsg.style.display = 'none';

    filteredMonthlyIndices.forEach((dataIndex, loopIdx) => {
        const item = monthlyDetailData[dataIndex];
        const tr = document.createElement('tr');
        const rowNum = '#' + String(loopIdx + 1).padStart(2, '0');

        tr.innerHTML = `
            <td class="period-row-num">${rowNum}</td>
            <td><span class="period-nb-badge">${item.no_nb}</span></td>
            <td class="period-barang-text">${item.nama_barang}</td>
            <td style="text-align: right; font-weight: 700; color: #4f46e5;">${item.tonase.toLocaleString('id-ID')} Kg</td>
            <td style="text-align: right; font-weight: 700; color: #059669;">${item.luas.toLocaleString('id-ID')} m²</td>
            <td class="period-nominal-text" style="text-align: right;">${formatRupiah(item.nominal)}</td>
        `;

        tbody.appendChild(tr);
    });

    const totalData = monthlyDetailData.length;
    const footerCount = document.getElementById('monthlyFooterCount');
    if (footerCount) {
        const labelFilter = activeTebalFilter ? ` (Tebal ${activeTebalFilter}mm)` : '';
        footerCount.innerText = `Menampilkan ${filteredMonthlyIndices.length} dari ${totalData} data${labelFilter}`;
    }

    const currentNominal = filteredMonthlyIndices.reduce((sum, idx) => sum + monthlyDetailData[idx].nominal, 0);
    const summaryTotal = document.getElementById('monthlySummaryTotal');
    if (summaryTotal) summaryTotal.innerText = formatRupiah(currentNominal) + ',00';

    const headerBadge = document.getElementById('monthlyHeaderBadge');
    if (headerBadge) headerBadge.innerText = filteredMonthlyIndices.length + ' Data';
}

function filterMonthlyList() {
    const searchInput = document.getElementById('monthlySearchInput');
    const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';

    filteredMonthlyIndices = [];

    monthlyDetailData.forEach((item, index) => {
        const matchTebal = activeTebalFilter === null || item.tebal === activeTebalFilter;
        const matchSearch = !searchVal ||
            item.no_nb.toLowerCase().includes(searchVal) ||
            item.nama_barang.toLowerCase().includes(searchVal);

        if (matchTebal && matchSearch) {
            filteredMonthlyIndices.push(index);
        }
    });

    renderMonthlyTable();
}

function exportMonthlyList() {
    const tebalLabel = activeTebalFilter ? ` Tebal ${activeTebalFilter}mm` : '';
    alert(`Mengekspor ${filteredMonthlyIndices.length} data${tebalLabel}...`);
}
</script>
    <?php
}