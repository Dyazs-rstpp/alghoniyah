<?php

require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../app/Middleware/AuthMiddleware.php';

// Khusus Admin
AuthMiddleware::requireRole(
    ['admin'],
    '/smk-tpi-alghoniyyah/public/login-manajemen.php'
);

// URL dashboard sesuai role yang sedang login
$dashboardUrl = AuthMiddleware::getDashboardUrl();

$db = (new Database())->connect();

$message = '';
$error = '';


function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getEnumValues(PDO $db, $table, $column, $fallback)
{
    $stmt = $db->prepare("
        SELECT COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
        AND COLUMN_NAME = ?
    ");

    $stmt->execute([$table, $column]);
    $type = $stmt->fetchColumn();

    if ($type && strpos($type, 'enum(') === 0) {
        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches);

        if (!empty($matches[1])) {
            return array_map(
                function ($value) {
                    return stripcslashes($value);
                },
                $matches[1]
            );
        }
    }

    return $fallback;
}

$genderOptions = getEnumValues(
    $db,
    'teachers',
    'gender',
    ['Laki-laki', 'Perempuan']
);

$employmentOptions = getEnumValues(
    $db,
    'teachers',
    'employment_status',
    ['PNS', 'PPPK', 'GTY', 'GTT', 'Honorer']
);



$stmtRole = $db->prepare("SELECT id FROM roles WHERE code = 'guru'");
$stmtRole->execute();
$guruRole = $stmtRole->fetch(PDO::FETCH_ASSOC);

if (!$guruRole) {
    die("Role guru belum tersedia di tabel roles.");
}

$guruRoleId = $guruRole['id'];

