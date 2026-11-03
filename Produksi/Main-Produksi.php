<?php
$pageTitle = 'Dashboard Produksi';
$activePage = 'produksi';
$basePath = '../';

require_once '../includes/header.php';

// ==========================================
// DATA SEMENTARA (DUMMY)
// Nantinya akan diganti dengan data MySQL
// ==========================================

$woTotal = 125;

$woData = [
    [
        'label' => 'Reguler',
        'value' => 85,
        'color' => '#0875d1'
    ],
    [
        'label' => 'Prioritas',
        'value' => 28,
        'color' => '#f59e0b'
    ],
    [
        'label' => 'Reject (Rework)',
        'value' => 12,
        'color' => '#dc2626'
    ]
];

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

$startDate = $_GET['tanggal_awal'] ?? date('Y-m-01');
$endDate = $_GET['tanggal_akhir'] ?? date('Y-m-d');

?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

/* ==========================================
   GENERAL
========================================== */

.production-dashboard {
    width: 100%;
    padding: 24px 28px;
    background: #f5f7fc;
    min-height: calc(100vh - 100px);
}

.production-dashboard * {
    box-sizing: border-box;
}


/* ==========================================
   HEADER DASHBOARD
========================================== */

.production-heading-info {
    min-width: 0;
}

.production-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.production-title .heading-icon {
    color: #0866c6;
    font-size: 22px;
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

.production-title h1 {
    font-size: 22px;
    font-weight: 800;
    color: #25364d;
    margin: 0;
}

/* RESPONSIVE */

@media (max-width: 650px) {
    .production-title h1 {
        font-size: 18px;
    }
}

.production-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
}

.production-heading h1 {
    font-size: 22px;
    font-weight: 800;
    color: #25364d;
    margin: 5px 0 10px;
}

.production-heading .heading-icon {
    color: #0866c6;
    font-size: 22px;
}

.production-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 12px;
}

.production-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #e8f1ff;
    color: #0866c6;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 9px;
    font-weight: 800;
}

.production-tag.teal {
    background: #d9f7f4;
    color: #087f80;
}

.production-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.production-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 12px;
    border-radius: 7px;
    border: 1px solid #dce5f1;
    background: #fff;
    color: #46566d;
    font-size: 10px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.production-btn:hover {
    background: #edf4ff;
    color: #0866c6;
}

.production-btn.primary {
    background: #0866c6;
    color: #fff;
    border-color: #0866c6;
}

.production-btn.primary:hover {
    background: #0754a5;
}


/* ==========================================
   FILTER PERIODE - FULL WIDTH
========================================== */

.production-filter {
    width: 100%;
    background: #eaf2ff;
    border: 1px solid #dce9fb;
    border-radius: 13px;
    padding: 20px 26px;
    margin-bottom: 18px;
}

.production-filter-title {
    font-size: 12px;
    font-weight: 800;
    color: #53647c;
    margin-bottom: 16px;
    text-transform: uppercase;
}

.production-filter-form {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
}

.production-filter-form input[type="date"] {
    height: 54px;
    flex: 1;
    min-width: 0;
    width: auto;
    padding: 0 15px;
    background: #fff;
    border: 1px solid #dce4f0;
    border-radius: 9px;
    color: #42536b;
    font-size: 13px;
}

.production-filter-form .separator {
    font-size: 13px;
    color: #738198;
    white-space: nowrap;
}

.production-filter-submit {
    height: 54px;
    padding: 0 28px;
    border: none;
    border-radius: 9px;
    background: #0866c6;
    color: white;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
}

.production-filter-submit:hover {
    background: #0754a5;
}

.production-today {
    height: 54px;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 0 18px;
    background: white;
    border: 1px solid #dce4f0;
    border-radius: 9px;
    color: #53647c;
    font-size: 12px;
    white-space: nowrap;
    cursor: pointer;
}


/* ==========================================
   DASHBOARD GRID
   6 KOLOM UNTUK DESKTOP
========================================== */

.production-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 16px;
}


/* ==========================================
   CARD UMUM
========================================== */

