
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

// Proses data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    try {

        // Menambahkan siswa ke kelas
        if ($action === 'tambah') {

            $studentId = (int) ($_POST['student_id'] ?? 0);
            $classId = (int) ($_POST['class_id'] ?? 0);
            $enrolledAt = trim($_POST['enrolled_at'] ?? '');

            if ($studentId <= 0 || $classId <= 0) {
                throw new Exception('Siswa dan kelas wajib dipilih.');
            }

            // Pastikan siswa tersedia dan aktif
            $stmt = $db->prepare("
                SELECT id
                FROM students
                WHERE id = ? AND student_status = 'active'
            ");
            $stmt->execute([$studentId]);

            if (!$stmt->fetch()) {
                throw new Exception('Data siswa tidak ditemukan atau tidak aktif.');
            }

            // Pastikan kelas tersedia
            $stmt = $db->prepare("
                SELECT id, capacity
                FROM classes
                WHERE id = ?
            ");
            $stmt->execute([$classId]);
            $class = $stmt->fetch();

            if (!$class) {
                throw new Exception('Kelas tidak ditemukan.');
            }

            // Cek apakah siswa masih memiliki pembagian kelas aktif
            $stmt = $db->prepare("
                SELECT COUNT(*)
                FROM class_enrollments
                WHERE student_id = ?
                AND enrollment_status = 'active'
            ");
            $stmt->execute([$studentId]);

            if ($stmt->fetchColumn() > 0) {
                throw new Exception(
                    'Siswa sudah memiliki pembagian kelas aktif. Ubah status pembagian sebelumnya terlebih dahulu.'
                );
            }

            // Cek kapasitas kelas
            if ($class['capacity'] !== null) {

                $stmt = $db->prepare("
                    SELECT COUNT(*)
                    FROM class_enrollments
                    WHERE class_id = ?
                    AND enrollment_status = 'active'
                ");
                $stmt->execute([$classId]);

                $totalSiswa = (int) $stmt->fetchColumn();

                if ($totalSiswa >= (int) $class['capacity']) {
                    throw new Exception('Kapasitas kelas sudah penuh.');
                }
            }

            // Simpan pembagian kelas
            $stmt = $db->prepare("
                INSERT INTO class_enrollments
                (student_id, class_id, enrollment_status, enrolled_at)
                VALUES (?, ?, 'active', ?)
            ");

            $stmt->execute([
                $studentId,
                $classId,
                $enrolledAt !== '' ? $enrolledAt : null
            ]);

            $message = 'Siswa berhasil dimasukkan ke kelas.';
        }

        // Mengubah status pembagian kelas
        elseif ($action === 'status') {

            $id = (int) ($_POST['id'] ?? 0);
            $status = $_POST['enrollment_status'] ?? '';

            $allowedStatus = ['active', 'completed', 'moved'];

            if ($id <= 0 || !in_array($status, $allowedStatus, true)) {
                throw new Exception('Data perubahan status tidak valid.');
            }

            $stmt = $db->prepare("
                UPDATE class_enrollments
                SET enrollment_status = ?
                WHERE id = ?
            ");

            $stmt->execute([$status, $id]);

            $message = 'Status pembagian kelas berhasil diperbarui.';
        }

        // Menghapus pembagian kelas
        elseif ($action === 'hapus') {

            $id = (int) ($_POST['id'] ?? 0);

            $stmt = $db->prepare("
                DELETE FROM class_enrollments
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            $message = 'Data pembagian kelas berhasil dihapus.';
        }

    } catch (PDOException $e) {

        $error = 'Terjadi kesalahan database: ' . $e->getMessage();

    } catch (Exception $e) {

        $error = $e->getMessage();
    }
}

// Ambil data tahun ajaran dan kelas
$classes = $db->query("
    SELECT
        c.id,
        c.class_name,
        c.grade_level,
        c.major_name,
        c.capacity,
        ay.year_name
    FROM classes c
    INNER JOIN academic_years ay
        ON c.academic_year_id = ay.id
    ORDER BY ay.year_name DESC, c.grade_level ASC, c.class_name ASC
")->fetchAll();

// Filter kelas
$selectedClass = (int) ($_GET['class_id'] ?? 0);

// Daftar siswa yang belum memiliki pembagian kelas aktif
$availableStudents = $db->query("
    SELECT
        s.id,
        s.nis,
        s.nisn,
        u.full_name
    FROM students s
    INNER JOIN users u ON s.user_id = u.id
    WHERE s.student_status = 'active'
    AND NOT EXISTS (
        SELECT 1
        FROM class_enrollments ce
        WHERE ce.student_id = s.id
        AND ce.enrollment_status = 'active'
    )
    ORDER BY u.full_name ASC
")->fetchAll();

// Statistik
$totalEnrollments = $db->query("
    SELECT COUNT(*)
    FROM class_enrollments
")->fetchColumn();

$totalActive = $db->query("
    SELECT COUNT(*)
    FROM class_enrollments
    WHERE enrollment_status = 'active'
")->fetchColumn();

$totalCompleted = $db->query("
    SELECT COUNT(*)
    FROM class_enrollments
    WHERE enrollment_status = 'completed'
")->fetchColumn();

// Daftar pembagian kelas
$sql = "
    SELECT
        ce.id,
        ce.enrollment_status,
        ce.enrolled_at,
        s.nis,
        s.nisn,
        u.full_name,
        c.class_name,
        c.grade_level,
        c.major_name,
        ay.year_name
    FROM class_enrollments ce
    INNER JOIN students s ON ce.student_id = s.id
    INNER JOIN users u ON s.user_id = u.id
    INNER JOIN classes c ON ce.class_id = c.id
    INNER JOIN academic_years ay ON c.academic_year_id = ay.id
";

$params = [];

if ($selectedClass > 0) {
    $sql .= " WHERE ce.class_id = ?";
    $params[] = $selectedClass;
}

$sql .= " ORDER BY u.full_name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pembagian Kelas</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #333;
            padding: 30px;
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
        }

        .header h2 {
            margin-bottom: 7px;
        }

        .back {
            text-decoration: none;
            background: #333;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat, .panel {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }

        .stat h3 {
            font-size: 25px;
            margin-top: 8px;
        }

        .panel {
            margin-bottom: 25px;
        }

        .panel h3 {
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 15px;
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
            font-size: 14px;
            background: white;
        }

        button, .btn {
            border: none;
            padding: 9px 13px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
        }

        .primary {
            background: #176b45;
            color: white;
        }

        .edit {
            background: #64748b;
            color: white;
        }

        .delete {
            background: #dc3545;
            color: white;
        }

        .cancel {
            background: #777;
            color: white;
        }

        .alert {
            padding: 12px;
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

        .filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            max-width: 500px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        th, td {
            padding: 13px;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f8f9fa;
        }

        .status {
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
        }

        .active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .completed {
            background: #cfe2ff;
            color: #084298;
        }

        .moved {
            background: #fff3cd;
            color: #664d03;
        }

        .actions {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }

        .actions select {
            width: auto;
            min-width: 120px;
            padding: 8px;
        }

        @media(max-width: 700px) {
            body {
                padding: 15px;
            }

            .stats, .form-grid {
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
            <h2>Pembagian Kelas</h2>
            <p>Kelola penempatan siswa berdasarkan kelas dan tahun ajaran.</p>
        </div>

        <a href="<?= e($dashboardUrl) ?>" class="back">
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

    <div class="stats">

        <div class="stat">
            <p>Total Pembagian Kelas</p>
            <h3><?= $totalEnrollments ?></h3>
        </div>

        <div class="stat">
            <p>Siswa Aktif di Kelas</p>
            <h3><?= $totalActive ?></h3>
        </div>

        <div class="stat">
            <p>Siswa Selesai</p>
            <h3><?= $totalCompleted ?></h3>
        </div>

    </div>

    <div class="panel">

        <h3>Masukkan Siswa ke Kelas</h3>

        <?php if (count($classes) > 0 && count($availableStudents) > 0): ?>

            <form method="POST">

                <input type="hidden" name="action" value="tambah">

                <div class="form-grid">

                    <div class="form-group">
                        <label>Pilih Siswa *</label>

                        <select name="student_id" required>
                            <option value="">-- Pilih Siswa --</option>

                            <?php foreach ($availableStudents as $student): ?>

                                <option value="<?= (int) $student['id'] ?>">
                                    <?= e($student['full_name']) ?>
                                    (NIS: <?= e($student['nis'] ?: '-') ?>)
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="form-group">
                        <label>Pilih Kelas *</label>

                        <select name="class_id" required>
                            <option value="">-- Pilih Kelas --</option>

                            <?php foreach ($classes as $class): ?>

                                <option value="<?= (int) $class['id'] ?>">
                                    <?= e($class['class_name']) ?>
                                    - <?= e($class['grade_level']) ?>
                                    <?= e($class['major_name']) ?>
                                    (<?= e($class['year_name']) ?>)
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Masuk Kelas</label>

                        <input
                            type="date"
                            name="enrolled_at"
                        >
                    </div>

                </div>

                <button type="submit" class="primary">
                    Tambahkan ke Kelas
                </button>

            </form>

        <?php elseif (count($classes) === 0): ?>

            <p>Belum ada data kelas. Silakan buat kelas terlebih dahulu.</p>

        <?php else: ?>

            <p>
                Tidak ada siswa aktif yang tersedia untuk dibagikan ke kelas.
            </p>

        <?php endif; ?>

    </div>

    <div class="panel">

        <h3>Daftar Pembagian Kelas</h3>

        <form method="GET" class="filter">

            <select name="class_id">
                <option value="0">-- Semua Kelas --</option>

                <?php foreach ($classes as $class): ?>

                    <option
                        value="<?= (int) $class['id'] ?>"
                        <?= $selectedClass === (int) $class['id'] ? 'selected' : '' ?>
                    >
                        <?= e($class['class_name']) ?>
                        - <?= e($class['year_name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit" class="primary">Filter</button>

        </form>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa</th>
                        <th>NIS</th>
                        <th>Kelas</th>
                        <th>Jurusan</th>
                        <th>Tahun Ajaran</th>
                        <th>Tanggal Masuk</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($enrollments) > 0): ?>

                    <?php $no = 1; ?>

                    <?php foreach ($enrollments as $item): ?>

                        <tr>

                            <td><?= $no++ ?></td>

                            <td><?= e($item['full_name']) ?></td>

                            <td><?= e($item['nis'] ?: '-') ?></td>

                            <td>
                                <?= e($item['class_name']) ?>
                                (<?= e($item['grade_level']) ?>)
                            </td>

                            <td><?= e($item['major_name']) ?></td>

                            <td><?= e($item['year_name']) ?></td>

                            <td><?= e($item['enrolled_at'] ?: '-') ?></td>

                            <td>
                                <?php
                                $status = $item['enrollment_status'];
                                ?>

                                <span class="status <?= e($status) ?>">
                                    <?php
                                    if ($status === 'active') {
                                        echo 'Aktif';
                                    } elseif ($status === 'completed') {
                                        echo 'Selesai';
                                    } else {
                                        echo 'Pindah';
                                    }
                                    ?>
                                </span>
                            </td>

                            <td>

                                <div class="actions">

                                    <form method="POST">

                                        <input type="hidden" name="action" value="status">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $item['id'] ?>"
                                        >

                                        <select name="enrollment_status">
                                            <option value="active"
                                                <?= $status === 'active' ? 'selected' : '' ?>>
                                                Aktif
                                            </option>

                                            <option value="completed"
                                                <?= $status === 'completed' ? 'selected' : '' ?>>
                                                Selesai
                                            </option>

                                            <option value="moved"
                                                <?= $status === 'moved' ? 'selected' : '' ?>>
                                                Pindah
                                            </option>
                                        </select>

                                        <button type="submit" class="edit">
                                            Simpan
                                        </button>

                                    </form>

                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus pembagian kelas ini?')"
                                    >

                                        <input type="hidden" name="action" value="hapus">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $item['id'] ?>"
                                        >

                                        <button type="submit" class="delete">
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
                            Belum ada data pembagian kelas.
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
