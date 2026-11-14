<?php
/**
 * Control Promosi: daftar member yang tidak memesan dalam 30 hari terakhir
 * beserta kontrol pengiriman notifikasi promo + kode voucher ke akun member.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin']);

$tab = $_GET['tab'] ?? 'member';
if (!in_array($tab, ['member', 'riwayat'], true)) {
    $tab = 'member';
}

$cari   = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'semua';
if (!in_array($status, ['semua', 'aktif', 'nonaktif'], true)) {
    $status = 'semua';
}

/* Ambil daftar voucher aktif yang valid untuk pilihan & validasi */
$voucher_tersedia = [];
$voucher_lookup   = [];
$q_v = mysqli_query($conn, "SELECT voucher_id, kode_voucher, potongan_harga, valid_until
    FROM voucher
    WHERE is_valid = 1 AND deleted_at IS NULL AND valid_until >= CURDATE()
    ORDER BY potongan_harga DESC, kode_voucher ASC");
if ($q_v) {
    while ($v = mysqli_fetch_assoc($q_v)) {
        $voucher_tersedia[] = $v;
        $voucher_lookup[strtoupper($v['kode_voucher'])] = [
            'voucher_id'     => (int) $v['voucher_id'],
            'kode_voucher'   => $v['kode_voucher'],
            'potongan_harga' => (float) $v['potongan_harga'],
            'potongan_label' => rupiah($v['potongan_harga']),
            'valid_until'    => tanggal_id($v['valid_until']),
        ];
    }
}

/* Query dasar member tidak pesan >= 30 hari */
$dormant_subquery = "SELECT m.member_id, m.name, m.email, m.no_telp, m.tgl_join, m.total_poin, m.member_status,
        MAX(CASE WHEN p.status_pesanan <> 'dibatalkan' THEN p.waktu_pemesanan ELSE NULL END) AS pesanan_terakhir,
        COALESCE(SUM(CASE WHEN p.status_pesanan <> 'dibatalkan' THEN 1 ELSE 0 END), 0) AS total_pesanan_valid,
        COALESCE(SUM(CASE WHEN p.status_pesanan = 'dibatalkan' THEN 1 ELSE 0 END), 0) AS total_pesanan_batal,
        DATEDIFF(
            CURDATE(),
            COALESCE(
                DATE(MAX(CASE WHEN p.status_pesanan <> 'dibatalkan' THEN p.waktu_pemesanan ELSE NULL END)),
                m.tgl_join
            )
        ) AS hari_tidak_pesan,
        (SELECT COUNT(*) FROM notifikasi_promo np WHERE np.member_id = m.member_id) AS total_promo_dikirim,
        (SELECT np.created_at FROM notifikasi_promo np WHERE np.member_id = m.member_id ORDER BY np.created_at DESC, np.notif_id DESC LIMIT 1) AS promo_terakhir_at,
        (SELECT np.kode_voucher FROM notifikasi_promo np WHERE np.member_id = m.member_id ORDER BY np.created_at DESC, np.notif_id DESC LIMIT 1) AS promo_terakhir_kode,
        (SELECT np.is_read FROM notifikasi_promo np WHERE np.member_id = m.member_id ORDER BY np.created_at DESC, np.notif_id DESC LIMIT 1) AS promo_terakhir_dibaca
    FROM member m
    LEFT JOIN pemesanan p ON p.member_id = m.member_id
    GROUP BY m.member_id, m.name, m.email, m.no_telp, m.tgl_join, m.total_poin, m.member_status";

$error = [];
$form_reopen = false;
$form_data = [
    'target_type'      => 'single',
    'member_id'        => 0,
    'member_nama'      => '',
    'lama_tidak_pesan' => '',
    'kode_voucher'     => '',
    'potongan_baru'    => '',
    'valid_until_baru' => date('Y-m-d', strtotime('+30 days')),
    'judul'            => 'Promo Spesial Untukmu di AbbyChicken!',
    'pesan'            => 'Sudah lama tidak pesan di AbbyChicken Nashville! Yuk nikmati menu favoritmu lagi dengan promo spesial khusus untukmu.',
];

/* --------------------------------------------------- POST Handler */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'kirim_promo') {
        $form_data['target_type']      = ($_POST['target_type'] ?? 'single') === 'all' ? 'all' : 'single';
        $form_data['member_id']        = (int) ($_POST['member_id'] ?? 0);
        $form_data['kode_voucher']     = strtoupper(trim($_POST['kode_voucher'] ?? ''));
        $raw_potongan_baru             = trim((string) ($_POST['potongan_baru'] ?? ''));
        $parsed_potongan_baru          = $raw_potongan_baru !== '' ? parse_nominal_rupiah($raw_potongan_baru) : null;
        $form_data['potongan_baru']    = ($parsed_potongan_baru !== null && $parsed_potongan_baru > 0) ? (float) $parsed_potongan_baru : 0.0;
        $form_data['potongan_baru_raw'] = $raw_potongan_baru;
        $form_data['valid_until_baru'] = trim($_POST['valid_until_baru'] ?? date('Y-m-d', strtotime('+30 days')));
        $form_data['judul']            = trim($_POST['judul'] ?? '');
        $form_data['pesan']            = trim($_POST['pesan'] ?? '');

        if ($form_data['judul'] === '' || mb_strlen($form_data['judul']) < 3) {
            $error['judul'] = 'Judul promosi minimal 3 karakter.';
        } elseif (mb_strlen($form_data['judul']) > 150) {
            $error['judul'] = 'Judul promosi maksimal 150 karakter.';
        }

        if (mb_strlen($form_data['pesan']) < 10) {
            $error['pesan'] = 'Pesan promosi minimal 10 karakter agar jelas bagi member.';
        } elseif (mb_strlen($form_data['pesan']) > 1000) {
            $error['pesan'] = 'Pesan promosi maksimal 1000 karakter.';
        }

        $voucher_id         = null;
        $kode_voucher_param = null;
        $create_new_voucher = false;

        if ($form_data['kode_voucher'] !== '') {
            if (!preg_match('/^[A-Z0-9_-]{3,50}$/', $form_data['kode_voucher'])) {
                $error['kode_voucher'] = 'Kode voucher harus 3–50 karakter (huruf, angka, - atau _).';
            } else {
                $stmt_v = mysqli_prepare($conn, 'SELECT voucher_id, kode_voucher, is_valid, deleted_at, valid_until FROM voucher WHERE kode_voucher = ? LIMIT 1');
                mysqli_stmt_bind_param($stmt_v, 's', $form_data['kode_voucher']);
                mysqli_stmt_execute($stmt_v);
                $v_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_v));
                mysqli_stmt_close($stmt_v);

                if ($v_row) {
                    $expired = strtotime($v_row['valid_until']) < strtotime(date('Y-m-d'));
                    $inactive = $v_row['deleted_at'] !== null || !(int) $v_row['is_valid'];
                    if ($expired || $inactive) {
                        $error['kode_voucher'] = 'Voucher ' . $v_row['kode_voucher'] . ' sudah kedaluwarsa atau nonaktif. Pilih voucher aktif atau gunakan kode baru.';
                    } else {
                        $voucher_id         = (int) $v_row['voucher_id'];
                        $kode_voucher_param = $v_row['kode_voucher'];
                    }
                } else {
                    $kode_voucher_param = $form_data['kode_voucher'];
                    if ($raw_potongan_baru !== '') {
                        if ($parsed_potongan_baru === null) {
                            $error['potongan_baru'] = 'Format nominal tidak valid. Contoh: 10000, 12.500, atau 10k.';
                        } elseif ($parsed_potongan_baru < 100) {
                            $error['potongan_baru'] = 'Potongan harga minimal Rp 100.';
                        } elseif ($parsed_potongan_baru > 500000) {
                            $error['potongan_baru'] = 'Potongan harga maksimal Rp 500.000.';
                        } elseif ($form_data['valid_until_baru'] === '' || strtotime($form_data['valid_until_baru']) < strtotime(date('Y-m-d'))) {
                            $error['valid_until_baru'] = 'Tanggal berlaku voucher baru minimal hari ini.';
                        } else {
                            $create_new_voucher = true;
                        }
                    }
                }
            }
        }

        $target_members = [];
        if ($form_data['target_type'] === 'all') {
            $q_all = mysqli_query($conn, "SELECT d.member_id, d.name, d.no_telp FROM ({$dormant_subquery}) AS d WHERE d.hari_tidak_pesan >= 30");
            if ($q_all) {
                while ($tm = mysqli_fetch_assoc($q_all)) {
                    $target_members[] = $tm;
                }
            }
            if (empty($target_members)) {
                $error['umum'] = 'Tidak ada member inaktif 30 hari yang dapat dikirimi promosi.';
            }
            $form_data['member_nama'] = 'Semua Member Tidak Pesan >= 30 Hari (' . count($target_members) . ' member)';
        } else {
            $stmt_m = mysqli_prepare($conn, 'SELECT member_id, name, no_telp FROM member WHERE member_id = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt_m, 'i', $form_data['member_id']);
            mysqli_stmt_execute($stmt_m);
            $tm = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_m));
            mysqli_stmt_close($stmt_m);

            if (!$tm) {
                $error['umum'] = 'Member tujuan tidak ditemukan.';
            } else {
                $target_members[] = $tm;
                $form_data['member_nama'] = $tm['name'];
            }
        }

        if (empty($error)) {
            $pegawai_id = (int) ($_SESSION['pegawai_id'] ?? 0);
            mysqli_begin_transaction($conn);
            try {
                if ($create_new_voucher) {
                    $stmt_cv = mysqli_prepare($conn, 'INSERT INTO voucher (kode_voucher, is_valid, potongan_harga, valid_until) VALUES (?, 1, ?, ?)');
                    mysqli_stmt_bind_param($stmt_cv, 'sds', $kode_voucher_param, $form_data['potongan_baru'], $form_data['valid_until_baru']);
                    mysqli_stmt_execute($stmt_cv);
                    $voucher_id = (int) mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt_cv);
                }

                $stmt_ins = mysqli_prepare($conn, 'INSERT INTO notifikasi_promo (member_id, pegawai_id, voucher_id, kode_voucher, judul, pesan, is_read) VALUES (?, ?, ?, ?, ?, ?, 0)');
                foreach ($target_members as $tm) {
                    $mid = (int) $tm['member_id'];
                    mysqli_stmt_bind_param($stmt_ins, 'iiisss', $mid, $pegawai_id, $voucher_id, $kode_voucher_param, $form_data['judul'], $form_data['pesan']);
                    mysqli_stmt_execute($stmt_ins);
                }
                mysqli_stmt_close($stmt_ins);

                mysqli_commit($conn);

                $info_voucher = $kode_voucher_param ? ' beserta kode voucher ' . $kode_voucher_param : '';
                if (count($target_members) === 1) {
                    set_flash('success', 'Notifikasi promosi berhasil dikirim ke ' . $target_members[0]['name'] . $info_voucher . '.');
                } else {
                    set_flash('success', 'Notifikasi promosi berhasil dikirim ke ' . count($target_members) . ' member' . $info_voucher . '.');
                }

                // Siapkan data auto-open WhatsApp & animasi pesawat terbang untuk member tunggal
                if (count($target_members) === 1) {
                    $tm_target = $target_members[0];
                    $raw_telp  = trim((string) ($tm_target['no_telp'] ?? ''));
                    if ($raw_telp === '') {
                        $raw_telp = trim((string) ($_POST['member_telp'] ?? ''));
                    }
                    $wa_number = format_wa($raw_telp);
                    if ($wa_number !== '') {
                        $wa_text = "Halo {$tm_target['name']}! 👋\n\n";
                        if ($form_data['judul'] !== '') {
                            $wa_text .= "*{$form_data['judul']}*\n";
                        }
                        $wa_text .= "{$form_data['pesan']}\n\n";
                        if (!empty($kode_voucher_param)) {
                            $wa_text .= "🎁 Gunakan kode voucher: *{$kode_voucher_param}* untuk mendapatkan potongan promo spesial!\n\n";
                        }
                        $wa_text .= "Pesan sekarang di AbbyChicken Nashville! 🍗";

                        $_SESSION['wa_launch'] = [
                            'url'         => 'https://wa.me/' . $wa_number . '?text=' . rawurlencode($wa_text),
                            'nama'        => $tm_target['name'],
                            'telp_sensor' => sensor_telepon($raw_telp, 5),
                            'voucher'     => $kode_voucher_param ?: '-',
                        ];
                    }
                }

                redirect(base_url('pages/promo/control.php'));
            } catch (Throwable $ex) {
                mysqli_rollback($conn);
                $error['umum'] = 'Gagal mengirim notifikasi promosi: ' . $ex->getMessage();
                $form_reopen = true;
            }
        } else {
            $form_reopen = true;
        }
    }

    if ($action === 'hapus_notif') {
        $notif_id = (int) ($_POST['notif_id'] ?? 0);
        $stmt_del = mysqli_prepare($conn, 'DELETE FROM notifikasi_promo WHERE notif_id = ?');
        mysqli_stmt_bind_param($stmt_del, 'i', $notif_id);
        mysqli_stmt_execute($stmt_del);
        $affected = mysqli_stmt_affected_rows($stmt_del);
        mysqli_stmt_close($stmt_del);

        set_flash($affected === 1 ? 'success' : 'error', $affected === 1 ? 'Riwayat notifikasi promo dihapus.' : 'Notifikasi tidak ditemukan.');
        redirect(base_url('pages/promo/control.php?tab=riwayat'));
    }
}

