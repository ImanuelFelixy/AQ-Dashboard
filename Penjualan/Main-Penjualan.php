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
/* =========================================
   AQUAMATE — PREMIUM SALES DASHBOARD
========================================= */

.sales-dashboard {
    position: relative;
    isolation: isolate;
    width: 100%;
    min-height: calc(100vh - 100px);
    padding: 30px 32px;
    background:
        radial-gradient(ellipse at 5% 0%, rgba(59,130,246,.13), transparent 32%),
        radial-gradient(ellipse at 95% 15%, rgba(14,165,233,.10), transparent 30%),
        linear-gradient(135deg, #edf4ff 0%, #f5f8ff 48%, #eaf2fc 100%);
}

.sales-dashboard * {
    box-sizing: border-box;
}

/* TOPBAR */

.sales-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 22px;
}

.sales-user-tag {
    color: #64748b;
    font-size: 14px;
    font-weight: 600;
}

.sales-user-tag span {
    color: #075db5;
    font-weight: 800;
}

.sales-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.sales-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 17px;
    border: 1px solid #dce7f5;
    border-radius: 11px;
    background: rgba(255,255,255,.9);
    color: #475569;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 3px 10px rgba(30,64,110,.04);
    transition: all .25s ease;
}

.sales-btn:hover {
    transform: translateY(-2px);
    background: #eff6ff;
    color: #075db5;
    border-color: #b9d6fb;
    box-shadow: 0 6px 16px rgba(37,99,235,.10);
}

