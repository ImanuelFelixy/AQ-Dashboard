<?php
require_once __DIR__ . '/../koneksi.php';

$pageTitle = 'Detail Monitoring Logistik';
$activePage = 'penjualan';
$basePath = '../';

// ==========================================
// FILTER TANGGAL
// ==========================================
$startDate = $_GET['tanggal_awal'] ?? '2026-10-01';
$endDate   = $_GET['tanggal_akhir'] ?? '2026-10-31';

// ==========================================
// PENCARIAN
// ==========================================
$keyword = trim($_GET['keyword'] ?? '');

// ==========================================
// FILTER STATUS LOGISTIK
// ==========================================
$statusFilter = $_GET['status'] ?? '';

$allowedStatus = [
    'Siap Dikirim',
    'Proses Pengiriman',
    'Barang Diterima'
];

if (!in_array($statusFilter, $allowedStatus, true)) {
    $statusFilter = '';
}

// ==========================================
// QUERY DASAR DATA LOGISTIK
// ==========================================
$sql = "
    SELECT
        GROUP_CONCAT(DISTINCT d.no_so SEPARATOR ', ') AS no_so,
        GROUP_CONCAT(DISTINCT d.no_wo SEPARATOR ', ') AS no_wo,
        d.no_do,
        MAX(d.nama_pelanggan) AS nama_pelanggan,
        MAX(d.tgl_kirim) AS tgl_kirim,
        MAX(d.tgl_terima) AS tgl_terima,
        MAX(d.no_invoice) AS no_invoice,

        CASE
            WHEN MAX(
                CASE
                    WHEN d.tgl_kirim IS NULL
                        OR CAST(d.tgl_kirim AS CHAR) LIKE '0000-00-00%'
                    THEN 0 ELSE 1
                END
            ) = 0
            THEN 'Siap Dikirim'

            WHEN MAX(
                CASE
                    WHEN d.tgl_terima IS NULL
                        OR CAST(d.tgl_terima AS CHAR) LIKE '0000-00-00%'
                    THEN 0 ELSE 1
                END
            ) = 0
            THEN 'Proses Pengiriman'

            ELSE 'Barang Diterima'
        END AS status_logistik

    FROM report_status_do d
    INNER JOIN report_so s
        ON TRIM(d.no_so) = TRIM(s.no_trans)

    WHERE s.inquiry_date BETWEEN :start_date AND :end_date
";

$params = [
    ':start_date' => $startDate,
    ':end_date' => $endDate
];

// FILTER PENCARIAN
if ($keyword !== '') {
    $sql .= "
        AND (
            d.no_so LIKE :keyword_so
            OR d.no_wo LIKE :keyword_wo
            OR d.no_do LIKE :keyword_do
            OR d.nama_pelanggan LIKE :keyword_pelanggan
            OR d.no_invoice LIKE :keyword_invoice
        )
    ";

    $searchKeyword = "%$keyword%";

    $params[':keyword_so'] = $searchKeyword;
    $params[':keyword_wo'] = $searchKeyword;
    $params[':keyword_do'] = $searchKeyword;
    $params[':keyword_pelanggan'] = $searchKeyword;
    $params[':keyword_invoice'] = $searchKeyword;
}

// GROUPING BERDASARKAN DELIVERY ORDER
$sql .= " GROUP BY d.no_do ";

// FILTER STATUS
if ($statusFilter !== '') {
    $sql .= " HAVING status_logistik = :status ";
    $params[':status'] = $statusFilter;
}
// ==========================================
// PAGINATION
// ==========================================
$countSql = "SELECT COUNT(*) FROM (" . $sql . ") AS data_logistik";

$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);

$totalData = (int) $countStmt->fetchColumn();

$perPage = (int) ($_GET['per_page'] ?? 10);
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 10;
}

$totalPages = max(1, (int) ceil($totalData / $perPage));

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$currentPage = min($currentPage, $totalPages);

$offset = ($currentPage - 1) * $perPage;

// ==========================================
// AMBIL DATA
// ==========================================
$sql .= "
    ORDER BY tgl_kirim DESC, no_do DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$dataLogistik = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.detail-container {
    padding: 30px;
    background: #f4f7fc;
    min-height: calc(100vh - 100px);
}

.detail-card {
    background: #ffffff;
    border: 1px solid #e5eaf2;
    border-radius: 18px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(30, 64, 110, .05);
}

