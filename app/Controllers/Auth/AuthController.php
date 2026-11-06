
<?php

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../Core/Session.php';

class AuthController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function login($identifier, $password, $allowedRoles)
    {
        Session::start();

        // Validasi input
        if (empty($identifier) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Username/email dan password wajib diisi.'
            ];
        }

        // Ambil data akun beserta role
        $query = "
            SELECT
                users.id,
                users.full_name,
                users.username,
                users.email,
                users.password_hash,
                users.status,
                roles.code AS role
            FROM users
            INNER JOIN roles ON users.role_id = roles.id
            WHERE users.username = :identifier
               OR users.email = :identifier
            LIMIT 1
        ";

        $statement = $this->db->prepare($query);
        $statement->execute([
            ':identifier' => $identifier
        ]);

        $user = $statement->fetch();

        // Periksa akun
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Username/email atau password salah.'
            ];
        }

        // Periksa status akun
        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'Akun kamu tidak aktif atau belum dapat digunakan.'
            ];
        }

        // Periksa kesesuaian jalur login dengan role
        if (!in_array($user['role'], $allowedRoles, true)) {
            return [
                'success' => false,
                'message' => 'Akun tidak memiliki akses melalui halaman login ini.'
            ];
        }

        // Simpan informasi pengguna ke session
        Session::setUser([
            'id'        => $user['id'],
            'full_name' => $user['full_name'],
            'username'  => $user['username'],
            'role'      => $user['role']
        ]);

        // Catat waktu login terakhir
        $update = $this->db->prepare(
            "UPDATE users SET last_login_at = NOW() WHERE id = :id"
        );

        $update->execute([
            ':id' => $user['id']
        ]);

        // Tentukan dashboard berdasarkan role
        $dashboards = [
            'superadmin' => 'superadmin',
            'admin'      => 'admin',
            'guru'       => 'guru'
        ];

        return [
            'success' => true,
            'message' => 'Login berhasil.',
            'redirect' => '/smk-tpi-alghoniyyah/public/dashboard/'
                . $dashboards[$user['role']] . '/index.php'
        ];
    }

    public function logout()
    {
        Session::destroy();

        header(
            'Location: /smk-tpi-alghoniyyah/public/login-manajemen.php'
        );
        exit;
    }
}
