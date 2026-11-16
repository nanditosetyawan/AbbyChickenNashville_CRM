<?php
/**
 * Stok & menu: daftar menu, toggle ketersediaan via AJAX, CRUD.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'Kasir']);

$cari     = trim($_GET['q'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');

$sql = 'SELECT * FROM menu WHERE 1=1';
$params = [];
$types  = '';

if ($cari !== '') {
    $sql .= ' AND name LIKE ?';
    $params[] = '%' . $cari . '%';
    $types .= 's';
}
if ($kategori !== '') {
    $sql .= ' AND kategori = ?';
    $params[] = $kategori;
    $types .= 's';
}
$sql .= ' ORDER BY kategori ASC, name ASC';

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$menu_list = mysqli_stmt_get_result($stmt);

$daftar_kategori = [];
$q = mysqli_query($conn, 'SELECT DISTINCT kategori FROM menu ORDER BY kategori');
while ($r = mysqli_fetch_assoc($q)) {
    $daftar_kategori[] = $r['kategori'];
}

$ringkas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    SUM(status_ketersediaan = 'tersedia') AS tersedia,
    SUM(status_ketersediaan = 'tidak tersedia') AS habis,
    COUNT(*) AS total FROM menu"));

$refill_card_info = get_refill_nasi_info();
$role_pegawai     = $_SESSION['role'] ?? '';

$page_title    = 'Stok & menu';
$page_subtitle = 'Ubah ketersediaan menu secara langsung. Perubahan tersimpan tanpa memuat ulang halaman.';
$active_menu   = 'menu';
$page_actions  = ($role_pegawai === 'Admin') ? '<a class="btn btn--primary" href="' . e(base_url('pages/menu/tambah.php')) . '">' . icon('plus', 16) . ' Tambah menu</a>' : '';

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="kpi-grid">
  <article class="kpi">
    <p class="kpi__label"><?= icon('menu', 15) ?> Total menu</p>
    <p class="kpi__value"><?= (int) $ringkas['total'] ?></p>
    <p class="kpi__meta">Tersebar di <?= count($daftar_kategori) ?> kategori</p>
  </article>
  <article class="kpi">
    <p class="kpi__label"><?= icon('check', 15) ?> Tersedia</p>
    <p class="kpi__value"><?= (int) $ringkas['tersedia'] ?></p>
    <p class="kpi__meta">Tampil di katalog pesan online</p>
  </article>
  <article class="kpi">
    <p class="kpi__label"><?= icon('close', 15) ?> Tidak tersedia</p>
    <p class="kpi__value"><?= (int) $ringkas['habis'] ?></p>
    <p class="kpi__meta">Ditandai habis pada katalog member</p>
  </article>
  <?php require __DIR__ . '/../../includes/refill_card.php'; ?>
</section>

<article class="card">
  <form class="toolbar" method="get" action="">
    <div class="search">
      <?= icon('search', 16) ?>
      <label class="visually-hidden" for="q">Cari menu</label>
      <input class="input" type="search" id="q" name="q" value="<?= e($cari) ?>" placeholder="Cari nama menu">
    </div>
    <label class="visually-hidden" for="kategori">Filter kategori</label>
    <select class="select" id="kategori" name="kategori" data-autosubmit style="width:auto">
      <option value="">Semua kategori</option>
      <?php foreach ($daftar_kategori as $k): ?>
        <option value="<?= e($k) ?>" <?= $kategori === $k ? 'selected' : '' ?>><?= e($k) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn--secondary" type="submit">Terapkan</button>
    <?php if ($cari !== '' || $kategori !== ''): ?>
      <a class="btn btn--ghost" href="<?= e(base_url('pages/menu/index.php')) ?>">Reset</a>
    <?php endif; ?>
    <div class="toolbar__right">
      <span class="text-small text-muted" id="statusToggle" role="status" aria-live="polite"></span>
    </div>
  </form>

  <div class="table-wrap">
    <?php if (mysqli_num_rows($menu_list) === 0): ?>
      <div class="empty">
        <div class="empty__icon"><?= icon('menu', 20) ?></div>
        <h3>Tidak ada menu yang cocok</h3>
        <p>Ubah kata kunci pencarian atau tambahkan menu baru ke katalog.</p>
        <a class="btn btn--primary" href="<?= e(base_url('pages/menu/tambah.php')) ?>">Tambah menu</a>
      </div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Menu</th>
            <th>Kategori</th>
            <th class="num">Harga</th>
            <th>Ketersediaan</th>
            <th>Diperbarui</th>
            <th class="col-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($m = mysqli_fetch_assoc($menu_list)): ?>
            <tr>
              <td>
                <div class="cell-stack">
                  <span class="strong"><?= e($m['name']) ?></span>
                  <small>ID menu <?= (int) $m['menu_id'] ?></small>
                </div>
              </td>
              <td><span class="badge badge--neutral"><?= e($m['kategori']) ?></span></td>
              <td class="num"><?= e(rupiah($m['harga'])) ?></td>
              <td>
                <label class="switch">
                  <input type="checkbox"
                         class="js-toggle-stok"
                         data-id="<?= (int) $m['menu_id'] ?>"
                         <?= $m['status_ketersediaan'] === 'tersedia' ? 'checked' : '' ?>
                         aria-label="Ketersediaan <?= e($m['name']) ?>">
                  <span class="switch__track" aria-hidden="true"></span>
                  <span class="switch__label"><?= $m['status_ketersediaan'] === 'tersedia' ? 'Tersedia' : 'Habis' ?></span>
                </label>
              </td>
              <td class="muted nowrap" data-updated="<?= (int) $m['menu_id'] ?>"><?= e(waktu_lalu($m['updated_at'])) ?></td>
              <td class="col-actions">
                <?php if ($role_pegawai === 'Admin'): ?>
                  <span class="btn-group">
                    <a class="btn btn--ghost btn--sm" href="<?= e(base_url('pages/menu/edit.php?id=' . (int) $m['menu_id'])) ?>"><?= icon('edit', 15) ?> Edit</a>
                    <form class="inline-form" method="post" action="<?= e(base_url('pages/menu/proses.php')) ?>"
                          data-confirm="Hapus menu <?= e($m['name']) ?>? Menu yang pernah dipesan tidak bisa dihapus.">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="hapus">
                      <input type="hidden" name="menu_id" value="<?= (int) $m['menu_id'] ?>">
                      <button class="btn btn--danger btn--sm" type="submit"><?= icon('trash', 15) ?> Hapus</button>
                    </form>
                  </span>
                <?php else: ?>
                  <small class="muted">Khusus Admin</small>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</article>

<input type="hidden" id="csrfToken" value="<?= e(csrf_token()) ?>" data-api="<?= e(base_url('api/update_status.php')) ?>">
<script src="<?= e(base_url('assets/js/refill_control.js')) ?>"></script>

<?php
$page_script = 'assets/js/menu.js';
require_once __DIR__ . '/../../includes/footer.php';
?>
