<?php
/**
 * purchase_order.php
 * Modul "Purchase Order" (card + modal daftar PO).
 * Dipanggil dari main_pembelian.php. Tabel sumber: purchasing_order
 * (1 baris = 1 item, dikelompokkan per no_trans menjadi 1 PO).
 */

/**
 * Ambil daftar PO (dikelompokkan per nomor PO) pada rentang tgl_trans.
 * Nominal PO = SUM(subtotal) item. Kolom `jumlah` tidak dipakai karena bertipe
 * INT(15) sehingga nilainya terpotong di 2147483647 untuk PO bernilai besar.
 */
function getPurchaseOrderList(?mysqli $db, string $start, string $end): array
{
    $rows = pembelian_query(
        $db,
        "SELECT no_trans,
                DATE_FORMAT(tgl_trans, '%d/%m/%Y') AS tgl_trans,
                nama_supplier,
                nama_barang,
                COALESCE(satuan, '')     AS satuan,
                qty,
                subtotal,
                COALESCE(keterangan, '') AS keterangan
         FROM purchasing_order
         WHERE tgl_trans BETWEEN ? AND ?
         ORDER BY purchasing_order.tgl_trans, no_trans, po_id",
        'ss',
        [$start, $end]
    );

    $list = [];
    foreach ($rows as $r) {
        $key = $r['no_trans'];
        if (!isset($list[$key])) {
            $list[$key] = [
                'no_po'         => $r['no_trans'],
                'tgl_trans'     => $r['tgl_trans'],
                'nama_supplier' => $r['nama_supplier'],
                'keterangan'    => '',
                'jumlah'        => 0.0,
                'items'         => [],
            ];
        }

        if ($list[$key]['keterangan'] === '' && trim($r['keterangan']) !== '') {
            $list[$key]['keterangan'] = trim($r['keterangan']);
        }

        $subtotal = (float) $r['subtotal'];
        $list[$key]['jumlah'] += $subtotal;
        $list[$key]['items'][] = [
            'nama_barang' => $r['nama_barang'],
            'satuan'      => $r['satuan'],
            'qty'         => (int) $r['qty'],
            'subtotal'    => $subtotal,
            'keterangan'  => '',
        ];
    }

    return array_values($list);
}

/** Card metrik "Purchase Order" */
function renderPurchaseOrderCard(array $allPoList): void
{
    $totalPO        = count($allPoList);
    $totalPONominal = array_sum(array_column($allPoList, 'jumlah'));
    ?>
            <!-- Card 1: Purchase Order -->
            <div class="p-card card-po" style="cursor: pointer;" onclick="openAllPoModal()" title="Klik untuk meninjau Daftar Purchase Order">
                <div class="p-card-header">
                    <h3 class="p-card-title">Purchase<br>Order</h3>
                    <button type="button" class="p-circle-btn btn-gray" onclick="event.stopPropagation(); openAllPoModal();" title="Lihat Detail Purchase Order">
                        <i class="bi bi-arrow-up-right"></i>
                    </button>
                </div>

                <div class="p-card-metric">
                    <span class="p-metric-number"><?= $totalPO ?></span>
                    <span class="p-metric-unit">Transaksi</span>
                </div>

                <div class="p-card-footer">
                    <span>Total Nominal :</span>
                    <strong>Rp <?= number_format($totalPONominal, 0, ',', '.') ?>,00</strong>
                </div>
            </div>
    <?php
}

