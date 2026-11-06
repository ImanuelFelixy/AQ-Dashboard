<?php
/*
|--------------------------------------------------------------------------
| DATA DASHBOARD PRODUKSI
|--------------------------------------------------------------------------
| File ini khusus mengambil dan menyiapkan data dashboard.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../koneksi.php';

// ==================================================
// FILTER TANGGAL DASHBOARD
// ==================================================

$startDate = $_GET['tanggal_awal'] ?? date('Y-m-01');
$endDate   = $_GET['tanggal_akhir'] ?? date('Y-m-d');

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    $startDate = date('Y-m-01');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $endDate = date('Y-m-d');
}

// Pastikan tanggal awal tidak melebihi tanggal akhir
if ($startDate > $endDate) {
    [$startDate, $endDate] = [$endDate, $startDate];
}

/*
|--------------------------------------------------------------------------
| 1. WORK ORDER BELUM DIOPTIMASI
|--------------------------------------------------------------------------
|
| WO tanpa reject:
| qty_optimasi < qty
|
| WO dengan reject:
| qty_optimasi < qty + total_reject
|
| Reject yang belum selesai dioptimasi ulang masuk kategori Reject.
|--------------------------------------------------------------------------
*/

$sqlWO = "
    SELECT
        CASE
            WHEN COALESCE(p.total_reject, 0) > 0
                 AND COALESCE(wo.qty_optimasi, 0)
                     < (
                         COALESCE(wo.qty, 0)
                         + COALESCE(p.total_reject, 0)
                     )
            THEN 'Reject'

            WHEN LOWER(TRIM(COALESCE(wo.prioritas_reguler, '')))
                 LIKE '%prioritas%'
            THEN 'Prioritas'

            ELSE 'Reguler'
        END AS kategori,

        COUNT(*) AS total

    FROM work_order wo

    LEFT JOIN (
        SELECT
            no_wo,
            SUM(COALESCE(qty_reject, 0)) AS total_reject
        FROM produksi
        GROUP BY no_wo
    ) p ON p.no_wo = wo.no_wo

    WHERE
        (
            (
                COALESCE(p.total_reject, 0) > 0
                AND COALESCE(wo.qty_optimasi, 0)
                    < (
                        COALESCE(wo.qty, 0)
                        + COALESCE(p.total_reject, 0)
                    )
            )

            OR

            (
                COALESCE(p.total_reject, 0) = 0
                AND COALESCE(wo.qty_optimasi, 0)
                    < COALESCE(wo.qty, 0)
            )
        )

        AND DATE(wo.tgl_trans_wo) BETWEEN :tanggal_awal AND :tanggal_akhir

    GROUP BY kategori
";

$stmtWO = $conn->prepare($sqlWO);
$stmtWO->execute([
    ':tanggal_awal' => $startDate,
    ':tanggal_akhir' => $endDate
]);

$hasilWO = $stmtWO->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| 2. SUSUN KATEGORI WO
|--------------------------------------------------------------------------
*/

$woKategori = [
    'Reguler' => 0,
    'Prioritas' => 0,
    'Reject' => 0
];

foreach ($hasilWO as $row) {
    $kategori = $row['kategori'];

    if (isset($woKategori[$kategori])) {
        $woKategori[$kategori] = (int) $row['total'];
    }
}

/*
|--------------------------------------------------------------------------
| 3. TOTAL WO BELUM DIOPTIMASI
|--------------------------------------------------------------------------
*/

$woTotal = array_sum($woKategori);

/*
|--------------------------------------------------------------------------
| 4. DATA DONUT CHART WO
|--------------------------------------------------------------------------
*/

$woData = [
    [
        'label' => 'Reguler',
        'value' => $woKategori['Reguler'],
        'color' => '#0875d1'
    ],
    [
        'label' => 'Prioritas',
        'value' => $woKategori['Prioritas'],
        'color' => '#f59e0b'
    ],
    [
        'label' => 'Reject',
        'value' => $woKategori['Reject'],
        'color' => '#dc2626'
    ]
];

// ==================================================
// PERMINTAAN DAN PENYERAHAN BAHAN
// ==================================================

