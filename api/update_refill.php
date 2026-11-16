<?php
/**
 * AJAX API: Ubah status ketersediaan refill nasi & kontrol timer restok otomatis.
 * Menerima POST status ('ready' atau 'habis'), optional timer_minutes (int), csrf_token.
 * Dapat diakses oleh Admin maupun Kasir. Mengembalikan JSON.
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

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['Admin', 'Kasir'], true)) {
    balas(['success' => false, 'message' => 'Hanya Admin atau Kasir yang dapat mengubah status refill nasi.'], 403);
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

$timer_minutes = (int) ($_POST['timer_minutes'] ?? 0);
if ($timer_minutes < 0) {
    $timer_minutes = 0;
}

if ($status === 'ready') {
    set_setting('refill_nasi', 'ready');
    set_setting('refill_nasi_timer', '');
    $pesan = 'Stok refill nasi berhasil diubah: Ready (Tersedia)';
} else {
    set_setting('refill_nasi', 'habis');
    if ($timer_minutes > 0) {
        $waktu_selesai = date('Y-m-d H:i:s', time() + ($timer_minutes * 60));
        set_setting('refill_nasi_timer', $waktu_selesai);

        if ($timer_minutes >= 60) {
            $jam = round($timer_minutes / 60, 1);
            $durasi_teks = ($jam == (int) $jam ? (int) $jam : $jam) . ' jam';
        } else {
            $durasi_teks = $timer_minutes . ' menit';
        }

        $pesan = 'Stok refill nasi: Habis. Otomatis restok dalam ' . $durasi_teks . ' (pukul ' . date('H:i', strtotime($waktu_selesai)) . ' WIB).';
    } else {
        set_setting('refill_nasi_timer', '');
        $pesan = 'Stok refill nasi: Habis (Manual tanpa timer).';
    }
}

$info = get_refill_nasi_info();

balas([
    'success'      => true,
    'status'       => $info['status'],
    'is_ready'     => $info['is_ready'],
    'has_timer'    => $info['has_timer'],
    'timer_end'    => $info['timer_end'],
    'timer_ts'     => $info['timer_ts'],
    'seconds_left' => $info['seconds_left'],
    'message'      => $pesan,
]);
