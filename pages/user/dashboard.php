<?php
/**
 * Dashboard Pelanggan (User / Member).
 * Desain Mobile Viewport berbasis C:\xampp\htdocs\CRM\.design\mobile.md
 * Warm Organic Editorial & Tactile Modernism.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['User', 'Member']);

$member_id   = (int) ($_SESSION['member_id'] ?? 0);
$nama_member = $_SESSION['nama'] ?? 'Pelanggan';

/* Ambil profil & poin member dari database */
$stmt_m = mysqli_prepare($conn, 'SELECT member_id, name, email, no_telp, tgl_join, total_poin, member_status FROM member WHERE member_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt_m, 'i', $member_id);
mysqli_stmt_execute($stmt_m);
$member_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_m)) ?: [
    'name' => $nama_member,
    'total_poin' => 0,
    'member_status' => 'aktif'
];
mysqli_stmt_close($stmt_m);

/* 1. Status Refill Nasi dan Data Timer dari database */
$refill_info     = get_refill_nasi_info();
$is_refill_ready = $refill_info['is_ready'];

/* 2. Menu Tidak Tersedia & Total Menu */
$q_menu = mysqli_query($conn, "SELECT
    SUM(status_ketersediaan = 'tidak tersedia') AS menu_habis,
    SUM(status_ketersediaan = 'tersedia') AS menu_tersedia,
    COUNT(*) AS menu_total
FROM menu");
$menu_stat   = mysqli_fetch_assoc($q_menu) ?: ['menu_habis' => 0, 'menu_total' => 0, 'menu_tersedia' => 0];
$menu_habis  = (int) ($menu_stat['menu_habis'] ?? 0);
$menu_total  = (int) ($menu_stat['menu_total'] ?? 0);
$menu_ready  = (int) ($menu_stat['menu_tersedia'] ?? 0);

/* Daftar item menu yang sedang tidak tersedia */
$menu_habis_list = [];
$q_habis = mysqli_query($conn, "SELECT name, kategori, harga FROM menu WHERE status_ketersediaan = 'tidak tersedia' ORDER BY name ASC");
while ($r = mysqli_fetch_assoc($q_habis)) {
    $menu_habis_list[] = $r;
}

/* 3. Komposisi Kategori Menu */
$kategori = [];
$q_kat = mysqli_query($conn, "SELECT kategori, COUNT(*) AS jml FROM menu GROUP BY kategori ORDER BY jml DESC");
while ($r = mysqli_fetch_assoc($q_kat)) {
    $kategori[$r['kategori']] = (int) $r['jml'];
}

$page_title    = 'Dashboard';
$page_subtitle = 'Selamat datang, ' . e($nama_member);
$active_menu   = 'dashboard';

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- 1. Header Status Card (Deep Forest Green #23331D - Bagian 5 Card Tipe A) -->
<article class="crm-card-forest">
  <div class="crm-card-forest__top">
    <div class="crm-card-forest__user">
      <div class="crm-card-forest__avatar">
        <?= e(strtoupper(mb_substr($member_data['name'], 0, 1))) ?>
      </div>
      <div>
        <h2 class="crm-card-forest__name"><?= e($member_data['name']) ?></h2>
        <p class="crm-card-forest__role">Member VIP &middot; <?= (int) $member_data['total_poin'] ?> Poin</p>
      </div>
    </div>
    <span class="crm-card-forest__badge">
      <?= strtoupper(e($member_data['member_status'])) ?>
    </span>
  </div>
  <div class="crm-card-forest__bottom">
    <span>Katalog siap: <strong class="crm-card-forest__stat-highlight"><?= $menu_ready ?> menu</strong></span>
    <span>Habis: <strong style="color: var(--crm-terracotta);"><?= $menu_habis ?></strong></span>
  </div>
</article>

<!-- 2. Bento Card Status Refill Nasi (Bagian 5 Card Tipe B & E) -->
<article class="crm-card-refill <?= $refill_info['is_ready'] ? 'is-ready' : 'is-habis' ?><?= $refill_info['has_timer'] ? ' has-timer' : '' ?>"
         id="userRefillCard"
         data-status="<?= e($refill_info['status']) ?>"
         data-target-ts="<?= (int) $refill_info['timer_ts'] ?>">
  <div class="crm-card-refill__top">
    <div class="crm-card-refill__left">
      <div class="crm-card-refill__icon" aria-hidden="true">
        <?= icon('rice', 28) ?>
      </div>
      <div>
        <h3 class="crm-card-refill__title">Refill Nasi</h3>
        <p class="crm-card-refill__sub" id="userRefillSub">
          <?php if ($refill_info['is_ready']): ?>
            Bebas tambah sepuasnya untuk dine-in
          <?php elseif ($refill_info['has_timer']): ?>
            Sedang dimasak ulang di dapur
          <?php else: ?>
            Sedang disiapkan ulang di dapur
          <?php endif; ?>
        </p>
      </div>
    </div>
    <span class="crm-card-refill__badge<?= $refill_info['has_timer'] ? ' crm-card-refill__badge--timer' : '' ?>" id="userRefillBadge">
      <?php if ($refill_info['is_ready']): ?>
        Ready
      <?php elseif ($refill_info['has_timer']): ?>
        <span class="crm-timer-pulse"></span> Dimasak
      <?php else: ?>
        Habis
      <?php endif; ?>
    </span>
  </div>

  <!-- Box Timer Restok Otomatis (Tampil saat tidak restok & timer aktif) -->
  <div class="crm-refill-timer-box" id="userTimerBox" style="<?= $refill_info['has_timer'] ? '' : 'display:none;' ?>">
    <div class="crm-refill-timer-box__header">
      <div class="crm-refill-timer-box__label">
        <svg class="crm-timer-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span>Tersedia Kembali Dalam:</span>
      </div>
      <span class="crm-refill-timer-box__target" id="userTimerTarget">
        <?= !empty($refill_info['timer_ts']) ? date('H:i', $refill_info['timer_ts']) . ' WIB' : '' ?>
      </span>
    </div>

    <div class="crm-refill-timer-digits" id="userRefillCountdown">
      <div class="crm-timer-unit-group">
        <span class="crm-timer-val" id="userCdHours">00</span>
        <span class="crm-timer-lbl">JAM</span>
      </div>
      <span class="crm-timer-sep">:</span>
      <div class="crm-timer-unit-group">
        <span class="crm-timer-val" id="userCdMinutes">00</span>
        <span class="crm-timer-lbl">MNT</span>
      </div>
      <span class="crm-timer-sep">:</span>
      <div class="crm-timer-unit-group">
        <span class="crm-timer-val" id="userCdSeconds">00</span>
        <span class="crm-timer-lbl">DTK</span>
      </div>
    </div>

    <div class="crm-refill-timer-footer">
      <span class="crm-refill-timer-note">
        Estimasi otomatis restok &middot; Nasi hangat pulen siap disajikan lagi
      </span>
    </div>
  </div>
</article>

<!-- 2b. Bento Card Notifikasi Promosi Pribadi Member -->
<?php if (!empty($member_notif_items)): ?>
  <?php
  $promo_utama = $member_notif_items[0];
  $promo_has_unread = ($member_unread_notif > 0);
  $promo_voucher_valid = !empty($promo_utama['potongan_harga'])
      && (int) ($promo_utama['is_valid'] ?? 0) === 1
      && ($promo_utama['deleted_at'] ?? null) === null
      && !empty($promo_utama['valid_until'])
      && strtotime($promo_utama['valid_until']) >= strtotime(date('Y-m-d'));
  ?>
  <article class="crm-card-<?= $promo_has_unread ? 'forest' : 'cream' ?>" id="dashboardPromoCard">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 8px;">
      <div>
        <div style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: <?= $promo_has_unread ? 'var(--crm-green-accent)' : 'var(--crm-terracotta)' ?>; margin-bottom: 3px;">
          Notifikasi Promo Untukmu
        </div>
        <h3 style="margin: 0; font-size: 15.5px; font-weight: 700; line-height: 1.3; color: <?= $promo_has_unread ? '#FFFFFF' : 'var(--crm-black)' ?>;">
          <?= e($promo_utama['judul']) ?>
        </h3>
      </div>
      <span class="crm-pill-tag <?= $promo_has_unread ? 'crm-pill-tag--green' : '' ?>" id="dashboardNotifUnreadBadge">
        <?= $promo_has_unread ? $member_unread_notif . ' promo baru' : 'Sudah dibaca' ?>
      </span>
    </div>

    <p style="margin: 0; font-size: 12.5px; line-height: 1.45; color: <?= $promo_has_unread ? '#DAE6D6' : 'var(--crm-sage)' ?>;">
      <?= e(potong($promo_utama['pesan'], 150)) ?>
    </p>

    <?php if (!empty($promo_utama['kode_voucher'])): ?>
      <div class="crm-notif-voucher-box">
        <div>
          <div style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px; color: <?= $promo_has_unread ? '#C5D5C0' : 'var(--crm-sage)' ?>;">
            Kode Voucher Promo
          </div>
          <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
            <span class="crm-notif-voucher-code"><?= icon('voucher', 13) ?> <?= e($promo_utama['kode_voucher']) ?></span>
            <?php if ($promo_voucher_valid): ?>
              <span style="font-size: 11.5px; font-weight: 700; color: <?= $promo_has_unread ? '#9CE481' : 'var(--crm-forest)' ?>;">
                Hemat <?= e(rupiah($promo_utama['potongan_harga'])) ?>
              </span>
            <?php endif; ?>
          </div>
        </div>
        <button type="button"
                class="crm-btn-mini <?= $promo_has_unread ? 'crm-btn-mini--ghost-dark' : 'crm-btn-mini--sand' ?> js-copy-voucher"
                data-kode="<?= e($promo_utama['kode_voucher']) ?>">
          <?= icon('copy', 12) ?> <span>Salin</span>
        </button>
      </div>
    <?php endif; ?>

    <div class="crm-notif-card__actions">
      <?php if (!empty($promo_utama['kode_voucher'])): ?>
        <a class="crm-btn-mini crm-btn-mini--green"
           href="<?= e(base_url('pages/user/pesan.php?voucher=' . urlencode($promo_utama['kode_voucher']) . '&notif_id=' . (int) $promo_utama['notif_id'])) ?>">
          Pakai Voucher di Pesan &rarr;
        </a>
      <?php endif; ?>
      <button type="button"
              class="crm-btn-mini <?= $promo_has_unread ? 'crm-btn-mini--ghost-dark' : 'crm-btn-mini--sand' ?>"
              data-open-notif-sheet>
        <?= icon('bell', 13) ?> Lihat Semua Notifikasi (<?= count($member_notif_items) ?>)
      </button>
    </div>
  </article>
<?php elseif (!empty($member_pengumuman_aktif)): ?>
  <?php $info_outlet = $member_pengumuman_aktif[0]; ?>
  <article class="crm-card-cream" style="padding: 16px 18px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 6px;">
      <div>
        <div style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--crm-terracotta); margin-bottom: 2px;">
          Info Outlet
        </div>
        <h3 style="margin: 0; font-size: 14.5px; font-weight: 700; color: var(--crm-black);"><?= e($info_outlet['judul']) ?></h3>
      </div>
      <button type="button" class="crm-btn-mini crm-btn-mini--sand" data-open-notif-sheet>
        <?= icon('bell', 13) ?> Info (<?= count($member_pengumuman_aktif) ?>)
      </button>
    </div>
    <p style="margin: 0; font-size: 12px; color: var(--crm-sage); line-height: 1.4;">
      <?= e(potong($info_outlet['isi'], 110)) ?>
    </p>
  </article>
<?php endif; ?>

<!-- 3. Tombol Utama Pesan Online (Vibrant Leaf Green #6CC24A) -->
<div style="margin-bottom: 16px;">
  <a class="crm-btn-primary" href="<?= e(base_url('pages/user/pesan.php')) ?>">
    <?= icon('order', 18) ?> Pesan Menu Online &rarr;
  </a>
</div>

<!-- 4. Bento Card Menu Siap Jual & Tidak Tersedia -->
<article class="crm-card-cream">
  <div class="crm-card-cream__head">
    <div>
      <h3 class="crm-card-cream__title">Ketersediaan Menu</h3>
      <p class="crm-card-cream__subtitle">Status stok dapur AbbyChicken secara langsung</p>
    </div>
    <span class="crm-pill-tag crm-pill-tag--green">Hari ini</span>
  </div>

  <div style="display: flex; gap: 12px; margin-top: 10px;">
    <div style="flex: 1; background: var(--crm-sand); padding: 14px; border-radius: var(--crm-radius-md); text-align: center;">
      <p style="margin: 0; font-size: 11px; font-weight: 600; color: var(--crm-sage); text-transform: uppercase;">Siap Jual</p>
      <p style="margin: 4px 0 0; font-size: 24px; font-weight: 700; color: var(--crm-forest);"><?= $menu_ready ?></p>
    </div>
    <div style="flex: 1; background: var(--crm-sand); padding: 14px; border-radius: var(--crm-radius-md); text-align: center;">
      <p style="margin: 0; font-size: 11px; font-weight: 600; color: var(--crm-sage); text-transform: uppercase;">Habis</p>
      <p style="margin: 4px 0 0; font-size: 24px; font-weight: 700; color: var(--crm-terracotta);"><?= $menu_habis ?></p>
    </div>
  </div>
</article>

<!-- 5. Bento Card Komposisi Menu (Diagram Donut) -->
<article class="crm-card-cream">
  <div class="crm-card-cream__head">
    <div>
      <h3 class="crm-card-cream__title">Komposisi Menu</h3>
      <p class="crm-card-cream__subtitle">Total <?= (int) $menu_total ?> item terdaftar dalam kategori</p>
    </div>
  </div>

  <div style="height: 160px; position: relative; margin: 10px 0 14px;">
    <canvas id="chartKategori" role="img" aria-label="Diagram donat kategori menu"></canvas>
  </div>
  <div id="legendKategori" style="display: flex; flex-wrap: wrap; gap: 8px; justify-content: center;"></div>
</article>

<!-- 6. Bento Card Daftar Menu Habis (Hierarchical Cards) -->
<?php if (!empty($menu_habis_list)): ?>
  <article class="crm-card-cream">
    <div class="crm-card-cream__head">
      <div>
        <h3 class="crm-card-cream__title">Menu Sedang Habis</h3>
        <p class="crm-card-cream__subtitle">Item berikut belum dapat dipesan saat ini</p>
      </div>
      <span class="crm-pill-tag crm-pill-tag--terracotta"><?= count($menu_habis_list) ?> item</span>
    </div>

    <div class="crm-list-stack">
      <?php foreach ($menu_habis_list as $index => $mh): ?>
        <div class="crm-item-card crm-item-card--tier-cream">
          <div>
            <h4 class="crm-item-card__title"><?= e($mh['name']) ?></h4>
            <p class="crm-item-card__desc"><?= e($mh['kategori']) ?> &middot; <?= rupiah($mh['harga']) ?></p>
          </div>
          <span class="crm-pill-tag crm-pill-tag--terracotta">Habis</span>
        </div>
      <?php endforeach; ?>
    </div>
  </article>
<?php endif; ?>

<!-- 7. Kartu Pintas Ulasan & Keluhan -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
  <a class="crm-item-card crm-item-card--tier-cream" href="<?= e(base_url('pages/user/ulasan.php')) ?>" style="padding: 16px; flex-direction: column; align-items: flex-start; gap: 8px;">
    <div style="width: 36px; height: 36px; border-radius: 12px; background: rgba(108, 194, 74, 0.16); color: #2E6B1B; display: flex; align-items: center; justify-content: center;">
      <?= icon('voucher', 18) ?>
    </div>
    <div>
      <h4 class="crm-item-card__title" style="font-size: 13.5px;">Klaim Ulasan</h4>
      <p class="crm-item-card__desc" style="font-size: 11px;">Dapat voucher diskon</p>
    </div>
  </a>

  <a class="crm-item-card crm-item-card--tier-cream" href="<?= e(base_url('pages/user/keluhan.php')) ?>" style="padding: 16px; flex-direction: column; align-items: flex-start; gap: 8px;">
    <div style="width: 36px; height: 36px; border-radius: 12px; background: rgba(200, 90, 50, 0.14); color: var(--crm-terracotta); display: flex; align-items: center; justify-content: center;">
      <?= icon('complaint', 18) ?>
    </div>
    <div>
      <h4 class="crm-item-card__title" style="font-size: 13.5px;">Kirim Keluhan</h4>
      <p class="crm-item-card__desc" style="font-size: 11px;">Bantuan tim PIC</p>
    </div>
  </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var kategoriData = <?= json_encode($kategori, JSON_UNESCAPED_UNICODE) ?>;
  var labels = Object.keys(kategoriData);
  var values = Object.values(kategoriData);
  // Warna palet organik dari mobile.md: Forest, Leaf Green, Olive, Terracotta, Sage
  var colors = ['#23331D', '#6CC24A', '#3A4633', '#C85A32', '#6E7865', '#8E9C7E'];

  var canvas = document.getElementById('chartKategori');
  if (canvas && labels.length > 0 && typeof Chart !== 'undefined') {
    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: colors.slice(0, labels.length),
          borderWidth: 3,
          borderColor: '#FAF9F5'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        cutout: '66%'
      }
    });

    var legendEl = document.getElementById('legendKategori');
    if (legendEl) {
      legendEl.innerHTML = labels.map(function (lab, i) {
        return '<span class="crm-pill-tag" style="background:#FAF9F5; border:1px solid #EAE9E0; display:inline-flex; align-items:center; gap:6px; font-size:11px;">' +
          '<span style="width:8px; height:8px; border-radius:50%; background:' + colors[i % colors.length] + ';"></span> ' +
          lab + ' (' + values[i] + ')' +
        '</span>';
      }).join('');
    }
  }

  // Live Real-Time Countdown Timer Refill Nasi (Ticking Second-by-Second)
  (function () {
    var card = document.getElementById('userRefillCard');
    if (!card) return;

    var targetTs = parseInt(card.getAttribute('data-target-ts'), 10) || 0;
    var timerBox = document.getElementById('userTimerBox');
    var cdHours = document.getElementById('userCdHours');
    var cdMinutes = document.getElementById('userCdMinutes');
    var cdSeconds = document.getElementById('userCdSeconds');
    var subText = document.getElementById('userRefillSub');
    var badge = document.getElementById('userRefillBadge');

    if (targetTs <= 0 || card.classList.contains('is-ready')) {
      return;
    }

    function pad(n) {
      return n < 10 ? '0' + n : String(n);
    }

    function tick() {
      var now = Math.floor(Date.now() / 1000);
      var remaining = targetTs - now;

      if (remaining <= 0) {
        if (cdHours) cdHours.textContent = '00';
        if (cdMinutes) cdMinutes.textContent = '00';
        if (cdSeconds) cdSeconds.textContent = '00';

        // Otomatis transisi ke Ready dengan animasi halus
        card.classList.remove('is-habis', 'has-timer');
        card.classList.add('is-ready');
        if (subText) subText.textContent = 'Bebas tambah sepuasnya untuk dine-in';
        if (badge) {
          badge.className = 'crm-card-refill__badge';
          badge.textContent = 'Ready';
        }
        if (timerBox) {
          timerBox.style.opacity = '0';
          timerBox.style.transform = 'scale(0.95)';
          setTimeout(function () {
            timerBox.style.display = 'none';
          }, 350);
        }

        // Sinkronisasi status di database via API
        fetch('<?= e(base_url('api/get_refill_status.php')) ?>').catch(function () {});
        return;
      }

      var h = Math.floor(remaining / 3600);
      var m = Math.floor((remaining % 3600) / 60);
      var s = remaining % 60;

      if (cdHours) cdHours.textContent = pad(h);
      if (cdMinutes) cdMinutes.textContent = pad(m);
      if (cdSeconds) cdSeconds.textContent = pad(s);

      setTimeout(tick, 1000);
    }

    tick();
  })();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
