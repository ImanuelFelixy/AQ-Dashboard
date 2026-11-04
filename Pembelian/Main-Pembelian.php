<?php
$pageTitle = 'Dashboard Pembelian';
$activePage = 'pembelian';
$basePath = '../';

require_once __DIR__ . '/../includes/header.php';

// ==========================================
// DATA SEMENTARA (DUMMY)
// Nantinya akan diganti dengan data MySQL
// ==========================================

$startDate = $_GET['tanggal_awal'] ?? '2026-09-01';
$endDate   = $_GET['tanggal_akhir'] ?? '2026-09-30';

$currentUser = 'NAYLA';
$userIp = $_SERVER['REMOTE_ADDR'] ?? '192.168.1.78';
if ($userIp === '::1') {
    $userIp = '127.0.0.1';
}

// 1. Purchase Order
$totalPO = 25;
$totalPONominal = 9999999;

// 2. Data Dummy Outstanding PO (5 Data)
$outstandingList = [
    [
        'no_po'         => 'PO-2026/09/001',
        'tgl_trans'     => '01/09/2026',
        'tgl_kirim'     => '05/09/2026',
        'nama_supplier' => 'PT Asahimas Flat Glass Tbk',
        'status'        => 'Dikirim Sebagian',
        'keterangan'    => 'Pengiriman tahap 1 via truk wingbox, sisa kaca tempered menunggu jadwal tempering batch 2.',
        'jumlah'        => 1150000,
        'items'         => [
            [
                'nama_barang' => 'Kaca Tempered 10mm Clear',
                'satuan'      => 'Lembar',
                'qty'         => 12,
                'qty_kirim'   => 8,
                'sisa_qty'    => 4,
                'subtotal'    => 540000,
                'keterangan'  => 'Ukuran custom 1200 x 2400 mm'
            ],
            [
                'nama_barang' => 'Kaca Float Bening 5mm Standard',
                'satuan'      => 'Lembar',
                'qty'         => 25,
                'qty_kirim'   => 25,
                'sisa_qty'    => 0,
                'subtotal'    => 380000,
                'keterangan'  => 'Lembaran standar gudang utama'
            ],
            [
                'nama_barang' => 'Kaca Tinted Panasap Dark Grey 6mm',
                'satuan'      => 'Lembar',
                'qty'         => 10,
                'qty_kirim'   => 5,
                'sisa_qty'    => 5,
                'subtotal'    => 230000,
                'keterangan'  => 'Batch khusus warna dark grey'
            ]
        ]
    ],
    [
        'no_po'         => 'PO-2026/09/002',
        'tgl_trans'     => '02/09/2026',
        'tgl_kirim'     => '06/09/2026',
        'nama_supplier' => 'PT Dowsil Silicon Prima',
        'status'        => 'Belum Dikirim',
        'keterangan'    => 'Menunggu konfirmasi jadwal dispatch ekspedisi dari pabrik Cikarang.',
        'jumlah'        => 850000,
        'items'         => [
            [
                'nama_barang' => 'Lem Sealant Silikon Transparan 300ml',
                'satuan'      => 'Tabung',
                'qty'         => 50,
                'qty_kirim'   => 0,
                'sisa_qty'    => 50,
                'subtotal'    => 450000,
                'keterangan'  => 'Grade struktural kedap air'
            ],
            [
                'nama_barang' => 'Sealant Acetic Cure Black 300ml',
                'satuan'      => 'Tabung',
                'qty'         => 30,
                'qty_kirim'   => 0,
                'sisa_qty'    => 30,
                'subtotal'    => 270000,
                'keterangan'  => 'Formula cepat kering hitam'
            ],
            [
                'nama_barang' => 'Nozzle Applicator Lem Kaca',
                'satuan'      => 'Pack',
                'qty'         => 10,
                'qty_kirim'   => 0,
                'sisa_qty'    => 10,
                'subtotal'    => 130000,
                'keterangan'  => '1 pack isi 20 unit corong'
            ]
        ]
    ],
    [
        'no_po'         => 'PO-2026/09/003',
        'tgl_trans'     => '03/09/2026',
        'tgl_kirim'     => '08/09/2026',
        'nama_supplier' => 'CV Aluminium Prima Jaya',
        'status'        => 'Dikirim Sebagian',
        'keterangan'    => 'Kusen U-Channel sudah sampai di workshop, karet gasket masih proses packing.',
        'jumlah'        => 920000,
        'items'         => [
            [
                'nama_barang' => 'Profil Aluminium U-Channel 12mm',
                'satuan'      => 'Batang',
                'qty'         => 40,
                'qty_kirim'   => 25,
                'sisa_qty'    => 15,
                'subtotal'    => 620000,
                'keterangan'  => 'Panjang 6 meter silver anodize'
            ],
            [
                'nama_barang' => 'Karet Gasket EPDM Kaca 10-12mm',
                'satuan'      => 'Roll',
                'qty'         => 15,
                'qty_kirim'   => 0,
                'sisa_qty'    => 15,
                'subtotal'    => 300000,
                'keterangan'  => 'Roll 50 meter hitam elastis'
            ]
        ]
    ],
    [
        'no_po'         => 'PO-2026/09/004',
        'tgl_trans'     => '04/09/2026',
        'tgl_kirim'     => '10/09/2026',
        'nama_supplier' => 'PT Sealant Mandiri Nusantara',
        'status'        => 'Belum Dikirim',
        'keterangan'    => 'PO disetujui, sedang menunggu penerbitan Surat Jalan resmi dan invoice proforma.',
        'jumlah'        => 675000,
        'items'         => [
            [
                'nama_barang' => 'Primer Kaca Kerapatan Tinggi 250ml',
                'satuan'      => 'Kaleng',
                'qty'         => 8,
                'qty_kirim'   => 0,
                'sisa_qty'    => 8,
                'subtotal'    => 360000,
                'keterangan'  => 'Cairan perekat kaca ke rangka'
            ],
            [
                'nama_barang' => 'Tape Spacer Struktural 6mm x 6mm',
                'satuan'      => 'Roll',
                'qty'         => 12,
                'qty_kirim'   => 0,
                'sisa_qty'    => 12,
                'subtotal'    => 315000,
                'keterangan'  => 'Double tape foam isolasi'
            ]
        ]
    ],
    [
        'no_po'         => 'PO-2026/09/005',
        'tgl_trans'     => '05/09/2026',
        'tgl_kirim'     => '11/09/2026',
        'nama_supplier' => 'PT Mulia Glasswork Sentosa',
        'status'        => 'Dikirim Sebagian',
        'keterangan'    => 'Kaca cermin tiba dengan kondisi aman tanpa retak, kaca laminated dalam proses kirim.',
        'jumlah'        => 780000,
        'items'         => [
            [
                'nama_barang' => 'Kaca Cermin Bevel Tebal 5mm',
                'satuan'      => 'Lembar',
                'qty'         => 20,
                'qty_kirim'   => 20,
                'sisa_qty'    => 0,
                'subtotal'    => 460000,
                'keterangan'  => 'Bevel 2cm keliling tepian'
            ],
            [
                'nama_barang' => 'Kaca Laminated Safety 5mm + 5mm',
                'satuan'      => 'Lembar',
                'qty'         => 8,
                'qty_kirim'   => 3,
                'sisa_qty'    => 5,
                'subtotal'    => 320000,
                'keterangan'  => 'Interlayer PVB 0.76mm clear'
            ]
        ]
    ]
];

$outstandingPO = count($outstandingList);
$outstandingPONominal = array_sum(array_column($outstandingList, 'jumlah'));

