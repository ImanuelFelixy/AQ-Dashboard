<?php
/**
 * pembelian_belum_dilunasi.php
 * Modul "Pembelian Belum Dilunasi" (card + modal master-detail).
 * Dipanggil dari main_pembelian.php.
 *
 * Tabel sumber:
 *  - payable_aging     : 1 baris = 1 nota beli (NB) yang punya hutang
 *  - mutasi_pembelian  : rincian barang per NB (relasi: REPLACE(no_trans,'/','-') = no_pembelian)
 */

/**
 * Ambil daftar pembelian yang belum lunas (kurang_bayar_rp > 0)
 * pada rentang tgl_pembelian.
 */
function getUnpaidList(?mysqli $db, string $start, string $end): array
{
    $rows = pembelian_query(
        $db,
        "SELECT p.payable_id,
                p.supplier,
                p.no_pembelian,
                COALESCE(p.no_pelunasan, '')                  AS no_pelunasan,
                DATE_FORMAT(p.tgl_pembelian, '%d/%m/%Y')       AS tgl_pembelian,
                DATE_FORMAT(p.tgl_tempo_pembelian, '%d/%m/%Y') AS tgl_tempo,
                p.total_rp,
                p.kurang_bayar_rp,
                m.nama_barang,
                m.total_harga_rp
         FROM payable_aging p
         LEFT JOIN mutasi_pembelian m
                ON REPLACE(m.no_trans, '/', '-') = p.no_pembelian
         WHERE p.kurang_bayar_rp > 0
           AND p.tgl_pembelian BETWEEN ? AND ?
         ORDER BY p.tgl_pembelian, p.payable_id, m.id_pembelian",
        'ss',
        [$start, $end]
    );

    $list = [];
    foreach ($rows as $r) {
        $key = $r['payable_id'];
        if (!isset($list[$key])) {
            $list[$key] = [
                'supplier'      => $r['supplier'],
                'no_nb'         => $r['no_pembelian'],
                'no_pelunasan'  => $r['no_pelunasan'],
                'tgl_pembelian' => $r['tgl_pembelian'],
                'tgl_tempo'     => $r['tgl_tempo'],
                'nominal'       => (float) $r['total_rp'],
                'kurang_bayar'  => (float) $r['kurang_bayar_rp'],
                'items'         => [],
            ];
        }

        if ($r['nama_barang'] !== null) {
            $list[$key]['items'][] = [
                'no_nb'   => $r['no_pembelian'],
                'barang'  => $r['nama_barang'],
                'nominal' => (float) $r['total_harga_rp'],
            ];
        }
    }

    return array_values($list);
}

/** Card metrik "Pembelian Belum Dilunasi" */
function renderUnpaidCard(array $unpaidList): void
{
    $unpaidNominal = array_sum(array_column($unpaidList, 'kurang_bayar'));
    ?>
            <!-- Card 3: Pembelian Belum Dilunasi -->
            <div class="p-card card-unpaid" style="cursor: pointer;" onclick="openUnpaidModal()" title="Klik untuk meninjau Pembelian Belum Dilunasi">
                <div class="p-card-header">
                    <h3 class="p-card-title">Pembelian<br>Belum Dilunasi</h3>
                    <button type="button" class="p-circle-btn btn-red" onclick="event.stopPropagation(); openUnpaidModal();" title="Lihat Detail Pembelian Belum Dilunasi">
                        <i class="bi bi-arrow-up-right"></i>
                    </button>
                </div>

                <div class="p-card-metric">
                    <span class="p-metric-nominal">Rp <?= number_format($unpaidNominal, 0, ',', '.') ?>,00</span>
                </div>

                <div class="p-card-footer">
                    <span class="status-indicator danger">
                        <span class="indicator-dot"></span>
                        Jatuh tempo berjalan
                    </span>
                </div>
            </div>
    <?php
}