.production-card {
    background: #fff;
    border: 1px solid #e9eef6;
    border-radius: 11px;
    padding: 17px;
    box-shadow: 0 2px 8px rgba(32, 66, 110, .035);
    min-width: 0;
}

.production-card.blue-bottom {
    border-bottom: 4px solid #0875d1;
}

.production-card.green-bottom {
    border-bottom: 4px solid #07866b;
}

.production-card-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding-bottom: 12px;
    margin-bottom: 13px;
    border-bottom: 1px solid #edf1f7;
}

.production-card-heading h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #34445c;
    font-size: 12px;
    font-weight: 800;
    margin: 0;
}

.production-card-heading h3 i {
    font-size: 16px;
    color: #0866c6;
}

.production-total {
    padding: 5px 10px;
    border-radius: 20px;
    background: #e8efff;
    color: #2563c5;
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
}

.production-total.green {
    background: #d9f5f4;
    color: #087f80;
}


/* ==========================================
   KARTU WO DAN PB
========================================== */

.production-summary-card {
    grid-column: span 2;
}

.production-donut-content {
    display: flex;
    align-items: center;
    gap: 15px;
    min-height: 130px;
}

.production-donut-wrap {
    width: 115px;
    height: 115px;
    position: relative;
    flex-shrink: 0;
}

.production-donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    pointer-events: none;
}

.production-donut-center strong {
    color: #34445c;
    font-size: 23px;
    font-weight: 800;
}

.production-donut-center span {
    color: #8794a8;
    font-size: 9px;
    font-weight: 700;
}

.production-legend {
    flex: 1;
    min-width: 0;
}

.production-legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 11px 0;
    font-size: 10px;
    color: #526178;
}

.production-legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.production-legend-label {
    flex: 1;
    min-width: 0;
}

.production-legend-value {
    color: #34445c;
    font-weight: 800;
}

.production-legend-percent {
    width: 39px;
    text-align: right;
    color: #8995a8;
    font-size: 9px;
}


/* ==========================================
   RASIO PENGGUNAAN BAHAN
========================================== */

.production-ratio-card {
    grid-column: span 2;
    border-bottom: 4px solid #07866b;
}

.production-ratio-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}

.production-ratio-number {
    font-size: 34px;
    font-weight: 800;
    color: #0866c6;
    line-height: 1;
}

.production-ratio-description {
    color: #65738a;
    font-size: 10px;
    margin-top: 9px;
}

.production-efficiency {
    display: inline-block;
    background: #d7fbe9;
    color: #07834d;
    padding: 6px 9px;
    border-radius: 5px;
    font-size: 9px;
    font-weight: 800;
    vertical-align: middle;
}

.production-target {
    background: #d1fae5;
    color: #08764b;
    padding: 9px 12px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 800;
    min-width: 95px;
}

.production-ratio-detail {
    display: flex;
    flex-direction: column;
    gap: 3px;
    background: #eef4ff;
    padding: 10px 12px;
    border-radius: 8px;
    margin-top: 13px;
    color: #65738a;
    font-size: 10px;
    line-height: 1.7;
}

.production-ratio-detail strong {
    color: #0866c6;
}


/* ==========================================
   GRAFIK PRODUKSI DAN REJECT
========================================== */

.production-chart-card {
    grid-column: span 3;
    min-height: 340px;
}

.production-chart-title {
    display: flex;
    align-items: center;
    gap: 8px;
}

.production-chart-icon {
    width: 28px;
    height: 28px;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    background: #e8efff;
    color: #0866c6;
    border-radius: 6px;
    font-size: 15px;
    flex-shrink: 0;
}

.production-chart-icon.danger {
    background: #fee2e2;
    color: #dc2626;
}

.production-average {
    padding: 6px 9px;
    background: #e5edff;
    color: #315da8;
    border-radius: 7px;
    font-size: 9px;
    font-weight: 800;
    white-space: nowrap;
}

.production-average.danger {
    background: #fee2e2;
    color: #c62828;
}

.production-chart-container {
    position: relative;
    height: 245px;
    background: #f0f5ff;
    border-radius: 9px;
    padding: 12px;
}


