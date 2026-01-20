<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frontline Framework - Documentation</title>

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
                        <h1>Documentation Hub</h1>
                        <p>Complete guide to Frontline Framework Interface</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="/php/admin/documentation_old.php" class="btn-view-switch">
                        <i class="fas fa-file-alt"></i>
                        Single-Page View
                    </a>
                    <a href="/php/web-admin.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                        Back to Admin
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="docs-main">
            <div class="welcome-banner">
                <div class="banner-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="banner-content">
                    <h2>Welcome to the Documentation</h2>
                    <p>This is a <strong>pre-deployment configuration interface</strong> - not a live site editor. Configure your WordPress site here, then deploy to Kinsta hosting.</p>
                </div>
            </div>

            <!-- Getting Started -->
            <section class="docs-section">
                <h2 class="section-title">
                    <i class="fas fa-play-circle"></i>
                    Getting Started
                </h2>
                <div class="card-grid">
                    <a href="introduction.php" class="doc-card">
                        <div class="card-icon blue">
                            <i class="fas fa-home"></i>
                        </div>
                        <h3>Introduction</h3>
                        <p>What is Frontline Framework and how does it work?</p>
                        <span class="card-badge">Start Here</span>
                    </a>

                    <a href="quick-start.php" class="doc-card">
                        <div class="card-icon green">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h3>Quick Start Guide</h3>
                        <p>Deploy your first website in 6 simple steps</p>
                        <span class="card-badge">5 min read</span>
                    </a>

                    <a href="deployment.php" class="doc-card">
                        <div class="card-icon purple">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h3>Deployment Process</h3>
                        <p>Understanding the 4-stage deployment workflow</p>
                        <span class="card-badge">Essential</span>
                    </a>
                </div>
            </section>

            <!-- Configuration -->
            <section class="docs-section">
                <h2 class="section-title">
                    <i class="fas fa-cogs"></i>
                    Configuration Guides
                </h2>
                <div class="card-grid">
                    <a href="config-git.php" class="doc-card">
                        <div class="card-icon orange">
                            <i class="fab fa-github"></i>
                        </div>
                        <h3>Git Configuration</h3>
                        <p>GitHub repository and deployment settings</p>
                    </a>

                    <a href="config-kinsta.php" class="doc-card">
                        <div class="card-icon blue">
                            <i class="fas fa-server"></i>
                        </div>
                        <h3>Kinsta Settings</h3>
                        <p>API credentials, WordPress config, server options</p>
                    </a>

                    <a href="config-security.php" class="doc-card">
                        <div class="card-icon red">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Security Configuration</h3>
                        <p>Wordfence, geo-blocking, IP whitelist, 2FA</p>
                    </a>

                    <a href="config-integrations.php" class="doc-card">
                        <div class="card-icon teal">
                            <i class="fas fa-puzzle-piece"></i>
                        </div>
                        <h3>Integrations</h3>
                        <p>Forms, maps, analytics, tracking pixels</p>
                    </a>

                    <a href="config-navigation.php" class="doc-card">
                        <div class="card-icon indigo">
                            <i class="fas fa-bars"></i>
                        </div>
                        <h3>Navigation & Menus</h3>
                        <p>Configure site navigation and menu structure</p>
                    </a>

                    <a href="config-plugins.php" class="doc-card">
                        <div class="card-icon pink">
                            <i class="fas fa-plug"></i>
                        </div>
                        <h3>Plugins Configuration</h3>
                        <p>Manage WordPress plugins and their settings</p>
                    </a>

                    <a href="config-policies.php" class="doc-card">
                        <div class="card-icon gray">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <h3>Policies & Legal</h3>
                        <p>Privacy policy, terms of service, disclaimers</p>
                    </a>

                    <a href="config-theme.php" class="doc-card">
                        <div class="card-icon purple">
                            <i class="fas fa-palette"></i>
                        </div>
                        <h3>Theme Configuration</h3>
                        <p>Choose themes, upload logos, customize branding (Available in both Deployment tab and Page Editor)</p>
                    </a>
                </div>
            </section>

            <!-- Content Management -->
            <section class="docs-section">
                <h2 class="section-title">
                    <i class="fas fa-edit"></i>
                    Content Preparation
                </h2>
                <div class="card-grid">
                    <a href="page-editor.php" class="doc-card">
                        <div class="card-icon blue">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h3>Page Layout Editor</h3>
                        <p>Pre-deployment page design (Optional - Use WordPress admin for major customizations post-deployment)</p>
                        <span class="card-badge">Optional</span>
                    </a>

                    <a href="content-manager.php" class="doc-card">
                        <div class="card-icon green">
                            <i class="fas fa-list-alt"></i>
                        </div>
                        <h3>Content Preparation</h3>
                        <p>Prepare demo posts, news, testimonials, issues</p>
                    </a>

                    <a href="forms-manager.php" class="doc-card">
                        <div class="card-icon orange">
                            <i class="fas fa-wpforms"></i>
                        </div>
                        <h3>Forms Configuration</h3>
                        <p>Create contact, volunteer, and upload forms</p>
                    </a>
                </div>
            </section>

            <!-- Post-Deployment -->
            <section class="docs-section">
                <h2 class="section-title">
                    <i class="fas fa-check-circle"></i>
                    After Deployment
                </h2>
                <div class="card-grid">
                    <a href="post-deployment.php" class="doc-card">
                        <div class="card-icon green">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <h3>Next Steps</h3>
                        <p>What to do after your site is deployed</p>
                    </a>

                    <a href="wordpress-admin.php" class="doc-card">
                        <div class="card-icon blue">
                            <i class="fab fa-wordpress"></i>
                        </div>
                        <h3>WordPress Admin Guide</h3>
                        <p>Managing your live site through wp-admin</p>
                    </a>

                    <a href="troubleshooting.php" class="doc-card">
                        <div class="card-icon red">
                            <i class="fas fa-wrench"></i>
                        </div>
                        <h3>Troubleshooting</h3>
                        <p>Common issues and their solutions</p>
                    </a>
                </div>
            </section>

            <!-- Technical Reference -->
            <section class="docs-section tech-section">
                <h2 class="section-title">
                    <i class="fas fa-code"></i>
                    Technical Reference
                </h2>
                <p class="section-desc">For developers and advanced users</p>
                <div class="card-grid">
                    <a href="architecture.php" class="doc-card">
                        <div class="card-icon gray">
                            <i class="fas fa-diagram-project"></i>
                        </div>
                        <h3>System Architecture</h3>
                        <p>Two-repository design and deployment flow</p>
                        <span class="card-badge tech">Advanced</span>
                    </a>

                    <a href="deployment-flow.php" class="doc-card">
                        <div class="card-icon gray">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <h3>Deployment Workflow</h3>
                        <p>12-step technical deployment process</p>
                        <span class="card-badge tech">Advanced</span>
                    </a>

                    <a href="scripts-reference.php" class="doc-card">
                        <div class="card-icon gray">
                            <i class="fas fa-terminal"></i>
                        </div>
                        <h3>Scripts Reference</h3>
                        <p>Bash scripts documentation and API</p>
                        <span class="card-badge tech">Advanced</span>
                    </a>

                    <a href="json-format.php" class="doc-card">
                        <div class="card-icon gray">
                            <i class="fas fa-file-code"></i>
                        </div>
                        <h3>JSON Format Guide</h3>
                        <p>Configuration file structures and schemas</p>
                        <span class="card-badge tech">Advanced</span>
                    </a>
                </div>
            </section>

            <!-- FAQ -->
            <section class="docs-section">
                <h2 class="section-title">
                    <i class="fas fa-question-circle"></i>
                    Help & Support
                </h2>
                <div class="card-grid">
                    <a href="faq.php" class="doc-card">
                        <div class="card-icon blue">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>FAQ</h3>
                        <p>Frequently asked questions and answers</p>
                    </a>

                    <a href="glossary.php" class="doc-card">
                        <div class="card-icon purple">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3>Glossary</h3>
                        <p>Terms and definitions explained</p>
                    </a>
                </div>
            </section>

        </main>

        <!-- Footer -->
        <footer class="docs-footer">
            <p>&copy; <?php echo date('Y'); ?> Frontline Framework. Documentation for version 2.0</p>
        </footer>
    </div>
</body>
</html>
