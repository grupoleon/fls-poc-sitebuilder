<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Architecture - Frontline Framework</title>

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
                        <h1>System Architecture</h1>
                        <p>How Frontline Framework works under the hood</p>
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
                    <a href="index.php#getting-started">Getting Started</a>
                    <span>/</span>
                    <span>System Architecture</span>
                </nav>

                <h1 class="page-title">How the System Works</h1>
                <p class="page-subtitle">Understanding the two-repository architecture and deployment flow.</p>

                <!-- Architecture Overview -->
                <section class="content-section">
                    <h2><i class="fas fa-diagram-project"></i> Architecture Overview</h2>

                    <p>Frontline Framework uses a two-repository architecture to separate configuration from deployment:</p>

                    <div class="alert alert-success">
                        <div class="alert-title">
                            <i class="fas fa-info-circle"></i>
                            Repository 1: fls-poc-sitebuilder (This Interface)
                        </div>
                        <p><strong>Purpose:</strong> Configuration and preparation BEFORE deployment</p>
                        <p><strong>Location:</strong> Your local machine or development server</p>
                        <p><strong>Contains:</strong> Web admin interface, JSON config files, page layouts, PHP deployment scripts</p>
                    </div>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-info-circle"></i>
                            Repository 2: WebsiteBuild (Deployment Automation)
                        </div>
                        <p><strong>Purpose:</strong> WordPress setup ON the Kinsta server</p>
                        <p><strong>Runs On:</strong> Kinsta server via GitHub Actions</p>
                        <p><strong>Contains:</strong> Bash scripts (template.sh, forms.sh, map.sh, security.sh, cache-clear.sh) that configure WordPress using WP-CLI</p>
                    </div>
                </section>

                <!-- Complete Deployment Flow -->
                <section class="content-section">
                    <h2><i class="fas fa-timeline"></i> Complete Deployment Flow</h2>

                    <ol class="step-list">
                        <li>
                            <strong>Configure in Web Interface (This System)</strong>
                            <p>Use the web admin to configure Kinsta settings, design page layouts, create forms, setup maps, and configure security. All changes are saved as JSON files in /config/ and /pages/ directories.</p>
                        </li>

                        <li>
                            <strong>Click "Deploy Website"</strong>
                            <p>Triggers background-deploy.php which executes deployment scripts in sequence.</p>
                        </li>

                        <li>
                            <strong>Site Creation (scripts/site.sh)</strong>
                            <p>Creates WordPress site on Kinsta platform using Kinsta API with configured settings (region, PHP version, WP language, etc.).</p>
                        </li>

                        <li>
                            <strong>Upload Configurations (scripts/deploy.sh)</strong>
                            <p>Uploads all JSON files (/config/*.json, /pages/**/*.json) to /tmp/ on Kinsta server via SSH using rsync.</p>
                        </li>

                        <li>
                            <strong>Trigger GitHub Actions</strong>
                            <p>deploy.sh triggers workflow dispatch in WebsiteBuild repository, passing branch and force_deploy parameters.</p>
                        </li>

                        <li>
                            <strong>GitHub Actions Runs (WebsiteBuild/.github/workflows/deploy.yml)</strong>
                            <p>Clones WebsiteBuild repository, uploads themes and plugins to Kinsta, then executes bash scripts in order.</p>
                        </li>

                        <li>
                            <strong>Template Setup (template.sh)</strong>
                            <p>Reads /tmp/config.json and /tmp/theme-config.json. Activates selected theme, configures logo/API keys, processes uploaded images, copies custom layouts to theme directory, imports SiteOrigin layouts, creates demo pages, and sets up menus.</p>
                        </li>

                        <li>
                            <strong>Forms Configuration (forms.sh)</strong>
                            <p>Installs and activates Forminator plugin. Reads form JSON files from /tmp/forms/, imports forms using PHP importer, places forms on configured pages, and configures reCAPTCHA if credentials provided.</p>
                        </li>

                        <li>
                            <strong>Map Setup (map.sh)</strong>
                            <p>Installs WP Go Maps plugin. Reads map configuration, creates map with markers, places map shortcode on configured page (typically contact page).</p>
                        </li>

                        <li>
                            <strong>Security Hardening (security.sh)</strong>
                            <p>Configures Wordfence (malware scanning, brute force protection, geo-blocking). Creates secure admin user, removes default admin. Configures 2FA if enabled. Sets up IP whitelisting if configured.</p>
                        </li>

                        <li>
                            <strong>Cache Clearing (cache-clear.sh)</strong>
                            <p>Clears WordPress caches, transients, menu caches, and flushes rewrite rules to ensure all changes are visible.</p>
                        </li>

                        <li>
                            <strong>Site is Live!</strong>
                            <p>Your WordPress site is now live at your-domain.com. Access WordPress admin at your-domain.com/wp-admin using configured credentials.</p>
                        </li>
                    </ol>
                </section>

                <!-- Timeline -->
                <section class="content-section">
                    <h2><i class="fas fa-clock"></i> Deployment Timeline</h2>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-clock"></i>
                            Typical Deployment Duration
                        </div>
                        <p><strong>Total Time:</strong> 10-15 minutes</p>
                        <ul>
                            <li><strong>Site Creation:</strong> 3-5 minutes</li>
                            <li><strong>Configuration Upload:</strong> 1-2 minutes</li>
                            <li><strong>GitHub Actions:</strong> 5-8 minutes (theme activation, forms, maps, security)</li>
                        </ul>
                        <p>You can monitor progress in real-time via the Deployment Progress section.</p>
                    </div>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="introduction.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Introduction</span>
                    </a>
                    <a href="quick-start.php" class="btn-nav next">
                        <span>Quick Start Guide</span>
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
