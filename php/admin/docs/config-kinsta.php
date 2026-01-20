<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kinsta Settings - Frontline Framework</title>

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
                        <h1>Kinsta API Settings</h1>
                        <p>Configure your Kinsta hosting connection</p>
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
                    <span>Kinsta Settings</span>
                </nav>

                <h1 class="page-title">Kinsta API Settings</h1>
                <p class="page-subtitle">Configure your Kinsta hosting credentials for automated site deployment.</p>

                <!-- Overview -->
                <section class="content-section">
                    <h2><i class="fas fa-server"></i> Overview</h2>

                    <p>These settings are required for site creation on Kinsta platform. They allow the system to communicate with Kinsta's API to create and configure your WordPress sites automatically.</p>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-info-circle"></i>
                            What You Need
                        </div>
                        <ul>
                            <li>Active Kinsta hosting account</li>
                            <li>Kinsta API Token (from Kinsta dashboard)</li>
                            <li>Company ID (from your Kinsta account)</li>
                        </ul>
                    </div>
                </section>

                <!-- API Credentials -->
                <section class="content-section">
                    <h2><i class="fas fa-key"></i> API Credentials</h2>

                    <h3>Kinsta API Token</h3>
                    <p>Your API token authenticates this system with Kinsta's API.</p>

                    <div class="card">
                        <h4>How to Get Your API Token</h4>
                        <ol>
                            <li>Log into your Kinsta dashboard</li>
                            <li>Go to <strong>API → API Keys</strong></li>
                            <li>Click <strong>"Generate API Key"</strong></li>
                            <li>Give it a descriptive name (e.g., "Frontline Framework")</li>
                            <li>Copy the generated token immediately (it won't be shown again)</li>
                            <li>Paste it into the "Kinsta API Token" field</li>
                        </ol>
                    </div>

                    <h3>Company ID</h3>
                    <p>Your Company ID identifies your Kinsta account.</p>

                    <div class="card">
                        <h4>How to Find Your Company ID</h4>
                        <ol>
                            <li>Log into Kinsta dashboard</li>
                            <li>Click on your company name in the top-left corner</li>
                            <li>Your Company ID will be displayed in the company information</li>
                            <li>Copy and paste it into the "Company ID" field</li>
                        </ol>
                    </div>

                    <div class="alert alert-danger">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Security Warning
                        </div>
                        <p><strong>Keep your API token secure!</strong></p>
                        <ul>
                            <li>Never share your API token publicly</li>
                            <li>Don't commit it to public repositories</li>
                            <li>If compromised, immediately regenerate in Kinsta dashboard</li>
                        </ul>
                    </div>
                </section>

                <!-- WordPress Configuration -->
                <section class="content-section">
                    <h2><i class="fab fa-wordpress"></i> WordPress Configuration</h2>

                    <h3>Site Title</h3>
                    <p>Your website name (e.g., "John Smith for Senate"). This appears in browser tabs and site metadata.</p>

                    <h3>Display Name</h3>
                    <p>URL-friendly site identifier used in Kinsta dashboard (e.g., "john-smith-campaign"). Must be unique in your Kinsta account.</p>

                    <h3>Admin Email</h3>
                    <p>Email address for WordPress admin notifications, password resets, and system updates.</p>

                    <h3>Admin Username</h3>
                    <p>WordPress admin login username.</p>
                    <div class="alert alert-warning">
                        <p><strong>Security Tip:</strong> Avoid using "admin" as your username - it's the first target for brute force attacks.</p>
                    </div>

                    <h3>Admin Password</h3>
                    <p>Strong password for WordPress admin login.</p>
                    <div class="alert alert-success">
                        <p><strong>Pro Tip:</strong> Click the "Generate" button for a secure random password!</p>
                    </div>
                </section>

                <!-- Server Configuration -->
                <section class="content-section">
                    <h2><i class="fas fa-cog"></i> Server Configuration</h2>

                    <h3>Region</h3>
                    <p>Select the data center location closest to your target audience:</p>
                    <ul>
                        <li><strong>US Central</strong> - Best for USA-based visitors</li>
                        <li><strong>Europe West</strong> - Best for European visitors</li>
                        <li><strong>Asia Southeast</strong> - Best for Asian visitors</li>
                    </ul>
                    <p><strong>Note:</strong> Closer servers = faster load times for visitors</p>

                    <h3>WordPress Language</h3>
                    <p>Choose the language for WordPress admin interface:</p>
                    <ul>
                        <li>English (US)</li>
                        <li>Spanish</li>
                        <li>French</li>
                        <li>German</li>
                        <li>And many more...</li>
                    </ul>

                    <h3>Install Mode</h3>
                    <ul>
                        <li><strong>New Installation (Recommended)</strong> - Creates a fresh WordPress site</li>
                        <li><strong>Existing Installation</strong> - Uses existing WordPress installation (advanced)</li>
                    </ul>
                </section>

                <!-- Optional Features -->
                <section class="content-section">
                    <h2><i class="fas fa-plug"></i> Optional Features</h2>

                    <h3>Enable Multisite</h3>
                    <p>Enables WordPress multisite network functionality, allowing you to run multiple WordPress sites from a single installation.</p>
                    <div class="alert alert-warning">
                        <p><strong>Advanced Feature:</strong> Only enable if you specifically need multisite functionality.</p>
                    </div>

                    <h3>Install WooCommerce</h3>
                    <p>Automatically installs WooCommerce plugin for e-commerce functionality (online stores, donations, merchandise sales).</p>

                    <h3>Install WordPress SEO</h3>
                    <p>Automatically installs Yoast SEO plugin for search engine optimization tools.</p>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="config-git.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Git Configuration</span>
                    </a>
                    <a href="config-security.php" class="btn-nav next">
                        <span>Security Configuration</span>
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
