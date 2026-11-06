
<?php
require_once __DIR__ . '/../koneksi.php';

$pageTitle = 'Detail Sales Order';
$activePage = 'penjualan';
$basePath = '../';

// ==========================================
// 1. FILTER TANGGAL
// ==========================================

$startDate = $_GET['tanggal_awal'] ?? '2026-10-01';
$endDate   = $_GET['tanggal_akhir'] ?? '2026-10-31';

// ==========================================
// 2. PENCARIAN
// ==========================================

$keyword = trim($_GET['keyword'] ?? '');

// ==========================================
// 3. QUERY DATA SALES ORDER
// ==========================================

$sql = "
    SELECT *
    FROM report_so
    WHERE inquiry_date BETWEEN :start_date AND :end_date
";

$params = [
    ':start_date' => $startDate,
    ':end_date'   => $endDate
];

if ($keyword !== '') {
    $sql .= "
        AND (
            no_trans LIKE :keyword1
            OR nama_pelanggan LIKE :keyword2
            OR sales LIKE :keyword3
        )
    ";

    $searchKeyword = "%$keyword%";

    $params[':keyword1'] = $searchKeyword;
    $params[':keyword2'] = $searchKeyword;
    $params[':keyword3'] = $searchKeyword;
}

// ==========================================
// 4. HITUNG TOTAL DATA
// ==========================================

$countSql = "
    SELECT COUNT(*)
    FROM report_so
    WHERE inquiry_date BETWEEN :start_date AND :end_date
";

$countParams = [
    ':start_date' => $startDate,
    ':end_date'   => $endDate
];

if ($keyword !== '') {
    $countSql .= "
        AND (
            no_trans LIKE :keyword1
            OR nama_pelanggan LIKE :keyword2
            OR sales LIKE :keyword3
        )
    ";

    $searchKeyword = "%$keyword%";

    $countParams[':keyword1'] = $searchKeyword;
    $countParams[':keyword2'] = $searchKeyword;
    $countParams[':keyword3'] = $searchKeyword;
}

$countStmt = $conn->prepare($countSql);
$countStmt->execute($countParams);

$totalData = (int) $countStmt->fetchColumn();

// ==========================================
// 5. PENGATURAN PAGINATION
// ==========================================

$allowedPerPage = [10, 25, 50, 100];

$perPage = (int) ($_GET['per_page'] ?? 10);

if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 10;
}

$totalPages = max(1, (int) ceil($totalData / $perPage));

$currentPage = max(1, (int) ($_GET['page'] ?? 1));

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;

// ==========================================
// 6. AMBIL DATA SESUAI HALAMAN
// ==========================================

$sql .= "
    ORDER BY inquiry_date DESC, no_trans DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$dataSO = $stmt->fetchAll();

// ==========================================
// 7. HEADER
// ==========================================

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* =========================================
   AQUAMATE - MODERN DETAIL DASHBOARD
========================================= */

:root {
    --aq-primary: #0866c6;
    --aq-primary-dark: #064b91;
    --aq-navy: #17365d;
    --aq-text: #334155;
    --aq-muted: #8492a6;
    --aq-border: #e5edf7;
    --aq-bg: #f3f7fd;
    --aq-white: #ffffff;
}

/* PAGE BACKGROUND */
.detail-container {
    padding: 32px;
    background:
        radial-gradient(circle at top right, #e5f0ff 0, transparent 35%),
        #f3f7fd;
    min-height: calc(100vh - 100px);
}

/* MAIN CARD */
.detail-card {
    background: var(--aq-white);
    border: 1px solid #e8eef7;
    border-radius: 22px;
    padding: 32px;
    box-shadow: 0 12px 35px rgba(28, 65, 110, .07);
}

/* HEADER */
.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 18px;
    margin-bottom: 30px;
}

.detail-header h2 {
    font-size: 27px;
    font-weight: 800;
    color: var(--aq-navy);
    margin: 0 0 8px;
    letter-spacing: -.6px;
}

.detail-header p {
    color: var(--aq-muted);
    font-size: 13px;
    margin: 0;
}

.detail-header a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #edf5ff, #e2efff);
    color: var(--aq-primary) !important;
    padding: 12px 18px;
    border: 1px solid #d8e9ff;
    border-radius: 11px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    transition: .25s ease;
}

.detail-header a:hover {
    background: var(--aq-primary);
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 7px 16px rgba(8, 102, 198, .2);
}

