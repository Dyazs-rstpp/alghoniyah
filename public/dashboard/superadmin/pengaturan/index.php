
<?php

require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../app/Middleware/AuthMiddleware.php';

$db = (new Database())->connect();

// Ambil identitas sekolah untuk ditampilkan pada halaman pengaturan
$query = $db->query("SELECT * FROM school_profile ORDER BY id ASC LIMIT 1");
$school = $query->fetch();

$schoolName = $school['school_name'] ?? 'SMK TPI Al-Ghoniyyah';

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pengaturan Sistem</title>

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
            max-width: 1100px;
            margin: auto;
        }

        h1 {
            margin-bottom: 5px;
        }

        .subtitle {
            color: #777;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
        }

        .card h2 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 19px;
        }

        .description {
            color: #777;
            font-size: 13px;
            margin-bottom: 22px;
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
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #198754;
        }

        .help {
            color: #888;
            font-size: 12px;
            margin-top: 7px;
        }

        .setting-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 17px 0;
            border-bottom: 1px solid #eee;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-info strong {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .setting-info span {
            display: block;
            color: #777;
            font-size: 12px;
        }

        .toggle {
            width: 45px;
            height: 23px;
            accent-color: #198754;
            cursor: pointer;
            flex-shrink: 0;
        }

        .btn {
            display: inline-block;
            padding: 11px 17px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
        }

        .primary {
            background: #198754;
            color: white;
        }

        .secondary {
            background: #1dab2e;
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

        .status-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            border-radius: 7px;
            background: #d1e7dd;
            color: #0f5132;
            font-size: 13px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .version {
            color: #888;
            font-size: 12px;
            margin-top: 20px;
            text-align: center;
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

            .setting-item {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Pengaturan Sistem</h1>

    <p class="subtitle">
        Kelola konfigurasi dan pengaturan utama sistem informasi sekolah.
    </p>

    <!-- IDENTITAS APLIKASI -->
    <div class="card">

        <h2>Identitas Aplikasi</h2>

        <p class="description">
            Informasi dasar aplikasi yang digunakan dalam sistem sekolah.
        </p>

        <div class="form-grid">

            <div class="form-group full">
                <label>Nama Aplikasi</label>
                <input
                    type="text"
                    value="Sistem Informasi SMK TPI Al-Ghoniyyah"
                    readonly
                >
            </div>

            <div class="form-group">
                <label>Nama Sekolah</label>
                <input
                    type="text"
                    value="<?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?>"
                    readonly
                >
            </div>

            <div class="form-group">
                <label>Versi Sistem</label>
                <input type="text" value="1.0.0" readonly>
            </div>

            <div class="form-group full">
                <label>Deskripsi Aplikasi</label>
                <textarea readonly>Sistem informasi terintegrasi untuk pengelolaan administrasi, akademik, dan layanan sekolah SMK TPI Al-Ghoniyyah.</textarea>
            </div>

        </div>

    </div>

    <!-- PENGATURAN TAMPILAN -->
    <div class="card">

        <h2>Pengaturan Tampilan</h2>

        <p class="description">
            Pengaturan tampilan umum aplikasi.
        </p>

        <div class="form-grid">

            <div class="form-group">
                <label>Tema Aplikasi</label>
                <select>
                    <option value="light" selected>Terang</option>
                    <option value="dark">Gelap</option>
                    <option value="system">Mengikuti Sistem</option>
                </select>
            </div>

            <div class="form-group">
                <label>Bahasa Aplikasi</label>
                <select>
                    <option value="id" selected>Bahasa Indonesia</option>
                    <option value="en">English</option>
                </select>
            </div>

        </div>

    </div>

    <!-- PENGATURAN AKSES -->
    <div class="card">

        <h2>Pengaturan Akses Pengguna</h2>

        <p class="description">
            Pengaturan umum yang berkaitan dengan akses pengguna sistem.
        </p>

        <div class="setting-item">
            <div class="setting-info">
                <strong>Registrasi Pengguna</strong>
                <span>Mengizinkan pendaftaran akun secara mandiri.</span>
            </div>

            <input type="checkbox" class="toggle" disabled>
        </div>

        <div class="setting-item">
            <div class="setting-info">
                <strong>Login Siswa dan Alumni</strong>
                <span>Mengaktifkan layanan login siswa dan alumni.</span>
            </div>

            <input type="checkbox" class="toggle" checked disabled>
        </div>

        <div class="setting-item">
            <div class="setting-info">
                <strong>Login Manajemen Sekolah</strong>
                <span>Mengaktifkan layanan login Superadmin, Admin, dan Guru.</span>
            </div>

            <input type="checkbox" class="toggle" checked disabled>
        </div>

    </div>

    <!-- PENGATURAN PEMELIHARAAN -->
    <div class="card">

        <h2>Pemeliharaan Sistem</h2>

        <p class="description">
            Fitur untuk pemeliharaan dan pengelolaan kondisi aplikasi.
        </p>

        <div class="setting-item">
            <div class="setting-info">
                <strong>Mode Pemeliharaan</strong>
                <span>Membatasi akses pengguna ketika sistem sedang diperbaiki.</span>
            </div>

            <input type="checkbox" class="toggle" disabled>
        </div>

        <div class="setting-item">
            <div class="setting-info">
                <strong>Informasi Sistem</strong>
                <span>Menampilkan informasi versi dan konfigurasi aplikasi.</span>
            </div>

            <button type="button" class="btn info">
                Lihat Informasi
            </button>
        </div>

        <div class="setting-item">
            <div class="setting-info">
                <strong>Backup Database</strong>
                <span>Fitur pencadangan database untuk menjaga keamanan data.</span>
            </div>

            <button type="button" class="btn warning">
                Backup Database
            </button>
        </div>

    </div>

    <!-- STATUS SISTEM -->
    <div class="card">

        <h2>Status Sistem</h2>

        <p class="description">
            Informasi kondisi umum aplikasi.
        </p>

        <div class="status-box">
            <strong>●</strong>
            Sistem Informasi SMK TPI Al-Ghoniyyah siap digunakan.
        </div>

        <p class="version">
            Sistem Informasi SMK TPI Al-Ghoniyyah — Versi 1.0.0
        </p>

    </div>

    <div class="actions">
        <a href="../index.php" class="btn secondary">
            Kembali ke Dashboard
        </a>
    </div>

</div>

</body>
</html>
