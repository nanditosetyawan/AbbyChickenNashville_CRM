<?php
/**
 * Detail tiket keluhan: isi keluhan, PIC, ubah status, assign.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'PIC Keluhan']);

$role       = $_SESSION['role'];
$pegawai_id = (int) $_SESSION['pegawai_id'];
$id         = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT t.*, m.name AS nama_member, m.email, m.no_telp, m.total_poin,
        pg.nama AS nama_pic
    FROM tiket t
    JOIN member m ON m.member_id = t.member_id
    LEFT JOIN pegawai pg ON pg.pegawai_id = t.pegawai_id
    WHERE t.tiket_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$tiket = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$tiket) {
    set_flash('error', 'Tiket tidak ditemukan.');
    redirect(base_url('pages/complaints/index.php'));
}

// PIC hanya boleh membuka tiket yang di-assign kepadanya.
if ($role === 'PIC Keluhan' && (int) $tiket['pegawai_id'] !== $pegawai_id) {
    set_flash('error', 'Tiket ini tidak di-assign kepada Anda.');
    redirect(base_url('pages/complaints/index.php'));
}

$pic_list = [];
$q = mysqli_query($conn, "SELECT pegawai_id, nama FROM pegawai WHERE role = 'PIC Keluhan' ORDER BY nama");
while ($r = mysqli_fetch_assoc($q)) {
    $pic_list[] = $r;
}

/* Durasi penanganan untuk tiket closed */
$durasi = null;
if ($tiket['status'] === 'closed' && $tiket['closed_at']) {
    $detik = strtotime($tiket['closed_at']) - strtotime($tiket['created_at']);
    if ($detik > 0) {
        $jam = floor($detik / 3600);
        $durasi = $jam >= 24
            ? floor($jam / 24) . ' hari ' . ($jam % 24) . ' jam'
            : $jam . ' jam ' . floor(($detik % 3600) / 60) . ' menit';
    }
}

