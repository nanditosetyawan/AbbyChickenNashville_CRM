<?php
/**
 * Halaman Ulasan Pelanggan (User / Member).
 * Desain Mobile Viewport berbasis C:\xampp\htdocs\CRM\.design\mobile.md
 * Fokus untuk input tautan bukti ulasan Google Maps dan mengirimkannya.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['User', 'Member']);

$member_id   = (int) ($_SESSION['member_id'] ?? 0);
$nama_member = $_SESSION['nama'] ?? 'Pelanggan';

// Ambil profil member terkini
$stmt_m = mysqli_prepare($conn, 'SELECT member_id, name, email, member_status FROM member WHERE member_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt_m, 'i', $member_id);
mysqli_stmt_execute($stmt_m);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_m));
mysqli_stmt_close($stmt_m);

$error     = [];
$url_input = '';

/* Proses Kirim Bukti Ulasan */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $url_input = trim($_POST['url_bukti'] ?? '');

    if (!$member || $member['member_status'] !== 'aktif') {
        $error['umum'] = 'Akun member Anda berstatus nonaktif atau tidak valid.';
    }

    if ($url_input === '') {
        $error['url_bukti'] = 'Tautan ulasan wajib diisi.';
    } elseif (!filter_var($url_input, FILTER_VALIDATE_URL)) {
        $error['url_bukti'] = 'Format URL tidak valid. Contoh: https://maps.app.goo.gl/...';
    } elseif (mb_strlen($url_input) > 500) {
        $error['url_bukti'] = 'Tautan ulasan maksimal 500 karakter.';
    } else {
        $skema = strtolower((string) parse_url($url_input, PHP_URL_SCHEME));
        if (!in_array($skema, ['http', 'https'], true)) {
            $error['url_bukti'] = 'Tautan ulasan harus diawali http:// atau https://.';
        }
    }

    // Cegah klaim ganda untuk URL yang sama
    if (empty($error)) {
        $stmt_cek = mysqli_prepare($conn, 'SELECT klaim_id FROM klaim_review WHERE url_bukti = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt_cek, 's', $url_input);
        mysqli_stmt_execute($stmt_cek);
        mysqli_stmt_store_result($stmt_cek);
        if (mysqli_stmt_num_rows($stmt_cek) > 0) {
            $error['url_bukti'] = 'Tautan ulasan ini sudah pernah diklaim sebelumnya.';
        }
        mysqli_stmt_close($stmt_cek);
    }

    if (empty($error)) {
        $stmt_ins = mysqli_prepare($conn, "INSERT INTO klaim_review (member_id, voucher_id, pegawai_id, url_bukti, status_validasi) VALUES (?, NULL, NULL, ?, 'pending')");
        mysqli_stmt_bind_param($stmt_ins, 'is', $member_id, $url_input);

        if (mysqli_stmt_execute($stmt_ins)) {
            $klaim_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_ins);

            set_flash('success', 'Tautan ulasan berhasil dikirim (Klaim #' . $klaim_id . ')! Tim Admin / Kasir akan segera memvalidasi ulasan Anda.');
            redirect(base_url('pages/user/ulasan.php'));
        } else {
            $error['umum'] = 'Gagal menyimpan klaim: ' . mysqli_error($conn);
            mysqli_stmt_close($stmt_ins);
        }
    }
}

