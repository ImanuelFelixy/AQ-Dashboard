<?php
$pageTitle = 'Dashboard Pembelian';
$activePage = 'pembelian';
$basePath = '../';

require_once __DIR__ . '/../includes/header.php';

// ==========================================
// DATA SEMENTARA (DUMMY)
// Nantinya akan diganti dengan data MySQL
// ==========================================

$startDate = $_GET['tanggal_awal'] ?? '2026-09-01';
$endDate   = $_GET['tanggal_akhir'] ?? '2026-09-30';

$currentUser = 'NAYLA';
$userIp = $_SERVER['REMOTE_ADDR'] ?? '192.168.1.78';
if ($userIp === '::1') {
    $userIp = '127.0.0.1';
}

// 1. Purchase Order
$totalPO = 25;
$totalPONominal = 9999999;

// 2. Outstanding PO
$outstandingPO = 10;
$outstandingPONominal = 9999999;

// 3. Pembelian Belum Dilunasi
$unpaidNominal = 9999999;

// 4. Pengajuan PO
$submissionCount = 35;

// 5. Diperlukan Pembelian Bahan (PPB)
$urgentPurchases = [
    [
        'name' => 'Lem Sealant Silikon Transparan 300ml',
        'code' => 'PPB-26090076',
        'qty'  => '6 tabung',
        'status' => 'critical',
        'badge' => 'Kritis'
    ],
    [
        'name' => 'Kaca Tempered Tebal 10mm (Custom Size)',
        'code' => 'PPB-26098765',
        'qty'  => '4 lembar',
        'status' => 'warning',
        'badge' => 'Menipis'
    ],
    [
        'name' => 'Lem Sealant Silikon Transparan 300ml',
        'code' => 'PPB-26090076',
        'qty'  => '6 tabung',
        'status' => 'critical',
        'badge' => 'Kritis'
    ],
    [
        'name' => 'Kaca Tempered Tebal 10mm (Custom Size)',
        'code' => 'PPB-26098765',
        'qty'  => '4 lembar',
        'status' => 'warning',
        'badge' => 'Menipis'
    ],
];

// 6. Pembelian per Periode (Data Harian 1-31)
$periodLabels = range(1, 31);
$periodData = [
    60, 48, 68, 42, 75, 14, 73, 15, 48, 54,
    50, 52, 58, 76, 45, 42, 38, 24, 70, 62,
    69, 48, 20, 22, 68, 50, 48, 52, 58, 55, 62
];

// 7. Pembelian Barang per Jenis
$categoryData = [
    [
        'label' => 'Bahan Baku',
        'percent' => 45,
        'po_count' => 11,
        'nominal' => 62400000,
        'subtext' => 'Barang Bahan Baku Kaca',
        'color' => '#0866c6'
    ],
    [
        'label' => 'Non-Bahan Baku',
        'percent' => 55,
        'po_count' => 14,
        'nominal' => 76250000,
        'subtext' => 'Barang Bahan Pembantu',
        'color' => '#60a5fa'
    ]
];
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* ==========================================
   GENERAL DASHBOARD STYLES
========================================== */

.purchase-dashboard {
    width: 100%;
    padding: 24px 28px;
    background: #f5f7fc;
    min-height: calc(100vh - 100px);
}

.purchase-dashboard * {
    box-sizing: border-box;
}

/* ==========================================
   HEADER DASHBOARD
========================================== */

.purchase-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
}

.purchase-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.purchase-title .heading-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #e8f1ff;
    color: #0866c6;
    font-size: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.purchase-title h1 {
    font-size: 22px;
    font-weight: 800;
    color: #25364d;
    margin: 0;
    line-height: 1.25;
}

.purchase-user-tag {
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
}

.purchase-user-tag span {
    color: #0866c6;
    font-weight: 800;
}

.purchase-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.purchase-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 14px;
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

.purchase-btn:hover {
    background: #edf4ff;
    color: #0866c6;
    border-color: #c7dbfb;
}

