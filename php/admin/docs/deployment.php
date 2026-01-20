<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deployment Process - Frontline Framework</title>

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
                        <h1>Deployment Process</h1>
                        <p>Understanding the deployment workflow</p>
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
                    <span>Deployment</span>
                </nav>

                <h1 class="page-title">Deployment Process</h1>
                <p class="page-subtitle">Initiate and monitor your site creation and configuration.</p>

                <!-- Overview -->
                <section class="content-section">
                    <h2><i class="fas fa-cloud-upload-alt"></i> Overview</h2>

                    <p>The Deployment page is where you initiate the site creation and configuration upload process.</p>
                </section>

                <!-- Starting Deployment -->
                <section class="content-section">
                    <h2><i class="fas fa-play-circle"></i> Starting Deployment</h2>

                    <h3>Site Title</h3>
                    <p>The name of your website. Must be unique in your Kinsta account. If a site with this name already exists, you'll see a warning with an option to delete the existing site first.</p>

                    <h3>Theme Selection</h3>
                    <p>Choose from 11 professionally designed themes. Each theme includes pre-designed layouts for Home, About, Contact, Issues, Get Involved, Endorsements, and News pages.</p>

                    <div class="card">
                        <h4>Available Themes</h4>
                        <ul>
                            <li><strong>BurBank</strong> - Modern political campaign theme</li>
                            <li><strong>Candidate</strong> - Professional candidate presentation</li>
                            <li><strong>Celeste</strong> - Clean and elegant design</li>
                            <li><strong>DoLife</strong> - Community-focused layout</li>
                            <li><strong>Everlead</strong> - Leadership-oriented theme</li>
                            <li><strong>LifeGuide</strong> - Issue-focused presentation</li>
                            <li><strong>Political</strong> - Traditional political design</li>
                            <li><strong>R.Cole</strong> - Bold and impactful theme</li>
                            <li><strong>Reform</strong> - Change-oriented design</li>
                            <li><strong>Speaker</strong> - Public speaking focused</li>
                            <li><strong>Tudor</strong> - Classic and trustworthy</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Before You Deploy
                        </div>
                        <p>Make sure you've configured:</p>
                        <ul>
                            <li>✓ Kinsta API Token and Company ID</li>
                            <li>✓ Site Title and Admin Credentials</li>
                            <li>✓ Selected a theme</li>
                            <li>✓ Customized page layouts (if desired)</li>
                            <li>✓ Configured forms and maps (if needed)</li>
                        </ul>
                    </div>
                </section>

                <!-- Deployment Stages -->
                <section class="content-section">
                    <h2><i class="fas fa-tasks"></i> Deployment Stages</h2>

                    <p>Monitor your deployment through 4 distinct stages:</p>

                    <ol class="step-list">
                        <li>
                            <strong>Setup Kinsta (3-5 minutes)</strong>
                            <p>Creates WordPress site on Kinsta platform using Kinsta API. Configures PHP version, region, WordPress language, and initial settings.</p>
                        </li>

                        <li>
                            <strong>Credentials (30-60 seconds)</strong>
                            <p>Retrieves SSH credentials and site access information from Kinsta. Waits for site to be fully provisioned and ready for configuration.</p>
                        </li>

                        <li>
                            <strong>Deploy (1-2 minutes)</strong>
                            <p>Uploads all JSON configuration files (/config/*.json, /pages/**/*.json, /uploads/images/*) to /tmp/ directory on Kinsta server via SSH. Triggers GitHub Actions workflow.</p>
                        </li>

                        <li>
                            <strong>GitHub Actions (5-8 minutes)</strong>
                            <p>Executes automated WordPress configuration: theme activation, page creation, form setup, map configuration, security hardening, cache clearing.</p>
                        </li>
                    </ol>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-chart-line"></i>
                            Progress Monitoring
                        </div>
                        <p>Watch the deployment progress bar and status messages in real-time. Each stage will display detailed status information and any errors that occur.</p>
                    </div>
                </section>

                <!-- Reset System -->
                <section class="content-section">
                    <h2><i class="fas fa-redo"></i> Reset System</h2>

                    <p>The "Reset System" button clears local deployment status files. Use this if:</p>

                    <ul>
                        <li>Deployment failed and you want to start over</li>
                        <li>Deployment status is stuck</li>
                        <li>You want to deploy a different site</li>
                    </ul>

                    <div class="alert alert-danger">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-circle"></i>
                            Warning
                        </div>
                        <p>Reset System only clears local status. It does NOT delete your site from Kinsta. To remove a site from Kinsta, use the Kinsta dashboard.</p>
                    </div>
                </section>

                <!-- Troubleshooting -->
                <section class="content-section">
                    <h2><i class="fas fa-wrench"></i> Common Deployment Issues</h2>

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

                    <p><a href="troubleshooting.php" class="btn-primary">View Full Troubleshooting Guide →</a></p>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="quick-start.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Quick Start Guide</span>
                    </a>
                    <a href="config-git.php" class="btn-nav next">
                        <span>Git Configuration</span>
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
