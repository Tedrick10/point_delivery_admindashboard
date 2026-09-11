-- MySQL dump 10.13  Distrib 8.0.46, for macos14.8 (x86_64)
--
-- Host: 127.0.0.1    Database: point_delivery
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_login_devices`
--

DROP TABLE IF EXISTS `admin_login_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_login_devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_at` timestamp NULL DEFAULT NULL,
  `logout_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_login_history`
--

DROP TABLE IF EXISTS `admin_login_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_login_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lat_long` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `org` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_mobile` tinyint(1) NOT NULL DEFAULT '0',
  `is_desktop` tinyint(1) NOT NULL DEFAULT '0',
  `is_tablet` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `advertisements`
--

DROP TABLE IF EXISTS `advertisements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `advertisements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ad_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'promotion',
  `placement` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'home',
  `target_app` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'client',
  `client_id` bigint unsigned DEFAULT NULL,
  `approval_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `link_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `site_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_description` longtext COLLATE utf8mb4_unicode_ci,
  `site_copyright` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `support_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `support_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notification_settings` json DEFAULT NULL,
  `language_option` json DEFAULT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '#5e3c9e',
  `prefix` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auto_assign` tinyint DEFAULT '0',
  `distance_unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'km, mile',
  `distance` double DEFAULT '0',
  `otp_verify_on_pickup_delivery` tinyint DEFAULT '1',
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_vehicle_in_order` tinyint DEFAULT '0',
  `is_bidding_in_order` double NOT NULL DEFAULT '0',
  `is_sms_order` tinyint DEFAULT '0',
  `admin_login_button` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `backup_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `backup_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `fixed_charges` double DEFAULT '0',
  `cancel_charges` double DEFAULT '0',
  `min_distance` double DEFAULT '0',
  `min_weight` double DEFAULT '0',
  `per_distance_charges` double DEFAULT '0',
  `per_weight_charges` double DEFAULT '0',
  `status` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `commission_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'fixed, percentage',
  `admin_commission` double DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `claims`
--

DROP TABLE IF EXISTS `claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `claims` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `traking_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prof_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detail` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending,approved,reject',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  `isClaimed` tinyint DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `claims_histories`
--

DROP TABLE IF EXISTS `claims_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `claims_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `claim_id` bigint unsigned DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `distance_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `links` longtext COLLATE utf8mb4_unicode_ci,
  `status` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `coupon_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `value_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_amount` double DEFAULT NULL,
  `city_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `city_id` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `courier_companies`
--

DROP TABLE IF EXISTS `courier_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courier_companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `tracking_details` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_shipped` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_supports`
--

