
<?php

require_once __DIR__ . '/../app/Core/Session.php';

Session::start();

// Simulasi data pengujian.
Session::setUser([
    'id' => 1,
    'full_name' => 'Pengujian Session',
    'username' => 'tester',
    'role' => 'superadmin'
]);

echo "<h2>Pengujian Session</h2>";

if (Session::isLoggedIn()) {
    echo "Status: Session aktif<br>";
    echo "Nama: " . htmlspecialchars(Session::getUser()['full_name']) . "<br>";
    echo "Role: " . htmlspecialchars(Session::getRole()) . "<br>";

    if (Session::hasRole('superadmin')) {
        echo "Hak akses Superadmin: tersedia";
    }
} else {
    echo "Session tidak aktif.";
}
