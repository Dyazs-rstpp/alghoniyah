
<?php

require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../app/Middleware/AuthMiddleware.php';

// PENGAMAN HALAMAN
// Salin pemanggilan AuthMiddleware yang sama persis
// dari halaman public/dashboard/superadmin/guru/index.php.

$db = (new Database())->connect();

$message = '';
$error = '';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Pilihan agama
$religionOptions = [
    'Islam',
    'Kristen',
    'Katolik',
    'Hindu',
    'Buddha',
    'Konghucu',
    'Lainnya'
];

// Status siswa mengikuti ENUM database
$studentStatusOptions = [
    'active',
    'graduated',
    'transferred',
    'inactive'
];

$statusLabels = [
    'active' => 'Aktif',
    'graduated' => 'Lulus',
    'transferred' => 'Pindah',
    'inactive' => 'Tidak Aktif'
];

// Ambil role siswa
$stmtRole = $db->prepare("SELECT id FROM roles WHERE code = 'siswa'");
$stmtRole->execute();

$studentRole = $stmtRole->fetch(PDO::FETCH_ASSOC);

if (!$studentRole) {
    die("Role siswa belum tersedia di tabel roles.");
}

$studentRoleId = $studentRole['id'];

/*
|--------------------------------------------------------------------------
| PROSES FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    try {

        // TAMBAH SISWA
        if ($action === 'tambah') {

            $fullName = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';

            $nis = trim($_POST['nis'] ?? '');
            $nisn = trim($_POST['nisn'] ?? '');
            $nik = trim($_POST['nik'] ?? '');
            $gender = $_POST['gender'] ?? '';
            $birthPlace = trim($_POST['birth_place'] ?? '');
            $birthDate = $_POST['birth_date'] ?? '';
            $religion = $_POST['religion'] ?? '';
            $address = trim($_POST['address'] ?? '');
            $parentName = trim($_POST['parent_name'] ?? '');
            $parentPhone = trim($_POST['parent_phone'] ?? '');
            $admissionYear = trim($_POST['admission_year'] ?? '');
            $studentStatus = $_POST['student_status'] ?? 'active';

            if (
                $fullName === '' ||
                $username === '' ||
                $password === '' ||
                $nis === '' ||
                $gender === '' ||
                $birthPlace === '' ||
                $birthDate === '' ||
                $address === '' ||
                $admissionYear === ''
            ) {
                throw new Exception("Kolom wajib belum lengkap.");
            }

            if (!in_array($gender, ['L', 'P'], true)) {
                throw new Exception("Jenis kelamin tidak valid.");
            }

            if (!in_array($religion, $religionOptions, true)) {
                throw new Exception("Pilihan agama tidak valid.");
            }

            if (!in_array($studentStatus, $studentStatusOptions, true)) {
                throw new Exception("Status siswa tidak valid.");
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Format email tidak valid.");
            }

            if (!ctype_digit($admissionYear) || strlen($admissionYear) !== 4) {
                throw new Exception("Tahun masuk harus berupa 4 angka.");
            }

            $db->beginTransaction();

            // Buat akun login
            $stmt = $db->prepare("
                INSERT INTO users
                (
                    role_id,
                    full_name,
                    username,
                    email,
                    phone,
                    password_hash,
                    status,
                    created_at
                )
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
            ");

            $stmt->execute([
                $studentRoleId,
                $fullName,
                $username,
                $email !== '' ? $email : null,
                $phone !== '' ? $phone : null,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $userId = $db->lastInsertId();

            // Simpan data siswa
            $stmt = $db->prepare("
                INSERT INTO students
                (
                    user_id,
                    nis,
                    nisn,
                    nik,
                    gender,
                    birth_place,
                    birth_date,
                    religion,
                    address,
                    parent_name,
                    parent_phone,
                    admission_year,
                    student_status,
                    created_at,
                    updated_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $stmt->execute([
                $userId,
                $nis,
                $nisn !== '' ? $nisn : null,
                $nik !== '' ? $nik : null,
                $gender,
                $birthPlace,
                $birthDate,
                $religion !== '' ? $religion : null,
                $address,
                $parentName !== '' ? $parentName : null,
                $parentPhone !== '' ? $parentPhone : null,
                $admissionYear,
                $studentStatus
            ]);

            $db->commit();

            header("Location: index.php?success=tambah");
            exit;
        }

        // EDIT SISWA
        elseif ($action === 'edit') {

            $studentId = (int) ($_POST['student_id'] ?? 0);

            $fullName = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            $nis = trim($_POST['nis'] ?? '');
            $nisn = trim($_POST['nisn'] ?? '');
            $nik = trim($_POST['nik'] ?? '');
            $gender = $_POST['gender'] ?? '';
            $birthPlace = trim($_POST['birth_place'] ?? '');
            $birthDate = $_POST['birth_date'] ?? '';
            $religion = $_POST['religion'] ?? '';
            $address = trim($_POST['address'] ?? '');
            $parentName = trim($_POST['parent_name'] ?? '');
            $parentPhone = trim($_POST['parent_phone'] ?? '');
            $admissionYear = trim($_POST['admission_year'] ?? '');
            $studentStatus = $_POST['student_status'] ?? '';

            if (
                $studentId <= 0 ||
                $fullName === '' ||
                $username === '' ||
                $nis === '' ||
                $gender === '' ||
                $birthPlace === '' ||
                $birthDate === '' ||
                $address === '' ||
                $admissionYear === ''
            ) {
                throw new Exception("Data edit belum lengkap.");
            }

            if (!in_array($gender, ['L', 'P'], true)) {
                throw new Exception("Jenis kelamin tidak valid.");
            }

            if (!in_array($religion, $religionOptions, true)) {
                throw new Exception("Pilihan agama tidak valid.");
            }

            if (!in_array($studentStatus, $studentStatusOptions, true)) {
                throw new Exception("Status siswa tidak valid.");
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Format email tidak valid.");
            }

            if (!ctype_digit($admissionYear) || strlen($admissionYear) !== 4) {
                throw new Exception("Tahun masuk harus berupa 4 angka.");
            }

            $stmt = $db->prepare("
                SELECT s.user_id
                FROM students s
                JOIN users u ON u.id = s.user_id
                WHERE s.id = ? AND u.role_id = ?
            ");

            $stmt->execute([$studentId, $studentRoleId]);

            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                throw new Exception("Data siswa tidak ditemukan.");
            }

            $db->beginTransaction();

            // Update akun
            $stmt = $db->prepare("
                UPDATE users
                SET full_name = ?,
                    username = ?,
                    email = ?,
                    phone = ?
                WHERE id = ? AND role_id = ?
            ");

            $stmt->execute([
                $fullName,
                $username,
                $email !== '' ? $email : null,
                $phone !== '' ? $phone : null,
                $student['user_id'],
                $studentRoleId
            ]);

            // Update data siswa
            $stmt = $db->prepare("
                UPDATE students
                SET nis = ?,
                    nisn = ?,
                    nik = ?,
                    gender = ?,
                    birth_place = ?,
                    birth_date = ?,
                    religion = ?,
                    address = ?,
                    parent_name = ?,
                    parent_phone = ?,
                    admission_year = ?,
                    student_status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $nis,
                $nisn !== '' ? $nisn : null,
                $nik !== '' ? $nik : null,
                $gender,
                $birthPlace,
                $birthDate,
                $religion !== '' ? $religion : null,
                $address,
                $parentName !== '' ? $parentName : null,
                $parentPhone !== '' ? $parentPhone : null,
                $admissionYear,
                $studentStatus,
                $studentId
            ]);

            // Update password jika diisi
            $newPassword = $_POST['password'] ?? '';

            if ($newPassword !== '') {

                $stmt = $db->prepare("
                    UPDATE users
                    SET password_hash = ?
                    WHERE id = ? AND role_id = ?
                ");

                $stmt->execute([
                    password_hash($newPassword, PASSWORD_DEFAULT),
                    $student['user_id'],
                    $studentRoleId
                ]);
            }

            $db->commit();

            header("Location: index.php?success=edit");
            exit;
        }

        // STATUS AKUN LOGIN
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

            $stmt->execute([$userId, $studentRoleId]);

            header("Location: index.php?success=status");
            exit;
        }

        // HAPUS SISWA
        elseif ($action === 'hapus') {

            $studentId = (int) ($_POST['student_id'] ?? 0);

            $stmt = $db->prepare("
                SELECT s.user_id
                FROM students s
                JOIN users u ON u.id = s.user_id
                WHERE s.id = ? AND u.role_id = ?
            ");

            $stmt->execute([$studentId, $studentRoleId]);

            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                throw new Exception("Data siswa tidak ditemukan.");
            }

            $db->beginTransaction();

            $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$studentId]);

            $stmt = $db->prepare("
                DELETE FROM users
                WHERE id = ? AND role_id = ?
            ");

            $stmt->execute([
                $student['user_id'],
                $studentRoleId
            ]);

            $db->commit();

            header("Location: index.php?success=hapus");
            exit;
        }

    } catch (PDOException $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $error = "Terjadi kesalahan database. Periksa kembali data yang dimasukkan, kemungkinan ada username, email, NIS, NISN, atau NIK yang sudah digunakan.";

    } catch (Exception $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $error = $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| NOTIFIKASI
|--------------------------------------------------------------------------
*/

