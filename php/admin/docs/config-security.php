<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Configuration - Frontline Framework</title>

    <link rel="icon" href="/php/admin/assets/img/favicon.ico">
    <link rel="stylesheet" href="//fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="/php/admin/docs/style.css">
</head>
<body>
    <div class="docs-container">
        <!-- Header -->
        <header class="docs-header">
            <div class="header-content">
                <div class="logo-section">
                    <img src="/php/admin/assets/img/logo.png" alt="Frontline Framework" class="logo">
                    <div>
                        <h1>Security Configuration</h1>
                        <p>Protect your website with advanced security features</p>
                    </div>
                </div>
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Documentation Home
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="docs-main">
            <div class="docs-content">
                <!-- Breadcrumb -->
                <nav class="breadcrumb">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <span>/</span>
                    <a href="index.php#configuration">Configuration</a>
                    <span>/</span>
                    <span>Security</span>
                </nav>

                <h1 class="page-title">Security Configuration</h1>
                <p class="page-subtitle">Configure advanced security features for deployment via security.sh script.</p>

                <!-- Wordfence -->
                <section class="content-section">
                    <h2><i class="fas fa-shield-halved"></i> Wordfence Security</h2>

                    <p>Wordfence is a comprehensive security plugin that protects your WordPress site from threats.</p>

                    <h3>Available Features</h3>

                    <div class="card">
                        <h4><i class="fas fa-search"></i> Real-Time Scanning</h4>
                        <p>Monitors file changes and suspicious activity in real-time. Alerts you immediately if malicious code or unauthorized changes are detected.</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-user-lock"></i> Brute Force Protection</h4>
                        <p>Blocks IP addresses after multiple failed login attempts. Prevents automated attacks trying to guess your password.</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-bug"></i> Malware Scanning</h4>
                        <p>Scheduled daily scans for malicious code, backdoors, and known vulnerabilities. Compares your files against official WordPress repository versions.</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-envelope"></i> Email Alerts</h4>
                        <p>Immediate notifications for security events including failed logins, file changes, malware detection, and suspicious activity.</p>
                    </div>
                </section>

                <!-- Geo-Blocking -->
                <section class="content-section">
                    <h2><i class="fas fa-globe"></i> Geo-Blocking</h2>

                    <p>Restrict access to your website by country. Only visitors from allowed countries can access the site.</p>

                    <h3>How It Works</h3>
                    <p>Select countries to allow access. All other countries will see an "Access Denied" page.</p>

                    <h3>Selecting Countries</h3>
                    <ul>
                        <li>Click on countries in the dropdown to add them to the allowed list</li>
                        <li>Use search to quickly find countries</li>
                        <li>Remove countries by clicking the X next to their name</li>
                    </ul>

                    <div class="alert alert-danger">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-circle"></i>
                            Critical Warning
                        </div>
                        <p><strong>Make sure YOUR country is in the allowed list!</strong></p>
                        <p>If you enable geo-blocking without including your location, you'll be locked out of your own site. Always test with IP whitelisting first or include your country before enabling.</p>
                    </div>

                    <h3>Use Cases</h3>
                    <ul>
                        <li><strong>Local Campaigns:</strong> Restrict to home country only</li>
                        <li><strong>Regional Sites:</strong> Allow specific geographic regions</li>
                        <li><strong>Security:</strong> Block countries known for attack traffic</li>
                    </ul>
                </section>

                <!-- IP Whitelisting -->
                <section class="content-section">
                    <h2><i class="fas fa-network-wired"></i> IP Whitelisting</h2>

                    <p>Only allow specific IP addresses or ranges to access the site.</p>

                    <h3>IP Format</h3>
                    <p>Enter IPs in CIDR notation:</p>
                    <ul>
                        <li><strong>Single IP:</strong> <code>192.168.1.1</code></li>
                        <li><strong>IP Range:</strong> <code>192.168.1.0/24</code> (allows 192.168.1.0 - 192.168.1.255)</li>
                        <li><strong>Multiple IPs:</strong> Enter one per line</li>
                    </ul>

                    <div class="card">
                        <h4>Example Configuration</h4>
                        <pre><code>192.168.1.100        # Office IP
