
<?php
/*
|--------------------------------------------------------------------------
| POPUP CARD 2 - PERMINTAAN & PENYERAHAN BAHAN
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../koneksi.php';

/*
|--------------------------------------------------------------------------
| A. ENDPOINT AJAX
|--------------------------------------------------------------------------
*/

if (isset($_GET['action'])) {

    header('Content-Type: application/json; charset=utf-8');

    try {

        /*
        |--------------------------------------------------------------------------
        | A1. DETAIL TRANSAKSI SB BERDASARKAN PB
        |--------------------------------------------------------------------------
        */

        if ($_GET['action'] === 'detail_sb') {

            $noPB = trim($_GET['no_pb'] ?? '');

            if ($noPB === '') {
                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' => 'Nomor PB tidak valid.'
                ]);

                exit;
            }

            $sqlDetail = "
                SELECT
                    sb.no_sb,
                    sb.tgl_trans,
                    sb.no_pb,
                    sb.id_barang,
                    mb.nama_barang,
                    sb.keluar
                FROM serah_barang sb

                LEFT JOIN master_barang mb
                    ON mb.id_barang = sb.id_barang

                WHERE sb.no_pb = :no_pb

                ORDER BY
                    sb.tgl_trans ASC,
                    sb.no_sb ASC
            ";

            $stmt = $conn->prepare($sqlDetail);
            $stmt->execute([
                ':no_pb' => $noPB
            ]);

            $detailSB = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'no_pb' => $noPB,
                'total' => count($detailSB),
                'data' => $detailSB
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | A2. DAFTAR PB BERDASARKAN KATEGORI
        |--------------------------------------------------------------------------
        */

        if ($_GET['action'] === 'detail_pb') {

            $kategori = trim($_GET['kategori'] ?? '');
            $search = trim($_GET['search'] ?? '');

            $kategoriValid = [
                'Sudah Seluruhnya',
                'Parsial / Sebagian',
                'Belum Diserahkan'
            ];

            if (!in_array($kategori, $kategoriValid, true)) {

                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' => 'Kategori PB tidak valid.'
                ]);

                exit;
            }

            $tanggalAwal = $_GET['tanggal_awal'] ?? date('Y-m-01');
            $tanggalAkhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalAwal)) {
                $tanggalAwal = date('Y-m-01');
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalAkhir)) {
                $tanggalAkhir = date('Y-m-d');
            }

            if ($tanggalAwal > $tanggalAkhir) {
                [$tanggalAwal, $tanggalAkhir] = [
                    $tanggalAkhir,
                    $tanggalAwal
                ];
            }

            /*
             * Subquery SB:
             * Menghitung total barang keluar untuk setiap PB.
             */

            $sqlPB = "
                SELECT
                    pb.no_pb,
                    pb.tgl_trans,
                    pb.jumlah_pb,

                    COALESCE(sb.total_keluar, 0) AS jumlah_keluar,

                    GREATEST(
                        pb.jumlah_pb - COALESCE(sb.total_keluar, 0),
                        0
                    ) AS sisa,

                    COALESCE(mb_pb.nama_barang, '-') AS nama_barang,

                    CASE
                        WHEN COALESCE(sb.total_keluar, 0) = 0
                            THEN 'Belum Diserahkan'

                        WHEN COALESCE(sb.total_keluar, 0) < pb.jumlah_pb
                            THEN 'Parsial / Sebagian'

                        ELSE 'Sudah Seluruhnya'
                    END AS status_pb

                FROM permintaan_bahan pb

                LEFT JOIN master_barang mb_pb
                    ON mb_pb.id_barang = pb.id_barang

                LEFT JOIN (
                    SELECT
                        sb.no_pb,
                        SUM(COALESCE(sb.keluar, 0)) AS total_keluar,

                        GROUP_CONCAT(
                            DISTINCT mb.nama_barang
                            ORDER BY mb.nama_barang
                            SEPARATOR ', '
                        ) AS nama_barang

                    FROM serah_barang sb

                    LEFT JOIN master_barang mb
                        ON mb.id_barang = sb.id_barang

                    GROUP BY sb.no_pb

                ) sb ON sb.no_pb = pb.no_pb

                WHERE
                    DATE(pb.tgl_trans)
                    BETWEEN :tanggal_awal AND :tanggal_akhir

                    AND

                    CASE
                        WHEN COALESCE(sb.total_keluar, 0) = 0
                            THEN 'Belum Diserahkan'

                        WHEN COALESCE(sb.total_keluar, 0) < pb.jumlah_pb
                            THEN 'Parsial / Sebagian'

                        ELSE 'Sudah Seluruhnya'
                    END = :kategori

                    AND (
                        :search_pb = ''

                        OR pb.no_pb LIKE :search_no_pb

                        OR EXISTS (
                            SELECT 1
                            FROM serah_barang sb_search
                            WHERE
                                sb_search.no_pb = pb.no_pb
                                AND sb_search.no_sb LIKE :search_no_sb
                        )
                    )

                ORDER BY pb.tgl_trans DESC, pb.no_pb DESC
            ";

            $stmt = $conn->prepare($sqlPB);

            $keyword = '%' . $search . '%';

            $stmt->execute([
                ':tanggal_awal' => $tanggalAwal,
                ':tanggal_akhir' => $tanggalAkhir,
                ':kategori' => $kategori,
                ':search_pb' => $search,
                ':search_no_pb' => $keyword,
                ':search_no_sb' => $keyword
            ]);

            $dataPB = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'kategori' => $kategori,
                'total' => count($dataPB),
                'data' => $dataPB
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Aksi tidak dikenali.'
        ]);

        exit;

    } catch (PDOException $e) {

        error_log('Popup Card 2: ' . $e->getMessage());

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengambil data Permintaan Bahan.'
        ]);

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| B. HTML MODAL
|--------------------------------------------------------------------------
*/

