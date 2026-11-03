<?php
$pageTitle = 'Dashboard Penjualan';
$activePage = 'penjualan';
$basePath = '../';

require_once __DIR__ . '/../includes/header.php';

// ==========================================
// DATA SEMENTARA (DUMMY)
// Nantinya akan diganti dengan data MySQL
// ==========================================

$startDate = $_GET['tanggal_awal'] ?? '2026-09-01';
$endDate   = $_GET['tanggal_akhir'] ?? '2026-09-21';

$currentUser = 'SARI';
$userIp = $_SERVER['REMOTE_ADDR'] ?? '192.168.1.78';
if ($userIp === '::1') {
    $userIp = '127.0.0.1';
}

// 1. Aktivitas Operasional
$salesOrderItemCount = 25;
$salesOrderNominal   = 9999999;

$workOrderItemCount  = 72;
$workOrderNominal    = 9999999;

// 2. Monitoring Logistik
$logisticData = [
    [
        'label' => 'Siap Untuk Dikirim',
        'value' => 7,
        'percent' => 18.42,
        'color' => '#7066e0'
    ],
    [
        'label' => 'Proses Pengiriman',
        'value' => 12,
        'percent' => 31.58,
        'color' => '#34d399'
    ],
    [
        'label' => 'Invoice Terbit',
        'value' => 19,
        'percent' => 50.00,
        'color' => '#ff8a7a'
    ]
];

$logisticTotal = array_sum(array_column($logisticData, 'value'));

// 3. Total Invoice per Periode (Data Harian 1-31)
$invoicePeriodLabels = range(1, 31);
$invoicePeriodData = [
    0, 63, 22, 8, 68, 54, 28, 35, 78, 36,
    58, 68, 28, 82, 18, 92, 90, 98, 62, 40,
    72, 88, 82, 98, 40, 20, 68, 74, 82, 40, 92
];
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* ==========================================
   GENERAL DASHBOARD STYLES
========================================== */

.sales-dashboard {
    width: 100%;
    padding: 24px 28px;
    background: #f5f7fc;
    min-height: calc(100vh - 100px);
}

.sales-dashboard * {
    box-sizing: border-box;
}

/* ==========================================
   TOP BAR (GREETING & ACTIONS)
========================================== */

.sales-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.sales-user-tag {
    color: #64748b;
    font-size: 14px;
    font-weight: 700;
}

.sales-user-tag span {
    color: #0866c6;
    font-weight: 800;
}

.sales-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.sales-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    border: 1px solid #dce5f1;
    background: #fff;
    color: #46566d;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: all .2s ease;
    text-decoration: none;
}

.sales-btn:hover {
    background: #edf4ff;
    color: #0866c6;
    border-color: #c7dbfb;
}

.sales-btn.primary {
    background: #0866c6;
    color: #fff;
    border-color: #0866c6;
}

.sales-btn.primary:hover {
    background: #0754a5;
    border-color: #0754a5;
    color: #fff;
}

/* ==========================================
   BANNER & FILTER PERIODE
========================================== */

.sales-banner-card {
    background: #fff;
    border: 1px solid #dce9fb;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(32, 66, 110, .04);
    margin-bottom: 20px;
}

