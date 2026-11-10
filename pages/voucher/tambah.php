<?php
/**
 * Form buat voucher.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'Kasir']);

$error = [];
$data  = ['kode_voucher' => '', 'potongan_harga' => '', 'valid_until' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data['kode_voucher']   = strtoupper(trim($_POST['kode_voucher'] ?? ''));
    $potongan_raw           = trim((string) ($_POST['potongan_harga'] ?? ''));
    $potongan_parsed        = parse_nominal_rupiah($potongan_raw);
    $data['potongan_harga'] = $potongan_raw;
    $data['valid_until']    = trim($_POST['valid_until'] ?? '');

    if ($data['kode_voucher'] === '') {
        $error['kode_voucher'] = 'Kode voucher wajib diisi.';
    } elseif (!preg_match('/^[A-Z0-9\-_]{3,50}$/', $data['kode_voucher'])) {
        $error['kode_voucher'] = 'Gunakan 3-50 karakter: huruf, angka, tanda hubung, atau garis bawah.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT voucher_id FROM voucher WHERE kode_voucher = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $data['kode_voucher']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error['kode_voucher'] = 'Kode voucher sudah dipakai. Gunakan kode lain.';
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

    if ($data['valid_until'] === '') {
        $error['valid_until'] = 'Tanggal berlaku wajib diisi.';
    } elseif (!DateTime::createFromFormat('Y-m-d', $data['valid_until'])) {
        $error['valid_until'] = 'Format tanggal tidak valid.';
    } elseif (strtotime($data['valid_until']) < strtotime(date('Y-m-d'))) {
        $error['valid_until'] = 'Tanggal berlaku tidak boleh di masa lalu.';
    }

    if (empty($error)) {
        $potongan_nominal = (float) $potongan_parsed;
        $stmt = mysqli_prepare($conn, 'INSERT INTO voucher (kode_voucher, is_valid, potongan_harga, valid_until, deleted_at) VALUES (?, 1, ?, ?, NULL)');
        mysqli_stmt_bind_param($stmt, 'sds', $data['kode_voucher'], $potongan_nominal, $data['valid_until']);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            set_flash('success', 'Voucher ' . $data['kode_voucher'] . ' (' . rupiah($potongan_nominal) . ') berhasil dibuat.');
            redirect(base_url('pages/voucher/index.php?tab=voucher'));
        }
        $error['umum'] = 'Gagal menyimpan voucher: ' . mysqli_error($conn);
        mysqli_stmt_close($stmt);
    }
}

$page_title    = 'Buat voucher';
$page_subtitle = 'Voucher aktif dapat diberikan pada klaim ulasan dan dipakai saat pemesanan.';
$active_menu   = 'voucher';
$page_actions  = '<a class="btn btn--secondary" href="' . e(base_url('pages/voucher/index.php?tab=voucher')) . '">' . icon('arrow-left', 16) . ' Kembali</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<form class="card" method="post" action="" novalidate>
  <?= csrf_field() ?>
  <div class="card__head">
    <div>
      <h2>Detail voucher</h2>
      <p>Kode voucher otomatis diubah menjadi huruf kapital.</p>
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
               value="<?= e($data['kode_voucher']) ?>" maxlength="50" placeholder="REVIEWHOT50" style="text-transform:uppercase" required>
        <?php if (isset($error['kode_voucher'])): ?>
          <span class="error"><?= e($error['kode_voucher']) ?></span>
        <?php else: ?>
          <span class="hint">Harus unik, tanpa spasi.</span>
        <?php endif; ?>
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
               min="<?= date('Y-m-d') ?>" value="<?= e($data['valid_until']) ?>" required>
        <?php if (isset($error['valid_until'])): ?>
          <span class="error"><?= e($error['valid_until']) ?></span>
        <?php else: ?>
          <span class="hint">Voucher otomatis tidak berlaku setelah tanggal ini.</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Simpan voucher</button>
      <a class="btn btn--secondary" href="<?= e(base_url('pages/voucher/index.php?tab=voucher')) ?>">Batal</a>
      <span class="form-actions__note">Status awal: aktif &middot; belum dihapus</span>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
