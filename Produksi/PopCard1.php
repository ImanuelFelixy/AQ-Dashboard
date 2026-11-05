<?php
/*
|--------------------------------------------------------------------------
| POPUP CARD 1 - DETAIL WO BELUM DIOPTIMASI
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../koneksi.php';

/*
|--------------------------------------------------------------------------
| A. ENDPOINT AJAX DETAIL WORK ORDER
|--------------------------------------------------------------------------
*/

if (isset($_GET['action']) && $_GET['action'] === 'detail_wo') {

    header('Content-Type: application/json; charset=utf-8');

    $kategori = $_GET['kategori'] ?? '';
    $tanggalAwal = $_GET['tanggal_awal'] ?? date('Y-m-01');
    $tanggalAkhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

    $kategoriValid = [
        'Reguler',
        'Prioritas',
        'Reject'
    ];

    if (!in_array($kategori, $kategoriValid, true)) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Kategori Work Order tidak valid.'
        ]);

        exit;
    }

    try {

        $sqlDetailWO = "
            SELECT
                wo.*,

                GREATEST(
                    (
                        COALESCE(wo.qty, 0)
                        + COALESCE(p.total_reject, 0)
                    ) - COALESCE(wo.qty_optimasi, 0),
                    0
                ) AS sisa_optimasi

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
                    END
                ) = :kategori

                AND

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

            ORDER BY wo.no_wo ASC
        ";

        $stmt = $conn->prepare($sqlDetailWO);

        $stmt->execute([
            ':kategori' => $kategori,
            ':tanggal_awal' => $tanggalAwal,
            ':tanggal_akhir' => $tanggalAkhir
        ]);

        $detailWO = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'kategori' => $kategori,
            'total' => count($detailWO),
            'data' => $detailWO
        ], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {

        error_log('Popup Card 1: ' . $e->getMessage());

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengambil detail Work Order.'
        ]);
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| B. HTML MODAL
|--------------------------------------------------------------------------
*/
?>

<div id="woDetailModal" class="wo-modal">

    <div
        class="wo-modal-backdrop"
        onclick="closeWOModal()"
    ></div>

    <div class="wo-modal-content">

        <!-- HEADER MODAL -->
        <div class="wo-modal-header">

            <div>
                <h3 id="woModalTitle">
                    Detail Work Order
                </h3>

                <p id="woModalSubtitle">
                    Daftar Work Order belum dioptimasi
                </p>
            </div>

            <button
                type="button"
                class="wo-modal-close"
                onclick="closeWOModal()"
                aria-label="Tutup popup"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>

        <!-- RINGKASAN -->
        <div class="wo-modal-summary">

            <div class="wo-modal-summary-item">
                <span>Total Work Order</span>
                <strong id="woModalTotal">0</strong>
            </div>

            <div class="wo-modal-summary-item">
                <span>Kategori</span>
                <strong id="woModalCategory">-</strong>
            </div>

        </div>

        <!-- TABEL -->
        <div class="wo-modal-table-wrapper">

            <div
                id="woModalLoading"
                class="wo-modal-loading"
                style="display: none;"
            >
                <div class="spinner-border text-primary"></div>
                <p>Memuat data Work Order...</p>
            </div>

            <div
                id="woModalTableContainer"
                class="wo-modal-table-container"
            ></div>

        </div>

        <!-- FOOTER -->
        <div class="wo-modal-footer">

            <span id="woModalInfo">
                Data Work Order
            </span>

            <button
                type="button"
                class="wo-modal-footer-close"
                onclick="closeWOModal()"
            >
                Tutup
            </button>

        </div>

    </div>

</div>

<script>
/*
|--------------------------------------------------------------------------
| C. JAVASCRIPT POPUP CARD 1
|--------------------------------------------------------------------------
*/

const woDetailEndpoint = 'POPCARD1.php';

