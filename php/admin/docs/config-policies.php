<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policies Configuration - Frontline Framework</title>

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
                        <h1>Policies Configuration</h1>
                        <p>Privacy policy, terms, and password requirements</p>
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
                    <span>Policies</span>
                </nav>

                <h1 class="page-title">Policies Configuration</h1>
                <p class="page-subtitle">Configure password requirements, privacy policies, terms of service, and other legal/compliance settings.</p>

                <!-- Overview -->
                <section class="content-section">
                    <h2><i class="fas fa-file-contract"></i> Overview</h2>

                    <p>The Policies Configuration manages security policies and legal documentation for your WordPress site. This includes:</p>
                    <ul>
                        <li><strong>Password Policy:</strong> Requirements for user passwords</li>
                        <li><strong>Privacy Policy:</strong> GDPR/legal privacy statement</li>
                        <li><strong>Terms of Service:</strong> User agreement terms</li>
                        <li><strong>Cookie Policy:</strong> Cookie usage disclosure</li>
                    </ul>
                </section>

                <!-- Password Policy -->
                <section class="content-section">
                    <h2><i class="fas fa-lock"></i> Password Policy</h2>

                    <p>Define password strength requirements for WordPress user accounts (administrators, editors, contributors, etc.).</p>

                    <h3>Password Requirements</h3>

                    <div class="card">
                        <h4><i class="fas fa-ruler-horizontal"></i> Minimum Length</h4>
                        <p>Set the minimum number of characters required for passwords.</p>
                        <ul>
                            <li><strong>Recommended:</strong> 12-16 characters</li>
                            <li><strong>Minimum:</strong> 8 characters (security baseline)</li>
                            <li><strong>Maximum:</strong> 50 characters</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-font"></i> Require Uppercase Letters</h4>
                        <p>Force passwords to contain at least one uppercase letter (A-Z).</p>
                        <p><strong>Example:</strong> <code>Password123!</code> ✅ | <code>password123!</code> ❌</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-font"></i> Require Lowercase Letters</h4>
                        <p>Force passwords to contain at least one lowercase letter (a-z).</p>
                        <p><strong>Example:</strong> <code>Password123!</code> ✅ | <code>PASSWORD123!</code> ❌</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-hashtag"></i> Require Numbers</h4>
                        <p>Force passwords to contain at least one numeric digit (0-9).</p>
                        <p><strong>Example:</strong> <code>Password123!</code> ✅ | <code>Password!</code> ❌</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-asterisk"></i> Require Special Characters</h4>
                        <p>Force passwords to contain at least one special character (!@#$%^&*...).</p>
                        <p><strong>Example:</strong> <code>Password123!</code> ✅ | <code>Password123</code> ❌</p>
                    </div>

                    <h3>Recommended Settings</h3>
                    <div class="alert alert-success">
                        <div class="alert-title">
                            <i class="fas fa-shield-alt"></i>
                            Strong Password Policy (Recommended)
                        </div>
                        <ul>
                            <li><strong>Minimum Length:</strong> 12 characters</li>
                            <li><strong>Require Uppercase:</strong> ✅ Enabled</li>
                            <li><strong>Require Lowercase:</strong> ✅ Enabled</li>
                            <li><strong>Require Numbers:</strong> ✅ Enabled</li>
                            <li><strong>Require Special Characters:</strong> ✅ Enabled</li>
                        </ul>
                        <p><strong>Example Valid Password:</strong> <code>MySecure#Pass2024</code></p>
                    </div>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Important Notes
                        </div>
                        <ul>
                            <li>Password policies apply to new passwords only (existing users keep current passwords)</li>
                            <li>To enforce on existing users, they must change passwords in WordPress wp-admin</li>
                            <li>Strong password policies significantly reduce brute-force attack success</li>
                        </ul>
                    </div>
                </section>

                <!-- Privacy Policy -->
                <section class="content-section">
                    <h2><i class="fas fa-user-shield"></i> Privacy Policy</h2>

                    <p>A privacy policy is legally required in many jurisdictions (especially under GDPR) if you collect any user data.</p>

                    <h3>When You Need a Privacy Policy</h3>
                    <ul>
                        <li>You have contact forms that collect names/emails</li>
                        <li>You use Google Analytics or tracking cookies</li>
                        <li>You have user registration/login functionality</li>
                        <li>You display ads or use third-party services</li>
                        <li>You're in or serve visitors from the EU (GDPR)</li>
                    </ul>

                    <h3>What to Include</h3>
                    <p>Your privacy policy should cover:</p>
                    <ul>
                        <li><strong>Data Collection:</strong> What information you collect (names, emails, IP addresses, etc.)</li>
                        <li><strong>Usage:</strong> How you use collected data</li>
                        <li><strong>Storage:</strong> Where data is stored and for how long</li>
                        <li><strong>Sharing:</strong> If data is shared with third parties (Google, email providers, etc.)</li>
                        <li><strong>User Rights:</strong> How users can access, modify, or delete their data</li>
                        <li><strong>Cookies:</strong> What cookies you use and why</li>
                        <li><strong>Contact:</strong> How users can contact you about privacy concerns</li>
                    </ul>

                    <h3>Configuration</h3>
                    <p>You can:</p>
                    <ul>
                        <li><strong>Upload policy document:</strong> Upload HTML or text file</li>
                        <li><strong>Link to external policy:</strong> Use a URL to your policy hosted elsewhere</li>
                        <li><strong>Write inline:</strong> Enter policy text directly in the form</li>
                    </ul>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-info-circle"></i>
                            Privacy Policy Generators
                        </div>
                        <p>Free privacy policy generators:</p>
                        <ul>
                            <li><a href="https://www.termsfeed.com/privacy-policy-generator/" target="_blank">TermsFeed Privacy Policy Generator</a></li>
                            <li><a href="https://www.freeprivacypolicy.com/" target="_blank">Free Privacy Policy Generator</a></li>
                            <li><a href="https://www.iubenda.com/en/privacy-and-cookie-policy-generator" target="_blank">Iubenda Privacy Policy Generator</a></li>
                        </ul>
                        <p><strong>Note:</strong> These are starting points. Consider legal review for compliance.</p>
                    </div>
                </section>

                <!-- Terms of Service -->
                <section class="content-section">
                    <h2><i class="fas fa-file-alt"></i> Terms of Service</h2>

                    <p>Terms of Service (ToS) outline the rules and guidelines for using your website.</p>

                    <h3>When You Need Terms of Service</h3>
                    <ul>
                        <li>You have user-generated content (comments, forums, reviews)</li>
                        <li>You sell products or services online</li>
                        <li>You have membership or subscription features</li>
                        <li>You want to limit your legal liability</li>
                    </ul>

                    <h3>What to Include</h3>
                    <ul>
                        <li><strong>Acceptance:</strong> By using the site, users agree to these terms</li>
                        <li><strong>User Conduct:</strong> Prohibited activities (spam, abuse, illegal content)</li>
                        <li><strong>Intellectual Property:</strong> Copyright and trademark notices</li>
                        <li><strong>Disclaimers:</strong> Limitations of liability and warranties</li>
                        <li><strong>Termination:</strong> Your right to suspend/terminate accounts</li>
                        <li><strong>Governing Law:</strong> Which jurisdiction's laws apply</li>
                    </ul>
                </section>

                <!-- Cookie Policy -->
                <section class="content-section">
                    <h2><i class="fas fa-cookie-bite"></i> Cookie Policy</h2>

                    <p>A cookie policy explains what cookies your site uses and why.</p>

                    <h3>Types of Cookies</h3>
                    <div class="card">
                        <h4><i class="fas fa-cog"></i> Essential Cookies</h4>
                        <p>Required for site functionality (login sessions, shopping carts)</p>
                        <p><strong>Example:</strong> WordPress session cookies</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-chart-line"></i> Analytics Cookies</h4>
                        <p>Track visitor behavior for analytics (Google Analytics)</p>
                        <p><strong>Example:</strong> _ga, _gid cookies</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-bullseye"></i> Marketing Cookies</h4>
                        <p>Used for advertising and retargeting</p>
                        <p><strong>Example:</strong> Facebook Pixel, Google Ads cookies</p>
                    </div>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-gavel"></i>
                            GDPR & Cookie Consent
                        </div>
                        <p>Under GDPR (EU) and similar laws:</p>
                        <ul>
                            <li>You must get user consent before setting non-essential cookies</li>
                            <li>Cookie consent banners should offer "Accept" and "Reject" options</li>
                            <li>Users must be able to withdraw consent easily</li>
                            <li>Consider using a cookie consent plugin (e.g., Cookie Notice, GDPR Cookie Compliance)</li>
                        </ul>
                    </div>
                </section>

                <!-- Best Practices -->
                <section class="content-section">
                    <h2><i class="fas fa-lightbulb"></i> Best Practices</h2>

                    <h3>Password Policy</h3>
                    <ul>
                        <li>Enforce strong passwords for admin accounts at minimum</li>
                        <li>Consider 2FA (Two-Factor Authentication) for additional security</li>
                        <li>Educate users on creating strong, unique passwords</li>
                        <li>Recommend password managers to users</li>
                    </ul>

                    <h3>Legal Policies</h3>
                    <ul>
                        <li><strong>Keep updated:</strong> Review and update policies annually</li>
                        <li><strong>Make accessible:</strong> Link policies in footer of every page</li>
                        <li><strong>Use clear language:</strong> Avoid excessive legal jargon</li>
                        <li><strong>Notify changes:</strong> Inform users when policies change</li>
                        <li><strong>Get legal review:</strong> Have policies reviewed by attorney for compliance</li>
                    </ul>

                    <h3>Compliance</h3>
                    <ul>
                        <li><strong>GDPR (EU):</strong> Privacy policy, cookie consent, data deletion requests</li>
                        <li><strong>CCPA (California):</strong> Disclose data collection and allow opt-out</li>
                        <li><strong>COPPA (US):</strong> If targeting children under 13, special requirements apply</li>
                        <li><strong>ADA (Accessibility):</strong> Ensure policies are readable by screen readers</li>
                    </ul>
                </section>

                <!-- Troubleshooting -->
                <section class="content-section">
                    <h2><i class="fas fa-wrench"></i> Troubleshooting</h2>

                    <h3>Password Policy Not Enforcing</h3>
                    <ul>
                        <li>Verify settings are saved in configuration</li>
                        <li>Redeploy site to apply policy</li>
                        <li>Check if security plugin is overriding settings</li>
                        <li>Test with new user registration (not existing users)</li>
                    </ul>

                    <h3>Policy Pages Not Displaying</h3>
                    <ul>
                        <li>Ensure policy content is not empty</li>
                        <li>Check if policy pages are created in WordPress</li>
                        <li>Verify links in footer or navigation point to correct pages</li>
                        <li>Clear site cache and browser cache</li>
                    </ul>

                    <h3>Cookie Consent Banner Not Showing</h3>
                    <ul>
                        <li>Install a cookie consent plugin (Policies config doesn't include banner)</li>
                        <li>Configure plugin to display banner according to your cookie policy</li>
                        <li>Test in incognito/private browsing mode</li>
                    </ul>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="config-plugins.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Plugins Configuration</span>
                    </a>
                    <a href="config-theme.php" class="btn-nav next">
                        <span>Theme Configuration</span>
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
