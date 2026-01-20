<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technical Deployment Workflow - Frontline Framework</title>

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
                        <h1>Technical Deployment Workflow</h1>
                        <p>Deep dive into the 12-step deployment process</p>
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
                    <span>Deployment Flow</span>
                </nav>

                <h1 class="page-title">Technical Deployment Workflow</h1>
                <p class="page-subtitle">A comprehensive technical breakdown of the complete deployment process from configuration to live site.</p>

                <!-- Overview -->
                <section class="content-section">
                    <h2><i class="fas fa-sitemap"></i> Overview</h2>
                    <p>The Frontline Framework deployment process is a carefully orchestrated 12-step workflow that transforms your pre-deployment configuration into a live WordPress website. This document provides technical details for each step, including file paths, API calls, error handling, and timing.</p>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-clock"></i>
                            Typical Timeline
                        </div>
                        <ul>
                            <li><strong>Configuration Validation:</strong> 5-10 seconds</li>
                            <li><strong>Git Operations:</strong> 10-15 seconds</li>
                            <li><strong>GitHub Actions Trigger:</strong> 2-5 seconds</li>
                            <li><strong>Kinsta Deployment:</strong> 3-5 minutes</li>
                            <li><strong>Total Time:</strong> 4-6 minutes (average)</li>
                        </ul>
                    </div>
                </section>

                <!-- Architecture Diagram -->
                <section class="content-section">
                    <h2><i class="fas fa-project-diagram"></i> System Architecture</h2>
                    <div class="architecture-diagram">
                        <div class="arch-component local">
                            <div class="component-header">Local Interface</div>
                            <div class="component-body">
                                <div class="component-item">Web Admin Interface</div>
                                <div class="component-item">PHP Backend</div>
                                <div class="component-item">Configuration Files</div>
                                <div class="component-item">Bash Scripts</div>
                            </div>
                        </div>
                        <div class="arch-arrow">→</div>
                        <div class="arch-component github">
                            <div class="component-header">GitHub</div>
                            <div class="component-body">
                                <div class="component-item">Repository</div>
                                <div class="component-item">Actions Workflows</div>
                                <div class="component-item">Version Control</div>
                            </div>
                        </div>
                        <div class="arch-arrow">→</div>
                        <div class="arch-component kinsta">
                            <div class="component-header">Kinsta Hosting</div>
                            <div class="component-body">
                                <div class="component-item">WordPress Installation</div>
                                <div class="component-item">WP-CLI Scripts</div>
                                <div class="component-item">Live Website</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Step-by-Step Breakdown -->
                <section class="content-section">
                    <h2><i class="fas fa-list-ol"></i> Step-by-Step Breakdown</h2>

                    <!-- Step 1 -->
                    <div class="workflow-step">
                        <div class="step-header">
                            <div class="step-num">1</div>
                            <h3>User Clicks "Deploy Website"</h3>
                            <span class="step-time">~1 second</span>
                        </div>
                        <div class="step-content">
                            <h4>What Happens</h4>
                            <p>User clicks the deploy button in the web interface (<code>/php/web-admin.php</code>), triggering an AJAX POST request to <code>/php/background-deploy.php</code>.</p>

                            <h4>Technical Details</h4>
                            <pre><code>// JavaScript (web-admin.php)
$('#deploy-button').on('click', function() {
    $.ajax({
        url: '/php/background-deploy.php',
        method: 'POST',
        data: {
            action: 'deploy',
            site_id: currentSiteId,
            timestamp: Date.now()
        },
        success: function(response) {
            showDeploymentProgress(response.deploy_id);
        }
    });
});</code></pre>

                            <h4>Files Involved</h4>
                            <ul>
                                <li><code>/php/web-admin.php</code> - User interface</li>
                                <li><code>/php/admin/assets/js/deploy.js</code> - Client-side deployment logic</li>
                                <li><code>/php/background-deploy.php</code> - Initiates background deployment</li>
                            </ul>

                            <h4>Error Handling</h4>
                            <p>If user is not authenticated or lacks permissions, return HTTP 403 and show error modal.</p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="workflow-step">
                        <div class="step-header">
                            <div class="step-num">2</div>
                            <h3>PHP Validates All Configurations</h3>
                            <span class="step-time">~5-10 seconds</span>
                        </div>
                        <div class="step-content">
                            <h4>What Happens</h4>
                            <p>PHP backend validates all configuration files, checks for required fields, ensures JSON syntax is valid, and verifies external dependencies (Git, API credentials).</p>

                            <h4>Technical Details</h4>
                            <pre><code>// PHP (background-deploy.php)
