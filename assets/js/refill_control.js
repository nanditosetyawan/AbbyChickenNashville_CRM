/**
 * Kontrol Refill Nasi & Timer Restok Otomatis (Admin & Kasir)
 * Menangani toggle Ready/Habis, preset chip, custom hours, live ticking countdown, dan auto-restock.
 */
(function () {
  'use strict';

  var card = document.getElementById('adminRefillCard');
  if (!card) return;

  var toggle = card.querySelector('.js-toggle-refill');
  var label = document.getElementById('refillLabel');
  var desc = document.getElementById('refillDesc');
  var timerPanel = document.getElementById('refillTimerPanel');
  var activeBox = document.getElementById('refillTimerActiveBox');
  var setupBox = document.getElementById('refillTimerSetupBox');
  var countdownEl = document.getElementById('adminCountdownDisplay');
  var targetTimeEl = document.getElementById('adminTimerTarget');
  var btnCancel = document.getElementById('btnCancelTimer');
  var btnChange = document.getElementById('btnChangeTimer');
  var formCustom = document.getElementById('formCustomTimer');
  var customInput = document.getElementById('customTimerInput');

  var tokenEl = document.getElementById('csrfToken') || document.querySelector('input[name="csrf_token"]');
  var token = tokenEl ? tokenEl.value : '';
  var apiUrl = toggle ? toggle.dataset.api : 'api/update_refill.php';

  var timerInterval = null;
  var currentTargetTs = parseInt(card.getAttribute('data-target-ts'), 10) || 0;

  function pad(n) {
    return n < 10 ? '0' + n : String(n);
  }

  function startCountdown(targetTs, targetClock) {
    currentTargetTs = targetTs;
    if (timerInterval) clearInterval(timerInterval);

    if (targetTimeEl && targetClock) {
      targetTimeEl.textContent = '(' + targetClock + ')';
    }

    function tick() {
      var now = Math.floor(Date.now() / 1000);
      var diff = currentTargetTs - now;

      if (diff <= 0) {
        clearInterval(timerInterval);
        timerInterval = null;
        if (countdownEl) countdownEl.textContent = '00:00:00';

        // Auto-restock triggered!
        if (toggle) toggle.checked = true;
        if (label) label.textContent = 'Ready';
        if (desc) desc.textContent = 'Stok nasi siap disajikan sepuasnya untuk dine-in.';
        if (timerPanel) timerPanel.style.display = 'none';

        if (window.tampilkanToast) {
          window.tampilkanToast('Waktu timer selesai! Refill nasi otomatis kembali Ready.', 'success');
        }

        // Sync with backend
        fetch('api/get_refill_status.php').catch(function () {});
        return;
      }

      var h = Math.floor(diff / 3600);
      var m = Math.floor((diff % 3600) / 60);
      var s = diff % 60;

      if (countdownEl) {
        countdownEl.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
      }
    }

    tick();
    timerInterval = setInterval(tick, 1000);
  }

  // Jika halaman dibuka saat timer sedang aktif
  if (currentTargetTs > Math.floor(Date.now() / 1000) && (!toggle || !toggle.checked)) {
    startCountdown(currentTargetTs, '');
  }

  // Toggle switch handler
  if (toggle) {
    toggle.addEventListener('change', function () {
      var isReady = toggle.checked;
      toggle.disabled = true;

      window.postJSON(apiUrl, {
        csrf_token: token,
        status: isReady ? 'ready' : 'habis',
        timer_minutes: 0
      }).then(function (res) {
        if (!res.success) {
          throw new Error(res.message || 'Gagal mengubah status refill nasi.');
        }

        if (isReady) {
          if (label) label.textContent = 'Ready';
          if (desc) desc.textContent = 'Stok nasi siap disajikan sepuasnya untuk dine-in.';
          if (timerPanel) timerPanel.style.display = 'none';
          if (timerInterval) clearInterval(timerInterval);
        } else {
          if (label) label.textContent = 'Habis';
          if (desc) desc.textContent = 'Stok nasi ditandai habis pada portal member.';
          if (timerPanel) timerPanel.style.display = '';
          if (activeBox) activeBox.style.display = 'none';
          if (setupBox) setupBox.style.display = '';
        }

        if (window.tampilkanToast) {
          window.tampilkanToast(res.message, 'success');
        }
      }).catch(function (err) {
        toggle.checked = !isReady;
        if (window.tampilkanToast) {
          window.tampilkanToast(err.message, 'error');
        }
      }).finally(function () {
        toggle.disabled = false;
      });
    });
  }

  // Preset chips handler
  card.querySelectorAll('.btn-chip[data-minutes]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var minutes = parseInt(btn.dataset.minutes, 10) || 0;
      if (minutes <= 0) return;

      btn.disabled = true;
      window.postJSON(apiUrl, {
        csrf_token: token,
        status: 'habis',
        timer_minutes: minutes
      }).then(function (res) {
        if (!res.success) {
          throw new Error(res.message || 'Gagal mengatur timer.');
        }

        if (setupBox) setupBox.style.display = 'none';
        if (activeBox) activeBox.style.display = '';

        var targetClock = res.timer_end ? 'Pukul ' + res.timer_end.substring(11, 16) + ' WIB' : '';
        startCountdown(res.timer_ts, targetClock);

        if (window.tampilkanToast) {
          window.tampilkanToast(res.message, 'success');
        }
      }).catch(function (err) {
        if (window.tampilkanToast) {
          window.tampilkanToast(err.message, 'error');
        }
      }).finally(function () {
        btn.disabled = false;
      });
    });
  });

  // Custom timer form handler
  if (formCustom) {
    formCustom.addEventListener('submit', function (e) {
      e.preventDefault();
      var hours = parseFloat(customInput.value);
      if (isNaN(hours) || hours <= 0) {
        if (window.tampilkanToast) {
          window.tampilkanToast('Masukkan jumlah jam yang valid (contoh: 5 atau 2.5).', 'error');
        }
        return;
      }

      var minutes = Math.round(hours * 60);
      var btnSubmit = formCustom.querySelector('button[type="submit"]');
      if (btnSubmit) btnSubmit.disabled = true;

      window.postJSON(apiUrl, {
        csrf_token: token,
        status: 'habis',
        timer_minutes: minutes
      }).then(function (res) {
        if (!res.success) {
          throw new Error(res.message || 'Gagal mengatur timer.');
        }

        if (setupBox) setupBox.style.display = 'none';
        if (activeBox) activeBox.style.display = '';
        customInput.value = '';

        var targetClock = res.timer_end ? 'Pukul ' + res.timer_end.substring(11, 16) + ' WIB' : '';
        startCountdown(res.timer_ts, targetClock);

        if (window.tampilkanToast) {
          window.tampilkanToast(res.message, 'success');
        }
      }).catch(function (err) {
        if (window.tampilkanToast) {
          window.tampilkanToast(err.message, 'error');
        }
      }).finally(function () {
        if (btnSubmit) btnSubmit.disabled = false;
      });
    });
  }

  // Cancel timer handler
  if (btnCancel) {
    btnCancel.addEventListener('click', function () {
      btnCancel.disabled = true;

      window.postJSON(apiUrl, {
        csrf_token: token,
        status: 'habis',
        timer_minutes: 0
      }).then(function (res) {
        if (!res.success) {
          throw new Error(res.message || 'Gagal membatalkan timer.');
        }

        if (timerInterval) clearInterval(timerInterval);
        timerInterval = null;

        if (activeBox) activeBox.style.display = 'none';
        if (setupBox) setupBox.style.display = '';

        if (window.tampilkanToast) {
          window.tampilkanToast('Timer otomatis restok dibatalkan. Status tetap Habis manual.', 'success');
        }
      }).catch(function (err) {
        if (window.tampilkanToast) {
          window.tampilkanToast(err.message, 'error');
        }
      }).finally(function () {
        btnCancel.disabled = false;
      });
    });
  }

  // Change timer button handler
  if (btnChange) {
    btnChange.addEventListener('click', function () {
      if (setupBox) {
        setupBox.style.display = (setupBox.style.display === 'none') ? '' : 'none';
      }
    });
  }
})();
