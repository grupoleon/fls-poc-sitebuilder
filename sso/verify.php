<?php
/**
 * SSO Token Verification Endpoint
 *
 * Called by the WordPress fls-google-auth plugin after receiving a token
 * in the SSO callback redirect. The plugin POSTs the token and its domain
 * here; if valid, this endpoint returns the authenticated user's data.
 *
 * This eliminates the need for FLS_SSO_SECRET in wp-config.php — the WP
 * site never holds any shared secret.
 *
 * Request (POST, JSON body):
 *   { "token": "<64-char hex>", "domain": "<site hostname>" }
 *
 * Response (JSON):
 *   200 { "success": true,  "email": "...", "name": "..." }
 *   401 { "success": false, "error": "invalid_token" }
 *   400 { "success": false, "error": "bad_request" }
 *   429 { "success": false, "error": "rate_limited" }
 */

require_once __DIR__ . '/../php/admin/includes/SsoManager.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

// --- Basic per-IP rate limiting via APCu (5 attempts per minute) ---
$ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateKey   = 'sso_verify_rate:' . $ip;
$rateLimit = 5;

if (function_exists('apcu_enabled') && apcu_enabled()) {
    $attempts = (int) apcu_fetch($rateKey);
    if ($attempts >= $rateLimit) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'rate_limited']);
        exit;
    }
    apcu_add($rateKey, 0, 60);
    apcu_inc($rateKey);
}

// --- Parse JSON body ---
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (! is_array($data) || empty($data['token']) || empty($data['domain'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'bad_request']);
    exit;
}

$token  = trim((string) $data['token']);
$domain = strtolower(trim((string) $data['domain']));

// --- Verify and consume token ---
try {
    $ssoManager = new SsoManager();
    $user       = $ssoManager->verifyAndConsumeToken($token, $domain);
} catch (\Exception $e) {
    error_log('SSO verify error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error']);
    exit;
}

if ($user === null) {
    // No detail leaked — covers: not found, already used, expired, domain mismatch
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'invalid_token']);
    exit;
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'email'   => $user['email'],
    'name'    => $user['name'],
]);
exit;
