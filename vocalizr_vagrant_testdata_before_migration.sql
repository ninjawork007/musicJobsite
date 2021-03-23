-- MySQL dump 10.13  Distrib 5.5.60, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: vocalizr
-- ------------------------------------------------------
-- Server version	5.5.60-0ubuntu0.14.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_action_audit`
--

DROP TABLE IF EXISTS `admin_action_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_action_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `actioner_id` int(11) DEFAULT NULL,
  `action` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C979128586DFF2` (`user_info_id`),
  KEY `IDX_C979128166D1F9C` (`project_id`),
  KEY `IDX_C979128E402B000` (`actioner_id`),
  CONSTRAINT `FK_C979128166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_C979128586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_C979128E402B000` FOREIGN KEY (`actioner_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_action_audit`
--

LOCK TABLES `admin_action_audit` WRITE;
/*!40000 ALTER TABLE `admin_action_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_action_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_message`
--

DROP TABLE IF EXISTS `app_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_type` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `message` longtext COLLATE utf8_unicode_ci NOT NULL,
  `learn_more_link` longtext COLLATE utf8_unicode_ci,
  `expire_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_message`
--

LOCK TABLES `app_message` WRITE;
/*!40000 ALTER TABLE `app_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_message_read`
--

DROP TABLE IF EXISTS `app_message_read`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_message_read` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `app_message_id` int(11) DEFAULT NULL,
  `read_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F34CC03F586DFF2` (`user_info_id`),
  KEY `IDX_F34CC03F561B855` (`app_message_id`),
  CONSTRAINT `FK_F34CC03F561B855` FOREIGN KEY (`app_message_id`) REFERENCES `app_message` (`id`),
  CONSTRAINT `FK_F34CC03F586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_message_read`
--

