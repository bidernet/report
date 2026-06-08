-- =================================================================
-- bidernet Reports System - Database Schema
-- Version: 1.0
-- Database: MySQL 5.7+ / MariaDB 10.2+
-- Domain: report.bidernet.co.il
-- =================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Table `users` - מערכת התחברות
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `role` ENUM('admin','user') NOT NULL DEFAULT 'user',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `clients` - לקוחות
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `clients` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(180) NOT NULL,
  `logo` LONGTEXT DEFAULT NULL COMMENT 'Base64 encoded image',
  `ad_account_id` VARCHAR(60) DEFAULT NULL,
  `ad_account_name` VARCHAR(180) DEFAULT NULL,
  `access_token` TEXT DEFAULT NULL COMMENT 'Encrypted Facebook access token',
  `last_synced_at` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_name` (`name`),
  CONSTRAINT `fk_clients_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `campaigns` - דוחות / קמפיינים
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `campaigns` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` INT UNSIGNED NOT NULL,
  `fb_campaign_id` VARCHAR(60) DEFAULT NULL COMMENT 'Facebook campaign ID for sync deduplication',
  `name` VARCHAR(255) NOT NULL,
  `platform` ENUM('facebook','instagram') NOT NULL,
  `campaign_type` VARCHAR(40) DEFAULT 'awareness',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `reach` INT UNSIGNED DEFAULT 0,
  `impressions` INT UNSIGNED DEFAULT 0,
  `clicks` INT UNSIGNED DEFAULT 0,
  `likes` INT UNSIGNED DEFAULT 0,
  `comments` INT UNSIGNED DEFAULT 0,
  `shares` INT UNSIGNED DEFAULT 0,
  `engagement` INT UNSIGNED DEFAULT 0,
  `leads` INT UNSIGNED DEFAULT 0,
  `conversions` INT UNSIGNED DEFAULT 0,
  `budget` DECIMAL(12,2) DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `synced_at` DATETIME DEFAULT NULL COMMENT 'Last sync from Facebook API',
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_dates` (`start_date`, `end_date`),
  KEY `idx_platform` (`platform`),
  KEY `idx_fb_campaign` (`fb_campaign_id`),
  CONSTRAINT `fk_campaigns_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_campaigns_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `activity_log` - יומן פעילות (אופציונלי)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL,
  `entity_type` VARCHAR(30) DEFAULT NULL,
  `entity_id` INT UNSIGNED DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- משתמש ראשוני (Admin) - אנא שנה את הסיסמה אחרי ההתקנה!
-- סיסמה ברירת מחדל: bidernet2026
-- password_hash = password_hash('bidernet2026', PASSWORD_BCRYPT)
-- -----------------------------------------------------
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`) VALUES
('admin', 'admin@bidernet.co.il', '$2y$10$YourHashWillBeGeneratedByInstallScript', 'מנהל מערכת', 'admin');

SET FOREIGN_KEY_CHECKS = 1;