/* FILTER */
.filter-form {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 20px;
    background: linear-gradient(135deg, #f7faff, #f0f6ff);
    border: 1px solid #e1ebf8;
    border-radius: 16px;
    margin-bottom: 25px;
}

.filter-form input,
.filter-form select {
    height: 46px;
    padding: 0 14px;
    border: 1px solid #dce6f2;
    border-radius: 10px;
    background: white;
    color: var(--aq-text);
    font-size: 12px;
    outline: none;
    transition: .2s;
}

.filter-form input:focus,
.filter-form select:focus {
    border-color: var(--aq-primary);
    box-shadow: 0 0 0 4px rgba(8, 102, 198, .09);
}

.filter-form input[type="text"] {
    min-width: 230px;
    flex: 1;
}

.filter-form button {
    height: 46px;
    padding: 0 25px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #0877df, #0757ad);
    color: white;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: .25s;
}

.filter-form button:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 16px rgba(8, 102, 198, .22);
}

/* TOTAL DATA */
.total-data {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 17px;
    margin-bottom: 20px;
    background: #edf5ff;
    border: 1px solid #d9eaff;
    border-radius: 11px;
    color: #64748b;
    font-size: 12px;
}

.total-data strong {
    color: var(--aq-primary);
    font-weight: 800;
}

/* TABLE CONTAINER */
.table-responsive {
    width: 100%;
    overflow-x: auto;
    border: 1px solid var(--aq-border);
    border-radius: 15px;
    background: white;
}

/* TABLE */
.detail-card table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

/* TABLE HEADER */
.detail-card th {
    padding: 17px 15px;
    background: #edf4fc;
    color: #29496e;
    font-size: 11px;
    font-weight: 800;
    text-align: left;
    white-space: nowrap;
    border-bottom: 1px solid #dce6f2;
}

/* TABLE BODY */
.detail-card td {
    padding: 16px 15px;
    color: #475569;
    font-size: 12px;
    border-bottom: 1px solid #edf1f7;
    vertical-align: middle;
    white-space: nowrap;
}

.detail-card tbody tr {
    background: white;
    transition: background .2s ease;
}

.detail-card tbody tr:nth-child(even) {
    background: #fafcff;
}

.detail-card tbody tr:hover {
    background: #edf6ff;
}

.detail-card tbody tr:last-child td {
    border-bottom: none;
}

.detail-card td:nth-child(1) {
    color: #94a3b8;
    font-weight: 700;
}

/* TRANSACTION NUMBERS */
.detail-card td:nth-child(2),
.detail-card td:nth-child(3) {
    font-weight: 700;
}

/* DETAIL SO BUTTON */
.btn-detail-so {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 9px 15px;
    background: linear-gradient(135deg, #0877df, #0757ad);
    color: white !important;
    border-radius: 9px;
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
    transition: .2s;
}

.btn-detail-so:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 13px rgba(8, 102, 198, .2);
}

/* LOGISTICS STATUS */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 13px;
    border-radius: 30px;
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
}

.status-siap {
    background: #eeedff;
    color: #6255c7;
}

.status-proses {
    background: #fff4df;
    color: #bd7912;
}

.status-diterima {
    background: #e5f7ee;
    color: #18865b;
}

/* WORK ORDER CHIPS */
.wo-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    max-width: 340px;
    white-space: normal;
}

.wo-chip {
    display: inline-flex;
    align-items: center;
    padding: 6px 9px;
    background: #f0f6ff;
    border: 1px solid #dceaff;
    color: #285b91;
    border-radius: 7px;
    font-size: 10px;
    font-weight: 700;
}

