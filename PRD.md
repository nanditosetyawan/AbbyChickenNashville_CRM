# Product Requirements Document (PRD)

## AbbyChicken Nashville CRM Web Application

| Field             | Detail                                                                                         |
| ----------------- | ---------------------------------------------------------------------------------------------- |
| **Nama Proyek**   | AbbyChicken Nashville CRM Web                                                                  |
| **Mata Kuliah**   | Manajemen Hubungan Pelanggan (CRM), Sistem Informasi, UPN Veteran Yogyakarta                   |
| **Kelompok**      | Miftahul Jannah (124240076), Sultannang Nandito S. (124240083), Wilda Ghoniyu J. (124240085), Fatihah Nafis A.Z. (124240106), Ardi Gunawan P. (124240115) |
| **Tech Stack**    | PHP Native, MySQL, HTML/CSS/JavaScript, XAMPP (localhost)                                       |
| **Deployment**    | Localhost via XAMPP                                                                             |
| **Versi Dokumen** | 1.0                                                                                            |
| **Tanggal**       | 4 November 2026                                                                                |

---

## Daftar Isi

1. [Executive Summary](#1-executive-summary)
2. [User Experience & Functionality](#2-user-experience--functionality)
3. [Technical Specifications](#3-technical-specifications)
4. [Risks & Roadmap](#4-risks--roadmap)

---

## 1. Executive Summary

### 1.1 Problem Statement

AbbyChicken Nashville adalah restoran fast food yang saat ini mengelola data pelanggan, pesanan, dan keluhan secara manual atau terpisah-pisah. Akibatnya, restoran kesulitan menjaga loyalitas pelanggan, mengelola ulasan negatif di Google Maps, menghilangkan antrian fisik di jam sibuk, serta merespons keluhan secara tepat waktu. Kondisi ini menghambat kemampuan bisnis untuk mempertahankan keunggulan kompetitif di pasar fast food yang semakin ketat.

### 1.2 Proposed Solution

Membangun aplikasi CRM berbasis web (PHP Native + MySQL, dijalankan di localhost via XAMPP) yang mengintegrasikan lima modul utama: Data Pelanggan & Membership, Ulasan & Voucher, Pesan Online, Keluhan/Ticketing, dan Stok Real-Time. Solusi ini dirancang berdasarkan analisis TOWS strategi ST1, ST2, WO1, dan WT2, sehingga setiap fitur menjawab kebutuhan bisnis yang spesifik.

### 1.3 Success Criteria

Berikut adalah KPI yang terukur untuk mengukur keberhasilan implementasi:

| No | KPI                                      | Target                                                       | Strategi TOWS |
| -- | ---------------------------------------- | ------------------------------------------------------------ | ------------- |
| 1  | Jumlah member terdaftar                  | >= 50 member tercatat dalam sistem selama periode uji coba    | ST1           |
| 2  | Persentase klaim review tervalidasi      | >= 80% klaim review diproses (approved/rejected) dalam 24 jam | ST2           |
| 3  | Waktu rata-rata penyelesaian tiket       | <= 48 jam dari tiket dibuat hingga status "selesai"           | WT2           |
| 4  | Akurasi status ketersediaan menu         | 100% menu yang ditampilkan sesuai dengan stok aktual          | WT2           |
| 5  | Jumlah pesanan online berhasil diproses  | >= 30 pesanan tercatat dalam sistem selama periode uji coba   | WO1           |

---

## 2. User Experience & Functionality

### 2.1 User Personas

#### Persona 1: Admin

| Atribut       | Deskripsi                                                                                              |
| ------------- | ------------------------------------------------------------------------------------------------------ |
| **Role**      | Admin (pegawai dengan role = "Admin")                                                                  |
| **Tujuan**    | Mengelola seluruh data CRM: member, voucher, pegawai, dan memantau dashboard KPI                       |
| **Kebutuhan** | Melihat ringkasan bisnis secara cepat, mengelola data master (member, menu, voucher, pegawai)          |
| **Frustrasi** | Data tersebar di berbagai tempat, tidak ada satu tampilan terpusat untuk memantau performa bisnis       |

#### Persona 2: Kasir

| Atribut       | Deskripsi                                                                                              |
| ------------- | ------------------------------------------------------------------------------------------------------ |
| **Role**      | Kasir (pegawai dengan role = "Kasir")                                                                  |
| **Tujuan**    | Memproses pesanan online, memperbarui status pesanan, dan menerapkan voucher                            |
| **Kebutuhan** | Antarmuka sederhana untuk melihat antrian pesanan dan mengubah status pesanan secara cepat              |
| **Frustrasi** | Antrian fisik yang panjang, kesulitan mencocokkan voucher secara manual                                 |

#### Persona 3: PIC Keluhan

| Atribut       | Deskripsi                                                                                              |
| ------------- | ------------------------------------------------------------------------------------------------------ |
| **Role**      | PIC Keluhan (pegawai dengan role = "PIC Keluhan")                                                      |
| **Tujuan**    | Menangani dan menyelesaikan tiket keluhan pelanggan                                                     |
| **Kebutuhan** | Melihat daftar keluhan yang di-assign kepadanya, memperbarui status, dan memberikan resolusi            |
| **Frustrasi** | Keluhan hilang karena tidak tercatat, tidak ada tracking status penyelesaian                            |

#### Persona 4: Member (Pelanggan Terdaftar)

| Atribut       | Deskripsi                                                                                              |
| ------------- | ------------------------------------------------------------------------------------------------------ |
| **Role**      | Pelanggan yang sudah mendaftar sebagai member                                                          |
| **Tujuan**    | Memesan makanan secara online, mengumpulkan poin, mengklaim voucher dari review, dan melaporkan keluhan |
| **Kebutuhan** | Proses pemesanan yang mudah tanpa harus mengantri, transparansi poin dan voucher                       |
| **Frustrasi** | Harus mengantri di jam sibuk, tidak tahu promo apa yang tersedia, keluhan tidak ditanggapi              |

---

### 2.2 User Stories & Acceptance Criteria

#### Modul 1: Data Pelanggan & Membership (ST1)

**US-1.1**: Sebagai Admin, saya ingin mendaftarkan member baru ke dalam sistem, sehingga data pelanggan tersimpan secara terpusat.

- **Acceptance Criteria:**
  - Form registrasi menerima input: name, email, no_telp.
  - Sistem mengisi otomatis: member_id (auto-increment), tgl_join (tanggal hari ini), total_poin (default 0), member_status (default "aktif").
  - Validasi: email harus unik dan format valid, no_telp harus numerik (10-15 digit).
  - Setelah submit, data tersimpan di tabel `member` dan muncul di daftar member.
  - Menampilkan pesan sukses "Member berhasil didaftarkan."

**US-1.2**: Sebagai Admin, saya ingin mencari member berdasarkan nama atau email, sehingga saya dapat menemukan data pelanggan dengan cepat.

- **Acceptance Criteria:**
  - Terdapat search bar di halaman Membership.
  - Pencarian bersifat case-insensitive dan mendukung partial match (LIKE query).
  - Hasil pencarian ditampilkan dalam tabel dengan kolom: member_id, name, email, no_telp, total_poin, member_status.
  - Jika tidak ditemukan, tampilkan pesan "Data member tidak ditemukan."

**US-1.3**: Sebagai Admin, saya ingin mengedit dan menghapus data member, sehingga data pelanggan selalu akurat.

- **Acceptance Criteria:**
  - Tombol "Edit" di setiap baris tabel membuka form pre-filled dengan data member.
  - Field yang dapat diedit: name, email, no_telp, member_status.
  - Tombol "Hapus" menampilkan konfirmasi sebelum menghapus data.
  - Setelah edit/hapus, tabel ter-refresh dan menampilkan data terbaru.

**US-1.4**: Sebagai Admin, saya ingin melihat riwayat transaksi seorang member, sehingga saya dapat memahami pola belanja pelanggan.

- **Acceptance Criteria:**
  - Tombol "Detail" / "Lihat Riwayat" di setiap baris member.
  - Menampilkan daftar transaksi dari tabel `pemesanan` yang terkait member_id tersebut.
  - Informasi yang ditampilkan: pemesanan_id, waktu_pemesanan, jumlah_bayar, status_pesanan.
  - Data diurutkan dari transaksi terbaru (descending by waktu_pemesanan).

---

#### Modul 2: Ulasan & Voucher (ST2)

**US-2.1**: Sebagai Member, saya ingin mengirimkan bukti review Google Maps untuk mendapatkan voucher, sehingga saya mendapat insentif untuk memberikan ulasan positif.

- **Acceptance Criteria:**
  - Form klaim review memiliki field: url_bukti (URL screenshot/link review).
  - Sistem menyimpan data ke tabel `klaim_review` dengan status_validasi = "pending" dan created_at = timestamp saat ini.
  - Member hanya dapat mengirimkan klaim jika member_status = "aktif".
  - Menampilkan pesan konfirmasi "Klaim review berhasil dikirim, menunggu validasi."

**US-2.2**: Sebagai Admin, saya ingin memvalidasi klaim review dari member, sehingga hanya ulasan yang valid yang mendapatkan voucher.

- **Acceptance Criteria:**
  - Halaman Ulasan & Voucher menampilkan daftar klaim dengan status "pending".
  - Admin dapat mengklik url_bukti untuk memverifikasi review.
  - Tombol "Approve" mengubah status_validasi menjadi "approved" dan meng-assign voucher_id ke klaim tersebut. Pegawai_id yang memvalidasi otomatis tercatat.
  - Tombol "Reject" mengubah status_validasi menjadi "rejected".
  - Voucher yang di-assign harus berstatus is_valid = true dan valid_until belum expired.

**US-2.3**: Sebagai Admin, saya ingin membuat dan mengelola voucher (CRUD), sehingga promo berjalan dengan kode yang valid.

- **Acceptance Criteria:**
  - Form pembuatan voucher memiliki field: kode_voucher, potongan_harga (nominal dalam Rupiah), valid_until (date picker).
  - Kode voucher harus unik (validasi di sisi server).
  - Default value: is_valid = true, deleted_at = NULL.
  - Admin dapat menonaktifkan voucher (soft delete dengan mengisi deleted_at).
  - Daftar voucher menampilkan: kode_voucher, potongan_harga, valid_until, is_valid, status aktif/nonaktif.

---

#### Modul 3: Pesan Online (WO1)

**US-3.1**: Sebagai Member, saya ingin melihat katalog menu yang tersedia beserta harga, sehingga saya dapat memilih makanan sebelum memesan.

- **Acceptance Criteria:**
  - Halaman Pesan Online menampilkan daftar menu dari tabel `menu` yang memiliki status_ketersediaan = "tersedia".
  - Informasi yang ditampilkan per item: name, kategori, harga (format Rupiah: Rp XX.XXX).
  - Menu dikelompokkan berdasarkan kategori (contoh: Makanan, Minuman, Snack).
  - Menu dengan status_ketersediaan = "tidak tersedia" tidak ditampilkan atau ditandai secara visual.

**US-3.2**: Sebagai Member, saya ingin membuat pesanan online dengan memilih menu dan jumlah, sehingga saya tidak perlu mengantri di tempat.

- **Acceptance Criteria:**
  - Member dapat menambahkan item menu ke pesanan (cart) dengan menentukan jumlah_pesanan per item.
  - Sistem menghitung subtotal per item (harga x jumlah_pesanan) dan total keseluruhan (jumlah_bayar).
  - Member dapat memasukkan kode_voucher (opsional). Jika valid (is_valid = true, valid_until >= hari ini, deleted_at IS NULL), potongan_harga diterapkan ke jumlah_bayar dan total_diskon terisi.
  - Setelah submit, sistem menyimpan data ke tabel `pemesanan` dan `detail_pesanan`.
  - Nomor antrian (nomor_antrian) di-generate secara auto-increment per hari (reset setiap hari baru).
  - Status pesanan awal: "menunggu".
  - Menampilkan konfirmasi pesanan dengan nomor_antrian.

**US-3.3**: Sebagai Kasir, saya ingin melihat daftar pesanan masuk dan mengubah status pesanan, sehingga antrian terkelola dengan baik.

- **Acceptance Criteria:**
  - Halaman antrian pesanan menampilkan daftar pesanan yang diurutkan berdasarkan waktu_pemesanan (ascending, FIFO).
  - Kolom yang ditampilkan: nomor_antrian, nama member, waktu_pemesanan, jumlah_bayar, status_pesanan.
  - Kasir dapat mengubah status pesanan melalui dropdown atau tombol: "menunggu" -> "diproses" -> "selesai" atau "dibatalkan".
  - Perubahan status tersimpan langsung ke database.
  - Status pesanan ditampilkan dengan badge berwarna: "menunggu" (kuning), "diproses" (biru), "selesai" (hijau), "dibatalkan" (merah).

**US-3.4**: Sebagai Admin, saya ingin mengirimkan push notification promo kepada member, sehingga pelanggan mengetahui promo eksklusif.

- **Acceptance Criteria:**
  - Terdapat halaman/form untuk membuat pengumuman promo.
  - Admin dapat menulis judul dan isi promo.
  - Notifikasi tersimpan di database dan ditampilkan kepada member saat mereka membuka halaman (in-app notification).
  - Catatan: karena deployment di localhost, push notification menggunakan pendekatan polling atau notifikasi in-app, bukan Firebase/APNs.

---

#### Modul 4: Keluhan/Ticketing (WT2)

**US-4.1**: Sebagai Member, saya ingin membuat tiket keluhan, sehingga keluhan saya tercatat dan dapat ditindaklanjuti.

- **Acceptance Criteria:**
  - Form keluhan memiliki field: kategori_keluhan (dropdown: "Kualitas Makanan", "Pelayanan", "Kebersihan", "Lainnya"), isi_keluhan (textarea, min 10 karakter).
  - Setelah submit, data tersimpan di tabel `tiket` dengan status = "open", created_at = timestamp saat ini, member_id = ID member yang login.
  - Pegawai_id diisi NULL sampai di-assign oleh Admin.
  - Menampilkan pesan "Keluhan berhasil dikirim. Nomor tiket: [tiket_id]."

**US-4.2**: Sebagai Admin, saya ingin meng-assign tiket keluhan ke PIC Keluhan, sehingga setiap keluhan memiliki penanggung jawab.

- **Acceptance Criteria:**
  - Halaman Keluhan menampilkan daftar tiket dengan filter berdasarkan status: "open", "in_progress", "closed".
  - Admin dapat memilih pegawai dengan role = "PIC Keluhan" dari dropdown untuk di-assign ke tiket.
  - Setelah assign, pegawai_id terisi dan status berubah menjadi "in_progress".

**US-4.3**: Sebagai PIC Keluhan, saya ingin melihat tiket yang di-assign kepada saya dan memperbarui statusnya, sehingga pelanggan mendapat penyelesaian.

- **Acceptance Criteria:**
  - PIC Keluhan hanya melihat tiket yang pegawai_id-nya sesuai dengan ID login.
  - PIC dapat mengubah status tiket menjadi "closed" setelah keluhan diselesaikan.
  - Status tiket ditampilkan dengan badge: "open" (merah), "in_progress" (kuning), "closed" (hijau).

---

#### Modul 5: Stok Real-Time (WT2)

**US-5.1**: Sebagai Admin, saya ingin mengubah status ketersediaan menu secara real-time, sehingga pelanggan tidak memesan menu yang habis.

- **Acceptance Criteria:**
  - Halaman Ketersediaan Stok/Menu menampilkan seluruh menu dari tabel `menu`.
  - Setiap item memiliki toggle switch untuk mengubah status_ketersediaan antara "tersedia" dan "tidak tersedia".
  - Perubahan toggle langsung tersimpan ke database (via AJAX request, tanpa reload halaman).
  - Kolom updated_at diperbarui setiap kali status berubah.

**US-5.2**: Sebagai Member, saya ingin melihat ketersediaan menu sebelum memesan, sehingga saya tidak kecewa karena menu habis.

- **Acceptance Criteria:**
  - Di halaman Pesan Online, menu yang tidak tersedia diberi tanda visual (grayscale, label "Habis", atau disembunyikan).
  - Member tidak dapat menambahkan menu yang tidak tersedia ke pesanan.

---

### 2.3 Non-Goals (Di Luar Scope)

Fitur-fitur berikut secara sadar **tidak** termasuk dalam scope proyek ini:

| No | Non-Goal                                | Alasan                                                         |
| -- | --------------------------------------- | -------------------------------------------------------------- |
| 1  | Payment gateway integration             | Deployment localhost, pembayaran tetap dilakukan secara fisik   |
| 2  | Native mobile application               | Scope tugas kuliah terbatas pada web application                |
| 3  | Multi-outlet / multi-branch support     | AbbyChicken Nashville diasumsikan satu outlet                   |
| 4  | Inventory management (bahan baku)       | Fokus pada ketersediaan menu, bukan manajemen stok bahan        |
| 5  | Push notification via Firebase/APNs     | Localhost tidak mendukung, diganti in-app notification          |
| 6  | Analytics/reporting lanjutan (BI tools) | Cukup dengan dashboard KPI sederhana                           |
| 7  | Multilingual support                    | Aplikasi hanya dalam Bahasa Indonesia                          |
| 8  | Customer self-service portal            | Member berinteraksi melalui sistem yang dioperasikan pegawai    |

---

## 3. Technical Specifications

### 3.1 Architecture Overview

Aplikasi menggunakan arsitektur **monolithic MVC-like** dengan PHP Native sebagai backend, MySQL sebagai database, dan HTML/CSS/JavaScript sebagai frontend. Seluruh komponen berjalan di localhost melalui XAMPP.

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT (Browser)                         │
│  HTML/CSS/JavaScript, Inter font, Responsive Layout             │
│  Dark teal sidebar, orange accent CTAs, white cards             │
└──────────────────────────┬──────────────────────────────────────┘
                           │ HTTP Request (GET/POST)
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                     XAMPP / Apache Server                        │
│                     (localhost:80)                               │
├─────────────────────────────────────────────────────────────────┤
│                     PHP Native Backend                           │
│                                                                  │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐                 │
│  │  Routing   │  │ Controller │  │   Views    │                 │
│  │ (index.php │──│  (Logic)   │──│  (HTML +   │                 │
│  │  + htaccess)│  │            │  │   PHP)     │                 │
│  └────────────┘  └─────┬──────┘  └────────────┘                 │
│                        │                                         │
│                  ┌─────▼──────┐                                  │
│                  │   Models   │                                  │
│                  │ (DB Query) │                                  │
│                  └─────┬──────┘                                  │
└────────────────────────┼────────────────────────────────────────┘
                         │ MySQLi / PDO
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│                     MySQL Database                               │
│                     (XAMPP - localhost:3306)                      │
│                     Database: abbychicken_crm                    │
└─────────────────────────────────────────────────────────────────┘
```

**Struktur Direktori yang Direkomendasikan:**

```
crm-web/
├── index.php                 # Entry point & routing
├── config/
│   └── database.php          # Koneksi MySQL (host, user, pass, db)
├── controllers/
│   ├── DashboardController.php
│   ├── MemberController.php
│   ├── PesananController.php
│   ├── VoucherController.php
│   ├── ReviewController.php
│   ├── TiketController.php
│   ├── MenuController.php
│   └── AuthController.php
├── models/
│   ├── Member.php
│   ├── Menu.php
│   ├── Pemesanan.php
│   ├── DetailPesanan.php
│   ├── Voucher.php
│   ├── KlaimReview.php
│   ├── Pegawai.php
│   └── Tiket.php
├── views/
│   ├── layouts/
│   │   ├── sidebar.php       # Sidebar navigation component
│   │   ├── header.php
│   │   └── footer.php
│   ├── dashboard/
│   ├── membership/
│   ├── pesanan/
│   ├── voucher/
│   ├── keluhan/
│   └── menu/
├── assets/
│   ├── css/
│   │   └── style.css         # Global styles, design system
│   ├── js/
│   │   └── app.js            # AJAX handlers, UI interactions
│   └── img/
└── .htaccess                 # URL rewriting (optional)
```

### 3.2 Database Schema

Database terdiri dari 8 tabel utama. Berikut adalah skema lengkap beserta tipe data, constraint, dan relasi:

#### Tabel `member`

| Kolom          | Tipe Data        | Constraint              | Keterangan                           |
| -------------- | ---------------- | ----------------------- | ------------------------------------ |
| member_id      | INT              | PK, AUTO_INCREMENT      | ID unik member                       |
| email          | VARCHAR(100)     | UNIQUE, NOT NULL        | Email member                         |
| no_telp        | VARCHAR(15)      | NOT NULL                | Nomor telepon                        |
| tgl_join       | DATE             | NOT NULL, DEFAULT NOW() | Tanggal pendaftaran                  |
| name           | VARCHAR(100)     | NOT NULL                | Nama lengkap member                  |
| total_poin     | INT              | DEFAULT 0               | Akumulasi poin loyalty               |
| member_status  | ENUM('aktif','nonaktif') | DEFAULT 'aktif' | Status keanggotaan                   |

#### Tabel `menu`

| Kolom                | Tipe Data              | Constraint              | Keterangan                  |
| -------------------- | ---------------------- | ----------------------- | --------------------------- |
| menu_id              | INT                    | PK, AUTO_INCREMENT      | ID unik menu                |
| name                 | VARCHAR(100)           | NOT NULL                | Nama menu                   |
| kategori             | VARCHAR(50)            | NOT NULL                | Kategori (Makanan, Minuman) |
| harga                | DECIMAL(10,2)          | NOT NULL                | Harga dalam Rupiah          |
| status_ketersediaan  | ENUM('tersedia','tidak tersedia') | DEFAULT 'tersedia' | Ketersediaan saat ini |
| updated_at           | TIMESTAMP              | ON UPDATE CURRENT_TIMESTAMP | Waktu update terakhir   |

#### Tabel `pemesanan`

| Kolom            | Tipe Data              | Constraint                       | Keterangan                     |
| ---------------- | ---------------------- | -------------------------------- | ------------------------------ |
| pemesanan_id     | INT                    | PK, AUTO_INCREMENT               | ID unik pesanan                |
| member_id        | INT                    | FK -> member.member_id, NOT NULL | Member yang memesan            |
| voucher_id       | INT                    | FK -> voucher.voucher_id, NULL   | Voucher yang digunakan         |
| nomor_antrian    | INT                    | NOT NULL                         | Nomor antrian harian           |
| total_diskon     | DECIMAL(10,2)          | DEFAULT 0                        | Total diskon dari voucher      |
| jumlah_bayar     | DECIMAL(10,2)          | NOT NULL                         | Total pembayaran setelah diskon|
| status_pesanan   | ENUM('menunggu','diproses','selesai','dibatalkan') | DEFAULT 'menunggu' | Status pesanan   |
| waktu_pemesanan  | TIMESTAMP              | DEFAULT CURRENT_TIMESTAMP        | Waktu pesanan dibuat           |

#### Tabel `detail_pesanan`

| Kolom           | Tipe Data     | Constraint                              | Keterangan                 |
| --------------- | ------------- | --------------------------------------- | -------------------------- |
| detail_id       | INT           | PK, AUTO_INCREMENT                      | ID unik detail             |
| menu_id         | INT           | FK -> menu.menu_id, NOT NULL            | Menu yang dipesan          |
| pemesanan_id    | INT           | FK -> pemesanan.pemesanan_id, NOT NULL  | Pesanan terkait            |
| jumlah_pesanan  | INT           | NOT NULL, CHECK > 0                     | Jumlah item yang dipesan   |
| subtotal        | DECIMAL(10,2) | NOT NULL                                | harga x jumlah_pesanan     |

#### Tabel `voucher`

| Kolom           | Tipe Data     | Constraint              | Keterangan                          |
| --------------- | ------------- | ----------------------- | ----------------------------------- |
| voucher_id      | INT           | PK, AUTO_INCREMENT      | ID unik voucher                     |
| kode_voucher    | VARCHAR(50)   | UNIQUE, NOT NULL        | Kode unik voucher                   |
| is_valid        | BOOLEAN       | DEFAULT TRUE            | Status validitas voucher            |
| potongan_harga  | DECIMAL(10,2) | NOT NULL                | Nominal potongan dalam Rupiah       |
| valid_until     | DATE          | NOT NULL                | Tanggal kedaluwarsa                 |
| deleted_at      | TIMESTAMP     | NULL                    | Soft delete timestamp               |

#### Tabel `klaim_review`

| Kolom            | Tipe Data              | Constraint                          | Keterangan                    |
| ---------------- | ---------------------- | ----------------------------------- | ----------------------------- |
| klaim_id         | INT                    | PK, AUTO_INCREMENT                  | ID unik klaim                 |
| member_id        | INT                    | FK -> member.member_id, NOT NULL    | Member yang mengklaim         |
| voucher_id       | INT                    | FK -> voucher.voucher_id, NULL      | Voucher yang diberikan        |
| pegawai_id       | INT                    | FK -> pegawai.pegawai_id, NULL      | Pegawai yang memvalidasi      |
| url_bukti        | VARCHAR(500)           | NOT NULL                            | URL bukti review              |
| status_validasi  | ENUM('pending','approved','rejected') | DEFAULT 'pending'    | Status validasi klaim         |
| created_at       | TIMESTAMP              | DEFAULT CURRENT_TIMESTAMP           | Waktu klaim dibuat            |

#### Tabel `pegawai`

| Kolom      | Tipe Data              | Constraint              | Keterangan                           |
| ---------- | ---------------------- | ----------------------- | ------------------------------------ |
| pegawai_id | INT                    | PK, AUTO_INCREMENT      | ID unik pegawai                      |
| email      | VARCHAR(100)           | UNIQUE, NOT NULL        | Email login pegawai                  |
| password   | VARCHAR(255)           | NOT NULL                | Password (hashed dengan password_hash) |
| nama       | VARCHAR(100)           | NOT NULL                | Nama pegawai                         |
| role       | ENUM('Admin','Kasir','PIC Keluhan') | NOT NULL   | Role/hak akses                       |
| alamat     | TEXT                   | NULL                    | Alamat pegawai                       |
| no_telp    | VARCHAR(15)            | NULL                    | Nomor telepon pegawai                |

#### Tabel `tiket`

| Kolom             | Tipe Data              | Constraint                          | Keterangan                     |
| ----------------- | ---------------------- | ----------------------------------- | ------------------------------ |
| tiket_id          | INT                    | PK, AUTO_INCREMENT                  | ID unik tiket                  |
| pegawai_id        | INT                    | FK -> pegawai.pegawai_id, NULL      | PIC yang menangani             |
| member_id         | INT                    | FK -> member.member_id, NOT NULL    | Member yang mengajukan keluhan |
| kategori_keluhan  | ENUM('Kualitas Makanan','Pelayanan','Kebersihan','Lainnya') | NOT NULL | Kategori keluhan  |
| isi_keluhan       | TEXT                   | NOT NULL                            | Detail keluhan                 |
| status            | ENUM('open','in_progress','closed') | DEFAULT 'open'       | Status penyelesaian            |
| created_at        | TIMESTAMP              | DEFAULT CURRENT_TIMESTAMP           | Waktu tiket dibuat             |

#### Entity Relationship Diagram (Relasi)

```
member (1) ──────< (N) pemesanan
member (1) ──────< (N) klaim_review
member (1) ──────< (N) tiket
menu   (1) ──────< (N) detail_pesanan
pemesanan (1) ───< (N) detail_pesanan
voucher (1) ─────< (N) pemesanan
voucher (1) ─────< (N) klaim_review
pegawai (1) ─────< (N) klaim_review
pegawai (1) ─────< (N) tiket
```

### 3.3 Integration Points

Karena aplikasi berjalan di localhost dan bersifat self-contained, integration points bersifat internal:

| Komponen              | Teknologi        | Keterangan                                                          |
| --------------------- | ---------------- | ------------------------------------------------------------------- |
| Web Server            | Apache (XAMPP)   | Menerima HTTP request, menjalankan PHP                              |
| Database              | MySQL (XAMPP)    | Penyimpanan seluruh data, diakses via MySQLi atau PDO               |
| AJAX Communication    | JavaScript Fetch / XMLHttpRequest | Untuk fitur toggle stok, update status pesanan tanpa reload |
| Session Management    | PHP Sessions     | Autentikasi dan otorisasi pegawai                                   |
| Password Hashing      | PHP password_hash / password_verify | Bcrypt hashing untuk keamanan password       |

### 3.4 Security & Privacy

Meskipun aplikasi berjalan di localhost, standar keamanan berikut tetap diterapkan:

#### Authentication & Authorization

- Login menggunakan email dan password.
- Password disimpan dalam bentuk hash menggunakan `password_hash()` dengan algoritma `PASSWORD_BCRYPT`.
- Verifikasi login menggunakan `password_verify()`.
- Session-based authentication: setelah login berhasil, data pegawai disimpan di `$_SESSION`.
- Role-Based Access Control (RBAC) dengan 3 role:

| Role          | Akses                                                                              |
| ------------- | ----------------------------------------------------------------------------------- |
| Admin         | Semua halaman: Dashboard, Membership, Pesan Online, Ulasan & Voucher, Keluhan, Stok |
| Kasir         | Pesan Online (antrian pesanan), Dashboard (view-only)                               |
| PIC Keluhan   | Keluhan (tiket yang di-assign), Dashboard (view-only)                               |

#### Data Protection

- **SQL Injection Prevention**: Menggunakan prepared statements (parameterized queries) untuk semua query database.
- **XSS Prevention**: Menggunakan `htmlspecialchars()` pada semua output data ke halaman HTML.
- **CSRF Protection**: Menggunakan token CSRF pada setiap form POST.
- **Session Security**: Menggunakan `session_regenerate_id()` setelah login untuk mencegah session fixation.
- **Input Validation**: Validasi server-side untuk semua input (tipe data, panjang, format).

#### Privacy

- Data member (email, no_telp) hanya dapat diakses oleh pegawai yang sudah login.
- Tidak ada data yang dikirim ke server eksternal (seluruh proses di localhost).

---

## 4. Risks & Roadmap

### 4.1 Technical Risks

| No | Risk                                       | Likelihood | Impact | Mitigasi                                                         |
| -- | ------------------------------------------ | ---------- | ------ | ---------------------------------------------------------------- |
| 1  | Data loss karena tidak ada backup          | Medium     | High   | Implementasi export SQL manual, dokumentasi prosedur backup      |
| 2  | Concurrent access conflict pada stok       | Low        | Medium | Gunakan transaction dan row-level locking pada update stok       |
| 3  | Performance degradation di data besar      | Low        | Medium | Tambahkan index pada kolom yang sering di-query (FK, email, status) |
| 4  | XAMPP version incompatibility              | Medium     | Medium | Dokumentasikan versi XAMPP, PHP, dan MySQL yang digunakan        |
| 5  | Nomor antrian conflict jika reset gagal    | Low        | Low    | Gunakan query MAX(nomor_antrian) + 1 WHERE DATE(waktu_pemesanan) = CURDATE() |

### 4.2 Phased Rollout

Implementasi dibagi menjadi dua fase berdasarkan prioritas dan dependency antar modul.

#### Phase 1 (P1): Foundation & Core Modules (Minggu 1-3)

Target: Infrastruktur dasar dan modul-modul yang menjadi fondasi sistem.

| Minggu | Deliverable                                         | Detail                                                              |
| ------ | --------------------------------------------------- | ------------------------------------------------------------------- |
| 1      | Project Setup & Authentication                      | Setup XAMPP, buat database + tabel, implementasi login/logout, RBAC, layout (sidebar, header) |
| 1      | Dashboard                                           | Halaman dashboard dengan KPI cards: total member, total pesanan hari ini, tiket open, menu tersedia |
| 2      | Modul Membership (CRUD + Search)                    | US-1.1, US-1.2, US-1.3, US-1.4: form registrasi, daftar member, search, edit, hapus, riwayat transaksi |
| 2      | Modul Ketersediaan Stok/Menu                        | US-5.1, US-5.2: daftar menu, toggle ketersediaan (AJAX), tampilan status di halaman pesan |
| 3      | Modul Pesan Online                                  | US-3.1, US-3.2, US-3.3: katalog menu, form pemesanan, nomor antrian, halaman antrian kasir, update status |

#### Phase 2 (P2): Advanced Modules & Polish (Minggu 4-6)

Target: Modul lanjutan dan penyempurnaan UI/UX.

| Minggu | Deliverable                                         | Detail                                                              |
| ------ | --------------------------------------------------- | ------------------------------------------------------------------- |
| 4      | Modul Ulasan & Voucher                              | US-2.1, US-2.2, US-2.3: form klaim review, validasi admin, CRUD voucher, penerapan voucher di pesanan |
| 5      | Modul Keluhan/Ticketing                             | US-4.1, US-4.2, US-4.3: form keluhan, assign PIC, update status, filter berdasarkan status |
| 5      | Notifikasi In-App                                   | US-3.4: form pengumuman promo, tampilan notifikasi untuk member     |
| 6      | Testing & Bug Fixing                                | End-to-end testing seluruh modul, perbaikan bug, optimasi query     |
| 6      | Dokumentasi & Presentasi                            | User manual, dokumentasi teknis, persiapan presentasi tugas kuliah  |

### 4.3 Acceptance Testing Checklist

Sebelum proyek dianggap selesai, seluruh item berikut harus terpenuhi:

- [ ] Login berhasil untuk ketiga role (Admin, Kasir, PIC Keluhan) dengan password yang di-hash.
- [ ] RBAC berfungsi: Kasir tidak dapat mengakses halaman Membership, PIC Keluhan tidak dapat mengakses Stok.
- [ ] CRUD member berfungsi lengkap (create, read, update, delete) dengan validasi.
- [ ] Search member by name/email mengembalikan hasil yang benar.
- [ ] Riwayat transaksi member menampilkan data pesanan yang sesuai.
- [ ] Katalog menu hanya menampilkan menu yang tersedia.
- [ ] Proses pemesanan menghasilkan nomor antrian yang unik per hari.
- [ ] Voucher dapat diterapkan pada pesanan dan mengurangi jumlah_bayar.
- [ ] Kasir dapat mengubah status pesanan dari "menunggu" ke "diproses" ke "selesai".
- [ ] Toggle ketersediaan menu bekerja tanpa reload halaman (AJAX).
- [ ] Klaim review tersimpan dengan status "pending".
- [ ] Admin dapat approve/reject klaim review.
- [ ] Voucher CRUD berfungsi lengkap termasuk soft delete.
- [ ] Tiket keluhan tersimpan dengan status "open".
- [ ] Admin dapat assign tiket ke PIC Keluhan.
- [ ] PIC Keluhan dapat mengubah status tiket menjadi "closed".
- [ ] Dashboard menampilkan KPI cards dengan data yang akurat.
- [ ] Tidak ada SQL injection vulnerability pada form input.
- [ ] Semua output di-escape dengan htmlspecialchars().
- [ ] CSRF token ada di setiap form POST.

---

### 4.4 Design System Reference

Berdasarkan desain Figma, berikut adalah panduan visual yang harus diikuti:

| Elemen              | Spesifikasi                                                   |
| -------------------- | ------------------------------------------------------------- |
| **Font**             | Inter (Google Fonts), body 14px, heading 18-24px              |
| **Sidebar**          | Dark teal/cyan background (#0D4F4F atau serupa), lebar 250px  |
| **Sidebar Active**   | Orange accent highlight pada menu aktif                       |
| **Background**       | Light gray (#F5F5F5 atau #F0F0F0)                             |
| **Cards**            | White (#FFFFFF), border-radius 8px, box-shadow subtle         |
| **CTA Buttons**      | Orange background (#FF6B00 atau serupa), white text, rounded   |
| **Status Badges**    | Rounded pill, warna sesuai status (lihat US-3.3 dan US-4.3)  |
| **Data Tables**      | Striped rows, header bold, hover highlight                    |
| **Search Bar**       | Input dengan icon search, border-radius 4px                   |
| **Charts**           | Bar chart atau line chart sederhana (gunakan Chart.js)         |

---

*Dokumen ini bersifat living document. Perubahan dan penambahan akan dilakukan seiring perkembangan proyek.*
