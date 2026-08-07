-- ============================================================
-- Migration (2026-08-06): Yelp-parity security fix #2
-- Claim approval workflow (prevents claim squatting)
--
-- For EXISTING installs that already ran database/install.php.
-- Fresh installs get these tables automatically from schema.sql,
-- so this file is only needed when upgrading an existing database.
--
-- Run exactly ONCE:  mysql -u USER -p DB_NAME < database/migrations/2026-08-06_claim_approval.sql
-- (Do not re-run with --force: the ALTER TABLE is not idempotent and the
--  audit backfill would duplicate rows.)
-- ============================================================

-- Audit trail of every claim request, so rejected claims and their
-- review notes are never lost, and the same listing can be re-claimed
-- after a rejection.
--
-- NOTE: `claims` is HISTORY-ONLY. Ownership is always read from
-- `businesses.claim_status` / `businesses.user_id`, never from this table.
CREATE TABLE IF NOT EXISTS `claims` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `proof` VARCHAR(500) DEFAULT NULL,
  `admin_notes` VARCHAR(500) DEFAULT NULL,
  `reviewed_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fast state column on the listing itself.
ALTER TABLE `businesses`
  ADD COLUMN `claim_status` ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none' AFTER `is_claimed`;

-- Existing genuinely-claimed listings (the old claim action always set
-- is_claimed=1) keep their rights and get an audit row. Listings that only
-- had a user_id pre-assigned (e.g. seed_expansion demo owners, is_claimed=0)
-- stay 'none', exactly as a fresh install would see them.
UPDATE `businesses` SET `claim_status` = 'approved' WHERE `is_claimed` = 1;

INSERT INTO `claims` (`business_id`, `user_id`, `status`, `reviewed_by`, `created_at`, `reviewed_at`)
SELECT `id`, `user_id`, 'approved', NULL, COALESCE(`claimed_at`, NOW()), COALESCE(`claimed_at`, NOW())
FROM `businesses` WHERE `is_claimed` = 1 AND `user_id` IS NOT NULL;
