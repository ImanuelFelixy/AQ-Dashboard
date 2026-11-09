<?php
$pageTitle = 'Dashboard Pembelian';
$activePage = 'pembelian';
$basePath = '../';

require_once __DIR__ . '/../includes/header.php';

// ==========================================
// KONEKSI DATABASE (aq_pembelian)
// Sesuaikan kredensial di bawah dengan server Anda
// (nilai default = XAMPP: root tanpa password).
// ==========================================
if (!defined('PEMBELIAN_DB_HOST')) define('PEMBELIAN_DB_HOST', '127.0.0.1');
if (!defined('PEMBELIAN_DB_USER')) define('PEMBELIAN_DB_USER', 'root');
if (!defined('PEMBELIAN_DB_PASS')) define('PEMBELIAN_DB_PASS', '');
if (!defined('PEMBELIAN_DB_NAME')) define('PEMBELIAN_DB_NAME', 'aq_pembelian');
if (!defined('PEMBELIAN_DB_PORT')) define('PEMBELIAN_DB_PORT', 3306);

$dbPembelian = null;
$dbPembelianError = null;
try {
    $dbPembelian = new mysqli(
        PEMBELIAN_DB_HOST,
        PEMBELIAN_DB_USER,
        PEMBELIAN_DB_PASS,
        PEMBELIAN_DB_NAME,
        (int) PEMBELIAN_DB_PORT
    );
    $dbPembelian->set_charset('utf8mb4');
} catch (Throwable $e) {
    $dbPembelian = null;
    $dbPembelianError = 'Koneksi database pembelian gagal. Periksa pengaturan di main_pembelian.php.';
    error_log('[pembelian] ' . $e->getMessage());
}

// ==========================================
// HELPER BERSAMA (dipakai semua modul)
// ==========================================

/** Jalankan SELECT ber-prepared-statement dan kembalikan seluruh baris (array asosiatif). */
function pembelian_query(?mysqli $db, string $sql, string $types = '', array $params = []): array
{
    if (!$db) {
        return [];
    }

    try {
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            error_log('[pembelian] prepare gagal: ' . $db->error);
            return [];
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            error_log('[pembelian] execute gagal: ' . $stmt->error);
            $stmt->close();
            return [];
        }
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    } catch (Throwable $e) {
        error_log('[pembelian] query gagal: ' . $e->getMessage());
        return [];
    }
}

/** json_encode yang aman disisipkan ke dalam tag <script>. */
function pembelian_json($value): string
{
    return json_encode(
        $value,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
    );
}

/** Validasi format tanggal Y-m-d; kembalikan $default jika tidak valid. */
function pembelian_valid_date($value, string $default): string
{
    if (is_string($value)) {
        $d = DateTime::createFromFormat('Y-m-d', $value);
        if ($d && $d->format('Y-m-d') === $value) {
            return $value;
        }
    }
    return $default;
}

/**
 * Ekspresi SQL tanggal Nota Beli (NB) untuk query atas `mutasi_pembelian` (alias m)
 * yang di-LEFT JOIN ke `payable_aging` (alias p).
 *
 * Kolom tgl_faktur_pajak di mutasi_pembelian berisi 0000-00-00 pada seluruh baris,
 * sehingga tanggal NB diambil dari payable_aging.tgl_pembelian. Untuk NB yang tidak
 * ada di payable_aging (mis. sudah lunas), dipakai tanggal 1 pada bulan yang
 * tertanam di nomor NB (NB/00/YYMMxxxx).
 */
function pembelian_tgl_nb_sql(): string
{
    return "COALESCE(p.tgl_pembelian, STR_TO_DATE(CONCAT('20', SUBSTRING(m.no_trans, 7, 2), SUBSTRING(m.no_trans, 9, 2), '01'), '%Y%m%d'))";
}

// ==========================================
// MODUL DASHBOARD
// ==========================================
require_once __DIR__ . '/purchase_order.php';
require_once __DIR__ . '/outsatnding_po.php';
require_once __DIR__ . '/pembelian_belum_dilunasi.php';
require_once __DIR__ . '/pembelian_periode.php';
require_once __DIR__ . '/pembelian_bahan_baku.php';
require_once __DIR__ . '/purchase_requisition.php';

// ==========================================
// FILTER PERIODE & INFO PENGGUNA
// ==========================================

$startDate = pembelian_valid_date($_GET['tanggal_awal'] ?? null, '2026-09-01');
$endDate   = pembelian_valid_date($_GET['tanggal_akhir'] ?? null, '2026-09-30');

$currentUser = 'NAYLA';
$userIp = $_SERVER['REMOTE_ADDR'] ?? '192.168.1.78';
if ($userIp === '::1') {
    $userIp = '127.0.0.1';
}

// ==========================================
// DATA DARI DATABASE
// ==========================================

// Purchase Order
$allPoList = getPurchaseOrderList($dbPembelian, $startDate, $endDate);

