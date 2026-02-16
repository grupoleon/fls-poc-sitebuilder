<?php

/**
 * Bootstrap - Application Initialization
 * Refactored to use modern architecture with separation of concerns
 */

ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);

// ====================
// LOAD CORE INFRASTRUCTURE
// ====================
require_once __DIR__ . '/core/ErrorHandler.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/Validator.php';
require_once __DIR__ . '/core/FileSystem.php';
require_once __DIR__ . '/core/Config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Router.php';

// ====================
// LOAD HELPERS
// ====================
require_once __DIR__ . '/helpers/ConfigHelper.php';
require_once __DIR__ . '/helpers/UploadHelper.php';

// ====================
// LOAD SERVICES
// ====================
require_once __DIR__ . '/services/KinstaService.php';

// ====================
// LOAD MANAGERS (LEGACY - TO BE REFACTORED)
// ====================
require_once __DIR__ . '/admin/includes/ConfigManager.php';
require_once __DIR__ . '/admin/includes/PageContentManager.php';
require_once __DIR__ . '/admin/includes/DeploymentManager.php';
require_once __DIR__ . '/admin/includes/ConfigDefaultsManager.php';

// ====================
// LOAD CONTROLLERS
// ====================
require_once __DIR__ . '/controllers/ConfigController.php';
require_once __DIR__ . '/controllers/PageController.php';
require_once __DIR__ . '/controllers/DeploymentController.php';

// ====================
// REGISTER ERROR HANDLER
// ====================
ErrorHandler::register();

// ====================
// INITIALIZE MANAGERS
// ====================
$configManager = new ConfigManager();
$pageManager = new PageContentManager();
$deploymentManager = new DeploymentManager();
$configDefaultsManager = new ConfigDefaultsManager();

// ====================
// INITIALIZE CONTROLLERS
// ====================
$configController = new ConfigController($configManager);
$pageController = new PageController($configManager, $pageManager);
$deploymentController = new DeploymentController($configManager, $deploymentManager);

// ====================
// INITIALIZE ROUTER
// ====================
$router = new Router();
$router->setController('ConfigController', $configController);
$router->setController('PageController', $pageController);
$router->setController('DeploymentController', $deploymentController);
$router->registerDefaultRoutes();

// ====================
// HANDLE AJAX/API REQUESTS
// ====================
if (isset($_SERVER['REQUEST_METHOD']) &&
    ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action']))) {
    $router->dispatch();
    exit;
}

// ====================
// LEGACY FUNCTION WRAPPERS (BACKWARD COMPATIBILITY)
// ====================

/**
 * Deep merge configuration arrays
 * @deprecated Use Config::deepMerge() instead
 */
function deepMergeConfig($existing, $new)
{
    return Config::deepMerge($existing, $new);
}

/**
 * Normalize config data types
 * @deprecated Use ConfigHelper::normalizeDataTypes() instead
 */
function normalizeConfigDataTypes(&$config)
{
    $config = ConfigHelper::normalizeDataTypes($config);
    return $config;
}

/**
 * Get config schema
 * @deprecated Use ConfigHelper::getSchema() instead
 */
function getConfigSchema($type)
{
    return ConfigHelper::getSchema($type);
}

/**
 * Get valid root keys
 * @deprecated Use ConfigHelper::getValidRootKeys() instead
 */
function getValidRootKeys($type)
{
    return ConfigHelper::getValidRootKeys($type);
}

/**
 * Filter config by schema
 * @deprecated Use ConfigHelper::filterBySchema() instead
 */
function filterConfigBySchema($config, $type)
{
    return ConfigHelper::filterBySchema($config, $type);
}

/**
 * Handle request (legacy)
 * @deprecated Requests now handled by Router
 */
function handleRequest()
{
    global $router;
    $router->dispatch();
}

/**
 * Validate imported config
 * @deprecated Use ConfigHelper::validateImported() instead
 */
function validateImportedConfig($configType, $data)
{
    return ConfigHelper::validateImported($configType, $data);
}

/**
 * Normalize uploaded files
 * @deprecated Use UploadHelper::normalizeFiles() instead
 */
function normalizeUploadedFiles($fileInput)
{
    return UploadHelper::normalizeFiles($fileInput);
}

/**
 * Import config content
 * @deprecated Use UploadHelper::importConfigContent() instead
 */