.purchase-btn.primary {
    background: #0866c6;
    color: #fff;
    border-color: #0866c6;
}

.purchase-btn.primary:hover {
    background: #0754a5;
    border-color: #0754a5;
    color: #fff;
}

/* ==========================================
   FILTER PERIODE
========================================== */

.purchase-filter {
    width: 100%;
    background: #eaf2ff;
    border: 1px solid #dce9fb;
    border-radius: 13px;
    padding: 18px 24px;
    margin-bottom: 20px;
}

.purchase-filter-title {
    font-size: 12px;
    font-weight: 800;
    color: #53647c;
    margin-bottom: 14px;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 8px;
}

.purchase-filter-title i {
    color: #0866c6;
    font-size: 14px;
}

.purchase-filter-form {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
}

.purchase-filter-form input[type="date"] {
    height: 50px;
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

.purchase-filter-form input[type="date"]:focus {
    outline: none;
    border-color: #0866c6;
    box-shadow: 0 0 0 3px rgba(8, 102, 198, .12);
}

.purchase-filter-form .separator {
    font-size: 13px;
    color: #738198;
    font-weight: 600;
    white-space: nowrap;
}

.purchase-filter-submit {
    height: 50px;
    padding: 0 28px;
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

.purchase-filter-submit:hover {
    background: #0754a5;
}

.purchase-today {
    height: 50px;
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

.purchase-today:hover {
    background: #f8fafc;
}

.purchase-today input[type="checkbox"] {
    accent-color: #0866c6;
    cursor: pointer;
    width: 15px;
    height: 15px;
}

/* ==========================================
   DASHBOARD TOP SECTION (METRICS & PPB)
========================================== */

.purchase-top-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1.35fr;
    gap: 16px;
    margin-bottom: 20px;
}

.purchase-left-stack {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-column: span 2;
    gap: 16px;
}

/* Base Card */
.p-card {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 2px 10px rgba(32, 66, 110, .03);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 158px;
    position: relative;
    transition: transform .2s ease, box-shadow .2s ease;
}

.p-card:hover {
    box-shadow: 0 6px 18px rgba(32, 66, 110, .06);
}

/* Card Borders Accent */
.p-card.card-default {
    border-color: #dce4f0;
}

.p-card.card-blue {
    border-color: #0875d1;
}

.p-card.card-red {
    border-color: #ef4444;
}

.p-card.card-green {
    border-color: #10b981;
}

.p-card.card-orange {
    border-color: #f7a072;
    background: #fffdfb;
}

/* Card Header */
.p-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
}

.p-card-title {
    font-size: 16px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
    line-height: 1.25;
}

.p-circle-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    font-size: 14px;
    flex-shrink: 0;
    cursor: pointer;
    transition: transform .2s ease, opacity .2s ease;
    text-decoration: none;
}

.p-circle-btn:hover {
    transform: scale(1.08);
}

.p-circle-btn.btn-gray {
    background: #e2e8f0;
    color: #334155;
}

.p-circle-btn.btn-blue {
    background: #dbeafe;
    color: #0875d1;
}

.p-circle-btn.btn-red {
    background: #fee2e2;
    color: #ef4444;
}

.p-circle-btn.btn-green {
    background: #d1fae5;
    color: #10b981;
}

.p-circle-btn.btn-orange {
    background: #ffedd5;
    color: #ea580c;
}

/* Card Body Metric */
.p-card-metric {
    margin: 12px 0 8px;
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.p-metric-number {
    font-size: 32px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
}

.p-metric-unit {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
}

.p-metric-nominal {
    font-size: 23px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.3px;
}

/* Card Footer */
.p-card-footer {
    font-size: 11.5px;
    color: #64748b;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
}

.p-card-footer strong {
    color: #1e293b;
    font-weight: 700;
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 11.5px;
    font-weight: 700;
}

.status-indicator.danger {
    color: #dc2626;
}

.status-indicator.success {
    color: #059669;
}

.status-indicator .indicator-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
}