/* HEADER */
.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 28px;
}

.detail-header h2 {
    font-size: 25px;
    font-weight: 800;
    color: #17365d;
    margin: 0 0 7px;
}

.detail-header p {
    color: #8492a6;
    font-size: 13px;
    margin: 0;
}

.detail-header a {
    display: inline-flex;
    align-items: center;
    background: #edf5ff;
    color: #0866c6 !important;
    padding: 10px 16px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    transition: .2s;
}

.detail-header a:hover {
    background: #dcecff;
}

/* FILTER */
.filter-form {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 18px;
    background: #f7faff;
    border: 1px solid #e7eef8;
    border-radius: 13px;
    margin-bottom: 22px;
}

.filter-form input,
.filter-form select {
    height: 43px;
    padding: 0 13px;
    border: 1px solid #dce4ef;
    border-radius: 8px;
    background: #ffffff;
    color: #334155;
    font-size: 12px;
    outline: none;
}

.filter-form input:focus,
.filter-form select:focus {
    border-color: #1680df;
    box-shadow: 0 0 0 3px rgba(22,128,223,.10);
}

.filter-form input[type="text"] {
    min-width: 220px;
    flex: 1;
}

.filter-form button {
    height: 43px;
    padding: 0 22px;
    border: none;
    border-radius: 8px;
    background: #0866c6;
    color: white;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.filter-form button:hover {
    background: #0754a5;
}

/* TOTAL DATA */
.total-data {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 15px;
    margin-bottom: 18px;
    background: #edf5ff;
    border: 1px solid #dcecff;
    border-radius: 9px;
    color: #64748b;
    font-size: 12px;
}

.total-data strong {
    color: #0866c6;
    font-weight: 800;
}

/* TABLE */
.table-responsive {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #e8edf4;
    border-radius: 12px;
}

.detail-card table {
    width: 100%;
    min-width: 1250px;
    border-collapse: separate;
    border-spacing: 0;
}

.detail-card th {
    padding: 15px 14px;
    background: #edf4fc;
    color: #29496e;
    font-size: 11px;
    font-weight: 800;
    text-align: left;
    white-space: nowrap;
    border-bottom: 1px solid #dce6f2;
}

.detail-card td {
    padding: 15px 14px;
    color: #475569;
    font-size: 12px;
    border-bottom: 1px solid #edf0f5;
    vertical-align: middle;
    white-space: nowrap;
}

.detail-card tbody tr {
    background: #ffffff;
    transition: background .2s;
}

.detail-card tbody tr:hover {
    background: #f5f9ff;
}

.detail-card tbody tr:last-child td {
    border-bottom: none;
}

/* NOMOR TRANSAKSI */
.so-number,
.do-number {
    color: #0866c6;
    font-weight: 700;
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
    border-radius: 6px;
    font-size: 10px;
    font-weight: 700;
    line-height: 1.4;
}

/* STATUS BADGE */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 7px 11px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
}

.status-siap {
    background: #edf0ff;
    color: #6255c7;
}

.status-proses {
    background: #fff3dc;
    color: #c17a12;
}

.status-diterima {
    background: #e5f7ee;
    color: #18865b;
}

/* PAGINATION */
.pagination-area {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 22px;
}

.pagination-area a {
    padding: 9px 15px;
    background: #0866c6;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    transition: .2s;
}

.pagination-area a:hover {
    background: #0754a5;
}

.pagination-area span {
    padding: 9px 14px;
    background: #edf4fc;
    color: #29496e;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
}

@media (max-width: 768px) {
    .detail-container {
        padding: 15px;
    }

    .detail-card {
        padding: 18px;
    }

    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-form input,
    .filter-form select,
    .filter-form button {
        width: 100%;
    }

    .pagination-area {
        flex-direction: column;
        align-items: flex-start;
    }
}
/* TOMBOL LIHAT WORK ORDER */
.wo-trigger {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 13px;
    border: 1px solid #d7e7fb;
    border-radius: 9px;
    background: #f0f6ff;
    color: #0866c6;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.wo-trigger:hover {
    background: #0866c6;
    color: #fff;
    border-color: #0866c6;
    transform: translateY(-1px);
}

.wo-trigger .wo-count {
    background: #dceaff;
    color: #0866c6;
    padding: 3px 7px;
    border-radius: 5px;
}