// Outstanding PO
$outstandingList = getOutstandingPoList($dbPembelian, $startDate, $endDate);

// Pembelian Belum Dilunasi
$unpaidList = getUnpaidList($dbPembelian, $startDate, $endDate);

// Pembelian Bahan Baku Kaca (grafik batang Tonase & Luas)
$bahanBaku = getBahanBakuData($dbPembelian, $startDate, $endDate);

// Pembelian per Periode (grafik garis harian + detail)
$periodChart = getPurchasePeriodData($dbPembelian, $startDate, $endDate);

// Purchase Request
$purchaseRequestList = getPurchaseRequestList($dbPembelian, $startDate, $endDate);
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
   HEADER DASHBOARD (PRODUCTION STYLE)
========================================== */

.production-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: nowrap; /* Mencegah elemen terpaksa turun ke bawah */
    gap: 20px;
    margin-bottom: 20px;
}

.production-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.production-title .heading-icon {
    color: #0866c6;
    font-size: 22px;
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

.production-title h1 {
    font-size: 22px;
    font-weight: 800;
    color: #25364d;
    margin: 0;
    white-space: nowrap;
}

.production-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.production-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 12px;
    border-radius: 7px;
    border: 1px solid #dce5f1;
    background: #fff;
    color: #46566d;
    font-size: 10px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.production-btn:hover {
    background: #edf4ff;
    color: #0866c6;
}

.production-btn.primary {
    background: #0866c6;
    color: #fff;
    border-color: #0866c6;
}

.production-btn.primary:hover {
    background: #0754a5;
}

/* ==========================================
   FILTER PERIODE - FULL WIDTH (PRODUCTION STYLE)
========================================== */

.production-filter {
    width: 100%;
    background: #eaf2ff;
    border: 1px solid #dce9fb;
    border-radius: 13px;
    padding: 20px 26px;
    margin-bottom: 18px;
}

.production-filter-title {
    font-size: 12px;
    font-weight: 800;
    color: #53647c;
    margin-bottom: 16px;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 8px;
}

.production-filter-title i {
    color: #0866c6;
}

.production-filter-form {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
}

.production-filter-form input[type="date"] {
    height: 54px;
    flex: 1;
    min-width: 0;
    width: auto;
    padding: 0 15px;
    background: #fff;
    border: 1px solid #dce4f0;
    border-radius: 9px;
    color: #42536b;
    font-size: 13px;
}

.production-filter-form .separator {
    font-size: 13px;
    color: #738198;
    white-space: nowrap;
}

.production-filter-submit {
    height: 54px;
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

.production-filter-submit:hover {
    background: #0754a5;
}

.production-today {
    height: 54px;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 0 18px;
    background: white;
    border: 1px solid #dce4f0;
    border-radius: 9px;
    color: #53647c;
    font-size: 12px;
    white-space: nowrap;
    cursor: pointer;
}
/* ==========================================
   DASHBOARD TOP SECTION (METRICS & BAR CHART)
========================================== */

.purchase-top-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 22px;
    align-items: stretch;
}

.purchase-left-stack {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Base Card */
.p-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px 22px;
    box-shadow: 0 2px 10px rgba(32, 66, 110, .03);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 165px;
    position: relative;
    transition: transform .2s ease, box-shadow .2s ease;
}

.p-card:hover {
    box-shadow: 0 6px 18px rgba(32, 66, 110, .06);
    transform: translateY(-2px);
}

/* Card Borders Accent */
.p-card.card-po {
    border: 2.5px solid #5e718d;
}

.p-card.card-outstanding {
    border: 2.5px solid #0066ff;
}

.p-card.card-unpaid {
    border: 2.5px solid #ff2626;
}

.p-card.card-submission {
    border: 2.5px solid #00c853;
}

/* Card Header */
.p-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
}

.p-card-title {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
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
    font-size: 15px;
    font-weight: 700;
    flex-shrink: 0;
    cursor: pointer;
    transition: transform .2s ease;
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
    color: #0066ff;
}

.p-circle-btn.btn-red {
    background: #fee2e2;
    color: #ff2626;
}

.p-circle-btn.btn-green {
    background: #d1fae5;
    color: #00c853;
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
    color: #475569;
}

.p-metric-nominal {
    font-size: 21px;
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
    color: #0f172a;
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
}

.status-indicator.success .indicator-dot {
    background: #059669;
}

/* ==========================================
   BAR CHART CARD (TONASE & LUAS)
========================================== */

.purchase-barchart-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px 24px 14px;
    box-shadow: 0 2px 10px rgba(32, 66, 110, .03);
    display: flex;
    flex-direction: column;
    justify-content: center;
    height: 100%;
    min-height: 350px;
}

.purchase-barchart-container {
    position: relative;
    height: 100%;
    min-height: 310px;
    width: 100%;
}

/* ==========================================
   LINE CHART CARD (PEMBELIAN PER PERIODE)
========================================== */

