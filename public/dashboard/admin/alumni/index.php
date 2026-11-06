<?php

require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../app/Middleware/AuthMiddleware.php';

// Gunakan pemanggilan AuthMiddleware yang sama seperti halaman Guru
// jika sudah ada pemeriksaan role di file tersebut.

$db = (new Database())->connect();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$success = '';
$error = '';

$roleQuery = $db->prepare("SELECT id FROM roles WHERE code = 'alumni' LIMIT 1");
$roleQuery->execute();
$role = $roleQuery->fetch();

if (!$role) {
    die("Role alumni belum tersedia di tabel roles.");
}

$roleId = $role['id'];

// Proses tambah alumni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {
    try {
        $db->beginTransaction();

        $nama = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $tahunLulus = trim($_POST['graduation_year'] ?? '');
        $jurusan = trim($_POST['major_name'] ?? '');
        $statusVerifikasi = $_POST['verification_status'] ?? 'pending';

        if (
            $nama === '' || $username === '' || $email === '' ||
            $password === '' || $tahunLulus === ''
        ) {
            throw new Exception("Nama, username, email, password, dan tahun kelulusan wajib diisi.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format email tidak valid.");
        }

        if (!preg_match('/^\d{4}$/', $tahunLulus)) {
            throw new Exception("Tahun kelulusan harus terdiri dari 4 angka.");
        }

        if (!in_array($statusVerifikasi, ['pending', 'verified', 'rejected'], true)) {
            throw new Exception("Status verifikasi tidak valid.");
        }

        $cek = $db->prepare("
            SELECT id FROM users
            WHERE username = ? OR email = ?
            LIMIT 1
        ");
        $cek->execute([$username, $email]);

        if ($cek->fetch()) {
            throw new Exception("Username atau email sudah digunakan.");
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $insertUser = $db->prepare("
            INSERT INTO users
            (role_id, full_name, username, email, phone, password_hash, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");

        $insertUser->execute([
            $roleId,
            $nama,
            $username,
            $email,
            $phone !== '' ? $phone : null,
            $passwordHash
        ]);

        $userId = $db->lastInsertId();

        $insertAlumni = $db->prepare("
            INSERT INTO alumni
            (user_id, student_id, graduation_year, major_name, verification_status)
            VALUES (?, NULL, ?, ?, ?)
        ");

        $insertAlumni->execute([
            $userId,
            $tahunLulus,
            $jurusan !== '' ? $jurusan : null,
            $statusVerifikasi
        ]);

        $db->commit();
        $success = "Data alumni berhasil ditambahkan.";

    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $error = $e->getMessage();
    }
}

// Proses edit alumni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    try {
        $alumniId = (int) ($_POST['alumni_id'] ?? 0);
        $nama = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $tahunLulus = trim($_POST['graduation_year'] ?? '');
        $jurusan = trim($_POST['major_name'] ?? '');
        $statusVerifikasi = $_POST['verification_status'] ?? '';

        if (
            $alumniId <= 0 || $nama === '' || $username === '' ||
            $email === '' || $tahunLulus === ''
        ) {
            throw new Exception("Data wajib belum lengkap.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format email tidak valid.");
        }

        if (!preg_match('/^\d{4}$/', $tahunLulus)) {
            throw new Exception("Tahun kelulusan harus terdiri dari 4 angka.");
        }

        if (!in_array($statusVerifikasi, ['pending', 'verified', 'rejected'], true)) {
            throw new Exception("Status verifikasi tidak valid.");
        }

        $cek = $db->prepare("
            SELECT a.id, a.user_id
            FROM alumni a
            WHERE a.id = ?
            LIMIT 1
        ");
        $cek->execute([$alumniId]);
        $data = $cek->fetch();

        if (!$data) {
            throw new Exception("Data alumni tidak ditemukan.");
        }

        $cekAkun = $db->prepare("
            SELECT id FROM users
            WHERE (username = ? OR email = ?)
            AND id != ?
            LIMIT 1
        ");
        $cekAkun->execute([$username, $email, $data['user_id']]);

        if ($cekAkun->fetch()) {
            throw new Exception("Username atau email sudah digunakan akun lain.");
        }

        $db->beginTransaction();

        $updateUser = $db->prepare("
            UPDATE users
            SET full_name = ?, username = ?, email = ?, phone = ?
            WHERE id = ?
        ");

        $updateUser->execute([
            $nama,
            $username,
            $email,
            $phone !== '' ? $phone : null,
            $data['user_id']
        ]);

        $updateAlumni = $db->prepare("
            UPDATE alumni
            SET graduation_year = ?, major_name = ?, verification_status = ?
            WHERE id = ?
        ");

        $updateAlumni->execute([
            $tahunLulus,
            $jurusan !== '' ? $jurusan : null,
            $statusVerifikasi,
            $alumniId
        ]);

        $db->commit();
        $success = "Data alumni berhasil diperbarui.";

    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $error = $e->getMessage();
    }
}

// Proses ubah status akun
if (isset($_GET['toggle'])) {
    try {
        $alumniId = (int) $_GET['toggle'];

        $query = $db->prepare("
            SELECT u.id, u.status
            FROM alumni a
            JOIN users u ON u.id = a.user_id
            WHERE a.id = ?
        ");
        $query->execute([$alumniId]);
        $akun = $query->fetch();

        if ($akun) {
            $statusBaru = $akun['status'] === 'active' ? 'inactive' : 'active';

            $update = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $update->execute([$statusBaru, $akun['id']]);

            $success = "Status akun alumni berhasil diubah.";
        } else {
            $error = "Data alumni tidak ditemukan.";
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Proses hapus alumni
if (isset($_GET['hapus'])) {
    try {
        $alumniId = (int) $_GET['hapus'];

        $query = $db->prepare("SELECT user_id FROM alumni WHERE id = ?");
        $query->execute([$alumniId]);
        $data = $query->fetch();

        if (!$data) {
            throw new Exception("Data alumni tidak ditemukan.");
        }

        $db->beginTransaction();

        $hapusAlumni = $db->prepare("DELETE FROM alumni WHERE id = ?");
        $hapusAlumni->execute([$alumniId]);

        $hapusUser = $db->prepare("DELETE FROM users WHERE id = ?");
        $hapusUser->execute([$data['user_id']]);

        $db->commit();
        $success = "Data alumni berhasil dihapus.";

    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $error = $e->getMessage();
    }
}

// Ambil data untuk form edit
$editAlumni = null;

if (isset($_GET['edit'])) {
    $query = $db->prepare("
        SELECT a.*, u.full_name, u.username, u.email, u.phone
        FROM alumni a
        JOIN users u ON u.id = a.user_id
        WHERE a.id = ?
    ");
    $query->execute([(int) $_GET['edit']]);
    $editAlumni = $query->fetch();
}

// Ambil seluruh data alumni
$query = $db->query("
    SELECT
        a.id,
        a.graduation_year,
        a.major_name,
        a.verification_status,
        a.created_at,
        u.full_name,
        u.username,
        u.email,
        u.phone,
        u.status AS account_status
    FROM alumni a
    JOIN users u ON u.id = a.user_id
    ORDER BY a.id DESC
");

$alumniList = $query->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Alumni</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #222;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        /* --- CSS Baru untuk Layout Header & Tombol --- */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
        }

        .header-text h1 {
            margin: 0;
            margin-bottom: 5px;
        }

        .header-text .subtitle {
            color: #777;
            margin: 0;
        }

        .btn-back {
            background-color: #198754; /* Background hijau */
            color: white; /* Teks putih */
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
        }

        .btn-back:hover {
            background-color: #146c43; /* Warna hijau lebih gelap saat disorot */
        }
        /* ------------------------------------------- */

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            font-size: 14px;
        }

        input, select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        button, .btn {
            display: inline-block;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            margin-top: 15px;
        }

        .primary {
            background: #198754;
            color: white;
        }

        .secondary {
            background: #6c757d;
            color: white;
        }

        .warning {
            background: #ffc107;
            color: #222;
        }

        .danger {
            background: #dc3545;
            color: white;
        }

        .info {
            background: #0d6efd;
            color: white;
        }

        .success-message {
            background: #d1e7dd;
            color: #0f5132;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .error-message {
            background: #f8d7da;
            color: #842029;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
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
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 13px;
        }

        th {
            background: #f8f9fa;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .active, .verified {
            background: #d1e7dd;
            color: #0f5132;
        }

        .inactive, .rejected {
            background: #f8d7da;
            color: #842029;
        }

        .pending {
            background: #fff3cd;
            color: #664d03;
        }

        @media (max-width: 700px) {
            body {
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            /* Penyesuaian header saat di layar kecil (HP) */
            .page-header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <!-- Header dengan posisi terbagi (Kiri: Judul, Kanan: Tombol) -->
    <div class="page-header">
        <div class="header-text">
            <h1>Manajemen Data Alumni</h1>
            <p class="subtitle">Kelola akun, data kelulusan, dan verifikasi alumni sekolah.</p>
        </div>
        <a href="../index.php" class="btn-back">&larr; Kembali ke Dashboard</a>
    </div>

    <?php if ($success): ?>
        <div class="success-message"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-message"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card">

        <h2><?= $editAlumni ? 'Edit Data Alumni' : 'Tambah Alumni' ?></h2>

        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="<?= $editAlumni ? 'edit' : 'tambah' ?>"
            >

            <?php if ($editAlumni): ?>
                <input
                    type="hidden"
                    name="alumni_id"
                    value="<?= e($editAlumni['id']) ?>"
                >
            <?php endif; ?>

            <div class="form-grid">

                <div>
                    <label>Nama Lengkap *</label>
                    <input
                        type="text"
                        name="full_name"
                        required
                        value="<?= e($editAlumni['full_name'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label>Username *</label>
                    <input
                        type="text"
                        name="username"
                        required
                        value="<?= e($editAlumni['username'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label>Email *</label>
                    <input
                        type="email"
                        name="email"
                        required
                        value="<?= e($editAlumni['email'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label>Nomor Telepon</label>
                    <input
                        type="text"
                        name="phone"
                        value="<?= e($editAlumni['phone'] ?? '') ?>"
                    >
                </div>

                <?php if (!$editAlumni): ?>
                    <div>
                        <label>Password *</label>
                        <input
                            type="password"
                            name="password"
                            required
                            minlength="6"
                        >
                    </div>
                <?php endif; ?>

                <div>
                    <label>Tahun Kelulusan *</label>
                    <input
                        type="number"
                        name="graduation_year"
                        min="1901"
                        max="2155"
                        required
                        value="<?= e($editAlumni['graduation_year'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label>Jurusan</label>
                    <input
                        type="text"
                        name="major_name"
                        value="<?= e($editAlumni['major_name'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label>Status Verifikasi</label>
                    <select name="verification_status">
                        <?php
                        $selectedStatus = $editAlumni['verification_status'] ?? 'pending';
                        foreach (['pending', 'verified', 'rejected'] as $status):
                        ?>
                            <option
                                value="<?= e($status) ?>"
                                <?= $selectedStatus === $status ? 'selected' : '' ?>
                            >
                                <?= e(ucfirst($status)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <button type="submit" class="primary">
                <?= $editAlumni ? 'Simpan Perubahan' : 'Tambah Alumni' ?>
            </button>

            <?php if ($editAlumni): ?>
                <a href="index.php" class="btn secondary">Batal</a>
            <?php endif; ?>

        </form>

    </div>

    <div class="card">

        <h2>Daftar Alumni</h2>

        <div class="table-wrapper">

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Tahun Lulus</th>
                        <th>Jurusan</th>
                        <th>Verifikasi</th>
                        <th>Status Akun</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($alumniList) > 0): ?>

                        <?php foreach ($alumniList as $i => $alumni): ?>

                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($alumni['full_name']) ?></td>
                                <td><?= e($alumni['username']) ?></td>
                                <td><?= e($alumni['email']) ?></td>
                                <td><?= e($alumni['graduation_year']) ?></td>
                                <td><?= e($alumni['major_name'] ?? '-') ?></td>

                                <td>
                                    <span class="status <?= e($alumni['verification_status']) ?>">
                                        <?= e(ucfirst($alumni['verification_status'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="status <?= e($alumni['account_status']) ?>">
                                        <?= e(ucfirst($alumni['account_status'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        href="?edit=<?= e($alumni['id']) ?>"
                                        class="btn warning"
                                    >Edit</a>

                                    <a
                                        href="?toggle=<?= e($alumni['id']) ?>"
                                        class="btn info"
                                        onclick="return confirm('Ubah status akun alumni ini?')"
                                    >
                                        <?= $alumni['account_status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                    </a>

                                    <a
                                        href="?hapus=<?= e($alumni['id']) ?>"
                                        class="btn danger"
                                        onclick="return confirm('Yakin ingin menghapus data alumni ini?')"
                                    >Hapus</a>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" style="text-align:center;">
                                Belum ada data alumni.
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