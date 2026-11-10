<?php
/**
 * Keluhan: daftar tiket, filter status, assign PIC.
 * PIC Keluhan hanya melihat tiket yang di-assign kepadanya.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'PIC Keluhan']);

$role = $_SESSION['role'];
$pegawai_id = (int) $_SESSION['pegawai_id'];

$status = $_GET['status'] ?? 'semua';
if (!in_array($status, ['semua', 'open', 'in_progress', 'closed'], true)) {
    $status = 'semua';
}

$cari = trim($_GET['q'] ?? '');

$sql = "SELECT t.*, m.name AS nama_member, m.email, pg.nama AS nama_pic
    FROM tiket t
    JOIN member m ON m.member_id = t.member_id
    LEFT JOIN pegawai pg ON pg.pegawai_id = t.pegawai_id
    WHERE 1=1";
$params = [];
$types  = '';

if ($role === 'PIC Keluhan') {
    $sql .= ' AND t.pegawai_id = ?';
    $params[] = $pegawai_id;
    $types .= 'i';
}

if ($status !== 'semua') {
    $sql .= ' AND t.status = ?';
    $params[] = $status;
    $types .= 's';
}

if ($cari !== '') {
    $sql .= ' AND (m.name LIKE ? OR t.kategori_keluhan LIKE ? OR CAST(t.tiket_id AS CHAR) LIKE ?)';
    $like = '%' . $cari . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

$sql .= " ORDER BY FIELD(t.status, 'open', 'in_progress', 'closed'), t.created_at ASC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$tiket_list = mysqli_stmt_get_result($stmt);

/* Hitung per status sesuai cakupan role */
$where_hitung = $role === 'PIC Keluhan' ? 'WHERE pegawai_id = ' . $pegawai_id : '';
$hitung = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    SUM(status = 'open') AS open,
    SUM(status = 'in_progress') AS in_progress,
    SUM(status = 'closed') AS closed,
    COUNT(*) AS total FROM tiket $where_hitung"));

/* Daftar PIC untuk dropdown assign */
$pic_list = [];
$q = mysqli_query($conn, "SELECT pegawai_id, nama FROM pegawai WHERE role = 'PIC Keluhan' ORDER BY nama");
while ($r = mysqli_fetch_assoc($q)) {
    $pic_list[] = $r;
}

$page_title    = 'Keluhan pelanggan';
$page_subtitle = $role === 'PIC Keluhan'
    ? 'Tiket yang di-assign kepada Anda. Perbarui status setelah keluhan ditangani.'
    : 'Tiket keluhan dari member, lengkap dengan penanggung jawab dan status penyelesaian.';
$active_menu   = 'complaints';
$page_actions  = '<a class="btn btn--primary" href="' . e(base_url('pages/complaints/tambah.php')) . '">' . icon('plus', 16) . ' Catat keluhan</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="kpi-grid">
  <article class="kpi">
    <p class="kpi__label"><?= icon('complaint', 15) ?> Open</p>
    <p class="kpi__value"><?= (int) $hitung['open'] ?></p>
    <p class="kpi__meta">Belum ada penanggung jawab</p>
  </article>
  <article class="kpi">
    <p class="kpi__label"><?= icon('clock', 15) ?> In progress</p>
    <p class="kpi__value"><?= (int) $hitung['in_progress'] ?></p>
    <p class="kpi__meta">Sedang ditangani PIC</p>
  </article>
  <article class="kpi">
    <p class="kpi__label"><?= icon('check', 15) ?> Closed</p>
    <p class="kpi__value"><?= (int) $hitung['closed'] ?></p>
    <p class="kpi__meta">Sudah diselesaikan</p>
  </article>
  <article class="kpi kpi--accent">
    <p class="kpi__label"><?= icon('chart', 15) ?> Total tiket</p>
    <p class="kpi__value"><?= (int) $hitung['total'] ?></p>
    <p class="kpi__meta"><?= $role === 'PIC Keluhan' ? 'Di-assign kepada Anda' : 'Seluruh tiket tercatat' ?></p>
  </article>
</section>