.purchase-linechart-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px 28px 18px;
    box-shadow: 0 2px 10px rgba(32, 66, 110, .03);
    margin-bottom: 22px;
}

.purchase-linechart-header {
    margin-bottom: 16px;
}

.purchase-linechart-title {
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}

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
    width: 18px;
    height: 2px;
    background: #0084ff;
    display: inline-block;
}

.purchase-legend-symbol .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #0084ff;
    display: inline-block;
    margin-left: -13px;
}

/* ==========================================
   SYSTEM STATUS FOOTER
========================================== */

.purchase-footer {
    background: #192638;
    color: #94a3b8;
    padding: 12px 24px;
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    font-size: 11px;
    margin-top: 10px;
}

.purchase-footer-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.footer-build-badge {
    color: #38bdf8;
    font-weight: 800;
}

.footer-sep {
    color: #475569;
}

.purchase-footer .online-status {
    color: #34d399;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 7px;
    letter-spacing: 0.3px;
}

.purchase-footer .online-dot {
    width: 8px;
    height: 8px;
    background: #34d399;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 8px #34d399;
}

/* ==========================================
   RESPONSIVE DESIGN
========================================== */

@media (max-width: 768px) {
    .purchase-dashboard {
        padding: 16px 14px;
    }

    .production-heading {
        flex-direction: row; /* Memastikan tetap sejajar menyamping di layar kecil */
        align-items: center;
        gap: 12px;
    }

    .production-actions {
        width: auto;
    }

    .purchase-left-stack {
        grid-template-columns: 1fr;
    }

    .purchase-filter-inputs {
        flex-direction: column;
        align-items: stretch;
    }

    .purchase-filter-input {
        width: 100%;
    }

    .purchase-line-container {
        height: 200px;
    }

    .purchase-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
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

    .purchase-filter-clean {
        display: none !important;
    }

    .p-card,
    .purchase-barchart-card,
    .purchase-linechart-card {
        box-shadow: none !important;
        break-inside: avoid;
    }

    .purchase-footer {
        display: none !important;
    }
}

/* ==========================================
   MODAL DAFTAR PO (PURCHASE ORDER LIST)
========================================== */

.allpo-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: poFadeIn .2s ease-out;
}

.allpo-modal-overlay.active {
    display: flex;
}

.allpo-modal-window {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 1360px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35);
    overflow: hidden;
    animation: poSlideUp .25s ease-out;
}