/* --------------------------------------------------- Data KPI & Daftar */
$kpi_stat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) AS total_dormant,
    SUM(CASE WHEN d.total_promo_dikirim = 0 THEN 1 ELSE 0 END) AS belum_dikirim
    FROM ({$dormant_subquery}) AS d
    WHERE d.hari_tidak_pesan >= 30")) ?: ['total_dormant' => 0, 'belum_dikirim' => 0];

$jumlah_dormant      = (int) ($kpi_stat['total_dormant'] ?? 0);
$belum_dikirim_promo = (int) ($kpi_stat['belum_dikirim'] ?? 0);
$total_member_aktif  = (int) (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM member WHERE member_status = 'aktif'"))['n'] ?? 0);

$notif_stat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) AS total_terkirim,
    SUM(is_read = 1) AS total_dibaca
    FROM notifikasi_promo")) ?: ['total_terkirim' => 0, 'total_dibaca' => 0];
$total_notif_terkirim = (int) ($notif_stat['total_terkirim'] ?? 0);
$total_notif_dibaca   = (int) ($notif_stat['total_dibaca'] ?? 0);

/* Filter Daftar Member >= 30 Hari */
$sql_list = "SELECT * FROM ({$dormant_subquery}) AS d WHERE d.hari_tidak_pesan >= 30";
$params = [];
$types  = '';

