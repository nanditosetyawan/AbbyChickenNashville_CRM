<?php
/**
 * Helper umum: escaping, format, CSRF, flash message, badge, ikon.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Escape output HTML. */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Format angka menjadi Rupiah tanpa desimal. */
function rupiah($angka)
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

/** Format tanggal Indonesia: 04 Nov 2026. */
function tanggal_id($datetime, $dengan_jam = false)
{
    if (empty($datetime)) {
        return '-';
    }
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($datetime);
    $teks = date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    if ($dengan_jam) {
        $teks .= ', ' . date('H:i', $ts);
    }
    return $teks;
}

/** Selisih waktu dalam bentuk ringkas: "12 menit lalu". */
function waktu_lalu($datetime)
{
    $selisih = time() - strtotime($datetime);
    if ($selisih < 60) {
        return 'baru saja';
    }
    if ($selisih < 3600) {
        return floor($selisih / 60) . ' menit lalu';
    }
    if ($selisih < 86400) {
        return floor($selisih / 3600) . ' jam lalu';
    }
    if ($selisih < 604800) {
        return floor($selisih / 86400) . ' hari lalu';
    }
    return tanggal_id($datetime);
}

/** Redirect lalu hentikan eksekusi. */
function redirect($tujuan)
{
    header('Location: ' . $tujuan);
    exit;
}

/* ---------------------------------------------------------------------
 * CSRF
 * ------------------------------------------------------------------- */

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verifikasi token CSRF untuk request POST.
 * Menghentikan request dengan pesan bila token tidak cocok.
 */
