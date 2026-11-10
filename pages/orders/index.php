<?php
/**
 * Pesan online: antrian pesanan (FIFO) + ubah status.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'Kasir']);

$status = $_GET['status'] ?? 'aktif';
$boleh_status = ['aktif', 'semua', 'menunggu', 'diproses', 'selesai', 'dibatalkan'];
if (!in_array($status, $boleh_status, true)) {
    $status = 'aktif';
}

$cari = trim($_GET['q'] ?? '');

$sql = "SELECT p.*, m.name AS nama_member, m.no_telp,
        (SELECT COUNT(*) FROM detail_pesanan d WHERE d.pemesanan_id = p.pemesanan_id) AS jml_item
    FROM pemesanan p
    JOIN member m ON m.member_id = p.member_id
    WHERE 1=1";
$params = [];
$types  = '';

if ($status === 'aktif') {
    $sql .= " AND p.status_pesanan IN ('menunggu','diproses')";
} elseif ($status !== 'semua') {
    $sql .= ' AND p.status_pesanan = ?';
    $params[] = $status;
    $types .= 's';
}

if ($cari !== '') {
    $sql .= ' AND (m.name LIKE ? OR CAST(p.pemesanan_id AS CHAR) LIKE ? OR CAST(p.nomor_antrian AS CHAR) LIKE ?)';
    $like = '%' . $cari . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

// FIFO: pesanan terlama diproses lebih dulu.
$sql .= ' ORDER BY p.waktu_pemesanan ASC';

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$pesanan = mysqli_stmt_get_result($stmt);

$hitung = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    SUM(status_pesanan = 'menunggu') AS menunggu,
    SUM(status_pesanan = 'diproses') AS diproses,
    SUM(status_pesanan = 'selesai') AS selesai,
    SUM(status_pesanan = 'dibatalkan') AS dibatalkan,
    COUNT(*) AS total FROM pemesanan"));

$hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) AS jml,
    COALESCE(SUM(CASE WHEN status_pesanan <> 'dibatalkan' THEN jumlah_bayar ELSE 0 END),0) AS total
    FROM pemesanan WHERE DATE(waktu_pemesanan) = CURDATE()"));

$page_title    = 'Pesan online';
$page_subtitle = 'Antrian pesanan diurutkan FIFO. Pesanan terlama muncul lebih dulu.';
$active_menu   = 'orders';
$page_actions  = '<a class="btn btn--primary" href="' . e(base_url('pages/orders/buat.php')) . '">' . icon('plus', 16) . ' Buat pesanan</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="kpi-grid">
  <article class="kpi kpi--accent">
    <p class="kpi__label"><?= icon('order', 15) ?> Pesanan hari ini</p>
    <p class="kpi__value"><?= (int) $hari_ini['jml'] ?></p>
    <p class="kpi__meta">Nilai tercatat <strong><?= e(rupiah($hari_ini['total'])) ?></strong></p>
  </article>
  <article class="kpi">
    <p class="kpi__label"><?= icon('clock', 15) ?> Menunggu</p>
    <p class="kpi__value"><?= (int) $hitung['menunggu'] ?></p>
    <p class="kpi__meta">Belum mulai dikerjakan dapur</p>
  </article>
  <article class="kpi">
    <p class="kpi__label"><?= icon('chart', 15) ?> Diproses</p>
    <p class="kpi__value"><?= (int) $hitung['diproses'] ?></p>
    <p class="kpi__meta">Sedang disiapkan</p>
  </article>
  <article class="kpi">
    <p class="kpi__label"><?= icon('check', 15) ?> Selesai</p>
    <p class="kpi__value"><?= (int) $hitung['selesai'] ?></p>
    <p class="kpi__meta">Dari <?= (int) $hitung['total'] ?> pesanan tercatat</p>
  </article>
</section>

<nav class="tabs" aria-label="Filter status pesanan">
  <?php
  $tab_list = [
      'aktif'      => ['Antrian aktif', (int) $hitung['menunggu'] + (int) $hitung['diproses']],
      'menunggu'   => ['Menunggu', (int) $hitung['menunggu']],
      'diproses'   => ['Diproses', (int) $hitung['diproses']],
      'selesai'    => ['Selesai', (int) $hitung['selesai']],
      'dibatalkan' => ['Dibatalkan', (int) $hitung['dibatalkan']],
      'semua'      => ['Semua', (int) $hitung['total']],
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
      <label class="visually-hidden" for="q">Cari pesanan</label>
      <input class="input" type="search" id="q" name="q" value="<?= e($cari) ?>" placeholder="Cari nama member, ID pesanan, atau nomor antrian">
    </div>
    <button class="btn btn--secondary" type="submit">Cari</button>
    <?php if ($cari !== ''): ?>
      <a class="btn btn--ghost" href="?status=<?= e($status) ?>">Reset</a>
    <?php endif; ?>
    <div class="toolbar__right">
      <span class="text-small text-muted"><?= mysqli_num_rows($pesanan) ?> pesanan</span>
    </div>
  </form>

  <div class="table-wrap">
    <?php if (mysqli_num_rows($pesanan) === 0): ?>
      <div class="empty">
        <div class="empty__icon"><?= icon('order', 20) ?></div>
        <h3><?= $status === 'aktif' ? 'Antrian sedang kosong' : 'Tidak ada pesanan pada filter ini' ?></h3>
        <p><?= $cari !== '' ? 'Tidak ada pesanan yang cocok dengan "' . e($cari) . '".' : ($status === 'aktif' ? 'Semua pesanan sudah diselesaikan atau dibatalkan. Pesanan baru dari member akan tampil di sini.' : 'Coba pilih tab status lain untuk melihat riwayat pesanan.') ?></p>
        <?php if ($status === 'aktif' && $cari === ''): ?>
          <a class="btn btn--primary" href="<?= e(base_url('pages/orders/buat.php')) ?>">Buat pesanan</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Antrian</th>
            <th>Member</th>
            <th>Waktu pesan</th>
            <th class="num">Item</th>
            <th class="num">Jumlah bayar</th>
            <th>Status</th>
            <th class="col-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($p = mysqli_fetch_assoc($pesanan)): ?>
            <tr>
              <td>
                <div class="cell-stack">
                  <span class="strong" style="font-size:15px">#<?= (int) $p['nomor_antrian'] ?></span>
                  <small>ID <?= (int) $p['pemesanan_id'] ?></small>
                </div>
              </td>
              <td>
                <div class="cell-stack">
                  <span><?= e($p['nama_member']) ?></span>
                  <small><?= e(tampil_telepon($p['no_telp'])) ?></small>
                </div>
              </td>
              <td class="muted nowrap">
                <?= e(tanggal_id($p['waktu_pemesanan'], true)) ?>
                <div class="text-small"><?= e(waktu_lalu($p['waktu_pemesanan'])) ?></div>
              </td>
              <td class="num"><?= (int) $p['jml_item'] ?></td>
              <td class="num strong"><?= e(rupiah($p['jumlah_bayar'])) ?></td>
              <td><?= badge_pesanan($p['status_pesanan']) ?></td>
              <td class="col-actions">
                <span class="btn-group">
                  <a class="btn btn--ghost btn--sm" href="<?= e(base_url('pages/orders/detail.php?id=' . (int) $p['pemesanan_id'])) ?>"><?= icon('eye', 15) ?> Detail</a>
                  <?php if ($p['status_pesanan'] === 'menunggu'): ?>
                    <form class="inline-form" method="post" action="<?= e(base_url('pages/orders/proses.php')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="ubah_status">
                      <input type="hidden" name="pemesanan_id" value="<?= (int) $p['pemesanan_id'] ?>">
                      <input type="hidden" name="status" value="diproses">
                      <input type="hidden" name="kembali" value="<?= e($status) ?>">
                      <button class="btn btn--teal btn--sm" type="submit">Proses</button>
                    </form>
                  <?php elseif ($p['status_pesanan'] === 'diproses'): ?>
                    <form class="inline-form" method="post" action="<?= e(base_url('pages/orders/proses.php')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="ubah_status">
                      <input type="hidden" name="pemesanan_id" value="<?= (int) $p['pemesanan_id'] ?>">
                      <input type="hidden" name="status" value="selesai">
                      <input type="hidden" name="kembali" value="<?= e($status) ?>">
                      <button class="btn btn--primary btn--sm" type="submit">Selesaikan</button>
                    </form>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
