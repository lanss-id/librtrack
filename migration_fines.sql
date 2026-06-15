-- ============================================================
-- LibTrack ERP - Migration: Fine System
-- Run this on an existing database to add fine features.
-- ============================================================

-- 1. Settings table for fine configuration
CREATE TABLE IF NOT EXISTS settings (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    setting_key   VARCHAR(100)  NOT NULL UNIQUE,
    setting_value TEXT          NOT NULL,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Default fine rate: Rp1.000/day
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('fine_per_day', '1000');

-- 3. Add fine_amount column to transactions (safe: ignore if already exists)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions' AND COLUMN_NAME = 'fine_amount');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE transactions ADD COLUMN fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER notes',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
