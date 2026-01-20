<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forms Configuration - Frontline Framework</title>

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
                        <h1>Forms Configuration</h1>
                        <p>Configure Forminator forms for your website</p>
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
                    <span>Forms Configuration</span>
                </nav>

                <h1 class="page-title">Forms Configuration</h1>
                <p class="page-subtitle">Configure Forminator forms that will be created during deployment.</p>

                <!-- Overview -->
                <section class="content-section">
                    <h2><i class="fas fa-file-lines"></i> Overview</h2>

                    <p>The Forms Manager configures Forminator forms that will be created during deployment. Form configurations are saved in /pages/forms/ directory as JSON files.</p>
                </section>

                <!-- Available Forms -->
                <section class="content-section">
                    <h2><i class="fas fa-list-check"></i> Available Forms</h2>

                    <div class="feature-grid">
                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h3>Contact Form</h3>
                            <p><strong>Fields:</strong> Name, Email, Phone, Message</p>
                            <p><strong>Placement:</strong> Contact page</p>
                            <p><strong>File:</strong> contact-form.json</p>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-hand-holding-heart"></i>
                            </div>
                            <h3>Volunteer Form</h3>
                            <p><strong>Fields:</strong> Name, Email, Phone, Availability, Interests, Skills</p>
                            <p><strong>Placement:</strong> Get Involved page</p>
                            <p><strong>File:</strong> volunteer-form.json</p>
                        </div>

                        <div class="card">
                            <div class="feature-icon">
                                <i class="fas fa-file-upload"></i>
                            </div>
                            <h3>Document Upload</h3>
                            <p><strong>Fields:</strong> Name, Email, Document Category, File Attachment</p>
                            <p><strong>Placement:</strong> Dedicated upload page</p>
                            <p><strong>File:</strong> document-upload-form.json</p>
                        </div>
                    </div>
                </section>

                <!-- Form Deployment Process -->
                <section class="content-section">
                    <h2><i class="fas fa-cogs"></i> Form Deployment Process</h2>

                    <ol class="step-list">
                        <li>
                            <strong>Configure Forms</strong>
                            <p>Use Forms Manager to edit form fields. Saved to /pages/forms/*.json in Forminator export format</p>
                        </li>

                        <li>
                            <strong>Upload During Deployment</strong>
                            <p>deploy.sh uploads JSON files to /tmp/forms/ on Kinsta server</p>
                        </li>

                        <li>
                            <strong>Install Forminator Plugin</strong>
                            <p>forms.sh installs and activates Forminator plugin using ensure_plugin_installed()</p>
                        </li>

                        <li>
                            <strong>Import Forms</strong>
                            <p>forms.sh reads JSON files and imports using PHP form-import.php script via WP-CLI</p>
                        </li>

                        <li>
                            <strong>Place Form Shortcodes</strong>
                            <p>forms.sh searches for [contact-form], [volunteer-form], [document-upload-form] placeholders in page content and replaces with Forminator shortcodes</p>
                        </li>

                        <li>
                            <strong>Configure reCAPTCHA (Optional)</strong>
                            <p>If recaptcha_site_key and recaptcha_secret_key provided in config, forms.sh configures reCAPTCHA protection</p>
                        </li>
                    </ol>
                </section>

                <!-- Form Placement -->
                <section class="content-section">
                    <h2><i class="fas fa-map-pin"></i> Form Placement</h2>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-lightbulb"></i>
                            Automatic Form Placement
                        </div>
                        <p>Forms are automatically placed on pages that contain form placeholders in their layout JSON:</p>
                        <ul>
                            <li><strong>[contact-form]</strong> → Contact page</li>
                            <li><strong>[volunteer-form]</strong> → Get Involved page</li>
                            <li><strong>[document-upload-form]</strong> → Dedicated upload page</li>
                        </ul>
                        <p>If no placeholder found, forms are appended to the bottom of the configured page.</p>
                    </div>

                    <h3>How Placeholders Work</h3>
                    <p>In your page layouts (Page Editor), include form placeholder tags where you want forms to appear. During deployment, these placeholders are replaced with actual Forminator shortcodes.</p>

                    <h3>Manual Placement</h3>
                    <p>After deployment, you can manually move forms in WordPress admin:</p>
                    <ol>
                        <li>Go to wp-admin → Pages → Edit Page</li>
                        <li>Use SiteOrigin Page Builder to add Forminator widget</li>
                        <li>Select your form from the dropdown</li>
                        <li>Save page</li>
                    </ol>
                </section>

                <!-- reCAPTCHA Configuration -->
                <section class="content-section">
                    <h2><i class="fas fa-shield-alt"></i> reCAPTCHA Configuration</h2>

                    <p>Protect your forms from spam submissions with Google reCAPTCHA.</p>

                    <h3>Setup Steps</h3>
                    <ol class="step-list">
                        <li>
                            <strong>Get reCAPTCHA Keys</strong>
                            <p>Visit <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA Admin Console</a></p>
                            <p>Register your site and choose reCAPTCHA v2 (Checkbox)</p>
                            <p>Copy your Site Key and Secret Key</p>
                        </li>

                        <li>
                            <strong>Add to Configuration</strong>
                            <p>Go to Configuration → Integrations</p>
                            <p>Paste Site Key and Secret Key in the Forms section</p>
                            <p>Save configuration</p>
                        </li>

                        <li>
                            <strong>Deploy Site</strong>
                            <p>forms.sh will automatically configure reCAPTCHA for all forms during deployment</p>
                        </li>
                    </ol>

                    <div class="card">
                        <h3>reCAPTCHA Versions</h3>
                        <ul>
                            <li><strong>v2 Checkbox:</strong> "I'm not a robot" checkbox (recommended)</li>
                            <li><strong>v2 Invisible:</strong> No user interaction, validates in background</li>
                            <li><strong>v3:</strong> Score-based, no user interaction (advanced)</li>
                        </ul>
                        <p><strong>Recommended:</strong> v2 Checkbox for best balance of security and user experience</p>
                    </div>
                </section>

                <!-- Form Fields -->
                <section class="content-section">
                    <h2><i class="fas fa-list"></i> Customizing Form Fields</h2>

                    <h3>Available Field Types</h3>
                    <ul>
                        <li><strong>Text:</strong> Single-line text input (Name, Email)</li>
                        <li><strong>Textarea:</strong> Multi-line text (Message, Comments)</li>
                        <li><strong>Email:</strong> Email validation built-in</li>
                        <li><strong>Phone:</strong> Phone number with formatting</li>
                        <li><strong>Select:</strong> Dropdown menu (Categories, Options)</li>
                        <li><strong>Radio:</strong> Single choice from multiple options</li>
                        <li><strong>Checkbox:</strong> Multiple selections or agreement</li>
                        <li><strong>File Upload:</strong> Document/image uploads</li>
                        <li><strong>Date:</strong> Date picker</li>
                        <li><strong>Hidden:</strong> Hidden fields for tracking</li>
                    </ul>

                    <h3>Field Configuration</h3>
                    <p>Each field can be configured with:</p>
                    <ul>
                        <li><strong>Label:</strong> Field name shown to users</li>
                        <li><strong>Placeholder:</strong> Example text in empty field</li>
                        <li><strong>Required:</strong> Make field mandatory</li>
                        <li><strong>Validation:</strong> Email format, number range, etc.</li>
                        <li><strong>Default Value:</strong> Pre-filled value</li>
                        <li><strong>Description:</strong> Help text below field</li>
                    </ul>
                </section>

                <!-- Form Notifications -->
                <section class="content-section">
                    <h2><i class="fas fa-bell"></i> Form Notifications</h2>

                    <h3>Email Notifications</h3>
                    <p>Configure who receives email notifications when forms are submitted:</p>
                    <ul>
                        <li><strong>Admin Notification:</strong> Send to site administrator</li>
                        <li><strong>User Notification:</strong> Confirmation email to form submitter</li>
                        <li><strong>Custom Recipients:</strong> Additional email addresses</li>
                    </ul>

                    <h3>Notification Settings</h3>
                    <ul>
                        <li><strong>Subject Line:</strong> Customize email subject</li>
                        <li><strong>Message Template:</strong> Email body with form data</li>
                        <li><strong>From Name/Email:</strong> Sender information</li>
                        <li><strong>Reply-To:</strong> Where replies should go</li>
                    </ul>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Testing Email Delivery
                        </div>
                        <p>After deployment, submit test forms to verify email notifications are working. Check spam folders if emails don't arrive. Configure SPF/DKIM records in your domain's DNS for better deliverability.</p>
                    </div>
                </section>

                <!-- After Deployment -->
                <section class="content-section">
                    <h2><i class="fas fa-edit"></i> Editing Forms After Deployment</h2>

                    <p>After your site is deployed, manage forms through WordPress admin:</p>

                    <h3>Accessing Forminator</h3>
                    <ol>
                        <li>Go to wp-admin → Forminator → Forms</li>
                        <li>Click on form name to edit</li>
                        <li>Modify fields, settings, notifications</li>
                        <li>Save changes</li>
                    </ol>

                    <h3>Viewing Submissions</h3>
                    <ol>
                        <li>Go to wp-admin → Forminator → Submissions</li>
                        <li>Select form from dropdown</li>
                        <li>View all submissions with details</li>
                        <li>Export to CSV if needed</li>
                    </ol>

                    <h3>Form Analytics</h3>
                    <p>Forminator provides built-in analytics:</p>
                    <ul>
                        <li>Submission rates over time</li>
                        <li>Field completion rates</li>
                        <li>Drop-off points</li>
                        <li>Popular field values</li>
                    </ul>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="content-manager.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Content Preparation</span>
                    </a>
                    <a href="post-deployment.php" class="btn-nav next">
                        <span>Post-Deployment Steps</span>
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
