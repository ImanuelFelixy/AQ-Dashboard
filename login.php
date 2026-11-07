<?php
session_start();
require_once __DIR__ . '/koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare(
        "SELECT * FROM admin_users WHERE username = :username LIMIT 1"
    );

    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);

        $_SESSION['admin_id'] = $admin['id'];
$_SESSION['username'] = $admin['username'];

require_once __DIR__ . '/includes/activity-log.php';

catatAktivitas(
    $conn,
    'Login',
    'Autentikasi',
    'Administrator berhasil login ke dashboard'
);

header('Location: index.php');
exit;
    } else {
        $error = 'Username atau password salah.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Administrator | Aquamate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100">

<div class="card shadow-sm border-0 p-4" style="width: 420px; border-radius: 16px;">
    <h3 class="fw-bold text-primary">Aquamate Administrator</h3>
    <p class="text-secondary">Login ke Dashboard Monitoring.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input
    type="text"
    name="username"
    class="form-control"
    autocomplete="off"
    value=""
    required
>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input
    type="password"
    name="password"
    class="form-control"
    autocomplete="new-password"
    value=""
    required
>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            Login
        </button>
    </form>
</div>

</body>
</html>