// 3. Data Dummy Pembelian Belum Dilunasi (10 Data)
$unpaidList = [
    [
        'supplier'      => 'PT Asahimas Flat Glass Tbk',
        'no_nb'         => 'NB-2026/09/001',
        'no_pelunasan'  => 'PL-2026/09/001',
        'tgl_pembelian' => '01/09/2026',
        'tgl_tempo'     => '05/09/2026',
        'nominal'       => 1850000,
        'kurang_bayar'  => 850000,
    ],
    [
        'supplier'      => 'PT Dowsil Silicon Prima',
        'no_nb'         => 'NB-2026/09/002',
        'no_pelunasan'  => 'PL-2026/09/002',
        'tgl_pembelian' => '02/09/2026',
        'tgl_tempo'     => '06/09/2026',
        'nominal'       => 1200000,
        'kurang_bayar'  => 600000,
    ],
    [
        'supplier'      => 'CV Sinar Kaca Abadi',
        'no_nb'         => 'NB-2026/09/003',
        'no_pelunasan'  => 'PL-2026/09/003',
        'tgl_pembelian' => '03/09/2026',
        'tgl_tempo'     => '07/09/2026',
        'nominal'       => 950000,
        'kurang_bayar'  => 450000,
    ],
    [
        'supplier'      => 'PT Mulia Glasswork Sentosa',
        'no_nb'         => 'NB-2026/09/004',
        'no_pelunasan'  => 'PL-2026/09/004',
        'tgl_pembelian' => '04/09/2026',
        'tgl_tempo'     => '08/09/2026',
        'nominal'       => 1450000,
        'kurang_bayar'  => 725000,
    ],
    [
        'supplier'      => 'PT Sealant Mandiri Nusantara',
        'no_nb'         => 'NB-2026/09/005',
        'no_pelunasan'  => 'PL-2026/09/005',
        'tgl_pembelian' => '05/09/2026',
        'tgl_tempo'     => '09/09/2026',
        'nominal'       => 880000,
        'kurang_bayar'  => 380000,
        'status'        => 'Tempo Berjalan'
    ],
    [
        'supplier'      => 'CV Aluminium Prima Jaya',
        'no_nb'         => 'NB-2026/09/006',
        'no_pelunasan'  => 'PL-2026/09/006',
        'tgl_pembelian' => '06/09/2026',
        'tgl_tempo'     => '10/09/2026',
        'nominal'       => 670000,
        'kurang_bayar'  => 370000,
    ],
    [
        'supplier'      => 'PT Gasket Megah Teknik',
        'no_nb'         => 'NB-2026/09/007',
        'no_pelunasan'  => 'PL-2026/09/007',
        'tgl_pembelian' => '07/09/2026',
        'tgl_tempo'     => '11/09/2026',
        'nominal'       => 520000,
        'kurang_bayar'  => 220000,
    ],
    [
        'supplier'      => 'CV Hardware Kaca Sukses',
        'no_nb'         => 'NB-2026/09/008',
        'no_pelunasan'  => 'PL-2026/09/008',
        'tgl_pembelian' => '08/09/2026',
        'tgl_tempo'     => '12/09/2026',
        'nominal'       => 740000,
        'kurang_bayar'  => 440000,
    ],
    [
        'supplier'      => 'PT Mulia Glasswork Sentosa',
        'no_nb'         => 'NB-2026/09/009',
        'no_pelunasan'  => 'PL-2026/09/009',
        'tgl_pembelian' => '09/09/2026',
        'tgl_tempo'     => '13/09/2026',
        'nominal'       => 1150000,
        'kurang_bayar'  => 550000,
    ],
    [
        'supplier'      => 'CV Aksesoris Kaca Nusantara',
        'no_nb'         => 'NB-2026/09/010',
        'no_pelunasan'  => 'PL-2026/09/010',
        'tgl_pembelian' => '10/09/2026',
        'tgl_tempo'     => '14/09/2026',
        'nominal'       => 800000,
        'kurang_bayar'  => 400000,
    ]
];

$unpaidNominal = array_sum(array_column($unpaidList, 'kurang_bayar'));

// 4. Pengajuan PO
$submissionCount = 35;

// 5. Diperlukan Pembelian Bahan (PPB)
$urgentPurchases = [
    [
        'name' => 'Lem Sealant Silikon Transparan 300ml',
        'code' => 'PPB-26090076',
        'qty'  => '6 tabung',
        'status' => 'critical',
        'badge' => 'Kritis'
    ],
    [
        'name' => 'Kaca Tempered Tebal 10mm (Custom Size)',
        'code' => 'PPB-26098765',
        'qty'  => '4 lembar',
        'status' => 'warning',
        'badge' => 'Menipis'
    ],
    [
        'name' => 'Lem Sealant Silikon Transparan 300ml',
        'code' => 'PPB-26090076',
        'qty'  => '6 tabung',
        'status' => 'critical',
        'badge' => 'Kritis'
    ],
    [
        'name' => 'Kaca Tempered Tebal 10mm (Custom Size)',
        'code' => 'PPB-26098765',
        'qty'  => '4 lembar',
        'status' => 'warning',
        'badge' => 'Menipis'
    ],
];

// 6. Pembelian per Periode (Data Harian 1-31)
$periodLabels = range(1, 31);
$periodData = [
    60, 48, 68, 42, 75, 14, 73, 15, 48, 54,
    50, 52, 58, 76, 45, 42, 38, 24, 70, 62,
    69, 48, 20, 22, 68, 50, 48, 52, 58, 55, 62
];

// 7. Pembelian Barang per Jenis
$categoryData = [
    [
        'label' => 'Bahan Baku',
        'percent' => 45,
        'po_count' => 11,
        'nominal' => 62400000,
        'subtext' => 'Barang Bahan Baku Kaca',
        'color' => '#0866c6'
    ],
    [
        'label' => 'Non-Bahan Baku',
        'percent' => 55,
        'po_count' => 14,
        'nominal' => 76250000,
        'subtext' => 'Barang Bahan Pembantu',
        'color' => '#60a5fa'
    ]
];
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* ==========================================
   GENERAL DASHBOARD STYLES
========================================== */

.purchase-dashboard {
    width: 100%;
    padding: 24px 28px;
    background: #f5f7fc;
    min-height: calc(100vh - 100px);
}

.purchase-dashboard * {
    box-sizing: border-box;
}

/* ==========================================
   HEADER DASHBOARD
========================================== */

.purchase-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
}

.purchase-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.purchase-title .heading-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #e8f1ff;
    color: #0866c6;
    font-size: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.purchase-title h1 {
    font-size: 22px;
    font-weight: 800;
    color: #25364d;
    margin: 0;
    line-height: 1.25;
}

.purchase-user-tag {
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
}

.purchase-user-tag span {
    color: #0866c6;
    font-weight: 800;
}

.purchase-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.purchase-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 14px;
    border-radius: 8px;
    border: 1px solid #dce5f1;
    background: #fff;
    color: #46566d;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: all .2s ease;
    text-decoration: none;
}

.purchase-btn:hover {
    background: #edf4ff;
    color: #0866c6;
    border-color: #c7dbfb;
}

.purchase-btn.primary {
    background: #0866c6;
    color: #fff;
    border-color: #0866c6;
}

.purchase-btn.primary:hover {
    background: #0754a5;
    border-color: #0754a5;
    color: #fff;
}

/* ==========================================
   FILTER PERIODE
========================================== */

.purchase-filter {
    width: 100%;
    background: #eaf2ff;
    border: 1px solid #dce9fb;
    border-radius: 13px;
    padding: 18px 24px;
    margin-bottom: 20px;
}

.purchase-filter-title {
    font-size: 12px;
    font-weight: 800;
    color: #53647c;
    margin-bottom: 14px;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 8px;
}

.purchase-filter-title i {
    color: #0866c6;
    font-size: 14px;
}

.purchase-filter-form {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
}

.purchase-filter-form input[type="date"] {
    height: 50px;
    flex: 1;
    min-width: 0;
    padding: 0 16px;
    background: #fff;
    border: 1px solid #dce4f0;
    border-radius: 9px;
    color: #42536b;
    font-size: 13px;
    font-weight: 600;
    transition: border-color .2s;
}

.purchase-filter-form input[type="date"]:focus {
    outline: none;
    border-color: #0866c6;
    box-shadow: 0 0 0 3px rgba(8, 102, 198, .12);
}

.purchase-filter-form .separator {
    font-size: 13px;
    color: #738198;
    font-weight: 600;
    white-space: nowrap;
}

.purchase-filter-submit {
    height: 50px;
    padding: 0 28px;
    border: none;
    border-radius: 9px;
    background: #0866c6;
    color: white;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
    transition: background-color .2s;
}

.purchase-filter-submit:hover {
    background: #0754a5;
}

.purchase-today {
    height: 50px;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 0 18px;
    background: white;
    border: 1px solid #dce4f0;
    border-radius: 9px;
    color: #53647c;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
    margin: 0;
    transition: background-color .2s;
}

.purchase-today:hover {
    background: #f8fafc;
}

.purchase-today input[type="checkbox"] {
    accent-color: #0866c6;
    cursor: pointer;
    width: 15px;
    height: 15px;
}

/* ==========================================
   DASHBOARD TOP SECTION (METRICS & PPB)
========================================== */

.purchase-top-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1.35fr;
    gap: 16px;
    margin-bottom: 20px;
}

