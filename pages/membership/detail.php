<?php
/**
 * Detail member: profil, statistik, riwayat pesanan, tiket, klaim review.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin']);

$id = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, 'SELECT * FROM member WHERE member_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$member) {
    set_flash('error', 'Member tidak ditemukan.');
    redirect(base_url('pages/membership/index.php'));
}

/* Riwayat pesanan (descending waktu_pemesanan) */
$stmt = mysqli_prepare($conn, "SELECT p.*, v.kode_voucher
    FROM pemesanan p
    LEFT JOIN voucher v ON v.voucher_id = p.voucher_id
    WHERE p.member_id = ?
    ORDER BY p.waktu_pemesanan DESC");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$riwayat = mysqli_stmt_get_result($stmt);

/* Statistik */
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS jml,
        COALESCE(SUM(CASE WHEN status_pesanan <> 'dibatalkan' THEN jumlah_bayar ELSE 0 END),0) AS total,
        COALESCE(AVG(CASE WHEN status_pesanan <> 'dibatalkan' THEN jumlah_bayar END),0) AS rata
    FROM pemesanan WHERE member_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$stat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

/* Tiket member */
$stmt = mysqli_prepare($conn, "SELECT t.*, pg.nama AS nama_pic
    FROM tiket t LEFT JOIN pegawai pg ON pg.pegawai_id = t.pegawai_id
    WHERE t.member_id = ? ORDER BY t.created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$tiket_member = mysqli_stmt_get_result($stmt);

/* Klaim review member */
$stmt = mysqli_prepare($conn, "SELECT k.*, v.kode_voucher
    FROM klaim_review k LEFT JOIN voucher v ON v.voucher_id = k.voucher_id
    WHERE k.member_id = ? ORDER BY k.created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$klaim_member = mysqli_stmt_get_result($stmt);

$page_title    = 'Detail member';
$page_subtitle = 'Profil, riwayat transaksi, dan interaksi pelanggan.';
$active_menu   = 'membership';
$page_actions  = '<a class="btn btn--secondary" href="' . e(base_url('pages/membership/index.php')) . '">' . icon('arrow-left', 16) . ' Kembali</a>'
    . '<a class="btn btn--primary" href="' . e(base_url('pages/membership/edit.php?id=' . $id)) . '">' . icon('edit', 16) . ' Edit member</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="detail-grid">
  <div class="stack">
    <article class="card">
      <div class="stat-strip">
        <div class="stat-strip__item">
          <span>Total pesanan</span>
          <strong><?= (int) $stat['jml'] ?></strong>
        </div>
        <div class="stat-strip__item">
          <span>Total belanja</span>
          <strong><?= e(rupiah($stat['total'])) ?></strong>
        </div>
        <div class="stat-strip__item">
          <span>Rata-rata / pesanan</span>
          <strong><?= e(rupiah($stat['rata'])) ?></strong>
        </div>
      </div>

      <div class="card__head">
        <div>
          <h2>Riwayat transaksi</h2>
          <p>Diurutkan dari transaksi terbaru.</p>
        </div>
      </div>

      <div class="table-wrap">
        <?php if (mysqli_num_rows($riwayat) === 0): ?>
          <div class="empty">
            <div class="empty__icon"><?= icon('order', 20) ?></div>
            <h3>Belum ada transaksi</h3>
            <p>Member ini belum pernah melakukan pemesanan. Buat pesanan baru untuk mulai mencatat riwayat.</p>
            <a class="btn btn--primary" href="<?= e(base_url('pages/orders/buat.php?member_id=' . $id)) ?>">Buat pesanan</a>
          </div>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th>ID pesanan</th>
                <th>Waktu</th>
                <th>Voucher</th>
                <th class="num">Diskon</th>
                <th class="num">Jumlah bayar</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($p = mysqli_fetch_assoc($riwayat)): ?>
                <tr>
                  <td>
                    <a class="strong" href="<?= e(base_url('pages/orders/detail.php?id=' . (int) $p['pemesanan_id'])) ?>">
                      #<?= (int) $p['pemesanan_id'] ?>
                    </a>
                    <div class="muted">Antrian <?= (int) $p['nomor_antrian'] ?></div>
                  </td>
                  <td class="muted nowrap"><?= e(tanggal_id($p['waktu_pemesanan'], true)) ?></td>
                  <td><?= $p['kode_voucher'] ? '<span class="badge badge--neutral">' . e($p['kode_voucher']) . '</span>' : '<span class="muted">-</span>' ?></td>
                  <td class="num"><?= $p['total_diskon'] > 0 ? e(rupiah($p['total_diskon'])) : '-' ?></td>
                  <td class="num strong"><?= e(rupiah($p['jumlah_bayar'])) ?></td>
                  <td><?= badge_pesanan($p['status_pesanan']) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </article>

    <article class="card">
      <div class="card__head">
        <div>
          <h2>Tiket keluhan</h2>
          <p>Keluhan yang diajukan member ini.</p>
        </div>
      </div>
      <?php if (mysqli_num_rows($tiket_member) === 0): ?>
        <div class="empty">
          <div class="empty__icon"><?= icon('complaint', 20) ?></div>
          <h3>Tidak ada keluhan</h3>
          <p>Member ini belum pernah mengajukan tiket keluhan.</p>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Tiket</th>
                <th>Kategori</th>
                <th>PIC</th>
                <th>Dibuat</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($t = mysqli_fetch_assoc($tiket_member)): ?>
                <tr>
                  <td><a class="strong" href="<?= e(base_url('pages/complaints/detail.php?id=' . (int) $t['tiket_id'])) ?>">#<?= (int) $t['tiket_id'] ?></a></td>
                  <td><?= e($t['kategori_keluhan']) ?></td>
                  <td><?= $t['nama_pic'] ? e($t['nama_pic']) : '<span class="muted">Belum di-assign</span>' ?></td>
                  <td class="muted nowrap"><?= e(tanggal_id($t['created_at'])) ?></td>
                  <td><?= badge_tiket($t['status']) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </article>
  </div>

  <div class="stack">
    <article class="card">
      <div class="card__body">
        <div class="profile">
          <span class="profile__avatar"><?= e(strtoupper(mb_substr($member['name'], 0, 1))) ?></span>
          <div class="profile__text">
            <h2><?= e($member['name']) ?></h2>
            <p>Member #<?= (int) $member['member_id'] ?> &middot; bergabung <?= e(tanggal_id($member['tgl_join'])) ?></p>
          </div>
        </div>

        <div class="mt-16">
          <?= badge_member($member['member_status']) ?>
          <span class="badge badge--info"><?= number_format((int) $member['total_poin'], 0, ',', '.') ?> poin</span>
        </div>
      </div>

      <div class="card__body" style="border-top:1px solid var(--gray-100)">
        <dl class="dl">
          <dt>Email</dt>
          <dd><?= e($member['email']) ?></dd>
          <dt>No. telepon</dt>
          <dd><?= e(tampil_telepon($member['no_telp'])) ?></dd>
          <dt>Tanggal join</dt>
          <dd><?= e(tanggal_id($member['tgl_join'])) ?></dd>
          <dt>Total poin</dt>
          <dd><?= number_format((int) $member['total_poin'], 0, ',', '.') ?> poin</dd>
          <dt>Status</dt>
          <dd><?= $member['member_status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?></dd>
        </dl>
      </div>
    </article>

    <article class="card">
      <div class="card__head">
        <div>
          <h2>Klaim review</h2>
          <p>Riwayat klaim voucher dari ulasan Google Maps.</p>
        </div>
      </div>
      <?php if (mysqli_num_rows($klaim_member) === 0): ?>
        <div class="empty">
          <div class="empty__icon"><?= icon('voucher', 20) ?></div>
          <h3>Belum ada klaim</h3>
          <p>Member belum mengirim bukti ulasan untuk mendapatkan voucher.</p>
        </div>
      <?php else: ?>
        <ul class="mini-list">
          <?php while ($k = mysqli_fetch_assoc($klaim_member)): ?>
            <li>
              <span class="mini-list__name">
                Klaim #<?= (int) $k['klaim_id'] ?>
                <small><?= e(tanggal_id($k['created_at'], true)) ?><?= $k['kode_voucher'] ? ' · ' . e($k['kode_voucher']) : '' ?></small>
              </span>
              <?= badge_klaim($k['status_validasi']) ?>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php endif; ?>
    </article>
  </div>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
