<?php
/**
 * Promo & notifikasi in-app (US-3.4).
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin']);

$error = [];
$data  = ['judul' => '', 'isi' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = $_POST['action'] ?? '';

    if ($action === 'buat') {
        $data['judul'] = trim($_POST['judul'] ?? '');
        $data['isi']   = trim($_POST['isi'] ?? '');

        if ($data['judul'] === '' || mb_strlen($data['judul']) < 5) {
            $error['judul'] = 'Judul minimal 5 karakter.';
        } elseif (mb_strlen($data['judul']) > 150) {
            $error['judul'] = 'Judul maksimal 150 karakter.';
        }

        if (mb_strlen($data['isi']) < 20) {
            $error['isi'] = 'Isi promo minimal 20 karakter agar informasinya jelas.';
        } elseif (mb_strlen($data['isi']) > 1000) {
            $error['isi'] = 'Isi promo maksimal 1000 karakter.';
        }

        if (empty($error)) {
            $stmt = mysqli_prepare($conn, 'INSERT INTO pengumuman (pegawai_id, judul, isi, aktif) VALUES (?, ?, ?, 1)');
            $pid = (int) $_SESSION['pegawai_id'];
            mysqli_stmt_bind_param($stmt, 'iss', $pid, $data['judul'], $data['isi']);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                set_flash('success', 'Pengumuman promo berhasil diterbitkan.');
                redirect(base_url('pages/promo/index.php'));
            }
            $error['umum'] = 'Gagal menyimpan pengumuman: ' . mysqli_error($conn);
            mysqli_stmt_close($stmt);
        }
    }

    if ($action === 'toggle') {
        $pid = (int) ($_POST['pengumuman_id'] ?? 0);
        $stmt = mysqli_prepare($conn, 'UPDATE pengumuman SET aktif = NOT aktif WHERE pengumuman_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $pid);
        mysqli_stmt_execute($stmt);
        $n = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        set_flash($n === 1 ? 'success' : 'error', $n === 1 ? 'Status pengumuman diperbarui.' : 'Pengumuman tidak ditemukan.');
        redirect(base_url('pages/promo/index.php'));
    }

    if ($action === 'hapus') {
        $pid = (int) ($_POST['pengumuman_id'] ?? 0);
        $stmt = mysqli_prepare($conn, 'DELETE FROM pengumuman WHERE pengumuman_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $pid);
        mysqli_stmt_execute($stmt);
        $n = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        set_flash($n === 1 ? 'success' : 'error', $n === 1 ? 'Pengumuman dihapus.' : 'Pengumuman tidak ditemukan.');
        redirect(base_url('pages/promo/index.php'));
    }
}

$daftar = mysqli_query($conn, "SELECT p.*, pg.nama AS penulis
    FROM pengumuman p LEFT JOIN pegawai pg ON pg.pegawai_id = p.pegawai_id
    ORDER BY p.aktif DESC, p.created_at DESC");

$total_aktif = (int) (mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) n FROM pengumuman WHERE aktif = 1'))['n'] ?? 0);
$member_aktif = (int) (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM member WHERE member_status = 'aktif'"))['n'] ?? 0);

$page_title    = 'Promo & notifikasi';
$page_subtitle = 'Pengumuman aktif tampil sebagai notifikasi in-app bagi member di halaman pesan online.';
$active_menu   = 'promo';

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="kpi-grid">
  <article class="kpi kpi--accent">
    <p class="kpi__label"><?= icon('bell', 15) ?> Pengumuman aktif</p>
    <p class="kpi__value"><?= $total_aktif ?></p>
    <p class="kpi__meta">Terlihat oleh <strong><?= $member_aktif ?> member aktif</strong></p>
  </article>
</section>

<section class="detail-grid">
  <article class="card">
    <div class="card__head">
      <div>
        <h2>Daftar pengumuman</h2>
        <p>Pengumuman nonaktif tetap tersimpan sebagai arsip.</p>
      </div>
    </div>

    <?php if (mysqli_num_rows($daftar) === 0): ?>
      <div class="empty">
        <div class="empty__icon"><?= icon('bell', 20) ?></div>
        <h3>Belum ada pengumuman</h3>
        <p>Buat pengumuman promo pertama agar member mengetahui penawaran terbaru.</p>
      </div>
    <?php else: ?>
      <ul class="mini-list">
        <?php while ($p = mysqli_fetch_assoc($daftar)): ?>
          <li style="align-items:flex-start;padding:14px 20px">
            <span class="mini-list__name">
              <strong><?= e($p['judul']) ?></strong>
              <small style="margin-top:2px"><?= e(tanggal_id($p['created_at'], true)) ?> · <?= e($p['penulis'] ?? 'Sistem') ?></small>
              <span class="text-small mt-8" style="display:block;color:var(--gray-600)"><?= e(potong($p['isi'], 130)) ?></span>
            </span>
            <span class="btn-group" style="flex:none">
              <?php if ((int) $p['aktif'] === 1): ?>
                <span class="badge badge--success">Aktif</span>
              <?php else: ?>
                <span class="badge badge--neutral">Arsip</span>
              <?php endif; ?>
              <form class="inline-form" method="post" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="pengumuman_id" value="<?= (int) $p['pengumuman_id'] ?>">
                <button class="btn btn--ghost btn--sm" type="submit"><?= (int) $p['aktif'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button>
              </form>
              <form class="inline-form" method="post" action="" data-confirm="Hapus pengumuman ini secara permanen?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="pengumuman_id" value="<?= (int) $p['pengumuman_id'] ?>">
                <button class="btn btn--danger btn--sm" type="submit"><?= icon('trash', 15) ?></button>
              </form>
            </span>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php endif; ?>
  </article>

  <form class="card" method="post" action="" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="buat">

    <div class="card__head">
      <div>
        <h2>Buat pengumuman</h2>
        <p>Notifikasi tampil di halaman pesan online member.</p>
      </div>
    </div>

    <div class="card__body">
      <?php if (!empty($error['umum'])): ?>
        <div class="flash flash--error mb-16"><span class="flash__icon"><?= icon('close', 16) ?></span><p><?= e($error['umum']) ?></p></div>
      <?php endif; ?>

      <div class="form-grid form-grid--1">
        <div class="field">
          <label for="judul">Judul promo <span aria-hidden="true">*</span></label>
          <input class="input<?= isset($error['judul']) ? ' is-invalid' : '' ?>" type="text" id="judul" name="judul"
                 value="<?= e($data['judul']) ?>" maxlength="150" placeholder="Promo Nashville Combo November" required>
          <?php if (isset($error['judul'])): ?><span class="error"><?= e($error['judul']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="isi">Isi promo <span aria-hidden="true">*</span></label>
          <textarea class="textarea<?= isset($error['isi']) ? ' is-invalid' : '' ?>" id="isi" name="isi" maxlength="1000"
                    placeholder="Jelaskan penawaran, syarat, dan masa berlakunya." required><?= e($data['isi']) ?></textarea>
          <?php if (isset($error['isi'])): ?>
            <span class="error"><?= e($error['isi']) ?></span>
          <?php else: ?>
            <span class="hint">Minimal 20 karakter, maksimal 1000 karakter.</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-actions">
        <button class="btn btn--primary btn--block" type="submit"><?= icon('bell', 16) ?> Terbitkan pengumuman</button>
      </div>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
