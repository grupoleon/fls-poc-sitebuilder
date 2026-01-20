<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Configuration - Frontline Framework</title>

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
                        <h1>Navigation Configuration</h1>
                        <p>Configure site navigation and menu structure</p>
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
                    <span>Navigation</span>
                </nav>

                <h1 class="page-title">Navigation & Menus Configuration</h1>
                <p class="page-subtitle">Define custom navigation menus that will appear in your website's header, footer, or other menu locations.</p>

                <!-- Overview -->
                <section class="content-section">
                    <h2><i class="fas fa-bars"></i> Overview</h2>

                    <p>The Navigation configuration allows you to create custom menus with links to pages, posts, categories, or external URLs. These menus are deployed to WordPress and assigned to theme menu locations.</p>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-info-circle"></i>
                            When to Use Custom Navigation
                        </div>
                        <p>Use this feature if you want to:</p>
                        <ul>
                            <li>Override the theme's default navigation structure</li>
                            <li>Create a specific menu order different from page hierarchy</li>
                            <li>Include external links in your navigation</li>
                            <li>Control exactly which pages appear in menus</li>
                        </ul>
                    </div>
                </section>

                <!-- Configuration Options -->
                <section class="content-section">
                    <h2><i class="fas fa-cog"></i> Configuration Options</h2>

                    <h3>Enable Custom Navigation</h3>
                    <p>Toggle this setting to activate custom menu configuration. When disabled, WordPress will use the theme's default menu structure.</p>

                    <h3>Replace Existing Menus</h3>
                    <p>This option determines how custom menus interact with existing WordPress menus:</p>
                    <ul>
                        <li><strong>Enabled:</strong> Your custom menus will completely replace any existing menus in WordPress</li>
                        <li><strong>Disabled:</strong> Custom menus will be added alongside existing menus (may cause duplicates)</li>
                    </ul>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Recommended Setting
                        </div>
                        <p><strong>Enable "Replace Existing Menus"</strong> for clean deployments. This ensures your configuration is the single source of truth and prevents conflicts with WordPress auto-generated menus.</p>
                    </div>
                </section>

                <!-- Adding Menu Items -->
                <section class="content-section">
                    <h2><i class="fas fa-plus-circle"></i> Adding Menu Items</h2>

                    <p>Click the <strong>"Add Menu Item"</strong> button to create new navigation links. Each menu item has the following properties:</p>

                    <h3>Menu Item Properties</h3>

                    <div class="card">
                        <h4><i class="fas fa-tag"></i> Label</h4>
                        <p>The text displayed in the navigation menu (e.g., "Home", "About Us", "Contact")</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-link"></i> URL</h4>
                        <p>Where the menu item links to. Options include:</p>
                        <ul>
                            <li><strong>Internal Pages:</strong> <code>/about</code>, <code>/contact</code></li>
                            <li><strong>External Links:</strong> <code>https://example.com</code></li>
                            <li><strong>Homepage:</strong> <code>/</code></li>
                            <li><strong>Anchor Links:</strong> <code>/page#section</code></li>
                        </ul>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-sort"></i> Order</h4>
                        <p>Numerical position in the menu (lower numbers appear first). Example:</p>
                        <ul>
                            <li>Order 1: Home</li>
                            <li>Order 2: About</li>
                            <li>Order 3: Services</li>
                            <li>Order 4: Contact</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-indent"></i> Parent Item (Optional)</h4>
                        <p>Create dropdown/submenu by selecting a parent menu item. Leave blank for top-level items.</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-external-link-alt"></i> Open in New Tab</h4>
                        <p>When enabled, the link opens in a new browser tab. Recommended for external links.</p>
                    </div>
                </section>

                <!-- Creating Hierarchical Menus -->
                <section class="content-section">
                    <h2><i class="fas fa-sitemap"></i> Creating Hierarchical Menus</h2>

                    <p>Build multi-level dropdown menus by assigning parent items:</p>

                    <h3>Example: Two-Level Menu</h3>
                    <div class="card">
                        <pre>
<strong>Top-Level Items:</strong>
- Home (Order: 1, Parent: None)
- About (Order: 2, Parent: None)
- Services (Order: 3, Parent: None)
- Contact (Order: 4, Parent: None)

<strong>Sub-Items (Dropdown under "About"):</strong>
- Our Team (Order: 1, Parent: About)
- Our Mission (Order: 2, Parent: About)
- History (Order: 3, Parent: About)

