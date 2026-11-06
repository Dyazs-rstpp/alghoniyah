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

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    try {

        // Tambah mata pelajaran
        if ($action === 'tambah') {

            $subjectCode = trim($_POST['subject_code'] ?? '');
            $subjectName = trim($_POST['subject_name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($subjectName === '') {
                throw new Exception('Nama mata pelajaran wajib diisi.');
            }

            $stmt = $db->prepare("
                INSERT INTO subjects
                (subject_code, subject_name, description)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $subjectCode !== '' ? $subjectCode : null,
                $subjectName,
                $description !== '' ? $description : null
            ]);

            $message = 'Mata pelajaran berhasil ditambahkan.';
        }

        // Edit mata pelajaran
        elseif ($action === 'edit') {

            $id = (int) ($_POST['id'] ?? 0);
            $subjectCode = trim($_POST['subject_code'] ?? '');
            $subjectName = trim($_POST['subject_name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($id <= 0 || $subjectName === '') {
                throw new Exception('Data mata pelajaran tidak valid.');
            }

            $stmt = $db->prepare("
                UPDATE subjects
                SET subject_code = ?,
                    subject_name = ?,
                    description = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $subjectCode !== '' ? $subjectCode : null,
                $subjectName,
                $description !== '' ? $description : null,
                $id
            ]);

            $message = 'Mata pelajaran berhasil diperbarui.';
        }

        // Aktif / nonaktif
        elseif ($action === 'toggle') {

            $id = (int) ($_POST['id'] ?? 0);

            $stmt = $db->prepare("
                UPDATE subjects
                SET is_active = IF(is_active = 1, 0, 1)
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            $message = 'Status mata pelajaran berhasil diperbarui.';
        }

        // Hapus mata pelajaran
        elseif ($action === 'hapus') {

            $id = (int) ($_POST['id'] ?? 0);

            $stmt = $db->prepare("
                DELETE FROM subjects
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            $message = 'Mata pelajaran berhasil dihapus.';
        }

    } catch (PDOException $e) {

        $error = 'Terjadi kesalahan database: ' . $e->getMessage();

    } catch (Exception $e) {

        $error = $e->getMessage();
    }
}

// Data untuk edit
$editData = null;

if (isset($_GET['edit'])) {

    $id = (int) $_GET['edit'];

    $stmt = $db->prepare("
        SELECT *
        FROM subjects
        WHERE id = ?
    ");

    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

// Pencarian
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT *
    FROM subjects
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE subject_name LIKE ?
        OR subject_code LIKE ?
    ";

    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY subject_name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$subjects = $stmt->fetchAll();

// Statistik
$totalSubjects = $db->query("
    SELECT COUNT(*) FROM subjects
")->fetchColumn();

$activeSubjects = $db->query("
    SELECT COUNT(*) FROM subjects WHERE is_active = 1
")->fetchColumn();

$inactiveSubjects = $db->query("
    SELECT COUNT(*) FROM subjects WHERE is_active = 0
")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Mata Pelajaran</title>

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
            max-width: 1200px;
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
            color: #222;
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

        .stat {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }

        .stat h3 {
            font-size: 25px;
            margin-top: 8px;
        }

        .panel {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
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

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            font-size: 14px;
        }

        input, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
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
            background: #e6a23c;
            color: white;
        }

        .toggle {
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

        .search {
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
        }

        .search input {
            max-width: 350px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
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

        .table-wrapper {
            overflow-x: auto;
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

        .inactive {
            background: #f8d7da;
            color: #842029;
        }

        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        @media(max-width: 700px) {
            body {
                padding: 15px;
            }

            .stats {
                grid-template-columns: 1fr;
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
            <h2>Manajemen Mata Pelajaran</h2>
            <p>Kelola data mata pelajaran sekolah.</p>
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
            <p>Total Mata Pelajaran</p>
            <h3><?= $totalSubjects ?></h3>
        </div>

        <div class="stat">
            <p>Mata Pelajaran Aktif</p>
            <h3><?= $activeSubjects ?></h3>
        </div>

        <div class="stat">
            <p>Mata Pelajaran Nonaktif</p>
            <h3><?= $inactiveSubjects ?></h3>
        </div>

    </div>

    <div class="panel">

        <h3>
            <?= $editData ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' ?>
        </h3>

        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="<?= $editData ? 'edit' : 'tambah' ?>"
            >

            <?php if ($editData): ?>
                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $editData['id'] ?>"
                >
            <?php endif; ?>

            <div class="form-grid">

                <div class="form-group">
                    <label>Kode Mata Pelajaran</label>
                    <input
                        type="text"
                        name="subject_code"
                        maxlength="30"
                        placeholder="Contoh: MTK-01"
                        value="<?= e($editData['subject_code'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Nama Mata Pelajaran *</label>
                    <input
                        type="text"
                        name="subject_name"
                        maxlength="120"
                        required
                        placeholder="Masukkan nama mata pelajaran"
                        value="<?= e($editData['subject_name'] ?? '') ?>"
                    >
                </div>

                <div class="form-group full">
                    <label>Deskripsi</label>
                    <textarea
                        name="description"
                        placeholder="Deskripsi mata pelajaran (opsional)"
                    ><?= e($editData['description'] ?? '') ?></textarea>
                </div>

            </div>

            <button type="submit" class="primary">
                <?= $editData ? 'Simpan Perubahan' : 'Tambah Mata Pelajaran' ?>
            </button>

            <?php if ($editData): ?>
                <a href="index.php" class="btn cancel">Batal</a>
            <?php endif; ?>

        </form>

    </div>

    <div class="panel">

        <h3>Daftar Mata Pelajaran</h3>

        <form method="GET" class="search">

            <input
                type="text"
                name="search"
                placeholder="Cari kode atau nama mata pelajaran..."
                value="<?= e($search) ?>"
            >

            <button type="submit" class="primary">Cari</button>

            <a href="index.php" class="btn cancel">Reset</a>

        </form>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Mata Pelajaran</th>
                        <th>Deskripsi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($subjects) > 0): ?>

                    <?php $no = 1; ?>

                    <?php foreach ($subjects as $subject): ?>

                        <tr>

                            <td><?= $no++ ?></td>

                            <td>
                                <?= e($subject['subject_code'] ?: '-') ?>
                            </td>

                            <td>
                                <?= e($subject['subject_name']) ?>
                            </td>

                            <td>
                                <?= e($subject['description'] ?: '-') ?>
                            </td>

                            <td>
                                <?php if ($subject['is_active'] == 1): ?>
                                    <span class="status active">Aktif</span>
                                <?php else: ?>
                                    <span class="status inactive">Nonaktif</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="actions">

                                    <a
                                        href="?edit=<?= (int) $subject['id'] ?>"
                                        class="btn edit"
                                    >
                                        Edit
                                    </a>

                                    <form method="POST">
                                        <input type="hidden" name="action" value="toggle">
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $subject['id'] ?>"
                                        >

                                        <button type="submit" class="toggle">
                                            <?= $subject['is_active'] == 1
                                                ? 'Nonaktifkan'
                                                : 'Aktifkan' ?>
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus mata pelajaran ini?')"
                                    >
                                        <input type="hidden" name="action" value="hapus">
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $subject['id'] ?>"
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
                        <td colspan="6" style="text-align:center;">
                            Belum ada data mata pelajaran.
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
