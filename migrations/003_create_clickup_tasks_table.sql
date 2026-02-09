-- Migration: Create ClickUp Tasks Table
-- Date: 2026-02-09
-- Description: Stores ClickUp task IDs so tasks can be auto-recovered if JSON files are deleted

CREATE TABLE IF NOT EXISTS clickup_tasks (
    task_id VARCHAR(100) PRIMARY KEY COMMENT 'ClickUp task ID (e.g., 86dzkunb6)',
    task_name VARCHAR(500) NULL COMMENT 'Task name for display',
    status VARCHAR(100) NULL COMMENT 'Last known ClickUp task status',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_last_fetched_at (last_fetched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
