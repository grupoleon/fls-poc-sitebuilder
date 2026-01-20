<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glossary - Frontline Framework</title>

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
                        <h1>Glossary</h1>
                        <p>Terms and definitions</p>
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
                    <a href="index.php#technical">Technical Reference</a>
                    <span>/</span>
                    <span>Glossary</span>
                </nav>

                <h1 class="page-title">Glossary of Terms</h1>
                <p class="page-subtitle">Definitions of key terms and concepts used throughout the Frontline Framework.</p>

                <!-- Overview -->
                <section class="content-section">
                    <h2><i class="fas fa-book"></i> Overview</h2>
                    <p>This glossary provides clear definitions of technical terms, acronyms, and concepts you'll encounter while using the Frontline Framework. Terms are organized alphabetically within categories for easy reference.</p>
                </section>

                <!-- Core Concepts -->
                <section class="content-section">
                    <h2><i class="fas fa-lightbulb"></i> Core Concepts</h2>

                    <div class="glossary-term">
                        <h3>Pre-deployment Configuration</h3>
                        <p>The process of setting up and configuring a WordPress website <strong>before</strong> it goes live. Unlike traditional WordPress admin panels (wp-admin) which manage live sites, pre-deployment configuration happens in this interface where you define all settings, content, and design choices. Once configured, the entire site is deployed to a live server in a single automated process.</p>
                        <div class="alert alert-info">
                            <strong>Key Difference:</strong> You're not editing a live site - you're building a blueprint that will be deployed.
                        </div>
                    </div>

                    <div class="glossary-term">
                        <h3>Deployment Pipeline</h3>
                        <p>An automated sequence of steps that takes your configuration from this interface and creates a live WordPress website. The pipeline includes: generating JSON configuration files → committing to Git → pushing to GitHub → triggering GitHub Actions → syncing files to Kinsta → running WP-CLI scripts → installing theme → importing content → clearing cache.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>Repository</h3>
                        <p>A Git-based storage location (typically on GitHub) that holds all deployment files, scripts, themes, and configuration data. Think of it as a central hub where your site's "source code" lives. The repository connects this interface to the Kinsta hosting server via GitHub Actions.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>JSON Configuration</h3>
                        <p>Structured data files in JSON (JavaScript Object Notation) format that store all your site settings, content, and preferences. These files are machine-readable and version-controlled, making it easy to track changes, rollback if needed, and automate deployments. Examples include <code>site.json</code>, <code>theme-config.json</code>, and <code>forms-config.json</code>.</p>
                    </div>
                </section>

                <!-- Hosting & Infrastructure -->
                <section class="content-section">
                    <h2><i class="fas fa-server"></i> Hosting & Infrastructure</h2>

                    <div class="glossary-term">
                        <h3>Kinsta Hosting</h3>
                        <p>A premium managed WordPress hosting provider powered by Google Cloud Platform. Kinsta provides enterprise-level performance, security, and scalability. The Frontline Framework uses Kinsta's API to programmatically create WordPress installations, configure server settings, and manage deployments without manual intervention.</p>
                        <ul>
                            <li><strong>Managed Hosting:</strong> Server maintenance, updates, and security are handled automatically</li>
                            <li><strong>CDN Integration:</strong> Built-in content delivery network for fast global performance</li>
                            <li><strong>Staging Environments:</strong> Test changes before pushing to production</li>
                            <li><strong>API Access:</strong> Automate site creation and configuration</li>
                        </ul>
                    </div>

                    <div class="glossary-term">
                        <h3>SSH (Secure Shell)</h3>
                        <p>A cryptographic network protocol for secure remote access to servers. Kinsta provides SSH access for advanced users to execute commands directly on the server, such as running WP-CLI commands, troubleshooting issues, or performing manual database operations. SSH access requires authentication via public/private key pairs.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>GitHub Actions</h3>
                        <p>A CI/CD (Continuous Integration/Continuous Deployment) automation platform built into GitHub. When you deploy from this interface, it triggers a GitHub Actions workflow that:</p>
                        <ul>
                            <li>Checks out the repository code</li>
                            <li>Syncs theme files to Kinsta servers</li>
                            <li>Executes deployment scripts in the correct order</li>
                            <li>Reports success or failure status</li>
                        </ul>
                        <p>GitHub Actions runs in the cloud, so deployments happen automatically without your computer needing to stay online.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>Personal Access Token (PAT)</h3>
                        <p>An authentication credential used instead of a password for accessing GitHub's API. PATs provide fine-grained permissions (scopes) so you can limit what the token can do. For the Frontline Framework, your PAT needs <code>repo</code>, <code>workflow</code>, and <code>read:org</code> scopes to push configurations, trigger deployments, and list repositories.</p>
                        <div class="alert alert-warning">
                            <strong>Security Note:</strong> Treat PATs like passwords - never share them or commit them to repositories.
                        </div>
                    </div>
                </section>

                <!-- WordPress Components -->
                <section class="content-section">
                    <h2><i class="fab fa-wordpress"></i> WordPress Components</h2>

                    <div class="glossary-term">
                        <h3>WordPress Admin (wp-admin)</h3>
                        <p>The traditional WordPress administration dashboard accessed via <code>yoursite.com/wp-admin</code>. This is where users manage live WordPress sites by creating posts, installing plugins, and changing settings. <strong>Important:</strong> The Frontline Framework interface is <em>not</em> wp-admin - it's a pre-deployment configuration tool that generates the site before it goes live.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>WP-CLI (WordPress Command Line Interface)</h3>
                        <p>A set of command-line tools for managing WordPress installations without using a web browser. The Frontline Framework deployment scripts use WP-CLI extensively to:</p>
                        <ul>
                            <li>Install and activate themes/plugins: <code>wp theme activate BurBank</code></li>
                            <li>Create posts and pages: <code>wp post create --post_type=page --post_title="About"</code></li>
                            <li>Update options: <code>wp option update siteurl "https://example.com"</code></li>
                            <li>Clear caches: <code>wp cache flush</code></li>
                            <li>Import content: <code>wp post create --porcelain &lt; post-data.json</code></li>
                        </ul>
                        <p>WP-CLI is ideal for automation because it's fast, reliable, and doesn't require a GUI.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>Theme</h3>
                        <p>A collection of template files that controls the visual appearance and functionality of a WordPress site. Themes include PHP templates, CSS stylesheets, JavaScript files, and images. The Frontline Framework includes pre-built themes like BurBank, Candidate, Political, and Reform, each designed for specific use cases (candidates, advocacy groups, speakers, etc.).</p>
                    </div>

                    <div class="glossary-term">
                        <h3>Plugin</h3>
                        <p>A software extension that adds specific features to WordPress without modifying core files. Common plugins used by Frontline Framework include:</p>
                        <ul>
                            <li><strong>Forminator:</strong> Form builder for contact forms, volunteer signups, etc.</li>
                            <li><strong>Wordfence:</strong> Security plugin with firewall and malware scanning</li>
                            <li><strong>SiteOrigin Page Builder:</strong> Drag-and-drop page layout builder</li>
                            <li><strong>Slider Revolution:</strong> Advanced slider/carousel creator</li>
                        </ul>
                    </div>

                    <div class="glossary-term">
                        <h3>Widget</h3>
                        <p>A small block of content that can be placed in widget-ready areas (sidebars, footers) of a WordPress theme. Widgets can display recent posts, custom menus, search boxes, social media feeds, or custom HTML. In SiteOrigin Page Builder context, widgets are building blocks used to construct page layouts.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>Shortcode</h3>
                        <p>A WordPress-specific code snippet enclosed in square brackets (e.g., <code>[contact-form-7 id="123"]</code>) that embeds complex functionality into posts or pages. Shortcodes act as placeholders that get replaced with the actual content when the page renders. Common examples: forms, galleries, maps, and custom widgets.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>Custom Post Types (CPT)</h3>
                        <p>Content types beyond the default WordPress "Posts" and "Pages." The Frontline Framework uses CPTs to organize different kinds of content:</p>
                        <ul>
                            <li><strong>News:</strong> Press releases and announcements</li>
                            <li><strong>Testimonials:</strong> Supporter quotes and endorsements</li>
                            <li><strong>Issues:</strong> Policy positions and campaign issues</li>
                            <li><strong>Endorsements:</strong> Organizational endorsements</li>
                            <li><strong>Posts:</strong> Blog articles</li>
                        </ul>
                        <p>Each CPT can have custom fields, taxonomies, and display templates.</p>
                    </div>
                </section>

                <!-- Page Builders & Content -->
                <section class="content-section">
                    <h2><i class="fas fa-tools"></i> Page Builders & Content Tools</h2>

                    <div class="glossary-term">
                        <h3>SiteOrigin Page Builder</h3>
                        <p>A free, open-source drag-and-drop page builder plugin for WordPress. Unlike proprietary builders (Elementor, Divi), SiteOrigin generates clean, semantic HTML that doesn't break if you deactivate the plugin. Key features:</p>
                        <ul>
                            <li><strong>Rows & Columns:</strong> Grid-based layout system</li>
                            <li><strong>Widgets:</strong> Reusable content blocks (hero sections, buttons, galleries)</li>
                            <li><strong>Responsive Design:</strong> Mobile-friendly layouts</li>
                            <li><strong>JSON Export:</strong> Layouts stored as JSON data for version control</li>
                        </ul>
                        <p>The Frontline Framework uses SiteOrigin exclusively for all themes.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>Forminator</h3>
                        <p>A WordPress form builder plugin that creates contact forms, surveys, polls, and quizzes. Forminator supports:</p>
                        <ul>
                            <li>Multi-step forms</li>
                            <li>Conditional logic (show/hide fields based on user input)</li>
                            <li>Email notifications</li>
                            <li>Third-party integrations (Mailchimp, Google Sheets, Zapier)</li>
                            <li>reCAPTCHA spam protection</li>
                        </ul>
                        <p>Forms are configured in this interface and imported during deployment.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>Wordfence</h3>
                        <p>A comprehensive WordPress security plugin that provides:</p>
                        <ul>
                            <li><strong>Firewall:</strong> Blocks malicious traffic before it reaches WordPress</li>
                            <li><strong>Malware Scanning:</strong> Checks files for known threats</li>
                            <li><strong>Login Security:</strong> Two-factor authentication, brute force protection</li>
                            <li><strong>Traffic Monitoring:</strong> Real-time view of visitors and login attempts</li>
                        </ul>
                        <p>Wordfence settings are configured automatically during deployment.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>Demo Content</h3>
                        <p>Pre-built example content (pages, posts, images, forms) that demonstrates how a theme should look when properly configured. Demo content serves as:</p>
                        <ul>
                            <li>A visual preview of theme capabilities</li>
                            <li>Starting point for customization (replace placeholder text/images)</li>
                            <li>Reference for layout structure</li>
                        </ul>
                        <p>All Frontline Framework themes include demo content stored as JSON files in the theme's <code>demo-data/</code> directory.</p>
                    </div>
                </section>

                <!-- Security & Protection -->
                <section class="content-section">
                    <h2><i class="fas fa-shield-alt"></i> Security & Protection</h2>

                    <div class="glossary-term">
                        <h3>Geo-blocking</h3>
                        <p>A security technique that restricts website access based on geographic location (country or region). Geo-blocking is useful for:</p>
                        <ul>
                            <li>Reducing spam from high-risk countries</li>
                            <li>Complying with regional legal requirements</li>
                            <li>Blocking botnet traffic</li>
                            <li>Targeting content to specific audiences</li>
                        </ul>
                        <p>The Frontline Framework implements geo-blocking via:</p>
                        <ul>
                            <li><strong>Cloudflare Workers:</strong> Block traffic at the edge (fastest, recommended)</li>
                            <li><strong>.htaccess Rules:</strong> Server-side blocking (fallback method)</li>
                        </ul>
                        <div class="alert alert-warning">
                            <strong>Note:</strong> Geo-blocking is based on IP address databases, which aren't 100% accurate. Use cautiously.
                        </div>
                    </div>
                </section>

                <!-- File Formats & Data -->
                <section class="content-section">
                    <h2><i class="fas fa-file-code"></i> File Formats & Data Structures</h2>

                    <div class="glossary-term">
                        <h3>JSON (JavaScript Object Notation)</h3>
                        <p>A lightweight, human-readable data format used to store and exchange structured information. JSON consists of key-value pairs and arrays:</p>
                        <pre><code>{
    "site": {
        "name": "Campaign 2026",
        "url": "https://example.com"
    },
    "theme": "BurBank",
    "features": ["blog", "donations", "events"]
}</code></pre>
                        <p>All Frontline Framework configurations are stored as JSON files.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>YAML (YAML Ain't Markup Language)</h3>
                        <p>A human-friendly data serialization format often used for configuration files. GitHub Actions workflows use YAML syntax:</p>
                        <pre><code>name: Deploy to Kinsta
on:
  repository_dispatch:
    types: [deploy_site]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3</code></pre>
                    </div>

                    <div class="glossary-term">
                        <h3>.htaccess</h3>
                        <p>Apache web server configuration file that controls URL rewrites, redirects, access restrictions, and security rules. Common uses:</p>
                        <ul>
                            <li>Forcing HTTPS: <code>RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]</code></li>
                            <li>Blocking IPs: <code>Deny from 123.456.789.0</code></li>
                            <li>Custom error pages: <code>ErrorDocument 404 /404.php</code></li>
                            <li>Geo-blocking: <code>SetEnvIf GEOIP_COUNTRY_CODE ^(CN|RU) DenyAccess</code></li>
                        </ul>
                    </div>
                </section>

                <!-- Development & Version Control -->
                <section class="content-section">
                    <h2><i class="fas fa-code-branch"></i> Development & Version Control</h2>

                    <div class="glossary-term">
                        <h3>Git</h3>
                        <p>A distributed version control system that tracks changes to files over time. Git allows you to:</p>
                        <ul>
                            <li>Save snapshots (commits) of your project at different points</li>
                            <li>Revert to previous versions if something breaks</li>
                            <li>Collaborate with team members without conflicts</li>
                            <li>Create branches for experimental features</li>
                        </ul>
                        <p>Every deployment creates a Git commit with a timestamped message.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>GitHub</h3>
                        <p>A cloud-based platform for hosting Git repositories with collaboration features like pull requests, issue tracking, and CI/CD (via GitHub Actions). The Frontline Framework uses GitHub as the central hub connecting this interface to Kinsta hosting.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>Branch</h3>
                        <p>An independent line of development in a Git repository. The main branch (often called <code>main</code> or <code>master</code>) holds production-ready code, while feature branches allow you to work on changes without affecting the main codebase. The Frontline Framework typically deploys from the <code>main</code> branch.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>Commit</h3>
                        <p>A snapshot of your repository at a specific point in time, saved with a descriptive message. Each deployment creates a commit like: <code>"Deploy: Updated site configuration - 2026-01-20 15:30:45"</code></p>
                    </div>

                    <div class="glossary-term">
                        <h3>Push</h3>
                        <p>The act of sending local Git commits to a remote repository (GitHub). When you deploy, the interface commits changes locally, then pushes to GitHub, which triggers the deployment workflow.</p>
                    </div>

                    <div class="glossary-term">
                        <h3>API (Application Programming Interface)</h3>
                        <p>A set of rules and protocols that allow different software systems to communicate. The Frontline Framework uses several APIs:</p>
                        <ul>
                            <li><strong>GitHub API:</strong> Create commits, trigger workflows, list repositories</li>
                            <li><strong>Kinsta API:</strong> Create sites, configure settings, retrieve site info</li>
                            <li><strong>WordPress REST API:</strong> Interact with WordPress data programmatically</li>
                        </ul>
                    </div>
                </section>

                <!-- Acronyms & Abbreviations -->
                <section class="content-section">
                    <h2><i class="fas fa-list"></i> Common Acronyms</h2>

                    <div class="glossary-grid">
                        <div class="glossary-term-compact">
                            <strong>CI/CD</strong>
                            <p>Continuous Integration / Continuous Deployment - automated testing and deployment workflows</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>CDN</strong>
                            <p>Content Delivery Network - distributed servers that cache static content for faster global delivery</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>CLI</strong>
                            <p>Command Line Interface - text-based interface for executing commands</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>CPT</strong>
                            <p>Custom Post Type - content types beyond default WordPress posts/pages</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>DNS</strong>
                            <p>Domain Name System - translates domain names (example.com) to IP addresses</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>FTP</strong>
                            <p>File Transfer Protocol - method for uploading files to servers (less secure than SFTP)</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>GUI</strong>
                            <p>Graphical User Interface - visual interface with buttons, menus, and windows</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>HTML</strong>
                            <p>HyperText Markup Language - standard language for creating web pages</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>HTTP/HTTPS</strong>
                            <p>HyperText Transfer Protocol (Secure) - protocol for transmitting web pages</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>IDE</strong>
                            <p>Integrated Development Environment - software for writing and debugging code</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>IP Address</strong>
                            <p>Internet Protocol Address - unique numerical identifier for devices on a network</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>JSON</strong>
                            <p>JavaScript Object Notation - lightweight data format</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>PAT</strong>
                            <p>Personal Access Token - authentication credential for API access</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>PHP</strong>
                            <p>Hypertext Preprocessor - server-side scripting language (WordPress is built with PHP)</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>REST</strong>
                            <p>Representational State Transfer - architectural style for designing APIs</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>SFTP</strong>
                            <p>Secure File Transfer Protocol - encrypted method for transferring files</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>SQL</strong>
                            <p>Structured Query Language - language for managing databases</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>SSH</strong>
                            <p>Secure Shell - encrypted protocol for remote server access</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>SSL/TLS</strong>
                            <p>Secure Sockets Layer / Transport Layer Security - encryption protocols for HTTPS</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>UI/UX</strong>
                            <p>User Interface / User Experience - design and usability of software</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>URL</strong>
                            <p>Uniform Resource Locator - web address (e.g., https://example.com/page)</p>
                        </div>

                        <div class="glossary-term-compact">
                            <strong>YAML</strong>
                            <p>YAML Ain't Markup Language - human-readable data format</p>
                        </div>
                    </div>
                </section>

                <!-- Related Resources -->
                <section class="content-section">
                    <h2><i class="fas fa-link"></i> Related Resources</h2>
                    <ul>
                        <li><a href="introduction.php">Introduction to Frontline Framework</a></li>
                        <li><a href="json-format.php">JSON Configuration Schemas</a></li>
                        <li><a href="scripts-reference.php">Bash Scripts Reference</a></li>
                        <li><a href="deployment-flow.php">Technical Deployment Workflow</a></li>
                        <li><a href="architecture.php">System Architecture</a></li>
                    </ul>
                </section>

                <!-- Navigation -->
                <nav class="doc-nav">
                    <a href="troubleshooting.php" class="nav-prev">
                        <i class="fas fa-arrow-left"></i>
                        <div>
                            <span>Previous</span>
                            <strong>Troubleshooting</strong>
                        </div>
                    </a>
                    <a href="json-format.php" class="nav-next">
                        <div>
                            <span>Next</span>
                            <strong>JSON Configuration</strong>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </nav>
            </div>
        </main>
    </div>

    <style>
    .glossary-term {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }

    .glossary-term:last-child {
        border-bottom: none;
    }

    .glossary-term h3 {
        color: var(--primary-color);
        margin-bottom: 0.75rem;
        font-size: 1.25rem;
    }

    .glossary-term p {
        margin-bottom: 0.75rem;
    }

    .glossary-term ul {
        margin-left: 1.5rem;
        margin-bottom: 0.75rem;
    }

    .glossary-term code {
        background: var(--bg-tertiary);
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.9em;
        color: var(--primary-dark);
    }

    .glossary-term pre {
        background: var(--bg-tertiary);
        padding: 1rem;
        border-radius: 0.5rem;
        overflow-x: auto;
        margin: 0.75rem 0;
    }

    .glossary-term pre code {
        background: none;
        padding: 0;
    }

    .glossary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }

    .glossary-term-compact {
        background: var(--bg-secondary);
        padding: 1rem;
        border-radius: 0.5rem;
        border-left: 3px solid var(--primary-color);
    }

    .glossary-term-compact strong {
        display: block;
        color: var(--primary-color);
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }

    .glossary-term-compact p {
        margin: 0;
        font-size: 0.9rem;
        color: var(--text-medium);
    }
    </style>
</body>
</html>
