-- MariaDB dump 10.19  Distrib 10.5.15-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: support_local
-- ------------------------------------------------------
-- Server version	10.5.15-MariaDB-0+deb11u1

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
-- Current Database: `support_local`
--

/*!40000 DROP DATABASE IF EXISTS `support_local`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `support_local` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;

USE `support_local`;

--
-- Table structure for table `role_navigation_items`
--

DROP TABLE IF EXISTS `role_navigation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_navigation_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` int(11) DEFAULT NULL,
  `icon` char(50) DEFAULT NULL,
  `title` char(50) DEFAULT NULL,
  `link` char(255) DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  `enabled` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_navigation_items_id_uindex` (`id`),
  KEY `role_navigation_items_roles_id_fk` (`role`),
  CONSTRAINT `role_navigation_items_roles_id_fk` FOREIGN KEY (`role`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_navigation_items`
--

LOCK TABLES `role_navigation_items` WRITE;
/*!40000 ALTER TABLE `role_navigation_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_navigation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(25) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_id_uindex` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='Holds the user roles in the system.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Admin'),(3,'Client');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_categories`
--

DROP TABLE IF EXISTS `ticket_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(255) DEFAULT NULL,
  `url` char(255) DEFAULT NULL,
  `icon` char(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_categories`
--

LOCK TABLES `ticket_categories` WRITE;
/*!40000 ALTER TABLE `ticket_categories` DISABLE KEYS */;
INSERT INTO `ticket_categories` VALUES (1,'Technical Support','technical-support',NULL),(2,'Phone Service Support','phone-support',NULL),(3,'Digital Marketing Order','marketing-support',NULL),(4,'Software Development','software-development',NULL);
/*!40000 ALTER TABLE `ticket_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_tokens`
--

DROP TABLE IF EXISTS `user_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) DEFAULT NULL,
  `token` varchar(1024) DEFAULT NULL,
  `delete_after` datetime NOT NULL,
  `jti` char(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_tokens_id_uindex` (`id`),
  KEY `user_tokens_users_id_fk` (`user`),
  KEY `user_tokens_jti_index` (`jti`),
  CONSTRAINT `user_tokens_users_id_fk` FOREIGN KEY (`user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Holds JWTs that are used as bearer tokens for acces.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_tokens`
--

LOCK TABLES `user_tokens` WRITE;
/*!40000 ALTER TABLE `user_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` char(255) NOT NULL,
  `email` char(255) NOT NULL,
  `password` char(64) DEFAULT NULL,
  `nonce` char(36) DEFAULT NULL,
  `role` int(11) DEFAULT NULL,
  `first` char(60) DEFAULT NULL,
  `last` char(60) DEFAULT NULL,
  `company_name` char(100) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT current_timestamp(),
  `activation_status` tinyint(4) DEFAULT 0 COMMENT 'Determines the status of activation. 0 is new (not activated), 1 is active, 2 is disabled (not in spec, just reserved for future use).',
  `activated_on` datetime DEFAULT NULL,
  `password_version` int(11) DEFAULT 1,
  `uuid` char(36) NOT NULL DEFAULT uuid() COMMENT 'The user''s UUID for use with messaging.',
  PRIMARY KEY (`username`),
  UNIQUE KEY `users_email_uindex` (`email`),
  UNIQUE KEY `users_id_uindex` (`id`),
  UNIQUE KEY `users_pk` (`uuid`),
  UNIQUE KEY `users_username_uindex` (`username`),
  KEY `users__fk_role` (`role`),
  CONSTRAINT `users__fk_role` FOREIGN KEY (`role`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'kaloyan@hph.io','kaloyan@hph.io','$2y$10$fqew8TQSNDBRcXugHh2D2elmkSsH8Vd0vs0CnkeQoI2xJb/2XEVee',NULL,1,'Kaloyan','Stoyanov',NULL,'2022-04-05 12:17:14',NULL,1,NULL,NULL,'a815a6a2-7d50-11ed-a6c9-f0d4e2e605b0');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'support_local'
--

--
-- Dumping routines for database 'support_local'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-03-31 18:58:52
