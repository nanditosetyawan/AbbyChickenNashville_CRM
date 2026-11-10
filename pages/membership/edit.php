<?php
/**
 * Form edit member.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin']);

$id = (int) ($_GET['id'] ?? $_POST['member_id'] ?? 0);

$stmt = mysqli_prepare($conn, 'SELECT * FROM member WHERE member_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$member) {
    set_flash('error', 'Member tidak ditemukan.');
    redirect(base_url('pages/membership/index.php'));
}

$telp_tersensor = tampil_telepon($member['no_telp']);
$telp_simpan    = $member['no_telp'];

$error = [];
$data  = [
    'name'          => $member['name'],
    'email'         => $member['email'],
    'no_telp'       => $telp_tersensor,
    'member_status' => $member['member_status'],
    'total_poin'    => $member['total_poin'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data['name']          = trim($_POST['name'] ?? '');
    $data['email']         = trim($_POST['email'] ?? '');
    $input_telp_raw        = trim((string) ($_POST['no_telp'] ?? ''));
    $data['member_status'] = $_POST['member_status'] ?? 'aktif';
    $data['total_poin']    = max(0, (int) ($_POST['total_poin'] ?? 0));

    if ($telp_tersensor !== '' && $input_telp_raw === $telp_tersensor) {
        // Admin tidak mengganti nomor: pertahankan nomor asli di database.
        $telp_simpan     = $member['no_telp'];
        $data['no_telp'] = $telp_tersensor;
    } elseif (strpos($input_telp_raw, '*') !== false) {
        $telp_simpan      = $member['no_telp'];
        $data['no_telp']  = $input_telp_raw;
        $error['no_telp'] = 'Ketik 10-15 digit nomor baru tanpa tanda bintang (*) bila ingin mengganti nomor.';
    } else {
        $telp_baru       = preg_replace('/\D/', '', $input_telp_raw);
        $telp_simpan     = $telp_baru;
        $data['no_telp'] = $telp_baru;
    }

    if (!in_array($data['member_status'], ['aktif', 'nonaktif'], true)) {
        $data['member_status'] = 'aktif';
    }

    if ($data['name'] === '' || mb_strlen($data['name']) < 3) {
        $error['name'] = 'Nama minimal 3 karakter.';
    }

    if ($data['email'] === '') {
        $error['email'] = 'Email wajib diisi.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error['email'] = 'Format email tidak valid.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT member_id FROM member WHERE email = ? AND member_id <> ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'si', $data['email'], $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error['email'] = 'Email sudah dipakai member lain.';
        }
        mysqli_stmt_close($stmt);
    }

    if (!isset($error['no_telp']) && ($telp_simpan === '' || strlen($telp_simpan) < 10 || strlen($telp_simpan) > 15)) {
        $error['no_telp'] = 'Nomor telepon harus 10-15 digit angka.';
    }

    if (empty($error)) {
        $stmt = mysqli_prepare($conn, 'UPDATE member SET name = ?, email = ?, no_telp = ?, member_status = ?, total_poin = ? WHERE member_id = ?');
        mysqli_stmt_bind_param($stmt, 'ssssii', $data['name'], $data['email'], $telp_simpan, $data['member_status'], $data['total_poin'], $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            set_flash('success', 'Data member berhasil diperbarui.');
            redirect(base_url('pages/membership/detail.php?id=' . $id));
        }
        $error['umum'] = 'Gagal memperbarui data: ' . mysqli_error($conn);
        mysqli_stmt_close($stmt);
    }
}

$page_title    = 'Edit member';
$page_subtitle = 'Perbarui data ' . $member['name'] . ' (member #' . $id . ').';
$active_menu   = 'membership';
$page_actions  = '<a class="btn btn--secondary" href="' . e(base_url('pages/membership/detail.php?id=' . $id)) . '">' . icon('arrow-left', 16) . ' Kembali ke detail</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<form class="card" method="post" action="" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="member_id" value="<?= (int) $id ?>">

  <div class="card__head">
    <div>
      <h2>Data member</h2>
      <p>Bergabung sejak <?= e(tanggal_id($member['tgl_join'])) ?>.</p>
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
               value="<?= e($data['email']) ?>" maxlength="100" required>
        <?php if (isset($error['email'])): ?><span class="error"><?= e($error['email']) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label for="no_telp">Nomor telepon <span aria-hidden="true">*</span></label>
        <input class="input<?= isset($error['no_telp']) ? ' is-invalid' : '' ?>" type="tel" id="no_telp" name="no_telp"
               value="<?= e($data['no_telp']) ?>" inputmode="numeric" maxlength="15" autocomplete="off" required>
        <?php if (isset($error['no_telp'])): ?>
          <span class="error"><?= e($error['no_telp']) ?></span>
        <?php else: ?>
          <span class="hint">Disensor untuk privasi. Ketik 10-15 digit nomor baru bila ingin mengganti.</span>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="member_status">Status keanggotaan</label>
        <select class="select" id="member_status" name="member_status">
          <option value="aktif" <?= $data['member_status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
          <option value="nonaktif" <?= $data['member_status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
        <span class="hint">Member nonaktif tidak dapat mengirim klaim review.</span>
      </div>

      <div class="field">
        <label for="total_poin">Total poin</label>
        <input class="input" type="number" id="total_poin" name="total_poin" min="0" step="1"
               value="<?= (int) $data['total_poin'] ?>">
        <span class="hint">Poin bertambah otomatis saat pesanan berstatus selesai.</span>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Simpan perubahan</button>
      <a class="btn btn--secondary" href="<?= e(base_url('pages/membership/index.php')) ?>">Batal</a>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