/* ==========================================
   FOOTER
========================================== */

.production-footer {
    background: #1d2b3e;
    color: #aab6c6;
    padding: 11px 22px;
    display: flex;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
    font-size: 9px;
}

.production-footer .online {
    color: #34d399;
    font-weight: 800;
}


/* ==========================================
   RESPONSIVE TABLET
========================================== */

@media (max-width: 1100px) {

    .production-dashboard {
        padding: 20px;
    }

    .production-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .production-summary-card {
        grid-column: span 1;
    }

    .production-ratio-card {
        grid-column: span 2;
    }

    .production-chart-card {
        grid-column: span 1;
    }

    .production-donut-content {
        gap: 10px;
    }

    .production-donut-wrap {
        width: 105px;
        height: 105px;
    }

}


/* ==========================================
   RESPONSIVE MOBILE
========================================== */

@media (max-width: 650px) {

    .production-dashboard {
        padding: 15px 12px;
    }

    .production-heading {
        flex-direction: column;
    }

    .production-heading h1 {
        font-size: 18px;
    }

    .production-actions {
        width: 100%;
    }

    .production-actions button {
        flex: 1;
    }

    .production-grid {
        grid-template-columns: 1fr;
    }

    .production-summary-card,
    .production-ratio-card,
    .production-chart-card {
        grid-column: span 1;
    }

    .production-filter {
        padding: 16px;
    }

    .production-filter-form {
        flex-wrap: wrap;
    }

    .production-filter-form input[type="date"] {
        flex: 1 1 40%;
        min-width: 120px;
    }

    .production-filter-form .separator {
        display: none;
    }

    .production-filter-submit {
        flex: 1;
    }

    .production-today {
        width: 100%;
        justify-content: center;
    }

    .production-donut-content {
        gap: 15px;
    }

    .production-donut-wrap {
        width: 115px;
        height: 115px;
    }

    .production-chart-container {
        height: 220px;
    }

    .production-ratio-content {
        flex-wrap: wrap;
    }

    .production-card-heading {
        flex-wrap: wrap;
    }

}


/* ==========================================
   PRINT
========================================== */

@media print {

    body {
        background: white !important;
    }

    .production-dashboard {
        padding: 10px;
        background: white;
    }

    .production-actions,
    .production-filter {
        display: none !important;
    }

    .production-card {
        box-shadow: none;
        break-inside: avoid;
    }

    .production-footer {
        display: none;
    }

}

</style>


<!-- ==========================================
     MAIN DASHBOARD
========================================== -->

