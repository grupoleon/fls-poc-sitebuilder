<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Admin Guide - Frontline Framework</title>

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
                        <h1>Managing Your Live Site</h1>
                        <p>WordPress admin panel guide</p>
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
                    <span>WordPress Admin</span>
                </nav>

                <h1 class="page-title">Managing Your Live Site</h1>
                <p class="page-subtitle">Use WordPress admin interface to manage your deployed website.</p>

                <!-- Accessing WordPress Admin -->
                <section class="content-section">
                    <h2><i class="fas fa-user-shield"></i> Accessing WordPress Admin</h2>

                    <div class="card">
                        <h3>Login Information</h3>
                        <p><strong>Login URL:</strong> https://your-domain.com/wp-admin</p>
                        <p><strong>Credentials:</strong> Use the admin username and password you configured in Kinsta Settings before deployment.</p>
                    </div>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-key"></i>
                            Forgot Your Password?
                        </div>
                        <p>Use WordPress's "Lost your password?" link on the login page. Reset email will be sent to the admin email address you configured.</p>
                    </div>
                </section>

                <!-- Editing Content -->
                <section class="content-section">
                    <h2><i class="fas fa-edit"></i> Editing Content</h2>

                    <p>In WordPress admin, you can manage all aspects of your website:</p>

                    <div class="card">
                        <h3><i class="fas fa-file-alt"></i> Edit Pages</h3>
                        <p>Go to <strong>Pages → All Pages</strong>, click page title to edit with SiteOrigin Page Builder</p>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-blog"></i> Add/Edit Posts</h3>
                        <p>Go to <strong>Posts → All Posts</strong> or <strong>Add New</strong> to create blog posts</p>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-images"></i> Manage Media</h3>
                        <p>Go to <strong>Media → Library</strong> to upload and manage images, videos, documents</p>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-palette"></i> Customize Theme</h3>
                        <p>Go to <strong>Appearance → Customize</strong> for theme settings, colors, logo</p>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-bars"></i> Edit Menus</h3>
                        <p>Go to <strong>Appearance → Menus</strong> to modify navigation menus</p>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-wpforms"></i> Manage Forms</h3>
                        <p>Go to <strong>Forminator → Forms</strong> to edit form fields and settings</p>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-map"></i> Configure Maps</h3>
                        <p>Go to <strong>WP Go Maps → Maps</strong> to edit map markers and settings</p>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-shield-alt"></i> Security Settings</h3>
                        <p>Go to <strong>Wordfence → All Options</strong> for security configuration</p>
                    </div>
                </section>

                <!-- Installed Plugins -->
                <section class="content-section">
                    <h2><i class="fas fa-plug"></i> Installed Plugins</h2>

                    <p>Your deployment automatically installs and configures these plugins:</p>

                    <div class="feature-grid">
                        <div class="card">
                            <h3>SiteOrigin Panels</h3>
                            <p>Page builder for layout customization with drag-and-drop interface</p>
                        </div>

                        <div class="card">
                            <h3>SiteOrigin Widgets Bundle</h3>
                            <p>Additional widgets for page builder (buttons, images, galleries)</p>
                        </div>

                        <div class="card">
                            <h3>Forminator</h3>
                            <p>Form builder and management for contact, volunteer, and upload forms</p>
                        </div>

                        <div class="card">
                            <h3>WP Go Maps</h3>
                            <p>Google Maps integration with markers and custom styling</p>
                        </div>

                        <div class="card">
                            <h3>Wordfence Security</h3>
                            <p>Firewall, malware scanning, brute force protection</p>
                        </div>

                        <div class="card">
                            <h3>WP 2FA</h3>
                            <p>Two-factor authentication (if enabled in config)</p>
                        </div>

                        <div class="card">
                            <h3>WPS Hide Login</h3>
                            <p>Custom login URL for security</p>
                        </div>
                    </div>

                    <div class="alert alert-danger">
                        <div class="alert-title">
                            <i class="fas fa-trash-alt"></i>
                            Don't Deactivate Required Plugins
                        </div>
                        <p>DO NOT deactivate SiteOrigin Panels, Forminator, WP Go Maps, or Wordfence unless you know what you're doing. These plugins are essential for your site's functionality and security.</p>
                    </div>
                </section>

                <!-- Common Tasks -->
                <section class="content-section">
                    <h2><i class="fas fa-tasks"></i> Common Management Tasks</h2>

                    <h3>Adding a New Blog Post</h3>
                    <ol>
                        <li>Go to wp-admin → Posts → Add New</li>
                        <li>Enter post title and content</li>
                        <li>Add featured image (recommended)</li>
                        <li>Select categories and tags</li>
                        <li>Click "Publish"</li>
                    </ol>

                    <h3>Updating a Page</h3>
                    <ol>
                        <li>Go to wp-admin → Pages → All Pages</li>
                        <li>Click page title to edit</li>
                        <li>Click "Edit with SiteOrigin" for page builder</li>
                        <li>Make changes to widgets and content</li>
                        <li>Click "Update" to save</li>
                    </ol>

                    <h3>Uploading Images</h3>
                    <ol>
                        <li>Go to wp-admin → Media → Add New</li>
                        <li>Drag images or click "Select Files"</li>
                        <li>Wait for upload to complete</li>
                        <li>Images are now available in Media Library</li>
                    </ol>

                    <h3>Viewing Form Submissions</h3>
                    <ol>
                        <li>Go to wp-admin → Forminator → Submissions</li>
                        <li>Select form from dropdown</li>
                        <li>View all submissions with details</li>
                        <li>Export to CSV if needed</li>
                    </ol>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="post-deployment.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Post-Deployment Steps</span>
                    </a>
                    <a href="troubleshooting.php" class="btn-nav next">
                        <span>Troubleshooting</span>
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