DROP TABLE IF EXISTS `customer_supports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_supports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message` text COLLATE utf8mb4_unicode_ci,
  `support_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending, inreview, resolved',
  `resolution_detail` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `daily_check_invoices`
--

DROP TABLE IF EXISTS `daily_check_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_check_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `party_type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `party_user_id` bigint unsigned NOT NULL DEFAULT '0',
  `branch_id` bigint unsigned DEFAULT NULL,
  `received_date` date NOT NULL,
  `remitted_date` date DEFAULT NULL,
  `remitted_photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remitted_by` bigint unsigned DEFAULT NULL,
  `item_ids` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_check_invoices_party_date_unique` (`party_type`,`party_user_id`,`received_date`),
  UNIQUE KEY `daily_check_invoices_invoice_no_unique` (`invoice_no`),
  KEY `daily_check_invoices_branch_id_foreign` (`branch_id`),
  KEY `daily_check_invoices_remitted_by_foreign` (`remitted_by`),
  KEY `daily_check_invoices_created_by_foreign` (`created_by`),
  KEY `daily_check_invoices_received_date_party_type_index` (`received_date`,`party_type`),
  CONSTRAINT `daily_check_invoices_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `daily_check_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `daily_check_invoices_remitted_by_foreign` FOREIGN KEY (`remitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `default_keywords`
--

DROP TABLE IF EXISTS `default_keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `default_keywords` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `screen_id` bigint unsigned DEFAULT NULL COMMENT 'screens screenID',
  `keyword_id` bigint unsigned DEFAULT NULL COMMENT 'app keyword',
  `keyword_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keyword_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=644 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `delivery_man_documents`
--

DROP TABLE IF EXISTS `delivery_man_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_man_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `document_id` bigint unsigned DEFAULT NULL,
  `is_verified` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `reminder_sent` tinyint(1) NOT NULL DEFAULT '0',
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_man_documents_document_id_foreign` (`document_id`),
  KEY `delivery_man_documents_delivery_man_id_foreign` (`delivery_man_id`),
  CONSTRAINT `delivery_man_documents_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delivery_man_documents_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `delivery_man_payout_reports`
--

DROP TABLE IF EXISTS `delivery_man_payout_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_man_payout_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_man_id` bigint unsigned NOT NULL,
  `week_start_date` date NOT NULL,
  `week_end_date` date NOT NULL,
  `total_trips` int NOT NULL DEFAULT '0',
  `driver_tips` int NOT NULL DEFAULT '0',
  `is_mail_sent` tinyint(1) NOT NULL DEFAULT '0',
  `total_fare` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_commission` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payout_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','processing','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `generated_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_man_payout_reports_delivery_man_id_foreign` (`delivery_man_id`),
  CONSTRAINT `delivery_man_payout_reports_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `delivery_man_section_titles`
--

DROP TABLE IF EXISTS `delivery_man_section_titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_man_section_titles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `section_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_man_section_titles_section_id_foreign` (`section_id`),
  CONSTRAINT `delivery_man_section_titles_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `delivery_man_sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `delivery_man_sections`
--

DROP TABLE IF EXISTS `delivery_man_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_man_sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `deliveryman_vehicle_histories`
--

DROP TABLE IF EXISTS `deliveryman_vehicle_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deliveryman_vehicle_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `vehicle_info` json DEFAULT NULL,
  `start_datetime` datetime DEFAULT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `is_active` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dispatch_item_messages`
--

DROP TABLE IF EXISTS `dispatch_item_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispatch_item_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dispatch_order_item_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  `sender_id` bigint unsigned DEFAULT NULL,
  `sender_type` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dispatch_item_messages_dispatch_order_item_id_index` (`dispatch_order_item_id`),
  KEY `dispatch_item_messages_order_id_index` (`order_id`),
  KEY `dispatch_item_messages_client_id_index` (`client_id`),
  KEY `dispatch_item_messages_sender_id_index` (`sender_id`),
  CONSTRAINT `dispatch_item_messages_dispatch_order_item_id_foreign` FOREIGN KEY (`dispatch_order_item_id`) REFERENCES `dispatch_order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dispatch_order_items`
--

DROP TABLE IF EXISTS `dispatch_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispatch_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `photo_id` bigint unsigned NOT NULL DEFAULT '0',
  `pending_photo_id` bigint unsigned NOT NULL DEFAULT '0',
  `delivered_photo_id` bigint unsigned NOT NULL DEFAULT '0',
  `delivered_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `cust_photo_id` bigint unsigned DEFAULT NULL,
  `cust_sign_id` bigint unsigned DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'collected',
  `delivery_locked` tinyint(1) NOT NULL DEFAULT '0',
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `admin_updated_at` timestamp NULL DEFAULT NULL,
  `admin_completed_at` timestamp NULL DEFAULT NULL,
  `admin_finished_at` timestamp NULL DEFAULT NULL,
  `rider_remit_at` timestamp NULL DEFAULT NULL,
  `rider_remit_date` date DEFAULT NULL,
  `from_branch_id` bigint unsigned DEFAULT NULL,
  `to_branch_id` bigint unsigned DEFAULT NULL,
  `to_branch_custom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `delivery_city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `township` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_name` text COLLATE utf8mb4_unicode_ci,
  `remark` text COLLATE utf8mb4_unicode_ci,
  `weight` decimal(12,2) NOT NULL DEFAULT '0.00',
  `advance_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `os_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `item_value` decimal(12,2) NOT NULL DEFAULT '0.00',
  `deli_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_address` text COLLATE utf8mb4_unicode_ci,
  `credit_to` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'customer',
  `pickup_pay_mode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cust_get` decimal(12,2) NOT NULL DEFAULT '0.00',
  `os_to_pay` decimal(12,2) NOT NULL DEFAULT '0.00',
  `gate_amount` double NOT NULL DEFAULT '0',
  `gate_os_paid` double NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dispatch_order_items_order_id_foreign` (`order_id`),
  KEY `dispatch_order_items_rider_remit_at_index` (`rider_remit_at`),
  KEY `dispatch_order_items_delivered_at_index` (`delivered_at`),
  KEY `dispatch_order_items_rider_remit_date_idx` (`rider_remit_date`),
  CONSTRAINT `dispatch_order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `is_required` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `emergencies`
--

DROP TABLE IF EXISTS `emergencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emergencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `emrgency_reason` text COLLATE utf8mb4_unicode_ci,
  `emergency_resolved` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint DEFAULT NULL COMMENT 'pending, in_progress, close',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emergencies_delivery_man_id_foreign` (`delivery_man_id`),
  CONSTRAINT `emergencies_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employee_types`
--

DROP TABLE IF EXISTS `employee_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `expense_cards`
--

DROP TABLE IF EXISTS `expense_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `expense_date` date NOT NULL,
  `total_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_cards_expense_date_unique` (`expense_date`),
  KEY `expense_cards_expense_date_index` (`expense_date`),
  KEY `expense_cards_created_by_foreign` (`created_by`),
  KEY `expense_cards_updated_by_foreign` (`updated_by`),
  CONSTRAINT `expense_cards_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expense_cards_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `expense_items`
--

DROP TABLE IF EXISTS `expense_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `expense_card_id` bigint unsigned NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `image_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `source` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_items_expense_card_id_sort_order_index` (`expense_card_id`,`sort_order`),
  KEY `expense_items_expense_card_id_source_index` (`expense_card_id`,`source`),
  CONSTRAINT `expense_items_expense_card_id_foreign` FOREIGN KEY (`expense_card_id`) REFERENCES `expense_cards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `expense_summaries`
--

DROP TABLE IF EXISTS `expense_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_summaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `summary_date` date NOT NULL,
  `expense_card_id` bigint unsigned DEFAULT NULL,
  `income` decimal(14,2) NOT NULL DEFAULT '0.00',
  `expense` decimal(14,2) NOT NULL DEFAULT '0.00',
  `ako_given` decimal(14,2) NOT NULL DEFAULT '0.00',
  `generated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_summaries_summary_date_unique` (`summary_date`),
  KEY `expense_summaries_expense_card_id_foreign` (`expense_card_id`),
  KEY `expense_summaries_generated_by_foreign` (`generated_by`),
  KEY `expense_summaries_summary_date_index` (`summary_date`),
  CONSTRAINT `expense_summaries_expense_card_id_foreign` FOREIGN KEY (`expense_card_id`) REFERENCES `expense_cards` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expense_summaries_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `extra_charges`
--

DROP TABLE IF EXISTS `extra_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `extra_charges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `charges_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'fixed, percentage',
  `charges` double DEFAULT '0',
  `country_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '0-inactive , 1 - active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `frontend_data`
--

DROP TABLE IF EXISTS `frontend_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `frontend_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'why_choose, partner_benefits, client_review',
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hr_bag_deduction_items`
--

DROP TABLE IF EXISTS `hr_bag_deduction_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_bag_deduction_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period_month` date NOT NULL,
  `staff_id` bigint unsigned DEFAULT NULL,
  `staff_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_date` date DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_bag_deduction_items_staff_id_foreign` (`staff_id`),
  KEY `hr_bag_deduction_items_period_month_staff_id_index` (`period_month`,`staff_id`),
  CONSTRAINT `hr_bag_deduction_items_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `hr_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hr_late_fine_items`
--

DROP TABLE IF EXISTS `hr_late_fine_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_late_fine_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period_month` date NOT NULL,
  `staff_id` bigint unsigned DEFAULT NULL,
  `staff_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fine_date` date DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_late_fine_items_staff_id_foreign` (`staff_id`),
  KEY `hr_late_fine_items_period_month_staff_id_index` (`period_month`,`staff_id`),
  CONSTRAINT `hr_late_fine_items_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `hr_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hr_late_fine_rows`
--

DROP TABLE IF EXISTS `hr_late_fine_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_late_fine_rows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period_month` date NOT NULL,
  `staff_id` bigint unsigned NOT NULL,
  `late_minutes` int unsigned NOT NULL DEFAULT '0',
  `allowance_minutes` int unsigned NOT NULL DEFAULT '60',
  `fine_per_minute` int unsigned NOT NULL DEFAULT '100',
  `absent_dates` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `absent_days` int unsigned NOT NULL DEFAULT '0',
  `absent_day_rate` int unsigned NOT NULL DEFAULT '6000',
  `way_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ako_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hr_late_fine_rows_period_month_staff_id_unique` (`period_month`,`staff_id`),
  KEY `hr_late_fine_rows_staff_id_foreign` (`staff_id`),
  KEY `hr_late_fine_rows_period_month_index` (`period_month`),
  CONSTRAINT `hr_late_fine_rows_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `hr_staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hr_office_salary_rows`
--

DROP TABLE IF EXISTS `hr_office_salary_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_office_salary_rows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period_month` date NOT NULL,
  `staff_id` bigint unsigned NOT NULL,
  `monthly_salary` decimal(12,2) NOT NULL DEFAULT '0.00',
  `salary_day_base` tinyint unsigned NOT NULL DEFAULT '28',
  `rest_days` tinyint unsigned NOT NULL DEFAULT '0',
  `rest_off_dates` json DEFAULT NULL,
  `way_count` int unsigned NOT NULL DEFAULT '0',
  `way_rate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `late_minute_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `fine_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `bag_deduction` decimal(12,2) NOT NULL DEFAULT '0.00',
  `personal_expense` decimal(12,2) NOT NULL DEFAULT '0.00',
  `deposit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hr_office_salary_rows_period_month_staff_id_unique` (`period_month`,`staff_id`),
  KEY `hr_office_salary_rows_staff_id_foreign` (`staff_id`),
  KEY `hr_office_salary_rows_period_month_index` (`period_month`),
  CONSTRAINT `hr_office_salary_rows_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `hr_staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hr_staff`
--

DROP TABLE IF EXISTS `hr_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_staff` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `staff_group` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'office',
  `user_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `monthly_salary` decimal(12,2) NOT NULL DEFAULT '0.00',
  `allowance_minutes` int unsigned NOT NULL DEFAULT '60',
  `way_rate` decimal(12,2) NOT NULL DEFAULT '1000.00',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hr_staff_code_unique` (`code`),
  KEY `hr_staff_user_id_foreign` (`user_id`),
  KEY `hr_staff_branch_id_foreign` (`branch_id`),
  KEY `hr_staff_staff_group_status_index` (`staff_group`,`status`),
  CONSTRAINT `hr_staff_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hr_staff_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_default_lists`
--

DROP TABLE IF EXISTS `language_default_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_default_lists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `languageName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `languageCode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `countryCode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=557 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_lists`
--

DROP TABLE IF EXISTS `language_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_lists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `language_id` bigint unsigned DEFAULT NULL,
  `language_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language_flag` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_rtl` tinyint DEFAULT '0',
  `is_default` tinyint DEFAULT '0' COMMENT '0-no, 1-yes',
  `status` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `language_lists_language_id_foreign` (`language_id`),
  CONSTRAINT `language_lists_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `language_default_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_version_details`
--

DROP TABLE IF EXISTS `language_version_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_version_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `default_language_id` bigint unsigned DEFAULT NULL,
  `version_no` bigint unsigned DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language_with_keywords`
--

DROP TABLE IF EXISTS `language_with_keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_with_keywords` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `language_id` bigint unsigned DEFAULT NULL,
  `keyword_id` bigint unsigned DEFAULT NULL,
  `screen_id` bigint unsigned DEFAULT NULL,
  `keyword_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `language_with_keywords_language_id_foreign` (`language_id`),
  CONSTRAINT `language_with_keywords_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `language_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB AUTO_INCREMENT=552 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=200 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_bids`
--

DROP TABLE IF EXISTS `order_bids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_bids` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `bid_amount` double DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_bid_accept` tinyint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_bids_order_id_foreign` (`order_id`),
  KEY `order_bids_delivery_man_id_foreign` (`delivery_man_id`),
  CONSTRAINT `order_bids_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_bids_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_chat_messages`
--

DROP TABLE IF EXISTS `order_chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_chat_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `sender_id` bigint unsigned DEFAULT NULL,
  `sender_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `way_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `message` text COLLATE utf8mb4_unicode_ci,
  `message_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_histories`
--

DROP TABLE IF EXISTS `order_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `datetime` datetime DEFAULT NULL,
  `history_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `history_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `history_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_histories_order_id_foreign` (`order_id`),
  CONSTRAINT `order_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_mails`
--

DROP TABLE IF EXISTS `order_mails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_mails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_description` longtext COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_vehicle_histories`
--

DROP TABLE IF EXISTS `order_vehicle_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_vehicle_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `vehicle_info` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint unsigned DEFAULT NULL,
  `pickup_point` json DEFAULT NULL,
  `delivery_point` json DEFAULT NULL,
  `packaging_symbols` json DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `parcel_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_weight` double DEFAULT '0',
  `total_distance` double DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `pickup_datetime` datetime DEFAULT NULL,
  `delivery_datetime` datetime DEFAULT NULL,
  `assign_datetime` datetime DEFAULT NULL,
  `parent_order_id` bigint unsigned DEFAULT NULL,
  `payment_id` bigint unsigned DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `pickup_error_at` timestamp NULL DEFAULT NULL,
  `pickup_error_choice` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_error_choice_at` timestamp NULL DEFAULT NULL,
  `pickup_error_os_choice` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_error_os_choice_at` timestamp NULL DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_photo_order` tinyint NOT NULL DEFAULT '0',
  `is_text_order` tinyint NOT NULL DEFAULT '0',
  `is_shop_order` tinyint NOT NULL DEFAULT '0',
  `is_gate_order` tinyint NOT NULL DEFAULT '0',
  `is_self_order` tinyint NOT NULL DEFAULT '1',
  `payment_collect_from` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'on_pickup, on_delivery',
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `fixed_charges` double DEFAULT '0',
  `weight_charge` double DEFAULT '0',
  `distance_charge` double DEFAULT '0',
  `extra_charges` json DEFAULT NULL,
  `insurance_charge` double NOT NULL DEFAULT '0',
  `package_value` double DEFAULT '0',
  `total_amount` double DEFAULT '0',
  `vehicle_charge` double DEFAULT '0',
  `pickup_confirm_by_client` tinyint DEFAULT '0' COMMENT '0-not confirm , 1 - confirm',
  `pickup_confirm_by_delivery_man` tinyint DEFAULT '0' COMMENT '0-not confirm , 1 - confirm',
  `total_parcel` double DEFAULT NULL,
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `pickup_vehicle_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'motorcycle',
  `vehicle_data` json DEFAULT NULL,
  `auto_assign` tinyint DEFAULT NULL,
  `cancelled_delivery_man_ids` text COLLATE utf8mb4_unicode_ci,
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `milisecond` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `couriercompany_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `rescheduledatetime` datetime DEFAULT NULL,
  `is_shipped` bigint unsigned DEFAULT '0',
  `coupon_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_amount` double NOT NULL DEFAULT '0',
  `shipped_verify_at` datetime DEFAULT NULL,
  `bid_type` tinyint DEFAULT NULL,
  `nearby_driver_ids` text COLLATE utf8mb4_unicode_ci,
  `accept_delivery_man_ids` text COLLATE utf8mb4_unicode_ci,
  `reject_delivery_man_ids` text COLLATE utf8mb4_unicode_ci,
  `payment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_reschedule` bigint unsigned DEFAULT NULL,
  `welcome_discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `orders_client_id_foreign` (`client_id`),
  KEY `orders_is_reschedule_foreign` (`is_reschedule`),
  CONSTRAINT `orders_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_is_reschedule_foreign` FOREIGN KEY (`is_reschedule`) REFERENCES `reschedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `os_cash_payouts`
--

DROP TABLE IF EXISTS `os_cash_payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_cash_payouts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `os_user_id` bigint unsigned NOT NULL,
  `settlement_batch_id` bigint unsigned DEFAULT NULL,
  `money_transfer_id` bigint unsigned DEFAULT NULL,
  `period_from` date NOT NULL,
  `period_to` date NOT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `slip_photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unassigned',
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `pending_at` timestamp NULL DEFAULT NULL,
  `done_at` timestamp NULL DEFAULT NULL,
  `pending_note` text COLLATE utf8mb4_unicode_ci,
  `pending_photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `done_note` text COLLATE utf8mb4_unicode_ci,
  `done_photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `os_cash_payouts_os_user_id_index` (`os_user_id`),
  KEY `os_cash_payouts_settlement_batch_id_index` (`settlement_batch_id`),
  KEY `os_cash_payouts_money_transfer_id_index` (`money_transfer_id`),
  KEY `os_cash_payouts_status_index` (`status`),
  KEY `os_cash_payouts_delivery_man_id_index` (`delivery_man_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `os_money_transfers`
--

DROP TABLE IF EXISTS `os_money_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_money_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period_from` date NOT NULL,
  `period_to` date NOT NULL,
  `branch_id` bigint unsigned NOT NULL DEFAULT '0',
  `os_user_id` bigint unsigned NOT NULL,
  `payment_method` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kpay',
  `settlement_batch_id` bigint unsigned DEFAULT NULL,
  `cash_amount` double NOT NULL DEFAULT '0',
  `kpay_amount` double NOT NULL DEFAULT '0',
  `freight_amount` double DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `os_money_transfers_settlement_batch_unique` (`settlement_batch_id`),
  KEY `os_money_transfers_updated_by_foreign` (`updated_by`),
  KEY `os_money_transfers_period_from_period_to_branch_id_index` (`period_from`,`period_to`,`branch_id`),
  CONSTRAINT `os_money_transfers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `os_receive_settlements`
--

DROP TABLE IF EXISTS `os_receive_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_receive_settlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `settlement_batch_id` bigint unsigned DEFAULT NULL,
  `os_user_id` bigint unsigned NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_qr_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `os_payslip_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `os_payslip_paths` json DEFAULT NULL,
  `admin_remark` text COLLATE utf8mb4_unicode_ci,
  `finished_by` bigint unsigned DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `os_submitted_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `os_receive_settlements_settlement_batch_id_index` (`settlement_batch_id`),
  KEY `os_receive_settlements_os_user_id_index` (`os_user_id`),
  KEY `os_receive_settlements_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `os_settlement_batches`
--

DROP TABLE IF EXISTS `os_settlement_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_settlement_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `os_user_id` bigint unsigned NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kpay',
  `settlement_side` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_format` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'table',
  `kpay_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kpay_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kpay_slip_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slip_table_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slip_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slip_pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kpay_pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `combined_pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_ids` json DEFAULT NULL,
  `finished_by` bigint unsigned DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `os_settlement_batches_finished_by_foreign` (`finished_by`),
  KEY `os_settlement_batches_os_user_id_from_date_to_date_index` (`os_user_id`,`from_date`,`to_date`),
  CONSTRAINT `os_settlement_batches_finished_by_foreign` FOREIGN KEY (`finished_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `os_settlement_batches_os_user_id_foreign` FOREIGN KEY (`os_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `os_settlement_drafts`
--

DROP TABLE IF EXISTS `os_settlement_drafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_settlement_drafts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `os_user_id` bigint unsigned NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `kpay_slip_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `os_settlement_drafts_os_user_id_from_date_to_date_unique` (`os_user_id`,`from_date`,`to_date`),
  CONSTRAINT `os_settlement_drafts_os_user_id_foreign` FOREIGN KEY (`os_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pay_t_r_payments`
--

DROP TABLE IF EXISTS `pay_t_r_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pay_t_r_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_oid` bigint DEFAULT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `total_amount` double DEFAULT '0',
  `payment_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'pending, paid, failed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pay_t_r_payments_client_id_foreign` (`client_id`),
  CONSTRAINT `pay_t_r_payments_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_gateways`
--

DROP TABLE IF EXISTS `payment_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_gateways` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint DEFAULT '1' COMMENT '0- InActive, 1- Active',
  `is_test` tinyint DEFAULT '1' COMMENT '0-  No, 1- Yes',
  `test_value` json DEFAULT NULL,
  `live_value` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint unsigned NOT NULL,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `order_id` bigint unsigned NOT NULL,
  `datetime` datetime DEFAULT NULL,
  `total_amount` double DEFAULT '0',
  `payment_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `txn_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'pending, paid, failed',
  `transaction_detail` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_settled` tinyint NOT NULL DEFAULT '0' COMMENT '0 = false, 1 = true',
  `payout_report_id` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `cancel_charges` double DEFAULT '0',
  `admin_commission` double DEFAULT '0',
  `delivery_man_commission` double DEFAULT '0',
  `received_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_man_fee` double DEFAULT '0',
  `delivery_man_tip` double DEFAULT '0',
  `merchant_oid` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_client_id_foreign` (`client_id`),
  KEY `payments_order_id_foreign` (`order_id`),
  KEY `payments_delivery_man_id_foreign` (`delivery_man_id`),
  CONSTRAINT `payments_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `profofpictures`
--

DROP TABLE IF EXISTS `profofpictures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `profofpictures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'pickup_time,delivery_time',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `push_notifications`
--

DROP TABLE IF EXISTS `push_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci,
  `target_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `for_client` tinyint(1) DEFAULT '0',
  `for_delivery_man` tinyint(1) DEFAULT '0',
  `for_all` tinyint(1) DEFAULT '0',
  `notification_count` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ratings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `review_user_id` bigint unsigned DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `dispatch_order_item_id` bigint unsigned DEFAULT NULL,
  `rating` double NOT NULL DEFAULT '0',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `rating_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ratings_item_user_unique` (`dispatch_order_item_id`,`user_id`),
  KEY `ratings_user_id_foreign` (`user_id`),
  KEY `ratings_review_user_id_foreign` (`review_user_id`),
  KEY `ratings_order_id_foreign` (`order_id`),
  CONSTRAINT `ratings_dispatch_order_item_id_foreign` FOREIGN KEY (`dispatch_order_item_id`) REFERENCES `dispatch_order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ratings_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_review_user_id_foreign` FOREIGN KEY (`review_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reschedules`
--

DROP TABLE IF EXISTS `reschedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reschedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rest_api_histories`
--

DROP TABLE IF EXISTS `rest_api_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rest_api_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rest_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `last_access_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rest_api_histories_order_id_foreign` (`order_id`),
  CONSTRAINT `rest_api_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rest_apis`
--

DROP TABLE IF EXISTS `rest_apis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rest_apis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rest_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `last_access_date` datetime DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rest_apis_country_id_foreign` (`country_id`),
  KEY `rest_apis_city_id_foreign` (`city_id`),
  CONSTRAINT `rest_apis_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rest_apis_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rider_remit_logs`
--

DROP TABLE IF EXISTS `rider_remit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rider_remit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `remit_date` date NOT NULL,
  `branch_id` bigint unsigned NOT NULL DEFAULT '0',
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rider_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `rider_remit_logs_day_branch_id_idx` (`remit_date`,`branch_id`,`id`),
  KEY `rider_remit_logs_remit_date_index` (`remit_date`),
  KEY `rider_remit_logs_branch_id_index` (`branch_id`),
  KEY `rider_remit_logs_delivery_man_id_index` (`delivery_man_id`),
  KEY `rider_remit_logs_actor_id_index` (`actor_id`),
  KEY `rider_remit_logs_action_index` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rider_remits`
--

DROP TABLE IF EXISTS `rider_remits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rider_remits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `remit_date` date NOT NULL,
  `branch_id` bigint unsigned NOT NULL DEFAULT '0',
  `delivery_man_id` bigint unsigned NOT NULL,
  `due_amount` double NOT NULL DEFAULT '0',
  `prepaid_amount` double NOT NULL DEFAULT '0',
  `fuel_amount` double NOT NULL DEFAULT '0',
  `fee_amount` double NOT NULL DEFAULT '0',
  `denominations` json DEFAULT NULL,
  `kpay_amount` double NOT NULL DEFAULT '0',
  `is_off` tinyint(1) NOT NULL DEFAULT '0',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rider_remits_day_branch_rider_uq` (`remit_date`,`branch_id`,`delivery_man_id`),
  KEY `rider_remits_remit_date_index` (`remit_date`),
  KEY `rider_remits_branch_id_index` (`branch_id`),
  KEY `rider_remits_delivery_man_id_index` (`delivery_man_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint DEFAULT '1',
  `employee_type_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`),
  KEY `roles_employee_type_id_foreign` (`employee_type_id`),
  CONSTRAINT `roles_employee_type_id_foreign` FOREIGN KEY (`employee_type_id`) REFERENCES `employee_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `s_m_s_settings`
--

DROP TABLE IF EXISTS `s_m_s_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `s_m_s_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint DEFAULT '1' COMMENT '0- InActive, 1- Active',
  `values` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `s_m_s_templates`
--

DROP TABLE IF EXISTS `s_m_s_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `s_m_s_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_description` longtext COLLATE utf8mb4_unicode_ci,
  `sms_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `s_m_s_templates_sms_id_foreign` (`sms_id`),
  CONSTRAINT `s_m_s_templates_sms_id_foreign` FOREIGN KEY (`sms_id`) REFERENCES `s_m_s_settings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `screens`
--

DROP TABLE IF EXISTS `screens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `screens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `screenId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `screenName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shop_banners`
--

DROP TABLE IF EXISTS `shop_banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_banners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'carousel',
  `link_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `link_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shop_categories`
--

DROP TABLE IF EXISTS `shop_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'product_type',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shop_products`
--

DROP TABLE IF EXISTS `shop_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `sale_price` decimal(12,2) DEFAULT NULL,
  `price_max` decimal(12,2) DEFAULT NULL,
  `stock_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_stock',
  `home_section` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `flash_sale_ends_at` timestamp NULL DEFAULT NULL,
  `storage_options` json DEFAULT NULL,
  `color_options` json DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shop_products_category_id_foreign` (`category_id`),
  CONSTRAINT `shop_products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `shop_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sos_numbers`
--

DROP TABLE IF EXISTS `sos_numbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sos_numbers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sos_numbers_delivery_man_id_foreign` (`delivery_man_id`),
  CONSTRAINT `sos_numbers_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `static_data`
--

DROP TABLE IF EXISTS `static_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `static_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `support_chathistories`
--

DROP TABLE IF EXISTS `support_chathistories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_chathistories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `support_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci,
  `message_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `datetime` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `support_chathistories_support_id_foreign` (`support_id`),
  CONSTRAINT `support_chathistories_support_id_foreign` FOREIGN KEY (`support_id`) REFERENCES `customer_supports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `latitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_type` text COLLATE utf8mb4_unicode_ci,
  `contact_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_addresses_user_id_foreign` (`user_id`),
  CONSTRAINT `user_addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_bank_accounts`
--

DROP TABLE IF EXISTS `user_bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_bank_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_holder_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `bank_address` text COLLATE utf8mb4_unicode_ci,
  `routing_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_iban` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_swift` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_bank_accounts_user_id_foreign` (`user_id`),
  CONSTRAINT `user_bank_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `address` text COLLATE utf8mb4_unicode_ci,
  `os_profile` json DEFAULT NULL,
  `contact_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `daily_contact_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `daily_contact_date` date DEFAULT NULL,
  `user_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `player_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'UTC',
  `last_notification_seen` timestamp NULL DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `rider_work_on` tinyint(1) NOT NULL DEFAULT '1',
  `rider_work_off_date` date DEFAULT NULL,
  `approval_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approved',
  `is_vip` tinyint NOT NULL DEFAULT '0',
  `welcome_orders_used` int NOT NULL DEFAULT '0',
  `is_temp_password` tinyint NOT NULL DEFAULT '0',
  `created_by_admin` tinyint NOT NULL DEFAULT '0',
  `flag` enum('0','1') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `otp` int DEFAULT NULL,
  `uid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fcm_token` text COLLATE utf8mb4_unicode_ci,
  `current_team_id` bigint unsigned DEFAULT NULL,
  `profile_photo_path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_actived_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `otp_verify_at` timestamp NULL DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `login_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_location_update_at` datetime DEFAULT NULL,
  `app_source` text COLLATE utf8mb4_unicode_ci,
  `document_verified_at` timestamp NULL DEFAULT NULL,
  `referral_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `partner_referral_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_autoverified_document` tinyint(1) NOT NULL DEFAULT '0',
  `is_autoverified_email` tinyint(1) NOT NULL DEFAULT '0',
  `is_autoverified_mobile` tinyint(1) NOT NULL DEFAULT '0',
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `apple_user_identifier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_branch_id_foreign` (`branch_id`),
  CONSTRAINT `users_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'all,city_wise',
  `size` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_ids` text COLLATE utf8mb4_unicode_ci,
  `capacity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` double DEFAULT '0',
  `min_km` double DEFAULT '0',
  `per_km_charge` double DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `verification_codes`
--

DROP TABLE IF EXISTS `verification_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `verification_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `verification_codes_user_id_foreign` (`user_id`),
  CONSTRAINT `verification_codes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wallet_histories`
--

DROP TABLE IF EXISTS `wallet_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'credit,debit',
  `transaction_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'topup,withdraw,order_fee,admin_commision,correction',
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` double DEFAULT '0',
  `balance` double DEFAULT '0',
  `datetime` datetime DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `data` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `week_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wallet_histories_user_id_foreign` (`user_id`),
  KEY `wallet_histories_order_id_foreign` (`order_id`),
  CONSTRAINT `wallet_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wallet_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wallets`
--

DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `total_amount` double DEFAULT '0',
  `online_received` double DEFAULT '0',
  `collected_cash` double DEFAULT '0',
  `manual_received` double DEFAULT '0',
  `total_withdrawn` double DEFAULT '0',
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wallets_user_id_foreign` (`user_id`),
  CONSTRAINT `wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `website_section_titles`
--

DROP TABLE IF EXISTS `website_section_titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `website_section_titles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `section_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `website_section_titles_section_id_foreign` (`section_id`),
  CONSTRAINT `website_section_titles_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `website_sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `website_sections`
--

DROP TABLE IF EXISTS `website_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `website_sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `welcome_promotions`
--

DROP TABLE IF EXISTS `welcome_promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `welcome_promotions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Welcome Promotion',
  `max_orders` int NOT NULL DEFAULT '10',
  `discount_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL DEFAULT '5.00',
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `withdraw_details`
--

DROP TABLE IF EXISTS `withdraw_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdraw_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `via` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `other_detail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `withdrawrequest_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `withdraw_requests`
--

DROP TABLE IF EXISTS `withdraw_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdraw_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `amount` double DEFAULT '0',
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'requested' COMMENT 'requested,approved,decline',
  `datetime` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `withdraw_requests_user_id_foreign` (`user_id`),
  CONSTRAINT `withdraw_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'point_delivery'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-04 20:47:09
