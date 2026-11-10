# AGENT.md - AbbyChicken Nashville CRM Web

> Panduan untuk AI coding agent (Claude Code, Codex, Antigravity, dll.) yang bekerja di proyek ini.

## 1. Project Overview

**AbbyChicken Nashville CRM Web** adalah aplikasi Customer Relationship Management berbasis web untuk restoran AbbyChicken Nashville. Aplikasi ini digunakan oleh pegawai (staff, admin, manager) untuk mengelola membership pelanggan, pesanan online, voucher/ulasan, keluhan pelanggan, dan stok menu.

Target pengguna: pegawai internal restoran AbbyChicken Nashville (bukan pelanggan langsung).

Referensi desain Figma: https://www.figma.com/design/j9hCGTFzHovCyOsVCFFyEP/MHP

## 2. Tech Stack & Prerequisites

| Komponen       | Teknologi                         |
|----------------|-----------------------------------|
| Backend        | PHP Native (prosedural, tanpa framework) |
| Database       | MySQL 5.7+                        |
| Frontend       | HTML5, CSS3, Vanilla JavaScript   |
| Server Lokal   | XAMPP (Apache + MySQL)            |
| PHP Version    | 7.4 atau lebih baru              |
| Font           | Inter (Google Fonts)              |
| Chart Library  | Chart.js (untuk dashboard)        |

**Tidak menggunakan**: framework PHP (Laravel, CI), jQuery (kecuali benar-benar diperlukan), Node.js build tools, CSS preprocessor.

## 3. Directory Structure

```
CRM Web/
├── AGENT.md                  # File ini
├── database.sql              # Schema + seed data
├── index.php                 # Entry point / login page
├── config/
│   └── database.php          # Koneksi database (mysqli)
├── includes/
│   ├── header.php            # HTML head, navbar atas
│   ├── sidebar.php           # Sidebar navigasi
│   ├── footer.php            # Footer, script closing tags
│   └── auth_check.php        # Session guard, role check
├── assets/
│   ├── css/
│   │   ├── style.css         # Global styles
│   │   └── [module].css      # Per-module styles jika perlu
│   ├── js/
│   │   ├── main.js           # Global scripts
│   │   └── [module].js       # Per-module scripts
│   └── img/
│       ├── logo.png
│       └── ...
├── pages/
│   ├── dashboard/
│   │   └── index.php         # Halaman dashboard utama
│   ├── membership/
│   │   ├── index.php         # List member
│   │   ├── tambah.php        # Form tambah member
│   │   ├── edit.php          # Form edit member
│   │   ├── detail.php        # Detail member + riwayat transaksi
│   │   └── proses.php        # Handler CRUD (POST target)
│   ├── orders/
│   │   ├── index.php         # List pesanan / antrian digital
│   │   ├── buat.php          # Form buat pesanan baru
│   │   ├── detail.php        # Detail pesanan + item
│   │   └── proses.php        # Handler CRUD
│   ├── voucher/
│   │   ├── index.php         # List voucher
│   │   ├── ulasan.php        # Validasi ulasan / klaim review
│   │   └── proses.php        # Handler CRUD
│   ├── complaints/
│   │   ├── index.php         # List tiket keluhan
│   │   ├── tambah.php        # Form input keluhan
│   │   ├── detail.php        # Detail + assign PIC + status
│   │   └── proses.php        # Handler CRUD
│   └── menu/
│       ├── index.php         # Katalog menu + toggle ketersediaan
│       ├── tambah.php        # Form tambah menu
│       ├── edit.php          # Form edit menu
│       └── proses.php        # Handler CRUD
├── api/                      # AJAX endpoints (JSON response)
│   ├── get_menu.php
│   ├── get_member.php
│   ├── update_status.php
│   └── dashboard_stats.php
└── uploads/
    └── reviews/              # Bukti foto ulasan pelanggan
```

**Aturan penting:**
- Setiap module punya folder sendiri di `pages/`.
- File `proses.php` di setiap module menangani semua operasi POST (tambah, edit, hapus). Gunakan parameter `action` untuk membedakan operasi.
- File di `api/` mengembalikan JSON, bukan HTML.
- Semua halaman di `pages/` harus `include` header, sidebar, dan footer dari `includes/`.

