<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Troubleshooting - Frontline Framework</title>

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
                        <h1>Troubleshooting</h1>
                        <p>Common issues and solutions</p>
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
                    <a href="index.php#support">Support</a>
                    <span>/</span>
                    <span>Troubleshooting</span>
                </nav>

                <h1 class="page-title">Troubleshooting</h1>
                <p class="page-subtitle">Solutions to common deployment and configuration issues.</p>

                <!-- Deployment Issues -->
                <section class="content-section">
                    <h2><i class="fas fa-exclamation-circle"></i> Deployment Issues</h2>

                    <div class="card">
                        <h3>Deployment Stuck at "Setup Kinsta"</h3>
                        <ul>
                            <li>Check Kinsta API token is valid and not expired</li>
                            <li>Verify Company ID is correct</li>
                            <li>Ensure you have available site slots in your Kinsta plan</li>
                            <li>Check Kinsta dashboard for site creation status</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>Deployment Fails at "Deploy" Stage</h3>
                        <ul>
                            <li>Check SSH connectivity in deployment logs</li>
                            <li>Verify Kinsta SSH credentials are correct in git.json</li>
                            <li>Ensure JSON config files are valid (no syntax errors)</li>
                            <li>Check /logs/deployment/deployment.log for detailed errors</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>GitHub Actions Fails</h3>
                        <ul>
                            <li>Check GitHub Actions tab in WebsiteBuild repository for error details</li>
                            <li>Verify GitHub token has correct permissions</li>
                            <li>Ensure theme-config.json was uploaded correctly</li>
                            <li>Check if WebsiteBuild repository is accessible</li>
                        </ul>
                    </div>
                </section>

                <!-- Access Issues -->
                <section class="content-section">
                    <h2><i class="fas fa-lock"></i> Access Issues</h2>

                    <div class="card">
                        <h3>Can't Access WordPress Admin</h3>
                        <ul>
                            <li>Wait 5-10 minutes after deployment for DNS propagation</li>
                            <li>Check if IP whitelisting is blocking your IP</li>
                            <li>Verify geo-blocking allows your country</li>
                            <li>Try accessing via Kinsta's staging URL instead of custom domain</li>
                            <li>Check admin username/password in /config/config.json</li>
                        </ul>
                    </div>

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
                </section>

                <!-- Content Issues -->
                <section class="content-section">
                    <h2><i class="fas fa-file-alt"></i> Content Issues</h2>

                    <div class="card">
                        <h3>Forms Not Appearing</h3>
                        <ul>
                            <li>Check if Forminator plugin is active in WordPress admin</li>
                            <li>Verify form JSON files exist in /pages/forms/</li>
                            <li>Check deployment logs for form import errors</li>
                            <li>Ensure page content contains form placeholders</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>Map Not Showing</h3>
                        <ul>
                            <li>Verify Google Maps API key is configured correctly</li>
                            <li>Check if WP Go Maps plugin is active</li>
                            <li>Ensure map placement is configured in integrations.maps.placement</li>
                            <li>Check browser console for JavaScript errors</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>Images Not Loading</h3>
                        <ul>
                            <li>Verify image files were uploaded to /uploads/images/</li>
                            <li>Check file permissions on server (should be 644)</li>
                            <li>Ensure image URLs are correct (no broken links)</li>
                            <li>Check if images exceed server upload limit</li>
                        </ul>
                    </div>
                </section>

                <!-- Checking Logs -->
                <section class="content-section">
                    <h2><i class="fas fa-file-alt"></i> Checking Logs</h2>

                    <p>Deployment logs provide detailed information about the deployment process:</p>

                    <div class="card">
                        <h3>Log Locations</h3>
                        <ul>
                            <li><strong>/logs/deployment/deployment.log</strong> - Main deployment log</li>
                            <li><strong>/logs/api/</strong> - Kinsta API request/response logs</li>
                            <li><strong>/tmp/deployment_status.json</strong> - Current deployment status and timing</li>
                            <li><strong>GitHub Actions Logs</strong> - View in WebsiteBuild repository → Actions tab</li>
                        </ul>
                    </div>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-code"></i>
                            SSH Debugging
                        </div>
                        <p>To debug issues on the server, SSH into your Kinsta site and check:</p>
                        <ul>
                            <li>/tmp/*.json - Uploaded configuration files</li>
                            <li>/tmp/pages/ - Page layouts and content</li>
                            <li>wp-content/debug.log - WordPress debug log</li>
                        </ul>
                    </div>
                </section>

                <!-- Performance Issues -->
                <section class="content-section">
                    <h2><i class="fas fa-tachometer-alt"></i> Performance Issues</h2>

                    <div class="card">
                        <h3>Slow Page Loading</h3>
                        <ul>
                            <li>Clear WordPress cache (Plugins → WP Rocket or similar)</li>
                            <li>Optimize images (compress large files)</li>
                            <li>Enable Kinsta CDN in dashboard</li>
                            <li>Minimize plugins (deactivate unused ones)</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>High Server Resource Usage</h3>
                        <ul>
                            <li>Check for plugins causing high CPU usage</li>
                            <li>Review recent form submissions (spam attacks)</li>
                            <li>Check Wordfence for ongoing attacks</li>
                            <li>Contact Kinsta support for resource analysis</li>
                        </ul>
                    </div>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="wordpress-admin.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>WordPress Admin Guide</span>
                    </a>
                    <a href="faq.php" class="btn-nav next">
                        <span>FAQ</span>
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
