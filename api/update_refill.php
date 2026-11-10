<?php
/**
 * AJAX API: Ubah status ketersediaan refill nasi.
 * Menerima POST status ('ready' atau 'habis'), csrf_token. Mengembalikan JSON.
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

function balas($data, $kode = 200)
{
    http_response_code($kode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['pegawai_id'])) {
    balas(['success' => false, 'message' => 'Sesi berakhir. Silakan login kembali.'], 401);
}

if (($_SESSION['role'] ?? '') !== 'Admin') {
    balas(['success' => false, 'message' => 'Hanya admin yang dapat mengubah status refill nasi.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    balas(['success' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
    balas(['success' => false, 'message' => 'Token CSRF tidak valid. Muat ulang halaman.'], 419);
}

$status = trim($_POST['status'] ?? '');
if (!in_array($status, ['ready', 'habis'], true)) {
    balas(['success' => false, 'message' => 'Status tidak valid.'], 422);
}

$ok = set_setting('refill_nasi', $status);
if (!$ok) {
    balas(['success' => false, 'message' => 'Gagal menyimpan status ke database.'], 500);
}

balas([
    'success' => true,
    'status'  => $status,
    'message' => 'Stok refill nasi berhasil diubah: ' . ($status === 'ready' ? 'Ready (Tersedia)' : 'Habis'),
]);