async function openWODetail(kategori) {

    const modal = document.getElementById('woDetailModal');
    const loading = document.getElementById('woModalLoading');
    const tableContainer = document.getElementById('woModalTableContainer');

    const title = document.getElementById('woModalTitle');
    const subtitle = document.getElementById('woModalSubtitle');
    const total = document.getElementById('woModalTotal');
    const category = document.getElementById('woModalCategory');
    const info = document.getElementById('woModalInfo');

    if (!modal) return;

    // Tampilkan modal
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';

    // Atur informasi modal
    title.textContent = 'Detail WO - ' + kategori;
    subtitle.textContent = 'Daftar Work Order kategori ' + kategori;

    total.textContent = '0';
    category.textContent = kategori;
    info.textContent = 'Sedang mengambil data...';

    tableContainer.innerHTML = '';
    loading.style.display = 'flex';

    try {

        const params = new URLSearchParams({
            action: 'detail_wo',
            kategori: kategori,
            tanggal_awal: document.getElementById('tanggalAwal').value,
            tanggal_akhir: document.getElementById('tanggalAkhir').value
        });

        const response = await fetch(
            woDetailEndpoint + '?' + params.toString(),
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
                result.message || 'Gagal mengambil data Work Order.'
            );
        }

        total.textContent = Number(result.total).toLocaleString('id-ID');
        category.textContent = result.kategori;

        info.textContent =
            'Menampilkan ' +
            Number(result.total).toLocaleString('id-ID') +
            ' Work Order';

        if (!result.data || result.data.length === 0) {

            tableContainer.innerHTML = `
                <div class="wo-modal-empty">
                    <i class="bi bi-inbox"></i>
                    <strong>Data tidak ditemukan</strong>
                    <span>Tidak ada Work Order pada kategori ini.</span>
                </div>
            `;

            return;
        }

        renderWOTable(result.data);

    } catch (error) {

        loading.style.display = 'none';

        tableContainer.innerHTML = `
            <div class="wo-modal-error">
                <i class="bi bi-exclamation-circle"></i>
                <strong>Gagal memuat data</strong>
                <span>${escapeWOHTML(error.message)}</span>
            </div>
        `;

        info.textContent = 'Terjadi kesalahan saat mengambil data.';
        console.error('Popup WO:', error);
    }
}


/*
|--------------------------------------------------------------------------
| RENDER TABEL DETAIL WO
|--------------------------------------------------------------------------
| Semua kolom work_order ditampilkan secara otomatis.
| Kolom sisa_optimasi ditambahkan dari query SQL.
|--------------------------------------------------------------------------
*/