?>

<div id="pbDetailModal" class="pb-modal">

    <div
        class="pb-modal-backdrop"
        onclick="closePBModal()"
    ></div>

    <div class="pb-modal-content">

        <!-- HEADER -->
        <div class="pb-modal-header">

            <div>
                <h3 id="pbModalTitle">
                    Detail Permintaan Bahan
                </h3>

                <p id="pbModalSubtitle">
                    Daftar Permintaan dan Penyerahan Bahan
                </p>
            </div>

            <button
                type="button"
                class="pb-modal-close"
                onclick="closePBModal()"
                aria-label="Tutup popup"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>


        <!-- RINGKASAN -->
        <div class="pb-modal-summary">

            <div class="pb-modal-summary-item">

                <span>Total Permintaan Bahan</span>

                <strong id="pbModalTotal">0</strong>

            </div>

            <div class="pb-modal-summary-item">

                <span>Kategori</span>

                <strong id="pbModalCategory">-</strong>

            </div>

        </div>


        <!-- PENCARIAN -->
        <div class="pb-modal-search">

            <div class="pb-search-input">

                <i class="bi bi-search"></i>

                <input
                    type="text"
                    id="pbSearchInput"
                    placeholder="Cari Nomor PB atau Nomor SB..."
                    autocomplete="off"
                >

            </div>

            <button
                type="button"
                class="pb-search-button"
                onclick="searchPB()"
            >
                Cari
            </button>

            <button
                type="button"
                class="pb-search-reset"
                onclick="resetPBSearch()"
            >
                Reset
            </button>

        </div>


        <!-- TABEL UTAMA -->
        <div class="pb-modal-table-wrapper">

            <div
                id="pbModalLoading"
                class="pb-modal-loading"
                style="display: none;"
            >

                <div class="spinner-border text-success"></div>

                <p>Memuat data Permintaan Bahan...</p>

            </div>

            <div
                id="pbModalTableContainer"
                class="pb-modal-table-container"
            ></div>

        </div>


        <!-- DETAIL SB -->
        <div
            id="pbSBDetailSection"
            class="pb-sb-detail-section"
            style="display: none;"
        >

            <div class="pb-sb-detail-heading">

                <div>

                    <h4>
                        <i class="bi bi-box-seam"></i>
                        Detail Penyerahan Barang
                    </h4>

                    <span id="pbSBDetailSubtitle">
                        Riwayat transaksi SB
                    </span>

                </div>

                <button
                    type="button"
                    class="pb-sb-detail-close"
                    onclick="closePBDetail()"
                >
                    <i class="bi bi-x-lg"></i>
                </button>

            </div>

            <div
                id="pbSBDetailLoading"
                class="pb-modal-loading"
                style="display: none;"
            >

                <div class="spinner-border text-success"></div>

                <p>Memuat detail transaksi SB...</p>

            </div>

            <div
                id="pbSBDetailContainer"
                class="pb-sb-detail-container"
            ></div>

        </div>


        <!-- FOOTER -->
        <div class="pb-modal-footer">

            <span id="pbModalInfo">
                Data Permintaan Bahan
            </span>

            <button
                type="button"
                class="pb-modal-footer-close"
                onclick="closePBModal()"
            >
                Tutup
            </button>

        </div>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| C. JAVASCRIPT POPUP CARD 2