/* Header */
.allpo-modal-header {
    background: linear-gradient(135deg, #075db5 0%, #0866c6 100%);
    color: #fff;
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.allpo-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.allpo-header-icon {
    width: 42px;
    height: 42px;
    background: #fff;
    color: #0866c6;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.allpo-header-text h3 {
    font-size: 18px;
    font-weight: 800;
    color: #fff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    line-height: 1.25;
}

.allpo-header-badge {
    background: rgba(255,255,255,0.22);
    color: #fff;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.allpo-header-text p {
    font-size: 11.5px;
    color: rgba(255,255,255,0.85);
    margin: 3px 0 0;
}

.allpo-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.allpo-search-wrap {
    position: relative;
    width: 260px;
}

.allpo-search-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
}

.allpo-search-wrap input {
    width: 100%;
    height: 38px;
    background: #fff;
    border: none;
    border-radius: 8px;
    padding: 0 12px 0 34px;
    font-size: 12px;
    color: #1e293b;
    outline: none;
    transition: box-shadow .2s;
}

.allpo-search-wrap input:focus {
    box-shadow: 0 0 0 3px rgba(255,255,255,0.35);
}

.allpo-btn-export {
    height: 38px;
    background: #fff;
    border: none;
    border-radius: 8px;
    padding: 0 14px;
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: background-color .2s;
}

.allpo-btn-export:hover { background: #f1f5f9; }

.allpo-btn-close-x {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: rgba(255,255,255,0.15);
    border: none;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    transition: background-color .2s;
}

.allpo-btn-close-x:hover { background: rgba(255,255,255,0.3); }

/* Body: master-detail grid */
.allpo-modal-body {
    display: grid;
    grid-template-columns: 1.3fr 1fr;
    flex: 1;
    overflow: hidden;
    min-height: 480px;
    max-height: calc(92vh - 130px);
}

/* Left panel */
.allpo-panel-left {
    overflow-y: auto;
    border-right: 1px solid #e2e8f0;
    background: #fff;
}

.allpo-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.allpo-table thead th {
    position: sticky;
    top: 0;
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 14px;
    border-bottom: 1px solid #e2e8f0;
    z-index: 1;
    white-space: nowrap;
}

.allpo-table tbody tr {
    cursor: pointer;
    transition: background-color .15s ease;
}

.allpo-table tbody tr:hover { background: #f8fafc; }

.allpo-table tbody tr.active-row { background: #edf5ff; }

.allpo-table tbody tr.active-row td:first-child {
    border-left: 4px solid #0866c6;
}

.allpo-table tbody td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.allpo-row-num { font-size: 11px; font-weight: 700; color: #94a3b8; }

.allpo-date-cell { color: #475569; font-weight: 600; font-size: 12px; white-space: nowrap; }

.allpo-nopo-cell { font-weight: 800; color: #0866c6; font-size: 12px; white-space: nowrap; }

.allpo-nopo-badge {
    background: #e0f2fe;
    color: #0369a1;
    font-weight: 700;
    font-size: 11.5px;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-block;
    white-space: nowrap;
}

.allpo-supplier-cell { font-size: 11.5px; font-weight: 700; color: #1e293b; }

.allpo-barang-cell { font-size: 11px; color: #64748b; font-weight: 600; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; }

.allpo-satuan-cell { font-size: 11.5px; color: #64748b; font-weight: 600; }

.allpo-qty-cell { font-weight: 700; color: #334155; text-align: center; }

.allpo-nominal-cell { font-weight: 800; color: #0f172a; white-space: nowrap; text-align: right; }

.allpo-arrow-cell { color: #cbd5e1; font-size: 13px; text-align: right; }
.allpo-table tbody tr.active-row .allpo-arrow-cell { color: #0866c6; }

/* Right detail panel — reuse existing PO detail styles */

/* Footer */
.allpo-modal-bottom {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 12px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.allpo-bottom-info {
    font-size: 11.5px;
    color: #64748b;
    font-weight: 600;
}

.allpo-bottom-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

@media (max-width: 992px) {
    .allpo-modal-body {
        grid-template-columns: 1fr;
        max-height: 80vh;
    }
    .allpo-panel-left {
        max-height: 300px;
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
    }
}

/* ==========================================
   MODAL OUTSTANDING PO STYLES
========================================== */

.po-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: poFadeIn .2s ease-out;
}

.po-modal-overlay.active {
    display: flex;
}

@keyframes poFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.po-modal-window {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 1300px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35);
    overflow: hidden;
    animation: poSlideUp .25s ease-out;
}

@keyframes poSlideUp {
    from { transform: translateY(20px) scale(0.98); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}

/* Modal Header */
.po-modal-header {
    background: linear-gradient(135deg, #075db5 0%, #0866c6 100%);
    color: #ffffff;
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.po-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.po-header-icon {
    width: 40px;
    height: 40px;
    background: #ffffff;
    color: #0866c6;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
}

.po-header-text h3 {
    font-size: 18px;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    line-height: 1.2;
}

.po-header-badge {
    background: rgba(255, 255, 255, 0.22);
    color: #ffffff;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.po-header-text p {
    font-size: 11.5px;
    color: rgba(255, 255, 255, 0.85);
    margin: 3px 0 0;
}

.po-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.po-search-input-wrap {
    position: relative;
    width: 240px;
}

.po-search-input-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
}

.po-search-input-wrap input {
    width: 100%;
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 12px 0 34px;
    font-size: 12px;
    color: #1e293b;
    outline: none;
    transition: box-shadow .2s;
}

.po-search-input-wrap input:focus {
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.35);
}

.po-select-status {
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    outline: none;
    cursor: pointer;
}

.po-btn-export {
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 14px;
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: background-color .2s;
}

.po-btn-export:hover {
    background: #f1f5f9;
}

.po-btn-close-x {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    transition: background-color .2s;
}

.po-btn-close-x:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Modal Body Grid */
.po-modal-body {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    flex: 1;
    overflow: hidden;
    min-height: 480px;
    max-height: calc(92vh - 135px);
}

/* Left Panel: Table of POs */
.po-panel-left {
    overflow-y: auto;
    border-right: 1px solid #e2e8f0;
    background: #ffffff;
}

.po-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.po-table thead th {
    position: sticky;
    top: 0;
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 14px;
    border-bottom: 1px solid #e2e8f0;
    z-index: 1;
    white-space: nowrap;
}

.po-table tbody tr {
    cursor: pointer;
    transition: background-color .15s ease;
}

.po-table tbody tr:hover {
    background: #f8fafc;
}

.po-table tbody tr.active-row {
    background: #edf5ff;
}

.po-table tbody tr.active-row td:first-child {
    border-left: 4px solid #0866c6;
}

.po-table tbody td {
    padding: 13px 14px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.po-row-num {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
}

.po-number-cell {
    font-weight: 800;
    color: #0866c6;
    font-size: 12.5px;
    line-height: 1.25;
}

.po-supplier-cell {
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
    margin-top: 3px;
}

.po-date-cell {
    color: #475569;
    font-weight: 600;
    font-size: 12px;
    white-space: nowrap;
}

.po-nominal-cell {
    font-weight: 800;
    color: #0f172a;
    font-size: 12.5px;
    white-space: nowrap;
}

.po-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.po-status-badge.amber {
    background: #fef3c7;
    color: #b45309;
}

.po-status-badge.amber .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #f59e0b;
}

.po-status-badge.blue {
    background: #e0f2fe;
    color: #0369a1;
}

.po-status-badge.blue .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #0284c7;
}

.po-arrow-cell {
    color: #cbd5e1;
    font-size: 13px;
    text-align: right;
}

.active-row .po-arrow-cell {
    color: #0866c6;
}

/* Right Panel: Detail View */
.po-panel-right {
    overflow-y: auto;
    padding: 22px 24px;
    background: #ffffff;
    display: flex;
    flex-direction: column;
}

.po-detail-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.po-detail-header-left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.po-badge-rincian {
    background: #0866c6;
    color: #ffffff;
    padding: 3px 9px;
    border-radius: 5px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.5px;
}

.po-detail-nopo {
    font-size: 15px;
    font-weight: 800;
    color: #1e293b;
}

.po-detail-supplier-name {
    font-size: 18px;
    font-weight: 800;
    color: #1e293b;
    margin: 4px 0 14px;
}

.po-detail-boxes {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 14px;
}

.po-meta-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    padding: 10px 14px;
}

.po-meta-card span {
    display: block;
    font-size: 10.5px;
    color: #64748b;
    font-weight: 600;
}

.po-meta-card strong {
    font-size: 13px;
    color: #1e293b;
    font-weight: 800;
    margin-top: 2px;
    display: block;
}

.po-keterangan-alert {
    background: #f0f7ff;
    border-left: 3px solid #0866c6;
    border-radius: 6px;
    padding: 9px 12px;
    font-size: 11px;
    color: #334155;
    margin-bottom: 18px;
    line-height: 1.5;
}

.po-keterangan-alert strong {
    color: #0866c6;
}

.po-items-title-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e2e8f0;
}

.po-items-title-bar h5 {
    font-size: 13px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.po-items-count-tag {
    font-size: 11px;
    font-weight: 800;
    color: #0866c6;
}

.po-items-table-wrap {
    flex: 1;
    overflow-x: auto;
}

.po-items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11.5px;
}

.po-items-table th {
    background: #f8fafc;
    color: #64748b;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 8px 10px;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

.po-items-table td {
    padding: 10px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
}

.po-items-table td.qty-col {
    text-align: center;
    font-weight: 700;
}

.po-items-table td.subtotal-col {
    text-align: right;
    font-weight: 800;
    color: #0f172a;
    white-space: nowrap;
}

.po-item-note {
    font-size: 10px;
    color: #64748b;
    margin-top: 2px;
}

.po-detail-footer-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    padding: 12px 16px;
    margin-top: 16px;
}

.po-detail-footer-summary span {
    font-size: 12px;
    color: #64748b;
    font-weight: 700;
}

.po-detail-footer-summary strong {
    font-size: 17px;
    font-weight: 800;
    color: #0866c6;
}

/* Modal Bottom Footer */
.po-modal-bottom {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 12px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.po-bottom-info {
    font-size: 11.5px;
    color: #64748b;
    font-weight: 600;
}

.po-bottom-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.po-page-btn {
    padding: 6px 12px;
    border: 1px solid #dce5f1;
    background: #ffffff;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    transition: all .15s;
}

.po-page-btn:hover:not(.disabled) {
    background: #edf4ff;
    color: #0866c6;
}

.po-page-btn.active {
    background: #0866c6;
    color: #ffffff;
    border-color: #0866c6;
}

.po-page-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.po-btn-tutup {
    padding: 6px 18px;
    border: 1px solid #dce5f1;
    background: #ffffff;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    transition: all .15s;
}

.po-btn-tutup:hover {
    background: #f1f5f9;
    color: #0f172a;
}

/* Responsive Modal */
@media (max-width: 992px) {
    .po-modal-body {
        grid-template-columns: 1fr;
        max-height: 80vh;
    }

    .po-panel-left {
        max-height: 300px;
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
    }
}

/* ==========================================
   MODAL PEMBELIAN PER PERIODE STYLES
========================================== */

.period-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: poFadeIn .2s ease-out;
}

.period-modal-overlay.active {
    display: flex;
}

.period-modal-window {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 1280px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35);
    overflow: hidden;
    animation: poSlideUp .25s ease-out;
}

.period-modal-header {
    background: linear-gradient(135deg, #075db5 0%, #0866c6 100%);
    color: #ffffff;
    padding: 20px 26px 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.period-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.period-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.period-header-icon {
    width: 42px;
    height: 42px;
    background: #ffffff;
    color: #0866c6;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
}

.period-header-title-wrap h3 {
    font-size: 19px;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    line-height: 1.25;
}

.period-badge-count {
    background: rgba(255, 255, 255, 0.22);
    color: #ffffff;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.period-header-title-wrap p {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.85);
    margin: 4px 0 0;
}

.period-header-summary-card {
    background: #ffffff;
    border-radius: 10px;
    padding: 8px 18px;
    text-align: right;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
    flex-shrink: 0;
}

.period-header-summary-card span {
    display: block;
    font-size: 10px;
    font-weight: 800;
    color: #64748b;
    letter-spacing: 0.6px;
    text-transform: uppercase;
}

.period-header-summary-card strong {
    display: block;
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    margin-top: 2px;
}

.period-header-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.period-controls-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    max-width: 600px;
}

.period-search-wrap {
    position: relative;
    flex: 1;
    min-width: 220px;
}

.period-search-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
}

.period-search-wrap input {
    width: 100%;
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 12px 0 34px;
    font-size: 12px;
    color: #1e293b;
    outline: none;
    transition: box-shadow .2s;
}

.period-search-wrap input:focus {
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.35);
}

.period-controls-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.period-btn-export {
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 16px;
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: background-color .2s;
}

.period-btn-export:hover {
    background: #f1f5f9;
}

.period-btn-close-x {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    transition: background-color .2s;
}

.period-btn-close-x:hover {
    background: rgba(255, 255, 255, 0.3);
}

.period-modal-body {
    overflow-y: auto;
    overflow-x: auto;
    flex: 1;
    background: #ffffff;
    min-height: 380px;
    max-height: calc(92vh - 190px);
}

.period-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12.5px;
}

.period-table thead th {
    position: sticky;
    top: 0;
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 13px 16px;
    border-bottom: 1px solid #e2e8f0;
    z-index: 2;
    white-space: nowrap;
}

.period-table tbody td {
    padding: 13px 16px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.period-table tbody tr {
    transition: background-color .15s ease;
}

.period-table tbody tr:hover {
    background: #f8fafc;
}

.period-row-num {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
}

.period-kode-badge {
    background: #e0f2fe;
    color: #0369a1;
    font-weight: 700;
    font-size: 11.5px;
    padding: 4px 9px;
    border-radius: 6px;
    display: inline-block;
    white-space: nowrap;
    font-family: 'DM Mono', 'Fira Code', monospace;
}

.period-barang-text {
    font-weight: 700;
    color: #1e293b;
}

.period-nb-badge {
    background: #f1f5f9;
    color: #475569;
    font-weight: 700;
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 5px;
    display: inline-block;
    white-space: nowrap;
}

.period-date-text {
    color: #475569;
    font-weight: 600;
    font-size: 12px;
    white-space: nowrap;
}

.period-nominal-text {
    font-weight: 800;
    color: #1e293b;
    white-space: nowrap;
}

.period-qty-text {
    font-weight: 700;
    color: #334155;
    text-align: center;
}

.period-stok-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.period-stok-badge.safe {
    background: #d1fae5;
    color: #065f46;
}

.period-stok-badge.safe .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
}

.period-stok-badge.low {
    background: #fef3c7;
    color: #b45309;
}

.period-stok-badge.low .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #f59e0b;
}

.period-modal-bottom {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 12px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.period-bottom-info {
    font-size: 11.5px;
    color: #64748b;
    font-weight: 600;
}

.period-bottom-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.period-btn-tutup {
    padding: 6px 18px;
    border: 1px solid #dce5f1;
    background: #ffffff;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    transition: all .15s;
}

.period-btn-tutup:hover {
    background: #f1f5f9;
    color: #0f172a;
}

/* Three-dots menu button on chart card */
.purchase-chart-dots-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    transition: all .2s ease;
    flex-shrink: 0;
}

.purchase-chart-dots-btn:hover {
    background: #edf4ff;
    color: #0866c6;
    border-color: #c7dbfb;
}

/* ==========================================
   MODAL PEMBELIAN BELUM DILUNASI STYLES
========================================== */

.unpaid-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: poFadeIn .2s ease-out;
}

.unpaid-modal-overlay.active {
    display: flex;
}

.unpaid-modal-window {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 1280px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35);
    overflow: hidden;
    animation: poSlideUp .25s ease-out;
}

.unpaid-modal-header {
    background: linear-gradient(135deg, #075db5 0%, #0866c6 100%);
    color: #ffffff;
    padding: 20px 26px 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.unpaid-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.unpaid-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.unpaid-header-icon {
    width: 42px;
    height: 42px;
    background: #ffffff;
    color: #0866c6;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
}

.unpaid-header-title-wrap h3 {
    font-size: 19px;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    line-height: 1.25;
}

.unpaid-badge-count {
    background: #ffffff;
    color: #dc2626;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.3px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.unpaid-badge-count .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #dc2626;
}

.unpaid-header-title-wrap p {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.85);
    margin: 4px 0 0;
}

.unpaid-header-summary-card {
    background: #ffffff;
    border-radius: 10px;
    padding: 8px 18px;
    text-align: right;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
    flex-shrink: 0;
}

.unpaid-header-summary-card span {
    display: block;
    font-size: 10px;
    font-weight: 800;
    color: #64748b;
    letter-spacing: 0.6px;
    text-transform: uppercase;
}

.unpaid-header-summary-card strong {
    display: block;
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    margin-top: 2px;
}

.unpaid-header-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.unpaid-controls-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    max-width: 600px;
}

.unpaid-search-wrap {
    position: relative;
    flex: 1;
    min-width: 220px;
}

.unpaid-search-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
}

.unpaid-search-wrap input {
    width: 100%;
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 12px 0 34px;
    font-size: 12px;
    color: #1e293b;
    outline: none;
    transition: box-shadow .2s;
}

.unpaid-search-wrap input:focus {
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.35);
}

.unpaid-select-status {
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    outline: none;
    cursor: pointer;
}

.unpaid-controls-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.unpaid-btn-export {
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 16px;
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: background-color .2s;
}

.unpaid-btn-export:hover {
    background: #f1f5f9;
}

.unpaid-btn-close-x {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    transition: background-color .2s;
}

.unpaid-btn-close-x:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Modal Body Grid: Master-Detail */
.unpaid-modal-body {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    flex: 1;
    overflow: hidden;
    min-height: 480px;
    max-height: calc(92vh - 135px);
}

/* Left Panel: Tabel Pembelian Belum Dilunasi */
.unpaid-panel-left {
    overflow-y: auto;
    border-right: 1px solid #e2e8f0;
    background: #ffffff;
}

.unpaid-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.unpaid-table thead th {
    position: sticky;
    top: 0;
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 14px;
    border-bottom: 1px solid #e2e8f0;
    z-index: 2;
    white-space: nowrap;
}

.unpaid-table tbody tr {
    cursor: pointer;
    transition: background-color .15s ease;
}

.unpaid-table tbody tr:hover {
    background: #f8fafc;
}

.unpaid-table tbody tr.active-row {
    background: #edf5ff;
}

.unpaid-table tbody tr.active-row td:first-child {
    border-left: 4px solid #0866c6;
}

.unpaid-table tbody td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.unpaid-row-num {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
}

.unpaid-supplier-text {
    font-weight: 700;
    color: #1e293b;
    font-size: 12.5px;
}

.unpaid-date-text {
    color: #475569;
    font-weight: 600;
    font-size: 11.5px;
    white-space: nowrap;
}

.unpaid-nominal-text {
    font-weight: 800;
    color: #1e293b;
    white-space: nowrap;
}

.unpaid-kurang-bayar-text {
    font-weight: 800;
    color: #dc2626;
    white-space: nowrap;
}

.unpaid-arrow-cell {
    color: #cbd5e1;
    font-size: 13px;
    text-align: right;
}

.active-row .unpaid-arrow-cell {
    color: #0866c6;
}

/* Right Panel: Detail View (Rincian NB) */
.unpaid-panel-right {
    overflow-y: auto;
    padding: 22px 24px;
    background: #ffffff;
    display: flex;
    flex-direction: column;
}

.unpaid-detail-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.unpaid-detail-header-left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.unpaid-badge-rincian {
    background: #0866c6;
    color: #ffffff;
    padding: 3px 9px;
    border-radius: 5px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.5px;
}

.unpaid-detail-nopo {
    font-size: 15px;
    font-weight: 800;
    color: #1e293b;
}

.unpaid-detail-supplier-name {
    font-size: 18px;
    font-weight: 800;
    color: #1e293b;
    margin: 4px 0 14px;
}

.unpaid-detail-boxes {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}

.unpaid-meta-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    padding: 9px 12px;
}

.unpaid-meta-card span {
    display: block;
    font-size: 10px;
    color: #64748b;
    font-weight: 600;
}

.unpaid-meta-card strong {
    font-size: 12.5px;
    color: #1e293b;
    font-weight: 800;
    margin-top: 2px;
    display: block;
    white-space: nowrap;
}

.unpaid-meta-card.danger strong {
    color: #dc2626;
}

.unpaid-items-title-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e2e8f0;
}

.unpaid-items-title-bar h5 {
    font-size: 13px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.unpaid-items-count-tag {
    font-size: 11px;
    font-weight: 800;
    color: #0866c6;
}

.unpaid-items-table-wrap {
    flex: 1;
    overflow-x: auto;
}

.unpaid-items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11.5px;
}

.unpaid-items-table th {
    background: #f8fafc;
    color: #64748b;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 8px 10px;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

.unpaid-items-table td {
    padding: 10px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
}

.unpaid-items-table td.nominal-col {
    text-align: right;
    font-weight: 800;
    color: #0f172a;
    white-space: nowrap;
}

.unpaid-detail-footer-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    padding: 12px 16px;
    margin-top: 16px;
}

.unpaid-detail-footer-summary span {
    font-size: 12px;
    color: #64748b;
    font-weight: 700;
}

.unpaid-detail-footer-summary strong {
    font-size: 16px;
    font-weight: 800;
    color: #0866c6;
}

@media (max-width: 992px) {
    .unpaid-modal-body {
        grid-template-columns: 1fr;
        max-height: 80vh;
    }

    .unpaid-panel-left {
        max-height: 300px;
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
    }
}

/* Modal Bottom Footer */
.unpaid-modal-bottom {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 12px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.unpaid-bottom-info {
    font-size: 11.5px;
    color: #64748b;
    font-weight: 600;
}

.unpaid-bottom-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.unpaid-btn-tutup {
    padding: 6px 18px;
    border: 1px solid #dce5f1;
    background: #ffffff;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    transition: all .15s;
}

.unpaid-btn-tutup:hover {
    background: #f1f5f9;
    color: #0f172a;
}

/* ==========================================
   MODAL PURCHASE REQUEST (BARU)
========================================== */

.pr-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: poFadeIn .2s ease-out;
}

.pr-modal-overlay.active {
    display: flex;
}

.pr-modal-window {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 1300px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35);
    overflow: hidden;
    animation: poSlideUp .25s ease-out;
}

