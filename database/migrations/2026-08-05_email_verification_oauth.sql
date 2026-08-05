-- ============================================================
-- Migration (2026-08-05): Yelp-parity features
-- Email verification + Google OAuth
--
-- For EXISTING installs that already ran database/install.php.
-- Fresh installs get these tables automatically from schema.sql,
-- so this file is only needed when upgrading an existing database.
--
-- Run:  mysql -u USER -p DB_NAME < database/migrations/2026-08-05_email_verification_oauth.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `email_verifications` (
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

CREATE TABLE IF NOT EXISTS `oauth_links` (
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

-- Existing accounts were created before email verification existed;
-- mark them verified so nobody gets locked out of their account.
UPDATE `users` SET `is_verified` = 1 WHERE `is_verified` = 0;
