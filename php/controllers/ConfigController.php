<?php

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../helpers/ConfigHelper.php';

/**
 * ConfigController
 * Handles configuration-related requests
 */
class ConfigController
{
    private $configManager;

    public function __construct($configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Get all configurations
     */
    public function getConfigs(): void
    {
        $mainConfig  = $this->configManager->getConfig('main') ?: $this->configManager->getConfig('config');
        $themeConfig = $this->configManager->getConfig('theme');

        Response::success([
            'git'    => $this->configManager->getConfig('git'),
            'site'   => $this->configManager->getConfig('site'),
            'main'   => $mainConfig,
            'config' => $mainConfig,
            'theme'  => $themeConfig,
        ]);
    }

    /**
     * Save configuration
     */
    public function saveConfig(): void
    {
        $input = $this->getJsonInput();

        $validator = new Validator($input);
        if (! $validator->validate(['type' => 'required', 'data' => 'required|array'])) {
            Response::validationError($validator->errors());
        }

        $type = $input['type'];
        $data = $input['data'];

        Logger::debug("Save config - Type: {$type}", ['data' => $data]);

        try {
            switch ($type) {
                case 'git':
                    $this->saveGitConfig($data);
                    break;

                case 'site':
                    $this->saveSiteConfig($data);
                    break;

                case 'theme':
                    $this->saveThemeConfig($data);
                    break;

                case 'main':
                case 'security':
                case 'integrations':
                case 'plugins':
                case 'policies':
                default:
                    $this->saveMainConfig($data, $type);
                    break;
            }

            Response::success(null, 'Configuration saved successfully');
        } catch (\Exception $e) {
            Logger::error("Config save error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Save git configuration
     */
    private function saveGitConfig(array $data): void
    {
        $data           = ConfigHelper::filterBySchema($data, 'git');
        $existingConfig = $this->configManager->getConfig('git');
        $mergedConfig   = ConfigHelper::smartMerge($existingConfig, $data, 'git');
        $this->configManager->updateConfig('git', $mergedConfig);
    }

    /**
     * Save site configuration
     */
    private function saveSiteConfig(array $data): void
    {
        $data           = ConfigHelper::filterBySchema($data, 'site');
        $existingConfig = $this->configManager->getConfig('site');
        $mergedConfig   = ConfigHelper::smartMerge($existingConfig, $data, 'site');
        $this->configManager->updateConfig('site', $mergedConfig);
    }

    /**
     * Save theme configuration
     */
    private function saveThemeConfig(array $data): void
    {
        $data           = ConfigHelper::filterBySchema($data, 'theme');
        $existingConfig = $this->configManager->getConfig('theme');
        $mergedConfig   = ConfigHelper::smartMerge($existingConfig, $data, 'theme');
        $this->configManager->updateConfig('theme', $mergedConfig);
    }

    /**
     * Save main configuration
     */
    private function saveMainConfig(array $data, string $type): void
    {
        $mainConfig   = $this->configManager->getConfig('main');
        $mergedConfig = ConfigHelper::smartMerge($mainConfig, $data, 'main');
        $this->configManager->updateConfig('main', $mergedConfig);

        Logger::debug("Main config saved for type: {$type}");
    }

    /**
     * Save forms configuration
     */
    public function saveFormsConfig(): void
    {
        $input = $this->getJsonInput();

        if (! $input) {
            Response::error('Invalid JSON data');
        }

        try {
            require_once __DIR__ . '/../core/FileSystem.php';

            $formsConfigPath = dirname(dirname(__DIR__)) . '/config/forms-config.json';

            if (! FileSystem::writeJson($formsConfigPath, $input)) {
                throw new \Exception('Failed to write forms-config.json');
            }

            Response::success(null, 'Form placeholders saved successfully');
        } catch (\Exception $e) {
            Logger::error("Forms config save error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Save active theme
     */
    public function saveActiveTheme(): void
    {
        $input = $this->getJsonInput();
        $theme = $input['theme'] ?? '';

        if (empty($theme)) {
            Response::error('Theme is required');
        }

        try {
            $themeConfig = $this->configManager->getConfig('theme');

            if (! isset($themeConfig['available_themes']) || empty($themeConfig['available_themes'])) {
                $themeConfig['available_themes'] = $this->configManager->getAvailableThemes();
            }

            if (! in_array($theme, $themeConfig['available_themes'], true)) {
                Response::error('Selected theme is not available');
            }

            $themeConfig['active_theme'] = $theme;
            $this->configManager->updateConfig('theme', $themeConfig);

            Response::success(null, 'Active theme updated successfully');
        } catch (\Exception $e) {
            Logger::error("Theme save error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Refresh theme list
     */
    public function refreshThemeList(): void
    {
        try {
            $availableThemes                 = $this->configManager->getAvailableThemes();
            $themeConfig                     = $this->configManager->getConfig('theme');
            $themeConfig['available_themes'] = $availableThemes;

            if (! in_array($themeConfig['active_theme'] ?? '', $availableThemes, true)) {
                $themeConfig['active_theme'] = $availableThemes[0] ?? 'LifeGuide';
            }

            $this->configManager->updateConfig('theme', $themeConfig);

            Response::success([
                'themes'       => $availableThemes,
                'active_theme' => $themeConfig['active_theme'],
            ], 'Theme list refreshed successfully');
        } catch (\Exception $e) {
            Logger::error("Theme refresh error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Get theme configuration
     */
    public function getThemeConfig(): void
    {
        $themeConfig = $this->configManager->getConfig('theme') ?: [];

        if (! isset($themeConfig['overrides'])) {
            $themeConfig['overrides'] = [
                'slides_override' => true,
                'pages_override'  => true,
                'cpt_override'    => true,
            ];
        }

        Response::success($themeConfig);
    }

    /**
     * Save theme overrides
     */
    public function saveThemeOverrides(): void
    {
        $input     = $this->getJsonInput();
        $overrides = $input['overrides'] ?? [];

        $validOverrides = [
            'slides_override' => isset($overrides['slides_override']) ? (bool) $overrides['slides_override'] : true,
            'pages_override'  => isset($overrides['pages_override']) ? (bool) $overrides['pages_override'] : true,
            'cpt_override'    => isset($overrides['cpt_override']) ? (bool) $overrides['cpt_override'] : true,
        ];

        $themeConfig              = $this->configManager->getConfig('theme') ?: [];
        $themeConfig['overrides'] = $validOverrides;
        $this->configManager->updateConfig('theme', $themeConfig);

        Response::success(['overrides' => $validOverrides], 'Override settings saved successfully');
    }

    /**
     * Get JSON input from request body
     */
    private function getJsonInput(): array
    {
        $rawInput = file_get_contents('php://input');
        $input    = json_decode($rawInput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON input');
        }

        return $input ?: [];
    }

    /**
     * List all config files
     */
    public function listConfigFiles(): void
    {
        try {
            $configDir = dirname(dirname(__DIR__)) . '/config';
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

            Response::success(['files' => $files]);
        } catch (\Exception $e) {
            Logger::error("List config files error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Get raw config file content
     */
    public function getRawConfig(): void
    {
        try {
            $filename = $_GET['file'] ?? '';

            if (empty($filename)) {
                Response::error('File name is required');
            }

            // Validate filename to prevent directory traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                Response::error('Invalid file name');
            }

            // Only allow .json files
            if (pathinfo($filename, PATHINFO_EXTENSION) !== 'json') {
                Response::error('Only JSON files are allowed');
            }

            $configDir = dirname(dirname(__DIR__)) . '/config';
            $filePath  = $configDir . '/' . $filename;

            if (! file_exists($filePath)) {
                Response::error('File not found');
            }

            $content  = file_get_contents($filePath);
            $metadata = [
                'name'     => $filename,
                'size'     => filesize($filePath),
                'lines'    => substr_count($content, "\n") + 1,
                'modified' => filemtime($filePath),
            ];

            // Check if a default exists for this config
            global $configDefaultsManager;
            $hasDefault  = $configDefaultsManager->hasDefault($filename);
            $defaultInfo = $hasDefault ? $configDefaultsManager->getDefaultInfo($filename) : null;

            Response::success([
                'content'      => $content,
                'metadata'     => $metadata,
                'has_default'  => $hasDefault,
                'default_info' => $defaultInfo,
            ]);
        } catch (\Exception $e) {
            Logger::error("Get raw config error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Get ClickUp configuration
     */
    public function getClickupConfig(): void
    {
        try {
            $localConfig   = $this->configManager->getConfig('local');
            $clickupConfig = $localConfig['integrations']['clickup'] ?? [
                'api_token'       => '',
                'team_id'         => '',
                'webhook_enabled' => true,
            ];

            Response::success(['config' => $clickupConfig]);
        } catch (\Exception $e) {
            Logger::error("Get ClickUp config error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Save ClickUp configuration
     */
    public function saveClickupConfig(): void
    {
        try {
            $input = $this->getJsonInput();

            $apiToken       = trim($input['api_token'] ?? '');
            $teamId         = trim($input['team_id'] ?? '');
            $webhookEnabled = $input['webhook_enabled'] ?? true;

            if (empty($apiToken)) {
                Response::error('API Token is required');
            }

            // Load current local config
            $localConfig = $this->configManager->getConfig('local');

            // Update ClickUp configuration
            if (! isset($localConfig['integrations'])) {
                $localConfig['integrations'] = [];
            }

            $localConfig['integrations']['clickup'] = [
                'api_token'       => $apiToken,
                'team_id'         => $teamId,
                'webhook_enabled' => $webhookEnabled,
            ];

            // Save to local config
            $this->configManager->updateConfig('local', $localConfig);

            Response::success(null, 'ClickUp configuration saved successfully');
        } catch (\Exception $e) {
            Logger::error("Save ClickUp config error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Test ClickUp connection
     */
    public function testClickupConnection(): void
    {
        try {
            $input = $this->getJsonInput();

            if (empty($input['api_token'])) {
                Response::error('API Token is required');
            }

            $apiToken = trim($input['api_token']);

            // Test connection by fetching authenticated user info
            $ch = curl_init('https://api.clickup.com/api/v2/user');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    "Authorization: {$apiToken}",
                    "Content-Type: application/json",
                ],
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Response::error("Connection error: {$error}");
            }

            if ($httpCode !== 200) {
                $responseData = json_decode($response, true);
                $errorMsg     = $responseData['err'] ?? 'Authentication failed';
                Response::error($errorMsg);
            }

            $userData = json_decode($response, true);

            Response::success([
                'message' => 'Connection successful',
                'user'    => [
                    'username' => $userData['user']['username'] ?? 'Unknown',
                    'email'    => $userData['user']['email'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            Logger::error("Test ClickUp connection error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Save config as default
     */
    public function saveConfigDefault(): void
    {
        try {
            $input    = $this->getJsonInput();
            $filename = $input['filename'] ?? '';

            if (empty($filename)) {
                Response::error('Filename is required');
            }

            // Validate filename
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                Response::error('Invalid file name');
            }

            if (pathinfo($filename, PATHINFO_EXTENSION) !== 'json') {
                Response::error('Only JSON files are allowed');
            }

            // Get user email from session if available
            require_once __DIR__ . '/../admin/includes/Auth.php';
            $userEmail = Auth::getEmail();

            global $configDefaultsManager;
            $result = $configDefaultsManager->saveDefault($filename, $userEmail);

            if ($result['success']) {
                Response::success($result, $result['message']);
            } else {
                Response::error($result['message']);
            }
        } catch (\Exception $e) {
            Logger::error("Save config default error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Load config from default
     */
    public function loadConfigDefault(): void
    {
        try {
            $input    = $this->getJsonInput();
            $filename = $input['filename'] ?? '';

            if (empty($filename)) {
                Response::error('Filename is required');
            }

            // Validate filename
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                Response::error('Invalid file name');
            }

            if (pathinfo($filename, PATHINFO_EXTENSION) !== 'json') {
                Response::error('Only JSON files are allowed');
            }

            global $configDefaultsManager;
            $result = $configDefaultsManager->loadDefault($filename);

            if ($result['success']) {
                Response::success($result, $result['message']);
            } else {
                Response::error($result['message']);
            }
        } catch (\Exception $e) {
            Logger::error("Load config default error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Check if config has default
     */
    public function checkConfigDefault(): void
    {
        try {
            $filename = $_GET['filename'] ?? '';

            if (empty($filename)) {
                Response::error('Filename is required');
            }

            global $configDefaultsManager;
            $hasDefault  = $configDefaultsManager->hasDefault($filename);
            $defaultInfo = $hasDefault ? $configDefaultsManager->getDefaultInfo($filename) : null;

            Response::success([
                'has_default'  => $hasDefault,
                'default_info' => $defaultInfo,
            ]);
        } catch (\Exception $e) {
            Logger::error("Check config default error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Load all config defaults
     */
    public function loadAllConfigDefaults(): void
    {
        try {
            global $configDefaultsManager;
            $result = $configDefaultsManager->loadAllDefaults();

            if ($result['success']) {
                Response::success($result, $result['message']);
            } else {
                Response::error($result['message']);
            }
        } catch (\Exception $e) {
            Logger::error("Load all config defaults error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }

    /**
     * Import config files
     */
    public function importConfig(): void
    {
        try {
            require_once __DIR__ . '/../helpers/UploadHelper.php';

            $uploads = [];

            if (isset($_FILES['config_files'])) {
                $uploads = UploadHelper::normalizeFiles($_FILES['config_files']);
            } elseif (isset($_FILES['config_file'])) {
                $uploads = UploadHelper::normalizeFiles($_FILES['config_file']);
            } else {
                Response::error('No files provided');
            }

            $configDir = dirname(dirname(__DIR__)) . '/config';
            $results   = [];
            $success   = true;

            foreach ($uploads as $file) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $results[$file['name']] = [
                        'success' => false,
                        'message' => 'Upload error code: ' . $file['error'],
                    ];
                    $success = false;
                    continue;
                }

                $content = file_get_contents($file['tmp_name']);
                $result  = UploadHelper::importConfigContent($file['name'], $content, $configDir, 'upload');

                $results[$file['name']] = $result;
                if (! $result['success']) {
                    $success = false;
                }
            }

            if ($success) {
                Response::success(['results' => $results], 'All config files imported successfully');
            } else {
                Response::success(['results' => $results], 'Some config files failed to import');
            }
        } catch (\Exception $e) {
            Logger::error("Import config error: " . $e->getMessage());
            Response::error($e->getMessage());
        }
    }
}
