<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

session_start();
set_flash('info', 'Anda sudah keluar dari sistem.');
redirect(base_url('index.php'));
