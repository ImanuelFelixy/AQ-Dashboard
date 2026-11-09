<?php
/**
 * outsatnding_po.php
 * Modul "Outstanding PO" (card + modal daftar outstanding PO).
 * Dipanggil dari main_pembelian.php. Tabel sumber: purchasing_order.
 *
 * Definisi outstanding: PO yang total sisa_qty seluruh itemnya > 0
 * (masih ada barang yang belum diterima).
 */

/**
 * Ambil daftar Outstanding PO pada rentang tgl_trans.
 */
function getOutstandingPoList(?mysqli $db, string $start, string $end): array
{
    $rows = pembelian_query(
        $db,
        "SELECT o.no_trans,
                DATE_FORMAT(o.tgl_trans, '%d/%m/%Y') AS tgl_trans,
                DATE_FORMAT(o.tgl_kirim, '%d/%m/%Y') AS tgl_kirim,
                o.nama_supplier,
                o.nama_barang,
                COALESCE(o.satuan, '')     AS satuan,
                o.qty,
                o.sisa_qty,
                o.subtotal,
                COALESCE(o.status, '') AS status,
                COALESCE(o.keterangan, '') AS keterangan
         FROM purchasing_order o
         WHERE o.tgl_trans BETWEEN ? AND ?
           AND o.no_trans IN (
                SELECT x.no_trans
                FROM purchasing_order x
                GROUP BY x.no_trans
                HAVING SUM(x.sisa_qty) > 0
           )
         ORDER BY o.tgl_trans, o.no_trans, o.po_id",
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
                'tgl_kirim'     => $r['tgl_kirim'] ?: '-',
                'nama_supplier' => $r['nama_supplier'],
                'status'        => $r['status'], 
                'keterangan'    => '',
                'jumlah'        => 0.0,
                'items'         => [],
            ];
        }

        if ($list[$key]['keterangan'] === '' && trim($r['keterangan']) !== '') {
            $list[$key]['keterangan'] = trim($r['keterangan']);
        }

        $qty      = (int) $r['qty'];
        $sisa     = (int) $r['sisa_qty'];
        $qtyKirim = max($qty - $sisa, 0);
        $subtotal = (float) $r['subtotal'];

        $list[$key]['jumlah'] += $subtotal;
        $list[$key]['items'][] = [
            'nama_barang' => $r['nama_barang'],
            'satuan'      => $r['satuan'],
            'qty'         => $qty,
            'qty_kirim'   => $qtyKirim,
            'sisa_qty'    => $sisa,
            'subtotal'    => $subtotal,
            'keterangan'  => '',
        ];
    }

    return array_values($list);
}

/** Card metrik "Outstanding PO" */
function renderOutstandingPoCard(array $outstandingList): void
{
    $outstandingPO        = count($outstandingList);
    $outstandingPONominal = array_sum(array_column($outstandingList, 'jumlah'));
    ?>
            <!-- Card 2: Outstanding PO -->
            <div class="p-card card-outstanding" style="cursor: pointer;" onclick="openOutstandingModal()" title="Klik untuk meninjau Daftar Outstanding PO">
                <div class="p-card-header">
                    <h3 class="p-card-title">Outstanding<br>PO</h3>
                    <button type="button" class="p-circle-btn btn-blue" onclick="event.stopPropagation(); openOutstandingModal();" title="Lihat Detail Outstanding PO">
                        <i class="bi bi-arrow-up-right"></i>
                    </button>
                </div>

                <div class="p-card-metric">
                    <span class="p-metric-number"><?= $outstandingPO ?></span>
                    <span class="p-metric-unit">Transaksi</span>
                </div>

                <div class="p-card-footer">
                    <span>Total Nominal :</span>
                    <strong>Rp <?= number_format($outstandingPONominal, 0, ',', '.') ?>,00</strong>
                </div>
            </div>
    <?php
}

/** Modal daftar Outstanding PO */
function renderOutstandingPoModal(array $outstandingList): void
{
    ?>
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
                        <option value="Belum Diterima">Belum Diterima</option>
                        <option value="Diterima Sebagian">Diterima Sebagian</option>
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
    <?php
}

/** JavaScript modal Outstanding PO */
function renderOutstandingPoScript(array $outstandingList): void
{
    ?>
<script>
// ======================================
// MODAL DAFTAR OUTSTANDING PO
// ======================================
const outstandingData = <?= pembelian_json($outstandingList) ?>;
let selectedPoIndex = 0;
let filteredPoIndices = outstandingData.map((_, i) => i);

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
        const badgeClass = po.status === 'Diterima Sebagian' ? 'amber' : 'blue';

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

    const badgeClass = po.status === 'Diterima Sebagian' ? 'amber' : 'blue';
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
    if (filteredPoIndices.length === 0) {
        alert('Tidak ada data untuk diexport!');
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,\uFEFF";

    // Header Kolom untuk Outstanding PO
    csvContent += "No;No. PO;Supplier;Tanggal Transaksi;Tanggal Pengiriman;Total Nominal (Rp);Status\r\n";

    // Masukkan data baris PO
    filteredPoIndices.forEach((dataIndex, loopIdx) => {
        const po = outstandingData[dataIndex];
        const rowNum = loopIdx + 1;
        
        const noPo = `"${po.no_po.replace(/"/g, '""')}"`;
        const supplier = `"${po.nama_supplier.replace(/"/g, '""')}"`;
        const status = `"${po.status.replace(/"/g, '""')}"`;

        const row = [
            rowNum,
            noPo,
            supplier,
            po.tgl_trans,
            po.tgl_kirim,
            po.jumlah,
            status
        ].join(";");

        csvContent += row + "\r\n";
    });

    // Proses download file
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "Laporan_Outstanding_PO.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
    <?php
}