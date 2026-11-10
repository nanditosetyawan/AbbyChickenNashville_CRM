<?php
/**
 * Handler POST modul menu.
 * action: hapus
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin']);
csrf_verify();

$action = $_POST['action'] ?? '';

if ($action === 'hapus') {
    $id = (int) ($_POST['menu_id'] ?? 0);

    $stmt = mysqli_prepare($conn, 'SELECT name FROM menu WHERE menu_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $menu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$menu) {
        set_flash('error', 'Menu tidak ditemukan.');
        redirect(base_url('pages/menu/index.php'));
    }

    // Cegah hapus menu yang sudah dipakai pada detail pesanan.
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS n FROM detail_pesanan WHERE menu_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $dipakai = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'];
    mysqli_stmt_close($stmt);

    if ($dipakai > 0) {
        set_flash('error', 'Menu ' . $menu['name'] . ' sudah dipakai pada ' . $dipakai . ' detail pesanan. Tandai sebagai tidak tersedia agar riwayat transaksi tetap utuh.');
        redirect(base_url('pages/menu/index.php'));
    }

    $stmt = mysqli_prepare($conn, 'DELETE FROM menu WHERE menu_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', 'Menu ' . $menu['name'] . ' berhasil dihapus.');
    } else {
        set_flash('error', 'Gagal menghapus menu: ' . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);

    redirect(base_url('pages/menu/index.php'));
}

set_flash('error', 'Aksi tidak dikenali.');
redirect(base_url('pages/menu/index.php'));
