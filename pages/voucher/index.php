<?php
/**
 * Ulasan & voucher: daftar voucher + klaim review member.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'Kasir']);

$tab = $_GET['tab'] ?? 'klaim';
if (!in_array($tab, ['klaim', 'voucher'], true)) {
    $tab = 'klaim';
}

$status_klaim = $_GET['status'] ?? 'pending';
if (!in_array($status_klaim, ['pending', 'approved', 'rejected', 'semua'], true)) {
    $status_klaim = 'pending';
}

/* --------------------------------------------------- Klaim review */
$sql = "SELECT k.*, m.name AS nama_member, m.email, m.member_status, v.kode_voucher, pg.nama AS nama_validator
    FROM klaim_review k
    JOIN member m ON m.member_id = k.member_id
    LEFT JOIN voucher v ON v.voucher_id = k.voucher_id
    LEFT JOIN pegawai pg ON pg.pegawai_id = k.pegawai_id
    WHERE 1=1";
$params = [];
$types  = '';

if ($status_klaim !== 'semua') {
    $sql .= ' AND k.status_validasi = ?';
    $params[] = $status_klaim;
    $types .= 's';
}
$sql .= ' ORDER BY k.created_at ASC';

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$klaim_list = mysqli_stmt_get_result($stmt);

$hitung_klaim = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    SUM(status_validasi = 'pending') AS pending,
    SUM(status_validasi = 'approved') AS approved,
    SUM(status_validasi = 'rejected') AS rejected,
    COUNT(*) AS total FROM klaim_review"));

