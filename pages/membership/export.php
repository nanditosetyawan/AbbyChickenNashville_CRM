<?php
/**
 * Export data seluruh member ke format CSV (auto download).
 * Khusus role Admin.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

// Pastikan hanya role Admin yang dapat mengakses & mendownload
cek_akses(['Admin']);

$filename = 'data_member_abbychicken_' . date('Ymd_His') . '.csv';

// Header response HTTP untuk trigger auto download file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Kirim UTF-8 BOM agar Excel di Windows/Mac membaca karakter khusus/nama Indonesia tanpa corrupt
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Baris judul kolom CSV
fputcsv($output, [
    'ID Member',
    'Nama Lengkap',
    'Email',
    'Nomor Telepon',
    'Tanggal Bergabung',
    'Poin Loyalitas',
    'Status Akun',
    'Total Transaksi',
    'Total Belanja (Rp)'
]);

// Ambil seluruh data member dari database
$sql = "SELECT m.member_id,
               m.name,
               m.email,
               m.no_telp,
               m.tgl_join,
               m.total_poin,
               m.member_status,
               (SELECT COUNT(*) FROM pemesanan p WHERE p.member_id = m.member_id AND p.status_pesanan <> 'dibatalkan') AS total_transaksi,
               (SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pemesanan p WHERE p.member_id = m.member_id AND p.status_pesanan <> 'dibatalkan') AS total_belanja
        FROM member m
        ORDER BY m.member_id ASC";

$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['member_id'],
            $row['name'],
            $row['email'],
            $row['no_telp'],
            $row['tgl_join'],
            (int) $row['total_poin'],
            ucfirst($row['member_status']),
            (int) $row['total_transaksi'],
            number_format((float) $row['total_belanja'], 2, '.', '')
        ]);
    }
}

fclose($output);
exit;
