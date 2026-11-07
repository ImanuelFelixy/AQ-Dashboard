<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('catatAktivitas')) {

    function catatAktivitas(
        PDO $conn,
        string $aktivitas,
        string $modul,
        string $keterangan = ''
    ): void {

        $adminId = $_SESSION['admin_id'] ?? null;
        $namaUser = $_SESSION['username'] ?? 'Administrator';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        $sql = "INSERT INTO user_activity_log
                (admin_user_id, nama_user, aktivitas, modul, keterangan, ip_address)
                VALUES
                (:admin_id, :nama_user, :aktivitas, :modul, :keterangan, :ip_address)";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            'admin_id'   => $adminId,
            'nama_user'  => $namaUser,
            'aktivitas'  => $aktivitas,
            'modul'      => $modul,
            'keterangan' => $keterangan,
            'ip_address' => $ipAddress
        ]);
    }
}