<?php
/**
 * Configuration Manager
 * Handles reading, writing, and validating all configuration files
 */

class ConfigManager
{
    private $configDir;
    private $configs = [];

    public function __construct()
    {
        $this->configDir = dirname(dirname(dirname(__DIR__))) . '/config';
        $this->loadConfigs();
    }

    /**
     * Load all configuration files
     */
    private function loadConfigs()
    {
        $configFiles = [
            'main'  => 'config.json',
            'local' => 'local-config.json',
            'theme' => 'theme-config.json',
            'git'   => 'git.json',
            'site'  => 'site.json',
        ];

        // Load all configuration from JSON files
        foreach ($configFiles as $key => $file) {
            $filePath = $this->configDir . '/' . $file;
            if (file_exists($filePath)) {
                $content             = file_get_contents($filePath);
                $this->configs[$key] = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON in {$file}: " . json_last_error_msg());
                }
            } else {
                $this->configs[$key] = [];
            }
        }
    }

    /**
     * Get configuration by type
     */
    public function getConfig($type)
    {
        return isset($this->configs[$type]) ? $this->configs[$type] : [];
    }

    /**
     * Update configuration
     */
    public function updateConfig($type, $data)
    {
        if (! isset($this->configs[$type])) {
            throw new Exception("Unknown config type: {$type}");
        }

        // Validate the data before saving
        if (! $this->validateConfig($type, $data)) {
            throw new Exception("Invalid configuration data for type: {$type}");
        }

        $this->configs[$type] = $data;

        return $this->saveConfig($type);
    }

    /**
     * Save configuration to file
     */
    private function saveConfig($type)
    {
        $configFiles = [
            'main'  => 'config.json',
            'local' => 'local-config.json',
            'theme' => 'theme-config.json',
            'git'   => 'git.json',
            'site'  => 'site.json',
        ];

        if (! isset($configFiles[$type])) {
            throw new Exception("Unknown config type: {$type}");
        }

        // For theme config, ensure available_themes is always present
        if ($type === 'theme') {
            if (! isset($this->configs[$type]['available_themes']) || empty($this->configs[$type]['available_themes'])) {
                $this->configs[$type]['available_themes'] = $this->getAvailableThemes();
            }
        }

        $filePath = $this->configDir . '/' . $configFiles[$type];
        $jsonData = json_encode($this->configs[$type], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to encode JSON: " . json_last_error_msg());
        }

        $result = file_put_contents($filePath, $jsonData);
        if ($result === false) {
            throw new Exception("Failed to write config file: {$filePath}");
        }

        return true;
    }

    /**
     * Validate configuration data
     */
    private function validateConfig($type, $data)
    {
        switch ($type) {
            case 'main':
                return $this->validateMainConfig($data);
            case 'theme':
                return $this->validateThemeConfig($data);
            case 'git':
                return $this->validateGitConfig($data);
            case 'site':
                return $this->validateSiteConfig($data);
            default:
                return false;
        }
    }

    /**
     * Validate main configuration
     */
    private function validateMainConfig($data)
    {
        $required = ['site', 'authentication', 'security'];
        foreach ($required as $key) {
            if (! isset($data[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate theme configuration
     */
    private function validateThemeConfig($data)
    {
        // Active theme is required
        if (! isset($data['active_theme']) || empty($data['active_theme'])) {
            error_log("Theme config validation failed: active_theme is missing or empty");
            return false;
        }

        // If available_themes is not set, auto-populate it
        if (! isset($data['available_themes']) || empty($data['available_themes'])) {
            error_log("Theme config: available_themes not set, will be auto-populated");
            // This is acceptable - we'll auto-populate it
            return true;
        }

        // Check if active theme exists in available themes
        if (! in_array($data['active_theme'], $data['available_themes'])) {
            error_log("Theme config validation failed: active_theme '{$data['active_theme']}' not in available_themes");
            return false;
        }

        return true;
    }

    /**
     * Validate git configuration
     */
    private function validateGitConfig($data)
    {
        // At minimum, we need org and repo for any Git operations
        $required = ['org', 'repo'];
        foreach ($required as $key) {
            if (! isset($data[$key]) || empty(trim($data[$key]))) {
                error_log("Git config validation failed for required field: $key");
                return false;
            }
        }

        // If token is provided, that's sufficient for GitHub API access
        if (isset($data['token']) && ! empty(trim($data['token']))) {
            return true;
        }

        // If no token, we need host and user for traditional Git access
        if ((isset($data['host']) && ! empty(trim($data['host']))) &&
            (isset($data['user']) && ! empty(trim($data['user'])))) {
            return true;
        }

        // Allow empty/minimal config for now - user might configure later
        return true;
    }

    /**
     * Validate site configuration
     */
    private function validateSiteConfig($data)
    {
        // Basic site info should always be present if provided
        $basicFields = ['site_title', 'display_name'];
        foreach ($basicFields as $key) {
            if (isset($data[$key]) && empty(trim($data[$key]))) {
                error_log("Site config validation failed for field: $key. Value cannot be empty when provided.");
                return false;
            }
        }

        // Admin fields are optional but if provided, both email and password should be set
        $adminFields   = ['admin_email', 'admin_password'];
        $hasAdminField = false;
        foreach ($adminFields as $key) {
            if (isset($data[$key]) && ! empty(trim($data[$key]))) {
                $hasAdminField = true;
                break;
            }
        }

        // If any admin field is provided, validate email format
        if (isset($data['admin_email']) && ! empty(trim($data['admin_email']))) {
            if (! filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
                error_log("Site config validation failed: admin_email is not a valid email address");
                return false;
            }
        }

        return true;
    }

    /**
     * Validate configuration data
     */
    public function getAvailableThemes()
    {
        $themes = [];

        // Prefer the newer structure: pages/themes/<ThemeName>/layouts
        $themesDir = dirname(dirname(dirname(__DIR__))) . '/pages/themes';
        if (is_dir($themesDir)) {
            $dirs = scandir($themesDir);
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($themesDir . '/' . $dir)) {
                    $layoutsDir = $themesDir . '/' . $dir . '/layouts';
                    if (is_dir($layoutsDir)) {
                        $themes[] = $dir;
                    }
                }
            }
            // Return early if we found themes in the newer structure
            if (! empty($themes)) {
                return $themes;
            }
        }

        // Fallback to legacy structure: pages/<ThemeName>/layouts
        $pagesDir     = dirname(dirname(dirname(__DIR__))) . '/pages';
        $excludedDirs = ['cpt']; // Exclude non-theme directories

        if (is_dir($pagesDir)) {
            $dirs = scandir($pagesDir);
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($pagesDir . '/' . $dir)) {
                    // Skip excluded directories that are not themes
                    if (! in_array($dir, $excludedDirs)) {
                        // Additional check: ensure it's a theme by checking for layouts directory
                        $layoutsDir = $pagesDir . '/' . $dir . '/layouts';
                        if (is_dir($layoutsDir)) {
                            $themes[] = $dir;
                        }
                    }
                }
            }
        }

        return $themes;
    }

    /**
     * Get theme pages
     */
    public function getThemePages($theme)
    {
        $themePagesDir = dirname(dirname(dirname(__DIR__))) . '/pages/' . $theme . '/layouts';
        $pages         = [];

        if (is_dir($themePagesDir)) {
            $files = scandir($themePagesDir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $pageName = pathinfo($file, PATHINFO_FILENAME);
                    $pages[]  = $pageName;
                }
            }
        }

        return $pages;
    }

    /**
     * Get configuration validation errors
     */
    public function getValidationErrors()
    {
        $errors = [];

        // Check each config type
        foreach ($this->configs as $type => $config) {
            if (! $this->validateConfig($type, $config)) {
                $errors[] = "Invalid configuration: {$type}";
            }
        }

        return $errors;
    }

    /**
     * Get configuration summary for dashboard
     */
    public function getConfigSummary()
    {
        return [
            'active_theme'     => $this->configs['theme']['active_theme'] ?? 'Unknown',
            'site_title'       => $this->configs['site']['site_title'] ?? 'Unknown',
            'admin_email'      => $this->configs['site']['admin_email'] ?? 'Unknown',
            'repo'             => ($this->configs['git']['org'] ?? 'Unknown') . '/' . ($this->configs['git']['repo'] ?? 'Unknown'),
            'security_enabled' => $this->configs['main']['security']['enabled'] ?? false,
            'maintenance_mode' => $this->configs['main']['site']['settings']['maintenance_mode'] ?? false,
        ];
    }
}