LOCK TABLES `app_message_read` WRITE;
/*!40000 ALTER TABLE `app_message_read` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_message_read` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `article`
--

DROP TABLE IF EXISTS `article`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_category_id` int(11) DEFAULT NULL,
  `spotlight_user_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `short_desc` longtext COLLATE utf8_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `seo_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `seo_desc` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `read_count` int(11) NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_23A0E6688C5F785` (`article_category_id`),
  KEY `IDX_23A0E6645F4E028` (`spotlight_user_id`),
  KEY `IDX_23A0E66F675F31B` (`author_id`),
  CONSTRAINT `FK_23A0E6645F4E028` FOREIGN KEY (`spotlight_user_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_23A0E6688C5F785` FOREIGN KEY (`article_category_id`) REFERENCES `article_category` (`id`),
  CONSTRAINT `FK_23A0E66F675F31B` FOREIGN KEY (`author_id`) REFERENCES `author` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `article`
--

LOCK TABLES `article` WRITE;
/*!40000 ALTER TABLE `article` DISABLE KEYS */;
/*!40000 ALTER TABLE `article` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `article_category`
--

DROP TABLE IF EXISTS `article_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `article_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `sort_order` int(11) NOT NULL,
  `display` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `article_category`
--

LOCK TABLES `article_category` WRITE;
/*!40000 ALTER TABLE `article_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `article_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `article_image`
--

DROP TABLE IF EXISTS `article_image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `article_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `article_image`
--

LOCK TABLES `article_image` WRITE;
/*!40000 ALTER TABLE `article_image` DISABLE KEYS */;
/*!40000 ALTER TABLE `article_image` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `author`
--

DROP TABLE IF EXISTS `author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `author` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `bio` longtext COLLATE utf8_unicode_ci NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `author`
--

LOCK TABLES `author` WRITE;
/*!40000 ALTER TABLE `author` DISABLE KEYS */;
/*!40000 ALTER TABLE `author` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `counter`
--

DROP TABLE IF EXISTS `counter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `counter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `date` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `count` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C1229478586DFF2` (`user_info_id`),
  CONSTRAINT `FK_C1229478586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `counter`
--

LOCK TABLES `counter` WRITE;
/*!40000 ALTER TABLE `counter` DISABLE KEYS */;
/*!40000 ALTER TABLE `counter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_change_request`
--

DROP TABLE IF EXISTS `email_change_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_change_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `unique_key` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_136F31FFE7927C74` (`email`),
  UNIQUE KEY `UNIQ_136F31FF586DFF2` (`user_info_id`),
  CONSTRAINT `FK_136F31FF586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_change_request`
--

LOCK TABLES `email_change_request` WRITE;
/*!40000 ALTER TABLE `email_change_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_change_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `engine_order`
--

DROP TABLE IF EXISTS `engine_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `engine_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `engine_product_id` int(11) DEFAULT NULL,
  `uid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `amount` int(11) NOT NULL,
  `fee` int(11) NOT NULL,
  `notes` longtext COLLATE utf8_unicode_ci,
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D84147D3586DFF2` (`user_info_id`),
  KEY `IDX_D84147D3A57FE1BB` (`engine_product_id`),
  CONSTRAINT `FK_D84147D3586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_D84147D3A57FE1BB` FOREIGN KEY (`engine_product_id`) REFERENCES `engine_product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `engine_order`
--

LOCK TABLES `engine_order` WRITE;
/*!40000 ALTER TABLE `engine_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `engine_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `engine_order_asset`
--

DROP TABLE IF EXISTS `engine_order_asset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `engine_order_asset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `engine_order_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dropbox_link` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mime_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_56DC07C8586DFF2` (`user_info_id`),
  KEY `IDX_56DC07C8576DE645` (`engine_order_id`),
  CONSTRAINT `FK_56DC07C8576DE645` FOREIGN KEY (`engine_order_id`) REFERENCES `engine_order` (`id`),
  CONSTRAINT `FK_56DC07C8586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `engine_order_asset`
--

LOCK TABLES `engine_order_asset` WRITE;
/*!40000 ALTER TABLE `engine_order_asset` DISABLE KEYS */;
/*!40000 ALTER TABLE `engine_order_asset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `engine_product`
--

DROP TABLE IF EXISTS `engine_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `engine_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `amount` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL,
  `pro_only` tinyint(1) NOT NULL,
  `sort_order` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `engine_product`
--

LOCK TABLES `engine_product` WRITE;
/*!40000 ALTER TABLE `engine_product` DISABLE KEYS */;
/*!40000 ALTER TABLE `engine_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `entry_vote`
--

DROP TABLE IF EXISTS `entry_vote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entry_vote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_bid_id` int(11) DEFAULT NULL,
  `ip_addr` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `browser` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_FE32FD77586DFF2` (`user_info_id`),
  KEY `IDX_FE32FD775A3C8DF2` (`project_bid_id`),
  CONSTRAINT `FK_FE32FD77586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_FE32FD775A3C8DF2` FOREIGN KEY (`project_bid_id`) REFERENCES `project_bid` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entry_vote`
--

LOCK TABLES `entry_vote` WRITE;
/*!40000 ALTER TABLE `entry_vote` DISABLE KEYS */;
/*!40000 ALTER TABLE `entry_vote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `genre`
--

DROP TABLE IF EXISTS `genre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `genre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genre`
--

LOCK TABLES `genre` WRITE;
/*!40000 ALTER TABLE `genre` DISABLE KEYS */;
INSERT INTO `genre` VALUES (1,'Electronica'),(2,'Progressive House'),(3,'Trance'),(4,'Tech'),(5,'Techno'),(6,'Electro'),(7,'Drum N Bass'),(8,'House'),(9,'Dubstep'),(10,'Chill Out'),(11,'Hardcore'),(12,'Indie Dance'),(13,'Nu Disco'),(14,'Trap'),(15,'Funk'),(16,'RnB'),(17,'Hip Hop'),(18,'Rap'),(19,'Rock'),(20,'Heavey Metal'),(21,'Prog Rock'),(22,'Country / Western'),(23,'Indie Rock'),(24,'Punk'),(25,'Pop'),(26,'Blues'),(27,'Soul'),(28,'Opera'),(29,'Reggae'),(30,'Jazz'),(31,'Hard Rock'),(32,'Folk'),(33,'Classical'),(34,'Latin'),(35,'Breaks');
/*!40000 ALTER TABLE `genre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `language`
--

DROP TABLE IF EXISTS `language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `language` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `language`
--

LOCK TABLES `language` WRITE;
/*!40000 ALTER TABLE `language` DISABLE KEYS */;
INSERT INTO `language` VALUES (1,'English'),(2,'Spanish'),(3,'French'),(4,'Dutch'),(5,'Italian'),(6,'Mandarin'),(7,'Japanese'),(8,'South Korean');
/*!40000 ALTER TABLE `language` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `language_project`
--

DROP TABLE IF EXISTS `language_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `language_project` (
  `language_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  PRIMARY KEY (`language_id`,`project_id`),
  KEY `IDX_8B7E07BA82F1BAF4` (`language_id`),
  KEY `IDX_8B7E07BA166D1F9C` (`project_id`),
  CONSTRAINT `FK_8B7E07BA166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_8B7E07BA82F1BAF4` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `language_project`
--

LOCK TABLES `language_project` WRITE;
/*!40000 ALTER TABLE `language_project` DISABLE KEYS */;
/*!40000 ALTER TABLE `language_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mag_user`
--

DROP TABLE IF EXISTS `mag_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mag_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `uid` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `unsubscribe_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6843FF586DFF2` (`user_info_id`),
  CONSTRAINT `FK_6843FF586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mag_user`
--

LOCK TABLES `mag_user` WRITE;
/*!40000 ALTER TABLE `mag_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `mag_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marketplace_item`
--

DROP TABLE IF EXISTS `marketplace_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marketplace_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `approved_by_id` int(11) DEFAULT NULL,
  `uuid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `status_reason` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `additional_info` longtext COLLATE utf8_unicode_ci,
  `item_type` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `bpm` int(11) DEFAULT NULL,
  `audio_key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `is_auction` tinyint(1) DEFAULT NULL,
  `has_assets` tinyint(1) DEFAULT NULL,
  `bids_due` date DEFAULT NULL,
  `num_bids` int(11) NOT NULL,
  `buyout_price` int(11) DEFAULT NULL,
  `reserve_price` int(11) DEFAULT NULL,
  `royalty_master` int(11) DEFAULT NULL,
  `royalty_publishing` int(11) DEFAULT NULL,
  `royalty_mechanical` int(11) DEFAULT NULL,
  `royalty_performance` int(11) DEFAULT NULL,
  `gender` varchar(6) COLLATE utf8_unicode_ci DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `approved` tinyint(1) NOT NULL,
  `approved_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D600F78586DFF2` (`user_info_id`),
  KEY `IDX_D600F782D234F6A` (`approved_by_id`),
  KEY `title_idx` (`title`),
  KEY `published_at_idx` (`published_at`),
  CONSTRAINT `FK_D600F782D234F6A` FOREIGN KEY (`approved_by_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_D600F78586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketplace_item`
--

LOCK TABLES `marketplace_item` WRITE;
/*!40000 ALTER TABLE `marketplace_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `marketplace_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marketplace_item_asset`
--

DROP TABLE IF EXISTS `marketplace_item_asset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marketplace_item_asset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `marketplace_item_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `preview_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dropbox_link` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mime_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `downloaded` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DE48CAED586DFF2` (`user_info_id`),
  KEY `IDX_DE48CAEDF6898142` (`marketplace_item_id`),
  CONSTRAINT `FK_DE48CAED586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_DE48CAEDF6898142` FOREIGN KEY (`marketplace_item_id`) REFERENCES `marketplace_item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketplace_item_asset`
--

LOCK TABLES `marketplace_item_asset` WRITE;
/*!40000 ALTER TABLE `marketplace_item_asset` DISABLE KEYS */;
/*!40000 ALTER TABLE `marketplace_item_asset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marketplace_item_audio`
--

DROP TABLE IF EXISTS `marketplace_item_audio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marketplace_item_audio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `marketplace_item_id` int(11) DEFAULT NULL,
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flag` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `wave_generated` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C49AA624586DFF2` (`user_info_id`),
  KEY `IDX_C49AA624F6898142` (`marketplace_item_id`),
  CONSTRAINT `FK_C49AA624586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_C49AA624F6898142` FOREIGN KEY (`marketplace_item_id`) REFERENCES `marketplace_item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketplace_item_audio`
--

LOCK TABLES `marketplace_item_audio` WRITE;
/*!40000 ALTER TABLE `marketplace_item_audio` DISABLE KEYS */;
/*!40000 ALTER TABLE `marketplace_item_audio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marketplace_item_genre`
--

DROP TABLE IF EXISTS `marketplace_item_genre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marketplace_item_genre` (
  `marketplace_item_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL,
  PRIMARY KEY (`marketplace_item_id`,`genre_id`),
  KEY `IDX_5FB7A349F6898142` (`marketplace_item_id`),
  KEY `IDX_5FB7A3494296D31F` (`genre_id`),
  CONSTRAINT `FK_5FB7A3494296D31F` FOREIGN KEY (`genre_id`) REFERENCES `genre` (`id`),
  CONSTRAINT `FK_5FB7A349F6898142` FOREIGN KEY (`marketplace_item_id`) REFERENCES `marketplace_item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketplace_item_genre`
--

LOCK TABLES `marketplace_item_genre` WRITE;
/*!40000 ALTER TABLE `marketplace_item_genre` DISABLE KEYS */;
/*!40000 ALTER TABLE `marketplace_item_genre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_thread_id` int(11) DEFAULT NULL,
  `user_info_id` int(11) DEFAULT NULL,
  `to_user_info_id` int(11) DEFAULT NULL,
  `uuid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B6BD307F8829462F` (`message_thread_id`),
  KEY `IDX_B6BD307F586DFF2` (`user_info_id`),
  KEY `IDX_B6BD307FD1137C2E` (`to_user_info_id`),
  CONSTRAINT `FK_B6BD307F586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_B6BD307F8829462F` FOREIGN KEY (`message_thread_id`) REFERENCES `message_thread` (`id`),
  CONSTRAINT `FK_B6BD307FD1137C2E` FOREIGN KEY (`to_user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message`
--

LOCK TABLES `message` WRITE;
/*!40000 ALTER TABLE `message` DISABLE KEYS */;
/*!40000 ALTER TABLE `message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_file`
--

DROP TABLE IF EXISTS `message_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `message_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dropbox_link` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `filesize` int(11) NOT NULL,
  `mime_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `downloaded` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_250AADC9586DFF2` (`user_info_id`),
  KEY `IDX_250AADC9166D1F9C` (`project_id`),
  KEY `IDX_250AADC9537A1329` (`message_id`),
  CONSTRAINT `FK_250AADC9166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_250AADC9537A1329` FOREIGN KEY (`message_id`) REFERENCES `message` (`id`),
  CONSTRAINT `FK_250AADC9586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_file`
--

LOCK TABLES `message_file` WRITE;
/*!40000 ALTER TABLE `message_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_thread`
--

DROP TABLE IF EXISTS `message_thread`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_thread` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employer_id` int(11) DEFAULT NULL,
  `bidder_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `uuid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `num_employer_unread` int(11) NOT NULL,
  `employer_last_read` datetime DEFAULT NULL,
  `num_bidder_unread` int(11) NOT NULL,
  `bidder_last_read` datetime DEFAULT NULL,
  `is_open` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `last_message_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_607D18C41CD9E7A` (`employer_id`),
  KEY `IDX_607D18CBE40AFAE` (`bidder_id`),
  KEY `IDX_607D18C166D1F9C` (`project_id`),
  CONSTRAINT `FK_607D18C166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_607D18C41CD9E7A` FOREIGN KEY (`employer_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_607D18CBE40AFAE` FOREIGN KEY (`bidder_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_thread`
--

LOCK TABLES `message_thread` WRITE;
/*!40000 ALTER TABLE `message_thread` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_thread` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `actioned_user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `user_audio_id` int(11) DEFAULT NULL,
  `notify_type` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  `notify_read` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BF5476CA586DFF2` (`user_info_id`),
  KEY `IDX_BF5476CAABF1F0F6` (`actioned_user_info_id`),
  KEY `IDX_BF5476CA166D1F9C` (`project_id`),
  KEY `IDX_BF5476CADABCC7C7` (`user_audio_id`),
  CONSTRAINT `FK_BF5476CA166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_BF5476CA586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_BF5476CAABF1F0F6` FOREIGN KEY (`actioned_user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_BF5476CADABCC7C7` FOREIGN KEY (`user_audio_id`) REFERENCES `user_audio` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification`
--

LOCK TABLES `notification` WRITE;
/*!40000 ALTER TABLE `notification` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paypal_transaction`
--

DROP TABLE IF EXISTS `paypal_transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paypal_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `txn_type` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `ipn_track_id` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `txn_id` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subscr_id` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `payer_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `payment_gross` decimal(9,3) NOT NULL,
  `amount` decimal(9,3) NOT NULL,
  `verified` tinyint(1) NOT NULL,
  `raw` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paypal_transaction`
--

LOCK TABLES `paypal_transaction` WRITE;
/*!40000 ALTER TABLE `paypal_transaction` DISABLE KEYS */;
/*!40000 ALTER TABLE `paypal_transaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project`
--

DROP TABLE IF EXISTS `project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `employee_user_info_id` int(11) DEFAULT NULL,
  `project_bid_id` int(11) DEFAULT NULL,
  `user_transaction_id` int(11) DEFAULT NULL,
  `hire_user_id` int(11) DEFAULT NULL,
  `project_escrow_id` int(11) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `uuid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `awarded_at` datetime DEFAULT NULL,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `project_type` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `lyrics` longtext COLLATE utf8_unicode_ci,
  `due_date` date DEFAULT NULL,
  `bids_due` date DEFAULT NULL,
  `num_bids` int(11) NOT NULL,
  `bid_total` int(11) NOT NULL,
  `last_bid_at` datetime DEFAULT NULL,
  `gender` varchar(6) COLLATE utf8_unicode_ci DEFAULT NULL,
  `studio_access` tinyint(1) DEFAULT NULL,
  `pro_required` tinyint(1) DEFAULT NULL,
  `budget_from` int(11) DEFAULT NULL,
  `budget_to` int(11) DEFAULT NULL,
  `royalty_mechanical` tinyint(1) DEFAULT NULL,
  `royalty_performance` tinyint(1) DEFAULT NULL,
  `royalty` int(11) DEFAULT NULL,
  `looking_for` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_lat` double DEFAULT NULL,
  `location_lng` double DEFAULT NULL,
  `enable_gig_hunter` datetime DEFAULT NULL,
  `publish_type` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `to_favorites` tinyint(1) NOT NULL,
  `show_in_news` tinyint(1) NOT NULL,
  `restrict_to_preferences` tinyint(1) DEFAULT NULL,
  `highlight` tinyint(1) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL,
  `featured_at` datetime DEFAULT NULL,
  `fees` int(11) NOT NULL,
  `bpm` varchar(11) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  `is_complete` tinyint(1) NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `employer_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `employee_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `prompt_assets` tinyint(1) NOT NULL,
  `last_activity` longtext COLLATE utf8_unicode_ci,
  `employer_read_at` datetime DEFAULT NULL,
  `employee_read_at` datetime DEFAULT NULL,
  `audio_brief` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `audio_brief_click` int(11) NOT NULL,
  `sfs` tinyint(1) DEFAULT NULL,
  `public_voting` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2FB3D0EE4B209DC8` (`project_escrow_id`),
  KEY `IDX_2FB3D0EE586DFF2` (`user_info_id`),
  KEY `IDX_2FB3D0EE2BB3F4B2` (`employee_user_info_id`),
  KEY `IDX_2FB3D0EE5A3C8DF2` (`project_bid_id`),
  KEY `IDX_2FB3D0EE44451456` (`user_transaction_id`),
  KEY `IDX_2FB3D0EE2C5E08EB` (`hire_user_id`),
  KEY `IDX_2FB3D0EE82F1BAF4` (`language_id`),
  KEY `title_idx` (`title`),
  KEY `published_at_idx` (`published_at`),
  CONSTRAINT `FK_2FB3D0EE2BB3F4B2` FOREIGN KEY (`employee_user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_2FB3D0EE2C5E08EB` FOREIGN KEY (`hire_user_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_2FB3D0EE44451456` FOREIGN KEY (`user_transaction_id`) REFERENCES `user_transaction` (`id`),
  CONSTRAINT `FK_2FB3D0EE4B209DC8` FOREIGN KEY (`project_escrow_id`) REFERENCES `project_escrow` (`id`),
  CONSTRAINT `FK_2FB3D0EE586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_2FB3D0EE5A3C8DF2` FOREIGN KEY (`project_bid_id`) REFERENCES `project_bid` (`id`),
  CONSTRAINT `FK_2FB3D0EE82F1BAF4` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project`
--

LOCK TABLES `project` WRITE;
/*!40000 ALTER TABLE `project` DISABLE KEYS */;
/*!40000 ALTER TABLE `project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_activity`
--

DROP TABLE IF EXISTS `project_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `actioned_user_info_id` int(11) DEFAULT NULL,
  `activity_type` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `activity_read` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_913A8281586DFF2` (`user_info_id`),
  KEY `IDX_913A8281166D1F9C` (`project_id`),
  KEY `IDX_913A8281ABF1F0F6` (`actioned_user_info_id`),
  CONSTRAINT `FK_913A8281166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_913A8281586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_913A8281ABF1F0F6` FOREIGN KEY (`actioned_user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_activity`
--

LOCK TABLES `project_activity` WRITE;
/*!40000 ALTER TABLE `project_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_asset`
--

DROP TABLE IF EXISTS `project_asset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_asset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `preview_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dropbox_link` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mime_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `downloaded` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_11FA53C2586DFF2` (`user_info_id`),
  KEY `IDX_11FA53C2166D1F9C` (`project_id`),
  CONSTRAINT `FK_11FA53C2166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_11FA53C2586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_asset`
--

LOCK TABLES `project_asset` WRITE;
/*!40000 ALTER TABLE `project_asset` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_asset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_audio`
--

DROP TABLE IF EXISTS `project_audio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_audio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flag` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `wave_generated` tinyint(1) NOT NULL,
  `download_count` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B283F0B586DFF2` (`user_info_id`),
  KEY `IDX_B283F0B166D1F9C` (`project_id`),
  CONSTRAINT `FK_B283F0B166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_B283F0B586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_audio`
--

LOCK TABLES `project_audio` WRITE;
/*!40000 ALTER TABLE `project_audio` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_audio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_audio_download`
--

DROP TABLE IF EXISTS `project_audio_download`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_audio_download` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_audio_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1EF79F24586DFF2` (`user_info_id`),
  KEY `IDX_1EF79F2477FA81C` (`project_audio_id`),
  CONSTRAINT `FK_1EF79F24586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_1EF79F2477FA81C` FOREIGN KEY (`project_audio_id`) REFERENCES `project_audio` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_audio_download`
--

LOCK TABLES `project_audio_download` WRITE;
/*!40000 ALTER TABLE `project_audio_download` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_audio_download` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_bid`
--

DROP TABLE IF EXISTS `project_bid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_bid` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `uuid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `message` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `hidden` tinyint(1) NOT NULL,
  `shortlist` tinyint(1) NOT NULL,
  `flag` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flag_comment` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `payment_percent_taken` int(11) DEFAULT NULL,
  `wave_generated` tinyint(1) NOT NULL,
  `vote_count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D8896910586DFF2` (`user_info_id`),
  KEY `IDX_D8896910166D1F9C` (`project_id`),
  CONSTRAINT `FK_D8896910166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_D8896910586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_bid`
--

LOCK TABLES `project_bid` WRITE;
/*!40000 ALTER TABLE `project_bid` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_bid` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_comment`
--

DROP TABLE IF EXISTS `project_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `project_audio_id` int(11) DEFAULT NULL,
  `from_id` int(11) DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_26A5E09166D1F9C` (`project_id`),
  KEY `IDX_26A5E0977FA81C` (`project_audio_id`),
  KEY `IDX_26A5E0978CED90B` (`from_id`),
  CONSTRAINT `FK_26A5E09166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_26A5E0977FA81C` FOREIGN KEY (`project_audio_id`) REFERENCES `project_audio` (`id`),
  CONSTRAINT `FK_26A5E0978CED90B` FOREIGN KEY (`from_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_comment`
--

LOCK TABLES `project_comment` WRITE;
/*!40000 ALTER TABLE `project_comment` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_comment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_contract`
--

DROP TABLE IF EXISTS `project_contract`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_contract` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D4C1A382586DFF2` (`user_info_id`),
  KEY `IDX_D4C1A382166D1F9C` (`project_id`),
  CONSTRAINT `FK_D4C1A382166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_D4C1A382586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_contract`
--

LOCK TABLES `project_contract` WRITE;
/*!40000 ALTER TABLE `project_contract` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_contract` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_dispute`
--

DROP TABLE IF EXISTS `project_dispute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_dispute` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `from_user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `reason` longtext COLLATE utf8_unicode_ci NOT NULL,
  `accepted` tinyint(1) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AA8C5C62586DFF2` (`user_info_id`),
  KEY `IDX_AA8C5C6238C00514` (`from_user_info_id`),
  KEY `IDX_AA8C5C62166D1F9C` (`project_id`),
  CONSTRAINT `FK_AA8C5C62166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_AA8C5C6238C00514` FOREIGN KEY (`from_user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_AA8C5C62586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_dispute`
--

LOCK TABLES `project_dispute` WRITE;
/*!40000 ALTER TABLE `project_dispute` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_dispute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_escrow`
--

DROP TABLE IF EXISTS `project_escrow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_escrow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_bid_id` int(11) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `fee` int(11) NOT NULL,
  `contractor_fee` int(11) NOT NULL,
  `released_date` datetime DEFAULT NULL,
  `refunded` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_12CF393B586DFF2` (`user_info_id`),
  KEY `IDX_12CF393B5A3C8DF2` (`project_bid_id`),
  CONSTRAINT `FK_12CF393B586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_12CF393B5A3C8DF2` FOREIGN KEY (`project_bid_id`) REFERENCES `project_bid` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_escrow`
--

LOCK TABLES `project_escrow` WRITE;
/*!40000 ALTER TABLE `project_escrow` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_escrow` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_feed`
--

DROP TABLE IF EXISTS `project_feed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_feed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `from_user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `object_type` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `object_id` int(11) DEFAULT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `feed_read` tinyint(1) NOT NULL,
  `notified` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1AD18CB3586DFF2` (`user_info_id`),
  KEY `IDX_1AD18CB338C00514` (`from_user_info_id`),
  KEY `IDX_1AD18CB3166D1F9C` (`project_id`),
  CONSTRAINT `FK_1AD18CB3166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_1AD18CB338C00514` FOREIGN KEY (`from_user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_1AD18CB3586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_feed`
--

LOCK TABLES `project_feed` WRITE;
/*!40000 ALTER TABLE `project_feed` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_feed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_file`
--

DROP TABLE IF EXISTS `project_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `project_comment_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dropbox_link` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mime_type` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `filesize` int(11) NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `downloaded` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B50EFE08586DFF2` (`user_info_id`),
  KEY `IDX_B50EFE08166D1F9C` (`project_id`),
  KEY `IDX_B50EFE08E0CF0621` (`project_comment_id`),
  CONSTRAINT `FK_B50EFE08166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_B50EFE08586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_B50EFE08E0CF0621` FOREIGN KEY (`project_comment_id`) REFERENCES `project_comment` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_file`
--

LOCK TABLES `project_file` WRITE;
/*!40000 ALTER TABLE `project_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_genre`
--

DROP TABLE IF EXISTS `project_genre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_genre` (
  `project_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL,
  PRIMARY KEY (`project_id`,`genre_id`),
  KEY `IDX_90053A66166D1F9C` (`project_id`),
  KEY `IDX_90053A664296D31F` (`genre_id`),
  CONSTRAINT `FK_90053A66166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_90053A664296D31F` FOREIGN KEY (`genre_id`) REFERENCES `genre` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_genre`
--

LOCK TABLES `project_genre` WRITE;
/*!40000 ALTER TABLE `project_genre` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_genre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_invite`
--

DROP TABLE IF EXISTS `project_invite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_invite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D046FB9D586DFF2` (`user_info_id`),
  KEY `IDX_D046FB9D166D1F9C` (`project_id`),
  CONSTRAINT `FK_D046FB9D166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_D046FB9D586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_invite`
--

LOCK TABLES `project_invite` WRITE;
/*!40000 ALTER TABLE `project_invite` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_invite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_lyrics`
--

DROP TABLE IF EXISTS `project_lyrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_lyrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `lyrics` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2C7E872C586DFF2` (`user_info_id`),
  KEY `IDX_2C7E872C166D1F9C` (`project_id`),
  CONSTRAINT `FK_2C7E872C166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_2C7E872C586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_lyrics`
--

LOCK TABLES `project_lyrics` WRITE;
/*!40000 ALTER TABLE `project_lyrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_lyrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_message`
--

DROP TABLE IF EXISTS `project_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `from_id` int(11) DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `public` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_20A33C1A166D1F9C` (`project_id`),
  KEY `IDX_20A33C1A78CED90B` (`from_id`),
  CONSTRAINT `FK_20A33C1A166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_20A33C1A78CED90B` FOREIGN KEY (`from_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_message`
--

LOCK TABLES `project_message` WRITE;
/*!40000 ALTER TABLE `project_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_upgrade`
--

DROP TABLE IF EXISTS `project_upgrade`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_upgrade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `upgrade` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `amount` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2178787F586DFF2` (`user_info_id`),
  KEY `IDX_2178787F166D1F9C` (`project_id`),
  CONSTRAINT `FK_2178787F166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_2178787F586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_upgrade`
--

LOCK TABLES `project_upgrade` WRITE;
/*!40000 ALTER TABLE `project_upgrade` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_upgrade` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_user`
--

DROP TABLE IF EXISTS `project_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `role` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `project_owner` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B4021E51586DFF2` (`user_info_id`),
  CONSTRAINT `FK_B4021E51586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_user`
--

LOCK TABLES `project_user` WRITE;
/*!40000 ALTER TABLE `project_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_vocal_characteristics`
--

DROP TABLE IF EXISTS `project_vocal_characteristics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_vocal_characteristics` (
  `project_id` int(11) NOT NULL,
  `vocal_characteristic_id` int(11) NOT NULL,
  PRIMARY KEY (`project_id`,`vocal_characteristic_id`),
  KEY `IDX_4DC9276A166D1F9C` (`project_id`),
  KEY `IDX_4DC9276AAB3A6FD2` (`vocal_characteristic_id`),
  CONSTRAINT `FK_4DC9276A166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_4DC9276AAB3A6FD2` FOREIGN KEY (`vocal_characteristic_id`) REFERENCES `vocal_characteristic` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_vocal_characteristics`
--

LOCK TABLES `project_vocal_characteristics` WRITE;
/*!40000 ALTER TABLE `project_vocal_characteristics` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_vocal_characteristics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_vocal_styles`
--

DROP TABLE IF EXISTS `project_vocal_styles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_vocal_styles` (
  `project_id` int(11) NOT NULL,
  `vocal_style_id` int(11) NOT NULL,
  PRIMARY KEY (`project_id`,`vocal_style_id`),
  KEY `IDX_7DA0398E166D1F9C` (`project_id`),
  KEY `IDX_7DA0398E9DDCAC1B` (`vocal_style_id`),
  CONSTRAINT `FK_7DA0398E166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_7DA0398E9DDCAC1B` FOREIGN KEY (`vocal_style_id`) REFERENCES `vocal_style` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_vocal_styles`
--

LOCK TABLES `project_vocal_styles` WRITE;
/*!40000 ALTER TABLE `project_vocal_styles` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_vocal_styles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reset_pass_request`
--

DROP TABLE IF EXISTS `reset_pass_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reset_pass_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `unique_key` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_3D7F1191586DFF2` (`user_info_id`),
  CONSTRAINT `FK_3D7F1191586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reset_pass_request`
--

LOCK TABLES `reset_pass_request` WRITE;
/*!40000 ALTER TABLE `reset_pass_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `reset_pass_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search`
--

DROP TABLE IF EXISTS `search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `search_term` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `num_results` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B4F0DBA7586DFF2` (`user_info_id`),
  CONSTRAINT `FK_B4F0DBA7586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search`
--

LOCK TABLES `search` WRITE;
/*!40000 ALTER TABLE `search` DISABLE KEYS */;
/*!40000 ALTER TABLE `search` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statistics`
--

DROP TABLE IF EXISTS `statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `statistics_type` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `users` int(11) NOT NULL,
  `vocalists` int(11) NOT NULL,
  `producers` int(11) NOT NULL,
  `gigs` int(11) NOT NULL,
  `published_gigs` int(11) NOT NULL,
  `public_published_gigs` int(11) NOT NULL,
  `private_published_gigs` int(11) NOT NULL,
  `awarded_gigs` int(11) NOT NULL,
  `public_awarded_gigs` int(11) NOT NULL,
  `private_awarded_gigs` int(11) NOT NULL,
  `completed_gigs` int(11) NOT NULL,
  `public_completed_gigs` int(11) NOT NULL,
  `private_completed_gigs` int(11) NOT NULL,
  `revenue` int(11) NOT NULL,
  `bids` int(11) NOT NULL,
  `messages` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type_idx` (`statistics_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statistics`
--

LOCK TABLES `statistics` WRITE;
/*!40000 ALTER TABLE `statistics` DISABLE KEYS */;
/*!40000 ALTER TABLE `statistics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stripe_charge`
--

DROP TABLE IF EXISTS `stripe_charge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stripe_charge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` int(11) NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stripe_charge`
--

LOCK TABLES `stripe_charge` WRITE;
/*!40000 ALTER TABLE `stripe_charge` DISABLE KEYS */;
/*!40000 ALTER TABLE `stripe_charge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_plan`
--

DROP TABLE IF EXISTS `subscription_plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `price` int(11) NOT NULL,
  `user_audio_limit` int(11) DEFAULT NULL,
  `project_percent_added` int(11) NOT NULL,
  `payment_percent_taken` int(11) NOT NULL,
  `project_private_fee` int(11) NOT NULL,
  `project_highlight_fee` int(11) NOT NULL,
  `project_feature_fee` int(11) NOT NULL,
  `project_announce_fee` int(11) NOT NULL,
  `connect_month_limit` int(11) NOT NULL DEFAULT '5',
  `message_month_limit` int(11) DEFAULT '5',
  `static_key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `unique_key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `hidden` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_plan`
--

LOCK TABLES `subscription_plan` WRITE;
/*!40000 ALTER TABLE `subscription_plan` DISABLE KEYS */;
INSERT INTO `subscription_plan` VALUES (1,'Free Membership','Free Membership',0,2,3,10,5,10,10,10,5,5,'FREE','0bea7c3810e6bb2cc440b33a850dbf05',NULL,0,'2018-05-08 09:47:15');
/*!40000 ALTER TABLE `subscription_plan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_audio`
--

DROP TABLE IF EXISTS `user_audio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_audio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `sc_user_track_id` int(11) DEFAULT NULL,
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `default_audio` tinyint(1) NOT NULL,
  `sc_id` int(11) DEFAULT NULL,
  `sc_synced` tinyint(1) NOT NULL,
  `sc_sync_start` datetime DEFAULT NULL,
  `sc_sync_finished` datetime DEFAULT NULL,
  `sc_permalink_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sc_stream_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sc_download_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sc_raw` longtext COLLATE utf8_unicode_ci,
  `play_count` int(11) NOT NULL,
  `total_likes` int(11) NOT NULL,
  `sc_upload_queued` int(11) NOT NULL,
  `sc_upload_result` int(11) DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `wave_generated` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_FABFCDCD586DFF2` (`user_info_id`),
  KEY `IDX_FABFCDCD89E2828E` (`sc_user_track_id`),
  CONSTRAINT `FK_FABFCDCD586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_FABFCDCD89E2828E` FOREIGN KEY (`sc_user_track_id`) REFERENCES `user_audio` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_audio`
--

LOCK TABLES `user_audio` WRITE;
/*!40000 ALTER TABLE `user_audio` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_audio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_block`
--

DROP TABLE IF EXISTS `user_block`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_block` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `block_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_61D96C7A586DFF2` (`user_info_id`),
  KEY `IDX_61D96C7ADD4D276B` (`block_user_id`),
  CONSTRAINT `FK_61D96C7A586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_61D96C7ADD4D276B` FOREIGN KEY (`block_user_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_block`
--

LOCK TABLES `user_block` WRITE;
/*!40000 ALTER TABLE `user_block` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_block` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_cancel_sub`
--

DROP TABLE IF EXISTS `user_cancel_sub`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_cancel_sub` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `reason` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7ACC2060586DFF2` (`user_info_id`),
  CONSTRAINT `FK_7ACC2060586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_cancel_sub`
--

LOCK TABLES `user_cancel_sub` WRITE;
/*!40000 ALTER TABLE `user_cancel_sub` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_cancel_sub` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_connect`
--

DROP TABLE IF EXISTS `user_connect`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_connect` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to_id` int(11) DEFAULT NULL,
  `from_id` int(11) DEFAULT NULL,
  `engaged` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2CC2E71530354A65` (`to_id`),
  KEY `IDX_2CC2E71578CED90B` (`from_id`),
  CONSTRAINT `FK_2CC2E71530354A65` FOREIGN KEY (`to_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_2CC2E71578CED90B` FOREIGN KEY (`from_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_connect`
--

LOCK TABLES `user_connect` WRITE;
/*!40000 ALTER TABLE `user_connect` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_connect` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_connect_invite`
--

DROP TABLE IF EXISTS `user_connect_invite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_connect_invite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to_id` int(11) DEFAULT NULL,
  `from_id` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `message` longtext COLLATE utf8_unicode_ci,
  `connected_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7947C43A30354A65` (`to_id`),
  KEY `IDX_7947C43A78CED90B` (`from_id`),
  CONSTRAINT `FK_7947C43A30354A65` FOREIGN KEY (`to_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_7947C43A78CED90B` FOREIGN KEY (`from_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_connect_invite`
--

LOCK TABLES `user_connect_invite` WRITE;
/*!40000 ALTER TABLE `user_connect_invite` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_connect_invite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_favorite`
--

DROP TABLE IF EXISTS `user_favorite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_favorite` (
  `user_info_id` int(11) NOT NULL,
  `favorite_user_info_id` int(11) NOT NULL,
  PRIMARY KEY (`user_info_id`,`favorite_user_info_id`),
  KEY `IDX_88486AD9586DFF2` (`user_info_id`),
  KEY `IDX_88486AD9EA90DD9D` (`favorite_user_info_id`),
  CONSTRAINT `FK_88486AD9586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_88486AD9EA90DD9D` FOREIGN KEY (`favorite_user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_favorite`
--

LOCK TABLES `user_favorite` WRITE;
/*!40000 ALTER TABLE `user_favorite` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_favorite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_follow`
--

DROP TABLE IF EXISTS `user_follow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_follow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `follow_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D665F4D586DFF2` (`user_info_id`),
  KEY `IDX_D665F4DF99B8B25` (`follow_user_id`),
  CONSTRAINT `FK_D665F4D586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_D665F4DF99B8B25` FOREIGN KEY (`follow_user_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_follow`
--

LOCK TABLES `user_follow` WRITE;
/*!40000 ALTER TABLE `user_follow` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_follow` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_genre`
--

DROP TABLE IF EXISTS `user_genre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_genre` (
  `user_info_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL,
  PRIMARY KEY (`user_info_id`,`genre_id`),
  KEY `IDX_6192C8A0586DFF2` (`user_info_id`),
  KEY `IDX_6192C8A04296D31F` (`genre_id`),
  CONSTRAINT `FK_6192C8A04296D31F` FOREIGN KEY (`genre_id`) REFERENCES `genre` (`id`),
  CONSTRAINT `FK_6192C8A0586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_genre`
--

LOCK TABLES `user_genre` WRITE;
/*!40000 ALTER TABLE `user_genre` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_genre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_info`
--

DROP TABLE IF EXISTS `user_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_plan_id` int(11) DEFAULT NULL,
  `user_stat_id` int(11) DEFAULT NULL,
  `soundcloud_id` int(11) DEFAULT NULL,
  `soundcloud_access_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `soundcloud_set_id` int(11) DEFAULT NULL,
  `soundcloud_username` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `username` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `display_name` varchar(120) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `salt` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `avatar` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `profile` longtext COLLATE utf8_unicode_ci,
  `gender` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_lat` double DEFAULT NULL,
  `location_lng` double DEFAULT NULL,
  `studio_access` tinyint(1) DEFAULT NULL,
  `microphone` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `vocalist_fee` int(11) DEFAULT NULL,
  `producer_fee` int(11) DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `unique_str` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `wallet` int(11) NOT NULL,
  `date_registered` datetime NOT NULL,
  `completed_profile` datetime DEFAULT NULL,
  `is_producer` tinyint(1) NOT NULL,
  `is_vocalist` tinyint(1) NOT NULL,
  `is_songwriter` tinyint(1) NOT NULL,
  `email_confirmed` tinyint(1) NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `date_activated` datetime DEFAULT NULL,
  `referral_code` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `rating` decimal(9,2) NOT NULL,
  `rated_count` int(11) NOT NULL,
  `rating_total` int(11) NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `unread_project_activity` tinyint(1) NOT NULL,
  `unseen_project_invitation` tinyint(1) NOT NULL,
  `num_unread_messages` int(11) NOT NULL,
  `num_notifications` int(11) NOT NULL,
  `soundcloud_register` tinyint(1) NOT NULL,
  `is_admin` tinyint(1) NOT NULL,
  `is_certified` tinyint(1) NOT NULL,
  `stripe_cust_id` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `connect_count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_B1087D9E515D3101` (`user_stat_id`),
  KEY `IDX_B1087D9E9B8CE200` (`subscription_plan_id`),
  KEY `email_idx` (`email`),
  KEY `last_login_idx` (`last_login`),
  KEY `rating_idx` (`rating`,`rated_count`,`last_login`),
  KEY `date_registered_idx` (`date_registered`),
  CONSTRAINT `FK_B1087D9E515D3101` FOREIGN KEY (`user_stat_id`) REFERENCES `user_stat` (`id`),
  CONSTRAINT `FK_B1087D9E9B8CE200` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plan` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_info`
--

LOCK TABLES `user_info` WRITE;
/*!40000 ALTER TABLE `user_info` DISABLE KEYS */;
INSERT INTO `user_info` VALUES (1,NULL,NULL,NULL,NULL,NULL,NULL,'robert79',NULL,'Robert','Homewood','robert@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'au','Melbourne',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af1722319dbd9.39644275',0,'2018-05-08 09:47:15',NULL,1,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(2,NULL,NULL,NULL,NULL,NULL,NULL,'timberlake',NULL,'Justin','Timberlake','robert+1@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'us','Los Angeles',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af1722319e507.98494900',0,'2018-05-08 09:47:15',NULL,1,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(3,NULL,NULL,NULL,NULL,NULL,NULL,'jayz',NULL,'Jay','Z','robert+2@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'us','New York',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af1722319e807.66846222',0,'2018-05-08 09:47:15',NULL,1,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(4,NULL,NULL,NULL,NULL,NULL,NULL,'usher',NULL,'User','Raymond IV','robert+3@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'us','New York',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af1722319eab7.82902610',0,'2018-05-08 09:47:15',NULL,1,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(5,NULL,NULL,NULL,NULL,NULL,NULL,'beyonce',NULL,'Beyonce','Knowles','robert+4@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'f',NULL,'us','New York',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af1722319ed38.25108328',0,'2018-05-08 09:47:15',NULL,1,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(6,NULL,NULL,NULL,NULL,NULL,NULL,'georgiamuldrow',NULL,'Georgia Anne','Muldrow','robert+5@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'f',NULL,'us','Los Angeles',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af1722319efc2.25186594',0,'2018-05-08 09:47:15',NULL,1,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(7,NULL,NULL,NULL,NULL,NULL,NULL,'Dr Dre',NULL,'Andre Romelle','Young','robert+6@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'us','Los Angeles',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af1722319f259.63189697',0,'2018-05-08 09:47:15',NULL,1,0,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(8,NULL,NULL,NULL,NULL,NULL,NULL,'luke79',NULL,'Luke','Chable','luke@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'au','Melbourne',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af1722319f4d6.26188731',0,'2018-05-08 09:47:15',NULL,1,0,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(9,NULL,NULL,NULL,NULL,NULL,NULL,'TOKiMONSTA',NULL,'Jennifer','Lee','luke+1@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'f',NULL,'us','Los Angeles',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af1722319f752.07997730',0,'2018-05-08 09:47:15',NULL,1,0,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(10,NULL,NULL,NULL,NULL,NULL,NULL,'realDeahl',NULL,'Dani','Deahl','luke+2@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'f',NULL,'us','Chicago',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af1722319f9d4.91999909',0,'2018-05-08 09:47:15',NULL,1,0,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(11,NULL,NULL,NULL,NULL,NULL,NULL,'simco',NULL,'Kate','Simco','luke+3@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'f',NULL,'us','Chicago',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af1722319fc65.67346777',0,'2018-05-08 09:47:15',NULL,1,0,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(12,NULL,NULL,NULL,NULL,NULL,NULL,'MJ',NULL,'Maya Jane','Coles','luke+4@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'f',NULL,'us','Los Angeles',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af1722319fed2.82606097',0,'2018-05-08 09:47:15',NULL,1,0,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(13,NULL,NULL,NULL,NULL,NULL,NULL,'ButchV',NULL,'Butch','Vig','luke+5@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'us','Los Angeles',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a0144.05891609',0,'2018-05-08 09:47:15',NULL,1,0,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(14,NULL,NULL,NULL,NULL,NULL,NULL,'GM',NULL,'George','Martin','luke+6@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'us','Los Angeles',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a0396.63554706',0,'2018-05-08 09:47:15',NULL,1,0,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(15,NULL,NULL,NULL,NULL,NULL,NULL,'Eminem',NULL,'Marshall','Mathers','luke+7@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'us','Los Angeles',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a0604.43083532',0,'2018-05-08 09:47:15',NULL,1,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(16,NULL,NULL,NULL,NULL,NULL,NULL,'smythe',NULL,'John','Smythe','john@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'au','Melbourne',NULL,NULL,1,NULL,NULL,NULL,'2018-05-08 10:32:56','2018-05-08 10:32:56','u5af172231a0882.53177442',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(17,NULL,NULL,NULL,NULL,NULL,NULL,'lcullen',NULL,'Lena','Cullen','john+1@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'f',NULL,'us','New York',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a0af9.76752239',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(18,NULL,NULL,NULL,NULL,NULL,NULL,'jj',NULL,'Jake','Jenkins','john+2@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'us','Chicago',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a0dc6.40474219',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(19,NULL,NULL,NULL,NULL,NULL,NULL,'swifty',NULL,'Taylor','Swift','john+3@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'f',NULL,'us','New York',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a1023.81791692',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(20,NULL,NULL,NULL,NULL,NULL,NULL,'biebs',NULL,'Justin','Bieber','john+4@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'us','New York',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a1294.65555251',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(21,NULL,NULL,NULL,NULL,NULL,NULL,'gunner',NULL,'Axel','Rose','john+5@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'us','Washington',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a14f3.12618139',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(22,NULL,NULL,NULL,NULL,NULL,NULL,'lennon',NULL,'John','Lennon','john+5@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'uk','London',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a1751.66714678',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(23,NULL,NULL,NULL,NULL,NULL,NULL,'lily',NULL,'Lily','Allen','john+6@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'f',NULL,'uk','London',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a19d7.07747784',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(24,NULL,NULL,NULL,NULL,NULL,NULL,'lazza',NULL,'Larry','McNary','john+7@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'uk','Sussex',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a1c57.69808159',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(25,NULL,NULL,NULL,NULL,NULL,NULL,'Sia',NULL,'Sia','Furler','john+8@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'f',NULL,'au','Melbourne',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a1ea9.08956140',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(26,NULL,NULL,NULL,NULL,NULL,NULL,'NellyFurtado',NULL,'Nelly','Furtado','john+9@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'f',NULL,'us','San Francisco',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a2109.12449952',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(27,NULL,NULL,NULL,NULL,NULL,NULL,'Freddie',NULL,'Freddie','Mercury','john+10@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'uk','London',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a2372.80245675',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(28,NULL,NULL,NULL,NULL,NULL,NULL,'Fun',NULL,'Nate','Reusso','john+11@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'m',NULL,'us','Los Angeles',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a25d1.95901553',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0),(29,NULL,NULL,NULL,NULL,NULL,NULL,'Pink',NULL,'Alecia','Moore','john+12@vocalizr.com','bcd4be6dd9250d2a033dad618358f27eff4e3a61','95d76cdfcef0c9af572ab5d67d396da5',NULL,NULL,'f',NULL,'us','Los Angeles',NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,'u5af172231a2838.97538823',0,'2018-05-08 09:47:15',NULL,0,1,0,1,1,NULL,NULL,0.00,0,0,NULL,0,0,0,0,0,0,0,NULL,0);
/*!40000 ALTER TABLE `user_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_message`
--

DROP TABLE IF EXISTS `user_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_id` int(11) DEFAULT NULL,
  `to_id` int(11) DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_EEB02E7578CED90B` (`from_id`),
  KEY `IDX_EEB02E7530354A65` (`to_id`),
  CONSTRAINT `FK_EEB02E7530354A65` FOREIGN KEY (`to_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_EEB02E7578CED90B` FOREIGN KEY (`from_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_message`
--

LOCK TABLES `user_message` WRITE;
/*!40000 ALTER TABLE `user_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_payment`
--

DROP TABLE IF EXISTS `user_payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `user_subscription_id` int(11) DEFAULT NULL,
  `amount` decimal(9,3) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_35259A0788C4EB53` (`user_subscription_id`),
  KEY `IDX_35259A07586DFF2` (`user_info_id`),
  CONSTRAINT `FK_35259A07586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_35259A0788C4EB53` FOREIGN KEY (`user_subscription_id`) REFERENCES `user_subscription` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_payment`
--

LOCK TABLES `user_payment` WRITE;
/*!40000 ALTER TABLE `user_payment` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_payment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_pref`
--

DROP TABLE IF EXISTS `user_pref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_pref` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `email_project_digest` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `email_project_bids` tinyint(1) NOT NULL,
  `email_project_invites` tinyint(1) NOT NULL,
  `email_new_projects` tinyint(1) NOT NULL,
  `email_vocalist_suggestions` tinyint(1) NOT NULL,
  `activity_filter` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `email_messages` tinyint(1) NOT NULL DEFAULT '1',
  `email_connections` tinyint(1) NOT NULL DEFAULT '1',
  `email_tag_voting` tinyint(1) NOT NULL,
  `email_new_collabs` tinyint(1) NOT NULL,
  `connect_restrict_subscribed` tinyint(1) NOT NULL DEFAULT '0',
  `connect_restrict_certified` tinyint(1) NOT NULL DEFAULT '0',
  `connect_accept` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DBD4D4F8586DFF2` (`user_info_id`),
  CONSTRAINT `FK_DBD4D4F8586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_pref`
--

LOCK TABLES `user_pref` WRITE;
/*!40000 ALTER TABLE `user_pref` DISABLE KEYS */;
INSERT INTO `user_pref` VALUES (1,16,'instantly',1,1,1,1,'all',1,1,1,0,0,0,1,NULL);
/*!40000 ALTER TABLE `user_pref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_review`
--

DROP TABLE IF EXISTS `user_review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_review` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `reviewed_by_id` int(11) DEFAULT NULL,
  `rating` double NOT NULL,
  `quality_of_work` int(11) NOT NULL,
  `communication` int(11) NOT NULL,
  `professionalism` int(11) NOT NULL,
  `work_with_again` int(11) NOT NULL,
  `on_time` tinyint(1) DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `hide` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1C119AFB586DFF2` (`user_info_id`),
  KEY `IDX_1C119AFB166D1F9C` (`project_id`),
  KEY `IDX_1C119AFBFC6B21F1` (`reviewed_by_id`),
  CONSTRAINT `FK_1C119AFB166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_1C119AFB586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_1C119AFBFC6B21F1` FOREIGN KEY (`reviewed_by_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_review`
--

LOCK TABLES `user_review` WRITE;
/*!40000 ALTER TABLE `user_review` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_review` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sc_track`
--

DROP TABLE IF EXISTS `user_sc_track`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_sc_track` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `sc_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `permalink_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `stream_url` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `duration` int(11) NOT NULL,
  `duration_string` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `genre` int(11) DEFAULT NULL,
  `bpm` int(11) DEFAULT NULL,
  `user_favorite` tinyint(1) NOT NULL,
  `raw_api_result` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F0374EFB586DFF2` (`user_info_id`),
  KEY `sc_idx` (`sc_id`),
  CONSTRAINT `FK_F0374EFB586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sc_track`
--

LOCK TABLES `user_sc_track` WRITE;
/*!40000 ALTER TABLE `user_sc_track` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_sc_track` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_setting`
--

DROP TABLE IF EXISTS `user_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8_unicode_ci NOT NULL,
  `public` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C779A692586DFF2` (`user_info_id`),
  CONSTRAINT `FK_C779A692586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_setting`
--

LOCK TABLES `user_setting` WRITE;
/*!40000 ALTER TABLE `user_setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_stat`
--

DROP TABLE IF EXISTS `user_stat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_stat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_viewied` int(11) NOT NULL,
  `in_search_results` int(11) NOT NULL,
  `heard` int(11) NOT NULL,
  `active_gigs` int(11) NOT NULL,
  `completed_gigs` int(11) NOT NULL,
  `rated` int(11) NOT NULL,
  `average_rating` double NOT NULL,
  `tagged` int(11) NOT NULL,
  `followers` int(11) NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_stat`
--

LOCK TABLES `user_stat` WRITE;
/*!40000 ALTER TABLE `user_stat` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_stat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_subscription`
--

DROP TABLE IF EXISTS `user_subscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_subscription` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `subscription_plan_id` int(11) DEFAULT NULL,
  `stripe_subscr_id` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `paypal_subscr_id` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_commenced` datetime DEFAULT NULL,
  `date_ended` datetime DEFAULT NULL,
  `last_payment_date` datetime DEFAULT NULL,
  `next_payment_date` date DEFAULT NULL,
  `cancel_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_230A18D1586DFF2` (`user_info_id`),
  KEY `IDX_230A18D19B8CE200` (`subscription_plan_id`),
  CONSTRAINT `FK_230A18D1586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_230A18D19B8CE200` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plan` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_subscription`
--

LOCK TABLES `user_subscription` WRITE;
/*!40000 ALTER TABLE `user_subscription` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_subscription` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_tag`
--

DROP TABLE IF EXISTS `user_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `tagged_by_id` int(11) DEFAULT NULL,
  `tag` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E89FD608586DFF2` (`user_info_id`),
  KEY `IDX_E89FD608B0156D6A` (`tagged_by_id`),
  KEY `tag_idx` (`tag`),
  CONSTRAINT `FK_E89FD608586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_E89FD608B0156D6A` FOREIGN KEY (`tagged_by_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_tag`
--

LOCK TABLES `user_tag` WRITE;
/*!40000 ALTER TABLE `user_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_transaction`
--

DROP TABLE IF EXISTS `user_transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `success` tinyint(1) NOT NULL,
  `amount` decimal(9,3) NOT NULL,
  `response` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DB2CCC44586DFF2` (`user_info_id`),
  CONSTRAINT `FK_DB2CCC44586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_transaction`
--

LOCK TABLES `user_transaction` WRITE;
/*!40000 ALTER TABLE `user_transaction` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_transaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_vocal_characteristic`
--

DROP TABLE IF EXISTS `user_vocal_characteristic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_vocal_characteristic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `vocal_characteristic_id` int(11) DEFAULT NULL,
  `agree` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_ECEEA2E5586DFF2` (`user_info_id`),
  KEY `IDX_ECEEA2E5AB3A6FD2` (`vocal_characteristic_id`),
  CONSTRAINT `FK_ECEEA2E5586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_ECEEA2E5AB3A6FD2` FOREIGN KEY (`vocal_characteristic_id`) REFERENCES `vocal_characteristic` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_vocal_characteristic`
--

LOCK TABLES `user_vocal_characteristic` WRITE;
/*!40000 ALTER TABLE `user_vocal_characteristic` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_vocal_characteristic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_vocal_characteristic_vote`
--

DROP TABLE IF EXISTS `user_vocal_characteristic_vote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_vocal_characteristic_vote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user_info_id` int(11) DEFAULT NULL,
  `user_vocal_characteristic_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_51D42D1438C00514` (`from_user_info_id`),
  KEY `IDX_51D42D1412EBC683` (`user_vocal_characteristic_id`),
  CONSTRAINT `FK_51D42D1412EBC683` FOREIGN KEY (`user_vocal_characteristic_id`) REFERENCES `user_vocal_characteristic` (`id`),
  CONSTRAINT `FK_51D42D1438C00514` FOREIGN KEY (`from_user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_vocal_characteristic_vote`
--

LOCK TABLES `user_vocal_characteristic_vote` WRITE;
/*!40000 ALTER TABLE `user_vocal_characteristic_vote` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_vocal_characteristic_vote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_vocal_style`
--

DROP TABLE IF EXISTS `user_vocal_style`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_vocal_style` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `vocal_style_id` int(11) DEFAULT NULL,
  `agree` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C91971B4586DFF2` (`user_info_id`),
  KEY `IDX_C91971B49DDCAC1B` (`vocal_style_id`),
  CONSTRAINT `FK_C91971B4586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_C91971B49DDCAC1B` FOREIGN KEY (`vocal_style_id`) REFERENCES `vocal_style` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_vocal_style`
--

LOCK TABLES `user_vocal_style` WRITE;
/*!40000 ALTER TABLE `user_vocal_style` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_vocal_style` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_vocal_style_vote`
--

DROP TABLE IF EXISTS `user_vocal_style_vote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_vocal_style_vote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user_info_id` int(11) DEFAULT NULL,
  `user_vocal_style_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_383E68AA38C00514` (`from_user_info_id`),
  KEY `IDX_383E68AAF6597342` (`user_vocal_style_id`),
  CONSTRAINT `FK_383E68AA38C00514` FOREIGN KEY (`from_user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_383E68AAF6597342` FOREIGN KEY (`user_vocal_style_id`) REFERENCES `user_vocal_style` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_vocal_style_vote`
--

LOCK TABLES `user_vocal_style_vote` WRITE;
/*!40000 ALTER TABLE `user_vocal_style_vote` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_vocal_style_vote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_voice_tag`
--

DROP TABLE IF EXISTS `user_voice_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_voice_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `voice_tag_id` int(11) DEFAULT NULL,
  `agree` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C2A16C98586DFF2` (`user_info_id`),
  KEY `IDX_C2A16C987D14E76D` (`voice_tag_id`),
  CONSTRAINT `FK_C2A16C98586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_C2A16C987D14E76D` FOREIGN KEY (`voice_tag_id`) REFERENCES `voice_tag` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_voice_tag`
--

LOCK TABLES `user_voice_tag` WRITE;
/*!40000 ALTER TABLE `user_voice_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_voice_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_voice_tag_vote`
--

DROP TABLE IF EXISTS `user_voice_tag_vote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_voice_tag_vote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user_info_id` int(11) DEFAULT NULL,
  `user_voice_tag_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B6C5538E38C00514` (`from_user_info_id`),
  KEY `IDX_B6C5538EFDD89B6F` (`user_voice_tag_id`),
  CONSTRAINT `FK_B6C5538E38C00514` FOREIGN KEY (`from_user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_B6C5538EFDD89B6F` FOREIGN KEY (`user_voice_tag_id`) REFERENCES `user_voice_tag` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_voice_tag_vote`
--

LOCK TABLES `user_voice_tag_vote` WRITE;
/*!40000 ALTER TABLE `user_voice_tag_vote` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_voice_tag_vote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_wallet_transaction`
--

DROP TABLE IF EXISTS `user_wallet_transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_wallet_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `amount` int(11) NOT NULL,
  `currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AB9E1F89586DFF2` (`user_info_id`),
  CONSTRAINT `FK_AB9E1F89586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_wallet_transaction`
--

LOCK TABLES `user_wallet_transaction` WRITE;
/*!40000 ALTER TABLE `user_wallet_transaction` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_wallet_transaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_withdraw`
--

DROP TABLE IF EXISTS `user_withdraw`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_withdraw` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `paypal_email` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `amount` int(11) NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `status_reason` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5553BB9E586DFF2` (`user_info_id`),
  CONSTRAINT `FK_5553BB9E586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_withdraw`
--

LOCK TABLES `user_withdraw` WRITE;
/*!40000 ALTER TABLE `user_withdraw` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_withdraw` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vocal_characteristic`
--

DROP TABLE IF EXISTS `vocal_characteristic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vocal_characteristic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vocal_characteristic`
--

LOCK TABLES `vocal_characteristic` WRITE;
/*!40000 ALTER TABLE `vocal_characteristic` DISABLE KEYS */;
INSERT INTO `vocal_characteristic` VALUES (1,'Raspy'),(2,'Rough'),(3,'Smoothe'),(4,'Silky'),(5,'Strong'),(6,'Crisp'),(7,'Deep'),(8,'High'),(9,'Low');
/*!40000 ALTER TABLE `vocal_characteristic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vocal_style`
--

DROP TABLE IF EXISTS `vocal_style`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vocal_style` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vocal_style`
--

LOCK TABLES `vocal_style` WRITE;
/*!40000 ALTER TABLE `vocal_style` DISABLE KEYS */;
INSERT INTO `vocal_style` VALUES (1,'Rock'),(2,'Diva'),(3,'Divo'),(4,'Soulful'),(5,'Heavy Metal'),(6,'Death Metal'),(7,'Rap'),(8,'Choir'),(9,'Opera'),(10,'Country'),(11,'Reggae'),(12,'Spoken Word'),(13,'Classical'),(14,'Pop Diva'),(15,'Pop Divo'),(16,'Musical Theatre'),(17,'A Capella');
/*!40000 ALTER TABLE `vocal_style` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vocalizr_activity`
--

DROP TABLE IF EXISTS `vocalizr_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vocalizr_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_id` int(11) DEFAULT NULL,
  `actioned_user_info_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `activity_type` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL,
  `activity_read` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_546B11C4586DFF2` (`user_info_id`),
  KEY `IDX_546B11C4ABF1F0F6` (`actioned_user_info_id`),
  KEY `IDX_546B11C4166D1F9C` (`project_id`),
  CONSTRAINT `FK_546B11C4166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  CONSTRAINT `FK_546B11C4586DFF2` FOREIGN KEY (`user_info_id`) REFERENCES `user_info` (`id`),
  CONSTRAINT `FK_546B11C4ABF1F0F6` FOREIGN KEY (`actioned_user_info_id`) REFERENCES `user_info` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vocalizr_activity`
--

LOCK TABLES `vocalizr_activity` WRITE;
/*!40000 ALTER TABLE `vocalizr_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `vocalizr_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voice_tag`
--

DROP TABLE IF EXISTS `voice_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voice_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voice_tag`
--

LOCK TABLES `voice_tag` WRITE;
/*!40000 ALTER TABLE `voice_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `voice_tag` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-05-24 10:11:46