.purchase-left-stack {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-column: span 2;
    gap: 16px;
}

/* Base Card */
.p-card {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 2px 10px rgba(32, 66, 110, .03);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 158px;
    position: relative;
    transition: transform .2s ease, box-shadow .2s ease;
}

.p-card:hover {
    box-shadow: 0 6px 18px rgba(32, 66, 110, .06);
}

/* Card Borders Accent */
.p-card.card-default {
    border-color: #dce4f0;
}

.p-card.card-blue {
    border-color: #0875d1;
}

.p-card.card-red {
    border-color: #ef4444;
}

.p-card.card-green {
    border-color: #10b981;
}

.p-card.card-orange {
    border-color: #f7a072;
    background: #fffdfb;
}

/* Card Header */
.p-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
}

.p-card-title {
    font-size: 16px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
    line-height: 1.25;
}

.p-circle-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    font-size: 14px;
    flex-shrink: 0;
    cursor: pointer;
    transition: transform .2s ease, opacity .2s ease;
    text-decoration: none;
}

.p-circle-btn:hover {
    transform: scale(1.08);
}

.p-circle-btn.btn-gray {
    background: #e2e8f0;
    color: #334155;
}

.p-circle-btn.btn-blue {
    background: #dbeafe;
    color: #0875d1;
}

.p-circle-btn.btn-red {
    background: #fee2e2;
    color: #ef4444;
}

.p-circle-btn.btn-green {
    background: #d1fae5;
    color: #10b981;
}

.p-circle-btn.btn-orange {
    background: #ffedd5;
    color: #ea580c;
}

/* Card Body Metric */
.p-card-metric {
    margin: 12px 0 8px;
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.p-metric-number {
    font-size: 32px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
}

.p-metric-unit {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
}

.p-metric-nominal {
    font-size: 23px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.3px;
}

/* Card Footer */
.p-card-footer {
    font-size: 11.5px;
    color: #64748b;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
}

.p-card-footer strong {
    color: #1e293b;
    font-weight: 700;
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 11.5px;
    font-weight: 700;
}

.status-indicator.danger {
    color: #dc2626;
}

.status-indicator.success {
    color: #059669;
}

.status-indicator .indicator-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
}

.status-indicator.danger .indicator-dot {
    background: #dc2626;
    box-shadow: 0 0 0 3px #fee2e2;
}

.status-indicator.success .indicator-dot {
    background: #059669;
    box-shadow: 0 0 0 3px #d1fae5;
}

/* ==========================================
   PPB CARD (DI PERLUKAN PEMBELIAN BAHAN)
========================================== */

.p-ppb-card {
    background: #fffdfb;
    border: 2px solid #f7a072;
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 2px 10px rgba(247, 160, 114, .06);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.p-ppb-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 14px;
}

.p-ppb-header h2 {
    font-size: 18px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
    line-height: 1.25;
}

.p-ppb-alert-strip {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    padding: 4px 0;
}

.p-ppb-alert-label {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #d97706;
    font-size: 12px;
    font-weight: 700;
}

.p-ppb-link {
    color: #2563eb;
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: gap .2s;
}

.p-ppb-link:hover {
    gap: 7px;
    color: #1d4ed8;
}

.p-ppb-list {
    display: flex;
    flex-direction: column;
    gap: 9px;
    flex: 1;
}

.p-ppb-item {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 11px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .02);
    transition: border-color .2s;
}

.p-ppb-item:hover {
    border-color: #fed7aa;
}

.p-ppb-item-left {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    min-width: 0;
}

.p-ppb-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-top: 5px;
    flex-shrink: 0;
}

.p-ppb-dot.critical {
    background: #ef4444;
}

.p-ppb-dot.warning {
    background: #f59e0b;
}

.p-ppb-item-content {
    min-width: 0;
}

.p-ppb-item-title {
    font-size: 12px;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.p-ppb-item-sub {
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
}

.p-ppb-item-badge {
    padding: 4px 10px;
    border-radius: 20px;
    background: #f1f5f9;
    color: #475569;
    font-size: 9.5px;
    font-weight: 700;
    white-space: nowrap;
    letter-spacing: 0.3px;
}

/* ==========================================
   CHARTS SECTION (BOTTOM GRID)
========================================== */

.purchase-bottom-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.purchase-chart-card {
    background: #fff;
    border: 1px solid #e9eef6;
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 2px 10px rgba(32, 66, 110, .03);
    display: flex;
    flex-direction: column;
}

.purchase-chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}

.purchase-chart-title {
    font-size: 16px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

/* Line Chart Specific */
.purchase-line-container {
    position: relative;
    height: 250px;
    width: 100%;
}

.purchase-line-legend {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 14px;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
}

.purchase-legend-symbol {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.purchase-legend-symbol .line {
    width: 16px;
    height: 2px;
    background: #0866c6;
    display: inline-block;
}

.purchase-legend-symbol .dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #0866c6;
    display: inline-block;
    margin-left: -11px;
}

/* Donut Chart Specific */
.purchase-donut-layout {
    display: flex;
    align-items: center;
    gap: 22px;
    min-height: 250px;
}

.purchase-donut-wrapper {
    width: 160px;
    height: 160px;
    position: relative;
    flex-shrink: 0;
}

.purchase-donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    pointer-events: none;
    text-align: center;
}

.purchase-donut-center small {
    color: #64748b;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.purchase-donut-center strong {
    color: #1e293b;
    font-size: 26px;
    font-weight: 800;
    line-height: 1.1;
    margin: 2px 0;
}

.purchase-donut-center span {
    color: #64748b;
    font-size: 9.5px;
    font-weight: 700;
}

.purchase-category-list {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 0;
}

.purchase-category-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
    padding: 12px 14px;
    transition: transform .15s ease, border-color .15s ease;
}

.purchase-category-box:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}

.purchase-cat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
}

.purchase-cat-label {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 11.5px;
    font-weight: 700;
    color: #1e293b;
}

.purchase-cat-color {
    width: 10px;
    height: 10px;
    border-radius: 3px;
    flex-shrink: 0;
}

.purchase-cat-po {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
}

.purchase-cat-nominal {
    font-size: 16px;
    font-weight: 800;
    color: #0866c6;
    margin-bottom: 2px;
}

.purchase-category-box:nth-child(2) .purchase-cat-nominal {
    color: #2563eb;
}

.purchase-cat-subtext {
    font-size: 10.5px;
    font-weight: 600;
    color: #64748b;
}

/* ==========================================
   SYSTEM STATUS FOOTER
========================================== */

.purchase-footer {
    background: #1d2b3e;
    color: #aab6c6;
    padding: 12px 22px;
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    font-size: 10px;
    margin-top: 10px;
}

.purchase-footer .online {
    color: #34d399;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 6px;
}

.purchase-footer .online-dot {
    width: 7px;
    height: 7px;
    background: #34d399;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 6px #34d399;
}

/* ==========================================
   RESPONSIVE DESIGN
========================================== */

@media (max-width: 1200px) {
    .purchase-top-grid {
        grid-template-columns: 1fr;
    }

    .purchase-left-stack {
        grid-column: span 1;
    }

    .p-ppb-card {
        height: auto;
    }

    .purchase-bottom-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .purchase-dashboard {
        padding: 16px 14px;
    }

    .purchase-heading {
        flex-direction: column;
    }

    .purchase-actions {
        width: 100%;
    }

    .purchase-actions .purchase-btn {
        flex: 1;
    }

    .purchase-filter {
        padding: 16px;
    }

    .purchase-filter-form {
        flex-wrap: wrap;
    }

    .purchase-filter-form input[type="date"] {
        flex: 1 1 45%;
        min-width: 120px;
    }

    .purchase-filter-form .separator {
        display: none;
    }

    .purchase-filter-submit {
        flex: 1 1 100%;
    }

    .purchase-today {
        width: 100%;
        justify-content: center;
    }

    .purchase-left-stack {
        grid-template-columns: 1fr;
    }

    .purchase-donut-layout {
        flex-direction: column;
        gap: 16px;
    }

    .purchase-line-container {
        height: 200px;
    }

    .purchase-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
}

/* ==========================================
   PRINT STYLES
========================================== */

@media print {
    body {
        background: white !important;
    }

    .purchase-dashboard {
        padding: 10px;
        background: white;
    }

    .purchase-actions,
    .purchase-filter {
        display: none !important;
    }

    .p-card,
    .p-ppb-card,
    .purchase-chart-card {
        box-shadow: none !important;
        break-inside: avoid;
    }

    .purchase-footer {
        display: none !important;
    }
}

