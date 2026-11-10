<?php
/**
 * Halaman login pelanggan (user / member).
 * Style 100% konsisten dengan halaman login pegawai.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!empty($_SESSION['member_id'])) {
    redirect(base_url('pages/user/dashboard.php'));
} elseif (!empty($_SESSION['pegawai_id'])) {
    redirect(base_url('pages/dashboard/index.php'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT member_id, name, email, password, member_status FROM member WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($member) {
            if ($member['member_status'] !== 'aktif') {
                $error = 'Akun member Anda berstatus nonaktif. Silakan hubungi kasir.';
            } elseif ($member['password'] === $password || password_verify($password, $member['password'])) {
                session_regenerate_id(true);
                $_SESSION['member_id'] = (int) $member['member_id'];
                $_SESSION['nama']      = $member['name'];
                $_SESSION['role']      = 'User';
                $_SESSION['email']     = $member['email'];
                set_flash('success', 'Selamat datang, ' . $member['name'] . '.');
                redirect(base_url('pages/user/dashboard.php'));
            } else {
                $error = 'Email atau password salah.';
            }
        } else {
            $error = 'Email atau password salah.';
        }
    }
}

$flash = ambil_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk &middot; Pelanggan AbbyChicken Nashville</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/style.css') ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%231A535C'/><path d='M9 22c0-7 3.5-12 7-12s7 5 7 12' stroke='%23FF6B35' stroke-width='2.5' fill='none' stroke-linecap='round'/></svg>">
</head>
<body>
<div class="auth">

  <section class="auth__panel">
    <div class="auth__brand">
      <img src="<?= e(base_url('logo.png')) ?>" alt="AbbyChicken Nashville" class="auth__brand-logo" height="38">
    </div>

    <div class="auth__pitch">
      <h2>Pesan menu favorit Anda dan nikmati refill nasi hangat.</h2>
      <p>Portal khusus pelanggan AbbyChicken Nashville untuk mengecek ketersediaan menu secara langsung, memesan makanan secara online, dan melihat stok refill nasi hari ini.</p>

      <ul class="auth__modules">
        <li><?= icon('rice', 16) ?> Status stok refill nasi real-time</li>
        <li><?= icon('menu', 16) ?> Informasi menu tersedia &amp; habis</li>
        <li><?= icon('order', 16) ?> Pemesanan praktis dan antrian digital</li>
        <li><?= icon('voucher', 16) ?> Promo &amp; voucher potongan belanja</li>
      </ul>
    </div>

    <p class="auth__foot">Manajemen Hubungan Pelanggan &middot; AbbyChicken Nashville</p>
  </section>

  <section class="auth__form-side">
    <form class="auth__form" method="post" action="" novalidate>
      <?= csrf_field() ?>
      <h1>Masuk ke akun pelanggan</h1>
      <p>Gunakan email member dan password Anda.</p>

      <?php if ($error !== ''): ?>
        <div class="flash flash--error" role="alert">
          <span class="flash__icon"><?= icon('close', 16) ?></span>
          <p><?= e($error) ?></p>
        </div>
      <?php endif; ?>

      <?php foreach ($flash as $f): ?>
        <div class="flash flash--<?= e($f['tipe']) ?>" role="status">
          <span class="flash__icon"><?= icon('check', 16) ?></span>
          <p><?= e($f['pesan']) ?></p>
        </div>
      <?php endforeach; ?>

      <div class="field mt-16">
        <label for="email">Email Member</label>
        <input class="input" type="email" id="email" name="email" autocomplete="username"
               value="<?= e($_POST['email'] ?? '') ?>" placeholder="wilda.ghoniyu@mail.com" required autofocus>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input class="input" type="password" id="password" name="password" autocomplete="current-password"
               placeholder="Masukkan password" required>
      </div>

      <button class="btn btn--primary btn--block" type="submit">Masuk</button>

      <div class="auth__hint">
        <strong>Akun demo member</strong><br>
        <span class="auth__demo-item" data-email="wilda.ghoniyu@mail.com" data-password="123456">Member 1: <code>wilda.ghoniyu@mail.com</code> / <code>123456</code></span><br>
        <span class="auth__demo-item" data-email="fatihah.nafis@mail.com" data-password="123456">Member 2: <code>fatihah.nafis@mail.com</code> / <code>123456</code></span><br>
        <span class="auth__demo-item" data-email="rangga.pratama@mail.com" data-password="123456">Member 3: <code>rangga.pratama@mail.com</code> / <code>123456</code></span>
      </div>

      <p class="text-small text-muted" style="text-align:center;margin-top:20px">
        Pegawai? <a href="<?= e(base_url('index.php')) ?>" class="link-quiet">Login pegawai di sini</a>
      </p>
    </form>
  </section>

</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var hint = document.querySelector('.auth__hint');
  var emailInput = document.getElementById('email');
  var passwordInput = document.getElementById('password');

  function fillCreds(email, password) {
    if (!emailInput || !passwordInput) return;
    emailInput.value = email;
    passwordInput.value = password;
    emailInput.dispatchEvent(new Event('input', { bubbles: true }));
    emailInput.dispatchEvent(new Event('change', { bubbles: true }));
    passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
    passwordInput.dispatchEvent(new Event('change', { bubbles: true }));
  }

  if (hint) {
    hint.addEventListener('click', function (e) {
      var item = e.target.closest('.auth__demo-item');
      if (item) {
        fillCreds(item.getAttribute('data-email'), item.getAttribute('data-password'));
      }
    });
  }
});
</script>
</body>
</html>

