<?php

require_once __DIR__ . '/../../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../config/database.php';

AuthMiddleware::requireRole(
    ['admin'],
    '../../login-manajemen.php'
);

Session::start();

$user = Session::getUser();

$database = new Database();
$db = $database->connect();

// Mengambil jumlah data Guru
$stmt = $db->query("SELECT COUNT(*) FROM teachers");
$jumlahGuru = $stmt->fetchColumn();

// Mengambil jumlah data Siswa
$stmt = $db->query("SELECT COUNT(*) FROM students");
$jumlahSiswa = $stmt->fetchColumn();

// Mengambil jumlah data Alumni
$stmt = $db->query("SELECT COUNT(*) FROM alumni");
$jumlahAlumni = $stmt->fetchColumn();

// Mengambil jumlah pengguna dengan role Admin
$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM users
    INNER JOIN roles ON users.role_id = roles.id
    WHERE roles.code = 'admin'
");
$stmt->execute();
$jumlahAdmin = $stmt->fetchColumn();

$totalPengguna = $jumlahAdmin + $jumlahGuru + $jumlahSiswa + $jumlahAlumni;

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin | SMK TPI Al-Ghoniyyah</title>

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
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: #07543f;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            padding: 25px 18px;
            overflow-y: auto;
        }

        .brand {
            padding: 10px;
            margin-bottom: 40px;
            border-bottom: 1px solid rgba(255,255,255,.2);
        }

        .brand h2 {
            font-size: 19px;
            margin-bottom: 7px;
        }

        .brand p {
            font-size: 12px;
            color: #c9ded5;
        }

        .menu-title {
            font-size: 11px;
            color: #b5d1c5;
            margin: 20px 10px 10px;
            text-transform: uppercase;
        }

        .menu a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 13px 12px;
            border-radius: 6px;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .menu a.active,
        .menu a:hover {
            background: #13765a;
        }

        .main {
            margin-left: 250px;
            padding: 30px;
        }

        .topbar {
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,.04);
        }

        .topbar h2 {
            font-size: 20px;
        }

        .topbar span {
            font-size: 13px;
            color: #68766f;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .welcome h1 {
            font-size: 25px;
            margin-bottom: 8px;
        }

        .welcome p {
            color: #7b8982;
            font-size: 14px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .card {
            background: white;
            padding: 23px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.04);
        }

        .card p {
            font-size: 13px;
            color: #7b8982;
            margin-bottom: 15px;
        }

        .card h2 {
            font-size: 29px;
            color: #07543f;
        }

        .section {
            margin-top: 30px;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        .section h3 {
            margin-bottom: 12px;
            font-size: 17px;
        }

        .section p {
            font-size: 14px;
            line-height: 1.8;
            color: #748078;
        }

        @media (max-width: 900px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main {
                margin-left: 0;
                padding: 18px;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .topbar {
                gap: 12px;
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

</head>

<body>

<aside class="sidebar">

    <div class="brand">
        <h2>SMK TPI</h2>
        <p>Portal Manajemen Sekolah</p>
    </div>

    <div class="menu">

        <p class="menu-title">Menu Utama</p>

        <a href="index.php" class="active">
            Dashboard
        </a>

        <p class="menu-title">Manajemen Sekolah</p>

        <a href="guru/index.php">Data Guru</a>
        <a href="siswa/index.php">Data Siswa</a>
        <a href="alumni/index.php">Data Alumni</a>

        <p class="menu-title">Akademik</p>

        <a href="tahun-ajaran/index.php">Tahun Ajaran</a>
        <a href="kelas/index.php">Data Kelas</a>
        <a href="mata-pelajaran/index.php">Mata Pelajaran</a>
        <a href="pembagian-kelas/index.php">Pembagian Kelas</a>

        <p class="menu-title">Lainnya</p>

        <a href="ppdb/index.php">Manajemen PPDB</a>

        <a href="../../logout.php"
           onclick="return confirm('Apakah kamu yakin ingin logout?');">
            Logout
        </a>

    </div>

</aside>

<main class="main">

    <div class="topbar">

        <h2>Dashboard Admin</h2>

        <span>
            <?= htmlspecialchars($user['full_name'] ?? 'Admin'); ?>
            &nbsp; | &nbsp; Admin
        </span>

    </div>

    <div class="welcome">

        <h1>Selamat Datang, Admin!</h1>

        <p>
            Berikut ringkasan data operasional
            SMK TPI Al-Ghoniyyah.
        </p>

    </div>

    <div class="cards">

        <div class="card">
            <p>Total Pengguna</p>
            <h2><?= $totalPengguna; ?></h2>
        </div>

        <div class="card">
            <p>Jumlah Guru</p>
            <h2><?= $jumlahGuru; ?></h2>
        </div>

        <div class="card">
            <p>Jumlah Siswa</p>
            <h2><?= $jumlahSiswa; ?></h2>
        </div>

        <div class="card">
            <p>Jumlah Alumni</p>
            <h2><?= $jumlahAlumni; ?></h2>
        </div>

    </div>

    <div class="section">

        <h3>Informasi Dashboard</h3>

        <p>
            Selamat datang di Dashboard Admin
            SMK TPI Al-Ghoniyyah. Melalui halaman ini,
            Admin dapat mengelola data guru, siswa,
            alumni, tahun ajaran, kelas, mata pelajaran,
            pembagian kelas, serta administrasi PPDB.
        </p>

    </div>

</main>

</body>
</html>
