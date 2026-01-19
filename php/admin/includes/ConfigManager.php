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
            'theme' => 'theme-config.json',
            'git'   => 'git.json',
            'site'  => 'site.json',
        ];

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
            'theme' => 'theme-config.json',
            'git'   => 'git.json',
            'site'  => 'site.json',
        ];

        if (! isset($configFiles[$type])) {
            throw new Exception("Unknown config type: {$type}");
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
        $required = ['active_theme', 'available_themes'];
        foreach ($required as $key) {
            if (! isset($data[$key])) {
                return false;
            }
        }

        // Check if active theme exists in available themes
        if (! in_array($data['active_theme'], $data['available_themes'])) {
            return false;
        }

        return true;
    }

    /**
     * Validate git configuration
     */
    private function validateGitConfig($data)
    {
        $required = ['user', 'host', 'port', 'org', 'repo'];
        foreach ($required as $key) {
            if (! isset($data[$key]) || empty($data[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate site configuration
     */
    private function validateSiteConfig($data)
    {
        $required = ['display_name', 'admin_email', 'admin_password', 'site_title'];
        foreach ($required as $key) {
            if (! isset($data[$key]) || empty(trim($data[$key]))) {
                error_log("Site config validation failed for field: $key. Value: " . ($data[$key] ?? 'NOT_SET'));
                return false;
            }
        }
        return true;
    }

    /**
     * Get all available themes
     */
    public function getAvailableThemes()
    {
        $pagesDir     = dirname(dirname(dirname(__DIR__))) . '/pages';
        $themes       = [];
        $excludedDirs = ['Custom Posts']; // Exclude non-theme directories

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
