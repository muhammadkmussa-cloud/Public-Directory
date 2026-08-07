-- Migration: Add sub-rating categories to reviews table for Yelp-style multi-criterion review breakdown

ALTER TABLE `reviews`
  ADD COLUMN `rating_service` TINYINT(1) DEFAULT NULL CHECK (`rating_service` BETWEEN 1 AND 5) AFTER `rating`,
  ADD COLUMN `rating_value` TINYINT(1) DEFAULT NULL CHECK (`rating_value` BETWEEN 1 AND 5) AFTER `rating_service`,
  ADD COLUMN `rating_ambience` TINYINT(1) DEFAULT NULL CHECK (`rating_ambience` BETWEEN 1 AND 5) AFTER `rating_value`,
  ADD COLUMN `rating_cleanliness` TINYINT(1) DEFAULT NULL CHECK (`rating_cleanliness` BETWEEN 1 AND 5) AFTER `rating_ambience`;
