<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bash Scripts Reference - Frontline Framework</title>

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
                        <h1>Bash Scripts Reference</h1>
                        <p>Complete guide to deployment automation scripts</p>
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
                    <span>Scripts Reference</span>
                </nav>

                <h1 class="page-title">Bash Scripts Reference</h1>
                <p class="page-subtitle">Documentation for all bash automation scripts used in the Frontline Framework deployment pipeline.</p>

                <!-- Overview -->
                <section class="content-section">
                    <h2><i class="fas fa-terminal"></i> Overview</h2>
                    <p>The <code>/scripts/</code> directory contains Bash shell scripts that handle deployment orchestration, API interactions, logging, and status management. These scripts run on your local machine (or CI/CD environment) and coordinate the entire deployment process from configuration generation to final site activation.</p>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-info-circle"></i>
                            Execution Context
                        </div>
                        <p>These scripts run <strong>locally on the interface machine</strong>, not on Kinsta servers. They interact with:</p>
                        <ul>
                            <li>Local filesystem (read/write config files)</li>
                            <li>Git repository (commit and push changes)</li>
                            <li>GitHub API (trigger Actions workflows)</li>
                            <li>Kinsta API (create sites, retrieve info)</li>
                        </ul>
                    </div>

                    <h3>Script Dependencies</h3>
                    <ul>
                        <li><strong>Bash:</strong> Version 4.0+ (macOS users: <code>brew install bash</code>)</li>
                        <li><strong>Git:</strong> For version control operations</li>
                        <li><strong>curl:</strong> For API requests</li>
                        <li><strong>jq:</strong> For JSON parsing (<code>brew install jq</code>)</li>
                    </ul>
                </section>

                <!-- Execution Order -->
                <section class="content-section">
                    <h2><i class="fas fa-list-ol"></i> Execution Order</h2>
                    <p>Scripts are executed in a specific sequence during deployment. Understanding this order is crucial for troubleshooting.</p>

                    <div class="execution-flow">
                        <div class="flow-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>deploy.sh</h4>
                                <p>Main orchestrator - validates config, generates JSON, commits to Git</p>
                            </div>
                        </div>
                        <div class="flow-arrow">↓</div>
                        <div class="flow-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>site.sh</h4>
                                <p>Creates/verifies Kinsta site via API</p>
                            </div>
                        </div>
                        <div class="flow-arrow">↓</div>
                        <div class="flow-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>actions.sh</h4>
                                <p>Triggers GitHub Actions deployment workflow</p>
                            </div>
                        </div>
                        <div class="flow-arrow">↓</div>
                        <div class="flow-step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h4>status.sh</h4>
                                <p>Monitors deployment progress and reports completion</p>
                            </div>
                        </div>
                    </div>

                    <p><strong>Note:</strong> <code>logger.sh</code>, <code>api.sh</code>, and <code>creds.sh</code> are utility scripts sourced by other scripts, not executed directly.</p>
                </section>

                <!-- deploy.sh -->
                <section class="content-section">
                    <h2><i class="fas fa-rocket"></i> deploy.sh - Main Deployment Orchestrator</h2>
                    <p>The primary script that coordinates the entire deployment process. Called when users click "Deploy Website" in the interface.</p>

                    <h3>Purpose</h3>
                    <ul>
                        <li>Validate all configuration files</li>
                        <li>Generate JSON configuration files in <code>/config/</code></li>
                        <li>Create Git commit with deployment changes</li>
                        <li>Push changes to GitHub repository</li>
                        <li>Call other scripts in sequence (site.sh, actions.sh, status.sh)</li>
                        <li>Handle errors and rollback on failure</li>
                    </ul>

                    <h3>Usage</h3>
                    <pre><code>./scripts/deploy.sh [options]

Options:
  --dry-run       Validate config without deploying
  --force         Skip confirmation prompts
  --verbose       Enable detailed logging
  --help          Show help message

