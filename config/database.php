<?php
/**
 * Koneksi database MySQL (mysqli).
 * Ubah nilai di bawah bila kredensial lokal berbeda.
 */

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'abbychicken_crm');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error() . '. Pastikan MySQL aktif dan database <strong>abbychicken_crm</strong> sudah diimpor dari database.sql.');
}

mysqli_set_charset($conn, 'utf8mb4');

date_default_timezone_set('Asia/Jakarta');
