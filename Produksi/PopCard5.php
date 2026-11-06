<?php
/*
|--------------------------------------------------------------------------
| POPUP CARD 5 - DETAIL REJECT PER MESIN
|--------------------------------------------------------------------------
| Muncul saat sebuah bar pada grafik "% Reject Mesin" diklik.
| Isi: ringkasan reject mesin, reject per operator, dan daftar kejadian
| reject (tanggal, WO, pelanggan, ukuran, operator, jumlah).
|
| Rumus (sama dengan grafik di data-Produksi.php):
|   reject % = reject / (OK + reject) x 100
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../koneksi.php';

/*
|--------------------------------------------------------------------------
| A. ENDPOINT AJAX DETAIL REJECT
|--------------------------------------------------------------------------
*/

if (isset($_GET['action']) && $_GET['action'] === 'detail_reject') {

    header('Content-Type: application/json; charset=utf-8');

    $mesinValid = [
        'CUT'  => 'POTONG',
        'SE'   => 'SE',
        'DE'   => 'DE',
        'JET'  => 'WATERJET',
        'TEMP' => 'TEMP'
    ];

    $mesin = strtoupper(trim($_GET['mesin'] ?? ''));

    $cekTanggal = function ($t) {
        $d = DateTime::createFromFormat('Y-m-d', (string) $t);
        return ($d && $d->format('Y-m-d') === $t) ? $t : null;
    };

    $tanggalAwal  = $cekTanggal($_GET['tanggal_awal'] ?? '');
    $tanggalAkhir = $cekTanggal($_GET['tanggal_akhir'] ?? '');

    if (
        !isset($mesinValid[$mesin]) ||
        !$tanggalAwal || !$tanggalAkhir ||
        $tanggalAwal > $tanggalAkhir
    ) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Mesin atau periode tanggal tidak valid.'
        ]);

        exit;
    }

    $param = [
        ':mesin' => $mesin,
        ':awal'  => $tanggalAwal,
        ':akhir' => $tanggalAkhir
    ];

    try {

        // 1. Ringkasan mesin pada periode
        $stmt = $conn->prepare("
            SELECT
                SUM(COALESCE(qty, 0))          AS pcs_ok,
                SUM(COALESCE(qty_reject, 0))   AS pcs_reject,
                SUM(COALESCE(m2_ok, 0))        AS m2_ok,
                SUM(COALESCE(m2_rjk, 0))       AS m2_reject
            FROM produksi
            WHERE UPPER(TRIM(nama_mesin)) = :mesin
              AND tgl_update BETWEEN :awal AND :akhir
        ");

        $stmt->execute($param);
        $s = $stmt->fetch(PDO::FETCH_ASSOC);

        $pcsOk     = (float) ($s['pcs_ok'] ?? 0);
        $pcsReject = (float) ($s['pcs_reject'] ?? 0);
        $m2Ok      = (float) ($s['m2_ok'] ?? 0);
        $m2Reject  = (float) ($s['m2_reject'] ?? 0);

        $ringkasan = [
            'pcs_ok'        => (int) $pcsOk,
            'pcs_reject'    => (int) $pcsReject,
            'm2_reject'     => round($m2Reject, 2),
            'persen_pcs'    => ($pcsOk + $pcsReject) > 0
                ? round($pcsReject * 100 / ($pcsOk + $pcsReject), 2)
                : 0,
            'persen_m2'     => ($m2Ok + $m2Reject) > 0
                ? round($m2Reject * 100 / ($m2Ok + $m2Reject), 2)
                : 0
        ];

        // 2. Reject per operator
        $stmt = $conn->prepare("
            SELECT
                COALESCE(NULLIF(TRIM(user_operator), ''), '-') AS operator,
                SUM(qty_reject)                                AS pcs_reject
            FROM produksi
            WHERE UPPER(TRIM(nama_mesin)) = :mesin
              AND tgl_update BETWEEN :awal AND :akhir
              AND qty_reject > 0
            GROUP BY COALESCE(NULLIF(TRIM(user_operator), ''), '-')
            ORDER BY pcs_reject DESC, operator ASC
        ");

        $stmt->execute($param);
        $operator = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Daftar kejadian reject
        $stmt = $conn->prepare("
            SELECT
                p.tgl_update                        AS tanggal,
                p.no_wo,
                w.pelanggan,
                w.bentuk,
                w.height,
                w.width,
                p.user_operator                     AS operator,
                p.qty_reject                        AS pcs_reject,
                ROUND(COALESCE(p.m2_rjk, 0), 3)     AS m2_reject
            FROM produksi p
            LEFT JOIN work_order w ON w.no_wo = p.no_wo
            WHERE UPPER(TRIM(p.nama_mesin)) = :mesin
              AND p.tgl_update BETWEEN :awal AND :akhir
              AND p.qty_reject > 0
            ORDER BY p.tgl_update DESC, p.id_produksi DESC
        ");

        $stmt->execute($param);
        $detail = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success'   => true,
            'mesin'     => $mesin,
            'label'     => $mesinValid[$mesin],
            'ringkasan' => $ringkasan,
            'operator'  => $operator,
            'total'     => count($detail),
            'data'      => $detail
        ], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {

        error_log('Popup Card 5: ' . $e->getMessage());

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengambil detail reject mesin.'
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

<div id="rjDetailModal" class="rj-modal">

    <div
        class="rj-modal-backdrop"
        onclick="closeRJModal()"
    ></div>

    <div class="rj-modal-content">

        <!-- HEADER MODAL -->
        <div class="rj-modal-header">

            <div>
                <h3 id="rjModalTitle">
                    Detail Reject Mesin
                </h3>

                <p id="rjModalSubtitle">
                    Daftar kejadian reject
                </p>
            </div>

            <button
                type="button"
                class="rj-modal-close"
                onclick="closeRJModal()"
                aria-label="Tutup popup"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>

        <!-- RINGKASAN -->
        <div class="rj-modal-summary">

            <div class="rj-modal-summary-item">
                <span>Mesin</span>
                <strong id="rjSumMesin">-</strong>
            </div>

            <div class="rj-modal-summary-item">
                <span>% Reject (pcs)</span>
                <strong id="rjSumPersen">-</strong>
            </div>

            <div class="rj-modal-summary-item">
                <span>Pcs Reject</span>
                <strong id="rjSumReject">-</strong>
            </div>

            <div class="rj-modal-summary-item">
                <span>Pcs OK</span>
                <strong id="rjSumOk">-</strong>
            </div>

            <div class="rj-modal-summary-item">
                <span>Luas Reject</span>
                <strong id="rjSumM2">-</strong>
            </div>

            <div class="rj-modal-summary-item">
                <span>% Reject (luas)</span>
                <strong id="rjSumPersenM2">-</strong>
            </div>

        </div>

        <div id="rjModalNote" class="rj-modal-note"></div>

        <!-- TABEL -->
        <div class="rj-modal-table-wrapper">

            <div
                id="rjModalLoading"
                class="rj-modal-loading"
                style="display: none;"
            >
                <div class="spinner-border text-primary"></div>
                <p>Memuat data reject...</p>
            </div>

            <div
                id="rjModalTableContainer"
                class="rj-modal-table-container"
            ></div>

        </div>

        <!-- FOOTER -->
        <div class="rj-modal-footer">

            <span id="rjModalInfo">
                Data reject mesin
            </span>

            <button
                type="button"
                class="rj-modal-footer-close"
                onclick="closeRJModal()"
            >
                Tutup
            </button>

        </div>

    </div>

</div>

<script>
/*
|--------------------------------------------------------------------------
| C. JAVASCRIPT POPUP CARD 5
|--------------------------------------------------------------------------
*/

const rjDetailEndpoint = 'PopCard5.php';

function rjAngka(nilai, desimal = 0) {
    return Number(nilai).toLocaleString('id-ID', {
        minimumFractionDigits: desimal,
        maximumFractionDigits: desimal
    });
}

function rjTanggal(iso) {
    const [y, m, d] = String(iso).split('-');
    return d + '/' + m + '/' + y;
}

// Sama dengan warna batang grafik: < 1% hijau, 1 - < 2% oranye, >= 2% merah
function rjWarna(persen) {
    if (persen < 1) return 'ok';
    if (persen < 2) return 'warn';
    return 'danger';
}

async function openRJDetail(mesin, label) {

    const modal = document.getElementById('rjDetailModal');
    const loading = document.getElementById('rjModalLoading');
    const tableContainer = document.getElementById('rjModalTableContainer');
    const info = document.getElementById('rjModalInfo');
    const note = document.getElementById('rjModalNote');

    if (!modal) return;

    const tanggalAwal = document.getElementById('tanggalAwal').value;
    const tanggalAkhir = document.getElementById('tanggalAkhir').value;

    modal.classList.add('show');
    document.body.style.overflow = 'hidden';

    document.getElementById('rjModalTitle').textContent =
        'Detail Reject - ' + label;

    document.getElementById('rjModalSubtitle').textContent =
        'Daftar kejadian reject, ' +
        rjTanggal(tanggalAwal) + ' s/d ' + rjTanggal(tanggalAkhir);

    ['rjSumPersen', 'rjSumReject', 'rjSumOk', 'rjSumM2', 'rjSumPersenM2']
        .forEach(id => {
            document.getElementById(id).textContent = '-';
        });

    document.getElementById('rjSumMesin').textContent = label;

    note.className = 'rj-modal-note';
    note.innerHTML = '';
    info.textContent = 'Sedang mengambil data...';
    tableContainer.innerHTML = '';
    loading.style.display = 'flex';

    try {

        const params = new URLSearchParams({
            action: 'detail_reject',
            mesin: mesin,
            tanggal_awal: tanggalAwal,
            tanggal_akhir: tanggalAkhir
        });

        const response = await fetch(
            rjDetailEndpoint + '?' + params.toString(),
            {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            }
        );

        const result = await response.json();

        loading.style.display = 'none';

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || 'Gagal mengambil data reject.'
            );
        }

        renderRJSummary(result.ringkasan, result.operator);

        info.textContent =
            'Menampilkan ' + rjAngka(result.total) + ' kejadian reject';

        if (!result.data || result.data.length === 0) {

            tableContainer.innerHTML = `
                <div class="rj-modal-empty">
                    <i class="bi bi-check-circle"></i>
                    <strong>Tidak ada reject</strong>
                    <span>Mesin ini tidak mencatat reject pada periode ini.</span>
                </div>
            `;

            return;
        }

        renderRJTable(result.data);

    } catch (error) {

        loading.style.display = 'none';

        tableContainer.innerHTML = `
            <div class="rj-modal-error">
                <i class="bi bi-exclamation-circle"></i>
                <strong>Gagal memuat data</strong>
                <span>${escapeRJHTML(error.message)}</span>
            </div>
        `;

        info.textContent = 'Terjadi kesalahan saat mengambil data.';
        console.error('Popup Reject:', error);
    }
}


/*
|--------------------------------------------------------------------------
| RINGKASAN + REJECT PER OPERATOR
|--------------------------------------------------------------------------
*/

function renderRJSummary(r, operator) {

    document.getElementById('rjSumPersen').innerHTML =
        rjAngka(r.persen_pcs, 2) + '% ' +
        '<span class="rj-badge ' + rjWarna(r.persen_pcs) + '">' +
        (r.persen_pcs < 1 ? 'Baik' : (r.persen_pcs < 2 ? 'Perlu perhatian' : 'Tinggi')) +
        '</span>';

    document.getElementById('rjSumReject').textContent =
        rjAngka(r.pcs_reject) + ' pcs';

    document.getElementById('rjSumOk').textContent =
        rjAngka(r.pcs_ok) + ' pcs';

    document.getElementById('rjSumM2').textContent =
        rjAngka(r.m2_reject, 2) + ' m²';

    document.getElementById('rjSumPersenM2').textContent =
        rjAngka(r.persen_m2, 2) + '%';

    const note = document.getElementById('rjModalNote');

    if (operator && operator.length > 0) {

        const chips = operator.slice(0, 6).map(o =>
            '<strong>' + escapeRJHTML(o.operator) + '</strong> ' +
            rjAngka(o.pcs_reject) + ' pcs'
        ).join(' &nbsp;·&nbsp; ');

        note.innerHTML = 'Reject per operator: ' + chips;
        note.classList.add('show');
    }
}


/*
|--------------------------------------------------------------------------
| TABEL KEJADIAN REJECT (dengan pencarian)
|--------------------------------------------------------------------------
*/

function renderRJTable(data) {

    const container = document.getElementById('rjModalTableContainer');
    const allData = data;

    container.innerHTML = `
        <div class="rj-modal-search">

            <div class="rj-search-input">

                <i class="bi bi-search"></i>

                <input
                    type="text"
                    id="rjSearchInput"
                    placeholder="Cari No. WO, Pelanggan, atau Operator..."
                    autocomplete="off"
                >

            </div>

            <button type="button" class="rj-search-button" id="rjSearchButton">
                Cari
            </button>

            <button type="button" class="rj-search-reset" id="rjSearchReset">
                Reset
            </button>

        </div>

        <div id="rjSearchTable"></div>
    `;

    const searchInput = document.getElementById('rjSearchInput');
    const tableContainer = document.getElementById('rjSearchTable');

    function createTable(rows) {

        if (rows.length === 0) {

            tableContainer.innerHTML = `
                <div class="rj-modal-empty">
                    <i class="bi bi-search"></i>
                    <strong>Data tidak ditemukan</strong>
                    <span>Tidak ada reject yang cocok dengan pencarian.</span>
                </div>
            `;

            return;
        }

        let html = `
            <table class="rj-detail-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. WO</th>
                        <th>Pelanggan</th>
                        <th>Bentuk</th>
                        <th>Ukuran (mm)</th>
                        <th>Operator</th>
                        <th>Pcs Reject</th>
                        <th>Luas Reject (m²)</th>
                    </tr>
                </thead>
                <tbody>
        `;

        rows.forEach(row => {

            const ukuran = (row.height !== null && row.width !== null)
                ? rjAngka(row.height) + ' x ' + rjAngka(row.width)
                : '-';

            html += `
                <tr>
                    <td>${escapeRJHTML(rjTanggal(row.tanggal))}</td>
                    <td>${escapeRJHTML(row.no_wo ?? '-')}</td>
                    <td>${escapeRJHTML(row.pelanggan ?? '-')}</td>
                    <td>${escapeRJHTML(row.bentuk ?? '-')}</td>
                    <td>${escapeRJHTML(ukuran)}</td>
                    <td>${escapeRJHTML(row.operator ?? '-')}</td>
                    <td><span class="rj-badge danger">${rjAngka(row.pcs_reject)}</span></td>
                    <td>${rjAngka(row.m2_reject, 3)}</td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        tableContainer.innerHTML = html;
    }

    function filterRJ() {

        const keyword = searchInput.value.toLowerCase().trim();

        const filtered = allData.filter(row =>
            String(row.no_wo ?? '').toLowerCase().includes(keyword) ||
            String(row.pelanggan ?? '').toLowerCase().includes(keyword) ||
            String(row.operator ?? '').toLowerCase().includes(keyword)
        );

        createTable(filtered);

        document.getElementById('rjModalInfo').textContent =
            'Menampilkan ' + rjAngka(filtered.length) +
            ' dari ' + rjAngka(allData.length) + ' kejadian reject';
    }

    document.getElementById('rjSearchButton')
        .addEventListener('click', filterRJ);

    searchInput.addEventListener('keydown', function (event) {

        if (event.key === 'Enter') {
            event.preventDefault();
            filterRJ();
        }

    });

    document.getElementById('rjSearchReset')
        .addEventListener('click', function () {

            searchInput.value = '';
            filterRJ();
            searchInput.focus();

        });

    createTable(allData);
}


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function escapeRJHTML(value) {

    const element = document.createElement('span');

    element.textContent = String(value);

    return element.innerHTML;
}


/*
|--------------------------------------------------------------------------
| TUTUP MODAL
|--------------------------------------------------------------------------
*/

function closeRJModal() {

    const modal = document.getElementById('rjDetailModal');

    if (!modal) return;

    modal.classList.remove('show');
    document.body.style.overflow = '';
}


// Tutup modal menggunakan tombol ESC
document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {
        closeRJModal();
    }

});
</script>

<style>
/* ==================================================
   POPUP CARD 5 - DETAIL REJECT MESIN (gaya sama dengan Popup Card 1 dan 2)
================================================== */

.rj-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.rj-modal.show {
    display: flex;
}

.rj-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.58);
    backdrop-filter: blur(4px);
}

.rj-modal-content {
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
    animation: rjModalAppear 0.2s ease;
}

@keyframes rjModalAppear {
    from { opacity: 0; transform: translateY(12px) scale(0.99); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.rj-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    padding: 22px 26px;
    border-bottom: 1px solid #e8edf4;
}

.rj-modal-header h3 {
    margin: 0;
    color: #172b4d;
    font-size: 19px;
    font-weight: 700;
}

.rj-modal-header p {
    margin: 5px 0 0;
    color: #718096;
    font-size: 13px;
}

.rj-modal-close {
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

.rj-modal-close:hover {
    background: #fee2e2;
    color: #dc2626;
}

.rj-modal-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 14px 28px;
    padding: 16px 26px;
    background: #f8fafc;
    border-bottom: 1px solid #e8edf4;
}

.rj-modal-summary-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 130px;
}

.rj-modal-summary-item span {
    color: #718096;
    font-size: 12px;
}

.rj-modal-summary-item strong {
    color: #1e293b;
    font-size: 17px;
    font-weight: 700;
}

.rj-modal-note {
    display: none;
    padding: 10px 26px;
    background: #fff8e6;
    border-bottom: 1px solid #f3e2b3;
    color: #8a6d00;
    font-size: 12px;
}

.rj-modal-note.show {
    display: block;
}

.rj-modal-table-wrapper {
    position: relative;
    min-height: 180px;
    overflow: auto;
}

.rj-modal-table-container {
    width: 100%;
    overflow: auto;
}

.rj-detail-table {
    width: 100%;
    min-width: max-content;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.rj-detail-table th {
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

.rj-detail-table td {
    padding: 12px 15px;
    color: #475569;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
    border-bottom: 1px solid #edf1f5;
}

.rj-detail-table tbody tr:hover {
    background: #f8fbff;
}

.rj-modal-loading,
.rj-modal-empty,
.rj-modal-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 220px;
    padding: 25px;
    text-align: center;
}

.rj-modal-loading p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
}

.rj-modal-empty i,
.rj-modal-error i {
    font-size: 32px;
}

.rj-modal-empty i { color: #94a3b8; }
.rj-modal-error i { color: #dc2626; }

.rj-modal-empty strong,
.rj-modal-error strong {
    color: #334155;
    font-size: 14px;
}

.rj-modal-empty span,
.rj-modal-error span {
    color: #64748b;
    font-size: 12px;
}

.rj-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 15px 26px;
    border-top: 1px solid #e8edf4;
}

.rj-modal-footer span {
    color: #718096;
    font-size: 12px;
}

.rj-modal-footer-close {
    padding: 9px 20px;
    border: 0;
    border-radius: 8px;
    background: #0875d1;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.rj-modal-footer-close:hover {
    background: #075eaa;
}

.rj-badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
}

.rj-badge.ok     { background: #d9f5ea; color: #07845e; }
.rj-badge.warn   { background: #fff1db; color: #b76a00; }
.rj-badge.danger { background: #fde2e2; color: #bd2020; }
.rj-badge.muted  { background: #eef2f7; color: #64748b; }

.rj-modal-search {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 18px;
    background: #ffffff;
}

.rj-search-input {
    position: relative;
    display: flex;
    align-items: center;
    flex: 1;
    max-width: 420px;
}

.rj-search-input i {
    position: absolute;
    left: 14px;
    color: #64748b;
    font-size: 15px;
    pointer-events: none;
}

.rj-search-input input {
    width: 100%;
    height: 42px;
    padding: 0 14px 0 42px;
    border: 1px solid #dbe3ed;
    border-radius: 8px;
    outline: none;
    font-size: 13px;
    color: #334155;
    background: #ffffff;
    transition: 0.2s ease;
}

.rj-search-input input:focus {
    border-color: #0875d1;
    box-shadow: 0 0 0 3px rgba(8, 117, 209, 0.10);
}

.rj-search-button,
.rj-search-reset {
    height: 42px;
    padding: 0 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.rj-search-button {
    border: 0;
    background: #0875d1;
    color: #ffffff;
}

.rj-search-reset {
    border: 1px solid #dbe3ed;
    background: #ffffff;
    color: #526176;
}

@media (max-width: 600px) {
    .rj-modal { padding: 10px; }
    .rj-modal-content { max-height: 94vh; border-radius: 12px; }
    .rj-modal-header { padding: 17px; }
    .rj-modal-header h3 { font-size: 16px; }
    .rj-modal-summary { padding: 13px 17px; }
    .rj-modal-note { padding: 10px 17px; }
    .rj-modal-footer { padding: 13px 17px; }
    .rj-modal-search { flex-wrap: wrap; padding: 12px; }
    .rj-search-input { max-width: 100%; flex-basis: 100%; }
}

</style>