<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Introduction - Frontline Framework</title>

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
                        <h1>Welcome to Frontline Framework</h1>
                        <p>Internal Website Builder Tool</p>
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
                    <span>Introduction</span>
                </nav>

                <h1 class="page-title">Welcome to Frontline Framework Interface</h1>
                <p class="page-subtitle">Your internal tool for creating and deploying WordPress websites quickly and efficiently.</p>

                <!-- Overview -->
                <section class="content-section">
                    <h2><i class="fas fa-home"></i> What is This Tool?</h2>

                    <p>Hey there! 👋 Welcome to our internal website builder tool. This is how we create and deploy WordPress websites for our organization. Don't worry if you're not technical – this guide will walk you through everything step-by-step.</p>

                    <p><strong>What does this tool do?</strong> It's a configuration interface where you set up how you want your website to look and work, BEFORE it goes live. Think of it like filling out a detailed form about your website, then clicking a button to make it real on the server.</p>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Important - Please Read!
                        </div>
                        <p>This is a <strong>pre-deployment configuration tool</strong>, not the actual live website. Changes you make here won't appear on your live site until you click "Deploy Website". After deployment, you'll use the regular WordPress admin panel (wp-admin) for day-to-day content updates.</p>
                    </div>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-lightbulb"></i>
                            How It Works in Simple Terms
                        </div>
                        <p><strong>Step 1:</strong> You fill in all the settings here (theme, pages, forms, security, etc.)</p>
                        <p><strong>Step 2:</strong> Click "Deploy Website" and wait 5-10 minutes</p>
                        <p><strong>Step 3:</strong> Your WordPress website is automatically created on Kinsta hosting</p>
                        <p><strong>Step 4:</strong> Go to your-website.com/wp-admin to manage the live site</p>
                    </div>
                </section>

                <!-- Key Features -->
                <section class="content-section">
                    <h2><i class="fas fa-star"></i> Key Features</h2>

                    <div class="feature-grid">
                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-file-code"></i>
                            </div>
                            <h3>JSON-Based Config</h3>
                            <p>All settings stored as JSON files for easy version control and deployment.</p>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-palette"></i>
                            </div>
                            <h3>11 Premium Themes</h3>
                            <p>Choose from professionally designed themes with custom layouts.</p>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-edit"></i>
                            </div>
                            <h3>SiteOrigin Page Builder</h3>
                            <p>Design page layouts with SiteOrigin Builder format for deployment.</p>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Advanced Security</h3>
                            <p>Configure geo-blocking, IP whitelisting, Wordfence, and 2FA.</p>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-wpforms"></i>
                            </div>
                            <h3>Forminator Forms</h3>
                            <p>Create contact forms, volunteer forms, and document uploads.</p>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <h3>Google Maps</h3>
                            <p>Configure WP Go Maps with markers and custom placements.</p>
                        </div>
                    </div>
                </section>

                <!-- What's Next -->
                <section class="content-section">
                    <h2><i class="fas fa-forward"></i> What's Next?</h2>

                    <p>Now that you understand what this tool does, here are your next steps:</p>

                    <div class="card">
                        <h3><i class="fas fa-cogs"></i> Learn How It Works</h3>
                        <p>Understand the system architecture and deployment flow.</p>
                        <a href="architecture.php" class="btn-primary">View Architecture →</a>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-rocket"></i> Quick Start Guide</h3>
                        <p>Deploy your first website in 6 simple steps.</p>
                        <a href="quick-start.php" class="btn-primary">Get Started →</a>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-sliders-h"></i> Configuration Guide</h3>
                        <p>Learn about all available configuration options.</p>
                        <a href="index.php#configuration" class="btn-primary">View Config Options →</a>
                    </div>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="index.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Documentation Home</span>
                    </a>
                    <a href="architecture.php" class="btn-nav next">
                        <span>System Architecture</span>
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
