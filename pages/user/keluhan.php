<?php
/**
 * Halaman Keluhan Pelanggan (User / Member).
 * Desain Mobile Viewport berbasis C:\xampp\htdocs\CRM\.design\mobile.md
 * Menyediakan form pengiriman tiket keluhan langsung ke database
 * dan memantau status tindak lanjut oleh Admin dan PIC Keluhan.
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['User', 'Member']);

$member_id   = (int) ($_SESSION['member_id'] ?? 0);
$nama_member = $_SESSION['nama'] ?? 'Pelanggan';

$error = [];
$kategori_list = ['Kualitas Makanan', 'Pelayanan', 'Kebersihan', 'Lainnya'];
$kategori_dipilih = $_POST['kategori_keluhan'] ?? '';
$isi_keluhan      = trim($_POST['isi_keluhan'] ?? '');

/* Proses Kirim Keluhan */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (!in_array($kategori_dipilih, $kategori_list, true)) {
        $error['kategori'] = 'Pilih kategori keluhan yang sesuai.';
    }

    if (mb_strlen($isi_keluhan) < 5) {
        $error['isi'] = 'Mohon jelaskan keluhan Anda minimal 5 karakter.';
    }

    $foto_blob = null;
    $foto_mime = null;

    // 1. Prioritas utama: Base64 dari kompresi otomatis canvas browser (sangat cepat & hemat DB)
    $foto_base64 = $_POST['foto_base64'] ?? '';
    if (!empty($foto_base64) && preg_match('/^data:(image\/(jpeg|png|webp));base64,(.+)$/', $foto_base64, $matches)) {
        $foto_mime = $matches[1];
        $foto_blob = base64_decode($matches[3]);
        if (strlen($foto_blob) > 2 * 1024 * 1024) {
            $error['foto'] = 'Ukuran foto maksimal 2 MB.';
            $foto_blob = null;
        }
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        // 2. Fallback: upload file biasa jika tanpa javascript
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $error['foto'] = 'Gagal mengunggah foto. Silakan coba lagi.';
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $error['foto'] = 'Ukuran foto maksimal 2 MB.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $_FILES['foto']['tmp_name']);
            finfo_close($finfo);

            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            if (!in_array($mime, $allowed, true)) {
                $error['foto'] = 'Format file tidak didukung. Gunakan JPG, PNG, atau WebP.';
            } else {
                $foto_blob = file_get_contents($_FILES['foto']['tmp_name']);
                $foto_mime = ($mime === 'image/jpg') ? 'image/jpeg' : $mime;
            }
        }
    }

    if (empty($error)) {
        if ($foto_blob !== null) {
            $stmt = mysqli_prepare($conn, "INSERT INTO tiket (member_id, pegawai_id, kategori_keluhan, isi_keluhan, foto, foto_mime, status) VALUES (?, NULL, ?, ?, ?, ?, 'open')");
            $null = null;
            mysqli_stmt_bind_param($stmt, 'issbs', $member_id, $kategori_dipilih, $isi_keluhan, $null, $foto_mime);
            mysqli_stmt_send_long_data($stmt, 3, $foto_blob);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO tiket (member_id, pegawai_id, kategori_keluhan, isi_keluhan, status) VALUES (?, NULL, ?, ?, 'open')");
            mysqli_stmt_bind_param($stmt, 'iss', $member_id, $kategori_dipilih, $isi_keluhan);
        }

        if (mysqli_stmt_execute($stmt)) {
            $tiket_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            set_flash('success', 'Keluhan Anda berhasil dikirim! Tiket #' . $tiket_id . ' telah tercatat dan segera ditangani tim PIC.');
            redirect(base_url('pages/user/keluhan.php?tab=riwayat'));
        } else {
            $error['umum'] = 'Gagal menyimpan keluhan: ' . mysqli_error($conn);
            mysqli_stmt_close($stmt);
        }
    }
}

