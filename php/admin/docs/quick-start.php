<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Start Guide - Frontline Framework</title>

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
                        <h1>Quick Start Guide</h1>
                        <p>Deploy your first website in 6 simple steps</p>
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
                    <span>Quick Start</span>
                </nav>

                <h1 class="page-title">Quick Start Guide</h1>
                <p class="page-subtitle">Follow these 6 steps to deploy your first WordPress website.</p>

                <!-- Steps -->
                <section class="content-section">
                    <h2><i class="fas fa-rocket"></i> 6-Step Deployment Process</h2>

                    <ol class="step-list">
                        <li>
                            <strong>Configure Kinsta Credentials</strong>
                            <p>Go to Configuration → Kinsta Settings. Enter your Kinsta API Token and Company ID (get these from your Kinsta dashboard → API keys section).</p>
                            <a href="config-kinsta.php" class="btn-secondary">Kinsta Settings →</a>
                        </li>

                        <li>
                            <strong>Choose Site Details</strong>
                            <p>Enter Site Title, Display Name, Admin Email, Admin Username, and Admin Password. Choose your preferred region and WordPress language.</p>
                            <div class="alert alert-info">
                                <p><strong>Tip:</strong> Use the password generator for a secure admin password!</p>
                            </div>
                        </li>

                        <li>
                            <strong>Select a Theme</strong>
                            <p>Go to Deployment page. Choose from 11 available themes (BurBank, Candidate, Celeste, DoLife, Everlead, LifeGuide, Political, R.Cole, Reform, Speaker, Tudor).</p>
                            <a href="deployment.php" class="btn-secondary">View Deployment Guide →</a>
                        </li>

                        <li>
                            <strong>Customize Page Layouts (Optional)</strong>
                            <p>Go to Page Editor to customize page layouts using the visual editor. Changes are saved as JSON files for deployment.</p>
                            <div class="alert alert-warning">
                                <p><strong>Note:</strong> This step is OPTIONAL. You can skip it and edit pages in WordPress admin after deployment.</p>
                            </div>
                            <a href="page-editor.php" class="btn-secondary">Page Editor Guide →</a>
                        </li>

                        <li>
                            <strong>Click "Deploy Website"</strong>
                            <p>Return to Deployment page and click "Deploy Website" button. Monitor progress through 4 stages: Setup Kinsta → Credentials → Deploy → GitHub Actions.</p>
                            <div class="alert alert-info">
                                <p><strong>Expected Time:</strong> 10-15 minutes total</p>
                            </div>
                        </li>

                        <li>
                            <strong>Access WordPress Admin</strong>
                            <p>Once deployment completes (10-15 minutes), visit your-domain.com/wp-admin and login with configured admin credentials.</p>
                            <a href="wordpress-admin.php" class="btn-secondary">WordPress Admin Guide →</a>
                        </li>
                    </ol>
                </section>

                <!-- Pro Tip -->
                <section class="content-section">
                    <h2><i class="fas fa-lightbulb"></i> Pro Tips</h2>

                    <div class="alert alert-success">
                        <div class="alert-title">
                            <i class="fas fa-check-circle"></i>
                            Configuration Best Practice
                        </div>
                        <p>Configure everything you want BEFORE clicking deploy. After deployment, you'll need to make changes through WordPress admin or redeploy (which will overwrite manual WordPress changes).</p>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-list-check"></i> Pre-Deployment Checklist</h3>
                        <ul>
                            <li>✓ Kinsta API Token and Company ID configured</li>
                            <li>✓ Site Title and Admin Credentials set</li>
                            <li>✓ Theme selected</li>
                            <li>✓ Page layouts customized (if desired)</li>
                            <li>✓ Forms configured (if needed)</li>
                            <li>✓ Maps configured (if needed)</li>
                            <li>✓ Security settings reviewed</li>
                        </ul>
                    </div>
                </section>

                <!-- What's Next -->
                <section class="content-section">
                    <h2><i class="fas fa-forward"></i> What's Next?</h2>

                    <div class="feature-grid">
                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-sliders-h"></i>
                            </div>
                            <h3>Explore Configuration</h3>
                            <p>Learn about all available configuration options for your site.</p>
                            <a href="index.php#configuration" class="btn-primary">View Options →</a>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Security Settings</h3>
                            <p>Configure geo-blocking, 2FA, and other security features.</p>
                            <a href="config-security.php" class="btn-primary">Security Guide →</a>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <h3>Troubleshooting</h3>
                            <p>Common issues and how to resolve them.</p>
                            <a href="troubleshooting.php" class="btn-primary">Get Help →</a>
                        </div>
                    </div>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="architecture.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>System Architecture</span>
                    </a>
                    <a href="deployment.php" class="btn-nav next">
                        <span>Deployment Process</span>
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
