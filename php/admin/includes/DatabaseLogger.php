<?php

/**
 * Database Logger
 * 
 * Handles logging of user sessions and deployment activities to MySQL database
 * Uses Kinsta environment variables for database connection
 */
class DatabaseLogger
{
    private static $instance = null;
    private $pdo = null;
    private $isAvailable = false;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection using Kinsta environment variables
     */
    private function connect()
    {
        try {
            // Get Kinsta database credentials from environment
            $dbHost = getenv('DB_HOST');
            $dbUser = getenv('DB_USER');
            $dbPass = getenv('DB_PASS');
            $dbName = getenv('DB_NAME') ?: 'frontline_poc'; // Default database name
            
            // Validate required credentials
            if (empty($dbHost) || empty($dbUser) || empty($dbPass)) {
                error_log('DatabaseLogger: Missing database credentials in environment variables');
                $this->isAvailable = false;
                return;
            }
            
            // Create DSN
            $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
            
            // PDO options
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            // Create PDO connection
            $this->pdo = new PDO($dsn, $dbUser, $dbPass, $options);
            $this->isAvailable = true;
            
        } catch (PDOException $e) {
            error_log('DatabaseLogger: Connection failed - ' . $e->getMessage());
            $this->isAvailable = false;
            $this->pdo = null;
        }
    }
    
    /**
     * Check if database is available
     */
    public function isAvailable()
    {
        return $this->isAvailable;
    }
    
