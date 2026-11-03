<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? '';
$basePath = $basePath ?? (str_contains($_SERVER['PHP_SELF'], '/pages/') ? '../' : '');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fc">
    <title><?= htmlspecialchars($pageTitle) ?> | Aquamate Indonesia Industries</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $basePath ?>assets/css/style.css" rel="stylesheet">
</head>

<body>
<nav class="navbar navbar-expand-lg aquamate-navbar sticky-top">
    <div class="container-fluid nav-container">
        <a class="navbar-brand aquamate-brand" href="<?= $basePath ?>index.php" aria-label="Aquamate Home">
            <span class="brand-word">AQUAMATE<small>INDONESIA INDUSTRIES</small></span>
        </a>
        <button class="navbar-toggler nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Buka navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavigation">
            <ul class="navbar-nav mx-auto nav-links">
                <li class="nav-item"><a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="<?= $basePath ?>index.php"><i class="bi bi-house-door"></i> Home</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'penjualan' ? 'active' : '' ?>" href="<?= $basePath ?>Penjualan/Main-Penjualan.php"><i class="bi bi-graph-up-arrow"></i> Dashboard Penjualan</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'pembelian' ? 'active' : '' ?>" href="<?= $basePath ?>Pembelian/Main-Pembelian.php"><i class="bi bi-box-seam"></i> Dashboard Pembelian</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'produksi' ? 'active' : '' ?>" href="<?= $basePath ?>Produksi/Main-Produksi.php"><i class="bi bi-gear-wide-connected"></i> Dashboard Produksi</a></li>
            </ul>
            <div class="nav-user">
                <div class="user-avatar">A</div>
                <div class="user-copy"><span>Selamat datang</span><strong>Administrator</strong></div>
                <i class="bi bi-chevron-down user-chevron"></i>
            </div>
        </div>
    </div>
</nav>
