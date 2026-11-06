
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

        // TAMBAH TAHUN AJARAN
        if ($action === 'tambah') {

            $yearName = trim($_POST['year_name'] ?? '');
            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? '';

            if ($yearName === '' || $startDate === '' || $endDate === '') {
                throw new Exception('Semua kolom wajib diisi.');
            }

            if ($startDate >= $endDate) {
                throw new Exception('Tanggal berakhir harus setelah tanggal mulai.');
            }

            $stmt = $db->prepare("
                SELECT COUNT(*) FROM academic_years
                WHERE year_name = ?
            ");
            $stmt->execute([$yearName]);

            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Tahun ajaran tersebut sudah tersedia.');
            }

            $stmt = $db->prepare("
                INSERT INTO academic_years
                (year_name, start_date, end_date, is_active, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");

            $stmt->execute([$yearName, $startDate, $endDate]);

            header('Location: index.php?success=tambah');
            exit;
        }

        // EDIT TAHUN AJARAN
        elseif ($action === 'edit') {

            $id = (int) ($_POST['id'] ?? 0);
            $yearName = trim($_POST['year_name'] ?? '');
            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? '';

            if ($id <= 0 || $yearName === '' || $startDate === '' || $endDate === '') {
                throw new Exception('Data edit belum lengkap.');
            }

            if ($startDate >= $endDate) {
                throw new Exception('Tanggal berakhir harus setelah tanggal mulai.');
            }

            $stmt = $db->prepare("
                SELECT COUNT(*) FROM academic_years
                WHERE year_name = ? AND id != ?
            ");
            $stmt->execute([$yearName, $id]);

            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Nama tahun ajaran sudah digunakan.');
            }

            $stmt = $db->prepare("
                UPDATE academic_years
                SET year_name = ?, start_date = ?, end_date = ?
                WHERE id = ?
            ");

            $stmt->execute([$yearName, $startDate, $endDate, $id]);

            header('Location: index.php?success=edit');
            exit;
        }

        // AKTIFKAN TAHUN AJARAN
        elseif ($action === 'aktif') {

            $id = (int) ($_POST['id'] ?? 0);

            $stmt = $db->prepare("
                SELECT id FROM academic_years WHERE id = ?
            ");
            $stmt->execute([$id]);

            if (!$stmt->fetch()) {
                throw new Exception('Tahun ajaran tidak ditemukan.');
            }

            $db->beginTransaction();

            $db->exec("
                UPDATE academic_years SET is_active = 0
            ");

            $stmt = $db->prepare("
                UPDATE academic_years
                SET is_active = 1
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            $db->commit();

            header('Location: index.php?success=aktif');
            exit;
        }

        // NONAKTIFKAN TAHUN AJARAN
        elseif ($action === 'nonaktif') {

            $id = (int) ($_POST['id'] ?? 0);

            $stmt = $db->prepare("
                SELECT id FROM academic_years WHERE id = ?
            ");
            $stmt->execute([$id]);

            if (!$stmt->fetch()) {
                throw new Exception('Tahun ajaran tidak ditemukan.');
            }

            $stmt = $db->prepare("
                UPDATE academic_years
                SET is_active = 0
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            header('Location: index.php?success=nonaktif');
            exit;
        }

        // HAPUS TAHUN AJARAN
        elseif ($action === 'hapus') {

            $id = (int) ($_POST['id'] ?? 0);

            $stmt = $db->prepare("
                SELECT is_active FROM academic_years
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $year = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$year) {
                throw new Exception('Tahun ajaran tidak ditemukan.');
            }

            if ((int) $year['is_active'] === 1) {
                throw new Exception('Tahun ajaran aktif tidak boleh dihapus.');
            }

            $stmt = $db->prepare("
                DELETE FROM academic_years WHERE id = ?
            ");
            $stmt->execute([$id]);

            header('Location: index.php?success=hapus');
            exit;
        }

    } catch (PDOException $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $error = 'Terjadi kesalahan database. Periksa kembali data dan relasinya.';

    } catch (Exception $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $error = $e->getMessage();
    }
}

//notif
if (isset($_GET['success'])) {

    $messages = [
        'tambah' => 'Tahun ajaran berhasil ditambahkan.',
        'edit' => 'Tahun ajaran berhasil diperbarui.',
        'aktif' => 'Tahun ajaran aktif berhasil diubah.',
        'nonaktif' => 'Tahun ajaran berhasil dinonaktifkan.',
        'hapus' => 'Tahun ajaran berhasil dihapus.'
    ];

    $message = $messages[$_GET['success']] ?? '';
}

//Data edit

$editYear = null;

if (isset($_GET['edit'])) {

    $stmt = $db->prepare("
        SELECT * FROM academic_years WHERE id = ?
    ");

    $stmt->execute([(int) $_GET['edit']]);
    $editYear = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Daftar tahun ajaran

$stmt = $db->query("
    SELECT * FROM academic_years
    ORDER BY start_date DESC, id DESC
");

$years = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Tahun Ajaran</title>

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
            max-width: 1100px;
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

        input {
            padding: 11px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
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

        .active-btn {
            background: #0d6efd;
            color: white;
        }

        .inactive-btn {
            background: #6c757d;
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
            min-width: 750px;
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

        .badge-active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-inactive {
            background: #e9ecef;
            color: #555;
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
            <h2>Manajemen Tahun Ajaran</h2>
            <div class="subtitle">
                Kelola periode tahun ajaran sekolah.
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

        <h3><?= $editYear ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran' ?></h3>

        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="<?= $editYear ? 'edit' : 'tambah' ?>"
            >

            <?php if ($editYear): ?>
                <input type="hidden" name="id" value="<?= e($editYear['id']) ?>">
            <?php endif; ?>

            <div class="form-grid">

                <div class="form-group full">
                    <label>Tahun Ajaran</label>
                    <input
                        type="text"
                        name="year_name"
                        placeholder="Contoh: 2026/2027"
                        required
                        value="<?= e($editYear['year_name'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input
                        type="date"
                        name="start_date"
                        required
                        value="<?= e($editYear['start_date'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Tanggal Berakhir</label>
                    <input
                        type="date"
                        name="end_date"
                        required
                        value="<?= e($editYear['end_date'] ?? '') ?>"
                    >
                </div>

            </div>

            <br>

            <button type="submit" class="primary">
                <?= $editYear ? 'Simpan Perubahan' : 'Tambah Tahun Ajaran' ?>
            </button>

            <?php if ($editYear): ?>
                <a href="index.php" class="btn cancel">Batal Edit</a>
            <?php endif; ?>

        </form>

    </div>

    <div class="card">

        <h3>Daftar Tahun Ajaran</h3>

        <div class="table-wrapper">

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tahun Ajaran</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Berakhir</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($years) > 0): ?>

                        <?php $no = 1; ?>

                        <?php foreach ($years as $year): ?>

                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= e($year['year_name']) ?></td>
                                <td><?= e($year['start_date']) ?></td>
                                <td><?= e($year['end_date']) ?></td>

                                <td>
                                    <?php if ((int) $year['is_active'] === 1): ?>
                                        <span class="badge badge-active">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="actions">

                                        <a
                                            href="index.php?edit=<?= e($year['id']) ?>"
                                            class="btn edit"
                                        >
                                            Edit
                                        </a>

                                        <?php if ((int) $year['is_active'] === 1): ?>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Yakin ingin menonaktifkan tahun ajaran ini?')"
                                            >
                                                <input type="hidden" name="action" value="nonaktif">
                                                <input type="hidden" name="id" value="<?= e($year['id']) ?>">

                                                <button type="submit" class="inactive-btn">
                                                    Nonaktifkan
                                                </button>
                                            </form>

                                        <?php else: ?>

                                            <form method="POST">
                                                <input type="hidden" name="action" value="aktif">
                                                <input type="hidden" name="id" value="<?= e($year['id']) ?>">

                                                <button type="submit" class="active-btn">
                                                    Aktifkan
                                                </button>
                                            </form>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Yakin ingin menghapus tahun ajaran ini?')"
                                            >
                                                <input type="hidden" name="action" value="hapus">
                                                <input type="hidden" name="id" value="<?= e($year['id']) ?>">

                                                <button type="submit" class="delete">
                                                    Hapus
                                                </button>
                                            </form>

                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" style="text-align:center;">
                                Belum ada data tahun ajaran.
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
