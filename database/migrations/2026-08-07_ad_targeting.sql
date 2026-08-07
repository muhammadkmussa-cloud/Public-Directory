-- ============================================================
-- 2026-08-07 — Ad targeting (category + city)
-- Adds optional targeting columns to `ads`. An ad with a
-- target_category and/or target_city only serves on pages whose
-- context matches; NULL means "any" (unrestricted).
-- ============================================================

ALTER TABLE `ads`
  ADD COLUMN `target_category` VARCHAR(100) NULL DEFAULT NULL
    COMMENT 'categories.slug this ad is limited to (NULL = all)' AFTER `priority`,
  ADD COLUMN `target_city` VARCHAR(100) NULL DEFAULT NULL
    COMMENT 'city name this ad is limited to (NULL = all)' AFTER `target_category`;
