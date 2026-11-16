-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: abbychicken_crm
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `detail_pesanan`
--

DROP TABLE IF EXISTS `detail_pesanan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detail_pesanan` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) NOT NULL,
  `pemesanan_id` int(11) NOT NULL,
  `jumlah_pesanan` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `catatan` varchar(100) DEFAULT '',
  PRIMARY KEY (`detail_id`),
  KEY `idx_detail_pemesanan` (`pemesanan_id`),
  KEY `idx_detail_menu` (`menu_id`),
  CONSTRAINT `fk_detail_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`menu_id`),
  CONSTRAINT `fk_detail_pemesanan` FOREIGN KEY (`pemesanan_id`) REFERENCES `pemesanan` (`pemesanan_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_jumlah_pesanan` CHECK (`jumlah_pesanan` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_pesanan`
--

LOCK TABLES `detail_pesanan` WRITE;
/*!40000 ALTER TABLE `detail_pesanan` DISABLE KEYS */;
INSERT INTO `detail_pesanan` VALUES (1,1,1,2,64000.00),(2,2,1,1,28000.00),(3,4,1,1,38000.00),(4,1,2,2,64000.00),(5,7,2,1,18000.00),(6,4,3,1,38000.00),(7,1,4,3,96000.00),(8,6,4,1,22000.00),(9,2,5,1,28000.00),(10,9,5,1,20000.00),(11,1,6,2,64000.00),(12,7,6,1,18000.00),(13,3,7,1,35000.00),(14,8,7,1,12000.00),(15,7,8,1,18000.00),(16,10,8,1,8000.00),(17,3,9,2,70000.00),(18,11,9,1,15000.00),(19,1,10,1,32000.00),(20,6,10,1,22000.00),(21,1,11,2,64000.00),(22,11,11,1,15000.00),(23,3,12,1,35000.00),(24,1,13,2,64000.00),(25,2,14,1,28000.00),(26,2,15,16,44800000.00),(27,3,16,4,140000.00),(28,3,17,11,385000.00),(31,3,20,1,35000.00),(32,3,21,1,35000.00),(33,1,21,1,32000.00),(34,4,21,1,38000.00);
/*!40000 ALTER TABLE `detail_pesanan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `klaim_review`
--

DROP TABLE IF EXISTS `klaim_review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `klaim_review` (
  `klaim_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `voucher_id` int(11) DEFAULT NULL,
  `pegawai_id` int(11) DEFAULT NULL,
  `url_bukti` varchar(500) NOT NULL,
  `status_validasi` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`klaim_id`),
  KEY `idx_klaim_status` (`status_validasi`),
  KEY `idx_klaim_member` (`member_id`),
  KEY `fk_klaim_voucher` (`voucher_id`),
  KEY `fk_klaim_pegawai` (`pegawai_id`),
  CONSTRAINT `fk_klaim_member` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_klaim_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_klaim_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `voucher` (`voucher_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `klaim_review`
--

LOCK TABLES `klaim_review` WRITE;
/*!40000 ALTER TABLE `klaim_review` DISABLE KEYS */;
INSERT INTO `klaim_review` VALUES (1,1,1,1,'https://maps.app.goo.gl/abbychicken-review-wilda','approved','2026-11-02 02:14:00'),(2,4,2,1,'https://maps.app.goo.gl/abbychicken-review-sinta','approved','2026-11-03 07:26:00'),(3,2,1,2,'https://maps.app.goo.gl/abbychicken-review-fatihah','approved','2026-11-04 01:41:00'),(4,6,NULL,NULL,'https://maps.app.goo.gl/abbychicken-review-dian','pending','2026-11-04 04:07:00'),(5,9,NULL,2,'https://maps.app.goo.gl/abbychicken-review-yoga','rejected','2026-11-01 09:50:00'),(6,1,1,1,'https://maps.app.goo.gl/AbbyNashvilleWilda_1793810550751','approved','2026-11-04 16:42:30'),(7,1,2,1,'https://www.tiktok.com/@denwaerr/video/7634914520602905863?q=SAYA%20AKAN%20LAWAN&t=1793810691140','approved','2026-11-04 16:45:16'),(8,1,NULL,NULL,'https://hehehe','pending','2026-11-05 17:58:57'),(9,11,NULL,NULL,'https://WIWOKDETOK','pending','2026-11-10 15:35:04');
/*!40000 ALTER TABLE `klaim_review` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member`
--

DROP TABLE IF EXISTS `member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL DEFAULT '123456',
  `no_telp` varchar(15) NOT NULL,
  `tgl_join` date NOT NULL,
  `name` varchar(100) NOT NULL,
  `total_poin` int(11) NOT NULL DEFAULT 0,
  `member_status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `uq_member_email` (`email`),
  KEY `idx_member_nama` (`name`),
  KEY `idx_member_status` (`member_status`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member`
--

LOCK TABLES `member` WRITE;
/*!40000 ALTER TABLE `member` DISABLE KEYS */;
INSERT INTO `member` VALUES (1,'wilda.ghoniyu@mail.com','123456','081345678901','2026-08-14','Wilda Ghoniyu Jannah',9387,'aktif'),(2,'fatihah.nafis@mail.com','123456','081345678902','2026-08-22','Fatihah Nafis Azzahra',120,'aktif'),(3,'rangga.pratama@mail.com','123456','081345678903','2026-09-02','Rangga Pratama',78,'aktif'),(4,'sinta.maharani@mail.com','123456','081345678904','2026-09-11','Sinta Maharani',210,'aktif'),(5,'bayu.setiawan@mail.com','123456','081345678905','2026-09-19','Bayu Setiawan',30,'nonaktif'),(6,'dian.puspita@mail.com','123456','081345678906','2026-09-27','Dian Puspita Sari',95,'aktif'),(7,'hendra.wijaya@mail.com','123456','081345678907','2026-10-03','Hendra Wijaya',18,'aktif'),(8,'nadia.rahma@mail.com','123456','081345678908','2026-10-12','Nadia Rahma',60,'aktif'),(9,'yoga.saputra@mail.com','123456','081345678909','2026-10-21','Yoga Saputra',0,'aktif'),(10,'melati.ayu@mail.com','123456','081345678910','2026-10-30','Melati Ayu Lestari',45,'aktif'),(11,'prawowo@gmail.com','123456','089182716252526','2026-11-10','Prawowo',19000010,'aktif'),(12,'rizky.fauzi@mail.com','123456','081298765431','2026-09-15','Rizky Fauzi',0,'aktif'),(13,'anisa.rahmawati@mail.com','123456','085712345671','2026-09-18','Anisa Rahmawati',0,'aktif'),(14,'dimas.pratama@mail.com','123456','082134567891','2026-09-22','Dimas Pratama',0,'aktif'),(15,'tiara.andini@mail.com','123456','081387654321','2026-09-28','Tiara Andini',0,'aktif'),(16,'bagas.kurniawan@mail.com','123456','085876543210','2026-10-01','Bagas Kurniawan',0,'aktif'),(17,'citra.dewi@mail.com','123456','082211223344','2026-10-05','Citra Dewi Anggraini',0,'aktif'),(18,'farhan.maulana@mail.com','123456','081233445566','2026-10-08','Farhan Maulana',0,'aktif'),(19,'gita.savitri@mail.com','123456','085699887766','2026-10-11','Gita Savitri',0,'aktif'),(20,'ilham.hidayat@mail.com','123456','081377889900','2026-10-13','Ilham Hidayat',0,'aktif'),(21,'jessica.putri@mail.com','123456','082155667788','2026-10-14','Jessica Putri',0,'aktif'),(22,'ujiauditwa@example.com','123456','081234567890','2026-11-14','Uji Audit WA',0,'aktif');
/*!40000 ALTER TABLE `member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu`
--

DROP TABLE IF EXISTS `menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `status_ketersediaan` enum('tersedia','tidak tersedia') NOT NULL DEFAULT 'tersedia',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`menu_id`),
  KEY `idx_menu_kategori` (`kategori`),
  KEY `idx_menu_status` (`status_ketersediaan`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu`
--

LOCK TABLES `menu` WRITE;
/*!40000 ALTER TABLE `menu` DISABLE KEYS */;
INSERT INTO `menu` VALUES (1,'Nashville Hot Chicken','Makanan',32000.00,'tersedia','2026-11-04 16:13:44'),(2,'Chicken Sandwich Original','Makanan',2800000.00,'tersedia','2026-11-04 16:14:03'),(3,'Crispy Chicken Rice Bowl','Makanan',35000.00,'tersedia','2026-11-04 16:13:44'),(4,'Spicy Chicken Wings (6 pcs)','Makanan',38000.00,'tersedia','2026-11-04 16:13:44'),(5,'Chicken Tenders (4 pcs)','Makanan',26000.00,'tidak tersedia','2026-11-04 16:13:44'),(6,'Mac and Cheese','Snack',22000.00,'tersedia','2026-11-04 16:13:44'),(7,'French Fries','Snack',18000.00,'tersedia','2026-11-04 16:13:44'),(8,'Coleslaw','Snack',12000.00,'tersedia','2026-11-04 16:13:44'),(9,'Onion Rings','Snack',20000.00,'tersedia','2026-11-04 16:13:44'),(10,'Es Teh Manis','Minuman',8000.00,'tersedia','2026-11-04 16:13:44'),(11,'Lemonade Segar','Minuman',15000.00,'tersedia','2026-11-04 16:13:44'),(12,'Milkshake Vanilla','Minuman',24000.00,'tersedia','2026-11-04 16:13:44'),(13,'Sweet Tea Bottle','Minuman',10000.00,'tidak tersedia','2026-11-04 16:13:44');
/*!40000 ALTER TABLE `menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifikasi_promo`
--

DROP TABLE IF EXISTS `notifikasi_promo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifikasi_promo` (
  `notif_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `pegawai_id` int(11) DEFAULT NULL,
  `voucher_id` int(11) DEFAULT NULL,
  `kode_voucher` varchar(50) DEFAULT NULL,
  `judul` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notif_id`),
  KEY `idx_notif_member` (`member_id`,`is_read`,`created_at`),
  KEY `fk_notif_pegawai` (`pegawai_id`),
  KEY `fk_notif_voucher` (`voucher_id`),
  CONSTRAINT `fk_notif_member` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_notif_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `voucher` (`voucher_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifikasi_promo`
--

LOCK TABLES `notifikasi_promo` WRITE;
/*!40000 ALTER TABLE `notifikasi_promo` DISABLE KEYS */;
INSERT INTO `notifikasi_promo` VALUES (1,3,1,2,'REVIEWGOOGLE25','heii antek antek aseng','Sudah lama tidak pesan di AbbyChicken Nashville! Yuk nikmati menu favoritmu lagi dengan promo spesial khusus untukmu.',1,'2026-11-10 12:41:48'),(3,3,1,6,'ANTEKASENG','hooooooo','hohohohoho',0,'2026-11-10 13:10:32'),(4,3,1,2,'REVIEWGOOGLE25','Promo Spesial Kangen Nashville!','Yuk pesan lagi paket Nashville Hot favoritmu hari ini!',0,'2026-11-14 14:40:14'),(5,3,1,2,'REVIEWGOOGLE25','Promo Spesial Kangen Nashville!','Yuk pesan lagi paket Nashville Hot favoritmu hari ini!',0,'2026-11-14 14:41:24');
/*!40000 ALTER TABLE `notifikasi_promo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pegawai`
--

DROP TABLE IF EXISTS `pegawai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pegawai` (
  `pegawai_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `role` enum('Admin','Kasir','PIC Keluhan','Pimpinan') NOT NULL,
  `alamat` text DEFAULT NULL,
  `no_telp` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pegawai_id`),
  UNIQUE KEY `uq_pegawai_email` (`email`),
  KEY `idx_pegawai_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pegawai`
--

LOCK TABLES `pegawai` WRITE;
/*!40000 ALTER TABLE `pegawai` DISABLE KEYS */;
INSERT INTO `pegawai` VALUES (1,'admin@abbychicken.id','$2y$12$zBLG3.4OF7yBKBu4M.4ZZulrRG72Tw0IB0zE/jEeTEhJsUuDgBTFK','Ardi Gunawan','Admin','Jl. Ringroad Utara No. 12, Sleman','081234567801','2026-11-04 16:13:44'),(2,'kasir@abbychicken.id','$2y$12$ltHCfxiLYAvCuunpM0LdJeGY/SdIMjxU64gD65QcJB4.92xytSGCW','Miftahul Jannah','Kasir','Jl. Kaliurang Km 5, Sleman','081234567802','2026-11-04 16:13:44'),(3,'pic@abbychicken.id','$2y$12$zmRR2OJTF4nNf31THwOjLOWBoDxC7tqJVkXVcoLfT2SsjlpDcweBO','Sultannang Nandito','PIC Keluhan','Jl. Magelang Km 7, Sleman','081234567803','2026-11-04 16:13:44'),(4,'pimpinan@abbychicken.id','$2y$10$PUEZSNNvrwy5fWcux2u75eP3uY.FTGa1EGw9IQDJ8p41QFun3L3TO','Jokowi Dodo','Pimpinan','Jl. Solo No. 1, Sleman','081234567804','2026-11-10 12:40:53');
/*!40000 ALTER TABLE `pegawai` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pemesanan`
--

DROP TABLE IF EXISTS `pemesanan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pemesanan` (
  `pemesanan_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `voucher_id` int(11) DEFAULT NULL,
  `nomor_antrian` int(11) NOT NULL,
  `total_diskon` decimal(10,2) NOT NULL DEFAULT 0.00,
  `jumlah_bayar` decimal(10,2) NOT NULL,
  `status_pesanan` enum('menunggu','diproses','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu',
  `waktu_pemesanan` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pemesanan_id`),
  KEY `idx_pemesanan_member` (`member_id`),
  KEY `idx_pemesanan_status` (`status_pesanan`),
  KEY `idx_pemesanan_waktu` (`waktu_pemesanan`),
  KEY `fk_pemesanan_voucher` (`voucher_id`),
  CONSTRAINT `fk_pemesanan_member` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pemesanan_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `voucher` (`voucher_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pemesanan`
--

LOCK TABLES `pemesanan` WRITE;
/*!40000 ALTER TABLE `pemesanan` DISABLE KEYS */;
INSERT INTO `pemesanan` VALUES (1,1,3,1,10000.00,86000.00,'selesai','2026-11-04 03:12:00'),(2,2,NULL,2,0.00,56000.00,'selesai','2026-11-04 03:31:00'),(3,4,NULL,3,0.00,38000.00,'selesai','2026-11-04 04:05:00'),(4,1,NULL,4,0.00,115000.00,'diproses','2026-11-04 04:48:00'),(5,7,NULL,5,0.00,32000.00,'selesai','2026-11-04 05:02:00'),(6,8,3,6,10000.00,58000.00,'menunggu','2026-11-04 05:15:00'),(7,3,NULL,7,0.00,44000.00,'dibatalkan','2026-11-04 05:26:00'),(8,10,NULL,8,0.00,27000.00,'diproses','2026-11-04 05:40:00'),(9,4,NULL,1,0.00,72000.00,'selesai','2026-11-03 04:20:00'),(10,6,3,2,10000.00,45000.00,'selesai','2026-11-03 06:44:00'),(11,2,NULL,3,0.00,92000.00,'selesai','2026-11-02 11:02:00'),(12,9,NULL,1,0.00,35000.00,'selesai','2026-11-01 05:15:00'),(13,1,NULL,2,0.00,64000.00,'selesai','2026-10-31 12:30:00'),(14,5,NULL,1,0.00,28000.00,'selesai','2026-10-29 03:55:00'),(15,1,NULL,9,0.00,44800000.00,'selesai','2026-11-04 16:16:18'),(16,1,2,10,25000.00,115000.00,'menunggu','2026-11-04 16:47:07'),(17,1,NULL,1,0.00,385000.00,'selesai','2026-11-05 17:40:09'),(20,3,6,1,2999.00,32001.00,'menunggu','2026-11-10 13:12:14'),(21,11,6,2,2999.00,102001.00,'selesai','2026-11-10 15:33:45');
/*!40000 ALTER TABLE `pemesanan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengaturan`
--

DROP TABLE IF EXISTS `pengaturan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pengaturan` (
  `setting_key` varchar(50) NOT NULL,
  `setting_val` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengaturan`
--

LOCK TABLES `pengaturan` WRITE;
/*!40000 ALTER TABLE `pengaturan` DISABLE KEYS */;
INSERT INTO `pengaturan` VALUES ('refill_nasi','ready','2026-11-16 19:30:00'),('refill_nasi_timer','','2026-11-16 19:30:00');
/*!40000 ALTER TABLE `pengaturan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengumuman`
--

DROP TABLE IF EXISTS `pengumuman`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pengumuman` (
  `pengumuman_id` int(11) NOT NULL AUTO_INCREMENT,
  `pegawai_id` int(11) DEFAULT NULL,
  `judul` varchar(150) NOT NULL,
  `isi` text NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pengumuman_id`),
  KEY `idx_pengumuman_aktif` (`aktif`,`created_at`),
  KEY `fk_pengumuman_pegawai` (`pegawai_id`),
  CONSTRAINT `fk_pengumuman_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengumuman`
--

LOCK TABLES `pengumuman` WRITE;
/*!40000 ALTER TABLE `pengumuman` DISABLE KEYS */;
INSERT INTO `pengumuman` VALUES (1,1,'Promo Nashville Combo November','Beli Nashville Hot Chicken + Es Teh Manis hanya Rp 35.000. Berlaku setiap Senin sampai Kamis selama November 2026.',1,'2026-11-04 16:13:44'),(2,1,'Poin Dobel untuk Member Aktif','Setiap transaksi di atas Rp 50.000 mendapat poin dobel sepanjang akhir pekan ini.',1,'2026-11-04 16:13:44'),(3,1,'Menu Baru: Mac and Cheese','Mac and Cheese sudah tersedia di semua outlet. Coba sebagai pendamping ayam pedas.',0,'2026-11-04 16:13:44');
/*!40000 ALTER TABLE `pengumuman` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tiket`
--

DROP TABLE IF EXISTS `tiket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tiket` (
  `tiket_id` int(11) NOT NULL AUTO_INCREMENT,
  `pegawai_id` int(11) DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `kategori_keluhan` enum('Kualitas Makanan','Pelayanan','Kebersihan','Lainnya') NOT NULL,
  `isi_keluhan` text NOT NULL,
  `status` enum('open','in_progress','closed') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`tiket_id`),
  KEY `idx_tiket_status` (`status`),
  KEY `idx_tiket_pegawai` (`pegawai_id`),
  KEY `fk_tiket_member` (`member_id`),
  CONSTRAINT `fk_tiket_member` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tiket_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tiket`
--

LOCK TABLES `tiket` WRITE;
/*!40000 ALTER TABLE `tiket` DISABLE KEYS */;
INSERT INTO `tiket` VALUES (1,3,3,'Kualitas Makanan','Ayam yang saya terima sudah dingin dan teksturnya keras saat tiba di meja.','closed','2026-10-30 05:20:00','2026-10-31 02:10:00'),(2,3,8,'Pelayanan','Kasir tidak memberi tahu estimasi waktu tunggu, pesanan tertukar dengan pelanggan lain.','in_progress','2026-11-03 06:35:00',NULL),(3,3,5,'Kebersihan','Meja di area makan masih berminyak dan tisu tidak tersedia di dispenser.','in_progress','2026-11-04 03:02:00',NULL),(4,3,2,'Lainnya','Saya ingin menanyakan apakah poin member bisa ditukar menjadi potongan harga.','closed','2026-11-04 02:30:00','2026-11-10 15:24:36'),(5,NULL,10,'Pelayanan','Pesanan online saya belum diproses padahal sudah menunggu lebih dari 30 menit.','open','2026-11-04 05:45:00',NULL),(6,3,1,'Kualitas Makanan','Ayam goreng sangat renyah, tapi saus nashville agak kurang pedas dibandingkan biasanya. Mohon dicek bumbunya.','in_progress','2026-11-04 16:26:15',NULL),(7,3,1,'Kualitas Makanan','WE WOK DE TOK','closed','2026-11-04 16:30:09','2026-11-04 16:32:30');
/*!40000 ALTER TABLE `tiket` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voucher`
--

DROP TABLE IF EXISTS `voucher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voucher` (
  `voucher_id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_voucher` varchar(50) NOT NULL,
  `is_valid` tinyint(1) NOT NULL DEFAULT 1,
  `potongan_harga` decimal(10,2) NOT NULL,
  `valid_until` date NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`voucher_id`),
  UNIQUE KEY `uq_voucher_kode` (`kode_voucher`),
  KEY `idx_voucher_valid` (`is_valid`,`valid_until`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voucher`
--

LOCK TABLES `voucher` WRITE;
/*!40000 ALTER TABLE `voucher` DISABLE KEYS */;
INSERT INTO `voucher` VALUES (1,'REVIEWHOT50',1,50000.00,'2026-12-31',NULL,'2026-11-04 16:13:44'),(2,'REVIEWGOOGLE25',1,25000.00,'2026-12-31',NULL,'2026-11-04 16:13:44'),(3,'NASHVILLE10',1,10000.00,'2026-11-30',NULL,'2026-11-04 16:13:44'),(4,'PROMOOKTOBER',1,15000.00,'2026-10-31',NULL,'2026-11-04 16:13:44'),(5,'GRANDOPENING',0,30000.00,'2026-09-30','2026-09-25 03:00:00','2026-11-04 16:13:44'),(6,'ANTEKASENG',1,2999.00,'2026-11-27',NULL,'2026-11-10 12:51:25');
/*!40000 ALTER TABLE `voucher` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'abbychicken_crm'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-14 22:06:13
