<?php
/**
 * Komponen Card Kontrol Refill Nasi (Admin & Kasir) dengan Kontrol Timer Restok Otomatis.
 */
$refill_card_info = $refill_card_info ?? get_refill_nasi_info();
$refill_card_api  = base_url('api/update_refill.php');
?>
<article class="kpi kpi--refill-card" id="adminRefillCard" data-status="<?= e($refill_card_info['status']) ?>" data-target-ts="<?= (int) $refill_card_info['timer_ts'] ?>">
  <!-- Header Card: Icon, Judul, Deskripsi Singkat, & Switch Ready/Habis -->
  <div class="kpi__refill-header-row">
    <div class="kpi__refill-identity">
      <div class="kpi__refill-icon" aria-hidden="true">
        <?= icon('rice', 20) ?>
      </div>
      <div class="kpi__refill-text">
        <p class="kpi__refill-title">Refill Nasi</p>
        <p class="kpi__refill-desc" id="refillDesc">
          <?= $refill_card_info['is_ready'] ? 'Stok siap disajikan.' : 'Stok habis di member.' ?>
        </p>
      </div>
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

  <!-- Panel Kontrol Timer (Hanya tampil saat status Habis) -->
  <div class="refill-timer-panel" id="refillTimerPanel" style="<?= $refill_card_info['is_ready'] ? 'display:none;' : '' ?>">
    <!-- 1. Tampilan Timer Aktif (Countdown Berjalan) -->
    <div class="refill-timer-active-box" id="refillTimerActiveBox" style="<?= $refill_card_info['has_timer'] ? '' : 'display:none;' ?>">
      <div class="refill-timer-active-top">
        <span class="refill-timer-badge">
          <span class="timer-dot-pulse"></span> RESTOK OTOMATIS
        </span>
        <span class="refill-timer-target-time" id="adminTimerTarget">
          <?= !empty($refill_card_info['timer_ts']) ? date('H:i', $refill_card_info['timer_ts']) . ' WIB' : '' ?>
        </span>
      </div>
      <div class="refill-timer-countdown-row">
        <span class="refill-timer-countdown-text" id="adminCountdownDisplay" data-target-ts="<?= (int) $refill_card_info['timer_ts'] ?>">
          --:--:--
        </span>
      </div>
      <div class="refill-timer-actions-grid">
        <button type="button" class="btn-refill-act btn-refill-act--cancel" id="btnCancelTimer" title="Batalkan timer dan jadikan Habis manual">
          <?= icon('close', 12) ?> <span>Batal</span>
        </button>
        <button type="button" class="btn-refill-act btn-refill-act--edit" id="btnChangeTimer" title="Ubah durasi timer">
          <?= icon('clock', 12) ?> <span>Ubah</span>
        </button>
      </div>
    </div>

    <!-- 2. Tampilan Pengaturan Preset & Custom Timer -->
    <div class="refill-timer-setup-box" id="refillTimerSetupBox" style="<?= $refill_card_info['has_timer'] ? 'display:none;' : '' ?>">
      <div class="refill-timer-setup-header">
        <span class="refill-timer-prompt">
          <?= icon('clock', 13) ?> Atur Timer Restok
        </span>
        <button type="button" class="btn-refill-back" id="btnBackToActiveTimer" style="<?= $refill_card_info['has_timer'] ? '' : 'display:none;' ?>" title="Kembali ke tampilan timer">
          Kembali
        </button>
      </div>
      <div class="refill-presets-grid">
        <button type="button" class="btn-chip" data-minutes="30">+30 Mnt</button>
        <button type="button" class="btn-chip" data-minutes="60">+1 Jam</button>
        <button type="button" class="btn-chip" data-minutes="120">+2 Jam</button>
        <button type="button" class="btn-chip" data-minutes="180">+3 Jam</button>
        <button type="button" class="btn-chip" data-minutes="240">+4 Jam</button>
        <button type="button" class="btn-chip btn-chip--accent" data-minutes="300">+5 Jam</button>
      </div>
      <form class="refill-custom-form" id="formCustomTimer">
        <label for="customTimerInput" class="visually-hidden">Durasi dalam jam</label>
        <div class="refill-custom-input-group">
          <div class="refill-input-wrapper">
            <input type="number" id="customTimerInput" class="refill-input-number" placeholder="Misal: 1.5" min="0.1" max="24" step="0.5">
            <span class="refill-input-unit">Jam</span>
          </div>
          <button type="submit" class="btn-refill-submit">Setel</button>
        </div>
      </form>
    </div>
  </div>
</article>
