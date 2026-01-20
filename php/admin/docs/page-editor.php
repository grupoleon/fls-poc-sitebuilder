<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Layout Editor - Frontline Framework</title>

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
                        <h1>Page Layout Configuration</h1>
                        <p>OPTIONAL pre-deployment page customization</p>
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
                    <span>Page Editor</span>
                </nav>

                <h1 class="page-title">Page Layout Configuration (Optional)</h1>
                <p class="page-subtitle">Pre-deployment page customization tool - skip this if you prefer editing in WordPress admin!</p>

                <!-- Important Notice -->
                <section class="content-section">
                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Recommended Approach for Non-Technical Users
                        </div>
                        <p><strong>Skip the Page Editor!</strong> For major content customizations and ongoing edits, use the WordPress admin panel (wp-admin) AFTER deployment. It's much easier with a visual editor. Only use this Page Editor if you need to customize initial demo content before first deployment.</p>
                    </div>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-lightbulb"></i>
                            When to Use This
                        </div>
                        <p><strong>Use Page Editor if:</strong> You want to customize demo content BEFORE deploying the site</p>
                        <p><strong>Use WordPress Admin if:</strong> You want to edit pages AFTER deployment (recommended for most users)</p>
                    </div>
                </section>

                <!-- How It Works -->
                <section class="content-section">
                    <h2><i class="fas fa-file-code"></i> How It Works</h2>

                    <ol class="step-list">
                        <li>
                            <strong>Select Theme and Page</strong>
                            <p>Choose your theme, then select which page to customize (Home, About, Contact, Issues, etc.)</p>
                        </li>

                        <li>
                            <strong>Edit Layout JSON</strong>
                            <p>Modify the SiteOrigin panels_data JSON structure. This defines widgets, grids, and content for the page.</p>
                        </li>

                        <li>
                            <strong>Save Configuration</strong>
                            <p>Saves to /pages/themes/[theme]/layouts/[page].json</p>
                        </li>

                        <li>
                            <strong>Deploy</strong>
                            <p>During deployment, deploy.sh uploads JSON files to /tmp/pages/themes/[theme]/layouts/ on server</p>
                        </li>

                        <li>
                            <strong>Import by template.sh</strong>
                            <p>template.sh copies layouts to active theme's demo-data directory, then WordPress imports them using theme's demo installation function</p>
                        </li>
                    </ol>
                </section>

                <!-- SiteOrigin Format -->
                <section class="content-section">
                    <h2><i class="fas fa-code"></i> SiteOrigin Page Builder Format</h2>

                    <p>Page layouts use SiteOrigin Page Builder JSON format:</p>

                    <div class="card">
                        <h3>JSON Structure</h3>
                        <ul>
                            <li><strong>widgets</strong> - Array of page widgets (hero sections, text blocks, images, buttons)</li>
                            <li><strong>grids</strong> - Layout grid configuration (columns, rows)</li>
                            <li><strong>grid_cells</strong> - Cell positions and widget assignments</li>
                            <li><strong>panels_info</strong> - Widget metadata (class, position, styling)</li>
                        </ul>
                    </div>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-lightbulb"></i>
                            Pro Tip
                        </div>
                        <p>Use the visual editor in your browser to make changes, then copy the generated JSON. Or edit JSON directly if you're familiar with SiteOrigin Page Builder format.</p>
                    </div>
                </section>

                <!-- Available Pages -->
                <section class="content-section">
                    <h2><i class="fas fa-layer-group"></i> Available Pages</h2>

                    <p>Each theme includes layouts for these pages:</p>

                    <div class="feature-grid">
                        <div class="card">
                            <h3><i class="fas fa-home"></i> home.json</h3>
                            <p>Homepage layout with hero section, intro, features</p>
                        </div>

                        <div class="card">
                            <h3><i class="fas fa-user"></i> about.json</h3>
                            <p>About page with bio, mission, team</p>
                        </div>

                        <div class="card">
                            <h3><i class="fas fa-envelope"></i> contact.json</h3>
                            <p>Contact page with form placeholder</p>
                        </div>

                        <div class="card">
                            <h3><i class="fas fa-list-check"></i> issues.json</h3>
                            <p>Issues/platform page</p>
                        </div>

                        <div class="card">
                            <h3><i class="fas fa-hand-holding-heart"></i> get-involved.json</h3>
                            <p>Volunteer signup page</p>
                        </div>

                        <div class="card">
                            <h3><i class="fas fa-star"></i> endorsements.json</h3>
                            <p>Endorsements display page</p>
                        </div>

                        <div class="card">
                            <h3><i class="fas fa-newspaper"></i> news.json</h3>
                            <p>News/blog listing page</p>
                        </div>
                    </div>
                </section>

                <!-- Editing Tips -->
                <section class="content-section">
                    <h2><i class="fas fa-graduation-cap"></i> Editing Tips</h2>

                    <h3>Using the Visual Editor</h3>
                    <ol>
                        <li>Select your theme and page</li>
                        <li>The visual editor loads with current page layout</li>
                        <li>Drag widgets to rearrange, add new widgets, edit content</li>
                        <li>Click "Save" to store changes as JSON</li>
                    </ol>

                    <h3>Editing JSON Directly</h3>
                    <ul>
                        <li>Click "View JSON" to see raw JSON structure</li>
                        <li>Edit text content, widget settings, grid layouts</li>
                        <li>Validate JSON before saving (invalid JSON will cause errors)</li>
                        <li>Click "Save JSON" to store changes</li>
                    </ul>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            JSON Validation
                        </div>
                        <p>Always validate your JSON before saving! Invalid JSON will cause deployment errors. Use online JSON validators or the built-in validator in the Page Editor.</p>
                    </div>
                </section>

                <!-- What Happens After Editing -->
                <section class="content-section">
                    <h2><i class="fas fa-question-circle"></i> What Happens to My Edits?</h2>

                    <div class="card">
                        <h3>Before Deployment</h3>
                        <p>Your page layout changes are stored as JSON files in /pages/themes/[theme]/layouts/. They don't affect anything until you deploy.</p>
                    </div>

                    <div class="card">
                        <h3>During Deployment</h3>
                        <p>The deploy.sh script uploads your JSON files to the Kinsta server. The template.sh script then imports them into WordPress, creating pages with your custom layouts.</p>
                    </div>

                    <div class="card">
                        <h3>After Deployment</h3>
                        <p>Your pages are live with the layouts you configured. Further edits should be made in WordPress admin (wp-admin → Pages → Edit Page → Edit with SiteOrigin).</p>
                    </div>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="config-theme.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Theme Configuration</span>
                    </a>
                    <a href="content-manager.php" class="btn-nav next">
                        <span>Content Preparation</span>
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
