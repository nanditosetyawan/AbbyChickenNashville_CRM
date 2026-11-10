<?php
/**
 * Manajemen pegawai (admin only).
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin']);

$error = [];
$data  = ['nama' => '', 'email' => '', 'role' => 'Kasir', 'no_telp' => '', 'alamat' => ''];

$role_pilihan = ['Admin', 'Pimpinan', 'Kasir', 'PIC Keluhan'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = $_POST['action'] ?? '';

    if ($action === 'tambah') {
        $data['nama']    = trim($_POST['nama'] ?? '');
        $data['email']   = trim($_POST['email'] ?? '');
        $data['role']    = $_POST['role'] ?? 'Kasir';
        $data['no_telp'] = preg_replace('/\D/', '', $_POST['no_telp'] ?? '');
        $data['alamat']  = trim($_POST['alamat'] ?? '');
        $password        = $_POST['password'] ?? '';

        if (mb_strlen($data['nama']) < 3) {
            $error['nama'] = 'Nama minimal 3 karakter.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $error['email'] = 'Format email tidak valid.';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT pegawai_id FROM pegawai WHERE email = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $data['email']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error['email'] = 'Email sudah dipakai pegawai lain.';
            }
            mysqli_stmt_close($stmt);
        }
        if (!in_array($data['role'], $role_pilihan, true)) {
            $error['role'] = 'Role tidak valid.';
        }
        if (strlen($password) < 8) {
            $error['password'] = 'Password minimal 8 karakter.';
        }

        if (empty($error)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, 'INSERT INTO pegawai (email, password, nama, role, alamat, no_telp) VALUES (?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'ssssss', $data['email'], $hash, $data['nama'], $data['role'], $data['alamat'], $data['no_telp']);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                set_flash('success', 'Pegawai ' . $data['nama'] . ' berhasil ditambahkan.');
                redirect(base_url('pages/pegawai/index.php'));
            }
            $error['umum'] = 'Gagal menyimpan pegawai: ' . mysqli_error($conn);
            mysqli_stmt_close($stmt);
        }
    }

    if ($action === 'hapus') {
        $pid = (int) ($_POST['pegawai_id'] ?? 0);

        if ($pid === (int) $_SESSION['pegawai_id']) {
            set_flash('error', 'Anda tidak dapat menghapus akun yang sedang dipakai.');
            redirect(base_url('pages/pegawai/index.php'));
        }

        $stmt = mysqli_prepare($conn, 'SELECT nama FROM pegawai WHERE pegawai_id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $pid);
        mysqli_stmt_execute($stmt);
        $target = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$target) {
            set_flash('error', 'Pegawai tidak ditemukan.');
            redirect(base_url('pages/pegawai/index.php'));
        }

        $stmt = mysqli_prepare($conn, 'DELETE FROM pegawai WHERE pegawai_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $pid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        set_flash('success', 'Pegawai ' . $target['nama'] . ' dihapus. Tiket dan klaim yang pernah ditangani menjadi tanpa PIC.');
        redirect(base_url('pages/pegawai/index.php'));
    }
}

$daftar = mysqli_query($conn, "SELECT p.*,
        (SELECT COUNT(*) FROM tiket t WHERE t.pegawai_id = p.pegawai_id) AS jml_tiket,
        (SELECT COUNT(*) FROM klaim_review k WHERE k.pegawai_id = p.pegawai_id) AS jml_validasi
    FROM pegawai p ORDER BY FIELD(p.role, 'Admin', 'Pimpinan', 'Kasir', 'PIC Keluhan'), p.nama ASC");

$page_title    = 'Pegawai';
$page_subtitle = 'Akun pegawai dan hak akses. Password disimpan dalam bentuk hash bcrypt.';
$active_menu   = 'pegawai';

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="detail-grid">
  <article class="card">
    <div class="card__head">
      <div>
        <h2>Daftar pegawai</h2>
        <p>Role menentukan modul yang dapat diakses.</p>
      </div>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>Role</th>
            <th class="num">Tiket</th>
            <th class="num">Validasi</th>
            <th class="col-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($p = mysqli_fetch_assoc($daftar)): ?>
            <tr>
              <td>
                <div class="cell-stack">
                  <span class="strong"><?= e($p['nama']) ?></span>
                  <small><?= e(!empty($p['no_telp']) ? tampil_telepon($p['no_telp']) : 'Telepon belum diisi') ?></small>
                </div>
              </td>
              <td><?= e($p['email']) ?></td>
              <td>
                <span class="badge <?= ($p['role'] === 'Admin' || $p['role'] === 'Pimpinan') ? 'badge--info' : ($p['role'] === 'Kasir' ? 'badge--success' : 'badge--warning') ?>">
                  <?= e($p['role']) ?>
                </span>
              </td>
              <td class="num"><?= (int) $p['jml_tiket'] ?></td>
              <td class="num"><?= (int) $p['jml_validasi'] ?></td>
              <td class="col-actions">
                <?php if ((int) $p['pegawai_id'] === (int) $_SESSION['pegawai_id']): ?>
                  <span class="text-small text-muted">Akun Anda</span>
                <?php else: ?>
                  <form class="inline-form" method="post" action=""
                        data-confirm="Hapus akun pegawai <?= e($p['nama']) ?>? Tindakan ini tidak dapat dibatalkan.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="pegawai_id" value="<?= (int) $p['pegawai_id'] ?>">
                    <button class="btn btn--danger btn--sm" type="submit"><?= icon('trash', 15) ?> Hapus</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </article>

  <form class="card" method="post" action="" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="tambah">

    <div class="card__head">
      <div>
        <h2>Tambah pegawai</h2>
        <p>Password minimal 8 karakter.</p>
      </div>
    </div>

    <div class="card__body">
      <?php if (!empty($error['umum'])): ?>
        <div class="flash flash--error mb-16"><span class="flash__icon"><?= icon('close', 16) ?></span><p><?= e($error['umum']) ?></p></div>
      <?php endif; ?>

      <div class="form-grid form-grid--1">
        <div class="field">
          <label for="nama">Nama lengkap <span aria-hidden="true">*</span></label>
          <input class="input<?= isset($error['nama']) ? ' is-invalid' : '' ?>" type="text" id="nama" name="nama"
                 value="<?= e($data['nama']) ?>" maxlength="100" required>
          <?php if (isset($error['nama'])): ?><span class="error"><?= e($error['nama']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="email">Email <span aria-hidden="true">*</span></label>
          <input class="input<?= isset($error['email']) ? ' is-invalid' : '' ?>" type="email" id="email" name="email"
                 value="<?= e($data['email']) ?>" maxlength="100" placeholder="nama@abbychicken.id" required>
          <?php if (isset($error['email'])): ?><span class="error"><?= e($error['email']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="role">Role <span aria-hidden="true">*</span></label>
          <select class="select<?= isset($error['role']) ? ' is-invalid' : '' ?>" id="role" name="role" required>
            <?php foreach ($role_pilihan as $r): ?>
              <option value="<?= e($r) ?>" <?= $data['role'] === $r ? 'selected' : '' ?>><?= e($r) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($error['role'])): ?><span class="error"><?= e($error['role']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="password">Password awal <span aria-hidden="true">*</span></label>
          <input class="input<?= isset($error['password']) ? ' is-invalid' : '' ?>" type="password" id="password" name="password"
                 minlength="8" autocomplete="new-password" required>
          <?php if (isset($error['password'])): ?><span class="error"><?= e($error['password']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label for="no_telp">Nomor telepon</label>
          <input class="input" type="tel" id="no_telp" name="no_telp" value="<?= e($data['no_telp']) ?>" maxlength="15" inputmode="numeric">
        </div>

        <div class="field">
          <label for="alamat">Alamat</label>
          <textarea class="textarea" id="alamat" name="alamat" maxlength="255" style="min-height:70px"><?= e($data['alamat']) ?></textarea>
        </div>
      </div>

      <div class="form-actions">
        <button class="btn btn--primary btn--block" type="submit"><?= icon('plus', 16) ?> Tambah pegawai</button>
      </div>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