Examples:
  ./scripts/deploy.sh                    # Standard deployment
  ./scripts/deploy.sh --dry-run          # Test validation only
  ./scripts/deploy.sh --force --verbose  # Automated deployment with logs</code></pre>

                    <h3>Key Functions</h3>
                    <div class="function-block">
                        <h4>validate_configuration()</h4>
                        <p>Checks all required JSON files exist and are valid:</p>
                        <pre><code>validate_configuration() {
    local config_files=("site.json" "theme-config.json" "forms-config.json")

    for file in "${config_files[@]}"; do
        if [[ ! -f "config/$file" ]]; then
            log_error "Missing required file: config/$file"
            return 1
        fi

        if ! jq empty "config/$file" 2>/dev/null; then
            log_error "Invalid JSON in config/$file"
            return 1
        fi
    done

    log_info "All configuration files validated successfully"
    return 0
}</code></pre>
                    </div>

                    <div class="function-block">
                        <h4>generate_config_files()</h4>
                        <p>Merges individual config files into master <code>config.json</code>:</p>
                        <pre><code>generate_config_files() {
    local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

    jq -s '
        {
            site: .[0],
            theme: .[1],
            forms: .[2],
            deployment: {
                timestamp: $timestamp,
                version: "2.0"
            }
        }
    ' --arg timestamp "$timestamp" \
        config/site.json \
        config/theme-config.json \
        config/forms-config.json \
        > config/config.json

    log_info "Generated master config.json"
}</code></pre>
                    </div>

                    <h3>Exit Codes</h3>
                    <ul>
                        <li><code>0</code> - Success (deployment completed)</li>
                        <li><code>1</code> - Configuration validation failed</li>
                        <li><code>2</code> - Git operations failed</li>
                        <li><code>3</code> - GitHub API error</li>
                        <li><code>4</code> - Deployment timeout</li>
                    </ul>

                    <div class="alert alert-warning">
                        <strong>Important:</strong> This script must be run from the project root directory, not from within <code>/scripts/</code>.
                    </div>
                </section>

                <!-- site.sh -->
                <section class="content-section">
                    <h2><i class="fas fa-server"></i> site.sh - Kinsta Site Management</h2>
                    <p>Interacts with Kinsta API to create, configure, or verify WordPress site existence.</p>

                    <h3>Purpose</h3>
                    <ul>
                        <li>Check if site already exists on Kinsta</li>
                        <li>Create new WordPress installation if needed</li>
                        <li>Configure site settings (PHP version, SSL, etc.)</li>
                        <li>Retrieve site credentials (SFTP, database)</li>
                        <li>Store site ID for subsequent operations</li>
                    </ul>

                    <h3>Usage</h3>
                    <pre><code>./scripts/site.sh [action] [site_name]

Actions:
  create    Create new Kinsta site
  check     Verify site exists
  info      Retrieve site information
  delete    Remove site (use with caution!)

Examples:
  ./scripts/site.sh create "janedoe2026"
  ./scripts/site.sh check "janedoe2026"
  ./scripts/site.sh info "janedoe2026"</code></pre>

                    <h3>Key Functions</h3>
                    <div class="function-block">
                        <h4>create_kinsta_site()</h4>
                        <p>Creates a new WordPress site via Kinsta API:</p>
                        <pre><code>create_kinsta_site() {
    local site_name="$1"
    local site_url="$2"

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
            "install_mode": "new"
        }')

    local site_id=$(echo "$response" | jq -r '.site.id')

    if [[ -z "$site_id" || "$site_id" == "null" ]]; then
        log_error "Failed to create Kinsta site"
        echo "$response" | jq '.'
        return 1
    fi

    log_info "Created Kinsta site with ID: $site_id"
    echo "$site_id" > /tmp/kinsta_site_id.txt
    return 0
}</code></pre>
                    </div>

                    <h3>Environment Variables</h3>
                    <ul>
                        <li><code>KINSTA_API_KEY</code> - Kinsta API authentication token</li>
                        <li><code>KINSTA_COMPANY_ID</code> - Company/organization ID</li>
                        <li><code>KINSTA_ENVIRONMENT_ID</code> - Environment ID (optional)</li>
                    </ul>

                    <div class="alert alert-info">
                        <strong>API Rate Limits:</strong> Kinsta API allows 60 requests per hour. Site creation counts as 1 request.
                    </div>
                </section>

                <!-- actions.sh -->
                <section class="content-section">
                    <h2><i class="fab fa-github"></i> actions.sh - GitHub Actions Trigger</h2>
                    <p>Triggers GitHub Actions workflows via repository dispatch events.</p>

                    <h3>Purpose</h3>
                    <ul>
                        <li>Trigger <code>deploy_site</code> workflow</li>
                        <li>Pass deployment parameters to workflow</li>
                        <li>Verify workflow started successfully</li>
                        <li>Return workflow run ID for status tracking</li>
                    </ul>

                    <h3>Usage</h3>
                    <pre><code>./scripts/actions.sh [workflow_type]

Workflow Types:
  deploy      Trigger deployment workflow (default)
  test        Trigger test workflow
  rollback    Trigger rollback workflow

