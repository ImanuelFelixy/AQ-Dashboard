<?php
session_start();

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/includes/activity-log.php';

if (isset($_SESSION['admin_id'])) {
    catatAktivitas(
        $conn,
        'Logout',
        'Autentikasi',
        'Administrator keluar dari dashboard'
    );
}

$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;