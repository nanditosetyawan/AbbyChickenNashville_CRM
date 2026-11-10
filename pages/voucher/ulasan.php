<?php
/**
 * Catat klaim review baru (dilakukan pegawai atas nama member).
 * Padanan US-2.1 pada sisi internal.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'Kasir']);

$error = [];
$data  = ['member_id' => '', 'url_bukti' => ''];

$members = mysqli_query($conn, "SELECT member_id, name, email, member_status FROM member ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data['member_id'] = (int) ($_POST['member_id'] ?? 0);
    $data['url_bukti'] = trim($_POST['url_bukti'] ?? '');

    $stmt = mysqli_prepare($conn, 'SELECT member_id, member_status FROM member WHERE member_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $data['member_id']);
    mysqli_stmt_execute($stmt);
    $member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$member) {
        $error['member_id'] = 'Pilih member yang mengirim bukti ulasan.';
    } elseif ($member['member_status'] !== 'aktif') {
        $error['member_id'] = 'Member berstatus nonaktif tidak dapat mengirim klaim review.';
    }

    if ($data['url_bukti'] === '') {
        $error['url_bukti'] = 'URL bukti ulasan wajib diisi.';
    } elseif (!filter_var($data['url_bukti'], FILTER_VALIDATE_URL)) {
        $error['url_bukti'] = 'Format URL tidak valid. Contoh: https://maps.app.goo.gl/...';
    } elseif (mb_strlen($data['url_bukti']) > 500) {
        $error['url_bukti'] = 'URL maksimal 500 karakter.';
    } else {
        $skema = strtolower((string) parse_url($data['url_bukti'], PHP_URL_SCHEME));
        if (!in_array($skema, ['http', 'https'], true)) {
            $error['url_bukti'] = 'URL harus diawali http:// atau https://.';
        }
    }

    if (empty($error)) {
        // Cegah klaim ganda untuk URL yang sama.
        $stmt = mysqli_prepare($conn, 'SELECT klaim_id FROM klaim_review WHERE url_bukti = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $data['url_bukti']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error['url_bukti'] = 'URL bukti ini sudah pernah diklaim.';
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($error)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO klaim_review (member_id, voucher_id, pegawai_id, url_bukti, status_validasi) VALUES (?, NULL, NULL, ?, 'pending')");
        mysqli_stmt_bind_param($stmt, 'is', $data['member_id'], $data['url_bukti']);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            set_flash('success', 'Klaim review berhasil dikirim, menunggu validasi.');
            redirect(base_url('pages/voucher/index.php?tab=klaim&status=pending'));
        }
        $error['umum'] = 'Gagal menyimpan klaim: ' . mysqli_error($conn);
        mysqli_stmt_close($stmt);
    }
}

$page_title    = 'Catat klaim review';
$page_subtitle = 'Simpan bukti ulasan Google Maps yang dikirim member untuk divalidasi.';
$active_menu   = 'voucher';
$page_actions  = '<a class="btn btn--secondary" href="' . e(base_url('pages/voucher/index.php?tab=klaim')) . '">' . icon('arrow-left', 16) . ' Kembali</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<form class="card" method="post" action="" novalidate>
  <?= csrf_field() ?>
  <div class="card__head">
    <div>
      <h2>Bukti ulasan</h2>
      <p>Klaim tersimpan dengan status pending sampai admin memvalidasi.</p>
    </div>
  </div>

  <div class="card__body">
    <?php if (!empty($error['umum'])): ?>
      <div class="flash flash--error mb-16"><span class="flash__icon"><?= icon('close', 16) ?></span><p><?= e($error['umum']) ?></p></div>
    <?php endif; ?>

    <div class="form-grid form-grid--1">
      <div class="field">
        <label for="member_id">Member <span aria-hidden="true">*</span></label>
        <select class="select<?= isset($error['member_id']) ? ' is-invalid' : '' ?>" id="member_id" name="member_id" required>
          <option value="">— Pilih member —</option>
          <?php while ($m = mysqli_fetch_assoc($members)): ?>
            <option value="<?= (int) $m['member_id'] ?>" <?= (int) $data['member_id'] === (int) $m['member_id'] ? 'selected' : '' ?>
              <?= $m['member_status'] !== 'aktif' ? 'disabled' : '' ?>>
              <?= e($m['name']) ?> — <?= e($m['email']) ?><?= $m['member_status'] !== 'aktif' ? ' (nonaktif)' : '' ?>
            </option>
          <?php endwhile; ?>
        </select>
        <?php if (isset($error['member_id'])): ?>
          <span class="error"><?= e($error['member_id']) ?></span>
        <?php else: ?>
          <span class="hint">Hanya member aktif yang dapat mengirim klaim.</span>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="url_bukti">URL bukti ulasan <span aria-hidden="true">*</span></label>
        <input class="input<?= isset($error['url_bukti']) ? ' is-invalid' : '' ?>" type="url" id="url_bukti" name="url_bukti"
               value="<?= e($data['url_bukti']) ?>" maxlength="500" placeholder="https://maps.app.goo.gl/..." required>
        <?php if (isset($error['url_bukti'])): ?>
          <span class="error"><?= e($error['url_bukti']) ?></span>
        <?php else: ?>
          <span class="hint">Tautan screenshot atau ulasan Google Maps milik member.</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Kirim klaim</button>
      <a class="btn btn--secondary" href="<?= e(base_url('pages/voucher/index.php?tab=klaim')) ?>">Batal</a>
      <span class="form-actions__note">Status awal: pending</span>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