Examples:
  ./scripts/actions.sh deploy
  ./scripts/actions.sh rollback</code></pre>

                    <h3>Key Functions</h3>
                    <div class="function-block">
                        <h4>trigger_github_workflow()</h4>
                        <p>Sends repository dispatch event to GitHub:</p>
                        <pre><code>trigger_github_workflow() {
    local workflow_type="${1:-deploy}"
    local site_id=$(cat /tmp/kinsta_site_id.txt 2>/dev/null)

    local response=$(curl -s -X POST \
        "https://api.github.com/repos/$GITHUB_REPO/dispatches" \
        -H "Authorization: token $GITHUB_TOKEN" \
        -H "Accept: application/vnd.github.v3+json" \
        -d '{
            "event_type": "'"$workflow_type"'_site",
            "client_payload": {
                "site_id": "'"$site_id"'",
                "timestamp": "'"$(date -u +"%Y-%m-%dT%H:%M:%SZ")"'"
            }
        }')

    if [[ $(echo "$response" | jq -r '.message // empty') == "Not Found" ]]; then
        log_error "Repository not found or token lacks permissions"
        return 1
    fi

    log_info "Triggered $workflow_type workflow"
    return 0
}</code></pre>
                    </div>

                    <h3>Workflow Payload</h3>
                    <p>The script passes this data to GitHub Actions:</p>
                    <pre><code>{
    "event_type": "deploy_site",
    "client_payload": {
        "site_id": "kinsta_abc123",
        "site_name": "janedoe2026",
        "branch": "main",
        "timestamp": "2026-01-20T15:30:45Z",
        "deploy_user": "admin"
    }
}</code></pre>

                    <h3>Environment Variables</h3>
                    <ul>
                        <li><code>GITHUB_TOKEN</code> - Personal Access Token with <code>repo</code> and <code>workflow</code> scopes</li>
                        <li><code>GITHUB_REPO</code> - Repository in format <code>owner/repo</code></li>
                    </ul>
                </section>

                <!-- status.sh -->
                <section class="content-section">
                    <h2><i class="fas fa-tasks"></i> status.sh - Deployment Status Monitor</h2>
                    <p>Monitors deployment progress and updates status in real-time.</p>

                    <h3>Purpose</h3>
                    <ul>
                        <li>Poll GitHub Actions workflow status</li>
                        <li>Update progress in <code>/tmp/deployment_status.json</code></li>
                        <li>Display real-time progress to users</li>
                        <li>Detect failures and report errors</li>
                        <li>Calculate deployment duration</li>
                    </ul>

                    <h3>Usage</h3>
                    <pre><code>./scripts/status.sh [workflow_run_id]

Examples:
  ./scripts/status.sh 1234567890        # Monitor specific workflow
  ./scripts/status.sh --latest          # Monitor latest workflow
  ./scripts/status.sh --wait            # Block until completion</code></pre>

                    <h3>Status JSON Structure</h3>
                    <pre><code>{
    "status": "in_progress",              // queued, in_progress, success, failure
    "stage": "Syncing files to Kinsta",   // Current deployment stage
    "progress": 60,                       // Percentage (0-100)
    "started_at": "2026-01-20T15:30:45Z",
    "updated_at": "2026-01-20T15:35:12Z",
    "estimated_completion": "2026-01-20T15:40:00Z",
    "workflow_url": "https://github.com/owner/repo/actions/runs/1234567890",
    "logs": [
        {
            "timestamp": "2026-01-20T15:30:45Z",
            "level": "info",
            "message": "Starting deployment..."
        }
    ]
}</code></pre>

                    <h3>Key Functions</h3>
                    <div class="function-block">
                        <h4>poll_workflow_status()</h4>
                        <p>Continuously checks GitHub Actions status:</p>
                        <pre><code>poll_workflow_status() {
    local run_id="$1"
    local max_attempts=60  # 10 minutes (60 * 10 seconds)
    local attempt=0

    while [[ $attempt -lt $max_attempts ]]; do
        local response=$(curl -s -X GET \
            "https://api.github.com/repos/$GITHUB_REPO/actions/runs/$run_id" \
            -H "Authorization: token $GITHUB_TOKEN")

        local status=$(echo "$response" | jq -r '.status')
        local conclusion=$(echo "$response" | jq -r '.conclusion')

        case "$status" in
            "completed")
                if [[ "$conclusion" == "success" ]]; then
                    log_info "Deployment completed successfully"
                    return 0
                else
                    log_error "Deployment failed: $conclusion"
                    return 1
                fi
                ;;
            "in_progress")
                log_info "Deployment in progress... (attempt $attempt/$max_attempts)"
                ;;
            *)
                log_warning "Unknown status: $status"
                ;;
        esac

        attempt=$((attempt + 1))
        sleep 10
    done

    log_error "Deployment timeout after 10 minutes"
    return 2
}</code></pre>
                    </div>
                </section>

                <!-- Utility Scripts -->
                <section class="content-section">
                    <h2><i class="fas fa-tools"></i> Utility Scripts</h2>
                    <p>These scripts provide shared functionality and are sourced by other scripts.</p>

                    <h3>logger.sh - Logging Utilities</h3>
                    <p>Provides standardized logging functions with color-coded output and file logging.</p>

                    <h4>Functions</h4>
                    <div class="function-block">
                        <pre><code>log_info "message"      # Info-level log (green)
