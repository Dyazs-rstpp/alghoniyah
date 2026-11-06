<?php

require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../app/Middleware/AuthMiddleware.php';

AuthMiddleware::requireRole(
    ['admin'],
    '/smk-tpi-alghoniyyah/public/login-manajemen.php'
);

$dashboardUrl = AuthMiddleware::getDashboardUrl();
$db = (new Database())->connect();

$message = '';
$error = '';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

//proses form

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    try {

        // TAMBAH KELAS
        if ($action === 'tambah') {

            $academicYearId = (int) ($_POST['academic_year_id'] ?? 0);
            $className = trim($_POST['class_name'] ?? '');
            $gradeLevel = $_POST['grade_level'] ?? '';
            $majorName = trim($_POST['major_name'] ?? '');
            $teacherId = $_POST['homeroom_teacher_id'] ?? '';
            $capacity = $_POST['capacity'] ?? '';

            if (
                $academicYearId <= 0 ||
                $className === '' ||
                $majorName === '' ||
                !in_array($gradeLevel, ['X', 'XI', 'XII'], true)
            ) {
                throw new Exception('Semua data wajib diisi dengan benar.');
            }

            // CEK TAHUN AJARAN
            $stmt = $db->prepare("
                SELECT id FROM academic_years WHERE id = ?
            ");
            $stmt->execute([$academicYearId]);

            if (!$stmt->fetch()) {
                throw new Exception('Tahun ajaran tidak ditemukan.');
            }

            // CEK DUPLIKASI NAMA KELAS
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM classes
                WHERE academic_year_id = ? AND class_name = ?
            ");
            $stmt->execute([$academicYearId, $className]);

            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Nama kelas sudah digunakan pada tahun ajaran tersebut.');
            }

            // VALIDASI WALI KELAS
            $teacherId = $teacherId !== '' ? (int) $teacherId : null;

            if ($teacherId !== null) {

                $stmt = $db->prepare("
                    SELECT id FROM teachers WHERE id = ?
                ");
                $stmt->execute([$teacherId]);

                if (!$stmt->fetch()) {
                    throw new Exception('Data wali kelas tidak ditemukan.');
                }
            }

            // VALIDASI KAPASITAS
            $capacity = $capacity !== '' ? (int) $capacity : null;

            if ($capacity !== null && ($capacity < 1 || $capacity > 65535)) {
                throw new Exception('Kapasitas harus antara 1 sampai 65535.');
            }

            $stmt = $db->prepare("
                INSERT INTO classes (
                    academic_year_id,
                    class_name,
                    grade_level,
                    major_name,
                    homeroom_teacher_id,
                    capacity
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $academicYearId,
                $className,
                $gradeLevel,
                $majorName,
                $teacherId,
                $capacity
            ]);

            header('Location: index.php?success=tambah');
            exit;
        }

        // EDIT KELAS
        elseif ($action === 'edit') {

            $id = (int) ($_POST['id'] ?? 0);
            $academicYearId = (int) ($_POST['academic_year_id'] ?? 0);
            $className = trim($_POST['class_name'] ?? '');
            $gradeLevel = $_POST['grade_level'] ?? '';
            $majorName = trim($_POST['major_name'] ?? '');
            $teacherId = $_POST['homeroom_teacher_id'] ?? '';
            $capacity = $_POST['capacity'] ?? '';

            if (
                $id <= 0 ||
                $academicYearId <= 0 ||
                $className === '' ||
                $majorName === '' ||
                !in_array($gradeLevel, ['X', 'XI', 'XII'], true)
            ) {
                throw new Exception('Data edit belum lengkap.');
            }

            // CEK KELAS
            $stmt = $db->prepare("
                SELECT id FROM classes WHERE id = ?
            ");
            $stmt->execute([$id]);

            if (!$stmt->fetch()) {
                throw new Exception('Data kelas tidak ditemukan.');
            }

            // CEK TAHUN AJARAN
            $stmt = $db->prepare("
                SELECT id FROM academic_years WHERE id = ?
            ");
            $stmt->execute([$academicYearId]);

            if (!$stmt->fetch()) {
                throw new Exception('Tahun ajaran tidak ditemukan.');
            }

            // CEK DUPLIKASI
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM classes
                WHERE academic_year_id = ?
                AND class_name = ?
                AND id != ?
            ");
            $stmt->execute([$academicYearId, $className, $id]);

            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Nama kelas sudah digunakan pada tahun ajaran tersebut.');
            }

            // VALIDASI WALI KELAS
            $teacherId = $teacherId !== '' ? (int) $teacherId : null;

            if ($teacherId !== null) {

                $stmt = $db->prepare("
                    SELECT id FROM teachers WHERE id = ?
                ");
                $stmt->execute([$teacherId]);

                if (!$stmt->fetch()) {
                    throw new Exception('Data wali kelas tidak ditemukan.');
                }
            }

            // VALIDASI KAPASITAS
            $capacity = $capacity !== '' ? (int) $capacity : null;

            if ($capacity !== null && ($capacity < 1 || $capacity > 65535)) {
                throw new Exception('Kapasitas harus antara 1 sampai 65535.');
            }

            $stmt = $db->prepare("
                UPDATE classes SET
                    academic_year_id = ?,
                    class_name = ?,
                    grade_level = ?,
                    major_name = ?,
                    homeroom_teacher_id = ?,
                    capacity = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $academicYearId,
                $className,
                $gradeLevel,
                $majorName,
                $teacherId,
                $capacity,
                $id
            ]);

            header('Location: index.php?success=edit');
            exit;
        }

        // HAPUS KELAS
        elseif ($action === 'hapus') {

            $id = (int) ($_POST['id'] ?? 0);

            $stmt = $db->prepare("
                SELECT id FROM classes WHERE id = ?
            ");
            $stmt->execute([$id]);

            if (!$stmt->fetch()) {
                throw new Exception('Data kelas tidak ditemukan.');
            }

            $stmt = $db->prepare("
                DELETE FROM classes WHERE id = ?
            ");
            $stmt->execute([$id]);

            header('Location: index.php?success=hapus');
            exit;
        }

    } catch (PDOException $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $error = 'Terjadi kesalahan database. Data mungkin masih digunakan oleh bagian lain.';

    } catch (Exception $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $error = $e->getMessage();
    }
}

// notif

if (isset($_GET['success'])) {

    $messages = [
        'tambah' => 'Data kelas berhasil ditambahkan.',
        'edit' => 'Data kelas berhasil diperbarui.',
        'hapus' => 'Data kelas berhasil dihapus.'
    ];

    $message = $messages[$_GET['success']] ?? '';
}

// data edit

$editClass = null;

if (isset($_GET['edit'])) {

    $stmt = $db->prepare("
        SELECT * FROM classes WHERE id = ?
    ");

    $stmt->execute([(int) $_GET['edit']]);
    $editClass = $stmt->fetch(PDO::FETCH_ASSOC);
}

//data tahun ajaran

$stmt = $db->query("
    SELECT id, year_name, is_active
    FROM academic_years
    ORDER BY is_active DESC, start_date DESC
");

$academicYears = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Data guru untuk wali kelas

$stmt = $db->query("
    SELECT
        teachers.id,
        teachers.employee_number,
        users.full_name
    FROM teachers
    INNER JOIN users ON users.id = teachers.user_id
    ORDER BY users.full_name ASC
");

$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Daftar kelas

$stmt = $db->query("
    SELECT
        classes.*,
        academic_years.year_name,
        users.full_name AS teacher_name
    FROM classes
    INNER JOIN academic_years
        ON academic_years.id = classes.academic_year_id
    LEFT JOIN teachers
        ON teachers.id = classes.homeroom_teacher_id
    LEFT JOIN users
        ON users.id = teachers.user_id
    ORDER BY
        academic_years.start_date DESC,
        classes.grade_level ASC,
        classes.class_name ASC
");

$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manajemen Data Kelas</title>

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
            max-width: 1200px;
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

        label {
            font-size: 14px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
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

        .primary {
            background: #198754;
            color: white;
        }

        .edit {
            background: #ffc107;
            color: #222;
        }

        .delete {
            background: #dc3545;
            color: white;
        }

        .cancel {
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

        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .actions form {
            margin: 0;
        }

        .badge {
            padding: 5px 9px;
            border-radius: 20px;
            background: #e9ecef;
            color: #555;
            font-size: 11px;
        }

        @media(max-width: 650px) {
            body {
                padding: 12px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <div>
            <h2>Manajemen Data Kelas</h2>
            <div class="subtitle">
                Kelola kelas berdasarkan tahun ajaran, jurusan, dan wali kelas.
            </div>
        </div>

        <a href="<?= e($dashboardUrl) ?>" class="btn cancel">
            Kembali ke Dashboard
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert success"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert danger"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card">

        <h3>
            <?= $editClass ? 'Edit Data Kelas' : 'Tambah Data Kelas' ?>
        </h3>

        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="<?= $editClass ? 'edit' : 'tambah' ?>"
            >

            <?php if ($editClass): ?>
                <input
                    type="hidden"
                    name="id"
                    value="<?= e($editClass['id']) ?>"
                >
            <?php endif; ?>

            <div class="form-grid">

                <div class="form-group">
                    <label>Tahun Ajaran</label>

                    <select name="academic_year_id" required>
                        <option value="">-- Pilih Tahun Ajaran --</option>

                        <?php foreach ($academicYears as $year): ?>

                            <option
                                value="<?= e($year['id']) ?>"
                                <?= (
                                    (string) ($editClass['academic_year_id'] ?? '') ===
                                    (string) $year['id']
                                ) ? 'selected' : '' ?>
                            >
                                <?= e($year['year_name']) ?>
                                <?= (int) $year['is_active'] === 1 ? '(Aktif)' : '' ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label>Nama Kelas</label>

                    <input
                        type="text"
                        name="class_name"
                        placeholder="Contoh: X RPL 1"
                        maxlength="50"
                        required
                        value="<?= e($editClass['class_name'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Tingkat Kelas</label>

                    <select name="grade_level" required>
                        <option value="">-- Pilih Tingkat --</option>

                        <?php
                        $grades = ['X', 'XI', 'XII'];
                        ?>

                        <?php foreach ($grades as $grade): ?>

                            <option
                                value="<?= e($grade) ?>"
                                <?= (
                                    ($editClass['grade_level'] ?? '') === $grade
                                ) ? 'selected' : '' ?>
                            >
                                <?= e($grade) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label>Jurusan</label>

                    <input
                        type="text"
                        name="major_name"
                        placeholder="Contoh: Rekayasa Perangkat Lunak"
                        maxlength="120"
                        required
                        value="<?= e($editClass['major_name'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Wali Kelas</label>

                    <select name="homeroom_teacher_id">
                        <option value="">-- Belum Ditentukan --</option>

                        <?php foreach ($teachers as $teacher): ?>

                            <option
                                value="<?= e($teacher['id']) ?>"
                                <?= (
                                    (string) ($editClass['homeroom_teacher_id'] ?? '') ===
                                    (string) $teacher['id']
                                ) ? 'selected' : '' ?>
                            >
                                <?= e($teacher['full_name']) ?>
                                <?php if (!empty($teacher['employee_number'])): ?>
                                    - <?= e($teacher['employee_number']) ?>
                                <?php endif; ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label>Kapasitas Siswa</label>

                    <input
                        type="number"
                        name="capacity"
                        min="1"
                        max="65535"
                        placeholder="Contoh: 36"
                        value="<?= e($editClass['capacity'] ?? '') ?>"
                    >
                </div>

            </div>

            <br>

            <button type="submit" class="primary">
                <?= $editClass ? 'Simpan Perubahan' : 'Tambah Kelas' ?>
            </button>

            <?php if ($editClass): ?>
                <a href="index.php" class="btn cancel">
                    Batal Edit
                </a>
            <?php endif; ?>

        </form>

    </div>

    <div class="card">

        <h3>Daftar Kelas</h3>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tahun Ajaran</th>
                        <th>Nama Kelas</th>
                        <th>Tingkat</th>
                        <th>Jurusan</th>
                        <th>Wali Kelas</th>
                        <th>Kapasitas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($classes) > 0): ?>

                        <?php $no = 1; ?>

                        <?php foreach ($classes as $class): ?>

                            <tr>

                                <td><?= $no++ ?></td>

                                <td>
                                    <?= e($class['year_name']) ?>
                                </td>

                                <td>
                                    <?= e($class['class_name']) ?>
                                </td>

                                <td>
                                    <span class="badge">
                                        <?= e($class['grade_level']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e($class['major_name']) ?>
                                </td>

                                <td>
                                    <?= !empty($class['teacher_name'])
                                        ? e($class['teacher_name'])
                                        : 'Belum ditentukan' ?>
                                </td>

                                <td>
                                    <?= $class['capacity'] !== null
                                        ? e($class['capacity']) . ' Siswa'
                                        : '-' ?>
                                </td>

                                <td>

                                    <div class="actions">

                                        <a
                                            href="index.php?edit=<?= e($class['id']) ?>"
                                            class="btn edit"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus kelas ini?')"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="hapus"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= e($class['id']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="delete"
                                            >
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
                                Belum ada data kelas.
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
