<?php
$pageTitle = 'Dashboard Penjualan';
$activePage = 'penjualan';
$basePath = '../';

require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../includes/header.php';
$currentUser = 'Administrator';
// ==========================================
// FILTER PERIODE
// ==========================================

$startDate = $_GET['tanggal_awal'] ?? '2026-10-01';
$endDate   = $_GET['tanggal_akhir'] ?? '2026-10-31';

// ==========================================
// 1. DATA SALES ORDER
// ==========================================

$stmtSO = $conn->prepare("
    SELECT
        COUNT(*) AS total_so,
        COALESCE(SUM(total_rp), 0) AS nominal_so
    FROM report_so
    WHERE inquiry_date BETWEEN :start_date AND :end_date
");

$stmtSO->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate
]);

$dataSO = $stmtSO->fetch();

$salesOrderItemCount = (int) $dataSO['total_so'];
$salesOrderNominal = (float) $dataSO['nominal_so'];

// ==========================================
// 2. DATA WORK ORDER
// ==========================================

$stmtWO = $conn->prepare("
    SELECT COUNT(DISTINCT no_wo) AS total_wo
    FROM report_alur_wo
    WHERE tgl_trans_wo BETWEEN :start_date AND :end_date
");

$stmtWO->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate
]);

$workOrderItemCount = (int) $stmtWO->fetchColumn();

// Nominal WO tidak tersedia pada laporan yang digunakan.
$workOrderNominal = 0;

// ==========================================
// 3. DATA MONITORING LOGISTIK
// ==========================================

$stmtLogistik = $conn->prepare("
    SELECT
        COUNT(DISTINCT CASE
            WHEN (d.tgl_kirim IS NULL
                OR DATE(d.tgl_kirim) = '0000-00-00')
            THEN d.no_do
        END) AS siap_dikirim,

        COUNT(DISTINCT CASE
            WHEN d.tgl_kirim IS NOT NULL
                AND DATE(d.tgl_kirim) <> '0000-00-00'
                AND (d.tgl_terima IS NULL
                    OR DATE(d.tgl_terima) = '0000-00-00')
            THEN d.no_do
        END) AS proses_pengiriman,

        COUNT(DISTINCT CASE
            WHEN d.tgl_terima IS NOT NULL
                AND DATE(d.tgl_terima) <> '0000-00-00'
            THEN d.no_do
        END) AS barang_diterima

    FROM report_status_do d
    INNER JOIN report_so s
        ON d.no_so = s.no_trans

    WHERE s.inquiry_date BETWEEN :start_date AND :end_date
");

$stmtLogistik->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate
]);

$logistik = $stmtLogistik->fetch();

$logisticData = [
    [
        'label' => 'Siap Untuk Dikirim',
        'value' => (int) $logistik['siap_dikirim'],
        'percent' => 0,
        'color' => '#7066e0'
    ],
    [
        'label' => 'Proses Pengiriman',
        'value' => (int) $logistik['proses_pengiriman'],
        'percent' => 0,
        'color' => '#34d399'
    ],
    [
        'label' => 'Barang Diterima',
        'value' => (int) $logistik['barang_diterima'],
        'percent' => 0,
        'color' => '#ff8a7a'
    ]
];

$logisticTotal = array_sum(array_column($logisticData, 'value'));

foreach ($logisticData as &$item) {
    $item['percent'] = $logisticTotal > 0
        ? round(($item['value'] / $logisticTotal) * 100, 2)
        : 0;
}
unset($item);

// ==========================================
// 4. DATA GRAFIK INVOICE
// ==========================================

$stmtInvoice = $conn->prepare("
    SELECT
        DAY(tgl_final) AS hari,
        SUM(total_sudah_invoice) AS total_invoice
    FROM report_invoice_per_so
    WHERE tgl_final BETWEEN :start_date AND :end_date
    GROUP BY DAY(tgl_final)
    ORDER BY DAY(tgl_final)
");

$stmtInvoice->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate
]);

$invoiceRows = $stmtInvoice->fetchAll();

$invoicePeriodLabels = range(1, 31);
$invoicePeriodData = array_fill(0, 31, 0);

foreach ($invoiceRows as $row) {
    $hari = (int) $row['hari'];

    if ($hari >= 1 && $hari <= 31) {
        $invoicePeriodData[$hari - 1] = (float) $row['total_invoice'];
    }
}
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
                <div class="s-metric-box"
                style="cursor:pointer;"
                onclick="window.location.href='Detail-SO.php?tanggal_awal=<?= urlencode($startDate) ?>&tanggal_akhir=<?= urlencode($endDate) ?>'">
                    <div class="s-box-top">
                        <h4 class="s-box-title">Sales Order</h4>
                        <a href="Detail-SO.php?tanggal_awal=<?= urlencode($startDate) ?>&tanggal_akhir=<?= urlencode($endDate) ?>"
                    class="s-circle-btn" title="Lihat Detail Sales Order">
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
                <div class="s-metric-box"
                style="cursor:pointer;"
                onclick="window.location.href='Detail-WO.php?tanggal_awal=<?= urlencode($startDate) ?>&tanggal_akhir=<?= urlencode($endDate) ?>'">
                    <div class="s-box-top">
                        <h4 class="s-box-title">Work Order</h4>
                        <a href="Detail-WO.php?tanggal_awal=<?= urlencode($startDate) ?>&tanggal_akhir=<?= urlencode($endDate) ?>"
                        class="s-circle-btn" title="Lihat Detail Work Order">
                            <i class="bi bi-arrow-up-right"></i>
                        </a>
                    </div>

                    <div class="s-box-metric">
                        <span class="s-metric-num"><?= number_format($workOrderItemCount, 0, ',', '.') ?></span>
                        <span class="s-metric-unit">item</span>
                    </div>

                    <div class="s-box-footer">
                     <span>Total Work Order</span>
                        <strong><?= number_format($workOrderItemCount, 0, ',', '.') ?> WO</strong>
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
                        return ' Total Invoice: Rp ' +
                        new Intl.NumberFormat('id-ID').format(context.parsed.y);
                    }
                }
            }
        },
        scales: {
            y: {
            beginAtZero: true,
            ticks: {
        font: { size: 10 },
        callback: function(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
        }
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

        onClick: function(event, elements) {
            if (elements.length > 0) {
                const index = elements[0].index;
                const selectedStatus = logisticData[index].label;

                const params = new URLSearchParams({
                    tanggal_awal: '<?= htmlspecialchars($startDate) ?>',
                    tanggal_akhir: '<?= htmlspecialchars($endDate) ?>',
                    status: selectedStatus
                });

                window.location.href = 'Detail-Logistik.php?' + params.toString();
            }
        },

        onHover: function(event, elements) {
            event.native.target.style.cursor =
                elements.length > 0 ? 'pointer' : 'default';
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
