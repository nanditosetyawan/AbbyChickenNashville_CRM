<?php
/**
 * Form edit voucher.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'Kasir']);

$id = (int) ($_GET['id'] ?? $_POST['voucher_id'] ?? 0);

$stmt = mysqli_prepare($conn, 'SELECT * FROM voucher WHERE voucher_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$voucher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$voucher) {
    set_flash('error', 'Voucher tidak ditemukan.');
    redirect(base_url('pages/voucher/index.php?tab=voucher'));
}

$error = [];
$data  = [
    'kode_voucher'   => $voucher['kode_voucher'],
    'potongan_harga' => number_format((int) $voucher['potongan_harga'], 0, ',', '.'),
    'valid_until'    => $voucher['valid_until'],
    'is_valid'       => (int) $voucher['is_valid'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data['kode_voucher']   = strtoupper(trim($_POST['kode_voucher'] ?? ''));
    $potongan_raw           = trim((string) ($_POST['potongan_harga'] ?? ''));
    $potongan_parsed        = parse_nominal_rupiah($potongan_raw);
    $data['potongan_harga'] = $potongan_raw;
    $data['valid_until']    = trim($_POST['valid_until'] ?? '');
    $data['is_valid']       = isset($_POST['is_valid']) ? 1 : 0;

    if ($data['kode_voucher'] === '') {
        $error['kode_voucher'] = 'Kode voucher wajib diisi.';
    } elseif (!preg_match('/^[A-Z0-9\-_]{3,50}$/', $data['kode_voucher'])) {
        $error['kode_voucher'] = 'Gunakan 3-50 karakter: huruf, angka, tanda hubung, atau garis bawah.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT voucher_id FROM voucher WHERE kode_voucher = ? AND voucher_id <> ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'si', $data['kode_voucher'], $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error['kode_voucher'] = 'Kode voucher sudah dipakai voucher lain.';
        }
        mysqli_stmt_close($stmt);
    }

    if ($potongan_raw === '') {
        $error['potongan_harga'] = 'Potongan harga wajib diisi.';
    } elseif ($potongan_parsed === null) {
        $error['potongan_harga'] = 'Format nominal tidak valid. Contoh: 10000, 12.500, 10k, atau 12.5k.';
    } elseif ($potongan_parsed < 100) {
        $error['potongan_harga'] = 'Potongan harga minimal Rp 100.';
    } elseif ($potongan_parsed > 500000) {
        $error['potongan_harga'] = 'Potongan harga maksimal Rp 500.000.';
    }

    if ($data['valid_until'] === '' || !DateTime::createFromFormat('Y-m-d', $data['valid_until'])) {
        $error['valid_until'] = 'Tanggal berlaku wajib diisi dengan format benar.';
    }

    if (empty($error)) {
        $potongan_nominal = (float) $potongan_parsed;
        $stmt = mysqli_prepare($conn, 'UPDATE voucher SET kode_voucher = ?, potongan_harga = ?, valid_until = ?, is_valid = ? WHERE voucher_id = ?');
        mysqli_stmt_bind_param($stmt, 'sdsii', $data['kode_voucher'], $potongan_nominal, $data['valid_until'], $data['is_valid'], $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            set_flash('success', 'Voucher ' . $data['kode_voucher'] . ' (' . rupiah($potongan_nominal) . ') berhasil diperbarui.');
            redirect(base_url('pages/voucher/index.php?tab=voucher'));
        }
        $error['umum'] = 'Gagal memperbarui voucher: ' . mysqli_error($conn);
        mysqli_stmt_close($stmt);
    }
}

$page_title    = 'Edit voucher';
$page_subtitle = 'Perbarui ' . $voucher['kode_voucher'] . ' (ID ' . $id . ').';
$active_menu   = 'voucher';
$page_actions  = '<a class="btn btn--secondary" href="' . e(base_url('pages/voucher/index.php?tab=voucher')) . '">' . icon('arrow-left', 16) . ' Kembali</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<form class="card" method="post" action="" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="voucher_id" value="<?= (int) $id ?>">

  <div class="card__head">
    <div>
      <h2>Detail voucher</h2>
      <p>Dibuat <?= e(tanggal_id($voucher['created_at'])) ?><?= $voucher['deleted_at'] ? ' · dihapus ' . e(tanggal_id($voucher['deleted_at'])) : '' ?>.</p>
    </div>
  </div>

  <div class="card__body">
    <?php if (!empty($error['umum'])): ?>
      <div class="flash flash--error mb-16"><span class="flash__icon"><?= icon('close', 16) ?></span><p><?= e($error['umum']) ?></p></div>
    <?php endif; ?>

    <div class="form-grid">
      <div class="field">
        <label for="kode_voucher">Kode voucher <span aria-hidden="true">*</span></label>
        <input class="input<?= isset($error['kode_voucher']) ? ' is-invalid' : '' ?>" type="text" id="kode_voucher" name="kode_voucher"
               value="<?= e($data['kode_voucher']) ?>" maxlength="50" style="text-transform:uppercase" required>
        <?php if (isset($error['kode_voucher'])): ?><span class="error"><?= e($error['kode_voucher']) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label for="potongan_harga">Potongan harga (Rupiah) <span aria-hidden="true">*</span></label>
        <input class="input<?= isset($error['potongan_harga']) ? ' is-invalid' : '' ?>" type="text" inputmode="numeric" id="potongan_harga" name="potongan_harga"
               data-nominal-rupiah="potonganPreview" value="<?= e($data['potongan_harga']) ?>"
               placeholder="Contoh: 10000, 12.500, atau 10k" autocomplete="off" required>
        <?php if (isset($error['potongan_harga'])): ?>
          <span class="error"><?= e($error['potongan_harga']) ?></span>
        <?php else: ?>
          <span class="hint" id="potonganPreview">Bisa ketik angka asli (12500), bertitik (12.500), atau 10k.</span>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="valid_until">Berlaku sampai <span aria-hidden="true">*</span></label>
        <input class="input<?= isset($error['valid_until']) ? ' is-invalid' : '' ?>" type="date" id="valid_until" name="valid_until"
               value="<?= e($data['valid_until']) ?>" required>
        <?php if (isset($error['valid_until'])): ?><span class="error"><?= e($error['valid_until']) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label>Status validitas</label>
        <label class="switch" style="padding:8px 0">
          <input type="checkbox" name="is_valid" value="1" <?= (int) $data['is_valid'] === 1 ? 'checked' : '' ?>>
          <span class="switch__track" aria-hidden="true"></span>
          <span class="switch__label">Voucher dapat dipakai</span>
        </label>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Simpan perubahan</button>
      <a class="btn btn--secondary" href="<?= e(base_url('pages/voucher/index.php?tab=voucher')) ?>">Batal</a>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
