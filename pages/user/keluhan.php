<?php
/**
 * Halaman Keluhan Pelanggan (User / Member).
 * Desain Mobile Viewport berbasis C:\xampp\htdocs\CRM\.design\mobile.md
 * Menyediakan form pengiriman tiket keluhan langsung ke database
 * dan memantau status tindak lanjut oleh Admin dan PIC Keluhan.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['User', 'Member']);

$member_id   = (int) ($_SESSION['member_id'] ?? 0);
$nama_member = $_SESSION['nama'] ?? 'Pelanggan';

$error = [];
$kategori_list = ['Kualitas Makanan', 'Pelayanan', 'Kebersihan', 'Lainnya'];
$kategori_dipilih = $_POST['kategori_keluhan'] ?? '';
$isi_keluhan      = trim($_POST['isi_keluhan'] ?? '');

/* Proses Kirim Keluhan */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (!in_array($kategori_dipilih, $kategori_list, true)) {
        $error['kategori'] = 'Pilih kategori keluhan yang sesuai.';
    }

    if (mb_strlen($isi_keluhan) < 5) {
        $error['isi'] = 'Mohon jelaskan keluhan Anda minimal 5 karakter.';
    }

    if (empty($error)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO tiket (member_id, pegawai_id, kategori_keluhan, isi_keluhan, status) VALUES (?, NULL, ?, ?, 'open')");
        mysqli_stmt_bind_param($stmt, 'iss', $member_id, $kategori_dipilih, $isi_keluhan);

        if (mysqli_stmt_execute($stmt)) {
            $tiket_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            set_flash('success', 'Keluhan Anda berhasil dikirim! Tiket #' . $tiket_id . ' telah tercatat dan segera ditangani tim PIC.');
            redirect(base_url('pages/user/keluhan.php?tab=riwayat'));
        } else {
            $error['umum'] = 'Gagal menyimpan keluhan: ' . mysqli_error($conn);
            mysqli_stmt_close($stmt);
        }
    }
}