.sales-banner-header {
    background: linear-gradient(135deg, #075db5 0%, #0866c6 100%);
    color: #ffffff;
    padding: 20px 26px;
}

.sales-banner-title {
    font-size: 20px;
    font-weight: 800;
    margin: 0 0 6px 0;
    letter-spacing: -0.3px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sales-banner-title i {
    font-size: 22px;
}

.sales-banner-desc {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
    line-height: 1.5;
}

.sales-filter-body {
    background: #eaf2ff;
    padding: 16px 24px;
    border-top: 1px solid #dce9fb;
}

.sales-filter-title {
    font-size: 11.5px;
    font-weight: 800;
    color: #53647c;
    margin-bottom: 12px;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: 0.5px;
}

.sales-filter-title i {
    color: #0866c6;
    font-size: 14px;
}

.sales-filter-form {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
}

.sales-filter-form input[type="date"] {
    height: 48px;
    flex: 1;
    min-width: 0;
    padding: 0 16px;
    background: #fff;
    border: 1px solid #dce4f0;
    border-radius: 9px;
    color: #42536b;
    font-size: 13px;
    font-weight: 600;
    transition: border-color .2s;
}

.sales-filter-form input[type="date"]:focus {
    outline: none;
    border-color: #0866c6;
    box-shadow: 0 0 0 3px rgba(8, 102, 198, .12);
}

.sales-filter-form .separator {
    font-size: 13px;
    color: #738198;
    font-weight: 600;
    white-space: nowrap;
}

.sales-filter-submit {
    height: 48px;
    padding: 0 26px;
    border: none;
    border-radius: 9px;
    background: #0866c6;
    color: white;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
    transition: background-color .2s;
}

.sales-filter-submit:hover {
    background: #0754a5;
}

.sales-today {
    height: 48px;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 0 18px;
    background: white;
    border: 1px solid #dce4f0;
    border-radius: 9px;
    color: #53647c;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
    margin: 0;
    transition: background-color .2s;
}

.sales-today:hover {
    background: #f8fafc;
}

.sales-today input[type="checkbox"] {
    accent-color: #0866c6;
    cursor: pointer;
    width: 15px;
    height: 15px;
}

/* ==========================================
   MIDDLE SECTION: FULL-WIDTH INVOICE CHART
========================================== */

.sales-invoice-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px 24px;
    box-shadow: 0 2px 10px rgba(32, 66, 110, .03);
    margin-bottom: 20px;
}

.sales-invoice-header {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 16px;
    position: relative;
}

.sales-invoice-title {
    font-size: 18px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.invoice-legend-symbol {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    color: #5b52e0;
}

.invoice-legend-symbol .line {
    width: 18px;
    height: 2px;
    background: #5b52e0;
    display: inline-block;
}

.invoice-legend-symbol .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #5b52e0;
    display: inline-block;
    margin-left: -13px;
}

.sales-line-container {
    position: relative;
    height: 260px;
    width: 100%;
}

/* ==========================================
   BOTTOM SECTION: OPERASIONAL & LOGISTIK
========================================== */

.sales-bottom-grid {
    display: grid;
    grid-template-columns: 1.45fr 1fr;
    gap: 18px;
    margin-bottom: 20px;
}

.sales-sub-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 22px 24px;
    box-shadow: 0 2px 10px rgba(32, 66, 110, .03);
    display: flex;
    flex-direction: column;
}

.sales-sub-header {
    text-align: center;
    margin-bottom: 20px;
}

.sales-sub-title {
    font-size: 18px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

/* Operasional: 2 Sub Metric Cards */
.sales-operasional-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    flex: 1;
}

.s-metric-box {
    background: #fff;
    border: 2px solid #0875d1;
    border-radius: 14px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 155px;
    box-shadow: 0 2px 8px rgba(8, 117, 209, .05);
    transition: transform .2s ease, box-shadow .2s ease;
}

.s-metric-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(8, 117, 209, .1);
}

.s-box-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.s-box-title {
    font-size: 16px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.s-circle-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #dbeafe;
    color: #0875d1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: transform .2s ease;
}

.s-circle-btn:hover {
    transform: scale(1.08);
}

.s-box-metric {
    margin: 14px 0 8px;
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.s-metric-num {
    font-size: 34px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
}

.s-metric-unit {
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
}

.s-box-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11.5px;
    color: #64748b;
    font-weight: 600;
    margin-top: auto;
}

.s-box-footer strong {
    color: #1e293b;
    font-weight: 700;
}

/* Logistik: Pie Chart & Legend */
.sales-pie-layout {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    min-height: 200px;
}

.sales-pie-wrapper {
    width: 170px;
    height: 170px;
    position: relative;
    margin: 6px 0 16px;
}

.sales-pie-legend {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-top: auto;
}

.sales-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
}

.sales-legend-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}

/* ==========================================
   SYSTEM STATUS FOOTER
========================================== */

.sales-footer {
    background: #1d2b3e;
    color: #aab6c6;
    padding: 12px 22px;
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    font-size: 10px;
    margin-top: 10px;
}

.sales-footer .online {
    color: #34d399;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 6px;
}

.sales-footer .online-dot {
    width: 7px;
    height: 7px;
    background: #34d399;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 6px #34d399;
}

/* ==========================================
   RESPONSIVE DESIGN
========================================== */