.sales-btn.primary {
    background: linear-gradient(135deg, #0878d5, #0754ad);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 5px 14px rgba(8,102,198,.22);
}

.sales-btn.primary:hover {
    background: linear-gradient(135deg, #0868bd, #064895);
    color: #fff;
}

/* =========================================
   BANNER & FILTER
========================================= */

.sales-banner-card {
    position: relative;
    overflow: hidden;
    background: #fff;
    border: 1px solid #dce8f7;
    border-radius: 20px;
    margin-bottom: 24px;
    box-shadow: 0 10px 30px rgba(30,64,110,.07);
}

.sales-banner-header {
    position: relative;
    overflow: hidden;
    padding: 30px 32px;
    color: #fff;
    background:
        radial-gradient(circle at 90% 20%, rgba(255,255,255,.18), transparent 25%),
        linear-gradient(115deg, #073b78 0%, #075db5 52%, #1688e5 100%);
}

.sales-banner-header::after {
    content: "";
    position: absolute;
    width: 260px;
    height: 260px;
    right: -70px;
    bottom: -190px;
    border: 35px solid rgba(255,255,255,.09);
    border-radius: 50%;
}

.sales-banner-title {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0 0 9px;
    color: #ffffff !important;
    font-size: 25px;
    font-weight: 800;
    letter-spacing: -.6px;
}

.sales-banner-title i {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 12px;
    background: rgba(255,255,255,.14);
    font-size: 20px;
}

.sales-banner-desc {
    position: relative;
    z-index: 1;
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.88) !important;
    font-size: 13px;
    line-height: 1.7;
}

.sales-filter-body {
    padding: 22px 28px 25px;
    background: linear-gradient(135deg, #f5f9ff, #eaf3ff);
    border-top: 1px solid #dce8f7;
}

.sales-filter-title {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 15px;
    color: #334e70;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
}

.sales-filter-title i {
    color: #0878d5;
    font-size: 16px;
}

.sales-filter-form {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
}

.sales-filter-form input[type="date"] {
    height: 49px;
    flex: 1;
    min-width: 0;
    padding: 0 15px;
    border: 1px solid #d8e4f3;
    border-radius: 11px;
    background: #fff;
    color: #334155;
    font-size: 13px;
    font-weight: 600;
    transition: all .2s ease;
}

.sales-filter-form input[type="date"]:focus {
    outline: none;
    border-color: #1684df;
    box-shadow: 0 0 0 4px rgba(22,132,223,.10);
}

.sales-filter-form .separator {
    color: #7b8da5;
    font-size: 13px;
    font-weight: 700;
}

.sales-filter-submit {
    height: 49px;
    padding: 0 27px;
    border: none;
    border-radius: 11px;
    background: linear-gradient(135deg, #087bd8, #0756b0);
    color: #fff;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 5px 13px rgba(8,102,198,.20);
    transition: all .25s ease;
}

.sales-filter-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(8,102,198,.25);
}

.sales-today {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    height: 49px;
    padding: 0 17px;
    margin: 0;
    border: 1px solid #d8e4f3;
    border-radius: 11px;
    background: #fff;
    color: #52657f;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
    transition: all .2s ease;
}

.sales-today:hover {
    border-color: #a9cdf7;
    background: #f0f7ff;
}

.sales-today input {
    width: 15px;
    height: 15px;
    accent-color: #0878d5;
}

/* =========================================
   INVOICE CHART
========================================= */

.sales-invoice-card {
    position: relative;
    overflow: hidden;
    padding: 25px 28px 24px;
    margin-bottom: 24px;
    border: 1px solid #e0eaf6;
    border-radius: 20px;
    background: linear-gradient(145deg, #ffffff 0%, #fbfdff 100%);
    box-shadow: 0 9px 28px rgba(30,64,110,.065);
    transition: all .25s ease;
}

.sales-invoice-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #0754ad, #1688e5, #69c5ff);
}

.sales-invoice-header {
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    margin-bottom: 22px;
}

.sales-invoice-title {
    display: inline-flex;
    align-items: center;
    gap: 11px;
    margin: 0;
    color: #17365d;
    font-size: 20px;
    font-weight: 800;
    letter-spacing: -.3px;
}

.invoice-legend-symbol {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    color: #0878d5;
}

.invoice-legend-symbol .line {
    width: 20px;
    height: 3px;
    border-radius: 5px;
    background: linear-gradient(90deg, #0754ad, #36a3f5);
}

.invoice-legend-symbol .dot {
    width: 9px;
    height: 9px;
    margin-left: -14px;
    border: 2px solid #1688e5;
    border-radius: 50%;
    background: #fff;
}

.sales-line-container {
    position: relative;
    width: 100%;
    height: 290px;
}

/* =========================================
   BOTTOM CARDS
========================================= */

.sales-bottom-grid {
    display: grid;
    grid-template-columns: 1.45fr 1fr;
    gap: 22px;
    margin-bottom: 24px;
}

.sales-sub-card {
    display: flex;
    flex-direction: column;
    padding: 25px;
    border: 1px solid #e0eaf6;
    border-radius: 20px;
    background: #fff;
    box-shadow: 0 9px 28px rgba(30,64,110,.06);
    transition: transform .25s ease, box-shadow .25s ease;
}

.sales-sub-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 13px 32px rgba(30,64,110,.10);
}

.sales-sub-header {
    margin-bottom: 23px;
    text-align: center;
}

.sales-sub-title {
    margin: 0;
    color: #17365d;
    font-size: 18px;
    font-weight: 800;
}

/* OPERATIONAL METRICS */

.sales-operasional-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 17px;
    flex: 1;
}

.s-metric-box {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 165px;
    padding: 21px;
    border: 1px solid #d7e8fb;
    border-radius: 16px;
    background: linear-gradient(145deg, #f5faff, #eaf4ff);
    box-shadow: 0 4px 13px rgba(8,102,198,.045);
    transition: all .25s ease;
}

.s-metric-box::after {
    content: "";
    position: absolute;
    width: 110px;
    height: 110px;
    right: -38px;
    bottom: -52px;
    border: 17px solid rgba(8,120,213,.07);
    border-radius: 50%;
}

.s-metric-box:hover {
    transform: translateY(-4px);
    border-color: #9bc9f7;
    box-shadow: 0 10px 22px rgba(8,102,198,.12);
}

.s-box-top {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.s-box-title {
    margin: 0;
    color: #29496e;
    font-size: 15px;
    font-weight: 800;
}

.s-circle-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: 1px solid #c8e1fc;
    border-radius: 11px;
    background: #fff;
    color: #0878d5;
    font-size: 14px;
    text-decoration: none;
    cursor: pointer;
    transition: all .2s ease;
}

.s-circle-btn:hover {
    transform: rotate(45deg);
    background: #0878d5;
    color: #fff;
}

.s-box-metric {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: baseline;
    gap: 9px;
    margin: 18px 0 12px;
}

.s-metric-num {
    color: #0754ad;
    font-size: 37px;
    font-weight: 800;
    line-height: 1;
}

.s-metric-unit {
    color: #71839b;
    font-size: 13px;
    font-weight: 700;
}

.s-box-footer {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-top: auto;
    color: #71839b;
    font-size: 11px;
    font-weight: 600;
}

.s-box-footer strong {
    color: #29496e;
    font-weight: 800;
}

/* LOGISTICS CHART */

.sales-pie-layout {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    min-height: 220px;
}

.sales-pie-wrapper {
    position: relative;
    width: 180px;
    height: 180px;
    margin: 5px 0 20px;
}

.sales-pie-legend {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: auto;
}

.sales-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
}

.sales-legend-dot {
    width: 9px;
    height: 9px;
    flex-shrink: 0;
    border-radius: 50%;
}

/* =========================================
   FOOTER
========================================= */

.sales-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 15px 22px;
    margin-top: 15px;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 13px;
    background: linear-gradient(110deg, #102b4b, #173e68);
    color: #c1d0e3;
    font-size: 11px;
}

.sales-footer .online {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #6ee7b7;
    font-weight: 800;
}

.sales-footer .online-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #34d399;
    box-shadow: 0 0 9px rgba(52,211,153,.7);
}

/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 1100px) {
    .sales-bottom-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .sales-dashboard {
        padding: 17px 14px;
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
        padding: 23px 20px;
    }

    .sales-banner-title {
        font-size: 19px;
    }

    .sales-filter-body {
        padding: 18px;
    }

    .sales-filter-form {
        flex-wrap: wrap;
    }

    .sales-filter-form input[type="date"] {
        flex: 1 1 40%;
        min-width: 120px;
    }

    .sales-filter-form .separator {
        display: none;
    }

    .sales-filter-submit,
    .sales-today {
        flex: 1 1 100%;
    }

    .sales-invoice-card,
    .sales-sub-card {
        padding: 19px;
    }

    .sales-line-container {
        height: 220px;
    }

    .sales-operasional-container {
        grid-template-columns: 1fr;
    }

    .sales-pie-wrapper {
        width: 155px;
        height: 155px;
    }

    .sales-footer {
        flex-direction: column;
        align-items: flex-start;
    }
}