/* ==========================================
   MODAL OUTSTANDING PO STYLES
========================================== */

.po-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: poFadeIn .2s ease-out;
}

.po-modal-overlay.active {
    display: flex;
}

@keyframes poFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.po-modal-window {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 1300px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35);
    overflow: hidden;
    animation: poSlideUp .25s ease-out;
}

@keyframes poSlideUp {
    from { transform: translateY(20px) scale(0.98); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}

/* Modal Header */
.po-modal-header {
    background: linear-gradient(135deg, #075db5 0%, #0866c6 100%);
    color: #ffffff;
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.po-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.po-header-icon {
    width: 40px;
    height: 40px;
    background: #ffffff;
    color: #0866c6;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
}

.po-header-text h3 {
    font-size: 18px;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    line-height: 1.2;
}

.po-header-badge {
    background: rgba(255, 255, 255, 0.22);
    color: #ffffff;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.po-header-text p {
    font-size: 11.5px;
    color: rgba(255, 255, 255, 0.85);
    margin: 3px 0 0;
}

.po-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.po-search-input-wrap {
    position: relative;
    width: 240px;
}

.po-search-input-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
}

.po-search-input-wrap input {
    width: 100%;
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 12px 0 34px;
    font-size: 12px;
    color: #1e293b;
    outline: none;
    transition: box-shadow .2s;
}

.po-search-input-wrap input:focus {
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.35);
}

.po-select-status {
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    outline: none;
    cursor: pointer;
}

.po-btn-export {
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 14px;
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: background-color .2s;
}

.po-btn-export:hover {
    background: #f1f5f9;
}

.po-btn-close-x {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    transition: background-color .2s;
}

.po-btn-close-x:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Modal Body Grid */
.po-modal-body {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    flex: 1;
    overflow: hidden;
    min-height: 480px;
    max-height: calc(92vh - 135px);
}

/* Left Panel: Table of POs */
.po-panel-left {
    overflow-y: auto;
    border-right: 1px solid #e2e8f0;
    background: #ffffff;
}

.po-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.po-table thead th {
    position: sticky;
    top: 0;
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 14px;
    border-bottom: 1px solid #e2e8f0;
    z-index: 1;
    white-space: nowrap;
}

.po-table tbody tr {
    cursor: pointer;
    transition: background-color .15s ease;
}

.po-table tbody tr:hover {
    background: #f8fafc;
}

.po-table tbody tr.active-row {
    background: #edf5ff;
}

.po-table tbody tr.active-row td:first-child {
    border-left: 4px solid #0866c6;
}

.po-table tbody td {
    padding: 13px 14px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.po-row-num {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
}

.po-number-cell {
    font-weight: 800;
    color: #0866c6;
    font-size: 12.5px;
    line-height: 1.25;
}

.po-supplier-cell {
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
    margin-top: 3px;
}

.po-date-cell {
    color: #475569;
    font-weight: 600;
    font-size: 12px;
    white-space: nowrap;
}

.po-nominal-cell {
    font-weight: 800;
    color: #0f172a;
    font-size: 12.5px;
    white-space: nowrap;
}

.po-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.po-status-badge.amber {
    background: #fef3c7;
    color: #b45309;
}

.po-status-badge.amber .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #f59e0b;
}

.po-status-badge.blue {
    background: #e0f2fe;
    color: #0369a1;
}

.po-status-badge.blue .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #0284c7;
}

.po-arrow-cell {
    color: #cbd5e1;
    font-size: 13px;
    text-align: right;
}

.active-row .po-arrow-cell {
    color: #0866c6;
}

/* Right Panel: Detail View */
.po-panel-right {
    overflow-y: auto;
    padding: 22px 24px;
    background: #ffffff;
    display: flex;
    flex-direction: column;
}

.po-detail-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.po-detail-header-left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.po-badge-rincian {
    background: #0866c6;
    color: #ffffff;
    padding: 3px 9px;
    border-radius: 5px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.5px;
}

.po-detail-nopo {
    font-size: 15px;
    font-weight: 800;
    color: #1e293b;
}

.po-detail-supplier-name {
    font-size: 18px;
    font-weight: 800;
    color: #1e293b;
    margin: 4px 0 14px;
}

.po-detail-boxes {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 14px;
}

.po-meta-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    padding: 10px 14px;
}

.po-meta-card span {
    display: block;
    font-size: 10.5px;
    color: #64748b;
    font-weight: 600;
}

.po-meta-card strong {
    font-size: 13px;
    color: #1e293b;
    font-weight: 800;
    margin-top: 2px;
    display: block;
}

.po-keterangan-alert {
    background: #f0f7ff;
    border-left: 3px solid #0866c6;
    border-radius: 6px;
    padding: 9px 12px;
    font-size: 11px;
    color: #334155;
    margin-bottom: 18px;
    line-height: 1.5;
}

.po-keterangan-alert strong {
    color: #0866c6;
}

.po-items-title-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e2e8f0;
}

.po-items-title-bar h5 {
    font-size: 13px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.po-items-count-tag {
    font-size: 11px;
    font-weight: 800;
    color: #0866c6;
}

.po-items-table-wrap {
    flex: 1;
    overflow-x: auto;
}

.po-items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11.5px;
}

.po-items-table th {
    background: #f8fafc;
    color: #64748b;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 8px 10px;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

.po-items-table td {
    padding: 10px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
}

.po-items-table td.qty-col {
    text-align: center;
    font-weight: 700;
}

.po-items-table td.subtotal-col {
    text-align: right;
    font-weight: 800;
    color: #0f172a;
    white-space: nowrap;
}

.po-item-note {
    font-size: 10px;
    color: #64748b;
    margin-top: 2px;
}

.po-detail-footer-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    padding: 12px 16px;
    margin-top: 16px;
}

.po-detail-footer-summary span {
    font-size: 12px;
    color: #64748b;
    font-weight: 700;
}

.po-detail-footer-summary strong {
    font-size: 17px;
    font-weight: 800;
    color: #0866c6;
}

/* Modal Bottom Footer */
.po-modal-bottom {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 12px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.po-bottom-info {
    font-size: 11.5px;
    color: #64748b;
    font-weight: 600;
}

.po-bottom-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.po-page-btn {
    padding: 6px 12px;
    border: 1px solid #dce5f1;
    background: #ffffff;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    transition: all .15s;
}

.po-page-btn:hover:not(.disabled) {
    background: #edf4ff;
    color: #0866c6;
}

.po-page-btn.active {
    background: #0866c6;
    color: #ffffff;
    border-color: #0866c6;
}

.po-page-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.po-btn-tutup {
    padding: 6px 18px;
    border: 1px solid #dce5f1;
    background: #ffffff;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    transition: all .15s;
}

.po-btn-tutup:hover {
    background: #f1f5f9;
    color: #0f172a;
}

/* Responsive Modal */
@media (max-width: 992px) {
    .po-modal-body {
        grid-template-columns: 1fr;
        max-height: 80vh;
    }

    .po-panel-left {
        max-height: 300px;
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
    }
}

/* ==========================================
   MODAL PEMBELIAN BELUM DILUNASI STYLES
========================================== */

.unpaid-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: poFadeIn .2s ease-out;
}

.unpaid-modal-overlay.active {
    display: flex;
}

.unpaid-modal-window {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 1280px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35);
    overflow: hidden;
    animation: poSlideUp .25s ease-out;
}

.unpaid-modal-header {
    background: linear-gradient(135deg, #075db5 0%, #0866c6 100%);
    color: #ffffff;
    padding: 20px 26px 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.unpaid-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.unpaid-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.unpaid-header-icon {
    width: 42px;
    height: 42px;
    background: #ffffff;
    color: #0866c6;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
}

.unpaid-header-title-wrap h3 {
    font-size: 19px;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    line-height: 1.25;
}

.unpaid-badge-count {
    background: #ffffff;
    color: #dc2626;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.3px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.unpaid-badge-count .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #dc2626;
}

.unpaid-header-title-wrap p {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.85);
    margin: 4px 0 0;
}

.unpaid-header-summary-card {
    background: #ffffff;
    border-radius: 10px;
    padding: 8px 18px;
    text-align: right;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
    flex-shrink: 0;
}

.unpaid-header-summary-card span {
    display: block;
    font-size: 10px;
    font-weight: 800;
    color: #64748b;
    letter-spacing: 0.6px;
    text-transform: uppercase;
}

.unpaid-header-summary-card strong {
    display: block;
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    margin-top: 2px;
}

.unpaid-header-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.unpaid-controls-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    max-width: 600px;
}

