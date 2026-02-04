<?php

/**
 * Google Workspace OAuth Authentication Handler
 *
 * Authenticates users against Google Workspace accounts
 * Only allows users with @frontlinestrategies.co email domain
 */
class Auth
{
    private static $configPath;
    private static $allowedDomain = 'frontlinestrategies.co';

    // Default Google OAuth config - must be configured with actual credentials
    private static $defaultConfig = [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => '',
        'allowed_domain' => 'frontlinestrategies.co'
    ];

    /**
     * Initialize the Auth system
     */
    public static function init($configDir = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($configDir === null) {
            $configDir = dirname(__DIR__, 3) . '/config';
        }

        self::$configPath = $configDir . '/auth.json';

        // Load allowed domain from config if set
        $config = self::getConfig();
        if (!empty($config['allowed_domain'])) {
            self::$allowedDomain = $config['allowed_domain'];
        }
    }

    /**
     * Get OAuth configuration
     */
    private static function getConfig()
    {
        if (file_exists(self::$configPath)) {
            $content = file_get_contents(self::$configPath);
            $config = json_decode($content, true);
            if ($config) {
                return array_merge(self::$defaultConfig, $config);
            }
        }
        return self::$defaultConfig;
    }

    /**
     * Check if user is logged in with valid Google Workspace session
     */
    public static function isLoggedIn()
    {
        self::init();

        // Check if we have a valid session
        if (!isset($_SESSION['google_auth']) || !is_array($_SESSION['google_auth'])) {
            return false;
        }

        $auth = $_SESSION['google_auth'];

        // Check required fields
        if (empty($auth['email']) || empty($auth['logged_in_at'])) {
            return false;
        }

        // Verify email domain
        if (!self::isAllowedDomain($auth['email'])) {
            return false;
        }

        // Check session expiry (8 hours)
        $sessionDuration = 8 * 60 * 60; // 8 hours in seconds
        if (time() - $auth['logged_in_at'] > $sessionDuration) {
            self::logout();
            return false;
        }

        // Check if access token is expired and needs refresh
        if (isset($auth['expires_at']) && time() > $auth['expires_at']) {
            // Try to refresh the token
            if (!empty($auth['refresh_token'])) {
                $refreshed = self::refreshAccessToken($auth['refresh_token']);
                if (!$refreshed) {
                    self::logout();
                    return false;
                }
            } else {
                self::logout();
                return false;
            }
        }

        return true;
    }

    /**
     * Check if email belongs to allowed domain
     */
    private static function isAllowedDomain($email)
    {
        $domain = substr(strrchr($email, '@'), 1);
        return strtolower($domain) === strtolower(self::$allowedDomain);
    }

    /**
     * Get Google OAuth authorization URL
     */
    public static function getGoogleAuthUrl()
    {
        self::init();
        $config = self::getConfig();

        if (empty($config['client_id']) || empty($config['redirect_uri'])) {
            return null;
        }

        // Generate state for CSRF protection
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'email profile openid',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
            'hd' => self::$allowedDomain // Restrict to Google Workspace domain
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Handle OAuth callback from Google
     */
    public static function handleOAuthCallback($code, $state)
    {
        self::init();
        $config = self::getConfig();

        // Verify state for CSRF protection
        if (empty($state) || !isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
            error_log('Auth: Invalid OAuth state');
            return ['success' => false, 'error' => 'Invalid authentication state. Please try again.'];
        }

        unset($_SESSION['oauth_state']);

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            return ['success' => false, 'error' => 'OAuth not configured. Please contact administrator.'];
        }

        // Exchange code for tokens
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $tokenData = [
            'code' => $code,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($tokenData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Auth: Token exchange failed - $error");
            return ['success' => false, 'error' => 'Failed to connect to Google. Please try again.'];
        }

        $tokens = json_decode($response, true);

        if ($httpCode !== 200 || !isset($tokens['access_token'])) {
            error_log('Auth: Token exchange failed - ' . ($tokens['error_description'] ?? 'Unknown error'));
            return ['success' => false, 'error' => $tokens['error_description'] ?? 'Failed to authenticate with Google.'];
        }

        // Get user info
        $userInfo = self::getUserInfo($tokens['access_token']);

        if (!$userInfo) {
            return ['success' => false, 'error' => 'Failed to get user information from Google.'];
        }

        // Verify email domain
        if (!self::isAllowedDomain($userInfo['email'])) {
            error_log('Auth: Domain not allowed - ' . $userInfo['email']);
            return [
                'success' => false,
                'error' => 'Access denied. Only @' . self::$allowedDomain . ' accounts are allowed.'
            ];
        }

        // Verify email is verified
        if (!($userInfo['verified_email'] ?? false)) {
            return ['success' => false, 'error' => 'Your Google email is not verified.'];
        }

        // Create session
        $_SESSION['google_auth'] = [
            'email' => $userInfo['email'],
            'name' => $userInfo['name'] ?? '',
            'picture' => $userInfo['picture'] ?? '',
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'expires_at' => time() + ($tokens['expires_in'] ?? 3600),
            'logged_in_at' => time()
        ];

        error_log('Auth: User logged in - ' . $userInfo['email']);

        return ['success' => true, 'user' => $userInfo];
    }

    /**
     * Get user info from Google API
     */
    private static function getUserInfo($accessToken)
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }

        return null;
    }

    /**
     * Refresh access token
     */
    private static function refreshAccessToken($refreshToken)
    {
        $config = self::getConfig();

        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $tokenData = [
            'refresh_token' => $refreshToken,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'grant_type' => 'refresh_token'
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($tokenData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $tokens = json_decode($response, true);
            if (isset($tokens['access_token'])) {
                $_SESSION['google_auth']['access_token'] = $tokens['access_token'];
                $_SESSION['google_auth']['expires_at'] = time() + ($tokens['expires_in'] ?? 3600);
                return true;
            }
        }

        return false;
    }

    /**
     * Logout user
     */
    public static function logout()
    {
        self::init();
        unset($_SESSION['google_auth']);
        session_destroy();
    }

    /**
     * Get current user's email
     */
    public static function getEmail()
    {
        self::init();
        return $_SESSION['google_auth']['email'] ?? null;
    }

    /**
     * Get current user's name
     */
    public static function getName()
    {
        self::init();
        return $_SESSION['google_auth']['name'] ?? null;
    }

    /**
     * Get current user's profile picture
     */
    public static function getPicture()
    {
        self::init();
        return $_SESSION['google_auth']['picture'] ?? null;
    }

    /**
     * Require authentication - redirect to login if not authenticated
     */
    public static function requireAuth()
    {
        if (!self::isLoggedIn()) {
            $loginUrl = '/php/login.php';
            header("Location: $loginUrl");
            exit;
        }
    }

    /**
     * Check if OAuth is configured
     */
    public static function isConfigured()
    {
        self::init();
        $config = self::getConfig();
        return !empty($config['client_id']) && !empty($config['client_secret']) && !empty($config['redirect_uri']);
    }

    /**
     * Get allowed domain
     */
    public static function getAllowedDomain()
    {
        self::init();
        return self::$allowedDomain;
    }
}
