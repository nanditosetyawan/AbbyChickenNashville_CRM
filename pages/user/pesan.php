<?php
/**
 * Halaman Pesan Online untuk Pelanggan (User / Member).
 * Desain Mobile Viewport berbasis C:\xampp\htdocs\CRM\.design\mobile.md
 * Terhubung langsung ke database: menyimpan pemesanan dan detail_pesanan.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['User', 'Member']);

$member_id   = (int) ($_SESSION['member_id'] ?? 0);
$nama_member = $_SESSION['nama'] ?? 'Pelanggan';

$error = [];
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['notif_id'])) {
    $notif_id_get = (int) $_GET['notif_id'];
    if ($notif_id_get > 0) {
        $stmt_nr = mysqli_prepare($conn, 'UPDATE notifikasi_promo SET is_read = 1 WHERE notif_id = ? AND member_id = ?');
        mysqli_stmt_bind_param($stmt_nr, 'ii', $notif_id_get, $member_id);
        mysqli_stmt_execute($stmt_nr);
        mysqli_stmt_close($stmt_nr);
    }
}
$kode_voucher = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? strtoupper(trim((string) ($_POST['kode_voucher'] ?? '')))
    : strtoupper(trim((string) ($_GET['voucher'] ?? '')));

/* Katalog menu per kategori */
$katalog = [];
$q_menu = mysqli_query($conn, 'SELECT * FROM menu ORDER BY kategori ASC, name ASC');
while ($row = mysqli_fetch_assoc($q_menu)) {
    $katalog[$row['kategori']][] = $row;
}

