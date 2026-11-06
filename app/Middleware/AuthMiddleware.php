<?php

require_once __DIR__ . '/../Core/Session.php';

class AuthMiddleware
{
    /*** Memastikan sudah login.*/
    public static function requireLogin($loginUrl)
    {
        Session::start();

        if (!Session::isLoggedIn()) {
            header("Location: " . $loginUrl);
            exit;
        }
    }

    /*** Memastikan user punya role yang diizinkan.*/
    public static function requireRole($allowedRoles, $loginUrl)
    {
        self::requireLogin($loginUrl);

        if (!Session::hasRole($allowedRoles)) {
            http_response_code(403);

            echo "<!DOCTYPE html>";
            echo "<html lang='id'>";
            echo "<head>";
            echo "<meta charset='UTF-8'>";
            echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
            echo "<title>Akses Ditolak</title>";
            echo "</head>";

            echo "<body style='font-family:Arial; padding:40px;'>";
            echo "<h1>403 - Akses Ditolak</h1>";
            echo "<p>Maaf, kamu tidak memiliki izin untuk membuka halaman ini.</p>";
            echo "<a href='javascript:history.back()'>Kembali</a>";
            echo "</body>";
            echo "</html>";

            exit;
        }
    }

    /*** Memastikan belum login.*/
    public static function requireGuest($dashboardUrl)
    {
        Session::start();

        if (Session::isLoggedIn()) {
            header("Location: " . $dashboardUrl);
            exit;
        }
    }

    /*** Mendapatkan URL dashboard dinamis sesuai role user yang sedang login.*/
    public static function getDashboardUrl()
    {
        Session::start();
        $user = Session::getUser();
        $role = $user['role'] ?? '';

        $dashboards = [
            'superadmin' => '/smk-tpi-alghoniyyah/public/dashboard/superadmin/index.php',
            'admin'      => '/smk-tpi-alghoniyyah/public/dashboard/admin/index.php',
            'guru'       => '/smk-tpi-alghoniyyah/public/dashboard/guru/index.php',
            'siswa'      => '/smk-tpi-alghoniyyah/public/dashboard/siswa/index.php',
            'alumni'     => '/smk-tpi-alghoniyyah/public/dashboard/alumni/index.php',
        ];

        return $dashboards[$role] ?? '/smk-tpi-alghoniyyah/public/login-manajemen.php';
    }
}