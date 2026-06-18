-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mahengold_demo
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
-- Table structure for table `bukti_pembayaran`
--

DROP TABLE IF EXISTS `bukti_pembayaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bukti_pembayaran` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) DEFAULT NULL,
  `tipe` enum('cash','cicilan','dp') NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bukti_pembayaran`
--

LOCK TABLES `bukti_pembayaran` WRITE;
/*!40000 ALTER TABLE `bukti_pembayaran` DISABLE KEYS */;
INSERT INTO `bukti_pembayaran` VALUES (1,'BKT-0001','dp',1,NULL,NULL,2,200000.00,'Putu Demo Pelanggan','1234567890','BCA','demo_bukti_dp_baru.png','menunggu',NULL,NULL,NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(2,'BKT-0002','dp',2,NULL,NULL,2,200000.00,'Putu Demo Pelanggan','1234567890','BCA','demo_bukti_dp_pending.png','menunggu',NULL,NULL,NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(3,'BKT-0003','dp',3,NULL,NULL,2,200000.00,'Putu Demo Pelanggan','1234567890','BCA','demo_bukti_dp_verified.png','terverifikasi',NULL,1,'2026-06-15 12:24:25','2026-06-15 12:24:25','2026-06-15 12:24:25'),(4,'BKT-0004','cash',4,NULL,NULL,2,2300000.00,'Putu Demo Pelanggan','1234567890','BCA','demo_bukti_cash.png','menunggu',NULL,NULL,NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jadwal_angsuran`
--