/* Riwayat tiket keluhan milik user */
$tiket_riwayat = [];
$stmt_r = mysqli_prepare($conn, "SELECT t.tiket_id, t.pegawai_id, t.member_id, t.kategori_keluhan, t.isi_keluhan, t.status, t.created_at, t.closed_at, (t.foto IS NOT NULL) AS has_foto, pg.nama AS nama_pic
    FROM tiket t
    LEFT JOIN pegawai pg ON pg.pegawai_id = t.pegawai_id
    WHERE t.member_id = ?
    ORDER BY t.created_at DESC");
mysqli_stmt_bind_param($stmt_r, 'i', $member_id);
mysqli_stmt_execute($stmt_r);
$res_r = mysqli_stmt_get_result($stmt_r);
while ($row = mysqli_fetch_assoc($res_r)) {
    $tiket_riwayat[] = $row;
}
mysqli_stmt_close($stmt_r);

$tab_aktif = $_GET['tab'] ?? 'form';

$page_title    = 'Keluhan';
$page_subtitle = 'Sampaikan kendala atau kritik saran Anda ke tim PIC';
$active_menu   = 'keluhan';

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Filter Bar (#EAE9E0) -->
<nav class="crm-filter-bar" aria-label="Navigasi Keluhan">
  <a class="crm-filter-pill<?= $tab_aktif === 'form' ? ' is-active' : '' ?>" href="<?= e(base_url('pages/user/keluhan.php')) ?>">
    <?php if ($tab_aktif === 'form'): ?><span class="crm-filter-pill__dot"></span><?php endif; ?>
    <?= icon('plus', 14) ?> Kirim Keluhan
  </a>
  <a class="crm-filter-pill<?= $tab_aktif === 'riwayat' ? ' is-active' : '' ?>" href="<?= e(base_url('pages/user/keluhan.php?tab=riwayat')) ?>">
    <?php if ($tab_aktif === 'riwayat'): ?><span class="crm-filter-pill__dot"></span><?php endif; ?>
    <?= icon('complaint', 14) ?> Riwayat Tiket
    <?php if (!empty($tiket_riwayat)): ?>
      <span class="crm-dock-badge" style="position:static;"><?= count($tiket_riwayat) ?></span>
    <?php endif; ?>
  </a>
</nav>

<?php if (!empty($error['umum'])): ?>
  <div class="crm-mobile-flash crm-mobile-flash--error"><p><?= e($error['umum']) ?></p></div>
<?php endif; ?>

<?php if ($tab_aktif === 'riwayat'): ?>
  <!-- Tab Riwayat: Hierarchical Bento Cards -->
  <div class="crm-list-stack">
    <?php if (empty($tiket_riwayat)): ?>
      <div class="crm-card-cream" style="text-align: center; padding: 36px 20px;">
        <div style="font-size: 32px; margin-bottom: 8px;"><?= icon('complaint', 36) ?></div>
        <h3 class="crm-card-cream__title">Belum ada tiket keluhan</h3>
        <p class="crm-card-cream__subtitle">Jika Anda mengalami kendala makanan atau pelayanan, kirimkan tiket baru.</p>
        <a class="crm-btn-primary" href="<?= e(base_url('pages/user/keluhan.php')) ?>" style="margin-top: 16px;">
          Tulis Keluhan Baru
        </a>
      </div>
    <?php else: ?>
      <?php foreach ($tiket_riwayat as $idx => $t): ?>
        <?php $is_open = ($t['status'] === 'open'); ?>
        <article class="crm-card-<?= $is_open ? 'forest' : 'cream' ?>">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
            <div style="flex: 1; min-width: 0; padding-right: 8px;">
              <span class="crm-pill-tag <?= $is_open ? 'crm-pill-tag--terracotta' : '' ?>" style="margin-bottom: 6px;">
                Tiket #<?= (int) $t['tiket_id'] ?> &middot; <?= e($t['kategori_keluhan']) ?>
              </span>
              <p style="margin: 6px 0 0; font-size: 13.5px; line-height: 1.45; color: <?= $is_open ? '#FFFFFF' : 'var(--crm-black)' ?>;">
                <?= nl2br(e($t['isi_keluhan'])) ?>
              </p>

              <?php if (!empty($t['has_foto'])): ?>
                <div class="crm-keluhan-foto-box">
                  <button type="button" class="crm-keluhan-foto-btn <?= $is_open ? 'is-forest' : 'is-cream' ?>"
                          onclick="crmOpenFotoModal('<?= e(base_url('pages/complaints/view_foto.php?id=' . (int) $t['tiket_id'])) ?>', '<?= (int) $t['tiket_id'] ?>')">
                    <img src="<?= e(base_url('pages/complaints/view_foto.php?id=' . (int) $t['tiket_id'])) ?>" alt="Bukti #<?= (int) $t['tiket_id'] ?>" loading="lazy" class="crm-keluhan-foto-mini">
                    <span><?= icon('camera', 13) ?> Lihat Foto Bukti</span>
                  </button>
                </div>
              <?php endif; ?>
            </div>
            <div>
              <?= badge_tiket($t['status']) ?>
            </div>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid <?= $is_open ? 'rgba(255,255,255,0.12)' : 'rgba(24,32,21,0.08)' ?>; padding-top: 10px; margin-top: 10px; font-size: 12px; color: <?= $is_open ? '#C0CDC0' : 'var(--crm-sage)' ?>;">
            <span><?= e(tanggal_id($t['created_at'], true)) ?></span>
            <span>
              <?php if (!empty($t['nama_pic'])): ?>
                PIC: <strong><?= e($t['nama_pic']) ?></strong>
              <?php else: ?>
                <em>Menunggu PIC</em>
              <?php endif; ?>
            </span>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- Tab Form Kirim Keluhan Baru (Bento Card #FAF9F5) -->
  <form class="crm-card-cream" method="post" action="" enctype="multipart/form-data" novalidate style="margin-bottom: 24px;">
    <?= csrf_field() ?>

    <div class="crm-card-cream__head">
      <div>
        <h3 class="crm-card-cream__title">Sampaikan Kendala Anda</h3>
        <p class="crm-card-cream__subtitle">Tim PIC AbbyChicken akan menindaklanjuti keluhan Anda</p>
      </div>
    </div>

    <div class="crm-mobile-field">
      <label for="kategori_keluhan">Kategori Keluhan <span style="color:var(--crm-terracotta);">*</span></label>
      <select class="crm-mobile-input" id="kategori_keluhan" name="kategori_keluhan" required>
        <option value="">&mdash; Pilih Kategori &mdash;</option>
        <?php foreach ($kategori_list as $kat): ?>
          <option value="<?= e($kat) ?>" <?= $kategori_dipilih === $kat ? 'selected' : '' ?>>
            <?= e($kat) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($error['kategori'])): ?>
        <p style="color: var(--crm-terracotta); font-size: 12px; margin: 4px 0 0;"><?= e($error['kategori']) ?></p>
      <?php endif; ?>
    </div>

    <div class="crm-mobile-field">
      <label for="isi_keluhan">Rincian Keluhan <span style="color:var(--crm-terracotta);">*</span></label>
      <textarea class="crm-mobile-input" id="isi_keluhan" name="isi_keluhan" rows="4"
                placeholder="Ceritakan kendala yang Anda alami secara detail..."
                required style="resize: vertical;"><?= e($isi_keluhan) ?></textarea>
      <?php if (isset($error['isi'])): ?>
        <p style="color: var(--crm-terracotta); font-size: 12px; margin: 4px 0 0;"><?= e($error['isi']) ?></p>
      <?php endif; ?>
    </div>

    <div class="crm-mobile-field">
      <label>Foto Bukti Keluhan <span style="font-size: 11px; font-weight: normal; color: var(--crm-sage);">(Opsional)</span></label>
      
      <div class="crm-upload-area" id="crmUploadArea">
        <input type="file" id="crmFotoInput" name="foto" accept="image/jpeg,image/png,image/webp" style="display:none;">
        <input type="hidden" name="foto_base64" id="crmFotoBase64">
        
        <div class="crm-upload-empty" id="crmUploadEmpty" onclick="document.getElementById('crmFotoInput').click();">
          <div class="crm-upload-icon"><?= icon('camera', 22) ?></div>
          <p class="crm-upload-title">Ketuk untuk mengambil atau memilih foto bukti</p>
          <span class="crm-upload-hint">Foto makanan/struk &middot; JPG, PNG, WebP</span>
        </div>

        <div class="crm-upload-preview" id="crmUploadPreview" style="display:none;">
          <img id="crmPreviewImg" src="" alt="Pratinjau Foto">
          <button type="button" class="crm-upload-remove" id="crmBtnRemovePhoto" title="Hapus foto">&times;</button>
          <div class="crm-upload-meta" id="crmUploadMeta">Foto terlampir &middot; Siap dikirim</div>
        </div>
      </div>
      <?php if (isset($error['foto'])): ?>
        <p style="color: var(--crm-terracotta); font-size: 12px; margin: 4px 0 0;"><?= e($error['foto']) ?></p>
      <?php endif; ?>
    </div>

    <button class="crm-btn-primary" type="submit" style="margin-top: 10px;">
      <?= icon('check', 16) ?> Kirim Keluhan Sekarang
    </button>
  </form>
<?php endif; ?>

<!-- Lightbox Modal Foto Keluhan (User) -->
<div class="crm-modal-lightbox" id="crmFotoModal" style="display:none;" onclick="crmCloseFotoModal(event)">
  <div class="crm-modal-lightbox__content" onclick="event.stopPropagation()">
    <div class="crm-modal-lightbox__head">
      <span id="crmFotoModalTitle">Foto Bukti Keluhan</span>
      <button type="button" class="crm-modal-lightbox__close" onclick="crmCloseFotoModal()">&times;</button>
    </div>
    <div class="crm-modal-lightbox__body">
      <img id="crmFotoModalImg" src="" alt="Bukti Keluhan">
    </div>
  </div>
</div>

<script>
// Smart Client-Side Image Resizer & Preview (Format 2: DB BLOB)
(function () {
  var input = document.getElementById('crmFotoInput');
  var base64Input = document.getElementById('crmFotoBase64');
  var emptyBox = document.getElementById('crmUploadEmpty');
  var previewBox = document.getElementById('crmUploadPreview');
  var previewImg = document.getElementById('crmPreviewImg');
  var btnRemove = document.getElementById('crmBtnRemovePhoto');
  var metaText = document.getElementById('crmUploadMeta');

  if (!input) return;

  input.addEventListener('change', function () {
    if (!this.files || !this.files[0]) return;
    var file = this.files[0];

    // Cek format gambar
    if (!file.type.match(/^image\/(jpeg|png|webp|jpg)$/i)) {
      alert('Format file tidak didukung. Mohon pilih foto JPG, PNG, atau WebP.');
      input.value = '';
      return;
    }

    var reader = new FileReader();
    reader.onload = function (e) {
      var img = new Image();
      img.onload = function () {
        // Resize menggunakan canvas off-screen agar hemat memori & database
        var maxDim = 1200;
        var w = img.width;
        var h = img.height;

        if (w > maxDim || h > maxDim) {
          if (w > h) {
            h = Math.round(h * (maxDim / w));
            w = maxDim;
          } else {
            w = Math.round(w * (maxDim / h));
            h = maxDim;
          }
        }

        var canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, w, h);

        // Kompresi ke format JPEG kualitas 0.8
        var compressedData = canvas.toDataURL('image/jpeg', 0.8);
        base64Input.value = compressedData;
        previewImg.src = compressedData;

        // Hitung estimasi ukuran setelah kompresi
        var estKb = Math.round((compressedData.length * 3 / 4) / 1024);
        metaText.textContent = 'Foto terlampir (' + estKb + ' KB) · Siap dikirim';

        emptyBox.style.display = 'none';
        previewBox.style.display = 'block';
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  });

  if (btnRemove) {
    btnRemove.addEventListener('click', function (e) {
      e.stopPropagation();
      input.value = '';
      base64Input.value = '';
      previewImg.src = '';
      previewBox.style.display = 'none';
      emptyBox.style.display = 'block';
    });
  }
})();

function crmOpenFotoModal(imgUrl, tiketId) {
  var modal = document.getElementById('crmFotoModal');
  var img = document.getElementById('crmFotoModalImg');
  var title = document.getElementById('crmFotoModalTitle');
  if (!modal || !img) return;

  img.src = imgUrl;
  if (title) title.textContent = 'Foto Bukti Tiket #' + tiketId;
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function crmCloseFotoModal(e) {
  var modal = document.getElementById('crmFotoModal');
  if (!modal) return;
  modal.style.display = 'none';
  var img = document.getElementById('crmFotoModalImg');
  if (img) img.src = '';
  document.body.style.overflow = '';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