    /**
     * Execute a prepared statement safely
     */
    private function execute($sql, $params = [])
    {
        if (!$this->isAvailable) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('DatabaseLogger: Query failed - ' . $e->getMessage());
            return false;
        }
    }
    
    // ============================================
    // USER SESSION LOGGING
    // ============================================
    
    /**
     * Log user login
     * 
     * @param string $userEmail User's email address
     * @param string $displayName User's display name
     * @param string $sessionId Session identifier
     * @param string|null $ipAddress User's IP address
     * @param string|null $userAgent User's browser agent
     * @return int|false Session record ID or false on failure
     */
    public function logUserLogin($userEmail, $displayName, $sessionId, $ipAddress = null, $userAgent = null)
    {
        $sql = "INSERT INTO user_sessions 
                (session_id, user_email, display_name, login_time, ip_address, user_agent) 
                VALUES (?, ?, ?, NOW(), ?, ?)";
        
        $stmt = $this->execute($sql, [
            $sessionId,
            $userEmail,
            $displayName,
            $ipAddress,
            $userAgent
        ]);
        
        if ($stmt) {
            return $this->pdo->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Log user logout
     * 
     * @param string $sessionId Session identifier
     * @return bool Success status
     */
    public function logUserLogout($sessionId)
    {
        $sql = "UPDATE user_sessions 
                SET logout_time = NOW(),
                    session_duration = TIMESTAMPDIFF(SECOND, login_time, NOW())
                WHERE session_id = ? AND logout_time IS NULL";
        
        $stmt = $this->execute($sql, [$sessionId]);
        return $stmt !== false;
    }
    
    /**
     * Get user session history
     * 
     * @param string $userEmail User's email address
     * @param int $limit Number of records to retrieve
     * @return array Session history
     */
    public function getUserSessions($userEmail, $limit = 10)
    {
        if (!$this->isAvailable) {
            return [];
        }
        
        $sql = "SELECT * FROM user_sessions 
                WHERE user_email = ? 
                ORDER BY login_time DESC 
                LIMIT ?";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userEmail, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('DatabaseLogger: Failed to fetch user sessions - ' . $e->getMessage());
            return [];
        }
    }
    
    // ============================================
    // DEPLOYMENT LOGGING
    // ============================================
    
    /**
     * Start deployment logging
     * 
     * @param string $deploymentId Unique deployment identifier
     * @param string $userEmail User initiating deployment
     * @param string|null $clickupTaskId Associated ClickUp task ID
     * @return bool Success status
     */
    public function startDeployment($deploymentId, $userEmail, $clickupTaskId = null)
    {
        $sql = "INSERT INTO deployments 
                (deployment_id, user_email, clickup_task_id, start_time, status) 
                VALUES (?, ?, ?, NOW(), 'running')";
        
        $stmt = $this->execute($sql, [
            $deploymentId,
            $userEmail,
            $clickupTaskId
        ]);
        
        return $stmt !== false;
    }
    
    /**
     * Update deployment status
     * 
     * @param string $deploymentId Deployment identifier
     * @param string $status Deployment status (running, completed, failed)
     * @param string|null $errorMessage Error message if failed
     * @return bool Success status
     */
    public function updateDeploymentStatus($deploymentId, $status, $errorMessage = null)
    {
        $sql = "UPDATE deployments 
                SET status = ?, 
                    error_message = ?,
                    end_time = CASE WHEN ? IN ('completed', 'failed') THEN NOW() ELSE end_time END,
                    total_duration = CASE WHEN ? IN ('completed', 'failed') THEN TIMESTAMPDIFF(SECOND, start_time, NOW()) ELSE total_duration END
                WHERE deployment_id = ?";
        
        $stmt = $this->execute($sql, [
            $status,
            $errorMessage,
            $status,
            $status,
            $deploymentId
        ]);
        
        return $stmt !== false;
    }
    
    /**
     * Update deployment site details
     * 
     * @param string $deploymentId Deployment identifier
     * @param string $siteUrl Site URL
     * @param string $adminUrl Admin URL
     * @param string $adminUsername Admin username
     * @param string $adminPassword Admin password (will be encrypted)
     * @return bool Success status
     */
    public function updateDeploymentSiteDetails($deploymentId, $siteUrl, $adminUrl, $adminUsername, $adminPassword)
    {
        // Simple encryption for password storage
        $encryptedPassword = base64_encode($adminPassword);
        
        $sql = "UPDATE deployments 
                SET site_url = ?, 
                    admin_url = ?, 
                    admin_username = ?, 
                    admin_password = ?
                WHERE deployment_id = ?";
        
        $stmt = $this->execute($sql, [
            $siteUrl,
            $adminUrl,
            $adminUsername,
            $encryptedPassword,
            $deploymentId
        ]);
        
        return $stmt !== false;
    }
    
    /**
     * Log deployment step
     * 
     * @param string $deploymentId Deployment identifier
     * @param string $stepKey Step key/identifier
     * @param string $stepName Step display name
     * @param string $status Step status (running, completed, failed, skipped)
     * @return int|false Step record ID or false on failure
     */
    public function logDeploymentStep($deploymentId, $stepKey, $stepName, $status = 'running')
    {
        $sql = "INSERT INTO deployment_steps 
                (deployment_id, step_key, step_name, start_time, step_status) 
                VALUES (?, ?, ?, NOW(), ?)";
        
        $stmt = $this->execute($sql, [
            $deploymentId,
            $stepKey,
            $stepName,
            $status
        ]);
        
        if ($stmt) {
            return $this->pdo->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update deployment step status
     * 
     * @param string $deploymentId Deployment identifier
     * @param string $stepKey Step key/identifier
     * @param string $status Step status (completed, failed, skipped)
     * @param string|null $outputLog Output log content
     * @param string|null $errorLog Error log content
     * @return bool Success status
     */
    public function updateDeploymentStep($deploymentId, $stepKey, $status, $outputLog = null, $errorLog = null)
    {
        $sql = "UPDATE deployment_steps 
                SET step_status = ?,
                    end_time = NOW(),
                    duration = TIMESTAMPDIFF(SECOND, start_time, NOW()),
                    output_log = COALESCE(?, output_log),
                    error_log = COALESCE(?, error_log)
                WHERE deployment_id = ? AND step_key = ? AND end_time IS NULL";
        
        $stmt = $this->execute($sql, [
            $status,
            $outputLog,
            $errorLog,
            $deploymentId,
            $stepKey
        ]);
        
        return $stmt !== false;
    }
    
    /**
     * Get deployment details with steps
     * 
     * @param string $deploymentId Deployment identifier
     * @return array|null Deployment details or null if not found
     */
    public function getDeploymentDetails($deploymentId)
    {
        if (!$this->isAvailable) {
            return null;
        }
        
        try {
            // Get deployment info
            $sql = "SELECT * FROM deployments WHERE deployment_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$deploymentId]);
            $deployment = $stmt->fetch();
            
            if (!$deployment) {
                return null;
            }
            
            // Decrypt password for display
            if (!empty($deployment['admin_password'])) {
                $deployment['admin_password'] = base64_decode($deployment['admin_password']);
            }
            
            // Get deployment steps
            $sql = "SELECT * FROM deployment_steps 
                    WHERE deployment_id = ? 
                    ORDER BY start_time ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$deploymentId]);
            $deployment['steps'] = $stmt->fetchAll();
            
            return $deployment;
            
        } catch (PDOException $e) {
            error_log('DatabaseLogger: Failed to fetch deployment details - ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get recent deployments
     * 
     * @param string|null $userEmail Filter by user email (optional)
     * @param int $limit Number of records to retrieve
     * @return array Deployments list
     */
    public function getRecentDeployments($userEmail = null, $limit = 10)
    {
        if (!$this->isAvailable) {
            return [];
        }
        
        try {
            $sql = "SELECT * FROM deployments ";
            $params = [];
            
            if ($userEmail) {
                $sql .= "WHERE user_email = ? ";
                $params[] = $userEmail;
            }
            
            $sql .= "ORDER BY start_time DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log('DatabaseLogger: Failed to fetch recent deployments - ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Close database connection
     */
    public function __destruct()
    {
        $this->pdo = null;
    }
}