<main class="production-dashboard">


    <!-- HEADER DASHBOARD -->

    <section class="production-heading">

        <div>

            <div class="production-title">
                <div class="heading-icon">
                    <i class="bi bi-clipboard2-data-fill"></i>
                </div>

                <h1>
                    Dashboard Laporan Produksi — AQUAMATE
                </h1>
            </div>

        </div>


        <div class="production-actions">

            <button
                type="button"
                class="production-btn"
                onclick="location.reload()"
            >
                <i class="bi bi-arrow-clockwise"></i>
                Refresh Data
            </button>

            <button
                type="button"
                class="production-btn"
                onclick="exportExcel()"
            >
                <i class="bi bi-file-earmark-excel"></i>
                Export XLS
            </button>

            <button
                type="button"
                class="production-btn primary"
                onclick="window.print()"
            >
                <i class="bi bi-printer"></i>
                Print Laporan
            </button>

        </div>

    </section>


    <!-- FILTER PERIODE -->

    <section class="production-filter">

        <div class="production-filter-title">
            <i class="bi bi-calendar3"></i>
            Periode Tanggal Transaksi
        </div>

        <form method="GET" class="production-filter-form">

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

            <button
                type="submit"
                class="production-filter-submit"
            >
                Tampilkan
            </button>

            <label class="production-today">

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
         GRID KARTU DASHBOARD
    ======================================= -->

    <section class="production-grid">


        <!-- 1. WO BELUM OPTIMASI -->

        <div class="production-card production-summary-card blue-bottom">

            <div class="production-card-heading">

                <h3>
                    <i class="bi bi-clipboard-data"></i>
                    WO BELUM DIOPTIMASI
                </h3>

                <span class="production-total">
                    Total: <?= number_format($woTotal, 0, ',', '.') ?> WO
                </span>

            </div>


            <div class="production-donut-content">

                <div class="production-donut-wrap">

                    <canvas id="woChart"></canvas>

                    <div class="production-donut-center">
                        <strong><?= $woTotal ?></strong>
                        <span>WO</span>
                    </div>

                </div>


                <div class="production-legend">

                    <?php foreach ($woData as $item): ?>

                        <?php
                        $percentage = $woTotal > 0
                            ? round(($item['value'] / $woTotal) * 100)
                            : 0;
                        ?>

                        <div class="production-legend-item">

                            <span
                                class="production-legend-dot"
                                style="background: <?= $item['color'] ?>"
                            ></span>

                            <span class="production-legend-label">
                                <?= htmlspecialchars($item['label']) ?>
                            </span>

                            <span class="production-legend-value">
                                <?= $item['value'] ?>
                            </span>

                            <span class="production-legend-percent">
                                (<?= $percentage ?>%)
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>



        <!-- 2. PB DAN PENYERAHAN BAHAN -->

        <div class="production-card production-summary-card green-bottom">

            <div class="production-card-heading">

                <h3>
                    <i class="bi bi-box-seam"></i>
                    PERMINTAAN & PENYERAHAN BAHAN
                </h3>

                <span class="production-total green">
                    Total: <?= number_format($pbTotal, 0, ',', '.') ?> PB
                </span>

            </div>


            <div class="production-donut-content">

                <div class="production-donut-wrap">

                    <canvas id="pbChart"></canvas>

                    <div class="production-donut-center">
                        <strong><?= $pbTotal ?></strong>
                        <span>PB</span>
                    </div>

                </div>


                <div class="production-legend">

                    <?php foreach ($pbData as $item): ?>

                        <?php
                        $percentage = $pbTotal > 0
                            ? round(($item['value'] / $pbTotal) * 100)
                            : 0;
                        ?>

                        <div class="production-legend-item">

                            <span
                                class="production-legend-dot"
                                style="background: <?= $item['color'] ?>"
                            ></span>

                            <span class="production-legend-label">
                                <?= htmlspecialchars($item['label']) ?>
                            </span>

                            <span class="production-legend-value">
                                <?= $item['value'] ?>
                            </span>

                            <span class="production-legend-percent">
                                (<?= $percentage ?>%)
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>



        <!-- 3. RASIO PENGGUNAAN BAHAN -->

        <div class="production-card production-ratio-card">

            <div class="production-card-heading">

                <h3>
                    <i class="bi bi-calculator"></i>
                    RASIO PENGGUNAAN BAHAN
                </h3>

                <span class="production-target">
                    Target ≤ 1.20
                </span>

            </div>


            <div class="production-ratio-content">

                <div>

                    <div>

                        <span class="production-ratio-number">
                            1.15
                        </span>

                        <span class="production-efficiency">
                            <i class="bi bi-check-circle"></i>
                            Efisien (Yield 87.3%)
                        </span>

                    </div>

                    <div class="production-ratio-description">
                        Rasio = Luas Bahan (m²) ÷ Luas Potong (m²)
                    </div>

                </div>

            </div>


            <div class="production-ratio-detail">

                <div>
                    Jml Bahan:
                    <strong>15,450.5 m²</strong>
                </div>

                <div>
                    Jml Potong:
                    <strong>13,469.8 m²</strong>
                </div>

                <div>
                    Offcut/Trim:
                    <strong>1,963.1 m²</strong>
                </div>

            </div>

        </div>



        <!-- 4. GRAFIK PENCAPAIAN PRODUKSI -->

        <div class="production-card production-chart-card">

            <div class="production-card-heading">

                <h3 class="production-chart-title">

                    <span class="production-chart-icon">
                        <i class="bi bi-bar-chart-fill"></i>
                    </span>

                    Grafik % Pencapaian Produksi / Kapasitas

                </h3>

                <span class="production-average">
                    Rata-rata: 87.8%
                </span>

            </div>


            <div class="production-chart-container">

                <canvas id="productionChart"></canvas>

            </div>

        </div>



        <!-- 5. GRAFIK REJECT MESIN -->

        <div class="production-card production-chart-card">

            <div class="production-card-heading">

                <h3 class="production-chart-title">

                    <span class="production-chart-icon danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </span>

                    Grafik % Reject Mesin

                </h3>

                <span class="production-average danger">
                    Rata-rata: 2.0%
                </span>

            </div>


            <div class="production-chart-container">

                <canvas id="rejectChart"></canvas>

            </div>

        </div>


    </section>

