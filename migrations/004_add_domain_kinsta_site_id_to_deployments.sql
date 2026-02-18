-- Migration: Add domain and kinsta_site_id to deployments table
-- Date: 2026-02-18 (updated 2026-02-18)
-- Purpose: Store explicit domain and Kinsta site identifier for deployment records
-- Compatibility: uses plain ALTER statements; runner tolerates duplicate-column/index errors

-- Add columns (plain ALTER statements — runner will skip if already present)
ALTER TABLE deployments ADD COLUMN domain VARCHAR(255) NULL AFTER site_url;
ALTER TABLE deployments ADD COLUMN kinsta_site_id VARCHAR(255) NULL AFTER domain;

-- Add index (plain CREATE INDEX — runner will skip if index already exists)
CREATE INDEX idx_domain_kinsta ON deployments(domain, kinsta_site_id);

-- Note: application enforces deduplication (domain + kinsta_site_id) at write time.