.unpaid-search-wrap {
    position: relative;
    flex: 1;
    min-width: 220px;
}

.unpaid-search-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
}

.unpaid-search-wrap input {
    width: 100%;
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 12px 0 34px;
    font-size: 12px;
    color: #1e293b;
    outline: none;
    transition: box-shadow .2s;
}

.unpaid-search-wrap input:focus {
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.35);
}

.unpaid-select-status {
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    outline: none;
    cursor: pointer;
}

.unpaid-controls-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.unpaid-btn-export {
    height: 38px;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 16px;
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: background-color .2s;
}

.unpaid-btn-export:hover {
    background: #f1f5f9;
}

.unpaid-btn-close-x {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    transition: background-color .2s;
}

.unpaid-btn-close-x:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Modal Body Table */
.unpaid-modal-body {
    overflow-y: auto;
    overflow-x: auto;
    flex: 1;
    background: #ffffff;
    min-height: 380px;
    max-height: calc(92vh - 190px);
}

.unpaid-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12.5px;
}

.unpaid-table thead th {
    position: sticky;
    top: 0;
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 13px 16px;
    border-bottom: 1px solid #e2e8f0;
    z-index: 2;
    white-space: nowrap;
}

.unpaid-table tbody td {
    padding: 13px 16px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.unpaid-table tbody tr:hover {
    background: #f8fafc;
}

.unpaid-row-num {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
}

.unpaid-nb-badge {
    background: #e0f2fe;
    color: #0369a1;
    font-weight: 700;
    font-size: 11.5px;
    padding: 4px 9px;
    border-radius: 6px;
    display: inline-block;
    white-space: nowrap;
}

.unpaid-pelunasan-badge {
    background: #f1f5f9;
    color: #475569;
    font-weight: 700;
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 5px;
    display: inline-block;
    white-space: nowrap;
}

.unpaid-supplier-text {
    font-weight: 700;
    color: #1e293b;
}

.unpaid-date-text {
    color: #475569;
    font-weight: 600;
    font-size: 12px;
    white-space: nowrap;
}

.unpaid-nominal-text {
    font-weight: 800;
    color: #1e293b;
    white-space: nowrap;
}

.unpaid-kurang-bayar-text {
    font-weight: 800;
    color: #dc2626;
    white-space: nowrap;
}

.unpaid-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.unpaid-status-badge.danger {
    background: #fee2e2;
    color: #b91c1c;
}

.unpaid-status-badge.danger .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #dc2626;
}

.unpaid-status-badge.info {
    background: #e0f2fe;
    color: #0369a1;
}

.unpaid-status-badge.info .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #0284c7;
}

