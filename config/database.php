<?php
/**
 * Koneksi database MySQL (mysqli).
 * Otomatis mendeteksi lingkungan Lokal (XAMPP) vs Server Hosting (InfinityFree).
 */

// Matikan exception fatal otomatis PHP 8.1+ agar tidak memicu blank HTTP 500 jika koneksi gagal
mysqli_report(MYSQLI_REPORT_OFF);

// Deteksi apakah sedang berjalan di lokal (XAMPP) atau di server hosting (InfinityFree)
$is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1'], true)
            || (php_sapi_name() === 'cli');

if ($is_local) {
    // 1. Kredensial XAMPP Lokal
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'abbychicken_crm');
} else {
    // 2. Kredensial Hosting InfinityFree
    // CATATAN: Ganti DB_HOST dengan "MySQL Hostname" dan DB_NAME dari vPanel InfinityFree Anda
    define('DB_HOST', 'sql209.infinityfree.com'); // Sesuaikan dengan MySQL Hostname di Control Panel InfinityFree
    define('DB_USER', 'if0_43411321');
    define('DB_PASS', 'Pg6TqhCOOhgnt');
    define('DB_NAME', 'if0_43411321_abbychicken_crm'); // Nama DB di InfinityFree selalu diawali if0_43411321_
}

try {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
} catch (Throwable $e) {
    $conn = false;
    $err_msg = $e->getMessage();
}

if (!$conn) {
    $detail_err = isset($err_msg) ? $err_msg : mysqli_connect_error();
    die('
    <div style="font-family:sans-serif; max-width:620px; margin:40px auto; padding:24px; border:2px solid #C85A32; border-radius:12px; background:#FAF9F5; color:#182015; box-shadow:0 8px 24px rgba(0,0,0,0.08);">
        <h2 style="color:#C85A32; margin-top:0;">Gagal Terhubung ke Database MySQL</h2>
        <p>Pesan error: <code style="background:#ECEAE1; padding:2px 6px; border-radius:4px; color:#C85A32;">' . htmlspecialchars($detail_err) . '</code></p>
        <hr style="border:none; border-top:1px solid #E5E0D8; margin:16px 0;">
        <p><strong>Panduan Cepat Pengaturan InfinityFree:</strong></p>
        <ul style="line-height:1.7; font-size:14px;">
            <li>Buka <strong>Control Panel InfinityFree</strong> &rarr; menu <strong>MySQL Databases</strong>.</li>
            <li>Salin <strong>MySQL Hostname</strong> (contoh: <code>sql308.infinityfree.com</code>) lalu masukkan ke <code>DB_HOST</code> di file <code>config/database.php</code>.</li>
            <li>Pastikan database sudah dibuat di vPanel dan namanya lengkap diawali username (contoh: <code>if0_43411321_xxxx</code>) pada <code>DB_NAME</code>.</li>
        </ul>
    </div>');
}

mysqli_set_charset($conn, 'utf8mb4');
date_default_timezone_set('Asia/Jakarta');
