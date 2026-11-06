
<?php

class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => false, // Ubah menjadi true kalo udah HTTPS.
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_start();
    }

    /*** Menyimpan informasi pengguna setelah login berhasil.*/
    public static function setUser($user)
    {
        self::start();

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'        => $user['id'],
            'full_name' => $user['full_name'],
            'username'  => $user['username'],
            'role'      => $user['role']
        ];
    }

    /*** Mengambil informasi pengguna yang sedang login.*/
    public static function getUser()
    {
        self::start();

        return $_SESSION['user'] ?? null;
    }

    /*** Mengambil role */
    public static function getRole()
    {
        $user = self::getUser();

        return $user['role'] ?? null;
    }

    /*** Memeriksa sudah login blom.*/
    public static function isLoggedIn()
    {
        return self::getUser() !== null;
    }

    /*** Memeriksa userpunya  salah satu role yang diizinkan.*/
    public static function hasRole($roles)
    {
        $currentRole = self::getRole();

        if ($currentRole === null) {
            return false;
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        return in_array($currentRole, $roles, true);
    }

    /**Menghapus session saat logout.*/
    public static function destroy()
    {
        self::start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax'
                ]
            );
        }

        session_destroy();
    }
}
