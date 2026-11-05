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
}

.detail-card {
    background: white;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
}

.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}

.detail-header h2 {
    font-size: 23px;
    font-weight: 700;
    color: #0965c5;
}

.detail-header p {
    color: #64748b;
    margin-top: 6px;
}

.filter-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.filter-form input,
.filter-form select,
.filter-form button {
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.filter-form button {
    background: #0965c5;
    color: white;
    border: none;
    cursor: pointer;
}

.filter-form button:hover {
    background: #0754a5;
}

.table-responsive {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1100px;
}

th, td {
    padding: 13px;
    border-bottom: 1px solid #eee;
    text-align: left;
    font-size: 13px;
    white-space: nowrap;
}
/* Tampilan daftar Work Order */
td.wo-list {
    white-space: normal;
    min-width: 280px;
    max-width: 400px;
    line-height: 1.8;
    overflow-wrap: anywhere;
}

th {
    background: #f2f6fc;
    color: #34516f;
}

.total-data {
    margin-bottom: 15px;
    color: #555;
    font-size: 14px;
}

.status-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.status-siap {
    background: #ede9fe;
    color: #7066e0;
}

.status-proses {
    background: #d1fae5;
    color: #059669;
}

.status-diterima {
    background: #ffe4e6;
    color: #e45b69;
}

.pagination-area {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 20px;
}

.pagination-area a {
    padding: 9px 14px;
    background: #0965c5;
    color: white;
    border-radius: 7px;
    text-decoration: none;
}

.pagination-area span {
    padding: 9px 14px;
    background: #eef3fb;
    border-radius: 7px;
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

                                <td class="wo-list">
    <?= htmlspecialchars($row['no_wo'] ?? '-') ?>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>