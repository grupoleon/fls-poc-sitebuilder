<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Configuration - Frontline Framework</title>

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
                        <h1>Theme Configuration</h1>
                        <p>Select and customize your WordPress theme</p>
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
                    <span>Theme Configuration</span>
                </nav>

                <h1 class="page-title">Theme Configuration</h1>
                <p class="page-subtitle">Select and customize your WordPress theme appearance.</p>

                <!-- Overview -->
                <section class="content-section">
                    <h2><i class="fas fa-palette"></i> Overview</h2>

                    <p>Select and customize your WordPress theme. Theme can be set from either the <strong>Deployment tab</strong> OR the <strong>Page Editor tab</strong>.</p>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-info-circle"></i>
                            Theme Selection Locations
                        </div>
                        <p>You can choose your theme in two places:</p>
                        <ul>
                            <li><strong>Deployment Page:</strong> Select theme before deploying</li>
                            <li><strong>Page Editor:</strong> Select theme while editing page layouts</li>
                        </ul>
                        <p>Both locations update the same theme configuration.</p>
                    </div>
                </section>

                <!-- Available Themes -->
                <section class="content-section">
                    <h2><i class="fas fa-th"></i> Available Themes</h2>

                    <p>Choose from 11 professionally designed themes, each optimized for political campaigns and organizational websites:</p>

                    <div class="feature-grid">
                        <div class="card">
                            <h3>BurBank</h3>
                            <p>Modern political campaign theme with bold typography and clear calls-to-action.</p>
                        </div>

                        <div class="card">
                            <h3>Candidate</h3>
                            <p>Professional candidate presentation with focus on biography and platform.</p>
                        </div>

                        <div class="card">
                            <h3>Celeste</h3>
                            <p>Clean and elegant design perfect for professional image building.</p>
                        </div>

                        <div class="card">
                            <h3>DoLife</h3>
                            <p>Community-focused layout emphasizing grassroots engagement.</p>
                        </div>

                        <div class="card">
                            <h3>Everlead</h3>
                            <p>Leadership-oriented theme highlighting experience and vision.</p>
                        </div>

                        <div class="card">
                            <h3>LifeGuide</h3>
                            <p>Issue-focused presentation with detailed policy sections.</p>
                        </div>

                        <div class="card">
                            <h3>Political</h3>
                            <p>Traditional political design with patriotic elements.</p>
                        </div>

                        <div class="card">
                            <h3>R.Cole</h3>
                            <p>Bold and impactful theme for strong messaging campaigns.</p>
                        </div>

                        <div class="card">
                            <h3>Reform</h3>
                            <p>Change-oriented design emphasizing transformation and progress.</p>
                        </div>

                        <div class="card">
                            <h3>Speaker</h3>
                            <p>Public speaking focused with event calendars and video integration.</p>
                        </div>

                        <div class="card">
                            <h3>Tudor</h3>
                            <p>Classic and trustworthy design with traditional layouts.</p>
                        </div>
                    </div>
                </section>

                <!-- Theme Customization -->
                <section class="content-section">
                    <h2><i class="fas fa-edit"></i> Theme Customization</h2>

                    <h3>Logo Upload</h3>
                    <p>Upload your organization or campaign logo to display in the site header.</p>

                    <div class="card">
                        <h4>Logo Specifications</h4>
                        <ul>
                            <li><strong>Format:</strong> PNG (recommended for transparency) or JPG</li>
                            <li><strong>Dimensions:</strong> 300x100px (recommended)</li>
                            <li><strong>Background:</strong> Transparent PNG works best</li>
                            <li><strong>File Size:</strong> Under 500KB for optimal loading</li>
                        </ul>
                    </div>

                    <h3>Theme Pages</h3>
                    <p>Each theme includes pre-designed layouts for:</p>
                    <ul>
                        <li><strong>Home:</strong> Homepage with hero section and key information</li>
                        <li><strong>About:</strong> Biography, mission, and team information</li>
                        <li><strong>Contact:</strong> Contact form and location information</li>
                        <li><strong>Issues:</strong> Platform positions and policy details</li>
                        <li><strong>Get Involved:</strong> Volunteer signup and engagement options</li>
                        <li><strong>Endorsements:</strong> Supporter testimonials and endorsements</li>
                        <li><strong>News:</strong> Blog posts and press releases</li>
                    </ul>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-edit"></i>
                            Customizing Page Layouts
                        </div>
                        <p>After selecting a theme, you can customize individual page layouts in the <strong>Page Editor</strong>. This is optional - default layouts work great out of the box!</p>
                        <p><a href="page-editor.php">Learn more about Page Editor →</a></p>
                    </div>
                </section>

                <!-- Theme Switching -->
                <section class="content-section">
                    <h2><i class="fas fa-sync-alt"></i> Switching Themes</h2>

                    <h3>Before Deployment</h3>
                    <p>You can freely switch between themes before deploying. Simply select a different theme from the dropdown.</p>

                    <h3>After Deployment</h3>
                    <p>To switch themes after your site is live:</p>
                    <ol>
                        <li>Change the theme selection in this interface</li>
                        <li>Redeploy your website</li>
                        <li>New theme will be activated on your live site</li>
                    </ol>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Important Note About Redeployment
                        </div>
                        <p>Redeployment will <strong>overwrite any manual WordPress changes</strong> you've made. Only redeploy if you want to reset the site to the configured state.</p>
                        <p>For simple theme changes on a live site, consider using WordPress admin (Appearance → Themes) instead of redeploying.</p>
                    </div>
                </section>

                <!-- Theme Features -->
                <section class="content-section">
                    <h2><i class="fas fa-star"></i> Common Theme Features</h2>

                    <p>All themes include these standard features:</p>

                    <div class="feature-grid">
                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-mobile-screen-button"></i>
                            </div>
                            <h3>Mobile Responsive</h3>
                            <p>Automatically adapts to any screen size - phones, tablets, desktops.</p>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-edit"></i>
                            </div>
                            <h3>Page Builder</h3>
                            <p>SiteOrigin Page Builder for drag-and-drop layout editing.</p>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-bars"></i>
                            </div>
                            <h3>Custom Menus</h3>
                            <p>Flexible navigation menus with dropdown support.</p>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-share-alt"></i>
                            </div>
                            <h3>Social Integration</h3>
                            <p>Built-in social media links and sharing buttons.</p>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3>SEO Optimized</h3>
                            <p>Clean code and semantic HTML for better search rankings.</p>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            <h3>Fast Loading</h3>
                            <p>Optimized code and assets for quick page loads.</p>
                        </div>
                    </div>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="config-policies.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Policies Configuration</span>
                    </a>
                    <a href="page-editor.php" class="btn-nav next">
                        <span>Page Layout Editor</span>
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
