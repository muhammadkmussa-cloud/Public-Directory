-- ============================================
-- UMMAH DIRECTORY - YELP-STYLE DATABASE SCHEMA
-- Phase 1: Core Tables & Authentication
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- 1. USERS & AUTHENTICATION
-- ============================================

CREATE TABLE `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `user_type` ENUM('regular', 'business_owner', 'fundi', 'admin', 'charity') DEFAULT 'regular',
  `is_verified` TINYINT(1) DEFAULT 0,
  `verification_badge` ENUM('none', 'verified', 'premium', 'top_contributor') DEFAULT 'none',
  `contributor_level` INT(11) DEFAULT 1,
  `total_reviews` INT(11) DEFAULT 0,
  `total_checkins` INT(11) DEFAULT 0,
  `total_photos` INT(11) DEFAULT 0,
  `helpful_votes` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  KEY `user_type` (`user_type`),
  KEY `is_verified` (`is_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions for authentication
CREATE TABLE `user_sessions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `session_token` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_token` (`session_token`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset tokens
CREATE TABLE `password_resets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `oauth_links` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `provider` ENUM('google') NOT NULL,
  `provider_user_id` VARCHAR(64) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_user` (`provider`, `provider_user_id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `email_verifications` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. CATEGORIES & ATTRIBUTES
-- ============================================

CREATE TABLE `categories` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `name_sw` VARCHAR(100) DEFAULT NULL,
  `name_ar` VARCHAR(100) DEFAULT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `parent_id` INT(11) UNSIGNED DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `type` ENUM('business', 'mosque', 'fundi', 'charity', 'emergency') NOT NULL,
  `display_order` INT(11) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`),
  KEY `type` (`type`),
  FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attributes` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `name_sw` VARCHAR(100) DEFAULT NULL,
  `category_type` ENUM('business', 'mosque', 'fundi', 'all') NOT NULL,
  `attribute_type` ENUM('boolean', 'select', 'text', 'price_range') NOT NULL,
  `options` TEXT DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `display_order` INT(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `category_type` (`category_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. BUSINESS LISTINGS (YELP-STYLE)
-- ============================================

CREATE TABLE `businesses` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `short_description` VARCHAR(255) DEFAULT NULL,
  `listing_type` ENUM('normal', 'special', 'premier') DEFAULT 'normal',
  `price_range` ENUM('$', '$$', '$$$', '$$$$') DEFAULT '$$',
  `rating_average` DECIMAL(3,2) DEFAULT 0.00,
  `rating_count` INT(11) DEFAULT 0,
  `review_count` INT(11) DEFAULT 0,
  `checkin_count` INT(11) DEFAULT 0,
  `photo_count` INT(11) DEFAULT 0,
  `is_claimed` TINYINT(1) DEFAULT 0,
  `is_verified` TINYINT(1) DEFAULT 0,
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_open` TINYINT(1) DEFAULT 1,
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `country` VARCHAR(50) DEFAULT 'Kenya',
  `phone` VARCHAR(20) DEFAULT NULL,
  `whatsapp` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `facebook` VARCHAR(255) DEFAULT NULL,
  `instagram` VARCHAR(255) DEFAULT NULL,
  `twitter` VARCHAR(255) DEFAULT NULL,
  `youtube` VARCHAR(255) DEFAULT NULL,
  `tiktok` VARCHAR(255) DEFAULT NULL,
  `opening_hours` JSON DEFAULT NULL,
  `special_hours` JSON DEFAULT NULL,
  `amenities` JSON DEFAULT NULL,
  `payment_methods` JSON DEFAULT NULL,
  `languages` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `claimed_at` TIMESTAMP NULL DEFAULT NULL,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `featured_until` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`),
  KEY `listing_type` (`listing_type`),
  KEY `is_verified` (`is_verified`),
  KEY `is_featured` (`is_featured`),
  KEY `city` (`city`),
  KEY `latitude_longitude` (`latitude`, `longitude`),
  FULLTEXT KEY `search_index` (`name`, `description`, `short_description`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `business_categories` (
  `business_id` INT(11) UNSIGNED NOT NULL,
  `category_id` INT(11) UNSIGNED NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`business_id`, `category_id`),
  KEY `category_id` (`category_id`),
  FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `business_attributes` (
  `business_id` INT(11) UNSIGNED NOT NULL,
  `attribute_id` INT(11) UNSIGNED NOT NULL,
  `value` VARCHAR(255) DEFAULT NULL,
  `value_boolean` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`business_id`, `attribute_id`),
  KEY `attribute_id` (`attribute_id`),
  FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`attribute_id`) REFERENCES `attributes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. MOSQUES DIRECTORY
-- ============================================

CREATE TABLE `mosques` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `rating_average` DECIMAL(3,2) DEFAULT 0.00,
  `rating_count` INT(11) DEFAULT 0,
  `review_count` INT(11) DEFAULT 0,
  `photo_count` INT(11) DEFAULT 0,
  `is_verified` TINYINT(1) DEFAULT 0,
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `whatsapp` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `imam_name` VARCHAR(100) DEFAULT NULL,
  `imam_phone` VARCHAR(20) DEFAULT NULL,
  `capacity` INT(11) DEFAULT NULL,
  `facilities` JSON DEFAULT NULL,
  `prayer_times_source` ENUM('api', 'manual', 'mixed') DEFAULT 'api',
  `api_location_id` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`),
  KEY `city` (`city`),
  KEY `latitude_longitude` (`latitude`, `longitude`),
  FULLTEXT KEY `search_index` (`name`, `description`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mosque_prayer_times` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mosque_id` INT(11) UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `fajr` TIME DEFAULT NULL,
  `sunrise` TIME DEFAULT NULL,
  `dhuhr` TIME DEFAULT NULL,
  `asr` TIME DEFAULT NULL,
  `maghrib` TIME DEFAULT NULL,
  `isha` TIME DEFAULT NULL,
  `jumuah` TIME DEFAULT NULL,
  `is_manual_override` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mosque_date` (`mosque_id`, `date`),
  KEY `mosque_id` (`mosque_id`),
  KEY `date` (`date`),
  FOREIGN KEY (`mosque_id`) REFERENCES `mosques`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. FUNDIS (SKILLED WORKERS)
-- ============================================

CREATE TABLE `fundis` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `profession` VARCHAR(100) NOT NULL,
  `profession_other` VARCHAR(100) DEFAULT NULL,
  `years_experience` INT(11) DEFAULT 0,
  `bio` TEXT DEFAULT NULL,
  `rating_average` DECIMAL(3,2) DEFAULT 0.00,
  `rating_count` INT(11) DEFAULT 0,
  `review_count` INT(11) DEFAULT 0,
  `portfolio_count` INT(11) DEFAULT 0,
  `is_verified` TINYINT(1) DEFAULT 0,
  `is_available` TINYINT(1) DEFAULT 1,
  `hourly_rate_min` DECIMAL(10,2) DEFAULT NULL,
  `hourly_rate_max` DECIMAL(10,2) DEFAULT NULL,
  `service_radius_km` INT(11) DEFAULT 50,
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `whatsapp` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `skills` JSON DEFAULT NULL,
  `certifications` JSON DEFAULT NULL,
  `languages` JSON DEFAULT NULL,
  `working_hours` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `profession` (`profession`),
  KEY `is_verified` (`is_verified`),
  KEY `is_available` (`is_available`),
  KEY `city` (`city`),
  KEY `latitude_longitude` (`latitude`, `longitude`),
  FULLTEXT KEY `search_index` (`profession`, `profession_other`, `bio`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fundi_categories` (
  `fundi_id` INT(11) UNSIGNED NOT NULL,
  `category_id` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`fundi_id`, `category_id`),
  KEY `category_id` (`category_id`),
  FOREIGN KEY (`fundi_id`) REFERENCES `fundis`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. REVIEWS & RATINGS (YELP CORE)
-- ============================================

CREATE TABLE `reviews` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `reviewable_id` INT(11) UNSIGNED NOT NULL,
  `reviewable_type` ENUM('business', 'mosque', 'fundi') NOT NULL,
  `rating` TINYINT(1) NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `title` VARCHAR(200) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `helpful_count` INT(11) DEFAULT 0,
  `not_helpful_count` INT(11) DEFAULT 0,
  `photos_count` INT(11) DEFAULT 0,
  `is_verified_visit` TINYINT(1) DEFAULT 0,
  `visit_date` DATE DEFAULT NULL,
  `owner_response` TEXT DEFAULT NULL,
  `owner_response_at` TIMESTAMP NULL DEFAULT NULL,
  `is_approved` TINYINT(1) DEFAULT 1,
  `is_hidden` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `reviewable` (`reviewable_type`, `reviewable_id`),
  KEY `rating` (`rating`),
  KEY `is_approved` (`is_approved`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `review_photos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `review_id` INT(11) UNSIGNED NOT NULL,
  `photo_path` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `review_id` (`review_id`),
  FOREIGN KEY (`review_id`) REFERENCES `reviews`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Review reactions: Useful / Funny / Cool (Yelp-style)
CREATE TABLE `review_helpful` (
  `review_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `reaction_type` ENUM('useful', 'funny', 'cool') NOT NULL DEFAULT 'useful',
  `is_helpful` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`, `user_id`, `reaction_type`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`review_id`) REFERENCES `reviews`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE: if upgrading an existing install, run:
--   ALTER TABLE review_helpful
--     ADD reaction_type ENUM('useful','funny','cool') NOT NULL DEFAULT 'useful' AFTER user_id,
--     DROP PRIMARY KEY, ADD PRIMARY KEY (review_id, user_id, reaction_type);

-- ============================================
-- 7. PHOTOS & MEDIA
-- ============================================

CREATE TABLE `business_photos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `photo_path` VARCHAR(255) NOT NULL,
  `thumbnail_path` VARCHAR(255) DEFAULT NULL,
  `caption` VARCHAR(255) DEFAULT NULL,
  `category` ENUM('exterior', 'interior', 'product', 'food', 'team', 'other') DEFAULT 'other',
  `is_primary` TINYINT(1) DEFAULT 0,
  `likes_count` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  KEY `user_id` (`user_id`),
  KEY `is_primary` (`is_primary`),
  FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mosque_photos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mosque_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `photo_path` VARCHAR(255) NOT NULL,
  `thumbnail_path` VARCHAR(255) DEFAULT NULL,
  `caption` VARCHAR(255) DEFAULT NULL,
  `category` ENUM('exterior', 'interior', 'prayer_hall', 'facilities', 'event', 'other') DEFAULT 'other',
  `is_primary` TINYINT(1) DEFAULT 0,
  `likes_count` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mosque_id` (`mosque_id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`mosque_id`) REFERENCES `mosques`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fundi_photos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fundi_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `photo_path` VARCHAR(255) NOT NULL,
  `thumbnail_path` VARCHAR(255) DEFAULT NULL,
  `caption` VARCHAR(255) DEFAULT NULL,
  `is_portfolio` TINYINT(1) DEFAULT 0,
  `likes_count` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fundi_id` (`fundi_id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`fundi_id`) REFERENCES `fundis`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. CHECK-INS & USER ACTIVITY
