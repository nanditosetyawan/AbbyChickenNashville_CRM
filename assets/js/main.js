/* =====================================================================
   AbbyChicken Nashville CRM - skrip global
   ===================================================================== */

(function () {
  'use strict';

  /* ---------------------------------------------------------------
   * Sidebar untuk layar kecil
   * ------------------------------------------------------------- */
  const shell = document.getElementById('appShell');
  const btnSidebar = document.getElementById('btnSidebar');
  const scrim = document.getElementById('scrim');

  function tutupNav() {
    if (!shell) return;
    shell.classList.remove('is-nav-open');
    btnSidebar?.setAttribute('aria-expanded', 'false');
    if (scrim) scrim.hidden = true;
  }

  function bukaNav() {
    if (!shell) return;
    shell.classList.add('is-nav-open');
    btnSidebar?.setAttribute('aria-expanded', 'true');
    if (scrim) scrim.hidden = false;
  }

  btnSidebar?.addEventListener('click', function () {
    if (shell.classList.contains('is-nav-open')) {
      tutupNav();
    } else {
      bukaNav();
    }
  });

  scrim?.addEventListener('click', tutupNav);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') tutupNav();
  });

  /* ---------------------------------------------------------------
   * Jam berjalan di topbar
   * ------------------------------------------------------------- */
  const jam = document.getElementById('clock');
  if (jam) {
    const opsi = { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' };
    const perbarui = function () {
      jam.textContent = new Date().toLocaleString('id-ID', opsi).replace('.', ':');
    };
    perbarui();
    setInterval(perbarui, 30000);
  }

  /* ---------------------------------------------------------------
   * Tutup flash message
   * ------------------------------------------------------------- */
  document.querySelectorAll('.flash__close').forEach(function (btn) {
    btn.addEventListener('click', function () {
      btn.closest('.flash')?.remove();
    });
  });

  /* ---------------------------------------------------------------
   * Konfirmasi hapus: form[data-confirm]
   * ------------------------------------------------------------- */
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      const pesan = form.getAttribute('data-confirm') || 'Lanjutkan tindakan ini?';
      if (!window.confirm(pesan)) {
        e.preventDefault();
      }
    });
  });

  /* ---------------------------------------------------------------
   * Modal konfirmasi berbasis <dialog>
   * ------------------------------------------------------------- */
  document.querySelectorAll('[data-modal-open]').forEach(function (trigger) {
    trigger.addEventListener('click', function () {
      const target = document.querySelector(trigger.getAttribute('data-modal-open'));
      if (!target) return;

      // Isi nilai dinamis dari atribut data-* pada pemicu.
      Object.keys(trigger.dataset).forEach(function (key) {
        if (key === 'modalOpen') return;
        const slot = target.querySelector('[data-slot="' + key + '"]');
        if (slot) slot.textContent = trigger.dataset[key];
        const input = target.querySelector('[data-input="' + key + '"]');
        if (input) input.value = trigger.dataset[key];
      });

      if (typeof target.showModal === 'function') {
        target.showModal();
      } else {
        target.setAttribute('open', '');
      }
    });
  });

  document.querySelectorAll('dialog.modal').forEach(function (dialog) {
    dialog.querySelectorAll('[data-modal-close]').forEach(function (btn) {
      btn.addEventListener('click', function () { dialog.close(); });
    });
    dialog.addEventListener('click', function (e) {
      if (e.target === dialog) dialog.close();
    });
  });

  /* ---------------------------------------------------------------
   * Auto-submit saat filter berubah
   * ------------------------------------------------------------- */
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () {
      el.closest('form')?.submit();
    });
  });

  /* ---------------------------------------------------------------
   * Format Rupiah untuk input angka & parser nominal pintar (10000, 12.500, 10k)
   * ------------------------------------------------------------- */
  document.querySelectorAll('[data-rupiah]').forEach(function (el) {
    el.addEventListener('input', function () {
      const angka = el.value.replace(/\D/g, '');
      el.value = angka ? Number(angka).toLocaleString('id-ID') : '';
    });
  });

  function parseNominalRupiahJS(raw) {
    let s = String(raw || '').trim().toLowerCase();
    if (!s) return { empty: true, value: null };
    s = s.replace(/^(?:rp\.?|idr)\s*/i, '').replace(/\s+/g, '');
    if (!s) return { empty: true, value: null };

    const shortMatch = s.match(/^(\d+(?:[.,]\d{1,2})?)(?:k|rb|ribu)$/);
    if (shortMatch) {
      const angka = parseFloat(shortMatch[1].replace(',', '.'));
      return { empty: false, value: Math.round(angka * 1000) };
    }
    if (/^\d{1,3}(?:\.\d{3})+(?:,00)?$/.test(s)) {
      return { empty: false, value: parseInt(s.replace(/,00$/, '').replace(/\./g, ''), 10) };
    }
    if (/^\d{1,3}(?:,\d{3})+(?:\.00)?$/.test(s)) {
      return { empty: false, value: parseInt(s.replace(/\.00$/, '').replace(/,/g, ''), 10) };
    }
    const plainMatch = s.match(/^(\d+)(?:[.,]00)?$/);
    if (plainMatch) {
      return { empty: false, value: parseInt(plainMatch[1], 10) };
    }
    return { empty: false, value: null };
  }

  document.querySelectorAll('[data-nominal-rupiah]').forEach(function (el) {
    const targetId = el.getAttribute('data-nominal-rupiah');
    const preview = targetId ? document.getElementById(targetId) : null;
    const defaultHint = preview ? preview.textContent : '';

    function refreshPreview() {
      if (!preview) return;
      const parsed = parseNominalRupiahJS(el.value);
      if (parsed.empty) {
        preview.textContent = defaultHint;
        preview.style.color = '';
        return;
      }
      if (parsed.value === null) {
        preview.textContent = 'Format belum dikenali. Contoh: 10000, 12.500, 10k, atau 12.5k.';
        preview.style.color = '#8A5B00';
        return;
      }
      if (parsed.value < 100 || parsed.value > 500000) {
        preview.textContent = 'Nominal Rp ' + parsed.value.toLocaleString('id-ID') + ' di luar batas (Rp 100 – Rp 500.000).';
        preview.style.color = 'var(--red, #B42318)';
        return;
      }
      preview.textContent = 'Nominal asli tersimpan: Rp ' + parsed.value.toLocaleString('id-ID');
      preview.style.color = '#1C7430';
    }

    el.addEventListener('input', refreshPreview);
    el.addEventListener('blur', function () {
      const parsed = parseNominalRupiahJS(el.value);
      if (!parsed.empty && parsed.value !== null && parsed.value >= 100 && parsed.value <= 500000) {
        el.value = parsed.value.toLocaleString('id-ID');
      }
      refreshPreview();
    });
    refreshPreview();
  });

  /* ---------------------------------------------------------------
   * Stepper jumlah
   * ------------------------------------------------------------- */
  document.querySelectorAll('.qty').forEach(function (box) {
    const input = box.querySelector('input');
    box.querySelectorAll('button').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const arah = btn.dataset.step === 'up' ? 1 : -1;
        const maks = parseInt(input.max || '99', 10);
        let nilai = parseInt(input.value || '0', 10) + arah;
        if (nilai < 0) nilai = 0;
        if (nilai > maks) nilai = maks;
        input.value = nilai;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });
  });

  /* ---------------------------------------------------------------
   * Toast ringan untuk hasil AJAX
   * ------------------------------------------------------------- */
  window.tampilkanToast = function (pesan, tipe) {
    let wadah = document.querySelector('.toast-stack');
    if (!wadah) {
      wadah = document.createElement('div');
      wadah.className = 'toast-stack';
      document.body.appendChild(wadah);
    }
    const toast = document.createElement('div');
    toast.className = 'toast toast--' + (tipe || 'success');
    toast.textContent = pesan;
    wadah.appendChild(toast);
    setTimeout(function () { toast.remove(); }, 3200);
  };

  /* ---------------------------------------------------------------
   * Helper fetch JSON dengan token CSRF dari meta
   * ------------------------------------------------------------- */
  window.postJSON = function (url, data) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(data).toString()
    }).then(function (res) { return res.json(); });
  };
})();
