<?php
// ==========================================
// MODUL PURCHASE REQUEST (PURCHASE REQUISITION)
// ==========================================

/** Purchase Request (1 baris tabel = 1 barang, dikelompokkan per no_pr) */
function getPurchaseRequestList(?mysqli $db, string $start, string $end): array
{
    $rows = pembelian_query(
        $db,
        "SELECT no_pr,
                divisi,
                DATE_FORMAT(tgl_permintaan, '%d/%m/%Y') AS tgl_permintaan,
                DATE_FORMAT(deadline, '%d/%m/%Y')       AS tgl_deadline,
                nama_barang,
                qty,
                status
         FROM purchase_requests
         WHERE tgl_permintaan BETWEEN ? AND ?
         ORDER BY purchase_requests.tgl_permintaan, no_pr, pr_id",
        'ss',
        [$start, $end]
    );

    $list = [];
    foreach ($rows as $r) {
        $key = $r['no_pr'];
        if (!isset($list[$key])) {
            $list[$key] = [
                'no_pr'          => $r['no_pr'],
                'tgl_permintaan' => $r['tgl_permintaan'],
                'tgl_deadline'   => $r['tgl_deadline'],
                'divisi'         => $r['divisi'],
                'keterangan'     => '',
                'items'          => [],
            ];
        }
        $list[$key]['items'][] = [
            'nama_barang' => $r['nama_barang'],
            'qty'         => (int) $r['qty'],
            'status'      => $r['status'],
        ];
    }

    return array_values($list);
}

/** Card metrik Purchase Request */
function renderPurchaseRequestCard(array $purchaseRequestList): void
{
    ?>
    <div class="p-card card-submission" style="cursor: pointer;" onclick="openPurchaseRequestModal()" title="Klik untuk meninjau Daftar Purchase Request">
        <div class="p-card-header">
            <h3 class="p-card-title">Purchase<br>Request</h3>
            <button type="button" class="p-circle-btn btn-green" onclick="event.stopPropagation(); openPurchaseRequestModal();" title="Lihat Detail Purchase Request">
                <i class="bi bi-arrow-up-right"></i>
            </button>
        </div>

        <div class="p-card-metric">
            <span class="p-metric-number"><?= count($purchaseRequestList) ?></span>
            <span class="p-metric-unit">Request</span>
        </div>

        <div class="p-card-footer">
            <span class="status-indicator success">
                <span class="indicator-dot"></span>
                Memerlukan Konfirmasi
            </span>
        </div>
    </div>
    <?php
}

/** Modal daftar Purchase Request */
function renderPurchaseRequestModal(array $purchaseRequestList): void
{
    ?>
    <div class="pr-modal-overlay" id="purchaseRequestModal" onclick="handlePrBackdropClick(event)">
        <div class="pr-modal-window" onclick="event.stopPropagation()">

            <!-- Header Modal -->
            <div class="po-modal-header">
                <div class="po-header-left">
                    <div class="po-header-icon">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div class="po-header-text">
                        <h3>
                            Daftar Purchase Request
                            <span class="po-header-badge"><?= count($purchaseRequestList) ?> Request</span>
                        </h3>
                        <p>Klik baris No. PR untuk meninjau rincian barang</p>
                    </div>
                </div>

                <div class="po-header-actions">
                    <div class="po-search-input-wrap">
                        <i class="bi bi-search"></i>
                        <input
                            type="text"
                            id="prSearchInput"
                            placeholder="Cari No. PR, Divisi, Barang..."
                            oninput="filterPrList()"
                        >
                    </div>

                    <button type="button" class="po-btn-close-x" onclick="closePurchaseRequestModal()" title="Tutup Modal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Body Modal: Master-Detail -->
            <div class="pr-modal-body">

                <!-- Panel Kiri: Tabel PR -->
                <div class="pr-panel-left">
                    <table class="po-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;">#</th>
                                <th>No. PR &amp; Divisi</th>
                                <th>Tgl Permintaan</th>
                                <th>Deadline</th>
                                <th style="width: 30px;"></th>
                            </tr>
                        </thead>
                        <tbody id="prTableBody">
                            <!-- Rendered by JavaScript -->
                        </tbody>
                    </table>
                    <div id="prEmptyMessage" style="display: none; padding: 40px 20px; text-align: center; color: #94a3b8;">
                        <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                        Tidak ada data Purchase Request yang sesuai dengan pencarian.
                    </div>
                </div>

                <!-- Panel Kanan: Rincian Barang -->
                <div class="po-panel-right">
                    <div class="po-detail-top-row">
                        <div class="po-detail-header-left">
                            <span class="po-badge-rincian">RINCIAN BARANG</span>
                            <span class="po-detail-nopo" id="prDetailNoPr">-</span>
                        </div>
                    </div>

                    <div class="po-detail-supplier-name" id="prDetailDivisi">-</div>

                    <div class="po-detail-boxes">
                        <div class="po-meta-card">
                            <span>Tgl Permintaan:</span>
                            <strong id="prDetailTglMinta">-</strong>
                        </div>
                        <div class="po-meta-card">
                            <span>Deadline:</span>
                            <strong id="prDetailDeadline">-</strong>
                        </div>
                    </div>

                    <div class="po-keterangan-alert" id="prDetailKeteranganBox">
                        <strong><i class="bi bi-info-circle"></i> Catatan PR:</strong>
                        <span id="prDetailKeterangan">-</span>
                    </div>

                    <div class="po-items-title-bar">
                        <h5>Daftar Barang yang Diminta</h5>
                        <span class="po-items-count-tag" id="prDetailItemsCount">0 Macam Barang</span>
                    </div>

                    <div class="po-items-table-wrap">
                        <table class="po-items-table">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th style="text-align: center;">Qty</th>
                                    <th style="text-align: center;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="prDetailItemsBody">
                                <!-- Rendered by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Footer Modal -->
            <div class="po-modal-bottom">
                <div class="po-bottom-info" id="prFooterCount">
                    Menampilkan <?= count($purchaseRequestList) ?> total Purchase Request
                </div>

                <div class="po-bottom-controls">
                    <button type="button" class="po-btn-tutup" onclick="closePurchaseRequestModal()">Tutup</button>
                </div>
            </div>

        </div>
    </div>
    <?php
}