-- ============================================

CREATE TABLE `checkins` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `checkinable_id` INT(11) UNSIGNED NOT NULL,
  `checkinable_type` ENUM('business', 'mosque') NOT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `photo_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `checkinable` (`checkinable_type`, `checkinable_id`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `favorites` (
  `user_id` INT(11) UNSIGNED NOT NULL,
  `favoritable_id` INT(11) UNSIGNED NOT NULL,
  `favoritable_type` ENUM('business', 'mosque', 'fundi', 'charity') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `favoritable_id`, `favoritable_type`),
  KEY `favoritable` (`favoritable_type`, `favoritable_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. CHARITIES & DONATIONS
-- ============================================

CREATE TABLE `charities` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `category` ENUM('zakat', 'sadaqah', 'masjid', 'emergency', 'orphan', 'education', 'health', 'other') NOT NULL,
  `rating_average` DECIMAL(3,2) DEFAULT 0.00,
  `rating_count` INT(11) DEFAULT 0,
  `is_verified` TINYINT(1) DEFAULT 0,
  `registration_number` VARCHAR(50) DEFAULT NULL,
  `logo_path` VARCHAR(255) DEFAULT NULL,
  `cover_photo` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `whatsapp` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL,
  `bank_name` VARCHAR(100) DEFAULT NULL,
  `bank_account` VARCHAR(50) DEFAULT NULL,
  `paybill_number` VARCHAR(50) DEFAULT NULL,
  `mpesa_number` VARCHAR(20) DEFAULT NULL,
  `paypal_link` VARCHAR(255) DEFAULT NULL,
  `stripe_link` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`),
  KEY `category` (`category`),
  KEY `is_verified` (`is_verified`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `campaigns` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `charity_id` INT(11) UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `goal_amount` DECIMAL(12,2) NOT NULL,
  `raised_amount` DECIMAL(12,2) DEFAULT 0.00,
  `donor_count` INT(11) DEFAULT 0,
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `status` ENUM('active', 'completed', 'paused', 'cancelled') DEFAULT 'active',
  `cover_photo` VARCHAR(255) DEFAULT NULL,
  `video_url` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `charity_id` (`charity_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`charity_id`) REFERENCES `charities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `donations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id` INT(11) UNSIGNED DEFAULT NULL,
  `charity_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'KES',
  `payment_method` ENUM('mpesa', 'paypal', 'stripe', 'bank', 'cash') DEFAULT NULL,
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `donor_name` VARCHAR(100) DEFAULT NULL,
  `donor_email` VARCHAR(100) DEFAULT NULL,
  `donor_phone` VARCHAR(20) DEFAULT NULL,
  `is_anonymous` TINYINT(1) DEFAULT 0,
  `message` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  `receipt_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  KEY `charity_id` (`charity_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`charity_id`) REFERENCES `charities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. EMERGENCY NUMBERS
-- ============================================

CREATE TABLE `emergency_numbers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `category` ENUM('police', 'ambulance', 'fire', 'hospital', 'helpline', 'ngo', 'religious', 'other') NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `whatsapp` VARCHAR(20) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL,
  `is_24_7` TINYINT(1) DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `display_order` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `is_active` (`is_active`),
  KEY `city` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. ADVERTISING SYSTEM