$sqlPB = "
    SELECT
        pb.no_pb,
        pb.tgl_trans,
        pb.jumlah_pb,
        COALESCE(SUM(sb.keluar), 0) AS jumlah_keluar,
        GREATEST(
            pb.jumlah_pb - COALESCE(SUM(sb.keluar), 0),
            0
        ) AS sisa,
        CASE
            WHEN COALESCE(SUM(sb.keluar), 0) = 0
                THEN 'Belum Diserahkan'
            WHEN COALESCE(SUM(sb.keluar), 0) < pb.jumlah_pb
                THEN 'Parsial / Sebagian'
            ELSE 'Sudah Seluruhnya'
        END AS status_pb,
        GROUP_CONCAT(
            DISTINCT mb.nama_barang
            ORDER BY mb.nama_barang
            SEPARATOR ', '
        ) AS nama_barang
    FROM permintaan_bahan pb
    LEFT JOIN serah_barang sb
        ON sb.no_pb = pb.no_pb
    LEFT JOIN master_barang mb
        ON mb.id_barang = sb.id_barang
    WHERE DATE(pb.tgl_trans) BETWEEN :tanggal_awal AND :tanggal_akhir
    GROUP BY
        pb.no_pb,
        pb.tgl_trans,
        pb.jumlah_pb;
    ";

    $stmtPB = $conn->prepare($sqlPB);

    $stmtPB->execute([
        ':tanggal_awal' => $startDate,
        ':tanggal_akhir' => $endDate
]);

$hasilPB = $stmtPB->fetchAll(PDO::FETCH_ASSOC);


// ==================================================
// REKAP STATUS PB
// ==================================================

$pbKategori = [
    'Sudah Seluruhnya' => 0,
    'Parsial / Sebagian' => 0,
    'Belum Diserahkan' => 0
];

foreach ($hasilPB as $row) {

    $status = $row['status_pb'];

    if (isset($pbKategori[$status])) {
        $pbKategori[$status]++;
    }

}

$pbTotal = count($hasilPB);


// ==================================================
// DATA DONUT CHART PB
// ==================================================

$pbData = [
    [
        'label' => 'Sudah Seluruhnya',
        'value' => $pbKategori['Sudah Seluruhnya'],
        'color' => '#07845e'
    ],
    [
        'label' => 'Parsial / Sebagian',
        'value' => $pbKategori['Parsial / Sebagian'],
        'color' => '#0891b2'
    ],
    [
        'label' => 'Belum Diserahkan',
        'value' => $pbKategori['Belum Diserahkan'],
        'color' => '#dc2626'
    ]
];

/* =========================================================
   RASIO PENGGUNAAN BAHAN
========================================================= */

// 1. Menghitung luas bahan berdasarkan id_barang
// Menggunakan tanggal transaksi bahan_potong.tgl_trans

$sqlLuasBahan = "
    SELECT
        COALESCE(
            SUM(
                COALESCE(bp.total_keluar, 0) * COALESCE(mb.m2, 0)
            ),
            0
        ) AS total_luas_bahan
    FROM (
        SELECT
            id_barang,
            SUM(COALESCE(keluar, 0)) AS total_keluar
        FROM bahan_potong
        WHERE DATE(tgl_trans) BETWEEN :tanggal_awal AND :tanggal_akhir
        GROUP BY id_barang
    ) bp
    INNER JOIN master_barang mb
        ON mb.id_barang = bp.id_barang
    WHERE LOWER(mb.nama_barang) NOT LIKE '%kaca sisa%'
";

$stmtLuasBahan = $conn->prepare($sqlLuasBahan);

$stmtLuasBahan->execute([
    ':tanggal_awal' => $startDate,
    ':tanggal_akhir' => $endDate
]);

$luasBahan = (float) $stmtLuasBahan->fetchColumn();


// 2. Menghitung luas potong dari mesin CUT
// Menggunakan tanggal produksi.tgl_update

$sqlLuasPotong = "
    SELECT
        COALESCE(SUM(m2_ok), 0) AS total_luas_potong
    FROM produksi
    WHERE UPPER(TRIM(nama_mesin)) = 'CUT'
      AND DATE(tgl_update) BETWEEN :tanggal_awal AND :tanggal_akhir
";

$stmtLuasPotong = $conn->prepare($sqlLuasPotong);

$stmtLuasPotong->execute([
    ':tanggal_awal' => $startDate,
    ':tanggal_akhir' => $endDate
]);

$luasPotong = (float) $stmtLuasPotong->fetchColumn();


// 3. Menghitung Offcut / Trim
$offcut = $luasBahan - $luasPotong;


// 4. Menghitung Yield
$yield = ($luasBahan > 0)
    ? ($luasPotong / $luasBahan) * 100
    : 0;


// 5. Menghitung Rasio Penggunaan Bahan
$rasio = ($luasPotong > 0)
    ? $luasBahan / $luasPotong
    : 0;


// 6. Menentukan status efisiensi
$statusRasio = ($luasPotong > 0 && $rasio <= 1.20)
    ? 'Efisien'
    : 'Tidak Efisien';


/*
|--------------------------------------------------------------------------
| 7. GRAFIK REJECT & PENCAPAIAN PER MESIN
|--------------------------------------------------------------------------
| Sumber : tabel produksi (tgl_update = tanggal produksi sebenarnya)
|          dan kapasitas-mesin.php (kapasitas diisi manual, DB tidak diubah)
|
| Reject %      = reject / (OK + reject) x 100
| Pencapaian %  = m2 OK / (kapasitas harian x hari kerja) x 100
|--------------------------------------------------------------------------
*/

