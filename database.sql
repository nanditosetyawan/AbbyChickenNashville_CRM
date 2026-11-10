-- =====================================================================
-- AbbyChicken Nashville CRM Web
-- Skema database + data contoh
-- Import melalui phpMyAdmin atau: mysql -u root < database.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS abbychicken_crm
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE abbychicken_crm;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notifikasi_promo;
DROP TABLE IF EXISTS detail_pesanan;
DROP TABLE IF EXISTS pemesanan;
DROP TABLE IF EXISTS klaim_review;
DROP TABLE IF EXISTS tiket;
DROP TABLE IF EXISTS pengumuman;
DROP TABLE IF EXISTS voucher;
DROP TABLE IF EXISTS menu;
DROP TABLE IF EXISTS pengaturan;
DROP TABLE IF EXISTS member;
DROP TABLE IF EXISTS pegawai;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- pegawai
-- ---------------------------------------------------------------------
CREATE TABLE pegawai (
  pegawai_id INT AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(100) NOT NULL,
  password   VARCHAR(255) NOT NULL,
  nama       VARCHAR(100) NOT NULL,
  role       ENUM('Admin','Kasir','PIC Keluhan','Pimpinan') NOT NULL,
  alamat     TEXT NULL,
  no_telp    VARCHAR(15) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pegawai_email (email),
  KEY idx_pegawai_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- member
-- ---------------------------------------------------------------------
CREATE TABLE member (
  member_id     INT AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(100) NOT NULL,
  password      VARCHAR(255) NOT NULL DEFAULT '123456',
  no_telp       VARCHAR(15) NOT NULL,
  tgl_join      DATE NOT NULL,
  name          VARCHAR(100) NOT NULL,
  total_poin    INT NOT NULL DEFAULT 0,
  member_status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  UNIQUE KEY uq_member_email (email),
  KEY idx_member_nama (name),
  KEY idx_member_status (member_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- pengaturan (pengaturan sistem / status operasional real-time)
-- ---------------------------------------------------------------------
CREATE TABLE pengaturan (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_val TEXT NOT NULL,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- menu
-- ---------------------------------------------------------------------
CREATE TABLE menu (
  menu_id             INT AUTO_INCREMENT PRIMARY KEY,
  name                VARCHAR(100) NOT NULL,
  kategori            VARCHAR(50) NOT NULL,
  harga               DECIMAL(10,2) NOT NULL,
  status_ketersediaan ENUM('tersedia','tidak tersedia') NOT NULL DEFAULT 'tersedia',
  updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_menu_kategori (kategori),
  KEY idx_menu_status (status_ketersediaan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- voucher
-- ---------------------------------------------------------------------
CREATE TABLE voucher (
  voucher_id     INT AUTO_INCREMENT PRIMARY KEY,
  kode_voucher   VARCHAR(50) NOT NULL,
  is_valid       BOOLEAN NOT NULL DEFAULT TRUE,
  potongan_harga DECIMAL(10,2) NOT NULL,
  valid_until    DATE NOT NULL,
  deleted_at     TIMESTAMP NULL DEFAULT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_voucher_kode (kode_voucher),
  KEY idx_voucher_valid (is_valid, valid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- pemesanan
-- ---------------------------------------------------------------------
CREATE TABLE pemesanan (
  pemesanan_id    INT AUTO_INCREMENT PRIMARY KEY,
  member_id       INT NOT NULL,
  voucher_id      INT NULL,
  nomor_antrian   INT NOT NULL,
  total_diskon    DECIMAL(10,2) NOT NULL DEFAULT 0,
  jumlah_bayar    DECIMAL(10,2) NOT NULL,
  status_pesanan  ENUM('menunggu','diproses','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu',
  waktu_pemesanan TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pemesanan_member (member_id),
  KEY idx_pemesanan_status (status_pesanan),
  KEY idx_pemesanan_waktu (waktu_pemesanan),
  CONSTRAINT fk_pemesanan_member FOREIGN KEY (member_id) REFERENCES member (member_id) ON DELETE CASCADE,
  CONSTRAINT fk_pemesanan_voucher FOREIGN KEY (voucher_id) REFERENCES voucher (voucher_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- detail_pesanan
-- ---------------------------------------------------------------------
CREATE TABLE detail_pesanan (
  detail_id      INT AUTO_INCREMENT PRIMARY KEY,
  menu_id        INT NOT NULL,
  pemesanan_id   INT NOT NULL,
  jumlah_pesanan INT NOT NULL,
  subtotal       DECIMAL(10,2) NOT NULL,
  KEY idx_detail_pemesanan (pemesanan_id),
  KEY idx_detail_menu (menu_id),
  CONSTRAINT fk_detail_menu FOREIGN KEY (menu_id) REFERENCES menu (menu_id) ON DELETE RESTRICT,
  CONSTRAINT fk_detail_pemesanan FOREIGN KEY (pemesanan_id) REFERENCES pemesanan (pemesanan_id) ON DELETE CASCADE,
  CONSTRAINT chk_jumlah_pesanan CHECK (jumlah_pesanan > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- klaim_review
-- ---------------------------------------------------------------------
CREATE TABLE klaim_review (
  klaim_id        INT AUTO_INCREMENT PRIMARY KEY,
  member_id       INT NOT NULL,
  voucher_id      INT NULL,
  pegawai_id      INT NULL,
  url_bukti       VARCHAR(500) NOT NULL,
  status_validasi ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_klaim_status (status_validasi),
  KEY idx_klaim_member (member_id),
  CONSTRAINT fk_klaim_member FOREIGN KEY (member_id) REFERENCES member (member_id) ON DELETE CASCADE,
  CONSTRAINT fk_klaim_voucher FOREIGN KEY (voucher_id) REFERENCES voucher (voucher_id) ON DELETE SET NULL,
  CONSTRAINT fk_klaim_pegawai FOREIGN KEY (pegawai_id) REFERENCES pegawai (pegawai_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- tiket
-- ---------------------------------------------------------------------
CREATE TABLE tiket (
  tiket_id         INT AUTO_INCREMENT PRIMARY KEY,
  pegawai_id       INT NULL,
  member_id        INT NOT NULL,
  kategori_keluhan ENUM('Kualitas Makanan','Pelayanan','Kebersihan','Lainnya') NOT NULL,
  isi_keluhan      TEXT NOT NULL,
  status           ENUM('open','in_progress','closed') NOT NULL DEFAULT 'open',
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at        TIMESTAMP NULL DEFAULT NULL,
  KEY idx_tiket_status (status),
  KEY idx_tiket_pegawai (pegawai_id),
  CONSTRAINT fk_tiket_pegawai FOREIGN KEY (pegawai_id) REFERENCES pegawai (pegawai_id) ON DELETE SET NULL,
  CONSTRAINT fk_tiket_member FOREIGN KEY (member_id) REFERENCES member (member_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- pengumuman (US-3.4 notifikasi in-app promo)
-- ---------------------------------------------------------------------
CREATE TABLE pengumuman (
  pengumuman_id INT AUTO_INCREMENT PRIMARY KEY,
  pegawai_id    INT NULL,
  judul         VARCHAR(150) NOT NULL,
  isi           TEXT NOT NULL,
  aktif         BOOLEAN NOT NULL DEFAULT TRUE,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pengumuman_aktif (aktif, created_at),
  CONSTRAINT fk_pengumuman_pegawai FOREIGN KEY (pegawai_id) REFERENCES pegawai (pegawai_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- notifikasi_promo (notifikasi promo terarah ke member inaktif 30 hari)
-- ---------------------------------------------------------------------
CREATE TABLE notifikasi_promo (
  notif_id     INT AUTO_INCREMENT PRIMARY KEY,
  member_id    INT NOT NULL,
  pegawai_id   INT NULL,
  voucher_id   INT NULL,
  kode_voucher VARCHAR(50) NULL,
  judul        VARCHAR(150) NOT NULL,
  pesan        TEXT NOT NULL,
  is_read      BOOLEAN NOT NULL DEFAULT FALSE,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notif_member (member_id, is_read, created_at),
  CONSTRAINT fk_notif_member FOREIGN KEY (member_id) REFERENCES member (member_id) ON DELETE CASCADE,
  CONSTRAINT fk_notif_pegawai FOREIGN KEY (pegawai_id) REFERENCES pegawai (pegawai_id) ON DELETE SET NULL,
  CONSTRAINT fk_notif_voucher FOREIGN KEY (voucher_id) REFERENCES voucher (voucher_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEED DATA
-- =====================================================================

-- Password: admin123 / kasir123 / pic123 / 123
INSERT INTO pegawai (email, password, nama, role, alamat, no_telp) VALUES
('admin@abbychicken.id',    '$2y$12$zBLG3.4OF7yBKBu4M.4ZZulrRG72Tw0IB0zE/jEeTEhJsUuDgBTFK', 'Ardi Gunawan',       'Admin',       'Jl. Ringroad Utara No. 12, Sleman', '081234567801'),
('kasir@abbychicken.id',    '$2y$12$ltHCfxiLYAvCuunpM0LdJeGY/SdIMjxU64gD65QcJB4.92xytSGCW', 'Miftahul Jannah',    'Kasir',       'Jl. Kaliurang Km 5, Sleman',        '081234567802'),
('pic@abbychicken.id',      '$2y$12$zmRR2OJTF4nNf31THwOjLOWBoDxC7tqJVkXVcoLfT2SsjlpDcweBO', 'Sultannang Nandito', 'PIC Keluhan', 'Jl. Magelang Km 7, Sleman',         '081234567803'),
('pimpinan@abbychicken.id', '$2y$10$PUEZSNNvrwy5fWcux2u75eP3uY.FTGa1EGw9IQDJ8p41QFun3L3TO', 'Jokowi Dodo',        'Pimpinan',    'Jl. Solo No. 1, Sleman',            '081234567804');

INSERT INTO member (email, password, no_telp, tgl_join, name, total_poin, member_status) VALUES
('wilda.ghoniyu@mail.com',    '123456', '081345678901', '2026-08-14', 'Wilda Ghoniyu Jannah',   340, 'aktif'),
('fatihah.nafis@mail.com',    '123456', '081345678902', '2026-08-22', 'Fatihah Nafis Azzahra',  120, 'aktif'),
('rangga.pratama@mail.com',   '123456', '081345678903', '2026-09-02', 'Rangga Pratama',          75, 'aktif'),
('sinta.maharani@mail.com',   '123456', '081345678904', '2026-09-11', 'Sinta Maharani',         210, 'aktif'),
('bayu.setiawan@mail.com',    '123456', '081345678905', '2026-09-19', 'Bayu Setiawan',           30, 'nonaktif'),
('dian.puspita@mail.com',     '123456', '081345678906', '2026-09-27', 'Dian Puspita Sari',       95, 'aktif'),
('hendra.wijaya@mail.com',    '123456', '081345678907', '2026-10-03', 'Hendra Wijaya',           15, 'aktif'),
('nadia.rahma@mail.com',      '123456', '081345678908', '2026-10-12', 'Nadia Rahma',             60, 'aktif'),
('yoga.saputra@mail.com',     '123456', '081345678909', '2026-10-21', 'Yoga Saputra',             0, 'aktif'),
('melati.ayu@mail.com',       '123456', '081345678910', '2026-10-30', 'Melati Ayu Lestari',      45, 'aktif');

INSERT INTO pengaturan (setting_key, setting_val) VALUES
('refill_nasi', 'ready')
ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val);

INSERT INTO menu (name, kategori, harga, status_ketersediaan) VALUES
('Nashville Hot Chicken',        'Makanan', 32000.00, 'tersedia'),
('Chicken Sandwich Original',    'Makanan', 28000.00, 'tersedia'),
('Crispy Chicken Rice Bowl',     'Makanan', 35000.00, 'tersedia'),
('Spicy Chicken Wings (6 pcs)',  'Makanan', 38000.00, 'tersedia'),
('Chicken Tenders (4 pcs)',      'Makanan', 26000.00, 'tidak tersedia'),
('Mac and Cheese',               'Snack',   22000.00, 'tersedia'),
('French Fries',                 'Snack',   18000.00, 'tersedia'),
('Coleslaw',                     'Snack',   12000.00, 'tersedia'),
('Onion Rings',                  'Snack',   20000.00, 'tersedia'),
('Es Teh Manis',                 'Minuman',  8000.00, 'tersedia'),
('Lemonade Segar',               'Minuman', 15000.00, 'tersedia'),
('Milkshake Vanilla',            'Minuman', 24000.00, 'tersedia'),
('Sweet Tea Bottle',             'Minuman', 10000.00, 'tidak tersedia');

INSERT INTO voucher (kode_voucher, is_valid, potongan_harga, valid_until, deleted_at) VALUES
('REVIEWHOT50',   TRUE,  50000.00, '2026-12-31', NULL),
('REVIEWGOOGLE25',TRUE,  25000.00, '2026-12-31', NULL),
('NASHVILLE10',   TRUE,  10000.00, '2026-11-30', NULL),
('PROMOOKTOBER',  TRUE,  15000.00, '2026-10-31', NULL),
('GRANDOPENING',  FALSE, 30000.00, '2026-09-30', '2026-09-25 10:00:00');

INSERT INTO pemesanan (member_id, voucher_id, nomor_antrian, total_diskon, jumlah_bayar, status_pesanan, waktu_pemesanan) VALUES
(1, 3,    1, 10000.00,  86000.00, 'selesai',    CONCAT(CURDATE(), ' 10:12:00')),
(2, NULL, 2,     0.00,  56000.00, 'selesai',    CONCAT(CURDATE(), ' 10:31:00')),
(4, NULL, 3,     0.00,  38000.00, 'selesai',    CONCAT(CURDATE(), ' 11:05:00')),
(1, NULL, 4,     0.00, 115000.00, 'diproses',   CONCAT(CURDATE(), ' 11:48:00')),
(7, NULL, 5,     0.00,  32000.00, 'menunggu',   CONCAT(CURDATE(), ' 12:02:00')),
(8, 3,    6, 10000.00,  58000.00, 'menunggu',   CONCAT(CURDATE(), ' 12:15:00')),
(3, NULL, 7,     0.00,  44000.00, 'dibatalkan', CONCAT(CURDATE(), ' 12:26:00')),
(10,NULL, 8,     0.00,  27000.00, 'diproses',   CONCAT(CURDATE(), ' 12:40:00')),
(4, NULL, 1,     0.00,  72000.00, 'selesai',    CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 11:20:00')),
(6, 3,    2, 10000.00,  45000.00, 'selesai',    CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 13:44:00')),
(2, NULL, 3,     0.00,  92000.00, 'selesai',    CONCAT(DATE_SUB(CURDATE(), INTERVAL 2 DAY), ' 18:02:00')),
(9, NULL, 1,     0.00,  35000.00, 'selesai',    CONCAT(DATE_SUB(CURDATE(), INTERVAL 3 DAY), ' 12:15:00')),
(1, NULL, 2,     0.00,  64000.00, 'selesai',    CONCAT(DATE_SUB(CURDATE(), INTERVAL 4 DAY), ' 19:30:00')),
(5, NULL, 1,     0.00,  28000.00, 'selesai',    CONCAT(DATE_SUB(CURDATE(), INTERVAL 6 DAY), ' 10:55:00'));

INSERT INTO detail_pesanan (menu_id, pemesanan_id, jumlah_pesanan, subtotal) VALUES
(1, 1, 2, 64000.00),
(2, 1, 1, 28000.00),
(4, 1, 1, 38000.00),
(1, 2, 2, 64000.00),
(7, 2, 1, 18000.00),
(4, 3, 1, 38000.00),
(1, 4, 3, 96000.00),
(6, 4, 1, 22000.00),
(2, 5, 1, 28000.00),
(9, 5, 1, 20000.00),
(1, 6, 2, 64000.00),
(7, 6, 1, 18000.00),
(3, 7, 1, 35000.00),
(8, 7, 1, 12000.00),
(7, 8, 1, 18000.00),
(10,8, 1,  8000.00),
(3, 9, 2, 70000.00),
(11,9, 1, 15000.00),
(1, 10, 1, 32000.00),
(6, 10, 1, 22000.00),
(1, 11, 2, 64000.00),
(11,11, 1, 15000.00),
(3, 12, 1, 35000.00),
(1, 13, 2, 64000.00),
(2, 14, 1, 28000.00);

INSERT INTO klaim_review (member_id, voucher_id, pegawai_id, url_bukti, status_validasi, created_at) VALUES
(1, 1, 1, 'https://maps.app.goo.gl/abbychicken-review-wilda', 'approved', CONCAT(DATE_SUB(CURDATE(), INTERVAL 2 DAY), ' 09:14:00')),
(4, 2, 1, 'https://maps.app.goo.gl/abbychicken-review-sinta', 'approved', CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 14:26:00')),
(2, NULL, NULL, 'https://maps.app.goo.gl/abbychicken-review-fatihah', 'pending', CONCAT(CURDATE(), ' 08:41:00')),
(6, NULL, NULL, 'https://maps.app.goo.gl/abbychicken-review-dian', 'pending', CONCAT(CURDATE(), ' 11:07:00')),
(9, NULL, 2, 'https://maps.app.goo.gl/abbychicken-review-yoga', 'rejected', CONCAT(DATE_SUB(CURDATE(), INTERVAL 3 DAY), ' 16:50:00'));

INSERT INTO tiket (pegawai_id, member_id, kategori_keluhan, isi_keluhan, status, created_at, closed_at) VALUES
(3, 3, 'Kualitas Makanan', 'Ayam yang saya terima sudah dingin dan teksturnya keras saat tiba di meja.', 'closed', CONCAT(DATE_SUB(CURDATE(), INTERVAL 5 DAY), ' 12:20:00'), CONCAT(DATE_SUB(CURDATE(), INTERVAL 4 DAY), ' 09:10:00')),
(3, 8, 'Pelayanan', 'Kasir tidak memberi tahu estimasi waktu tunggu, pesanan tertukar dengan pelanggan lain.', 'in_progress', CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 13:35:00'), NULL),
(3, 5, 'Kebersihan', 'Meja di area makan masih berminyak dan tisu tidak tersedia di dispenser.', 'in_progress', CONCAT(CURDATE(), ' 10:02:00'), NULL),
(NULL, 2, 'Lainnya', 'Saya ingin menanyakan apakah poin member bisa ditukar menjadi potongan harga.', 'open', CONCAT(CURDATE(), ' 09:30:00'), NULL),
(NULL, 10, 'Pelayanan', 'Pesanan online saya belum diproses padahal sudah menunggu lebih dari 30 menit.', 'open', CONCAT(CURDATE(), ' 12:45:00'), NULL);

INSERT INTO pengumuman (pegawai_id, judul, isi, aktif) VALUES
(1, 'Promo Nashville Combo November', 'Beli Nashville Hot Chicken + Es Teh Manis hanya Rp 35.000. Berlaku setiap Senin sampai Kamis selama November 2026.', TRUE),
(1, 'Poin Dobel untuk Member Aktif', 'Setiap transaksi di atas Rp 50.000 mendapat poin dobel sepanjang akhir pekan ini.', TRUE),
(1, 'Menu Baru: Mac and Cheese', 'Mac and Cheese sudah tersedia di semua outlet. Coba sebagai pendamping ayam pedas.', FALSE);