/* Voucher aktif */
$voucher_map = [];
$q_v = mysqli_query($conn, "SELECT kode_voucher, potongan_harga FROM voucher
    WHERE is_valid = 1 AND deleted_at IS NULL AND valid_until >= CURDATE()");
while ($v = mysqli_fetch_assoc($q_v)) {
    $voucher_map[strtoupper($v['kode_voucher'])] = (float) $v['potongan_harga'];
}

/* Proses Pemesanan */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $jumlah = $_POST['jumlah'] ?? [];

    $items = [];
    if (is_array($jumlah)) {
        foreach ($jumlah as $menu_id => $qty) {
            $menu_id = (int) $menu_id;
            $qty     = (int) $qty;
            if ($qty <= 0) {
                continue;
            }

            $stmt = mysqli_prepare($conn, 'SELECT menu_id, name, harga, status_ketersediaan FROM menu WHERE menu_id = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'i', $menu_id);
            mysqli_stmt_execute($stmt);
            $menu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$menu) {
                continue;
            }
            if ($menu['status_ketersediaan'] !== 'tersedia') {
                $error['item'] = 'Menu ' . $menu['name'] . ' sedang tidak tersedia.';
                continue;
            }
            if ($qty > 50) {
                $qty = 50;
            }
            $items[] = [
                'menu_id'  => (int) $menu['menu_id'],
                'name'     => $menu['name'],
                'harga'    => (float) $menu['harga'],
                'qty'      => $qty,
                'subtotal' => (float) $menu['harga'] * $qty,
            ];
        }
    }

    if (empty($items)) {
        $error['item'] = $error['item'] ?? 'Pilih minimal satu menu untuk dipesan.';
    }

    $total = 0.0;
    foreach ($items as $it) {
        $total += $it['subtotal'];
    }

    /* Validasi voucher opsional */
    $voucher_id   = null;
    $total_diskon = 0.0;
    if ($kode_voucher !== '') {
        $stmt = mysqli_prepare($conn, "SELECT voucher_id, potongan_harga FROM voucher
            WHERE kode_voucher = ? AND is_valid = 1 AND deleted_at IS NULL AND valid_until >= CURDATE() LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $kode_voucher);
        mysqli_stmt_execute($stmt);
        $v = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$v) {
            $error['voucher'] = 'Kode voucher tidak valid atau sudah kedaluwarsa.';
        } else {
            $voucher_id   = (int) $v['voucher_id'];
            $total_diskon = min((float) $v['potongan_harga'], $total);
        }
    }

    if (empty($error)) {
        $jumlah_bayar = max(0, $total - $total_diskon);

        mysqli_begin_transaction($conn);
        try {
            /* Nomor antrian harian */
            $stmt = mysqli_prepare($conn, "SELECT COALESCE(MAX(nomor_antrian), 0) + 1 AS berikut
                FROM pemesanan WHERE DATE(waktu_pemesanan) = CURDATE()");
            mysqli_stmt_execute($stmt);
            $nomor_antrian = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['berikut'];
            mysqli_stmt_close($stmt);

            /* Simpan ke tabel pemesanan */
            $stmt = mysqli_prepare($conn, "INSERT INTO pemesanan
                (member_id, voucher_id, nomor_antrian, total_diskon, jumlah_bayar, status_pesanan)
                VALUES (?, ?, ?, ?, ?, 'menunggu')");
            mysqli_stmt_bind_param($stmt, 'iiidd', $member_id, $voucher_id, $nomor_antrian, $total_diskon, $jumlah_bayar);
            mysqli_stmt_execute($stmt);
            $pemesanan_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            /* Simpan detail pesanan */
            $stmt_detail = mysqli_prepare($conn, 'INSERT INTO detail_pesanan (menu_id, pemesanan_id, jumlah_pesanan, subtotal) VALUES (?, ?, ?, ?)');
            foreach ($items as $it) {
                mysqli_stmt_bind_param($stmt_detail, 'iiid', $it['menu_id'], $pemesanan_id, $it['qty'], $it['subtotal']);
                mysqli_stmt_execute($stmt_detail);
            }
            mysqli_stmt_close($stmt_detail);

            /* Berikan poin loyalty */
            $poin_baru = (int) floor($jumlah_bayar / 10000);
            if ($poin_baru > 0) {
                $stmt_poin = mysqli_prepare($conn, 'UPDATE member SET total_poin = total_poin + ? WHERE member_id = ?');
                mysqli_stmt_bind_param($stmt_poin, 'ii', $poin_baru, $member_id);
                mysqli_stmt_execute($stmt_poin);
                mysqli_stmt_close($stmt_poin);
            }

            mysqli_commit($conn);

            set_flash('success', 'Pesanan #' . $pemesanan_id . ' berhasil dikirim ke kasir! Nomor antrian Anda: #' . $nomor_antrian . '.');
            redirect(base_url('pages/user/pesan.php?tab=riwayat'));
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            $error['umum'] = 'Gagal menyimpan pesanan: ' . $ex->getMessage();
        }
    }
}

/* Riwayat pesanan member */
$riwayat = [];
$q_riw = mysqli_prepare($conn, "SELECT p.pemesanan_id, p.nomor_antrian, p.total_diskon, p.jumlah_bayar,
        p.status_pesanan, p.waktu_pemesanan, v.kode_voucher
    FROM pemesanan p
    LEFT JOIN voucher v ON v.voucher_id = p.voucher_id
    WHERE p.member_id = ?
    ORDER BY p.waktu_pemesanan DESC");
mysqli_stmt_bind_param($q_riw, 'i', $member_id);
mysqli_stmt_execute($q_riw);
$res_riw = mysqli_stmt_get_result($q_riw);
while ($r = mysqli_fetch_assoc($res_riw)) {
    $riwayat[] = $r;
}
mysqli_stmt_close($q_riw);

$tab_aktif = $_GET['tab'] ?? 'pesan';

$page_title    = 'Pesan';
$page_subtitle = 'Pesan menu online langsung ke antrian kasir';
$active_menu   = 'pesan';

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Filter Bar / Deck Selector (#EAE9E0 - Bagian 4.3) -->
<nav class="crm-filter-bar" aria-label="Navigasi Pesanan">
  <a class="crm-filter-pill<?= $tab_aktif === 'pesan' ? ' is-active' : '' ?>" href="<?= e(base_url('pages/user/pesan.php')) ?>">
    <?php if ($tab_aktif === 'pesan'): ?><span class="crm-filter-pill__dot"></span><?php endif; ?>
    Pesan
  </a>
  <a class="crm-filter-pill<?= $tab_aktif === 'riwayat' ? ' is-active' : '' ?>" href="<?= e(base_url('pages/user/pesan.php?tab=riwayat')) ?>">
    <?php if ($tab_aktif === 'riwayat'): ?><span class="crm-filter-pill__dot"></span><?php endif; ?>
    Antrian &amp; Riwayat
    <?php if (!empty($riwayat)): ?>
      <span class="crm-dock-badge" style="position:static;"><?= count($riwayat) ?></span>
    <?php endif; ?>
  </a>
</nav>

<?php if (!empty($error['umum'])): ?>
  <div class="crm-mobile-flash crm-mobile-flash--error"><p><?= e($error['umum']) ?></p></div>
<?php endif; ?>
<?php if (!empty($error['item'])): ?>
  <div class="crm-mobile-flash crm-mobile-flash--error"><p><?= e($error['item']) ?></p></div>
<?php endif; ?>
<?php if (!empty($error['voucher'])): ?>
  <div class="crm-mobile-flash crm-mobile-flash--error"><p><?= e($error['voucher']) ?></p></div>
<?php endif; ?>

<?php if ($tab_aktif === 'riwayat'): ?>
  <!-- Tab Riwayat: Hierarchical Bento Cards (#23331D & #FAF9F5) -->
  <div class="crm-list-stack">
    <?php if (empty($riwayat)): ?>
      <div class="crm-card-cream" style="text-align: center; padding: 36px 20px;">
        <div style="font-size: 32px; margin-bottom: 8px;"><?= icon('order', 36) ?></div>
        <h3 class="crm-card-cream__title">Belum ada antrian pesanan</h3>
        <p class="crm-card-cream__subtitle">Pilih menu favorit Anda dan pesan secara online.</p>
        <a class="crm-btn-primary" href="<?= e(base_url('pages/user/pesan.php')) ?>" style="margin-top: 16px;">
          Mulai Pesan Sekarang
        </a>
      </div>
    <?php else: ?>
      <?php foreach ($riwayat as $idx => $p): ?>
        <?php $is_newest = ($idx === 0 && in_array($p['status_pesanan'], ['menunggu', 'diproses'], true)); ?>
        <article class="crm-card-<?= $is_newest ? 'forest' : 'cream' ?>">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
            <div>
              <span class="crm-pill-tag <?= $is_newest ? 'crm-pill-tag--green' : '' ?>" style="margin-bottom: 6px;">
                Antrian #<?= (int) $p['nomor_antrian'] ?>
              </span>
              <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: <?= $is_newest ? '#FFFFFF' : 'var(--crm-black)' ?>;">
                Pesanan #<?= (int) $p['pemesanan_id'] ?>
              </h3>
            </div>
            <div>
              <?= badge_pesanan($p['status_pesanan']) ?>
            </div>
          </div>

          <div style="font-size: 12.5px; opacity: 0.85; margin-bottom: 12px; color: <?= $is_newest ? '#E2EDE0' : 'var(--crm-sage)' ?>;">
            Waktu: <?= e(tanggal_id($p['waktu_pemesanan'], true)) ?>
            <?php if (!empty($p['kode_voucher'])): ?>
              <br>Voucher: <strong><?= e($p['kode_voucher']) ?></strong> (hemat <?= rupiah($p['total_diskon']) ?>)
            <?php endif; ?>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid <?= $is_newest ? 'rgba(255,255,255,0.12)' : 'rgba(24,32,21,0.08)' ?>; padding-top: 10px;">
            <span style="font-size: 12px; color: <?= $is_newest ? '#C0CDC0' : 'var(--crm-sage)' ?>;">Total Bayar</span>
            <strong style="font-size: 16px; color: <?= $is_newest ? 'var(--crm-green-accent)' : 'var(--crm-terracotta)' ?>;">
              <?= e(rupiah($p['jumlah_bayar'])) ?>
            </strong>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- Tab Buat Pesanan Baru -->
  <form id="formPesanan" method="post" action="">
    <?= csrf_field() ?>

    <!-- Katalog Menu Bento Cards -->
    <div class="crm-list-stack" style="margin-bottom: 20px;">
      <?php foreach ($katalog as $kategori => $daftar): ?>
        <div style="margin-top: 8px;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
            <h3 style="margin: 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--crm-sage);">
              <?= e($kategori) ?>
            </h3>
            <span class="crm-pill-tag"><?= count($daftar) ?> item</span>
          </div>

          <div class="crm-list-stack">
            <?php foreach ($daftar as $m): ?>
              <?php $habis = ($m['status_ketersediaan'] !== 'tersedia'); ?>
              <div class="crm-item-card crm-item-card--tier-cream" style="padding: 14px 16px; flex-direction: column; align-items: stretch; gap: 10px; opacity: <?= $habis ? '0.6' : '1' ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                  <div>
                    <h4 class="crm-item-card__title" style="font-size: 15px;"><?= e($m['name']) ?></h4>
                    <p style="margin: 2px 0 0; font-size: 14px; font-weight: 700; color: var(--crm-forest);">
                      <?= e(rupiah($m['harga'])) ?>
                    </p>
                  </div>
                  <?php if ($habis): ?>
                    <span class="crm-pill-tag crm-pill-tag--terracotta">Habis</span>
                  <?php endif; ?>
                </div>

                <div style="display: flex; justify-content: flex-end; align-items: center; border-top: 1px solid rgba(24,32,21,0.05); padding-top: 8px;">
                  <?php if ($habis): ?>
                    <span style="font-size: 12px; color: var(--crm-terracotta);">Stok belum tersedia</span>
                  <?php else: ?>
                    <div class="qty" style="display: flex; align-items: center; gap: 8px;">
                      <button type="button" data-step="down" aria-label="Kurangi <?= e($m['name']) ?>" style="width: 32px; height: 32px; border-radius: var(--crm-radius-full); border: 1px solid #D5D3C8; background: #FFFFFF; font-weight: 700; cursor: pointer;">&minus;</button>
                      <input type="number" name="jumlah[<?= (int) $m['menu_id'] ?>]"
                             data-harga="<?= (float) $m['harga'] ?>"
                             data-nama="<?= e($m['name']) ?>"
                             value="<?= (int) ($_POST['jumlah'][$m['menu_id']] ?? 0) ?>"
                             min="0" max="50" step="1"
                             aria-label="Jumlah <?= e($m['name']) ?>"
                             style="width: 44px; height: 32px; text-align: center; border-radius: 8px; border: 1px solid #D5D3C8; font-weight: 700; font-size: 14px;">
                      <button type="button" data-step="up" aria-label="Tambah <?= e($m['name']) ?>" style="width: 32px; height: 32px; border-radius: var(--crm-radius-full); border: none; background: var(--crm-green-accent); color: var(--crm-black); font-weight: 700; cursor: pointer;">+</button>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Ringkasan Pesanan (Bento Card #FAF9F5) -->
    <article class="crm-card-cream" style="margin-bottom: 24px;">
      <div class="crm-card-cream__head">
        <div>
          <h3 class="crm-card-cream__title">Ringkasan Pesanan</h3>
          <p class="crm-card-cream__subtitle">Dihitung otomatis saat Anda memilih jumlah menu</p>
        </div>
      </div>

      <div id="ringkasanItem" style="margin-bottom: 14px; font-size: 13px;">
        <p class="text-small text-muted" style="margin: 0; color: var(--crm-sage);">Belum ada item dipilih.</p>
      </div>

      <?php
      $notif_vouchers = [];
      if (!empty($member_notif_items)) {
          foreach ($member_notif_items as $mn) {
              $kv = strtoupper(trim((string) ($mn['kode_voucher'] ?? '')));
              if ($kv !== '' && isset($voucher_map[$kv]) && !isset($notif_vouchers[$kv])) {
                  $notif_vouchers[$kv] = $voucher_map[$kv];
              }
          }
      }
      ?>
      <div class="crm-mobile-field">
        <label for="kode_voucher">Kode Voucher (Opsional)</label>
        <input class="crm-mobile-input" type="text" id="kode_voucher" name="kode_voucher"
               value="<?= e($kode_voucher) ?>" placeholder="Contoh: NASHVILLE10"
               autocomplete="off" style="text-transform:uppercase;">
        <?php if (!empty($notif_vouchers)): ?>
          <p style="margin: 6px 0 0; font-size: 11.5px; color: var(--crm-sage);">
            Voucher dari notifikasi promo Anda (ketuk untuk pakai):
          </p>
          <div class="crm-voucher-chips">
            <?php foreach ($notif_vouchers as $kv => $pot): ?>
              <button type="button"
                      class="crm-voucher-chip js-pick-order-voucher<?= $kode_voucher === $kv ? ' is-selected' : '' ?>"
                      data-kode="<?= e($kv) ?>">
                <?= icon('voucher', 12) ?> <?= e($kv) ?> (&minus;<?= e(rupiah($pot)) ?>)
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div style="background: var(--crm-sand); border-radius: var(--crm-radius-md); padding: 14px; margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; color: var(--crm-black);">
          <span>Subtotal</span>
          <span id="subtotalTeks" style="font-weight: 600;">Rp 0</span>
        </div>
        <div id="barisDiskon" hidden style="display: none; justify-content: space-between; margin-bottom: 6px; font-size: 13px; color: var(--crm-terracotta);">
          <span>Potongan Voucher</span>
          <span id="diskonTeks" style="font-weight: 600;">-Rp 0</span>
        </div>
        <div style="display: flex; justify-content: space-between; border-top: 1px solid rgba(24,32,21,0.1); padding-top: 8px; font-size: 15px; font-weight: 700; color: var(--crm-forest);">
          <span>Total Bayar</span>
          <span id="totalTeks" style="color: var(--crm-forest);">Rp 0</span>
        </div>
      </div>

      <button class="crm-btn-primary" type="submit">
        <?= icon('order', 18) ?> Kirim Pesanan ke Kasir
      </button>
    </article>
  </form>

  <script>
    window.__voucherMap = <?= json_encode($voucher_map, JSON_UNESCAPED_UNICODE) ?>;
  </script>
<?php endif; ?>

<?php
$page_script = 'assets/js/orders.js';
require_once __DIR__ . '/../../includes/footer.php';
?>