<strong>Sub-Items (Dropdown under "Services"):</strong>
- Web Design (Order: 1, Parent: Services)
- Marketing (Order: 2, Parent: Services)
- Consulting (Order: 3, Parent: Services)
                        </pre>
                    </div>

                    <p><strong>Result:</strong> Your navigation will show "About" and "Services" with dropdown menus when hovered.</p>
                </section>

                <!-- Menu Locations -->
                <section class="content-section">
                    <h2><i class="fas fa-map-marked-alt"></i> Menu Locations</h2>

                    <p>WordPress themes typically support multiple menu locations. Common locations include:</p>

                    <ul>
                        <li><strong>Primary Menu:</strong> Main navigation in header</li>
                        <li><strong>Secondary Menu:</strong> Top bar or utility navigation</li>
                        <li><strong>Footer Menu:</strong> Links in footer area</li>
                        <li><strong>Mobile Menu:</strong> Responsive navigation for mobile devices</li>
                    </ul>

                    <p>The framework automatically assigns your custom menu to the theme's primary menu location during deployment.</p>
                </section>

                <!-- Best Practices -->
                <section class="content-section">
                    <h2><i class="fas fa-lightbulb"></i> Best Practices</h2>

                    <h3>Menu Structure</h3>
                    <ul>
                        <li><strong>Keep it simple:</strong> 5-7 top-level items is optimal</li>
                        <li><strong>Logical grouping:</strong> Related pages should be grouped under parent items</li>
                        <li><strong>Descriptive labels:</strong> Use clear, concise text (avoid "Click Here")</li>
                        <li><strong>Consistent ordering:</strong> Use increments of 10 (10, 20, 30) to allow easy reordering later</li>
                    </ul>

                    <h3>URL Formatting</h3>
                    <ul>
                        <li><strong>Internal pages:</strong> Use relative URLs starting with <code>/</code> (e.g., <code>/about</code>)</li>
                        <li><strong>External links:</strong> Include full URL with protocol (e.g., <code>https://example.com</code>)</li>
                        <li><strong>Lowercase slugs:</strong> Use <code>/about-us</code> not <code>/About-Us</code></li>
                        <li><strong>Match page slugs:</strong> Ensure URLs match the pages created in Page Editor</li>
                    </ul>

                    <h3>Accessibility</h3>
                    <ul>
                        <li>Use descriptive labels that make sense out of context</li>
                        <li>Limit dropdown levels to 2 (parent → child, not parent → child → grandchild)</li>
                        <li>Ensure mobile menu remains usable with touch navigation</li>
                    </ul>
                </section>

                <!-- Example Configuration -->
                <section class="content-section">
                    <h2><i class="fas fa-code"></i> Example Configuration</h2>

                    <p>Here's a typical menu configuration for a political campaign website:</p>

                    <div class="card">
                        <pre>
<strong>Top-Level Menu Items:</strong>

1. Home
   - Label: "Home"
   - URL: /
   - Order: 10

2. About
   - Label: "About [Candidate Name]"
   - URL: /about
   - Order: 20

3. Issues
   - Label: "Issues"
   - URL: /issues
   - Order: 30

4. News
   - Label: "News & Updates"
   - URL: /news
   - Order: 40

5. Get Involved
   - Label: "Get Involved"
   - URL: /get-involved
   - Order: 50

6. Donate (External)
   - Label: "Donate"
   - URL: https://secure.actblue.com/yourpage
   - Order: 60
   - Open in New Tab: Yes
                        </pre>
                    </div>
                </section>

                <!-- Troubleshooting -->
                <section class="content-section">
                    <h2><i class="fas fa-wrench"></i> Troubleshooting</h2>

                    <h3>Menu Not Appearing</h3>
                    <ul>
                        <li>Verify "Enable Custom Navigation" is toggled ON</li>
                        <li>Check that at least one menu item exists</li>
                        <li>Ensure menu items have valid URLs and labels</li>
                        <li>Redeploy the site to apply changes</li>
                    </ul>

                    <h3>Wrong Menu Order</h3>
                    <ul>
                        <li>Double-check the Order numbers (lower = first)</li>
                        <li>Ensure no duplicate Order values exist</li>
                        <li>Save configuration and redeploy</li>
                    </ul>

                    <h3>Broken Links</h3>
                    <ul>
                        <li>Verify URL format (internal: <code>/page</code>, external: <code>https://...</code>)</li>
                        <li>Check that internal page slugs match pages created in Page Editor</li>
                        <li>Test all links after deployment</li>
                    </ul>

                    <h3>Dropdown Not Working</h3>
                    <ul>
                        <li>Confirm child items have correct Parent Item selected</li>
                        <li>Ensure theme supports multi-level menus</li>
                        <li>Check browser console for JavaScript errors</li>
                    </ul>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="config-integrations.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Integrations Configuration</span>
                    </a>
                    <a href="config-plugins.php" class="btn-nav next">
                        <span>Plugins Configuration</span>
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
