<?php
/**
 * Form tambah member.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin']);

$error = [];
$data  = ['name' => '', 'email' => '', 'no_telp' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data['name']    = trim($_POST['name'] ?? '');
    $data['email']   = trim($_POST['email'] ?? '');
    $data['no_telp'] = preg_replace('/\D/', '', $_POST['no_telp'] ?? '');

    if ($data['name'] === '' || mb_strlen($data['name']) < 3) {
        $error['name'] = 'Nama minimal 3 karakter.';
    }
    if ($data['email'] === '') {
        $error['email'] = 'Email wajib diisi.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error['email'] = 'Format email tidak valid.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT member_id FROM member WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $data['email']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error['email'] = 'Email sudah terdaftar pada member lain.';
        }
        mysqli_stmt_close($stmt);
    }

    if ($data['no_telp'] === '') {
        $error['no_telp'] = 'Nomor telepon wajib diisi.';
    } elseif (strlen($data['no_telp']) < 10 || strlen($data['no_telp']) > 15) {
        $error['no_telp'] = 'Nomor telepon harus 10-15 digit angka.';
    }

    if (empty($error)) {
        $stmt = mysqli_prepare($conn, 'INSERT INTO member (email, no_telp, tgl_join, name, total_poin, member_status) VALUES (?, ?, CURDATE(), ?, 0, ?)');
        $status_awal = 'aktif';
        mysqli_stmt_bind_param($stmt, 'ssss', $data['email'], $data['no_telp'], $data['name'], $status_awal);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            set_flash('success', 'Member berhasil didaftarkan.');
            redirect(base_url('pages/membership/index.php'));
        }
        $error['umum'] = 'Gagal menyimpan data: ' . mysqli_error($conn);
        mysqli_stmt_close($stmt);
    }
}

$page_title    = 'Daftarkan member';
$page_subtitle = 'Member baru otomatis aktif dengan poin awal 0 dan tanggal join hari ini.';
$active_menu   = 'membership';
$page_actions  = '<a class="btn btn--secondary" href="' . e(base_url('pages/membership/index.php')) . '">' . icon('arrow-left', 16) . ' Kembali</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<form class="card" method="post" action="" novalidate>
  <?= csrf_field() ?>
  <div class="card__head">
    <div>
      <h2>Data member</h2>
      <p>Field bertanda wajib harus diisi lengkap.</p>
    </div>
  </div>

  <div class="card__body">
    <?php if (!empty($error['umum'])): ?>
      <div class="flash flash--error mb-16"><span class="flash__icon"><?= icon('close', 16) ?></span><p><?= e($error['umum']) ?></p></div>
    <?php endif; ?>

    <div class="form-grid">
      <div class="field field--full">
        <label for="name">Nama lengkap <span aria-hidden="true">*</span></label>
        <input class="input<?= isset($error['name']) ? ' is-invalid' : '' ?>" type="text" id="name" name="name"
               value="<?= e($data['name']) ?>" maxlength="100" required>
        <?php if (isset($error['name'])): ?><span class="error"><?= e($error['name']) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label for="email">Email <span aria-hidden="true">*</span></label>
        <input class="input<?= isset($error['email']) ? ' is-invalid' : '' ?>" type="email" id="email" name="email"
               value="<?= e($data['email']) ?>" maxlength="100" placeholder="nama@contoh.com" required>
        <?php if (isset($error['email'])): ?>
          <span class="error"><?= e($error['email']) ?></span>
        <?php else: ?>
          <span class="hint">Email harus unik, dipakai untuk mencari data member.</span>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="no_telp">Nomor telepon <span aria-hidden="true">*</span></label>
        <input class="input<?= isset($error['no_telp']) ? ' is-invalid' : '' ?>" type="tel" id="no_telp" name="no_telp"
               value="<?= e($data['no_telp']) ?>" inputmode="numeric" maxlength="15" placeholder="08xxxxxxxxxx" required>
        <?php if (isset($error['no_telp'])): ?>
          <span class="error"><?= e($error['no_telp']) ?></span>
        <?php else: ?>
          <span class="hint">10-15 digit angka, tanpa spasi atau tanda hubung.</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Simpan member</button>
      <a class="btn btn--secondary" href="<?= e(base_url('pages/membership/index.php')) ?>">Batal</a>
      <span class="form-actions__note">Poin awal 0 &middot; status aktif &middot; tanggal join hari ini</span>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
