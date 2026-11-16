/**
 * Refill Nasi User Watcher & Auto-Reload Tiap 15 Detik
 * Memantau ketersediaan refill nasi secara real-time di sisi user (member).
 * Memutar suara notifikasi kencang (Dual-engine: Audio file + Web Audio API synthesizer)
 * dan menampilkan alert modal saat timer selesai & refill nasi siap disajikan.
 */
(function () {
  'use strict';

  var POLL_INTERVAL = 15000; // 15 detik
  var audioContext = null;
  var audioUnlocked = false;
  var audioFile = null;

  // Sound path
  var soundUrl = (window.BASE_URL || '/CRM/') + 'assets/sound_refill_ready.wav';

  // Inisialisasi Audio Element
  try {
    audioFile = new Audio(soundUrl);
    audioFile.volume = 1.0;
    audioFile.preload = 'auto';
  } catch (e) {
    audioFile = null;
  }

  // Buka blokir autoplay browser saat user pertama kali berinteraksi
  function unlockAudio() {
    if (audioUnlocked) return;
    try {
      var AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (AudioCtx && !audioContext) {
        audioContext = new AudioCtx();
      }
      if (audioContext && audioContext.state === 'suspended') {
        audioContext.resume();
      }
      if (audioFile) {
        audioFile.play().then(function () {
          audioFile.pause();
          audioFile.currentTime = 0;
        }).catch(function () {});
      }
      audioUnlocked = true;
    } catch (err) {}
  }

  window.addEventListener('click', unlockAudio, { once: true });
  window.addEventListener('touchstart', unlockAudio, { once: true });

  /**
   * Mainkan suara kencang menggunakan Web Audio API (Ascending Bell Chime)
   * Berfrekuensi tinggi dan jernih, dirancang agar terdengar jelas dan kencang.
   */
  function playWebAudioChime() {
    try {
      var AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      if (!audioContext) audioContext = new AudioCtx();
      if (audioContext.state === 'suspended') audioContext.resume();

      var now = audioContext.currentTime;

      // Master gain booster
      var masterGain = audioContext.createGain();
      masterGain.gain.setValueAtTime(1.0, now);
      masterGain.connect(audioContext.destination);

      // 4 Nada lonceng bertingkat (G5, B5, E6, G6)
      var notes = [
        { freq: 783.99, time: 0.0, dur: 0.5 },
        { freq: 987.77, time: 0.22, dur: 0.5 },
        { freq: 1318.51, time: 0.44, dur: 0.6 },
        { freq: 1567.98, time: 0.68, dur: 1.6 }
      ];

      notes.forEach(function (n) {
        var start = now + n.time;

        // Nada fundamental (sine + triangle untuk resonansi logam)
        var osc1 = audioContext.createOscillator();
        var osc2 = audioContext.createOscillator();
        var gain = audioContext.createGain();

        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(n.freq, start);

        osc2.type = 'triangle';
        osc2.frequency.setValueAtTime(n.freq * 2, start); // Harmonic ke-2

        gain.gain.setValueAtTime(0.001, start);
        gain.gain.linearRampToValueAtTime(0.9, start + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.001, start + n.dur);

        osc1.connect(gain);
        osc2.connect(gain);
        gain.connect(masterGain);

        osc1.start(start);
        osc2.start(start);
        osc1.stop(start + n.dur + 0.05);
        osc2.stop(start + n.dur + 0.05);
      });
    } catch (e) {
      console.warn('Web Audio error:', e);
    }
  }

  /**
   * Mainkan suara alarm kencang (Dual Playback)
   */
  function playLoudAlarm() {
    // 1. Web Audio API Synthesizer (Zero-latency & boosted volume)
    playWebAudioChime();

    // 2. Mainkan file WAV eksternal
    try {
      if (audioFile) {
        audioFile.currentTime = 0;
        audioFile.volume = 1.0;
        var p = audioFile.play();
        if (p && typeof p.catch === 'function') {
          p.catch(function () {});
        }
      } else {
        var snd = new Audio(soundUrl);
        snd.volume = 1.0;
        snd.play().catch(function () {});
      }
    } catch (err) {}

    // 3. Getaran ponsel (Vibration API) jika didukung
    if (navigator.vibrate) {
      navigator.vibrate([250, 100, 250, 100, 400]);
    }
  }

  /**
   * Buat dan tampilkan Modal Alert Pop-up Refill Ready
   */
  function showRefillReadyModal() {
    var existing = document.getElementById('crmRefillReadyModal');
    if (existing) return;

    var backdrop = document.createElement('div');
    backdrop.className = 'crm-refill-alert-backdrop';
    backdrop.id = 'crmRefillReadyBackdrop';

    var modal = document.createElement('div');
    modal.className = 'crm-refill-alert-modal';
    modal.id = 'crmRefillReadyModal';
    modal.setAttribute('role', 'alertdialog');
    modal.setAttribute('aria-modal', 'true');

    modal.innerHTML = [
      '<div class="crm-refill-alert-icon-wrap">🍚</div>',
      '<h2 class="crm-refill-alert-title">Nasi Refill Sudah Ready!</h2>',
      '<p class="crm-refill-alert-desc">Stok nasi hangat pulen sudah matang dan siap disajikan sepuasnya untuk dine-in.</p>',
      '<div class="crm-refill-alert-actions">',
      '  <a class="crm-refill-alert-btn-primary" href="' + (window.BASE_URL || '/CRM/') + 'pages/user/pesan.php">',
      '    <span>Pesan &amp; Nikmati Sekarang</span> &rarr;',
      '  </a>',
      '  <button type="button" class="crm-refill-alert-btn-secondary" id="btnCloseRefillAlert">Tutup &amp; Muat Ulang Halaman</button>',
      '</div>'
    ].join('');

    document.body.appendChild(backdrop);
    document.body.appendChild(modal);

    // Animasi tampil
    setTimeout(function () {
      backdrop.classList.add('is-visible');
      modal.classList.add('is-visible');
    }, 50);

    function closeModalAndReload() {
      backdrop.classList.remove('is-visible');
      modal.classList.remove('is-visible');
      setTimeout(function () {
        if (backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
        if (modal.parentNode) modal.parentNode.removeChild(modal);
        window.location.reload();
      }, 300);
    }

    var btnClose = modal.querySelector('#btnCloseRefillAlert');
    if (btnClose) {
      btnClose.addEventListener('click', closeModalAndReload);
    }
    backdrop.addEventListener('click', closeModalAndReload);

    // Auto-reload otomatis setelah 10 detik jika tidak ditutup
    setTimeout(function () {
      if (document.getElementById('crmRefillReadyModal')) {
        closeModalAndReload();
      }
    }, 10000);
  }

  /**
   * Pemicu utama saat Refill Nasi Ready
   */
  window.triggerRefillReadyAlarm = function () {
    // Mainkan alarm kencang
    playLoudAlarm();

    // Perbarui UI pada halaman jika ada elemen refill
    var userCard = document.getElementById('userRefillCard');
    if (userCard) {
      userCard.classList.remove('is-habis', 'has-timer');
      userCard.classList.add('is-ready');
      var badge = document.getElementById('userRefillBadge');
      if (badge) {
        badge.className = 'crm-card-refill__badge';
        badge.textContent = 'Ready';
      }
      var sub = document.getElementById('userRefillSub');
      if (sub) sub.textContent = 'Bebas tambah sepuasnya untuk dine-in';
      var tbox = document.getElementById('userTimerBox');
      if (tbox) tbox.style.display = 'none';
    }

    var pesanBanner = document.getElementById('userPesanRefillBanner');
    if (pesanBanner) {
      pesanBanner.className = 'crm-refill-banner is-ready';
      var textEl = document.getElementById('pesanRefillText');
      if (textEl) textEl.textContent = 'Ready sepuasnya untuk dine-in';
      var badgeEl = document.getElementById('pesanRefillBadge');
      if (badgeEl) badgeEl.textContent = 'Ready';
    }

    // Tampilkan modal alert
    showRefillReadyModal();
  };

  /**
   * Cek status awal pada DOM saat halaman dimuat
   */
  var initialCard = document.getElementById('userRefillCard');
  var initialBanner = document.getElementById('userPesanRefillBanner');
  var isCurrentlyHabis = (initialCard && (initialCard.classList.contains('is-habis') || initialCard.classList.contains('has-timer')))
    || (initialBanner && (initialBanner.classList.contains('is-habis') || initialBanner.classList.contains('has-timer')));

  if (isCurrentlyHabis) {
    sessionStorage.setItem('crm_refill_waiting', '1');
  }

  /**
   * Fungsi Polling / Auto-Reload Tiap 15 Detik
   */
  function checkRefillStatusAuto() {
    var apiUrl = (window.BASE_URL || '/CRM/') + 'api/get_refill_status.php';

    fetch(apiUrl, { cache: 'no-store' })
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (!data || !data.success) return;

        var isWaiting = sessionStorage.getItem('crm_refill_waiting') === '1';

        // Jika status sekarang READY dan sebelumnya user sedang menunggu
        if (data.is_ready && isWaiting) {
          sessionStorage.setItem('crm_refill_waiting', '0');
          window.triggerRefillReadyAlarm();
        } else if (!data.is_ready) {
          // Tandai bahwa refill saat ini sedang habis / dimasak
          sessionStorage.setItem('crm_refill_waiting', '1');

          // Sinkronkan countdown jika ada elemen di halaman
          var card = document.getElementById('userRefillCard');
          if (card && data.timer_ts) {
            card.setAttribute('data-target-ts', data.timer_ts);
          }
        }
      })
      .catch(function () {});
  }

  // Jalankan polling auto-reload tiap 15 detik
  setInterval(checkRefillStatusAuto, POLL_INTERVAL);
})();