/** Modal daftar Purchase Order */
function renderPurchaseOrderModal(array $allPoList): void
{
    $totalPONominal = array_sum(array_column($allPoList, 'jumlah'));
    ?>
    <!-- ==========================================
         MODAL POPUP: DAFTAR PURCHASE ORDER (ALL PO)
    ========================================== -->
    <div class="allpo-modal-overlay" id="allPoModal" onclick="handleAllPoBackdropClick(event)">
        <div class="allpo-modal-window" onclick="event.stopPropagation()">

            <!-- Header -->
            <div class="allpo-modal-header">
                <div class="allpo-header-left">
                    <div class="allpo-header-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div class="allpo-header-text">
                        <h3>
                            Daftar Purchasing Order (PO)
                            <span class="allpo-header-badge" id="allPoHeaderBadge"><?= count($allPoList) ?> Transaksi</span>
                        </h3>
                        <p>Rincian <?= count($allPoList) ?> transaksi purchasing order aktif periode berjalan &bull; Total Nilai: Rp <?= number_format($totalPONominal, 0, ',', '.') ?>,00</p>
                    </div>
                </div>

                <div class="allpo-header-actions">
                    <div class="allpo-search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" id="allPoSearchInput" placeholder="Cari No. PO, Supplier, atau Barang..." oninput="filterAllPoList()">
                    </div>
                    <button type="button" class="allpo-btn-export" onclick="exportAllPoList()">
                        <i class="bi bi-box-arrow-up"></i>
                        Export
                    </button>
                    <button type="button" class="allpo-btn-close-x" onclick="closeAllPoModal()" title="Tutup">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Body: Master-Detail -->
            <div class="allpo-modal-body">

                <!-- Panel Kiri: Tabel PO -->
                <div class="allpo-panel-left">
                    <table class="allpo-table">
                        <thead>
                            <tr>
                                <th style="width:42px;">#</th>
                                <th>Tgl Transaksi</th>
                                <th>No. PO</th>
                                <th>Supplier</th>
                                <th>Nama Barang</th>
                                <th>Satuan</th>
                                <th style="text-align:center;">Qty</th>
                                <th style="text-align:right;">Nominal (Rp)</th>
                                <th style="width:28px;"></th>
                            </tr>
                        </thead>
                        <tbody id="allPoTableBody">
                            <!-- JS rendered -->
                        </tbody>
                    </table>
                    <div id="allPoEmptyMessage" style="display:none; padding:40px 20px; text-align:center; color:#94a3b8;">
                        <i class="bi bi-inbox" style="font-size:32px; display:block; margin-bottom:8px;"></i>
                        Tidak ada data PO yang sesuai dengan pencarian.
                    </div>
                </div>

                <!-- Panel Kanan: Rincian Barang (reuse po-panel-right styles) -->
                <div class="po-panel-right">
                    <div class="po-detail-top-row">
                        <div class="po-detail-header-left">
                            <span class="po-badge-rincian">RINCIAN BARANG</span>
                            <span class="po-detail-nopo" id="allPoDetailNoPo">-</span>
                        </div>
                    </div>

                    <div class="po-detail-supplier-name" id="allPoDetailSupplier">-</div>

                    <div class="po-detail-boxes">
                        <div class="po-meta-card">
                            <span>Tgl Transaksi:</span>
                            <strong id="allPoDetailTglTrans">-</strong>
                        </div>
                        <div class="po-meta-card">
                            <span>Total Nominal:</span>
                            <strong id="allPoDetailJumlah" style="color:#0866c6;">-</strong>
                        </div>
                    </div>

                    <div class="po-keterangan-alert" id="allPoDetailKeteranganBox">
                        <strong><i class="bi bi-info-circle"></i> Catatan PO:</strong>
                        <span id="allPoDetailKeterangan">-</span>
                    </div>

                    <div class="po-items-title-bar">
                        <h5>Daftar Barang yang Dipesan</h5>
                        <span class="po-items-count-tag" id="allPoDetailItemsCount">0 Macam Barang</span>
                    </div>

                    <div class="po-items-table-wrap">
                        <table class="po-items-table">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Satuan</th>
                                    <th style="text-align:center;">Qty</th>
                                    <th style="text-align:right;">Nominal</th>
                                </tr>
                            </thead>
                            <tbody id="allPoDetailItemsBody">
                                <!-- JS rendered -->
                            </tbody>
                        </table>
                    </div>

                    <div class="po-detail-footer-summary">
                        <span>Total Nominal PO Terpilih:</span>
                        <strong id="allPoDetailTotal">Rp 0</strong>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="allpo-modal-bottom">
                <div class="allpo-bottom-info" id="allPoFooterCount">
                    Menampilkan 1-10 dari <?= count($allPoList) ?> total Purchasing Order
                </div>
                <div class="allpo-bottom-controls">
                    <button type="button" class="po-page-btn disabled">&lsaquo; Sebelumnya</button>
                    <button type="button" class="po-page-btn active">1</button>
                    <button type="button" class="po-page-btn disabled">Selanjutnya &rsaquo;</button>
                    <button type="button" class="po-btn-tutup" onclick="closeAllPoModal()">Tutup</button>
                </div>
            </div>

        </div>
    </div>
    <?php
}

