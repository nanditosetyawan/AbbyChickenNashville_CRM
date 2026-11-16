<?php
/**
 * Komponen Card Kontrol Refill Nasi (Admin & Kasir) dengan Kontrol Timer Restok Otomatis.
 */
$refill_card_info = $refill_card_info ?? get_refill_nasi_info();
$refill_card_api  = base_url('api/update_refill.php');
?>
<article class="kpi kpi--refill-card" id="adminRefillCard" data-status="<?= e($refill_card_info['status']) ?>" data-target-ts="<?= (int) $refill_card_info['timer_ts'] ?>">
  <div class="kpi__refill-inner">
    <div class="kpi__refill-icon" aria-hidden="true">
      <?= icon('rice', 28) ?>
    </div>
    <div class="kpi__refill-content">
      <div class="kpi__refill-header-row">
        <div>
          <p class="kpi__refill-title">Refill Nasi</p>
          <p class="kpi__refill-desc" id="refillDesc">
            <?= $refill_card_info['is_ready'] ? 'Stok nasi siap disajikan sepuasnya untuk dine-in.' : 'Stok nasi ditandai habis pada portal member.' ?>
          </p>
        </div>
        <div class="kpi__refill-toggle">
          <label class="switch switch--refill switch--orange" for="toggleRefill">
            <input type="checkbox" id="toggleRefill" class="js-toggle-refill"
                   <?= $refill_card_info['is_ready'] ? 'checked' : '' ?>
                   data-api="<?= e($refill_card_api) ?>">
            <span class="switch__track"></span>
            <span class="switch__label" id="refillLabel"><?= $refill_card_info['is_ready'] ? 'Ready' : 'Habis' ?></span>
          </label>
        </div>
      </div>

      <!-- Panel Kontrol Timer (Tampil saat status Habis) -->
      <div class="refill-timer-panel" id="refillTimerPanel" style="<?= $refill_card_info['is_ready'] ? 'display:none;' : '' ?>">
        <!-- 1. Tampilan Timer Aktif -->
        <div class="refill-timer-active-box" id="refillTimerActiveBox" style="<?= $refill_card_info['has_timer'] ? '' : 'display:none;' ?>">
          <div class="refill-timer-active-info">
            <span class="refill-timer-badge">
              <span class="timer-dot-pulse"></span> Otomatis Restok Dalam:
            </span>
            <span class="refill-timer-countdown-text" id="adminCountdownDisplay" data-target-ts="<?= (int) $refill_card_info['timer_ts'] ?>">
              --:--:--
            </span>
            <span class="refill-timer-target-time" id="adminTimerTarget">
              <?= !empty($refill_card_info['timer_ts']) ? '(Pukul ' . date('H:i', $refill_card_info['timer_ts']) . ' WIB)' : '' ?>
            </span>
          </div>
          <div class="refill-timer-actions">
            <button type="button" class="btn btn--sm btn--ghost" id="btnCancelTimer" title="Batalkan timer dan jadikan Habis manual">
              <?= icon('close', 14) ?> Batal Timer
            </button>
            <button type="button" class="btn btn--sm btn--secondary" id="btnChangeTimer">
              Ubah Waktu
            </button>
          </div>
        </div>

        <!-- 2. Tampilan Pengaturan Preset & Custom Timer -->
        <div class="refill-timer-setup-box" id="refillTimerSetupBox" style="<?= $refill_card_info['has_timer'] ? 'display:none;' : '' ?>">
          <p class="refill-timer-prompt">
            <?= icon('clock', 14) ?> Atur waktu otomatis restok kembali ke <strong>Ready</strong>:
          </p>
          <div class="refill-timer-presets">
            <button type="button" class="btn-chip" data-minutes="30">+30 Mnt</button>
            <button type="button" class="btn-chip" data-minutes="60">+1 Jam</button>
            <button type="button" class="btn-chip" data-minutes="120">+2 Jam</button>
            <button type="button" class="btn-chip" data-minutes="180">+3 Jam</button>
            <button type="button" class="btn-chip btn-chip--primary" data-minutes="300">+5 Jam</button>
          </div>
          <form class="refill-timer-custom-form" id="formCustomTimer">
            <label for="customTimerInput" class="visually-hidden">Durasi dalam jam</label>
            <div class="refill-timer-custom-group">
              <input type="number" id="customTimerInput" class="input input--sm" placeholder="Contoh: 5" min="0.1" max="24" step="0.5" style="width: 80px;">
              <span class="refill-timer-unit-lbl">Jam</span>
              <button type="submit" class="btn btn--sm btn--primary">Atur Timer</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</article>
