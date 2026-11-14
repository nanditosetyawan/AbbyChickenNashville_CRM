<?php
/**
 * Halaman Aturan & Standar Operasional Prosedur (SOP)
 * AbbyChicken Nashville CRM
 *
 * Dapat diakses oleh semua role internal (Admin, Kasir, PIC Keluhan, Pimpinan).
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
cek_akses(['Admin', 'Kasir', 'PIC Keluhan', 'Pimpinan']);

$page_title    = 'Aturan & Standar Operasional Prosedur';
$page_subtitle = 'Pedoman resmi operasional, tata tertib, dan etika kerja seluruh tim AbbyChicken Nashville CRM.';
$active_menu   = 'aturan';
$page_actions  = '<button class="btn btn--secondary" type="button" onclick="window.print()">' . icon('download', 16) . ' Cetak / Simpan PDF</button>';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card mb-24" style="background: linear-gradient(135deg, #1A535C 0%, #14444B 100%); color:#FFFFFF; border:none; padding:24px 28px; border-radius:10px;">
  <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
    <div style="width:52px; height:52px; border-radius:12px; background:rgba(255,107,53,0.18); border:1px solid rgba(255,107,53,0.4); display:flex; align-items:center; justify-content:center; color:#FF6B35; flex-shrink:0;">
      <?= icon('book', 28) ?>
    </div>
    <div style="flex:1; min-width:260px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
        <span class="badge" style="background:#FF6B35; color:#fff; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; font-size:11px;">Dokumen Resmi Internal</span>
        <span style="font-size:12px; opacity:0.85;">Revisi: November 2026 &middot; Berlaku untuk Seluruh Pegawai</span>
      </div>
      <h2 style="font-size:20px; font-weight:700; margin-bottom:6px; color:#FFFFFF;">Pedoman Operasional & Tata Tertib CRM AbbyChicken Nashville</h2>
      <p style="font-size:13px; opacity:0.9; line-height:1.6; margin:0;">
        Setiap pegawai dan pemangku kepentingan wajib membaca, memahami, dan menjalankan seluruh butir SOP di bawah demi menjaga konsistensi mutu rasa ayam Nashville, kepuasan member, dan integritas data sistem.
      </p>
    </div>
  </div>
</div>

<div class="sop-container" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(360px, 1fr)); gap:20px; margin-bottom:32px;">

  <!-- Aturan 1 -->
  <article class="card" style="display:flex; flex-direction:column; border-top:4px solid var(--teal-dark);">
    <div class="card__head" style="display:flex; align-items:center; gap:12px; padding:16px 20px;">
      <span style="width:32px; height:32px; border-radius:50%; background:var(--teal-dark); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">1</span>
      <div style="flex:1;">
        <h3 style="font-size:15px; margin:0;">Tanggung Jawab Hak Akses & Pembagian Role</h3>
        <small class="muted">Pembagian kewenangan sistem internal</small>
      </div>
      <span class="badge badge--info"><?= icon('pegawai', 12) ?> Organisasi</span>
    </div>
    <div class="card__body" style="padding:16px 20px; font-size:13px; line-height:1.6; flex:1;">
      <p style="margin-bottom:10px;">Setiap staf hanya diperkenankan menggunakan modul sesuai mandat fungsinya:</p>
      <ul style="padding-left:18px; margin:0; display:flex; flex-direction:column; gap:6px;">
        <li><strong>Admin:</strong> Manajemen menyeluruh data pegawai, katalog menu, voucher, konfigurasi promo, dan kontrol akses.</li>
        <li><strong>Kasir:</strong> Pemrosesan pesanan online real-time, validasi voucher saat bayar, dan verifikasi stok harian.</li>
        <li><strong>PIC Keluhan:</strong> Investigasi dan penyelesaian tiket keluhan pelanggan dalam SLA maksimal 1x24 jam.</li>
        <li><strong>Pimpinan:</strong> Pengawasan performa operasional, pendapatan, tren pesanan, dan monitoring KPI bisnis.</li>
      </ul>
    </div>
  </article>

  <!-- Aturan 2 -->
  <article class="card" style="display:flex; flex-direction:column; border-top:4px solid var(--orange);">
    <div class="card__head" style="display:flex; align-items:center; gap:12px; padding:16px 20px;">
      <span style="width:32px; height:32px; border-radius:50%; background:var(--orange); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">2</span>
      <div style="flex:1;">
        <h3 style="font-size:15px; margin:0;">Prosedur Pemesanan Online & SLA Waktu</h3>
        <small class="muted">Standar respon dapur dan kasir</small>
      </div>
      <span class="badge badge--warning"><?= icon('order', 12) ?> Pesanan</span>
    </div>
    <div class="card__body" style="padding:16px 20px; font-size:13px; line-height:1.6; flex:1;">
      <ul style="padding-left:18px; margin:0; display:flex; flex-direction:column; gap:6px;">
        <li>Kasir wajib memantau halaman <strong>Pesan Online</strong> secara berkala saat jam operasional buka.</li>
        <li>Pesanan masuk berstatus <em>Menunggu</em> wajib dikonfirmasi/diubah ke <em>Diproses</em> maksimal <strong>5 menit</strong> setelah pelanggan submit.</li>
        <li>Waktu penggorengan dan peracikan bumbu Nashville standar adalah <strong>15–20 menit</strong> hingga pesanan siap saji.</li>
        <li>Pesanan yang telah diserahkan ke pelanggan atau kurir wajib segera diubah statusnya menjadi <em>Selesai</em>.</li>
      </ul>
    </div>
  </article>

  <!-- Aturan 3 -->
  <article class="card" style="display:flex; flex-direction:column; border-top:4px solid var(--teal);">
    <div class="card__head" style="display:flex; align-items:center; gap:12px; padding:16px 20px;">
      <span style="width:32px; height:32px; border-radius:50%; background:var(--teal); color:#1A535C; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">3</span>
      <div style="flex:1;">
        <h3 style="font-size:15px; margin:0;">Pengelolaan Stok & Menu Siap Jual</h3>
        <small class="muted">Sinkronisasi dapur dengan katalog pelanggan</small>
      </div>
      <span class="badge badge--success"><?= icon('menu', 12) ?> Dapur</span>
    </div>
    <div class="card__body" style="padding:16px 20px; font-size:13px; line-height:1.6; flex:1;">
      <ul style="padding-left:18px; margin:0; display:flex; flex-direction:column; gap:6px;">
        <li>Pengecekan fisik stok ayam, bumbu chili oil Nashville, saus keju, dan kentang dilakukan setiap pagi pukul 08:30 WIB.</li>
        <li>Apabila salah satu bahan menu habis di tengah shift, Kasir/Admin wajib segera mengubah status menu menjadi <em>Habis / Tidak Tersedia</em> di menu <strong>Stok & Menu</strong>.</li>
        <li>Dilarang menerima pesanan untuk item menu yang stoknya sudah dinyatakan kosong demi mencegah komplain pelanggan.</li>
      </ul>
    </div>
  </article>

  <!-- Aturan 4 -->
  <article class="card" style="display:flex; flex-direction:column; border-top:4px solid var(--red);">
    <div class="card__head" style="display:flex; align-items:center; gap:12px; padding:16px 20px;">
      <span style="width:32px; height:32px; border-radius:50%; background:var(--red); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">4</span>
      <div style="flex:1;">
        <h3 style="font-size:15px; margin:0;">Penanganan Tiket Keluhan & Eskalasi</h3>
        <small class="muted">Layanan purna jual & resolusi komplain</small>
      </div>
      <span class="badge badge--danger"><?= icon('complaint', 12) ?> Layanan</span>
    </div>
    <div class="card__body" style="padding:16px 20px; font-size:13px; line-height:1.6; flex:1;">
      <ul style="padding-left:18px; margin:0; display:flex; flex-direction:column; gap:6px;">
        <li>Setiap tiket keluhan berstatus <em>Open</em> harus di-assign dan direspons paling lambat <strong>60 menit</strong> sejak masuk.</li>
        <li>Gunakan bahasa yang ramah, sopan, empatik, serta fokus pada solusi saat menghubungi pelanggan via telepon/chat.</li>
        <li>Jika keluhan menyangkut salah kirim atau mutu makanan, PIC berwenang menawarkan penggantian item menu atau voucher kompensasi.</li>
        <li>Tiket hanya boleh diubah ke status <em>Closed</em> setelah pelanggan memberikan konfirmasi puas atas penyelesaian.</li>
      </ul>
    </div>
  </article>

  <!-- Aturan 5 -->
  <article class="card" style="display:flex; flex-direction:column; border-top:4px solid #8A5B00;">
    <div class="card__head" style="display:flex; align-items:center; gap:12px; padding:16px 20px;">
      <span style="width:32px; height:32px; border-radius:50%; background:#8A5B00; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">5</span>
      <div style="flex:1;">
        <h3 style="font-size:15px; margin:0;">Tata Kelola Voucher & Batas Diskon</h3>
        <small class="muted">Integritas keuangan & promosi</small>
      </div>
      <span class="badge badge--neutral"><?= icon('voucher', 12) ?> Finansial</span>
    </div>
    <div class="card__body" style="padding:16px 20px; font-size:13px; line-height:1.6; flex:1;">
      <ul style="padding-left:18px; margin:0; display:flex; flex-direction:column; gap:6px;">
        <li>Pembuatan kode voucher baru wajib mencantumkan nominal Rupiah yang presisi dan batas tanggal kedaluwarsa yang wajar.</li>
        <li>Potongan harga maksimal per voucher adalah <strong>Rp 50.000</strong> kecuali program promo khusus yang telah disetujui Pimpinan.</li>
        <li>Dilarang membocorkan kode voucher khusus member berbayar ke khalayak umum di media sosial tanpa izin.</li>
        <li>Voucher hanya dapat digunakan 1 kali per transaksi dan tidak dapat diuangkan secara tunai.</li>
      </ul>
    </div>
  </article>

  <!-- Aturan 6 -->
  <article class="card" style="display:flex; flex-direction:column; border-top:4px solid var(--teal-dark);">
    <div class="card__head" style="display:flex; align-items:center; gap:12px; padding:16px 20px;">
      <span style="width:32px; height:32px; border-radius:50%; background:var(--teal-dark); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">6</span>
      <div style="flex:1;">
        <h3 style="font-size:15px; margin:0;">Perlindungan Privasi & Sensor Nomor Telepon</h3>
        <small class="muted">Kepatuhan UU PDP & etika kerahasiaan data</small>
      </div>
      <span class="badge badge--info"><?= icon('lock', 12) ?> Keamanan</span>
    </div>
    <div class="card__body" style="padding:16px 20px; font-size:13px; line-height:1.6; flex:1;">
      <ul style="padding-left:18px; margin:0; display:flex; flex-direction:column; gap:6px;">
        <li>Nomor kontak member ditampilkan secara tersensor (hanya 5 digit awal, misal <code>08135*******</code>) di seluruh modul staf.</li>
        <li>Dilarang menyalin, mencatat secara pribadi, atau menyebarluaskan data kontak member ke pihak ketiga manapun.</li>
        <li>Akses nomor telepon asli hanya dipakai melalui sistem otomatis terenkripsi saat integrasi kontak promosi resmi.</li>
        <li>Pelanggaran terhadap privasi data member akan dikenakan pemutusan hubungan kerja serta sanksi hukum.</li>
      </ul>
    </div>
  </article>

  <!-- Aturan 7 -->
  <article class="card" style="display:flex; flex-direction:column; border-top:4px solid var(--green);">
    <div class="card__head" style="display:flex; align-items:center; gap:12px; padding:16px 20px;">
      <span style="width:32px; height:32px; border-radius:50%; background:var(--green); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">7</span>
      <div style="flex:1;">
        <h3 style="font-size:15px; margin:0;">Etika Pengiriman Promosi WhatsApp</h3>
        <small class="muted">Standar komunikasi retensi member inaktif</small>
      </div>
      <span class="badge badge--success"><?= icon('send', 12) ?> WhatsApp</span>
    </div>
    <div class="card__body" style="padding:16px 20px; font-size:13px; line-height:1.6; flex:1;">
      <ul style="padding-left:18px; margin:0; display:flex; flex-direction:column; gap:6px;">
        <li>Pesan promosi via fitur <strong>Control Promosi</strong> hanya boleh dikirim pada jam santun (<strong>09:00 – 20:30 WIB</strong>).</li>
        <li>Teks pesan wajib memuat sapaan nama member secara personal dan menyertakan kode voucher yang aktif.</li>
        <li>Dilarang mengirim pesan beruntun (spam) ke member yang sama lebih dari 1 kali dalam kurun waktu 7 hari.</li>
        <li>Apabila member menyatakan keberatan menerima info promo di WhatsApp, staf wajib menghormati dan mencatat catatan tersebut.</li>
      </ul>
    </div>
  </article>

  <!-- Aturan 8 -->
  <article class="card" style="display:flex; flex-direction:column; border-top:4px solid var(--orange);">
    <div class="card__head" style="display:flex; align-items:center; gap:12px; padding:16px 20px;">
      <span style="width:32px; height:32px; border-radius:50%; background:var(--orange); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">8</span>
      <div style="flex:1;">
        <h3 style="font-size:15px; margin:0;">Prosedur Pembatalan Pesanan & Refund</h3>
        <small class="muted">Mitigasi kerugian & kepuasan pelanggan</small>
      </div>
      <span class="badge badge--warning"><?= icon('close', 12) ?> Kebijakan</span>
    </div>
    <div class="card__body" style="padding:16px 20px; font-size:13px; line-height:1.6; flex:1;">
      <ul style="padding-left:18px; margin:0; display:flex; flex-direction:column; gap:6px;">
        <li>Pembatalan pesanan hanya diperbolehkan jika ayam/menu habis, pelanggan tidak dapat dihubungi > 30 menit, atau atas permintaan resmi pelanggan.</li>
        <li>Kasir wajib mencatat alasan pembatalan secara jelas di sistem untuk rekap audit bulanan.</li>
        <li>Jika pesanan sudah berstatus <em>Diproses</em> dan bahan telah dimasak, pembatalan sepihak dari pelanggan tidak diperkenankan kecuali kondisi darurat.</li>
      </ul>
    </div>
  </article>

  <!-- Aturan 9 -->
  <article class="card" style="display:flex; flex-direction:column; border-top:4px solid var(--teal);">
    <div class="card__head" style="display:flex; align-items:center; gap:12px; padding:16px 20px;">
      <span style="width:32px; height:32px; border-radius:50%; background:var(--teal); color:#1A535C; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">9</span>
      <div style="flex:1;">
        <h3 style="font-size:15px; margin:0;">Klaim Voucher Google Review & Ulasan</h3>
        <small class="muted">Verifikasi program loyalitas review jujur</small>
      </div>
      <span class="badge badge--info"><?= icon('voucher', 12) ?> Ulasan</span>
    </div>
    <div class="card__body" style="padding:16px 20px; font-size:13px; line-height:1.6; flex:1;">
      <ul style="padding-left:18px; margin:0; display:flex; flex-direction:column; gap:6px;">
        <li>Staf wajib memverifikasi link ulasan Google Maps yang diajukan member di menu <strong>Ulasan & Voucher</strong>.</li>
        <li>Pastikan ulasan benar-benar terbit, memuat nama akun yang cocok, dan belum pernah diklaim sebelumnya.</li>
        <li>Status disetujui (<em>Approved</em>) akan langsung mengaktifkan voucher potongan untuk pesanan berikutnya.</li>
        <li>Tolak klaim (<em>Rejected</em>) jika tautan palsu, ulasan dihapus, atau berupa konten duplikasi/spam.</li>
      </ul>
    </div>
  </article>

  <!-- Aturan 10 -->
  <article class="card" style="display:flex; flex-direction:column; border-top:4px solid #1A535C;">
    <div class="card__head" style="display:flex; align-items:center; gap:12px; padding:16px 20px;">
      <span style="width:32px; height:32px; border-radius:50%; background:#1A535C; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">10</span>
      <div style="flex:1;">
        <h3 style="font-size:15px; margin:0;">Standar Mutu Rasa Ayam Nashville & Kemasan</h3>
        <small class="muted">Quality control hidangan khas AbbyChicken</small>
      </div>
      <span class="badge badge--success"><?= icon('rice', 12) ?> Kualitas</span>
    </div>
    <div class="card__body" style="padding:16px 20px; font-size:13px; line-height:1.6; flex:1;">
      <ul style="padding-left:18px; margin:0; display:flex; flex-direction:column; gap:6px;">
        <li>Minyak goreng wajib disaring setiap pergantian shift dan diganti total setiap maksimal 2 hari pakai.</li>
        <li>Tingkat kepedasan (Level Mild, Hot, Extra Hot Nashville) wajib ditakar menggunakan sendok takar khusus bumbu rempah.</li>
        <li>Box kemasan makanan harus diberi lubang uap secukupnya agar kulit ayam tetap renyah saat sampai di tangan pelanggan.</li>
        <li>Stiker segel higienis AbbyChicken wajib ditempel di setiap kotak kemasan sebelum diserahkan ke kurir/member.</li>
      </ul>
    </div>
  </article>

  <!-- Aturan 11 -->
  <article class="card" style="display:flex; flex-direction:column; border-top:4px solid #2B7FD4;">
    <div class="card__head" style="display:flex; align-items:center; gap:12px; padding:16px 20px;">
      <span style="width:32px; height:32px; border-radius:50%; background:#2B7FD4; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">11</span>
      <div style="flex:1;">
        <h3 style="font-size:15px; margin:0;">Keamanan Akun, Password & Komputer Kasir</h3>
        <small class="muted">Disiplin digital & perlindungan sesi</small>
      </div>
      <span class="badge badge--info"><?= icon('lock', 12) ?> Kemanan</span>
    </div>
    <div class="card__body" style="padding:16px 20px; font-size:13px; line-height:1.6; flex:1;">
      <ul style="padding-left:18px; margin:0; display:flex; flex-direction:column; gap:6px;">
        <li>Setiap staf dilarang keras memberitahukan password akunnya kepada orang lain atau sesama rekan kerja.</li>
        <li>Komputer kasir / meja staf wajib di-<em>Logout</em> saat meninggalkan meja atau saat pergantian pergantian shift.</li>
        <li>Dilarang menginstal aplikasi pihak ketiga yang tidak berizin di komputer kasir operasional AbbyChicken.</li>
      </ul>
    </div>
  </article>

  <!-- Aturan 12 -->
  <article class="card" style="display:flex; flex-direction:column; border-top:4px solid var(--red);">
    <div class="card__head" style="display:flex; align-items:center; gap:12px; padding:16px 20px;">
      <span style="width:32px; height:32px; border-radius:50%; background:var(--red); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">12</span>
      <div style="flex:1;">
        <h3 style="font-size:15px; margin:0;">Ketentuan Sanksi & Penegakan Disiplin</h3>
        <small class="muted">Tindakan atas kelalaian & pelanggaran SOP</small>
      </div>
      <span class="badge badge--danger"><?= icon('close', 12) ?> Disiplin</span>
    </div>
    <div class="card__body" style="padding:16px 20px; font-size:13px; line-height:1.6; flex:1;">
      <ul style="padding-left:18px; margin:0; display:flex; flex-direction:column; gap:6px;">
        <li><strong>Teguran Lisan:</strong> Kelalaian kecil pertama (misal: keterlambatan update status pesanan < 10 menit).</li>
        <li><strong>Surat Peringatan 1 (SP1):</strong> Pengabaian keluhan pelanggan hingga > 3 jam atau kelalaian pencatatan stok.</li>
        <li><strong>Surat Peringatan 2 (SP2):</strong> Pembatalan pesanan sepihak tanpa konfirmasi member atau salah hitung voucher.</li>
        <li><strong>Surat Peringatan 3 (SP3) / PHK:</strong> Pembocoran data member, penyalahgunaan wewenang diskon, atau manipulasi laporan keuangan.</li>
      </ul>
    </div>
  </article>

</div>

<div class="card" style="background:#FBFBF9; border:1px solid var(--gray-200); padding:20px; margin-bottom:30px; border-radius:8px;">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
    <div>
      <h4 style="margin-bottom:4px; font-size:15px;">Punya pertanyaan atau usulan perbaikan SOP?</h4>
      <p class="muted" style="font-size:13px; margin:0;">Hubungi manajemen atau diskusikan pada rapat evaluasi mingguan outlet AbbyChicken Nashville.</p>
    </div>
    <div style="display:flex; gap:10px;">
      <a class="btn btn--secondary btn--sm" href="<?= e(base_url('pages/dashboard/index.php')) ?>"><?= icon('dashboard', 14) ?> Kembali ke Dashboard</a>
      <button class="btn btn--primary btn--sm" type="button" onclick="window.scrollTo({top:0, behavior:'smooth'})"><?= icon('check', 14) ?> Mengerti Seluruh Aturan</button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