/*
|--------------------------------------------------------------------------
| PROSES FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    try {

        // TAMBAH GURU
        if ($action === 'tambah') {

            $fullName = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';

            $employeeNumber = trim($_POST['employee_number'] ?? '');
            $gender = $_POST['gender'] ?? '';
            $birthPlace = trim($_POST['birth_place'] ?? '');
            $birthDate = $_POST['birth_date'] ?? '';
            $address = trim($_POST['address'] ?? '');
            $employmentStatus = $_POST['employment_status'] ?? '';

            if (
                $fullName === '' ||
                $username === '' ||
                $password === '' ||
                $employeeNumber === '' ||
                $gender === '' ||
                $birthPlace === '' ||
                $birthDate === '' ||
                $address === '' ||
                $employmentStatus === ''
            ) {
                throw new Exception("Semua kolom wajib diisi.");
            }

            if (!in_array($gender, $genderOptions, true)) {
                throw new Exception("Pilihan jenis kelamin tidak valid.");
            }

            if (!in_array($employmentStatus, $employmentOptions, true)) {
                throw new Exception("Status kepegawaian tidak valid.");
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Format email tidak valid.");
            }

            $db->beginTransaction();

            // Buat akun login guru
            $stmt = $db->prepare("
                INSERT INTO users
                (role_id, full_name, username, email, phone, password_hash, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
            ");

            $stmt->execute([
                $guruRoleId,
                $fullName,
                $username,
                $email !== '' ? $email : null,
                $phone !== '' ? $phone : null,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $userId = $db->lastInsertId();

            // Simpan identitas guru
            $stmt = $db->prepare("
                INSERT INTO teachers
                (
                    user_id,
                    employee_number,
                    gender,
                    birth_place,
                    birth_date,
                    address,
                    employment_status,
                    created_at,
                    updated_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $stmt->execute([
                $userId,
                $employeeNumber,
                $gender,
                $birthPlace,
                $birthDate,
                $address,
                $employmentStatus
            ]);

            $db->commit();

            header("Location: index.php?success=tambah");
            exit;
        }

        // EDIT GURU
        elseif ($action === 'edit') {

            $teacherId = (int) ($_POST['teacher_id'] ?? 0);

            $fullName = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            $employeeNumber = trim($_POST['employee_number'] ?? '');
            $gender = $_POST['gender'] ?? '';
            $birthPlace = trim($_POST['birth_place'] ?? '');
            $birthDate = $_POST['birth_date'] ?? '';
            $address = trim($_POST['address'] ?? '');
            $employmentStatus = $_POST['employment_status'] ?? '';

            if (
                $teacherId <= 0 ||
                $fullName === '' ||
                $username === '' ||
                $employeeNumber === '' ||
                $gender === '' ||
                $birthPlace === '' ||
                $birthDate === '' ||
                $address === '' ||
                $employmentStatus === ''
            ) {
                throw new Exception("Data edit belum lengkap.");
            }

            if (!in_array($gender, $genderOptions, true)) {
                throw new Exception("Pilihan jenis kelamin tidak valid.");
            }

            if (!in_array($employmentStatus, $employmentOptions, true)) {
                throw new Exception("Status kepegawaian tidak valid.");
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Format email tidak valid.");
            }

            $stmt = $db->prepare("
                SELECT t.user_id
                FROM teachers t
                JOIN users u ON u.id = t.user_id
                WHERE t.id = ? AND u.role_id = ?
            ");

            $stmt->execute([$teacherId, $guruRoleId]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$teacher) {
                throw new Exception("Data guru tidak ditemukan.");
            }

            $db->beginTransaction();

            // Update akun guru
            $stmt = $db->prepare("
                UPDATE users
                SET full_name = ?, username = ?, email = ?, phone = ?
                WHERE id = ? AND role_id = ?
            ");

            $stmt->execute([
                $fullName,
                $username,
                $email !== '' ? $email : null,
                $phone !== '' ? $phone : null,
                $teacher['user_id'],
                $guruRoleId
            ]);

            // Update identitas guru
            $stmt = $db->prepare("
                UPDATE teachers
                SET employee_number = ?,
                    gender = ?,
                    birth_place = ?,
                    birth_date = ?,
                    address = ?,
                    employment_status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $employeeNumber,
                $gender,
                $birthPlace,
                $birthDate,
                $address,
                $employmentStatus,
                $teacherId
            ]);

            // Password hanya diubah jika diisi
            $newPassword = $_POST['password'] ?? '';

            if ($newPassword !== '') {
                $stmt = $db->prepare("
                    UPDATE users
                    SET password_hash = ?
                    WHERE id = ? AND role_id = ?
                ");

                $stmt->execute([
                    password_hash($newPassword, PASSWORD_DEFAULT),
                    $teacher['user_id'],
                    $guruRoleId
                ]);
            }

            $db->commit();

            header("Location: index.php?success=edit");
            exit;
        }

        // AKTIF / NONAKTIF
        elseif ($action === 'status') {

            $userId = (int) ($_POST['user_id'] ?? 0);

            $stmt = $db->prepare("
                UPDATE users
                SET status = CASE
                    WHEN status = 'active' THEN 'inactive'
                    ELSE 'active'
                END
                WHERE id = ? AND role_id = ?
            ");

            $stmt->execute([$userId, $guruRoleId]);

            header("Location: index.php?success=status");
            exit;
        }

        // HAPUS GURU
        elseif ($action === 'hapus') {

            $teacherId = (int) ($_POST['teacher_id'] ?? 0);

            $stmt = $db->prepare("
                SELECT t.user_id
                FROM teachers t
                JOIN users u ON u.id = t.user_id
                WHERE t.id = ? AND u.role_id = ?
            ");

            $stmt->execute([$teacherId, $guruRoleId]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$teacher) {
                throw new Exception("Data guru tidak ditemukan.");
            }

            $db->beginTransaction();

            $stmt = $db->prepare("DELETE FROM teachers WHERE id = ?");
            $stmt->execute([$teacherId]);

            $stmt = $db->prepare("
                DELETE FROM users
                WHERE id = ? AND role_id = ?
            ");

            $stmt->execute([
                $teacher['user_id'],
                $guruRoleId
            ]);

            $db->commit();

            header("Location: index.php?success=hapus");
            exit;
        }

    } catch (Exception $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $error = $e->getMessage();

    } catch (PDOException $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $error = "Terjadi kesalahan database. Periksa username, email, dan nomor induk guru agar tidak duplikat.";
    }
}

//notif

if (isset($_GET['success'])) {
    $successMessages = [
        'tambah' => 'Data guru berhasil ditambahkan.',
        'edit' => 'Data guru berhasil diperbarui.',
        'status' => 'Status akun guru berhasil diperbarui.',
        'hapus' => 'Data guru berhasil dihapus.'
    ];

    $message = $successMessages[$_GET['success']] ?? '';
}

// data edit
$editGuru = null;

if (isset($_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    $stmt = $db->prepare("
        SELECT
            t.*,
            u.full_name,
            u.username,
            u.email,
            u.phone
        FROM teachers t
        JOIN users u ON u.id = t.user_id
        WHERE t.id = ? AND u.role_id = ?
    ");

    $stmt->execute([$editId, $guruRoleId]);
    $editGuru = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Ambil data guru

$stmt = $db->prepare("
    SELECT
        t.id AS teacher_id,
        t.employee_number,
        t.gender,
        t.birth_place,
        t.birth_date,
        t.address,
        t.employment_status,
        u.id AS user_id,
        u.full_name,
        u.username,
        u.email,
        u.phone,
        u.status,
        u.created_at
    FROM teachers t
    JOIN users u ON u.id = t.user_id
    WHERE u.role_id = ?
    ORDER BY t.id DESC
");

$stmt->execute([$guruRoleId]);
$gurus = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Guru</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #222;
        }

        .container {
            max-width: 1250px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        h2 {
            margin: 0 0 7px;
        }

        .subtitle {
            color: #777;
            font-size: 14px;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        button, .btn {
            border: none;
            border-radius: 6px;
            padding: 10px 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
        }

        .btn-primary {
            background: #198754;
            color: white;
        }

        .btn-edit {
            background: #ffc107;
            color: #222;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-status {
            background: #6c757d;
            color: white;
        }

        .btn-cancel {
            background: #ddd;
            color: #222;
        }

        .alert {
            padding: 13px;
            border-radius: 6px;
            margin-bottom: 18px;
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .danger {
            background: #f8d7da;
            color: #842029;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        th {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
        }

        .active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .inactive {
            background: #f8d7da;
            color: #842029;
        }

        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .actions form {
            margin: 0;
        }

        @media(max-width: 650px) {
            body {
                padding: 12px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <div>
            <h2>Manajemen Data Guru</h2>
            <div class="subtitle">
                Kelola akun dan informasi tenaga pendidik sekolah.
            </div>
        </div>

        <a href="<?= e($dashboardUrl) ?>" class="btn btn-cancel">
            Kembali ke Dashboard </a>
    </div>

    <?php if ($message): ?>
        <div class="alert success">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert danger">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="card">

        <h3>
            <?= $editGuru ? 'Edit Data Guru' : 'Tambah Guru Baru' ?>
        </h3>

        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="<?= $editGuru ? 'edit' : 'tambah' ?>"
            >

            <?php if ($editGuru): ?>
                <input
                    type="hidden"
                    name="teacher_id"
                    name="teacher_id"
                    value="<?= e($editGuru['id']) ?>"
                >
            <?php endif; ?>

            <div class="form-grid">

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input
                        type="text"
                        name="full_name"
                        required
                        value="<?= e($editGuru['full_name'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Nomor Induk Pegawai / NIP</label>
                    <input
                        type="text"
                        name="employee_number"
                        required
                        value="<?= e($editGuru['employee_number'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input
                        type="text"
                        name="username"
                        required
                        value="<?= e($editGuru['username'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        value="<?= e($editGuru['email'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input
                        type="text"
                        name="phone"
                        value="<?= e($editGuru['phone'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>
                        Password
                        <?= $editGuru ? '(Kosongkan jika tidak diubah)' : '' ?>
                    </label>
                    <input
                        type="password"
                        name="password"
                        <?= $editGuru ? '' : 'required' ?>
                    >
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="gender" required>
                        <option value="">-- Pilih Jenis Kelamin --</option>

                        <?php foreach ($genderOptions as $option): ?>
                            <option
                                value="<?= e($option) ?>"
                                <?= (($editGuru['gender'] ?? '') === $option) ? 'selected' : '' ?>
                            >
                                <?= e($option) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label>Tempat Lahir</label>
                    <input
                        type="text"
                        name="birth_place"
                        required
                        value="<?= e($editGuru['birth_place'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input
                        type="date"
                        name="birth_date"
                        required
                        value="<?= e($editGuru['birth_date'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Status Kepegawaian</label>
                    <select name="employment_status" required>
                        <option value="">-- Pilih Status --</option>

                        <?php foreach ($employmentOptions as $option): ?>
                            <option
                                value="<?= e($option) ?>"
                                <?= (($editGuru['employment_status'] ?? '') === $option) ? 'selected' : '' ?>
                            >
                                <?= e($option) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group full">
                    <label>Alamat</label>
                    <textarea
                        name="address"
                        required
                    ><?= e($editGuru['address'] ?? '') ?></textarea>
                </div>

            </div>

            <br>

            <button type="submit" class="btn-primary">
                <?= $editGuru ? 'Simpan Perubahan' : 'Tambah Guru' ?>
            </button>

            <?php if ($editGuru): ?>
                <a href="index.php" class="btn btn-cancel">
                    Batal Edit
                </a>
            <?php endif; ?>

        </form>

    </div>

    <div class="card">

        <h3>Daftar Guru</h3>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Guru</th>
                        <th>NIP / Nomor Induk</th>
                        <th>Username</th>
                        <th>Jenis Kelamin</th>
                        <th>Status Kepegawaian</th>
                        <th>Status Akun</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($gurus) > 0): ?>

                        <?php $no = 1; ?>

                        <?php foreach ($gurus as $guru): ?>

                            <tr>
                                <td><?= $no++ ?></td>

                                <td><?= e($guru['full_name']) ?></td>

                                <td><?= e($guru['employee_number']) ?></td>

                                <td><?= e($guru['username']) ?></td>

                                <td><?= e($guru['gender']) ?></td>

                                <td><?= e($guru['employment_status']) ?></td>

                                <td>
                                    <span class="badge <?= $guru['status'] === 'active' ? 'active' : 'inactive' ?>">
                                        <?= e($guru['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="actions">

                                        <a
                                            href="index.php?edit=<?= e($guru['teacher_id']) ?>"
                                            class="btn btn-edit"
                                        >
                                            Edit
                                        </a>

                                        <form method="POST">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="user_id" value="<?= e($guru['user_id']) ?>">

                                            <button type="submit" class="btn-status">
                                                <?= $guru['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                            </button>
                                        </form>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus data guru ini beserta akun loginnya?')"
                                        >
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="teacher_id" value="<?= e($guru['teacher_id']) ?>">

                                            <button type="submit" class="btn-delete">
                                                Hapus
                                            </button>
                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" style="text-align:center;">
                                Belum ada data guru.
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
