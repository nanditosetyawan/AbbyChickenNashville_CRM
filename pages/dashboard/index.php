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

/* ------------------------------------------------- 5 Menu Terlaris */
$menu_terlaris = [];
$max_terjual = 1;
if (in_array($role, ['Admin', 'Pimpinan'], true)) {
    $q_terlaris = mysqli_query($conn, "SELECT m.menu_id, m.name, m.kategori, m.harga,
            COALESCE(SUM(dp.jumlah_pesanan), 0) AS total_terjual,
            COALESCE(SUM(dp.subtotal), 0) AS total_omset
        FROM menu m
        JOIN detail_pesanan dp ON dp.menu_id = m.menu_id
        JOIN pemesanan p ON p.pemesanan_id = dp.pemesanan_id
        WHERE p.status_pesanan <> 'dibatalkan'
        GROUP BY m.menu_id, m.name, m.kategori, m.harga
        ORDER BY total_terjual DESC, total_omset DESC, m.name ASC
        LIMIT 5");
    if ($q_terlaris) {
        while ($row = mysqli_fetch_assoc($q_terlaris)) {
            $menu_terlaris[] = $row;
        }
    }
    if (!empty($menu_terlaris)) {
        $max_terjual = max(1, (int) $menu_terlaris[0]['total_terjual']);
    }
}

/* ------------------------------------------- Tier Loyalitas (Khusus Admin) */
$top_members = [];
$max_belanja_member = 1;
if ($role === 'Admin') {
    $q_loyalty = mysqli_query($conn, "SELECT m.member_id, m.name, m.email, m.no_telp, m.tgl_join, m.total_poin,
            COUNT(p.pemesanan_id) AS total_transaksi,
            COALESCE(SUM(p.jumlah_bayar), 0) AS total_belanja
        FROM member m
        JOIN pemesanan p ON p.member_id = m.member_id AND p.status_pesanan <> 'dibatalkan'
        WHERE m.member_status = 'aktif'
        GROUP BY m.member_id, m.name, m.email, m.no_telp, m.tgl_join, m.total_poin
        HAVING total_belanja > 0
        ORDER BY total_belanja DESC, total_transaksi DESC, m.total_poin DESC
        LIMIT 5");
    if ($q_loyalty) {
        while ($row = mysqli_fetch_assoc($q_loyalty)) {
            $top_members[] = $row;
        }
    }
    if (!empty($top_members)) {
        $max_belanja_member = max(1, (float) $top_members[0]['total_belanja']);
    }
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

    <?php if ($role === 'Admin'): ?>
      <article class="card piagam-card">
        <div class="card__head piagam-card__head">
          <div>
            <div class="piagam-head-eyebrow">
              <span class="piagam-badge-pill">PRESTASI BELANJA</span>
              <span class="piagam-head-date">Akumulasi Belanja Tertinggi</span>
            </div>
            <h2 class="piagam-title">Tier Loyalitas &amp; Piagam Member</h2>
            <p class="piagam-subtitle">Peringkat member teratas berdasarkan akumulasi rupiah belanja (Khusus Admin).</p>
          </div>
          <div class="card__head-actions">
            <a class="link-quiet nowrap" href="<?= e(base_url('pages/membership/index.php')) ?>">Kelola member &rarr;</a>
          </div>
        </div>

        <?php if (empty($top_members)): ?>
          <div class="empty">
            <div class="empty__icon"><?= icon('member', 20) ?></div>
            <h3>Belum ada data belanja member</h3>
            <p>Peringkat loyalitas akan otomatis terhitung setelah member melakukan transaksi valid.</p>
          </div>
        <?php else:
          $hero_member = $top_members[0];
          $runner_members = array_slice($top_members, 1);
        ?>
          <!-- Pedestal Hero Juara 1 -->
          <div class="piagam-hero">
            <div class="piagam-hero__ribbon-tag">JUARA 1 &middot; PIAGAM EMAS UTAMA</div>
            <div class="piagam-hero__inner">
              <div class="piagam-hero__medal">
                <svg class="piagam-svg piagam-svg--gold" viewBox="0 0 54 58" fill="none" aria-hidden="true">
                  <path d="M18 34L11 54L22 49L26 36" fill="#D97706"/>
                  <path d="M36 34L43 54L32 49L28 36" fill="#B45309"/>
                  <path d="M18 34L11 54L15 44Z" fill="#F59E0B" opacity="0.6"/>
                  <path d="M12 24C10 18 13 11 20 8M34 8C41 11 44 18 42 24M11 29C13 36 18 40 27 41M43 29C41 36 36 40 27 41" stroke="#D97706" stroke-width="2" stroke-linecap="round"/>
                  <circle cx="11" cy="18" r="2.2" fill="#F59E0B"/>
                  <circle cx="43" cy="18" r="2.2" fill="#F59E0B"/>
                  <circle cx="13" cy="27" r="2.2" fill="#F59E0B"/>
                  <circle cx="41" cy="27" r="2.2" fill="#F59E0B"/>
                  <circle cx="27" cy="24" r="17" fill="url(#goldGrad)" stroke="#B45309" stroke-width="1.8"/>
                  <circle cx="27" cy="24" r="14.5" stroke="#FEF3C7" stroke-width="1.2" stroke-dasharray="2.5 1.5"/>
                  <path d="M21 16L22.5 19.5L27 18L31.5 19.5L33 16L30.5 21H23.5L21 16Z" fill="#FFFBEB" stroke="#92400E" stroke-width="0.7"/>
                  <circle cx="27" cy="17" r="1" fill="#DC2626"/>
                  <text x="27" y="32" text-anchor="middle" font-family="'Inter', sans-serif" font-weight="900" font-size="14" fill="#78350F">1</text>
                  <defs>
                    <linearGradient id="goldGrad" x1="12" y1="9" x2="42" y2="39" gradientUnits="userSpaceOnUse">
                      <stop stop-color="#FDE68A"/>
                      <stop offset="0.45" stop-color="#F59E0B"/>
                      <stop offset="1" stop-color="#B45309"/>
                    </linearGradient>
                  </defs>
                </svg>
              </div>

              <div class="piagam-hero__info">
                <div class="piagam-hero__header-row">
                  <div class="piagam-hero__member-name">
                    <a href="<?= e(base_url('pages/membership/detail.php?id=' . (int) $hero_member['member_id'])) ?>"><?= e($hero_member['name']) ?></a>
                    <span class="piagam-crown-pill" title="Tier Tertinggi">👑 Sultan VIP</span>
                  </div>
                  <div class="piagam-hero__spend-val">
                    <small>Total Belanja</small>
                    <strong><?= e(rupiah($hero_member['total_belanja'])) ?></strong>
                  </div>
                </div>
                <div class="piagam-hero__meta-row">
                  <span class="piagam-meta-item"><strong><?= (int) $hero_member['total_transaksi'] ?></strong> kali transaksi</span>
                  <span class="piagam-meta-divider">&bull;</span>
                  <span class="piagam-meta-item"><strong><?= number_format((int) $hero_member['total_poin'], 0, ',', '.') ?></strong> poin loyalitas</span>
                  <span class="piagam-meta-divider">&bull;</span>
                  <span class="piagam-meta-item muted">Member sejak <?= e(tanggal_id($hero_member['tgl_join'])) ?></span>
                </div>
                <div class="piagam-hero__bar-track">
                  <div class="piagam-hero__bar-fill" style="width: 100%;"></div>
                </div>
              </div>
            </div>
          </div>

          <?php if (!empty($runner_members)): ?>
            <!-- Runner-up List (Peringkat 2 s/d 5) -->
            <ul class="piagam-list">
              <?php foreach ($runner_members as $idx => $rm):
                $rank = $idx + 2;
                $pct = $max_belanja_member > 0 ? round(($rm['total_belanja'] / $max_belanja_member) * 100, 1) : 0;
              ?>
                <li class="piagam-item piagam-item--<?= $rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : 'neutral') ?>">
                  <div class="piagam-item__medal">
                    <?php if ($rank === 2): ?>
                      <svg class="piagam-svg piagam-svg--silver" viewBox="0 0 44 48" fill="none" aria-hidden="true">
                        <path d="M15 28L9 44L18 40L21 30" fill="#64748B"/>
                        <path d="M29 28L35 44L26 40L23 30" fill="#475569"/>
                        <path d="M10 20C9 14 12 9 17 6M27 6C32 9 35 14 34 20M9 24C11 30 15 33 22 34M35 24C33 30 29 33 22 34" stroke="#64748B" stroke-width="1.6" stroke-linecap="round"/>
                        <circle cx="22" cy="20" r="14" fill="url(#silverGrad)" stroke="#475569" stroke-width="1.5"/>
                        <circle cx="22" cy="20" r="11.8" stroke="#F8FAFC" stroke-width="1" stroke-dasharray="2 1.5"/>
                        <path d="M22 9.5L23 12L25.5 12.3L23.5 14L24 16.5L22 15.2L20 16.5L20.5 14L18.5 12.3L21 12L22 9.5Z" fill="#F8FAFC" stroke="#475569" stroke-width="0.5"/>
                        <text x="22" y="27" text-anchor="middle" font-family="'Inter', sans-serif" font-weight="900" font-size="12" fill="#1E293B">2</text>
                        <defs>
                          <linearGradient id="silverGrad" x1="10" y1="8" x2="34" y2="32" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#F1F5F9"/>
                            <stop offset="0.5" stop-color="#CBD5E1"/>
                            <stop offset="1" stop-color="#94A3B8"/>
                          </linearGradient>
                        </defs>
                      </svg>
                    <?php elseif ($rank === 3): ?>
                      <svg class="piagam-svg piagam-svg--bronze" viewBox="0 0 44 48" fill="none" aria-hidden="true">
                        <path d="M15 28L9 44L18 40L21 30" fill="#B45309"/>
                        <path d="M29 28L35 44L26 40L23 30" fill="#7C2D12"/>
                        <path d="M10 20C9 14 12 9 17 6M27 6C32 9 35 14 34 20M9 24C11 30 15 33 22 34M35 24C33 30 29 33 22 34" stroke="#B45309" stroke-width="1.6" stroke-linecap="round"/>
                        <circle cx="22" cy="20" r="14" fill="url(#bronzeGrad)" stroke="#7C2D12" stroke-width="1.5"/>
                        <circle cx="22" cy="20" r="11.8" stroke="#FFEDD5" stroke-width="1" stroke-dasharray="2 1.5"/>
                        <path d="M22 9.5L23 12L25.5 12.3L23.5 14L24 16.5L22 15.2L20 16.5L20.5 14L18.5 12.3L21 12L22 9.5Z" fill="#FFF7ED" stroke="#7C2D12" stroke-width="0.5"/>
                        <text x="22" y="27" text-anchor="middle" font-family="'Inter', sans-serif" font-weight="900" font-size="12" fill="#451A03">3</text>
                        <defs>
                          <linearGradient id="bronzeGrad" x1="10" y1="8" x2="34" y2="32" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#FED7AA"/>
                            <stop offset="0.5" stop-color="#FDBA74"/>
                            <stop offset="1" stop-color="#C2410C"/>
                          </linearGradient>
                        </defs>
                      </svg>
                    <?php else: ?>
                      <div class="piagam-badge-num">#<?= $rank ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="piagam-item__body">
                    <div class="piagam-item__top">
                      <div class="piagam-item__name-wrap">
                        <a class="piagam-item__name" href="<?= e(base_url('pages/membership/detail.php?id=' . (int) $rm['member_id'])) ?>"><?= e($rm['name']) ?></a>
                        <?php if ($rank === 2): ?>
                          <span class="badge-piagam badge-piagam--silver">Piagam Perak &middot; Platinum</span>
                        <?php elseif ($rank === 3): ?>
                          <span class="badge-piagam badge-piagam--bronze">Piagam Perunggu &middot; Gold</span>
                        <?php else: ?>
                          <span class="badge-piagam badge-piagam--neutral">Silver Tier</span>
                        <?php endif; ?>
                      </div>
                      <div class="piagam-item__spend">
                        <strong><?= e(rupiah($rm['total_belanja'])) ?></strong>
                      </div>
                    </div>
                    <div class="piagam-item__sub">
                      <span><?= (int) $rm['total_transaksi'] ?> pesanan &middot; <?= number_format((int) $rm['total_poin'], 0, ',', '.') ?> poin</span>
                      <small class="muted">Member sejak <?= e(tanggal_id($rm['tgl_join'])) ?></small>
                    </div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        <?php endif; ?>
      </article>
    <?php endif; ?>

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
    <?php if (in_array($role, ['Admin', 'Pimpinan'], true)): ?>
      <article class="card">
        <div class="card__head">
          <div>
            <h2>5 Menu terlaris</h2>
            <p>Urutan menu paling banyak dipesan dari transaksi valid.</p>
          </div>
          <?php if ($role !== 'Pimpinan'): ?>
            <div class="card__head-actions">
              <a class="link-quiet" href="<?= e(base_url('pages/menu/index.php')) ?>">Kelola menu</a>
            </div>
          <?php endif; ?>
        </div>
        <?php if (empty($menu_terlaris)): ?>
          <div class="empty">
            <div class="empty__icon"><?= icon('chart', 20) ?></div>
            <h3>Belum ada data penjualan</h3>
            <p>Menu terlaris akan otomatis dihitung setelah ada pesanan masuk.</p>
          </div>
        <?php else: ?>
          <ul class="top-menu-list">
            <?php foreach ($menu_terlaris as $idx => $m):
              $rank = $idx + 1;
              $pct = $max_terjual > 0 ? round(($m['total_terjual'] / $max_terjual) * 100, 1) : 0;
            ?>
              <li class="top-menu-item">
                <span class="top-menu-rank top-menu-rank--<?= $rank ?>">#<?= $rank ?></span>
                <div class="top-menu-info">
                  <div class="top-menu-row">
                    <span class="top-menu-name" title="<?= e($m['name']) ?>"><?= e($m['name']) ?></span>
                    <span class="top-menu-qty"><strong><?= (int) $m['total_terjual'] ?></strong> dipesan</span>
                  </div>
                  <div class="top-menu-bar-track">
                    <div class="top-menu-bar-fill top-menu-bar-fill--<?= $rank ?>" style="width: <?= $pct ?>%;"></div>
                  </div>
                  <div class="top-menu-meta">
                    <span><?= e($m['kategori']) ?> &middot; <?= e(rupiah($m['harga'])) ?></span>
                    <span class="top-menu-omset">Total <?= e(rupiah($m['total_omset'])) ?></span>
                  </div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </article>
    <?php endif; ?>

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
