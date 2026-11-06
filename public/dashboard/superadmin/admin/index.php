
<?php

require_once __DIR__ . '/../../../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../../config/database.php';

AuthMiddleware::requireRole(
    ['superadmin'],
    '../../../login-manajemen.php'
);

Session::start();

$database = new Database();
$db = $database->connect();

$message = '';
$error = '';

// Ambil ID role Admin
$roleQuery = $db->prepare(
    "SELECT id FROM roles WHERE code = :code LIMIT 1"
);
$roleQuery->execute([':code' => 'admin']);
$adminRole = $roleQuery->fetch();

if (!$adminRole) {
    die('Role Admin tidak ditemukan di database.');
}

$adminRoleId = $adminRole['id'];

// Proses tambah, edit, ubah status, dan hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    try {

        // TAMBAH ADMIN
        if ($action === 'create') {

            $fullName = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';

            if (
                $fullName === '' ||
                $username === '' ||
                $password === ''
            ) {
                throw new Exception('Nama, username, dan password wajib diisi.');
            }

            if (strlen($password) < 8) {
                throw new Exception('Password minimal 8 karakter.');
            }

            // Periksa username atau email yang sudah digunakan
            $check = $db->prepare("
                SELECT id FROM users
                WHERE username = :username
                   OR (:email_check <> '' AND email = :email)
                LIMIT 1
            ");

            $check->execute([
                ':username' => $username,
                ':email_check' => $email,
                ':email' => $email
            ]);

            if ($check->fetch()) {
                throw new Exception('Username atau email sudah digunakan.');
            }

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $insert = $db->prepare("
                INSERT INTO users
                (role_id, full_name, username, email, phone, password_hash, status)
                VALUES
                (:role_id, :full_name, :username, :email, :phone, :password, 'active')
            ");

            $insert->execute([
                ':role_id' => $adminRoleId,
                ':full_name' => $fullName,
                ':username' => $username,
                ':email' => $email !== '' ? $email : null,
                ':phone' => $phone !== '' ? $phone : null,
                ':password' => $passwordHash
            ]);

            $message = 'Akun Admin berhasil ditambahkan.';

        }

        // EDIT ADMIN
        elseif ($action === 'update') {

            $id = (int) ($_POST['id'] ?? 0);
            $fullName = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if (
                $id <= 0 ||
                $fullName === '' ||
                $username === ''
            ) {
                throw new Exception('Nama dan username wajib diisi.');
            }

            // Pastikan akun yang diedit memang Admin
            $checkAdmin = $db->prepare("
                SELECT id FROM users
                WHERE id = :id AND role_id = :role_id
                LIMIT 1
            ");

            $checkAdmin->execute([
                ':id' => $id,
                ':role_id' => $adminRoleId
            ]);

            if (!$checkAdmin->fetch()) {
                throw new Exception('Data Admin tidak ditemukan.');
            }

            // Periksa username atau email milik akun lain
            $check = $db->prepare("
                SELECT id FROM users
                WHERE (
                    username = :username
                    OR (:email_check <> '' AND email = :email)
                )
                AND id <> :id
                LIMIT 1
            ");

            $check->execute([
                ':username' => $username,
                ':email_check' => $email,
                ':email' => $email,
                ':id' => $id
            ]);

            if ($check->fetch()) {
                throw new Exception('Username atau email sudah digunakan akun lain.');
            }

            // Simpan perubahan data Admin
            $update = $db->prepare("
                UPDATE users
                SET
                    full_name = :full_name,
                    username = :username,
                    email = :email,
                    phone = :phone
                WHERE id = :id
                AND role_id = :role_id
            ");

            $update->execute([
                ':full_name' => $fullName,
                ':username' => $username,
                ':email' => $email !== '' ? $email : null,
                ':phone' => $phone !== '' ? $phone : null,
                ':id' => $id,
                ':role_id' => $adminRoleId
            ]);

            $message = 'Data Admin berhasil diperbarui.';

        }

        // UBAH STATUS ADMIN
        elseif ($action === 'toggle') {

            $id = (int) ($_POST['id'] ?? 0);

            $update = $db->prepare("
                UPDATE users
                SET status = CASE
                    WHEN status = 'active' THEN 'inactive'
                    ELSE 'active'
                END
                WHERE id = :id AND role_id = :role_id
            ");

            $update->execute([
                ':id' => $id,
                ':role_id' => $adminRoleId
            ]);

            $message = 'Status akun Admin berhasil diperbarui.';

        }

        // HAPUS ADMIN
        elseif ($action === 'delete') {

            $id = (int) ($_POST['id'] ?? 0);

            $delete = $db->prepare("
                DELETE FROM users
                WHERE id = :id AND role_id = :role_id
            ");

            $delete->execute([
                ':id' => $id,
                ':role_id' => $adminRoleId
            ]);

            $message = 'Akun Admin berhasil dihapus.';
        }

    } catch (PDOException $e) {

        $error = 'Terjadi kesalahan database. Periksa kembali data yang dimasukkan.';

    } catch (Exception $e) {

        $error = $e->getMessage();
    }
}

// Ambil data Admin yang akan diedit
$editAdmin = null;

if (isset($_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    $editQuery = $db->prepare("
        SELECT id, full_name, username, email, phone
        FROM users
        WHERE id = :id
        AND role_id = :role_id
        LIMIT 1
    ");

    $editQuery->execute([
        ':id' => $editId,
        ':role_id' => $adminRoleId
    ]);

    $editAdmin = $editQuery->fetch();
}

// Ambil seluruh akun Admin
$query = $db->prepare("
    SELECT id, full_name, username, email, phone, status, created_at
    FROM users
    WHERE role_id = :role_id
    ORDER BY created_at DESC
");

$query->execute([':role_id' => $adminRoleId]);

$admins = $query->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manajemen Admin | SMK TPI Al-Ghoniyyah</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f5;
            color: #26352f;
            padding: 30px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
        }

        .header h1 {
            font-size: 25px;
        }

        .header p {
            color: #77847d;
            font-size: 14px;
            margin-top: 7px;
        }

        .back {
            color: #07543f;
            text-decoration: none;
            font-size: 14px;
        }

        .panel {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,.04);
        }

        .panel h2 {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #dce3df;
            border-radius: 6px;
            outline: none;
        }

        .form-group input:focus {
            border-color: #087451;
        }

        button {
            cursor: pointer;
            border: none;
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 13px;
        }

        .btn-primary {
            background: #07543f;
            color: white;
            margin-top: 20px;
        }

        .btn-primary:hover {
            background: #087451;
        }

        .btn-toggle {
            background: #e9f2ed;
            color: #07543f;
        }

        .btn-delete {
            background: #fff0f0;
            color: #b42318;
        }

        .btn-edit {
            display: inline-block;
            background: #e8f0ff;
            color: #2455a4;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
        }

        .btn-cancel {
            display: inline-block;
            margin-left: 12px;
            color: #07543f;
            text-decoration: none;
            font-size: 13px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th, td {
            text-align: left;
            padding: 14px;
            border-bottom: 1px solid #edf0ee;
            font-size: 13px;
        }

        th {
            background: #f7f9f8;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
        }

        .active {
            background: #e4f5eb;
            color: #147443;
        }

        .inactive {
            background: #fff0e5;
            color: #a45116;
        }

        .message {
            padding: 13px;
            background: #e4f5eb;
            color: #147443;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .error {
            padding: 13px;
            background: #fff0f0;
            color: #b42318;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .actions {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        .empty {
            text-align: center;
            color: #7b8982;
            padding: 30px;
        }

        @media (max-width: 650px) {
            body {
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <div>
            <h1>Manajemen Data Admin</h1>
            <p>Kelola akun administrator sekolah.</p>
        </div>

        <a class="back" href="../index.php">
            &larr; Kembali ke Dashboard
        </a>
    </div>

    <?php if ($message): ?>
        <div class="message">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>


    <!-- FORM EDIT ADMIN -->
    <?php if ($editAdmin): ?>

        <div class="panel">

            <h2>Edit Data Admin</h2>

            <form method="POST">

                <input type="hidden" name="action" value="update">

                <input
                    type="hidden"
                    name="id"
                    value="<?= $editAdmin['id']; ?>"
                >

                <div class="form-grid">

                    <div class="form-group">
                        <label>Nama Lengkap *</label>
                        <input
                            type="text"
                            name="full_name"
                            value="<?= htmlspecialchars($editAdmin['full_name']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Username *</label>
                        <input
                            type="text"
                            name="username"
                            value="<?= htmlspecialchars($editAdmin['username']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($editAdmin['email'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label>Nomor Telepon</label>
                        <input
                            type="text"
                            name="phone"
                            value="<?= htmlspecialchars($editAdmin['phone'] ?? ''); ?>"
                        >
                    </div>

                </div>

                <button type="submit" class="btn-primary">
                    Simpan Perubahan
                </button>

                <a href="index.php" class="btn-cancel">
                    Batal
                </a>

            </form>

        </div>

    <?php endif; ?>


    <!-- FORM TAMBAH ADMIN -->
    <div class="panel">

        <h2>Tambah Akun Admin</h2>

        <form method="POST">

            <input type="hidden" name="action" value="create">

            <div class="form-grid">

                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="full_name" required>
                </div>

                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email">
                </div>

                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input type="text" name="phone">
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" minlength="8" required>
                </div>

            </div>

            <button type="submit" class="btn-primary">
                + Tambahkan Admin
            </button>

        </form>

    </div>


    <!-- TABEL DAFTAR ADMIN -->
    <div class="panel">

        <h2>Daftar Admin (<?= count($admins); ?>)</h2>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Status</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($admins) > 0): ?>

                        <?php foreach ($admins as $index => $admin): ?>

                            <tr>
                                <td><?= $index + 1; ?></td>

                                <td>
                                    <?= htmlspecialchars($admin['full_name']); ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($admin['username']); ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($admin['email'] ?? '-'); ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($admin['phone'] ?? '-'); ?>
                                </td>

                                <td>
                                    <span class="status <?= $admin['status'] === 'active' ? 'active' : 'inactive'; ?>">
                                        <?= htmlspecialchars($admin['status']); ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="actions">

                                        <!-- EDIT -->
                                        <a
                                            href="index.php?edit=<?= $admin['id']; ?>"
                                            class="btn-edit"
                                        >
                                            Edit
                                        </a>

                                        <!-- UBAH STATUS -->
                                        <form method="POST">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= $admin['id']; ?>">

                                            <button
                                                class="btn-toggle"
                                                type="submit"
                                                onclick="return confirm('Ubah status akun Admin ini?')">
                                                Ubah Status
                                            </button>
                                        </form>

                                        <!-- HAPUS -->
                                        <form method="POST">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $admin['id']; ?>">

                                            <button
                                                class="btn-delete"
                                                type="submit"
                                                onclick="return confirm('Yakin ingin menghapus akun Admin ini?')">
                                                Hapus
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="empty">
                                Belum ada akun Admin.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>
