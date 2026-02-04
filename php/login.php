<?php
/**
 * Login Page - Google Workspace Authentication
 *
 * Authenticates users using Google OAuth2
 * Only allows @frontlinestrategies.co domain
 */

require_once __DIR__ . '/admin/includes/Auth.php';

Auth::init();

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    Auth::logout();
    header('Location: /php/login.php?logged_out=1');
    exit;
}

// Handle OAuth callback
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $state = $_GET['state'] ?? '';

    $result = Auth::handleOAuthCallback($code, $state);

    if ($result['success']) {
        // Redirect to admin panel
        $redirect = $_SESSION['auth_redirect'] ?? '/php/web-admin.php';
        unset($_SESSION['auth_redirect']);
        header("Location: $redirect");
        exit;
    } else {
        $error = $result['error'];
    }
}

// Handle OAuth error from Google
if (isset($_GET['error'])) {
    $error = match ($_GET['error']) {
        'access_denied' => 'Access was denied. Please try again.',
        'invalid_request' => 'Invalid request. Please try again.',
        default => 'Authentication failed. Please try again.'
    };
}

// Check if already logged in
if (Auth::isLoggedIn()) {
    header('Location: /php/web-admin.php');
    exit;
}

// Get Google auth URL
$googleAuthUrl = Auth::getGoogleAuthUrl();
$isConfigured = Auth::isConfigured();
$allowedDomain = Auth::getAllowedDomain();

// Check for messages
$loggedOut = isset($_GET['logged_out']);
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Frontline Framework</title>

    <link rel="icon" href="/php/admin/assets/img/favicon.ico">
    <link rel="stylesheet" href="//fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --bg-input: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-primary: #14b8a6;
            --accent-hover: #0d9488;
            --accent-glow: rgba(20, 184, 166, 0.3);
            --border-color: rgba(255, 255, 255, 0.1);
            --error-color: #ef4444;
            --error-bg: rgba(239, 68, 68, 0.1);
            --success-color: #22c55e;
            --success-bg: rgba(34, 197, 94, 0.1);
            --warning-color: #f59e0b;
            --warning-bg: rgba(245, 158, 11, 0.1);
            --google-blue: #4285f4;
            --google-blue-hover: #357ae8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1a1a2e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 48px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border-color);
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-logo {
            width: 80px;
            height: auto;
            margin-bottom: 24px;
        }

        .login-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--text-primary), var(--accent-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .alert-error {
            background: var(--error-bg);
            color: var(--error-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success-color);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .alert-warning {
            background: var(--warning-bg);
            color: var(--warning-color);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .alert i {
            font-size: 16px;
        }

        .google-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 14px 24px;
            background: var(--google-blue);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .google-btn:hover {
            background: var(--google-blue-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.4);
        }

        .google-btn:active {
            transform: translateY(0);
        }

        .google-btn svg {
            width: 20px;
            height: 20px;
        }

        .google-btn.disabled {
            background: var(--bg-input);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .google-btn.disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .domain-info {
            margin-top: 24px;
            padding: 16px;
            background: rgba(20, 184, 166, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(20, 184, 166, 0.2);
        }

        .domain-info-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--accent-primary);
            margin-bottom: 8px;
        }

        .domain-info-text {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .domain-badge {
            display: inline-block;
            background: var(--bg-input);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--accent-primary);
        }

        .setup-instructions {
            margin-top: 24px;
            padding: 20px;
            background: var(--bg-input);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .setup-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .setup-steps {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.8;
        }

        .setup-steps li {
            margin-bottom: 8px;
        }

        .setup-steps code {
            background: var(--bg-dark);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--accent-primary);
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .login-footer a {
            color: var(--accent-primary);
            text-decoration: none;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="/php/admin/assets/img/logo.png" alt="Frontline" class="login-logo">
                <h1 class="login-title">Frontline Framework</h1>
                <p class="login-subtitle">Sign in with your Google Workspace account to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($loggedOut): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>You have been logged out successfully.</span>
                </div>
            <?php endif; ?>

            <?php if ($isConfigured): ?>
                <a href="<?php echo htmlspecialchars($googleAuthUrl); ?>" class="google-btn">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#fff"
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                        <path fill="#fff"
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                        <path fill="#fff"
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                        <path fill="#fff"
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                    </svg>
                    Sign in with Google
                </a>

                <div class="domain-info">
                    <div class="domain-info-title">
                        <i class="fas fa-shield-alt"></i>
                        Restricted Access
                    </div>
                    <p class="domain-info-text">
                        Only <span class="domain-badge">@<?php echo htmlspecialchars($allowedDomain); ?></span>
                        Google Workspace accounts are allowed to access this system.
                    </p>
                </div>
            <?php else: ?>
                <button class="google-btn disabled" disabled>
                    <i class="fas fa-lock"></i>
                    Google OAuth Not Configured
                </button>

                <div class="setup-instructions">
                    <div class="setup-title">
                        <i class="fas fa-cog"></i>
                        Setup Required
                    </div>
                    <ol class="setup-steps">
                        <li>Create a project in <a href="https://console.cloud.google.com/" target="_blank">Google Cloud
                                Console</a></li>
                        <li>Enable the <strong>Google+ API</strong> or <strong>People API</strong></li>
                        <li>Create <strong>OAuth 2.0 credentials</strong> (Web application)</li>
                        <li>Add your redirect URI: <code>/php/login.php</code></li>
                        <li>Create <code>config/auth.json</code> with:
                            <pre style="margin-top: 8px; background: var(--bg-dark); padding: 12px; border-radius: 6px; overflow-x: auto;">
{
  "client_id": "YOUR_CLIENT_ID",
  "client_secret": "YOUR_SECRET",
  "redirect_uri": "https://your-domain/php/login.php",
  "allowed_domain": "frontlinestrategies.co"
}</pre>
                        </li>
                    </ol>
                </div>
            <?php endif; ?>

            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> Frontline Strategies</p>
            </div>
        </div>
    </div>
</body>

</html>