function renderWOTable(data) {

    const container = document.getElementById('woModalTableContainer');

    // Simpan seluruh data WO
    const allData = data;

    // Kolom yang ditampilkan pada tabel
    const columns = Object.keys(data[0]);

    // Buat area search dan tabel
    container.innerHTML = `
        <div class="wo-search-wrapper">
            <div class="wo-search-box">
                <i class="bi bi-search"></i>

                <input
                    type="text"
                    id="woSearchInput"
                    placeholder="Cari No. WO, No. SO, atau Pelanggan..."
                    autocomplete="off"
                >

                <button
                    type="button"
                    id="woSearchClear"
                    title="Hapus pencarian"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <div id="woSearchTable"></div>
    `;

    const searchInput = document.getElementById('woSearchInput');
    const clearButton = document.getElementById('woSearchClear');
    const tableContainer = document.getElementById('woSearchTable');

    // Fungsi untuk membuat tabel
    function createTable(rows) {

        if (rows.length === 0) {

            tableContainer.innerHTML = `
                <div class="wo-modal-empty">
                    <i class="bi bi-search"></i>
                    <strong>Data tidak ditemukan</strong>
                    <span>
                        Tidak ada WO yang cocok dengan pencarian.
                    </span>
                </div>
            `;

            return;
        }

        let html = `
            <table class="wo-detail-table">
                <thead>
                    <tr>
        `;

        columns.forEach(column => {

            let label = column
                .replace(/_/g, ' ')
                .replace(/\b\w/g, letter => letter.toUpperCase());

            if (column === 'sisa_optimasi') {
                label = 'Sisa Optimasi';
            }

            html += `<th>${escapeWOHTML(label)}</th>`;
        });

        html += `
                    </tr>
                </thead>
                <tbody>
        `;

        rows.forEach(row => {

            html += '<tr>';

            columns.forEach(column => {

                let value = row[column];

                if (value === null || value === '') {
                    value = '-';
                }

                if (column === 'sisa_optimasi') {

                    html += `
                        <td>
                            <span class="wo-remaining-value">
                                ${escapeWOHTML(value)}
                            </span>
                        </td>
                    `;

                } else {

                    html += `
                        <td>${escapeWOHTML(value)}</td>
                    `;
                }

            });

            html += '</tr>';
        });

        html += `
                </tbody>
            </table>
        `;

        tableContainer.innerHTML = html;
    }

    // Fungsi pencarian hanya pada tiga kolom
    function filterWO(keyword) {

        const searchValue = keyword.toLowerCase().trim();

        const filteredData = allData.filter(row => {

            const noWO = String(row.no_wo ?? '').toLowerCase();
            const noSO = String(row.no_so ?? '').toLowerCase();
            const pelanggan = String(row.pelanggan ?? '').toLowerCase();

            return (
                noWO.includes(searchValue) ||
                noSO.includes(searchValue) ||
                pelanggan.includes(searchValue)
            );

        });

        createTable(filteredData);

        // Perbarui informasi jumlah data
        document.getElementById('woModalInfo').textContent =
            'Menampilkan ' +
            filteredData.length.toLocaleString('id-ID') +
            ' dari ' +
            allData.length.toLocaleString('id-ID') +
            ' Work Order';
    }

    // Jalankan pencarian saat mengetik
    searchInput.addEventListener('input', function() {
        filterWO(this.value);
    });

    // Hapus pencarian
    clearButton.addEventListener('click', function() {

        searchInput.value = '';

        filterWO('');

        searchInput.focus();

    });

    // Tampilkan seluruh data pertama kali
    createTable(allData);
}


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
| Mencegah nilai dari database dianggap sebagai HTML.
|--------------------------------------------------------------------------
*/

function escapeWOHTML(value) {

    const element = document.createElement('span');

    element.textContent = String(value);

    return element.innerHTML;
}


/*
|--------------------------------------------------------------------------
| TUTUP MODAL
|--------------------------------------------------------------------------
*/

function closeWOModal() {

    const modal = document.getElementById('woDetailModal');

    if (!modal) return;

    modal.classList.remove('show');
    document.body.style.overflow = '';
}


// Tutup modal menggunakan tombol ESC
document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {
        closeWOModal();
    }

});
</script>

<style>
/* ==================================================
   POPUP CARD 1 - WO BELUM DIOPTIMASI
================================================== */

.wo-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.wo-modal.show {
    display: flex;
}

.wo-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.58);
    backdrop-filter: blur(4px);
}

.wo-modal-content {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 1250px;
    max-height: 88vh;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    animation: woModalAppear 0.2s ease;
}

