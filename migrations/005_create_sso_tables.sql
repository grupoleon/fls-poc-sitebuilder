-- Migration: Create SSO Tables
-- Date: 2026-02-18
-- Description: Replaces config/sso.json with database-backed SSO site registry and
--              one-time verification tokens. Eliminates the need for FLS_SSO_SECRET
--              in WordPress wp-config.php by using callback-based token verification.

-- ============================================
-- SSO Sites Table
-- Registered WordPress sites allowed to use SSO
-- (replaces the "secrets" object in config/sso.json)
-- ============================================
CREATE TABLE IF NOT EXISTS sso_sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL UNIQUE COMMENT 'Hostname of the WordPress site (e.g. site.kinsta.cloud)',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Set to FALSE to revoke SSO access without deleting',
    created_by VARCHAR(255) NULL COMMENT 'Email of sitebuilder user who registered this site',
    notes TEXT NULL COMMENT 'Optional notes (deployment ID, site name, etc.)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_domain (domain),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SSO Tokens Table
-- Short-lived one-time verification tokens issued during SSO flow.
-- WP plugin exchanges token via /sso/verify for user data.
-- Token is single-use: used_at is set on first successful verification.
-- ============================================
CREATE TABLE IF NOT EXISTS sso_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE COMMENT 'bin2hex(random_bytes(32)) — 64 hex chars',
    domain VARCHAR(255) NOT NULL COMMENT 'Domain this token was issued for',
    email VARCHAR(255) NOT NULL COMMENT 'Authenticated user email',
    name VARCHAR(255) NOT NULL COMMENT 'Authenticated user display name',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL COMMENT 'Valid for 5 minutes from creation',
    used_at DATETIME NULL COMMENT 'NULL = unused, set when WP plugin verifies',
    INDEX idx_token (token),
    INDEX idx_domain (domain),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