/* Riwayat klaim ulasan milik member */
$stmt_r = mysqli_prepare($conn, "SELECT k.*, v.kode_voucher, v.potongan_harga, v.valid_until, pg.nama AS nama_validator
    FROM klaim_review k
    LEFT JOIN voucher v ON v.voucher_id = k.voucher_id
    LEFT JOIN pegawai pg ON pg.pegawai_id = k.pegawai_id
    WHERE k.member_id = ?
    ORDER BY k.created_at DESC");
mysqli_stmt_bind_param($stmt_r, 'i', $member_id);
mysqli_stmt_execute($stmt_r);
$res_r = mysqli_stmt_get_result($stmt_r);

$riwayat_klaim = [];
$total_pending  = 0;
$total_approved = 0;
while ($row = mysqli_fetch_assoc($res_r)) {
    $riwayat_klaim[] = $row;
    if ($row['status_validasi'] === 'pending') {
        $total_pending++;
    } elseif ($row['status_validasi'] === 'approved') {
        $total_approved++;
    }
}
mysqli_stmt_close($stmt_r);

$page_title    = 'Ulasan & Voucher';
$page_subtitle = 'Kirim tautan ulasan Google Maps dan klaim voucher diskon';
$active_menu   = 'ulasan';

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Form Input Tautan Ulasan (Bento Card #FAF9F5) -->
<form class="crm-card-cream" method="post" action="" novalidate style="margin-bottom: 20px;">
  <?= csrf_field() ?>

  <div class="crm-card-cream__head">
    <div>
      <h3 class="crm-card-cream__title">Klaim Ulasan Google Maps</h3>
      <p class="crm-card-cream__subtitle">Beri ulasan di Google Maps AbbyChicken &amp; tempelkan tautannya di sini</p>
    </div>
  </div>

  <?php if (!empty($error['umum'])): ?>
    <div class="crm-mobile-flash crm-mobile-flash--error"><p><?= e($error['umum']) ?></p></div>
  <?php endif; ?>

  <div class="crm-mobile-field">
    <label for="url_bukti">Tautan Bukti Ulasan Google Maps <span style="color:var(--crm-terracotta);">*</span></label>
    <input class="crm-mobile-input" type="url" id="url_bukti" name="url_bukti"
           value="<?= e($url_input) ?>" placeholder="https://maps.app.goo.gl/..." required>
    <?php if (isset($error['url_bukti'])): ?>
      <p style="color: var(--crm-terracotta); font-size: 12px; margin: 4px 0 0;"><?= e($error['url_bukti']) ?></p>
    <?php else: ?>
      <p style="color: var(--crm-sage); font-size: 11.5px; margin: 4px 0 0;">
        Buka ulasan Anda di Google Maps &rarr; klik Bagikan &rarr; salin tautan.
      </p>
    <?php endif; ?>
  </div>

  <button class="crm-btn-primary" type="submit" style="margin-top: 6px;">
    <?= icon('check', 16) ?> Kirim Bukti Ulasan
  </button>
</form>

<!-- Riwayat Klaim Ulasan (Hierarchical Bento Cards) -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
  <h3 style="margin: 0; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--crm-sage);">
    Riwayat Klaim Ulasan Anda
  </h3>
  <span class="crm-pill-tag"><?= count($riwayat_klaim) ?> ulasan</span>
</div>

<div class="crm-list-stack" style="margin-bottom: 24px;">
  <?php if (empty($riwayat_klaim)): ?>
    <div class="crm-card-cream" style="text-align: center; padding: 36px 20px;">
      <div style="font-size: 32px; margin-bottom: 8px;"><?= icon('voucher', 36) ?></div>
      <h3 class="crm-card-cream__title">Belum ada klaim ulasan</h3>
      <p class="crm-card-cream__subtitle">Bagikan ulasan Anda di Google Maps untuk mendapatkan voucher diskon!</p>
    </div>
  <?php else: ?>
    <?php foreach ($riwayat_klaim as $idx => $k): ?>
      <?php $is_approved = ($k['status_validasi'] === 'approved'); ?>
      <?php $is_pending = ($k['status_validasi'] === 'pending'); ?>
      <article class="crm-card-<?= $is_approved ? 'forest' : 'cream' ?>">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
          <div>
            <span class="crm-pill-tag <?= $is_approved ? 'crm-pill-tag--green' : ($is_pending ? 'crm-pill-tag--terracotta' : '') ?>" style="margin-bottom: 6px; color: #FFFFFF;<?= $is_pending ? ' background: var(--crm-terracotta);' : '' ?>">
              Klaim #<?= (int) $k['klaim_id'] ?>
            </span>
            <div style="margin-top: 4px;">
              <a href="<?= e($k['url_bukti']) ?>" target="_blank" rel="noopener noreferrer" style="color: <?= $is_approved ? '#FFFFFF' : 'var(--crm-forest)' ?>; font-size: 13px; font-weight: 600; text-decoration: underline; display: inline-flex; align-items: center; gap: 4px;">
                <?= icon('link', 14) ?> Buka tautan ulasan
              </a>
            </div>
          </div>
          <div>
            <?= badge_klaim($k['status_validasi']) ?>
          </div>
        </div>

        <div style="font-size: 12px; margin-bottom: 10px; color: <?= $is_approved ? '#C0CDC0' : 'var(--crm-sage)' ?>; display: flex; flex-direction: column; gap: 3px;">
          <div>Tanggal Diajukan: <strong style="color: <?= $is_approved ? '#FFFFFF' : 'var(--crm-black)' ?>;"><?= e(tanggal_id($k['created_at'], true)) ?></strong></div>
          <?php if (!empty($k['nama_validator'])): ?>
            <div>Disetujui oleh: <strong style="color: <?= $is_approved ? 'var(--crm-green-accent)' : 'var(--crm-forest)' ?>;"><?= e($k['nama_validator']) ?> (Admin)</strong></div>
          <?php elseif ($is_pending): ?>
            <div style="color: var(--crm-terracotta);">Status: Menunggu verifikasi admin</div>
          <?php endif; ?>
        </div>

        <div style="border-top: 1px solid <?= $is_approved ? 'rgba(255,255,255,0.12)' : 'rgba(24,32,21,0.08)' ?>; padding-top: 10px; display: flex; justify-content: space-between; align-items: center;">
          <span style="font-size: 12px; color: <?= $is_approved ? '#C0CDC0' : 'var(--crm-sage)' ?>;">Voucher Diberikan</span>
          <?php if ($is_approved && !empty($k['kode_voucher'])): ?>
            <div style="display: inline-flex; align-items: center; gap: 6px;">
              <span class="crm-pill-tag" style="background: var(--crm-green-accent); color: var(--crm-black); font-weight: 700; font-family: monospace; font-size: 13px;">
                <?= e($k['kode_voucher']) ?>
              </span>
              <?php if (!empty($k['potongan_harga'])): ?>
                <span style="font-size: 12px; font-weight: 600; color: var(--crm-green-accent);">(<?= rupiah($k['potongan_harga']) ?>)</span>
              <?php endif; ?>
            </div>
          <?php elseif ($is_pending): ?>
            <span style="font-size: 12px; color: var(--crm-terracotta);">Menunggu verifikasi admin</span>
          <?php else: ?>
            <span style="font-size: 12px; color: var(--crm-sage);">Belum memenuhi syarat</span>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
