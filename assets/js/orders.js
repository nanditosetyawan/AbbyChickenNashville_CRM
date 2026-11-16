/* Keranjang pesanan dengan dukungan Level Kepedasan (Hot items). */

(function () {
  'use strict';

  const form = document.getElementById('formPesanan');
  if (!form) return;

  const catalogInputs = Array.from(form.querySelectorAll('.menu-catalog input[name^="jumlah["], .crm-list-stack input[name^="jumlah["]'));
  const boxRingkasan = document.getElementById('ringkasanItem');
  const elSubtotal = document.getElementById('subtotalTeks');
  const elDiskon = document.getElementById('diskonTeks');
  const barisDiskon = document.getElementById('barisDiskon');
  const elTotal = document.getElementById('totalTeks');
  const cartHiddenInputs = document.getElementById('cartHiddenInputs');

  const rupiah = function (nilai) {
    return 'Rp ' + Math.round(nilai).toLocaleString('id-ID');
  };

  // Cart state: array of { menuId, nama, harga, catatan, qty, isHot }
  let cart = [];

  // Inisialisasi item awal dari server jika ada
  if (window.__initialItems && Array.isArray(window.__initialItems) && window.__initialItems.length > 0) {
    cart = window.__initialItems.map(function (it) {
      const isHot = (it.catatan && it.catatan !== '') || /hot/i.test(it.name || '');
      return {
        menuId: parseInt(it.menu_id, 10),
        nama: it.name || '',
        harga: parseFloat(it.harga || 0),
        catatan: it.catatan || (isHot ? 'Level 1' : ''),
        qty: parseInt(it.qty, 10) || 1,
        isHot: isHot
      };
    });
  } else {
    // Populate dari input jika sudah ada nilai > 0
    catalogInputs.forEach(function (inp) {
      const q = parseInt(inp.value || '0', 10);
      if (q > 0) {
        const isHot = inp.dataset.isHot === '1' || /hot/i.test(inp.dataset.nama || '');
        cart.push({
          menuId: parseInt(inp.dataset.menuId, 10),
          nama: inp.dataset.nama || '',
          harga: parseFloat(inp.dataset.harga || '0'),
          catatan: isHot ? 'Level 1' : '',
          qty: q,
          isHot: isHot
        });
      }
    });
  }

  // Elemen Modal Desktop & Mobile
  const desktopModal = document.getElementById('modalLevelPedas');
  const mobileSheet = document.getElementById('spicyBottomSheet');
  const mobileBackdrop = document.getElementById('spicySheetBackdrop');

  let activeModalMenu = null;
  let activeModalLevel = 'Level 1';
  let activeModalQty = 1;

  function openSpicyModal(input) {
    const menuId = parseInt(input.dataset.menuId, 10);
    const nama = input.dataset.nama || '';
    const harga = parseFloat(input.dataset.harga || '0');

    activeModalMenu = { menuId: menuId, name: nama, harga: harga, inputEl: input };
    activeModalLevel = 'Level 1';
    activeModalQty = 1;

    if (desktopModal) {
      const elName = document.getElementById('desktopSpicyMenuName');
      const elQty = document.getElementById('desktopSpicyQty');
      const elSub = document.getElementById('desktopSpicySubtotal');
      if (elName) elName.textContent = nama + ' (' + rupiah(harga) + ')';
      if (elQty) elQty.value = 1;
      if (elSub) elSub.textContent = rupiah(harga * 1);

      // Reset level chip selection
      document.querySelectorAll('#modalLevelPedas .spicy-chip').forEach(function (chip) {
        const isL1 = chip.dataset.level === 'Level 1';
        chip.classList.toggle('is-selected', isL1);
        const radio = chip.querySelector('input[type="radio"]');
        if (radio) radio.checked = isL1;
        chip.style.borderColor = isL1 ? '#FF6B35' : 'var(--gray-300)';
        chip.style.background = isL1 ? '#FFF4ED' : '#FFFFFF';
      });

      if (typeof desktopModal.showModal === 'function') {
        desktopModal.showModal();
      } else {
        desktopModal.setAttribute('open', '');
      }
    } else if (mobileSheet) {
      const elName = document.getElementById('mobileSpicyMenuName');
      const elQty = document.getElementById('mobileSpicyQty');
      const elSub = document.getElementById('mobileSpicySubtotal');
      if (elName) elName.textContent = nama + ' (' + rupiah(harga) + ')';
      if (elQty) elQty.value = 1;
      if (elSub) elSub.textContent = rupiah(harga * 1);

      // Reset mobile level chip selection
      document.querySelectorAll('#spicyBottomSheet .spicy-mobile-chip').forEach(function (chip) {
        const isL1 = chip.dataset.level === 'Level 1';
        chip.classList.toggle('is-selected', isL1);
        const radio = chip.querySelector('input[type="radio"]');
        if (radio) radio.checked = isL1;
        chip.style.borderColor = isL1 ? 'var(--crm-forest)' : '#D5D3C8';
        chip.style.background = isL1 ? 'var(--crm-forest)' : 'var(--crm-sand)';
        chip.style.color = isL1 ? '#FFFFFF' : 'var(--crm-black)';
      });

      mobileSheet.classList.add('is-open');
      mobileSheet.setAttribute('aria-hidden', 'false');
      if (mobileBackdrop) mobileBackdrop.classList.add('is-open');
    }
  }

  function closeSpicyModal() {
    if (desktopModal) {
      if (typeof desktopModal.close === 'function') {
        desktopModal.close();
      } else {
        desktopModal.removeAttribute('open');
      }
    }
    if (mobileSheet) {
      mobileSheet.classList.remove('is-open');
      mobileSheet.setAttribute('aria-hidden', 'true');
      if (mobileBackdrop) mobileBackdrop.classList.remove('is-open');
    }
    activeModalMenu = null;
  }

  // Intercept '+' dan '-' klik pada menu Hot di capturing phase
  form.addEventListener('click', function (e) {
    const btnUp = e.target.closest('button[data-step="up"]');
    if (btnUp) {
      const qtyBox = btnUp.closest('.qty');
      const input = qtyBox ? qtyBox.querySelector('input') : null;
      if (input && (input.dataset.isHot === '1' || /hot/i.test(input.dataset.nama || ''))) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        openSpicyModal(input);
        return;
      }
    }

    const btnDown = e.target.closest('button[data-step="down"]');
    if (btnDown) {
      const qtyBox = btnDown.closest('.qty');
      const input = qtyBox ? qtyBox.querySelector('input') : null;
      if (input && (input.dataset.isHot === '1' || /hot/i.test(input.dataset.nama || ''))) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        handleHotDecrement(input);
        return;
      }
    }
  }, true);

  function handleHotDecrement(input) {
    const menuId = parseInt(input.dataset.menuId, 10);
    const items = cart.filter(function (it) { return it.menuId === menuId; });
    if (items.length === 0) return;

    // Kurangi varian terakhir yang ditambahkan
    const lastItem = items[items.length - 1];
    lastItem.qty--;
    if (lastItem.qty <= 0) {
      const idx = cart.indexOf(lastItem);
      if (idx !== -1) cart.splice(idx, 1);
    }
    renderCart();
  }

  // Event listener untuk Modal Desktop
  if (desktopModal) {
    document.querySelectorAll('#modalLevelPedas .spicy-chip').forEach(function (chip) {
      chip.addEventListener('click', function () {
        activeModalLevel = chip.dataset.level || 'Level 1';
        document.querySelectorAll('#modalLevelPedas .spicy-chip').forEach(function (c) {
          const sel = (c === chip);
          c.classList.toggle('is-selected', sel);
          const r = c.querySelector('input[type="radio"]');
          if (r) r.checked = sel;
          c.style.borderColor = sel ? '#FF6B35' : 'var(--gray-300)';
          c.style.background = sel ? '#FFF4ED' : '#FFFFFF';
        });
      });
    });

    const qtyInp = document.getElementById('desktopSpicyQty');
    const subEl = document.getElementById('desktopSpicySubtotal');

    function updateDesktopModalQty(newQty) {
      activeModalQty = Math.max(1, Math.min(50, newQty));
      if (qtyInp) qtyInp.value = activeModalQty;
      if (subEl && activeModalMenu) subEl.textContent = rupiah(activeModalQty * activeModalMenu.harga);
    }

    document.getElementById('btnDesktopSpicyDown')?.addEventListener('click', function () {
      updateDesktopModalQty(activeModalQty - 1);
    });
    document.getElementById('btnDesktopSpicyUp')?.addEventListener('click', function () {
      updateDesktopModalQty(activeModalQty + 1);
    });
    qtyInp?.addEventListener('input', function () {
      updateDesktopModalQty(parseInt(qtyInp.value || '1', 10));
    });

    document.getElementById('btnDesktopSpicyConfirm')?.addEventListener('click', function () {
      if (!activeModalMenu) return;
      confirmAddToCart(activeModalMenu, activeModalLevel, activeModalQty);
      closeSpicyModal();
    });
  }

  // Event listener untuk Bottom Sheet Mobile
  if (mobileSheet) {
    document.querySelectorAll('#spicyBottomSheet .spicy-mobile-chip').forEach(function (chip) {
      chip.addEventListener('click', function () {
        activeModalLevel = chip.dataset.level || 'Level 1';
        document.querySelectorAll('#spicyBottomSheet .spicy-mobile-chip').forEach(function (c) {
          const sel = (c === chip);
          c.classList.toggle('is-selected', sel);
          const r = c.querySelector('input[type="radio"]');
          if (r) r.checked = sel;
          c.style.borderColor = sel ? 'var(--crm-forest)' : '#D5D3C8';
          c.style.background = sel ? 'var(--crm-forest)' : 'var(--crm-sand)';
          c.style.color = sel ? '#FFFFFF' : 'var(--crm-black)';
        });
      });
    });

    const mobileQtyInp = document.getElementById('mobileSpicyQty');
    const mobileSubEl = document.getElementById('mobileSpicySubtotal');

    function updateMobileModalQty(newQty) {
      activeModalQty = Math.max(1, Math.min(50, newQty));
      if (mobileQtyInp) mobileQtyInp.value = activeModalQty;
      if (mobileSubEl && activeModalMenu) mobileSubEl.textContent = rupiah(activeModalQty * activeModalMenu.harga);
    }

    document.getElementById('btnMobileSpicyDown')?.addEventListener('click', function () {
      updateMobileModalQty(activeModalQty - 1);
    });
    document.getElementById('btnMobileSpicyUp')?.addEventListener('click', function () {
      updateMobileModalQty(activeModalQty + 1);
    });
    mobileQtyInp?.addEventListener('input', function () {
      updateMobileModalQty(parseInt(mobileQtyInp.value || '1', 10));
    });

    document.getElementById('btnMobileSpicyConfirm')?.addEventListener('click', function () {
      if (!activeModalMenu) return;
      confirmAddToCart(activeModalMenu, activeModalLevel, activeModalQty);
      closeSpicyModal();
    });

    document.getElementById('btnCloseSpicySheet')?.addEventListener('click', closeSpicyModal);
    if (mobileBackdrop) mobileBackdrop.addEventListener('click', closeSpicyModal);
  }

  function confirmAddToCart(menuInfo, level, qty) {
    const existing = cart.find(function (it) {
      return it.menuId === menuInfo.menuId && it.catatan === level;
    });

    if (existing) {
      existing.qty = Math.min(50, existing.qty + qty);
    } else {
      cart.push({
        menuId: menuInfo.menuId,
        nama: menuInfo.name,
        harga: menuInfo.harga,
        catatan: level,
        qty: qty,
        isHot: true
      });
    }

    renderCart();
  }

  // Handle perubahan input menu non-hot
  catalogInputs.forEach(function (input) {
    if (input.dataset.isHot === '1' || /hot/i.test(input.dataset.nama || '')) {
      return;
    }
    input.addEventListener('input', function () {
      const q = Math.max(0, parseInt(input.value || '0', 10));
      const menuId = parseInt(input.dataset.menuId, 10);
      const existing = cart.find(function (it) { return it.menuId === menuId && (!it.catatan || it.catatan === ''); });
      if (q > 0) {
        if (existing) {
          existing.qty = q;
        } else {
          cart.push({
            menuId: menuId,
            nama: input.dataset.nama || '',
            harga: parseFloat(input.dataset.harga || '0'),
            catatan: '',
            qty: q,
            isHot: false
          });
        }
      } else {
        if (existing) {
          const idx = cart.indexOf(existing);
          if (idx !== -1) cart.splice(idx, 1);
        }
      }
      renderCart(true);
    });
  });

  // Render keranjang, total, dan hidden inputs
  function renderCart(skipInputSync) {
    // 1. Sinkronisasi angka input pada kartu menu (total per menu_id)
    if (!skipInputSync) {
      catalogInputs.forEach(function (inp) {
        const mId = parseInt(inp.dataset.menuId, 10);
        const totalQty = cart.reduce(function (acc, it) {
          return it.menuId === mId ? acc + it.qty : acc;
        }, 0);
        inp.value = totalQty;
      });
    }

    // 2. Render input hidden untuk dikirimkan melalui form POST
    if (cartHiddenInputs) {
      let hiddenHtml = '';
      cart.forEach(function (it, idx) {
        hiddenHtml += '<input type="hidden" name="items[' + idx + '][menu_id]" value="' + it.menuId + '">';
        hiddenHtml += '<input type="hidden" name="items[' + idx + '][qty]" value="' + it.qty + '">';
        hiddenHtml += '<input type="hidden" name="items[' + idx + '][catatan]" value="' + (it.catatan || '') + '">';
      });
      cartHiddenInputs.innerHTML = hiddenHtml;
    }

    // 3. Render Ringkasan Pesanan
    let subtotal = 0;
    const isMobile = !!document.querySelector('.crm-member-shell');

    if (boxRingkasan) {
      if (cart.length === 0) {
        boxRingkasan.innerHTML = isMobile
          ? '<p class="text-small text-muted" style="margin: 0; color: var(--crm-sage);">Belum ada item dipilih.</p>'
          : '<p class="text-small text-muted">Belum ada item dipilih.</p>';
      } else {
        const lines = cart.map(function (it, idx) {
          const lineTotal = it.harga * it.qty;
          subtotal += lineTotal;

          if (isMobile) {
            const levelTag = it.catatan
              ? '<span class="crm-pill-tag crm-pill-tag--terracotta" style="font-size:10px;padding:1px 6px;margin-left:4px;">' + it.catatan + '</span>'
              : '';
            return '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(24,32,21,0.06);">' +
              '<div style="flex:1;">' +
                '<div style="font-weight:700;font-size:13.5px;color:var(--crm-black);display:flex;align-items:center;flex-wrap:wrap;gap:4px;">' +
                  it.nama + levelTag +
                '</div>' +
                '<div style="font-size:12px;color:var(--crm-sage);margin-top:2px;">' +
                  it.qty + ' × ' + rupiah(it.harga) + ' &middot; <strong style="color:var(--crm-forest);">' + rupiah(lineTotal) + '</strong>' +
                '</div>' +
              '</div>' +
              '<button type="button" class="js-remove-cart-item" data-index="' + idx + '" aria-label="Hapus ' + it.nama + '" style="width:26px;height:26px;border-radius:50%;border:none;background:rgba(200,90,50,0.12);color:var(--crm-terracotta);font-size:16px;display:flex;align-items:center;justify-content:center;cursor:pointer;">&times;</button>' +
            '</div>';
          } else {
            const levelBadge = it.catatan
              ? '<span class="badge badge--warning" style="font-size:10px;margin-left:6px;font-weight:600;">(' + it.catatan + ')</span>'
              : '';
            return '<div class="summary__line" style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px dashed var(--gray-200);">' +
              '<div style="flex:1;">' +
                '<span class="summary__line-name" style="font-weight:600;">' +
                  it.nama + levelBadge +
                  '<small style="display:block;color:var(--gray-500);font-size:11.5px;">' + it.qty + ' × ' + rupiah(it.harga) + '</small>' +
                '</span>' +
              '</div>' +
              '<div style="display:flex;align-items:center;gap:8px;">' +
                '<span class="summary__line-value" style="font-weight:700;">' + rupiah(lineTotal) + '</span>' +
                '<button type="button" class="btn-icon js-remove-cart-item" data-index="' + idx + '" title="Hapus item" style="color:var(--red);background:none;border:none;cursor:pointer;font-size:18px;line-height:1;padding:0 2px;">&times;</button>' +
              '</div>' +
            '</div>';
          }
        });

        boxRingkasan.innerHTML = lines.join('');

        // Listener untuk tombol hapus per baris di ringkasan
        boxRingkasan.querySelectorAll('.js-remove-cart-item').forEach(function (btn) {
          btn.addEventListener('click', function (e) {
            e.preventDefault();
            const idx = parseInt(btn.dataset.index, 10);
            if (!isNaN(idx) && idx >= 0 && idx < cart.length) {
              cart.splice(idx, 1);
              renderCart();
            }
          });
        });
      }
    } else {
      cart.forEach(function (it) {
        subtotal += it.harga * it.qty;
      });
    }

    if (elSubtotal) elSubtotal.textContent = rupiah(subtotal);

    // Hitung diskon voucher
    const potongan = window.__potonganVoucher || 0;
    if (barisDiskon && elDiskon) {
      if (potongan > 0 && subtotal > 0) {
        barisDiskon.hidden = false;
        barisDiskon.style.display = 'flex';
        elDiskon.textContent = '-' + rupiah(Math.min(potongan, subtotal));
      } else {
        barisDiskon.hidden = true;
        barisDiskon.style.display = 'none';
      }
    }

    if (elTotal) {
      const total = Math.max(0, subtotal - Math.min(potongan, subtotal));
      elTotal.textContent = rupiah(total);
    }
  }

  // Logika Voucher
  const inputVoucher = document.getElementById('kode_voucher');
  function applyVoucher() {
    if (!inputVoucher) {
      window.__potonganVoucher = 0;
      renderCart();
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

    renderCart();
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
  renderCart();
})();