@media (max-width: 1100px) {
    .sales-bottom-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .sales-dashboard {
        padding: 16px 14px;
    }

    .sales-topbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .sales-actions {
        width: 100%;
    }

    .sales-actions .sales-btn {
        flex: 1;
    }

    .sales-banner-header {
        padding: 16px 18px;
    }

    .sales-banner-title {
        font-size: 17px;
    }

    .sales-filter-body {
        padding: 14px 16px;
    }

    .sales-filter-form {
        flex-wrap: wrap;
    }

    .sales-filter-form input[type="date"] {
        flex: 1 1 45%;
        min-width: 120px;
    }

    .sales-filter-form .separator {
        display: none;
    }

    .sales-filter-submit {
        flex: 1 1 100%;
    }

    .sales-today {
        width: 100%;
        justify-content: center;
    }

    .sales-operasional-container {
        grid-template-columns: 1fr;
    }

    .sales-line-container {
        height: 200px;
    }

    .sales-pie-wrapper {
        width: 150px;
        height: 150px;
    }

    .sales-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
}

/* ==========================================
   PRINT STYLES
========================================== */

@media print {
    body {
        background: white !important;
    }

    .sales-dashboard {
        padding: 10px;
        background: white;
    }

    .sales-actions,
    .sales-filter-body {
        display: none !important;
    }

    .sales-invoice-card,
    .sales-sub-card {
        box-shadow: none !important;
        break-inside: avoid;
    }

    .sales-footer {
        display: none !important;
    }
}
</style>

<!-- ==========================================
     MAIN DASHBOARD
========================================== -->

<main class="sales-dashboard">

    <!-- TOPBAR: USER GREETING & ACTIONS -->
    <section class="sales-topbar">
        <span class="sales-user-tag">
            Halo, <span><?= htmlspecialchars($currentUser) ?></span>
        </span>

        <div class="sales-actions">
            <button type="button" class="sales-btn" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh Data
            </button>

            <button type="button" class="sales-btn" onclick="exportExcel()">
                <i class="bi bi-file-earmark-excel"></i>
                Export XLS
            </button>

            <button type="button" class="sales-btn primary" onclick="window.print()">
                <i class="bi bi-printer"></i>
                Print Laporan
            </button>
        </div>
    </section>

    <!-- BANNER & FILTER TRANSAKSI -->
    <section class="sales-banner-card">
        <div class="sales-banner-header">
            <h1 class="sales-banner-title">
                <i class="bi bi-graph-up-arrow"></i>
                Dashboard Monitoring Transaksi Penjualan
            </h1>
            <p class="sales-banner-desc">
                Pelacakan status transaksi Sales Order, progres Work Order, pengiriman logistik, dan faktur penjualan secara kontinu.
            </p>
        </div>

        <div class="sales-filter-body">
            <div class="sales-filter-title">
                <i class="bi bi-calendar3"></i>
                Periode Transaksi SO
            </div>

            <form method="GET" class="sales-filter-form">
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

                <button type="submit" class="sales-filter-submit">
                    Tampilkan
                </button>

                <label class="sales-today">
                    <input
                        type="checkbox"
                        id="todayOnly"
                        <?= ($startDate === date('Y-m-d') && $endDate === date('Y-m-d')) ? 'checked' : '' ?>
                    >
                    <i class="bi bi-calendar-check"></i>
                    Hari Ini Saja
                </label>
            </form>
        </div>
    </section>

    <!-- ======================================
         MIDDLE SECTION: TOTAL INVOICE CHART
    ======================================= -->
    <section class="sales-invoice-card">
        <div class="sales-invoice-header">
            <h2 class="sales-invoice-title">
                <span class="invoice-legend-symbol">
                    <span class="line"></span>
                    <span class="dot"></span>
                </span>
                Total Invoice
            </h2>
        </div>

        <div class="sales-line-container">
            <canvas id="invoicePeriodChart"></canvas>
        </div>
    </section>

    <!-- ======================================
         BOTTOM SECTION: OPERASIONAL & LOGISTIK
    ======================================= -->
    <section class="sales-bottom-grid">

        <!-- Left: Monitoring Aktivitas Operasional -->
        <div class="sales-sub-card">
            <div class="sales-sub-header">
                <h3 class="sales-sub-title">Monitoring Aktivitas Operasional</h3>
            </div>

            <div class="sales-operasional-container">

                <!-- 1. Sales Order -->
                <div class="s-metric-box">
                    <div class="s-box-top">
                        <h4 class="s-box-title">Sales Order</h4>
                        <a href="#" class="s-circle-btn" title="Lihat Detail Sales Order">
                            <i class="bi bi-arrow-up-right"></i>
                        </a>
                    </div>

                    <div class="s-box-metric">
                        <span class="s-metric-num"><?= number_format($salesOrderItemCount, 0, ',', '.') ?></span>
                        <span class="s-metric-unit">item</span>
                    </div>

                    <div class="s-box-footer">
                        <span>Total Nominal :</span>
                        <strong>Rp <?= number_format($salesOrderNominal, 0, ',', '.') ?>, 00</strong>
                    </div>
                </div>

                <!-- 2. Work Order -->
                <div class="s-metric-box">
                    <div class="s-box-top">
                        <h4 class="s-box-title">Work Order</h4>
                        <a href="#" class="s-circle-btn" title="Lihat Detail Work Order">
                            <i class="bi bi-arrow-up-right"></i>
                        </a>
                    </div>

                    <div class="s-box-metric">
                        <span class="s-metric-num"><?= number_format($workOrderItemCount, 0, ',', '.') ?></span>
                        <span class="s-metric-unit">item</span>
                    </div>

                    <div class="s-box-footer">
                        <span>Total Nominal :</span>
                        <strong>Rp <?= number_format($workOrderNominal, 0, ',', '.') ?>, 00</strong>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right: Monitoring Logistik -->
        <div class="sales-sub-card">
            <div class="sales-sub-header">
                <h3 class="sales-sub-title">Monitoring Logistik</h3>
            </div>

            <div class="sales-pie-layout">
                <div class="sales-pie-wrapper">
                    <canvas id="logisticsPieChart"></canvas>
                </div>

                <div class="sales-pie-legend">
                    <?php foreach ($logisticData as $item): ?>
                        <div class="sales-legend-item">
                            <span class="sales-legend-dot" style="background: <?= $item['color'] ?>;"></span>
                            <span><?= htmlspecialchars($item['label']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </section>

</main>

<!-- ======================================
     JAVASCRIPT
======================================= -->
<script>
// ======================================
// CHART CONFIGURATION
// ======================================
Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#64748b';

// ======================================
// 1. LINE CHART: TOTAL INVOICE
// ======================================
const invoiceLabels = <?= json_encode($invoicePeriodLabels) ?>;
const invoiceData = <?= json_encode($invoicePeriodData) ?>;

const ctxInvoice = document.getElementById('invoicePeriodChart').getContext('2d');

new Chart(ctxInvoice, {
    type: 'line',
    data: {
        labels: invoiceLabels,
        datasets: [{
            label: 'Total Invoice',
            data: invoiceData,
            borderColor: '#5b52e0',
            backgroundColor: 'rgba(91, 82, 224, 0.04)',
            borderWidth: 2,
            tension: 0.2,
            fill: true,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#5b52e0',
            pointBorderWidth: 2,
            pointRadius: 3.5,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: '#5b52e0',
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
                    label: function(context) {
                        return ' Total Invoice: ' + context.parsed.y;
                    }
                }
            }
        },
        scales: {
            y: {
                min: 0,
                max: 100,
                ticks: {
                    stepSize: 20,
                    font: { size: 10 }
                },
                grid: {
                    color: '#e2e8f0',
                    borderDash: [3, 3],
                    drawBorder: false
                }
            },
            x: {
                ticks: {
                    font: { size: 10 }
                },
                grid: {
                    display: false,
                    drawBorder: false
                }
            }
        }
    }
});

