<?php
/*
|--------------------------------------------------------------------------
| POPUP CARD 4 - DETAIL PENCAPAIAN PRODUKSI / KAPASITAS PER MESIN
|--------------------------------------------------------------------------
| Muncul saat sebuah bar pada grafik "% Pencapaian Produksi / Kapasitas"
| diklik. Isi: ringkasan mesin + tabel hasil produksi per tanggal.
|
| Rumus (sama dengan grafik di data-Produksi.php):
|   pencapaian % = m2 hasil OK / (kapasitas harian x hari kerja) x 100
|   hari kerja   = Senin-Sabtu pada periode
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../koneksi.php';

/*
|--------------------------------------------------------------------------
| A. ENDPOINT AJAX DETAIL PENCAPAIAN
|--------------------------------------------------------------------------
*/

if (isset($_GET['action']) && $_GET['action'] === 'detail_pencapaian') {

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
        return ($d && $d->format('Y-m-d') === $t) ? $d : null;
    };

    $dAwal  = $cekTanggal($_GET['tanggal_awal'] ?? '');
    $dAkhir = $cekTanggal($_GET['tanggal_akhir'] ?? '');

    if (
        !isset($mesinValid[$mesin]) ||
        !$dAwal || !$dAkhir ||
        $dAwal > $dAkhir ||
        $dAwal->diff($dAkhir)->days > 400
    ) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Mesin atau periode tanggal tidak valid.'
        ]);

        exit;
    }

    $tanggalAwal  = $dAwal->format('Y-m-d');
    $tanggalAkhir = $dAkhir->format('Y-m-d');

    try {

        // 1. Hasil produksi per tanggal
        $stmt = $conn->prepare("
            SELECT
                tgl_update,
                SUM(COALESCE(m2_ok, 0))        AS m2_hasil,
                SUM(COALESCE(qty, 0))          AS pcs_ok,
                COUNT(DISTINCT no_wo)          AS jumlah_wo
            FROM produksi
            WHERE UPPER(TRIM(nama_mesin)) = :mesin
              AND tgl_update BETWEEN :awal AND :akhir
            GROUP BY tgl_update
        ");

        $stmt->execute([
            ':mesin' => $mesin,
            ':awal'  => $tanggalAwal,
            ':akhir' => $tanggalAkhir
        ]);

        $perHari = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $perHari[$r['tgl_update']] = $r;
        }

        // 2. Kapasitas mesin (file kapasitas-mesin.php, database tidak diubah)
        $kapasitas = null;
        $sumber = null;

        $konfigKapasitas = is_file(__DIR__ . '/kapasitas-mesin.php')
            ? (require __DIR__ . '/kapasitas-mesin.php')
            : [];

        if ((float) ($konfigKapasitas[$mesin]['kapasitas'] ?? 0) > 0) {
            $kapasitas = (float) $konfigKapasitas[$mesin]['kapasitas'];
            $sumber = $konfigKapasitas[$mesin]['sumber'] ?? 'PERKIRAAN';
        }

        // 3. Susun setiap tanggal pada periode (termasuk hari tanpa hasil)
        $namaHari = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ];

        $data = [];
        $hariKerja = 0;
        $totalM2 = 0.0;

        $d = clone $dAwal;

        while ($d <= $dAkhir) {

            $tgl = $d->format('Y-m-d');
            $no  = (int) $d->format('N');
            $r   = $perHari[$tgl] ?? null;

            $m2 = $r ? (float) $r['m2_hasil'] : 0.0;
            $totalM2 += $m2;

            if ($no !== 7) {
                $hariKerja++;
            }

            $data[] = [
                'tanggal'   => $tgl,
                'hari'      => $namaHari[$no],
                'libur'     => ($no === 7),
                'm2_hasil'  => round($m2, 2),
                'pcs_ok'    => $r ? (int) $r['pcs_ok'] : 0,
                'jumlah_wo' => $r ? (int) $r['jumlah_wo'] : 0,
                'persen'    => ($kapasitas !== null)
                    ? round($m2 * 100 / $kapasitas, 1)
                    : null
            ];

            $d->modify('+1 day');
        }

        $kapasitasPeriode = ($kapasitas !== null) ? $kapasitas * $hariKerja : null;

        $ringkasan = [
            'kapasitas_harian'  => $kapasitas,
            'sumber'            => $sumber,
            'hari_kerja'        => $hariKerja,
            'kapasitas_periode' => $kapasitasPeriode,
            'hasil_m2'          => round($totalM2, 2),
            'persen'            => ($kapasitasPeriode > 0)
                ? round($totalM2 * 100 / $kapasitasPeriode, 1)
                : null,
            'selisih'           => ($kapasitasPeriode !== null)
                ? round($totalM2 - $kapasitasPeriode, 2)
                : null
        ];

        echo json_encode([
            'success'   => true,
            'mesin'     => $mesin,
            'label'     => $mesinValid[$mesin],
            'ringkasan' => $ringkasan,
            'total'     => count($data),
            'data'      => $data
        ], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {

        error_log('Popup Card 4: ' . $e->getMessage());

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengambil detail pencapaian produksi.'
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

<div id="cpDetailModal" class="cp-modal">

    <div
        class="cp-modal-backdrop"
        onclick="closeCPModal()"
    ></div>

    <div class="cp-modal-content">

        <!-- HEADER MODAL -->
        <div class="cp-modal-header">

            <div>
                <h3 id="cpModalTitle">
                    Detail Pencapaian Produksi
                </h3>

                <p id="cpModalSubtitle">
                    Hasil produksi per tanggal
                </p>
            </div>

            <button
                type="button"
                class="cp-modal-close"
                onclick="closeCPModal()"
                aria-label="Tutup popup"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>

        <!-- RINGKASAN -->
        <div class="cp-modal-summary">

            <div class="cp-modal-summary-item">
                <span>Mesin</span>
                <strong id="cpSumMesin">-</strong>
            </div>

            <div class="cp-modal-summary-item">
                <span>Kapasitas / Hari</span>
                <strong id="cpSumKapasitas">-</strong>
            </div>

            <div class="cp-modal-summary-item">
                <span>Hari Kerja</span>
                <strong id="cpSumHari">-</strong>
            </div>

            <div class="cp-modal-summary-item">
                <span>Kapasitas Periode</span>
                <strong id="cpSumKapPeriode">-</strong>
            </div>

            <div class="cp-modal-summary-item">
                <span>Hasil Produksi</span>
                <strong id="cpSumHasil">-</strong>
            </div>

            <div class="cp-modal-summary-item">
                <span>Pencapaian</span>
                <strong id="cpSumPersen">-</strong>
            </div>

            <div class="cp-modal-summary-item">
                <span>Selisih vs Kapasitas</span>
                <strong id="cpSumSelisih">-</strong>
            </div>

        </div>

        <div id="cpModalNote" class="cp-modal-note"></div>

        <!-- TABEL -->
        <div class="cp-modal-table-wrapper">

            <div
                id="cpModalLoading"
                class="cp-modal-loading"
                style="display: none;"
            >
                <div class="spinner-border text-primary"></div>
                <p>Memuat data pencapaian...</p>
            </div>

            <div
                id="cpModalTableContainer"
                class="cp-modal-table-container"
            ></div>

        </div>

        <!-- FOOTER -->
        <div class="cp-modal-footer">

            <span id="cpModalInfo">
                Data pencapaian produksi
            </span>

            <button
                type="button"
                class="cp-modal-footer-close"
                onclick="closeCPModal()"
            >
                Tutup
            </button>

        </div>

    </div>

</div>

<script>
/*
|--------------------------------------------------------------------------
| C. JAVASCRIPT POPUP CARD 4
|--------------------------------------------------------------------------
*/

const cpDetailEndpoint = 'PopCard4.php';

function cpAngka(nilai, desimal = 0) {
    return Number(nilai).toLocaleString('id-ID', {
        minimumFractionDigits: desimal,
        maximumFractionDigits: desimal
    });
}

function cpTanggal(iso) {
    const [y, m, d] = String(iso).split('-');
    return d + '/' + m + '/' + y;
}

function cpWarna(persen) {
    if (persen === null) return 'muted';
    if (persen >= 85) return 'ok';
    if (persen >= 60) return 'warn';
    return 'danger';
}

async function openCPDetail(mesin, label) {

    const modal = document.getElementById('cpDetailModal');
    const loading = document.getElementById('cpModalLoading');
    const tableContainer = document.getElementById('cpModalTableContainer');
    const info = document.getElementById('cpModalInfo');
    const note = document.getElementById('cpModalNote');

    if (!modal) return;

    const tanggalAwal = document.getElementById('tanggalAwal').value;
    const tanggalAkhir = document.getElementById('tanggalAkhir').value;

    modal.classList.add('show');
    document.body.style.overflow = 'hidden';

    document.getElementById('cpModalTitle').textContent =
        'Detail Pencapaian - ' + label;

    document.getElementById('cpModalSubtitle').textContent =
        'Hasil produksi per tanggal, ' +
        cpTanggal(tanggalAwal) + ' s/d ' + cpTanggal(tanggalAkhir);

    ['cpSumKapasitas', 'cpSumHari', 'cpSumKapPeriode',
     'cpSumHasil', 'cpSumPersen', 'cpSumSelisih'].forEach(id => {
        document.getElementById(id).textContent = '-';
    });

    document.getElementById('cpSumMesin').textContent = label;

    note.className = 'cp-modal-note';
    note.textContent = '';
    info.textContent = 'Sedang mengambil data...';
    tableContainer.innerHTML = '';
    loading.style.display = 'flex';

    try {

        const params = new URLSearchParams({
            action: 'detail_pencapaian',
            mesin: mesin,
            tanggal_awal: tanggalAwal,
            tanggal_akhir: tanggalAkhir
        });

        const response = await fetch(
            cpDetailEndpoint + '?' + params.toString(),
            {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            }
        );

        const result = await response.json();

        loading.style.display = 'none';

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || 'Gagal mengambil data pencapaian.'
            );
        }

        renderCPSummary(result.ringkasan);
        renderCPTable(result.data, result.ringkasan);

        info.textContent =
            'Menampilkan ' + cpAngka(result.total) + ' tanggal';

    } catch (error) {

        loading.style.display = 'none';

        tableContainer.innerHTML = `
            <div class="cp-modal-error">
                <i class="bi bi-exclamation-circle"></i>
                <strong>Gagal memuat data</strong>
                <span>${escapeCPHTML(error.message)}</span>
            </div>
        `;

        info.textContent = 'Terjadi kesalahan saat mengambil data.';
        console.error('Popup Pencapaian:', error);
    }
}