LOCK TABLES `jadwal_angsuran` WRITE;
/*!40000 ALTER TABLE `jadwal_angsuran` DISABLE KEYS */;
INSERT INTO `jadwal_angsuran` VALUES (1,1,1,'2026-05-06',137500.00,137500.00,'dibayar','2026-05-06','2026-06-15 12:24:24','2026-06-15 12:24:24'),(2,1,2,'2026-06-06',137500.00,137500.00,'dibayar','2026-06-06','2026-06-15 12:24:24','2026-06-15 12:24:24'),(3,1,3,'2026-07-06',137500.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(4,1,4,'2026-08-06',137500.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(5,1,5,'2026-09-06',137500.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(6,1,6,'2026-10-06',137500.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(7,1,7,'2026-11-06',137500.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(8,1,8,'2026-12-06',137500.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(9,1,9,'2027-01-06',137500.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(10,1,10,'2027-02-06',137500.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(11,1,11,'2027-03-06',137500.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(12,1,12,'2027-04-06',137500.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(13,2,1,'2026-05-25',88000.00,88000.00,'dibayar','2026-05-25','2026-06-15 12:24:24','2026-06-15 12:24:24'),(14,2,2,'2026-06-01',88000.00,88000.00,'dibayar','2026-06-01','2026-06-15 12:24:24','2026-06-15 12:24:24'),(15,2,3,'2026-06-08',88000.00,88000.00,'dibayar','2026-06-08','2026-06-15 12:24:24','2026-06-15 12:24:24'),(16,2,4,'2026-06-15',88000.00,88000.00,'dibayar','2026-06-15','2026-06-15 12:24:24','2026-06-15 12:24:24'),(17,2,5,'2026-06-22',88000.00,88000.00,'dibayar','2026-06-22','2026-06-15 12:24:24','2026-06-15 12:24:24'),(18,2,6,'2026-06-29',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(19,2,7,'2026-07-06',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(20,2,8,'2026-07-13',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(21,2,9,'2026-07-20',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(22,2,10,'2026-07-27',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(23,2,11,'2026-08-03',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(24,2,12,'2026-08-10',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(25,2,13,'2026-08-17',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(26,2,14,'2026-08-24',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(27,2,15,'2026-08-31',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(28,2,16,'2026-09-07',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(29,2,17,'2026-09-14',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(30,2,18,'2026-09-21',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(31,2,19,'2026-09-28',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(32,2,20,'2026-10-05',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(33,2,21,'2026-10-12',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(34,2,22,'2026-10-19',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(35,2,23,'2026-10-26',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(36,2,24,'2026-11-02',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(37,2,25,'2026-11-09',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(38,2,26,'2026-11-16',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(39,2,27,'2026-11-23',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(40,2,28,'2026-11-30',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(41,2,29,'2026-12-07',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(42,2,30,'2026-12-14',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(43,2,31,'2026-12-21',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(44,2,32,'2026-12-28',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(45,2,33,'2027-01-04',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(46,2,34,'2027-01-11',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(47,2,35,'2027-01-18',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(48,2,36,'2027-01-25',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(49,2,37,'2027-02-01',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(50,2,38,'2027-02-08',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(51,2,39,'2027-02-15',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(52,2,40,'2027-02-22',88000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(53,3,1,'2025-12-17',229167.00,229167.00,'dibayar','2025-12-17','2026-06-15 12:24:24','2026-06-15 12:24:24'),(54,3,2,'2026-01-17',229167.00,229167.00,'dibayar','2026-01-17','2026-06-15 12:24:24','2026-06-15 12:24:24'),(55,3,3,'2026-02-17',229167.00,229167.00,'dibayar','2026-02-17','2026-06-15 12:24:24','2026-06-15 12:24:24'),(56,3,4,'2026-03-17',229167.00,229167.00,'dibayar','2026-03-17','2026-06-15 12:24:24','2026-06-15 12:24:24'),(57,3,5,'2026-04-17',229167.00,229167.00,'dibayar','2026-04-17','2026-06-15 12:24:24','2026-06-15 12:24:24'),(58,3,6,'2026-05-17',229165.00,229165.00,'dibayar','2026-05-17','2026-06-15 12:24:24','2026-06-15 12:24:24'),(59,4,1,'2026-07-15',195834.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(60,4,2,'2026-08-15',195834.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(61,4,3,'2026-09-15',195834.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(62,4,4,'2026-10-15',195834.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(63,4,5,'2026-11-15',195834.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(64,4,6,'2026-12-15',195830.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(65,5,1,'2026-06-22',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(66,5,2,'2026-06-29',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(67,5,3,'2026-07-06',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(68,5,4,'2026-07-13',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(69,5,5,'2026-07-20',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(70,5,6,'2026-07-27',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(71,5,7,'2026-08-03',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(72,5,8,'2026-08-10',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(73,5,9,'2026-08-17',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(74,5,10,'2026-08-24',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(75,5,11,'2026-08-31',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(76,5,12,'2026-09-07',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(77,5,13,'2026-09-14',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(78,5,14,'2026-09-21',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(79,5,15,'2026-09-28',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(80,5,16,'2026-10-05',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(81,5,17,'2026-10-12',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(82,5,18,'2026-10-19',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(83,5,19,'2026-10-26',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(84,5,20,'2026-11-02',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(85,5,21,'2026-11-09',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(86,5,22,'2026-11-16',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(87,5,23,'2026-11-23',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(88,5,24,'2026-11-30',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(89,5,25,'2026-12-07',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(90,5,26,'2026-12-14',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(91,5,27,'2026-12-21',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(92,5,28,'2026-12-28',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(93,5,29,'2027-01-04',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(94,5,30,'2027-01-11',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(95,5,31,'2027-01-18',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(96,5,32,'2027-01-25',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(97,5,33,'2027-02-01',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(98,5,34,'2027-02-08',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(99,5,35,'2027-02-15',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(100,5,36,'2027-02-22',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(101,5,37,'2027-03-01',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(102,5,38,'2027-03-08',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(103,5,39,'2027-03-15',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(104,5,40,'2027-03-22',83000.00,0.00,'belum_dibayar',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kredit`
--

LOCK TABLES `kredit` WRITE;
/*!40000 ALTER TABLE `kredit` DISABLE KEYS */;
INSERT INTO `kredit` VALUES (1,NULL,'KRD-0001',1,1,'2026-04-06',1500000.00,10.00,150000.00,1650000.00,0.00,NULL,12,'bulanan',12,137500.00,275000.00,1375000.00,'aktif','Data transaksi kredit dummy untuk demo.','2026-06-15 12:24:24','2026-06-15 12:24:24'),(2,NULL,'KRD-0002',2,2,'2026-05-11',3200000.00,10.00,320000.00,3520000.00,0.00,NULL,10,'mingguan',40,88000.00,440000.00,3080000.00,'aktif','Data transaksi kredit dummy untuk demo.','2026-06-15 12:24:24','2026-06-15 12:24:24'),(3,NULL,'KRD-0003',3,3,'2025-11-17',1250000.00,10.00,125000.00,1375000.00,0.00,NULL,6,'bulanan',6,229167.00,1375000.00,0.00,'lunas','Data transaksi kredit dummy untuk demo.','2026-06-15 12:24:24','2026-06-15 12:24:24'),(4,2,'KRD-0004',4,3,'2026-06-15',1250000.00,10.00,125000.00,1375000.00,200000.00,1175000.00,6,'bulanan',6,195834.00,0.00,1175000.00,'aktif','Auto dari pesanan MG-DEMO-002','2026-06-15 12:24:25','2026-06-15 12:24:25'),(5,3,'KRD-0005',4,2,'2026-06-15',3200000.00,10.00,320000.00,3520000.00,200000.00,3320000.00,10,'mingguan',40,83000.00,0.00,3320000.00,'aktif','Auto dari pesanan MG-DEMO-003','2026-06-15 12:24:25','2026-06-15 12:24:25');
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
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (109,'2026-04-26-080000','App\\Database\\Migrations\\CreateUsersTable','default','App',1781497463,1),(110,'2026-04-26-080100','App\\Database\\Migrations\\CreateProdukEmasTable','default','App',1781497463,1),(111,'2026-04-26-080200','App\\Database\\Migrations\\CreateNasabahTable','default','App',1781497463,1),(112,'2026-04-26-080300','App\\Database\\Migrations\\CreateKreditTable','default','App',1781497463,1),(113,'2026-04-26-080400','App\\Database\\Migrations\\CreateJadwalAngsuranTable','default','App',1781497463,1),(114,'2026-04-26-080500','App\\Database\\Migrations\\CreatePembayaranAngsuranTable','default','App',1781497463,1),(115,'2026-04-26-080600','App\\Database\\Migrations\\CreateWhatsappLogsTable','default','App',1781497463,1),(116,'2026-04-26-080700','App\\Database\\Migrations\\CreatePengaturanSistemTable','default','App',1781497463,1),(117,'2026-05-29-000001','App\\Database\\Migrations\\AlterUsersAddPelangganRole','default','App',1781497463,1),(118,'2026-05-29-000002','App\\Database\\Migrations\\CreatePengajuanTable','default','App',1781497463,1),(119,'2026-06-02-000001','App\\Database\\Migrations\\AddUserIdToNasabahTable','default','App',1781497464,1),(120,'2026-06-04-000001','App\\Database\\Migrations\\MakeUsernameNullable','default','App',1781497464,1),(121,'2026-06-04-100001','App\\Database\\Migrations\\AddPesananFieldsToPengajuan','default','App',1781497464,1),(122,'2026-06-04-100002','App\\Database\\Migrations\\AlterWhatsappLogsTipeAddKonfirmasiPesanan','default','App',1781497464,1),(123,'2026-06-04-100003','App\\Database\\Migrations\\CreatePengajuanAktivitasTable','default','App',1781497464,1),(124,'2026-06-04-100004','App\\Database\\Migrations\\CreateEmailLogsTable','default','App',1781497464,1),(125,'2026-06-04-110001','App\\Database\\Migrations\\AddPembayaranStatusToPengajuan','default','App',1781497464,1),(126,'2026-06-04-110002','App\\Database\\Migrations\\AddPengajuanIdToKredit','default','App',1781497464,1),(127,'2026-06-04-110003','App\\Database\\Migrations\\CreateBuktiPembayaranTable','default','App',1781497464,1),(128,'2026-06-04-110004','App\\Database\\Migrations\\DropWaktuSesiFromPengajuan','default','App',1781497464,1),(129,'2026-06-09-100001','App\\Database\\Migrations\\AddUangMukaToPengajuanDanKredit','default','App',1781497464,1),(130,'2026-06-09-100002','App\\Database\\Migrations\\AddRekeningToBuktiPembayaran','default','App',1781497464,1),(131,'2026-06-13-100001','App\\Database\\Migrations\\AddDpTipeToBuktiPembayaran','default','App',1781497464,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nasabah`
--

LOCK TABLES `nasabah` WRITE;
/*!40000 ALTER TABLE `nasabah` DISABLE KEYS */;
INSERT INTO `nasabah` VALUES (1,'NSB-0001',NULL,'Ayu Lestari','6281234567890','Denpasar','Data nasabah dummy demo MahenGold.','2026-06-15 12:24:24','2026-06-15 12:24:24',NULL),(2,'NSB-0002',NULL,'Kadek Surya','6289876543210','Badung','Data nasabah dummy demo MahenGold.','2026-06-15 12:24:24','2026-06-15 12:24:24',NULL),(3,'NSB-0003',NULL,'Ni Putu Sari','6281112223334','Gianyar','Data nasabah dummy demo MahenGold.','2026-06-15 12:24:24','2026-06-15 12:24:24',NULL),(4,'NSB-0004',2,'Putu Demo Pelanggan','6281200000001','Jl. Tunjung Sari No. 12, Denpasar, Bali','Auto dari pesanan MG-DEMO-002','2026-06-15 12:24:25','2026-06-15 12:24:25',NULL);
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
INSERT INTO `pembayaran_angsuran` VALUES (1,'BYR-0001',1,1,'2026-05-06',137500.00,'transfer','Pembayaran dummy demo.',1,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(2,'BYR-0002',1,2,'2026-06-06',137500.00,'transfer','Pembayaran dummy demo.',1,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(3,'BYR-0003',2,13,'2026-05-25',88000.00,'transfer','Pembayaran dummy demo.',1,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(4,'BYR-0004',2,14,'2026-06-01',88000.00,'transfer','Pembayaran dummy demo.',1,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(5,'BYR-0005',2,15,'2026-06-08',88000.00,'transfer','Pembayaran dummy demo.',1,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(6,'BYR-0006',2,16,'2026-06-15',88000.00,'transfer','Pembayaran dummy demo.',1,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(7,'BYR-0007',2,17,'2026-06-22',88000.00,'transfer','Pembayaran dummy demo.',1,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(8,'BYR-0008',3,53,'2025-12-17',229167.00,'transfer','Pembayaran dummy demo.',1,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(9,'BYR-0009',3,54,'2026-01-17',229167.00,'transfer','Pembayaran dummy demo.',1,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(10,'BYR-0010',3,55,'2026-02-17',229167.00,'transfer','Pembayaran dummy demo.',1,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(11,'BYR-0011',3,56,'2026-03-17',229167.00,'transfer','Pembayaran dummy demo.',1,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(12,'BYR-0012',3,57,'2026-04-17',229167.00,'transfer','Pembayaran dummy demo.',1,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(13,'BYR-0013',3,58,'2026-05-17',229165.00,'transfer','Pembayaran dummy demo.',1,'2026-06-15 12:24:24','2026-06-15 12:24:24');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengajuan`
--

LOCK TABLES `pengajuan` WRITE;
/*!40000 ALTER TABLE `pengajuan` DISABLE KEYS */;
INSERT INTO `pengajuan` VALUES (1,'MG-DEMO-001',2,1,'kredit',NULL,'Putu Demo Pelanggan','6281200000001','Jl. Tunjung Sari No. 12, Denpasar, Bali',12,'bulanan',200000.00,'demo_ktp.png','baru','menunggu',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(2,'MG-DEMO-002',2,3,'kredit',NULL,'Putu Demo Pelanggan','6281200000001','Jl. Tunjung Sari No. 12, Denpasar, Bali',6,'bulanan',200000.00,'demo_ktp.png','disetujui','menunggu',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(3,'MG-DEMO-003',2,2,'kredit',NULL,'Putu Demo Pelanggan','6281200000001','Jl. Tunjung Sari No. 12, Denpasar, Bali',10,'mingguan',200000.00,'demo_ktp.png','disetujui','terverifikasi',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25'),(4,'MG-DEMO-004',2,6,'cash',NULL,'Putu Demo Pelanggan','6281200000001','Jl. Tunjung Sari No. 12, Denpasar, Bali',NULL,NULL,0.00,NULL,'disetujui','menunggu',NULL,'2026-06-15 12:24:25','2026-06-15 12:24:25');
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengajuan_aktivitas`
--

LOCK TABLES `pengajuan_aktivitas` WRITE;
/*!40000 ALTER TABLE `pengajuan_aktivitas` DISABLE KEYS */;
INSERT INTO `pengajuan_aktivitas` VALUES (1,1,'dibuat','Pesanan dibuat oleh pelanggan','pelanggan','2026-06-15 12:24:25'),(2,2,'dibuat','Pesanan dibuat oleh pelanggan','pelanggan','2026-06-15 12:24:25'),(3,2,'diverifikasi','Pesanan disetujui admin.','Administrator MahenGold','2026-06-15 12:24:25'),(4,2,'kredit_dibuat','Kredit otomatis dibuat: KRD-0004','Administrator MahenGold','2026-06-15 12:24:25'),(5,3,'dibuat','Pesanan dibuat oleh pelanggan','pelanggan','2026-06-15 12:24:25'),(6,3,'diverifikasi','Pesanan disetujui admin.','Administrator MahenGold','2026-06-15 12:24:25'),(7,3,'kredit_dibuat','Kredit otomatis dibuat: KRD-0005','Administrator MahenGold','2026-06-15 12:24:25'),(8,4,'dibuat','Pesanan dibuat oleh pelanggan','pelanggan','2026-06-15 12:24:25'),(9,4,'diverifikasi','Pesanan disetujui admin.','Administrator MahenGold','2026-06-15 12:24:25');
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
INSERT INTO `pengaturan_sistem` VALUES (1,'MahenGold','6282146575233',10.00,'MG','Denpasar, Bali','2026-06-15 12:24:24','2026-06-15 12:24:24');
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
INSERT INTO `produk_emas` VALUES (1,'MGD-001','Cincin Emas 1 Gram','Perhiasan','22K',1.00,1500000.00,4,'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.',NULL,'aktif','2026-06-15 12:24:24','2026-06-15 12:24:24',NULL),(2,'MGD-002','Kalung Emas 2 Gram','Perhiasan','22K',2.00,3200000.00,1,'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.',NULL,'aktif','2026-06-15 12:24:24','2026-06-15 12:24:25',NULL),(3,'MGD-003','Anting Emas 0.8 Gram','Perhiasan','22K',0.80,1250000.00,6,'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.',NULL,'aktif','2026-06-15 12:24:24','2026-06-15 12:24:25',NULL),(4,'MGD-004','Gelang Emas 3 Gram','Perhiasan','22K',3.00,4800000.00,4,'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.',NULL,'aktif','2026-06-15 12:24:24','2026-06-15 12:24:24',NULL),(5,'MGD-005','Logam Mulia 5 Gram','Logam Mulia','24K',5.00,7500000.00,6,'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.',NULL,'aktif','2026-06-15 12:24:24','2026-06-15 12:24:24',NULL),(6,'MGD-006','Liontin Emas 1.5 Gram','Perhiasan','22K',1.50,2300000.00,7,'Produk emas premium MahenGold untuk kebutuhan investasi dan perhiasan.',NULL,'aktif','2026-06-15 12:24:24','2026-06-15 12:24:24',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrator MahenGold','admin@mahengold.test',NULL,'admin','$2y$10$hnoozRtc5qAVF5rij3EOc.jVqBhi7MI8Jnzc/XMhhFqdcITj1Ql2K','admin',1,'2026-06-15 12:24:24','2026-06-15 12:24:24'),(2,'Putu Demo Pelanggan','demo.pelanggan@mahengold.test','6281200000001',NULL,'$2y$10$a1uy9HtPtlWMKEVFY.FVreCJkH4tgdekb7HkYDOhQJE11fTyVBvZK','pelanggan',1,'2026-06-15 12:24:25','2026-06-15 12:24:25');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-15 12:24:25