log_warning "message"   # Warning-level log (yellow)
log_error "message"     # Error-level log (red)
log_debug "message"     # Debug-level log (cyan) - only if DEBUG=1
log_success "message"   # Success-level log (bright green)</code></pre>
                    </div>

                    <h4>Log File Location</h4>
                    <p>Logs are written to <code>/logs/deployment/deploy_YYYYMMDD_HHMMSS.log</code></p>

                    <h4>Usage Example</h4>
                    <pre><code>#!/bin/bash
source "$(dirname "$0")/logger.sh"

log_info "Starting deployment process"
log_warning "This is a test environment"
log_error "Configuration file missing"
log_success "Deployment completed"</code></pre>

                    <hr>

                    <h3>api.sh - API Interaction Helpers</h3>
                    <p>Wrapper functions for common API operations with retry logic and error handling.</p>

                    <h4>Functions</h4>
                    <div class="function-block">
                        <pre><code>api_request()          # Generic API request with retry
github_api()           # GitHub API wrapper
kinsta_api()           # Kinsta API wrapper
check_api_response()   # Validate API response
parse_json_field()     # Extract field from JSON response</code></pre>
                    </div>

                    <h4>Usage Example</h4>
                    <pre><code>#!/bin/bash
source "$(dirname "$0")/api.sh"

# Make GitHub API request with retry
response=$(github_api GET "/repos/$GITHUB_REPO")
repo_name=$(parse_json_field "$response" "name")

# Make Kinsta API request
kinsta_response=$(kinsta_api POST "/sites" '{"name": "test"}')</code></pre>

                    <hr>

                    <h3>creds.sh - Credentials Management</h3>
                    <p>Securely loads and manages API credentials from environment or encrypted storage.</p>

                    <h4>Functions</h4>
                    <div class="function-block">
                        <pre><code>load_credentials()     # Load all credentials