## 4. Database

### 4.1 Tabel (8 tabel)

| Tabel            | Deskripsi                                      |
|------------------|-------------------------------------------------|
| `member`         | Data pelanggan member (nama, telepon, poin, tier) |
| `menu`           | Daftar menu (nama, harga, kategori, stok_status) |
| `pemesanan`      | Header pesanan (member_id, tanggal, total, status) |
| `detail_pesanan` | Item per pesanan (pesanan_id, menu_id, qty, subtotal) |
| `voucher`        | Voucher yang tersedia (kode, diskon, syarat, aktif) |
| `klaim_review`   | Klaim ulasan untuk voucher (member_id, bukti_foto, status) |
| `pegawai`        | Data pegawai/user login (username, password_hash, role) |
| `tiket`          | Tiket keluhan pelanggan (member_id, subjek, deskripsi, pic_id, status) |

### 4.2 Setup Database

1. Buka phpMyAdmin (`http://localhost/phpmyadmin`).
2. Buat database baru: `abbychicken_crm`.
3. Import file `database.sql` yang ada di root project.
4. Pastikan user MySQL yang dipakai punya hak akses penuh ke database tersebut.

### 4.3 Konfigurasi Koneksi

File `config/database.php`:
```php
<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'abbychicken_crm';

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');
```

## 5. Cara Menjalankan (XAMPP)

1. Install XAMPP, aktifkan Apache dan MySQL.
2. Clone/copy folder `CRM Web` ke `C:/xampp/htdocs/crm-abbychicken/` (atau buat symbolic link).
3. Import `database.sql` via phpMyAdmin.
4. Buka browser: `http://localhost/crm-abbychicken/`.
5. Login dengan akun pegawai dari tabel `pegawai`.

**Alternatif tanpa copy**: buat Apache alias di `httpd.conf` yang mengarah ke `D:/UPN VY/Semester 5/Customer Relationship Management/Project/CRM Web/`.

## 6. Coding Conventions

### 6.1 PHP

- **Paradigma**: prosedural (tidak OOP, kecuali benar-benar perlu).
- **Database**: gunakan `mysqli` dengan **prepared statements wajib**. Jangan pernah concat variabel langsung ke query.
  ```php
  // BENAR
  $stmt = mysqli_prepare($conn, "SELECT * FROM member WHERE id = ?");
  mysqli_stmt_bind_param($stmt, "i", $id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  // SALAH - SQL injection risk
  $result = mysqli_query($conn, "SELECT * FROM member WHERE id = $id");
  ```
- **Output escaping**: selalu gunakan `htmlspecialchars()` saat menampilkan data user ke HTML.
- **File encoding**: UTF-8 tanpa BOM.
- **PHP closing tag**: jangan gunakan `?>` di akhir file PHP-only (mencegah whitespace issues).

### 6.2 Naming

| Konteks              | Konvensi        | Contoh                        |
|----------------------|-----------------|-------------------------------|
| File PHP             | snake_case      | `detail_pesanan.php`          |
| Variabel PHP         | snake_case      | `$total_poin`, `$nama_member` |
| Fungsi PHP           | snake_case      | `get_member_by_id()`          |
| Tabel database       | snake_case      | `detail_pesanan`              |
| Kolom database       | snake_case      | `created_at`, `status_aktif`  |
| CSS class            | kebab-case / BEM | `card-member`, `btn--primary` |
| ID HTML              | camelCase       | `formTambahMember`            |
| File JS              | snake_case      | `dashboard_chart.js`          |
| Konstanta PHP        | UPPER_SNAKE     | `MAX_POIN_PER_TRX`           |

### 6.3 HTML

- Gunakan tag semantik: `<main>`, `<section>`, `<nav>`, `<article>`, `<table>`, `<form>`.
- Setiap `<input>` harus punya `<label>` yang terhubung via `for`/`id`.
- Setiap `<img>` harus punya atribut `alt`.
- Setiap `<table>` harus punya `<thead>` dan `<tbody>`.

### 6.4 CSS

- Utamakan class-based styling, hindari inline style.
- Responsive: gunakan media queries, minimal breakpoint di 768px dan 1024px.
- Jangan gunakan `!important` kecuali override library pihak ketiga.

### 6.5 JavaScript