if (isset($_GET['success'])) {

    $successMessages = [
        'tambah' => 'Data siswa berhasil ditambahkan.',
        'edit' => 'Data siswa berhasil diperbarui.',
        'status' => 'Status akun siswa berhasil diperbarui.',
        'hapus' => 'Data siswa berhasil dihapus.'
    ];

    $message = $successMessages[$_GET['success']] ?? '';
}

/*
|--------------------------------------------------------------------------
| DATA EDIT
|--------------------------------------------------------------------------
*/

$editSiswa = null;

if (isset($_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    $stmt = $db->prepare("
        SELECT
            s.*,
            u.full_name,
            u.username,
            u.email,
            u.phone
        FROM students s
        JOIN users u ON u.id = s.user_id
        WHERE s.id = ? AND u.role_id = ?
    ");

    $stmt->execute([$editId, $studentRoleId]);

    $editSiswa = $stmt->fetch(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| DAFTAR SISWA
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        s.id AS student_id,
        s.nis,
        s.nisn,
        s.nik,
        s.gender,
        s.birth_place,
        s.birth_date,
        s.religion,
        s.address,
        s.parent_name,
        s.parent_phone,
        s.admission_year,
        s.student_status,
        u.id AS user_id,
        u.full_name,
        u.username,
        u.email,
        u.phone,
        u.status AS account_status
    FROM students s
    JOIN users u ON u.id = s.user_id
    WHERE u.role_id = ?
    ORDER BY s.id DESC
");

$stmt->execute([$studentRoleId]);

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manajemen Data Siswa</title>

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
            max-width: 1300px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
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

        .full {
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
            min-width: 1100px;
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

            .full {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <div>
            <h2>Manajemen Data Siswa</h2>
            <div class="subtitle">
                Kelola akun dan informasi peserta didik sekolah.
            </div>
        </div>

        <a href="../index.php" class="btn btn-cancel">
            Kembali ke Dashboard
        </a>
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
            <?= $editSiswa ? 'Edit Data Siswa' : 'Tambah Siswa Baru' ?>
        </h3>

        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="<?= $editSiswa ? 'edit' : 'tambah' ?>"
            >

            <?php if ($editSiswa): ?>
                <input
                    type="hidden"
                    name="student_id"
                    value="<?= e($editSiswa['id']) ?>"
                >
            <?php endif; ?>

            <div class="form-grid">

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input
                        type="text"
                        name="full_name"
                        required
                        value="<?= e($editSiswa['full_name'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>NIS</label>
                    <input
                        type="text"
                        name="nis"
                        required
                        value="<?= e($editSiswa['nis'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>NISN</label>
                    <input
                        type="text"
                        name="nisn"
                        value="<?= e($editSiswa['nisn'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>NIK</label>
                    <input
                        type="text"
                        name="nik"
                        value="<?= e($editSiswa['nik'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input
                        type="text"
                        name="username"
                        required
                        value="<?= e($editSiswa['username'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        value="<?= e($editSiswa['email'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Nomor Telepon Siswa</label>
                    <input
                        type="text"
                        name="phone"
                        value="<?= e($editSiswa['phone'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>
                        Password
                        <?= $editSiswa ? '(Kosongkan jika tidak diubah)' : '' ?>
                    </label>
                    <input
                        type="password"
                        name="password"
                        <?= $editSiswa ? '' : 'required' ?>
                    >
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="gender" required>
                        <option value="">-- Pilih --</option>

                        <option
                            value="L"
                            <?= (($editSiswa['gender'] ?? '') === 'L') ? 'selected' : '' ?>
                        >
                            Laki-laki
                        </option>

                        <option
                            value="P"
                            <?= (($editSiswa['gender'] ?? '') === 'P') ? 'selected' : '' ?>
                        >
                            Perempuan
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tempat Lahir</label>
                    <input
                        type="text"
                        name="birth_place"
                        required
                        value="<?= e($editSiswa['birth_place'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input
                        type="date"
                        name="birth_date"
                        required
                        value="<?= e($editSiswa['birth_date'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Agama</label>
                    <select name="religion">
                        <option value="">-- Pilih Agama --</option>

                        <?php foreach ($religionOptions as $option): ?>
                            <option
                                value="<?= e($option) ?>"
                                <?= (($editSiswa['religion'] ?? '') === $option) ? 'selected' : '' ?>
                            >
                                <?= e($option) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label>Nama Orang Tua / Wali</label>
                    <input
                        type="text"
                        name="parent_name"
                        value="<?= e($editSiswa['parent_name'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Nomor Telepon Orang Tua</label>
                    <input
                        type="text"
                        name="parent_phone"
                        value="<?= e($editSiswa['parent_phone'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Tahun Masuk</label>
                    <input
                        type="number"
                        name="admission_year"
                        min="2000"
                        max="2100"
                        required
                        value="<?= e($editSiswa['admission_year'] ?? date('Y')) ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Status Siswa</label>
                    <select name="student_status" required>

                        <?php foreach ($studentStatusOptions as $option): ?>
                            <option
                                value="<?= e($option) ?>"
                                <?= (($editSiswa['student_status'] ?? 'active') === $option) ? 'selected' : '' ?>
                            >
                                <?= e($statusLabels[$option]) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group full">
                    <label>Alamat</label>
                    <textarea
                        name="address"
                        required
                    ><?= e($editSiswa['address'] ?? '') ?></textarea>
                </div>

            </div>

            <br>

            <button type="submit" class="btn-primary">
                <?= $editSiswa ? 'Simpan Perubahan' : 'Tambah Siswa' ?>
            </button>

            <?php if ($editSiswa): ?>
                <a href="index.php" class="btn btn-cancel">
                    Batal Edit
                </a>
            <?php endif; ?>

        </form>

    </div>

    <div class="card">

        <h3>Daftar Siswa</h3>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa</th>
                        <th>NIS</th>
                        <th>NISN</th>
                        <th>Jenis Kelamin</th>
                        <th>Tahun Masuk</th>
                        <th>Status Siswa</th>
                        <th>Status Akun</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($students) > 0): ?>

                        <?php $no = 1; ?>

                        <?php foreach ($students as $student): ?>

                            <tr>
                                <td><?= $no++ ?></td>

                                <td><?= e($student['full_name']) ?></td>

                                <td><?= e($student['nis']) ?></td>

                                <td><?= e($student['nisn']) ?></td>

                                <td>
                                    <?= $student['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                </td>

                                <td><?= e($student['admission_year']) ?></td>

                                <td>
                                    <?= e($statusLabels[$student['student_status']] ?? $student['student_status']) ?>
                                </td>

                                <td>
                                    <span class="badge <?= $student['account_status'] === 'active' ? 'active' : 'inactive' ?>">
                                        <?= e($student['account_status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="actions">

                                        <a
                                            href="index.php?edit=<?= e($student['student_id']) ?>"
                                            class="btn btn-edit"
                                        >
                                            Edit
                                        </a>

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="status"
                                            >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?= e($student['user_id']) ?>"
                                            >

                                            <button type="submit" class="btn-status">
                                                <?= $student['account_status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                            </button>

                                        </form>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus data siswa beserta akun loginnya?')"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="hapus"
                                            >

                                            <input
                                                type="hidden"
                                                name="student_id"
                                                value="<?= e($student['student_id']) ?>"
                                            >

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
                            <td colspan="9" style="text-align:center;">
                                Belum ada data siswa.
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
