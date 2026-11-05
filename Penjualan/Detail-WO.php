<?php
require_once __DIR__ . '/../koneksi.php';

$pageTitle = 'Detail Work Order';
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
// 3. QUERY DATA WORK ORDER
// ==========================================

$sql = "
    SELECT *
    FROM report_alur_wo
    WHERE tgl_trans_wo BETWEEN :start_date AND :end_date
";

$params = [
    ':start_date' => $startDate,
    ':end_date'   => $endDate
];

if ($keyword !== '') {
    $sql .= "
        AND (
            no_wo LIKE :keyword
            OR no_so LIKE :keyword
            OR pelanggan LIKE :keyword
            OR barang LIKE :keyword
        )
    ";

    $params[':keyword'] = "%$keyword%";
}

// ==========================================
// 4. HITUNG TOTAL DATA
// ==========================================

$countSql = "
    SELECT COUNT(*)
    FROM report_alur_wo
    WHERE tgl_trans_wo BETWEEN :start_date AND :end_date
";

$countParams = [
    ':start_date' => $startDate,
    ':end_date'   => $endDate
];

if ($keyword !== '') {
    $countSql .= "
        AND (
            no_wo LIKE :keyword
            OR no_so LIKE :keyword
            OR pelanggan LIKE :keyword
            OR barang LIKE :keyword
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
    ORDER BY tgl_trans_wo DESC, no_wo DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$dataWO = $stmt->fetchAll();

// ==========================================
// 7. HEADER
// ==========================================

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
    min-width: 1000px;
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
                <h2>Detail Work Order</h2>
                <p>Daftar transaksi Work Order berdasarkan periode yang dipilih.</p>
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
                   placeholder="Cari nomor WO / SO / pelanggan / barang"
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
            Total Work Order:
            <strong><?= number_format($totalData, 0, ',', '.') ?> data</strong>
        </div>

        <!-- TABEL WORK ORDER -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>No. WO</th>
                        <th>No. SO</th>
                        <th>Pelanggan</th>
                        <th>Tanggal WO</th>
                        <th>Estimasi Selesai</th>
                        <th>Barang</th>
                        <th>Tebal</th>
                        <th>Qty</th>
                        <th>Qty DO</th>
                        <th>Prioritas</th>
                        <th>Status Kirim</th>
                        <th>Kota</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($dataWO)): ?>

                        <?php foreach ($dataWO as $index => $row): ?>

                            <tr>
                                <td><?= $offset + $index + 1 ?></td>

                                <td>
                                    <?= htmlspecialchars($row['no_wo']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['no_so']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['pelanggan']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['tgl_trans_wo']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['tgl_estimasi_selesai']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['barang']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['tebal']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['qty']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['qty_do']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['prioritas_reguler']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['status_kirim']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['kota']) ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="13" style="text-align:center;">
                                Tidak ada data Work Order pada periode ini.
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
                dari <?= number_format($totalData, 0, ',', '.') ?> data
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