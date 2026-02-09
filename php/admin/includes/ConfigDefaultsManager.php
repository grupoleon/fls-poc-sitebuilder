<?php

/**
 * Config Defaults Manager
 *
 * Handles saving and loading default configuration files to/from database
 */
class ConfigDefaultsManager
{
    private $pdo;
    private $isAvailable = false;
    private $configDir;

    public function __construct()
    {
        $this->configDir = dirname(dirname(dirname(__DIR__))) . '/config';
        $this->connect();
    }

    /**
     * Establish database connection using Kinsta environment variables
     */
    private function connect()
    {
        try {
            $dbHost = getenv('DB_HOST');
            $dbUser = getenv('DB_USER');
            $dbPass = getenv('DB_PASSWORD') ?: getenv('DB_PASS');
            $dbPort = getenv('DB_PORT') ?: '3306';
            $dbName = getenv('DB_NAME') ?: 'frontline_poc';

            if (empty($dbHost) || empty($dbUser) || empty($dbPass)) {
                error_log('ConfigDefaultsManager: Missing required database credentials');
                $this->isAvailable = false;
                return;
            }

            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            $this->pdo         = new PDO($dsn, $dbUser, $dbPass, $options);
            $this->isAvailable = true;

            error_log('ConfigDefaultsManager: Connected successfully to database');
        } catch (PDOException $e) {
            error_log('ConfigDefaultsManager: Connection failed - ' . $e->getMessage());
            $this->isAvailable = false;
            $this->pdo         = null;
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
     * Save a config file as default to database
     *
     * @param string $filename The config file name
     * @param string $userEmail Email of user saving the default
     * @return array Result with success status and message
     */
    public function saveDefault($filename, $userEmail = null)
    {
        if (!$this->isAvailable) {
            return [
                'success' => false,
                'message' => 'Database is not available'
            ];
        }

        try {
            // Read the config file
            $filePath = $this->configDir . '/' . $filename;

            if (!file_exists($filePath)) {
                return [
                    'success' => false,
                    'message' => "Config file not found: {$filename}"
                ];
            }

            $rawContent = file_get_contents($filePath);

            // Validate JSON
            $jsonData = json_decode($rawContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'message' => 'Invalid JSON in config file: ' . json_last_error_msg()
                ];
            }

            // Beautify JSON for storage
            $rawContent = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $fileHash = hash('sha256', $rawContent);

            // Check if default already exists
            $stmt = $this->pdo->prepare('SELECT id FROM config_defaults WHERE filename = ?');
            $stmt->execute([$filename]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Update existing default
                $stmt = $this->pdo->prepare(
                    'UPDATE config_defaults 
                     SET raw_content = ?, file_hash = ?, updated_by = ?, updated_at = NOW() 
                     WHERE filename = ?'
                );
                $stmt->execute([$rawContent, $fileHash, $userEmail, $filename]);

                error_log("ConfigDefaultsManager: Updated default for {$filename}");

                return [
                    'success' => true,
                    'message' => "Default configuration for {$filename} updated successfully",
                    'action' => 'updated'
                ];
            } else {
                // Insert new default
                $stmt = $this->pdo->prepare(
                    'INSERT INTO config_defaults (filename, raw_content, file_hash, created_by, updated_by) 
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$filename, $rawContent, $fileHash, $userEmail, $userEmail]);

                error_log("ConfigDefaultsManager: Created default for {$filename}");

                return [
                    'success' => true,
                    'message' => "Default configuration for {$filename} saved successfully",
                    'action' => 'created'
                ];
            }
        } catch (PDOException $e) {
            error_log('ConfigDefaultsManager: Failed to save default - ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log('ConfigDefaultsManager: Failed to save default - ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Load a default config and write it to the file system
     *
     * @param string $filename The config file name
     * @return array Result with success status and message
     */
    public function loadDefault($filename)
    {
        if (!$this->isAvailable) {
            return [
                'success' => false,
                'message' => 'Database is not available'
            ];
        }

        try {
            // Fetch default from database
            $stmt = $this->pdo->prepare('SELECT raw_content FROM config_defaults WHERE filename = ?');
            $stmt->execute([$filename]);
            $result = $stmt->fetch();

            if (!$result) {
                return [
                    'success' => false,
                    'message' => "No default found for {$filename}"
                ];
            }

            $rawContent = $result['raw_content'];

            // Validate JSON before writing
            $jsonData = json_decode($rawContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'message' => 'Invalid JSON in stored default: ' . json_last_error_msg()
                ];
            }

            // Create backup of current file
            $filePath = $this->configDir . '/' . $filename;
            if (file_exists($filePath)) {
                $backupPath = $this->configDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . 
                              '_backup_' . date('Y-m-d_H-i-s') . '.json';
                copy($filePath, $backupPath);
                error_log("ConfigDefaultsManager: Created backup at {$backupPath}");
            }

            // Write default to file
            $result = file_put_contents($filePath, $rawContent);

            if ($result === false) {
                return [
                    'success' => false,
                    'message' => "Failed to write config file: {$filename}"
                ];
            }

            error_log("ConfigDefaultsManager: Loaded default for {$filename}");

            return [
                'success' => true,
                'message' => "Default configuration loaded for {$filename}"
            ];
        } catch (PDOException $e) {
            error_log('ConfigDefaultsManager: Failed to load default - ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log('ConfigDefaultsManager: Failed to load default - ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if a default exists for a config file
     *
     * @param string $filename The config file name
     * @return bool True if default exists
     */
    public function hasDefault($filename)
    {
        if (!$this->isAvailable) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM config_defaults WHERE filename = ?');
            $stmt->execute([$filename]);
            $result = $stmt->fetch();

            return $result && $result['count'] > 0;
        } catch (PDOException $e) {
            error_log('ConfigDefaultsManager: Failed to check default - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all config files that have defaults
     *
     * @return array List of config files with default info
     */
    public function getAllDefaults()
    {
        if (!$this->isAvailable) {
            return [];
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT filename, created_by, updated_by, created_at, updated_at 
                 FROM config_defaults 
                 ORDER BY filename'
            );
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('ConfigDefaultsManager: Failed to fetch defaults - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get default info for a specific config file
     *
     * @param string $filename The config file name
     * @return array|null Default info or null if not found
     */
    public function getDefaultInfo($filename)
    {
        if (!$this->isAvailable) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT filename, created_by, updated_by, created_at, updated_at 
                 FROM config_defaults 
                 WHERE filename = ?'
            );
            $stmt->execute([$filename]);

            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log('ConfigDefaultsManager: Failed to fetch default info - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Load all defaults to file system (used during deployment)
     *
     * @return array Summary of loaded defaults
     */
    public function loadAllDefaults()
    {
        if (!$this->isAvailable) {
            return [
                'success' => false,
                'message' => 'Database is not available',
                'loaded' => [],
                'failed' => []
            ];
        }

        $loaded = [];
        $failed = [];

        try {
            $defaults = $this->getAllDefaults();

            foreach ($defaults as $default) {
                $filename = $default['filename'];
                $result = $this->loadDefault($filename);

                if ($result['success']) {
                    $loaded[] = $filename;
                } else {
                    $failed[] = [
                        'filename' => $filename,
                        'error' => $result['message']
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'Loaded ' . count($loaded) . ' default configs',
                'loaded' => $loaded,
                'failed' => $failed
            ];
        } catch (Exception $e) {
            error_log('ConfigDefaultsManager: Failed to load all defaults - ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'loaded' => $loaded,
                'failed' => $failed
            ];
        }
    }

    /**
     * Delete a default config
     *
     * @param string $filename The config file name
     * @return array Result with success status and message
     */
    public function deleteDefault($filename)
    {
        if (!$this->isAvailable) {
            return [
                'success' => false,
                'message' => 'Database is not available'
            ];
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM config_defaults WHERE filename = ?');
            $stmt->execute([$filename]);

            if ($stmt->rowCount() > 0) {
                error_log("ConfigDefaultsManager: Deleted default for {$filename}");
                return [
                    'success' => true,
                    'message' => "Default configuration for {$filename} deleted successfully"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "No default found for {$filename}"
                ];
            }
        } catch (PDOException $e) {
            error_log('ConfigDefaultsManager: Failed to delete default - ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
}