// Mesin yang tampil di grafik: nama_mesin di database => label di dashboard
const MESIN_GRAFIK = [
    'CUT'  => 'POTONG',
    'SE'   => 'SE',
    'DE'   => 'DE',
    'JET'  => 'WATERJET',
    'TEMP' => 'TEMP',
];

// Dasar hitung reject: 'QTY' (jumlah pcs) atau 'M2' (luas kaca)
const REJECT_DASAR = 'QTY';

// Jumlah hari kerja (Senin-Sabtu) pada periode
function hitungHariKerja(string $awal, string $akhir): int
{
    $n = 0;
    $d = new DateTime($awal);
    $end = new DateTime($akhir);
    while ($d <= $end) {
        if ($d->format('N') !== '7') {   // 7 = Minggu
            $n++;
        }
        $d->modify('+1 day');
    }
    return $n;
}

// Warna batang reject: < 1% hijau, 1 s/d < 2% oranye, >= 2% merah
function warnaRejectMesin(float $persen): string
{
    if ($persen < 1) return '#07845e';
    if ($persen < 2) return '#e58a00';
    return '#bd2020';
}

function rataRataGrafik(array $nilai): float
{
    $v = array_filter($nilai, fn($x) => $x !== null);
    return $v ? round(array_sum($v) / count($v), 1) : 0.0;
}

$mesinKode  = array_keys(MESIN_GRAFIK);
$mesinLabel = array_values(MESIN_GRAFIK);
$hariKerja  = hitungHariKerja($startDate, $endDate);

// --- Data produksi per mesin dalam periode ---
if (REJECT_DASAR === 'M2') {
    $kolomOk = 'm2_ok';
    $kolomRj = 'm2_rjk';
} else {
    $kolomOk = 'qty';
    $kolomRj = 'qty_reject';
}

$placeholder = implode(',', array_fill(0, count($mesinKode), '?'));

$stmtMesin = $conn->prepare("
    SELECT
        UPPER(TRIM(nama_mesin))        AS mesin,
        SUM(COALESCE($kolomOk, 0))     AS total_ok,
        SUM(COALESCE($kolomRj, 0))     AS total_reject,
        SUM(COALESCE(m2_ok, 0))        AS total_m2
    FROM produksi
    WHERE tgl_update BETWEEN ? AND ?
      AND UPPER(TRIM(nama_mesin)) IN ($placeholder)
    GROUP BY UPPER(TRIM(nama_mesin))
");
$stmtMesin->execute(array_merge([$startDate, $endDate], $mesinKode));
$produksiMesin = [];
foreach ($stmtMesin->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $produksiMesin[$r['mesin']] = $r;
}

// --- Reject per mesin ---
$rejectNilai = [];   // null = mesin tidak ada aktivitas pada periode
foreach ($mesinKode as $kode) {
    $r = $produksiMesin[$kode] ?? null;
    $total = $r ? (float)$r['total_ok'] + (float)$r['total_reject'] : 0;
    $rejectNilai[] = $total > 0 ? round((float)$r['total_reject'] * 100 / $total, 2) : null;
}
$rejectValues = array_map(fn($v) => $v ?? 0.0, $rejectNilai);
$rejectColors = array_map('warnaRejectMesin', $rejectValues);
$rejectAvg    = rataRataGrafik($rejectNilai);
$rejectMax    = max(3, ceil(max($rejectValues ?: [0]) + 0.5));

// --- Pencapaian per mesin (kapasitas dari file kapasitas-mesin.php) ---
$kapasitas = is_file(__DIR__ . '/kapasitas-mesin.php')
    ? (require __DIR__ . '/kapasitas-mesin.php')
    : [];
$kapasitasPerkiraan = false;
$pencapaianSiap = false;

$pencapaianNilai = [];
foreach ($mesinKode as $kode) {
    $kap = (float)($kapasitas[$kode]['kapasitas'] ?? 0);
    if ($kap > 0 && $hariKerja > 0) {
        $pencapaianSiap = true;
        if (($kapasitas[$kode]['sumber'] ?? '') !== 'RESMI') {
            $kapasitasPerkiraan = true;
        }
        $m2 = (float)($produksiMesin[$kode]['total_m2'] ?? 0);
        $pencapaianNilai[] = round($m2 * 100 / ($kap * $hariKerja), 1);
    } else {
        $pencapaianNilai[] = null;     // kapasitas belum diisi
    }
}
$pencapaianValues = array_map(fn($v) => $v ?? 0.0, $pencapaianNilai);
$pencapaianAvg    = rataRataGrafik($pencapaianNilai);
$pencapaianMax    = max(100, ceil(max($pencapaianValues ?: [0]) / 10) * 10);
?>