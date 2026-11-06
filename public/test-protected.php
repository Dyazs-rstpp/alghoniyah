
<?php

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';

// Cuman Superadmin, Admin, dan Guru yang boleh masuk.
AuthMiddleware::requireRole(
    ['superadmin', 'admin', 'guru'],
    '/smk-tpi-alghoniyyah/public/login-manajemen.php'
);

$user = Session::getUser();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengujian Middleware</title>
</head>
<body>

    <h2>Pengujian Middleware Berhasil!</h2>

    <p>
        Selamat datang,
        <?= htmlspecialchars($user['full_name']); ?>
    </p>

    <p>
        Role:
        <?= htmlspecialchars($user['role']); ?>
    </p>

    <p>Halaman ini hanya dapat diakses oleh Superadmin, Admin, dan Guru.</p>

</body>
</html>
