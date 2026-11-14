<?php
/**
 * Layout atas: <head>, sidebar, topbar, pembuka <main>.
 *
 * Variabel opsional sebelum include:
 *   $page_title     Judul halaman (string)
 *   $page_subtitle  Keterangan satu baris (string)
 *   $page_actions   HTML tombol aksi di kanan judul
 *   $active_menu    Key menu aktif, mis. 'membership'
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';

$page_title    = $page_title ?? 'Dashboard';
$page_subtitle = $page_subtitle ?? '';
$page_actions  = $page_actions ?? '';
$active_menu   = $active_menu ?? '';
$nama_pegawai  = $_SESSION['nama'] ?? 'Pegawai';
$role_pegawai  = $_SESSION['role'] ?? '-';
$is_member     = ($role_pegawai === 'User' || $role_pegawai === 'Member');
$brand_url     = $is_member ? 'pages/user/dashboard.php' : 'pages/dashboard/index.php';

// Jumlah tiket yang masih perlu ditangani (staff) & notifikasi promo (member).
$tiket_terbuka = 0;
$member_notif_data = ['items' => [], 'unread' => 0];
$member_notif_items = [];
$member_unread_notif = 0;
$member_pengumuman_aktif = [];
if (isset($conn)) {
    if ($is_member) {
        $member_notif_data = ambil_notifikasi_member($conn, (int) ($_SESSION['member_id'] ?? 0));
        $member_notif_items = $member_notif_data['items'];
        $member_unread_notif = (int) $member_notif_data['unread'];
        $member_pengumuman_aktif = ambil_pengumuman_aktif($conn);
    } else {
        if ($role_pegawai === 'PIC Keluhan') {
            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS n FROM tiket WHERE status <> 'closed' AND pegawai_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $_SESSION['pegawai_id']);
        } else {
            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS n FROM tiket WHERE status = 'open'");
        }
        mysqli_stmt_execute($stmt);
        $tiket_terbuka = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'] ?? 0);
        mysqli_stmt_close($stmt);
    }
}

$flash = ambil_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> &middot; AbbyChicken Nashville CRM</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
<?php if ($is_member): ?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/member_mobile.css')) ?>?v=<?= filemtime(__DIR__ . '/../assets/css/member_mobile.css') ?>">
<?php endif; ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%231A535C'/><path d='M9 22c0-7 3.5-12 7-12s7 5 7 12' stroke='%23FF6B35' stroke-width='2.5' fill='none' stroke-linecap='round'/></svg>">
</head>
<?php if ($is_member): ?>
<body class="crm-member-body">
<div class="crm-member-canvas">
  <div class="crm-member-shell" id="memberMobileShell">
    <!-- Mobile Header -->
    <header class="crm-mobile-header">
      <a class="crm-mobile-brand" href="<?= e(base_url('pages/user/dashboard.php')) ?>">
        <img src="<?= e(base_url('logo_mobile.png')) ?>" alt="AbbyChicken Nashville" class="crm-mobile-brand__logo" height="30">
      </a>
      <div class="crm-mobile-header__actions">
        <button type="button"
                id="btnMemberNotif"
                class="crm-mobile-header__notif<?= $member_unread_notif > 0 ? ' has-unread' : '' ?>"
                aria-label="Notifikasi promo<?= $member_unread_notif > 0 ? ' (' . $member_unread_notif . ' belum dibaca)' : '' ?>"
                aria-expanded="false"
                aria-controls="memberNotifSheet"
                title="Notifikasi & Promo">
          <?= icon('bell', 18) ?>
          <span class="crm-notif-badge" id="memberNotifBadge"<?= $member_unread_notif > 0 ? '' : ' hidden style="display:none;"' ?>><?= $member_unread_notif ?></span>
        </button>
        <a class="crm-mobile-header__logout" href="<?= e(base_url('logout.php')) ?>" title="Keluar">
          <?= icon('logout', 16) ?>
        </a>
      </div>
    </header>

    <main class="crm-mobile-content" id="memberMainContent">
      <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $f): ?>
          <div class="crm-mobile-flash crm-mobile-flash--<?= e($f['tipe']) ?>">
            <span><?= $f['tipe'] === 'success' ? icon('check', 16) : icon('close', 16) ?></span>
            <span><?= e($f['pesan']) ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
<?php else: ?>
<body>
<div class="app" id="appShell">

  <aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
    <a class="sidebar__brand" href="<?= e(base_url($brand_url)) ?>">
      <img src="<?= e(base_url('logo.png')) ?>" alt="AbbyChicken Nashville" class="sidebar__brand-logo">
    </a>

    <nav class="sidebar__nav">
      <p class="sidebar__label">Operasional</p>
      <?php foreach (menu_per_role($role_pegawai) as $key => $item): ?>
        <?php if ($key === 'pegawai'): ?>
          <p class="sidebar__label sidebar__label--spaced">Administrasi</p>
        <?php elseif ($key === 'aturan' && $role_pegawai !== 'Admin'): ?>
          <p class="sidebar__label sidebar__label--spaced">SOP & Panduan</p>
        <?php endif; ?>
        <a class="nav-item<?= $active_menu === $key ? ' is-active' : '' ?>"
           href="<?= e(base_url($item['url'])) ?>"
           <?= $active_menu === $key ? 'aria-current="page"' : '' ?>>
          <span class="nav-item__icon"><?= icon($item['icon']) ?></span>
          <span class="nav-item__text"><?= e($item['label']) ?></span>
          <?php if ($key === 'complaints' && $tiket_terbuka > 0): ?>
            <span class="nav-item__count" title="<?= (int) $tiket_terbuka ?> tiket perlu ditangani"><?= (int) $tiket_terbuka ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar__foot">
      <div class="sidebar__user">
        <span class="avatar"><?= e(strtoupper(mb_substr($nama_pegawai, 0, 1))) ?></span>
        <span class="sidebar__user-text">
          <strong><?= e($nama_pegawai) ?></strong>
          <small><?= e($role_pegawai) ?></small>
        </span>
      </div>
      <a class="sidebar__logout" href="<?= e(base_url('logout.php')) ?>">
        <?= icon('logout', 16) ?> Keluar
      </a>
    </div>
  </aside>

  <div class="app__body">
    <header class="topbar">
      <button class="topbar__toggle" type="button" id="btnSidebar" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="sidebar">
        <?= icon('menu-bars', 20) ?>
      </button>

      <nav class="crumbs" aria-label="Breadcrumb">
        <a href="<?= e(base_url($brand_url)) ?>">Dashboard</a>
        <?php if ($active_menu !== 'dashboard' && $active_menu !== ''): ?>
          <span aria-hidden="true">/</span>
          <span aria-current="page"><?= e($page_title) ?></span>
        <?php endif; ?>
      </nav>

      <div class="topbar__right">
        <span class="topbar__clock" id="clock" aria-hidden="true"></span>
        <?php $topbar_notif_url = $role_pegawai === 'Pimpinan' ? base_url('pages/dashboard/index.php#tiketButuhTindakan') : base_url('pages/complaints/index.php'); ?>
        <a class="topbar__notif" href="<?= e($topbar_notif_url) ?>" title="<?= (int) $tiket_terbuka ?> tiket perlu ditangani">
          <?= icon('bell', 18) ?>
          <?php if ($tiket_terbuka > 0): ?>
            <span class="topbar__notif-count"><?= (int) $tiket_terbuka ?></span>
          <?php endif; ?>
        </a>
      </div>
    </header>

    <main class="content" id="mainContent">
      <div class="page-head">
        <div class="page-head__text">
          <h1><?= e($page_title) ?></h1>
          <?php if ($page_subtitle !== ''): ?>
            <p><?= e($page_subtitle) ?></p>
          <?php endif; ?>
        </div>
        <?php if ($page_actions !== ''): ?>
          <div class="page-head__actions"><?= $page_actions ?></div>
        <?php endif; ?>
      </div>

      <?php if (!empty($flash)): ?>
        <div class="flash-stack" role="status" aria-live="polite">
          <?php foreach ($flash as $f): ?>
            <div class="flash flash--<?= e($f['tipe']) ?>">
              <span class="flash__icon"><?= $f['tipe'] === 'success' ? icon('check', 16) : icon('close', 16) ?></span>
              <p><?= e($f['pesan']) ?></p>
              <button type="button" class="flash__close" aria-label="Tutup pesan">&times;</button>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
<?php endif; ?>