if ($cari !== '') {
    $sql_list .= ' AND (d.name LIKE ? OR d.email LIKE ? OR d.no_telp LIKE ?)';
    $like = '%' . $cari . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($status !== 'semua') {
    $sql_list .= ' AND d.member_status = ?';
    $params[] = $status;
    $types .= 's';
}

$sql_list .= ' ORDER BY d.hari_tidak_pesan DESC, d.name ASC';

$stmt_list = mysqli_prepare($conn, $sql_list);
if ($params) {
    mysqli_stmt_bind_param($stmt_list, $types, ...$params);
}
mysqli_stmt_execute($stmt_list);
$dormant_res = mysqli_stmt_get_result($stmt_list);
$dormant_members = [];
while ($row = mysqli_fetch_assoc($dormant_res)) {
    $dormant_members[] = $row;
}
mysqli_stmt_close($stmt_list);

/* Filter Riwayat Pengiriman Notifikasi Promo */
$riwayat_notif = [];
if ($tab === 'riwayat') {
    $sql_riw = "SELECT n.*, m.name AS nama_member, m.email AS email_member, pg.nama AS nama_pengirim, v.potongan_harga
        FROM notifikasi_promo n
        JOIN member m ON m.member_id = n.member_id
        LEFT JOIN pegawai pg ON pg.pegawai_id = n.pegawai_id
        LEFT JOIN voucher v ON v.voucher_id = n.voucher_id
        WHERE 1=1";
    $r_params = [];
    $r_types  = '';
    if ($cari !== '') {
        $sql_riw .= ' AND (m.name LIKE ? OR m.email LIKE ? OR n.judul LIKE ? OR n.kode_voucher LIKE ?)';
        $like = '%' . $cari . '%';
        $r_params = [$like, $like, $like, $like];
        $r_types  = 'ssss';
    }
    $sql_riw .= ' ORDER BY n.created_at DESC, n.notif_id DESC';
    $stmt_riw = mysqli_prepare($conn, $sql_riw);
    if ($r_params) {
        mysqli_stmt_bind_param($stmt_riw, $r_types, ...$r_params);
    }
    mysqli_stmt_execute($stmt_riw);
    $res_riw = mysqli_stmt_get_result($stmt_riw);
    while ($rn = mysqli_fetch_assoc($res_riw)) {
        $riwayat_notif[] = $rn;
    }
    mysqli_stmt_close($stmt_riw);
}

$page_title    = 'Control promosi';
$page_subtitle = 'Pantau member yang belum memesan dalam 30 hari terakhir dan kirim notifikasi promo beserta kode voucher.';
$active_menu   = 'control_promo';
$page_actions  = '<a class="btn btn--secondary" href="' . e(base_url('pages/voucher/tambah.php')) . '">' . icon('plus', 16) . ' Buat voucher</a>';
if ($tab === 'member' && count($dormant_members) > 0) {
    $page_actions .= '<button class="btn btn--primary" type="button" id="btnKirimSemuaPromo"
        data-modal-open="#modalKirimPromo"
        data-target-type="all"
        data-member-id="0"
        data-member-nama="Semua Member Tidak Pesan ≥ 30 Hari (' . count($dormant_members) . ' member)"
        data-lama-tidak-pesan="≥ 30 hari tidak memesan"
        data-member-telp="">'
        . icon('send', 16) . ' Kirim ke semua (' . count($dormant_members) . ')</button>';
}

require_once __DIR__ . '/../../includes/header.php';

$wa_launch = $_SESSION['wa_launch'] ?? null;
unset($_SESSION['wa_launch']);
?>

<?php if (!empty($wa_launch)): ?>
<div class="wa-flight-overlay is-active" id="waFlightOverlay" role="dialog" aria-modal="true" aria-labelledby="waFlightTitle">
  <div class="wa-flight-stage">
    <svg class="wa-flight-trail" viewBox="0 0 240 90" fill="none">
      <path d="M10 75 Q 80 65, 140 40 T 230 15" stroke="rgba(255,255,255,0.45)" stroke-width="2.5" stroke-dasharray="6 6" stroke-linecap="round"/>
    </svg>
    <div class="wa-airplane-glider">
      <svg viewBox="0 0 100 100" width="76" height="76" style="overflow:visible;">
        <defs>
          <linearGradient id="waPlaneBody" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#FFFFFF"/>
            <stop offset="100%" stop-color="#E2E8F0"/>
          </linearGradient>
          <linearGradient id="waPlaneWing" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#FF6B35"/>
            <stop offset="100%" stop-color="#E55A2B"/>
          </linearGradient>
          <linearGradient id="waPlaneShade" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#CBD5E1"/>
            <stop offset="100%" stop-color="#94A3B8"/>
          </linearGradient>
        </defs>
        <polygon points="92,8 12,56 46,66" fill="url(#waPlaneBody)"/>
        <polygon points="92,8 46,66 54,86 64,68" fill="url(#waPlaneShade)"/>
        <polygon points="92,8 64,68 88,44" fill="url(#waPlaneWing)"/>
      </svg>
    </div>
  </div>

  <div class="wa-flight-card">
    <div class="wa-flight-card__title" id="waFlightTitle">
      <?= icon('send', 20) ?> Promosi Terkirim!
    </div>
    <p class="wa-flight-card__sub">
      Notifikasi promosi berhasil disimpan di sistem. Menghubungkan ke kontak WhatsApp member…
    </p>

    <div class="wa-flight-card__pill">
      <?= icon('member', 14) ?> <?= e($wa_launch['nama']) ?> &middot; <?= e($wa_launch['telp_sensor']) ?>
      <?php if ($wa_launch['voucher'] !== '-'): ?>
        &middot; <strong><?= e($wa_launch['voucher']) ?></strong>
      <?php endif; ?>
    </div>

    <div class="wa-flight-progress-bar">
      <div class="wa-flight-progress-fill" id="waProgressFill"></div>
    </div>

    <div class="wa-flight-card__timer" id="waFlightTimer">
      <?= icon('clock', 14) ?> Membuka WhatsApp dalam <strong id="waCountdownSec">2</strong> detik…
    </div>

    <div class="wa-flight-card__actions">
      <a class="btn btn--whatsapp" id="btnWaDirect" href="<?= e($wa_launch['url']) ?>" target="_blank" rel="noopener noreferrer">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.414-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.888 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.652c1.746.951 3.71 1.452 5.71 1.453h.005c6.554 0 11.89-5.335 11.893-11.893a11.82 11.82 0 00-3.48-8.413z"/>
        </svg>
        Buka WhatsApp Sekarang
      </a>
      <button class="btn btn--secondary" type="button" id="btnWaClose">Tutup</button>
    </div>
  </div>
</div>
<?php endif; ?>
?>

<section class="kpi-grid">
  <article class="kpi kpi--accent">
    <p class="kpi__label"><?= icon('member', 15) ?> Tidak pesan &ge; 30 hari</p>
    <p class="kpi__value"><?= $jumlah_dormant ?></p>
    <p class="kpi__meta">Dari <strong><?= $total_member_aktif ?> member aktif</strong> terdaftar</p>
  </article>

  <article class="kpi">
    <p class="kpi__label"><?= icon('clock', 15) ?> Belum dikirimi promo</p>
    <p class="kpi__value"><?= $belum_dikirim_promo ?></p>
    <p class="kpi__meta">Member inaktif tanpa notifikasi promo</p>
  </article>

  <article class="kpi">
    <p class="kpi__label"><?= icon('send', 15) ?> Promosi terkirim</p>
    <p class="kpi__value"><?= $total_notif_terkirim ?></p>
    <p class="kpi__meta"><strong><?= $total_notif_dibaca ?></strong> sudah dibaca member</p>
  </article>

  <article class="kpi">
    <p class="kpi__label"><?= icon('voucher', 15) ?> Voucher aktif tersedia</p>
    <p class="kpi__value"><?= count($voucher_tersedia) ?></p>
    <p class="kpi__meta">Siap disisipkan ke pesan promosi</p>
  </article>
</section>

<?php if (!empty($error['umum'])): ?>
  <div class="flash flash--error mb-16">
    <span class="flash__icon"><?= icon('close', 16) ?></span>
    <p><?= e($error['umum']) ?></p>
    <button type="button" class="flash__close" aria-label="Tutup pesan">&times;</button>
  </div>
<?php endif; ?>

<nav class="tabs" aria-label="Navigasi Control Promosi">
  <a class="tab<?= $tab === 'member' ? ' is-active' : '' ?>" href="?tab=member<?= $cari !== '' ? '&q=' . urlencode($cari) : '' ?>">
    Member tidak pesan &ge; 30 hari <span class="tab__count"><?= $jumlah_dormant ?></span>
  </a>
  <a class="tab<?= $tab === 'riwayat' ? ' is-active' : '' ?>" href="?tab=riwayat<?= $cari !== '' ? '&q=' . urlencode($cari) : '' ?>">
    Riwayat notifikasi terkirim <span class="tab__count"><?= $total_notif_terkirim ?></span>
  </a>
</nav>

<?php if ($tab === 'member'): ?>

  <article class="card">
    <form class="toolbar" method="get" action="">
      <input type="hidden" name="tab" value="member">
      <div class="search">
        <?= icon('search', 16) ?>
        <label class="visually-hidden" for="q">Cari member</label>
        <input class="input" type="search" id="q" name="q" value="<?= e($cari) ?>"
               placeholder="Cari nama, email, no. HP">
      </div>
      <select class="select" name="status" aria-label="Filter status member" data-autosubmit style="width:auto;min-width:150px;">
        <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua status</option>
        <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Member aktif</option>
        <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Member nonaktif</option>
      </select>
      <button class="btn btn--secondary" type="submit">Cari</button>
      <?php if ($cari !== '' || $status !== 'semua'): ?>
        <a class="btn btn--ghost" href="?tab=member">Reset</a>
      <?php endif; ?>
      <div class="toolbar__right">
        <span class="text-small text-muted"><?= count($dormant_members) ?> member ditemukan</span>
      </div>
    </form>

    <div class="table-wrap">
      <?php if (empty($dormant_members)): ?>
        <div class="empty">
          <div class="empty__icon"><?= icon('check', 20) ?></div>
          <h3>Tidak ada member inaktif &ge; 30 hari</h3>
          <p><?= $cari !== '' ? 'Tidak ada member yang cocok dengan pencarian "' . e($cari) . '".' : 'Semua member aktif telah melakukan pemesanan dalam 30 hari terakhir.' ?></p>
        </div>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Member</th>
              <th>Pesanan terakhir</th>
              <th>Lama tidak pesan</th>
              <th>Status &amp; poin</th>
              <th>Promo terakhir</th>
              <th class="col-actions">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dormant_members as $m): ?>
              <?php
              $hari = (int) $m['hari_tidak_pesan'];
              $ket_lama = $m['pesanan_terakhir']
                  ? 'Terakhir pesan ' . $hari . ' hari lalu'
                  : 'Belum pernah pesan (' . $hari . ' hari sejak gabung)';
              ?>
              <tr>
                <td>
                  <div class="cell-stack">
                    <span class="strong"><?= e($m['name']) ?></span>
                    <small><?= e($m['email']) ?> &middot; <?= e(tampil_telepon($m['no_telp'])) ?></small>
                    <small>Gabung: <?= e(tanggal_id($m['tgl_join'])) ?></small>
                  </div>
                </td>
                <td>
                  <?php if ($m['pesanan_terakhir']): ?>
                    <div class="cell-stack">
                      <span><?= e(tanggal_id($m['pesanan_terakhir'], true)) ?></span>
                      <small><?= (int) $m['total_pesanan_valid'] ?> pesanan berhasil</small>
                    </div>
                  <?php else: ?>
                    <div class="cell-stack">
                      <span class="muted">Belum pernah pesan</span>
                      <?php if ((int) $m['total_pesanan_batal'] > 0): ?>
                        <small><?= (int) $m['total_pesanan_batal'] ?> pesanan dibatalkan</small>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= $hari >= 60 ? 'badge--danger' : 'badge--warning' ?>">
                    <?= $hari ?> hari lalu
                  </span>
                </td>
                <td>
                  <div class="cell-stack">
                    <span><?= badge_member($m['member_status']) ?></span>
                    <small><?= number_format((int) $m['total_poin'], 0, ',', '.') ?> poin</small>
                  </div>
                </td>
                <td>
                  <?php if (!empty($m['promo_terakhir_at'])): ?>
                    <div class="cell-stack">
                      <span class="text-small">
                        <strong><?= e(waktu_lalu($m['promo_terakhir_at'])) ?></strong>
                        <?php if (!empty($m['promo_terakhir_kode'])): ?>
                          &middot; <code><?= e($m['promo_terakhir_kode']) ?></code>
                        <?php endif; ?>
                      </span>
                      <small>
                        <?= (int) $m['promo_terakhir_dibaca'] === 1 ? 'Sudah dibaca' : 'Belum dibaca' ?>
                        (<?= (int) $m['total_promo_dikirim'] ?>x kirim)
                      </small>
                    </div>
                  <?php else: ?>
                    <span class="muted">Belum pernah dikirim</span>
                  <?php endif; ?>
                </td>
                <td class="col-actions">
                  <button class="btn btn--primary btn--sm" type="button"
                          data-modal-open="#modalKirimPromo"
                          data-target-type="single"
                          data-member-id="<?= (int) $m['member_id'] ?>"
                          data-member-nama="<?= e($m['name']) ?>"
                          data-lama-tidak-pesan="<?= e($ket_lama) ?>"
                          data-member-telp="<?= e($m['no_telp']) ?>">
                    <?= icon('send', 14) ?> Kirim Promosi
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </article>