$page_title    = 'Tiket #' . $id;
$page_subtitle = 'Dibuat ' . tanggal_id($tiket['created_at'], true) . ' oleh ' . $tiket['nama_member'] . '.';
$active_menu   = 'complaints';
$page_actions  = '<a class="btn btn--secondary" href="' . e(base_url('pages/complaints/index.php')) . '">' . icon('arrow-left', 16) . ' Kembali</a>';

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="detail-grid">
  <div class="stack">
    <article class="card">
      <div class="stat-strip">
        <div class="stat-strip__item">
          <span>Status</span>
          <strong style="font-size:13px"><?= badge_tiket($tiket['status']) ?></strong>
        </div>
        <div class="stat-strip__item">
          <span>Penanggung jawab</span>
          <strong style="font-size:13px"><?= $tiket['nama_pic'] ? e($tiket['nama_pic']) : 'Belum di-assign' ?></strong>
        </div>
        <div class="stat-strip__item">
          <span>Waktu penanganan</span>
          <strong style="font-size:13px"><?= $durasi ? e($durasi) : ($tiket['status'] === 'closed' ? '-' : 'Berjalan') ?></strong>
        </div>
      </div>

      <div class="card__head">
        <div>
          <h2><?= e($tiket['kategori_keluhan']) ?></h2>
          <p>Dibuat <?= e(tanggal_id($tiket['created_at'], true)) ?> &middot; <?= e(waktu_lalu($tiket['created_at'])) ?></p>
        </div>
      </div>

      <div class="card__body">
        <p style="white-space:pre-line"><?= e($tiket['isi_keluhan']) ?></p>
      </div>

      <?php if ($tiket['closed_at']): ?>
        <div class="card__foot">
          <span>Ditutup <?= e(tanggal_id($tiket['closed_at'], true)) ?></span>
          <span><?= $durasi ? 'Durasi ' . e($durasi) : '' ?></span>
        </div>
      <?php endif; ?>
    </article>

    <?php if ($role === 'Admin' || (int) $tiket['pegawai_id'] === $pegawai_id): ?>
      <article class="card">
        <div class="card__head">
          <div>
            <h2>Tindakan</h2>
            <p>
              <?php if ($tiket['status'] === 'open'): ?>
                Assign tiket ke PIC Keluhan untuk mulai menangani keluhan ini.
              <?php elseif ($tiket['status'] === 'in_progress'): ?>
                Tandai closed setelah keluhan benar-benar terselesaikan.
              <?php else: ?>
                Tiket sudah ditutup dan tidak dapat diubah lagi.
              <?php endif; ?>
            </p>
          </div>
        </div>

        <div class="card__body">
          <?php if ($tiket['status'] === 'closed'): ?>
            <p class="text-small text-muted">Tiket closed bersifat final. Bila pelanggan menghubungi lagi, buat tiket baru agar riwayat penanganan tetap terpisah.</p>
          <?php else: ?>
            <div class="btn-group" style="flex-wrap:wrap">
              <?php if ($role === 'Admin' && $tiket['status'] === 'open'): ?>
                <?php if (empty($pic_list)): ?>
                  <a class="btn btn--secondary" href="<?= e(base_url('pages/pegawai/index.php')) ?>">Tambahkan PIC Keluhan</a>
                <?php else: ?>
                  <form class="inline-form" method="post" action="<?= e(base_url('pages/complaints/proses.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="tiket_id" value="<?= (int) $id ?>">
                    <input type="hidden" name="kembali" value="detail">
                    <label class="visually-hidden" for="pegawai_id">Pilih PIC</label>
                    <select class="select" id="pegawai_id" name="pegawai_id" style="width:auto" required>
                      <?php foreach ($pic_list as $pic): ?>
                        <option value="<?= (int) $pic['pegawai_id'] ?>"><?= e($pic['nama']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn--teal" type="submit">Assign PIC</button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>

              <?php if ($tiket['status'] === 'in_progress'): ?>
                <form class="inline-form" method="post" action="<?= e(base_url('pages/complaints/proses.php')) ?>"
                      data-confirm="Tandai tiket ini sebagai closed? Status akhir tidak dapat dibatalkan.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="tutup">
                  <input type="hidden" name="tiket_id" value="<?= (int) $id ?>">
                  <input type="hidden" name="kembali" value="detail">
                  <button class="btn btn--primary" type="submit"><?= icon('check', 16) ?> Tandai closed</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </article>
    <?php endif; ?>
  </div>

  <div class="stack">
    <article class="card">
      <div class="card__head">
        <div>
          <h2>Data member</h2>
        </div>
      </div>
      <div class="card__body">
        <dl class="dl">
          <dt>Nama</dt>
          <dd><?= e($tiket['nama_member']) ?></dd>
          <dt>Email</dt>
          <dd><?= e($tiket['email']) ?></dd>
          <dt>No. telepon</dt>
          <dd><?= e(tampil_telepon($tiket['no_telp'])) ?></dd>
          <dt>Poin</dt>
          <dd><?= number_format((int) $tiket['total_poin'], 0, ',', '.') ?> poin</dd>
          <dt>ID member</dt>
          <dd>#<?= (int) $tiket['member_id'] ?></dd>
        </dl>
        <?php if ($role === 'Admin'): ?>
          <a class="btn btn--secondary btn--block mt-16" href="<?= e(base_url('pages/membership/detail.php?id=' . (int) $tiket['member_id'])) ?>">Lihat profil member</a>
        <?php endif; ?>
      </div>
    </article>

    <article class="card">
      <div class="card__head">
        <div>
          <h2>Riwayat status</h2>
          <p>Jejak waktu penanganan tiket.</p>
        </div>
      </div>
      <div class="card__body">
        <ul class="timeline">
          <li>
            <div class="timeline__head">Tiket dibuat <?= badge_tiket('open') ?></div>
            <p><?= e(tanggal_id($tiket['created_at'], true)) ?> &middot; <?= e($tiket['nama_member']) ?></p>
          </li>
          <?php if ($tiket['pegawai_id']): ?>
            <li>
              <div class="timeline__head">Di-assign ke PIC <?= badge_tiket('in_progress') ?></div>
              <p>Ditangani oleh <?= e($tiket['nama_pic']) ?></p>
            </li>
          <?php endif; ?>
          <?php if ($tiket['closed_at']): ?>
            <li>
              <div class="timeline__head">Tiket ditutup <?= badge_tiket('closed') ?></div>
              <p><?= e(tanggal_id($tiket['closed_at'], true)) ?><?= $durasi ? ' · durasi ' . e($durasi) : '' ?></p>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </article>
  </div>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
