-- Migration: Create Logging Tables for User Sessions and Deployments
-- Date: 2026-02-05
-- Description: Creates tables for tracking user login/logout and deployment activities

-- ============================================
-- Users Table
-- Stores user information from first login
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) NULL,
    picture_url TEXT NULL,
    first_login_at DATETIME NOT NULL,
    last_login_at DATETIME NULL,
    login_count INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_last_login (last_login_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- User Sessions Table
-- Tracks user login and logout activities
-- ============================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NULL,
    login_time DATETIME NOT NULL,
    logout_time DATETIME NULL,
    session_duration INT NULL COMMENT 'Duration in seconds',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_email (user_email),
    INDEX idx_session_id (session_id),
    INDEX idx_login_time (login_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Deployments Table
-- Tracks deployment runs with user and task information
-- ============================================
CREATE TABLE IF NOT EXISTS deployments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deployment_id VARCHAR(255) NOT NULL UNIQUE COMMENT 'Unique identifier for each deployment',
    user_email VARCHAR(255) NOT NULL,
    clickup_task_id VARCHAR(100) NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NULL,
    total_duration INT NULL COMMENT 'Total duration in seconds',
    status ENUM('running', 'completed', 'failed') NOT NULL DEFAULT 'running',
    site_url VARCHAR(500) NULL,
    admin_url VARCHAR(500) NULL,
    admin_username VARCHAR(255) NULL,
    admin_password TEXT NULL COMMENT 'Encrypted password',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_deployment_id (deployment_id),
    INDEX idx_user_email (user_email),
    INDEX idx_status (status),
    INDEX idx_start_time (start_time),
    INDEX idx_clickup_task_id (clickup_task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Deployment Steps Table
-- Tracks individual steps within each deployment
-- ============================================
CREATE TABLE IF NOT EXISTS deployment_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deployment_id VARCHAR(255) NOT NULL,
    step_name VARCHAR(255) NOT NULL,
    step_key VARCHAR(100) NOT NULL COMMENT 'Internal step identifier',
    step_status ENUM('running', 'completed', 'failed', 'skipped') NOT NULL DEFAULT 'running',
    start_time DATETIME NOT NULL,
    end_time DATETIME NULL,
    duration INT NULL COMMENT 'Duration in seconds',
    output_log TEXT NULL,
    error_log TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (deployment_id) REFERENCES deployments(deployment_id) ON DELETE CASCADE,
    INDEX idx_deployment_id (deployment_id),
    INDEX idx_step_status (step_status),
    INDEX idx_start_time (start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Sample Queries
-- ============================================

-- Query to get all deployments by a user
-- SELECT * FROM deployments WHERE user_email = 'user@example.com' ORDER BY start_time DESC;

-- Query to get deployment details with steps
-- SELECT d.*, ds.step_name, ds.step_status, ds.duration 
-- FROM deployments d 
-- LEFT JOIN deployment_steps ds ON d.deployment_id = ds.deployment_id 
-- WHERE d.deployment_id = 'your_deployment_id';

-- Query to get user login history
-- SELECT * FROM user_sessions WHERE user_email = 'user@example.com' ORDER BY login_time DESC;

-- Query to calculate average deployment time
-- SELECT AVG(total_duration) as avg_duration FROM deployments WHERE status = 'completed';

-- Query to get failed deployments
-- SELECT * FROM deployments WHERE status = 'failed' ORDER BY start_time DESC;
