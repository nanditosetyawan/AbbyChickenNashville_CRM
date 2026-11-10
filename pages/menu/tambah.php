<?php
/**
 * Form tambah menu.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin']);

$error = [];
$data  = ['name' => '', 'kategori' => 'Makanan', 'harga' => '', 'status_ketersediaan' => 'tersedia'];

$kategori_umum = ['Makanan', 'Minuman', 'Snack'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data['name']     = trim($_POST['name'] ?? '');
    $data['kategori'] = trim($_POST['kategori'] ?? '');
    $data['harga']    = (int) preg_replace('/\D/', '', $_POST['harga'] ?? '');
    $data['status_ketersediaan'] = $_POST['status_ketersediaan'] ?? 'tersedia';

    if (!in_array($data['status_ketersediaan'], ['tersedia', 'tidak tersedia'], true)) {
        $data['status_ketersediaan'] = 'tersedia';
    }

    if ($data['name'] === '' || mb_strlen($data['name']) < 3) {
        $error['name'] = 'Nama menu minimal 3 karakter.';
    }
    if ($data['kategori'] === '') {
        $error['kategori'] = 'Kategori wajib diisi.';
    }
    if ($data['harga'] <= 0) {
        $error['harga'] = 'Harga harus lebih dari 0.';
    }

    if (empty($error)) {
        $stmt = mysqli_prepare($conn, 'INSERT INTO menu (name, kategori, harga, status_ketersediaan) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'ssds', $data['name'], $data['kategori'], $data['harga'], $data['status_ketersediaan']);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            set_flash('success', 'Menu ' . $data['name'] . ' berhasil ditambahkan.');
            redirect(base_url('pages/menu/index.php'));
        }
        $error['umum'] = 'Gagal menyimpan menu: ' . mysqli_error($conn);
        mysqli_stmt_close($stmt);
    }
}

$page_title    = 'Tambah menu';
$page_subtitle = 'Menu baru langsung masuk katalog pesan online sesuai status ketersediaan.';
$active_menu   = 'menu';
$page_actions  = '<a class="btn btn--secondary" href="' . e(base_url('pages/menu/index.php')) . '">' . icon('arrow-left', 16) . ' Kembali</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<form class="card" method="post" action="" novalidate>
  <?= csrf_field() ?>
  <div class="card__head">
    <div>
      <h2>Detail menu</h2>
      <p>Harga diisi nominal Rupiah tanpa titik.</p>
    </div>
  </div>

  <div class="card__body">
    <?php if (!empty($error['umum'])): ?>
      <div class="flash flash--error mb-16"><span class="flash__icon"><?= icon('close', 16) ?></span><p><?= e($error['umum']) ?></p></div>
    <?php endif; ?>

    <div class="form-grid">
      <div class="field field--full">
        <label for="name">Nama menu <span aria-hidden="true">*</span></label>
        <input class="input<?= isset($error['name']) ? ' is-invalid' : '' ?>" type="text" id="name" name="name"
               value="<?= e($data['name']) ?>" maxlength="100" required>
        <?php if (isset($error['name'])): ?><span class="error"><?= e($error['name']) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label for="kategori">Kategori <span aria-hidden="true">*</span></label>
        <input class="input<?= isset($error['kategori']) ? ' is-invalid' : '' ?>" type="text" id="kategori" name="kategori"
               list="kategoriList" value="<?= e($data['kategori']) ?>" maxlength="50" required>
        <datalist id="kategoriList">
          <?php foreach ($kategori_umum as $k): ?>
            <option value="<?= e($k) ?>"></option>
          <?php endforeach; ?>
        </datalist>
        <?php if (isset($error['kategori'])): ?>
          <span class="error"><?= e($error['kategori']) ?></span>
        <?php else: ?>
          <span class="hint">Gunakan kategori yang sudah ada agar katalog tetap rapi.</span>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="harga">Harga (Rupiah) <span aria-hidden="true">*</span></label>
        <input class="input<?= isset($error['harga']) ? ' is-invalid' : '' ?>" type="number" id="harga" name="harga"
               min="1" step="500" value="<?= $data['harga'] !== '' ? (int) $data['harga'] : '' ?>" required>
        <?php if (isset($error['harga'])): ?><span class="error"><?= e($error['harga']) ?></span><?php endif; ?>
      </div>

      <div class="field field--full">
        <label for="status_ketersediaan">Status ketersediaan</label>
        <select class="select" id="status_ketersediaan" name="status_ketersediaan">
          <option value="tersedia" <?= $data['status_ketersediaan'] === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
          <option value="tidak tersedia" <?= $data['status_ketersediaan'] === 'tidak tersedia' ? 'selected' : '' ?>>Tidak tersedia</option>
        </select>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Simpan menu</button>
      <a class="btn btn--secondary" href="<?= e(base_url('pages/menu/index.php')) ?>">Batal</a>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
