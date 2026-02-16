<?php

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../core/ErrorHandler.php';

/**
 * Router
 * Routes requests to appropriate controllers
 */
class Router
{
    private $routes      = [];
    private $controllers = [];

    /**
     * Register a route
     *
     * @param string $action Action name
     * @param string $controller Controller class name
     * @param string $method Controller method name
     */
    public function register(string $action, string $controller, string $method): void
    {
        $this->routes[$action] = [
            'controller' => $controller,
            'method'     => $method,
        ];
    }

    /**
     * Set controller instance
     *
     * @param string $name Controller name
     * @param object $instance Controller instance
     */
    public function setController(string $name, object $instance): void
    {
        $this->controllers[$name] = $instance;
    }

    /**
     * Get action from request
     *
     * @return string Action name
     */
    private function getAction(): string
    {
        if (isset($_POST['action'])) {
            return $_POST['action'];
        }

        if (isset($_GET['action'])) {
            return $_GET['action'];
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $rawInput  = file_get_contents('php://input');
            $jsonInput = json_decode($rawInput, true);

            if (isset($jsonInput['action'])) {
                return $jsonInput['action'];
            }
        }

        return '';
    }

    /**
     * Dispatch request to appropriate controller
     */
    public function dispatch(): void
    {
        header('Content-Type: application/json');

        try {
            $action = $this->getAction();

            if (empty($action)) {
                Response::error('Action parameter is required');
            }

            if (! isset($this->routes[$action])) {
                Logger::warning("Unknown action requested: {$action}");
                Response::error('Unknown action: ' . $action, null, 404);
            }

            $route          = $this->routes[$action];
            $controllerName = $route['controller'];
            $method         = $route['method'];

            if (! isset($this->controllers[$controllerName])) {
                Logger::error("Controller not found: {$controllerName}");
                Response::serverError('Controller not found');
            }

            $controller = $this->controllers[$controllerName];

            if (! method_exists($controller, $method)) {
                Logger::error("Method not found: {$controllerName}::{$method}");
                Response::serverError('Method not found');
            }

            Logger::debug("Routing action: {$action} -> {$controllerName}::{$method}");

            ErrorHandler::wrap([$controller, $method]);
        } catch (\Exception $e) {
            Logger::exception($e);
            Response::serverError('An unexpected error occurred', $e);
        }
    }

    /**
     * Register all routes
     */
    public function registerDefaultRoutes(): void
    {
        $this->register('get_configs', 'ConfigController', 'getConfigs');
        $this->register('save_config', 'ConfigController', 'saveConfig');
        $this->register('save_forms_config', 'ConfigController', 'saveFormsConfig');
        $this->register('save_active_theme', 'ConfigController', 'saveActiveTheme');
        $this->register('refresh_theme_list', 'ConfigController', 'refreshThemeList');
        $this->register('get_theme_config', 'ConfigController', 'getThemeConfig');
        $this->register('save_theme_overrides', 'ConfigController', 'saveThemeOverrides');

        $this->register('get_themes', 'PageController', 'getThemes');
        $this->register('get_pages', 'PageController', 'getPages');
        $this->register('get_theme_pages', 'PageController', 'getThemePages');
        $this->register('get_theme_pages_with_names', 'PageController', 'getThemePagesWithNames');
        $this->register('get_page_content', 'PageController', 'getPageContent');
        $this->register('save_page_content', 'PageController', 'savePageContent');
        $this->register('upload_page_image', 'PageController', 'uploadPageImage');
        $this->register('upload_image', 'PageController', 'uploadImage');
        $this->register('upload_logo', 'PageController', 'uploadLogo');
        $this->register('get_current_logo', 'PageController', 'getCurrentLogo');
        $this->register('get_other_contents', 'PageController', 'getOtherContents');
        $this->register('save_other_contents', 'PageController', 'saveOtherContents');
        $this->register('delete_other_content', 'PageController', 'deleteOtherContent');

        $this->register('list_config_files', 'ConfigController', 'listConfigFiles');
        $this->register('get_raw_config', 'ConfigController', 'getRawConfig');

        $this->register('trigger_deployment', 'DeploymentController', 'trigger');
        $this->register('get_deployment_status', 'DeploymentController', 'getStatus');
        $this->register('deployment_status', 'DeploymentController', 'getStatus'); // Alias
        $this->register('get_logs', 'DeploymentController', 'getLogs');
        $this->register('deployment_logs', 'DeploymentController', 'getLogs'); // Alias
        $this->register('clear_logs', 'DeploymentController', 'clearLogs');
        $this->register('reset_system', 'DeploymentController', 'resetSystem');
        $this->register('get_kinsta_site_info', 'DeploymentController', 'getKinstaSiteInfo');
        $this->register('check_site_exists', 'DeploymentController', 'checkSiteExists');
        $this->register('get_available_regions', 'DeploymentController', 'getAvailableRegions');
        $this->register('delete_kinsta_site', 'DeploymentController', 'deleteKinstaSite');
        $this->register('list_log_files', 'DeploymentController', 'listLogFiles');
        $this->register('read_log_file', 'DeploymentController', 'readLogFile');
        $this->register('clear_deployment_status', 'DeploymentController', 'clearDeploymentStatus');
    }
}