- Vanilla JS. Tidak pakai jQuery kecuali ada alasan teknis spesifik yang didokumentasikan.
- Gunakan `fetch()` untuk AJAX calls, bukan `XMLHttpRequest`.
- Event listener via `addEventListener()`, bukan atribut `onclick` di HTML.

## 7. Authentication & Authorization

### 7.1 Mekanisme

- Login berbasis **session PHP** (`$_SESSION`).
- Password di-hash dengan `password_hash()` (bcrypt) saat registrasi, diverifikasi dengan `password_verify()` saat login.
- Setelah login berhasil, simpan `$_SESSION['pegawai_id']`, `$_SESSION['role']`, `$_SESSION['nama']`.

### 7.2 Role

| Role      | Akses                                                |
|-----------|------------------------------------------------------|
| `admin`   | Semua fitur, termasuk manajemen pegawai              |
| `manager` | Dashboard, laporan, approve voucher, assign PIC tiket |
| `staff`   | Input pesanan, membership, keluhan                   |

### 7.3 Guard

Setiap halaman di `pages/` harus include `auth_check.php` di baris paling atas:
```php
<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Contoh: halaman khusus admin
cek_akses(['admin']);
```

Fungsi `cek_akses($roles_allowed)` mengecek apakah `$_SESSION['role']` ada di array yang diizinkan. Jika tidak, redirect ke halaman unauthorized.

## 8. File Naming Patterns

| Tipe File                   | Pola Nama                    | Contoh                    |
|-----------------------------|------------------------------|---------------------------|
| Halaman list/index          | `index.php`                  | `pages/membership/index.php` |
| Halaman form tambah         | `tambah.php`                 | `pages/membership/tambah.php` |
| Halaman form edit           | `edit.php`                   | `pages/membership/edit.php` |
| Halaman detail              | `detail.php`                 | `pages/membership/detail.php` |
| Handler proses (POST)       | `proses.php`                 | `pages/membership/proses.php` |
| AJAX endpoint               | `get_[resource].php`         | `api/get_member.php`      |
| AJAX update                 | `update_[resource].php`      | `api/update_status.php`   |
| CSS per module              | `[module].css`               | `assets/css/membership.css` |
| JS per module               | `[module].js`                | `assets/js/orders.js`     |

## 9. Design Reference

### 9.1 Figma

Link: https://www.figma.com/design/j9hCGTFzHovCyOsVCFFyEP/MHP

Selalu rujuk Figma untuk layout, spacing, dan komponen UI sebelum membuat halaman baru.

### 9.2 Color Palette

| Nama            | Hex       | Penggunaan                        |
|-----------------|-----------|-----------------------------------|
| Dark Teal       | `#1A535C` | Sidebar background                |
| Teal            | `#4ECDC4` | Sidebar active item, accent       |
| Orange          | `#FF6B35` | Primary button, CTA, highlight    |
| Dark Orange     | `#E55A2B` | Button hover state                |
| White           | `#FFFFFF` | Content area background           |
| Light Gray      | `#F5F5F5` | Card background, table alt rows   |
| Dark Gray       | `#333333` | Body text                         |
| Medium Gray     | `#666666` | Secondary text, labels            |
| Success Green   | `#28A745` | Status aktif, badge sukses        |
| Danger Red      | `#DC3545` | Status gagal, tombol hapus        |
| Warning Yellow  | `#FFC107` | Status pending, badge warning     |

### 9.3 Typography

- **Font family**: `'Inter', sans-serif`
- **Import**: `<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">`
- Heading h1: 24px/700, h2: 20px/600, h3: 16px/600
- Body text: 14px/400
- Small/caption: 12px/400

### 9.4 Layout

- Sidebar: fixed left, width 260px, dark teal background, logo di atas, menu navigasi vertical.
- Content area: margin-left 260px, padding 24px.
- Cards: border-radius 8px, shadow `0 2px 8px rgba(0,0,0,0.08)`, padding 20px.
- Tables: full width, striped rows, header background `#F5F5F5`.
- Buttons: border-radius 6px, padding 8px 16px, font-weight 500.

## 10. Testing Checklist (Manual)

Sebelum commit atau merge, pastikan semua poin ini dites:

