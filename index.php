<?php
$pageTitle = 'Home';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>
<main class="container-fluid page-shell">
    <section class="welcome-panel">
        <div class="welcome-copy">
            <span class="eyebrow"><i class="bi bi-stars"></i> AQUAMATE BUSINESS INTELLIGENCE</span>
            <h1><span>Selamat datang di Aquamate.</span></h1>
            <p>Pusat informasi operasional untuk membantu memantau aktivitas penjualan, pembelian, dan produksi dalam satu tempat.</p>
        </div>
        <div class="welcome-art" aria-hidden="true">
            <div class="art-orbit orbit-one"></div>
            <div class="art-orbit orbit-two"></div>
            <div class="art-glass">
                <img
                    src="https://images.squarespace-cdn.com/content/v1/686dcb511f1eac53aee09c48/e5d6d21d-bda0-415b-94fe-5b808a9aa30f/Untitled+design.png"
                    alt="Logo Aquamate"
                    class="aquamate-logo"
                >
            </div>
            <span class="art-dot dot-one"></span><span class="art-dot dot-two"></span>
        </div>
    </section>

    <div class="section-heading">
        <div>
            <h2>PUSAT DASHBOARD</h2>
            <p>Pilih dashboard yang ingin Anda lihat.</p>
        </div>
        <span class="live-indicator"><span></span> Sistem siap digunakan</span>
    </div>

    <section class="row g-4 dashboard-grid">
        <div class="col-md-6 col-xl-4">
            <a class="module-card sales-card" href="Penjualan/Main-Penjualan.php">
                <div class="module-icon"><i class="bi bi-graph-up-arrow"></i></div>
                <span class="module-tag">COMMERCIAL</span>
                <h3>Dashboard Penjualan</h3>
                <p>Pantau aktivitas penjualan, pesanan pelanggan, dan perkembangan transaksi.</p>
                <span class="module-link">Buka dashboard <i class="bi bi-arrow-right"></i></span>
            </a>
        </div>
        <div class="col-md-6 col-xl-4">
            <a class="module-card purchase-card" href="Pembelian/Main-Pembelian.php">
                <div class="module-icon"><i class="bi bi-box-seam"></i></div>
                <span class="module-tag">PROCUREMENT</span>
                <h3>Dashboard Pembelian</h3>
                <p>Telusuri informasi pembelian, kebutuhan material, dan aktivitas pengadaan.</p>
                <span class="module-link">Buka dashboard <i class="bi bi-arrow-right"></i></span>
            </a>
        </div>
        <div class="col-md-6 col-xl-4">
            <a class="module-card production-card" href="Produksi/Main-Produksi.php">
                <div class="module-icon"><i class="bi bi-gear-wide-connected"></i></div>
                <span class="module-tag">OPERATIONS</span>
                <h3>Dashboard Produksi</h3>
                <p>Memantau keseluruhan proses produksi mulai dari Work Order, permintaan dan penyerahan bahan, 
                    hingga progres produksi, pencapaian target setiap mesin, serta tingkat reject secara terintegrasi.</p>
                <span class="module-link">Buka dashboard <i class="bi bi-arrow-right"></i></span>
            </a>
        </div>
    </section>

    <section class="info-strip">
        <div class="info-strip-icon"><i class="bi bi-lightbulb"></i></div>
        <div>
            <h3>Sistem Dashboard Aquamate</h3>
            <p>Pembelian Aman, Penjualan Nyaman, Produksi Jadi Andalan.</p>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
