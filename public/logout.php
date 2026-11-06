<?php

require_once __DIR__ . '/../app/Core/Session.php';

Session::destroy();

header('Location: login-manajemen.php');
exit;