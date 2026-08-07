-- Migration: Add composite ad_time index for advertiser time-series analytics
-- Drops single-column ad_id index (redundant with prefix column of idx_ad_time).

ALTER TABLE ad_impressions DROP INDEX ad_id, ADD INDEX idx_ad_time (ad_id, created_at);
ALTER TABLE ad_clicks DROP INDEX ad_id, ADD INDEX idx_ad_time (ad_id, created_at);
