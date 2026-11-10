<?php
/**
 * Handler POST modul membership.
 * action: hapus
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin']);
csrf_verify();

$action = $_POST['action'] ?? '';

if ($action === 'hapus') {
    $id = (int) ($_POST['member_id'] ?? 0);

    if ($id <= 0) {
        set_flash('error', 'Member tidak valid.');
        redirect(base_url('pages/membership/index.php'));
    }

    $stmt = mysqli_prepare($conn, 'SELECT name FROM member WHERE member_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$member) {
        set_flash('error', 'Member tidak ditemukan.');
        redirect(base_url('pages/membership/index.php'));
    }

    mysqli_begin_transaction($conn);
    try {
        // Bersihkan turunan yang tidak memakai ON DELETE CASCADE pada pemesanan.
        $stmt = mysqli_prepare($conn, 'DELETE dp FROM detail_pesanan dp JOIN pemesanan p ON p.pemesanan_id = dp.pemesanan_id WHERE p.member_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, 'DELETE FROM pemesanan WHERE member_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, 'DELETE FROM member WHERE member_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        set_flash('success', 'Member ' . $member['name'] . ' berhasil dihapus.');
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        set_flash('error', 'Gagal menghapus member: ' . $e->getMessage());
    }

    redirect(base_url('pages/membership/index.php'));
}

set_flash('error', 'Aksi tidak dikenali.');
redirect(base_url('pages/membership/index.php'));