.status-indicator.danger .indicator-dot {
    background: #dc2626;
    box-shadow: 0 0 0 3px #fee2e2;
}

.status-indicator.success .indicator-dot {
    background: #059669;
    box-shadow: 0 0 0 3px #d1fae5;
}

/* ==========================================
   PPB CARD (DI PERLUKAN PEMBELIAN BAHAN)
========================================== */

.p-ppb-card {
    background: #fffdfb;
    border: 2px solid #f7a072;
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 2px 10px rgba(247, 160, 114, .06);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.p-ppb-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 14px;
}

.p-ppb-header h2 {
    font-size: 18px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
    line-height: 1.25;
}

.p-ppb-alert-strip {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    padding: 4px 0;
}

.p-ppb-alert-label {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #d97706;
    font-size: 12px;
    font-weight: 700;
}

.p-ppb-link {
    color: #2563eb;
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: gap .2s;
}

.p-ppb-link:hover {
    gap: 7px;
    color: #1d4ed8;
}

.p-ppb-list {
    display: flex;
    flex-direction: column;
    gap: 9px;
    flex: 1;
}

.p-ppb-item {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 11px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .02);
    transition: border-color .2s;
}

.p-ppb-item:hover {
    border-color: #fed7aa;
}

.p-ppb-item-left {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    min-width: 0;
}

.p-ppb-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-top: 5px;
    flex-shrink: 0;
}

.p-ppb-dot.critical {
    background: #ef4444;
}

.p-ppb-dot.warning {
    background: #f59e0b;
}

.p-ppb-item-content {
    min-width: 0;
}

.p-ppb-item-title {
    font-size: 12px;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.p-ppb-item-sub {
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
}

.p-ppb-item-badge {
    padding: 4px 10px;
    border-radius: 20px;
    background: #f1f5f9;
    color: #475569;
    font-size: 9.5px;
    font-weight: 700;
    white-space: nowrap;
    letter-spacing: 0.3px;
}

/* ==========================================
   CHARTS SECTION (BOTTOM GRID)
========================================== */

.purchase-bottom-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.purchase-chart-card {
    background: #fff;
    border: 1px solid #e9eef6;
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 2px 10px rgba(32, 66, 110, .03);
    display: flex;
    flex-direction: column;
}

.purchase-chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}

.purchase-chart-title {
    font-size: 16px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

/* Line Chart Specific */
.purchase-line-container {
    position: relative;
    height: 250px;
    width: 100%;
}

.purchase-line-legend {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 14px;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
}

.purchase-legend-symbol {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.purchase-legend-symbol .line {
    width: 16px;
    height: 2px;
    background: #0866c6;
    display: inline-block;
}

.purchase-legend-symbol .dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #0866c6;
    display: inline-block;
    margin-left: -11px;
}

/* Donut Chart Specific */
.purchase-donut-layout {
    display: flex;
    align-items: center;
    gap: 22px;
    min-height: 250px;
}

.purchase-donut-wrapper {
    width: 160px;
    height: 160px;
    position: relative;
    flex-shrink: 0;
}

.purchase-donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    pointer-events: none;
    text-align: center;
}

.purchase-donut-center small {
    color: #64748b;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.purchase-donut-center strong {
    color: #1e293b;
    font-size: 26px;
    font-weight: 800;
    line-height: 1.1;
    margin: 2px 0;
}

.purchase-donut-center span {
    color: #64748b;
    font-size: 9.5px;
    font-weight: 700;
}

.purchase-category-list {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 0;
}

.purchase-category-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
    padding: 12px 14px;
    transition: transform .15s ease, border-color .15s ease;
}

.purchase-category-box:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}

.purchase-cat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
}

.purchase-cat-label {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 11.5px;
    font-weight: 700;
    color: #1e293b;
}

.purchase-cat-color {
    width: 10px;
    height: 10px;
    border-radius: 3px;
    flex-shrink: 0;
}

.purchase-cat-po {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
}

.purchase-cat-nominal {
    font-size: 16px;
    font-weight: 800;
    color: #0866c6;
    margin-bottom: 2px;
}

