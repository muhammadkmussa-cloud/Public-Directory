-- Migration: Add business_menu_items table for visual restaurant menus

CREATE TABLE IF NOT EXISTS `business_menu_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT(11) UNSIGNED NOT NULL,
  `category` VARCHAR(100) NOT NULL DEFAULT 'Main',
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `is_popular` TINYINT(1) NOT NULL DEFAULT 0,
  `is_halal_certified` TINYINT(1) NOT NULL DEFAULT 1,
  `photo_url` VARCHAR(255) DEFAULT NULL,
  `display_order` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_biz_cat` (`business_id`, `category`, `display_order`),
  FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