/** Modal daftar Pembelian Belum Dilunasi */
function renderUnpaidModal(array $unpaidList): void
{
    $unpaidNominal = array_sum(array_column($unpaidList, 'kurang_bayar'));
    ?>
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
                                placeholder="Cari Supplier, No. NB, Barang..."
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

            <!-- Body Modal: Master-Detail -->
            <div class="unpaid-modal-body">

                <!-- Panel Kiri: Tabel Pembelian Belum Dilunasi -->
                <div class="unpaid-panel-left">
                    <table class="unpaid-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;">#</th>
                                <th>Supplier</th>
                                <th>Tgl. Pembelian</th>
                                <th>Tgl. Tempo</th>
                                <th style="text-align: right;">Nominal</th>
                                <th style="text-align: right;">Kurang Bayar</th>
                                <th style="width: 30px;"></th>
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

                <!-- Panel Kanan: Rincian Nota Beli (NB) -->
                <div class="unpaid-panel-right">
                    <div class="unpaid-detail-top-row">
                        <div class="unpaid-detail-header-left">
                            <span class="unpaid-badge-rincian">RINCIAN NOTA BELI</span>
                            <span class="unpaid-detail-nopo" id="unpaidDetailBadge">-</span>
                        </div>
                        <div id="unpaidDetailStatusWrap">
                            <span class="po-status-badge amber">
                                <span class="dot"></span>
                                Belum Lunas
                            </span>
                        </div>
                    </div>

                    <div class="unpaid-detail-supplier-name" id="unpaidDetailSupplier">-</div>

                    <div class="unpaid-detail-boxes">
                        <div class="unpaid-meta-card">
                            <span>Tgl Pembelian:</span>
                            <strong id="unpaidDetailTglBeli">-</strong>
                        </div>
                        <div class="unpaid-meta-card">
                            <span>Jatuh Tempo:</span>
                            <strong id="unpaidDetailTglTempo">-</strong>
                        </div>
                        <div class="unpaid-meta-card danger">
                            <span>Kurang Bayar:</span>
                            <strong id="unpaidDetailKurangBayar">-</strong>
                        </div>
                    </div>

                    <div class="unpaid-items-title-bar">
                        <h5>Rincian Nota Beli (NB)</h5>
                        <span class="unpaid-items-count-tag" id="unpaidDetailItemsCount">0 Item</span>
                    </div>

                    <div class="unpaid-items-table-wrap">
                        <table class="unpaid-items-table">
                            <thead>
                                <tr>
                                    <th style="width: 140px;">No. NB</th>
                                    <th>Barang</th>
                                    <th style="text-align: right; width: 140px;">Nominal</th>
                                </tr>
                            </thead>
                            <tbody id="unpaidDetailItemsTableBody">
                                <!-- Rendered by JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <div class="unpaid-detail-footer-summary">
                        <span>Total Nominal NB Terpilih:</span>
                        <strong id="unpaidDetailTotalNominal">Rp 0</strong>
                    </div>
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
    <?php
}

