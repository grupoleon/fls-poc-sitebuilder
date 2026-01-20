<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Frontline Framework</title>

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
                        <h1>Frequently Asked Questions</h1>
                        <p>Quick answers to common questions</p>
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
                    <span>FAQ</span>
                </nav>

                <h1 class="page-title">Frequently Asked Questions</h1>
                <p class="page-subtitle">Quick answers to common questions about Frontline Framework.</p>

                <!-- General Questions -->
                <section class="content-section">
                    <h2><i class="fas fa-info-circle"></i> General Questions</h2>

                    <div class="card">
                        <h3>Q: What is this interface exactly?</h3>
                        <p><strong>A:</strong> This is a pre-deployment configuration system. You configure your WordPress site BEFORE deployment by creating JSON files. When you click "Deploy Website," configurations are uploaded to Kinsta and WordPress is automatically configured via bash scripts.</p>
                    </div>

                    <div class="card">
                        <h3>Q: Can I edit my live site from this interface?</h3>
                        <p><strong>A:</strong> No. This interface only creates configuration files for deployment. To edit your live site, use WordPress admin (wp-admin) after deployment.</p>
                    </div>

                    <div class="card">
                        <h3>Q: What happens if I redeploy after making WordPress changes?</h3>
                        <p><strong>A:</strong> Redeployment will OVERWRITE your WordPress changes with the configurations stored in JSON files. Only redeploy if you want to reset the site.</p>
                    </div>

                    <div class="card">
                        <h3>Q: How long does deployment take?</h3>
                        <p><strong>A:</strong> 10-15 minutes total. Site creation (3-5 min) + configuration upload (1-2 min) + GitHub Actions (5-8 min).</p>
                    </div>
                </section>

                <!-- Technical Questions -->
                <section class="content-section">
                    <h2><i class="fas fa-code"></i> Technical Questions</h2>

                    <div class="card">
                        <h3>Q: Where are my configuration files stored?</h3>
                        <p><strong>A:</strong> Locally in /config/ (site settings) and /pages/ (layouts, content, forms). During deployment, they're uploaded to /tmp/ on Kinsta server.</p>
                    </div>

                    <div class="card">
                        <h3>Q: Can I use this with an existing WordPress site?</h3>
                        <p><strong>A:</strong> This system is designed for NEW site deployment. It creates sites from scratch on Kinsta. Don't use it on existing production sites.</p>
                    </div>

                    <div class="card">
                        <h3>Q: What if I need a feature not available in the interface?</h3>
                        <p><strong>A:</strong> After deployment, install additional WordPress plugins and configure them through WordPress admin.</p>
                    </div>

                    <div class="card">
                        <h3>Q: Why two repositories (fls-poc-sitebuilder and WebsiteBuild)?</h3>
                        <p><strong>A:</strong> Separation of concerns. fls-poc-sitebuilder is the configuration interface (runs locally). WebsiteBuild contains deployment automation (runs on server via GitHub Actions).</p>
                    </div>
                </section>

                <!-- Backup & Migration -->
                <section class="content-section">
                    <h2><i class="fas fa-database"></i> Backup & Migration</h2>

                    <div class="card">
                        <h3>Q: How do I backup my site?</h3>
                        <p><strong>A:</strong> Kinsta provides automatic daily backups. Access them in Kinsta dashboard → Backups. You can also use WordPress backup plugins for additional protection.</p>
                    </div>

                    <div class="card">
                        <h3>Q: Can I migrate my site to another host?</h3>
                        <p><strong>A:</strong> Yes, use WordPress export/import tools or migration plugins. However, you'll lose the automated deployment benefits.</p>
                    </div>

                    <div class="card">
                        <h3>Q: What happens to the JSON files after deployment?</h3>
                        <p><strong>A:</strong> They remain on your local system and in /tmp/ on the server. WordPress uses them only during initial setup. After deployment, your site is a standard WordPress installation.</p>
                    </div>
                </section>

                <!-- Security Questions -->
                <section class="content-section">
                    <h2><i class="fas fa-shield-alt"></i> Security Questions</h2>

                    <div class="card">
                        <h3>Q: Is this safe for production use?</h3>
                        <p><strong>A:</strong> Yes, but understand that redeployment overwrites WordPress changes. Use version control for JSON files, and only redeploy when necessary.</p>
                    </div>

                    <div class="card">
                        <h3>Q: What security features are included?</h3>
                        <p><strong>A:</strong> Wordfence firewall, brute force protection, malware scanning, geo-blocking, IP whitelisting, 2FA, SSL certificates, and custom login URLs.</p>
                    </div>

                    <div class="card">
                        <h3>Q: Should I enable geo-blocking?</h3>
                        <p><strong>A:</strong> Only if you need to restrict access by country. Always include YOUR country in the allowed list before enabling, or you'll lock yourself out!</p>
                    </div>
                </section>

                <!-- Content Management -->
                <section class="content-section">
                    <h2><i class="fas fa-edit"></i> Content Management</h2>

                    <div class="card">
                        <h3>Q: Do I need to use the Page Editor?</h3>
                        <p><strong>A:</strong> No, it's optional. Most users find it easier to edit pages in WordPress admin after deployment using the visual SiteOrigin Page Builder.</p>
                    </div>

                    <div class="card">
                        <h3>Q: How do I add more blog posts after deployment?</h3>
                        <p><strong>A:</strong> Use WordPress admin: wp-admin → Posts → Add New. Don't use the Content Manager after initial deployment.</p>
                    </div>

                    <div class="card">
                        <h3>Q: Can I change themes after deployment?</h3>
                        <p><strong>A:</strong> Yes, either redeploy with a new theme selection (overwrites changes) or use WordPress admin → Appearance → Themes (preserves changes).</p>
                    </div>
                </section>

                <!-- Support -->
                <section class="content-section">
                    <h2><i class="fas fa-life-ring"></i> Getting Help</h2>

                    <p>If you can't find the answer you're looking for:</p>

                    <ol>
                        <li>Check the <a href="troubleshooting.php">Troubleshooting Guide</a> for common issues</li>
                        <li>Review deployment logs in /logs/deployment/</li>
                        <li>Check GitHub Actions logs for deployment errors</li>
                        <li>Contact your system administrator for technical support</li>
                        <li>Review Kinsta support documentation for hosting issues</li>
                    </ol>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="troubleshooting.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Troubleshooting</span>
                    </a>
                    <a href="index.php" class="btn-nav next">
                        <span>Documentation Home</span>
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
