<?php
/**
 * SSO Endpoint - Identity Provider
 *
 * Handles SSO requests from WordPress sites that have the fls-google-auth plugin.
 *
 * Flow:
 *   1. WP site redirects user here with domain, callback_url, state params
 *   2. If user not logged into sitebuilder → redirect to login.php
 *   3. After login (or if already logged in) → sign user data with HMAC and redirect back
 *
 * Signature format (webhook-style):
 *   payload   = "{email}|{name}|{timestamp}|{state}"
 *   signature = HMAC-SHA256(payload, shared_secret)
 */

require_once __DIR__ . '/../php/admin/includes/Auth.php';

Auth::init();

// --- Helper functions ---

/**
 * Load SSO configuration from config/sso.json
 */
function loadSsoConfig(): ?array
{
    $configPath = dirname(__DIR__) . '/config/sso.json';
    if (! file_exists($configPath)) {
        return null;
    }
    $config = json_decode(file_get_contents($configPath), true);
    if (! $config || empty($config['secret'])) {
        return null;
    }
    return $config;
}

/**
 * Validate incoming SSO request parameters
 *
 * @return true|string True if valid, error message string if not
 */
function validateRequest(string $domain, string $callbackUrl, string $state)
{
    if (empty($domain) || empty($callbackUrl) || empty($state)) {
        return 'Missing required parameters: domain, callback_url, state';
    }

    if (! filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
        return 'Invalid callback URL format';
    }

    // In production, enforce HTTPS for callbacks
    if (defined('APP_ENV') && APP_ENV !== 'development') {
        $scheme = parse_url($callbackUrl, PHP_URL_SCHEME);
        if ($scheme !== 'https') {
            return 'Callback URL must use HTTPS';
        }
    }

    return true;
}

/**
 * Check if the requesting domain is allowed
 */
function isDomainAllowed(string $domain, array $allowedDomains): bool
{
    if (in_array('*', $allowedDomains, true)) {
        return true;
    }
    foreach ($allowedDomains as $allowed) {
        if (strcasecmp($domain, $allowed) === 0) {
            return true;
        }
    }
    return false;
}

/**
 * Generate HMAC-SHA256 signature for the SSO response
 */
function generateSignature(string $email, string $name, int $timestamp, string $state, string $secret): string
{
    $payload = "{$email}|{$name}|{$timestamp}|{$state}";
    return hash_hmac('sha256', $payload, $secret);
}

/**
 * Redirect back to WordPress with an error code
 */
function redirectWithError(string $callbackUrl, string $state, string $errorCode): void
{
    $params = http_build_query([
        'fls_sso' => 'callback',
        'error'   => $errorCode,
        'state'   => $state,
    ]);
    $separator = (strpos($callbackUrl, '?') !== false) ? '&' : '?';
    header('Location: ' . $callbackUrl . $separator . $params);
    exit;
}

// --- Main SSO handler ---

// 1. Load SSO configuration
$ssoConfig = loadSsoConfig();
if (! $ssoConfig) {
    http_response_code(500);
    error_log('SSO: Configuration missing or secret not set in config/sso.json');
    die('SSO not configured. Please set the shared secret in config/sso.json');
}

// 2. Determine request source: returning from login (session) or fresh SSO request (GET)
if (isset($_SESSION['sso_pending'])) {
    // Returning from login.php after authentication
    $domain      = $_SESSION['sso_pending']['domain'];
    $callbackUrl = $_SESSION['sso_pending']['callback_url'];
    $state       = $_SESSION['sso_pending']['state'];
    unset($_SESSION['sso_pending']);
} elseif (isset($_GET['domain'], $_GET['callback_url'], $_GET['state'])) {
    // Fresh SSO request from a WordPress site
    $domain      = trim($_GET['domain']);
    $callbackUrl = trim($_GET['callback_url']);
    $state       = trim($_GET['state']);
} else {
    http_response_code(400);
    die('Missing required parameters: domain, callback_url, state');
}

// 3. Validate request parameters
$validation = validateRequest($domain, $callbackUrl, $state);
if ($validation !== true) {
    http_response_code(400);
    die($validation);
}

// 4. Check domain whitelist
$allowedDomains = $ssoConfig['allowed_domains'] ?? ['*'];
if (! isDomainAllowed($domain, $allowedDomains)) {
    redirectWithError($callbackUrl, $state, 'domain_not_allowed');
}

// 5. Check if user is authenticated at this sitebuilder
if (! Auth::isLoggedIn()) {
    // Store SSO request params in session so we can resume after login
    $_SESSION['sso_pending'] = [
        'domain'       => $domain,
        'callback_url' => $callbackUrl,
        'state'        => $state,
    ];

    // Set auth_redirect so login.php sends user back here after successful login
    $_SESSION['auth_redirect'] = '/sso/';

    // Redirect to sitebuilder login page
    header('Location: /php/login.php');
    exit;
}

// 6. User IS authenticated — get their data from the sitebuilder session
$email = Auth::getEmail();
$name  = Auth::getName();

if (empty($email)) {
    error_log('SSO: Authenticated user has no email in session');
    redirectWithError($callbackUrl, $state, 'no_email');
}

// 7. Generate signed response
$timestamp = time();
$signature = generateSignature($email, $name, $timestamp, $state, $ssoConfig['secret']);

// 8. Build callback URL with signed data and redirect back to WordPress
$params = http_build_query([
    'fls_sso'   => 'callback',
    'email'     => $email,
    'name'      => $name,
    'timestamp' => $timestamp,
    'state'     => $state,
    'signature' => $signature,
]);

$separator   = (strpos($callbackUrl, '?') !== false) ? '&' : '?';
$redirectUrl = $callbackUrl . $separator . $params;

header('Location: ' . $redirectUrl);
exit;