<?php else: ?>

  <article class="card">
    <form class="toolbar" method="get" action="">
      <input type="hidden" name="tab" value="riwayat">
      <div class="search">
        <?= icon('search', 16) ?>
        <label class="visually-hidden" for="q">Cari riwayat promosi</label>
        <input class="input" type="search" id="q" name="q" value="<?= e($cari) ?>"
               placeholder="Cari member, judul promo, atau kode voucher">
      </div>
      <button class="btn btn--secondary" type="submit">Cari</button>
      <?php if ($cari !== ''): ?>
        <a class="btn btn--ghost" href="?tab=riwayat">Reset</a>
      <?php endif; ?>
      <div class="toolbar__right">
        <span class="text-small text-muted"><?= count($riwayat_notif) ?> notifikasi ditampilkan</span>
      </div>
    </form>

    <div class="table-wrap">
      <?php if (empty($riwayat_notif)): ?>
        <div class="empty">
          <div class="empty__icon"><?= icon('bell', 20) ?></div>
          <h3>Belum ada riwayat promosi terkirim</h3>
          <p>Pilih member pada tab target 30 hari lalu klik <strong>Kirim Promosi</strong> untuk mengirimkan notifikasi promo.</p>
          <a class="btn btn--primary" href="?tab=member">Lihat daftar member</a>
        </div>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Waktu kirim</th>
              <th>Member</th>
              <th>Promosi</th>
              <th>Kode voucher</th>
              <th>Status baca</th>
              <th class="col-actions">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($riwayat_notif as $rn): ?>
              <tr>
                <td class="muted nowrap">
                  <div class="cell-stack">
                    <span><?= e(tanggal_id($rn['created_at'], true)) ?></span>
                    <small>oleh <?= e($rn['nama_pengirim'] ?? 'Admin') ?></small>
                  </div>
                </td>
                <td>
                  <div class="cell-stack">
                    <span class="strong"><?= e($rn['nama_member']) ?></span>
                    <small><?= e($rn['email_member']) ?></small>
                  </div>
                </td>
                <td>
                  <div class="cell-stack">
                    <span class="strong"><?= e($rn['judul']) ?></span>
                    <small><?= e(potong($rn['pesan'], 110)) ?></small>
                  </div>
                </td>
                <td>
                  <?php if (!empty($rn['kode_voucher'])): ?>
                    <span class="badge badge--info"><?= e($rn['kode_voucher']) ?></span>
                    <?php if (!empty($rn['potongan_harga'])): ?>
                      <div class="text-small muted mt-8"><?= e(rupiah($rn['potongan_harga'])) ?></div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="muted">Tanpa voucher</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ((int) $rn['is_read'] === 1): ?>
                    <span class="badge badge--success">Sudah dibaca</span>
                  <?php else: ?>
                    <span class="badge badge--warning">Belum dibaca</span>
                  <?php endif; ?>
                </td>
                <td class="col-actions">
                  <form class="inline-form" method="post" action="" data-confirm="Hapus notifikasi promo ini dari riwayat & kotak masuk member?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="hapus_notif">
                    <input type="hidden" name="notif_id" value="<?= (int) $rn['notif_id'] ?>">
                    <button class="btn btn--danger btn--sm" type="submit"><?= icon('trash', 14) ?> Hapus</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </article>

