<?php

require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../app/Middleware/AuthMiddleware.php';

$db = (new Database())->connect();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$success = '';
$error = '';

// Lokasi penyimpanan logo
$uploadDir = __DIR__ . '/../../../uploads/school/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Ambil data profil sekolah
$query = $db->query("SELECT * FROM school_profile ORDER BY id ASC LIMIT 1");
$profile = $query->fetch();

// Jika belum ada data profil, buat data awal
if (!$profile) {
    $insert = $db->prepare("
        INSERT INTO school_profile (id, school_name)
        VALUES (1, 'SMK TPI Al-Ghoniyyah')
    ");
    $insert->execute();

    $query = $db->query("SELECT * FROM school_profile WHERE id = 1");
    $profile = $query->fetch();
}

// Proses simpan profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $schoolName = trim($_POST['school_name'] ?? '');
        $npsn = trim($_POST['npsn'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $history = trim($_POST['history'] ?? '');
        $vision = trim($_POST['vision'] ?? '');
        $mission = trim($_POST['mission'] ?? '');

        if ($schoolName === '') {
            throw new Exception("Nama sekolah wajib diisi.");
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format email tidak valid.");
        }

        $logo = $profile['logo'] ?? null;

        // Hapus logo jika diminta
        if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
            if ($logo) {
                $oldFile = $uploadDir . basename($logo);

                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }

            $logo = null;
        }

        // Proses upload logo baru
        if (
            isset($_FILES['logo']) &&
            $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Upload logo gagal.");
            }

            if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Ukuran logo maksimal 2 MB.");
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['logo']['tmp_name']);

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($allowedTypes[$mime])) {
                throw new Exception("Logo harus berformat JPG, PNG, atau WEBP.");
            }

            $extension = $allowedTypes[$mime];
            $fileName = 'logo_sekolah_' . bin2hex(random_bytes(8)) . '.' . $extension;

            if (!move_uploaded_file(
                $_FILES['logo']['tmp_name'],
                $uploadDir . $fileName
            )) {
                throw new Exception("Gagal menyimpan file logo.");
            }

            // Hapus logo lama setelah logo baru berhasil diunggah
            if ($logo) {
                $oldFile = $uploadDir . basename($logo);

                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }

            $logo = 'uploads/school/' . $fileName;
        }

        $update = $db->prepare("
            UPDATE school_profile SET
                school_name = ?,
                npsn = ?,
                address = ?,
                postal_code = ?,
                phone = ?,
                email = ?,
                logo = ?,
                history = ?,
                vision = ?,
                mission = ?
            WHERE id = ?
        ");

        $update->execute([
            $schoolName,
            $npsn !== '' ? $npsn : null,
            $address !== '' ? $address : null,
            $postalCode !== '' ? $postalCode : null,
            $phone !== '' ? $phone : null,
            $email !== '' ? $email : null,
            $logo,
            $history !== '' ? $history : null,
            $vision !== '' ? $vision : null,
            $mission !== '' ? $mission : null,
            $profile['id']
        ]);

        $success = "Profil sekolah berhasil diperbarui.";

        // Ambil ulang data terbaru
        $query = $db->prepare("SELECT * FROM school_profile WHERE id = ?");
        $query->execute([$profile['id']]);
        $profile = $query->fetch();

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Profil Sekolah</title>

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
            max-width: 1000px;
            margin: auto;
        }

        /* --- CSS Baru untuk Header & Tombol Kembali --- */
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
            color: #006b5a; /* Warna hijau sesuai gambar */
            text-decoration: none;
            font-size: 14px;
            font-weight: normal;
        }

        .btn-back:hover {
            text-decoration: underline;
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
            gap: 18px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #198754;
        }

        button {
            background: #198754;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 20px;
        }

        button:hover {
            background: #146c43;
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 13px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .error {
            background: #f8d7da;
            color: #842029;
            padding: 13px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .logo-preview {
            width: 130px;
            height: 130px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px;
            display: block;
            margin-bottom: 12px;
            background: white;
        }

        .help {
            color: #777;
            font-size: 12px;
            margin-top: 7px;
        }

        .remove-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            font-size: 13px;
        }

        .remove-logo input {
            width: auto;
        }

        @media (max-width: 700px) {
            body {
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }
            
            /* Agar rapi di HP */
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
            <h1>Profil Sekolah</h1>
            <p class="subtitle">
                Kelola identitas dan informasi resmi SMK TPI Al-Ghoniyyah.
            </p>
        </div>
        <a href="../index.php" class="btn-back">&larr; Kembali ke Dashboard</a>
    </div>

    <?php if ($success): ?>
        <div class="success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card">

        <h2>Informasi Identitas Sekolah</h2>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-grid">

                <div class="form-group full">
                    <label>Nama Sekolah *</label>
                    <input
                        type="text"
                        name="school_name"
                        value="<?= e($profile['school_name']) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>NPSN</label>
                    <input
                        type="text"
                        name="npsn"
                        value="<?= e($profile['npsn']) ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Kode Pos</label>
                    <input
                        type="text"
                        name="postal_code"
                        value="<?= e($profile['postal_code']) ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input
                        type="text"
                        name="phone"
                        value="<?= e($profile['phone']) ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Email Sekolah</label>
                    <input
                        type="email"
                        name="email"
                        value="<?= e($profile['email']) ?>"
                    >
                </div>

                <div class="form-group full">
                    <label>Alamat Sekolah</label>
                    <textarea name="address"><?= e($profile['address']) ?></textarea>
                </div>

                <div class="form-group full">
                    <label>Logo Sekolah</label>

                    <?php if (!empty($profile['logo'])): ?>
                        <img
                            src="../../../<?= e($profile['logo']) ?>"
                            alt="Logo Sekolah"
                            class="logo-preview"
                        >

                        <label class="remove-logo">
                            <input type="checkbox" name="remove_logo" value="1">
                            Hapus logo saat menyimpan
                        </label>
                    <?php endif; ?>

                    <input
                        type="file"
                        name="logo"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <p class="help">
                        Format JPG, PNG, WEBP. Ukuran maksimal 2 MB.
                    </p>
                </div>

            </div>

            <hr style="border:0;border-top:1px solid #eee;margin:25px 0;">

            <h2>Informasi Profil Sekolah</h2>

            <div class="form-grid">

                <div class="form-group full">
                    <label>Sejarah Sekolah</label>
                    <textarea name="history"><?= e($profile['history']) ?></textarea>
                </div>

                <div class="form-group full">
                    <label>Visi Sekolah</label>
                    <textarea name="vision"><?= e($profile['vision']) ?></textarea>
                </div>

                <div class="form-group full">
                    <label>Misi Sekolah</label>
                    <textarea name="mission"><?= e($profile['mission']) ?></textarea>
                </div>

            </div>

            <button type="submit">
                Simpan Perubahan Profil
            </button>

        </form>

    </div>

</div>

</body>
</html>

```