.pr-modal-body {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    flex: 1;
    overflow: hidden;
    min-height: 480px;
    max-height: calc(92vh - 135px);
}

.pr-panel-left {
    overflow-y: auto;
    border-right: 1px solid #e2e8f0;
    background: #ffffff;
}

.pr-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.pr-status-badge.done {
    background: #d1fae5;
    color: #065f46;
}

.pr-status-badge.pending {
    background: #fef3c7;
    color: #b45309;
}

@media (max-width: 992px) {
    .pr-modal-body {
        grid-template-columns: 1fr;
        max-height: 80vh;
    }

    .pr-panel-left {
        max-height: 300px;
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
    }
}

</style>

<!-- ==========================================
     MAIN DASHBOARD
========================================== -->

<main class="purchase-dashboard">

   <!-- HEADER DASHBOARD -->
    <section class="production-heading">
        <div>
            <div class="production-title">
                <div class="heading-icon">
                    <i class="bi bi-clipboard2-data-fill"></i>
                </div>
                <h1>
                    Dashboard Monitoring Pembelian — AQUAMATE
                </h1>
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

    <?php if ($dbPembelianError): ?>
    <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 16px; border-radius:10px; font-size:12px; font-weight:700; margin-bottom:18px;">
        <?= htmlspecialchars($dbPembelianError) ?>
    </div>
    <?php endif; ?>

    <!-- FILTER PERIODE -->
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
                    <?= ($startDate === date('Y-m-d') && $endDate === date('Y-m-d')) ? 'checked' : '' ?>
                >
                <i class="bi bi-calendar-check"></i>
                Hari Ini Saja
            </label>
        </form>
    </section>

    <!-- ======================================
         TOP GRID: 4 METRIC CARDS + BAR CHART
    ======================================= -->
    <section class="purchase-top-grid">

        <!-- Left 2x2 Metric Cards Grid -->
        <div class="purchase-left-stack">

            <!-- Card 1: Purchase Request -->
            <?php renderPurchaseRequestCard($purchaseRequestList); ?>

            <!-- Card 2: Purchase Order -->
            <?php renderPurchaseOrderCard($allPoList); ?>

            <!-- Card 3: Outstanding PO -->
            <?php renderOutstandingPoCard($outstandingList); ?>

            <!-- Card 4: Pembelian Belum Dilunasi -->
            <?php renderUnpaidCard($unpaidList); ?>

        </div>

        <?php renderBahanBakuChartSection(); ?>

    <?php /* Struktur HTML section.purchase-top-grid sengaja dipertahankan persis seperti file asli (tidak ditutup di sini). */ ?>
    <?php renderPembelianPeriodeChartSection(); ?>

    <?php renderBahanBakuModal($bahanBaku['detail']); ?>

    <?php renderPurchaseOrderModal($allPoList); ?>

    <?php renderOutstandingPoModal($outstandingList); ?>

    <?php renderUnpaidModal($unpaidList); ?>

    <?php renderPembelianPeriodeModal($periodChart['detail']); ?>

    <?php renderPurchaseRequestModal($purchaseRequestList); ?>

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
// EXPORT EXCEL SEMENTARA
// ======================================
function exportExcel() {
    alert('Fitur Export Excel akan diaktifkan setelah data pembelian terhubung dengan database.');
}

function formatRupiah(number) {
    return 'Rp ' + Number(number).toLocaleString('id-ID');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAllPoModal();
        closeOutstandingModal();
        closeUnpaidModal();
        closePeriodDetailModal();
        closeMonthlyDetailModal();
        closePurchaseRequestModal();
    }
});
</script>

<?php renderBahanBakuScript($bahanBaku); ?>

<?php renderPembelianPeriodeScript($periodChart); ?>

<?php renderPurchaseOrderScript($allPoList); ?>

<?php renderOutstandingPoScript($outstandingList); ?>

<?php renderUnpaidScript($unpaidList); ?>

<?php renderPurchaseRequestScript($purchaseRequestList); ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>