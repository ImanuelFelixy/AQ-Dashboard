<?php
session_start();
require_once __DIR__ . '/koneksi.php';

$message = '';
$adminExists = false;

$stmt = $conn->query("SELECT COUNT(*) FROM admin_users");
$adminExists = (int) $stmt->fetchColumn() > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$adminExists) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 10) {
        $message = 'Password minimal 10 karakter.';
    } elseif ($password !== $confirmPassword) {
        $message = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO admin_users (username, password_hash)
             VALUES (:username, :password_hash)"
        );

        $stmt->execute([
            ':username' => 'admin',
            ':password_hash' => $hash
        ]);

        $adminExists = true;
        $message = 'Akun Administrator berhasil dibuat!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Administrator - Aquamate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow-sm border-0 p-4" style="width: 420px; border-radius: 16px;">
        <h4 class="fw-bold text-primary mb-2">Aquamate Administrator</h4>
        <p class="text-muted small">Setup akun administrator dashboard.</p>

        <?php if ($message): ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($adminExists): ?>
            <div class="alert alert-success">
                Akun Administrator sudah tersedia.
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="admin" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password"
                           class="form-control" minlength="3" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" name="confirm_password"
                           class="form-control" minlength="3" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Buat Akun Administrator
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>