/* POPUP */
.wo-dialog {
    width: min(500px, calc(100% - 30px));
    border: none;
    border-radius: 18px;
    padding: 0;
    box-shadow: 0 20px 60px rgba(15, 35, 65, .2);
}

.wo-dialog::backdrop {
    background: rgba(15, 35, 65, .48);
    backdrop-filter: blur(3px);
}

.wo-modal-header {
    padding: 22px 25px;
    background: #f0f6ff;
    border-bottom: 1px solid #e1ebf7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.wo-modal-header h3 {
    color: #17365d;
    font-size: 17px;
    font-weight: 800;
    margin: 0;
}

.wo-modal-header p {
    color: #8492a6;
    font-size: 11px;
    margin: 5px 0 0;
}

.wo-modal-close {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
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
    border-radius: 10px;
}

.wo-modal-number {
    width: 29px;
    height: 29px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #e4efff;
    color: #0866c6;
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
/* =========================================
   AQUAMATE - PREMIUM DETAIL LOGISTIK
   VISUAL MATCH DETAIL SALES ORDER
========================================= */

/* PAGE BACKGROUND */
.detail-container {
    padding: 34px;
    background:
        radial-gradient(ellipse at 90% 0%, rgba(46, 130, 220, .10), transparent 35%),
        #f1f5fb;
    min-height: calc(100vh - 100px);
}

/* MAIN CARD */
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

/* HEADER */
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

.detail-header a {
    border-radius: 10px;
    transition: all .25s ease;
}

.detail-header a:hover {
    transform: translateX(-4px);
}

/* FILTER */
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

/* TOTAL DATA */
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

/* ALTERNATING ROW COLORS */
.detail-card tbody tr:nth-child(odd) {
    background: #ffffff;
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

/* WO BUTTON */
.wo-trigger {
    transition: all .25s ease;
}

.wo-trigger:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 18px rgba(8, 102, 198, .18);
}

/* STATUS BADGE */
.status-badge {
    font-weight: 800;
}

/* PAGINATION */
.pagination-container,
.pagination-area {
    padding-top: 22px;
    margin-top: 25px;
    border-top: 1px solid #edf1f7;
}

.pagination-buttons a,
.pagination-area a {
    box-shadow: 0 3px 9px rgba(8, 102, 198, .12);
}

.pagination-current,
.pagination-area div > span {
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

        <div class="detail-header">
            <div>
                <h2>Detail Monitoring Logistik</h2>
                <p>
                    Daftar transaksi pengiriman berdasarkan periode
                    Sales Order yang dipilih.
                </p>
            </div>

            <a href="Main-Penjualan.php?tanggal_awal=<?= urlencode($startDate) ?>&tanggal_akhir=<?= urlencode($endDate) ?>"
               style="text-decoration:none;color:#0965c5;">
                ← Kembali ke Dashboard
            </a>
        </div>

        <!-- FILTER -->
        <form method="GET" class="filter-form">

            <input type="date"
                   name="tanggal_awal"
                   value="<?= htmlspecialchars($startDate) ?>">

            <input type="date"
                   name="tanggal_akhir"
                   value="<?= htmlspecialchars($endDate) ?>">

            <input type="text"
                   name="keyword"
                   placeholder="Cari SO / WO / DO / pelanggan"
                   value="<?= htmlspecialchars($keyword) ?>">

            <select name="status">
                <option value="">Semua Status</option>

                <?php foreach ($allowedStatus as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>"
                        <?= $statusFilter === $status ? 'selected' : '' ?>>
                        <?= htmlspecialchars($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="per_page">
                <?php foreach ($allowedPerPage as $jumlah): ?>
                    <option value="<?= $jumlah ?>"
                        <?= $perPage === $jumlah ? 'selected' : '' ?>>
                        <?= $jumlah ?> Data
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Tampilkan</button>
        </form>

        <!-- TOTAL DATA -->
        <div class="total-data">
            Total Data Logistik:
            <strong>
                <?= number_format($totalData, 0, ',', '.') ?> data
            </strong>
        </div>

        <!-- TABEL -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>No. Sales Order</th>
                        <th>No. Work Order</th>
                        <th>No. Delivery Order</th>
                        <th>Nama Pelanggan</th>
                        <th>Tanggal Kirim</th>
                        <th>Tanggal Terima</th>
                        <th>No. Invoice</th>
                        <th>Status Logistik</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($dataLogistik)): ?>

                        <?php foreach ($dataLogistik as $index => $row): ?>

                            <?php
                            $statusClass = 'status-diterima';

                            if ($row['status_logistik'] === 'Siap Dikirim') {
                                $statusClass = 'status-siap';
                            } elseif ($row['status_logistik'] === 'Proses Pengiriman') {
                                $statusClass = 'status-proses';
                            }
                            ?>

                            <tr>
                                <td><?= $offset + $index + 1 ?></td>

                                <td><?= htmlspecialchars($row['no_so'] ?? '-') ?></td>

<td>
    <?php
    $daftarWO = array_values(array_filter(
        array_map('trim', explode(',', $row['no_wo'] ?? ''))
    ));
    ?>

    <?php if (!empty($daftarWO)): ?>
        <button
            type="button"
            class="wo-trigger"
            data-wo="<?= htmlspecialchars(json_encode($daftarWO), ENT_QUOTES, 'UTF-8') ?>"
            onclick="bukaDetailWO(this)"
        >
            Lihat Work Order
            <span class="wo-count"><?= count($daftarWO) ?></span>
            <i class="bi bi-arrow-up-right"></i>
        </button>
    <?php else: ?>
        <span>-</span>
    <?php endif; ?>
</td>
                                <td><?= htmlspecialchars($row['no_do'] ?? '-') ?></td>

                                <td><?= htmlspecialchars($row['nama_pelanggan'] ?? '-') ?></td>

                                <td>
                                    <?= (
                                        empty($row['tgl_kirim']) ||
                                        str_starts_with((string)$row['tgl_kirim'], '0000-00-00')
                                    ) ? '-' : htmlspecialchars($row['tgl_kirim']) ?>
                                </td>

                                <td>
                                    <?= (
                                        empty($row['tgl_terima']) ||
                                        str_starts_with((string)$row['tgl_terima'], '0000-00-00')
                                    ) ? '-' : htmlspecialchars($row['tgl_terima']) ?>
                                </td>

                                <td><?= htmlspecialchars($row['no_invoice'] ?? '-') ?></td>

                                <td>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= htmlspecialchars($row['status_logistik']) ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" style="text-align:center;">
                                Tidak ada data logistik pada periode ini.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>
            </table>
        </div>
<dialog id="woDialog" class="wo-dialog">
    <div class="wo-modal-header">
        <div>
            <h3>Daftar Work Order</h3>
            <p id="woModalSubtitle">Rincian Work Order</p>
        </div>

        <button
            type="button"
            class="wo-modal-close"
            onclick="tutupDetailWO()"
        >&times;</button>
    </div>

    <div class="wo-modal-body">
        <div id="woModalList" class="wo-modal-list"></div>
    </div>
</dialog>
        <!-- PAGINATION -->
        <div class="pagination-area">

            <span style="font-size:13px;color:#555;">
                Menampilkan
                <?= $totalData > 0 ? $offset + 1 : 0 ?>
                -
                <?= min($offset + $perPage, $totalData) ?>
                dari
                <?= number_format($totalData, 0, ',', '.') ?> data
            </span>

            <div style="display:flex;gap:8px;align-items:center;">

                <?php
                $queryParams = $_GET;
                ?>

                <?php if ($currentPage > 1): ?>
                    <?php $queryParams['page'] = $currentPage - 1; ?>

                    <a href="?<?= htmlspecialchars(http_build_query($queryParams)) ?>">
                        Previous
                    </a>
                <?php endif; ?>

                <span>
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
<script>
function bukaDetailWO(button) {
    const daftarWO = JSON.parse(button.dataset.wo);

    const dialog = document.getElementById('woDialog');
    const list = document.getElementById('woModalList');
    const subtitle = document.getElementById('woModalSubtitle');

    list.innerHTML = '';
    subtitle.textContent = daftarWO.length + ' Work Order ditemukan';

    daftarWO.forEach((wo, index) => {
        const item = document.createElement('div');
        item.className = 'wo-modal-item';

        const number = document.createElement('div');
        number.className = 'wo-modal-number';
        number.textContent = index + 1;

        const code = document.createElement('div');
        code.className = 'wo-modal-code';
        code.textContent = wo;

        item.appendChild(number);
        item.appendChild(code);
        list.appendChild(item);
    });

    dialog.showModal();
}

function tutupDetailWO() {
    document.getElementById('woDialog').close();
}

document.getElementById('woDialog').addEventListener('click', function(event) {
    if (event.target === this) {
        this.close();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>