/** JavaScript modal Purchase Order */
function renderPurchaseOrderScript(array $allPoList): void
{
    ?>
<script>
// ======================================
// MODAL DAFTAR PURCHASE ORDER (ALL PO)
// ======================================
const allPoData = <?= pembelian_json($allPoList) ?>;
let allPoSelectedIndex = 0;
let allPoFilteredIndices = allPoData.map((_, i) => i);
let allPoCurrentPage = 1;
const allPoPageSize = 10;

function openAllPoModal() {
    const modal = document.getElementById('allPoModal');
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Reset filter & halaman
    const searchInput = document.getElementById('allPoSearchInput');
    if (searchInput) searchInput.value = '';
    allPoCurrentPage = 1;
    filterAllPoList();
}

function closeAllPoModal() {
    const modal = document.getElementById('allPoModal');
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function handleAllPoBackdropClick(e) {
    if (e.target.id === 'allPoModal') {
        closeAllPoModal();
    }
}

function selectAllPo(index) {
    allPoSelectedIndex = index;
    const po = allPoData[index];

    // Beri highlight pada baris aktif
    const tbody = document.getElementById('allPoTableBody');
    if (tbody) {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(r => {
            if (parseInt(r.getAttribute('data-index')) === index) {
                r.classList.add('active-row');
            } else {
                r.classList.remove('active-row');
            }
        });
    }

    renderAllPoDetail(po);
}

function renderAllPoDetail(po) {
    if (!po) return;

    const noPoEl = document.getElementById('allPoDetailNoPo');
    if (noPoEl) noPoEl.innerText = po.no_po;

    const supplierEl = document.getElementById('allPoDetailSupplier');
    if (supplierEl) supplierEl.innerText = po.nama_supplier;

    const tglTransEl = document.getElementById('allPoDetailTglTrans');
    if (tglTransEl) tglTransEl.innerText = po.tgl_trans;

    const jumlahEl = document.getElementById('allPoDetailJumlah');
    if (jumlahEl) jumlahEl.innerText = formatRupiah(po.jumlah);

    const ketBox = document.getElementById('allPoDetailKeteranganBox');
    const ketText = document.getElementById('allPoDetailKeterangan');
    if (ketBox && ketText) {
        if (po.keterangan) {
            ketBox.style.display = 'block';
            ketText.innerText = ' ' + po.keterangan;
        } else {
            ketBox.style.display = 'none';
        }
    }

    const itemsCountEl = document.getElementById('allPoDetailItemsCount');
    if (itemsCountEl) itemsCountEl.innerText = `${po.items ? po.items.length : 0} Macam Barang`;

    const totalEl = document.getElementById('allPoDetailTotal');
    if (totalEl) totalEl.innerText = formatRupiah(po.jumlah);

    const itemsBody = document.getElementById('allPoDetailItemsBody');
    if (itemsBody) {
        itemsBody.innerHTML = '';
        if (po.items && po.items.length > 0) {
            po.items.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <div style="font-weight: 700; color: #1e293b;">${item.nama_barang}</div>
                        ${item.keterangan ? `<div class="po-item-note"><i class="bi bi-tag"></i> ${item.keterangan}</div>` : ''}
                    </td>
                    <td style="color: #64748b; font-weight: 600;">${item.satuan}</td>
                    <td class="qty-col">${item.qty}</td>
                    <td class="subtotal-col">${formatRupiah(item.subtotal || Math.round(po.jumlah / po.items.length))}</td>
                `;
                itemsBody.appendChild(tr);
            });
        }
    }
}

function renderAllPoTable() {
    const tbody = document.getElementById('allPoTableBody');
    const emptyMsg = document.getElementById('allPoEmptyMessage');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (allPoFilteredIndices.length === 0) {
        if (emptyMsg) emptyMsg.style.display = 'block';
        clearAllPoDetail();
        renderAllPoPagination(0, 0, 0);
        return;
    }

    if (emptyMsg) emptyMsg.style.display = 'none';

    // Hitung Paginasi
    const totalItems = allPoFilteredIndices.length;
    const totalPages = Math.ceil(totalItems / allPoPageSize) || 1;
    if (allPoCurrentPage > totalPages) allPoCurrentPage = totalPages;
    if (allPoCurrentPage < 1) allPoCurrentPage = 1;

    const startIndex = (allPoCurrentPage - 1) * allPoPageSize;
    const endIndex = Math.min(startIndex + allPoPageSize, totalItems);
    const pageIndices = allPoFilteredIndices.slice(startIndex, endIndex);

    pageIndices.forEach((dataIndex, loopIdx) => {
        const po = allPoData[dataIndex];
        const tr = document.createElement('tr');
        tr.setAttribute('data-index', dataIndex);
        if (dataIndex === allPoSelectedIndex) {
            tr.classList.add('active-row');
        }

        const globalNum = String(startIndex + loopIdx + 1).padStart(2, '0');
        const primaryItem = (po.items && po.items.length > 0) ? po.items[0] : { nama_barang: '-', satuan: '-', qty: 0 };
        const otherCount = po.items && po.items.length > 1 ? ` <span style="font-size:10px; color:#0866c6; font-weight:700;">(+${po.items.length - 1} lainnya)</span>` : '';

        tr.innerHTML = `
            <td class="allpo-row-num">${globalNum}</td>
            <td class="allpo-date-cell">${po.tgl_trans}</td>
            <td><span class="allpo-nopo-badge">${po.no_po}</span></td>
            <td class="allpo-supplier-cell">${po.nama_supplier}</td>
            <td>
                <div style="font-weight: 600; color: #1e293b;">${primaryItem.nama_barang}${otherCount}</div>
            </td>
            <td class="allpo-satuan-cell">${primaryItem.satuan}</td>
            <td class="allpo-qty-cell">${primaryItem.qty}</td>
            <td class="allpo-nominal-cell">${formatRupiah(po.jumlah)}</td>
            <td class="allpo-arrow-cell">
                <i class="bi bi-chevron-right"></i>
            </td>
        `;

        tr.addEventListener('click', () => {
            selectAllPo(dataIndex);
        });

        tbody.appendChild(tr);
    });

    // Pastikan ada item yang terpilih untuk rincian
    if (!pageIndices.includes(allPoSelectedIndex) && pageIndices.length > 0) {
        selectAllPo(pageIndices[0]);
    } else if (allPoData[allPoSelectedIndex]) {
        renderAllPoDetail(allPoData[allPoSelectedIndex]);
    }

    renderAllPoPagination(totalItems, startIndex + 1, endIndex);
}

function clearAllPoDetail() {
    const noPoEl = document.getElementById('allPoDetailNoPo');
    if (noPoEl) noPoEl.innerText = '-';
    const supplierEl = document.getElementById('allPoDetailSupplier');
    if (supplierEl) supplierEl.innerText = '-';
    const tglTransEl = document.getElementById('allPoDetailTglTrans');
    if (tglTransEl) tglTransEl.innerText = '-';
    const jumlahEl = document.getElementById('allPoDetailJumlah');
    if (jumlahEl) jumlahEl.innerText = '-';
    const ketBox = document.getElementById('allPoDetailKeteranganBox');
    if (ketBox) ketBox.style.display = 'none';
    const itemsCountEl = document.getElementById('allPoDetailItemsCount');
    if (itemsCountEl) itemsCountEl.innerText = '0 Macam Barang';
    const totalEl = document.getElementById('allPoDetailTotal');
    if (totalEl) totalEl.innerText = 'Rp 0';
    const itemsBody = document.getElementById('allPoDetailItemsBody');
    if (itemsBody) itemsBody.innerHTML = '';
}

function renderAllPoPagination(totalItems, startItem = 0, endItem = 0) {
    const footerCount = document.getElementById('allPoFooterCount');
    if (footerCount) {
        if (totalItems === 0) {
            footerCount.innerText = 'Menampilkan 0 Purchasing Order';
        } else {
            footerCount.innerText = `Menampilkan ${startItem}-${endItem} dari ${totalItems} total Purchasing Order`;
        }
    }

    const controls = document.querySelector('.allpo-bottom-controls');
    if (!controls) return;

    const totalPages = Math.ceil(totalItems / allPoPageSize) || 1;

    let html = '';
    // Tombol Sebelumnya
    const prevDisabled = allPoCurrentPage === 1 ? 'disabled' : '';
    html += `<button type="button" class="po-page-btn ${prevDisabled}" onclick="goToAllPoPage(${allPoCurrentPage - 1})">&lsaquo; Sebelumnya</button>`;

    // Nomor Halaman
    for (let p = 1; p <= totalPages; p++) {
        const activeClass = p === allPoCurrentPage ? 'active' : '';
        html += `<button type="button" class="po-page-btn ${activeClass}" onclick="goToAllPoPage(${p})">${p}</button>`;
    }

    // Tombol Selanjutnya
    const nextDisabled = allPoCurrentPage >= totalPages ? 'disabled' : '';
    html += `<button type="button" class="po-page-btn ${nextDisabled}" onclick="goToAllPoPage(${allPoCurrentPage + 1})">Selanjutnya &rsaquo;</button>`;

    // Tombol Tutup
    html += `<button type="button" class="po-btn-tutup" onclick="closeAllPoModal()">Tutup</button>`;

    controls.innerHTML = html;
}

function goToAllPoPage(page) {
    const totalPages = Math.ceil(allPoFilteredIndices.length / allPoPageSize) || 1;
    if (page < 1 || page > totalPages) return;
    allPoCurrentPage = page;
    renderAllPoTable();
}

function filterAllPoList() {
    const searchInput = document.getElementById('allPoSearchInput');
    const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';

    allPoFilteredIndices = [];

    allPoData.forEach((po, index) => {
        const matchSearch =
            !searchVal ||
            po.no_po.toLowerCase().includes(searchVal) ||
            po.nama_supplier.toLowerCase().includes(searchVal) ||
            po.tgl_trans.toLowerCase().includes(searchVal) ||
            (po.items && po.items.some(item => item.nama_barang.toLowerCase().includes(searchVal)));

        if (matchSearch) {
            allPoFilteredIndices.push(index);
        }
    });

    allPoCurrentPage = 1;
    renderAllPoTable();
}

function exportAllPoList() {
    alert(`Mengekspor ${allPoFilteredIndices.length} data Purchasing Order...`);
}
</script>
    <?php
}
