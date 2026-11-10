<?php
/**
 * Handler POST modul keluhan.
 * action: assign (Admin) | tutup (Admin, PIC pemilik tiket)
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'PIC Keluhan']);
csrf_verify();

$action     = $_POST['action'] ?? '';
$role       = $_SESSION['role'];
$pegawai_id = (int) $_SESSION['pegawai_id'];
$id         = (int) ($_POST['tiket_id'] ?? 0);
$kembali    = $_POST['kembali'] ?? 'index';

$tujuan = $kembali === 'detail'
    ? base_url('pages/complaints/detail.php?id=' . $id)
    : base_url('pages/complaints/index.php');

$stmt = mysqli_prepare($conn, 'SELECT tiket_id, status, pegawai_id FROM tiket WHERE tiket_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$tiket = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$tiket) {
    set_flash('error', 'Tiket tidak ditemukan.');
    redirect(base_url('pages/complaints/index.php'));
}

/* ------------------------------------------------------------- Assign */
if ($action === 'assign') {
    if ($role !== 'Admin') {
        set_flash('error', 'Hanya admin yang dapat meng-assign tiket.');
        redirect($tujuan);
    }

    if ($tiket['status'] !== 'open') {
        set_flash('error', 'Tiket berstatus ' . $tiket['status'] . ' tidak dapat di-assign ulang.');
        redirect($tujuan);
    }

    $pic_id = (int) ($_POST['pegawai_id'] ?? 0);

    $stmt = mysqli_prepare($conn, "SELECT pegawai_id, nama FROM pegawai WHERE pegawai_id = ? AND role = 'PIC Keluhan' LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $pic_id);
    mysqli_stmt_execute($stmt);
    $pic = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$pic) {
        set_flash('error', 'Pegawai yang dipilih bukan PIC Keluhan yang valid.');
        redirect($tujuan);
    }

    // Assign otomatis mengubah status menjadi in_progress (US-4.2).
    $stmt = mysqli_prepare($conn, "UPDATE tiket SET pegawai_id = ?, status = 'in_progress' WHERE tiket_id = ? AND status = 'open'");
    mysqli_stmt_bind_param($stmt, 'ii', $pic_id, $id);
    mysqli_stmt_execute($stmt);
    $terpengaruh = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($terpengaruh === 1) {
        set_flash('success', 'Tiket #' . $id . ' di-assign ke ' . $pic['nama'] . ' dan berstatus in progress.');
    } else {
        set_flash('error', 'Tiket gagal di-assign. Muat ulang halaman lalu coba lagi.');
    }

    redirect($tujuan);
}

/* -------------------------------------------------------------- Tutup */
if ($action === 'tutup') {
    // PIC hanya boleh menutup tiket miliknya sendiri.
    if ($role === 'PIC Keluhan' && (int) $tiket['pegawai_id'] !== $pegawai_id) {
        set_flash('error', 'Anda hanya dapat menutup tiket yang di-assign kepada Anda.');
        redirect($tujuan);
    }

    if ($tiket['status'] !== 'in_progress') {
        set_flash('error', 'Hanya tiket berstatus in progress yang dapat ditutup.');
        redirect($tujuan);
    }

    $stmt = mysqli_prepare($conn, "UPDATE tiket SET status = 'closed', closed_at = CURRENT_TIMESTAMP WHERE tiket_id = ? AND status = 'in_progress'");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $terpengaruh = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($terpengaruh === 1) {
        set_flash('success', 'Tiket #' . $id . ' ditutup. Pelanggan dapat dihubungi untuk konfirmasi penyelesaian.');
    } else {
        set_flash('error', 'Status tiket gagal diperbarui.');
    }

    redirect($tujuan);
}

set_flash('error', 'Aksi tidak dikenali.');
redirect(base_url('pages/complaints/index.php'));
