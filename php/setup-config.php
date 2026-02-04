#!/usr/bin/env php
<?php
    /**
 * Kinsta Environment Configuration Setup
 *
 * Reads environment variables from Kinsta and writes auth.json
 * Run this via: php php/setup-config.php
 * Or via web: curl https://your-app/php/setup-config.php
 */

    // Determine if running via CLI or web
    $isCLI = php_sapi_name() === 'cli';

    // Set content type for web requests
    if (! $isCLI) {
    header('Content-Type: application/json');
    }

    // Security check for web requests
    if (! $isCLI) {
    // Only allow from localhost or authenticated sessions
    $allowedIPs = ['127.0.0.1', '::1'];
    $remoteIP   = $_SERVER['REMOTE_ADDR'] ?? '';

    // Check if request has setup token or is from localhost
    $setupToken = $_GET['token'] ?? $_ENV['SETUP_TOKEN'] ?? getenv('SETUP_TOKEN');
    $validToken = ! empty($setupToken) && $setupToken === ($_ENV['SETUP_TOKEN'] ?? getenv('SETUP_TOKEN'));

    if (! in_array($remoteIP, $allowedIPs) && ! $validToken) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied. Use setup token or run from localhost.']);
        exit;
    }
    }

    // Configuration
    $configDir      = __DIR__ . '/../config';
    $authConfigFile = $configDir . '/auth.json';

    // Read Kinsta environment variables
    $envVars = [
    'client_id'      => $_ENV['client_id'] ?? getenv('client_id') ?: '',
    'client_secret'  => $_ENV['client_secret'] ?? getenv('client_secret') ?: '',
    'redirect_uri'   => $_ENV['redirect_uri'] ?? getenv('redirect_uri') ?: '',
    'allowed_domain' => $_ENV['allowed_domain'] ?? getenv('allowed_domain') ?: 'frontlinestrategies.co',
    ];

    // Validation
    $missingVars = [];
    foreach (['client_id', 'client_secret', 'redirect_uri'] as $required) {
    if (empty($envVars[$required])) {
        $missingVars[] = $required;
    }
    }

    // Prepare result
    $result = [
    'success'      => false,
    'message'      => '',
    'config_file'  => $authConfigFile,
    'missing_vars' => $missingVars,
    ];

    // Check if environment variables exist
    if (! empty($missingVars)) {
    $result['message']       = 'Missing required environment variables. Please configure in Kinsta dashboard.';
    $result['required_vars'] = ['client_id', 'client_secret', 'redirect_uri'];
    $result['optional_vars'] = ['allowed_domain'];

    if ($isCLI) {
        echo "⚠️  Missing environment variables:\n";
        foreach ($missingVars as $var) {
            echo "   - $var\n";
        }
        echo "\nConfigure these in Kinsta Environment Variables dashboard.\n";
        exit(1);
    } else {
        http_response_code(400);
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }
    }

    // Ensure config directory exists
    if (! is_dir($configDir)) {
    mkdir($configDir, 0755, true);
    }

    // Create auth configuration
    $authConfig = [
    'client_id'      => $envVars['client_id'],
    'client_secret'  => $envVars['client_secret'],
    'redirect_uri'   => $envVars['redirect_uri'],
    'allowed_domain' => $envVars['allowed_domain'],
    ];

    // Write configuration file
    $jsonContent  = json_encode($authConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $writeSuccess = file_put_contents($authConfigFile, $jsonContent);

    if ($writeSuccess === false) {
    $result['message'] = 'Failed to write auth.json. Check directory permissions.';

    if ($isCLI) {
        echo "❌ Failed to write $authConfigFile\n";
        echo "   Check directory permissions for: $configDir\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }
    }

    // Set secure permissions
    @chmod($authConfigFile, 0640);

    // Success!
    $result['success'] = true;
    $result['message'] = 'Google OAuth configuration written successfully';
    $result['config']  = [
    'client_id'      => substr($envVars['client_id'], 0, 20) . '...****',
    'redirect_uri'   => $envVars['redirect_uri'],
    'allowed_domain' => $envVars['allowed_domain'],
    ];

    if ($isCLI) {
    echo "✓ Google OAuth configured successfully\n";
    echo "  - Config file: $authConfigFile\n";
    echo "  - Client ID: " . substr($envVars['client_id'], 0, 20) . "...****\n";
    echo "  - Redirect URI: {$envVars['redirect_uri']}\n";
    echo "  - Allowed Domain: {$envVars['allowed_domain']}\n";
    exit(0);
    } else {
    echo json_encode($result, JSON_PRETTY_PRINT);
}
