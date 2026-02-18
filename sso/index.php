<?php
/**
 * SSO Endpoint - Identity Provider
 *
 * Handles SSO requests from WordPress sites that have the fls-google-auth plugin.
 * Registered sites are stored in the database (sso_sites table) instead of sso.json.
 * A domain not registered in the DB is rejected (implicit allowlist).
 *
 * Flow:
 *   1. WP site redirects user here with domain, callback_url, state params
 *   2. If user not logged into sitebuilder → redirect to login.php
 *   3. After login (or if already logged in) → create a one-time DB token and redirect back
 *   4. WP plugin calls /sso/verify with the token to exchange it for user data
 *
 * Why tokens instead of HMAC signatures:
 *   - WP plugin no longer needs FLS_SSO_SECRET in wp-config.php
 *   - Tokens are single-use — replaying the callback URL is impossible
 *   - Full audit trail in the sso_tokens table
 */

require_once __DIR__ . '/../php/admin/includes/Auth.php';
require_once __DIR__ . '/../php/admin/includes/SsoManager.php';

Auth::init();

// --- Helper ---

/**
 * Validate incoming SSO request parameters.
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

    // Verify the callback URL hostname matches the claimed domain
    $callbackHost = strtolower(parse_url($callbackUrl, PHP_URL_HOST) ?? '');
    if ($callbackHost !== strtolower($domain)) {
        return 'Callback URL host does not match the declared domain';
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
 * Redirect back to WordPress with an error code.
 */
function redirectWithError(string $callbackUrl, string $state, string $errorCode): void
{
    $params    = http_build_query(['fls_sso' => 'callback', 'error' => $errorCode, 'state' => $state]);
    $separator = (strpos($callbackUrl, '?') !== false) ? '&' : '?';
    header('Location: ' . $callbackUrl . $separator . $params);
    exit;
}

// --- Main SSO handler ---

// 1. Determine request source: returning from login (session) or fresh SSO request (GET)
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

// 2. Validate request parameters
$validation = validateRequest($domain, $callbackUrl, $state);
if ($validation !== true) {
    http_response_code(400);
    die($validation);
}

// 3. Check domain is registered in DB — rejects unknown domains (implicit allowlist)
try {
    $ssoManager = new SsoManager();
    if (! $ssoManager->isDomainRegistered($domain)) {
        error_log('SSO: Rejected request from unregistered domain: ' . $domain);
        http_response_code(403);
        die('Domain not registered for SSO');
    }
} catch (\Exception $e) {
    error_log('SSO: Database error checking domain registration: ' . $e->getMessage());
    http_response_code(500);
    die('SSO temporarily unavailable');
}

// 4. Check if user is authenticated at this sitebuilder
if (! Auth::isLoggedIn()) {
    // Store SSO request params in session so we can resume after login
    $_SESSION['sso_pending'] = [
        'domain'       => $domain,
        'callback_url' => $callbackUrl,
        'state'        => $state,
    ];

    // Set auth_redirect so login.php sends user back here after successful login
    $_SESSION['auth_redirect'] = '/sso/';

    header('Location: /php/login.php');
    exit;
}

// 5. User IS authenticated — get their data from the sitebuilder session
$email = Auth::getEmail();
$name  = Auth::getName();

if (empty($email)) {
    error_log('SSO: Authenticated user has no email in session');
    redirectWithError($callbackUrl, $state, 'no_email');
}

// 6. Create a one-time verification token stored in DB (valid for 5 minutes)
try {
    $token = $ssoManager->createToken($domain, $email, $name);
} catch (\Exception $e) {
    error_log('SSO: Failed to create verification token: ' . $e->getMessage());
    redirectWithError($callbackUrl, $state, 'server_error');
}

// 7. Redirect back to WordPress with the token and state (no signature, no user data in URL)
$params    = http_build_query(['fls_sso' => 'callback', 'token' => $token, 'state' => $state]);
$separator = (strpos($callbackUrl, '?') !== false) ? '&' : '?';

header('Location: ' . $callbackUrl . $separator . $params);
exit;
