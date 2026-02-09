-- Migration: Create Config Defaults Table
-- Date: 2026-02-09
-- Description: Creates table for storing default configuration files

-- ============================================
-- Config Defaults Table
-- Stores default configuration files as raw JSON
-- ============================================
CREATE TABLE IF NOT EXISTS config_defaults (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE COMMENT 'Configuration file name (e.g., config.json)',
    raw_content LONGTEXT NOT NULL COMMENT 'Raw JSON content of the configuration file',
    file_hash VARCHAR(64) NULL COMMENT 'SHA-256 hash of content for change detection',
    created_by VARCHAR(255) NULL COMMENT 'Email of user who created the default',
    updated_by VARCHAR(255) NULL COMMENT 'Email of user who last updated the default',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_filename (filename),
    INDEX idx_created_at (created_at),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Sample Queries
-- ============================================

-- Query to get all default configs
-- SELECT filename, created_at, updated_at FROM config_defaults ORDER BY filename;

-- Query to get a specific default config
-- SELECT raw_content FROM config_defaults WHERE filename = 'config.json';

-- Query to check if a default exists
-- SELECT COUNT(*) as exists_count FROM config_defaults WHERE filename = 'config.json';
