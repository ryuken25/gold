-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: mahengold_demo
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bukti_pembayaran`
--

DROP TABLE IF EXISTS `bukti_pembayaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bukti_pembayaran` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) DEFAULT NULL,
  `tipe` enum('cash','cicilan') NOT NULL,
  `pengajuan_id` int(10) unsigned DEFAULT NULL,
  `kredit_id` int(10) unsigned DEFAULT NULL,
  `jadwal_angsuran_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `nominal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `nama_pengirim` varchar(150) DEFAULT NULL,
  `no_rekening` varchar(50) DEFAULT NULL,
  `bank_pengirim` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `status` enum('menunggu','terverifikasi','ditolak') NOT NULL DEFAULT 'menunggu',
  `catatan_admin` text DEFAULT NULL,
  `diverifikasi_oleh` int(10) unsigned DEFAULT NULL,
  `diverifikasi_pada` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`),
  KEY `bukti_pembayaran_pengajuan_id_foreign` (`pengajuan_id`),
  KEY `bukti_pembayaran_kredit_id_foreign` (`kredit_id`),
  KEY `bukti_pembayaran_jadwal_angsuran_id_foreign` (`jadwal_angsuran_id`),
  KEY `bukti_pembayaran_user_id_foreign` (`user_id`),
  KEY `bukti_pembayaran_diverifikasi_oleh_foreign` (`diverifikasi_oleh`),
  KEY `status` (`status`),
  KEY `tipe` (`tipe`),
  CONSTRAINT `bukti_pembayaran_diverifikasi_oleh_foreign` FOREIGN KEY (`diverifikasi_oleh`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  CONSTRAINT `bukti_pembayaran_jadwal_angsuran_id_foreign` FOREIGN KEY (`jadwal_angsuran_id`) REFERENCES `jadwal_angsuran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `bukti_pembayaran_kredit_id_foreign` FOREIGN KEY (`kredit_id`) REFERENCES `kredit` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `bukti_pembayaran_pengajuan_id_foreign` FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `bukti_pembayaran_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bukti_pembayaran`
--

LOCK TABLES `bukti_pembayaran` WRITE;
/*!40000 ALTER TABLE `bukti_pembayaran` DISABLE KEYS */;
/*!40000 ALTER TABLE `bukti_pembayaran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipe` varchar(40) NOT NULL COMMENT 'pesanan_dibuat | pesanan_diverifikasi | reminder_sesi',
  `tujuan_email` varchar(190) NOT NULL,
  `nama_tujuan` varchar(150) DEFAULT NULL,
  `subjek` varchar(190) DEFAULT NULL,
  `body` mediumtext DEFAULT NULL,
  `status` enum('terkirim','gagal') NOT NULL DEFAULT 'terkirim',
  `error` text DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `related_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `related_type_related_id` (`related_type`,`related_id`),
  KEY `tipe` (`tipe`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jadwal_angsuran`
--

DROP TABLE IF EXISTS `jadwal_angsuran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jadwal_angsuran` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kredit_id` int(10) unsigned NOT NULL,
  `angsuran_ke` int(11) NOT NULL,
  `tanggal_jatuh_tempo` date NOT NULL,
  `nominal_tagihan` decimal(15,2) NOT NULL,
  `nominal_dibayar` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('belum_dibayar','sebagian','dibayar','terlambat') NOT NULL DEFAULT 'belum_dibayar',
  `tanggal_dibayar` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jadwal_angsuran_kredit_id_foreign` (`kredit_id`),
  CONSTRAINT `jadwal_angsuran_kredit_id_foreign` FOREIGN KEY (`kredit_id`) REFERENCES `kredit` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jadwal_angsuran`
--

LOCK TABLES `jadwal_angsuran` WRITE;
/*!40000 ALTER TABLE `jadwal_angsuran` DISABLE KEYS */;
INSERT INTO `jadwal_angsuran` (`id`, `kredit_id`, `angsuran_ke`, `tanggal_jatuh_tempo`, `nominal_tagihan`, `nominal_dibayar`, `status`, `tanggal_dibayar`, `created_at`, `updated_at`) VALUES (1,1,1,'2026-05-03',137500.00,137500.00,'dibayar','2026-05-03','2026-06-12 21:28:21','2026-06-12 21:28:21'),(2,1,2,'2026-06-03',137500.00,137500.00,'dibayar','2026-06-03','2026-06-12 21:28:21','2026-06-12 21:28:21'),(3,1,3,'2026-07-03',137500.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(4,1,4,'2026-08-03',137500.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(5,1,5,'2026-09-03',137500.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(6,1,6,'2026-10-03',137500.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(7,1,7,'2026-11-03',137500.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(8,1,8,'2026-12-03',137500.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(9,1,9,'2027-01-03',137500.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(10,1,10,'2027-02-03',137500.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(11,1,11,'2027-03-03',137500.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(12,1,12,'2027-04-03',137500.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(13,2,1,'2026-05-22',88000.00,88000.00,'dibayar','2026-05-22','2026-06-12 21:28:21','2026-06-12 21:28:21'),(14,2,2,'2026-05-29',88000.00,88000.00,'dibayar','2026-05-29','2026-06-12 21:28:21','2026-06-12 21:28:21'),(15,2,3,'2026-06-05',88000.00,88000.00,'dibayar','2026-06-05','2026-06-12 21:28:21','2026-06-12 21:28:21'),(16,2,4,'2026-06-12',88000.00,88000.00,'dibayar','2026-06-12','2026-06-12 21:28:21','2026-06-12 21:28:21'),(17,2,5,'2026-06-19',88000.00,88000.00,'dibayar','2026-06-19','2026-06-12 21:28:21','2026-06-12 21:28:21'),(18,2,6,'2026-06-26',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(19,2,7,'2026-07-03',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(20,2,8,'2026-07-10',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(21,2,9,'2026-07-17',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(22,2,10,'2026-07-24',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(23,2,11,'2026-07-31',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(24,2,12,'2026-08-07',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(25,2,13,'2026-08-14',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(26,2,14,'2026-08-21',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(27,2,15,'2026-08-28',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(28,2,16,'2026-09-04',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(29,2,17,'2026-09-11',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(30,2,18,'2026-09-18',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(31,2,19,'2026-09-25',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(32,2,20,'2026-10-02',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(33,2,21,'2026-10-09',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(34,2,22,'2026-10-16',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(35,2,23,'2026-10-23',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(36,2,24,'2026-10-30',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(37,2,25,'2026-11-06',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(38,2,26,'2026-11-13',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(39,2,27,'2026-11-20',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(40,2,28,'2026-11-27',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(41,2,29,'2026-12-04',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(42,2,30,'2026-12-11',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(43,2,31,'2026-12-18',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(44,2,32,'2026-12-25',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(45,2,33,'2027-01-01',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(46,2,34,'2027-01-08',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(47,2,35,'2027-01-15',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(48,2,36,'2027-01-22',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(49,2,37,'2027-01-29',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(50,2,38,'2027-02-05',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(51,2,39,'2027-02-12',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(52,2,40,'2027-02-19',88000.00,0.00,'belum_dibayar',NULL,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(53,3,1,'2025-12-14',229167.00,229167.00,'dibayar','2025-12-14','2026-06-12 21:28:21','2026-06-12 21:28:21'),(54,3,2,'2026-01-14',229167.00,229167.00,'dibayar','2026-01-14','2026-06-12 21:28:21','2026-06-12 21:28:21'),(55,3,3,'2026-02-14',229167.00,229167.00,'dibayar','2026-02-14','2026-06-12 21:28:21','2026-06-12 21:28:21'),(56,3,4,'2026-03-14',229167.00,229167.00,'dibayar','2026-03-14','2026-06-12 21:28:21','2026-06-12 21:28:21'),(57,3,5,'2026-04-14',229167.00,229167.00,'dibayar','2026-04-14','2026-06-12 21:28:21','2026-06-12 21:28:21'),(58,3,6,'2026-05-14',229165.00,229165.00,'dibayar','2026-05-14','2026-06-12 21:28:21','2026-06-12 21:28:21');
/*!40000 ALTER TABLE `jadwal_angsuran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kredit`
--

DROP TABLE IF EXISTS `kredit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kredit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pengajuan_id` int(10) unsigned DEFAULT NULL,
  `kode_kredit` varchar(50) NOT NULL,
  `nasabah_id` int(10) unsigned NOT NULL,
  `produk_emas_id` int(10) unsigned NOT NULL,
  `tanggal_kredit` date NOT NULL,
  `harga_pokok_snapshot` decimal(15,2) NOT NULL,
  `margin_persen` decimal(5,2) NOT NULL DEFAULT 10.00,
  `margin_nominal` decimal(15,2) NOT NULL,
  `total_harga_kredit` decimal(15,2) NOT NULL,
  `uang_muka` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sisa_pokok_kredit` decimal(15,2) DEFAULT NULL,
  `tenor_bulan` int(11) NOT NULL,
  `periode_angsuran` enum('bulanan','mingguan') NOT NULL DEFAULT 'bulanan',
  `jumlah_periode` int(11) NOT NULL,
  `nominal_angsuran` decimal(15,2) NOT NULL,
  `total_terbayar` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sisa_piutang` decimal(15,2) NOT NULL,
  `status` enum('aktif','lunas','dibatalkan') NOT NULL DEFAULT 'aktif',
  `catatan` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_kredit` (`kode_kredit`),
  KEY `kredit_nasabah_id_foreign` (`nasabah_id`),
  KEY `kredit_produk_emas_id_foreign` (`produk_emas_id`),
  KEY `fk_kredit_pengajuan` (`pengajuan_id`),
  CONSTRAINT `fk_kredit_pengajuan` FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `kredit_nasabah_id_foreign` FOREIGN KEY (`nasabah_id`) REFERENCES `nasabah` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `kredit_produk_emas_id_foreign` FOREIGN KEY (`produk_emas_id`) REFERENCES `produk_emas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kredit`
--

LOCK TABLES `kredit` WRITE;
/*!40000 ALTER TABLE `kredit` DISABLE KEYS */;
INSERT INTO `kredit` (`id`, `pengajuan_id`, `kode_kredit`, `nasabah_id`, `produk_emas_id`, `tanggal_kredit`, `harga_pokok_snapshot`, `margin_persen`, `margin_nominal`, `total_harga_kredit`, `uang_muka`, `sisa_pokok_kredit`, `tenor_bulan`, `periode_angsuran`, `jumlah_periode`, `nominal_angsuran`, `total_terbayar`, `sisa_piutang`, `status`, `catatan`, `created_at`, `updated_at`) VALUES (1,NULL,'KRD-0001',1,1,'2026-04-03',1500000.00,10.00,150000.00,1650000.00,0.00,NULL,12,'bulanan',12,137500.00,275000.00,1375000.00,'aktif','Data transaksi kredit dummy untuk demo.','2026-06-12 21:28:21','2026-06-12 21:28:21'),(2,NULL,'KRD-0002',2,2,'2026-05-08',3200000.00,10.00,320000.00,3520000.00,0.00,NULL,10,'mingguan',40,88000.00,440000.00,3080000.00,'aktif','Data transaksi kredit dummy untuk demo.','2026-06-12 21:28:21','2026-06-12 21:28:21'),(3,NULL,'KRD-0003',3,3,'2025-11-14',1250000.00,10.00,125000.00,1375000.00,0.00,NULL,6,'bulanan',6,229167.00,1375000.00,0.00,'lunas','Data transaksi kredit dummy untuk demo.','2026-06-12 21:28:21','2026-06-12 21:28:21');
/*!40000 ALTER TABLE `kredit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES (17,'2026-04-26-080000','App\\Database\\Migrations\\CreateUsersTable','default','App',1777207161,1),(18,'2026-04-26-080100','App\\Database\\Migrations\\CreateProdukEmasTable','default','App',1777207161,1),(19,'2026-04-26-080200','App\\Database\\Migrations\\CreateNasabahTable','default','App',1777207161,1),(20,'2026-04-26-080300','App\\Database\\Migrations\\CreateKreditTable','default','App',1777207162,1),(21,'2026-04-26-080400','App\\Database\\Migrations\\CreateJadwalAngsuranTable','default','App',1777207162,1),(22,'2026-04-26-080500','App\\Database\\Migrations\\CreatePembayaranAngsuranTable','default','App',1777207162,1),(23,'2026-04-26-080600','App\\Database\\Migrations\\CreateWhatsappLogsTable','default','App',1777207162,1),(24,'2026-04-26-080700','App\\Database\\Migrations\\CreatePengaturanSistemTable','default','App',1777207162,1),(25,'2026-05-29-000001','App\\Database\\Migrations\\AlterUsersAddPelangganRole','default','App',1780036641,2),(26,'2026-05-29-000002','App\\Database\\Migrations\\CreatePengajuanTable','default','App',1780036641,2),(27,'2026-06-02-000001','App\\Database\\Migrations\\AddUserIdToNasabahTable','default','App',1780563383,3),(28,'2026-06-04-000001','App\\Database\\Migrations\\MakeUsernameNullable','default','App',1780563383,3),(29,'2026-06-04-100001','App\\Database\\Migrations\\AddPesananFieldsToPengajuan','default','App',1780567671,4),(30,'2026-06-04-100002','App\\Database\\Migrations\\AlterWhatsappLogsTipeAddKonfirmasiPesanan','default','App',1780567671,4),(31,'2026-06-04-100003','App\\Database\\Migrations\\CreatePengajuanAktivitasTable','default','App',1780567671,4),(32,'2026-06-04-100004','App\\Database\\Migrations\\CreateEmailLogsTable','default','App',1780567671,4),(33,'2026-06-04-110001','App\\Database\\Migrations\\AddPembayaranStatusToPengajuan','default','App',1780583383,5),(34,'2026-06-04-110002','App\\Database\\Migrations\\AddPengajuanIdToKredit','default','App',1780583383,5),(35,'2026-06-04-110003','App\\Database\\Migrations\\CreateBuktiPembayaranTable','default','App',1780583383,5),(36,'2026-06-04-110004','App\\Database\\Migrations\\DropWaktuSesiFromPengajuan','default','App',1780583383,5),(37,'2026-06-09-100001','App\\Database\\Migrations\\AddUangMukaToPengajuanDanKredit','default','App',1780999444,6),(38,'2026-06-09-100002','App\\Database\\Migrations\\AddRekeningToBuktiPembayaran','default','App',1780999444,6);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nasabah`
--

DROP TABLE IF EXISTS `nasabah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nasabah` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kode_nasabah` varchar(50) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'Tautan opsional ke akun pelanggan (users.role = pelanggan)',
  `nama` varchar(150) NOT NULL,
  `no_telepon` varchar(30) NOT NULL,
  `alamat` text NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_nasabah` (`kode_nasabah`),
  KEY `nasabah_user_id_foreign` (`user_id`),
  CONSTRAINT `nasabah_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nasabah`
--

LOCK TABLES `nasabah` WRITE;
/*!40000 ALTER TABLE `nasabah` DISABLE KEYS */;
INSERT INTO `nasabah` (`id`, `kode_nasabah`, `user_id`, `nama`, `no_telepon`, `alamat`, `catatan`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,'NSB-0001',NULL,'Ayu Lestari','6281234567890','Denpasar','Data nasabah dummy demo MahenGold.','2026-06-12 21:28:21','2026-06-12 21:28:21',NULL),(2,'NSB-0002',NULL,'Kadek Surya','6289876543210','Badung','Data nasabah dummy demo MahenGold.','2026-06-12 21:28:21','2026-06-12 21:28:21',NULL),(3,'NSB-0003',NULL,'Ni Putu Sari','6281112223334','Gianyar','Data nasabah dummy demo MahenGold.','2026-06-12 21:28:21','2026-06-12 21:28:21',NULL);
/*!40000 ALTER TABLE `nasabah` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pembayaran_angsuran`
--

DROP TABLE IF EXISTS `pembayaran_angsuran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pembayaran_angsuran` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kode_pembayaran` varchar(50) NOT NULL,
  `kredit_id` int(10) unsigned NOT NULL,
  `jadwal_angsuran_id` int(10) unsigned DEFAULT NULL,
  `tanggal_bayar` date NOT NULL,
  `nominal_bayar` decimal(15,2) NOT NULL,
  `metode_pembayaran` enum('transfer','cash','lainnya') NOT NULL DEFAULT 'transfer',
  `keterangan` text DEFAULT NULL,
  `dicatat_oleh` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_pembayaran` (`kode_pembayaran`),
  KEY `pembayaran_angsuran_kredit_id_foreign` (`kredit_id`),
  KEY `pembayaran_angsuran_jadwal_angsuran_id_foreign` (`jadwal_angsuran_id`),
  KEY `pembayaran_angsuran_dicatat_oleh_foreign` (`dicatat_oleh`),
  CONSTRAINT `pembayaran_angsuran_dicatat_oleh_foreign` FOREIGN KEY (`dicatat_oleh`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pembayaran_angsuran_jadwal_angsuran_id_foreign` FOREIGN KEY (`jadwal_angsuran_id`) REFERENCES `jadwal_angsuran` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  CONSTRAINT `pembayaran_angsuran_kredit_id_foreign` FOREIGN KEY (`kredit_id`) REFERENCES `kredit` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pembayaran_angsuran`
--

LOCK TABLES `pembayaran_angsuran` WRITE;
/*!40000 ALTER TABLE `pembayaran_angsuran` DISABLE KEYS */;
INSERT INTO `pembayaran_angsuran` (`id`, `kode_pembayaran`, `kredit_id`, `jadwal_angsuran_id`, `tanggal_bayar`, `nominal_bayar`, `metode_pembayaran`, `keterangan`, `dicatat_oleh`, `created_at`, `updated_at`) VALUES (1,'BYR-0001',1,1,'2026-05-03',137500.00,'transfer','Pembayaran dummy demo.',1,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(2,'BYR-0002',1,2,'2026-06-03',137500.00,'transfer','Pembayaran dummy demo.',1,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(3,'BYR-0003',2,13,'2026-05-22',88000.00,'transfer','Pembayaran dummy demo.',1,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(4,'BYR-0004',2,14,'2026-05-29',88000.00,'transfer','Pembayaran dummy demo.',1,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(5,'BYR-0005',2,15,'2026-06-05',88000.00,'transfer','Pembayaran dummy demo.',1,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(6,'BYR-0006',2,16,'2026-06-12',88000.00,'transfer','Pembayaran dummy demo.',1,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(7,'BYR-0007',2,17,'2026-06-19',88000.00,'transfer','Pembayaran dummy demo.',1,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(8,'BYR-0008',3,53,'2025-12-14',229167.00,'transfer','Pembayaran dummy demo.',1,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(9,'BYR-0009',3,54,'2026-01-14',229167.00,'transfer','Pembayaran dummy demo.',1,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(10,'BYR-0010',3,55,'2026-02-14',229167.00,'transfer','Pembayaran dummy demo.',1,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(11,'BYR-0011',3,56,'2026-03-14',229167.00,'transfer','Pembayaran dummy demo.',1,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(12,'BYR-0012',3,57,'2026-04-14',229167.00,'transfer','Pembayaran dummy demo.',1,'2026-06-12 21:28:21','2026-06-12 21:28:21'),(13,'BYR-0013',3,58,'2026-05-14',229165.00,'transfer','Pembayaran dummy demo.',1,'2026-06-12 21:28:21','2026-06-12 21:28:21');
/*!40000 ALTER TABLE `pembayaran_angsuran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengajuan`
--

DROP TABLE IF EXISTS `pengajuan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pengajuan` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kode_pesanan` varchar(30) DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'null = pengajuan anonim via WA lama',
  `produk_emas_id` int(10) unsigned NOT NULL,
  `metode_pembayaran` enum('cash','kredit') NOT NULL,
  `metode_konfirmasi` varchar(20) DEFAULT NULL,
  `nama` varchar(150) NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `alamat` text NOT NULL,
  `tenor_bulan` tinyint(4) DEFAULT NULL,
  `periode_angsuran` enum('bulanan','mingguan') DEFAULT NULL,
  `uang_muka` decimal(15,2) NOT NULL DEFAULT 0.00,
  `foto_ktp` varchar(255) DEFAULT NULL,
  `status` enum('baru','diproses','disetujui','ditolak','dibatalkan','selesai') NOT NULL DEFAULT 'baru',
  `pembayaran_status` enum('belum','menunggu','terverifikasi') NOT NULL DEFAULT 'belum',
  `catatan` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_kode_pesanan` (`kode_pesanan`),
  KEY `pengajuan_produk_emas_id_foreign` (`produk_emas_id`),
  KEY `status` (`status`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `pengajuan_produk_emas_id_foreign` FOREIGN KEY (`produk_emas_id`) REFERENCES `produk_emas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pengajuan_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengajuan`
--

LOCK TABLES `pengajuan` WRITE;
/*!40000 ALTER TABLE `pengajuan` DISABLE KEYS */;
/*!40000 ALTER TABLE `pengajuan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengajuan_aktivitas`
--

DROP TABLE IF EXISTS `pengajuan_aktivitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pengajuan_aktivitas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pengajuan_id` int(10) unsigned NOT NULL,
  `aksi` varchar(50) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `aktor` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pengajuan_id` (`pengajuan_id`),
  CONSTRAINT `pengajuan_aktivitas_pengajuan_id_foreign` FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengajuan_aktivitas`
--

LOCK TABLES `pengajuan_aktivitas` WRITE;
/*!40000 ALTER TABLE `pengajuan_aktivitas` DISABLE KEYS */;
INSERT INTO `pengajuan_aktivitas` (`id`, `pengajuan_id`, `aksi`, `keterangan`, `aktor`, `created_at`) VALUES (5,3,'dibuat','Pesanan dibuat oleh pelanggan','pelanggan','2026-06-04 18:58:00'),(6,4,'dibuat','Pesanan dibuat oleh pelanggan','pelanggan','2026-06-04 19:00:02'),(7,4,'diverifikasi','Disetujui. Jadwal kedatangan: 2026-06-05 09:59:00','Administrator MahenGold','2026-06-04 19:08:28'),(8,4,'status_diubah','Status diubah menjadi diproses','Administrator MahenGold','2026-06-04 19:09:05'),(9,4,'status_diubah','Status diubah menjadi disetujui','Administrator MahenGold','2026-06-04 19:10:00'),(10,4,'status_diubah','Status diubah menjadi selesai','Administrator MahenGold','2026-06-04 19:11:43'),(31,15,'dibuat','Pesanan dibuat oleh pelanggan','Putu Demo','2026-06-10 23:23:58');
/*!40000 ALTER TABLE `pengajuan_aktivitas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengaturan_sistem`
--

DROP TABLE IF EXISTS `pengaturan_sistem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pengaturan_sistem` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nama_toko` varchar(150) NOT NULL DEFAULT 'MahenGold',
  `nomor_whatsapp_toko` varchar(30) NOT NULL DEFAULT '6282146575233',
  `margin_default` decimal(5,2) NOT NULL DEFAULT 10.00,
  `logo_text` varchar(20) NOT NULL DEFAULT 'MG',
  `alamat_toko` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengaturan_sistem`
--

LOCK TABLES `pengaturan_sistem` WRITE;
/*!40000 ALTER TABLE `pengaturan_sistem` DISABLE KEYS */;
INSERT INTO `pengaturan_sistem` (`id`, `nama_toko`, `nomor_whatsapp_toko`, `margin_default`, `logo_text`, `alamat_toko`, `created_at`, `updated_at`) VALUES (1,'MahenGold','6282146575233',10.00,'MG','Denpasar, Bali','2026-06-12 21:28:20','2026-06-12 21:28:20');
/*!40000 ALTER TABLE `pengaturan_sistem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produk_emas`
--

DROP TABLE IF EXISTS `produk_emas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produk_emas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kode_produk` varchar(50) NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `jenis_emas` varchar(100) NOT NULL,
  `kadar` varchar(50) NOT NULL,
  `berat_gram` decimal(10,2) NOT NULL,
  `harga_pokok` decimal(15,2) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `deskripsi` text DEFAULT NULL,
  `gambar_url` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_produk` (`kode_produk`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produk_emas`
--

LOCK TABLES `produk_emas` WRITE;
/*!40000 ALTER TABLE `produk_emas` DISABLE KEYS */;
INSERT INTO `produk_emas` (`id`, `kode_produk`, `nama_produk`, `jenis_emas`, `kadar`, `berat_gram`, `harga_pokok`, `stok`, `deskripsi`, `gambar_url`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,'MGD-001','Cincin Emas 1 Gram','Perhiasan','22K',1.00,1500000.00,5,'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.',NULL,'aktif','2026-06-12 21:28:21','2026-06-13 12:40:45',NULL),(2,'MGD-002','Kalung Emas 2 Gram','Perhiasan','22K',2.00,3200000.00,3,'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.',NULL,'aktif','2026-06-12 21:28:21','2026-06-13 12:40:45',NULL),(3,'MGD-003','Anting Emas 0.8 Gram','Perhiasan','22K',0.80,1250000.00,8,'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.',NULL,'aktif','2026-06-12 21:28:21','2026-06-13 12:40:45',NULL),(4,'MGD-004','Gelang Emas 3 Gram','Perhiasan','22K',3.00,4800000.00,4,'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.',NULL,'aktif','2026-06-12 21:38:05','2026-06-13 12:40:45',NULL),(5,'MGD-005','Logam Mulia 5 Gram','Logam Mulia','24K',5.00,7500000.00,6,'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.',NULL,'aktif','2026-06-12 21:38:05','2026-06-13 12:40:45',NULL),(6,'MGD-006','Liontin Emas 1.5 Gram','Perhiasan','22K',1.50,2300000.00,7,'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.',NULL,'aktif','2026-06-12 21:38:05','2026-06-13 12:40:45',NULL);
/*!40000 ALTER TABLE `produk_emas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','pelanggan') NOT NULL DEFAULT 'pelanggan',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `nama`, `email`, `no_telepon`, `username`, `password_hash`, `role`, `is_active`, `created_at`, `updated_at`) VALUES (1,'Administrator MahenGold','admin@mahengold.test',NULL,'admin','$2y$10$jd4xEe.GDXAS0N8DPYTSS.ApmRqOxvlwWpo9btm44DUIfa0qy1sRC','admin',1,'2026-06-12 21:28:20','2026-06-12 21:28:20');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_logs`
--

DROP TABLE IF EXISTS `whatsapp_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `whatsapp_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipe` enum('pengajuan_kredit','pengingat_jatuh_tempo','pembayaran_diterima','kredit_lunas','info_transaksi','konfirmasi_pesanan') DEFAULT 'pengajuan_kredit',
  `target` enum('pelanggan','admin') NOT NULL DEFAULT 'pelanggan',
  `tujuan_nomor` varchar(30) DEFAULT NULL,
  `nama_tujuan` varchar(150) DEFAULT NULL,
  `pesan` text NOT NULL,
  `wa_url` text DEFAULT NULL,
  `status` enum('dibuat','dibuka','dikirim_manual','gagal') NOT NULL DEFAULT 'dibuat',
  `related_type` varchar(100) DEFAULT NULL,
  `related_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_logs`
--

LOCK TABLES `whatsapp_logs` WRITE;
/*!40000 ALTER TABLE `whatsapp_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_logs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-13 12:40:54
