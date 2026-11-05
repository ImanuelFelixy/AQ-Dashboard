
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
            no_trans LIKE :keyword
            OR nama_pelanggan LIKE :keyword
            OR sales LIKE :keyword
        )
    ";

    $params[':keyword'] = "%$keyword%";
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
            no_trans LIKE :keyword
            OR nama_pelanggan LIKE :keyword
            OR sales LIKE :keyword
        )
    ";

    $countParams[':keyword'] = "%$keyword%";
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
    $stmt->bindValue($key, $value);
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
    .btn-detail-so {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 7px 13px;
    background: #0866c6;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    transition: background .2s;
}

.btn-detail-so:hover {
    background: #0754a5;
    color: white;
}
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
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.detail-header h2 {
    font-size: 23px;
    font-weight: 700;
}

.filter-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    align-items: center;
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
    background: #0755a5;
}

.table-responsive {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

th, td {
    padding: 13px;
    border-bottom: 1px solid #eee;
    text-align: left;
    font-size: 13px;
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

.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 20px;
}

.pagination-info {
    font-size: 13px;
    color: #555;
}

.pagination-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pagination-buttons a {
    padding: 9px 14px;
    background: #0965c5;
    color: white;
    border-radius: 7px;
    text-decoration: none;
    font-size: 13px;
}

.pagination-buttons a:hover {
    background: #0755a5;
}

.pagination-current {
    padding: 9px 14px;
    background: #eef3fb;
    border-radius: 7px;
    font-size: 13px;
}

@media (max-width: 768px) {
    .detail-container {
        padding: 15px;
    }

    .detail-card {
        padding: 15px;
    }

    .pagination-container {
        flex-direction: column;
        align-items: flex-start;
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