/* ------------------------------------------------------- Voucher */
$voucher_list = mysqli_query($conn, "SELECT v.*,
        (SELECT COUNT(*) FROM pemesanan p WHERE p.voucher_id = v.voucher_id) AS dipakai_pesanan,
        (SELECT COUNT(*) FROM klaim_review k WHERE k.voucher_id = v.voucher_id) AS dipakai_klaim
    FROM voucher v
    ORDER BY v.deleted_at IS NOT NULL ASC, v.valid_until DESC, v.voucher_id DESC");

$voucher_aktif_untuk_approve = mysqli_query($conn, "SELECT voucher_id, kode_voucher, potongan_harga, valid_until
    FROM voucher WHERE is_valid = 1 AND deleted_at IS NULL AND valid_until >= CURDATE()
    ORDER BY potongan_harga DESC");

$voucher_tersedia = [];
while ($v = mysqli_fetch_assoc($voucher_aktif_untuk_approve)) {
    $voucher_tersedia[] = $v;
}

$page_title    = 'Ulasan & voucher';
$page_subtitle = 'Validasi bukti ulasan Google Maps dan kelola kode voucher yang berlaku.';
$active_menu   = 'voucher';

if ($tab === 'voucher') {
    $page_actions = '<a class="btn btn--primary" href="' . e(base_url('pages/voucher/tambah.php')) . '">' . icon('plus', 16) . ' Buat voucher</a>';
} else {
    $page_actions = '<a class="btn btn--secondary" href="' . e(base_url('pages/voucher/ulasan.php')) . '">' . icon('plus', 16) . ' Catat klaim review</a>';
}

require_once __DIR__ . '/../../includes/header.php';
?>

<nav class="tabs" aria-label="Bagian ulasan dan voucher">
  <a class="tab<?= $tab === 'klaim' ? ' is-active' : '' ?>" href="?tab=klaim">
    Klaim review <span class="tab__count"><?= (int) $hitung_klaim['total'] ?></span>
  </a>
  <a class="tab<?= $tab === 'voucher' ? ' is-active' : '' ?>" href="?tab=voucher">
    Daftar voucher
  </a>
</nav>

<?php if ($tab === 'klaim'): ?>

  <nav class="tabs" aria-label="Filter status klaim">
    <?php
    $tab_klaim = [
        'pending'  => ['Pending', (int) $hitung_klaim['pending']],
        'approved' => ['Approved', (int) $hitung_klaim['approved']],
        'rejected' => ['Rejected', (int) $hitung_klaim['rejected']],
        'semua'    => ['Semua', (int) $hitung_klaim['total']],
    ];
    foreach ($tab_klaim as $key => [$label, $jumlah]):
    ?>
      <a class="tab<?= $status_klaim === $key ? ' is-active' : '' ?>" href="?tab=klaim&status=<?= e($key) ?>">
        <?= e($label) ?> <span class="tab__count"><?= $jumlah ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <article class="card">
    <div class="card__head">
      <div>
        <h2>Klaim bukti ulasan</h2>
        <p>Klaim diurutkan dari yang paling lama menunggu. Buka tautan bukti sebelum memutuskan.</p>
      </div>
    </div>

    <div class="table-wrap">
      <?php if (mysqli_num_rows($klaim_list) === 0): ?>
        <div class="empty">
          <div class="empty__icon"><?= icon('voucher', 20) ?></div>
          <h3>Tidak ada klaim pada status ini</h3>
          <p><?= $status_klaim === 'pending' ? 'Semua klaim sudah diproses. Klaim baru dari member akan muncul di sini.' : 'Coba pilih tab status lain untuk melihat riwayat klaim.' ?></p>
        </div>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Klaim</th>
              <th>Member</th>
              <th>Bukti ulasan</th>
              <th>Diajukan</th>
              <th>Status</th>
              <th class="col-actions">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($k = mysqli_fetch_assoc($klaim_list)): ?>
              <tr>
                <td>
                  <div class="cell-stack">
                    <span class="strong">#<?= (int) $k['klaim_id'] ?></span>
                    <?php if ($k['kode_voucher']): ?>
                      <small>Voucher <?= e($k['kode_voucher']) ?></small>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div class="cell-stack">
                    <span><?= e($k['nama_member']) ?></span>
                    <small><?= e($k['email']) ?></small>
                  </div>
                </td>
                <td>
                  <a class="link-quiet" href="<?= e($k['url_bukti']) ?>" target="_blank" rel="noopener noreferrer">
                    <?= icon('link', 14) ?> Buka bukti
                  </a>
                </td>
                <td class="muted nowrap"><?= e(tanggal_id($k['created_at'], true)) ?></td>
                <td>
                  <?= badge_klaim($k['status_validasi']) ?>
                  <?php if ($k['nama_validator']): ?>
                    <div class="text-small text-muted">oleh <?= e($k['nama_validator']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="col-actions">
                  <?php if ($k['status_validasi'] === 'pending'): ?>
                    <?php if ($k['member_status'] !== 'aktif'): ?>
                      <span class="text-small text-muted">Member nonaktif</span>
                    <?php elseif (empty($voucher_tersedia)): ?>
                      <a class="btn btn--secondary btn--sm" href="<?= e(base_url('pages/voucher/tambah.php')) ?>">Buat voucher dulu</a>
                    <?php else: ?>
                      <span class="btn-group">
                        <button class="btn btn--primary btn--sm" type="button"
                                data-modal-open="#modalApprove"
                                data-klaim-id="<?= (int) $k['klaim_id'] ?>"
                                data-klaim-member="<?= e($k['nama_member']) ?>">
                          <?= icon('check', 15) ?> Approve
                        </button>
                        <form class="inline-form" method="post" action="<?= e(base_url('pages/voucher/proses.php')) ?>"
                              data-confirm="Tolak klaim review dari <?= e($k['nama_member']) ?>?">
                          <?= csrf_field() ?>
                          <input type="hidden" name="action" value="tolak_klaim">
                          <input type="hidden" name="klaim_id" value="<?= (int) $k['klaim_id'] ?>">
                          <button class="btn btn--danger btn--sm" type="submit"><?= icon('close', 15) ?> Reject</button>
                        </form>
                      </span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-small text-muted">Sudah diproses</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </article>

  <dialog class="modal" id="modalApprove" aria-labelledby="judulApprove">
    <form method="post" action="<?= e(base_url('pages/voucher/proses.php')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="approve_klaim">
      <input type="hidden" name="klaim_id" value="" data-input="klaimId">

      <div class="modal__head">
        <h2 id="judulApprove">Setujui klaim review</h2>
        <p>Klaim dari <strong data-slot="klaimMember">member</strong> akan diberi satu voucher aktif.</p>
      </div>

      <div class="modal__body">
        <div class="field">
          <label for="voucher_id">Voucher yang diberikan</label>
          <select class="select" id="voucher_id" name="voucher_id" required>
            <?php foreach ($voucher_tersedia as $v): ?>
              <option value="<?= (int) $v['voucher_id'] ?>">
                <?= e($v['kode_voucher']) ?> — <?= e(rupiah($v['potongan_harga'])) ?> (s.d. <?= e(tanggal_id($v['valid_until'])) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <span class="hint">Hanya voucher valid dan belum kedaluwarsa yang ditampilkan.</span>
        </div>
      </div>

      <div class="modal__foot">
        <button class="btn btn--secondary" type="button" data-modal-close>Batal</button>
        <button class="btn btn--primary" type="submit">Setujui klaim</button>
      </div>
    </form>
  </dialog>

<?php else: ?>

  <article class="card">
    <div class="card__head">
      <div>
        <h2>Daftar voucher</h2>
        <p>Voucher nonaktif tetap tampil agar riwayat pemakaian dapat ditelusuri.</p>
      </div>
    </div>

    <div class="table-wrap">
      <?php if (mysqli_num_rows($voucher_list) === 0): ?>
        <div class="empty">
          <div class="empty__icon"><?= icon('voucher', 20) ?></div>
          <h3>Belum ada voucher</h3>
          <p>Buat voucher pertama agar klaim ulasan dapat langsung diberikan.</p>
          <a class="btn btn--primary" href="<?= e(base_url('pages/voucher/tambah.php')) ?>">Buat voucher</a>
        </div>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Kode</th>
              <th class="num">Potongan</th>
              <th>Berlaku s.d.</th>
              <th class="num">Dipakai</th>
              <th>Status</th>
              <th class="col-actions">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($v = mysqli_fetch_assoc($voucher_list)): ?>
              <?php
              $kedaluwarsa = strtotime($v['valid_until']) < strtotime(date('Y-m-d'));
              $nonaktif = $v['deleted_at'] !== null || !(int) $v['is_valid'];
              ?>
              <tr>
                <td>
                  <div class="cell-stack">
                    <span class="strong"><?= e($v['kode_voucher']) ?></span>
                    <small>ID <?= (int) $v['voucher_id'] ?></small>
                  </div>
                </td>
                <td class="num strong"><?= e(rupiah($v['potongan_harga'])) ?></td>
                <td class="nowrap">
                  <?= e(tanggal_id($v['valid_until'])) ?>
                  <?php if ($kedaluwarsa && !$nonaktif): ?>
                    <div class="text-small" style="color:var(--red)">Kedaluwarsa</div>
                  <?php endif; ?>
                </td>
                <td class="num">
                  <?= (int) $v['dipakai_pesanan'] + (int) $v['dipakai_klaim'] ?> kali
                  <div class="muted text-small"><?= (int) $v['dipakai_klaim'] ?> dari klaim</div>
                </td>
                <td>
                  <?php if ($v['deleted_at'] !== null): ?>
                    <span class="badge badge--neutral">Dihapus</span>
                  <?php elseif (!(int) $v['is_valid']): ?>
                    <span class="badge badge--neutral">Nonaktif</span>
                  <?php elseif ($kedaluwarsa): ?>
                    <span class="badge badge--warning">Kedaluwarsa</span>
                  <?php else: ?>
                    <span class="badge badge--success">Aktif</span>
                  <?php endif; ?>
                </td>
                <td class="col-actions">
                  <?php if ($v['deleted_at'] === null): ?>
                    <span class="btn-group">
                      <a class="btn btn--ghost btn--sm" href="<?= e(base_url('pages/voucher/edit.php?id=' . (int) $v['voucher_id'])) ?>"><?= icon('edit', 15) ?> Edit</a>
                      <form class="inline-form" method="post" action="<?= e(base_url('pages/voucher/proses.php')) ?>"
                            data-confirm="Nonaktifkan voucher <?= e($v['kode_voucher']) ?>? Voucher tidak dapat dipakai lagi.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="nonaktifkan">
                        <input type="hidden" name="voucher_id" value="<?= (int) $v['voucher_id'] ?>">
                        <button class="btn btn--danger btn--sm" type="submit"><?= icon('trash', 15) ?> Nonaktifkan</button>
                      </form>
                    </span>
                  <?php else: ?>
                    <span class="text-small text-muted">Arsip</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </article>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