/* WORK ORDER POPUP BUTTON */
.wo-trigger {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border: 1px solid #d7e7fb;
    border-radius: 10px;
    background: #edf5ff;
    color: var(--aq-primary);
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.wo-trigger:hover {
    background: var(--aq-primary);
    color: white;
    border-color: var(--aq-primary);
    transform: translateY(-2px);
}

.wo-trigger .wo-count {
    background: #dceaff;
    color: var(--aq-primary);
    padding: 4px 8px;
    border-radius: 6px;
}

/* WORK ORDER DIALOG */
.wo-dialog {
    width: min(500px, calc(100% - 30px));
    border: none;
    border-radius: 18px;
    padding: 0;
    box-shadow: 0 20px 60px rgba(15, 35, 65, .2);
}

.wo-dialog::backdrop {
    background: rgba(15, 35, 65, .48);
    backdrop-filter: blur(4px);
}

.wo-modal-header {
    padding: 22px 25px;
    background: linear-gradient(135deg, #edf5ff, #e2efff);
    border-bottom: 1px solid #e1ebf7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.wo-modal-header h3 {
    color: var(--aq-navy);
    font-size: 17px;
    font-weight: 800;
    margin: 0;
}

.wo-modal-header p {
    color: var(--aq-muted);
    font-size: 11px;
    margin: 5px 0 0;
}

.wo-modal-close {
    width: 34px;
    height: 34px;
    border: none;
    border-radius: 9px;
    background: white;
    color: #64748b;
    font-size: 19px;
    cursor: pointer;
}

.wo-modal-body {
    padding: 22px 25px;
    max-height: 380px;
    overflow-y: auto;
}

.wo-modal-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.wo-modal-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px;
    background: #f8faff;
    border: 1px solid #e8eef7;
    border-radius: 11px;
}

.wo-modal-number {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #e4efff;
    color: var(--aq-primary);
    border-radius: 8px;
    font-size: 11px;
    font-weight: 800;
}

.wo-modal-code {
    color: #29496e;
    font-size: 12px;
    font-weight: 700;
    word-break: break-word;
}

/* PAGINATION */
.pagination-container,
.pagination-area {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 24px;
}

.pagination-info,
.pagination-area > span {
    color: var(--aq-muted);
    font-size: 12px;
}

.pagination-buttons,
.pagination-area > div {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pagination-buttons a,
.pagination-area a {
    padding: 10px 16px;
    background: #0866c6;
    color: white;
    border-radius: 9px;
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    transition: .2s;
}

.pagination-buttons a:hover,
.pagination-area a:hover {
    background: #064b91;
    transform: translateY(-1px);
}

.pagination-current,
.pagination-area div > span {
    padding: 10px 15px;
    background: #edf4fc;
    color: #29496e;
    border-radius: 9px;
    font-size: 11px;
    font-weight: 700;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .detail-container {
        padding: 15px;
    }

    .detail-card {
        padding: 18px;
        border-radius: 16px;
    }

    .detail-header h2 {
        font-size: 22px;
    }

    .filter-form {
        flex-direction: column;
        align-items: stretch;
        padding: 15px;
    }

    .filter-form input,
    .filter-form select,
    .filter-form button {
        width: 100%;
    }

    .pagination-container,
    .pagination-area {
        flex-direction: column;
        align-items: flex-start;
    }
}
/* =========================================
   AQUAMATE PREMIUM DETAIL SO REDESIGN
========================================= */

.detail-container {
    padding: 35px;
    background: linear-gradient(135deg, #eef4fc, #f8faff);
}

.detail-card {
    border: 1px solid #e1eaf5;
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 15px 40px rgba(25, 55, 95, .08);
}

/* HEADER */
.detail-header {
    padding-bottom: 23px;
    border-bottom: 1px solid #e9eef6;
}

.detail-header h2 {
    font-size: 29px;
    color: #142f50;
    letter-spacing: -.8px;
}

.detail-header p {
    font-size: 13px;
    color: #8292a8;
}

/* FILTER */
.filter-form {
    background: #f6f9fe;
    border: 1px solid #e3ebf7;
    border-radius: 15px;
    padding: 18px;
    box-shadow: inset 0 1px 3px rgba(20, 47, 80, .025);
}

.filter-form input,
.filter-form select {
    border: 1px solid #d9e3f0;
    border-radius: 9px;
    background: #fff;
}

.filter-form button {
    background: linear-gradient(135deg, #0878df, #0753a5);
    border-radius: 9px;
    box-shadow: 0 4px 10px rgba(8, 102, 198, .15);
}

/* SUMMARY */
.total-data {
    padding: 15px 20px;
    background: linear-gradient(110deg, #e9f3ff, #f4f8ff);
    border: 1px solid #d5e6fc;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(25, 80, 140, .04);
}

/* TABLE */
.table-responsive {
    border: 1px solid #e1e9f3;
    border-radius: 14px;
    box-shadow: 0 5px 18px rgba(25, 55, 95, .04);
}

.detail-card th {
    padding: 19px 16px;
    background: #eaf2fc;
    color: #1d426d;
    font-size: 11px;
    letter-spacing: .2px;
    border-bottom: 1px solid #dce7f5;
}

.detail-card td {
    padding: 18px 16px;
    font-size: 12px;
    color: #465a72;
    border-bottom: 1px solid #edf1f7;
}

.detail-card tbody tr:nth-child(even) {
    background: #f7faff;
}

.detail-card tbody tr:hover {
    background: #eaf4ff;
}

.detail-card tbody tr {
    transition: background .2s ease;
}

/* NOMOR TRANSAKSI */
.detail-card td:nth-child(3) {
    color: #0866c6;
    font-weight: 800;
}

/* DETAIL BUTTON */
.btn-detail-so {
    padding: 10px 17px;
    border-radius: 9px;
    background: linear-gradient(135deg, #0878df, #0753a5);
    box-shadow: 0 3px 9px rgba(8, 102, 198, .14);
}

.btn-detail-so:hover {
    box-shadow: 0 7px 15px rgba(8, 102, 198, .24);
}

/* PAGINATION */
.pagination-container {
    padding-top: 18px;
    border-top: 1px solid #edf1f7;
}

.pagination-current {
    background: #eaf2fc;
    color: #075caf;
    border: 1px solid #d9e8fa;
}
/* =========================================
   AQUAMATE — PREMIUM ENTERPRISE UI
   FINAL VISUAL ENHANCEMENT
========================================= */

/* PAGE */
.detail-container {
    padding: 34px;
    background:
        radial-gradient(ellipse at 90% 0%, rgba(46, 130, 220, .10), transparent 35%),
        #f1f5fb;
}

/* MAIN PANEL */
.detail-card {
    position: relative;
    overflow: hidden;
    border: 1px solid #e4ebf5;
    border-radius: 22px;
    background: #ffffff;
    box-shadow: 0 18px 50px rgba(25, 55, 95, .08);
}

.detail-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, #0753a5, #1689f5, #75bdff);
}

/* PAGE HEADER */
.detail-header {
    position: relative;
    padding-bottom: 25px;
    margin-bottom: 28px;
    border-bottom: 1px solid #edf1f7;
}

.detail-header h2 {
    font-size: 30px;
    font-weight: 850;
    color: #142f50;
    letter-spacing: -1px;
}


.detail-header p {
    font-size: 13px;
    color: #8493a8;
}

/* BACK BUTTON */
.detail-header a {
    border-radius: 10px;
    transition: all .25s ease;
}

.detail-header a:hover {
    transform: translateX(-4px);
}

/* FILTER PANEL */
.filter-form {
    display: flex;
    gap: 12px;
    padding: 20px;
    background: linear-gradient(120deg, #f5f9ff, #edf5ff);
    border: 1px solid #e0ebf9;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(35, 80, 130, .035);
}

.filter-form input,
.filter-form select {
    height: 47px;
    border: 1px solid #dce6f2;
    border-radius: 10px;
    font-size: 12px;
    transition: all .2s ease;
}

.filter-form input:focus,
.filter-form select:focus {
    border-color: #1683e5;
    box-shadow: 0 0 0 4px rgba(22, 131, 229, .10);
}

/* SUMMARY CARD */
.total-data {
    position: relative;
    min-width: 250px;
    padding: 18px 20px 18px 55px;
    margin: 0 0 23px;
    background: linear-gradient(120deg, #eaf4ff, #f5f9ff);
    border: 1px solid #d8e8fb;
    border-radius: 13px;
    box-shadow: 0 6px 18px rgba(25, 90, 160, .06);
}

.total-data::before {
    content: "▤";
    position: absolute;
    left: 17px;
    top: 50%;
    transform: translateY(-50%);
    width: 27px;
    height: 27px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #d5e9ff;
    color: #0866c6;
    font-size: 17px;
    font-weight: bold;
}

.total-data strong {
    font-size: 13px;
}

/* TABLE */
.table-responsive {
    border: 1px solid #e2eaf4;
    border-radius: 15px;
    box-shadow: 0 8px 24px rgba(20, 50, 90, .045);
}

.detail-card table {
    border-spacing: 0;
}

.detail-card th {
    padding: 19px 16px;
    background: #eaf2fc;
    color: #23476f;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .25px;
    border-bottom: 1px solid #dce7f5;
}

.detail-card td {
    padding: 19px 16px;
    color: #52647a;
    font-size: 12px;
    transition: background .2s ease;
}

.detail-card tbody tr:nth-child(even) {
    background: #f8faff;
}

.detail-card tbody tr:hover {
    background: #eaf4ff;
}

.detail-card tbody tr:hover td {
    color: #1c426c;
}

/* SO NUMBER */
.detail-card td:nth-child(3) {
    color: #0866c6;
    font-weight: 800;
}

/* DETAIL BUTTON */
.btn-detail-so {
    padding: 10px 17px;
    border-radius: 9px;
    background: linear-gradient(135deg, #087ce3, #0755a8);
    box-shadow: 0 4px 10px rgba(8, 102, 198, .16);
    transition: all .25s ease;
}

.btn-detail-so:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 18px rgba(8, 102, 198, .25);
}

/* PAGINATION */
.pagination-container {
    padding-top: 22px;
    margin-top: 25px;
    border-top: 1px solid #edf1f7;
}

.pagination-buttons a {
    box-shadow: 0 3px 9px rgba(8, 102, 198, .12);
}

.pagination-current {
    border: 1px solid #d9e8fa;
    background: #eaf3ff;
    color: #075caf;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .detail-container {
        padding: 14px;
    }

    .detail-card {
        padding: 18px;
    }

    .detail-header h2 {
        font-size: 24px;
    }

    .total-data {
        width: 100%;
    }
}
</style>

<div class="detail-container">
    <div class="detail-card">

        <!-- HEADER -->
        <div class="detail-header">
            <div>
                <h2>Detail Sales Order</h2>
                <p>Daftar transaksi Sales Order berdasarkan periode yang dipilih.</p>
            </div>

            <a href="Main-Penjualan.php"
               style="text-decoration:none;color:#0965c5;">
                ← Kembali ke Dashboard
            </a>
        </div>

        <!-- FILTER DAN PENCARIAN -->
        <form method="GET" class="filter-form">

            <input type="date"
                   name="tanggal_awal"
                   value="<?= htmlspecialchars($startDate) ?>">

            <input type="date"
                   name="tanggal_akhir"
                   value="<?= htmlspecialchars($endDate) ?>">

            <input type="text"
                   name="keyword"
                   placeholder="Cari nomor SO / pelanggan / sales"
                   value="<?= htmlspecialchars($keyword) ?>">

            <select name="per_page">
                <option value="10" <?= $perPage == 10 ? 'selected' : '' ?>>10 Data</option>
                <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25 Data</option>
                <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50 Data</option>
                <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100 Data</option>
            </select>

            <input type="hidden" name="page" value="1">

            <button type="submit">Tampilkan</button>

        </form>

        <!-- TOTAL DATA -->
        <div class="total-data">
            Total Sales Order:
            <strong><?= number_format($totalData, 0, ',', '.') ?> transaksi</strong>
        </div>

        <!-- TABEL SALES ORDER -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
    <th>No.</th>
    <th>Aksi</th>
    <th>No. Sales Order</th>
    <th>Tanggal Inquiry</th>
    <th>Tanggal Konfirmasi</th>
    <th>Nama Pelanggan</th>
    <th>Qty Order</th>
    <th>Total Nominal</th>
    <th>Sales</th>
    <th>Status</th>
</tr>
                </thead>

                <tbody>

                    <?php if (count($dataSO) > 0): ?>

                        <?php foreach ($dataSO as $index => $row): ?>

                            <tr>
                                <td><?= $offset + $index + 1 ?></td>
<td>
    <a href="Detail-SO-View.php?no_so=<?= urlencode($row['no_trans']) ?>&tanggal_awal=<?= urlencode($startDate) ?>&tanggal_akhir=<?= urlencode($endDate) ?>"
       class="btn-detail-so">
        <i class="bi bi-eye"></i> Detail
    </a>
</td>
                                <td>
                                    <?= htmlspecialchars($row['no_trans']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['inquiry_date']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['confirm_date'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['nama_pelanggan']) ?>
                                </td>

                                <td>
                                    <?= number_format((float)$row['qty_order'], 0, ',', '.') ?>
                                </td>

                                <td>
                                    Rp <?= number_format((float)$row['total_rp'], 0, ',', '.') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['sales']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['status']) ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="10" style="text-align:center;">
                                Tidak ada data Sales Order pada periode ini.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <div class="pagination-container">

            <span class="pagination-info">
                Menampilkan
                <?= $totalData > 0 ? $offset + 1 : 0 ?>
                -
                <?= min($offset + $perPage, $totalData) ?>
                dari <?= number_format($totalData, 0, ',', '.') ?> transaksi
            </span>

            <div class="pagination-buttons">

                <?php
                $queryParams = $_GET;
                ?>

                <?php if ($currentPage > 1): ?>

                    <?php $queryParams['page'] = $currentPage - 1; ?>

                    <a href="?<?= htmlspecialchars(http_build_query($queryParams)) ?>">
                        Previous
                    </a>

                <?php endif; ?>

                <span class="pagination-current">
                    Halaman <?= $currentPage ?> / <?= $totalPages ?>
                </span>

                <?php if ($currentPage < $totalPages): ?>

                    <?php $queryParams['page'] = $currentPage + 1; ?>

                    <a href="?<?= htmlspecialchars(http_build_query($queryParams)) ?>">
                        Next
                    </a>

                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
```