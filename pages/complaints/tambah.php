<?php
/**
 * Form catat keluhan baru (atas nama member).
 * Padanan US-4.1 pada sisi internal.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'PIC Keluhan']);

$error = [];
$data  = ['member_id' => '', 'kategori_keluhan' => '', 'isi_keluhan' => ''];

$kategori_pilihan = ['Kualitas Makanan', 'Pelayanan', 'Kebersihan', 'Lainnya'];

$members = mysqli_query($conn, "SELECT member_id, name, email, member_status FROM member WHERE member_status = 'aktif' ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data['member_id']        = (int) ($_POST['member_id'] ?? 0);
    $data['kategori_keluhan'] = $_POST['kategori_keluhan'] ?? '';
    $data['isi_keluhan']      = trim($_POST['isi_keluhan'] ?? '');

    $stmt = mysqli_prepare($conn, "SELECT member_id FROM member WHERE member_id = ? AND member_status = 'aktif' LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $data['member_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) === 0) {
        $error['member_id'] = 'Pilih member aktif yang mengajukan keluhan.';
    }
    mysqli_stmt_close($stmt);

    if (!in_array($data['kategori_keluhan'], $kategori_pilihan, true)) {
        $error['kategori_keluhan'] = 'Pilih kategori keluhan.';
    }

    if (mb_strlen($data['isi_keluhan']) < 10) {
        $error['isi_keluhan'] = 'Isi keluhan minimal 10 karakter agar dapat ditindaklanjuti.';
    } elseif (mb_strlen($data['isi_keluhan']) > 1000) {
        $error['isi_keluhan'] = 'Isi keluhan maksimal 1000 karakter.';
    }

    if (empty($error)) {
        // pegawai_id NULL sampai di-assign admin (US-4.1).
        $stmt = mysqli_prepare($conn, "INSERT INTO tiket (pegawai_id, member_id, kategori_keluhan, isi_keluhan, status) VALUES (NULL, ?, ?, ?, 'open')");
        mysqli_stmt_bind_param($stmt, 'iss', $data['member_id'], $data['kategori_keluhan'], $data['isi_keluhan']);
        if (mysqli_stmt_execute($stmt)) {
            $tiket_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            set_flash('success', 'Keluhan berhasil dikirim. Nomor tiket: ' . $tiket_id . '.');
            redirect(base_url('pages/complaints/detail.php?id=' . $tiket_id));
        }
        $error['umum'] = 'Gagal menyimpan keluhan: ' . mysqli_error($conn);
        mysqli_stmt_close($stmt);
    }
}

$page_title    = 'Catat keluhan';
$page_subtitle = 'Tiket tersimpan dengan status open dan belum memiliki PIC sampai di-assign admin.';
$active_menu   = 'complaints';
$page_actions  = '<a class="btn btn--secondary" href="' . e(base_url('pages/complaints/index.php')) . '">' . icon('arrow-left', 16) . ' Kembali</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<form class="card" method="post" action="" novalidate>
  <?= csrf_field() ?>
  <div class="card__head">
    <div>
      <h2>Detail keluhan</h2>
      <p>Semakin lengkap keterangan, semakin cepat PIC menindaklanjuti.</p>
    </div>
  </div>

  <div class="card__body">
    <?php if (!empty($error['umum'])): ?>
      <div class="flash flash--error mb-16"><span class="flash__icon"><?= icon('close', 16) ?></span><p><?= e($error['umum']) ?></p></div>
    <?php endif; ?>

    <div class="form-grid">
      <div class="field">
        <label for="member_id">Member <span aria-hidden="true">*</span></label>
        <select class="select<?= isset($error['member_id']) ? ' is-invalid' : '' ?>" id="member_id" name="member_id" required>
          <option value="">— Pilih member —</option>
          <?php while ($m = mysqli_fetch_assoc($members)): ?>
            <option value="<?= (int) $m['member_id'] ?>" <?= (int) $data['member_id'] === (int) $m['member_id'] ? 'selected' : '' ?>>
              <?= e($m['name']) ?> — <?= e($m['email']) ?>
            </option>
          <?php endwhile; ?>
        </select>
        <?php if (isset($error['member_id'])): ?>
          <span class="error"><?= e($error['member_id']) ?></span>
        <?php else: ?>
          <span class="hint">Hanya member aktif yang dapat mengajukan keluhan.</span>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="kategori_keluhan">Kategori keluhan <span aria-hidden="true">*</span></label>
        <select class="select<?= isset($error['kategori_keluhan']) ? ' is-invalid' : '' ?>" id="kategori_keluhan" name="kategori_keluhan" required>
          <option value="">— Pilih kategori —</option>
          <?php foreach ($kategori_pilihan as $k): ?>
            <option value="<?= e($k) ?>" <?= $data['kategori_keluhan'] === $k ? 'selected' : '' ?>><?= e($k) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($error['kategori_keluhan'])): ?><span class="error"><?= e($error['kategori_keluhan']) ?></span><?php endif; ?>
      </div>

      <div class="field field--full">
        <label for="isi_keluhan">Isi keluhan <span aria-hidden="true">*</span></label>
        <textarea class="textarea<?= isset($error['isi_keluhan']) ? ' is-invalid' : '' ?>" id="isi_keluhan" name="isi_keluhan"
                  maxlength="1000" required placeholder="Tuliskan kejadian, waktu, dan menu yang dikeluhkan."><?= e($data['isi_keluhan']) ?></textarea>
        <?php if (isset($error['isi_keluhan'])): ?>
          <span class="error"><?= e($error['isi_keluhan']) ?></span>
        <?php else: ?>
          <span class="hint">Minimal 10 karakter, maksimal 1000 karakter.</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Simpan keluhan</button>
      <a class="btn btn--secondary" href="<?= e(base_url('pages/complaints/index.php')) ?>">Batal</a>
      <span class="form-actions__note">Status awal: open &middot; PIC: belum di-assign</span>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