validate_credentials() # Check required credentials exist
encrypt_credential()   # Encrypt sensitive data
decrypt_credential()   # Decrypt sensitive data</code></pre>
                    </div>

                    <h4>Credential Sources (in priority order)</h4>
                    <ol>
                        <li>Environment variables (<code>$GITHUB_TOKEN</code>, <code>$KINSTA_API_KEY</code>)</li>
                        <li>Encrypted credentials file (<code>/config/.credentials.enc</code>)</li>
                        <li>System keychain (macOS only)</li>
                    </ol>

                    <div class="alert alert-warning">
                        <strong>Security:</strong> Never commit credentials to Git. Use <code>.gitignore</code> to exclude credential files.
                    </div>
                </section>

                <!-- Error Handling -->
                <section class="content-section">
                    <h2><i class="fas fa-exclamation-triangle"></i> Error Handling</h2>

                    <h3>Common Exit Codes</h3>
                    <table class="error-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Meaning</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>0</code></td>
                                <td>Success</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><code>1</code></td>
                                <td>General error / validation failed</td>
                                <td>Check error logs for details</td>
                            </tr>
                            <tr>
                                <td><code>2</code></td>
                                <td>Git operations failed</td>
                                <td>Verify Git config and repository access</td>
                            </tr>
                            <tr>
                                <td><code>3</code></td>
                                <td>API error (GitHub/Kinsta)</td>
                                <td>Check API credentials and rate limits</td>
                            </tr>
                            <tr>
                                <td><code>4</code></td>
                                <td>Timeout</td>
                                <td>Wait and retry deployment</td>
                            </tr>
                            <tr>
                                <td><code>5</code></td>
                                <td>Missing dependencies</td>
                                <td>Install required tools (jq, curl, git)</td>
                            </tr>
                        </tbody>
                    </table>

                    <h3>Debugging Tips</h3>
                    <ul>
                        <li>Enable verbose mode: <code>./script.sh --verbose</code></li>
                        <li>Enable debug logging: <code>DEBUG=1 ./script.sh</code></li>
                        <li>Check deployment logs: <code>tail -f /logs/deployment/*.log</code></li>
                        <li>Test JSON syntax: <code>jq empty config.json</code></li>
                        <li>Test API credentials: <code>curl -H "Authorization: token $GITHUB_TOKEN" https://api.github.com/user</code></li>
                    </ul>
                </section>

                <!-- Best Practices -->
                <section class="content-section">
                    <h2><i class="fas fa-star"></i> Best Practices</h2>

                    <h3>Script Development</h3>
                    <ul>
                        <li><strong>Always source utilities:</strong> Include <code>source "$(dirname "$0")/logger.sh"</code></li>
                        <li><strong>Use absolute paths:</strong> Avoid relative paths that break when PWD changes</li>
                        <li><strong>Quote variables:</strong> <code>"$var"</code> prevents word splitting</li>
                        <li><strong>Check exit codes:</strong> <code>if ! command; then handle_error; fi</code></li>
                        <li><strong>Use set -e:</strong> Exit immediately on error</li>
                        <li><strong>Validate inputs:</strong> Check all parameters before processing</li>
                    </ul>

                    <h3>API Interactions</h3>
                    <ul>
                        <li><strong>Implement retry logic:</strong> Network requests can fail transiently</li>
                        <li><strong>Check rate limits:</strong> Respect API quotas</li>
                        <li><strong>Parse responses:</strong> Don't assume success - check HTTP status codes</li>
                        <li><strong>Use jq for JSON:</strong> Don't parse JSON with grep/sed</li>
                    </ul>

                    <h3>Security</h3>
                    <ul>
                        <li><strong>Never log credentials:</strong> Sanitize logs before writing</li>
                        <li><strong>Use environment variables:</strong> Don't hardcode secrets</li>
                        <li><strong>Validate all inputs:</strong> Prevent injection attacks</li>
                        <li><strong>Restrict file permissions:</strong> <code>chmod 600</code> for credential files</li>
                    </ul>
                </section>

                <!-- Related Resources -->
                <section class="content-section">
                    <h2><i class="fas fa-link"></i> Related Resources</h2>
                    <ul>
                        <li><a href="deployment-flow.php">Technical Deployment Workflow</a></li>
                        <li><a href="json-format.php">JSON Configuration Schemas</a></li>
                        <li><a href="troubleshooting.php">Troubleshooting Guide</a></li>
                        <li><a href="architecture.php">System Architecture</a></li>
                    </ul>
                </section>

                <!-- Navigation -->
                <nav class="doc-nav">
                    <a href="json-format.php" class="nav-prev">
                        <i class="fas fa-arrow-left"></i>
                        <div>
                            <span>Previous</span>
                            <strong>JSON Configuration</strong>
                        </div>
                    </a>
                    <a href="deployment-flow.php" class="nav-next">
                        <div>
                            <span>Next</span>
                            <strong>Deployment Flow</strong>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </nav>
            </div>
        </main>
    </div>

    <style>
    .execution-flow {
        margin: 2rem 0;
    }

    .flow-step {
        display: flex;
        align-items: center;
        background: var(--bg-secondary);
        padding: 1.5rem;
        border-radius: 0.5rem;
        border-left: 4px solid var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .step-number {
        background: var(--primary-color);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.2rem;
        margin-right: 1.5rem;
        flex-shrink: 0;
    }

    .step-content h4 {
        margin: 0 0 0.25rem 0;
        color: var(--primary-color);
    }

    .step-content p {
        margin: 0;
        color: var(--text-medium);
    }

    .flow-arrow {
        text-align: center;
        font-size: 2rem;
        color: var(--primary-color);
        margin: 0.5rem 0;
    }

    .function-block {
        background: var(--bg-tertiary);
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin: 1rem 0;
    }

    .function-block h4 {
        color: var(--primary-color);
        margin-bottom: 0.75rem;
        font-family: 'Courier New', monospace;
    }

    .function-block pre {
        margin-top: 0.75rem;
    }

    .error-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0;
    }

    .error-table th,
    .error-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    .error-table th {
        background: var(--bg-tertiary);
        font-weight: 600;
    }

    .error-table code {
        background: var(--bg-secondary);
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
    }

    hr {
        border: none;
        border-top: 1px solid var(--border-color);
        margin: 2rem 0;
    }
    </style>
</body>
</html>
