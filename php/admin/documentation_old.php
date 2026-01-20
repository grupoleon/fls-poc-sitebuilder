<?php
    require_once __DIR__ . '/../bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Frontline Framework Interface - User Guide</title>

        <link rel="icon" href="/php/admin/assets/img/favicon.ico">
        <link rel="stylesheet" href="//fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

        <style>
        :root {
            --primary-color: #14b8a6;
            --primary-dark: #0d9488;
            --primary-light: #2dd4bf;
            --secondary-color: #10b981;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --text-dark: #1f2937;
            --text-medium: #4b5563;
            --text-light: #6b7280;
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-tertiary: #f3f4f6;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-dark);
            background-color: var(--bg-secondary);
            line-height: 1.6;
        }

        .doc-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .doc-sidebar {
            width: 300px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
        }

        .doc-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .doc-sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .doc-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .sidebar-header {
            padding: 0 2rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }

        .sidebar-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .sidebar-header p {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .sidebar-nav {
            padding: 0 1rem;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 600;
            opacity: 0.7;
            padding: 0.5rem 1rem;
            margin-bottom: 0.5rem;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(4px);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
        }

        .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
            opacity: 0.8;
        }

        .nav-subitem {
            margin-left: 2rem;
        }

        /* Main Content */
        .doc-main {
            flex: 1;
            margin-left: 300px;
            padding: 3rem 4rem;
            max-width: 1400px;
        }

        .doc-section {
            background: var(--bg-primary);
            border-radius: 1rem;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            scroll-margin-top: 2rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--bg-tertiary);
        }

        .section-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            color: white;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
            box-shadow: var(--shadow-sm);
        }

        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .subsection {
            margin-bottom: 2rem;
        }

        .subsection:last-child {
            margin-bottom: 0;
        }

        .subsection-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .subsection-title i {
            color: var(--primary-color);
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .content-text {
            color: var(--text-medium);
            line-height: 1.8;
            margin-bottom: 1rem;
        }

        /* Lists */
        .step-list {
            list-style: none;
            counter-reset: step-counter;
        }

        .step-item {
            counter-increment: step-counter;
            margin-bottom: 1.5rem;
            padding-left: 3rem;
            position: relative;
        }

        .step-item::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
        }

        .bullet-list {
            list-style: none;
            padding-left: 0;
        }

        .bullet-item {
            padding-left: 2rem;
            margin-bottom: 0.75rem;
            position: relative;
            color: var(--text-medium);
        }

        .bullet-item::before {
            content: '→';
            position: absolute;
            left: 0;
            color: var(--primary-color);
            font-weight: 700;
        }

        /* Info Boxes */
        .info-box {
            padding: 1.25rem;
            border-radius: 0.75rem;
            margin: 1.5rem 0;
            display: flex;
            align-items: flex-start;
        }

        .info-box i {
            font-size: 1.25rem;
            margin-right: 1rem;
            margin-top: 0.125rem;
        }

        .info-box-content {
            flex: 1;
        }

        .info-box-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .info-box.tip {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            color: #1e40af;
        }

        .info-box.warning {
            background: #fef3c7;
            border-left: 4px solid var(--warning-color);
            color: #92400e;
        }

        .info-box.success {
            background: #d1fae5;
            border-left: 4px solid var(--success-color);
            color: #065f46;
        }

        .info-box.danger {
            background: #fee2e2;
            border-left: 4px solid var(--danger-color);
            color: #991b1b;
        }

        /* Feature Grid */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .feature-card {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            color: white;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .feature-title {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .feature-desc {
            font-size: 0.875rem;
            color: var(--text-medium);
            line-height: 1.6;
        }

        /* Back to Admin Button */
        .back-button {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-weight: 600;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            z-index: 999;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
        }

        /* View Switcher Button */
        .view-switch-button {
            position: fixed;
            bottom: 6rem;
            right: 2rem;
            background: white;
            color: var(--primary-color);
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-weight: 600;
            border: 2px solid var(--primary-color);
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            z-index: 999;
        }

        .view-switch-button:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
        }

        .view-switch-button i,
        .back-button i {
            margin-right: 0.5rem;
        }

        .back-button i {
            margin-right: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .doc-sidebar {
                width: 250px;
            }

            .doc-main {
                margin-left: 250px;
                padding: 2rem;
            }
        }

        @media (max-width: 768px) {
            .doc-sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .doc-main {
                margin-left: 0;
                padding: 1.5rem;
            }

            .doc-title {
                font-size: 2rem;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }
        }

        html {
            scroll-behavior: smooth;
        }

        </style>
    </head>

    <body>
        <div class="doc-container">
            <!-- Sidebar Navigation -->
            <nav class="doc-sidebar">
                <div class="sidebar-header">
                    <h1><i class="fas fa-book"></i> User Guide</h1>
                    <p>Internal Website Builder Tool</p>
                </div>

                <div class="sidebar-nav">
                    <div class="nav-section">
                        <div class="nav-section-title">Getting Started</div>
                        <div class="nav-item">
                            <a href="#introduction" class="nav-link active">
                                <i class="fas fa-home"></i>
                                Introduction
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="#how-it-works" class="nav-link">
                                <i class="fas fa-cogs"></i>
                                How It Works
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="#quick-start" class="nav-link">
                                <i class="fas fa-rocket"></i>
                                Quick Start
                            </a>
                        </div>
                    </div>

                    <div class="nav-section">
                        <div class="nav-section-title">Configuration</div>
                        <div class="nav-item">
                            <a href="#deployment" class="nav-link">
                                <i class="fas fa-cloud-upload-alt"></i>
                                Deployment
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="#configuration" class="nav-link">
                                <i class="fas fa-sliders-h"></i>
                                Site Configuration
                            </a>
                        </div>
                        <div class="nav-item nav-subitem">
                            <a href="#config-git" class="nav-link">
                                <i class="fab fa-github"></i>
                                Git Configuration
                            </a>
                        </div>
                        <div class="nav-item nav-subitem">
                            <a href="#config-kinsta" class="nav-link">
                                <i class="fas fa-server"></i>
                                Kinsta Settings
                            </a>
                        </div>
                        <div class="nav-item nav-subitem">
                            <a href="#config-security" class="nav-link">
                                <i class="fas fa-shield-alt"></i>
                                Security
                            </a>
                        </div>
                        <div class="nav-item nav-subitem">
                            <a href="#config-integrations" class="nav-link">
                                <i class="fas fa-puzzle-piece"></i>
                                Integrations
                            </a>
                        </div>
                        <div class="nav-item nav-subitem">
                            <a href="#config-navigation" class="nav-link">
                                <i class="fas fa-bars"></i>
                                Navigation & Menus
                            </a>
                        </div>
                        <div class="nav-item nav-subitem">
                            <a href="#config-plugins" class="nav-link">
                                <i class="fas fa-plug"></i>
                                Plugins
                            </a>
                        </div>
                        <div class="nav-item nav-subitem">
                            <a href="#config-policies" class="nav-link">
                                <i class="fas fa-file-contract"></i>
                                Policies
                            </a>
                        </div>
                        <div class="nav-item nav-subitem">
                            <a href="#config-theme" class="nav-link">
                                <i class="fas fa-palette"></i>
                                Theme Settings
                            </a>
                        </div>

                        <div class="nav-item">
                            <a href="#page-editor" class="nav-link">
                                <i class="fas fa-edit"></i>
                                Page Layouts
                            </a>
                        </div>

                        <div class="nav-item">
                            <a href="#content-manager" class="nav-link">
                                <i class="fas fa-list-alt"></i>
                                Content Preparation
                            </a>
                        </div>

                        <div class="nav-item">
                            <a href="#forms" class="nav-link">
                                <i class="fas fa-file-lines"></i>
                                Forms Configuration
                            </a>
                        </div>
                    </div>

                    <div class="nav-section">
                        <div class="nav-section-title">After Deployment</div>
                        <div class="nav-item">
                            <a href="#post-deployment" class="nav-link">
                                <i class="fas fa-check-circle"></i>
                                Next Steps
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="#wordpress-admin" class="nav-link">
                                <i class="fab fa-wordpress"></i>
                                WordPress Admin
                            </a>
                        </div>
                    </div>

                    <div class="nav-section">
                        <div class="nav-section-title">Support</div>
                        <div class="nav-item">
                            <a href="#troubleshooting" class="nav-link">
                                <i class="fas fa-wrench"></i>
                                Troubleshooting
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="#faq" class="nav-link">
                                <i class="fas fa-question-circle"></i>
                                FAQ
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="doc-main">
                <!-- Introduction Section -->
                <section id="introduction" class="doc-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h2 class="section-title">Welcome to Frontline Framework Interface</h2>
                    </div>

                    <p class="content-text">
                        Hey there! 👋 Welcome to our internal website builder tool. This is how we create and deploy
                        WordPress websites for our organization. Don't worry if you're not technical – this guide will
                        walk you through everything step-by-step.
                    </p>

                    <p class="content-text">
                        <strong>What does this tool do?</strong> It's a configuration interface where you set up how you
                        want your website to look and work, BEFORE it goes live. Think of it like filling out a detailed
                        form about your website, then clicking a button to make it real on the server.
                    </p>

                    <div class="info-box warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="info-box-content">
                            <div class="info-box-title">Important - Please Read!</div>
                            <p>This is a <strong>pre-deployment configuration tool</strong>, not the actual live
                                website. Changes you make here won't appear on your live site until you click "Deploy
                                Website". After deployment, you'll use the regular WordPress admin panel (wp-admin) for
                                day-to-day content updates.</p>
                        </div>
                    </div>

                    <div class="info-box tip">
                        <i class="fas fa-lightbulb"></i>
                        <div class="info-box-content">
                            <div class="info-box-title">How It Works in Simple Terms</div>
                            <p><strong>Step 1:</strong> You fill in all the settings here (theme, pages, forms,
                                security, etc.)<br><br>
                                <strong>Step 2:</strong> Click "Deploy Website" and wait 5-10 minutes<br><br>
                                <strong>Step 3:</strong> Your WordPress website is automatically created on Kinsta
                                hosting<br><br>
                                <strong>Step 4:</strong> Go to your-website.com/wp-admin to manage the live site
                            </p>
                        </div>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-star"></i>
                            Key Features
                        </h3>

                        <div class="feature-grid">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-file-code"></i>
                                </div>
                                <div class="feature-title">JSON-Based Config</div>
                                <p class="feature-desc">All settings stored as JSON files for easy version control and
                                    deployment.</p>
                            </div>

                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-palette"></i>
                                </div>
                                <div class="feature-title">11 Premium Themes</div>
                                <p class="feature-desc">Choose from professionally designed themes with custom layouts.
                                </p>
                            </div>

                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div class="feature-title">SiteOrigin Page Builder</div>
                                <p class="feature-desc">Design page layouts with SiteOrigin Builder format for
                                    deployment.</p>
                            </div>

                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="feature-title">Advanced Security</div>
                                <p class="feature-desc">Configure geo-blocking, IP whitelisting, Wordfence, and 2FA.</p>
                            </div>

                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-wpforms"></i>
                                </div>
                                <div class="feature-title">Forminator Forms</div>
                                <p class="feature-desc">Create contact forms, volunteer forms, and document uploads.</p>
                            </div>

                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-map-marked-alt"></i>
                                </div>
                                <div class="feature-title">Google Maps</div>
                                <p class="feature-desc">Configure WP Go Maps with markers and custom placements.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- How It Works Section -->
                <section id="how-it-works" class="doc-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h2 class="section-title">How the System Works</h2>
                    </div>

                    <p class="content-text">
                        Frontline Framework uses a two-repository architecture to separate configuration from
                        deployment:
                    </p>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-diagram-project"></i>
                            Architecture Overview
                        </h3>

                        <div class="info-box success">
                            <i class="fas fa-info-circle"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Repository 1: fls-poc-sitebuilder (This Interface)</div>
                                <p><strong>Purpose:</strong> Configuration and preparation BEFORE deployment<br>
                                    <strong>Location:</strong> Your local machine or development server<br>
                                    <strong>Contains:</strong> Web admin interface, JSON config files, page layouts, PHP
                                    deployment scripts
                                </p>
                            </div>
                        </div>

                        <div class="info-box tip">
                            <i class="fas fa-info-circle"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Repository 2: WebsiteBuild (Deployment Automation)</div>
                                <p><strong>Purpose:</strong> WordPress setup ON the Kinsta server<br>
                                    <strong>Runs On:</strong> Kinsta server via GitHub Actions<br>
                                    <strong>Contains:</strong> Bash scripts (template.sh, forms.sh, map.sh, security.sh,
                                    cache-clear.sh) that configure WordPress using WP-CLI
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-timeline"></i>
                            Complete Deployment Flow
                        </h3>

                        <ol class="step-list">
                            <li class="step-item">
                                <strong>Configure in Web Interface (This System)</strong><br>
                                <span class="content-text">Use the web admin to configure Kinsta settings, design page
                                    layouts, create forms, setup maps, and configure security. All changes are saved as
                                    JSON files in /config/ and /pages/ directories.</span>
                            </li>
                            <li class="step-item">
                                <strong>Click "Deploy Website"</strong><br>
                                <span class="content-text">Triggers background-deploy.php which executes deployment
                                    scripts in sequence.</span>
                            </li>
                            <li class="step-item">
                                <strong>Site Creation (scripts/site.sh)</strong><br>
                                <span class="content-text">Creates WordPress site on Kinsta platform using Kinsta API
                                    with configured settings (region, PHP version, WP language, etc.).</span>
                            </li>
                            <li class="step-item">
                                <strong>Upload Configurations (scripts/deploy.sh)</strong><br>
                                <span class="content-text">Uploads all JSON files (/config/*.json, /pages/**/*.json) to
                                    /tmp/ on Kinsta server via SSH using rsync.</span>
                            </li>
                            <li class="step-item">
                                <strong>Trigger GitHub Actions</strong><br>
                                <span class="content-text">deploy.sh triggers workflow dispatch in WebsiteBuild
                                    repository, passing branch and force_deploy parameters.</span>
                            </li>
                            <li class="step-item">
                                <strong>GitHub Actions Runs (WebsiteBuild/.github/workflows/deploy.yml)</strong><br>
                                <span class="content-text">Clones WebsiteBuild repository, uploads themes and plugins to
                                    Kinsta, then executes bash scripts in order.</span>
                            </li>
                            <li class="step-item">
                                <strong>Template Setup (template.sh)</strong><br>
                                <span class="content-text">Reads /tmp/config.json and /tmp/theme-config.json. Activates
                                    selected theme, configures logo/API keys, processes uploaded images, copies custom
                                    layouts to theme directory, imports SiteOrigin layouts, creates demo pages, and sets
                                    up menus.</span>
                            </li>
                            <li class="step-item">
                                <strong>Forms Configuration (forms.sh)</strong><br>
                                <span class="content-text">Installs and activates Forminator plugin. Reads form JSON
                                    files from /tmp/forms/, imports forms using PHP importer, places forms on configured
                                    pages, and configures reCAPTCHA if credentials provided.</span>
                            </li>
                            <li class="step-item">
                                <strong>Map Setup (map.sh)</strong><br>
                                <span class="content-text">Installs WP Go Maps plugin. Reads map configuration, creates
                                    map with markers, places map shortcode on configured page (typically contact
                                    page).</span>
                            </li>
                            <li class="step-item">
                                <strong>Security Hardening (security.sh)</strong><br>
                                <span class="content-text">Configures Wordfence (malware scanning, brute force
                                    protection, geo-blocking). Creates secure admin user, removes default admin.
                                    Configures 2FA if enabled. Sets up IP whitelisting if configured.</span>
                            </li>
                            <li class="step-item">
                                <strong>Cache Clearing (cache-clear.sh)</strong><br>
                                <span class="content-text">Clears WordPress caches, transients, menu caches, and flushes
                                    rewrite rules to ensure all changes are visible.</span>
                            </li>
                            <li class="step-item">
                                <strong>Site is Live!</strong><br>
                                <span class="content-text">Your WordPress site is now live at your-domain.com. Access
                                    WordPress admin at your-domain.com/wp-admin using configured credentials.</span>
                            </li>
                        </ol>
                    </div>

                    <div class="info-box warning">
                        <i class="fas fa-clock"></i>
                        <div class="info-box-content">
                            <div class="info-box-title">Deployment Timeline</div>
                            <p><strong>Total Time:</strong> 10-15 minutes<br>
                                <strong>Site Creation:</strong> 3-5 minutes<br>
                                <strong>Configuration Upload:</strong> 1-2 minutes<br>
                                <strong>GitHub Actions:</strong> 5-8 minutes (theme activation, forms, maps,
                                security)<br><br>
                                You can monitor progress in real-time via the Deployment Progress section.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Quick Start Section -->
                <section id="quick-start" class="doc-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h2 class="section-title">Quick Start Guide</h2>
                    </div>

                    <p class="content-text">
                        Deploy your first website in 6 simple steps:
                    </p>

                    <ol class="step-list">
                        <li class="step-item">
                            <strong>Configure Kinsta Credentials</strong><br>
                            <span class="content-text">Go to Configuration → Kinsta Settings. Enter your Kinsta API
                                Token and Company ID (get these from your Kinsta dashboard → API keys section).</span>
                        </li>
                        <li class="step-item">
                            <strong>Choose Site Details</strong><br>
                            <span class="content-text">Enter Site Title, Display Name, Admin Email, Admin Username, and
                                Admin Password. Choose your preferred region and WordPress language.</span>
                        </li>
                        <li class="step-item">
                            <strong>Select a Theme</strong><br>
                            <span class="content-text">Go to Deployment page. Choose from 11 available themes (BurBank,
                                Candidate, Celeste, DoLife, Everlead, LifeGuide, Political, R.Cole, Reform, Speaker,
                                Tudor).</span>
                        </li>
                        <li class="step-item">
                            <strong>Customize Page Layouts (Optional)</strong><br>
                            <span class="content-text">Go to Page Editor to customize page layouts using the visual
                                editor. Changes are saved as JSON files for deployment.</span>
                        </li>
                        <li class="step-item">
                            <strong>Click "Deploy Website"</strong><br>
                            <span class="content-text">Return to Deployment page and click "Deploy Website" button.
                                Monitor progress through 4 stages: Setup Kinsta → Credentials → Deploy → GitHub
                                Actions.</span>
                        </li>
                        <li class="step-item">
                            <strong>Access WordPress Admin</strong><br>
                            <span class="content-text">Once deployment completes (10-15 minutes), visit
                                your-domain.com/wp-admin and login with configured admin credentials.</span>
                        </li>
                    </ol>

                    <div class="info-box tip">
                        <i class="fas fa-lightbulb"></i>
                        <div class="info-box-content">
                            <div class="info-box-title">Pro Tip</div>
                            <p>Configure everything you want BEFORE clicking deploy. After deployment, you'll need to
                                make changes through WordPress admin or redeploy (which will overwrite manual WordPress
                                changes).</p>
                        </div>
                    </div>
                </section>

                <!-- Deployment Section -->
                <section id="deployment" class="doc-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h2 class="section-title">Deployment Process</h2>
                    </div>

                    <p class="content-text">
                        The Deployment page is where you initiate the site creation and configuration upload process.
                    </p>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-play-circle"></i>
                            Starting Deployment
                        </h3>

                        <p class="content-text">
                            <strong>Site Title:</strong> The name of your website. Must be unique in your Kinsta
                            account. If a site with this name already exists, you'll see a warning with an option to
                            delete the existing site first.
                        </p>

                        <p class="content-text">
                            <strong>Theme Selection:</strong> Choose from 11 professionally designed themes. Each theme
                            includes pre-designed layouts for Home, About, Contact, Issues, Get Involved, Endorsements,
                            and News pages.
                        </p>

                        <div class="info-box warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Before You Deploy</div>
                                <p>Make sure you've configured:<br>
                                    ✓ Kinsta API Token and Company ID<br>
                                    ✓ Site Title and Admin Credentials<br>
                                    ✓ Selected a theme<br>
                                    ✓ Customized page layouts (if desired)<br>
                                    ✓ Configured forms and maps (if needed)</p>
                            </div>
                        </div>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-tasks"></i>
                            Deployment Stages
                        </h3>

                        <p class="content-text">
                            Monitor your deployment through 4 distinct stages:
                        </p>

                        <ol class="step-list">
                            <li class="step-item">
                                <strong>Setup Kinsta (3-5 minutes)</strong><br>
                                <span class="content-text">Creates WordPress site on Kinsta platform using Kinsta API.
                                    Configures PHP version, region, WordPress language, and initial settings.</span>
                            </li>
                            <li class="step-item">
                                <strong>Credentials (30-60 seconds)</strong><br>
                                <span class="content-text">Retrieves SSH credentials and site access information from
                                    Kinsta. Waits for site to be fully provisioned and ready for configuration.</span>
                            </li>
                            <li class="step-item">
                                <strong>Deploy (1-2 minutes)</strong><br>
                                <span class="content-text">Uploads all JSON configuration files (/config/*.json,
                                    /pages/**/*.json, /uploads/images/*) to /tmp/ directory on Kinsta server via SSH.
                                    Triggers GitHub Actions workflow.</span>
                            </li>
                            <li class="step-item">
                                <strong>GitHub Actions (5-8 minutes)</strong><br>
                                <span class="content-text">Executes automated WordPress configuration: theme activation,
                                    page creation, form setup, map configuration, security hardening, cache
                                    clearing.</span>
                            </li>
                        </ol>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-redo"></i>
                            Reset System
                        </h3>

                        <p class="content-text">
                            The "Reset System" button clears local deployment status files. Use this if:
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item">Deployment failed and you want to start over</li>
                            <li class="bullet-item">Deployment status is stuck</li>
                            <li class="bullet-item">You want to deploy a different site</li>
                        </ul>

                        <div class="info-box danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Warning</div>
                                <p>Reset System only clears local status. It does NOT delete your site from Kinsta. To
                                    remove a site from Kinsta, use the Kinsta dashboard</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Configuration Section -->
                <section id="configuration" class="doc-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                        <h2 class="section-title">Site Configuration</h2>
                    </div>

                    <p class="content-text">
                        The Configuration section contains all settings that will be applied during deployment. All
                        settings are saved as JSON files in /config/ directory.
                    </p>

                    <div class="subsection" id="config-git">
                        <h3 class="subsection-title">
                            <i class="fab fa-github"></i>
                            Git Configuration
                        </h3>

                        <p class="content-text">
                            Connect this tool to your GitHub repository for automated deployments. This is how your
                            configurations get uploaded and deployed to Kinsta.
                        </p>

                        <p class="content-text">
                            <strong>GitHub Personal Access Token:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item">Go to GitHub Settings → Developer Settings → Personal Access Tokens
                            </li>
                            <li class="bullet-item">Click "Generate new token (classic)"</li>
                            <li class="bullet-item">Select scopes: <code>repo</code>, <code>workflow</code>,
                                <code>read:org</code>
                            </li>
                            <li class="bullet-item">Copy the token and paste it in the "GitHub Personal Access Token"
                                field</li>
                            <li class="bullet-item">Click "Edit" button to unlock the field and save your token</li>
                        </ul>

                        <div class="info-box danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Keep Your Token Safe!</div>
                                <p>Treat this token like a password. Never share it or commit it to public repositories.
                                </p>
                            </div>
                        </div>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Repository Settings:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Organization</strong> - Your GitHub username or organization
                                name (dropdown auto-populates after adding token)</li>
                            <li class="bullet-item"><strong>Repository</strong> - Select the WebsiteBuild repository (or
                                your deployment repo)</li>
                            <li class="bullet-item"><strong>Branch</strong> - Usually "main" (this is where configs will
                                be pushed)</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Server Connection (SSH):</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Host</strong> - Server address (from Kinsta dashboard)</li>
                            <li class="bullet-item"><strong>Username</strong> - SSH username (from Kinsta)</li>
                            <li class="bullet-item"><strong>Port</strong> - SSH port number (Kinsta provides this)</li>
                            <li class="bullet-item"><strong>Server Path</strong> - Full path where files are uploaded
                            </li>
                        </ul>

                        <div class="info-box tip">
                            <i class="fas fa-lightbulb"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">First Time Setup</div>
                                <p>After adding your GitHub token, the Organization and Repository dropdowns will
                                    automatically populate. Just select your values and save!</p>
                            </div>
                        </div>
                    </div>

                    <div class="subsection" id="config-kinsta">
                        <h3 class="subsection-title">
                            <i class="fas fa-server"></i>
                            Kinsta Settings
                        </h3>

                        <p class="content-text">
                            These settings are required for site creation on Kinsta platform.
                        </p>

                        <p class="content-text">
                            <strong>API Credentials:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Kinsta API Token</strong> - Generate from Kinsta Dashboard →
                                API → Generate API Key</li>
                            <li class="bullet-item"><strong>Company ID</strong> - Found in your Kinsta account (click
                                your company name)</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>WordPress Configuration:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Site Title</strong> - Your website name</li>
                            <li class="bullet-item"><strong>Display Name</strong> - URL-friendly site identifier</li>
                            <li class="bullet-item"><strong>Admin Email</strong> - WordPress admin notifications email
                            </li>
                            <li class="bullet-item"><strong>Admin Username</strong> - WordPress admin login (avoid
                                "admin")</li>
                            <li class="bullet-item"><strong>Admin Password</strong> - Strong password (click Generate
                                for secure password)</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Server Configuration:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Region</strong> - Server location (US Central, Europe West,
                                Asia Southeast)</li>
                            <li class="bullet-item"><strong>WordPress Language</strong> - Site language (English US,
                                Spanish, French, etc.)</li>
                            <li class="bullet-item"><strong>Install Mode</strong> - New Installation (recommended) or
                                Existing Installation</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Optional Features:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Enable Multisite</strong> - WordPress multisite network</li>
                            <li class="bullet-item"><strong>Install WooCommerce</strong> - E-commerce functionality</li>
                            <li class="bullet-item"><strong>Install WordPress SEO</strong> - Yoast SEO plugin</li>
                        </ul>
                    </div>

                    <div class="subsection" id="config-security">
                        <h3 class="subsection-title">
                            <i class="fas fa-shield-alt"></i>
                            Security Configuration
                        </h3>

                        <p class="content-text">
                            Configure advanced security features that will be applied during deployment via security.sh
                            script.
                        </p>

                        <p class="content-text">
                            <strong>Wordfence Configuration:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Real-Time Scanning</strong> - Monitors file changes and
                                suspicious activity</li>
                            <li class="bullet-item"><strong>Brute Force Protection</strong> - Blocks IPs after failed
                                login attempts</li>
                            <li class="bullet-item"><strong>Malware Scanning</strong> - Scheduled daily scans for
                                malicious code</li>
                            <li class="bullet-item"><strong>Email Alerts</strong> - Immediate notifications for security
                                events</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Geo-Blocking:</strong>
                        </p>

                        <p class="content-text">
                            Restrict access to your website by country. Only visitors from allowed countries can access
                            the site.
                        </p>

                        <div class="info-box danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Critical Warning</div>
                                <p>Make sure YOUR country is in the allowed list! If you enable geo-blocking without
                                    including your location, you'll be locked out of your own site.</p>
                            </div>
                        </div>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>IP Whitelisting:</strong>
                        </p>

                        <p class="content-text">
                            Only allow specific IP addresses or ranges to access the site. Enter IPs in CIDR notation
                            (e.g., 192.168.1.0/24 or single IP 192.168.1.1).
                        </p>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Two-Factor Authentication (2FA):</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Enforce for Administrators</strong> - Require 2FA for admin
                                users</li>
                            <li class="bullet-item"><strong>Grace Period</strong> - Number of days before 2FA is
                                mandatory</li>
                            <li class="bullet-item"><strong>Supported Methods</strong> - Email codes, authenticator apps
                                (Google Authenticator, Authy)</li>
                        </ul>
                    </div>

                    <div class="subsection" id="config-integrations">
                        <h3 class="subsection-title">
                            <i class="fas fa-puzzle-piece"></i>
                            Integrations
                        </h3>

                        <p class="content-text">
                            Connect third-party services like Google Analytics, forms, maps, and social media to your
                            website.
                        </p>

                        <p class="content-text">
                            <strong>Analytics:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Google Analytics</strong> - Enter your tracking ID (format:
                                G-XXXXXXXXXX) to track website visitors</li>
                            <li class="bullet-item">Get your tracking ID from Google Analytics dashboard</li>
                            <li class="bullet-item">Toggle "Enable Analytics Integration" ON to activate</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Forms:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Auto-Find Form Placements</strong> - Automatically detects
                                form placeholders in pages and places forms accordingly</li>
                            <li class="bullet-item">When enabled, forms you create in Forms Manager will be
                                automatically inserted where you've added form placeholder tags</li>
                            <li class="bullet-item">If disabled, you'll need to manually configure form placements</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Social Media Links:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Facebook, Twitter, Instagram, LinkedIn</strong> - Add your
                                profile URLs</li>
                            <li class="bullet-item"><strong>Placement</strong> - Choose where icons appear (footer,
                                header, or specific page)</li>
                            <li class="bullet-item">Social icons will automatically display on your selected location
                            </li>
                        </ul>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Google Maps:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>API Key</strong> - Required for map functionality (get from
                                Google Cloud Console)</li>
                            <li class="bullet-item"><strong>Center Coordinates</strong> - Latitude/Longitude where map
                                centers (e.g., 38.8977, -77.0365)</li>
                            <li class="bullet-item"><strong>Zoom Level</strong> - 1 (world view) to 20 (street level)
                            </li>
                            <li class="bullet-item"><strong>Markers</strong> - Click on map preview to add location pins
                            </li>
                            <li class="bullet-item"><strong>Auto-Find Map Placements</strong> - Like forms,
                                automatically places maps where you've added map placeholders</li>
                        </ul>

                        <div class="info-box warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Google Maps API Security</div>
                                <p>In Google Cloud Console, restrict your Maps API key to your domain only. This
                                    prevents unauthorized usage and unexpected charges!</p>
                            </div>
                        </div>
                    </div>

                    <div class="subsection" id="config-navigation">
                        <h3 class="subsection-title">
                            <i class="fas fa-bars"></i>
                            Navigation & Menus
                        </h3>

                        <p class="content-text">
                            Create custom navigation menus that appear in your website header, footer, or other
                            locations.
                        </p>

                        <p class="content-text">
                            <strong>Menu Settings:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Enable Custom Navigation</strong> - Turn ON to use custom
                                menus</li>
                            <li class="bullet-item"><strong>Replace Existing Menus</strong> - Recommended ON to avoid
                                duplicate menus</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Adding Menu Items:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Label</strong> - Text shown in navigation (e.g., "Home",
                                "About Us", "Contact")</li>
                            <li class="bullet-item"><strong>URL</strong> - Where the link goes:
                                <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                                    <li>Internal pages: /about, /contact</li>
                                    <li>Homepage: /</li>
                                    <li>External links: https://example.com</li>
                                </ul>
                            </li>
                            <li class="bullet-item"><strong>Order</strong> - Number that determines position (lower
                                numbers appear first)</li>
                            <li class="bullet-item"><strong>Parent Item</strong> - Leave blank for top-level, or select
                                parent to create dropdown submenu</li>
                            <li class="bullet-item"><strong>Open in New Tab</strong> - Check for external links</li>
                        </ul>

                        <div class="info-box tip">
                            <i class="fas fa-lightbulb"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Creating Dropdown Menus</div>
                                <p><strong>Example:</strong> To create "About" dropdown with "Our Team" and "Our
                                    Mission" sub-items:</p>
                                <ol style="margin-top: 0.5rem; margin-left: 1rem;">
                                    <li>Create menu item: Label = "About", URL = /about, Order = 20, Parent = (none)
                                    </li>
                                    <li>Create menu item: Label = "Our Team", URL = /our-team, Order = 1, Parent =
                                        "About"</li>
                                    <li>Create menu item: Label = "Our Mission", URL = /our-mission, Order = 2, Parent =
                                        "About"</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="subsection" id="config-plugins">
                        <h3 class="subsection-title">
                            <i class="fas fa-plug"></i>
                            Plugins Configuration
                        </h3>

                        <p class="content-text">
                            Manage which WordPress plugins are installed or protected during deployment.
                        </p>

                        <p class="content-text">
                            <strong>Plugins to Keep:</strong>
                        </p>

                        <p class="content-text">
                            If you have custom plugins installed manually in WordPress that you don't want removed
                            during redeployment, add them to this list.
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item">Click "Add Plugin" button</li>
                            <li class="bullet-item">Enter the plugin folder name (e.g., <code>jetpack</code>,
                                <code>custom-donations</code>)
                            </li>
                            <li class="bullet-item">Find folder name in WordPress: Plugins → Installed Plugins (hover
                                over plugin to see folder in URL)</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Plugins to Install:</strong>
                        </p>

                        <p class="content-text">
                            Automatically install plugins from WordPress.org during deployment.
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item">Click "Add Plugin" button</li>
                            <li class="bullet-item"><strong>Plugin Slug</strong> - The plugin identifier from
                                WordPress.org (e.g., for wordpress.org/plugins/akismet/, slug is "akismet")</li>
                            <li class="bullet-item"><strong>Version</strong> - Leave blank for latest, or specify
                                version number</li>
                            <li class="bullet-item"><strong>Activate After Install</strong> - Toggle ON to auto-activate
                            </li>
                        </ul>

                        <div class="info-box tip">
                            <i class="fas fa-lightbulb"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Common Plugins</div>
                                <p><strong>SEO:</strong> wordpress-seo (Yoast), google-sitemap-generator</p>
                                <p><strong>Security:</strong> better-wp-security, sucuri-scanner</p>
                                <p><strong>Backup:</strong> updraftplus, duplicator</p>
                                <p><strong>Forms:</strong> contact-form-7, ninja-forms</p>
                            </div>
                        </div>
                    </div>

                    <div class="subsection" id="config-policies">
                        <h3 class="subsection-title">
                            <i class="fas fa-file-contract"></i>
                            Policies Configuration
                        </h3>

                        <p class="content-text">
                            Configure password requirements and legal policy pages.
                        </p>

                        <p class="content-text">
                            <strong>Password Policy:</strong>
                        </p>

                        <p class="content-text">
                            Set requirements for WordPress user passwords (admins, editors, contributors).
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Minimum Length</strong> - Recommended: 12-16 characters</li>
                            <li class="bullet-item"><strong>Require Uppercase Letters</strong> - At least one A-Z</li>
                            <li class="bullet-item"><strong>Require Lowercase Letters</strong> - At least one a-z</li>
                            <li class="bullet-item"><strong>Require Numbers</strong> - At least one 0-9</li>
                            <li class="bullet-item"><strong>Require Special Characters</strong> - At least one !@#$%^&*
                            </li>
                        </ul>

                        <div class="info-box success">
                            <i class="fas fa-shield-alt"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Recommended Strong Password Policy</div>
                                <p>✅ Minimum Length: 12 characters</p>
                                <p>✅ Require: Uppercase, Lowercase, Numbers, Special Characters</p>
                                <p><strong>Example valid password:</strong> MySecure#Pass2024</p>
                            </div>
                        </div>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Privacy Policy & Terms:</strong>
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Privacy Policy</strong> - Required if you collect any user
                                data (forms, analytics, cookies)</li>
                            <li class="bullet-item"><strong>Terms of Service</strong> - Rules for using your website
                            </li>
                            <li class="bullet-item"><strong>Cookie Policy</strong> - Explain what cookies you use</li>
                            <li class="bullet-item">You can upload policy documents or paste text directly</li>
                        </ul>

                        <div class="info-box tip">
                            <i class="fas fa-lightbulb"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Free Policy Generators</div>
                                <p>Use these websites to generate basic privacy policies:</p>
                                <ul style="margin-top: 0.5rem;">
                                    <li>TermsFeed Privacy Policy Generator</li>
                                    <li>FreePrivacyPolicy.com</li>
                                    <li>Iubenda Policy Generator</li>
                                </ul>
                                <p style="margin-top: 0.5rem;"><strong>Note:</strong> These are starting points. For
                                    legal websites, consider having a lawyer review them.</p>
                            </div>
                        </div>
                    </div>

                    <div class="subsection" id="config-theme">
                        <h3 class="subsection-title">
                            <i class="fas fa-palette"></i>
                            Theme Configuration
                        </h3>

                        <p class="content-text">
                            Select and customize your WordPress theme. Theme can be set from either the Deployment tab
                            OR the Page Editor tab.
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Active Theme</strong> - Selected theme from 11 available
                                options</li>
                            <li class="bullet-item"><strong>Logo Upload</strong> - Upload PNG logo (recommended:
                                300x100px, transparent background)</li>
                            <li class="bullet-item"><strong>Available Themes</strong> - BurBank, Candidate, Celeste,
                                DoLife, Everlead, LifeGuide, Political, R.Cole, Reform, Speaker, Tudor</li>
                        </ul>

                        <div class="info-box tip">
                            <i class="fas fa-info-circle"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Theme Switching</div>
                                <p>To switch themes after deployment, change the theme selection and redeploy. Note:
                                    Redeployment will overwrite any manual WordPress changes.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Page Editor Section -->
                <section id="page-editor" class="doc-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h2 class="section-title">Page Layout Configuration (Optional)</h2>
                    </div>

                    <p class="content-text">
                        The Page Editor is an <strong>OPTIONAL</strong> feature for pre-deployment page customization.
                        You can skip this entirely and edit pages in WordPress admin after deployment instead.
                    </p>

                    <div class="info-box warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="info-box-content">
                            <div class="info-box-title">Recommended Approach for Non-Technical Users</div>
                            <p><strong>Skip the Page Editor!</strong> For major content customizations and ongoing
                                edits, use the WordPress admin panel (wp-admin) AFTER deployment. It's much easier with
                                a visual editor. Only use this Page Editor if you need to customize initial demo content
                                before first deployment.</p>
                        </div>
                    </div>

                    <div class="info-box tip">
                        <i class="fas fa-lightbulb"></i>
                        <div class="info-box-content">
                            <div class="info-box-title">When to Use This</div>
                            <p><strong>Use Page Editor if:</strong> You want to customize demo content BEFORE deploying
                                the site</p>
                            <p><strong>Use WordPress Admin if:</strong> You want to edit pages AFTER deployment
                                (recommended for most users)</p>
                        </div>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-file-code"></i>
                            How It Works
                        </h3>

                        <ol class="step-list">
                            <li class="step-item">
                                <strong>Select Theme and Page</strong><br>
                                <span class="content-text">Choose your theme, then select which page to customize (Home,
                                    About, Contact, Issues, etc.)</span>
                            </li>
                            <li class="step-item">
                                <strong>Edit Layout JSON</strong><br>
                                <span class="content-text">Modify the SiteOrigin panels_data JSON structure. This
                                    defines widgets, grids, and content for the page.</span>
                            </li>
                            <li class="step-item">
                                <strong>Save Configuration</strong><br>
                                <span class="content-text">Saves to /pages/themes/[theme]/layouts/[page].json</span>
                            </li>
                            <li class="step-item">
                                <strong>Deploy</strong><br>
                                <span class="content-text">During deployment, deploy.sh uploads JSON files to
                                    /tmp/pages/themes/[theme]/layouts/ on server</span>
                            </li>
                            <li class="step-item">
                                <strong>Import by template.sh</strong><br>
                                <span class="content-text">template.sh copies layouts to active theme's demo-data
                                    directory, then WordPress imports them using theme's demo installation
                                    function</span>
                            </li>
                        </ol>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-code"></i>
                            SiteOrigin Format
                        </h3>

                        <p class="content-text">
                            Page layouts use SiteOrigin Page Builder JSON format:
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>widgets</strong> - Array of page widgets (hero sections,
                                text blocks, images, buttons)</li>
                            <li class="bullet-item"><strong>grids</strong> - Layout grid configuration (columns, rows)
                            </li>
                            <li class="bullet-item"><strong>grid_cells</strong> - Cell positions and widget assignments
                            </li>
                            <li class="bullet-item"><strong>panels_info</strong> - Widget metadata (class, position,
                                styling)</li>
                        </ul>

                        <div class="info-box tip">
                            <i class="fas fa-lightbulb"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Pro Tip</div>
                                <p>Use the visual editor in your browser to make changes, then copy the generated JSON.
                                    Or edit JSON directly if you're familiar with SiteOrigin Page Builder format.</p>
                            </div>
                        </div>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-layer-group"></i>
                            Available Pages
                        </h3>

                        <p class="content-text">
                            Each theme includes layouts for these pages:
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>home.json</strong> - Homepage layout with hero section,
                                intro, features</li>
                            <li class="bullet-item"><strong>about.json</strong> - About page with bio, mission, team
                            </li>
                            <li class="bullet-item"><strong>contact.json</strong> - Contact page with form placeholder
                            </li>
                            <li class="bullet-item"><strong>issues.json</strong> - Issues/platform page</li>
                            <li class="bullet-item"><strong>get-involved.json</strong> - Volunteer signup page</li>
                            <li class="bullet-item"><strong>endorsements.json</strong> - Endorsements display page</li>
                            <li class="bullet-item"><strong>news.json</strong> - News/blog listing page</li>
                        </ul>
                    </div>
                </section>

                <!-- Content Manager Section -->
                <section id="content-manager" class="doc-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-list-alt"></i>
                        </div>
                        <h2 class="section-title">Content Preparation</h2>
                    </div>

                    <p class="content-text">
                        The Content Manager prepares demo content (posts, news, testimonials, endorsements, issues) as
                        JSON files for deployment. Content is saved in /pages/cpt/ directory.
                    </p>

                    <div class="info-box warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="info-box-content">
                            <div class="info-box-title">Demo Content Purpose</div>
                            <p>This content is for initial site setup only. After deployment, manage content through
                                WordPress admin (wp-admin → Posts/Pages). Changes made here are only applied during
                                redeployment.</p>
                        </div>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-file-alt"></i>
                            Content Types
                        </h3>

                        <p class="content-text">
                            <strong>Blog Posts (posts.json):</strong> Standard WordPress blog posts with title, content,
                            excerpt, featured image, categories, tags.
                        </p>

                        <p class="content-text">
                            <strong>News Articles (news.json):</strong> Custom post type for news/press releases.
                            Includes date, headline, body content, link to full article.
                        </p>

                        <p class="content-text">
                            <strong>Testimonials (testimonials.json):</strong> Customer/supporter testimonials with
                            name, photo, quote, title/organization.
                        </p>

                        <p class="content-text">
                            <strong>Endorsements (endorsements.json):</strong> Political/organizational endorsements
                            with endorser name, title, photo, statement.
                        </p>

                        <p class="content-text">
                            <strong>Issues (issues.json):</strong> Platform issues/positions with title, description,
                            stance, supporting data.
                        </p>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-cogs"></i>
                            How Content is Imported
                        </h3>

                        <ol class="step-list">
                            <li class="step-item">
                                <strong>Configure Content</strong><br>
                                <span class="content-text">Use Content Manager to create/edit content. Saved to
                                    /pages/cpt/*.json</span>
                            </li>
                            <li class="step-item">
                                <strong>Upload During Deployment</strong><br>
                                <span class="content-text">deploy.sh uploads JSON files to /tmp/pages/cpt/ on Kinsta
                                    server</span>
                            </li>
                            <li class="step-item">
                                <strong>Import by template.sh</strong><br>
                                <span class="content-text">template.sh copies CPT files to active theme's demo-data
                                    directory using copy_custom_cpt_to_theme() function</span>
                            </li>
                            <li class="step-item">
                                <strong>WordPress Creates Posts</strong><br>
                                <span class="content-text">Theme's demo installation function reads JSON and creates
                                    WordPress posts using wp_insert_post()</span>
                            </li>
                        </ol>
                    </div>
                </section>

                <!-- Forms Section -->
                <section id="forms" class="doc-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-file-lines"></i>
                        </div>
                        <h2 class="section-title">Forms Configuration</h2>
                    </div>

                    <p class="content-text">
                        The Forms Manager configures Forminator forms that will be created during deployment. Form
                        configurations are saved in /pages/forms/ directory as JSON files.
                    </p>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-list-check"></i>
                            Available Forms
                        </h3>

                        <div class="feature-grid">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="feature-title">Contact Form</div>
                                <p class="feature-desc">Standard contact form with name, email, phone, message fields.
                                    Placed on contact page.</p>
                            </div>

                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-hand-holding-heart"></i>
                                </div>
                                <div class="feature-title">Volunteer Form</div>
                                <p class="feature-desc">Volunteer signup with name, email, phone, availability,
                                    interests, skills fields.</p>
                            </div>

                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-file-upload"></i>
                                </div>
                                <div class="feature-title">Document Upload</div>
                                <p class="feature-desc">File upload form with name, email, document category, file
                                    attachment fields.</p>
                            </div>
                        </div>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-cogs"></i>
                            Form Deployment Process
                        </h3>

                        <ol class="step-list">
                            <li class="step-item">
                                <strong>Configure Forms</strong><br>
                                <span class="content-text">Use Forms Manager to edit form fields. Saved to
                                    /pages/forms/*.json in Forminator export format</span>
                            </li>
                            <li class="step-item">
                                <strong>Upload During Deployment</strong><br>
                                <span class="content-text">deploy.sh uploads JSON files to /tmp/forms/ on Kinsta
                                    server</span>
                            </li>
                            <li class="step-item">
                                <strong>Install Forminator Plugin</strong><br>
                                <span class="content-text">forms.sh installs and activates Forminator plugin using
                                    ensure_plugin_installed()</span>
                            </li>
                            <li class="step-item">
                                <strong>Import Forms</strong><br>
                                <span class="content-text">forms.sh reads JSON files and imports using PHP
                                    form-import.php script via WP-CLI</span>
                            </li>
                            <li class="step-item">
                                <strong>Place Form Shortcodes</strong><br>
                                <span class="content-text">forms.sh searches for [contact-form], [volunteer-form],
                                    [document-upload-form] placeholders in page content and replaces with Forminator
                                    shortcodes</span>
                            </li>
                            <li class="step-item">
                                <strong>Configure reCAPTCHA (Optional)</strong><br>
                                <span class="content-text">If recaptcha_site_key and recaptcha_secret_key provided in
                                    config, forms.sh configures reCAPTCHA protection</span>
                            </li>
                        </ol>
                    </div>

                    <div class="info-box tip">
                        <i class="fas fa-lightbulb"></i>
                        <div class="info-box-content">
                            <div class="info-box-title">Form Placement</div>
                            <p>Forms are automatically placed on pages that contain form placeholders in their layout
                                JSON:<br>
                                • [contact-form] → Contact page<br>
                                • [volunteer-form] → Get Involved page<br>
                                • [document-upload-form] → Dedicated upload page<br><br>
                                If no placeholder found, forms are appended to the bottom of the configured page.</p>
                        </div>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-shield-alt"></i>
                            reCAPTCHA Configuration
                        </h3>

                        <p class="content-text">
                            To enable spam protection:
                        </p>

                        <ol class="step-list">
                            <li class="step-item">Get reCAPTCHA keys from Google reCAPTCHA admin console</li>
                            <li class="step-item">Add credentials to /config/config.json under
                                integrations.forms.recaptcha</li>
                            <li class="step-item">Deploy site - forms.sh will automatically configure reCAPTCHA</li>
                        </ol>
                    </div>
                </section>

                <!-- Post-Deployment Section -->
                <section id="post-deployment" class="doc-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2 class="section-title">After Deployment</h2>
                    </div>

                    <p class="content-text">
                        Once deployment completes successfully, your WordPress site is live on Kinsta hosting. Here's
                        what happens next:
                    </p>

                    <div class="info-box success">
                        <i class="fas fa-party-horn"></i>
                        <div class="info-box-content">
                            <div class="info-box-title">Your Site is Live!</div>
                            <p>Your WordPress website is now accessible at your Kinsta-assigned domain (or custom domain
                                if configured). All configurations, pages, forms, maps, and security settings have been
                                applied.</p>
                        </div>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-tasks"></i>
                            Next Steps
                        </h3>

                        <ol class="step-list">
                            <li class="step-item">
                                <strong>Access WordPress Admin</strong><br>
                                <span class="content-text">Go to your-domain.com/wp-admin and login with the admin
                                    credentials you configured</span>
                            </li>
                            <li class="step-item">
                                <strong>Verify Configuration</strong><br>
                                <span class="content-text">Check that pages, menus, forms, and maps are working
                                    correctly. Test contact forms and security features.</span>
                            </li>
                            <li class="step-item">
                                <strong>Add Real Content</strong><br>
                                <span class="content-text">Replace demo content with real posts, images, and text using
                                    WordPress admin interface</span>
                            </li>
                            <li class="step-item">
                                <strong>Configure Domain (Optional)</strong><br>
                                <span class="content-text">In Kinsta dashboard, add your custom domain and configure DNS
                                    settings</span>
                            </li>
                            <li class="step-item">
                                <strong>Install SSL Certificate</strong><br>
                                <span class="content-text">Kinsta automatically provides Let's Encrypt SSL for custom
                                    domains</span>
                            </li>
                            <li class="step-item">
                                <strong>Configure Backups</strong><br>
                                <span class="content-text">Kinsta provides automatic daily backups. Configure backup
                                    schedule in Kinsta dashboard.</span>
                            </li>
                        </ol>
                    </div>

                    <div class="info-box warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="info-box-content">
                            <div class="info-box-title">Important: Redeployment Overwrites Changes</div>
                            <p>If you make changes through WordPress admin, then redeploy from this interface, your
                                WordPress changes will be OVERWRITTEN by the configuration files. Only redeploy if you
                                want to reset the site to the configured state.</p>
                        </div>
                    </div>
                </section>

                <!-- WordPress Admin Section -->
                <section id="wordpress-admin" class="doc-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fab fa-wordpress"></i>
                        </div>
                        <h2 class="section-title">Managing Your Live Site</h2>
                    </div>

                    <p class="content-text">
                        After deployment, manage your live WordPress site through the standard WordPress admin interface
                        at your-domain.com/wp-admin.
                    </p>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-user-shield"></i>
                            Accessing WordPress Admin
                        </h3>

                        <p class="content-text">
                            <strong>Login URL:</strong> https://your-domain.com/wp-admin
                        </p>

                        <p class="content-text">
                            <strong>Credentials:</strong> Use the admin username and password you configured in Kinsta
                            Settings before deployment.
                        </p>

                        <div class="info-box tip">
                            <i class="fas fa-key"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">Forgot Your Password?</div>
                                <p>Use WordPress's "Lost your password?" link on the login page. Reset email will be
                                    sent to the admin email address you configured.</p>
                            </div>
                        </div>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-edit"></i>
                            Editing Content
                        </h3>

                        <p class="content-text">
                            In WordPress admin, you can:
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>Edit Pages:</strong> Pages → All Pages → Click page to edit
                                with SiteOrigin Page Builder</li>
                            <li class="bullet-item"><strong>Add/Edit Posts:</strong> Posts → All Posts or Add New</li>
                            <li class="bullet-item"><strong>Manage Media:</strong> Media → Library to upload/manage
                                images</li>
                            <li class="bullet-item"><strong>Customize Theme:</strong> Appearance → Customize for theme
                                settings, colors, logo</li>
                            <li class="bullet-item"><strong>Edit Menus:</strong> Appearance → Menus to modify navigation
                            </li>
                            <li class="bullet-item"><strong>Manage Forms:</strong> Forminator → Forms to edit form
                                fields and settings</li>
                            <li class="bullet-item"><strong>Configure Maps:</strong> WP Go Maps → Maps to edit map
                                markers and settings</li>
                            <li class="bullet-item"><strong>Security Settings:</strong> Wordfence → All Options for
                                security configuration</li>
                        </ul>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-plug"></i>
                            Installed Plugins
                        </h3>

                        <p class="content-text">
                            Your deployment automatically installs and configures these plugins:
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>SiteOrigin Panels</strong> - Page builder for layout
                                customization</li>
                            <li class="bullet-item"><strong>SiteOrigin Widgets Bundle</strong> - Additional widgets for
                                page builder</li>
                            <li class="bullet-item"><strong>Forminator</strong> - Form builder and management</li>
                            <li class="bullet-item"><strong>WP Go Maps</strong> - Google Maps integration</li>
                            <li class="bullet-item"><strong>Wordfence Security</strong> - Firewall, malware scanning,
                                brute force protection</li>
                            <li class="bullet-item"><strong>WP 2FA</strong> - Two-factor authentication (if enabled in
                                config)</li>
                            <li class="bullet-item"><strong>WPS Hide Login</strong> - Custom login URL for security</li>
                        </ul>
                    </div>

                    <div class="info-box danger">
                        <i class="fas fa-trash-alt"></i>
                        <div class="info-box-content">
                            <div class="info-box-title">Don't Deactivate Required Plugins</div>
                            <p>DO NOT deactivate SiteOrigin Panels, Forminator, WP Go Maps, or Wordfence unless you know
                                what you're doing. These plugins are essential for your site's functionality and
                                security.</p>
                        </div>
                    </div>
                </section>

                <!-- Troubleshooting Section -->
                <section id="troubleshooting" class="doc-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-wrench"></i>
                        </div>
                        <h2 class="section-title">Troubleshooting</h2>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-exclamation-circle"></i>
                            Common Issues
                        </h3>

                        <p class="content-text">
                            <strong>Deployment Stuck at "Setup Kinsta":</strong>
                        </p>
                        <ul class="bullet-list">
                            <li class="bullet-item">Check Kinsta API token is valid and not expired</li>
                            <li class="bullet-item">Verify Company ID is correct</li>
                            <li class="bullet-item">Ensure you have available site slots in your Kinsta plan</li>
                            <li class="bullet-item">Check Kinsta dashboard for site creation status</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1.5rem;">
                            <strong>Deployment Fails at "Deploy" Stage:</strong>
                        </p>
                        <ul class="bullet-list">
                            <li class="bullet-item">Check SSH connectivity in deployment logs</li>
                            <li class="bullet-item">Verify Kinsta SSH credentials are correct in git.json</li>
                            <li class="bullet-item">Ensure JSON config files are valid (no syntax errors)</li>
                            <li class="bullet-item">Check /logs/deployment/deployment.log for detailed errors</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1.5rem;">
                            <strong>GitHub Actions Fails:</strong>
                        </p>
                        <ul class="bullet-list">
                            <li class="bullet-item">Check GitHub Actions tab in WebsiteBuild repository for error
                                details</li>
                            <li class="bullet-item">Verify GitHub token has correct permissions</li>
                            <li class="bullet-item">Ensure theme-config.json was uploaded correctly</li>
                            <li class="bullet-item">Check if WebsiteBuild repository is accessible</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1.5rem;">
                            <strong>Can't Access WordPress Admin:</strong>
                        </p>
                        <ul class="bullet-list">
                            <li class="bullet-item">Wait 5-10 minutes after deployment for DNS propagation</li>
                            <li class="bullet-item">Check if IP whitelisting is blocking your IP</li>
                            <li class="bullet-item">Verify geo-blocking allows your country</li>
                            <li class="bullet-item">Try accessing via Kinsta's staging URL instead of custom domain</li>
                            <li class="bullet-item">Check admin username/password in /config/config.json</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1.5rem;">
                            <strong>Forms Not Appearing:</strong>
                        </p>
                        <ul class="bullet-list">
                            <li class="bullet-item">Check if Forminator plugin is active in WordPress admin</li>
                            <li class="bullet-item">Verify form JSON files exist in /pages/forms/</li>
                            <li class="bullet-item">Check deployment logs for form import errors</li>
                            <li class="bullet-item">Ensure page content contains form placeholders</li>
                        </ul>

                        <p class="content-text" style="margin-top: 1.5rem;">
                            <strong>Map Not Showing:</strong>
                        </p>
                        <ul class="bullet-list">
                            <li class="bullet-item">Verify Google Maps API key is configured correctly</li>
                            <li class="bullet-item">Check if WP Go Maps plugin is active</li>
                            <li class="bullet-item">Ensure map placement is configured in integrations.maps.placement
                            </li>
                            <li class="bullet-item">Check browser console for JavaScript errors</li>
                        </ul>
                    </div>

                    <div class="subsection">
                        <h3 class="subsection-title">
                            <i class="fas fa-file-alt"></i>
                            Checking Logs
                        </h3>

                        <p class="content-text">
                            Deployment logs provide detailed information about the deployment process:
                        </p>

                        <ul class="bullet-list">
                            <li class="bullet-item"><strong>/logs/deployment/deployment.log</strong> - Main deployment
                                log</li>
                            <li class="bullet-item"><strong>/logs/api/</strong> - Kinsta API request/response logs</li>
                            <li class="bullet-item"><strong>/tmp/deployment_status.json</strong> - Current deployment
                                status and timing</li>
                            <li class="bullet-item"><strong>GitHub Actions Logs</strong> - View in WebsiteBuild
                                repository → Actions tab</li>
                        </ul>

                        <div class="info-box tip">
                            <i class="fas fa-code"></i>
                            <div class="info-box-content">
                                <div class="info-box-title">SSH Debugging</div>
                                <p>To debug issues on the server, SSH into your Kinsta site and check:<br>
                                    • /tmp/*.json - Uploaded configuration files<br>
                                    • /tmp/pages/ - Page layouts and content<br>
                                    • WordPress debug.log at wp-content/debug.log</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- FAQ Section -->
                <section id="faq" class="doc-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h2 class="section-title">Frequently Asked Questions</h2>
                    </div>

                    <div class="subsection">
                        <p class="content-text">
                            <strong>Q: What is this interface exactly?</strong><br>
                            A: This is a pre-deployment configuration system. You configure your WordPress site BEFORE
                            deployment by creating JSON files. When you click "Deploy Website," configurations are
                            uploaded to Kinsta and WordPress is automatically configured via bash scripts.
                        </p>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Q: Can I edit my live site from this interface?</strong><br>
                            A: No. This interface only creates configuration files for deployment. To edit your live
                            site, use WordPress admin (wp-admin) after deployment.
                        </p>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Q: What happens if I redeploy after making WordPress changes?</strong><br>
                            A: Redeployment will OVERWRITE your WordPress changes with the configurations stored in JSON
                            files. Only redeploy if you want to reset the site.
                        </p>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Q: How long does deployment take?</strong><br>
                            A: 10-15 minutes total. Site creation (3-5 min) + configuration upload (1-2 min) + GitHub
                            Actions (5-8 min).
                        </p>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Q: Where are my configuration files stored?</strong><br>
                            A: Locally in /config/ (site settings) and /pages/ (layouts, content, forms). During
                            deployment, they're uploaded to /tmp/ on Kinsta server.
                        </p>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Q: Can I use this with an existing WordPress site?</strong><br>
                            A: This system is designed for NEW site deployment. It creates sites from scratch on Kinsta.
                            Don't use it on existing production sites.
                        </p>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Q: What if I need a feature not available in the interface?</strong><br>
                            A: After deployment, install additional WordPress plugins and configure them through
                            WordPress admin.
                        </p>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Q: How do I backup my site?</strong><br>
                            A: Kinsta provides automatic daily backups. Access them in Kinsta dashboard → Backups. You
                            can also use WordPress backup plugins.
                        </p>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Q: Can I migrate my site to another host?</strong><br>
                            A: Yes, use WordPress export/import tools or migration plugins. However, you'll lose the
                            automated deployment benefits.
                        </p>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Q: What happens to the JSON files after deployment?</strong><br>
                            A: They remain on your local system and in /tmp/ on the server. WordPress uses them only
                            during initial setup. After deployment, your site is a standard WordPress installation.
                        </p>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Q: Is this safe for production use?</strong><br>
                            A: Yes, but understand that redeployment overwrites WordPress changes. Use version control
                            for JSON files, and only redeploy when necessary.
                        </p>

                        <p class="content-text" style="margin-top: 1rem;">
                            <strong>Q: Why two repositories (fls-poc-sitebuilder and WebsiteBuild)?</strong><br>
                            A: Separation of concerns. fls-poc-sitebuilder is the configuration interface (runs
                            locally). WebsiteBuild contains deployment automation (runs on server via GitHub Actions).
                        </p>
                    </div>
                </section>

            </main>

            <!-- View Switcher & Back Button -->
            <a href="/php/admin/docs/index.php" class="view-switch-button">
                <i class="fas fa-th-list"></i>
                Multi-Page View
            </a>
            <a href="/php/web-admin.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Admin
            </a>
        </div>

        <script>
        // Smooth scroll and active link management
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();

                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');

                const targetId = this.getAttribute('href');
                const targetSection = document.querySelector(targetId);

                if (targetSection) {
                    targetSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Update active link on scroll
        window.addEventListener('scroll', function() {
            let current = '';
            const sections = document.querySelectorAll('.doc-section');

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (pageYOffset >= (sectionTop - 100)) {
                    current = section.getAttribute('id');
                }
            });

            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });
        </script>
    </body>

</html>
