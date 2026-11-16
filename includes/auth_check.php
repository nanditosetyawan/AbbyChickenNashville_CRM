<?php
/**
 * Session guard + role check.
 * Wajib di-include setelah config/database.php pada setiap halaman di pages/.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

/** Apakah user sudah login (pegawai atau member/user). */
function sudah_login()
{
    return !empty($_SESSION['pegawai_id']) || !empty($_SESSION['member_id']);
}

/**
 * Hentikan akses bila belum login.
 */
function cek_login()
{
    if (!sudah_login()) {
        set_flash('error', 'Silakan login terlebih dahulu.');
        redirect(base_url('index.php'));
    }
}

/**
 * Batasi halaman berdasarkan role.
 *
 * @param array $roles_allowed Contoh: ['Admin'] atau ['User', 'Member']
 */
function cek_akses(array $roles_allowed)
{
    cek_login();
    $role = $_SESSION['role'] ?? '';
    // Samakan toleransi User & Member
    if ($role === 'Member' && in_array('User', $roles_allowed, true)) {
        return;
    }
    if ($role === 'User' && in_array('Member', $roles_allowed, true)) {
        return;
    }
    if (!in_array($role, $roles_allowed, true)) {
        http_response_code(403);
        set_flash('error', 'Role ' . e($role) . ' tidak memiliki akses ke halaman ini.');
        redirect(base_url('pages/unauthorized.php'));
    }
}

/**
 * Daftar modul yang boleh dibuka per role.
 * Dipakai sidebar dan pengecekan menu aktif.
 */
function menu_per_role($role)
{
    if ($role === 'User' || $role === 'Member') {
        return [
            'dashboard' => ['label' => 'Dashboard', 'url' => 'pages/user/dashboard.php', 'icon' => 'dashboard', 'roles' => ['User', 'Member']],
            'pesan'     => ['label' => 'Pesan',     'url' => 'pages/user/pesan.php',     'icon' => 'order',     'roles' => ['User', 'Member']],
            'keluhan'   => ['label' => 'Keluhan',   'url' => 'pages/user/keluhan.php',   'icon' => 'complaint', 'roles' => ['User', 'Member']],
            'ulasan'    => ['label' => 'Ulasan',    'url' => 'pages/user/ulasan.php',    'icon' => 'voucher',   'roles' => ['User', 'Member']],
        ];
    }

    $semua = [
        'dashboard'     => ['label' => 'Dashboard',          'url' => 'pages/dashboard/index.php',  'icon' => 'dashboard', 'roles' => ['Admin', 'Kasir', 'PIC Keluhan', 'Pimpinan']],
        'membership'    => ['label' => 'Membership',         'url' => 'pages/membership/index.php', 'icon' => 'member',    'roles' => ['Admin']],
        'orders'     => ['label' => 'Pesan Online',     'url' => 'pages/orders/index.php',     'icon' => 'order',     'roles' => ['Admin', 'Kasir']],
        'voucher'    => ['label' => 'Ulasan & Voucher', 'url' => 'pages/voucher/index.php',    'icon' => 'voucher',   'roles' => ['Admin', 'Kasir']],
        'complaints' => ['label' => 'Keluhan',          'url' => 'pages/complaints/index.php', 'icon' => 'complaint', 'roles' => ['Admin', 'PIC Keluhan']],
        'menu'          => ['label' => 'Stok & Menu',        'url' => 'pages/menu/index.php',       'icon' => 'menu',      'roles' => ['Admin', 'Kasir']],
        'promo'         => ['label' => 'Promo & Notifikasi', 'url' => 'pages/promo/index.php',      'icon' => 'bell',      'roles' => ['Admin']],
        'control_promo' => ['label' => 'Control Promosi',    'url' => 'pages/promo/control.php',    'icon' => 'send',      'roles' => ['Admin']],
        'pegawai'       => ['label' => 'Pegawai',            'url' => 'pages/pegawai/index.php',    'icon' => 'pegawai',   'roles' => ['Admin']],
        'aturan'        => ['label' => 'Aturan & SOP',        'url' => 'pages/aturan/index.php',     'icon' => 'book',      'roles' => ['Admin', 'Kasir', 'PIC Keluhan', 'Pimpinan']],
    ];

    return array_filter($semua, static function ($item) use ($role) {
        return in_array($role, $item['roles'], true);
    });
}