/** JavaScript modal Pembelian Belum Dilunasi */
function renderUnpaidScript(array $unpaidList): void
{
    ?>
<script>
// ======================================
// MODAL PEMBELIAN BELUM DILUNASI (MASTER-DETAIL)
// ======================================
const unpaidData = <?= pembelian_json($unpaidList) ?>;
let filteredUnpaidIndices = unpaidData.map((_, i) => i);
let selectedUnpaidIndex = 0;

function openUnpaidModal() {
    const modal = document.getElementById('unpaidBillsModal');
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Reset filter & selection
    const searchInput = document.getElementById('unpaidSearchInput');
    if (searchInput) searchInput.value = '';
    selectedUnpaidIndex = 0;

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
        clearUnpaidDetail();
        return;
    }

    if (emptyMsg) emptyMsg.style.display = 'none';

    filteredUnpaidIndices.forEach((dataIndex, loopIdx) => {
        const item = unpaidData[dataIndex];
        const tr = document.createElement('tr');
        if (dataIndex === selectedUnpaidIndex) {
            tr.classList.add('active-row');
        }

        const rowNum = '#' + String(loopIdx + 1).padStart(2, '0');

        tr.innerHTML = `
            <td class="unpaid-row-num">${rowNum}</td>
            <td class="unpaid-supplier-text">${item.supplier}</td>
            <td class="unpaid-date-text">${item.tgl_pembelian}</td>
            <td class="unpaid-date-text">${item.tgl_tempo}</td>
            <td class="unpaid-nominal-text" style="text-align: right;">${formatRupiah(item.nominal)}</td>
            <td class="unpaid-kurang-bayar-text" style="text-align: right;">${formatRupiah(item.kurang_bayar)}</td>
            <td class="unpaid-arrow-cell">
                <i class="bi bi-chevron-right"></i>
            </td>
        `;

        tr.addEventListener('click', () => {
            selectUnpaid(dataIndex);
        });

        tbody.appendChild(tr);
    });

    // Pastikan panel rincian menampilkan data yang valid
    if (!filteredUnpaidIndices.includes(selectedUnpaidIndex)) {
        selectUnpaid(filteredUnpaidIndices[0]);
    } else {
        renderUnpaidDetail(unpaidData[selectedUnpaidIndex]);
    }

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

function selectUnpaid(index) {
    selectedUnpaidIndex = index;
    const item = unpaidData[index];

    // Highlight baris aktif
    const tbody = document.getElementById('unpaidTableBody');
    if (tbody) {
        const rows = tbody.querySelectorAll('tr');
        filteredUnpaidIndices.forEach((dataIndex, loopIdx) => {
            if (rows[loopIdx]) {
                if (dataIndex === selectedUnpaidIndex) {
                    rows[loopIdx].classList.add('active-row');
                } else {
                    rows[loopIdx].classList.remove('active-row');
                }
            }
        });
    }

    renderUnpaidDetail(item);
}

function renderUnpaidDetail(item) {
    if (!item) return;

    const badgeElem = document.getElementById('unpaidDetailBadge');
    if (badgeElem) badgeElem.innerText = item.no_nb || '-';

    const suppElem = document.getElementById('unpaidDetailSupplier');
    if (suppElem) suppElem.innerText = item.supplier;

    const tglBeliElem = document.getElementById('unpaidDetailTglBeli');
    if (tglBeliElem) tglBeliElem.innerText = item.tgl_pembelian;

    const tglTempoElem = document.getElementById('unpaidDetailTglTempo');
    if (tglTempoElem) tglTempoElem.innerText = item.tgl_tempo;

    const kurangBayarElem = document.getElementById('unpaidDetailKurangBayar');
    if (kurangBayarElem) kurangBayarElem.innerText = formatRupiah(item.kurang_bayar);

    const items = item.items || [];
    const countTag = document.getElementById('unpaidDetailItemsCount');
    if (countTag) countTag.innerText = `${items.length} Item`;

    const detailTbody = document.getElementById('unpaidDetailItemsTableBody');
    if (detailTbody) {
        detailTbody.innerHTML = '';
        items.forEach(it => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <span class="unpaid-nb-badge" style="background: #e0f2fe; color: #0369a1; font-weight: 700; font-size: 11.5px; padding: 3px 8px; border-radius: 5px;">
                        ${it.no_nb}
                    </span>
                </td>
                <td style="font-weight: 700; color: #1e293b;">${it.barang}</td>
                <td class="nominal-col">${formatRupiah(it.nominal)}</td>
            `;
            detailTbody.appendChild(tr);
        });
    }

    const totalNominalElem = document.getElementById('unpaidDetailTotalNominal');
    if (totalNominalElem) {
        totalNominalElem.innerText = formatRupiah(item.nominal);
    }
}

function clearUnpaidDetail() {
    const badgeElem = document.getElementById('unpaidDetailBadge');
    if (badgeElem) badgeElem.innerText = '-';

    const suppElem = document.getElementById('unpaidDetailSupplier');
    if (suppElem) suppElem.innerText = '-';

    const tglBeliElem = document.getElementById('unpaidDetailTglBeli');
    if (tglBeliElem) tglBeliElem.innerText = '-';

    const tglTempoElem = document.getElementById('unpaidDetailTglTempo');
    if (tglTempoElem) tglTempoElem.innerText = '-';

    const kurangBayarElem = document.getElementById('unpaidDetailKurangBayar');
    if (kurangBayarElem) kurangBayarElem.innerText = 'Rp 0';

    const countTag = document.getElementById('unpaidDetailItemsCount');
    if (countTag) countTag.innerText = '0 Item';

    const detailTbody = document.getElementById('unpaidDetailItemsTableBody');
    if (detailTbody) detailTbody.innerHTML = '';

    const totalNominalElem = document.getElementById('unpaidDetailTotalNominal');
    if (totalNominalElem) totalNominalElem.innerText = 'Rp 0';
}

function filterUnpaidList() {
    const searchInput = document.getElementById('unpaidSearchInput');
    const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';

    filteredUnpaidIndices = [];

    unpaidData.forEach((item, index) => {
        const itemMatch = item.items && item.items.some(it =>
            (it.no_nb && it.no_nb.toLowerCase().includes(searchVal)) ||
            (it.barang && it.barang.toLowerCase().includes(searchVal))
        );

        const matchSearch =
            item.supplier.toLowerCase().includes(searchVal) ||
            item.tgl_pembelian.toLowerCase().includes(searchVal) ||
            item.tgl_tempo.toLowerCase().includes(searchVal) ||
            (item.no_nb && item.no_nb.toLowerCase().includes(searchVal)) ||
            (item.no_pelunasan && item.no_pelunasan.toLowerCase().includes(searchVal)) ||
            itemMatch;

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
}
