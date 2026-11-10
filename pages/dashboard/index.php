<?php
/**
 * Dashboard: ringkasan KPI, tren 7 hari, komposisi kategori, aktivitas terbaru.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'Kasir', 'PIC Keluhan', 'Pimpinan']);

$role = $_SESSION['role'];

/* ---------------------------------------------------------------- KPI */
$kpi = [
    'member_aktif'  => 0,
    'member_baru'   => 0,
    'pesanan_hari'  => 0,
    'pendapatan'    => 0.0,
    'tiket_open'    => 0,
    'tiket_proses'  => 0,
    'menu_habis'    => 0,
    'menu_total'    => 0,
    'klaim_pending' => 0,
];

$q = mysqli_query($conn, "SELECT
    (SELECT COUNT(*) FROM member WHERE member_status = 'aktif') AS member_aktif,
    (SELECT COUNT(*) FROM member WHERE tgl_join >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS member_baru,
    (SELECT COUNT(*) FROM pemesanan WHERE DATE(waktu_pemesanan) = CURDATE() AND status_pesanan <> 'dibatalkan') AS pesanan_hari,
    (SELECT COALESCE(SUM(jumlah_bayar),0) FROM pemesanan WHERE DATE(waktu_pemesanan) = CURDATE() AND status_pesanan <> 'dibatalkan') AS pendapatan,
    (SELECT COUNT(*) FROM tiket WHERE status = 'open') AS tiket_open,
    (SELECT COUNT(*) FROM tiket WHERE status = 'in_progress') AS tiket_proses,
    (SELECT COUNT(*) FROM menu WHERE status_ketersediaan = 'tidak tersedia') AS menu_habis,
    (SELECT COUNT(*) FROM menu) AS menu_total,
    (SELECT COUNT(*) FROM klaim_review WHERE status_validasi = 'pending') AS klaim_pending
");
if ($q) {
    $kpi = array_merge($kpi, mysqli_fetch_assoc($q));
}

/* ------------------------------------------------- Tren 7 hari terakhir */
$tren = [];
for ($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    $tren[$tanggal] = ['label' => tanggal_id($tanggal), 'pendapatan' => 0.0, 'pesanan' => 0];
}

$stmt = mysqli_prepare($conn, "SELECT DATE(waktu_pemesanan) AS tgl,
        COUNT(*) AS jml,
        COALESCE(SUM(jumlah_bayar),0) AS total
    FROM pemesanan
    WHERE waktu_pemesanan >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      AND status_pesanan <> 'dibatalkan'
    GROUP BY DATE(waktu_pemesanan)");
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($hasil)) {
    if (isset($tren[$row['tgl']])) {
        $tren[$row['tgl']]['pendapatan'] = (float) $row['total'];
        $tren[$row['tgl']]['pesanan']    = (int) $row['jml'];
    }
}
mysqli_stmt_close($stmt);

$label_hari   = array_column($tren, 'label');
$data_pendapatan = array_column($tren, 'pendapatan');
$data_pesanan    = array_column($tren, 'pesanan');

/* --------------------------------------------- Komposisi kategori menu */
$kategori = [];
$q = mysqli_query($conn, "SELECT kategori, COUNT(*) AS jml FROM menu GROUP BY kategori ORDER BY jml DESC");
while ($row = mysqli_fetch_assoc($q)) {
    $kategori[$row['kategori']] = (int) $row['jml'];
}

/* ------------------------------------------ Pesanan terbaru (5 baris) */
$pesanan_terbaru = [];
$q = mysqli_query($conn, "SELECT p.pemesanan_id, p.nomor_antrian, p.jumlah_bayar, p.status_pesanan,
        p.waktu_pemesanan, m.name AS nama_member
    FROM pemesanan p
    JOIN member m ON m.member_id = p.member_id
    ORDER BY p.waktu_pemesanan DESC
    LIMIT 6");
while ($row = mysqli_fetch_assoc($q)) {
    $pesanan_terbaru[] = $row;
}

/* ------------------------------------------------- Aktivitas terbaru */
$aktivitas = [];
$q = mysqli_query($conn, "SELECT 'pesanan' AS jenis, p.pemesanan_id AS ref, p.waktu_pemesanan AS waktu,
        CONCAT('Pesanan #', p.pemesanan_id, ' dari ', m.name) AS teks,
        CONCAT('Antrian ', p.nomor_antrian, ' · ', FORMAT(p.jumlah_bayar, 0)) AS detail
    FROM pemesanan p JOIN member m ON m.member_id = p.member_id
    UNION ALL
    SELECT 'tiket', t.tiket_id, t.created_at,
        CONCAT('Tiket #', t.tiket_id, ' · ', t.kategori_keluhan),
        CONCAT('dari ', m.name)
    FROM tiket t JOIN member m ON m.member_id = t.member_id
    UNION ALL
    SELECT 'klaim', k.klaim_id, k.created_at,
        CONCAT('Klaim review #', k.klaim_id),
        CONCAT('dari ', m.name, ' · ', k.status_validasi)
    FROM klaim_review k JOIN member m ON m.member_id = k.member_id
    ORDER BY waktu DESC
    LIMIT 8");
while ($row = mysqli_fetch_assoc($q)) {
    $aktivitas[] = $row;
}

/* -------------------------------------------------- Tiket butuh tindakan */
$tiket_perhatian = [];
if ($role === 'PIC Keluhan') {
    $stmt = mysqli_prepare($conn, "SELECT t.tiket_id, t.kategori_keluhan, t.isi_keluhan, t.status, t.created_at, m.name
        FROM tiket t JOIN member m ON m.member_id = t.member_id
        WHERE t.pegawai_id = ? AND t.status <> 'closed'
        ORDER BY t.created_at ASC LIMIT 5");
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['pegawai_id']);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    $res = mysqli_query($conn, "SELECT t.tiket_id, t.kategori_keluhan, t.isi_keluhan, t.status, t.created_at, m.name
        FROM tiket t JOIN member m ON m.member_id = t.member_id
        WHERE t.status <> 'closed'
        ORDER BY FIELD(t.status, 'open', 'in_progress'), t.created_at ASC LIMIT 5");
}
while ($row = mysqli_fetch_assoc($res)) {
    $tiket_perhatian[] = $row;
}

/* ------------------------------------------------------------ Render */
$page_title    = 'Dashboard';
$page_subtitle = 'Ringkasan operasional AbbyChicken Nashville hari ini, ' . tanggal_id(date('Y-m-d'));
$active_menu   = 'dashboard';

if ($role === 'Admin') {
    $page_actions = '<a class="btn btn--secondary" href="' . e(base_url('pages/menu/index.php')) . '">' . icon('menu', 16) . ' Kelola stok</a>'
        . '<a class="btn btn--primary" href="' . e(base_url('pages/orders/buat.php')) . '">' . icon('plus', 16) . ' Buat pesanan</a>';
} elseif ($role === 'Kasir') {
    $page_actions = '<a class="btn btn--primary" href="' . e(base_url('pages/orders/buat.php')) . '">' . icon('plus', 16) . ' Buat pesanan</a>';
} elseif ($role === 'Pimpinan') {
    $page_actions = '';
} else {
    $page_actions = '<a class="btn btn--primary" href="' . e(base_url('pages/complaints/index.php')) . '">' . icon('complaint', 16) . ' Tiket saya</a>';
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="kpi-grid" aria-label="Indikator utama">
  <article class="kpi">
    <p class="kpi__label"><?= icon('member', 15) ?> Member aktif</p>
    <p class="kpi__value"><?= (int) $kpi['member_aktif'] ?></p>
    <p class="kpi__meta"><strong><?= (int) $kpi['member_baru'] ?></strong> bergabung 30 hari terakhir</p>
  </article>

  <article class="kpi kpi--accent">
    <p class="kpi__label"><?= icon('order', 15) ?> Pesanan hari ini</p>
    <p class="kpi__value"><?= (int) $kpi['pesanan_hari'] ?></p>
    <p class="kpi__meta">Pendapatan tercatat <strong><?= e(rupiah($kpi['pendapatan'])) ?></strong></p>
  </article>

  <article class="kpi">
    <p class="kpi__label"><?= icon('complaint', 15) ?> Tiket belum selesai</p>
    <p class="kpi__value"><?= (int) $kpi['tiket_open'] + (int) $kpi['tiket_proses'] ?></p>
    <p class="kpi__meta"><strong><?= (int) $kpi['tiket_open'] ?></strong> belum di-assign · <strong><?= (int) $kpi['tiket_proses'] ?></strong> dikerjakan</p>
  </article>

  <article class="kpi">
    <p class="kpi__label"><?= icon('menu', 15) ?> Menu tidak tersedia</p>
    <p class="kpi__value"><?= (int) $kpi['menu_habis'] ?> / <?= (int) $kpi['menu_total'] ?></p>
    <p class="kpi__meta"><?= (int) $kpi['menu_total'] - (int) $kpi['menu_habis'] ?> menu siap dijual hari ini</p>
  </article>
</section>

<section class="chart-grid">
  <article class="card">
    <div class="card__head">
      <div>
        <h2>Pendapatan dan pesanan 7 hari terakhir</h2>
        <p>Transaksi berstatus dibatalkan tidak dihitung.</p>
      </div>
    </div>
    <div class="card__body">
      <div class="chart-box">
        <canvas id="chartTren" role="img" aria-label="Grafik tren pendapatan dan jumlah pesanan tujuh hari terakhir"></canvas>
      </div>
      <div class="legend">
        <span class="legend__item"><span class="legend__swatch" style="background:#1A535C"></span> Pendapatan (Rp)</span>
        <span class="legend__item"><span class="legend__swatch" style="background:#FF6B35"></span> Jumlah pesanan</span>
      </div>
    </div>
  </article>

  <article class="card">
    <div class="card__head">
      <div>
        <h2>Komposisi menu per kategori</h2>
        <p>Total <?= (int) $kpi['menu_total'] ?> item terdaftar.</p>
      </div>
    </div>
    <div class="card__body">
      <div class="chart-box chart-box--sm">
        <canvas id="chartKategori" role="img" aria-label="Diagram donat komposisi menu per kategori"></canvas>
      </div>
      <div class="legend" id="legendKategori"></div>
    </div>
  </article>
</section>

<section class="split-grid">
  <div class="stack">
    <article class="card">
      <div class="card__head">
        <div>
          <h2>Pesanan terbaru</h2>
          <p>Enam pesanan terakhir yang masuk ke sistem.</p>
        </div>
        <?php if ($role !== 'Pimpinan'): ?>
          <div class="card__head-actions">
            <a class="link-quiet" href="<?= e(base_url('pages/orders/index.php')) ?>">Lihat antrian</a>
          </div>
        <?php endif; ?>
      </div>
      <div class="table-wrap">
        <?php if (empty($pesanan_terbaru)): ?>
          <div class="empty">
            <div class="empty__icon"><?= icon('order', 20) ?></div>
            <h3>Belum ada pesanan</h3>
            <p>Pesanan yang dibuat kasir akan muncul di sini.</p>
          </div>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th>Antrian</th>
                <th>Member</th>
                <th>Waktu</th>
                <th class="num">Jumlah bayar</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pesanan_terbaru as $p): ?>
                <tr>
                  <td class="strong">#<?= (int) $p['nomor_antrian'] ?></td>
                  <td>
                    <div class="cell-stack">
                      <span><?= e($p['nama_member']) ?></span>
                      <small>ID pesanan <?= (int) $p['pemesanan_id'] ?></small>
                    </div>
                  </td>
                  <td class="muted nowrap"><?= e(tanggal_id($p['waktu_pemesanan'], true)) ?></td>
                  <td class="num"><?= e(rupiah($p['jumlah_bayar'])) ?></td>
                  <td><?= badge_pesanan($p['status_pesanan']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </article>

    <article class="card" id="tiketButuhTindakan">
      <div class="card__head">
        <div>
          <h2>Tiket keluhan yang perlu ditangani</h2>
          <p><?= $role === 'PIC Keluhan' ? 'Tiket yang di-assign kepada Anda dan belum ditutup.' : 'Tiket berstatus open dan in progress, terurut dari yang terlama.' ?></p>
        </div>
        <?php if ($role !== 'Pimpinan'): ?>
          <div class="card__head-actions">
            <a class="link-quiet" href="<?= e(base_url('pages/complaints/index.php')) ?>">Semua tiket</a>
          </div>
        <?php endif; ?>
      </div>

      <?php if (empty($tiket_perhatian)): ?>
        <div class="empty">
          <div class="empty__icon"><?= icon('check', 20) ?></div>
          <h3>Tidak ada tiket terbuka</h3>
          <p>Semua keluhan pelanggan sudah ditutup. Tiket baru akan muncul di sini.</p>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Tiket</th>
                <th>Keluhan</th>
                <th>Dibuat</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tiket_perhatian as $t): ?>
                <tr>
                  <td class="nowrap">
                    <div class="cell-stack">
                      <?php if ($role !== 'Pimpinan'): ?>
                        <a class="strong" href="<?= e(base_url('pages/complaints/detail.php?id=' . (int) $t['tiket_id'])) ?>">#<?= (int) $t['tiket_id'] ?></a>
                      <?php else: ?>
                        <span class="strong">#<?= (int) $t['tiket_id'] ?></span>
                      <?php endif; ?>
                      <small><?= e($t['name']) ?></small>
                    </div>
                  </td>
                  <td>
                    <div class="cell-stack">
                      <span><?= e($t['kategori_keluhan']) ?></span>
                      <small><?= e(potong($t['isi_keluhan'], 60)) ?></small>
                    </div>
                  </td>
                  <td class="muted nowrap"><?= e(waktu_lalu($t['created_at'])) ?></td>
                  <td><?= badge_tiket($t['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </article>
  </div>

  <div class="stack">
    <article class="card">
      <div class="card__head">
        <div>
          <h2>Aktivitas terakhir</h2>
          <p>Pesanan, tiket, dan klaim review terbaru.</p>
        </div>
      </div>
      <?php if (empty($aktivitas)): ?>
        <div class="empty">
          <div class="empty__icon"><?= icon('clock', 20) ?></div>
          <h3>Belum ada aktivitas</h3>
          <p>Aktivitas tercatat setelah ada pesanan, tiket, atau klaim review.</p>
        </div>
      <?php else: ?>
        <ul class="activity">
          <?php foreach ($aktivitas as $a): ?>
            <li>
              <span class="activity__mark"><?= icon($a['jenis'] === 'pesanan' ? 'order' : ($a['jenis'] === 'tiket' ? 'complaint' : 'voucher'), 15) ?></span>
              <div class="activity__body">
                <p><?= e($a['teks']) ?></p>
                <small><?= e($a['detail']) ?> &middot; <?= e(waktu_lalu($a['waktu'])) ?></small>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </article>

    <article class="card">
      <div class="card__head">
        <div>
          <h2>Status ketersediaan</h2>
          <p>Ringkasan menu yang perlu refill.</p>
        </div>
      </div>
      <?php
      $menu_habis_list = mysqli_query($conn, "SELECT name, kategori, updated_at FROM menu WHERE status_ketersediaan = 'tidak tersedia' ORDER BY updated_at DESC LIMIT 5");
      $ada_habis = mysqli_num_rows($menu_habis_list) > 0;
      ?>
      <?php if (!$ada_habis): ?>
        <div class="empty">
          <div class="empty__icon"><?= icon('check', 20) ?></div>
          <h3>Semua menu tersedia</h3>
          <p>Tidak ada item yang ditandai habis saat ini.</p>
        </div>
      <?php else: ?>
        <ul class="mini-list">
          <?php while ($m = mysqli_fetch_assoc($menu_habis_list)): ?>
            <li>
              <span class="mini-list__name">
                <?= e($m['name']) ?>
                <small><?= e($m['kategori']) ?> &middot; diubah <?= e(waktu_lalu($m['updated_at'])) ?></small>
              </span>
              <span class="badge badge--danger">Habis</span>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php endif; ?>
    </article>
  </div>
</section>

<?php
$page_script = 'assets/js/dashboard.js';
$chart_data = [
    'label'      => $label_hari,
    'pendapatan' => array_map('floatval', $data_pendapatan),
    'pesanan'    => array_map('intval', $data_pesanan),
    'kategori'   => $kategori,
];
?>
<script>
window.dashboardData = <?= json_encode($chart_data, JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