function importConfigContent($filename, $content, $configDir, $source = null)
{
    $result = UploadHelper::importConfigContent($filename, $content, $configDir, $source);
    return $result['success'];
}

/**
 * Import configs from ZIP
 * @deprecated Use UploadHelper::importConfigsFromZip() instead
 */
function importConfigsFromZip($zipPath, $zipName, $configDir)
{
    $result = UploadHelper::importConfigsFromZip($zipPath, $zipName, $configDir);
    return [
        'success'  => $result['success'],
        'imported' => $result['imported'],
        'message'  => $result['message'],
    ];
}

/**
 * Get Kinsta token
 * @deprecated Use KinstaService::getTokenFromConfig() instead
 */
function getKinstaToken()
{
    global $configManager;
    return KinstaService::getTokenFromConfig($configManager);
}

/**
 * Make Kinsta API request
 * @deprecated Use KinstaService methods instead
 */
function makeKinstaApiRequest($url, $method = 'GET', $kinstaToken = null, $timeout = 30)
{
    if ($kinstaToken === null) {
        global $configManager;
        $kinstaToken = KinstaService::getTokenFromConfig($configManager);
    }

    $kinstaService = new KinstaService($kinstaToken);
    $kinstaService->setTimeout($timeout);

    try {
        $endpoint = str_replace('https://api.kinsta.com/v2', '', $url);
        
        $reflectionMethod = new ReflectionMethod($kinstaService, 'request');
        $reflectionMethod->setAccessible(true);
        $result = $reflectionMethod->invoke($kinstaService, $endpoint, $method);

        return [
            'body'      => json_encode($result),
            'http_code' => 200,
            'error'     => '',
        ];
    } catch (Exception $e) {
        return [
            'body'      => '',
            'http_code' => 500,
            'error'     => $e->getMessage(),
        ];
    }
}

/**
 * Get site info from Kinsta
 * @deprecated Use KinstaService::getSiteInfo() instead
 */
function getSiteInfoFromKinsta()
{
    global $configManager;
    
    $kinstaService = KinstaService::fromConfig($configManager);
    
    $siteIdFile = dirname(__DIR__) . '/tmp/site_id.txt';
    if (! file_exists($siteIdFile)) {
        throw new Exception('Site ID not found. Please complete deployment first.');
    }
    
    $siteId = trim(file_get_contents($siteIdFile));
    return $kinstaService->getSiteInfo($siteId);
}

/**
 * Check if site exists in Kinsta
 * @deprecated Use KinstaService::checkSiteExists() instead
 */
function checkIfSiteExistsInKinsta($siteTitle)
{
    global $configManager;
    
    $kinstaService = KinstaService::fromConfig($configManager);
    $siteConfig = $configManager->getConfig('site');
    $companyId = $siteConfig['company'] ?? null;

    if (empty($companyId)) {
        throw new Exception("Company ID not configured");
    }

    $result = $kinstaService->checkSiteExists($siteTitle, $companyId);
    
    return [
        'exists'         => $result['exists'],
        'matching_sites' => $result['matching_sites'],
    ];
}

/**
 * Get available regions from Kinsta
 * @deprecated Use KinstaService::getAvailableRegions() instead
 */
function getAvailableRegionsFromKinsta($companyId)
{
    global $configManager;
    
    $kinstaService = KinstaService::fromConfig($configManager);
    $regions = $kinstaService->getAvailableRegions($companyId);
    
    return [
        'success' => true,
        'data'    => ['regions' => $regions],
    ];
}

/**
 * Delete Kinsta site
 * @deprecated Use KinstaService::deleteSite() instead
 */
function deleteKinstaSite($siteId)
{
    global $configManager;
    
    $kinstaService = KinstaService::fromConfig($configManager);
    $success = $kinstaService->deleteSite($siteId);
    
    return [
        'success' => $success,
        'message' => $success ? 'Site deleted successfully' : 'Failed to delete site',
    ];
}

/**
 * Remove directory recursively
 * @deprecated Use FileSystem::deleteDir() instead
 */
function removeDirectory($dir)
{
    return FileSystem::deleteDir($dir);
}

// ====================
// INITIALIZATION COMPLETE
// ====================
Logger::debug("Bootstrap initialization complete");
