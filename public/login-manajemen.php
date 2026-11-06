
<?php

require_once __DIR__ . '/../app/Controllers/Auth/AuthController.php';

Session::start();

// Jika sudah login, arahkan ke dashboard sesuai role.
if (Session::isLoggedIn()) {
    $role = Session::getRole();

    $dashboards = [
        'superadmin' => 'superadmin',
        'admin'      => 'admin',
        'guru'       => 'guru'
    ];

    if (isset($dashboards[$role])) {
        header('Location: dashboard/' . $dashboards[$role] . '/index.php');
        exit;
    }

    Session::destroy();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $auth = new AuthController();

        $result = $auth->login(
            $identifier,
            $password,
            ['superadmin', 'admin', 'guru']
        );

        if ($result['success']) {
            header('Location: ' . $result['redirect']);
            exit;
        }

        $error = $result['message'];

    } catch (PDOException $e) {
        $error = 'Terjadi kesalahan pada sistem. Silakan coba kembali.';
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Manajemen Sekolah | SMK TPI Al-Ghoniyyah</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7f6;
            min-height: 100vh;
        }

        .login-container {
            display: flex;
            min-height: 100vh;
        }

        /* PANEL KIRI */
        .login-sidebar {
            width: 35%;
            background: #07543f;
            color: white;
            padding: 45px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .school-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .school-logo {
            width: 58px;
            height: 58px;
            border-radius: 12px;
            background: white;
            color: #07543f;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 18px;
        }

        .school-name {
            font-size: 15px;
            font-weight: bold;
            line-height: 1.5;
        }

        .sidebar-content h1 {
            font-size: 35px;
            font-weight: normal;
            margin-bottom: 15px;
        }

        .sidebar-content p {
            font-size: 14px;
            line-height: 1.8;
            color: #d5e8e0;
        }

        .sidebar-footer {
            font-size: 12px;
            color: #d5e8e0;
        }

        /* PANEL KANAN */
        .login-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 35px;
        }

        .login-card {
            width: 100%;
            max-width: 430px;
        }

        .login-heading {
            margin-bottom: 30px;
        }

        .login-heading h2 {
            font-size: 27px;
            color: #183b30;
            margin-bottom: 9px;
        }

        .login-heading p {
            font-size: 14px;
            color: #777;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #333;
            margin-bottom: 9px;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            border: 1px solid #dce3df;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        .form-group input:focus {
            border-color: #087451;
            box-shadow: 0 0 0 3px rgba(8, 116, 81, 0.1);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 75px;
        }

        .show-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: #087451;
            cursor: pointer;
            font-size: 12px;
        }

        .login-button {
            width: 100%;
            padding: 14px;
            background: #d93232;
            color: white;
            border: none;
            border-radius: 7px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 8px;
        }

        .login-button:hover {
            background: #b92323;
        }

        .error-message {
            background: #fff0f0;
            color: #b42318;
            border: 1px solid #f5c2c0;
            padding: 12px;
            border-radius: 7px;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #087451;
            font-size: 13px;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-sidebar {
                width: 100%;
                min-height: 220px;
                padding: 25px;
            }

            .sidebar-content {
                margin-top: 40px;
            }

            .sidebar-content h1 {
                font-size: 27px;
            }

            .sidebar-footer {
                display: none;
            }

            .login-main {
                padding: 35px 22px;
            }
        }
    </style>
</head>

<body>

<div class="login-container">

    <!-- PANEL KIRI -->
    <aside class="login-sidebar">

        <div class="school-brand">
            <div class="school-logo">TPI</div>

            <div class="school-name">
                SMK TPI Al-Ghoniyyah<br>
                <small>Portal Manajemen Sekolah</small>
            </div>
        </div>

        <div class="sidebar-content">
            <h1>Portal Pengguna</h1>
            <p>
                Silakan masuk untuk mengakses sistem
                manajemen dan informasi sekolah.
            </p>
        </div>

        <div class="sidebar-footer">
            &copy; <?= date('Y'); ?> SMK TPI Al-Ghoniyyah.
            Seluruh hak dilindungi.
        </div>

    </aside>

    <!-- PANEL KANAN -->
    <main class="login-main">

        <div class="login-card">

            <div class="login-heading">
                <h2>Login Manajemen Sekolah</h2>
                <p>
                    Masukkan username atau email dan password
                    akun yang telah diberikan.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="on">

                <div class="form-group">
                    <label for="identifier">Username / Email</label>

                    <input
                        type="text"
                        id="identifier"
                        name="identifier"
                        placeholder="Masukkan username atau email"
                        value="<?= htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Kata Sandi</label>

                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan kata sandi"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="show-password"
                            onclick="togglePassword()"
                        >
                            Lihat
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-button">
                    Masuk ke Portal
                </button>

            </form>

            <a href="index.php" class="back-link">
                &larr; Kembali ke Website Sekolah
            </a>

        </div>

    </main>

</div>

<script>
    function togglePassword() {
        const password = document.getElementById('password');
        const button = document.querySelector('.show-password');

        if (password.type === 'password') {
            password.type = 'text';
            button.textContent = 'Sembunyikan';
        } else {
            password.type = 'password';
            button.textContent = 'Lihat';
        }
    }
</script>

</body>
</html>
