<?php
/**
 * Stream binary foto keluhan langsung dari database MySQL (MEDIUMBLOB).
 * Menghindari penyimpanan file di server fisik (Format 2: DB BLOB).
 * Hak akses: Admin, PIC Keluhan, Pemimpin, Kasir, dan Member pemilik tiket.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

cek_login();

$tiket_id = (int) ($_GET['id'] ?? 0);
if ($tiket_id <= 0) {
    http_response_code(400);
    exit('ID tiket tidak valid.');
}

$role       = $_SESSION['role'] ?? '';
$member_id  = (int) ($_SESSION['member_id'] ?? 0);

// Ambil binary foto, mime, dan pemilik tiket
$stmt = mysqli_prepare($conn, "SELECT tiket_id, member_id, pegawai_id, foto, foto_mime FROM tiket WHERE tiket_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $tiket_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$row || empty($row['foto'])) {
    http_response_code(404);
    exit('Foto bukti keluhan tidak ditemukan.');
}

// Cek hak akses keamanan
$boleh_akses = false;
if (in_array($role, ['Admin', 'Pemimpin', 'PIC Keluhan', 'Kasir'], true)) {
    $boleh_akses = true;
} elseif (in_array($role, ['User', 'Member'], true) && $member_id === (int) $row['member_id']) {
    $boleh_akses = true;
}

if (!$boleh_akses) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$mime = !empty($row['foto_mime']) ? $row['foto_mime'] : 'image/jpeg';
$binary = $row['foto'];

// Header respons gambar dengan caching browser
header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($binary));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
echo $binary;
exit;