/* Modal Bottom Footer */
.unpaid-modal-bottom {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 12px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.unpaid-bottom-info {
    font-size: 11.5px;
    color: #64748b;
    font-weight: 600;
}

.unpaid-bottom-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.unpaid-btn-tutup {
    padding: 6px 18px;
    border: 1px solid #dce5f1;
    background: #ffffff;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    transition: all .15s;
}

.unpaid-btn-tutup:hover {
    background: #f1f5f9;
    color: #0f172a;
}

</style>

<!-- ==========================================
     MAIN DASHBOARD
========================================== -->

<main class="purchase-dashboard">

    <!-- HEADER DASHBOARD -->
    <section class="purchase-heading">
        <div>
            <div class="purchase-title">
                <div class="heading-icon">
                    <i class="bi bi-cart-check-fill"></i>
                </div>
                <div>
                    <h1>Dashboard Monitoring Pembelian</h1>
                </div>
            </div>
        </div>

        <div class="purchase-actions">
            <span class="purchase-user-tag me-2">Halo, <span><?= htmlspecialchars($currentUser) ?></span></span>

            <button type="button" class="purchase-btn" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh Data
            </button>

            <button type="button" class="purchase-btn" onclick="exportExcel()">
                <i class="bi bi-file-earmark-excel"></i>
                Export XLS
            </button>

            <button type="button" class="purchase-btn primary" onclick="window.print()">
                <i class="bi bi-printer"></i>
                Print Laporan
            </button>
        </div>
    </section>

    <!-- FILTER PERIODE -->
    <section class="purchase-filter">
        <div class="purchase-filter-title">
            <i class="bi bi-calendar3"></i>
            Periode Transaksi
        </div>

        <form method="GET" class="purchase-filter-form">
            <input
                type="date"
                name="tanggal_awal"
                id="tanggalAwal"
                value="<?= htmlspecialchars($startDate) ?>"
            >

            <span class="separator">s/d</span>

            <input
                type="date"
                name="tanggal_akhir"
                id="tanggalAkhir"
                value="<?= htmlspecialchars($endDate) ?>"
            >

            <button type="submit" class="purchase-filter-submit">
                Tampilkan
            </button>

            <label class="purchase-today">
                <input
                    type="checkbox"
                    id="todayOnly"
                    <?= ($startDate === date('Y-m-d') && $endDate === date('Y-m-d')) ? 'checked' : '' ?>
                >
                <i class="bi bi-calendar-check"></i>
                Hari Ini Saja
            </label>
        </form>
    </section>

    <!-- ======================================
         TOP GRID: 4 METRIC CARDS + PPB CARD
    ======================================= -->
    <section class="purchase-top-grid">

        <!-- Left 2x2 Metric Cards Grid -->
        <div class="purchase-left-stack">

            <!-- Card 1: Purchase Order -->
            <div class="p-card card-default">
                <div class="p-card-header">
                    <h3 class="p-card-title">Purchase Order</h3>
                    <a href="#" class="p-circle-btn btn-gray" title="Lihat Detail Purchase Order">
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                </div>

                <div class="p-card-metric">
                    <span class="p-metric-number"><?= number_format($totalPO, 0, ',', '.') ?></span>
                    <span class="p-metric-unit">Transaksi</span>
                </div>

                <div class="p-card-footer">
                    <span>Total Nominal :</span>
                    <strong>Rp <?= number_format($totalPONominal, 0, ',', '.') ?>, 00</strong>
                </div>
            </div>

            <!-- Card 2: Outstanding PO -->
            <div class="p-card card-blue" style="cursor: pointer;" onclick="openOutstandingModal()" title="Klik untuk meninjau Daftar Outstanding PO">
                <div class="p-card-header">
                    <h3 class="p-card-title">Outstanding PO</h3>
                    <button type="button" class="p-circle-btn btn-blue" onclick="event.stopPropagation(); openOutstandingModal();" title="Lihat Detail Outstanding PO">
                        <i class="bi bi-arrow-up-right"></i>
                    </button>
                </div>

                <div class="p-card-metric">
                    <span class="p-metric-number"><?= number_format($outstandingPO, 0, ',', '.') ?></span>
                    <span class="p-metric-unit">Transaksi</span>
                </div>

                <div class="p-card-footer">
                    <span>Total Nominal :</span>
                    <strong>Rp <?= number_format($outstandingPONominal, 0, ',', '.') ?>, 00</strong>
                </div>
            </div>

            <!-- Card 3: Pembelian Belum Dilunasi -->
            <div class="p-card card-red" style="cursor: pointer;" onclick="openUnpaidModal()" title="Klik untuk meninjau Pembelian Belum Dilunasi">
                <div class="p-card-header">
                    <h3 class="p-card-title">Pembelian Belum Dilunasi</h3>
                    <button type="button" class="p-circle-btn btn-red" onclick="event.stopPropagation(); openUnpaidModal();" title="Lihat Detail Pembelian Belum Dilunasi">
                        <i class="bi bi-arrow-up-right"></i>
                    </button>
                </div>

                <div class="p-card-metric">
                    <span class="p-metric-nominal">Rp <?= number_format($unpaidNominal, 0, ',', '.') ?>, 00</span>
                </div>

                <div class="p-card-footer">
                    <span class="status-indicator danger">
                        <span class="indicator-dot"></span>
                        Jatuh tempo berjalan
                    </span>
                </div>
            </div>

            <!-- Card 4: Pengajuan PO -->
            <div class="p-card card-green">
                <div class="p-card-header">
                    <h3 class="p-card-title">Pengajuan PO</h3>
                    <a href="#" class="p-circle-btn btn-green" title="Lihat Detail Pengajuan PO">
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                </div>

                <div class="p-card-metric">
                    <span class="p-metric-number"><?= number_format($submissionCount, 0, ',', '.') ?></span>
                    <span class="p-metric-unit">pengajuan</span>
                </div>

                <div class="p-card-footer">
                    <span class="status-indicator success">
                        <span class="indicator-dot"></span>
                        Memerlukan Konfirmasi
                    </span>
                </div>
            </div>

        </div>

        <!-- Right: Diperlukan Pembelian Bahan (PPB) -->
        <div class="p-ppb-card">
            <div class="p-ppb-header">
                <h2>Diperlukan Pembelian Bahan</h2>
                <a href="#" class="p-circle-btn btn-orange" title="Buka Modul PPB">
                    <i class="bi bi-arrow-up-right"></i>
                </a>
            </div>

            <div class="p-ppb-alert-strip">
                <div class="p-ppb-alert-label">
                    <i class="bi bi-exclamation-triangle"></i>
                    Diperlukan Pembelian Bahan
                </div>
                <a href="#" class="p-ppb-link">
                    Lihat Semua Kebutuhan &rarr;
                </a>
            </div>

            <div class="p-ppb-list">
                <?php foreach ($urgentPurchases as $item): ?>
                    <div class="p-ppb-item">
                        <div class="p-ppb-item-left">
                            <span class="p-ppb-dot <?= $item['status'] ?>"></span>
                            <div class="p-ppb-item-content">
                                <div class="p-ppb-item-title"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="p-ppb-item-sub"><?= htmlspecialchars($item['code']) ?> = <?= htmlspecialchars($item['qty']) ?></div>
                            </div>
                        </div>
                        <div class="p-ppb-item-badge">
                            <?= htmlspecialchars($item['badge']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </section>

    <!-- ======================================
         BOTTOM GRID: CHARTS
    ======================================= -->
    <section class="purchase-bottom-grid">

        <!-- Chart 1: Pembelian per Periode (Line Chart) -->
        <div class="purchase-chart-card">
            <div class="purchase-chart-header">
                <h3 class="purchase-chart-title">Pembelian per Periode</h3>
            </div>

            <div class="purchase-line-container">
                <canvas id="purchasePeriodChart"></canvas>
            </div>

            <div class="purchase-line-legend">
                <span class="purchase-legend-symbol">
                    <span class="line"></span>
                    <span class="dot"></span>
                </span>
                Total Pembelian
            </div>
        </div>

        <!-- Chart 2: Pembelian Barang per Jenis (Donut Chart) -->
        <div class="purchase-chart-card">
            <div class="purchase-chart-header">
                <h3 class="purchase-chart-title">Pembelian Barang per Jenis</h3>
            </div>

            <div class="purchase-donut-layout">
                <!-- Donut Canvas & Center text -->
                <div class="purchase-donut-wrapper">
                    <canvas id="categoryDonutChart"></canvas>
                    <div class="purchase-donut-center">
                        <small>TOTAL PO</small>
                        <strong><?= $totalPO ?></strong>
                        <span>PO Aktif</span>
                    </div>
                </div>

                <!-- Breakdown Info Boxes -->
                <div class="purchase-category-list">
                    <?php foreach ($categoryData as $cat): ?>
                        <div class="purchase-category-box">
                            <div class="purchase-cat-top">
                                <div class="purchase-cat-label">
                                    <span class="purchase-cat-color" style="background: <?= $cat['color'] ?>;"></span>
                                    <span><?= htmlspecialchars($cat['label']) ?> (<?= $cat['percent'] ?>%)</span>
                                </div>
                                <span class="purchase-cat-po"><?= $cat['po_count'] ?> PO</span>
                            </div>

                            <div class="purchase-cat-nominal">
                                Rp <?= number_format($cat['nominal'], 0, ',', '.') ?>
                            </div>

                            <div class="purchase-cat-subtext">
                                <?= htmlspecialchars($cat['subtext']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </section>

    <!-- ==========================================
         MODAL POPUP: DAFTAR OUTSTANDING PO
    ========================================== -->
    <div class="po-modal-overlay" id="outstandingPoModal" onclick="handlePoBackdropClick(event)">
        <div class="po-modal-window" onclick="event.stopPropagation()">

            <!-- Header Modal -->
            <div class="po-modal-header">
                <div class="po-header-left">
                    <div class="po-header-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div class="po-header-text">
                        <h3>
                            Daftar Outstanding PO
                            <span class="po-header-badge" id="poHeaderBadge"><?= count($outstandingList) ?> Data Terkini</span>
                        </h3>
                        <p>Klik baris No. PO untuk meninjau rincian barang</p>
                    </div>
                </div>

                <div class="po-header-actions">
                    <div class="po-search-input-wrap">
                        <i class="bi bi-search"></i>
                        <input
                            type="text"
                            id="poSearchInput"
                            placeholder="Cari No. PO, Supplier..."
                            oninput="filterPoList()"
                        >
                    </div>

                    <select id="poStatusFilter" class="po-select-status" onchange="filterPoList()">
                        <option value="all">Semua Status</option>
                        <option value="Belum Dikirim">Belum Dikirim</option>
                        <option value="Dikirim Sebagian">Dikirim Sebagian</option>
                    </select>

                    <button type="button" class="po-btn-export" onclick="exportPoList()">
                        <i class="bi bi-box-arrow-up"></i>
                        Export
                    </button>

                    <button type="button" class="po-btn-close-x" onclick="closeOutstandingModal()" title="Tutup Modal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Body Modal: Master-Detail -->
            <div class="po-modal-body">

                <!-- Panel Kiri: Tabel PO -->
                <div class="po-panel-left">
                    <table class="po-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;">#</th>
                                <th>No. PO &amp; Supplier</th>
                                <th>Tgl Transaksi</th>
                                <th>Tgl Pengiriman</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th style="width: 30px;"></th>
                            </tr>
                        </thead>
                        <tbody id="poTableBody">
                            <!-- Rendered by JavaScript -->
                        </tbody>
                    </table>
                    <div id="poEmptyMessage" style="display: none; padding: 40px 20px; text-align: center; color: #94a3b8;">
                        <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                        Tidak ada data Purchasing Order yang sesuai dengan pencarian.
                    </div>
                </div>

                <!-- Panel Kanan: Rincian Barang -->
                <div class="po-panel-right">
                    <div class="po-detail-top-row">
                        <div class="po-detail-header-left">
                            <span class="po-badge-rincian">RINCIAN BARANG</span>
                            <span class="po-detail-nopo" id="detailNoPo">-</span>
                        </div>
                        <div id="detailStatusWrap">
                            <!-- Status Badge -->
                        </div>
                    </div>

                    <div class="po-detail-supplier-name" id="detailSupplier">-</div>

                    <div class="po-detail-boxes">
                        <div class="po-meta-card">
                            <span>Tgl Transaksi:</span>
                            <strong id="detailTglTrans">-</strong>
                        </div>
                        <div class="po-meta-card">
                            <span>Tgl Pengiriman:</span>
                            <strong id="detailTglKirim">-</strong>
                        </div>
                    </div>

                    <div class="po-keterangan-alert" id="detailKeteranganBox">
                        <strong><i class="bi bi-info-circle"></i> Catatan PO:</strong>
                        <span id="detailKeterangan">-</span>
                    </div>

                    <div class="po-items-title-bar">
                        <h5>Daftar Barang yang Dipesan</h5>
                        <span class="po-items-count-tag" id="detailItemsCount">0 Macam Barang</span>
                    </div>

                    <div class="po-items-table-wrap">
                        <table class="po-items-table">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Satuan</th>
                                    <th style="text-align: center;">Qty Total</th>
                                    <th style="text-align: center;">Qty Kirim</th>
                                    <th style="text-align: center;">Qty Sisa</th>
                                    <th style="text-align: right;">Nominal</th>
                                </tr>
                            </thead>
                            <tbody id="detailItemsTableBody">
                                <!-- Rendered by JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <div class="po-detail-footer-summary">
                        <span>Total Nominal PO Terpilih:</span>
                        <strong id="detailTotalNominal">Rp 0</strong>
                    </div>
                </div>

            </div>

            <!-- Footer Modal -->
            <div class="po-modal-bottom">
                <div class="po-bottom-info" id="poFooterCount">
                    Menampilkan <?= count($outstandingList) ?> total Purchasing Order
                </div>

                <div class="po-bottom-controls">
                    <button type="button" class="po-page-btn disabled">&lsaquo; Sebelumnya</button>
                    <button type="button" class="po-page-btn active">1</button>
                    <button type="button" class="po-page-btn disabled">Selanjutnya &rsaquo;</button>
                    <button type="button" class="po-btn-tutup" onclick="closeOutstandingModal()">Tutup</button>
                </div>
            </div>

        </div>
    </div>

    <!-- ==========================================
         MODAL POPUP: DAFTAR PEMBELIAN BELUM DILUNASI
    ========================================== -->
    <div class="unpaid-modal-overlay" id="unpaidBillsModal" onclick="handleUnpaidBackdropClick(event)">
        <div class="unpaid-modal-window" onclick="event.stopPropagation()">

            <!-- Header Modal -->
            <div class="unpaid-modal-header">
                <div class="unpaid-header-top">
                    <div class="unpaid-header-left">
                        <div class="unpaid-header-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="unpaid-header-title-wrap">
                            <h3>
                                Daftar Pembelian Belum Dilunasi
                                <span class="unpaid-badge-count" id="unpaidHeaderBadge">
                                    <span class="dot"></span>
                                    <?= count($unpaidList) ?> Tagihan Belum Lunas
                                </span>
                            </h3>
                            <p>Daftar faktur pembelian tempo dan nota beli (NB) yang menunggu pelunasan pembayaran</p>
                        </div>
                    </div>

                    <div class="unpaid-header-summary-card">
                        <span>TOTAL KURANG BAYAR</span>
                        <strong id="unpaidSummaryTotal">Rp <?= number_format($unpaidNominal, 0, ',', '.') ?>,00</strong>
                    </div>
                </div>

                <div class="unpaid-header-controls">
                    <div class="unpaid-controls-left">
                        <div class="unpaid-search-wrap">
                            <i class="bi bi-search"></i>
                            <input
                                type="text"
                                id="unpaidSearchInput"
                                placeholder="Cari Supplier, No. NB, No. Pelunasan..."
                                oninput="filterUnpaidList()"
                            >
                        </div>
                    </div>

                    <div class="unpaid-controls-right">
                        <button type="button" class="unpaid-btn-export" onclick="exportUnpaidList()">
                            <i class="bi bi-box-arrow-up"></i>
                            Export
                        </button>

                        <button type="button" class="unpaid-btn-close-x" onclick="closeUnpaidModal()" title="Tutup Modal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Body Modal: Tabel Full Width -->
            <div class="unpaid-modal-body">
                <table class="unpaid-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Supplier</th>
                            <th>No. NB</th>
                            <th>No. Pelunasan</th>
                            <th>Tgl. Pembelian</th>
                            <th>Tgl. Tempo Pembelian</th>
                            <th style="text-align: right;">Nominal</th>
                            <th style="text-align: right;">Kurang Bayar</th>
                        </tr>
                    </thead>
                    <tbody id="unpaidTableBody">
                        <!-- Rendered by JavaScript -->
                    </tbody>
                </table>
                <div id="unpaidEmptyMessage" style="display: none; padding: 40px 20px; text-align: center; color: #94a3b8;">
                    <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                    Tidak ada data Pembelian Belum Dilunasi yang sesuai dengan pencarian.
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="unpaid-modal-bottom">
                <div class="unpaid-bottom-info" id="unpaidFooterCount">
                    Menampilkan <?= count($unpaidList) ?> dari <?= count($unpaidList) ?> total Pembelian Belum Dilunasi
                </div>

                <div class="unpaid-bottom-controls">
                    <button type="button" class="po-page-btn disabled">&lsaquo; Sebelumnya</button>
                    <button type="button" class="po-page-btn active">1</button>
                    <button type="button" class="po-page-btn disabled">Selanjutnya &rsaquo;</button>
                    <button type="button" class="unpaid-btn-tutup" onclick="closeUnpaidModal()">Tutup</button>
                </div>
            </div>

        </div>
    </div>
   
</main>

<!-- ======================================
     JAVASCRIPT
======================================= -->
<script>
// ======================================
// CHART CONFIGURATION
// ======================================
Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#64748b';

// ======================================
// 1. LINE CHART: PEMBELIAN PER PERIODE
// ======================================
const periodLabels = <?= json_encode($periodLabels) ?>;
const periodData = <?= json_encode($periodData) ?>;

const ctxPeriod = document.getElementById('purchasePeriodChart').getContext('2d');

new Chart(ctxPeriod, {
    type: 'line',
    data: {
        labels: periodLabels,
        datasets: [{
            label: 'Total Pembelian',
            data: periodData,
            borderColor: '#0875d1',
            backgroundColor: 'rgba(8, 117, 209, 0.05)',
            borderWidth: 2,
            tension: 0.25,
            fill: true,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#0875d1',
            pointBorderWidth: 2,
            pointRadius: 3.5,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: '#0875d1',
            pointHoverBorderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: '#1e293b',
                titleFont: { weight: 'bold', size: 12 },
                bodyFont: { size: 11 },
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: function(context) {
                        return ' Total: ' + context.parsed.y + ' Transaksi';
                    }
                }
            }
        },
        scales: {
            y: {
                min: 0,
                max: 100,
                ticks: {
                    stepSize: 25,
                    font: { size: 10 }
                },
                grid: {
                    color: '#e2e8f0',
                    drawBorder: false
                }
            },
            x: {
                ticks: {
                    font: { size: 10 }
                },
                grid: {
                    color: '#edf2f7',
                    drawBorder: false
                }
            }
        }
    }
});

// ======================================
// 2. DONUT CHART: PEMBELIAN BARANG PER JENIS
// ======================================
const categoryData = <?= json_encode($categoryData) ?>;
const catValues = categoryData.map(c => c.percent);
const catColors = categoryData.map(c => c.color);

const ctxCategory = document.getElementById('categoryDonutChart').getContext('2d');

new Chart(ctxCategory, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: catValues,
            backgroundColor: catColors,
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: '#1e293b',
                titleFont: { weight: 'bold', size: 12 },
                bodyFont: { size: 11 },
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: function(context) {
                        const item = categoryData[context.dataIndex];
                        return ` ${item.label}: ${item.percent}% (${item.po_count} PO)`;
                    }
                }
            }
        }
    }
});

