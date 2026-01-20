<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post-Deployment Steps - Frontline Framework</title>

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
                        <h1>After Deployment</h1>
                        <p>Next steps once your site is live</p>
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
                    <a href="index.php#post-deployment">After Deployment</a>
                    <span>/</span>
                    <span>Next Steps</span>
                </nav>

                <h1 class="page-title">After Deployment</h1>
                <p class="page-subtitle">What to do once your WordPress site is live on Kinsta hosting.</p>

                <!-- Success Message -->
                <section class="content-section">
                    <div class="alert alert-success">
                        <div class="alert-title">
                            <i class="fas fa-party-horn"></i>
                            Your Site is Live!
                        </div>
                        <p>Your WordPress website is now accessible at your Kinsta-assigned domain (or custom domain if configured). All configurations, pages, forms, maps, and security settings have been applied.</p>
                    </div>
                </section>

                <!-- Next Steps -->
                <section class="content-section">
                    <h2><i class="fas fa-tasks"></i> Next Steps</h2>

                    <ol class="step-list">
                        <li>
                            <strong>Access WordPress Admin</strong>
                            <p>Go to your-domain.com/wp-admin and login with the admin credentials you configured during setup.</p>
                            <a href="wordpress-admin.php" class="btn-secondary">WordPress Admin Guide →</a>
                        </li>

                        <li>
                            <strong>Verify Configuration</strong>
                            <p>Check that pages, menus, forms, and maps are working correctly. Test contact forms and security features to ensure everything is functioning as expected.</p>
                        </li>

                        <li>
                            <strong>Add Real Content</strong>
                            <p>Replace demo content with real posts, images, and text using WordPress admin interface. Edit pages, create blog posts, upload media.</p>
                        </li>

                        <li>
                            <strong>Configure Domain (Optional)</strong>
                            <p>In Kinsta dashboard, add your custom domain and configure DNS settings. Point your domain's nameservers or A records to Kinsta's servers.</p>
                        </li>

                        <li>
                            <strong>Install SSL Certificate</strong>
                            <p>Kinsta automatically provides Let's Encrypt SSL for custom domains. Verify the SSL certificate is active (look for padlock in browser).</p>
                        </li>

                        <li>
                            <strong>Configure Backups</strong>
                            <p>Kinsta provides automatic daily backups. Configure backup schedule in Kinsta dashboard. Consider additional backup solutions for extra protection.</p>
                        </li>
                    </ol>
                </section>

                <!-- Domain Setup -->
                <section class="content-section">
                    <h2><i class="fas fa-globe"></i> Custom Domain Setup</h2>

                    <h3>Adding Your Domain in Kinsta</h3>
                    <ol>
                        <li>Log into Kinsta dashboard</li>
                        <li>Go to Sites → Your Site → Domains</li>
                        <li>Click "Add Domain"</li>
                        <li>Enter your domain name (e.g., yoursite.com)</li>
                        <li>Choose primary domain (with or without www)</li>
                    </ol>

                    <h3>DNS Configuration</h3>
                    <p>Update your domain's DNS settings with your domain registrar:</p>

                    <div class="card">
                        <h4>Option 1: Nameservers (Recommended)</h4>
                        <p>Point your domain's nameservers to Kinsta's nameservers for full DNS management through Kinsta.</p>
                    </div>

                    <div class="card">
                        <h4>Option 2: A Records</h4>
                        <p>Add A records pointing to Kinsta's IP address. Also add CNAME for www subdomain.</p>
                    </div>

                    <div class="alert alert-info">
                        <p><strong>DNS Propagation:</strong> DNS changes can take 24-48 hours to propagate globally. Your site will work at Kinsta's staging URL immediately while DNS propagates.</p>
                    </div>
                </section>

                <!-- Content Management -->
                <section class="content-section">
                    <h2><i class="fas fa-edit"></i> Content Management</h2>

                    <h3>Replacing Demo Content</h3>
                    <p>Your deployed site includes demo content. Replace it with your real content:</p>
                    <ul>
                        <li><strong>Pages:</strong> Edit page content in wp-admin → Pages</li>
                        <li><strong>Blog Posts:</strong> Create new posts in wp-admin → Posts</li>
                        <li><strong>Images:</strong> Upload to wp-admin → Media Library</li>
                        <li><strong>Menus:</strong> Customize in wp-admin → Appearance → Menus</li>
                    </ul>

                    <h3>Using SiteOrigin Page Builder</h3>
                    <p>Edit page layouts with the visual page builder:</p>
                    <ol>
                        <li>Go to wp-admin → Pages → Edit Page</li>
                        <li>Click "Edit with SiteOrigin" button</li>
                        <li>Drag widgets, edit content, rearrange sections</li>
                        <li>Click "Publish" or "Update" to save</li>
                    </ol>
                </section>

                <!-- Security Verification -->
                <section class="content-section">
                    <h2><i class="fas fa-shield-alt"></i> Security Verification</h2>

                    <h3>Check Security Features</h3>
                    <ul>
                        <li><strong>Wordfence:</strong> Go to wp-admin → Wordfence → Dashboard to verify it's active</li>
                        <li><strong>SSL Certificate:</strong> Visit your site (https://), ensure padlock icon appears</li>
                        <li><strong>Admin Login:</strong> Test login at /wp-admin with your credentials</li>
                        <li><strong>2FA:</strong> If enabled, set up 2FA on first admin login</li>
                        <li><strong>Geo-Blocking:</strong> Test from different locations if enabled</li>
                    </ul>

                    <h3>Security Best Practices</h3>
                    <ol>
                        <li>Change admin password to something unique (if not already strong)</li>
                        <li>Enable 2FA for all administrator accounts</li>
                        <li>Review Wordfence settings and configure email alerts</li>
                        <li>Keep WordPress, themes, and plugins updated</li>
                        <li>Review user accounts, remove unnecessary users</li>
                    </ol>
                </section>

                <!-- Testing Checklist -->
                <section class="content-section">
                    <h2><i class="fas fa-list-check"></i> Post-Deployment Testing Checklist</h2>

                    <div class="card">
                        <h3>✓ Functionality Tests</h3>
                        <ul>
                            <li>All pages load without errors</li>
                            <li>Navigation menus work correctly</li>
                            <li>Contact forms submit successfully</li>
                            <li>Form submissions send email notifications</li>
                            <li>Maps display correctly with markers</li>
                            <li>Social media links work</li>
                            <li>Search functionality works</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>✓ Design & Layout Tests</h3>
                        <ul>
                            <li>Logo displays correctly</li>
                            <li>Images load properly</li>
                            <li>Text is readable (fonts, colors)</li>
                            <li>Layout looks good on mobile devices</li>
                            <li>No broken images or links</li>
                            <li>Page sections align correctly</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>✓ Security Tests</h3>
                        <ul>
                            <li>SSL certificate is active (https://)</li>
                            <li>Wordfence is scanning</li>
                            <li>Brute force protection is active</li>
                            <li>Geo-blocking works (if enabled)</li>
                            <li>2FA prompts on admin login</li>
                            <li>Non-admin pages accessible to public</li>
                        </ul>
                    </div>
                </section>

                <!-- Important Warning -->
                <section class="content-section">
                    <h2><i class="fas fa-exclamation-triangle"></i> Important: About Redeployment</h2>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Redeployment Overwrites Changes
                        </div>
                        <p>If you make changes through WordPress admin, then redeploy from this interface, your WordPress changes will be <strong>OVERWRITTEN</strong> by the configuration files. Only redeploy if you want to reset the site to the configured state.</p>
                        <p><strong>Recommended Workflow:</strong> Use this interface for initial deployment only. Make all ongoing changes through WordPress admin.</p>
                    </div>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="forms-manager.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Forms Configuration</span>
                    </a>
                    <a href="wordpress-admin.php" class="btn-nav next">
                        <span>WordPress Admin Guide</span>
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
