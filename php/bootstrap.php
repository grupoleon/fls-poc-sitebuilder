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
// LOAD MANAGERS
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
$configManager         = new ConfigManager();
$pageManager           = new PageContentManager();
$deploymentManager     = new DeploymentManager();
$configDefaultsManager = new ConfigDefaultsManager();

// ====================
// INITIALIZE CONTROLLERS
// ====================
$configController     = new ConfigController($configManager);
$pageController       = new PageController($configManager, $pageManager);
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
// INITIALIZATION COMPLETE
// ====================
Logger::debug("Bootstrap initialization complete");