/** JavaScript modal Purchase Request */
function renderPurchaseRequestScript(array $purchaseRequestList): void
{
    ?>
<script>
// ======================================
// MODAL PURCHASE REQUEST
// ======================================
const prData = <?= pembelian_json($purchaseRequestList) ?>;
let filteredPrIndices = prData.map((_, i) => i);
let selectedPrIndex = 0;

function openPurchaseRequestModal() {
    const modal = document.getElementById('purchaseRequestModal');
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    const searchInput = document.getElementById('prSearchInput');
    if (searchInput) searchInput.value = '';
    selectedPrIndex = 0;
    filterPrList();
}

function closePurchaseRequestModal() {
    const modal = document.getElementById('purchaseRequestModal');
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function handlePrBackdropClick(e) {
    if (e.target.id === 'purchaseRequestModal') {
        closePurchaseRequestModal();
    }
}

function filterPrList() {
    const searchInput = document.getElementById('prSearchInput');
    const q = searchInput ? searchInput.value.toLowerCase().trim() : '';

    filteredPrIndices = [];
    prData.forEach((pr, index) => {
        const match = !q ||
            pr.no_pr.toLowerCase().includes(q) ||
            pr.divisi.toLowerCase().includes(q) ||
            pr.tgl_permintaan.toLowerCase().includes(q) ||
            (pr.items && pr.items.some(it => it.nama_barang.toLowerCase().includes(q)));

        if (match) filteredPrIndices.push(index);
    });

    renderPrTable();
}

function renderPrTable() {
    const tbody = document.getElementById('prTableBody');
    const emptyMsg = document.getElementById('prEmptyMessage');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (filteredPrIndices.length === 0) {
        if (emptyMsg) emptyMsg.style.display = 'block';
        clearPrDetail();
        return;
    }

    if (emptyMsg) emptyMsg.style.display = 'none';

    filteredPrIndices.forEach((dataIndex, loopIdx) => {
        const pr = prData[dataIndex];
        const tr = document.createElement('tr');
        if (dataIndex === selectedPrIndex) tr.classList.add('active-row');

        tr.innerHTML = `
            <td class="po-row-num">${String(loopIdx + 1).padStart(2, '0')}</td>
            <td>
                <div class="po-number-cell">${pr.no_pr}</div>
                <div class="po-supplier-cell">${pr.divisi}</div>
            </td>
            <td class="po-date-cell">${pr.tgl_permintaan}</td>
            <td class="po-date-cell">${pr.tgl_deadline}</td>
            <td class="po-arrow-cell"><i class="bi bi-chevron-right"></i></td>
        `;

        tr.addEventListener('click', () => selectPr(dataIndex));
        tbody.appendChild(tr);
    });

    if (!filteredPrIndices.includes(selectedPrIndex)) {
        selectPr(filteredPrIndices[0]);
    } else {
        renderPrDetail(prData[selectedPrIndex]);
    }

    const footer = document.getElementById('prFooterCount');
    if (footer) {
        footer.innerText = `Menampilkan ${filteredPrIndices.length} dari ${prData.length} total Purchase Request`;
    }
}

function selectPr(index) {
    selectedPrIndex = index;

    const rows = document.getElementById('prTableBody').querySelectorAll('tr');
    filteredPrIndices.forEach((dataIndex, loopIdx) => {
        if (rows[loopIdx]) {
            rows[loopIdx].classList.toggle('active-row', dataIndex === index);
        }
    });

    renderPrDetail(prData[index]);
}

function renderPrDetail(pr) {
    if (!pr) return;

    document.getElementById('prDetailNoPr').innerText = pr.no_pr;
    document.getElementById('prDetailDivisi').innerText = pr.divisi;
    document.getElementById('prDetailTglMinta').innerText = pr.tgl_permintaan;
    document.getElementById('prDetailDeadline').innerText = pr.tgl_deadline;

    const ketBox = document.getElementById('prDetailKeteranganBox');
    if (pr.keterangan) {
        ketBox.style.display = 'block';
        document.getElementById('prDetailKeterangan').innerText = ' ' + pr.keterangan;
    } else {
        ketBox.style.display = 'none';
    }

    const items = pr.items || [];
    document.getElementById('prDetailItemsCount').innerText = `${items.length} Macam Barang`;

    const body = document.getElementById('prDetailItemsBody');
    body.innerHTML = '';
    items.forEach(it => {
        const done = it.status === 'Sudah PO';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="font-weight: 700; color: #1e293b;">${it.nama_barang}</td>
            <td class="qty-col">${it.qty}</td>
            <td style="text-align: center;">
                <span class="pr-status-badge ${done ? 'done' : 'pending'}">${it.status}</span>
            </td>
        `;
        body.appendChild(tr);
    });
}

function clearPrDetail() {
    ['prDetailNoPr', 'prDetailDivisi', 'prDetailTglMinta', 'prDetailDeadline'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerText = '-';
    });

    const ketBox = document.getElementById('prDetailKeteranganBox');
    if (ketBox) ketBox.style.display = 'none';

    const count = document.getElementById('prDetailItemsCount');
    if (count) count.innerText = '0 Macam Barang';

    const body = document.getElementById('prDetailItemsBody');
    if (body) body.innerHTML = '';
}
</script>
    <?php
}