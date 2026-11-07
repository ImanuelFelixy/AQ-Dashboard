<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/koneksi.php';

$pageTitle = 'Riwayat Aktivitas';
$activePage = 'aktivitas';
$basePath = '';

/* =====================================
   FILTER DAN PAGINATION
===================================== */

$tanggalAwal = $_GET['tanggal_awal'] ?? '';
$tanggalAkhir = $_GET['tanggal_akhir'] ?? '';
$keyword = trim($_GET['keyword'] ?? '');

$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if ($tanggalAwal !== '') {
    $where[] = "DATE(created_at) >= :tanggal_awal";
    $params['tanggal_awal'] = $tanggalAwal;
}

if ($tanggalAkhir !== '') {
    $where[] = "DATE(created_at) <= :tanggal_akhir";
    $params['tanggal_akhir'] = $tanggalAkhir;
}

if ($keyword !== '') {
    $where[] = "(
        nama_user LIKE :keyword1
        OR aktivitas LIKE :keyword2
        OR modul LIKE :keyword3
        OR keterangan LIKE :keyword4
    )";

    $params['keyword1'] = "%$keyword%";
    $params['keyword2'] = "%$keyword%";
    $params['keyword3'] = "%$keyword%";
    $params['keyword4'] = "%$keyword%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* =====================================
   TOTAL AKTIVITAS
===================================== */

$stmt = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(DATE(created_at) = CURDATE()) AS hari_ini
    FROM user_activity_log
");

$summary = $stmt->fetch();

$totalAktivitas = (int)($summary['total'] ?? 0);
$aktivitasHariIni = (int)($summary['hari_ini'] ?? 0);

/* =====================================
   HITUNG DATA SESUAI FILTER
===================================== */

$sqlCount = "
    SELECT COUNT(*)
    FROM user_activity_log
    $whereSQL
";

$stmtCount = $conn->prepare($sqlCount);
$stmtCount->execute($params);

$totalData = (int)$stmtCount->fetchColumn();
$totalPage = max(1, (int)ceil($totalData / $limit));

/* =====================================
   AMBIL DATA AKTIVITAS
===================================== */

$sql = "
    SELECT *
    FROM user_activity_log
    $whereSQL
    ORDER BY created_at DESC, id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$activities = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars((string)($value ?? '-'), ENT_QUOTES, 'UTF-8');
}

function badgeAktivitas($aktivitas) {
    $aktivitas = strtolower($aktivitas);

    if (str_contains($aktivitas, 'login')) {
        return 'badge-login';
    }

    if (str_contains($aktivitas, 'logout')) {
        return 'badge-logout';
    }

    if (str_contains($aktivitas, 'export')) {
        return 'badge-export';
    }

    if (str_contains($aktivitas, 'print')) {
        return 'badge-print';
    }

    return 'badge-view';
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
.activity-page {
    min-height: calc(100vh - 80px);
    padding: 32px;
    background:
        radial-gradient(ellipse at 90% 0%, rgba(46,130,220,.10), transparent 35%),
        linear-gradient(135deg, #edf4fc, #f8faff);
}

.activity-card {
    background: #fff;
    border: 1px solid #e2eaf4;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 12px 35px rgba(25,55,95,.07);
    position: relative;
    overflow: hidden;
}

.activity-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg,#0753a5,#1689f5,#75bdff);
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 28px;
}

.activity-header h2 {
    color: #17365d;
    font-size: 26px;
    font-weight: 800;
    margin: 0 0 7px;
}

.activity-header p {
    color: #8492a6;
    font-size: 13px;
    margin: 0;
}

.activity-back {
    background: #edf5ff;
    color: #0866c6;
    padding: 10px 16px;
    border-radius: 9px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
}

.activity-summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.activity-summary-card {
    display: flex;
    align-items: center;
    gap: 17px;
    padding: 22px;
    border: 1px solid #dce9f8;
    border-radius: 15px;
    background: linear-gradient(130deg,#f5faff,#eaf4ff);
}

.activity-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 13px;
    background: #d9ebff;
    color: #0866c6;
    font-size: 22px;
}

.activity-summary-card span {
    color: #7b8da5;
    font-size: 12px;
}

.activity-summary-card strong {
    display: block;
    color: #17365d;
    font-size: 25px;
    font-weight: 800;
}

.activity-filter {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 18px;
    margin-bottom: 22px;
    background: #f5f9ff;
    border: 1px solid #e3edf9;
    border-radius: 13px;
}

.activity-filter input {
    height: 43px;
    padding: 0 12px;
    border: 1px solid #dce5f0;
    border-radius: 8px;
    font-size: 12px;
    color: #334155;
}

.activity-filter input[type="text"] {
    min-width: 220px;
    flex: 1;
}

.activity-filter button {
    height: 43px;
    padding: 0 20px;
    border: none;
    border-radius: 8px;
    background: #0866c6;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
}

.activity-filter a {
    padding: 12px 15px;
    border-radius: 8px;
    background: #e8edf4;
    color: #52647a;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
}

.activity-total {
    margin-bottom: 15px;
    color: #71839b;
    font-size: 12px;
}

.activity-total strong {
    color: #0866c6;
}

.activity-table-wrapper {
    overflow-x: auto;
    border: 1px solid #e3eaf3;
    border-radius: 13px;
}

.activity-table {
    width: 100%;
    min-width: 950px;
    border-collapse: separate;
    border-spacing: 0;
}

.activity-table th {
    padding: 16px;
    background: #eaf2fc;
    color: #29496e;
    font-size: 11px;
    font-weight: 800;
    text-align: left;
    white-space: nowrap;
}

.activity-table td {
    padding: 16px;
    color: #52647a;
    font-size: 12px;
    border-bottom: 1px solid #edf1f7;
    vertical-align: middle;
}

.activity-table tbody tr:nth-child(even) {
    background: #f6f9fe;
}

.activity-table tbody tr:hover {
    background: #eaf4ff;
}

.activity-table tbody tr:last-child td {
    border-bottom: none;
}

.activity-badge {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
}

.badge-login {
    background: #dcfce7;
    color: #15803d;
}

.badge-logout {
    background: #fee2e2;
    color: #b91c1c;
}

.badge-export {
    background: #ede9fe;
    color: #6d28d9;
}

.badge-print {
    background: #ffedd5;
    color: #c2410c;
}

.badge-view {
    background: #e0f2fe;
    color: #0369a1;
}

.activity-empty {
    padding: 45px !important;
    text-align: center;
    color: #94a3b8 !important;
}

.activity-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 22px;
}

