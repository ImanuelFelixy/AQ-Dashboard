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
    min-width: 250px;
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

/* TOTAL */
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
    min-width: 1450px;
    border-collapse: separate;
    border-spacing: 0;
}

.detail-card th {
    padding: 15px 13px;
    background: #edf4fc;
    color: #29496e;
    font-size: 11px;
    font-weight: 800;
    text-align: left;
    white-space: nowrap;
    border-bottom: 1px solid #dce6f2;
}

.detail-card td {
    padding: 15px 13px;
    color: #475569;
    font-size: 12px;
    border-bottom: 1px solid #edf0f5;
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

.detail-card td:nth-child(1) {
    color: #94a3b8;
    font-weight: 700;
}

.detail-card td:nth-child(2) {
    color: #0866c6;
    font-weight: 700;
}

.detail-card td:nth-child(3) {
    color: #475569;
    font-weight: 700;
}

/* PAGINATION */
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 22px;
}

.pagination-info {
    color: #8492a6;
    font-size: 12px;
}

.pagination-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pagination-buttons a {
    padding: 9px 15px;
    background: #0866c6;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    transition: .2s;
}

.pagination-buttons a:hover {
    background: #0754a5;
}

.pagination-current {
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

    .detail-header h2 {
        font-size: 21px;
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