|--------------------------------------------------------------------------
*/

const pbDetailEndpoint = 'POPCARD2.php';

const pbTanggalAwal = <?= json_encode($startDate ?? date('Y-m-01')) ?>;

const pbTanggalAkhir = <?= json_encode($endDate ?? date('Y-m-d')) ?>;

let currentPBCategory = '';


/*
|--------------------------------------------------------------------------
| MEMBUKA POPUP PB
|--------------------------------------------------------------------------
*/

async function openPBDetail(kategori) {

    const modal = document.getElementById('pbDetailModal');

    if (!modal) return;

    currentPBCategory = kategori;

    modal.classList.add('show');

    document.body.style.overflow = 'hidden';

    document.getElementById('pbModalTitle').textContent =
        'Detail Permintaan Bahan';

    document.getElementById('pbModalSubtitle').textContent =
        'Daftar PB kategori ' + kategori;

    document.getElementById('pbModalTotal').textContent = '0';

    document.getElementById('pbModalCategory').textContent = kategori;

    document.getElementById('pbModalInfo').textContent =
        'Sedang mengambil data...';

    document.getElementById('pbSearchInput').value = '';

    closePBDetail();

    await loadPBData('');
}


/*
|--------------------------------------------------------------------------
| MENGAMBIL DATA PB
|--------------------------------------------------------------------------
*/

