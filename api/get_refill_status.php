<?php
/**
 * AJAX API: Ambil status refill nasi terkini beserta data timer.
 * Jika status sedang habis namun timer telah terlewati, get_refill_nasi_info() otomatis
 * mengupdate status menjadi 'ready' di database.
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$info = get_refill_nasi_info();

echo json_encode([
    'success'      => true,
    'status'       => $info['status'],
    'is_ready'     => $info['is_ready'],
    'has_timer'    => $info['has_timer'],
    'timer_end'    => $info['timer_end'],
    'timer_ts'     => $info['timer_ts'],
    'seconds_left' => $info['seconds_left'],
    'target_clock' => !empty($info['timer_ts']) ? date('H:i', $info['timer_ts']) . ' WIB' : '',
    'time_now'     => time(),
], JSON_UNESCAPED_UNICODE);
exit;
