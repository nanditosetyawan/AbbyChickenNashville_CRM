<?php
/**
 * Membership: daftar member + pencarian + filter status.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin']);

$cari   = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'semua';
if (!in_array($status, ['semua', 'aktif', 'nonaktif'], true)) {
    $status = 'semua';
}

$sql = "SELECT m.*,
        (SELECT COUNT(*) FROM pemesanan p WHERE p.member_id = m.member_id) AS jml_pesanan,
        (SELECT COALESCE(SUM(jumlah_bayar),0) FROM pemesanan p WHERE p.member_id = m.member_id AND p.status_pesanan <> 'dibatalkan') AS total_belanja
    FROM member m WHERE 1=1";
$params = [];
$types  = '';

if ($cari !== '') {
    $sql .= ' AND (m.name LIKE ? OR m.email LIKE ? OR m.no_telp LIKE ?)';
    $like = '%' . $cari . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($status !== 'semua') {
    $sql .= ' AND m.member_status = ?';
    $params[] = $status;
    $types .= 's';
}

$sql .= ' ORDER BY m.tgl_join DESC, m.member_id DESC';

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$members = mysqli_stmt_get_result($stmt);

$total_aktif = (int) (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM member WHERE member_status='aktif'"))['n'] ?? 0);
$total_semua = (int) (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM member"))['n'] ?? 0);
$total_nonaktif = $total_semua - $total_aktif;

$page_title    = 'Membership';
$page_subtitle = 'Data pelanggan terdaftar, poin loyalty, dan riwayat belanja.';
$active_menu   = 'membership';
$page_actions  = '<a class="btn btn--primary" href="' . e(base_url('pages/membership/tambah.php')) . '">' . icon('plus', 16) . ' Daftarkan member</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<nav class="tabs" aria-label="Filter status member">
  <a class="tab<?= $status === 'semua' ? ' is-active' : '' ?>" href="?status=semua<?= $cari !== '' ? '&q=' . urlencode($cari) : '' ?>">Semua <span class="tab__count"><?= $total_semua ?></span></a>
  <a class="tab<?= $status === 'aktif' ? ' is-active' : '' ?>" href="?status=aktif<?= $cari !== '' ? '&q=' . urlencode($cari) : '' ?>">Aktif <span class="tab__count"><?= $total_aktif ?></span></a>
  <a class="tab<?= $status === 'nonaktif' ? ' is-active' : '' ?>" href="?status=nonaktif<?= $cari !== '' ? '&q=' . urlencode($cari) : '' ?>">Nonaktif <span class="tab__count"><?= $total_nonaktif ?></span></a>
</nav>

<article class="card">
  <form class="toolbar" method="get" action="">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <div class="search">
      <?= icon('search', 16) ?>
      <label class="visually-hidden" for="q">Cari member</label>
      <input class="input" type="search" id="q" name="q" value="<?= e($cari) ?>"
             placeholder="Cari nama, email, atau nomor telepon">
    </div>
    <button class="btn btn--secondary" type="submit">Cari</button>
    <?php if ($cari !== ''): ?>
      <a class="btn btn--ghost" href="?status=<?= e($status) ?>">Reset</a>
    <?php endif; ?>
    <div class="toolbar__right">
      <span class="text-small text-muted"><?= mysqli_num_rows($members) ?> member ditampilkan</span>
    </div>
  </form>

  <div class="table-wrap">
    <?php if (mysqli_num_rows($members) === 0): ?>
      <div class="empty">
        <div class="empty__icon"><?= icon('member', 20) ?></div>
        <h3>Data member tidak ditemukan</h3>
        <p><?= $cari !== '' ? 'Tidak ada member yang cocok dengan kata kunci "' . e($cari) . '". Coba kata kunci lain atau reset pencarian.' : 'Belum ada member terdaftar. Daftarkan member pertama untuk mulai mengumpulkan data pelanggan.' ?></p>
        <?php if ($cari === ''): ?>
          <a class="btn btn--primary" href="<?= e(base_url('pages/membership/tambah.php')) ?>">Daftarkan member</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Kontak</th>
            <th>Tgl join</th>
            <th class="num">Poin</th>
            <th class="num">Total belanja</th>
            <th>Status</th>
            <th class="col-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($m = mysqli_fetch_assoc($members)): ?>
            <tr>
              <td class="muted">#<?= (int) $m['member_id'] ?></td>
              <td>
                <div class="cell-stack">
                  <span class="strong"><?= e($m['name']) ?></span>
                  <small><?= (int) $m['jml_pesanan'] ?> pesanan</small>
                </div>
              </td>
              <td>
                <div class="cell-stack">
                  <span><?= e($m['email']) ?></span>
                  <small><?= e(tampil_telepon($m['no_telp'])) ?></small>
                </div>
              </td>
              <td class="muted nowrap"><?= e(tanggal_id($m['tgl_join'])) ?></td>
              <td class="num"><?= number_format((int) $m['total_poin'], 0, ',', '.') ?></td>
              <td class="num"><?= e(rupiah($m['total_belanja'])) ?></td>
              <td><?= badge_member($m['member_status']) ?></td>
              <td class="col-actions">
                <span class="btn-group">
                  <a class="btn btn--ghost btn--sm" href="<?= e(base_url('pages/membership/detail.php?id=' . (int) $m['member_id'])) ?>" title="Lihat detail dan riwayat"><?= icon('eye', 15) ?> Detail</a>
                  <a class="btn btn--ghost btn--sm" href="<?= e(base_url('pages/membership/edit.php?id=' . (int) $m['member_id'])) ?>"><?= icon('edit', 15) ?> Edit</a>
                  <form class="inline-form" method="post" action="<?= e(base_url('pages/membership/proses.php')) ?>"
                        data-confirm="Hapus member <?= e($m['name']) ?>? Riwayat pesanan, tiket, dan klaim review member ini ikut terhapus.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="member_id" value="<?= (int) $m['member_id'] ?>">
                    <button class="btn btn--danger btn--sm" type="submit"><?= icon('trash', 15) ?> Hapus</button>
                  </form>
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
