<?php
/**
 * AJAX API: Tandai notifikasi promo milik member sebagai sudah dibaca.
 * Menerima POST action ('mark_read' | 'mark_all_read'), notif_id (opsional), csrf_token.
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

$role = $_SESSION['role'] ?? '';
$member_id = (int) ($_SESSION['member_id'] ?? 0);
if ($member_id <= 0 || !in_array($role, ['User', 'Member'], true)) {
    balas(['success' => false, 'message' => 'Sesi member tidak valid.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    balas(['success' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
    balas(['success' => false, 'message' => 'Token CSRF tidak valid. Muat ulang halaman.'], 419);
}

$action = $_POST['action'] ?? '';
if ($action === 'mark_all_read') {
    $stmt = mysqli_prepare($conn, 'UPDATE notifikasi_promo SET is_read = 1 WHERE member_id = ? AND is_read = 0');
    mysqli_stmt_bind_param($stmt, 'i', $member_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} elseif ($action === 'mark_read') {
    $notif_id = (int) ($_POST['notif_id'] ?? 0);
    if ($notif_id <= 0) {
        balas(['success' => false, 'message' => 'ID notifikasi tidak valid.'], 422);
    }
    $stmt = mysqli_prepare($conn, 'UPDATE notifikasi_promo SET is_read = 1 WHERE notif_id = ? AND member_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $notif_id, $member_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} else {
    balas(['success' => false, 'message' => 'Aksi tidak dikenali.'], 422);
}

$stmt_cnt = mysqli_prepare($conn, 'SELECT COUNT(*) AS n FROM notifikasi_promo WHERE member_id = ? AND is_read = 0');
mysqli_stmt_bind_param($stmt_cnt, 'i', $member_id);
mysqli_stmt_execute($stmt_cnt);
$unread = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cnt))['n'] ?? 0);
mysqli_stmt_close($stmt_cnt);

balas([
    'success' => true,
    'unread'  => $unread,
]);