-- ============================================

CREATE TABLE `ad_placements` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `location` ENUM('homepage_header', 'homepage_sidebar', 'listing_page', 'search_results', 'detail_page', 'mosque_page') NOT NULL,
  `width` INT(11) DEFAULT NULL,
  `height` INT(11) DEFAULT NULL,
  `max_ads` INT(11) DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `display_order` INT(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `location` (`location`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ads` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `placement_id` INT(11) UNSIGNED NOT NULL,
  `advertiser_id` INT(11) UNSIGNED DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `video_url` VARCHAR(255) DEFAULT NULL,
  `link_url` VARCHAR(255) DEFAULT NULL,
  `html_content` TEXT DEFAULT NULL,
  `impressions` INT(11) DEFAULT 0,
  `clicks` INT(11) DEFAULT 0,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('active', 'paused', 'expired', 'draft') DEFAULT 'draft',
  `priority` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `placement_id` (`placement_id`),
  KEY `advertiser_id` (`advertiser_id`),
  KEY `status` (`status`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`),
  FOREIGN KEY (`placement_id`) REFERENCES `ad_placements`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`advertiser_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ad_impressions` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `page_url` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ad_id` (`ad_id`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`ad_id`) REFERENCES `ads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ad_clicks` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `page_url` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ad_id` (`ad_id`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`ad_id`) REFERENCES `ads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. NOTIFICATIONS
-- ============================================

CREATE TABLE `notifications` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `type` ENUM('new_review', 'review_reply', 'claim_request', 'verification', 'donation', 'message', 'system', 'prayer_time') NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `data` JSON DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. REPORTS & MODERATION
-- ============================================

CREATE TABLE `reports` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporter_id` INT(11) UNSIGNED NOT NULL,
  `reportable_id` INT(11) UNSIGNED NOT NULL,
  `reportable_type` ENUM('business', 'mosque', 'fundi', 'review', 'photo', 'charity', 'user') NOT NULL,
  `reason` ENUM('spam', 'fake', 'inappropriate', 'scam', 'duplicate', 'closed', 'other') NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'investigating', 'resolved', 'rejected') DEFAULT 'pending',
  `admin_notes` TEXT DEFAULT NULL,
  `resolved_by` INT(11) UNSIGNED DEFAULT NULL,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reporter_id` (`reporter_id`),
  KEY `reportable` (`reportable_type`, `reportable_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`resolved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 14. MESSAGES (Business-Fundi Communication)
-- NOTE: messaging is delivered via WhatsApp (wa.me links) — this table is
-- kept for record-keeping/reference only and is not wired to the UI.
-- ============================================

CREATE TABLE `messages` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_id` INT(11) UNSIGNED NOT NULL,
  `recipient_id` INT(11) UNSIGNED NOT NULL,
  `subject` VARCHAR(200) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `recipient_id` (`recipient_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`recipient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 15. QUOTE REQUESTS (fundis)
-- Communication happens on WhatsApp: the site builds a wa.me link with the
-- request pre-filled; the request is stored here for tracking.
-- ============================================

CREATE TABLE `quote_requests` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fundi_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('pending', 'contacted', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fundi_id` (`fundi_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`fundi_id`) REFERENCES `fundis`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INITIAL DATA SEEDING
-- ============================================

-- Insert default categories
INSERT INTO `categories` (`name`, `name_sw`, `name_ar`, `slug`, `type`, `icon`, `display_order`) VALUES
('Restaurants', 'Mikahawa', 'مطاعم', 'restaurants', 'business', 'restaurant', 1),
('Shopping', 'Ununuzi', 'تسوق', 'shopping', 'business', 'shopping-bag', 2),
('Services', 'Huduma', 'خدمات', 'services', 'business', 'briefcase', 3),
('Health & Medical', 'Afya', 'صحة وطب', 'health-medical', 'business', 'heart', 4),
('Education', 'Elimu', 'تعليم', 'education', 'business', 'book', 5),
('Automotive', 'Magari', 'سيارات', 'automotive', 'business', 'car', 6),
('Home Services', 'Huduma za Nyumbani', 'خدمات منزلية', 'home-services', 'fundi', 'home', 1),
('Plumber', 'Fundi wa Mabomba', 'سباك', 'plumber', 'fundi', 'wrench', 2),
('Electrician', 'Fundi wa Umeme', 'كهربائي', 'electrician', 'fundi', 'bolt', 3),
('Carpenter', 'Fundi wa Kuni', 'نجار', 'carpenter', 'fundi', 'hammer', 4),
('Tailor', 'Shone', 'خياط', 'tailor', 'fundi', 'scissors', 5),
('Mechanic', 'Fundi wa Magari', 'ميكانيكي', 'mechanic', 'fundi', 'cog', 6),
('Builder', 'Fundi wa Ujenzi', 'بناء', 'builder', 'fundi', 'trowel', 7),
('Painter', 'Fundi wa Rangi', 'دهان', 'painter', 'fundi', 'brush', 8),
('Mosque', 'Msikiti', 'مسجد', 'mosque', 'mosque', 'mosque', 1),
('Islamic Center', 'Kituo cha Kiislamu', 'مركز إسلامي', 'islamic-center', 'mosque', 'kaaba', 2),
('Orphanage', 'Yatima', 'دار أيتام', 'orphanage', 'charity', 'child', 1),
('Medical Fund', 'Msaada wa Matibabu', 'صندوق طبي', 'medical-fund', 'charity', 'hospital', 2),
('Emergency Relief', 'Msaada wa Dharura', 'إغاثة طارئة', 'emergency-relief', 'charity', 'life-ring', 3);

-- Insert default attributes
INSERT INTO `attributes` (`name`, `category_type`, `attribute_type`, `options`, `icon`) VALUES
('Wheelchair Accessible', 'all', 'boolean', NULL, 'wheelchair'),
('Free WiFi', 'all', 'boolean', NULL, 'wifi'),
('Parking Available', 'all', 'boolean', NULL, 'parking'),
('Accepts Credit Cards', 'business', 'boolean', NULL, 'credit-card'),
('Delivery Available', 'business', 'boolean', NULL, 'truck'),
('Takeout Available', 'business', 'boolean', NULL, 'takeout-box'),
('Outdoor Seating', 'business', 'boolean', NULL, 'umbrella'),
('Women Prayer Area', 'mosque', 'boolean', NULL, 'woman'),
('Ablution Facilities', 'mosque', 'boolean', NULL, 'shower'),
('Friday Prayer', 'mosque', 'boolean', NULL, 'people'),
('Quran Classes', 'mosque', 'boolean', NULL, 'book-open'),
('Available for Hire', 'fundi', 'boolean', NULL, 'clock'),
('Provides Materials', 'fundi', 'boolean', NULL, 'box'),
('Emergency Service', 'fundi', 'boolean', NULL, 'alert-circle'),
('Price Range', 'all', 'price_range', '$,$$,$$$,$$$$', 'tag');

-- Insert emergency numbers
INSERT INTO `emergency_numbers` (`name`, `category`, `phone`, `description`, `is_24_7`) VALUES
('Police Emergency', 'police', '999', 'National police emergency number', 1),
('Ambulance', 'ambulance', '999', 'National ambulance service', 1),
('Fire Brigade', 'fire', '999', 'National fire emergency', 1),
('Red Cross Kenya', 'ambulance', '1044', 'Kenya Red Cross emergency', 1),
('Poison Control', 'hospital', '+254-20-2717077', 'Kenyatta National Hospital Poison Control', 0),
('Child Helpline', 'helpline', '116', 'National child protection helpline', 1);

-- Insert ad placements
INSERT INTO `ad_placements` (`name`, `location`, `width`, `height`, `max_ads`) VALUES
('Homepage Header Banner', 'homepage_header', 1200, 300, 1),
('Homepage Sidebar', 'homepage_sidebar', 300, 600, 2),
('Search Results Top', 'search_results', 728, 90, 1),
('Listing Page Sidebar', 'listing_page', 300, 250, 2),
('Detail Page Bottom', 'detail_page', 728, 90, 1);

COMMIT;
