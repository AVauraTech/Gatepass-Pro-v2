-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: getpassdb
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
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `updation_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'admin','21232f297a57a5a743894a0e4a801fc3','2025-06-06 07:32:55');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checkpoint_logs`
--

DROP TABLE IF EXISTS `checkpoint_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `checkpoint_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pass_number` varchar(255) NOT NULL,
  `visitor_name` varchar(255) DEFAULT '',
  `action` varchar(50) NOT NULL,
  `checkpoint_gate` varchar(100) DEFAULT 'Gate-Alpha North',
  `guard_id` varchar(100) DEFAULT 'SYS-AI-GUARD',
  `biometric_match_pct` decimal(5,2) DEFAULT NULL,
  `alpr_plate` varchar(100) DEFAULT NULL,
  `status_note` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checkpoint_logs`
--

LOCK TABLES `checkpoint_logs` WRITE;
/*!40000 ALTER TABLE `checkpoint_logs` DISABLE KEYS */;
INSERT INTO `checkpoint_logs` VALUES (1,'682109789','ram kumar','CHECK_IN','Gate-Alpha North','ADMIN-01',98.60,'DL-08-AX-9942','Visitor checked into facility','2026-10-11 12:48:27'),(2,'682109789','ram kumar','BARRIER_BLOCKED_EXPIRED','Gate-Alpha North','TURNSTILE-01',NULL,NULL,'Expired pass at turnstile','2026-10-11 13:10:38');
/*!40000 ALTER TABLE `checkpoint_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `passes`
--

DROP TABLE IF EXISTS `passes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `passes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pass_number` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `contact_number` bigint(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `identity_type` varchar(200) DEFAULT NULL,
  `identity_card_no` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `reason` mediumtext DEFAULT NULL,
  `pass_creation_date` timestamp NULL DEFAULT current_timestamp(),
  `status` varchar(100) DEFAULT 'Pending',
  `vehicle_no` varchar(50) DEFAULT 'N/A',
  `security_clearance` varchar(50) DEFAULT 'Standard',
  `photo_url` text DEFAULT NULL,
  `hmac_hash` varchar(64) DEFAULT NULL,
  `checkin_time` datetime DEFAULT NULL,
  `checkout_time` datetime DEFAULT NULL,
  `overstay_alert` tinyint(1) DEFAULT 0,
  `host_name` varchar(255) DEFAULT 'Security Operations',
  `host_department` varchar(255) DEFAULT 'General',
  `nda_signed` tinyint(1) DEFAULT 0,
  `nda_signature_data` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `passes`
--

LOCK TABLES `passes` WRITE;
/*!40000 ALTER TABLE `passes` DISABLE KEYS */;
INSERT INTO `passes` VALUES (1,'682109789','ram kumar',8787451200,'ramk@gmail.com','Adhar Card','53205452885','Visitor','2025-06-06','2025-06-13','visitor','2025-06-06 07:36:42','Flagged','N/A','Standard',NULL,'68b6aa65cfaebf6a7056de9b1c5ff6a7d56694bf073e1636f44e75ff28e36b18','2026-10-11 14:48:27',NULL,1,'Security Operations','General',0,NULL),(2,'788646440','Ravi',9598780012,'ravi@gmail.com','Adhar Card','530259387798','Visitor','2025-06-19','2025-06-19','meet to teacher','2025-06-19 04:15:08','Pending','N/A','Standard',NULL,'00f3e132eb7f7fa7987af6eb74ff7ac621db1323d79ddc1c1bcd8aa77c56cd45',NULL,NULL,0,'Security Operations','General',0,NULL),(3,'918237465','Dr. Vikramaditya Rao',9876543210,'v.rao@nexusdefense.com','Aadhaar Card','5420-1928-8812','Security Auditor','2026-10-10','2026-10-25','Operational security audit & inspection','2026-10-11 13:14:18','Approved','DL-08-AX-9942','Secret',NULL,'83615b793e31e0af2ea1ddb4cf62d74531fd223c866ea929625320f20687fa45',NULL,NULL,0,'Dr. Samantha Vance','Cybersecurity',1,NULL),(4,'845920138','Elena Rostova',9811223344,'elena.r@quantum.org','Passport','PASS-990218-IND','VIP Delegate','2026-10-11','2026-10-30','Bi-lateral executive summit delegate','2026-10-11 13:14:18','Approved','DL-04-CX-8192','VIP Delegate',NULL,'47d4a5f8b98ba7d01d2746b3b1d0aa80bd8bdb0fe229d15e5d0a902f89a0c8e6',NULL,NULL,0,'Director Arthur Pendleton','Executive Office',1,NULL);
/*!40000 ALTER TABLE `passes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_alerts`
--

DROP TABLE IF EXISTS `security_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pass_number` varchar(255) DEFAULT NULL,
  `visitor_name` varchar(255) DEFAULT '',
  `alert_type` varchar(100) NOT NULL,
  `severity` varchar(50) DEFAULT 'MEDIUM',
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_alerts`
--

LOCK TABLES `security_alerts` WRITE;
/*!40000 ALTER TABLE `security_alerts` DISABLE KEYS */;
INSERT INTO `security_alerts` VALUES (1,'682109789','ram kumar','CRYPTOGRAPHIC_TAMPER','CRITICAL','Pass payload signature mismatch. Possible counterfeit or manual DB tampering.','2026-10-11 12:36:53',0),(2,'682109789','ram kumar','OVERSTAY_EXCEEDED','CRITICAL','Visitor pass expired on 2025-06-13 but exit check-out was never registered.','2026-10-11 12:48:39',0);
/*!40000 ALTER TABLE `security_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `simulation_leaderboard`
--

DROP TABLE IF EXISTS `simulation_leaderboard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `simulation_leaderboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_name` varchar(100) NOT NULL,
  `score` int(11) DEFAULT 0,
  `level` int(11) DEFAULT 1,
  `accuracy_pct` decimal(5,2) DEFAULT 0.00,
  `processed_count` int(11) DEFAULT 0,
  `breaches_prevented` int(11) DEFAULT 0,
  `throughput_rate` decimal(5,2) DEFAULT 0.00,
  `date_played` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `simulation_leaderboard`
--

LOCK TABLES `simulation_leaderboard` WRITE;
/*!40000 ALTER TABLE `simulation_leaderboard` DISABLE KEYS */;
INSERT INTO `simulation_leaderboard` VALUES (1,'Commander-Vance',1450,4,94.50,22,7,2.80,'2026-10-11 13:11:35'),(2,'Agent-Rao',1200,3,91.00,18,5,2.40,'2026-10-11 13:11:48'),(3,'Officer-Maya',980,3,88.50,15,4,2.10,'2026-10-11 13:11:48');
/*!40000 ALTER TABLE `simulation_leaderboard` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-11 19:16:14