.purchase-category-box:nth-child(2) .purchase-cat-nominal {
    color: #2563eb;
}

.purchase-cat-subtext {
    font-size: 10.5px;
    font-weight: 600;
    color: #64748b;
}

/* ==========================================
   SYSTEM STATUS FOOTER
========================================== */

.purchase-footer {
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

.purchase-footer .online {
    color: #34d399;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 6px;
}

.purchase-footer .online-dot {
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

@media (max-width: 1200px) {
    .purchase-top-grid {
        grid-template-columns: 1fr;
    }

    .purchase-left-stack {
        grid-column: span 1;
    }

    .p-ppb-card {
        height: auto;
    }

    .purchase-bottom-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .purchase-dashboard {
        padding: 16px 14px;
    }

    .purchase-heading {
        flex-direction: column;
    }

    .purchase-actions {
        width: 100%;
    }

    .purchase-actions .purchase-btn {
        flex: 1;
    }

    .purchase-filter {
        padding: 16px;
    }

    .purchase-filter-form {
        flex-wrap: wrap;
    }

    .purchase-filter-form input[type="date"] {
        flex: 1 1 45%;
        min-width: 120px;
    }

    .purchase-filter-form .separator {
        display: none;
    }

    .purchase-filter-submit {
        flex: 1 1 100%;
    }

    .purchase-today {
        width: 100%;
        justify-content: center;
    }

    .purchase-left-stack {
        grid-template-columns: 1fr;
    }

    .purchase-donut-layout {
        flex-direction: column;
        gap: 16px;
    }

    .purchase-line-container {
        height: 200px;
    }

    .purchase-footer {
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

    .purchase-dashboard {
        padding: 10px;
        background: white;
    }

    .purchase-actions,
    .purchase-filter {
        display: none !important;
    }

    .p-card,
    .p-ppb-card,
    .purchase-chart-card {
        box-shadow: none !important;
        break-inside: avoid;
    }

    .purchase-footer {
        display: none !important;
    }
}
</style>

<!-- ==========================================
     MAIN DASHBOARD
========================================== -->

<main class="purchase-dashboard">

    <!-- HEADER DASHBOARD -->
    <section class="purchase-heading">
        <div>
            <div class="purchase-title">
                <div class="heading-icon">
                    <i class="bi bi-cart-check-fill"></i>
                </div>
                <div>
                    <h1>Dashboard Monitoring Pembelian</h1>
                </div>
            </div>
        </div>

        <div class="purchase-actions">
            <span class="purchase-user-tag me-2">Halo, <span><?= htmlspecialchars($currentUser) ?></span></span>

            <button type="button" class="purchase-btn" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh Data
            </button>

            <button type="button" class="purchase-btn" onclick="exportExcel()">
                <i class="bi bi-file-earmark-excel"></i>
                Export XLS
            </button>

            <button type="button" class="purchase-btn primary" onclick="window.print()">
                <i class="bi bi-printer"></i>
                Print Laporan
            </button>
        </div>
    </section>

    <!-- FILTER PERIODE -->
    <section class="purchase-filter">
        <div class="purchase-filter-title">
            <i class="bi bi-calendar3"></i>
            Periode Transaksi
        </div>

        <form method="GET" class="purchase-filter-form">
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

            <button type="submit" class="purchase-filter-submit">
                Tampilkan
            </button>

            <label class="purchase-today">
                <input
                    type="checkbox"
                    id="todayOnly"
                    <?= ($startDate === date('Y-m-d') && $endDate === date('Y-m-d')) ? 'checked' : '' ?>
                >
                <i class="bi bi-calendar-check"></i>
                Hari Ini Saja
            </label>
        </form>
    </section>

    <!-- ======================================
         TOP GRID: 4 METRIC CARDS + PPB CARD
    ======================================= -->
    <section class="purchase-top-grid">

        <!-- Left 2x2 Metric Cards Grid -->
        <div class="purchase-left-stack">

            <!-- Card 1: Purchase Order -->
            <div class="p-card card-default">
                <div class="p-card-header">
                    <h3 class="p-card-title">Purchase Order</h3>
                    <a href="#" class="p-circle-btn btn-gray" title="Lihat Detail Purchase Order">
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                </div>

                <div class="p-card-metric">
                    <span class="p-metric-number"><?= number_format($totalPO, 0, ',', '.') ?></span>
                    <span class="p-metric-unit">Transaksi</span>
                </div>

                <div class="p-card-footer">
                    <span>Total Nominal :</span>
                    <strong>Rp <?= number_format($totalPONominal, 0, ',', '.') ?>, 00</strong>
                </div>
            </div>

            <!-- Card 2: Outstanding PO -->
            <div class="p-card card-blue">
                <div class="p-card-header">
                    <h3 class="p-card-title">Outstanding PO</h3>
                    <a href="#" class="p-circle-btn btn-blue" title="Lihat Detail Outstanding PO">
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                </div>

                <div class="p-card-metric">
                    <span class="p-metric-number"><?= number_format($outstandingPO, 0, ',', '.') ?></span>
                    <span class="p-metric-unit">Transaksi</span>
                </div>

                <div class="p-card-footer">
                    <span>Total Nominal :</span>
                    <strong>Rp <?= number_format($outstandingPONominal, 0, ',', '.') ?>, 00</strong>
                </div>
            </div>

            <!-- Card 3: Pembelian Belum Dilunasi -->
            <div class="p-card card-red">
                <div class="p-card-header">
                    <h3 class="p-card-title">Pembelian Belum Dilunasi</h3>
                    <a href="#" class="p-circle-btn btn-red" title="Lihat Detail Pembelian Belum Dilunasi">
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                </div>

                <div class="p-card-metric">
                    <span class="p-metric-nominal">Rp <?= number_format($unpaidNominal, 0, ',', '.') ?>, 00</span>
                </div>

                <div class="p-card-footer">
                    <span class="status-indicator danger">
                        <span class="indicator-dot"></span>
                        Jatuh tempo berjalan
                    </span>
                </div>
            </div>

            <!-- Card 4: Pengajuan PO -->
            <div class="p-card card-green">
                <div class="p-card-header">
                    <h3 class="p-card-title">Pengajuan PO</h3>
                    <a href="#" class="p-circle-btn btn-green" title="Lihat Detail Pengajuan PO">
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                </div>

                <div class="p-card-metric">
                    <span class="p-metric-number"><?= number_format($submissionCount, 0, ',', '.') ?></span>
                    <span class="p-metric-unit">pengajuan</span>
                </div>

                <div class="p-card-footer">
                    <span class="status-indicator success">
                        <span class="indicator-dot"></span>
                        Memerlukan Konfirmasi
                    </span>
                </div>
            </div>

        </div>

        <!-- Right: Diperlukan Pembelian Bahan (PPB) -->
        <div class="p-ppb-card">
            <div class="p-ppb-header">
                <h2>Diperlukan Pembelian Bahan</h2>
                <a href="#" class="p-circle-btn btn-orange" title="Buka Modul PPB">
                    <i class="bi bi-arrow-up-right"></i>
                </a>
            </div>

            <div class="p-ppb-alert-strip">
                <div class="p-ppb-alert-label">
                    <i class="bi bi-exclamation-triangle"></i>
                    Diperlukan Pembelian Bahan
                </div>
                <a href="#" class="p-ppb-link">
                    Lihat Semua Kebutuhan &rarr;
                </a>
            </div>

            <div class="p-ppb-list">
                <?php foreach ($urgentPurchases as $item): ?>
                    <div class="p-ppb-item">
                        <div class="p-ppb-item-left">
                            <span class="p-ppb-dot <?= $item['status'] ?>"></span>
                            <div class="p-ppb-item-content">
                                <div class="p-ppb-item-title"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="p-ppb-item-sub"><?= htmlspecialchars($item['code']) ?> = <?= htmlspecialchars($item['qty']) ?></div>
                            </div>
                        </div>
                        <div class="p-ppb-item-badge">
                            <?= htmlspecialchars($item['badge']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </section>

    <!-- ======================================
         BOTTOM GRID: CHARTS
    ======================================= -->
    <section class="purchase-bottom-grid">

        <!-- Chart 1: Pembelian per Periode (Line Chart) -->
        <div class="purchase-chart-card">
            <div class="purchase-chart-header">
                <h3 class="purchase-chart-title">Pembelian per Periode</h3>
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
        </div>

        <!-- Chart 2: Pembelian Barang per Jenis (Donut Chart) -->
        <div class="purchase-chart-card">
            <div class="purchase-chart-header">
                <h3 class="purchase-chart-title">Pembelian Barang per Jenis</h3>
            </div>

            <div class="purchase-donut-layout">
                <!-- Donut Canvas & Center text -->
                <div class="purchase-donut-wrapper">
                    <canvas id="categoryDonutChart"></canvas>
                    <div class="purchase-donut-center">
                        <small>TOTAL PO</small>
                        <strong><?= $totalPO ?></strong>
                        <span>PO Aktif</span>
                    </div>
                </div>

                <!-- Breakdown Info Boxes -->
                <div class="purchase-category-list">
                    <?php foreach ($categoryData as $cat): ?>
                        <div class="purchase-category-box">
                            <div class="purchase-cat-top">
                                <div class="purchase-cat-label">
                                    <span class="purchase-cat-color" style="background: <?= $cat['color'] ?>;"></span>
                                    <span><?= htmlspecialchars($cat['label']) ?> (<?= $cat['percent'] ?>%)</span>
                                </div>
                                <span class="purchase-cat-po"><?= $cat['po_count'] ?> PO</span>
                            </div>

                            <div class="purchase-cat-nominal">
                                Rp <?= number_format($cat['nominal'], 0, ',', '.') ?>
                            </div>

                            <div class="purchase-cat-subtext">
                                <?= htmlspecialchars($cat['subtext']) ?>
                            </div>
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
// 1. LINE CHART: PEMBELIAN PER PERIODE
// ======================================
const periodLabels = <?= json_encode($periodLabels) ?>;
const periodData = <?= json_encode($periodData) ?>;

const ctxPeriod = document.getElementById('purchasePeriodChart').getContext('2d');

new Chart(ctxPeriod, {
    type: 'line',
    data: {
        labels: periodLabels,
        datasets: [{
            label: 'Total Pembelian',
            data: periodData,
            borderColor: '#0875d1',
            backgroundColor: 'rgba(8, 117, 209, 0.05)',
            borderWidth: 2,
            tension: 0.25,
            fill: true,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#0875d1',
            pointBorderWidth: 2,
            pointRadius: 3.5,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: '#0875d1',
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
                        return ' Total: ' + context.parsed.y + ' Transaksi';
                    }
                }
            }
        },
        scales: {
            y: {
                min: 0,
                max: 100,
                ticks: {
                    stepSize: 25,
                    font: { size: 10 }
                },
                grid: {
                    color: '#e2e8f0',
                    drawBorder: false
                }
            },
            x: {
                ticks: {
                    font: { size: 10 }
                },
                grid: {
                    color: '#edf2f7',
                    drawBorder: false
                }
            }
        }
    }
});

// ======================================
// 2. DONUT CHART: PEMBELIAN BARANG PER JENIS
// ======================================
const categoryData = <?= json_encode($categoryData) ?>;
const catValues = categoryData.map(c => c.percent);
const catColors = categoryData.map(c => c.color);

const ctxCategory = document.getElementById('categoryDonutChart').getContext('2d');

new Chart(ctxCategory, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: catValues,
            backgroundColor: catColors,
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
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
                        const item = categoryData[context.dataIndex];
                        return ` ${item.label}: ${item.percent}% (${item.po_count} PO)`;
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
    alert('Fitur Export Excel akan diaktifkan setelah data pembelian terhubung dengan database.');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
