<?php
/*
|--------------------------------------------------------------------------
| KONFIGURASI DATABASE AQUAMATE
|--------------------------------------------------------------------------
*/

$host     = 'localhost';
$dbname   = 'aq_dashboard';
$username = 'root';
$password = '';

/*
|--------------------------------------------------------------------------
| KONEKSI DATABASE
|--------------------------------------------------------------------------
*/

try {

    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );

} catch (PDOException $e) {

    error_log("Koneksi database gagal: " . $e->getMessage());

    die("Tidak dapat terhubung ke database. Silakan periksa konfigurasi koneksi.");

}
?>