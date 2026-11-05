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

/*
|--------------------------------------------------------------------------
| 5. DATA PB DAN PENYERAHAN BAHAN (DUMMY)
|--------------------------------------------------------------------------
| Sementara tetap menggunakan data dummy.
| Nanti akan dihubungkan dengan database.
|--------------------------------------------------------------------------
*/

$pbTotal = 120;

$pbData = [
    [
        'label' => 'Sudah Seluruhnya',
        'value' => 80,
        'color' => '#07845e'
    ],
    [
        'label' => 'Parsial / Sebagian',
        'value' => 25,
        'color' => '#0891b2'
    ],
    [
        'label' => 'Belum Diserahkan',
        'value' => 15,
        'color' => '#dc2626'
    ]
];
?>