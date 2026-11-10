/* Toggle ketersediaan menu via AJAX (US-5.1). */

(function () {
  'use strict';

  const tokenEl = document.getElementById('csrfToken');
  const token = tokenEl ? tokenEl.value : '';
  const apiUrl = tokenEl ? tokenEl.dataset.api : 'api/update_status.php';
  const status = document.getElementById('statusToggle');

  function pesan(teks) {
    if (status) {
      status.textContent = teks;
    }
    if (window.tampilkanToast) {
      window.tampilkanToast(teks, 'success');
    }
  }

  document.querySelectorAll('.js-toggle-stok').forEach(function (input) {
    input.addEventListener('change', function () {
      const label = input.closest('.switch').querySelector('.switch__label');
      const id = input.dataset.id;
      const nilai = input.checked ? 'tersedia' : 'tidak tersedia';

      input.disabled = true;

      window.postJSON(apiUrl, {
        csrf_token: token,
        menu_id: id,
        status: nilai
      }).then(function (res) {
        if (!res.success) {
          throw new Error(res.message || 'Gagal menyimpan perubahan.');
        }
        if (label) label.textContent = input.checked ? 'Tersedia' : 'Habis';
        const sel = document.querySelector('[data-updated="' + id + '"]');
        if (sel) sel.textContent = 'baru saja';
        pesan(res.message);
      }).catch(function (err) {
        input.checked = !input.checked;
        if (label) label.textContent = input.checked ? 'Tersedia' : 'Habis';
        if (window.tampilkanToast) {
          window.tampilkanToast(err.message, 'error');
        }
      }).finally(function () {
        input.disabled = false;
      });
    });
  });

  const refillToggle = document.querySelector('.js-toggle-refill');
  if (refillToggle) {
    refillToggle.addEventListener('change', function () {
      const label = document.getElementById('refillLabel');
      const apiUrl = refillToggle.dataset.api;
      const nilai = refillToggle.checked ? 'ready' : 'habis';

      refillToggle.disabled = true;

      window.postJSON(apiUrl, {
        csrf_token: token,
        status: nilai
      }).then(function (res) {
        if (!res.success) {
          throw new Error(res.message || 'Gagal mengubah status refill nasi.');
        }
        if (label) label.textContent = refillToggle.checked ? 'Ready' : 'Habis';
        pesan(res.message);
      }).catch(function (err) {
        refillToggle.checked = !refillToggle.checked;
        if (label) label.textContent = refillToggle.checked ? 'Ready' : 'Habis';
        if (window.tampilkanToast) {
          window.tampilkanToast(err.message, 'error');
        }
      }).finally(function () {
        refillToggle.disabled = false;
      });
    });
  }
})();
