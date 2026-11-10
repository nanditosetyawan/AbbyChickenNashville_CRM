<?php
/**
 * Handler POST modul pesanan.
 * action: ubah_status
 *
 * Transisi yang diizinkan:
 *   menunggu -> diproses | dibatalkan
 *   diproses -> selesai  | dibatalkan
 * Status selesai/dibatalkan bersifat final.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'Kasir']);
csrf_verify();

/** 1 poin untuk setiap Rp 10.000 jumlah bayar. */
const POIN_PER_RUPIAH = 10000;

$action = $_POST['action'] ?? '';

if ($action !== 'ubah_status') {
    set_flash('error', 'Aksi tidak dikenali.');
    redirect(base_url('pages/orders/index.php'));
}

$id     = (int) ($_POST['pemesanan_id'] ?? 0);
$status = $_POST['status'] ?? '';
$kembali = $_POST['kembali'] ?? 'index';

if (!in_array($status, ['diproses', 'selesai', 'dibatalkan'], true)) {
    set_flash('error', 'Status tujuan tidak valid.');
    redirect(base_url('pages/orders/index.php'));
}

$stmt = mysqli_prepare($conn, 'SELECT pemesanan_id, member_id, status_pesanan, jumlah_bayar FROM pemesanan WHERE pemesanan_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$pesanan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$pesanan) {
    set_flash('error', 'Pesanan tidak ditemukan.');
    redirect(base_url('pages/orders/index.php'));
}

$asal = $pesanan['status_pesanan'];

$transisi_sah = [
    'menunggu' => ['diproses', 'dibatalkan'],
    'diproses' => ['selesai', 'dibatalkan'],
    'selesai'  => [],
    'dibatalkan' => [],
];

if (!in_array($status, $transisi_sah[$asal] ?? [], true)) {
    set_flash('error', 'Pesanan berstatus ' . $asal . ' tidak dapat diubah menjadi ' . $status . '.');
    redirect($kembali === 'detail'
        ? base_url('pages/orders/detail.php?id=' . $id)
        : base_url('pages/orders/index.php'));
}

mysqli_begin_transaction($conn);
try {
    $stmt = mysqli_prepare($conn, 'UPDATE pemesanan SET status_pesanan = ? WHERE pemesanan_id = ?');
    mysqli_stmt_bind_param($stmt, 'si', $status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Poin diberikan sekali saat pesanan pertama kali selesai.
    if ($status === 'selesai') {
        $poin = (int) floor(((float) $pesanan['jumlah_bayar']) / POIN_PER_RUPIAH);
        if ($poin > 0) {
            $stmt = mysqli_prepare($conn, 'UPDATE member SET total_poin = total_poin + ? WHERE member_id = ?');
            mysqli_stmt_bind_param($stmt, 'ii', $poin, $pesanan['member_id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    mysqli_commit($conn);

    $pesan = 'Status pesanan #' . $id . ' diubah menjadi ' . $status . '.';
    if ($status === 'selesai') {
        $poin = (int) floor(((float) $pesanan['jumlah_bayar']) / POIN_PER_RUPIAH);
        if ($poin > 0) {
            $pesan .= ' ' . $poin . ' poin ditambahkan ke member.';
        }
    }
    set_flash('success', $pesan);
} catch (Throwable $ex) {
    mysqli_rollback($conn);
    set_flash('error', 'Gagal mengubah status: ' . $ex->getMessage());
}

redirect($kembali === 'detail'
    ? base_url('pages/orders/detail.php?id=' . $id)
    : base_url('pages/orders/index.php?status=' . urlencode($_POST['kembali'] ?? 'aktif')));
