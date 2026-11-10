<?php if (!empty($is_member)): ?>
    </main>

    <!-- Floating Bottom Navigation Dock Khusus Member -->
    <nav class="crm-bottom-dock" aria-label="Navigasi Utama Member">
      <a class="crm-dock-item<?= ($active_menu ?? '') === 'dashboard' ? ' is-active' : '' ?>" href="<?= e(base_url('pages/user/dashboard.php')) ?>">
        <?= icon('dashboard', 20) ?>
        <span>Beranda</span>
      </a>
      <a class="crm-dock-item<?= ($active_menu ?? '') === 'pesan' ? ' is-active' : '' ?>" href="<?= e(base_url('pages/user/pesan.php')) ?>">
        <?= icon('order', 20) ?>
        <span>Pesan</span>
      </a>
      <a class="crm-dock-item<?= ($active_menu ?? '') === 'keluhan' ? ' is-active' : '' ?>" href="<?= e(base_url('pages/user/keluhan.php')) ?>">
        <?= icon('complaint', 20) ?>
        <span>Keluhan</span>
      </a>
      <a class="crm-dock-item<?= ($active_menu ?? '') === 'ulasan' ? ' is-active' : '' ?>" href="<?= e(base_url('pages/user/ulasan.php')) ?>">
        <?= icon('voucher', 20) ?>
        <span>Ulasan</span>
      </a>
    </nav>

    <!-- Modal Bottom Sheet Notifikasi & Promo Member (.design/mobile.md Bagian 4.3 & Halaman 4) -->
    <div class="crm-bottom-sheet-backdrop" id="memberNotifBackdrop" hidden></div>
    <section class="crm-bottom-sheet" id="memberNotifSheet" role="dialog" aria-modal="true" aria-labelledby="memberNotifSheetTitle" aria-hidden="true" hidden>
      <div class="crm-bottom-sheet__handle" aria-hidden="true"></div>
      <div class="crm-bottom-sheet__head">
        <div>
          <h2 class="crm-bottom-sheet__title" id="memberNotifSheetTitle">
            Notifikasi &amp; Promo
            <span class="crm-pill-tag crm-pill-tag--terracotta" id="memberSheetUnreadPill"<?= ($member_unread_notif ?? 0) > 0 ? '' : ' hidden style="display:none;"' ?>>
              <span class="js-unread-num"><?= (int) ($member_unread_notif ?? 0) ?></span> baru
            </span>
          </h2>
          <p class="crm-bottom-sheet__sub">Promo voucher personal &amp; info terbaru AbbyChicken</p>
        </div>
        <button type="button" class="crm-bottom-sheet__close" id="btnCloseNotifSheet" aria-label="Tutup notifikasi">&times;</button>
      </div>

      <?php if (!empty($member_notif_items) && ($member_unread_notif ?? 0) > 0): ?>
        <div id="memberMarkAllRow" style="display: flex; justify-content: flex-end; margin-bottom: 12px;">
          <button type="button" class="crm-btn-mini crm-btn-mini--sand" id="btnMarkAllNotifRead">
            <?= icon('check', 13) ?> Tandai dibaca semua
          </button>
        </div>
      <?php endif; ?>

      <div style="margin-bottom: 18px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
          <h3 style="margin: 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--crm-sage);">
            Promo Khusus Untukmu
          </h3>
          <span class="crm-pill-tag"><?= count($member_notif_items ?? []) ?> promo</span>
        </div>

        <?php if (empty($member_notif_items)): ?>
          <div style="background: var(--crm-bg-app); border-radius: var(--crm-radius-md); padding: 20px 16px; text-align: center; border: 1px solid rgba(24,32,21,0.06);">
            <div style="color: var(--crm-sage); margin-bottom: 6px;"><?= icon('bell', 26) ?></div>
            <p style="margin: 0; font-size: 13.5px; font-weight: 700; color: var(--crm-black);">Belum ada notifikasi promo</p>
            <p style="margin: 4px 0 0; font-size: 12px; color: var(--crm-sage);">Jika admin mengirimkan promo atau kode voucher khusus untuk akun Anda, pesannya akan muncul di sini.</p>
          </div>
        <?php else: ?>
          <div class="crm-list-stack">
            <?php foreach ($member_notif_items as $n): ?>
              <?php
              $is_unread = ((int) ($n['is_read'] ?? 0) === 0);
              $has_valid_voucher = !empty($n['potongan_harga'])
                  && (int) ($n['is_valid'] ?? 0) === 1
                  && ($n['deleted_at'] ?? null) === null
                  && !empty($n['valid_until'])
                  && strtotime($n['valid_until']) >= strtotime(date('Y-m-d'));
              ?>
              <article class="<?= $is_unread ? 'crm-card-forest' : 'crm-card-cream' ?> js-member-notif-item"
                       data-notif-id="<?= (int) $n['notif_id'] ?>"
                       data-is-read="<?= $is_unread ? '0' : '1' ?>"
                       style="margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 8px;">
                  <div>
                    <h4 style="margin: 0; font-size: 14.5px; font-weight: 700; line-height: 1.3; color: <?= $is_unread ? '#FFFFFF' : 'var(--crm-black)' ?>;" class="js-notif-title">
                      <?= e($n['judul']) ?>
                    </h4>
                    <p style="margin: 3px 0 0; font-size: 11.5px; color: <?= $is_unread ? '#B9C8B3' : 'var(--crm-sage)' ?>;" class="js-notif-time">
                      <?= e(waktu_lalu($n['created_at'])) ?> &middot; <?= e(tanggal_id($n['created_at'], true)) ?>
                    </p>
                  </div>
                  <span class="crm-pill-tag <?= $is_unread ? 'crm-pill-tag--green' : '' ?> js-notif-status-badge">
                    <?= $is_unread ? 'BARU' : 'Dibaca' ?>
                  </span>
                </div>

                <p style="margin: 0; font-size: 13px; line-height: 1.48; color: <?= $is_unread ? '#EEF5EC' : 'var(--crm-black)' ?>;" class="js-notif-body">
                  <?= nl2br(e($n['pesan'])) ?>
                </p>

                <?php if (!empty($n['kode_voucher'])): ?>
                  <div class="crm-notif-voucher-box">
                    <div>
                      <div style="font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px; color: <?= $is_unread ? '#C5D5C0' : 'var(--crm-sage)' ?>;" class="js-notif-vlabel">
                        Kode Voucher Promo
                      </div>
                      <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                        <span class="crm-notif-voucher-code"><?= icon('voucher', 13) ?> <?= e($n['kode_voucher']) ?></span>
                        <?php if ($has_valid_voucher): ?>
                          <span style="font-size: 11.5px; font-weight: 700; color: <?= $is_unread ? '#9CE481' : 'var(--crm-forest)' ?>;" class="js-notif-vdisc">
                            Hemat <?= e(rupiah($n['potongan_harga'])) ?>
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <button type="button"
                            class="crm-btn-mini <?= $is_unread ? 'crm-btn-mini--ghost-dark' : 'crm-btn-mini--sand' ?> js-copy-voucher"
                            data-kode="<?= e($n['kode_voucher']) ?>">
                      <?= icon('copy', 12) ?> <span>Salin</span>
                    </button>
                  </div>
                <?php endif; ?>

                <?php if (!empty($n['kode_voucher']) || $is_unread): ?>
                  <div class="crm-notif-card__actions">
                    <?php if (!empty($n['kode_voucher'])): ?>
                      <a class="crm-btn-mini crm-btn-mini--green"
                         href="<?= e(base_url('pages/user/pesan.php?voucher=' . urlencode($n['kode_voucher']) . '&notif_id=' . (int) $n['notif_id'])) ?>">
                        Pakai Voucher &rarr;
                      </a>
                    <?php else: ?>
                      <span></span>
                    <?php endif; ?>
                    <?php if ($is_unread): ?>
                      <button type="button"
                              class="crm-btn-mini crm-btn-mini--ghost-dark js-mark-notif-read"
                              data-notif-id="<?= (int) $n['notif_id'] ?>">
                        Tandai dibaca
                      </button>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($member_pengumuman_aktif)): ?>
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h3 style="margin: 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--crm-sage);">
              Info &amp; Pengumuman Outlet
            </h3>
            <span class="crm-pill-tag"><?= count($member_pengumuman_aktif) ?> info</span>
          </div>
          <div class="crm-list-stack">
            <?php foreach ($member_pengumuman_aktif as $pg_info): ?>
              <article class="crm-card-cream" style="background: var(--crm-bg-app); margin-bottom: 0; padding: 14px 16px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
                  <h4 style="margin: 0; font-size: 13.5px; font-weight: 700; color: var(--crm-black);">
                    <?= e($pg_info['judul']) ?>
                  </h4>
                  <span class="crm-pill-tag"><?= e(waktu_lalu($pg_info['created_at'])) ?></span>
                </div>
                <p style="margin: 0; font-size: 12.5px; line-height: 1.45; color: var(--crm-sage);">
                  <?= nl2br(e($pg_info['isi'])) ?>
                </p>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </section>
  </div><!-- /.crm-member-shell -->
