<?php
/**
 * Form buat pesanan baru (US-3.1, US-3.2).
 * Katalog menu + keranjang + penerapan voucher.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'Kasir']);

$error = [];
$pilihan_member = (int) ($_GET['member_id'] ?? $_POST['member_id'] ?? 0);
$kode_voucher   = trim($_POST['kode_voucher'] ?? '');

/* Member aktif untuk dropdown */
$members = mysqli_query($conn, "SELECT member_id, name, email, total_poin FROM member WHERE member_status = 'aktif' ORDER BY name ASC");

/* Katalog menu dikelompokkan per kategori */
$katalog = [];
$q = mysqli_query($conn, 'SELECT * FROM menu ORDER BY kategori ASC, name ASC');
while ($row = mysqli_fetch_assoc($q)) {
    $katalog[$row['kategori']][] = $row;
}

/* Voucher yang masih bisa dipakai */
$voucher_aktif = mysqli_query($conn, "SELECT kode_voucher, potongan_harga, valid_until FROM voucher
    WHERE is_valid = 1 AND deleted_at IS NULL AND valid_until >= CURDATE()
    ORDER BY potongan_harga DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $jumlah = $_POST['jumlah'] ?? [];  // [menu_id => qty]

    if ($pilihan_member <= 0) {
        $error['member'] = 'Pilih member yang memesan.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT member_id FROM member WHERE member_id = ? AND member_status = 'aktif' LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $pilihan_member);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) === 0) {
            $error['member'] = 'Member tidak ditemukan atau berstatus nonaktif.';
        }
        mysqli_stmt_close($stmt);
    }

    /* Validasi item */
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
                $error['item'] = $menu['name'] . ' sedang tidak tersedia dan tidak bisa dipesan.';
                continue;
            }
            if ($qty > 50) {
                $error['item'] = 'Jumlah per item maksimal 50 porsi.';
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

    /* Voucher opsional */
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
            $error['voucher'] = 'Kode voucher tidak valid, sudah kedaluwarsa, atau tidak aktif.';
        } else {
            $voucher_id   = (int) $v['voucher_id'];
            $total_diskon = min((float) $v['potongan_harga'], $total);
        }
    }

    if (empty($error)) {
        $jumlah_bayar = max(0, $total - $total_diskon);

        mysqli_begin_transaction($conn);
        try {
            /* Nomor antrian harian: MAX + 1 untuk tanggal hari ini. */
            $stmt = mysqli_prepare($conn, "SELECT COALESCE(MAX(nomor_antrian), 0) + 1 AS berikut
                FROM pemesanan WHERE DATE(waktu_pemesanan) = CURDATE()");
            mysqli_stmt_execute($stmt);
            $nomor_antrian = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['berikut'];
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($conn, "INSERT INTO pemesanan
                (member_id, voucher_id, nomor_antrian, total_diskon, jumlah_bayar, status_pesanan)
                VALUES (?, ?, ?, ?, ?, 'menunggu')");
            mysqli_stmt_bind_param($stmt, 'iiidd', $pilihan_member, $voucher_id, $nomor_antrian, $total_diskon, $jumlah_bayar);
            mysqli_stmt_execute($stmt);
            $pemesanan_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            $stmt_detail = mysqli_prepare($conn, 'INSERT INTO detail_pesanan (menu_id, pemesanan_id, jumlah_pesanan, subtotal) VALUES (?, ?, ?, ?)');
            foreach ($items as $it) {
                mysqli_stmt_bind_param($stmt_detail, 'iiid', $it['menu_id'], $pemesanan_id, $it['qty'], $it['subtotal']);
                mysqli_stmt_execute($stmt_detail);
            }
            mysqli_stmt_close($stmt_detail);

            mysqli_commit($conn);

            set_flash('success', 'Pesanan #' . $pemesanan_id . ' dibuat. Nomor antrian ' . $nomor_antrian . '.');
            redirect(base_url('pages/orders/detail.php?id=' . $pemesanan_id));
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            $error['umum'] = 'Gagal menyimpan pesanan: ' . $ex->getMessage();
        }
    }
}

$page_title    = 'Buat pesanan';
$page_subtitle = 'Pilih menu, tentukan jumlah, lalu terapkan voucher bila ada.';
$active_menu   = 'orders';
$page_actions  = '<a class="btn btn--secondary" href="' . e(base_url('pages/orders/index.php')) . '">' . icon('arrow-left', 16) . ' Kembali ke antrian</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if (!empty($error['umum'])): ?>
  <div class="flash flash--error mb-16"><span class="flash__icon"><?= icon('close', 16) ?></span><p><?= e($error['umum']) ?></p></div>
<?php endif; ?>

<form method="post" action="" id="formPesanan" novalidate>
  <?= csrf_field() ?>

  <section class="detail-grid">
    <div class="stack">
      <article class="card">
        <div class="card__head">
          <div>
            <h2>Member pemesan</h2>
            <p>Hanya member aktif yang dapat memesan dan mengumpulkan poin.</p>
          </div>
        </div>
        <div class="card__body">
          <div class="form-grid form-grid--1">
            <div class="field">
              <label for="member_id">Pilih member <span aria-hidden="true">*</span></label>
              <select class="select<?= isset($error['member']) ? ' is-invalid' : '' ?>" id="member_id" name="member_id" required>
                <option value="">— Pilih member —</option>
                <?php mysqli_data_seek($members, 0); while ($m = mysqli_fetch_assoc($members)): ?>
                  <option value="<?= (int) $m['member_id'] ?>" <?= $pilihan_member === (int) $m['member_id'] ? 'selected' : '' ?>>
                    <?= e($m['name']) ?> — <?= e($m['email']) ?> (<?= (int) $m['total_poin'] ?> poin)
                  </option>
                <?php endwhile; ?>
              </select>
              <?php if (isset($error['member'])): ?>
                <span class="error"><?= e($error['member']) ?></span>
              <?php else: ?>
                <span class="hint">Member nonaktif tidak muncul di daftar ini.</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </article>

      <article class="card">
        <div class="card__head">
          <div>
            <h2>Katalog menu</h2>
            <p>Menu bertanda habis tidak dapat ditambahkan ke pesanan.</p>
          </div>
        </div>

        <div class="card__body">
          <?php if (isset($error['item'])): ?>
            <div class="flash flash--error mb-16"><span class="flash__icon"><?= icon('close', 16) ?></span><p><?= e($error['item']) ?></p></div>
          <?php endif; ?>

          <?php foreach ($katalog as $kategori => $daftar): ?>
            <div class="mb-20">
              <h3 class="mb-16" style="font-size:13px;text-transform:uppercase;letter-spacing:.04em;color:var(--gray-600)">
                <?= e($kategori) ?>
              </h3>
              <div class="menu-catalog">
                <?php foreach ($daftar as $m): ?>
                  <?php $habis = $m['status_ketersediaan'] !== 'tersedia'; ?>
                  <div class="menu-card<?= $habis ? ' is-unavailable' : '' ?>">
                    <div class="menu-card__top">
                      <span class="menu-card__name"><?= e($m['name']) ?></span>
                      <?= $habis ? '<span class="badge badge--danger">Habis</span>' : '' ?>
                    </div>
                    <p class="menu-card__price"><?= e(rupiah($m['harga'])) ?></p>
                    <div class="menu-card__foot">
                      <?php if ($habis): ?>
                        <span class="text-small">Tidak dapat dipesan</span>
                      <?php else: ?>
                        <div class="qty">
                          <button type="button" data-step="down" aria-label="Kurangi <?= e($m['name']) ?>">&minus;</button>
                          <input type="number" name="jumlah[<?= (int) $m['menu_id'] ?>]"
                                 data-harga="<?= (float) $m['harga'] ?>"
                                 data-nama="<?= e($m['name']) ?>"
                                 value="<?= (int) ($_POST['jumlah'][$m['menu_id']] ?? 0) ?>"
                                 min="0" max="50" step="1"
                                 aria-label="Jumlah <?= e($m['name']) ?>">
                          <button type="button" data-step="up" aria-label="Tambah <?= e($m['name']) ?>">+</button>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </article>
    </div>

    <aside class="summary">
      <article class="card">
        <div class="card__head">
          <div>
            <h2>Ringkasan pesanan</h2>
            <p>Total dihitung otomatis dari item yang dipilih.</p>
          </div>
        </div>

        <div class="card__body">
          <div class="summary__lines" id="ringkasanItem">
            <p class="text-small text-muted">Belum ada item dipilih.</p>
          </div>

          <div class="summary__row">
            <span>Subtotal</span>
            <span id="subtotalTeks">Rp 0</span>
          </div>
          <div class="summary__row summary__row--diskon" id="barisDiskon" hidden>
            <span>Diskon voucher</span>
            <span id="diskonTeks">Rp 0</span>
          </div>

          <div class="summary__total">
            <span>Jumlah bayar</span>
            <span id="totalTeks">Rp 0</span>
          </div>

          <div class="field mt-16">
            <label for="kode_voucher">Kode voucher (opsional)</label>
            <input class="input<?= isset($error['voucher']) ? ' is-invalid' : '' ?>" type="text" id="kode_voucher" name="kode_voucher"
                   value="<?= e($kode_voucher) ?>" placeholder="Contoh: REVIEWHOT50" autocomplete="off">
            <?php if (isset($error['voucher'])): ?>
              <span class="error"><?= e($error['voucher']) ?></span>
            <?php else: ?>
              <span class="hint">Potongan diterapkan saat pesanan disimpan.</span>
            <?php endif; ?>
          </div>

          <button class="btn btn--primary btn--block mt-16" type="submit" id="btnSimpan"><?= icon('check', 16) ?> Simpan pesanan</button>

          <details class="mt-12">
            <summary class="text-small text-muted" style="cursor:pointer">Lihat voucher aktif</summary>
            <ul class="mini-list mt-8" style="border:1px solid var(--gray-200);border-radius:6px">
              <?php mysqli_data_seek($voucher_aktif, 0); $ada_v = false; while ($v = mysqli_fetch_assoc($voucher_aktif)): $ada_v = true; ?>
                <li>
                  <span class="mini-list__name">
                    <strong><?= e($v['kode_voucher']) ?></strong>
                    <small>berlaku s.d. <?= e(tanggal_id($v['valid_until'])) ?></small>
                  </span>
                  <span class="text-small"><?= e(rupiah($v['potongan_harga'])) ?></span>
                </li>
              <?php endwhile; ?>
              <?php if (!$ada_v): ?>
                <li><span class="text-small text-muted">Tidak ada voucher aktif saat ini.</span></li>
              <?php endif; ?>
            </ul>
          </details>
        </div>
      </article>
    </aside>
  </section>
</form>

<?php
/* Peta kode voucher -> potongan, dipakai untuk estimasi total di sisi klien. */
$voucher_map = [];
mysqli_data_seek($voucher_aktif, 0);
while ($v = mysqli_fetch_assoc($voucher_aktif)) {
    $voucher_map[$v['kode_voucher']] = (float) $v['potongan_harga'];
}
?>
<script>
window.__voucherMap = <?= json_encode($voucher_map, JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php
$page_script = 'assets/js/orders.js';
require_once __DIR__ . '/../../includes/footer.php';
?>