/* Riwayat tiket keluhan milik user */
$tiket_riwayat = [];
$stmt_r = mysqli_prepare($conn, "SELECT t.*, pg.nama AS nama_pic
    FROM tiket t
    LEFT JOIN pegawai pg ON pg.pegawai_id = t.pegawai_id
    WHERE t.member_id = ?
    ORDER BY t.created_at DESC");
mysqli_stmt_bind_param($stmt_r, 'i', $member_id);
mysqli_stmt_execute($stmt_r);
$res_r = mysqli_stmt_get_result($stmt_r);
while ($row = mysqli_fetch_assoc($res_r)) {
    $tiket_riwayat[] = $row;
}
mysqli_stmt_close($stmt_r);

$tab_aktif = $_GET['tab'] ?? 'form';

$page_title    = 'Keluhan';
$page_subtitle = 'Sampaikan kendala atau kritik saran Anda ke tim PIC';
$active_menu   = 'keluhan';

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Filter Bar (#EAE9E0) -->
<nav class="crm-filter-bar" aria-label="Navigasi Keluhan">
  <a class="crm-filter-pill<?= $tab_aktif === 'form' ? ' is-active' : '' ?>" href="<?= e(base_url('pages/user/keluhan.php')) ?>">
    <?php if ($tab_aktif === 'form'): ?><span class="crm-filter-pill__dot"></span><?php endif; ?>
    <?= icon('plus', 14) ?> Kirim Keluhan
  </a>
  <a class="crm-filter-pill<?= $tab_aktif === 'riwayat' ? ' is-active' : '' ?>" href="<?= e(base_url('pages/user/keluhan.php?tab=riwayat')) ?>">
    <?php if ($tab_aktif === 'riwayat'): ?><span class="crm-filter-pill__dot"></span><?php endif; ?>
    <?= icon('complaint', 14) ?> Riwayat Tiket
    <?php if (!empty($tiket_riwayat)): ?>
      <span class="crm-dock-badge" style="position:static;"><?= count($tiket_riwayat) ?></span>
    <?php endif; ?>
  </a>
</nav>

<?php if (!empty($error['umum'])): ?>
  <div class="crm-mobile-flash crm-mobile-flash--error"><p><?= e($error['umum']) ?></p></div>
<?php endif; ?>

<?php if ($tab_aktif === 'riwayat'): ?>
  <!-- Tab Riwayat: Hierarchical Bento Cards -->
  <div class="crm-list-stack">
    <?php if (empty($tiket_riwayat)): ?>
      <div class="crm-card-cream" style="text-align: center; padding: 36px 20px;">
        <div style="font-size: 32px; margin-bottom: 8px;"><?= icon('complaint', 36) ?></div>
        <h3 class="crm-card-cream__title">Belum ada tiket keluhan</h3>
        <p class="crm-card-cream__subtitle">Jika Anda mengalami kendala makanan atau pelayanan, kirimkan tiket baru.</p>
        <a class="crm-btn-primary" href="<?= e(base_url('pages/user/keluhan.php')) ?>" style="margin-top: 16px;">
          Tulis Keluhan Baru
        </a>
      </div>
    <?php else: ?>
      <?php foreach ($tiket_riwayat as $idx => $t): ?>
        <?php $is_open = ($t['status'] === 'open'); ?>
        <article class="crm-card-<?= $is_open ? 'forest' : 'cream' ?>">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
            <div>
              <span class="crm-pill-tag <?= $is_open ? 'crm-pill-tag--terracotta' : '' ?>" style="margin-bottom: 6px;">
                Tiket #<?= (int) $t['tiket_id'] ?> &middot; <?= e($t['kategori_keluhan']) ?>
              </span>
              <p style="margin: 6px 0 0; font-size: 13.5px; line-height: 1.45; color: <?= $is_open ? '#FFFFFF' : 'var(--crm-black)' ?>;">
                <?= nl2br(e($t['isi_keluhan'])) ?>
              </p>
            </div>
            <div>
              <?= badge_tiket($t['status']) ?>
            </div>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid <?= $is_open ? 'rgba(255,255,255,0.12)' : 'rgba(24,32,21,0.08)' ?>; padding-top: 10px; margin-top: 10px; font-size: 12px; color: <?= $is_open ? '#C0CDC0' : 'var(--crm-sage)' ?>;">
            <span><?= e(tanggal_id($t['created_at'], true)) ?></span>
            <span>
              <?php if (!empty($t['nama_pic'])): ?>
                PIC: <strong><?= e($t['nama_pic']) ?></strong>
              <?php else: ?>
                <em>Menunggu PIC</em>
              <?php endif; ?>
            </span>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- Tab Form Kirim Keluhan Baru (Bento Card #FAF9F5) -->
  <form class="crm-card-cream" method="post" action="" novalidate style="margin-bottom: 24px;">
    <?= csrf_field() ?>

    <div class="crm-card-cream__head">
      <div>
        <h3 class="crm-card-cream__title">Sampaikan Kendala Anda</h3>
        <p class="crm-card-cream__subtitle">Tim PIC AbbyChicken akan menindaklanjuti keluhan Anda</p>
      </div>
    </div>

    <div class="crm-mobile-field">
      <label for="kategori_keluhan">Kategori Keluhan <span style="color:var(--crm-terracotta);">*</span></label>
      <select class="crm-mobile-input" id="kategori_keluhan" name="kategori_keluhan" required>
        <option value="">&mdash; Pilih Kategori &mdash;</option>
        <?php foreach ($kategori_list as $kat): ?>
          <option value="<?= e($kat) ?>" <?= $kategori_dipilih === $kat ? 'selected' : '' ?>>
            <?= e($kat) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($error['kategori'])): ?>
        <p style="color: var(--crm-terracotta); font-size: 12px; margin: 4px 0 0;"><?= e($error['kategori']) ?></p>
      <?php endif; ?>
    </div>

    <div class="crm-mobile-field">
      <label for="isi_keluhan">Rincian Keluhan <span style="color:var(--crm-terracotta);">*</span></label>
      <textarea class="crm-mobile-input" id="isi_keluhan" name="isi_keluhan" rows="4"
                placeholder="Ceritakan kendala yang Anda alami secara detail..."
                required style="resize: vertical;"><?= e($isi_keluhan) ?></textarea>
      <?php if (isset($error['isi'])): ?>
        <p style="color: var(--crm-terracotta); font-size: 12px; margin: 4px 0 0;"><?= e($error['isi']) ?></p>
      <?php endif; ?>
    </div>

    <button class="crm-btn-primary" type="submit" style="margin-top: 10px;">
      <?= icon('check', 16) ?> Kirim Keluhan Sekarang
    </button>
  </form>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
