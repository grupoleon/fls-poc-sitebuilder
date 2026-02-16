<?php

require_once __DIR__ . '/../core/Config.php';

/**
 * ConfigHelper
 * Configuration-specific helper functions
 */
class ConfigHelper
{
    /**
     * Get valid schema for each config file type
     *
     * @param string $type Config type (main, git, site, theme)
     * @return array Schema definition
     */
    public static function getSchema(string $type): array
    {
        $schemas = [
            'main'  => self::getMainSchema(),
            'git'   => ['token', 'org', 'repo', 'branch', 'host', 'user', 'port', 'path'],
            'site'  => ['site_title', 'display_name', 'admin_email', 'admin_user', 'admin_password', 'region', 'company', 'wp_language', 'install_mode', 'is_multisite', 'is_subdomain_multisite', 'woocommerce', 'wordpressseo'],
            'theme' => ['active_theme', 'available_themes', 'overrides'],
        ];

        return $schemas[$type] ?? [];
    }

    /**
     * Get main config schema (complex schema)
     *
     * @return array
     */
    private static function getMainSchema(): array
    {
        return [
            'site'                  => [
                'admin'      => ['username', 'email', 'password'],
                'settings'   => ['delete_default_pages'],
                'navigation' => ['enabled', 'replace_existing', 'menu_items'],
                'kinsta_token', 'logo', 'site_title', 'display_name', 'admin_email',
                'admin_user', 'admin_password', 'region', 'company', 'wp_language',
                'install_mode', 'is_multisite', 'is_subdomain_multisite', 'woocommerce', 'wordpressseo',
            ],
            'authentication'        => [
                'api_keys' => [
                    'google_maps', 'google_analytics',
                    'recaptcha' => ['site_key', 'secret_key'],
                    'security'  => ['wpvulndb_api_key', 'vulnerability_scanner_api'],
                ],
            ],
            'security'              => self::getSecuritySchema(),
            'password_policy'       => ['min_length', 'require_uppercase', 'require_lowercase', 'require_numbers', 'require_special_chars', 'prevent_username_password', 'prevent_common_passwords'],
            'wp_2fa_config'         => ['enabled', 'enforce_on_multisite', 'enforcement_policy', 'grace_policy', 'grace_period', 'enforced_roles', 'backup_codes_enabled', 'enable_destroy_session', 'create_custom_user_page', 'custom_user_page_url', 'redirect_after_wizard_redirect', 'login_code_expiry_time', 'backup_codes_wrapper'],
            'wp_security_audit_log' => ['enabled', 'restrict_log_viewer', 'incognito_mode', 'hide_plugin', 'frontend_events', 'backend_events', 'login_page_notification', 'pruning_date_e', 'pruning_limit_e', 'log_404', 'purge_404_log', 'log_visitor_404'],
            'plugins'               => ['keep', 'install'],
            'themes'                => ['install'],
            'integrations'          => self::getIntegrationsSchema(),
        ];
    }

    /**
     * Get security schema
     *
     * @return array
     */
    private static function getSecuritySchema(): array
    {
        return [
            'enabled',
            'ip_blocking'                 => ['enabled', 'allowed_ips', 'blocked_ips'],
            'geo_blocking'                => ['enabled', 'allowed_countries', 'block_admin_area', 'block_frontend', 'emergency_access', 'emergency_access_code', 'whitelist_ips'],
            'two_factor_auth'             => ['enabled', 'required_for_all_users', 'required_roles', 'grace_period_days'],
            'login_protection'            => ['enabled', 'custom_login_url', 'hide_login_page', 'limit_failed_attempts', 'max_attempts', 'lockout_duration'],
            'wordpress_hardening'         => ['enabled', 'disable_file_editing', 'disable_installer', 'hide_wp_version', 'disable_xmlrpc', 'security_headers'],
            'recaptcha_protection'        => ['enabled', 'protect_forms', 'protect_login', 'protect_comments'],
            'vulnerability_monitoring'    => ['enabled', 'notification_email', 'check_frequency', 'check_plugins', 'check_themes', 'auto_update_minor', 'severity_threshold'],
            'vulnerability_notifications' => ['enabled', 'email', 'frequency'],
            'malware_protection'          => [
                'enabled',
                'real_time_scanning' => ['enabled', 'sensitivity'],
                'scheduled_scans'    => ['enabled', 'frequency', 'scan_hour'],
                'scan_options'       => ['scan_malware', 'scan_file_changes', 'scan_core_files', 'scan_plugins', 'scan_themes', 'scan_images', 'scan_comments', 'scan_posts'],
                'email_alerts'       => ['enabled', 'scan_issues', 'blocking_events', 'login_lockouts', 'admin_logins', 'breach_attempts', 'plugin_deactivation', 'file_changes', 'alert_frequency', 'alert_threshold'],
                'auto_cleaning'      => ['enabled', 'comment'],
            ],
            'brute_force_protection'      => ['enabled', 'login_attempt_threshold', 'block_duration_hours', 'immediate_ip_blocking'],
            'ip_whitelist'                => ['enabled', 'ips'],
            'admin_protection'            => ['enabled'],
        ];
    }