async function loadPBData(search = '') {

    const loading = document.getElementById('pbModalLoading');

    const container = document.getElementById('pbModalTableContainer');

    const info = document.getElementById('pbModalInfo');

    loading.style.display = 'flex';

    container.innerHTML = '';

    closePBDetail();

    try {

        const params = new URLSearchParams({

            action: 'detail_pb',

            kategori: currentPBCategory,

            search: search,

            tanggal_awal: pbTanggalAwal,

            tanggal_akhir: pbTanggalAkhir

        });

        const response = await fetch(
            pbDetailEndpoint + '?' + params.toString(),
            {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        const result = await response.json();

        loading.style.display = 'none';

        if (!response.ok || !result.success) {

            throw new Error(
                result.message || 'Gagal mengambil data PB.'
            );

        }

        document.getElementById('pbModalTotal').textContent =
            Number(result.total).toLocaleString('id-ID');

        document.getElementById('pbModalCategory').textContent =
            result.kategori;

        info.textContent =
            'Menampilkan ' +
            Number(result.total).toLocaleString('id-ID') +
            ' Permintaan Bahan';

        if (!result.data || result.data.length === 0) {

            container.innerHTML = `
                <div class="pb-modal-empty">

                    <i class="bi bi-inbox"></i>

                    <strong>Data tidak ditemukan</strong>

                    <span>
                        Tidak ada Permintaan Bahan pada kategori ini.
                    </span>

                </div>
            `;

            return;
        }

        renderPBTable(result.data);

    } catch (error) {

        loading.style.display = 'none';

        container.innerHTML = `
            <div class="pb-modal-error">

                <i class="bi bi-exclamation-circle"></i>

                <strong>Gagal memuat data</strong>

                <span>${escapePBHTML(error.message)}</span>

            </div>
        `;

        info.textContent = 'Terjadi kesalahan saat mengambil data.';

        console.error('Popup PB:', error);

    }

}


/*
|--------------------------------------------------------------------------
| RENDER TABEL PB
|--------------------------------------------------------------------------
*/

function renderPBTable(data) {

    const container = document.getElementById('pbModalTableContainer');

    let html = `

        <table class="pb-detail-table">

            <thead>

                <tr>
                    <th>No.</th>
                    <th>No. PB</th>
                    <th>Tanggal Transaksi</th>
                    <th>Nama Barang</th>
                    <th>Jumlah PB</th>
                    <th>Jumlah Keluar</th>
                    <th>Sisa</th>
                    <th>Detail</th>
                </tr>

            </thead>

            <tbody>

    `;

    data.forEach((row, index) => {

        const tanggalTransaksi = row.tgl_trans
            ? new Date(row.tgl_trans + 'T00:00:00')
                .toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                })
            : '-';

        const jumlahPB = Number(row.jumlah_pb) || 0;

        const jumlahKeluar = Number(row.jumlah_keluar) || 0;

        const sisa = Number(row.sisa) || 0;

        html += `

            <tr>

                <td>${index + 1}</td>

                <td>
                    <strong class="pb-number">
                        ${escapePBHTML(row.no_pb)}
                    </strong>
                </td>

                <td>
                    ${escapePBHTML(tanggalTransaksi)}
                </td>

                <td class="pb-item-name">
                    ${escapePBHTML(row.nama_barang || '-')}
                </td>

                <td>${jumlahPB.toLocaleString('id-ID')}</td>

                <td class="pb-quantity-out">
                    ${jumlahKeluar.toLocaleString('id-ID')}
                </td>

                <td>
                    <span class="pb-remaining">
                        ${sisa.toLocaleString('id-ID')}
                    </span>
                </td>

                <td>

                    <button
                        type="button"
                        class="pb-detail-button"
                        onclick="loadSBDetail('${escapePBJS(row.no_pb)}')"
                    >
                        <i class="bi bi-eye"></i>
                        Lihat Detail
                    </button>

                </td>

            </tr>

        `;

    });

    html += `

            </tbody>

        </table>

    `;

    container.innerHTML = html;

}


/*
|--------------------------------------------------------------------------
| DETAIL TRANSAKSI SB
|--------------------------------------------------------------------------
*/

async function loadSBDetail(noPB) {

    const section = document.getElementById('pbSBDetailSection');

    const loading = document.getElementById('pbSBDetailLoading');

    const container = document.getElementById('pbSBDetailContainer');

    const subtitle = document.getElementById('pbSBDetailSubtitle');

    section.style.display = 'block';

    loading.style.display = 'flex';

    container.innerHTML = '';

    subtitle.textContent = 'Nomor PB: ' + noPB;

    try {

        const params = new URLSearchParams({

            action: 'detail_sb',

            no_pb: noPB

        });

        const response = await fetch(
            pbDetailEndpoint + '?' + params.toString(),
            {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        const result = await response.json();

        loading.style.display = 'none';

        if (!response.ok || !result.success) {

            throw new Error(
                result.message || 'Gagal mengambil detail SB.'
            );

        }

        if (!result.data || result.data.length === 0) {

            container.innerHTML = `

                <div class="pb-modal-empty">

                    <i class="bi bi-inbox"></i>

                    <strong>Belum ada transaksi SB</strong>

                    <span>
                        PB ini belum memiliki transaksi penyerahan barang.
                    </span>

                </div>

            `;

            section.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });

            return;

        }

        renderSBTable(result.data);

        section.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });

    } catch (error) {

        loading.style.display = 'none';

        container.innerHTML = `

            <div class="pb-modal-error">

                <i class="bi bi-exclamation-circle"></i>

                <strong>Gagal memuat detail SB</strong>

                <span>${escapePBHTML(error.message)}</span>

            </div>

        `;

    }

}


