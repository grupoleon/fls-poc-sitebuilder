<?php

// Include required classes
require_once __DIR__ . '/admin/includes/ConfigManager.php';
require_once __DIR__ . '/admin/includes/PageContentManager.php';
require_once __DIR__ . '/admin/includes/DeploymentManager.php';

// Initialize managers
$configManager     = new ConfigManager();
$pageManager       = new PageContentManager();
$deploymentManager = new DeploymentManager();

// Handle AJAX requests
if (isset($_SERVER['REQUEST_METHOD']) &&
    ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action']))) {
    handleRequest();
    exit;
}

/**
 * Deep merge configuration arrays, preserving structure
 */
function deepMergeConfig($existing, $new)
{
    foreach ($new as $key => $value) {
        if (is_array($value) && isset($existing[$key]) && is_array($existing[$key])) {
            // Check if this is an indexed array (like menu_items, plugins.install, etc.)
            // If so, replace entirely instead of merging to allow deletions
            if (array_keys($value) === range(0, count($value) - 1) || empty($value)) {
                // This is an indexed array or empty array - replace entirely
                $existing[$key] = $value;
            } else {
                // This is an associative array - merge recursively
                $existing[$key] = deepMergeConfig($existing[$key], $value);
            }
        } else {
            $existing[$key] = $value;
        }
    }
    return $existing;
}

/**
 * Handle AJAX and API requests
 */
