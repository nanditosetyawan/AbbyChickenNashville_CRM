<?php
/**
 * Halaman 403: role tidak berhak membuka modul.
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
cek_login();

$page_title    = 'Akses ditolak';
$page_subtitle = 'Role Anda tidak memiliki izin untuk membuka halaman tersebut.';
$active_menu   = '';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="card">
  <div class="empty">
    <div class="empty__icon"><?= icon('lock', 20) ?></div>
    <h3>Halaman ini khusus role lain</h3>
    <p>
      Anda masuk sebagai <strong><?= e($_SESSION['role'] ?? '-') ?></strong>.
      Bila memang membutuhkan akses, hubungi admin untuk menyesuaikan role akun Anda.
    </p>
    <div class="btn-group" style="justify-content:center;margin-top:18px">
      <a class="btn btn--primary" href="<?= e(base_url('pages/dashboard/index.php')) ?>"><?= icon('dashboard', 16) ?> Ke dashboard</a>
      <a class="btn btn--secondary" href="<?= e(base_url('logout.php')) ?>"><?= icon('logout', 16) ?> Ganti akun</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