/*
|--------------------------------------------------------------------------
| RINGKASAN
|--------------------------------------------------------------------------
*/

function renderCPSummary(r) {

    const note = document.getElementById('cpModalNote');

    document.getElementById('cpSumHari').textContent =
        cpAngka(r.hari_kerja) + ' hari';

    document.getElementById('cpSumHasil').textContent =
        cpAngka(r.hasil_m2, 2) + ' m²';

    if (r.kapasitas_harian === null) {

        note.textContent =
            'Kapasitas mesin ini belum diisi pada file kapasitas-mesin.php, ' +
            'sehingga persentase pencapaian belum dapat dihitung.';
        note.classList.add('show');

        return;
    }

    document.getElementById('cpSumKapasitas').textContent =
        cpAngka(r.kapasitas_harian, 2) + ' m²';

    document.getElementById('cpSumKapPeriode').textContent =
        cpAngka(r.kapasitas_periode, 2) + ' m²';

    const persen = document.getElementById('cpSumPersen');
    persen.innerHTML =
        cpAngka(r.persen, 1) + '% ' +
        '<span class="cp-badge ' + cpWarna(r.persen) + '">' +
        (r.persen >= 85 ? 'Baik' : (r.persen >= 60 ? 'Perlu perhatian' : 'Rendah')) +
        '</span>';

    const selisih = document.getElementById('cpSumSelisih');
    selisih.textContent =
        (r.selisih > 0 ? '+' : '') + cpAngka(r.selisih, 2) + ' m²';
    selisih.style.color = r.selisih >= 0 ? '#07845e' : '#bd2020';

    if (r.sumber !== 'RESMI') {
        note.textContent =
            'Kapasitas masih perkiraan (puncak harian historis), bukan kapasitas ' +
            'resmi. Ganti di file kapasitas-mesin.php agar persentase akurat.';
        note.classList.add('show');
    }
}