### 10.1 Authentication
- [ ] Login dengan kredensial benar berhasil redirect ke dashboard.
- [ ] Login dengan kredensial salah menampilkan pesan error.
- [ ] Akses halaman tanpa login redirect ke halaman login.
- [ ] Role staff tidak bisa akses halaman admin-only.
- [ ] Logout menghapus session dan redirect ke login.

### 10.2 Membership
- [ ] Tambah member baru dengan data lengkap berhasil.
- [ ] Validasi form: field wajib kosong menampilkan error.
- [ ] Edit member menyimpan perubahan dengan benar.
- [ ] Hapus member (soft delete atau konfirmasi) berfungsi.
- [ ] Riwayat transaksi member tampil di halaman detail.
- [ ] Poin member terupdate otomatis setelah transaksi.

### 10.3 Pesanan Online
- [ ] Buat pesanan baru dengan pilihan menu.
- [ ] Antrian digital menampilkan nomor urut.
- [ ] Detail pesanan menampilkan item, qty, subtotal, total.
- [ ] Status pesanan bisa diupdate (diproses, selesai, dibatalkan).

### 10.4 Voucher & Ulasan
- [ ] List voucher menampilkan semua voucher aktif/nonaktif.
- [ ] Klaim ulasan: upload bukti foto berhasil.
- [ ] Validasi ulasan oleh manager (approve/reject).
- [ ] Voucher otomatis diberikan setelah ulasan di-approve.

### 10.5 Tiket Keluhan
- [ ] Input keluhan baru dengan data lengkap.
- [ ] Assign PIC ke tiket.
- [ ] Update status tiket (baru, diproses, selesai).
- [ ] Filter tiket berdasarkan status.

### 10.6 Menu / Stok
- [ ] Toggle ketersediaan menu (tersedia/habis).
- [ ] Tambah, edit, hapus menu.
- [ ] Status refill terlihat di list.

### 10.7 Dashboard
- [ ] KPI cards menampilkan angka yang benar (total member, pesanan hari ini, tiket open, pendapatan).
- [ ] Chart/grafik ter-render tanpa error.
- [ ] Recent activity menampilkan data terbaru.

### 10.8 General
- [ ] Tidak ada error PHP (warning, notice) di halaman manapun.
- [ ] Semua form menggunakan CSRF token atau validasi POST.
- [ ] Tidak ada SQL injection (semua query pakai prepared statements).
- [ ] Upload file hanya menerima format gambar (jpg, png, jpeg).
- [ ] Responsive: layout tidak pecah di layar 768px.

## 11. Common Pitfalls

- **Lupa `session_start()`**: harus dipanggil di baris pertama setiap file yang butuh session, sebelum output HTML apapun.
- **Path relatif salah**: saat include file, gunakan `__DIR__` untuk path absolut. Contoh: `require_once __DIR__ . '/../config/database.php';`
- **Upload folder permission**: pastikan folder `uploads/reviews/` writable.
- **Karakter encoding**: selalu set `mysqli_set_charset($conn, 'utf8mb4')` setelah koneksi.
- **Redirect setelah POST**: selalu gunakan pattern POST-Redirect-GET. Setelah proses form, `header('Location: ...')` lalu `exit;`.
- **XSS**: setiap data dari database yang ditampilkan di HTML harus melewati `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')`.

## 12. Git Conventions

- Branch naming: `feature/[module]-[deskripsi]`, contoh: `feature/membership-crud`, `fix/dashboard-chart-error`.
- Commit message dalam Bahasa Indonesia, format: `[module] deskripsi singkat`. Contoh: `[membership] tambah fitur export data member`.
- Jangan commit file `config/database.php` yang berisi kredensial production. Gunakan `.gitignore`.

---

<!-- antislop:start -->
## antislop

Untuk pekerjaan terkait UI, copywriting, people, mobile layout, atau code comments, baca `antislop.md` (core) terlebih dahulu, lalu baca skill spesifik sesuai task:

- UI / visual: `skills/antislop-ui/SKILL.md`
- Copy & text: `skills/antislop-copywriting/SKILL.md`
- People: `skills/antislop-human/SKILL.md`
- Mobile / responsive: `skills/antislop-layoutmobile/SKILL.md`
- Code comments: `skills/antislop-code/SKILL.md`
<!-- antislop:end -->