<nav class="tabs" aria-label="Filter status tiket">
  <?php
  $tab_list = [
      'semua'       => ['Semua', (int) $hitung['total']],
      'open'        => ['Open', (int) $hitung['open']],
      'in_progress' => ['In progress', (int) $hitung['in_progress']],
      'closed'      => ['Closed', (int) $hitung['closed']],
  ];
  foreach ($tab_list as $key => [$label, $jumlah]):
  ?>
    <a class="tab<?= $status === $key ? ' is-active' : '' ?>"
       href="?status=<?= e($key) ?><?= $cari !== '' ? '&q=' . urlencode($cari) : '' ?>">
      <?= e($label) ?> <span class="tab__count"><?= $jumlah ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<article class="card">
  <form class="toolbar" method="get" action="">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <div class="search">
      <?= icon('search', 16) ?>
      <label class="visually-hidden" for="q">Cari tiket</label>
      <input class="input" type="search" id="q" name="q" value="<?= e($cari) ?>" placeholder="Cari nama member, kategori, atau nomor tiket">
    </div>
    <button class="btn btn--secondary" type="submit">Cari</button>
    <?php if ($cari !== ''): ?>
      <a class="btn btn--ghost" href="?status=<?= e($status) ?>">Reset</a>
    <?php endif; ?>
    <div class="toolbar__right">
      <span class="text-small text-muted"><?= mysqli_num_rows($tiket_list) ?> tiket</span>
    </div>
  </form>

  <div class="table-wrap">
    <?php if (mysqli_num_rows($tiket_list) === 0): ?>
      <div class="empty">
        <div class="empty__icon"><?= icon('complaint', 20) ?></div>
        <h3>Tidak ada tiket pada filter ini</h3>
        <p><?= $cari !== '' ? 'Tidak ada tiket yang cocok dengan "' . e($cari) . '".' : ($role === 'PIC Keluhan' ? 'Belum ada tiket yang di-assign kepada Anda. Admin akan meng-assign tiket keluhan baru.' : 'Belum ada keluhan tercatat. Tiket baru dari member akan tampil di sini.') ?></p>
        <?php if ($cari === '' && $role === 'Admin'): ?>
          <a class="btn btn--primary" href="<?= e(base_url('pages/complaints/tambah.php')) ?>">Catat keluhan</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Tiket</th>
            <th>Member</th>
            <th>Keluhan</th>
            <th>PIC</th>
            <th>Dibuat</th>
            <th>Status</th>
            <th class="col-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($t = mysqli_fetch_assoc($tiket_list)): ?>
            <tr>
              <td class="strong nowrap">#<?= (int) $t['tiket_id'] ?></td>
              <td>
                <div class="cell-stack">
                  <span><?= e($t['nama_member']) ?></span>
                  <small><?= e($t['email']) ?></small>
                </div>
              </td>
              <td>
                <div class="cell-stack">
                  <span><?= e($t['kategori_keluhan']) ?></span>
                  <small><?= e(potong($t['isi_keluhan'], 70)) ?></small>
                </div>
              </td>
              <td>
                <?php if ($t['nama_pic']): ?>
                  <?= e($t['nama_pic']) ?>
                <?php else: ?>
                  <span class="badge badge--warning">Belum di-assign</span>
                <?php endif; ?>
              </td>
              <td class="muted nowrap">
                <?= e(tanggal_id($t['created_at'])) ?>
                <div class="text-small"><?= e(waktu_lalu($t['created_at'])) ?></div>
              </td>
              <td><?= badge_tiket($t['status']) ?></td>
              <td class="col-actions">
                <span class="btn-group">
                  <a class="btn btn--ghost btn--sm" href="<?= e(base_url('pages/complaints/detail.php?id=' . (int) $t['tiket_id'])) ?>"><?= icon('eye', 15) ?> Detail</a>
                  <?php if ($role === 'Admin' && $t['status'] === 'open' && !empty($pic_list)): ?>
                    <button class="btn btn--teal btn--sm" type="button"
                            data-modal-open="#modalAssign"
                            data-tiket-id="<?= (int) $t['tiket_id'] ?>"
                            data-tiket-member="<?= e($t['nama_member']) ?>">
                      Assign PIC
                    </button>
                  <?php endif; ?>
                </span>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</article>

<?php if ($role === 'Admin'): ?>
<dialog class="modal" id="modalAssign" aria-labelledby="judulAssign">
  <form method="post" action="<?= e(base_url('pages/complaints/proses.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="assign">
    <input type="hidden" name="tiket_id" value="" data-input="tiketId">

    <div class="modal__head">
      <h2 id="judulAssign">Assign tiket ke PIC</h2>
      <p>Tiket dari <strong data-slot="tiketMember">member</strong> akan berstatus in progress setelah di-assign.</p>
    </div>

    <div class="modal__body">
      <?php if (empty($pic_list)): ?>
        <p class="text-small text-muted">Belum ada pegawai dengan role PIC Keluhan. Tambahkan pegawai terlebih dahulu melalui menu Pegawai.</p>
      <?php else: ?>
        <div class="field">
          <label for="pic_id">Penanggung jawab</label>
          <select class="select" id="pic_id" name="pegawai_id" required>
            <?php foreach ($pic_list as $pic): ?>
              <option value="<?= (int) $pic['pegawai_id'] ?>"><?= e($pic['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
    </div>

    <div class="modal__foot">
      <button class="btn btn--secondary" type="button" data-modal-close>Batal</button>
      <button class="btn btn--primary" type="submit" <?= empty($pic_list) ? 'disabled' : '' ?>>Assign tiket</button>
    </div>
  </form>
</dialog>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
