<?php
/**
 * Handler POST modul voucher.
 * action: approve_klaim | tolak_klaim | nonaktifkan
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'Kasir']);
csrf_verify();

$action    = $_POST['action'] ?? '';
$pegawai_id = (int) $_SESSION['pegawai_id'];

if ($action === 'approve_klaim') {
    $klaim_id   = (int) ($_POST['klaim_id'] ?? 0);
    $voucher_id = (int) ($_POST['voucher_id'] ?? 0);

    $stmt = mysqli_prepare($conn, 'SELECT k.klaim_id, k.status_validasi, m.name, m.member_status
        FROM klaim_review k JOIN member m ON m.member_id = k.member_id
        WHERE k.klaim_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $klaim_id);
    mysqli_stmt_execute($stmt);
    $klaim = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$klaim) {
        set_flash('error', 'Klaim tidak ditemukan.');
        redirect(base_url('pages/voucher/index.php?tab=klaim'));
    }

    if ($klaim['status_validasi'] !== 'pending') {
        set_flash('error', 'Klaim #' . $klaim_id . ' sudah diproses sebelumnya.');
        redirect(base_url('pages/voucher/index.php?tab=klaim'));
    }

    if ($klaim['member_status'] !== 'aktif') {
        set_flash('error', 'Member ' . $klaim['name'] . ' berstatus nonaktif, klaim tidak dapat disetujui.');
        redirect(base_url('pages/voucher/index.php?tab=klaim'));
    }

    // Voucher wajib valid dan belum kedaluwarsa.
    $stmt = mysqli_prepare($conn, "SELECT voucher_id, kode_voucher FROM voucher
        WHERE voucher_id = ? AND is_valid = 1 AND deleted_at IS NULL AND valid_until >= CURDATE() LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $voucher_id);
    mysqli_stmt_execute($stmt);
    $voucher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$voucher) {
        set_flash('error', 'Voucher yang dipilih tidak valid atau sudah kedaluwarsa.');
        redirect(base_url('pages/voucher/index.php?tab=klaim'));
    }

    $stmt = mysqli_prepare($conn, "UPDATE klaim_review SET status_validasi = 'approved', voucher_id = ?, pegawai_id = ? WHERE klaim_id = ? AND status_validasi = 'pending'");
    mysqli_stmt_bind_param($stmt, 'iii', $voucher_id, $pegawai_id, $klaim_id);
    mysqli_stmt_execute($stmt);
    $terpengaruh = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($terpengaruh === 1) {
        set_flash('success', 'Klaim #' . $klaim_id . ' disetujui. Voucher ' . $voucher['kode_voucher'] . ' diberikan kepada ' . $klaim['name'] . '.');
    } else {
        set_flash('error', 'Klaim gagal diperbarui. Muat ulang halaman lalu coba lagi.');
    }

    redirect(base_url('pages/voucher/index.php?tab=klaim&status=pending'));
}

if ($action === 'tolak_klaim') {
    $klaim_id = (int) ($_POST['klaim_id'] ?? 0);

    $stmt = mysqli_prepare($conn, "UPDATE klaim_review SET status_validasi = 'rejected', pegawai_id = ? WHERE klaim_id = ? AND status_validasi = 'pending'");
    mysqli_stmt_bind_param($stmt, 'ii', $pegawai_id, $klaim_id);
    mysqli_stmt_execute($stmt);
    $terpengaruh = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($terpengaruh === 1) {
        set_flash('success', 'Klaim #' . $klaim_id . ' ditolak.');
    } else {
        set_flash('error', 'Klaim tidak ditemukan atau sudah diproses.');
    }

    redirect(base_url('pages/voucher/index.php?tab=klaim&status=pending'));
}

if ($action === 'nonaktifkan') {
    $voucher_id = (int) ($_POST['voucher_id'] ?? 0);

    $stmt = mysqli_prepare($conn, 'SELECT kode_voucher, deleted_at FROM voucher WHERE voucher_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $voucher_id);
    mysqli_stmt_execute($stmt);
    $voucher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$voucher) {
        set_flash('error', 'Voucher tidak ditemukan.');
        redirect(base_url('pages/voucher/index.php?tab=voucher'));
    }

    if ($voucher['deleted_at'] !== null) {
        set_flash('info', 'Voucher ' . $voucher['kode_voucher'] . ' sudah nonaktif.');
        redirect(base_url('pages/voucher/index.php?tab=voucher'));
    }

    // Soft delete: isi deleted_at dan matikan is_valid.
    $stmt = mysqli_prepare($conn, 'UPDATE voucher SET deleted_at = CURRENT_TIMESTAMP, is_valid = 0 WHERE voucher_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $voucher_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    set_flash('success', 'Voucher ' . $voucher['kode_voucher'] . ' dinonaktifkan.');
    redirect(base_url('pages/voucher/index.php?tab=voucher'));
}

set_flash('error', 'Aksi tidak dikenali.');
redirect(base_url('pages/voucher/index.php'));
