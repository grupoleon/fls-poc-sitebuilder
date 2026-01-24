<?php

// Suppress all error output to prevent HTML error messages from interfering with JSON responses
ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);

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

            case 'save_forms_config':
                $input = json_decode(file_get_contents('php://input'), true);

                if (! $input) {
                    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
                    break;
                }

                try {
                    $formsConfigPath = __DIR__ . '/../config/forms-config.json';
                    $result          = file_put_contents($formsConfigPath, json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                    if ($result === false) {
                        throw new Exception('Failed to write forms-config.json');
                    }

                    echo json_encode(['success' => true, 'message' => 'Form placeholders saved successfully']);
                } catch (Exception $e) {
                    error_log("Forms config save error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
                    // Get existing theme config to preserve all fields
                    $themeConfig = $configManager->getConfig('theme');

                    // Ensure available_themes is set, if not, get from available themes
                    if (! isset($themeConfig['available_themes']) || empty($themeConfig['available_themes'])) {
                        $themeConfig['available_themes'] = $configManager->getAvailableThemes();
                    }

                    // Validate that the selected theme is available
                    if (! in_array($theme, $themeConfig['available_themes'])) {
                        echo json_encode(['success' => false, 'message' => 'Selected theme is not available']);
                        break;
                    }

                    // Update active theme
                    $themeConfig['active_theme'] = $theme;

                    // Save the updated config
                    $configManager->updateConfig('theme', $themeConfig);

                    echo json_encode(['success' => true, 'message' => 'Active theme updated successfully']);
                } catch (Exception $e) {
                    error_log("Theme save error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;

            case 'refresh_theme_list':
                try {
                    // Get available themes from folder structure
                    $availableThemes = $configManager->getAvailableThemes();

                    // Get current theme config
                    $themeConfig = $configManager->getConfig('theme');

                    // Update available_themes list
                    $themeConfig['available_themes'] = $availableThemes;

                    // Validate active theme still exists
                    if (! in_array($themeConfig['active_theme'], $availableThemes)) {
                        $themeConfig['active_theme'] = $availableThemes[0] ?? 'LifeGuide';
                    }

                    // Save updated config
                    $configManager->updateConfig('theme', $themeConfig);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Theme list refreshed successfully',
                        'data'    => [
                            'themes'       => $availableThemes,
                            'active_theme' => $themeConfig['active_theme'],
                        ],
                    ]);
                } catch (Exception $e) {
                    error_log("Theme refresh error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;case 'get_pages':
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

            case 'list_log_files':
                try {
                    $logsDir  = __DIR__ . '/../logs';
                    $logFiles = [];

                    // Recursive function to scan directories
                    $scanDirectory = function ($dir, $prefix = '') use (&$scanDirectory, &$logFiles, $logsDir) {
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
                                $scanDirectory($fullPath, $relativePath . '/');
                            } elseif (is_file($fullPath)) {
                                $logFiles[] = [
                                    'name'     => $item,
                                    'path'     => $relativePath,
                                    'size'     => filesize($fullPath),
                                    'modified' => filemtime($fullPath),
                                    'category' => $prefix ? rtrim($prefix, '/') : 'root',
                                ];
                            }
                        }
                    };

                    $scanDirectory($logsDir);

                    // Sort by modified time (newest first)
                    usort($logFiles, function ($a, $b) {
                        return $b['modified'] - $a['modified'];
                    });

                    echo json_encode([
                        'success' => true,
                        'data'    => $logFiles,
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to list log files: ' . $e->getMessage(),
                    ]);
                }
                break;

            case 'read_log_file':
                try {
                    $filePath = $_GET['file'] ?? '';

                    if (empty($filePath)) {
                        throw new Exception('File path is required');
                    }

                    // Security: ensure the path is within logs directory
                    $logsDir       = realpath(__DIR__ . '/../logs');
                    $requestedFile = realpath($logsDir . '/' . $filePath);

                    if ($requestedFile === false || strpos($requestedFile, $logsDir) !== 0) {
                        throw new Exception('Invalid file path');
                    }

                    if (! file_exists($requestedFile)) {
                        throw new Exception('File not found');
                    }

                    // Read file content
                    $content = file_get_contents($requestedFile);

                    echo json_encode([
                        'success' => true,
                        'data'    => [
                            'content'  => $content,
                            'size'     => filesize($requestedFile),
                            'modified' => filemtime($requestedFile),
                        ],
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage(),
                    ]);
                }
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
                try {
                    $resetSuccess = resetSystem();
                    $message = $resetSuccess 
                        ? 'System reset successfully' 
                        : 'System reset completed with some warnings. Check logs for details.';
                    
                    echo json_encode([
                        'success' => true, // Always return true since partial reset is still useful
                        'message' => $message,
                        'warnings' => !$resetSuccess,
                    ]);
                } catch (Exception $e) {
                    error_log("Critical reset error: " . $e->getMessage());
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to reset system: ' . $e->getMessage(),
                    ]);
                }
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

            case 'clean_uploads':
                // Scan all uploads and remove any images/videos not referenced in pages JSON
                $confirmed = isset($_GET['confirmed']) ? $_GET['confirmed'] : (isset($_POST['confirmed']) ? $_POST['confirmed'] : 'false');
                $execute   = ($confirmed === '1' || strtolower($confirmed) === 'true');

                try {
                    $cleanResult = $pageManager->cleanUnusedUploads($execute);
                    echo json_encode($cleanResult);
                } catch (Exception $e) {
                    error_log('Clean uploads error: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Failed to clean uploads: ' . $e->getMessage()]);
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

            case 'check_site_exists':
                // Check if a site with the given title exists in Kinsta
                $input     = json_decode(file_get_contents('php://input'), true);
                $siteTitle = $input['site_title'] ?? '';

                if (empty($siteTitle)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Site title is required',
                    ]);
                    break;
                }

                try {
                    $existsResult = checkIfSiteExistsInKinsta($siteTitle);
                    echo json_encode(['success' => true, 'data' => $existsResult]);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to check site existence: ' . $e->getMessage(),
                    ]);
                }
                break;

            case 'delete_kinsta_site':
                // Delete a site from Kinsta (with safety checks)
                $input  = json_decode(file_get_contents('php://input'), true);
                $siteId = $input['site_id'] ?? '';

                if (empty($siteId)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Site ID is required',
                    ]);
                    break;
                }

                try {
                    $deleteResult = deleteKinstaSite($siteId);
                    echo json_encode(['success' => true, 'data' => $deleteResult, 'message' => 'Site deletion initiated successfully']);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to delete site: ' . $e->getMessage(),
                    ]);
                }
                break;

            case 'list_config_files':
                try {
                    $configDir = dirname(__DIR__) . '/config';
                    $files     = [];

                    if (is_dir($configDir)) {
                        $items = scandir($configDir);
                        foreach ($items as $item) {
                            if (pathinfo($item, PATHINFO_EXTENSION) === 'json') {
                                $filePath = $configDir . '/' . $item;
                                $files[]  = [
                                    'name'     => $item,
                                    'size'     => filesize($filePath),
                                    'modified' => filemtime($filePath),
                                ];
                            }
                        }
                    }

                    echo json_encode(['success' => true, 'files' => $files]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;

            case 'get_raw_config':
                try {
                    $filename = $_GET['file'] ?? '';

                    if (empty($filename)) {
                        throw new Exception('File name is required');
                    }

                    // Validate filename to prevent directory traversal
                    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                        throw new Exception('Invalid file name');
                    }

                    // Only allow .json files
                    if (pathinfo($filename, PATHINFO_EXTENSION) !== 'json') {
                        throw new Exception('Only JSON files are allowed');
                    }

                    $configDir = dirname(__DIR__) . '/config';
                    $filePath  = $configDir . '/' . $filename;

                    if (! file_exists($filePath)) {
                        throw new Exception('File not found');
                    }

                    $content  = file_get_contents($filePath);
                    $metadata = [
                        'name'     => $filename,
                        'size'     => filesize($filePath),
                        'lines'    => substr_count($content, "\n") + 1,
                        'modified' => filemtime($filePath),
                    ];

                    echo json_encode([
                        'success'  => true,
                        'content'  => $content,
                        'metadata' => $metadata,
                    ]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;

            case 'import_config':
                try {
                    // Check if file was uploaded
                    if (! isset($_FILES['config_file'])) {
                        throw new Exception('No file uploaded');
                    }

                    $file = $_FILES['config_file'];

                    // Check for upload errors
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception('File upload error: ' . $file['error']);
                    }

                    // Validate file extension
                    $filename = basename($file['name']);
                    if (pathinfo($filename, PATHINFO_EXTENSION) !== 'json') {
                        throw new Exception('Only JSON files are allowed');
                    }

                    // Validate filename to prevent directory traversal
                    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                        throw new Exception('Invalid file name');
                    }

                    // Read and validate JSON content
                    $content = file_get_contents($file['tmp_name']);
                    if ($content === false) {
                        throw new Exception('Failed to read uploaded file');
                    }

                    // Validate JSON structure
                    $jsonData = json_decode($content, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('Invalid JSON format: ' . json_last_error_msg());
                    }

                    // Validate the config based on its type
                    $configType = pathinfo($filename, PATHINFO_FILENAME);
                    if (! validateImportedConfig($configType, $jsonData)) {
                        throw new Exception('Configuration validation failed. Please check the structure matches the expected format.');
                    }

                    // Save to config directory
                    $configDir  = dirname(__DIR__) . '/config';
                    $targetPath = $configDir . '/' . $filename;

                    // Backup existing file if it exists
                    if (file_exists($targetPath)) {
                        $backupPath = $targetPath . '.backup.' . date('YmdHis');
                        copy($targetPath, $backupPath);
                    }

                    // Write the file with pretty formatting
                    $result = file_put_contents(
                        $targetPath,
                        json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    );

                    if ($result === false) {
                        throw new Exception('Failed to save config file');
                    }

                    echo json_encode([
                        'success' => true,
                        'message' => "Configuration file '{$filename}' imported successfully",
                        'file' => $filename,
                    ]);

                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage(),
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
 * Validate imported configuration based on type
 */
function validateImportedConfig($configType, $data)
{
    // Validate based on config type
    switch ($configType) {
        case 'config':
        case 'local-config':
            // Main config should have basic structure
            return is_array($data);

        case 'git':
            // Git config should have org and repo
            return isset($data['org']) && isset($data['repo']);

        case 'site':
            // Site config validation
            return is_array($data);

        case 'theme-config':
            // Theme config should have active_theme
            return isset($data['active_theme']);

        case 'forms-config':
            // Forms config validation
            return is_array($data);

        default:
            // For unknown types, just validate it's valid JSON (array or object)
            return is_array($data);
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
        $errors  = [];

        // Stop any running deployment
        try {
            $deploymentManager->stopDeployment();
        } catch (Exception $e) {
            error_log("Failed to stop deployment: " . $e->getMessage());
            $errors[] = "Failed to stop deployment";
        }

        // Clear temporary files in project tmp directory (not system /tmp)
        $baseDir = dirname(__DIR__);
        $tmpDir  = $baseDir . '/tmp';

        // Clear tmp directory contents instead of removing the directory itself
        if (is_dir($tmpDir)) {
            try {
                $files = array_diff(scandir($tmpDir), ['.', '..']);
                foreach ($files as $file) {
                    $filePath = $tmpDir . DIRECTORY_SEPARATOR . $file;
                    try {
                        if (is_dir($filePath)) {
                            removeDirectory($filePath);
                        } else {
                            @unlink($filePath);
                        }
                    } catch (Exception $e) {
                        error_log("Failed to delete: $filePath - " . $e->getMessage());
                        // Don't fail the entire reset for individual files
                    }
                }
                error_log("Cleared tmp directory contents");
            } catch (Exception $e) {
                error_log("Error clearing tmp directory: " . $e->getMessage());
                $errors[] = "Failed to clear some temporary files";
                // Don't mark as failure - continue with reset
            }
        } else {
            // Create tmp directory if it doesn't exist
            try {
                mkdir($tmpDir, 0755, true);
            } catch (Exception $e) {
                error_log("Failed to create tmp directory: " . $e->getMessage());
                $errors[] = "Failed to create tmp directory";
            }
        }

        // Ensure tmp directory has proper permissions
        if (is_dir($tmpDir)) {
            @chmod($tmpDir, 0755);
        }

        // Clear log files and directories
        $logsDir = __DIR__ . '/../logs';

        // Create logs directory if it doesn't exist
        if (!is_dir($logsDir)) {
            try {
                mkdir($logsDir, 0755, true);
            } catch (Exception $e) {
                error_log("Failed to create logs directory: " . $e->getMessage());
                $errors[] = "Failed to create logs directory";
            }
        }

        // Create subdirectories if they don't exist
        $logSubdirs = ['api', 'deployment'];
        foreach ($logSubdirs as $subdir) {
            $subdirPath = $logsDir . '/' . $subdir;
            if (!is_dir($subdirPath)) {
                try {
                    mkdir($subdirPath, 0755, true);
                } catch (Exception $e) {
                    error_log("Failed to create log subdirectory $subdir: " . $e->getMessage());
                }
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
            try {
                if (file_exists($logFile)) {
                    // Try to clear the file
                    if (@file_put_contents($logFile, '') === false) {
                        // If can't clear, try to delete and recreate
                        @unlink($logFile);
                        @touch($logFile);
                    }
                    error_log("Cleared log file: $logFile");
                } else {
                    // Create the log file if it doesn't exist
                    @touch($logFile);
                    error_log("Created log file: $logFile");
                }
            } catch (Exception $e) {
                error_log("Failed to process log file $logFile: " . $e->getMessage());
                // Don't fail the entire reset for log files
            }
        }

        // Reset deployment status
        try {
            $deploymentManager->resetDeployment();
            error_log("Deployment status reset successfully");
        } catch (Exception $e) {
            error_log("Failed to reset deployment status: " . $e->getMessage());
            $errors[] = "Failed to reset deployment status";
        }

        // Only return false if there were critical errors
        if (count($errors) > 0) {
            error_log("Reset system completed with warnings: " . implode(", ", $errors));
        } else {
            error_log("Reset system completed successfully");
        }

        return $success;

    } catch (Exception $e) {
        error_log("Reset system error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get Kinsta API token from configuration
 * Checks multiple locations for backward compatibility
 */
function getKinstaToken()
{
    global $configManager;

    $siteConfig = $configManager->getConfig('site');
    $mainConfig = $configManager->getConfig('main');

    // 1) site config top-level
    if (! empty($siteConfig) && isset($siteConfig['kinsta_token']) && $siteConfig['kinsta_token']) {
        return $siteConfig['kinsta_token'];
    }

    // 2) main config under ['site']['kinsta_token']
    if (! empty($mainConfig) && isset($mainConfig['site']['kinsta_token']) && $mainConfig['site']['kinsta_token']) {
        return $mainConfig['site']['kinsta_token'];
    }

    // 3) main config direct key (legacy)
    if (! empty($mainConfig) && isset($mainConfig['kinsta_token']) && $mainConfig['kinsta_token']) {
        return $mainConfig['kinsta_token'];
    }

    // 4) try alternate 'config' key
    $alt = $configManager->getConfig('config');
    if (! empty($alt) && isset($alt['site']['kinsta_token']) && $alt['site']['kinsta_token']) {
        return $alt['site']['kinsta_token'];
    }

    // 5) final fallback: read config file directly from filesystem
    $configFilePath = dirname(__DIR__) . '/config/config.json';
    if (file_exists($configFilePath)) {
        $raw  = @file_get_contents($configFilePath);
        $json = @json_decode($raw, true);
        if (isset($json['site']['kinsta_token']) && $json['site']['kinsta_token']) {
            return $json['site']['kinsta_token'];
        }
    }

    throw new Exception('Kinsta API token not configured');
}

/**
 * Make a request to the Kinsta API
 *
 * @param string $url API endpoint URL
 * @param string $method HTTP method (GET, POST, DELETE)
 * @param string|null $kinstaToken Optional token (will be retrieved if not provided)
 * @param int $timeout Timeout in seconds
 * @return array Response data with 'body', 'http_code', and 'error'
 */
function makeKinstaApiRequest($url, $method = 'GET', $kinstaToken = null, $timeout = 30)
{
    if ($kinstaToken === null) {
        $kinstaToken = getKinstaToken();
    }

    $headers = [
        'Authorization: Bearer ' . $kinstaToken,
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    return [
        'body'      => $response,
        'http_code' => $httpCode,
        'error'     => $curlError,
    ];
}

/**
 * Get site information from Kinsta API
 */
function getSiteInfoFromKinsta()
{
    global $deploymentManager;

    $kinstaToken = getKinstaToken();

    // Try to get site ID from deployment status or temp files
    $siteId = null;

    // Check temp file first
    $siteIdFile = dirname(__DIR__) . '/tmp/site_id.txt';
    if (file_exists($siteIdFile)) {
        $siteId = trim(file_get_contents($siteIdFile));
    }

    // Fallback: try to get site id from deployment manager status if temp file missing
    if (empty($siteId) && $deploymentManager) {
        error_log('site_id.txt not found - attempting to read from deployment manager');
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

    if (empty($siteId)) {
        throw new Exception('Site ID not found. Please complete deployment first.');
    }

    // Make API call to get site info
    $url    = "https://api.kinsta.com/v2/sites/{$siteId}";
    $result = makeKinstaApiRequest($url, 'GET', $kinstaToken);

    if ($result['error']) {
        throw new Exception("Kinsta API request failed: {$result['error']}");
    }

    if ($result['http_code'] !== 200) {
        throw new Exception("Kinsta API returned error code: {$result['http_code']}");
    }

    $data = json_decode($result['body'], true);

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
 * Check if a site with the given title exists in Kinsta
 */
function checkIfSiteExistsInKinsta($siteTitle)
{
    global $configManager;

    $kinstaToken = getKinstaToken();

    // Get company ID from config
    $siteConfig = $configManager->getConfig('site');
    $companyId  = $siteConfig['company'] ?? null;

    if (empty($companyId)) {
        throw new Exception("Company ID not configured. Please set it in site configuration.");
    }

    // Make API call to list all sites - company parameter is required
    $url    = "https://api.kinsta.com/v2/sites?company={$companyId}";
    $result = makeKinstaApiRequest($url, 'GET', $kinstaToken);

    if ($result['error']) {
        throw new Exception("Kinsta API request failed: {$result['error']}");
    }

    if ($result['http_code'] !== 200) {
        throw new Exception("Kinsta API returned error code: {$result['http_code']}");
    }

    $data = json_decode($result['body'], true);

    // Support multiple response formats: company.sites, sites, or top-level array
    if (isset($data['company']['sites']) && is_array($data['company']['sites'])) {
        $sites = $data['company']['sites'];
    } elseif (isset($data['sites']) && is_array($data['sites'])) {
        $sites = $data['sites'];
    } elseif (is_array($data) && array_values($data) === $data) {
        $sites = $data;
    } else {
        throw new Exception('Invalid response from Kinsta API');
    }

    // Check if any site matches the title (case-insensitive)
    // Kinsta saves site titles as slugs, so we need to check both the original title and its slug
    $exists                = false;
    $matchingSites         = [];
    $normalizedSearchTitle = strtolower(trim($siteTitle));

    // Convert site title to slug format (same as Kinsta does)
    $slugifiedSearchTitle = strtolower(trim($siteTitle));
    $slugifiedSearchTitle = preg_replace('/\s+/', '-', $slugifiedSearchTitle);    // Replace spaces with hyphens
    $slugifiedSearchTitle = preg_replace('/[^\w\-]/', '', $slugifiedSearchTitle); // Remove non-word chars except hyphens
    $slugifiedSearchTitle = preg_replace('/\-\-+/', '-', $slugifiedSearchTitle);  // Replace multiple hyphens with single
    $slugifiedSearchTitle = trim($slugifiedSearchTitle, '-');                     // Trim hyphens from start/end

    foreach ($sites as $site) {
        $siteName        = strtolower(trim($site['name'] ?? ''));
        $siteDisplayName = strtolower(trim($site['display_name'] ?? ''));

        // Check if the site name or display name matches either the original title or its slug
        if ($siteName === $normalizedSearchTitle ||
            $siteDisplayName === $normalizedSearchTitle ||
            $siteName === $slugifiedSearchTitle ||
            $siteDisplayName === $slugifiedSearchTitle) {
            $exists          = true;
            $matchingSites[] = [
                'id'           => $site['id'] ?? '',
                'name'         => $site['name'] ?? '',
                'display_name' => $site['display_name'] ?? '',
            ];
        }
    }

    return [
        'exists'         => $exists,
        'site_title'     => $siteTitle,
        'matching_sites' => $matchingSites,
    ];
}

/**
 * Delete a site from Kinsta
 * CRITICAL: This permanently deletes a site and all its data
 */
function deleteKinstaSite($siteId)
{
    // Validate site ID format
    if (empty($siteId) || ! is_string($siteId)) {
        throw new Exception('Invalid site ID provided');
    }

    $kinstaToken = getKinstaToken();

    // First, verify the site exists before attempting deletion
    $verifyUrl    = "https://api.kinsta.com/v2/sites/{$siteId}";
    $verifyResult = makeKinstaApiRequest($verifyUrl, 'GET', $kinstaToken);

    if ($verifyResult['error']) {
        throw new Exception("Failed to verify site before deletion: {$verifyResult['error']}");
    }

    if ($verifyResult['http_code'] !== 200) {
        throw new Exception("Site not found or inaccessible (HTTP {$verifyResult['http_code']})");
    }

    $siteData = json_decode($verifyResult['body'], true);
    if (! $siteData || ! isset($siteData['site'])) {
        throw new Exception('Invalid response when verifying site');
    }

    // Log the deletion attempt
    error_log("CRITICAL: Attempting to delete Kinsta site {$siteId} - " . ($siteData['site']['name'] ?? 'unknown'));

    // Company ID verification: ensure the site belongs to the configured company
    global $configManager;

    $siteConfig          = $configManager->getConfig('site');
    $configuredCompanyId = $siteConfig['company'] ?? null;

    if (empty($configuredCompanyId)) {
        throw new Exception('Company ID not configured. Cannot verify ownership before deletion.');
    }

    // Get site's company ID from the site data
    $siteCompanyId = $siteData['site']['company_id'] ?? null;

    if (empty($siteCompanyId)) {
        throw new Exception('Site company_id missing from Kinsta response. Cannot verify ownership.');
    }

    // Compare company IDs directly (no API calls needed)
    if (trim($configuredCompanyId) !== trim($siteCompanyId)) {
        error_log("Company ID mismatch - Configured: {$configuredCompanyId}, Site: {$siteCompanyId}");
        throw new Exception("Site belongs to company '{$siteCompanyId}' but configured company is '{$configuredCompanyId}'. Deletion aborted for safety.");
    }

    error_log("Company ID verified: {$siteCompanyId} matches configured company");

    // Make API call to delete the site
    $deleteUrl    = "https://api.kinsta.com/v2/sites/{$siteId}";
    $deleteResult = makeKinstaApiRequest($deleteUrl, 'DELETE', $kinstaToken, 60);

    if ($deleteResult['error']) {
        throw new Exception("Kinsta API deletion request failed: {$deleteResult['error']}");
    }

    // Kinsta DELETE may return 204 (No Content) on success or 200 with operation info
    if ($deleteResult['http_code'] !== 200 && $deleteResult['http_code'] !== 204 && $deleteResult['http_code'] !== 202) {
        $errorData = json_decode($deleteResult['body'], true);
        $errorMsg  = $errorData['message'] ?? "HTTP {$deleteResult['http_code']}";
        throw new Exception("Failed to delete site: {$errorMsg}");
    }

    $data = json_decode($deleteResult['body'], true);

    error_log("SUCCESS: Kinsta site {$siteId} deletion initiated");

    return [
        'site_id'      => $siteId,
        'status'       => 'deletion_initiated',
        'http_code'    => $deleteResult['http_code'],
        'operation_id' => $data['operation_id'] ?? null,
        'message'      => 'Site deletion has been initiated. It may take a few moments to complete.',
    ];
}

/**
 * Recursively remove directory and its contents
 */
function removeDirectory($dir)
{
    if (!is_dir($dir)) {
        return false;
    }

    try {
        $files = @scandir($dir);
        if ($files === false) {
            error_log("Failed to scan directory: $dir");
            return false;
        }

        $files = array_diff($files, ['.', '..']);
        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            try {
                if (is_dir($filePath)) {
                    removeDirectory($filePath);
                } else {
                    @unlink($filePath);
                }
            } catch (Exception $e) {
                error_log("Failed to remove: $filePath - " . $e->getMessage());
                // Continue with other files
            }
        }

        return @rmdir($dir);
    } catch (Exception $e) {
        error_log("Error removing directory $dir: " . $e->getMessage());
        return false;
    }
}