/*
|--------------------------------------------------------------------------
| RENDER TABEL SB
|--------------------------------------------------------------------------
*/

function renderSBTable(data) {

    const container = document.getElementById('pbSBDetailContainer');

    let totalKeluar = 0;

    let html = `

        <table class="pb-detail-table pb-sb-table">

            <thead>

                <tr>
                    <th>No.</th>
                    <th>No. SB</th>
                    <th>Tanggal Transaksi</th>
                    <th>Nama Barang</th>
                    <th>ID Barang</th>
                    <th>Jumlah Keluar</th>
                </tr>

            </thead>

            <tbody>

    `;

    data.forEach((row, index) => {

        const tanggalTransaksi = row.tgl_trans
            ? new Date(row.tgl_trans + 'T00:00:00')
                .toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                })
            : '-';

        const keluar = Number(row.keluar) || 0;

        totalKeluar += keluar;

        html += `

            <tr>

                <td>${index + 1}</td>

                <td>
                    <strong class="pb-number">
                        ${escapePBHTML(row.no_sb)}
                    </strong>
                </td>

                <td>
                    ${escapePBHTML(tanggalTransaksi)}
                </td>

                <td>${escapePBHTML(row.nama_barang || '-')}</td>

                <td>${escapePBHTML(row.id_barang || '-')}</td>

                <td class="pb-quantity-out">
                    ${keluar.toLocaleString('id-ID')}
                </td>

            </tr>

        `;

    });

    html += `

            </tbody>

            <tfoot>

                <tr>

                    <td colspan="5">
                        Total Jumlah Keluar
                    </td>

                    <td>
                        ${totalKeluar.toLocaleString('id-ID')}
                    </td>

                </tr>

            </tfoot>

        </table>

    `;

    container.innerHTML = html;

}


/*
|--------------------------------------------------------------------------
| PENCARIAN PB DAN SB
|--------------------------------------------------------------------------
*/

function searchPB() {

    const search = document
        .getElementById('pbSearchInput')
        .value
        .trim();

    loadPBData(search);

}

function resetPBSearch() {

    document.getElementById('pbSearchInput').value = '';

    loadPBData('');

}

document.getElementById('pbSearchInput')
    .addEventListener('keydown', function(event) {

        if (event.key === 'Enter') {

            event.preventDefault();

            searchPB();

        }

    });


/*
|--------------------------------------------------------------------------
| TUTUP DETAIL SB
|--------------------------------------------------------------------------
*/

function closePBDetail() {

    document.getElementById('pbSBDetailSection').style.display = 'none';

    document.getElementById('pbSBDetailContainer').innerHTML = '';

}


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function escapePBHTML(value) {

    const element = document.createElement('span');

    element.textContent = String(value ?? '');

    return element.innerHTML;

}


/*
|--------------------------------------------------------------------------
| ESCAPE VALUE UNTUK INLINE JAVASCRIPT
|--------------------------------------------------------------------------
*/

function escapePBJS(value) {

    return String(value ?? '')
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '\\r');

}


/*
|--------------------------------------------------------------------------
| TUTUP MODAL PB
|--------------------------------------------------------------------------
*/

