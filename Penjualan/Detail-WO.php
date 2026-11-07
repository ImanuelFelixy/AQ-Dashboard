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
:root {
    --aq-primary: #0866c6;
    --aq-navy: #17365d;
    --aq-text: #334155;
    --aq-muted: #8492a6;
}

/* PAGE BACKGROUND */
.detail-container {
    padding: 34px;
    background:
        radial-gradient(ellipse at 90% 0%, rgba(46, 130, 220, .10), transparent 35%),
        linear-gradient(135deg, #eef4fc, #f8faff);
    min-height: calc(100vh - 100px);
}

/* MAIN CARD */
.detail-card {
    position: relative;
    overflow: hidden;
    background: #ffffff;
    border: 1px solid #e4ebf5;
    border-radius: 22px;
    padding: 32px;
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
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 18px;
    padding-bottom: 25px;
    margin-bottom: 28px;
    border-bottom: 1px solid #edf1f7;
}

.detail-header h2 {
    font-size: 30px;
    font-weight: 800;
    color: #142f50;
    margin: 0 0 8px;
    letter-spacing: -1px;
}

.detail-header p {
    color: #8493a8;
    font-size: 13px;
    margin: 0;
}

.detail-header a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 18px;
    background: linear-gradient(135deg, #edf5ff, #e2efff);
    border: 1px solid #d8e9ff;
    border-radius: 10px;
    color: #0866c6 !important;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    transition: all .25s ease;
}

.detail-header a:hover {
    background: #0866c6;
    color: white !important;
    transform: translateX(-4px);
    box-shadow: 0 7px 16px rgba(8, 102, 198, .2);
}

/* FILTER */
.filter-form {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 20px;
    margin-bottom: 25px;
    background: linear-gradient(120deg, #f5f9ff, #edf5ff);
    border: 1px solid #e0ebf9;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(35, 80, 130, .035);
}

.filter-form input,
.filter-form select {
    height: 47px;
    padding: 0 14px;
    background: #ffffff;
    border: 1px solid #dce6f2;
    border-radius: 10px;
    color: #334155;
    font-size: 12px;
    outline: none;
    transition: all .2s ease;
}

.filter-form input:focus,
.filter-form select:focus {
    border-color: #1683e5;
    box-shadow: 0 0 0 4px rgba(22, 131, 229, .10);
}

.filter-form input[type="text"] {
    min-width: 230px;
    flex: 1;
}

.filter-form button {
    height: 47px;
    padding: 0 25px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #087ce3, #0755a8);
    color: white;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all .25s ease;
}

.filter-form button:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 16px rgba(8, 102, 198, .22);
}

/* TOTAL DATA */
.total-data {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 250px;
    padding: 18px 20px 18px 55px;
    margin-bottom: 23px;
    background: linear-gradient(120deg, #eaf4ff, #f5f9ff);
    border: 1px solid #d8e8fb;
    border-radius: 13px;
    color: #64748b;
    font-size: 12px;
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
    color: #0866c6;
    font-size: 13px;
    font-weight: 800;
}

/* TABLE */
.table-responsive {
    width: 100%;
    overflow-x: auto;
    background: #ffffff;
    border: 1px solid #e2eaf4;
    border-radius: 15px;
    box-shadow: 0 8px 24px rgba(20, 50, 90, .045);
}

.detail-card table {
    width: 100%;
    min-width: 1450px;
    border-collapse: separate;
    border-spacing: 0;
}

/* TABLE HEADER */
.detail-card th {
    padding: 19px 16px;
    background: #eaf2fc;
    color: #23476f;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .25px;
    text-align: left;
    white-space: nowrap;
    border-bottom: 1px solid #dce7f5;
}

/* TABLE ROWS */
.detail-card td {
    padding: 19px 16px;
    color: #52647a;
    font-size: 12px;
    vertical-align: middle;
    white-space: nowrap;
    border-bottom: 1px solid #edf1f7;
    transition: background .2s ease;
}

.detail-card tbody tr {
    background: #ffffff;
    transition: background .2s ease;
}

/* ALTERNATING ROW COLORS */
.detail-card tbody tr:nth-child(even) {
    background: #f4f8fe;
}

.detail-card tbody tr:nth-child(odd) {
    background: #ffffff;
}

/* HOVER */
.detail-card tbody tr:hover {
    background: #eaf4ff;
}

.detail-card tbody tr:hover td {
    color: #1c426c;
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
    font-weight: 800;
}

.detail-card td:nth-child(3) {
    color: #29496e;
    font-weight: 700;
}

/* PAGINATION */
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    padding-top: 22px;
    margin-top: 25px;
    border-top: 1px solid #edf1f7;
}

.pagination-info {
    color: #8493a8;
    font-size: 12px;
}

.pagination-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pagination-buttons a {
    padding: 10px 16px;
    background: linear-gradient(135deg, #087ce3, #0755a8);
    color: white;
    border-radius: 9px;
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    box-shadow: 0 3px 9px rgba(8, 102, 198, .12);
    transition: all .2s ease;
}

.pagination-buttons a:hover {
    background: #064b91;
    transform: translateY(-2px);
}

.pagination-current {
    padding: 10px 15px;
    background: #eaf3ff;
    color: #075caf;
    border: 1px solid #d9e8fa;
    border-radius: 9px;
    font-size: 11px;
    font-weight: 700;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .detail-container {
        padding: 14px;
    }

    .detail-card {
        padding: 18px;
        border-radius: 16px;
    }

    .detail-header h2 {
        font-size: 24px;
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

    .total-data {
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