/* PRINT */

@media print {
    body {
        background: #fff !important;
    }

    .sales-dashboard {
        padding: 10px;
        background: #fff;
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

        <style>
/* ACTIVITY MONITORING */

.activity-toggle-container {
    display: flex;
    justify-content: flex-end;
    width: 100%;
    grid-column: 1 / -1;
    margin: 22px 0 16px;
}

.activity-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 11px 20px;
    border: 1px solid #dbe8f8;
    border-radius: 10px;
    background: #ffffff;
    color: #145da8;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 3px 12px rgba(20, 70, 130, .07);
    transition: .2s ease;
}

.activity-toggle-btn:hover,
.activity-toggle-btn.active {
    background: #0866c6;
    color: #ffffff;
    border-color: #0866c6;
}

.activity-chevron {
    transition: transform .2s ease;
}

.activity-toggle-btn.active .activity-chevron {
    transform: rotate(180deg);
}

/* PANEL HARUS DI BAWAH TOMBOL */

.activity-history-panel {
    display: block;
    width: 100%;
    grid-column: 1 / -1;
    padding: 25px;
    margin-bottom: 25px;
    background: #ffffff;
    border: 1px solid #e2ebf7;
    border-radius: 16px;
    box-shadow: 0 5px 20px rgba(30, 64, 110, .06);
    box-sizing: border-box;
}

.activity-history-panel[hidden] {
    display: none !important;
}

.activity-history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.activity-history-header h3 {
    margin: 0 0 6px;
    color: #173b68;
    font-size: 19px;
    font-weight: 700;
}

.activity-history-header p {
    margin: 0;
    color: #8796aa;
    font-size: 13px;
}

.activity-view-all {
    padding: 10px 15px;
    background: #edf5ff;
    color: #0866c6;
    border-radius: 9px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}

.activity-history-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.activity-history-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.activity-history-table th {
    padding: 14px;
    background: #edf4fd;
    color: #31577f;
    text-align: left;
}

.activity-history-table td {
    padding: 14px;
    border-bottom: 1px solid #edf1f7;
    color: #64748b;
}

.activity-empty {
    text-align: center;
    padding: 30px !important;
}
</style>
<!-- TOMBOL RIWAYAT AKTIVITAS -->
<?php
$stmtActivity = $conn->query("
    SELECT nama_user, aktivitas, modul, keterangan, created_at
    FROM user_activity_log
    ORDER BY created_at DESC
    LIMIT 5
");
$recentActivities = $stmtActivity->fetchAll();
?>

<div class="activity-toggle-container">
    <button type="button"
            class="activity-toggle-btn"
            id="activityToggleBtn"
            onclick="toggleActivityHistory()">

        <i class="bi bi-clock-history"></i>
        Riwayat Aktivitas
        <i class="bi bi-chevron-down activity-chevron"></i>
    </button>
</div>

<div id="activityHistoryPanel" class="activity-history-panel" hidden>

    <div class="activity-history-header">
        <div>
            <h3>Riwayat Aktivitas</h3>
            <p>Pantau aktivitas terbaru Dashboard Aquamate.</p>
        </div>

        <a href="../Riwayat-Aktivitas.php" class="activity-view-all">
            Lihat Semua <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="activity-history-table-wrapper">
        <table class="activity-history-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Nama User</th>
                    <th>Aktivitas</th>
                    <th>Modul</th>
                    <th>Keterangan</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($recentActivities)): ?>
                    <?php foreach ($recentActivities as $activity): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?></td>
                            <td><?= htmlspecialchars($activity['nama_user']) ?></td>
                            <td><?= htmlspecialchars($activity['aktivitas']) ?></td>
                            <td><?= htmlspecialchars($activity['modul']) ?></td>
                            <td><?= htmlspecialchars($activity['keterangan'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="activity-empty">
                            Belum ada aktivitas.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

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
    type: 'doughnut',
    data: {
        labels: logLabels,
        datasets: [{
            data: logValues,
            backgroundColor: logColors,
            borderWidth: 1,
            borderColor: '#ffffff',
            hoverOffset: 8,
borderWidth: 4,
borderColor: '#ffffff',
spacing: 3,
borderRadius: 5
        }]
    },
    options: {
        cutout: '72%',
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
// TOGGLE RIWAYAT AKTIVITAS
function toggleActivityHistory() {
    const panel = document.getElementById('activityHistoryPanel');
    const button = document.getElementById('activityToggleBtn');

    if (!panel || !button) return;

    const isHidden = panel.hidden;

    panel.hidden = !isHidden;
    button.classList.toggle('active', isHidden);

    button.setAttribute(
        'aria-expanded',
        isHidden ? 'true' : 'false'
    );
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