function closePBModal() {

    const modal = document.getElementById('pbDetailModal');

    if (!modal) return;

    modal.classList.remove('show');

    document.body.style.overflow = '';

    closePBDetail();

}


// Tutup popup menggunakan tombol ESC
document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {

        closePBModal();

    }

});

</script>


<style>

/* ==================================================
   POPUP CARD 2 - PB DAN SERAH BARANG
================================================== */

.pb-modal {
    position: fixed;
    inset: 0;
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.pb-modal.show {
    display: flex;
}

.pb-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.58);
    backdrop-filter: blur(4px);
}

.pb-modal-content {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;

    width: 100%;
    max-width: 1250px;

    height: 90vh;
    max-height: 90vh;
    min-height: 0;

    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);

    overflow: hidden;
}

@keyframes pbModalAppear {

    from {
        opacity: 0;
        transform: translateY(12px) scale(0.99);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

}


/* HEADER */

.pb-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    padding: 22px 26px;
    border-bottom: 1px solid #e8edf4;
}

.pb-modal-header h3 {
    margin: 0;
    color: #172b4d;
    font-size: 19px;
    font-weight: 700;
}

.pb-modal-header p {
    margin: 5px 0 0;
    color: #718096;
    font-size: 13px;
}

.pb-modal-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 9px;
    background: #f1f5f9;
    color: #526176;
    cursor: pointer;
}

.pb-modal-close:hover {
    background: #fee2e2;
    color: #dc2626;
}


/* SUMMARY */

.pb-modal-summary {
    display: flex;
    gap: 14px;
    padding: 16px 26px;
    background: #f8fafc;
    border-bottom: 1px solid #e8edf4;
}

.pb-modal-summary-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 150px;
}

.pb-modal-summary-item span {
    color: #718096;
    font-size: 12px;
}

.pb-modal-summary-item strong {
    color: #1e293b;
    font-size: 17px;
    font-weight: 700;
}


/* SEARCH */

.pb-modal-search {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 16px 26px;
    border-bottom: 1px solid #e8edf4;
}

.pb-search-input {
    position: relative;
    flex: 1;
}

.pb-search-input i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
}

.pb-search-input input {
    width: 100%;
    height: 39px;
    padding: 0 12px 0 36px;
    border: 1px solid #dce4ee;
    border-radius: 8px;
    outline: none;
    color: #334155;
    font-size: 12px;
}

.pb-search-input input:focus {
    border-color: #07845e;
    box-shadow: 0 0 0 3px rgba(7, 132, 94, 0.08);
}

.pb-search-button,
.pb-search-reset {
    height: 39px;
    padding: 0 17px;
    border: 0;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.pb-search-button {
    background: #07845e;
    color: #ffffff;
}

.pb-search-button:hover {
    background: #066c4d;
}

.pb-search-reset {
    background: #eef2f7;
    color: #526176;
}

.pb-search-reset:hover {
    background: #e2e8f0;
}


/* TABEL */

.pb-modal-table-wrapper {
    position: relative;

    flex: 1 1 0;
    min-height: 100px;

    overflow: auto;
}


.pb-modal-table-container {
    width: 100%;
    min-height: 0;
    overflow: auto;
}

.pb-sb-detail-container {
    flex: 1 1 auto;

    min-height: 0;
    max-height: 220px;

    overflow: auto;
}

.pb-detail-table {
    width: 100%;
    min-width: 850px;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.pb-detail-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    padding: 13px 15px;
    background: #eff5fc;
    color: #334155;
    text-align: center;
    white-space: nowrap;
    font-weight: 700;
    border-bottom: 1px solid #dbe5f0;
}

.pb-detail-table td {
    padding: 12px 15px;
    color: #475569;
    text-align: center;
    white-space: nowrap;
    border-bottom: 1px solid #edf1f5;
}

.pb-detail-table tbody tr:hover {
    background: #f8fbff;
}

.pb-detail-table tfoot td {
    background: #f0fdf4;
    color: #166534;
    font-weight: 700;
    border-top: 1px solid #bbf7d0;
}

.pb-number {
    color: #0875b9;
    font-weight: 700;
}

.pb-item-name {
    max-width: 280px;
    white-space: normal !important;
    text-align: left !important;
    line-height: 1.5;
}

.pb-quantity-out {
    color: #07845e !important;
    font-weight: 700;
}

.pb-remaining {
    display: inline-block;
    min-width: 35px;
    padding: 4px 9px;
    border-radius: 6px;
    background: #fff1db;
    color: #b76a00;
    font-weight: 700;
}

.pb-detail-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 11px;
    border: 0;
    border-radius: 7px;
    background: #e5f3ff;
    color: #0875b9;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.pb-detail-button:hover {
    background: #cce8ff;
}