</div><!-- /.crm-member-canvas -->

<script>
document.addEventListener('DOMContentLoaded', function () {
  var btnNotif = document.getElementById('btnMemberNotif');
  var sheet = document.getElementById('memberNotifSheet');
  var backdrop = document.getElementById('memberNotifBackdrop');
  var btnClose = document.getElementById('btnCloseNotifSheet');
  var btnMarkAll = document.getElementById('btnMarkAllNotifRead');
  var notifEndpoint = <?= json_encode(base_url('api/notifikasi_member.php'), JSON_UNESCAPED_SLASHES) ?>;
  var csrfToken = <?= json_encode(csrf_token()) ?>;

  function openSheet() {
    if (!sheet || !backdrop) return;
    backdrop.hidden = false;
    backdrop.style.display = 'block';
    sheet.hidden = false;
    requestAnimationFrame(function () {
      backdrop.classList.add('is-open');
      sheet.classList.add('is-open');
    });
    sheet.setAttribute('aria-hidden', 'false');
    if (btnNotif) btnNotif.setAttribute('aria-expanded', 'true');
  }

  function closeSheet() {
    if (!sheet || !backdrop) return;
    backdrop.classList.remove('is-open');
    sheet.classList.remove('is-open');
    sheet.setAttribute('aria-hidden', 'true');
    if (btnNotif) btnNotif.setAttribute('aria-expanded', 'false');
    setTimeout(function () {
      if (!sheet.classList.contains('is-open')) {
        backdrop.hidden = true;
        backdrop.style.display = 'none';
        sheet.hidden = true;
      }
    }, 360);
  }

  if (btnNotif) {
    btnNotif.addEventListener('click', function () {
      if (sheet && sheet.classList.contains('is-open')) {
        closeSheet();
      } else {
        openSheet();
      }
    });
  }

  document.querySelectorAll('[data-open-notif-sheet]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      openSheet();
    });
  });

  if (btnClose) btnClose.addEventListener('click', closeSheet);
  if (backdrop) backdrop.addEventListener('click', closeSheet);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sheet && sheet.classList.contains('is-open')) {
      closeSheet();
    }
  });

  function syncUnreadUI(unread) {
    var badge = document.getElementById('memberNotifBadge');
    var sheetPill = document.getElementById('memberSheetUnreadPill');
    var markAllRow = document.getElementById('memberMarkAllRow');
    var dashBadge = document.getElementById('dashboardNotifUnreadBadge');

    if (badge) {
      badge.textContent = String(unread);
      badge.hidden = unread <= 0;
      badge.style.display = unread > 0 ? 'inline-flex' : 'none';
    }
    if (btnNotif) {
      btnNotif.classList.toggle('has-unread', unread > 0);
      btnNotif.setAttribute('aria-label', unread > 0 ? 'Notifikasi promo (' + unread + ' belum dibaca)' : 'Notifikasi promo');
    }
    if (sheetPill) {
      sheetPill.hidden = unread <= 0;
      sheetPill.style.display = unread > 0 ? 'inline-flex' : 'none';
      var num = sheetPill.querySelector('.js-unread-num');
      if (num) num.textContent = String(unread);
    }
    if (dashBadge) {
      if (unread > 0) {
        dashBadge.textContent = unread + ' promo baru';
        dashBadge.className = 'crm-pill-tag crm-pill-tag--green';
      } else {
        dashBadge.textContent = 'Sudah dibaca';
        dashBadge.className = 'crm-pill-tag';
      }
    }
    if (markAllRow && unread <= 0) {
      markAllRow.style.display = 'none';
    }
  }

  function styleCardAsRead(card) {
    if (!card) return;
    card.setAttribute('data-is-read', '1');
    card.classList.remove('crm-card-forest');
    card.classList.add('crm-card-cream');
    var title = card.querySelector('.js-notif-title');
    if (title) title.style.color = 'var(--crm-black)';
    var time = card.querySelector('.js-notif-time');
    if (time) time.style.color = 'var(--crm-sage)';
    var body = card.querySelector('.js-notif-body');
    if (body) body.style.color = 'var(--crm-black)';
    var vlabel = card.querySelector('.js-notif-vlabel');
    if (vlabel) vlabel.style.color = 'var(--crm-sage)';
    var vdisc = card.querySelector('.js-notif-vdisc');
    if (vdisc) vdisc.style.color = 'var(--crm-forest)';
    var statusBadge = card.querySelector('.js-notif-status-badge');
    if (statusBadge) {
      statusBadge.textContent = 'Dibaca';
      statusBadge.classList.remove('crm-pill-tag--green');
    }
    var copyBtn = card.querySelector('.js-copy-voucher');
    if (copyBtn) {
      copyBtn.classList.remove('crm-btn-mini--ghost-dark');
      copyBtn.classList.add('crm-btn-mini--sand');
    }
    var markBtn = card.querySelector('.js-mark-notif-read');
    if (markBtn) markBtn.remove();
  }

  function postNotifAction(params) {
    var formBody = new URLSearchParams(params);
    formBody.set('csrf_token', csrfToken);
    return fetch(notifEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: formBody.toString(),
      credentials: 'same-origin'
    }).then(function (res) {
      return res.json();
    });
  }

  document.querySelectorAll('.js-mark-notif-read').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var notifId = btn.getAttribute('data-notif-id');
      if (!notifId) return;
      btn.disabled = true;
      postNotifAction({ action: 'mark_read', notif_id: notifId }).then(function (data) {
        if (data && data.success) {
          styleCardAsRead(btn.closest('.js-member-notif-item'));
          syncUnreadUI(parseInt(data.unread || 0, 10));
        } else {
          btn.disabled = false;
        }
      }).catch(function () {
        btn.disabled = false;
      });
    });
  });

  if (btnMarkAll) {
    btnMarkAll.addEventListener('click', function () {
      btnMarkAll.disabled = true;
      postNotifAction({ action: 'mark_all_read' }).then(function (data) {
        if (data && data.success) {
          document.querySelectorAll('.js-member-notif-item[data-is-read="0"]').forEach(styleCardAsRead);
          syncUnreadUI(parseInt(data.unread || 0, 10));
        } else {
          btnMarkAll.disabled = false;
        }
      }).catch(function () {
        btnMarkAll.disabled = false;
      });
    });
  }

  document.querySelectorAll('.js-copy-voucher').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var kode = btn.getAttribute('data-kode') || '';
      if (!kode) return;
      var labelSpan = btn.querySelector('span');
      function flashCopied() {
        if (!labelSpan) return;
        var prev = labelSpan.textContent;
        labelSpan.textContent = 'Tersalin!';
        setTimeout(function () {
          labelSpan.textContent = prev;
        }, 1600);
      }
      if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        navigator.clipboard.writeText(kode).then(flashCopied).catch(function () {
          fallbackCopy(kode);
          flashCopied();
        });
      } else {
        fallbackCopy(kode);
        flashCopied();
      }
    });
  });

  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (err) {}
    document.body.removeChild(ta);
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script src="<?= e(base_url('assets/js/main.js')) ?>" defer></script>
<?php if (!empty($page_script)): ?>
<script src="<?= e(base_url($page_script)) ?>" defer></script>
<?php endif; ?>
</body>
</html>
<?php else: ?>
    </main>

    <footer class="appfoot">
      <p>AbbyChicken Nashville CRM &middot; Sistem internal pegawai</p>
      <p class="appfoot__meta"><?= date('Y') ?> &middot; Manajemen Hubungan Pelanggan, UPN Veteran Yogyakarta</p>
    </footer>
  </div>
</div>

<div class="scrim" id="scrim" hidden></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script src="<?= e(base_url('assets/js/main.js')) ?>" defer></script>
<?php if (!empty($page_script)): ?>
<script src="<?= e(base_url($page_script)) ?>" defer></script>
<?php endif; ?>
</body>
</html>
<?php endif; ?>
