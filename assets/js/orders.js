/* Keranjang pesanan: hitung subtotal dan total secara langsung. */

(function () {
  'use strict';

  const form = document.getElementById('formPesanan');
  if (!form) return;

  const inputs = Array.from(form.querySelectorAll('input[name^="jumlah["]'));
  const boxRingkasan = document.getElementById('ringkasanItem');
  const elSubtotal = document.getElementById('subtotalTeks');
  const elDiskon = document.getElementById('diskonTeks');
  const barisDiskon = document.getElementById('barisDiskon');
  const elTotal = document.getElementById('totalTeks');

  const rupiah = function (nilai) {
    return 'Rp ' + Math.round(nilai).toLocaleString('id-ID');
  };

  function hitung() {
    let subtotal = 0;
    const baris = [];

    inputs.forEach(function (input) {
      const qty = Math.max(0, parseInt(input.value || '0', 10));
      const harga = parseFloat(input.dataset.harga || '0');
      if (qty > 0) {
        subtotal += harga * qty;
        baris.push(
          '<div class="summary__line">' +
            '<span class="summary__line-name">' + input.dataset.nama +
              '<small>' + qty + ' × ' + rupiah(harga) + '</small>' +
            '</span>' +
            '<span class="summary__line-value">' + rupiah(harga * qty) + '</span>' +
          '</div>'
        );
      }
    });

    if (boxRingkasan) {
      boxRingkasan.innerHTML = baris.length
        ? baris.join('')
        : '<p class="text-small text-muted">Belum ada item dipilih.</p>';
    }

    if (elSubtotal) elSubtotal.textContent = rupiah(subtotal);

    // Diskon hanya estimasi tampilan; nilai final dihitung ulang di server.
    const potongan = window.__potonganVoucher || 0;
    if (barisDiskon && elDiskon) {
      if (potongan > 0) {
        barisDiskon.hidden = false;
        if (!barisDiskon.classList.contains('summary__row--diskon')) {
          barisDiskon.style.display = 'flex';
        } else {
          barisDiskon.style.display = '';
        }
        elDiskon.textContent = '-' + rupiah(Math.min(potongan, subtotal));
      } else {
        barisDiskon.hidden = true;
        if (!barisDiskon.classList.contains('summary__row--diskon')) {
          barisDiskon.style.display = 'none';
        } else {
          barisDiskon.style.display = '';
        }
      }
    }

    if (elTotal) {
      const total = Math.max(0, subtotal - Math.min(potongan, subtotal));
      elTotal.textContent = rupiah(total);
    }
  }

  inputs.forEach(function (input) {
    input.addEventListener('input', hitung);
    input.addEventListener('change', hitung);
  });

  const inputVoucher = document.getElementById('kode_voucher');
  function applyVoucher() {
    if (!inputVoucher) {
      window.__potonganVoucher = 0;
      hitung();
      return;
    }
    const rawKode = inputVoucher.value.trim();
    const kode = rawKode.toUpperCase();
    const peta = window.__voucherMap || {};
    window.__potonganVoucher = peta[kode] || peta[rawKode] || 0;

    document.querySelectorAll('.js-pick-order-voucher').forEach(function (chip) {
      const chipKode = (chip.getAttribute('data-kode') || '').toUpperCase();
      chip.classList.toggle('is-selected', kode !== '' && chipKode === kode);
    });

    hitung();
  }

  if (inputVoucher) {
    inputVoucher.addEventListener('input', applyVoucher);
    inputVoucher.addEventListener('change', applyVoucher);
  }

  document.querySelectorAll('.js-pick-order-voucher').forEach(function (chip) {
    chip.addEventListener('click', function () {
      if (!inputVoucher) return;
      inputVoucher.value = chip.getAttribute('data-kode') || '';
      applyVoucher();
    });
  });

  applyVoucher();
})();
