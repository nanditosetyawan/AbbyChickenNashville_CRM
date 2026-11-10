<?php
/**
 * Form edit menu.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin']);

$id = (int) ($_GET['id'] ?? $_POST['menu_id'] ?? 0);

$stmt = mysqli_prepare($conn, 'SELECT * FROM menu WHERE menu_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$menu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$menu) {
    set_flash('error', 'Menu tidak ditemukan.');
    redirect(base_url('pages/menu/index.php'));
}

$error = [];
$data  = [
    'name'               => $menu['name'],
    'kategori'           => $menu['kategori'],
    'harga'              => (int) $menu['harga'],
    'status_ketersediaan' => $menu['status_ketersediaan'],
];

$kategori_umum = [];
$q = mysqli_query($conn, 'SELECT DISTINCT kategori FROM menu ORDER BY kategori');
while ($r = mysqli_fetch_assoc($q)) {
    $kategori_umum[] = $r['kategori'];
}

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
        $stmt = mysqli_prepare($conn, 'UPDATE menu SET name = ?, kategori = ?, harga = ?, status_ketersediaan = ? WHERE menu_id = ?');
        mysqli_stmt_bind_param($stmt, 'ssdsi', $data['name'], $data['kategori'], $data['harga'], $data['status_ketersediaan'], $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            set_flash('success', 'Menu berhasil diperbarui.');
            redirect(base_url('pages/menu/index.php'));
        }
        $error['umum'] = 'Gagal memperbarui menu: ' . mysqli_error($conn);
        mysqli_stmt_close($stmt);
    }
}

$page_title    = 'Edit menu';
$page_subtitle = 'Perbarui detail dan ketersediaan ' . $menu['name'] . '.';
$active_menu   = 'menu';
$page_actions  = '<a class="btn btn--secondary" href="' . e(base_url('pages/menu/index.php')) . '">' . icon('arrow-left', 16) . ' Kembali</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<form class="card" method="post" action="" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="menu_id" value="<?= (int) $id ?>">

  <div class="card__head">
    <div>
      <h2>Detail menu</h2>
      <p>Terakhir diperbarui <?= e(tanggal_id($menu['updated_at'], true)) ?>.</p>
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
        <?php if (isset($error['kategori'])): ?><span class="error"><?= e($error['kategori']) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label for="harga">Harga (Rupiah) <span aria-hidden="true">*</span></label>
        <input class="input<?= isset($error['harga']) ? ' is-invalid' : '' ?>" type="number" id="harga" name="harga"
               min="1" step="500" value="<?= (int) $data['harga'] ?>" required>
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
      <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Simpan perubahan</button>
      <a class="btn btn--secondary" href="<?= e(base_url('pages/menu/index.php')) ?>">Batal</a>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