</main>

<!-- ======================================
     JAVASCRIPT
======================================= -->

<script>

// ======================================
// DATA DUMMY
// ======================================

const woData = <?= json_encode(array_column($woData, 'value')) ?>;

const woColors = <?= json_encode(array_column($woData, 'color')) ?>;

const pbData = <?= json_encode(array_column($pbData, 'value')) ?>;

const pbColors = <?= json_encode(array_column($pbData, 'color')) ?>;


// ======================================
// KONFIGURASI CHART
// ======================================

Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.font.size = 10;
Chart.defaults.color = '#65738a';


// ======================================
// FUNGSI DONUT CHART
// ======================================

function createDonutChart(elementId, values, colors) {

    const canvas = document.getElementById(elementId);

    new Chart(canvas, {

        type: 'doughnut',

        data: {

            datasets: [{

                data: values,

                backgroundColor: colors,

                borderWidth: 0,

                hoverOffset: 5

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
                    enabled: true
                }

            }

        }

    });

}


// Jalankan donut chart
createDonutChart('woChart', woData, woColors);

createDonutChart('pbChart', pbData, pbColors);


// ======================================
// GRAFIK PENCAPAIAN PRODUKSI
// ======================================

new Chart(document.getElementById('productionChart'), {

    type: 'bar',

    data: {

        labels: [
            'POTONG',
            'SE',
            'DE',
            'WATERJET',
            'TEMP'
        ],

        datasets: [{

            label: 'Pencapaian Produksi',

            data: [92.5, 79.4, 86.3, 82.1, 95.2],

            backgroundColor: '#0866c6',

            borderRadius: 3,

            barPercentage: 0.58

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                display: false
            }

        },

        scales: {

            y: {

                min: 0,

                max: 100,

                ticks: {

                    callback: function(value) {
                        return value + '%';
                    }

                },

                grid: {
                    color: '#dfe8f6',
                    borderDash: [3, 3]
                }

            },

            x: {

                grid: {
                    display: false
                }

            }

        }

    }

});


// ======================================
// GRAFIK REJECT MESIN
// ======================================

new Chart(document.getElementById('rejectChart'), {

    type: 'bar',

    data: {

        labels: [
            'POTONG',
            'SE',
            'DE',
            'WATERJET',
            'TEMP'
        ],

        datasets: [{

            label: 'Persentase Reject',

            data: [0.3, 1.4, 1.5, 2.3, 0.7],

            backgroundColor: [
                '#07845e',
                '#e58a00',
                '#e58a00',
                '#bd2020',
                '#07845e'
            ],

            borderRadius: 3,

            barPercentage: 0.58

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                display: false
            }

        },

        scales: {

            y: {

                min: 0,

                max: 3,

                ticks: {

                    callback: function(value) {
                        return value + '%';
                    }

                },

                grid: {
                    color: '#dfe8f6',
                    borderDash: [3, 3]
                }

            },

            x: {

                grid: {
                    display: false
                }

            }

        }

    }

});


// ======================================
// FILTER HARI INI
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
// EXPORT EXCEL SEMENTARA
// ======================================

function exportExcel() {

    alert(
        'Fitur Export Excel akan diaktifkan setelah data produksi terhubung dengan database.'
    );

}

</script>


<?php
require_once '../includes/footer.php';
?>