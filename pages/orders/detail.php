<?php
/**
 * Detail pesanan: item, total, dan aksi ubah status.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'Kasir']);

$id = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT p.*, m.name AS nama_member, m.email, m.no_telp, m.total_poin,
        v.kode_voucher, v.potongan_harga
    FROM pemesanan p
    JOIN member m ON m.member_id = p.member_id
    LEFT JOIN voucher v ON v.voucher_id = p.voucher_id
    WHERE p.pemesanan_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$pesanan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$pesanan) {
    set_flash('error', 'Pesanan tidak ditemukan.');
    redirect(base_url('pages/orders/index.php'));
}

$stmt = mysqli_prepare($conn, "SELECT d.*, m.name, m.kategori, m.harga
    FROM detail_pesanan d JOIN menu m ON m.menu_id = d.menu_id
    WHERE d.pemesanan_id = ? ORDER BY d.detail_id ASC");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);

$subtotal = 0.0;
$items_arr = [];
while ($row = mysqli_fetch_assoc($items)) {
    $items_arr[] = $row;
    $subtotal += (float) $row['subtotal'];
}

$page_title    = 'Pesanan #' . $id;
$page_subtitle = 'Dibuat ' . tanggal_id($pesanan['waktu_pemesanan'], true) . ' untuk ' . $pesanan['nama_member'] . '.';
$active_menu   = 'orders';
$page_actions  = '<a class="btn btn--secondary" href="' . e(base_url('pages/orders/index.php')) . '">' . icon('arrow-left', 16) . ' Kembali ke antrian</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="detail-grid">
  <div class="stack">
    <article class="card">
      <div class="stat-strip">
        <div class="stat-strip__item">
          <span>Nomor antrian</span>
          <strong>#<?= (int) $pesanan['nomor_antrian'] ?></strong>
        </div>
        <div class="stat-strip__item">
          <span>Status pesanan</span>
          <strong style="font-size:13px"><?= badge_pesanan($pesanan['status_pesanan']) ?></strong>
        </div>
        <div class="stat-strip__item">
          <span>Jumlah bayar</span>
          <strong><?= e(rupiah($pesanan['jumlah_bayar'])) ?></strong>
        </div>
      </div>

      <div class="card__head">
        <div>
          <h2>Item pesanan</h2>
          <p><?= count($items_arr) ?> jenis menu dipesan.</p>
        </div>
      </div>

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Menu</th>
              <th>Kategori</th>
              <th class="num">Harga</th>
              <th class="num">Jumlah</th>
              <th class="num">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items_arr as $it): ?>
              <tr>
                <td class="strong">
                  <?= e($it['name']) ?>
                  <?php if (!empty($it['catatan'])): ?>
                    <span class="badge badge--warning" style="margin-left:6px;font-size:11px;font-weight:700;">(<?= e($it['catatan']) ?>)</span>
                  <?php endif; ?>
                </td>
                <td><span class="badge badge--neutral"><?= e($it['kategori']) ?></span></td>
                <td class="num"><?= e(rupiah($it['harga'])) ?></td>
                <td class="num"><?= (int) $it['jumlah_pesanan'] ?></td>
                <td class="num strong"><?= e(rupiah($it['subtotal'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4" class="text-right muted">Subtotal</td>
              <td class="num"><?= e(rupiah($subtotal)) ?></td>
            </tr>
            <?php if ((float) $pesanan['total_diskon'] > 0): ?>
              <tr>
                <td colspan="4" class="text-right muted">
                  Diskon voucher <?= $pesanan['kode_voucher'] ? '(' . e($pesanan['kode_voucher']) . ')' : '' ?>
                </td>
                <td class="num" style="color:var(--green)">-<?= e(rupiah($pesanan['total_diskon'])) ?></td>
              </tr>
            <?php endif; ?>
            <tr>
              <td colspan="4" class="text-right strong">Jumlah bayar</td>
              <td class="num strong" style="font-size:15px"><?= e(rupiah($pesanan['jumlah_bayar'])) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </article>

    <article class="card">
      <div class="card__head">
        <div>
          <h2>Ubah status pesanan</h2>
          <p>Alur normal: menunggu &rarr; diproses &rarr; selesai. Pesanan dapat dibatalkan sebelum selesai.</p>
        </div>
      </div>
      <div class="card__body">
        <?php if ($pesanan['status_pesanan'] === 'selesai' || $pesanan['status_pesanan'] === 'dibatalkan'): ?>
          <p class="text-small text-muted">
            Pesanan sudah <?= e($pesanan['status_pesanan']) ?> dan tidak dapat diubah lagi.
            Status akhir ini menjaga konsistensi poin dan riwayat transaksi.
          </p>
        <?php else: ?>
          <div class="btn-group" style="flex-wrap:wrap">
            <?php
            $transisi = [];
            if ($pesanan['status_pesanan'] === 'menunggu') {
                $transisi['diproses'] = ['Proses pesanan', 'btn--teal'];
            }
            if ($pesanan['status_pesanan'] === 'diproses') {
                $transisi['selesai'] = ['Tandai selesai', 'btn--primary'];
            }
            $transisi['dibatalkan'] = ['Batalkan pesanan', 'btn--danger'];
            ?>
            <?php foreach ($transisi as $nilai => [$label, $kelas]): ?>
              <form class="inline-form" method="post" action="<?= e(base_url('pages/orders/proses.php')) ?>"
                    <?= $nilai === 'dibatalkan' ? 'data-confirm="Batalkan pesanan ini? Tindakan tidak dapat dibatalkan."' : '' ?>>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="ubah_status">
                <input type="hidden" name="pemesanan_id" value="<?= (int) $id ?>">
                <input type="hidden" name="status" value="<?= e($nilai) ?>">
                <input type="hidden" name="kembali" value="detail">
                <button class="btn <?= e($kelas) ?>" type="submit"><?= e($label) ?></button>
              </form>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </article>
  </div>

  <div class="stack">
    <article class="card">
      <div class="card__head">
        <div>
          <h2>Data pemesan</h2>
        </div>
      </div>
      <div class="card__body">
        <dl class="dl">
          <dt>Nama</dt>
          <dd><a href="<?= e(base_url('pages/membership/detail.php?id=' . (int) $pesanan['member_id'])) ?>"><?= e($pesanan['nama_member']) ?></a></dd>
          <dt>Email</dt>
          <dd><?= e($pesanan['email']) ?></dd>
          <dt>No. telepon</dt>
          <dd><?= e(tampil_telepon($pesanan['no_telp'])) ?></dd>
          <dt>Poin saat ini</dt>
          <dd><?= number_format((int) $pesanan['total_poin'], 0, ',', '.') ?> poin</dd>
        </dl>
      </div>
    </article>

    <article class="card">
      <div class="card__head">
        <div>
          <h2>Rincian pembayaran</h2>
        </div>
      </div>
      <div class="card__body">
        <div class="summary__row">
          <span>Subtotal item</span>
          <span><?= e(rupiah($subtotal)) ?></span>
        </div>
        <div class="summary__row summary__row--diskon">
          <span>Diskon</span>
          <span>-<?= e(rupiah($pesanan['total_diskon'])) ?></span>
        </div>
        <div class="summary__row">
          <span>Voucher</span>
          <span><?= $pesanan['kode_voucher'] ? e($pesanan['kode_voucher']) : 'Tidak digunakan' ?></span>
        </div>
        <div class="summary__total">
          <span>Jumlah bayar</span>
          <span><?= e(rupiah($pesanan['jumlah_bayar'])) ?></span>
        </div>
        <p class="text-small text-muted mt-12">Pembayaran dilakukan di kasir. Sistem hanya mencatat nilai transaksi.</p>
      </div>
    </article>
  </div>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
