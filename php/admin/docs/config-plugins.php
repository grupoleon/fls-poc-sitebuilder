<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plugins Configuration - Frontline Framework</title>

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
                        <h1>Plugins Configuration</h1>
                        <p>Manage WordPress plugins and their settings</p>
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
                    <span>Plugins</span>
                </nav>

                <h1 class="page-title">Plugins Configuration</h1>
                <p class="page-subtitle">Control which WordPress plugins are installed, kept, or removed during deployment.</p>

                <!-- Overview -->
                <section class="content-section">
                    <h2><i class="fas fa-plug"></i> Overview</h2>

                    <p>The Plugins Configuration manages WordPress plugins during deployment. You can specify:</p>
                    <ul>
                        <li><strong>Plugins to Keep:</strong> Existing plugins that should not be removed</li>
                        <li><strong>Plugins to Install:</strong> New plugins to be installed and activated</li>
                    </ul>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-info-circle"></i>
                            Default Behavior
                        </div>
                        <p>By default, the framework installs essential plugins:</p>
                        <ul>
                            <li>SiteOrigin Widgets Bundle (page builder)</li>
                            <li>Forminator (forms)</li>
                            <li>WP Go Maps (maps)</li>
                            <li>Wordfence Security (security)</li>
                        </ul>
                        <p>These are automatically configured unless overridden.</p>
                    </div>
                </section>

                <!-- Plugins to Keep -->
                <section class="content-section">
                    <h2><i class="fas fa-shield-alt"></i> Plugins to Keep</h2>

                    <p>Use this section to protect plugins from being removed during deployment. This is useful when:</p>
                    <ul>
                        <li>You have custom plugins installed manually in WordPress</li>
                        <li>Previous deployments installed plugins you want to preserve</li>
                        <li>You're using third-party plugins not managed by this framework</li>
                    </ul>

                    <h3>Adding Plugins to Keep List</h3>
                    <ol>
                        <li>Click the <strong>"Add Plugin"</strong> button in the "Plugins to Keep" section</li>
                        <li>Enter the plugin folder name (e.g., <code>jetpack</code>, <code>yoast-seo</code>)</li>
                        <li>The plugin will be excluded from removal scripts</li>
                    </ol>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Plugin Folder Names
                        </div>
                        <p>Use the exact plugin folder name from <code>wp-content/plugins/</code>, not the display name. For example:</p>
                        <ul>
                            <li><strong>Correct:</strong> <code>contact-form-7</code></li>
                            <li><strong>Wrong:</strong> "Contact Form 7"</li>
                        </ul>
                        <p>You can find folder names in WordPress at <strong>Plugins &gt; Installed Plugins</strong> (hover over plugin name to see folder in URL).</p>
                    </div>

                    <h3>Example: Keeping Custom Plugins</h3>
                    <div class="card">
                        <p><strong>Scenario:</strong> You manually installed a custom donations plugin that should not be removed during redeployment.</p>
                        <p><strong>Solution:</strong> Add <code>custom-donations</code> to the "Plugins to Keep" list.</p>
                    </div>
                </section>

                <!-- Plugins to Install -->
                <section class="content-section">
                    <h2><i class="fas fa-download"></i> Plugins to Install</h2>

                    <p>Specify additional plugins that should be installed from the WordPress Plugin Repository during deployment.</p>

                    <h3>Adding Plugins to Install</h3>
                    <ol>
                        <li>Click <strong>"Add Plugin"</strong> in the "Plugins to Install" section</li>
                        <li>Enter the plugin slug from WordPress.org (e.g., <code>akismet</code>, <code>jetpack</code>)</li>
                        <li>Optionally specify version number (leave blank for latest)</li>
                        <li>Toggle activation status (enabled = activate after install)</li>
                    </ol>

                    <h3>Plugin Properties</h3>
                    <div class="card">
                        <h4><i class="fas fa-tag"></i> Plugin Slug</h4>
                        <p>The unique identifier from WordPress.org plugin URL.</p>
                        <p><strong>Example:</strong> For <code>https://wordpress.org/plugins/jetpack/</code>, the slug is <code>jetpack</code></p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-code-branch"></i> Version (Optional)</h4>
                        <p>Specific version number to install. Examples:</p>
                        <ul>
                            <li><code>3.2.1</code> - Install exact version</li>
                            <li>Leave blank - Install latest version</li>
                        </ul>
                        <p><strong>Recommendation:</strong> Leave blank unless you need a specific version for compatibility.</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-toggle-on"></i> Activate After Install</h4>
                        <p>When enabled, the plugin will be activated immediately after installation.</p>
                        <p><strong>Note:</strong> Most plugins should be activated unless they require manual configuration first.</p>
                    </div>
                </section>

                <!-- Common Plugins -->
                <section class="content-section">
                    <h2><i class="fas fa-star"></i> Common Plugins</h2>

                    <p>Here are commonly used WordPress plugins you might want to install:</p>

                    <h3>SEO & Marketing</h3>
                    <ul>
                        <li><code>wordpress-seo</code> - Yoast SEO</li>
                        <li><code>google-sitemap-generator</code> - XML Sitemaps</li>
                        <li><code>redirection</code> - Redirect Manager</li>
                    </ul>

                    <h3>Performance & Caching</h3>
                    <ul>
                        <li><code>w3-total-cache</code> - W3 Total Cache</li>
                        <li><code>wp-super-cache</code> - WP Super Cache</li>
                        <li><code>autoptimize</code> - Autoptimize</li>
                    </ul>

                    <h3>Security (Additional)</h3>
                    <ul>
                        <li><code>better-wp-security</code> - iThemes Security</li>
                        <li><code>all-in-one-wp-security-and-firewall</code> - All In One WP Security</li>
                        <li><code>sucuri-scanner</code> - Sucuri Security</li>
                    </ul>

                    <h3>Backup</h3>
                    <ul>
                        <li><code>updraftplus</code> - UpdraftPlus Backup</li>
                        <li><code>duplicator</code> - Duplicator</li>
                    </ul>

                    <h3>Forms & Lead Generation</h3>
                    <ul>
                        <li><code>contact-form-7</code> - Contact Form 7</li>
                        <li><code>ninja-forms</code> - Ninja Forms</li>
                        <li><code>mailchimp-for-wp</code> - Mailchimp for WordPress</li>
                    </ul>
                </section>

                <!-- Best Practices -->
                <section class="content-section">
                    <h2><i class="fas fa-lightbulb"></i> Best Practices</h2>

                    <h3>Plugin Selection</h3>
                    <ul>
                        <li><strong>Less is more:</strong> Only install plugins you actually need</li>
                        <li><strong>Check compatibility:</strong> Verify plugins work with your WordPress version</li>
                        <li><strong>Read reviews:</strong> Check ratings and reviews on WordPress.org before installing</li>
                        <li><strong>Avoid conflicts:</strong> Don't install multiple plugins with overlapping functionality</li>
                    </ul>

                    <h3>Security Considerations</h3>
                    <ul>
                        <li>Only install plugins from WordPress.org or trusted sources</li>
                        <li>Keep plugin list minimal to reduce attack surface</li>
                        <li>Regularly review and remove unused plugins</li>
                        <li>Check plugin update frequency (avoid abandoned plugins)</li>
                    </ul>

                    <h3>Performance</h3>
                    <ul>
                        <li>Each plugin adds load time - be selective</li>
                        <li>Avoid heavy plugins that load on every page</li>
                        <li>Test site speed after adding new plugins</li>
                        <li>Consider combining functionality (one multi-purpose plugin vs. many single-purpose)</li>
                    </ul>
                </section>

                <!-- Troubleshooting -->
                <section class="content-section">
                    <h2><i class="fas fa-wrench"></i> Troubleshooting</h2>

                    <h3>Plugin Installation Fails</h3>
                    <ul>
                        <li>Verify the plugin slug is correct (check WordPress.org URL)</li>
                        <li>Ensure WordPress can connect to WordPress.org API</li>
                        <li>Check deployment logs for specific error messages</li>
                        <li>Try installing manually in WordPress wp-admin to test</li>
                    </ul>

                    <h3>Plugin Gets Removed on Redeploy</h3>
                    <ul>
                        <li>Add the plugin to "Plugins to Keep" list</li>
                        <li>Ensure you're using the correct folder name</li>
                        <li>Save configuration before redeployment</li>
                    </ul>

                    <h3>Plugin Activated But Not Working</h3>
                    <ul>
                        <li>Check if plugin requires additional configuration</li>
                        <li>Verify plugin is compatible with your theme</li>
                        <li>Look for plugin-specific settings in WordPress wp-admin</li>
                        <li>Check error logs for plugin-related errors</li>
                    </ul>

                    <h3>Site Breaks After Plugin Install</h3>
                    <ul>
                        <li>Remove the problematic plugin from "Plugins to Install"</li>
                        <li>Redeploy to clean install</li>
                        <li>Contact plugin author for support</li>
                        <li>Look for alternative plugins with similar functionality</li>
                    </ul>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="config-navigation.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Navigation Configuration</span>
                    </a>
                    <a href="config-policies.php" class="btn-nav next">
                        <span>Policies Configuration</span>
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
