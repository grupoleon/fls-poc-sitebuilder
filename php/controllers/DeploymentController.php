<?php

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../services/KinstaService.php';

/**
 * DeploymentController
 * Handles deployment-related requests
 */
class DeploymentController
{
    private $configManager;
    private $deploymentManager;

    public function __construct($configManager, $deploymentManager)
    {
        $this->configManager     = $configManager;
        $this->deploymentManager = $deploymentManager;
    }

    /**
     * Trigger deployment
     */
    public function trigger(): void
    {
        $input = $this->getJsonInput(false);
        $steps = $input['steps'] ?? null;
        $force = $input['force'] ?? false;

        try {
            $this->deploymentManager->triggerDeployment($steps, $force);
            Response::success(null, 'Deployment initiated successfully');
        } catch (\Exception $e) {
            Logger::error("Deployment trigger error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Get deployment status
     */
    public function getStatus(): void
    {
        try {
            $status = $this->deploymentManager->getDeploymentStatus();
            Response::success($status);
        } catch (\Exception $e) {
            Logger::error("Get deployment status error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Get logs
     */
    public function getLogs(): void
    {
        try {
            $category = $_GET['category'] ?? '';
            $logFiles = Logger::getLogFiles($category);

            Response::success(['logs' => $logFiles]);
        } catch (\Exception $e) {
            Logger::error("Get logs error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Clear logs
     */
    public function clearLogs(): void
    {
        try {
            $days    = $_GET['days'] ?? 30;
            $deleted = Logger::clearOldLogs((int) $days);

            Response::success(
                ['deleted' => $deleted],
                "Cleared {$deleted} old log file(s)"
            );
        } catch (\Exception $e) {
            Logger::error("Clear logs error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Reset system
     */
    public function resetSystem(): void
    {
        try {
            require_once __DIR__ . '/../core/FileSystem.php';

            $baseDir = dirname(dirname(__DIR__));

            $this->deploymentManager->resetDeployment();

            $filesToDelete = [
                $baseDir . '/tmp/site_id.txt',
                $baseDir . '/tmp/operation_id.txt',
                $baseDir . '/tmp/operation_status.json',
                $baseDir . '/tmp/deployment_status.json',
                $baseDir . '/tmp/deploy_runner.sh',
            ];

            foreach ($filesToDelete as $file) {
                if (file_exists($file)) {
                    FileSystem::delete($file);
                }
            }

            $logsDir = $baseDir . '/logs';
            if (is_dir($logsDir)) {
                $logFiles = FileSystem::listFiles($logsDir, true, 'log');
                foreach ($logFiles as $logFile) {
                    FileSystem::delete($logFile);
                }
            }

            Logger::info("System reset completed successfully");
            Response::success(null, 'System reset successfully');
        } catch (\Exception $e) {
            Logger::error("System reset error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Get Kinsta site info
     */
    public function getKinstaSiteInfo(): void
    {
        try {
            $kinstaService = KinstaService::fromConfig($this->configManager);

            $siteId = $this->getSiteId();

            if (empty($siteId)) {
                Response::error('Site ID not found. Please complete deployment first.');
            }

            $siteInfo = $kinstaService->getSiteInfo($siteId);
            Response::success($siteInfo);
        } catch (\Exception $e) {
            Logger::error("Get Kinsta site info error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Check if site exists in Kinsta
     */
    public function checkSiteExists(): void
    {
        $siteTitle = $_GET['site_title'] ?? '';

        if (empty($siteTitle)) {
            Response::error('Site title parameter is required');
        }

        try {
            $kinstaService = KinstaService::fromConfig($this->configManager);
            $siteConfig    = $this->configManager->getConfig('site');
            $companyId     = $siteConfig['company'] ?? null;

            if (empty($companyId)) {
                Response::error('Company ID not configured');
            }

            $result = $kinstaService->checkSiteExists($siteTitle, $companyId);
            Response::success($result);
        } catch (\Exception $e) {
            Logger::error("Check site exists error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Get available Kinsta regions
     */
    public function getAvailableRegions(): void
    {
        try {
            $kinstaService = KinstaService::fromConfig($this->configManager);
            $siteConfig    = $this->configManager->getConfig('site');
            $companyId     = $siteConfig['company'] ?? null;

            if (empty($companyId)) {
                Response::error('Company ID not configured');
            }

            $regions = $kinstaService->getAvailableRegions($companyId);
            Response::success(['regions' => $regions]);
        } catch (\Exception $e) {
            Logger::error("Get available regions error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Delete site from Kinsta
     */
    public function deleteKinstaSite(): void
    {
        try {
            $kinstaService = KinstaService::fromConfig($this->configManager);
            $siteId        = $this->getSiteId();

            if (empty($siteId)) {
                Response::error('Site ID not found');
            }

            $success = $kinstaService->deleteSite($siteId);

            if ($success) {
                require_once __DIR__ . '/../core/FileSystem.php';
                $siteIdFile = dirname(dirname(__DIR__)) . '/tmp/site_id.txt';
                FileSystem::delete($siteIdFile);

                Response::success(null, 'Site deleted successfully from Kinsta');
            } else {
                Response::error('Failed to delete site from Kinsta');
            }
        } catch (\Exception $e) {
            Logger::error("Delete Kinsta site error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Get site ID from file or deployment status
     */
    private function getSiteId(): string
    {
        $baseDir    = dirname(dirname(__DIR__));
        $siteIdFile = $baseDir . '/tmp/site_id.txt';

        if (file_exists($siteIdFile)) {
            return trim(file_get_contents($siteIdFile));
        }

        try {
            $status = $this->deploymentManager->getDeploymentStatus();
            return $status['site_id'] ?? '';
        } catch (\Exception $e) {
            Logger::debug("Could not get site ID from deployment status");
            return '';
        }
    }

    /**
     * Get JSON input from request body
     */
    private function getJsonInput(bool $required = true): array
    {
        $rawInput = file_get_contents('php://input');
        $input    = json_decode($rawInput, true);

        if ($required && json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON input');
        }

        return $input ?: [];
    }

    /**
     * List all log files
     */
    public function listLogFiles(): void
    {
        try {
            $baseDir  = dirname(dirname(__DIR__));
            $logsDir  = $baseDir . '/logs';
            $tmpDir   = $baseDir . '/tmp';
            $logFiles = [];

            // Recursive function to scan directories
            $scanDirectory = function ($dir, $prefix = '', $category = 'Logs') use (&$scanDirectory, &$logFiles, $logsDir) {
                if (! is_dir($dir)) {
                    return;
                }

                $items = scandir($dir);
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') {
                        continue;
                    }

                    $fullPath     = $dir . '/' . $item;
                    $relativePath = $prefix . $item;

                    if (is_dir($fullPath)) {
                        // Determine category based on subdirectory name
                        $subCategory = ucfirst($item);
                        if (in_array($item, ['api', 'deployment', 'webhook'])) {
                            $subCategory = ucfirst($item);
                        } else {
                            $subCategory = $category;
                        }
                        $scanDirectory($fullPath, $relativePath . '/', $subCategory);
                    } elseif (is_file($fullPath)) {
                        // Determine category based on file name
                        $fileCategory = $category;
                        if (strpos($item, 'webhook') === 0) {
                            $fileCategory = 'Webhook';
                        } elseif (strpos($item, 'deployment') === 0) {
                            $fileCategory = 'Deployment';
                        } elseif (strpos($item, 'api') === 0) {
                            $fileCategory = 'API';
                        } elseif (in_array($item, ['errors.log', 'system.log'])) {
                            $fileCategory = 'System';
                        }

                        $logFiles[] = [
                            'name'     => $item,
                            'path'     => $relativePath,
                            'fullPath' => $fullPath,
                            'size'     => filesize($fullPath),
                            'modified' => filemtime($fullPath),
                            'category' => $fileCategory,
                        ];
                    }
                }
            };

            // Scan logs directory
            $scanDirectory($logsDir, '', 'Logs');

            // Scan tmp directory for status JSON files
            if (is_dir($tmpDir)) {
                $tmpItems = scandir($tmpDir);
                foreach ($tmpItems as $item) {
                    if ($item === '.' || $item === '..') {
                        continue;
                    }

                    $fullPath = $tmpDir . '/' . $item;
                    if (is_file($fullPath) && (strpos($item, '.json') !== false || strpos($item, '.txt') !== false)) {
                        $logFiles[] = [
                            'name'     => $item,
                            'path'     => 'tmp/' . $item,
                            'fullPath' => $fullPath,
                            'size'     => filesize($fullPath),
                            'modified' => filemtime($fullPath),
                            'category' => 'Status',
                        ];
                    }
                }
            }

            // Sort by modified time (newest first)
            usort($logFiles, function ($a, $b) {
                return $b['modified'] - $a['modified'];
            });

            Response::success(['files' => $logFiles]);
        } catch (\Exception $e) {
            Logger::error("List log files error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Read log file content
     */
    public function readLogFile(): void
    {
        try {
            $filePath = $_GET['file'] ?? '';

            if (empty($filePath)) {
                Response::error('File path is required');
            }

            $baseDir = dirname(dirname(__DIR__));
            $logsDir = realpath($baseDir . '/logs');
            $tmpDir  = realpath($baseDir . '/tmp');

            // Determine which directory to use based on path
            if (strpos($filePath, 'tmp/') === 0) {
                $allowedDir    = $tmpDir;
                $relativePath  = substr($filePath, 4); // Remove 'tmp/' prefix
                $requestedFile = realpath($tmpDir . '/' . $relativePath);
            } else {
                $allowedDir    = $logsDir;
                $requestedFile = realpath($logsDir . '/' . $filePath);
            }

            // Security: ensure the path is within allowed directories
            if ($requestedFile === false || strpos($requestedFile, $allowedDir) !== 0) {
                Response::error('Invalid file path');
            }

            if (! file_exists($requestedFile)) {
                Response::error('File not found');
            }

            // Read file content
            $content = file_get_contents($requestedFile);

            // If it's a JSON file, pretty format it
            if (pathinfo($requestedFile, PATHINFO_EXTENSION) === 'json') {
                $jsonData = json_decode($content, true);
                if ($jsonData !== null) {
                    $content = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                }
            }

            Response::success([
                'content'  => $content,
                'size'     => filesize($requestedFile),
                'modified' => filemtime($requestedFile),
            ]);
        } catch (\Exception $e) {
            Logger::error("Read log file error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Clear deployment status
     */
    public function clearDeploymentStatus(): void
    {
        try {
            $statusFile = dirname(dirname(__DIR__)) . '/tmp/deployment_status.json';

            if (file_exists($statusFile)) {
                if (unlink($statusFile)) {
                    Logger::info('Deployment status cleared');
                    Response::success(null, 'Deployment status cleared successfully');
                } else {
                    Response::error('Failed to clear deployment status');
                }
            } else {
                Response::success(null, 'No deployment status to clear');
            }
        } catch (\Exception $e) {
            Logger::error("Clear deployment status error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }
}