function csrf_verify()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        die('Sesi form sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}

/* ---------------------------------------------------------------------
 * Flash message
 * ------------------------------------------------------------------- */

function set_flash($tipe, $pesan)
{
    $_SESSION['flash'][] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function ambil_flash()
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

/* ---------------------------------------------------------------------
 * Badge status
 * ------------------------------------------------------------------- */

function badge_pesanan($status)
{
    $peta = [
        'menunggu'   => ['Menunggu', 'badge--warning'],
        'diproses'   => ['Diproses', 'badge--info'],
        'selesai'    => ['Selesai', 'badge--success'],
        'dibatalkan' => ['Dibatalkan', 'badge--danger'],
    ];
    [$label, $kelas] = $peta[$status] ?? [ucfirst((string) $status), 'badge--neutral'];
    return '<span class="badge ' . $kelas . '">' . e($label) . '</span>';
}

function badge_tiket($status)
{
    $peta = [
        'open'        => ['Open', 'badge--danger'],
        'in_progress' => ['In Progress', 'badge--warning'],
        'closed'      => ['Closed', 'badge--success'],
    ];
    [$label, $kelas] = $peta[$status] ?? [ucfirst((string) $status), 'badge--neutral'];
    return '<span class="badge ' . $kelas . '">' . e($label) . '</span>';
}

function badge_klaim($status)
{
    $peta = [
        'pending'  => ['Pending', 'badge--warning'],
        'approved' => ['Approved', 'badge--success'],
        'rejected' => ['Rejected', 'badge--danger'],
    ];
    [$label, $kelas] = $peta[$status] ?? [ucfirst((string) $status), 'badge--neutral'];
    return '<span class="badge ' . $kelas . '">' . e($label) . '</span>';
}

function badge_member($status)
{
    if ($status === 'aktif') {
        return '<span class="badge badge--success">Aktif</span>';
    }
    return '<span class="badge badge--neutral">Nonaktif</span>';
}

function badge_stok($status)
{
    if ($status === 'tersedia') {
        return '<span class="badge badge--success">Tersedia</span>';
    }
    return '<span class="badge badge--danger">Habis</span>';
}

/* ---------------------------------------------------------------------
 * Ikon SVG (satu set, stroke 1.6, ukuran 18px)
 * ------------------------------------------------------------------- */

function icon($nama, $ukuran = 18)
{
    $path = [
        'dashboard'  => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'member'     => '<circle cx="9" cy="8" r="3.2"/><path d="M3.2 20c0-3.2 2.6-5.2 5.8-5.2s5.8 2 5.8 5.2"/><path d="M16.5 4.5a3 3 0 0 1 0 5.6"/><path d="M18 14.4c1.7.6 2.8 1.9 2.8 3.6"/>',
        'order'      => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6"/><path d="M9 12h6"/>',
        'voucher'    => '<path d="M3 9V6.5A1.5 1.5 0 0 1 4.5 5h15A1.5 1.5 0 0 1 21 6.5V9a3 3 0 0 0 0 6v2.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.5V15a3 3 0 0 0 0-6z"/><path d="M12 8v1"/><path d="M12 12v1"/><path d="M12 16v1"/>',
        'complaint'  => '<path d="M20 15a2 2 0 0 1-2 2H8l-4 3V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2z"/><path d="M8.5 9h7"/><path d="M8.5 12.5h4"/>',
        'menu'       => '<path d="M4 11h16a8 8 0 0 1-16 0z"/><path d="M9 7.5c0-1 1-1.5 1-2.5"/><path d="M13 7.5c0-1 1-1.5 1-2.5"/><path d="M3 20h18"/>',
        'bell'       => '<path d="M18 8a6 6 0 1 0-12 0c0 5-2 6-2 6h16s-2-1-2-6"/><path d="M10.3 19a2 2 0 0 0 3.4 0"/>',
        'pegawai'    => '<circle cx="12" cy="7.5" r="3.5"/><path d="M5 21v-1.5c0-2.8 3.1-4.5 7-4.5s7 1.7 7 4.5V21"/>',
        'logout'     => '<path d="M15 4h3.5A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5H15"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h9"/>',
        'search'     => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>',
        'plus'       => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'edit'       => '<path d="M4 20h4l10-10a2.5 2.5 0 0 0-3.5-3.5L4.5 16.5z"/><path d="M14.5 6.5L17.5 9.5"/>',
        'trash'      => '<path d="M4 7h16"/><path d="M9.5 7V4.8h5V7"/><path d="M6 7l1 13h10l1-13"/><path d="M10.5 11v5"/><path d="M13.5 11v5"/>',
        'eye'        => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>',
        'check'      => '<path d="M4.5 12.5l5 5L19.5 7"/>',
        'close'      => '<path d="M6 6l12 12"/><path d="M18 6L6 18"/>',
        'arrow-left' => '<path d="M11 5l-7 7 7 7"/><path d="M4 12h16"/>',
        'chart'      => '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M21 20H3"/>',
        'clock'      => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'link'       => '<path d="M10 13.5a4 4 0 0 0 5.7 0l2.8-2.8a4 4 0 0 0-5.7-5.7l-1.4 1.4"/><path d="M14 10.5a4 4 0 0 0-5.7 0l-2.8 2.8a4 4 0 0 0 5.7 5.7l1.4-1.4"/>',
        'download'   => '<path d="M12 4v11"/><path d="M7.5 10.5L12 15l4.5-4.5"/><path d="M5 20h14"/>',
        'lock'       => '<rect x="4.5" y="10" width="15" height="10.5" rx="2"/><path d="M8 10V7.5a4 4 0 0 1 8 0V10"/>',
        'mail'       => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="M3.5 7l8.5 6 8.5-6"/>',
        'filter'     => '<path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/>',
        'menu-bars'  => '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>',
        'rice'       => '<path d="M3 11c0 4.97 4.03 9 9 9s9-4.03 9-9H3z"/><path d="M7 20h10"/><path d="M8 8c0-1.5 1-2 1-3"/><path d="M12 8c0-1.5 1-2 1-3"/><path d="M16 8c0-1.5 1-2 1-3"/>',
        'send'       => '<path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/>',
        'copy'       => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'book'       => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15z"/><path d="M9 7h6"/><path d="M9 11h4"/>',
        'camera'     => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
        'image'      => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
    ];
    $isi = $path[$nama] ?? $path['dashboard'];
    return '<svg class="icon" width="' . (int) $ukuran . '" height="' . (int) $ukuran . '" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $isi . '</svg>';
}

/**
 * Potong teks panjang dengan aman untuk tabel.
 */
function potong($teks, $batas = 90)
{
    $teks = trim((string) $teks);
    if (mb_strlen($teks) <= $batas) {
        return $teks;
    }
    return mb_substr($teks, 0, $batas - 1) . '…';
}

/** URL absolut aplikasi (dipakai untuk redirect antar folder). */
function base_url($path = '')
{
    $root = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    // Naik ke root proyek bila halaman berada di dalam /pages/ atau subfolder lainnya
    if (preg_match('#^(.*?)/(?:pages|api)(?:/.*)?$#', $root, $m)) {
        $root = $m[1];
    }
    return $root . '/' . ltrim($path, '/');
}

/** Ambil nilai pengaturan sistem dari database. */
function get_setting($key, $default = '')
{
    global $conn;
    if (!$conn) {
        return $default;
    }
    $stmt = mysqli_prepare($conn, 'SELECT setting_val FROM pengaturan WHERE setting_key = ? LIMIT 1');
    if (!$stmt) {
        return $default;
    }
    mysqli_stmt_bind_param($stmt, 's', $key);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row['setting_val'] ?? $default;
}

/** Simpan atau perbarui nilai pengaturan sistem di database. */
function set_setting($key, $val)
{
    global $conn;
    if (!$conn) {
        return false;
    }
    $stmt = mysqli_prepare($conn, 'INSERT INTO pengaturan (setting_key, setting_val) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val)');
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ss', $key, $val);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

/**
 * Ambil informasi lengkap status dan timer refill nasi.
 * Jika status sedang 'habis' dan waktu timer telah terlewati, status otomatis diubah ke 'ready' (auto-restock).
 *
 * @return array{
 *   status: string,
 *   is_ready: bool,
 *   timer_end: string,
 *   timer_ts: int,
 *   seconds_left: int,
 *   has_timer: bool
 * }
 */
function get_refill_nasi_info()
{
    global $conn;
    $status    = get_setting('refill_nasi', 'ready');
    $timer_end = get_setting('refill_nasi_timer', '');
    $now       = time();
    $timer_ts  = (!empty($timer_end)) ? strtotime($timer_end) : 0;

    // Otomatis restok jika sedang habis dan waktu timer sudah lewat
    if (($status === 'habis' || $status === 'tidak tersedia') && $timer_ts > 0 && $now >= $timer_ts) {
        $status    = 'ready';
        $timer_end = '';
        $timer_ts  = 0;
        set_setting('refill_nasi', 'ready');
        set_setting('refill_nasi_timer', '');
    }

    $is_ready     = ($status === 'ready' || $status === 'tersedia');
    $seconds_left = ($timer_ts > $now && !$is_ready) ? ($timer_ts - $now) : 0;
    $has_timer    = ($seconds_left > 0 && !$is_ready);

    return [
        'status'       => $status,
        'is_ready'     => $is_ready,
        'timer_end'    => $timer_end,
        'timer_ts'     => $timer_ts,
        'seconds_left' => $seconds_left,
        'has_timer'    => $has_timer,
    ];
}

/**
 * Ambil daftar notifikasi promo pribadi milik member beserta detail voucher (jika ada).
 *
 * @return array{items: array, unread: int}
 */
function ambil_notifikasi_member($conn, $member_id)
{
    $hasil = ['items' => [], 'unread' => 0];
    if (!$conn || $member_id <= 0) {
        return $hasil;
    }
    $sql = "SELECT n.*, v.potongan_harga, v.valid_until, v.is_valid, v.deleted_at, pg.nama AS nama_pengirim
            FROM notifikasi_promo n
            LEFT JOIN voucher v ON v.voucher_id = n.voucher_id
            LEFT JOIN pegawai pg ON pg.pegawai_id = n.pegawai_id
            WHERE n.member_id = ?
            ORDER BY n.is_read ASC, n.created_at DESC, n.notif_id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return $hasil;
    }
    mysqli_stmt_bind_param($stmt, 'i', $member_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        if ((int) $row['is_read'] === 0) {
            $hasil['unread']++;
        }
        $hasil['items'][] = $row;
    }
    mysqli_stmt_close($stmt);
    return $hasil;
}

/**
 * Ambil daftar pengumuman promo aktif untuk member.
 */
function ambil_pengumuman_aktif($conn)
{
    $list = [];
    if (!$conn) {
        return $list;
    }
    $res = mysqli_query($conn, "SELECT p.pengumuman_id, p.judul, p.isi, p.created_at, pg.nama AS penulis
        FROM pengumuman p
        LEFT JOIN pegawai pg ON pg.pegawai_id = p.pegawai_id
        WHERE p.aktif = 1
        ORDER BY p.created_at DESC");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $list[] = $row;
        }
    }
    return $list;
}

/**
 * Parse input nominal Rupiah ke integer (nilai Rupiah asli).
 * Mendukung angka polos ("10000", "12500"), pemisah ribuan ("10.000", "12.500", "12,500"),
 * awalan "Rp", akhiran ",00"/".00", serta singkatan "10k" / "12.5k" / "12,5rb".
 * Mengembalikan null bila string kosong atau formatnya tidak valid/ambigu.
 */
function parse_nominal_rupiah($raw)
{
    $s = strtolower(trim((string) $raw));
    if ($s === '') {
        return null;
    }

    $s = preg_replace('/^(?:rp\.?|idr)\s*/i', '', $s);
    $s = preg_replace('/\s+/', '', $s);
    if ($s === '') {
        return null;
    }

    // Singkatan: 10k, 10K, 12.5k, 12,5k, 10rb, 12.5ribu
    if (preg_match('/^(\d+(?:[.,]\d{1,2})?)(?:k|rb|ribu)$/', $s, $m)) {
        $angka = (float) str_replace(',', '.', $m[1]);
        return (int) round($angka * 1000);
    }

    // Format ribuan bertitik (10.000, 12.500, 100.000, opsional ,00)
    if (preg_match('/^\d{1,3}(?:\.\d{3})+(?:,00)?$/', $s)) {
        $bersih = preg_replace('/,00$/', '', $s);
        return (int) str_replace('.', '', $bersih);
    }

    // Format ribuan berkoma (10,000, 12,500, opsional .00)
    if (preg_match('/^\d{1,3}(?:,\d{3})+(?:\.00)?$/', $s)) {
        $bersih = preg_replace('/\.00$/', '', $s);
        return (int) str_replace(',', '', $bersih);
    }

    // Angka polos (10000, 12500, opsional .00 / ,00)
    if (preg_match('/^(\d+)(?:[.,]00)?$/', $s, $m)) {
        return (int) $m[1];
    }

    return null;
}

/**
 * Sensor nomor telepon dengan mempertahankan digit awal (default 5 digit)
 * dan mengganti sisa karakter dengan tanda bintang (*).
 * Contoh: "081356789012" -> "08135*******"
 */
function sensor_telepon($telepon, $tampil_awal = 5)
{
    $tel = trim((string) $telepon);
    if ($tel === '') {
        return '';
    }

    $panjang     = mb_strlen($tel);
    $tampil_awal = max(0, (int) $tampil_awal);

    if ($panjang <= $tampil_awal) {
        return str_repeat('*', $panjang);
    }

    return mb_substr($tel, 0, $tampil_awal) . str_repeat('*', $panjang - $tampil_awal);
}

/**
 * Format tampilan nomor telepon berdasarkan role sesi aktif.
 * Seluruh role selain Member/User melihat nomor telepon yang disensor (5 digit awal).
 */
function tampil_telepon($telepon, $role = null)
{
    $tel = trim((string) $telepon);
    if ($tel === '') {
        return '';
    }

    $role_aktif = $role !== null ? (string) $role : (string) ($_SESSION['role'] ?? '');
    if (in_array($role_aktif, ['User', 'Member'], true)) {
        return $tel;
    }

    return sensor_telepon($tel, 5);
}

/**
 * Format nomor telepon Indonesia ke format WhatsApp (awalan 62).
 * Contoh: "081345678903" -> "6281345678903"
 */
function format_wa($no_telp)
{
    $tel = preg_replace('/\D/', '', trim((string) $no_telp));
    if ($tel === '') {
        return '';
    }
    if (substr($tel, 0, 1) === '0') {
        $tel = '62' . substr($tel, 1);
    }
    return $tel;
}