.activity-pagination a,
.activity-pagination span {
    display: inline-block;
    padding: 9px 14px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
}

.activity-pagination a {
    background: #0866c6;
    color: #fff;
}

.activity-pagination .current {
    background: #eaf3ff;
    color: #075caf;
}

@media(max-width:768px) {
    .activity-page {
        padding: 15px;
    }

    .activity-card {
        padding: 19px;
    }

    .activity-summary {
        grid-template-columns: 1fr;
    }

    .activity-filter {
        align-items: stretch;
        flex-direction: column;
    }

    .activity-filter input,
    .activity-filter button,
    .activity-filter a {
        width: 100%;
    }
}
</style>

<main class="activity-page">
    <section class="activity-card">

        <div class="activity-header">
            <div>
                <h2><i class="bi bi-clock-history"></i> Riwayat Aktivitas</h2>
                <p>Monitoring aktivitas penggunaan Dashboard Aquamate.</p>
            </div>

            <a href="index.php" class="activity-back">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="activity-summary">
            <div class="activity-summary-card">
                <div class="activity-icon">
                    <i class="bi bi-activity"></i>
                </div>
                <div>
                    <span>Total Aktivitas</span>
                    <strong><?= number_format($totalAktivitas) ?></strong>
                </div>
            </div>

            <div class="activity-summary-card">
                <div class="activity-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <span>Aktivitas Hari Ini</span>
                    <strong><?= number_format($aktivitasHariIni) ?></strong>
                </div>
            </div>
        </div>

        <form method="GET" class="activity-filter">
            <input type="date" name="tanggal_awal"
                   value="<?= e($tanggalAwal) ?>">

            <input type="date" name="tanggal_akhir"
                   value="<?= e($tanggalAkhir) ?>">

            <input type="text" name="keyword"
                   placeholder="Cari aktivitas, modul, atau keterangan..."
                   value="<?= e($keyword) ?>">

            <button type="submit">
                <i class="bi bi-search"></i> Filter
            </button>

            <a href="Riwayat-Aktivitas.php">
                Reset
            </a>
        </form>

        <div class="activity-total">
            Menampilkan <strong><?= count($activities) ?></strong>
            dari <strong><?= number_format($totalData) ?></strong> aktivitas.
        </div>

        <div class="activity-table-wrapper">
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Waktu</th>
                        <th>Nama User</th>
                        <th>Aktivitas</th>
                        <th>Modul</th>
                        <th>Keterangan</th>
                        <th>IP Address</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($activities)): ?>
                        <tr>
                            <td colspan="7" class="activity-empty">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Belum ada riwayat aktivitas.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activities as $index => $row): ?>
                            <tr>
                                <td><?= $offset + $index + 1 ?></td>
                                <td>
                                    <?= date('d/m/Y H:i:s', strtotime($row['created_at'])) ?>
                                </td>
                                <td><strong><?= e($row['nama_user']) ?></strong></td>
                                <td>
                                    <span class="activity-badge <?= badgeAktivitas($row['aktivitas']) ?>">
                                        <?= e($row['aktivitas']) ?>
                                    </span>
                                </td>
                                <td><?= e($row['modul']) ?></td>
                                <td><?= e($row['keterangan']) ?></td>
                                <td><?= e($row['ip_address']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="activity-pagination">
            <span>
                Halaman <?= $page ?> dari <?= $totalPage ?>
            </span>

            <div>
                <?php
                $queryParams = $_GET;
                ?>

                <?php if ($page > 1): ?>
                    <?php $queryParams['page'] = $page - 1; ?>
                    <a href="?<?= e(http_build_query($queryParams)) ?>">
                        <i class="bi bi-chevron-left"></i> Sebelumnya
                    </a>
                <?php endif; ?>

                <span class="current"><?= $page ?></span>

                <?php if ($page < $totalPage): ?>
                    <?php $queryParams['page'] = $page + 1; ?>
                    <a href="?<?= e(http_build_query($queryParams)) ?>">
                        Berikutnya <i class="bi bi-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>