@keyframes woModalAppear {
    from {
        opacity: 0;
        transform: translateY(12px) scale(0.99);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.wo-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    padding: 22px 26px;
    border-bottom: 1px solid #e8edf4;
}

.wo-modal-header h3 {
    margin: 0;
    color: #172b4d;
    font-size: 19px;
    font-weight: 700;
}

.wo-modal-header p {
    margin: 5px 0 0;
    color: #718096;
    font-size: 13px;
}

.wo-modal-close {
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

.wo-modal-close:hover {
    background: #fee2e2;
    color: #dc2626;
}

.wo-modal-summary {
    display: flex;
    gap: 14px;
    padding: 16px 26px;
    background: #f8fafc;
    border-bottom: 1px solid #e8edf4;
}

.wo-modal-summary-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 150px;
}

.wo-modal-summary-item span {
    color: #718096;
    font-size: 12px;
}

.wo-modal-summary-item strong {
    color: #1e293b;
    font-size: 17px;
    font-weight: 700;
}

.wo-modal-table-wrapper {
    position: relative;
    min-height: 180px;
    overflow: auto;
}

.wo-modal-table-container {
    width: 100%;
    overflow: auto;
}

.wo-detail-table {
    width: 100%;
    min-width: max-content;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.wo-detail-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    padding: 13px 15px;
    background: #eff5fc;
    color: #334155;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
    font-weight: 700;
    border-bottom: 1px solid #dbe5f0;
}

.wo-detail-table td {
    padding: 12px 15px;
    color: #475569;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
    border-bottom: 1px solid #edf1f5;
}

.wo-detail-table tbody tr:hover {
    background: #f8fbff;
}

.wo-remaining-value {
    display: inline-block;
    padding: 4px 9px;
    border-radius: 6px;
    background: #fff1db;
    color: #b76a00;
    font-weight: 700;
}

.wo-modal-loading,
.wo-modal-empty,
.wo-modal-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 220px;
    padding: 25px;
    text-align: center;
}

.wo-modal-loading p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
}

.wo-modal-empty i,
.wo-modal-error i {
    font-size: 32px;
}

.wo-modal-empty i {
    color: #94a3b8;
}

.wo-modal-error i {
    color: #dc2626;
}

.wo-modal-empty strong,
.wo-modal-error strong {
    color: #334155;
    font-size: 14px;
}

.wo-modal-empty span,
.wo-modal-error span {
    color: #64748b;
    font-size: 12px;
}

.wo-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 15px 26px;
    border-top: 1px solid #e8edf4;
}

.wo-modal-footer span {
    color: #718096;
    font-size: 12px;
}

.wo-modal-footer-close {
    padding: 9px 20px;
    border: 0;
    border-radius: 8px;
    background: #0875d1;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.wo-modal-footer-close:hover {
    background: #075eaa;
}

@media (max-width: 600px) {

    .wo-modal {
        padding: 10px;
    }

    .wo-modal-content {
        max-height: 94vh;
        border-radius: 12px;
    }

    .wo-modal-header {
        padding: 17px;
    }

    .wo-modal-header h3 {
        font-size: 16px;
    }

    .wo-modal-summary {
        padding: 13px 17px;
    }

    .wo-modal-footer {
        padding: 13px 17px;
    }
}

/* ==================================================
   SEARCH POPUP WORK ORDER
================================================== */

.wo-search-wrapper {
    width: 100%;
    padding: 15px 18px;
    background: #ffffff;
}

.wo-search-box {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    max-width: 420px;
}

.wo-search-box > i {
    position: absolute;
    left: 14px;
    color: #64748b;
    font-size: 15px;
    pointer-events: none;
}

.wo-search-box input {
    width: 100%;
    height: 42px;
    padding: 0 42px;
    border: 1px solid #dbe3ed;
    border-radius: 8px;
    outline: none;
    font-size: 13px;
    color: #334155;
    background: #ffffff;
    transition: 0.2s ease;
}

.wo-search-box input:focus {
    border-color: #0875d1;
    box-shadow: 0 0 0 3px rgba(8, 117, 209, 0.10);
}

.wo-search-box input::placeholder {
    color: #94a3b8;
}

.wo-search-box button {
    position: absolute;
    right: 10px;
    border: none;
    background: transparent;
    color: #94a3b8;
    cursor: pointer;
    padding: 5px;
}

.wo-search-box button:hover {
    color: #dc2626;
}

@media (max-width: 600px) {

    .wo-search-wrapper {
        padding: 12px;
    }

    .wo-search-box {
        max-width: 100%;
    }

}
</style>