function validate_deployment_config() {
    $config_files = [
        'site.json',
        'theme-config.json',
        'forms-config.json',
        'git.json'
    ];

    foreach ($config_files as $file) {
        $path = __DIR__ . '/../config/' . $file;

        if (!file_exists($path)) {
            throw new Exception("Missing config: $file");
        }

        $content = file_get_contents($path);
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in $file: " . json_last_error_msg());
        }

        validate_config_schema($json, $file);
    }

    return true;
}</code></pre>

                            <h4>Validation Checks</h4>
                            <ul>
                                <li>All required config files exist in <code>/config/</code></li>
                                <li>JSON syntax is valid (no trailing commas, proper quotes)</li>
                                <li>Required fields are present (site_name, site_url, theme_name)</li>
                                <li>Email addresses are valid format</li>
                                <li>URLs include protocol (https://)</li>
                                <li>Color codes are valid hex (#rrggbb)</li>
                                <li>Git credentials are configured</li>
                            </ul>

                            <h4>Error Handling</h4>
                            <p>If validation fails, log error to <code>/logs/deployment/</code> and return detailed error message to user. Deployment aborts.</p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="workflow-step">
                        <div class="step-header">
                            <div class="step-num">3</div>
                            <h3>JSON Files Generated in /config/</h3>
                            <span class="step-time">~2-3 seconds</span>
                        </div>
                        <div class="step-content">
                            <h4>What Happens</h4>
                            <p>Individual configuration files are merged into a master <code>config.json</code> file. Page layouts, CPT content, and form configurations are also exported as JSON.</p>

                            <h4>Technical Details</h4>
                            <pre><code>// PHP (background-deploy.php)
function generate_master_config() {
    $site = json_decode(file_get_contents(__DIR__ . '/../config/site.json'), true);
    $theme = json_decode(file_get_contents(__DIR__ . '/../config/theme-config.json'), true);
    $forms = json_decode(file_get_contents(__DIR__ . '/../config/forms-config.json'), true);

    $master_config = [
        'site' => $site,
        'theme' => $theme,
        'forms' => $forms,
        'deployment' => [
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'version' => FRAMEWORK_VERSION,
            'deploy_user' => get_current_user_email()
        ]
    ];

    file_put_contents(
        __DIR__ . '/../config/config.json',
        json_encode($master_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

// Export page layouts
function export_page_layouts() {
    $theme = get_active_theme();
    $layouts_dir = __DIR__ . "/../pages/themes/$theme/layouts/";

    foreach (glob($layouts_dir . '*.json') as $layout_file) {
        copy($layout_file, __DIR__ . '/../config/layouts/' . basename($layout_file));
    }
}</code></pre>

                            <h4>Files Generated</h4>
                            <ul>
                                <li><code>/config/config.json</code> - Master configuration</li>
                                <li><code>/config/layouts/*.json</code> - SiteOrigin page layouts</li>
                                <li><code>/config/cpt/*.json</code> - Custom post type content</li>
                                <li><code>/config/forms/*.json</code> - Forminator form definitions</li>
                                <li><code>/config/slides/*.json</code> - Slider content</li>
                            </ul>

                            <h4>Error Handling</h4>
                            <p>If file write fails (permissions issue), log error and abort deployment. Ensure <code>/config/</code> directory has write permissions (755).</p>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="workflow-step">
                        <div class="step-header">
                            <div class="step-num">4</div>
                            <h3>Git Commit Created</h3>
                            <span class="step-time">~3-5 seconds</span>
                        </div>
                        <div class="step-content">
                            <h4>What Happens</h4>
                            <p>All generated configuration files are staged and committed to the local Git repository with a timestamped commit message.</p>

                            <h4>Technical Details</h4>
                            <pre><code>// Bash (scripts/deploy.sh)
create_git_commit() {
    local timestamp=$(date -u +"%Y-%m-%d %H:%M:%S UTC")
    local commit_message="Deploy: Updated site configuration - $timestamp"

    cd "$PROJECT_ROOT" || exit 1

    # Stage all config files
    git add config/*.json
    git add config/layouts/*.json
    git add config/cpt/*.json
    git add config/forms/*.json

    # Create commit
    git commit -m "$commit_message" \
        --author="Frontline Framework <noreply@frontlineframework.com>"

    if [[ $? -ne 0 ]]; then
        log_error "Git commit failed"
        return 1
    fi

    local commit_hash=$(git rev-parse HEAD)
    log_info "Created commit: $commit_hash"

    echo "$commit_hash" > /tmp/deployment_commit.txt
    return 0
}</code></pre>

                            <h4>Git Operations</h4>
                            <ol>
                                <li>Check for uncommitted changes: <code>git status --porcelain</code></li>
                                <li>Stage configuration files: <code>git add config/</code></li>
                                <li>Create commit: <code>git commit -m "Deploy: ..."</code></li>
                                <li>Store commit hash for tracking</li>
                            </ol>

                            <h4>Error Handling</h4>
                            <ul>
                                <li>If Git not configured: Prompt user to run <code>git config</code></li>
                                <li>If merge conflicts exist: Abort and ask user to resolve</li>
                                <li>If nothing to commit: Continue (configs unchanged)</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Step 5 -->
                    <div class="workflow-step">
                        <div class="step-header">
                            <div class="step-num">5</div>
                            <h3>Push to GitHub Repository</h3>
                            <span class="step-time">~5-10 seconds</span>
                        </div>
                        <div class="step-content">
                            <h4>What Happens</h4>
                            <p>Local Git commits are pushed to the remote GitHub repository, making configuration available to GitHub Actions.</p>

                            <h4>Technical Details</h4>
                            <pre><code>// Bash (scripts/deploy.sh)
push_to_github() {
    local remote="origin"
    local branch=$(git symbolic-ref --short HEAD)

    log_info "Pushing to $remote/$branch..."

    git push "$remote" "$branch" 2>&1 | tee -a "$LOG_FILE"

    if [[ ${PIPESTATUS[0]} -ne 0 ]]; then
        log_error "Git push failed. Check credentials and network."
        return 1
    fi

    log_success "Successfully pushed to GitHub"
    return 0
}</code></pre>

                            <h4>Authentication Methods</h4>
                            <ul>
                                <li><strong>HTTPS:</strong> Uses Personal Access Token in Git credentials</li>
                                <li><strong>SSH:</strong> Uses SSH key pair (requires <code>ssh-agent</code>)</li>
                            </ul>

                            <h4>Error Handling</h4>
                            <ul>
                                <li><strong>Authentication failed:</strong> Invalid PAT or SSH key</li>
                                <li><strong>Network timeout:</strong> Check internet connection</li>
                                <li><strong>Non-fast-forward:</strong> Remote has changes, need to pull first</li>
                                <li><strong>Large file rejected:</strong> File exceeds GitHub's 100MB limit</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Step 6 -->
                    <div class="workflow-step">
                        <div class="step-header">
                            <div class="step-num">6</div>
                            <h3>GitHub Actions Workflow Triggered</h3>
                            <span class="step-time">~2-5 seconds</span>
                        </div>
                        <div class="step-content">
                            <h4>What Happens</h4>
                            <p>A repository dispatch event is sent to GitHub API, triggering the deployment workflow defined in <code>.github/workflows/deploy.yml</code>.</p>

                            <h4>Technical Details</h4>
                            <pre><code>// Bash (scripts/actions.sh)
trigger_github_workflow() {
    local repo="$GITHUB_REPO"
    local token="$GITHUB_TOKEN"
    local site_id=$(cat /tmp/kinsta_site_id.txt)

    local payload=$(jq -n \
        --arg site_id "$site_id" \
        --arg timestamp "$(date -u +"%Y-%m-%dT%H:%M:%SZ")" \
        '{
            event_type: "deploy_site",
            client_payload: {
                site_id: $site_id,
                timestamp: $timestamp
            }
        }')

    local response=$(curl -s -w "\n%{http_code}" -X POST \
        "https://api.github.com/repos/$repo/dispatches" \
        -H "Authorization: token $token" \
        -H "Accept: application/vnd.github.v3+json" \
        -H "Content-Type: application/json" \
        -d "$payload")

    local http_code=$(echo "$response" | tail -n1)

    if [[ "$http_code" != "204" ]]; then
        log_error "Failed to trigger workflow. HTTP $http_code"
        return 1
    fi

    log_success "Workflow triggered successfully"
    return 0
}</code></pre>

                            <h4>Workflow YAML</h4>
                            <pre><code># .github/workflows/deploy.yml
name: Deploy to Kinsta
on:
  repository_dispatch:
    types: [deploy_site]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Sync files to Kinsta
        env:
          KINSTA_SITE_ID: ${{ github.event.client_payload.site_id }}
        run: |
          rsync -avz --delete \
            wp-content/themes/ \
            kinsta@$KINSTA_HOST:/www/wp-content/themes/</code></pre>

                            <h4>Error Handling</h4>
                            <ul>
                                <li><strong>HTTP 401:</strong> Invalid or expired GitHub token</li>
                                <li><strong>HTTP 404:</strong> Repository not found or token lacks permissions</li>
                                <li><strong>HTTP 422:</strong> Invalid payload format</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Step 7 -->
                    <div class="workflow-step">
                        <div class="step-header">
                            <div class="step-num">7</div>
                            <h3>Actions Syncs Files to Kinsta</h3>
                            <span class="step-time">~1-2 minutes</span>
                        </div>
                        <div class="step-content">
                            <h4>What Happens</h4>
                            <p>GitHub Actions runner connects to Kinsta via SSH/SFTP and syncs theme files, scripts, and configurations.</p>

                            <h4>Technical Details</h4>
                            <pre><code># GitHub Actions workflow step
- name: Sync files to Kinsta
  run: |
    # Add Kinsta host to known_hosts
    mkdir -p ~/.ssh
    ssh-keyscan -H $KINSTA_HOST >> ~/.ssh/known_hosts

    # Sync themes
    rsync -avz --delete \
      --exclude='node_modules' \
      --exclude='.git' \
      wp-content/themes/ \
      $KINSTA_USER@$KINSTA_HOST:$KINSTA_PATH/wp-content/themes/

    # Sync scripts
    rsync -avz scripts/ \
      $KINSTA_USER@$KINSTA_HOST:$KINSTA_PATH/scripts/

    # Upload config files
    scp -r config/*.json \
      $KINSTA_USER@$KINSTA_HOST:$KINSTA_PATH/config/</code></pre>

                            <h4>Files Synced</h4>
                            <ul>
                                <li><code>/wp-content/themes/</code> - All theme files</li>
                                <li><code>/scripts/</code> - Deployment automation scripts</li>
                                <li><code>/config/</code> - Configuration JSON files</li>
                            </ul>

                            <h4>Sync Options</h4>
                            <ul>
                                <li><code>-a</code> Archive mode (preserve permissions, timestamps)</li>
                                <li><code>-v</code> Verbose output</li>
                                <li><code>-z</code> Compress during transfer</li>
                                <li><code>--delete</code> Remove files not in source</li>
                            </ul>

                            <h4>Error Handling</h4>
                            <ul>
                                <li><strong>Connection refused:</strong> Check Kinsta SSH credentials</li>
                                <li><strong>Permission denied:</strong> Verify SSH key is added to Kinsta</li>
                                <li><strong>Disk quota exceeded:</strong> Free up space on Kinsta</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Step 8 -->
                    <div class="workflow-step">
                        <div class="step-header">
                            <div class="step-num">8</div>
                            <h3>Kinsta Creates WordPress Installation</h3>
                            <span class="step-time">~30-60 seconds</span>
                        </div>
                        <div class="step-content">
                            <h4>What Happens</h4>
                            <p>If site doesn't exist, Kinsta API creates a new WordPress installation with database, configures PHP settings, and sets up SSL certificate.</p>

                            <h4>Technical Details</h4>
                            <pre><code>// Bash (scripts/site.sh)
create_kinsta_site() {
    local site_name="$1"

    local response=$(curl -s -X POST \
        "https://api.kinsta.com/v2/sites" \
        -H "Authorization: Bearer $KINSTA_API_KEY" \
        -H "Content-Type: application/json" \
        -d '{
            "company": "'"$KINSTA_COMPANY_ID"'",
            "display_name": "'"$site_name"'",
            "region": "us-central1",
            "hosting_type": "managed_wordpress",
            "wp_language": "en_US",
            "php_version": "8.1",
            "install_mode": "new"
        }')

    local site_id=$(echo "$response" | jq -r '.site.id')
    echo "$site_id" > /tmp/kinsta_site_id.txt
}</code></pre>

                            <h4>Site Configuration</h4>
                            <ul>
                                <li><strong>PHP Version:</strong> 8.1 (default)</li>
                                <li><strong>Region:</strong> us-central1 (Google Cloud)</li>
                                <li><strong>SSL:</strong> Let's Encrypt certificate (auto-renewed)</li>
                                <li><strong>Database:</strong> MySQL 8.0</li>
                                <li><strong>Caching:</strong> Kinsta Cache (built-in)</li>
                            </ul>

                            <h4>Error Handling</h4>
                            <ul>
                                <li><strong>Site limit reached:</strong> Upgrade Kinsta plan</li>
                                <li><strong>Domain already exists:</strong> Choose different domain</li>
                                <li><strong>API rate limit:</strong> Wait 1 hour before retrying</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Step 9 -->
                    <div class="workflow-step">
                        <div class="step-header">
                            <div class="step-num">9</div>
                            <h3>WP-CLI Executes Setup Scripts</h3>
                            <span class="step-time">~1-2 minutes</span>
                        </div>
                        <div class="step-content">
                            <h4>What Happens</h4>
                            <p>Scripts from WebsiteBuild repository execute in order: cleanup → template → forms → map → analytics → security → cache-clear.</p>

                            <h4>Technical Details</h4>
                            <pre><code># Bash (executed on Kinsta via SSH)
cd /www/kinsta_site/public
export PATH="$PATH:/usr/local/bin/wp"

# Execute scripts in order
./scripts/cleanup.sh
./scripts/template.sh
./scripts/forms.sh
./scripts/map.sh
./scripts/analytics.sh
./scripts/security.sh
./scripts/cache-clear.sh</code></pre>

                            <h4>Script Functions</h4>
                            <ul>
                                <li><strong>cleanup.sh:</strong> Remove default WordPress content, clear transients</li>
                                <li><strong>template.sh:</strong> Import SiteOrigin page layouts</li>
                                <li><strong>forms.sh:</strong> Import Forminator forms</li>
                                <li><strong>map.sh:</strong> Configure Google Maps API</li>
                                <li><strong>analytics.sh:</strong> Install GA4, Facebook Pixel</li>
                                <li><strong>security.sh:</strong> Configure Wordfence, geo-blocking</li>
                                <li><strong>cache-clear.sh:</strong> Flush all caches</li>
                            </ul>

                            <h4>Error Handling</h4>
                            <p>If any script returns non-zero exit code, deployment pauses. Admin receives error notification with script name and error message.</p>
                        </div>
                    </div>

                    <!-- Step 10 -->
                    <div class="workflow-step">
                        <div class="step-header">
                            <div class="step-num">10</div>
                            <h3>Theme Installed & Activated</h3>
                            <span class="step-time">~15-30 seconds</span>
                        </div>
                        <div class="step-content">
                            <h4>What Happens</h4>
                            <p>Selected theme is activated, theme options are configured, and required plugins are installed/activated.</p>

                            <h4>Technical Details</h4>
                            <pre><code># WP-CLI commands (scripts/template.sh)
wp theme activate BurBank

# Install required plugins
wp plugin install forminator --activate
wp plugin install wordfence --activate
wp plugin install siteorigin-panels --activate

# Configure theme options
wp option update theme_mods_BurBank "$(cat /config/theme-config.json)" --format=json

# Import demo content
wp eval-file wp-content/themes/BurBank/import-demo.php</code></pre>

                            <h4>Theme Setup Tasks</h4>
                            <ul>
                                <li>Activate theme: <code>wp theme activate</code></li>
                                <li>Install dependencies: <code>wp plugin install</code></li>
                                <li>Set theme mods: <code>wp option update theme_mods_*</code></li>
                                <li>Import demo content: <code>wp eval-file</code></li>
                                <li>Set menus: <code>wp menu create</code></li>
                                <li>Assign widgets: <code>wp widget add</code></li>
                            </ul>

                            <h4>Error Handling</h4>
                            <ul>
                                <li><strong>Theme not found:</strong> Verify theme files synced correctly</li>
                                <li><strong>Plugin install failed:</strong> Check WordPress.org API availability</li>
                                <li><strong>Demo import failed:</strong> Check JSON file paths</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Step 11 -->
                    <div class="workflow-step">
                        <div class="step-header">
                            <div class="step-num">11</div>
                            <h3>Content Imported</h3>
                            <span class="step-time">~30-90 seconds</span>
                        </div>
                        <div class="step-content">
                            <h4>What Happens</h4>
                            <p>All custom post types (news, testimonials, issues, endorsements) and media files are imported from JSON.</p>

                            <h4>Technical Details</h4>
                            <pre><code># PHP (theme import-demo.php)
function import_cpt_content() {
    $cpt_types = ['news', 'testimonials', 'issues', 'endorsements', 'posts'];

    foreach ($cpt_types as $type) {
        $json_file = "/config/cpt/$type.json";

        if (!file_exists($json_file)) {
            WP_CLI::warning("Skipping $type - file not found");
            continue;
        }

        $data = json_decode(file_get_contents($json_file), true);

        foreach ($data['posts'] as $post) {
            $post_id = wp_insert_post([
                'post_title' => $post['title'],
                'post_content' => $post['content'],
                'post_excerpt' => $post['excerpt'],
                'post_type' => $type,
                'post_status' => $post['status'],
                'post_date' => $post['date']
            ]);

            // Import custom fields
            foreach ($post['meta'] as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }

            // Set featured image
            if (!empty($post['featured_image'])) {
                $attachment_id = media_sideload_image($post['featured_image'], $post_id, '', 'id');
                set_post_thumbnail($post_id, $attachment_id);
            }
        }

        WP_CLI::success("Imported " . count($data['posts']) . " $type items");
    }
}</code></pre>

                            <h4>Import Order</h4>
                            <ol>
                                <li>Pages (home, about, contact, etc.)</li>
                                <li>Posts (blog articles)</li>
                                <li>News (press releases)</li>
                                <li>Testimonials</li>
                                <li>Issues (policy positions)</li>
                                <li>Endorsements</li>
                                <li>Media files (images, PDFs)</li>
                            </ol>

                            <h4>Error Handling</h4>
                            <ul>
                                <li><strong>Duplicate content:</strong> Check for existing post slugs, skip if found</li>
                                <li><strong>Media upload failed:</strong> Check file size/format, log error</li>
                                <li><strong>Invalid JSON:</strong> Skip file and continue with others</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Step 12 -->
                    <div class="workflow-step">
                        <div class="step-header">
                            <div class="step-num">12</div>
                            <h3>Cache Cleared & Site Live</h3>
                            <span class="step-time">~10-15 seconds</span>
                        </div>
                        <div class="step-content">
                            <h4>What Happens</h4>
                            <p>All WordPress caches are flushed, Kinsta cache is cleared, rewrite rules regenerated, and site status is set to "live".</p>

                            <h4>Technical Details</h4>
                            <pre><code># WP-CLI commands (scripts/cache-clear.sh)
wp cache flush
wp transient delete --all
wp rewrite flush

# Clear Kinsta cache via API
curl -X DELETE \
    "https://api.kinsta.com/v2/sites/$KINSTA_SITE_ID/cache" \
    -H "Authorization: Bearer $KINSTA_API_KEY"

# Update deployment status
wp option update deployment_status "live"
wp option update deployment_timestamp "$(date -u +"%Y-%m-%dT%H:%M:%SZ")"</code></pre>

                            <h4>Cache Types Cleared</h4>
                            <ul>
                                <li><strong>WordPress Object Cache:</strong> <code>wp cache flush</code></li>
                                <li><strong>Transients:</strong> <code>wp transient delete --all</code></li>
                                <li><strong>Kinsta Cache:</strong> Edge cache via API</li>
                                <li><strong>Browser Cache:</strong> Headers set to force revalidation</li>
                                <li><strong>Rewrite Rules:</strong> <code>wp rewrite flush</code></li>
                            </ul>

                            <h4>Final Verification</h4>
                            <pre><code># Verify site is accessible
response_code=$(curl -s -o /dev/null -w "%{http_code}" "https://$SITE_URL")

if [[ "$response_code" == "200" ]]; then
    log_success "Site is live and accessible"
    wp option update site_status "live"
else
    log_error "Site returned HTTP $response_code"
    wp option update site_status "error"
fi</code></pre>

                            <h4>Error Handling</h4>
                            <ul>
                                <li><strong>Cache clear failed:</strong> Not critical, continue deployment</li>
                                <li><strong>Site not accessible:</strong> Check DNS, SSL certificate</li>
                                <li><strong>502/503 errors:</strong> Wait 30 seconds for services to start</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Post-Deployment -->
                <section class="content-section">
                    <h2><i class="fas fa-check-circle"></i> Post-Deployment Verification</h2>
                    <p>After deployment completes, the system performs automated verification checks:</p>

                    <h3>Automated Checks</h3>
                    <ul>
                        <li>✓ Site returns HTTP 200 status code</li>
                        <li>✓ Homepage renders without errors</li>
                        <li>✓ Theme activated correctly</li>
                        <li>✓ Required plugins are active</li>
                        <li>✓ Forms are functional (test submission)</li>
                        <li>✓ SSL certificate is valid</li>
                        <li>✓ Analytics tracking installed</li>
                        <li>✓ All pages created successfully</li>
                        <li>✓ Media files uploaded</li>
                        <li>✓ Contact form sends emails</li>
                    </ul>

                    <h3>Manual Verification</h3>
                    <p>Users should also verify:</p>
                    <ul>
                        <li>Visual appearance matches preview</li>
                        <li>Navigation menus work correctly</li>
                        <li>Forms submit successfully</li>
                        <li>Mobile responsiveness</li>
                        <li>Social media links</li>
                        <li>Contact information accuracy</li>
                    </ul>
                </section>

                <!-- Rollback Procedure -->
                <section class="content-section">
                    <h2><i class="fas fa-undo"></i> Rollback Procedure</h2>
                    <p>If deployment fails or produces unexpected results, follow this rollback process:</p>

                    <h3>Automatic Rollback</h3>
                    <p>If any critical step fails (steps 2-9), deployment automatically rolls back:</p>
                    <pre><code>#!/bin/bash
rollback_deployment() {
    log_warning "Rolling back deployment..."

    # Revert Git commit
    git reset --hard HEAD~1

    # Delete generated config files
    rm -f config/config.json

    # Restore previous Kinsta backup (if available)
    if [[ -f /tmp/kinsta_backup_id.txt ]]; then
        kinsta_api POST "/backups/$(cat /tmp/kinsta_backup_id.txt)/restore"
    fi

    log_info "Rollback completed. Site restored to previous state."
}</code></pre>

                    <h3>Manual Rollback</h3>
                    <ol>
                        <li>Go to GitHub repository</li>
                        <li>Find previous successful commit</li>
                        <li>Click "Revert this commit"</li>
                        <li>Trigger new deployment with reverted config</li>
                    </ol>
                </section>

                <!-- Monitoring & Logs -->
                <section class="content-section">
                    <h2><i class="fas fa-chart-line"></i> Monitoring & Logs</h2>

                    <h3>Log Files</h3>
                    <ul>
                        <li><code>/logs/deployment/deploy_YYYYMMDD_HHMMSS.log</code> - Full deployment log</li>
                        <li><code>/tmp/deployment_status.json</code> - Real-time status</li>
                        <li><code>/logs/api/github_api.log</code> - GitHub API requests</li>
                        <li><code>/logs/api/kinsta_api.log</code> - Kinsta API requests</li>
                    </ul>

                    <h3>Accessing Logs</h3>
                    <pre><code># View latest deployment log
tail -f /logs/deployment/$(ls -t /logs/deployment/ | head -1)

# Search for errors
grep -i "error" /logs/deployment/*.log

# View deployment status
cat /tmp/deployment_status.json | jq .</code></pre>

                    <h3>Performance Metrics</h3>
                    <p>Each deployment records timing metrics:</p>
                    <pre><code>{
    "total_duration": "4m 32s",
    "steps": {
        "validation": "8s",
        "git_operations": "12s",
        "github_trigger": "3s",
        "kinsta_sync": "1m 45s",
        "wp_setup": "2m 10s",
        "content_import": "45s",
        "cache_clear": "9s"
    }
}</code></pre>
                </section>

                <!-- Related Resources -->
                <section class="content-section">
                    <h2><i class="fas fa-link"></i> Related Resources</h2>
                    <ul>
                        <li><a href="scripts-reference.php">Bash Scripts Reference</a></li>
                        <li><a href="json-format.php">JSON Configuration Schemas</a></li>
                        <li><a href="architecture.php">System Architecture</a></li>
                        <li><a href="troubleshooting.php">Troubleshooting Guide</a></li>
                        <li><a href="glossary.php">Glossary of Terms</a></li>
                    </ul>
                </section>

                <!-- Navigation -->
                <nav class="doc-nav">
                    <a href="scripts-reference.php" class="nav-prev">
                        <i class="fas fa-arrow-left"></i>
                        <div>
                            <span>Previous</span>
                            <strong>Scripts Reference</strong>
                        </div>
                    </a>
                    <a href="index.php" class="nav-next">
                        <div>
                            <span>Back to</span>
                            <strong>Documentation Home</strong>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </nav>
            </div>
        </main>
    </div>

    <style>
    .architecture-diagram {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 2rem 0;
        gap: 1rem;
    }

    .arch-component {
        flex: 1;
        border-radius: 0.75rem;
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }

    .component-header {
        padding: 1rem;
        font-weight: 600;
        font-size: 1.1rem;
        color: white;
        text-align: center;
    }

    .arch-component.local .component-header {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
    }

    .arch-component.github .component-header {
        background: linear-gradient(135deg, #1e293b, #334155);
    }

    .arch-component.kinsta .component-header {
        background: linear-gradient(135deg, #14b8a6, #0d9488);
    }

    .component-body {
        background: white;
        padding: 1.5rem;
    }

    .component-item {
        background: var(--bg-secondary);
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        border-left: 3px solid var(--primary-color);
        font-size: 0.9rem;
    }

    .arch-arrow {
        font-size: 2rem;
        color: var(--primary-color);
        font-weight: bold;
    }

    .workflow-step {
        background: var(--bg-secondary);
        border-radius: 0.75rem;
        margin-bottom: 2rem;
        overflow: hidden;
        border: 1px solid var(--border-color);
    }

    .step-header {
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        color: white;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .step-num {
        background: white;
        color: var(--primary-color);
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.4rem;
        flex-shrink: 0;
    }

    .step-header h3 {
        flex: 1;
        margin: 0;
        font-size: 1.3rem;
    }

    .step-time {
        background: rgba(255,255,255,0.2);
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.9rem;
        white-space: nowrap;
    }

    .step-content {
        padding: 2rem;
    }

    .step-content h4 {
        color: var(--primary-color);
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        font-size: 1.1rem;
    }

    .step-content h4:first-child {
        margin-top: 0;
    }

    .step-content ul {
        margin-left: 1.5rem;
        margin-bottom: 1rem;
    }

    .step-content pre {
        background: #1e293b;
        color: #e2e8f0;
        padding: 1.5rem;
        border-radius: 0.5rem;
        overflow-x: auto;
        margin: 1rem 0;
        font-size: 0.9rem;
    }

    .step-content pre code {
        color: inherit;
        background: none;
    }

    @media (max-width: 768px) {
        .architecture-diagram {
            flex-direction: column;
        }

        .arch-arrow {
            transform: rotate(90deg);
        }

        .step-header {
            flex-wrap: wrap;
        }

        .step-time {
            width: 100%;
            text-align: center;
            margin-top: 0.5rem;
        }
    }
    </style>
</body>
</html>