/* DETAIL SB */

.pb-sb-detail-section {
    flex: 0 1 auto;

    max-height: 30vh;
    min-height: 0;

    margin: 0 26px 12px;

    border: 1px solid #dce8e2;
    border-radius: 10px;

    overflow: hidden;
    background: #ffffff;
}

.pb-sb-detail-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 14px 17px;
    background: #f0fdf4;
    border-bottom: 1px solid #dce8e2;
}

.pb-sb-detail-heading h4 {
    margin: 0;
    color: #166534;
    font-size: 13px;
    font-weight: 700;
}

.pb-sb-detail-heading h4 i {
    margin-right: 5px;
}

.pb-sb-detail-heading span {
    display: block;
    margin-top: 4px;
    color: #718096;
    font-size: 11px;
}

.pb-sb-detail-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border: 0;
    border-radius: 7px;
    background: #dcfce7;
    color: #166534;
    cursor: pointer;
}

.pb-sb-detail-close:hover {
    background: #bbf7d0;
}

.pb-sb-table {
    min-width: 750px;
}

.pb-sb-table th {
    background: #f7fcf8;
}

.pb-sb-detail-container {
    max-height: 260px;
}


/* LOADING DAN EMPTY */

.pb-modal-loading,
.pb-modal-empty,
.pb-modal-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 180px;
    padding: 25px;
    text-align: center;
}

.pb-modal-loading p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
}

.pb-modal-empty i,
.pb-modal-error i {
    font-size: 32px;
}

.pb-modal-empty i {
    color: #94a3b8;
}

.pb-modal-error i {
    color: #dc2626;
}

.pb-modal-empty strong,
.pb-modal-error strong {
    color: #334155;
    font-size: 14px;
}

.pb-modal-empty span,
.pb-modal-error span {
    color: #64748b;
    font-size: 12px;
}


/* FOOTER */

.pb-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 15px 26px;
    border-top: 1px solid #e8edf4;
}

.pb-modal-footer span {
    color: #718096;
    font-size: 12px;
}

.pb-modal-footer-close {
    padding: 9px 20px;
    border: 0;
    border-radius: 8px;
    background: #07845e;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.pb-modal-footer-close:hover {
    background: #066c4d;
}


/* RESPONSIVE */

@media (max-width: 650px) {

    .pb-modal {
        padding: 10px;
    }

    .pb-modal-content {
        max-height: 94vh;
        border-radius: 12px;
    }

    .pb-modal-header {
        padding: 17px;
    }

    .pb-modal-header h3 {
        font-size: 16px;
    }

    .pb-modal-summary {
        padding: 13px 17px;
    }

    .pb-modal-search {
        flex-wrap: wrap;
        padding: 13px 17px;
    }

    .pb-search-input {
        flex-basis: 100%;
    }

    .pb-sb-detail-section {
        margin: 0 12px 14px;
    }

    .pb-modal-footer {
        padding: 13px 17px;
    }

}

</style>