<?php endif; ?>

<!-- Modal Kirim Promosi -->
<dialog class="modal" id="modalKirimPromo" aria-labelledby="judulModalPromo" style="width:min(540px, calc(100vw - 32px));">
  <form method="post" action="" id="formKirimPromo" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="kirim_promo">
    <input type="hidden" name="target_type" value="<?= e($form_data['target_type']) ?>" data-input="targetType">
    <input type="hidden" name="member_id" value="<?= (int) $form_data['member_id'] ?>" data-input="memberId">

    <div class="modal__head">
      <h2 id="judulModalPromo">Kirim notifikasi promosi</h2>
      <p>Kirim ke <strong data-slot="memberNama"><?= e($form_data['member_nama'] ?: 'member') ?></strong> &middot; <span data-slot="lamaTidakPesan"><?= e($form_data['lama_tidak_pesan'] ?: '-') ?></span></p>
    </div>

    <div class="modal__body">
      <div class="form-grid form-grid--1">
        <div class="field">
          <label for="kodeVoucherInput">Kode voucher promosi</label>
          <input class="input<?= isset($error['kode_voucher']) ? ' is-invalid' : '' ?>"
                 type="text" id="kodeVoucherInput" name="kode_voucher" list="daftarVoucherAktif"
                 value="<?= e($form_data['kode_voucher']) ?>" maxlength="50"
                 placeholder="Ketik atau pilih kode voucher (mis. NASHVILLE10)"
                 autocomplete="off" style="text-transform:uppercase;">
          <datalist id="daftarVoucherAktif">
            <?php foreach ($voucher_tersedia as $v): ?>
              <option value="<?= e($v['kode_voucher']) ?>"><?= e(rupiah($v['potongan_harga'])) ?> (s.d. <?= e(tanggal_id($v['valid_until'])) ?>)</option>
            <?php endforeach; ?>
          </datalist>
          <?php if (isset($error['kode_voucher'])): ?>
            <span class="error"><?= e($error['kode_voucher']) ?></span>
          <?php else: ?>
            <span class="hint" id="voucherStatusHint">Pilih voucher aktif di bawah atau ketik kode voucher khusus.</span>
          <?php endif; ?>

          <?php if (!empty($voucher_tersedia)): ?>
            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:4px;">
              <?php foreach ($voucher_tersedia as $v): ?>
                <button type="button" class="btn btn--secondary btn--sm js-pick-voucher"
                        data-kode="<?= e($v['kode_voucher']) ?>">
                  <?= icon('voucher', 13) ?> <?= e($v['kode_voucher']) ?> (<?= e(rupiah($v['potongan_harga'])) ?>)
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div id="boxVoucherBaru" hidden style="display:none; padding:12px; border-radius:6px; background:var(--gray-50); border:1px solid var(--gray-200);">
          <p class="text-small" style="font-weight:600; margin-bottom:8px;">Kode baru belum terdaftar di tabel Voucher (opsional):</p>
          <div class="form-grid">
            <div class="field">
              <label for="potonganBaru">Potongan harga (Rp)</label>
              <input class="input<?= isset($error['potongan_baru']) ? ' is-invalid' : '' ?>" type="text" inputmode="numeric" id="potonganBaru" name="potongan_baru"
                     data-nominal-rupiah="potonganBaruPreview"
                     value="<?= isset($error['potongan_baru']) ? e($form_data['potongan_baru_raw'] ?? '') : ($form_data['potongan_baru'] > 0 ? number_format((float) $form_data['potongan_baru'], 0, ',', '.') : '') ?>"
                     placeholder="Contoh: 12500 atau 15.000" autocomplete="off">
              <?php if (isset($error['potongan_baru'])): ?>
                <span class="error"><?= e($error['potongan_baru']) ?></span>
              <?php else: ?>
                <span class="hint" id="potonganBaruPreview">Bisa isi 10000, 12.500, atau 10k.</span>
              <?php endif; ?>
            </div>
            <div class="field">
              <label for="validUntilBaru">Berlaku sampai</label>
              <input class="input<?= isset($error['valid_until_baru']) ? ' is-invalid' : '' ?>"
                     type="date" id="validUntilBaru" name="valid_until_baru"
                     min="<?= date('Y-m-d') ?>" value="<?= e($form_data['valid_until_baru']) ?>">
              <?php if (isset($error['valid_until_baru'])): ?>
                <span class="error"><?= e($error['valid_until_baru']) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <span class="hint" style="display:block; margin-top:6px;">Jika nominal diisi &gt; 0, voucher otomatis diaktifkan agar dapat dipakai member saat checkout.</span>
        </div>

        <div class="field">
          <label for="judulPromo">Judul notifikasi <span aria-hidden="true">*</span></label>
          <input class="input<?= isset($error['judul']) ? ' is-invalid' : '' ?>" type="text" id="judulPromo" name="judul"
                 value="<?= e($form_data['judul']) ?>" maxlength="150" required>
          <?php if (isset($error['judul'])): ?><span class="error"><?= e($error['judul']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="pesanPromo">Pesan promosi <span aria-hidden="true">*</span></label>
          <textarea class="textarea<?= isset($error['pesan']) ? ' is-invalid' : '' ?>" id="pesanPromo" name="pesan"
                    maxlength="1000" required><?= e($form_data['pesan']) ?></textarea>
          <?php if (isset($error['pesan'])): ?>
            <span class="error"><?= e($error['pesan']) ?></span>
          <?php else: ?>
            <span class="hint">Akan tampil langsung di daftar notifikasi akun member.</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="modal__foot">
      <button class="btn btn--secondary" type="button" data-modal-close>Batal</button>
      <button class="btn btn--primary" type="submit"><?= icon('send', 15) ?> Kirim Promosi</button>
    </div>

    <input type="hidden" name="member_telp" value="" data-input="memberTelp">
  </form>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var voucherLookup = <?= json_encode($voucher_lookup, JSON_UNESCAPED_UNICODE) ?>;
  var modal = document.getElementById('modalKirimPromo');
  var inputKode = document.getElementById('kodeVoucherInput');
  var hint = document.getElementById('voucherStatusHint');
  var boxBaru = document.getElementById('boxVoucherBaru');

  function updateVoucherStatus() {
    if (!inputKode || !hint || !boxBaru) return;
    var kode = inputKode.value.trim().toUpperCase();
    inputKode.value = kode;

    document.querySelectorAll('.js-pick-voucher').forEach(function (btn) {
      var aktif = (btn.getAttribute('data-kode') || '').toUpperCase() === kode && kode !== '';
      btn.classList.toggle('btn--teal', aktif);
      btn.classList.toggle('btn--secondary', !aktif);
    });

    if (kode === '') {
      hint.textContent = 'Pilih voucher aktif di bawah atau ketik kode voucher khusus.';
      hint.style.color = '';
      boxBaru.hidden = true;
      boxBaru.style.display = 'none';
      return;
    }

    if (voucherLookup[kode]) {
      var info = voucherLookup[kode];
      hint.textContent = 'Voucher aktif ditemukan: potongan ' + info.potongan_label + ' (berlaku s.d. ' + info.valid_until + ').';
      hint.style.color = '#1C7430';
      boxBaru.hidden = true;
      boxBaru.style.display = 'none';
    } else {
      hint.textContent = 'Kode "' + kode + '" belum ada di katalog voucher. Anda tetap dapat mengirimkannya atau mengaktifkan potongan di bawah.';
      hint.style.color = '#8A5B00';
      boxBaru.hidden = false;
      boxBaru.style.display = 'block';
    }
  }

  if (inputKode) {
    inputKode.addEventListener('input', updateVoucherStatus);
    inputKode.addEventListener('change', updateVoucherStatus);
  }

  document.querySelectorAll('.js-pick-voucher').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!inputKode) return;
      inputKode.value = btn.getAttribute('data-kode') || '';
      updateVoucherStatus();
    });
  });

  document.querySelectorAll('[data-modal-open="#modalKirimPromo"]').forEach(function (trigger) {
    trigger.addEventListener('click', function () {
      setTimeout(updateVoucherStatus, 20);
    });
  });

  <?php if ($form_reopen): ?>
  if (modal && typeof modal.showModal === 'function') {
    modal.showModal();
    updateVoucherStatus();
  }
  <?php endif; ?>

  // --- Penanganan Animasi Pesawat Kertas & WhatsApp Auto-Open ---
  <?php if (!empty($wa_launch)): ?>
  (function () {
    var overlay = document.getElementById('waFlightOverlay');
    var progress = document.getElementById('waProgressFill');
    var countdownSec = document.getElementById('waCountdownSec');
    var btnClose = document.getElementById('btnWaClose');
    var timerBox = document.getElementById('waFlightTimer');
    var waUrl = <?= json_encode($wa_launch['url'], JSON_UNESCAPED_SLASHES) ?>;

    if (progress) {
      setTimeout(function () {
        progress.style.width = '100%';
      }, 80);
    }

    var timeLeft = 2;
    var timerInterval = setInterval(function () {
      timeLeft--;
      if (countdownSec && timeLeft >= 0) {
        countdownSec.textContent = timeLeft;
      }
      if (timeLeft <= 0) {
        clearInterval(timerInterval);
      }
    }, 1000);

    var waTimer = setTimeout(function () {
      if (waUrl) {
        try {
          var win = window.open(waUrl, '_blank');
          if (!win || win.closed || typeof win.closed === 'undefined') {
            if (timerBox) {
              timerBox.innerHTML = '<span style="color:#FF6B35;font-weight:600;">Klik tombol hijau di bawah untuk membuka WhatsApp.</span>';
            }
            return;
          }
        } catch (e) {
          // ignore
        }
      }
      if (timerBox) {
        timerBox.innerHTML = '<span style="color:#25D366;font-weight:600;">✓ WhatsApp telah dibuka di tab baru!</span>';
      }
      setTimeout(function () {
        if (overlay) {
          overlay.style.opacity = '0';
          setTimeout(function () { overlay.remove(); }, 400);
        }
      }, 2500);
    }, 2400);

    if (btnClose) {
      btnClose.addEventListener('click', function () {
        clearTimeout(waTimer);
        clearInterval(timerInterval);
        if (overlay) {
          overlay.style.opacity = '0';
          setTimeout(function () { overlay.remove(); }, 350);
        }
      });
    }
  })();
  <?php endif; ?>

  // Feedback tombol saat form dikirim
  var formPromo = document.getElementById('formKirimPromo');
  if (formPromo) {
    formPromo.addEventListener('submit', function () {
      var btnSubmit = formPromo.querySelector('button[type="submit"]');
      if (btnSubmit) {
        btnSubmit.innerHTML = '✈️ Mengirim Promosi…';
        btnSubmit.style.opacity = '0.85';
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