    /**
     * Get integrations schema
     *
     * @return array
     */
    private static function getIntegrationsSchema(): array
    {
        return [
            'analytics'           => ['enabled'],
            'theme_customization' => ['enabled'],
            'forms'               => [
                'enabled', 'auto_find_placements',
                'contact_form'         => ['enabled', 'placement', 'placeholders'],
                'volunteer_form'       => ['enabled', 'placement', 'placeholders'],
                'document_upload_form' => ['enabled', 'placement', 'placeholders'],
                'test_form'            => ['enabled', 'placement', 'placeholders'],
            ],
            'social_links'        => ['enabled', 'facebook', 'twitter', 'instagram', 'youtube'],
            'donation'            => ['winred'],
            'maps'                => ['enabled', 'placement', 'markers', 'center', 'zoom', 'auto_find_placements'],
        ];
    }

    /**
     * Get valid root-level keys for a config type
     *
     * @param string $type Config type
     * @return array
     */
    public static function getValidRootKeys(string $type): array
    {
        $schema = self::getSchema($type);

        if (empty($schema)) {
            return [];
        }

        if (in_array($type, ['git', 'site', 'theme'], true)) {
            return $schema;
        }

        return array_keys($schema);
    }

    /**
     * Filter config by schema (whitelist approach)
     *
     * @param array $config Configuration data
     * @param string $type Config type
     * @return array Filtered config
     */
    public static function filterBySchema(array $config, string $type): array
    {
        $validRootKeys = self::getValidRootKeys($type);

        if (empty($validRootKeys)) {
            return $config;
        }

        $filtered = [];

        foreach ($config as $key => $value) {
            if (in_array($key, $validRootKeys, true)) {
                $filtered[$key] = $value;
            }
        }

        if ($type === 'main' && isset($filtered['integrations']['social_links']['linkedin'])) {
            unset($filtered['integrations']['social_links']['linkedin']);
        }

        return $filtered;
    }

    /**
     * Normalize config data types to ensure proper types for specific fields
     *
     * @param array $config Configuration array
     * @return array Normalized config
     */
    public static function normalizeDataTypes(array $config): array
    {
        $arrayFields = [
            'security.ip_blocking.allowed_ips'        => [],
            'security.ip_blocking.blocked_ips'        => [],
            'security.two_factor_auth.required_roles' => ['administrator', 'editor'],
            'security.geo_blocking.allowed_countries' => ['US'],
            'security.geo_blocking.whitelist_ips'     => [],
            'wp_2fa_config.enforced_roles'            => ['administrator', 'editor'],
        ];

        foreach ($arrayFields as $path => $defaultValue) {
            $keys    = explode('.', $path);
            $current = &$config;
            $valid   = true;

            foreach ($keys as $i => $key) {
                if ($i === count($keys) - 1) {
                    if (isset($current[$key])) {
                        $value = $current[$key];

                        if (! is_array($value)) {
                            if (is_string($value)) {
                                if (($value[0] ?? '') === '[' || ($value[0] ?? '') === '{') {
                                    $decoded       = json_decode($value, true);
                                    $current[$key] = is_array($decoded) ? $decoded : $defaultValue;
                                } else {
                                    $current[$key] = empty($value) ? $defaultValue : array_filter(array_map('trim', explode(',', $value)));
                                }
                            } elseif (is_bool($value)) {
                                $current[$key] = $defaultValue;
                            } else {
                                $current[$key] = $defaultValue;
                            }
                        }

                        if (is_array($current[$key])) {
                            $current[$key] = array_values(array_unique($current[$key]));
                        }
                    } else {
                        $current[$key] = $defaultValue;
                    }
                } else {
                    if (! isset($current[$key]) || ! is_array($current[$key])) {
                        $valid = false;
                        break;
                    }
                    $current = &$current[$key];
                }
            }
        }

        return $config;
    }

    /**
     * Validate imported config
     *
     * @param string $configType Config type
     * @param array $data Configuration data
     * @return bool
     */
    public static function validateImported(string $configType, array $data): bool
    {
        $requiredKeys = [
            'config'       => ['site', 'plugins'],
            'git'          => ['token', 'org', 'repo'],
            'site'         => ['site_title', 'admin_email'],
            'theme'        => ['active_theme'],
            'local-config' => [],
        ];

        if (! isset($requiredKeys[$configType])) {
            return false;
        }

        foreach ($requiredKeys[$configType] as $key) {
            if (! isset($data[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get config type from filename
     *
     * @param string $filename Filename
     * @return string Config type
     */
    public static function getTypeFromFilename(string $filename): string
    {
        $basename = basename($filename, '.json');

        $typeMap = [
            'config'       => 'config',
            'git'          => 'git',
            'site'         => 'site',
            'theme-config' => 'theme',
            'local-config' => 'local-config',
        ];

        return $typeMap[$basename] ?? 'unknown';
    }

    /**
     * Merge configurations intelligently
     *
     * @param array $existing Existing configuration
     * @param array $new New configuration
     * @param string $type Config type
     * @return array Merged configuration
     */
    public static function smartMerge(array $existing, array $new, string $type): array
    {
        $existing = self::filterBySchema($existing, $type);
        $existing = self::normalizeDataTypes($existing);

        $new = self::filterBySchema($new, $type);
        $new = self::normalizeDataTypes($new);

        $merged = Config::deepMerge($existing, $new);
        $merged = self::normalizeDataTypes($merged);
        $merged = self::filterBySchema($merged, $type);

        return $merged;
    }
}