// ======================================
// 3. FILTER HARI INI
// ======================================
const todayCheckbox = document.getElementById('todayOnly');

todayCheckbox.addEventListener('change', function() {
    if (this.checked) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const formattedDate = `${year}-${month}-${day}`;

        document.getElementById('tanggalAwal').value = formattedDate;
        document.getElementById('tanggalAkhir').value = formattedDate;
    }
});

// ======================================
// 4. EXPORT EXCEL SEMENTARA
// ======================================
function exportExcel() {
    alert('Fitur Export Excel akan diaktifkan setelah data pembelian terhubung dengan database.');
}

// ======================================
// 5. MODAL DAFTAR OUTSTANDING PO
// ======================================
const outstandingData = <?= json_encode($outstandingList) ?>;
let selectedPoIndex = 0;
let filteredPoIndices = outstandingData.map((_, i) => i);

function formatRupiah(number) {
    return 'Rp ' + Number(number).toLocaleString('id-ID');
}

function openOutstandingModal() {
    const modal = document.getElementById('outstandingPoModal');
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Reset filter
    document.getElementById('poSearchInput').value = '';
    document.getElementById('poStatusFilter').value = 'all';
    filterPoList();
}

function closeOutstandingModal() {
    const modal = document.getElementById('outstandingPoModal');
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function handlePoBackdropClick(e) {
    if (e.target.id === 'outstandingPoModal') {
        closeOutstandingModal();
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeOutstandingModal();
        closeUnpaidModal();
    }
});

function renderPoTable() {
    const tbody = document.getElementById('poTableBody');
    const emptyMsg = document.getElementById('poEmptyMessage');
    tbody.innerHTML = '';

    if (filteredPoIndices.length === 0) {
        emptyMsg.style.display = 'block';
        clearPoDetail();
        return;
    }

    emptyMsg.style.display = 'none';

    filteredPoIndices.forEach((dataIndex, loopIdx) => {
        const po = outstandingData[dataIndex];
        const tr = document.createElement('tr');
        if (dataIndex === selectedPoIndex) {
            tr.classList.add('active-row');
        }

        const rowNum = String(loopIdx + 1).padStart(2, '0');
        const badgeClass = po.status === 'Dikirim Sebagian' ? 'amber' : 'blue';

        tr.innerHTML = `
            <td class="po-row-num">${rowNum}</td>
            <td>
                <div class="po-number-cell">${po.no_po}</div>
                <div class="po-supplier-cell">${po.nama_supplier}</div>
            </td>
            <td class="po-date-cell">${po.tgl_trans}</td>
            <td class="po-date-cell">${po.tgl_kirim}</td>
            <td class="po-nominal-cell">${formatRupiah(po.jumlah)}</td>
            <td>
                <span class="po-status-badge ${badgeClass}">
                    <span class="dot"></span>
                    ${po.status}
                </span>
            </td>
            <td class="po-arrow-cell">
                <i class="bi bi-chevron-right"></i>
            </td>
        `;

        tr.addEventListener('click', () => {
            selectPo(dataIndex);
        });

        tbody.appendChild(tr);
    });

    // Pastikan panel rincian menampilkan PO yang valid
    if (!filteredPoIndices.includes(selectedPoIndex)) {
        selectPo(filteredPoIndices[0]);
    } else {
        renderPoDetail(outstandingData[selectedPoIndex]);
    }

    // Perbarui counter di footer
    const footerCount = document.getElementById('poFooterCount');
    if (footerCount) {
        footerCount.innerText = `Menampilkan ${filteredPoIndices.length} dari ${outstandingData.length} total Purchasing Order`;
    }
}