/*
|--------------------------------------------------------------------------
| TABEL PER TANGGAL
|--------------------------------------------------------------------------
*/

function renderCPTable(data, r) {

    const container = document.getElementById('cpModalTableContainer');

    if (!data || data.length === 0) {

        container.innerHTML = `
            <div class="cp-modal-empty">
                <i class="bi bi-inbox"></i>
                <strong>Data tidak ditemukan</strong>
                <span>Tidak ada tanggal pada periode ini.</span>
            </div>
        `;

        return;
    }

    const adaKapasitas = r.kapasitas_harian !== null;

    let html = `
        <table class="cp-detail-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Hari</th>
                    <th>Hasil (m²)</th>
                    <th>Pcs OK</th>
                    <th>Jumlah WO</th>
                    ${adaKapasitas ? '<th>% Pencapaian Harian</th>' : ''}
                </tr>
            </thead>
            <tbody>
    `;

    data.forEach(row => {

        const kosong = row.m2_hasil === 0;
        const style = row.libur && kosong ? ' style="color:#94a3b8"' : '';

        let kolomPersen = '';

        if (adaKapasitas) {

            if (row.libur && kosong) {
                kolomPersen = '<td>Libur</td>';
            } else {
                kolomPersen = `
                    <td>
                        <span class="cp-badge ${cpWarna(row.persen)}">
                            ${cpAngka(row.persen, 1)}%
                        </span>
                    </td>
                `;
            }
        }

        html += `
            <tr${style}>
                <td>${escapeCPHTML(cpTanggal(row.tanggal))}</td>
                <td>${escapeCPHTML(row.hari)}</td>
                <td>${cpAngka(row.m2_hasil, 2)}</td>
                <td>${cpAngka(row.pcs_ok)}</td>
                <td>${cpAngka(row.jumlah_wo)}</td>
                ${kolomPersen}
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
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function escapeCPHTML(value) {

    const element = document.createElement('span');

    element.textContent = String(value);

    return element.innerHTML;
}


/*
|--------------------------------------------------------------------------
| TUTUP MODAL
|--------------------------------------------------------------------------
*/

function closeCPModal() {

    const modal = document.getElementById('cpDetailModal');

    if (!modal) return;

    modal.classList.remove('show');
    document.body.style.overflow = '';
}


// Tutup modal menggunakan tombol ESC
document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {
        closeCPModal();
    }

});
</script>

<style>
/* ==================================================
   POPUP CARD 4 - DETAIL PENCAPAIAN PRODUKSI (gaya sama dengan Popup Card 1 dan 2)
================================================== */

.cp-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.cp-modal.show {
    display: flex;
}

.cp-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.58);
    backdrop-filter: blur(4px);
}

.cp-modal-content {
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
    animation: cpModalAppear 0.2s ease;
}

@keyframes cpModalAppear {
    from { opacity: 0; transform: translateY(12px) scale(0.99); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.cp-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    padding: 22px 26px;
    border-bottom: 1px solid #e8edf4;
}

.cp-modal-header h3 {
    margin: 0;
    color: #172b4d;
    font-size: 19px;
    font-weight: 700;
}

.cp-modal-header p {
    margin: 5px 0 0;
    color: #718096;
    font-size: 13px;
}

.cp-modal-close {
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

.cp-modal-close:hover {
    background: #fee2e2;
    color: #dc2626;
}

.cp-modal-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 14px 28px;
    padding: 16px 26px;
    background: #f8fafc;
    border-bottom: 1px solid #e8edf4;
}

.cp-modal-summary-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 130px;
}

.cp-modal-summary-item span {
    color: #718096;
    font-size: 12px;
}

.cp-modal-summary-item strong {
    color: #1e293b;
    font-size: 17px;
    font-weight: 700;
}

.cp-modal-note {
    display: none;
    padding: 10px 26px;
    background: #fff8e6;
    border-bottom: 1px solid #f3e2b3;
    color: #8a6d00;
    font-size: 12px;
}

.cp-modal-note.show {
    display: block;
}

.cp-modal-table-wrapper {
    position: relative;
    min-height: 180px;
    overflow: auto;
}

.cp-modal-table-container {
    width: 100%;
    overflow: auto;
}

.cp-detail-table {
    width: 100%;
    min-width: max-content;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.cp-detail-table th {
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

.cp-detail-table td {
    padding: 12px 15px;
    color: #475569;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
    border-bottom: 1px solid #edf1f5;
}

.cp-detail-table tbody tr:hover {
    background: #f8fbff;
}

.cp-modal-loading,
.cp-modal-empty,
.cp-modal-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 220px;
    padding: 25px;
    text-align: center;
}

.cp-modal-loading p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
}

.cp-modal-empty i,
.cp-modal-error i {
    font-size: 32px;
}

.cp-modal-empty i { color: #94a3b8; }
.cp-modal-error i { color: #dc2626; }

.cp-modal-empty strong,
.cp-modal-error strong {
    color: #334155;
    font-size: 14px;
}

.cp-modal-empty span,
.cp-modal-error span {
    color: #64748b;
    font-size: 12px;
}

.cp-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 15px 26px;
    border-top: 1px solid #e8edf4;
}

.cp-modal-footer span {
    color: #718096;
    font-size: 12px;
}

.cp-modal-footer-close {
    padding: 9px 20px;
    border: 0;
    border-radius: 8px;
    background: #0875d1;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.cp-modal-footer-close:hover {
    background: #075eaa;
}

.cp-badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
}

.cp-badge.ok     { background: #d9f5ea; color: #07845e; }
.cp-badge.warn   { background: #fff1db; color: #b76a00; }
.cp-badge.danger { background: #fde2e2; color: #bd2020; }
.cp-badge.muted  { background: #eef2f7; color: #64748b; }

.cp-modal-search {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 18px;
    background: #ffffff;
}

.cp-search-input {
    position: relative;
    display: flex;
    align-items: center;
    flex: 1;
    max-width: 420px;
}

.cp-search-input i {
    position: absolute;
    left: 14px;
    color: #64748b;
    font-size: 15px;
    pointer-events: none;
}

.cp-search-input input {
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

.cp-search-input input:focus {
    border-color: #0875d1;
    box-shadow: 0 0 0 3px rgba(8, 117, 209, 0.10);
}

.cp-search-button,
.cp-search-reset {
    height: 42px;
    padding: 0 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.cp-search-button {
    border: 0;
    background: #0875d1;
    color: #ffffff;
}

.cp-search-reset {
    border: 1px solid #dbe3ed;
    background: #ffffff;
    color: #526176;
}

@media (max-width: 600px) {
    .cp-modal { padding: 10px; }
    .cp-modal-content { max-height: 94vh; border-radius: 12px; }
    .cp-modal-header { padding: 17px; }
    .cp-modal-header h3 { font-size: 16px; }
    .cp-modal-summary { padding: 13px 17px; }
    .cp-modal-note { padding: 10px 17px; }
    .cp-modal-footer { padding: 13px 17px; }
    .cp-modal-search { flex-wrap: wrap; padding: 12px; }
    .cp-search-input { max-width: 100%; flex-basis: 100%; }
}

</style>