function handleRequest()
{
    global $configManager, $pageManager, $deploymentManager;

    header('Content-Type: application/json');

    try {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        switch ($action) {
            case 'get_configs':
                $mainConfig = $configManager->getConfig('main') ?: $configManager->getConfig('config');
                echo json_encode([
                    'success' => true,
                    'data'    => [
                        'git'    => $configManager->getConfig('git'),
                        'site'   => $configManager->getConfig('site'),
                        'main'   => $mainConfig,
                        'config' => $mainConfig, // Fallback for different naming
                    ],
                ]);
                break;

            case 'get_themes':
                echo json_encode([
                    'success' => true,
                    'data'    => $pageManager->getAvailableThemes(),
                ]);
                break;

            case 'save_config':
                $input = json_decode(file_get_contents('php://input'), true);
                $type  = $input['type'] ?? '';
                $data  = $input['data'] ?? [];

                // Debug logging
                error_log("Save config - Type: $type, Data: " . json_encode($data, JSON_PRETTY_PRINT));

                try {
                    // Handle different config types - some save to main config, others to their own files
                    switch ($type) {
                        case 'git':
                        case 'site':
                            $configManager->updateConfig($type, $data);
                            break;

                        case 'main':
                        case 'security':
                        case 'integrations':
                        case 'plugins':
                        case 'policies':
                        default:
                            // These are saved as part of the main config
                            $debugFile = __DIR__ . '/../logs/save_debug.log';
                            file_put_contents($debugFile, "[" . date('Y-m-d H:i:s') . "] Save config - Processing main config merge for type: $type\n", FILE_APPEND);

                            $mainConfig = $configManager->getConfig('main');
                            file_put_contents($debugFile, "[" . date('Y-m-d H:i:s') . "] Original main config: " . json_encode($mainConfig, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
                            file_put_contents($debugFile, "[" . date('Y-m-d H:i:s') . "] Data to merge: " . json_encode($data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

                            // Smart merge - preserve existing structure but update provided values
                            $mergedConfig = deepMergeConfig($mainConfig, $data);
                            file_put_contents($debugFile, "[" . date('Y-m-d H:i:s') . "] Merged config: " . json_encode($mergedConfig, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

                            $configManager->updateConfig('main', $mergedConfig);

                            // Verify the save worked
                            $verifyConfig = $configManager->getConfig('main');
                            file_put_contents($debugFile, "[" . date('Y-m-d H:i:s') . "] Verification config: " . json_encode($verifyConfig, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
                            break;
                    }

                    echo json_encode(['success' => true, 'message' => 'Configuration saved successfully']);
                } catch (Exception $e) {
                    error_log("Config save error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;

            case 'save_active_theme':
                $input = json_decode(file_get_contents('php://input'), true);
                $theme = $input['theme'] ?? '';

                if (empty($theme)) {
                    echo json_encode(['success' => false, 'message' => 'Theme is required']);
                    break;
                }

                try {
                    $themeConfig                 = $configManager->getConfig('theme');
                    $themeConfig['active_theme'] = $theme;
                    $configManager->updateConfig('theme', $themeConfig);

                    echo json_encode(['success' => true, 'message' => 'Active theme updated successfully']);
                } catch (Exception $e) {
                    error_log("Theme save error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;

            case 'get_pages':
                $themes      = $configManager->getAvailableThemes();
                $themeConfig = $configManager->getConfig('theme');
                $activeTheme = $themeConfig['active_theme'] ?? ($themes[0] ?? 'FLS-One');

                echo json_encode([
                    'success' => true,
                    'data'    => [
                        'themes'       => $themes,
                        'active_theme' => $activeTheme,
                    ],
                ]);
                break;

            case 'get_theme_pages':
                $theme = $_GET['theme'] ?? '';
                $pages = $pageManager->getThemePages($theme);

                echo json_encode([
                    'success' => true,
                    'data'    => ['pages' => $pages],
                ]);
                break;

            case 'get_theme_pages_with_names':
                $theme = $_GET['theme'] ?? '';
                $pages = $pageManager->getThemePagesWithNames($theme);

                echo json_encode([
                    'success' => true,
                    'data'    => ['pages' => $pages],
                ]);
                break;

            case 'get_page_content':
                $theme = $_GET['theme'] ?? '';
                $page  = $_GET['page'] ?? '';

                $content = $pageManager->extractEditableContent($theme, $page);
                echo json_encode(['success' => true, 'data' => $content]);
                break;

            case 'save_page_content':
                $input = json_decode(file_get_contents('php://input'), true);
                $theme = $input['theme'] ?? '';
                $page  = $input['page'] ?? '';

                // Support both old format (sections) and new format (full data)
                if (isset($input['sections'])) {
                    // Legacy format
                    $sections = $input['sections'];

                    // Validate required fields
                    if (empty($theme)) {
                        throw new Exception('Theme is required for saving page content');
                    }

                    if (empty($page)) {
                        throw new Exception('Page is required for saving page content');
                    }

                    foreach ($sections as $index => $sectionData) {
                        $pageManager->updateWidgetContent($theme, $page, $index, $sectionData);
                    }
                } else {
                    // New format with full page data (widgets, grids, etc.)
                    $data = $input['data'] ?? [];

                    // Validate required fields
                    if (empty($theme)) {
                        throw new Exception('Theme is required for saving page content');
                    }

                    if (empty($page)) {
                        throw new Exception('Page is required for saving page content');
                    }

                    $pageManager->saveFullPageData($theme, $page, $data);
                }

                echo json_encode(['success' => true, 'message' => 'Page content saved successfully']);
                break;

            case 'upload_image':
                if (isset($_FILES['image'])) {
                    $folder   = $_POST['folder'] ?? 'general';
                    $filename = $pageManager->handleImageUpload($_FILES['image'], $folder);
                    echo json_encode([
                        'success' => true,
                        'data'    => [
                            'filename' => $filename,
                            'url'      => 'uploads/images/' . $filename, // Fixed: Remove ../ relative path
                        ],
                    ]);
                } else {
                    throw new Exception('No image file provided');
                }
                break;

            case 'upload_logo':
                if (isset($_FILES['logo'])) {
                    $filename = $pageManager->handleLogoUpload($_FILES['logo']);
                    echo json_encode([
                        'success' => true,
                        'data'    => [
                            'filename' => $filename,
                            'url'      => 'uploads/images/' . $filename, // Fixed: Remove ../ relative path
                        ],
                    ]);
                } else {
                    throw new Exception('No logo file provided');
                }
                break;

            case 'get_current_logo':
                $logoInfo = $pageManager->getCurrentLogo();
                echo json_encode([
                    'success' => true,
                    'data'    => $logoInfo,
                ]);
                break;

            case 'get_other_contents':
                $type = $_GET['type'] ?? '';

                if (! in_array($type, ['issues', 'endorsements', 'news', 'posts', 'testimonials', 'sliders', 'forms'])) {
                    throw new Exception('Invalid content type. Must be "issues", "endorsements", "news", "posts", "testimonials", "sliders", or "forms".');
                }

                $contents = $pageManager->getOtherContents($type);
                echo json_encode([
                    'success' => true,
                    'data'    => $contents,
                ]);
                break;

            case 'save_other_contents':
                $input    = json_decode(file_get_contents('php://input'), true);
                $type     = $input['type'] ?? '';
                $contents = $input['contents'] ?? [];

                if (! in_array($type, ['issues', 'endorsements', 'news', 'posts', 'testimonials', 'sliders', 'forms'])) {
                    throw new Exception('Invalid content type. Must be "issues", "endorsements", "news", "posts", "testimonials", "sliders", or "forms".');
                }

                $pageManager->saveOtherContents($type, $contents);
                echo json_encode([
                    'success' => true,
                    'message' => ucfirst($type) . ' content saved successfully',
                ]);
                break;

            case 'delete_other_content':
                $type = $_GET['type'] ?? $_POST['type'] ?? '';
                $id   = $_GET['id'] ?? $_POST['id'] ?? '';

                if (! in_array($type, ['forms'])) {
                    throw new Exception('Invalid content type for deletion.');
                }

                if (empty($id)) {
                    throw new Exception('Content ID is required for deletion.');
                }

                // Delete the specific form file
                $formsDir = __DIR__ . '/../pages/forms';
                $formFile = $formsDir . '/' . $id . '.json';

                if (file_exists($formFile)) {
                    if (unlink($formFile)) {
                        echo json_encode([
                            'success' => true,
                            'message' => ucfirst($type) . ' deleted successfully',
                        ]);
                    } else {
                        throw new Exception('Failed to delete ' . $type . ' file.');
                    }
                } else {
                    throw new Exception('Content file not found.');
                }
                break;

            case 'deploy':
                // Set a shorter timeout for deployment trigger
                set_time_limit(10);

                $input = json_decode(file_get_contents('php://input'), true);
                $step  = $input['step'] ?? null;
                $force = $input['force'] ?? false;

                try {
                    // Update site config if site_title or theme provided
                    if (isset($input['site_title']) || isset($input['theme'])) {
                        $siteConfig = $configManager->getConfig('site');

                        if (isset($input['site_title'])) {
                            $siteConfig['site_title'] = $input['site_title'];
                        }

                        if (isset($input['display_name'])) {
                            $siteConfig['display_name'] = $input['display_name'];
                        }

                        $configManager->updateConfig('site', $siteConfig);
                    }

                    $result = $deploymentManager->triggerDeployment($step, $force);
                    echo json_encode(['success' => true, 'data' => $result, 'step' => $step, 'force' => $force]);
                } catch (Exception $e) {
                    error_log("Deployment trigger error: " . $e->getMessage());
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to start deployment: ' . $e->getMessage(),
                    ]);
                }
                break;

            case 'deploy_again':
                // Set a shorter timeout for deployment trigger
                set_time_limit(10);

                try {
                    // Trigger deployment with deploy step only (uses existing credentials)
                    $result = $deploymentManager->triggerDeploymentAgain();
                    echo json_encode(['success' => true, 'data' => $result, 'message' => 'Deployment started with existing credentials']);
                } catch (Exception $e) {
                    error_log("Deploy Again trigger error: " . $e->getMessage());
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to start deployment: ' . $e->getMessage(),
                    ]);
                }
                break;

            case 'deployment_status':
                $status = $deploymentManager->getDeploymentStatus();
                // Add site config to the status response
                $siteConfig             = $configManager->getConfig('site');
                $status['site_title']   = $siteConfig['site_title'] ?? '';
                $status['display_name'] = $siteConfig['display_name'] ?? '';
                echo json_encode(['success' => true, 'data' => $status]);
                break;

            case 'deployment_logs':
                $lines    = isset($_GET['lines']) ? (int) $_GET['lines'] : 50;
                $lastRead = isset($_GET['last_read']) ? (int) $_GET['last_read'] : null;
                $logs     = $deploymentManager->getDeploymentLogs($lines, $lastRead);
                echo json_encode([
                    'success'   => true,
                    'data'      => $logs,
                    'timestamp' => time(),
                ]);
                break;

            case 'deployment_history':
                $history = $deploymentManager->getDeploymentHistory();
                echo json_encode(['success' => true, 'data' => $history]);
                break;

            case 'github_actions_status':
                $githubStatus = $deploymentManager->checkGitHubActionsStatus();
                echo json_encode(['success' => true, 'data' => $githubStatus]);
                break;

            case 'github_actions_logs':
                $runId      = $_GET['run_id'] ?? null;
                $githubLogs = $deploymentManager->getGitHubActionsLogs($runId);
                echo json_encode(['success' => true, 'data' => $githubLogs]);
                break;

            case 'reset_system':
                // Clear temporary files, logs, and reset deployment status
                $resetSuccess = resetSystem();
                echo json_encode([
                    'success' => $resetSuccess,
                    'message' => $resetSuccess ? 'System reset successfully' : 'Failed to reset system',
                ]);
                break;

            case 'clear_github_run_id':
                // Clear the stored GitHub run ID to force fresh status checking
                $runIdFile = __DIR__ . '/../tmp/github_run_id.txt';
                if (file_exists($runIdFile)) {
                    $cleared = unlink($runIdFile);
                    echo json_encode([
                        'success' => $cleared,
                        'message' => $cleared ? 'GitHub run ID cleared successfully' : 'Failed to clear GitHub run ID',
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => 'No GitHub run ID file found',
                    ]);
                }
                break;

            case 'clear_deployment_status':
                // Clear the deployment status file to allow fresh deployments
                $statusFile = __DIR__ . '/../tmp/deployment_status.json';
                if (file_exists($statusFile)) {
                    $cleared = unlink($statusFile);
                    echo json_encode([
                        'success' => $cleared,
                        'message' => $cleared ? 'Deployment status cleared successfully' : 'Failed to clear deployment status',
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => 'No deployment status file found',
                    ]);
                }
                break;

            case 'get_site_info':
                // Get site information from Kinsta API
                try {
                    $siteInfo = getSiteInfoFromKinsta();
                    echo json_encode(['success' => true, 'data' => $siteInfo]);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to get site info: ' . $e->getMessage(),
                    ]);
                }
                break;

            default:
                throw new Exception('Invalid action: ' . $action);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ]);
    }
}

/**
 * Reset system - clear temporary files, logs, and reset deployment status
 */
function resetSystem()
{
    global $deploymentManager;

    try {
        $success = true;

        // Stop any running deployment
        $deploymentManager->stopDeployment();

        // Clear temporary files in project tmp directory (not system /tmp)
        $baseDir = dirname(__DIR__);
        $tmpDir  = $baseDir . '/tmp';

        // Create tmp directory if it doesn't exist
        if (is_dir($tmpDir)) {
            //delete folder and contents
            if (! removeDirectory($tmpDir)) {
                error_log("Failed to remove tmp directory: $tmpDir");
                $success = false;
            }
        }

        // Recreate tmp directory
        mkdir($tmpDir, 0755, true);

        // Clear log files and directories
        $logsDir = __DIR__ . '/../logs';

        // Create logs directory if it doesn't exist
        if (! is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        // Create subdirectories if they don't exist
        $logSubdirs = ['api', 'deployment'];
        foreach ($logSubdirs as $subdir) {
            $subdirPath = $logsDir . '/' . $subdir;
            if (! is_dir($subdirPath)) {
                mkdir($subdirPath, 0755, true);
            }
        }

        // Clear/reset log files
        $logFiles = [
            $logsDir . '/errors.log',
            $logsDir . '/system.log',
            $logsDir . '/api/api.log',
            $logsDir . '/deployment/deployment.log',
        ];

        foreach ($logFiles as $logFile) {
            if (file_exists($logFile)) {
                if (! file_put_contents($logFile, '')) {
                    // If can't clear, delete and recreate
                    unlink($logFile);
                    touch($logFile);
                }
                error_log("Cleared log file: $logFile");
            } else {
                // Create the log file if it doesn't exist
                touch($logFile);
                error_log("Created log file: $logFile");
            }
        }

        // Reset deployment status
        $deploymentManager->resetDeployment();

        return $success;

    } catch (Exception $e) {
        error_log("Reset system error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get site information from Kinsta API
 */
function getSiteInfoFromKinsta()
{
    global $configManager;

    // Get site config - prefer dedicated site config, but fall back to main config (config.json)
    $siteConfig = $configManager->getConfig('site');
    $mainConfig = $configManager->getConfig('main');

    // token may be stored in several places depending on older/newer config formats
    $kinstaToken = '';
    // 1) site config top-level
    if (! empty($siteConfig) && isset($siteConfig['kinsta_token']) && $siteConfig['kinsta_token']) {
        $kinstaToken = $siteConfig['kinsta_token'];
        error_log('Kinsta token loaded from site config');
    }
    // 2) main config under ['site']['kinsta_token']
    if (empty($kinstaToken) && ! empty($mainConfig) && isset($mainConfig['site']['kinsta_token']) && $mainConfig['site']['kinsta_token']) {
        $kinstaToken = $mainConfig['site']['kinsta_token'];
        error_log('Kinsta token loaded from main.config -> site.kinsta_token');
    }
    // 3) main config direct key (legacy)
    if (empty($kinstaToken) && ! empty($mainConfig) && isset($mainConfig['kinsta_token']) && $mainConfig['kinsta_token']) {
        $kinstaToken = $mainConfig['kinsta_token'];
        error_log('Kinsta token loaded from main.config -> kinsta_token');
    }
    // 4) try alternate 'config' key (some code uses getConfig('config'))
    if (empty($kinstaToken)) {
        $alt = $configManager->getConfig('config');
        if (! empty($alt) && isset($alt['site']['kinsta_token']) && $alt['site']['kinsta_token']) {
            $kinstaToken = $alt['site']['kinsta_token'];
            error_log('Kinsta token loaded from config (alternate)');
        }
    }
    // 5) final fallback: read config file directly from filesystem
    if (empty($kinstaToken)) {
        $configFilePath = dirname(__DIR__) . '/config/config.json';
        if (file_exists($configFilePath)) {
            $raw  = @file_get_contents($configFilePath);
            $json = @json_decode($raw, true);
            if (isset($json['site']['kinsta_token']) && $json['site']['kinsta_token']) {
                $kinstaToken = $json['site']['kinsta_token'];
                error_log('Kinsta token loaded from filesystem config.json');
            }
        }
    }

    if (empty($kinstaToken)) {
        throw new Exception('Kinsta API token not configured');
    }

    // Try to get site ID from deployment status or temp files
    $siteId = null;

    // Check temp file first
    $siteIdFile = dirname(__DIR__) . '/tmp/site_id.txt';
    if (file_exists($siteIdFile)) {
        $siteId = trim(file_get_contents($siteIdFile));
    }

    // Fallback: try to get site id from deployment manager status if temp file missing
    if (empty($siteId)) {
        error_log('site_id.txt not found - attempting to read from deployment manager');
        global $deploymentManager;
        if ($deploymentManager) {
            try {
                $status = $deploymentManager->getDeploymentStatus();
                if (! empty($status['site_id'])) {
                    $siteId = $status['site_id'];
                    error_log('site_id obtained from deployment manager: ' . $siteId);
                }
            } catch (Exception $e) {
                error_log('Error fetching deployment status for site_id fallback: ' . $e->getMessage());
            }
        }
    }

    if (empty($siteId)) {
        throw new Exception('Site ID not found. Please complete deployment first.');
    }

    // Make API call to get site info
    $url = "https://api.kinsta.com/v2/sites/{$siteId}";

    $headers = [
        'Authorization: Bearer ' . $kinstaToken,
        'Content-Type: application/json',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Kinsta API request failed: {$error}");
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Kinsta API returned error code: {$httpCode}");
    }

    $data = json_decode($response, true);

    if (! $data || ! isset($data['site'])) {
        throw new Exception('Invalid response from Kinsta API');
    }

    return [
        'site_id'      => $siteId,
        'site_url'     => 'https://' . $data['site']['environments'][0]['primaryDomain']['name'] ?? '',
        'domain'       => $data['site']['environments'][0]['primaryDomain']['name'] ?? '',
        'status'       => $data['site']['status'] ?? '',
        'name'         => $data['site']['name'] ?? '',
        'display_name' => $data['site']['display_name'] ?? '',
    ];
}

/**
 * Recursively remove directory and its contents
 */
function removeDirectory($dir)
{
    if (! is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $filePath = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($filePath)) {
            removeDirectory($filePath);
        } else {
            unlink($filePath);
        }
    }

    return rmdir($dir);
}
