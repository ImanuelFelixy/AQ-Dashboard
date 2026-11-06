<?php
$pageTitle = 'Detail Monitoring Sales Order';
$activePage = 'penjualan';
$basePath = '../';

require_once __DIR__ . '/../koneksi.php';

$noSO = trim($_GET['no_so'] ?? '');
$startDate = $_GET['tanggal_awal'] ?? '2026-10-01';
$endDate = $_GET['tanggal_akhir'] ?? '2026-10-31';

if ($noSO === '') {
    die('Nomor Sales Order tidak ditemukan.');
}

// ==========================================
// FUNGSI FORMAT TANGGAL
// ==========================================
function tanggalTampil($tanggal)
{
    if (
        empty($tanggal) ||
        str_starts_with((string)$tanggal, '0000-00-00')
    ) {
        return '-';
    }

    return date('d/m/Y', strtotime($tanggal));
}

// ==========================================
// DATA UTAMA SALES ORDER
// ==========================================
$stmtSO = $conn->prepare("
    SELECT *
    FROM report_so
    WHERE TRIM(no_trans) = :no_so
    LIMIT 1
");

$stmtSO->execute([
    ':no_so' => $noSO
]);

$dataSO = $stmtSO->fetch();

if (!$dataSO) {
    die('Data Sales Order tidak ditemukan.');
}

// ==========================================
// DATA WORK ORDER
// ==========================================
$stmtWO = $conn->prepare("
    SELECT *
    FROM report_alur_wo
    WHERE TRIM(no_so) = :no_so
    ORDER BY no_wo ASC
");

$stmtWO->execute([
    ':no_so' => $noSO
]);

$dataWO = $stmtWO->fetchAll();

// ==========================================
// DATA DELIVERY ORDER / LOGISTIK
// ==========================================
$stmtDO = $conn->prepare("
    SELECT *
    FROM report_status_do
    WHERE TRIM(no_so) = :no_so
    ORDER BY tgl_kirim DESC, no_do DESC
");

$stmtDO->execute([
    ':no_so' => $noSO
]);

$dataDO = $stmtDO->fetchAll();

// ==========================================
// HITUNG PROGRES TRANSAKSI
// ==========================================

// Total Work Order
$totalWO = count($dataWO);

// Total Delivery Order unik
$stmtTotalDO = $conn->prepare("
    SELECT COUNT(DISTINCT TRIM(no_do))
    FROM report_status_do
    WHERE TRIM(no_so) = :no_so
    AND no_do IS NOT NULL
    AND TRIM(no_do) <> ''
");

$stmtTotalDO->execute([
    ':no_so' => $noSO
]);

$totalDO = (int) $stmtTotalDO->fetchColumn();

// Total Qty Surat Jalan berdasarkan WO unik
$stmtQtyDO = $conn->prepare("
    SELECT COALESCE(SUM(qty_do), 0)
    FROM (
        SELECT
            no_wo,
            MAX(qty_do) AS qty_do
        FROM report_alur_wo
        WHERE TRIM(no_so) = :no_so
        GROUP BY no_wo
    ) AS hasil
");

$stmtQtyDO->execute([
    ':no_so' => $noSO
]);

$totalQtyDO = (float) $stmtQtyDO->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* Perbaikan warna judul banner dashboard */
.sales-banner-header .sales-banner-title {
    color: #ffffff !important;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.4px;
}

.sales-banner-header .sales-banner-title i {
    color: #bfdbfe !important;
}

.sales-banner-header .sales-banner-desc {
    color: #eaf3ff !important;
}
.so-detail-container {
    padding: 28px;
    background: #f1f5ff;
    min-height: 100vh;
}

.so-card {
    background: #fff;
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
}

.so-banner {
    background: #0754c7;
    color: #fff;
    padding: 18px 22px;
    border-radius: 10px 10px 0 0;
}

.so-banner h2 {
    font-size: 18px;
    font-weight: 800;
    margin: 0 0 5px;
    color: #ffffff;
}
.so-banner p {
    font-size: 12px;
    margin: 0;
    opacity: .9;
}

.so-main-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    padding: 20px;
}

.so-main-info h3 {
    font-size: 22px;
    font-weight: 800;
    margin: 0 0 8px;
    color: #172b4d;
}

.so-main-info p {
    margin: 0;
    font-size: 13px;
    color: #64748b;
}

.so-status {
    background: #dbeafe;
    color: #0754c7;
    padding: 7px 13px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.so-progress {
    background: #e0f2fe;
    padding: 13px 18px;
    border-radius: 8px;
    color: #0754c7;
    font-size: 13px;
    font-weight: 700;
}

.so-section-title {
    font-size: 15px;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 17px;
}

.so-table-wrapper {
    overflow-x: auto;
}

.so-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 750px;
}

.so-table th {
    background: #eaf2ff;
    color: #34516f;
    padding: 13px;
    font-size: 12px;
    text-align: left;
    white-space: nowrap;
}

.so-table td {
    padding: 13px;
    border-bottom: 1px solid #e8edf5;
    font-size: 12px;
    color: #334155;
}

.so-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 15px;
    font-size: 10px;
    font-weight: 700;
}

.badge-blue {
    background: #dbeafe;
    color: #0754c7;
}

.badge-green {
    background: #d1fae5;
    color: #047857;
}

.badge-orange {
    background: #ffedd5;
    color: #c2410c;
}

.badge-red {
    background: #fee2e2;
    color: #dc2626;
}

.so-empty {
    text-align: center;
    padding: 25px !important;
    color: #94a3b8 !important;
}

.so-back {
    display: inline-block;
    margin: 5px 0 20px;
    color: #0754c7;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
}

.so-back:hover {
    text-decoration: underline;
}

.so-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-top: 15px;
}

.so-summary-item {
    background: #f5f8ff;
    border: 1px solid #e5edfa;
    padding: 15px;
    border-radius: 9px;
}

.so-summary-item span {
    display: block;
    font-size: 11px;
    color: #64748b;
    margin-bottom: 7px;
}

.so-summary-item strong {
    font-size: 21px;
    color: #0754c7;
}

@media(max-width:768px) {
    .so-detail-container {
        padding: 15px;
    }

    .so-summary {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="so-detail-container">

    <!-- ================================== -->
    <!-- INFORMASI UTAMA SALES ORDER -->
    <!-- ================================== -->

    <div class="so-card" style="padding:0; overflow:hidden;">

        <div class="so-banner">
            <h2>Detail Monitoring Sales Order</h2>
            <p>
                Pelacakan transaksi Sales Order, progres Work Order,
                pengiriman logistik, dan faktur penjualan.
            </p>
        </div>

        <div class="so-main-info">

            <div>
                <h3>
                    <?= htmlspecialchars($dataSO['no_trans']) ?>
                </h3>

                <p>
                    <?= htmlspecialchars($dataSO['nama_pelanggan']) ?>
                    &nbsp; • &nbsp;
                    Sales: <?= htmlspecialchars($dataSO['sales'] ?? '-') ?>
                </p>
            </div>

            <div>
                <span class="so-status">
                    <?= htmlspecialchars($dataSO['status'] ?? '-') ?>
                </span>
            </div>

        </div>

    </div>

    <!-- ================================== -->
    <!-- RINGKASAN PROGRES -->
    <!-- ================================== -->

    <div class="so-card">

        <h3 class="so-section-title">
            Ringkasan Aktivitas Transaksi
        </h3>

        <div class="so-summary">

            <div class="so-summary-item">
                <span>Total Work Order</span>
                <strong><?= $totalWO ?></strong>
            </div>

            <div class="so-summary-item">
                <span>Total Delivery Order</span>
                <strong><?= $totalDO ?></strong>
            </div>

            <div class="so-summary-item">
    <span>Qty DO</span>
    <strong><?= number_format($totalQtyDO, 0, ',', '.') ?></strong>
</div>

        </div>

    </div>

    <!-- ================================== -->
    <!-- INFORMASI SALES ORDER -->
    <!-- ================================== -->

    <div class="so-card">

        <h3 class="so-section-title">
            Informasi Sales Order
        </h3>

        <div class="so-table-wrapper">

            <table class="so-table">

                <thead>
                    <tr>
                        <th>No. SO</th>
                        <th>Tanggal Inquiry</th>
                        <th>Tanggal Konfirmasi</th>
                        <th>Nama Pelanggan</th>
                        <th>Qty Order</th>
                        <th>Total Nominal</th>
                        <th>Sales</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>
                            <?= htmlspecialchars($dataSO['no_trans']) ?>
                        </td>

                        <td>
                            <?= tanggalTampil($dataSO['inquiry_date']) ?>
                        </td>

                        <td>
                            <?= tanggalTampil($dataSO['confirm_date']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($dataSO['nama_pelanggan']) ?>
                        </td>

                        <td>
                            <?= number_format((float)$dataSO['qty_order'], 0, ',', '.') ?>
                        </td>

                        <td>
                            Rp <?= number_format((float)$dataSO['total_rp'], 0, ',', '.') ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($dataSO['sales'] ?? '-') ?>
                        </td>
                    </tr>
                </tbody>

            </table>

        </div>

    </div>

    <!-- ================================== -->
    <!-- RINCIAN WORK ORDER -->
    <!-- ================================== -->

    <div class="so-card">

        <h3 class="so-section-title">
            Rincian Work Orders & Pelacakan Alur
        </h3>

        <div class="so-table-wrapper">

            <table class="so-table">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>No. Work Order</th>
                        <th>Tanggal WO</th>
                        <th>Estimasi Selesai</th>
                        <th>Barang</th>
                        <th>Tebal</th>
                        <th>Qty</th>
                        <th>Qty DO</th>
                        <th>Status Kirim</th>
                        <th>Kota</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($dataWO)): ?>

                        <?php foreach ($dataWO as $i => $wo): ?>

                            <tr>
                                <td><?= $i + 1 ?></td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($wo['no_wo'] ?? '-') ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= tanggalTampil($wo['tgl_trans_wo']) ?>
                                </td>

                                <td>
                                    <?= tanggalTampil($wo['tgl_estimasi_selesai']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($wo['barang'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($wo['tebal'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= number_format((float)$wo['qty'], 2, ',', '.') ?>
                                </td>

                                <td>
                                    <?= number_format((float)$wo['qty_do'], 2, ',', '.') ?>
                                </td>

                                <td>
                                    <span class="so-badge badge-blue">
                                        <?= htmlspecialchars($wo['status_kirim'] ?? '-') ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($wo['kota'] ?? '-') ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="10" class="so-empty">
                                Belum ada Work Order untuk Sales Order ini.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

    <!-- ================================== -->
    <!-- DETAIL PENGIRIMAN LOGISTIK -->
    <!-- ================================== -->

    <div class="so-card">

        <h3 class="so-section-title">
            Detail Pengiriman Logistik
        </h3>

        <div class="so-table-wrapper">

            <table class="so-table">

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

                    <?php if (!empty($dataDO)): ?>

                        <?php foreach ($dataDO as $i => $do): ?>

                            <?php
                            $tglKirim = $do['tgl_kirim'] ?? null;
                            $tglTerima = $do['tgl_terima'] ?? null;

                            if (
                                empty($tglKirim) ||
                                str_starts_with((string)$tglKirim, '0000-00-00')
                            ) {
                                $statusLogistik = 'Siap Dikirim';
                                $badgeClass = 'badge-blue';
                            } elseif (
                                empty($tglTerima) ||
                                str_starts_with((string)$tglTerima, '0000-00-00')
                            ) {
                                $statusLogistik = 'Proses Pengiriman';
                                $badgeClass = 'badge-orange';
                            } else {
                                $statusLogistik = 'Barang Diterima';
                                $badgeClass = 'badge-green';
                            }
                            ?>

                            <tr>

                                <td><?= $i + 1 ?></td>

                                <td>
                                    <?= htmlspecialchars($do['no_so'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($do['no_wo'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($do['no_do'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($do['nama_pelanggan'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= tanggalTampil($do['tgl_kirim']) ?>
                                </td>

                                <td>
                                    <?= tanggalTampil($do['tgl_terima']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($do['no_invoice'] ?? '-') ?>
                                </td>

                                <td>
                                    <span class="so-badge <?= $badgeClass ?>">
                                        <?= $statusLogistik ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="so-empty">
                                Belum ada data pengiriman untuk Sales Order ini.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

    <!-- ================================== -->
    <!-- TOMBOL KEMBALI -->
    <!-- ================================== -->

    <a class="so-back"
       href="Detail-SO.php?tanggal_awal=<?= urlencode($startDate) ?>&tanggal_akhir=<?= urlencode($endDate) ?>">

        ← Kembali ke Monitoring Sales Order

    </a>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>