// ======================================
// 2. PIE CHART: MONITORING LOGISTIK
// ======================================
const logisticData = <?= json_encode($logisticData) ?>;
const logValues = logisticData.map(l => l.value);
const logColors = logisticData.map(l => l.color);
const logLabels = logisticData.map(l => l.label);

const ctxLogistics = document.getElementById('logisticsPieChart').getContext('2d');

new Chart(ctxLogistics, {
    type: 'pie',
    data: {
        labels: logLabels,
        datasets: [{
            data: logValues,
            backgroundColor: logColors,
            borderWidth: 1,
            borderColor: '#ffffff',
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
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
                    label: function(context) {
                        const item = logisticData[context.dataIndex];
                        return ` ${item.label}: ${item.value} (${item.percent}%)`;
                    }
                }
            }
        }
    }
});

// ======================================
// 3. FILTER HARI INI
// ======================================
const todayCheckbox = document.getElementById('todayOnly');

todayCheckbox.addEventListener('change', function() {
    if (this.checked) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const formattedDate = `${year}-${month}-${day}`;

        document.getElementById('tanggalAwal').value = formattedDate;
        document.getElementById('tanggalAkhir').value = formattedDate;
    }
});

// ======================================
// 4. EXPORT EXCEL SEMENTARA
// ======================================
function exportExcel() {
    alert('Fitur Export Excel akan diaktifkan setelah data penjualan terhubung dengan database.');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