203.0.113.0/24       # Office network range
198.51.100.50        # Remote worker IP</code></pre>
                    </div>

                    <h3>Finding Your IP</h3>
                    <p>Visit <a href="https://www.whatismyip.com/" target="_blank">whatismyip.com</a> to find your current IP address.</p>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Important Note
                        </div>
                        <p>Most home/mobile IPs change periodically. Consider using IP ranges or combine with other security methods. For dynamic IPs, IP whitelisting may lock you out when your IP changes.</p>
                    </div>

                    <h3>When to Use</h3>
                    <ul>
                        <li><strong>Internal Sites:</strong> Staff-only access</li>
                        <li><strong>Staging Sites:</strong> Developer/client access only</li>
                        <li><strong>High Security:</strong> Combined with geo-blocking for maximum protection</li>
                    </ul>
                </section>

                <!-- Two-Factor Authentication -->
                <section class="content-section">
                    <h2><i class="fas fa-mobile-screen"></i> Two-Factor Authentication (2FA)</h2>

                    <p>Require a second authentication factor (beyond password) for admin login.</p>

                    <h3>Configuration Options</h3>

                    <div class="card">
                        <h4><i class="fas fa-user-shield"></i> Enforce for Administrators</h4>
                        <p>Require all admin users to set up 2FA. Highly recommended for administrator accounts.</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-calendar"></i> Grace Period</h4>
                        <p>Number of days before 2FA is mandatory. Gives administrators time to set up their 2FA devices.</p>
                        <ul>
                            <li><strong>0 days:</strong> Immediate enforcement (next login)</li>
                            <li><strong>7 days:</strong> Standard grace period (recommended)</li>
                            <li><strong>14 days:</strong> Extended setup time</li>
                        </ul>
                    </div>

                    <h3>Supported Methods</h3>
                    <ul>
                        <li><strong>Email Codes:</strong> Receive authentication codes via email</li>
                        <li><strong>Authenticator Apps:</strong> Google Authenticator, Authy, Microsoft Authenticator</li>
                        <li><strong>Backup Codes:</strong> One-time use codes for emergencies</li>
                    </ul>

                    <h3>How It Works</h3>
                    <ol>
                        <li>Administrator logs in with username and password</li>
                        <li>System prompts for 2FA setup (if first time)</li>
                        <li>Scan QR code with authenticator app or request email code</li>
                        <li>Enter 6-digit code from app or email</li>
                        <li>Access granted after successful verification</li>
                    </ol>

                    <div class="alert alert-success">
                        <div class="alert-title">
                            <i class="fas fa-check-circle"></i>
                            Security Best Practice
                        </div>
                        <p><strong>Always enable 2FA for administrator accounts!</strong> It's the single most effective protection against account compromise, even if passwords are stolen.</p>
                    </div>
                </section>

                <!-- Security Best Practices -->
                <section class="content-section">
                    <h2><i class="fas fa-lightbulb"></i> Security Best Practices</h2>

                    <h3>Recommended Configuration</h3>
                    <div class="card">
                        <h4>✅ Essential Security Setup</h4>
                        <ul>
                            <li><strong>Wordfence:</strong> Enable all features (scanning, brute force protection, alerts)</li>
                            <li><strong>2FA:</strong> Enforce for all administrators with 7-day grace period</li>
                            <li><strong>Strong Passwords:</strong> Use generated passwords, never reuse</li>
                            <li><strong>Regular Updates:</strong> Keep WordPress, themes, and plugins updated</li>
                        </ul>
                    </div>

                    <h3>Optional Enhanced Security</h3>
                    <div class="card">
                        <h4>🔒 For High-Security Needs</h4>
                        <ul>
                            <li><strong>Geo-Blocking:</strong> Restrict to necessary countries only</li>
                            <li><strong>IP Whitelisting:</strong> For internal/admin access sites</li>
                            <li><strong>Custom Login URL:</strong> Hide wp-admin from bots</li>
                            <li><strong>Disable File Editing:</strong> Prevent code changes via admin</li>
                        </ul>
                    </div>

                    <h3>Security Layers</h3>
                    <p>Use multiple security features together for defense in depth:</p>
                    <ol>
                        <li><strong>Network Layer:</strong> Geo-blocking + IP whitelisting</li>
                        <li><strong>Application Layer:</strong> Wordfence firewall + brute force protection</li>
                        <li><strong>Authentication Layer:</strong> Strong passwords + 2FA</li>
                        <li><strong>Monitoring Layer:</strong> Real-time alerts + malware scanning</li>
                    </ol>
                </section>

                <!-- Troubleshooting -->
                <section class="content-section">
                    <h2><i class="fas fa-wrench"></i> Troubleshooting Security Features</h2>

                    <div class="card">
                        <h3>Locked Out After Enabling Geo-Blocking</h3>
                        <ul>
                            <li>Contact server administrator to disable geo-blocking</li>
                            <li>Access via Kinsta's staging URL (bypasses Cloudflare)</li>
                            <li>SSH into server and disable Wordfence country blocking</li>
                            <li><strong>Prevention:</strong> Always include your country before enabling</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>IP Whitelist Blocking Access</h3>
                        <ul>
                            <li>Your IP address may have changed (common with mobile/home connections)</li>
                            <li>Check current IP at whatismyip.com</li>
                            <li>Contact administrator to add new IP to whitelist</li>
                            <li><strong>Prevention:</strong> Use IP ranges instead of single IPs</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>Lost 2FA Device</h3>
                        <ul>
                            <li>Use backup codes if you saved them during 2FA setup</li>
                            <li>Use email-based 2FA as alternative method</li>
                            <li>Contact administrator to disable 2FA for your account</li>
                            <li><strong>Prevention:</strong> Save backup codes and store securely</li>
                        </ul>
                    </div>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="config-kinsta.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Kinsta Settings</span>
                    </a>
                    <a href="config-integrations.php" class="btn-nav next">
                        <span>Integrations</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="docs-footer">
            <p>&copy; <?php echo date('Y'); ?> Frontline Framework. Documentation for version 2.0</p>
        </footer>
    </div>
</body>
</html>