function selectPo(index) {
    selectedPoIndex = index;
    const po = outstandingData[index];

    // Beri highlight pada baris aktif
    const tbody = document.getElementById('poTableBody');
    const rows = tbody.querySelectorAll('tr');
    filteredPoIndices.forEach((dataIndex, loopIdx) => {
        if (rows[loopIdx]) {
            if (dataIndex === selectedPoIndex) {
                rows[loopIdx].classList.add('active-row');
            } else {
                rows[loopIdx].classList.remove('active-row');
            }
        }
    });

    renderPoDetail(po);
}

function renderPoDetail(po) {
    if (!po) return;

    document.getElementById('detailNoPo').innerText = po.no_po;
    document.getElementById('detailSupplier').innerText = po.nama_supplier;
    document.getElementById('detailTglTrans').innerText = po.tgl_trans;
    document.getElementById('detailTglKirim').innerText = po.tgl_kirim;
    
    const ketBox = document.getElementById('detailKeteranganBox');
    if (po.keterangan) {
        ketBox.style.display = 'block';
        document.getElementById('detailKeterangan').innerText = ' ' + po.keterangan;
    } else {
        ketBox.style.display = 'none';
    }

    const badgeClass = po.status === 'Dikirim Sebagian' ? 'amber' : 'blue';
    document.getElementById('detailStatusWrap').innerHTML = `
        <span class="po-status-badge ${badgeClass}">
            <span class="dot"></span>
            ${po.status}
        </span>
    `;

    document.getElementById('detailItemsCount').innerText = `${po.items.length} Macam Barang`;
    document.getElementById('detailTotalNominal').innerText = formatRupiah(po.jumlah);

    const itemsTbody = document.getElementById('detailItemsTableBody');
    itemsTbody.innerHTML = '';

    po.items.forEach(item => {
        const itemTr = document.createElement('tr');
        itemTr.innerHTML = `
            <td>
                <div style="font-weight: 700; color: #1e293b;">${item.nama_barang}</div>
                ${item.keterangan ? `<div class="po-item-note"><i class="bi bi-tag"></i> ${item.keterangan}</div>` : ''}
            </td>
            <td style="color: #64748b; font-weight: 600;">${item.satuan}</td>
            <td class="qty-col">${item.qty}</td>
            <td class="qty-col" style="color: #059669;">${item.qty_kirim}</td>
            <td class="qty-col" style="color: ${item.sisa_qty > 0 ? '#d97706' : '#64748b'};">${item.sisa_qty}</td>
            <td class="subtotal-col">${formatRupiah(item.subtotal)}</td>
        `;
        itemsTbody.appendChild(itemTr);
    });
}

function clearPoDetail() {
    document.getElementById('detailNoPo').innerText = '-';
    document.getElementById('detailSupplier').innerText = '-';
    document.getElementById('detailTglTrans').innerText = '-';
    document.getElementById('detailTglKirim').innerText = '-';
    document.getElementById('detailKeterangan').innerText = '-';
    document.getElementById('detailStatusWrap').innerHTML = '';
    document.getElementById('detailItemsCount').innerText = '0 Macam Barang';
    document.getElementById('detailTotalNominal').innerText = 'Rp 0';
    document.getElementById('detailItemsTableBody').innerHTML = '';
}

function filterPoList() {
    const searchInput = document.getElementById('poSearchInput');
    const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const statusSelect = document.getElementById('poStatusFilter');
    const statusVal = statusSelect ? statusSelect.value : 'all';

    filteredPoIndices = [];

    outstandingData.forEach((po, index) => {
        const matchSearch = 
            po.no_po.toLowerCase().includes(searchVal) || 
            po.nama_supplier.toLowerCase().includes(searchVal);

        const matchStatus = (statusVal === 'all') || (po.status === statusVal);

        if (matchSearch && matchStatus) {
            filteredPoIndices.push(index);
        }
    });

    renderPoTable();
}

function exportPoList() {
    alert('Mengekspor ' + filteredPoIndices.length + ' data Outstanding PO...');
}

// ======================================
// 6. MODAL PEMBELIAN BELUM DILUNASI
// ======================================
const unpaidData = <?= json_encode($unpaidList) ?>;
let filteredUnpaidIndices = unpaidData.map((_, i) => i);

function openUnpaidModal() {
    const modal = document.getElementById('unpaidBillsModal');
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Reset filter
    const searchInput = document.getElementById('unpaidSearchInput');
    if (searchInput) searchInput.value = '';

    filterUnpaidList();
}

function closeUnpaidModal() {
    const modal = document.getElementById('unpaidBillsModal');
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function handleUnpaidBackdropClick(e) {
    if (e.target.id === 'unpaidBillsModal') {
        closeUnpaidModal();
    }
}

function renderUnpaidTable() {
    const tbody = document.getElementById('unpaidTableBody');
    const emptyMsg = document.getElementById('unpaidEmptyMessage');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (filteredUnpaidIndices.length === 0) {
        if (emptyMsg) emptyMsg.style.display = 'block';
        return;
    }

    if (emptyMsg) emptyMsg.style.display = 'none';

    filteredUnpaidIndices.forEach((dataIndex, loopIdx) => {
        const item = unpaidData[dataIndex];
        const tr = document.createElement('tr');

        const rowNum = '#' + String(loopIdx + 1).padStart(2, '0');

        tr.innerHTML = `
            <td class="unpaid-row-num">${rowNum}</td>
            <td class="unpaid-supplier-text">${item.supplier}</td>
            <td><span class="unpaid-nb-badge">${item.no_nb}</span></td>
            <td><span class="unpaid-pelunasan-badge">${item.no_pelunasan}</span></td>
            <td class="unpaid-date-text">${item.tgl_pembelian}</td>
            <td class="unpaid-date-text">${item.tgl_tempo}</td>
            <td class="unpaid-nominal-text" style="text-align: right;">${formatRupiah(item.nominal)}</td>
            <td class="unpaid-kurang-bayar-text" style="text-align: right;">${formatRupiah(item.kurang_bayar)}</td>
        `;

        tbody.appendChild(tr);
    });

    // Update footer info & summary card total for filtered items
    const footerCount = document.getElementById('unpaidFooterCount');
    if (footerCount) {
        footerCount.innerText = `Menampilkan ${filteredUnpaidIndices.length} dari ${unpaidData.length} total Pembelian Belum Dilunasi`;
    }

    const currentKurangBayar = filteredUnpaidIndices.reduce((sum, idx) => sum + unpaidData[idx].kurang_bayar, 0);
    const summaryTotal = document.getElementById('unpaidSummaryTotal');
    if (summaryTotal) {
        summaryTotal.innerText = formatRupiah(currentKurangBayar) + ',00';
    }
}

function filterUnpaidList() {
    const searchInput = document.getElementById('unpaidSearchInput');
    const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';

    filteredUnpaidIndices = [];

    unpaidData.forEach((item, index) => {
        const matchSearch = 
            item.supplier.toLowerCase().includes(searchVal) ||
            item.no_nb.toLowerCase().includes(searchVal) || 
            item.no_pelunasan.toLowerCase().includes(searchVal) ||
            item.tgl_pembelian.toLowerCase().includes(searchVal) ||
            item.tgl_tempo.toLowerCase().includes(searchVal);

        if (matchSearch) {
            filteredUnpaidIndices.push(index);
        }
    });

    renderUnpaidTable();
}

function exportUnpaidList() {
    alert('Mengekspor ' + filteredUnpaidIndices.length + ' data Pembelian Belum Dilunasi...');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
