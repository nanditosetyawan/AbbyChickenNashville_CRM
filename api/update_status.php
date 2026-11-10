<?php
/**
 * AJAX: ubah status ketersediaan menu.
 * Menerima POST menu_id, status, csrf_token. Mengembalikan JSON.
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
    balas(['success' => false, 'message' => 'Hanya admin yang dapat mengubah ketersediaan menu.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    balas(['success' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
    balas(['success' => false, 'message' => 'Token CSRF tidak valid. Muat ulang halaman.'], 419);
}

$menu_id = (int) ($_POST['menu_id'] ?? 0);
$status  = $_POST['status'] ?? '';

if ($menu_id <= 0) {
    balas(['success' => false, 'message' => 'Menu tidak valid.'], 422);
}

if (!in_array($status, ['tersedia', 'tidak tersedia'], true)) {
    balas(['success' => false, 'message' => 'Status ketersediaan tidak dikenal.'], 422);
}

$stmt = mysqli_prepare($conn, 'SELECT name FROM menu WHERE menu_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $menu_id);
mysqli_stmt_execute($stmt);
$menu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$menu) {
    balas(['success' => false, 'message' => 'Menu tidak ditemukan.'], 404);
}

$stmt = mysqli_prepare($conn, 'UPDATE menu SET status_ketersediaan = ?, updated_at = CURRENT_TIMESTAMP WHERE menu_id = ?');
mysqli_stmt_bind_param($stmt, 'si', $status, $menu_id);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    balas(['success' => false, 'message' => 'Gagal menyimpan perubahan ke database.'], 500);
}
mysqli_stmt_close($stmt);

balas([
    'success' => true,
    'message' => $menu['name'] . ' ditandai ' . $status . '.',
    'status'  => $status,
]);
