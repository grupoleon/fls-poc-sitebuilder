<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integrations Configuration - Frontline Framework</title>

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
                        <h1>Integrations Configuration</h1>
                        <p>Connect external services and tools</p>
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
                    <span>Integrations</span>
                </nav>

                <h1 class="page-title">Integrations Configuration</h1>
                <p class="page-subtitle">Connect your WordPress site with third-party services including analytics, forms, maps, and social media platforms.</p>

                <!-- Analytics Integration -->
                <section class="content-section">
                    <h2><i class="fas fa-chart-bar"></i> Analytics Integration</h2>

                    <p>Track your website traffic and visitor behavior using Google Analytics.</p>

                    <h3>Configuration Steps</h3>
                    <ol>
                        <li><strong>Enable Analytics:</strong> Toggle the "Enable Analytics Integration" switch to activate Google Analytics tracking</li>
                        <li><strong>Enter Tracking ID:</strong> Paste your Google Analytics tracking ID (format: <code>G-XXXXXXXXXX</code>)</li>
                        <li>The tracking code will be automatically injected into all pages during deployment</li>
                    </ol>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-info-circle"></i>
                            Getting Your Tracking ID
                        </div>
                        <p>To get your Google Analytics tracking ID:</p>
                        <ol>
                            <li>Log into <a href="https://analytics.google.com" target="_blank">Google Analytics</a></li>
                            <li>Create a new property for your website</li>
                            <li>Copy the Measurement ID (starts with "G-")</li>
                        </ol>
                    </div>
                </section>

                <!-- Forms Integration -->
                <section class="content-section">
                    <h2><i class="fas fa-envelope"></i> Forms Integration</h2>

                    <p>Automatically place Forminator forms on your pages based on placeholder detection or manual configuration.</p>

                    <h3>Configuration Options</h3>

                    <div class="card">
                        <h4><i class="fas fa-toggle-on"></i> Enable Forms Integration</h4>
                        <p>Activates automatic form placement on pages during deployment.</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-search-location"></i> Auto-Find Form Placements</h4>
                        <p>When enabled, the system will:</p>
                        <ul>
                            <li>Scan page layouts for form placeholder tags</li>
                            <li>Automatically match forms from Forms Manager to their designated locations</li>
                            <li>Place forms without manual page-by-page configuration</li>
                        </ul>
                    </div>

                    <h3>How Form Placement Works</h3>
                    <ol>
                        <li>Create forms in the <strong>Forms Manager</strong> tab</li>
                        <li>In your page layouts (Page Editor), add form placeholders (e.g., <code>[contact-form]</code>)</li>
                        <li>Enable "Auto-Find Form Placements"</li>
                        <li>During deployment, the system matches form names to placeholders and injects the correct form shortcodes</li>
                    </ol>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Manual vs. Automatic Placement
                        </div>
                        <p>If "Auto-Find Form Placements" is disabled, you must manually configure form placements for each page in the Configuration &gt; Integrations &gt; Dynamic Forms section.</p>
                    </div>
                </section>

                <!-- Social Media Links -->
                <section class="content-section">
                    <h2><i class="fas fa-share-alt"></i> Social Media Links</h2>

                    <p>Add social media profile links that appear on your website (typically in footer or header).</p>

                    <h3>Supported Platforms</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="card">
                            <h4><i class="fab fa-facebook"></i> Facebook</h4>
                            <p>Enter your Facebook page URL</p>
                            <code>https://facebook.com/yourpage</code>
                        </div>
                        <div class="card">
                            <h4><i class="fab fa-twitter"></i> Twitter/X</h4>
                            <p>Enter your Twitter/X profile URL</p>
                            <code>https://twitter.com/youraccount</code>
                        </div>
                        <div class="card">
                            <h4><i class="fab fa-instagram"></i> Instagram</h4>
                            <p>Enter your Instagram profile URL</p>
                            <code>https://instagram.com/youraccount</code>
                        </div>
                        <div class="card">
                            <h4><i class="fab fa-linkedin"></i> LinkedIn</h4>
                            <p>Enter your LinkedIn profile URL</p>
                            <code>https://linkedin.com/in/yourprofile</code>
                        </div>
                    </div>

                    <h3>Placement Options</h3>
                    <p>Select where social media icons should appear on your site:</p>
                    <ul>
                        <li><strong>Footer:</strong> Most common - appears at bottom of all pages</li>
                        <li><strong>Header:</strong> Appears in top navigation area</li>
                        <li><strong>Specific Page:</strong> Only on selected page(s)</li>
                    </ul>
                </section>

                <!-- Google Maps Integration -->
                <section class="content-section">
                    <h2><i class="fas fa-map-marker-alt"></i> Google Maps Integration</h2>

                    <p>Embed interactive Google Maps with custom markers on your pages using the WP Go Maps plugin.</p>

                    <h3>Setup Process</h3>

                    <h4>1. Get Google Maps API Key</h4>
                    <ol>
                        <li>Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a></li>
                        <li>Create a new project or select existing one</li>
                        <li>Enable "Maps JavaScript API" and "Geocoding API"</li>
                        <li>Create API credentials and copy the API key</li>
                        <li>Paste the key in the "Google Maps API Key" field</li>
                    </ol>

                    <div class="alert alert-danger">
                        <div class="alert-title">
                            <i class="fas fa-shield-alt"></i>
                            API Key Security
                        </div>
                        <p><strong>Important:</strong> Restrict your API key to your domain only in Google Cloud Console to prevent unauthorized usage and unexpected charges.</p>
                    </div>

                    <h4>2. Configure Map Settings</h4>
                    <ul>
                        <li><strong>Center Latitude/Longitude:</strong> Coordinates where the map centers (e.g., <code>38.8977, -77.0365</code>)</li>
                        <li><strong>Zoom Level:</strong> Initial zoom (1 = world view, 20 = street level)</li>
                        <li><strong>Map Placement:</strong> Choose which page the map appears on</li>
                    </ul>

                    <h4>3. Add Markers</h4>
                    <p>Markers are pins that show locations on the map. To add markers:</p>
                    <ol>
                        <li>Click on the map preview where you want the marker</li>
                        <li>Or use the "Add Marker" button</li>
                        <li>Enter marker details:
                            <ul>
                                <li><strong>Title:</strong> Name shown on hover</li>
                                <li><strong>Address:</strong> Full street address</li>
                                <li><strong>Description:</strong> Info window text (optional)</li>
                            </ul>
                        </li>
                    </ol>

                    <h4>4. Auto-Find Map Placements</h4>
                    <p>Similar to forms, enable this option to automatically detect map placeholders in page layouts and place maps accordingly.</p>
                </section>

                <!-- Best Practices -->
                <section class="content-section">
                    <h2><i class="fas fa-lightbulb"></i> Best Practices</h2>

                    <h3>Analytics</h3>
                    <ul>
                        <li>Set up conversion goals in Google Analytics after deployment</li>
                        <li>Verify tracking is working using Google Analytics Realtime reports</li>
                        <li>Consider GDPR compliance if serving European users</li>
                    </ul>

                    <h3>Forms</h3>
                    <ul>
                        <li>Test form submissions immediately after deployment</li>
                        <li>Configure email notifications for form submissions</li>
                        <li>Enable spam protection (reCAPTCHA) for public-facing forms</li>
                    </ul>

                    <h3>Maps</h3>
                    <ul>
                        <li>Use descriptive marker titles and addresses</li>
                        <li>Set appropriate zoom levels (12-15 works well for city/neighborhood views)</li>
                        <li>Test map loading on mobile devices</li>
                        <li>Monitor Google Maps API usage to avoid unexpected costs</li>
                    </ul>

                    <h3>Social Media</h3>
                    <ul>
                        <li>Use official profile URLs (not shortened links)</li>
                        <li>Verify all links work before deployment</li>
                        <li>Only include active social media accounts</li>
                    </ul>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="config-security.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Security Configuration</span>
                    </a>
                    <a href="config-navigation.php" class="btn-nav next">
                        <span>Navigation & Menus</span>
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
