
<?php

require_once __DIR__ . '/../../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../config/database.php';

AuthMiddleware::requireRole(
    ['superadmin'],
    '../../login-manajemen.php'
);

Session::start();

$user = Session::getUser();

$database = new Database();
$db = $database->connect();

// Mengambil jumlah pengguna berdasarkan role
$query = "
    SELECT roles.code, COUNT(users.id) AS total
    FROM roles
    LEFT JOIN users ON users.role_id = roles.id
    GROUP BY roles.id, roles.code
";

$stmt = $db->query($query);
$roleCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$jumlahAdmin = $roleCounts['admin'] ?? 0;
$jumlahGuru = $roleCounts['guru'] ?? 0;
$jumlahSiswa = $roleCounts['siswa'] ?? 0;
$jumlahAlumni = $roleCounts['alumni'] ?? 0;

$totalPengguna = $jumlahAdmin + $jumlahGuru + $jumlahSiswa + $jumlahAlumni;

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Superadmin | SMK TPI Al-Ghoniyyah</title>

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

        <p class="menu-title">Manajemen</p>

        <a href="admin/index.php">Data Admin</a>
        <a href="guru/index.php">Data Guru</a>
        <a href="siswa/index.php">Data Siswa</a>
        <a href="alumni/index.php">Data Alumni</a>

        <p class="menu-title">Pengaturan</p>

        <a href="profil-sekolah/index.php">Profil Sekolah</a>
        <a href="pengaturan/index.php">Pengaturan Sistem</a>
        <a href="../../logout.php"
        onclick="return confirm('Apakah kamu yakin ingin logout?');">
        Logout
</a>
    </div>

</aside>

<main class="main">

    <div class="topbar">
        <h2>Dashboard Superadmin</h2>

        <span>
            <?= htmlspecialchars($user['full_name'] ?? 'Superadmin'); ?>
            &nbsp; | &nbsp; Superadmin
        </span>
    </div>

    <div class="welcome">
        <h1>Selamat Datang, Superadmin!</h1>
        <p>
            Berikut ringkasan data pengguna
            SMK TPI Al-Ghoniyyah.
        </p>
    </div>

    <div class="cards">

        <div class="card">
            <p>Total Pengguna</p>
            <h2><?= $totalPengguna; ?></h2>
        </div>

        <div class="card">
            <p>Administrator</p>
            <h2><?= $jumlahAdmin; ?></h2>
        </div>

        <div class="card">
            <p>Guru</p>
            <h2><?= $jumlahGuru; ?></h2>
        </div>

        <div class="card">
            <p>Siswa</p>
            <h2><?= $jumlahSiswa; ?></h2>
        </div>

        <div class="card">
            <p>Alumni</p>
            <h2><?= $jumlahAlumni; ?></h2>
        </div>

    </div>

    <div class="section">
        <h3>Informasi Sistem</h3>

        <p>
            Selamat datang di panel Superadmin
            SMK TPI Al-Ghoniyyah. Melalui halaman ini,
            Superadmin dapat mengelola administrasi
            pengguna dan pengaturan sistem sekolah.
        </p>
    </div>

</main>

</body>
</html>
