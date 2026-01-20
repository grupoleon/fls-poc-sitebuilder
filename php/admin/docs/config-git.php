<?php
    require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Git Configuration - Frontline Framework</title>

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
                        <h1>Git & Deployment Configuration</h1>
                        <p>GitHub repository and deployment settings</p>
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
                    <span>Git Configuration</span>
                </nav>

                <h1 class="page-title">Git & Deployment Configuration</h1>
                <p class="page-subtitle">Configure GitHub repository connection, deployment branch, and server details for automated deployments.</p>

                <!-- Overview -->
                <section class="content-section">
                    <h2><i class="fab fa-github"></i> Overview</h2>

                    <p>The Git Configuration connects this interface to your GitHub repository where deployment files are stored. This enables:</p>
                    <ul>
                        <li><strong>Automated Deployments:</strong> Upload configurations to GitHub, trigger Actions workflows</li>
                        <li><strong>Version Control:</strong> Track all configuration changes via Git commits</li>
                        <li><strong>Rollback Capability:</strong> Revert to previous configurations if needed</li>
                        <li><strong>CI/CD Integration:</strong> GitHub Actions automatically deploy to Kinsta hosting</li>
                    </ul>

                    <div class="alert alert-info">
                        <div class="alert-title">
                            <i class="fas fa-info-circle"></i>
                            Prerequisites
                        </div>
                        <p>Before configuring, ensure you have:</p>
                        <ul>
                            <li>A GitHub account with repository access</li>
                            <li>GitHub Personal Access Token with repo permissions</li>
                            <li>The target repository created on GitHub</li>
                        </ul>
                    </div>
                </section>

                <!-- API Tokens -->
                <section class="content-section">
                    <h2><i class="fas fa-key"></i> GitHub Personal Access Token</h2>

                    <p>The Personal Access Token (PAT) authenticates this interface with GitHub API to:</p>
                    <ul>
                        <li>List your organizations and repositories</li>
                        <li>Push configuration files to the repository</li>
                        <li>Trigger GitHub Actions workflows</li>
                        <li>Read branch information</li>
                    </ul>

                    <h3>Creating a GitHub Personal Access Token</h3>
                    <ol>
                        <li>Go to <a href="https://github.com/settings/tokens" target="_blank">GitHub Settings → Developer Settings → Personal Access Tokens</a></li>
                        <li>Click <strong>"Generate new token (classic)"</strong></li>
                        <li>Give it a descriptive name (e.g., "Frontline Framework - Site Builder")</li>
                        <li>Set expiration (recommended: 90 days - renewable)</li>
                        <li>Select the following scopes:
                            <ul>
                                <li><code>repo</code> - Full control of private repositories</li>
                                <li><code>workflow</code> - Update GitHub Action workflows</li>
                                <li><code>read:org</code> - Read organization membership</li>
                            </ul>
                        </li>
                        <li>Click <strong>"Generate token"</strong></li>
                        <li>Copy the token immediately (it won't be shown again)</li>
                        <li>Paste it in the "GitHub Personal Access Token" field</li>
                        <li>Click the <strong>"Edit"</strong> button to unlock the field and save</li>
                    </ol>

                    <div class="alert alert-danger">
                        <div class="alert-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Security Warning
                        </div>
                        <p><strong>NEVER share your Personal Access Token!</strong></p>
                        <ul>
                            <li>Treat it like a password - keep it confidential</li>
                            <li>Don't commit tokens to public repositories</li>
                            <li>If compromised, immediately revoke and generate a new one</li>
                            <li>Use fine-grained tokens with minimal permissions when possible</li>
                        </ul>
                    </div>

                    <h3>Token Format</h3>
                    <p>GitHub Personal Access Tokens start with:</p>
                    <ul>
                        <li><code>ghp_</code> - Classic tokens</li>
                        <li><code>github_pat_</code> - Fine-grained tokens (newer)</li>
                    </ul>
                    <p><strong>Example:</strong> <code>ghp_1234567890abcdefghijklmnopqrstuvwxyzABCD</code></p>
                </section>

                <!-- Repository Settings -->
                <section class="content-section">
                    <h2><i class="fas fa-folder-open"></i> Repository Settings</h2>

                    <p>Select the GitHub repository that stores your deployment configurations and scripts.</p>

                    <h3>GitHub Organization</h3>
                    <p>Choose the organization or personal account that owns the repository. The dropdown will populate with:</p>
                    <ul>
                        <li><strong>Your personal account</strong> (your GitHub username)</li>
                        <li><strong>Organizations you belong to</strong> (if you're a member)</li>
                    </ul>
                    <p>Click the <strong>Refresh</strong> button if you recently created an organization or gained access.</p>

                    <h3>Repository Name</h3>
                    <p>After selecting an organization, choose the specific repository for deployments. This is typically named:</p>
                    <ul>
                        <li><code>WebsiteBuild</code> - The standard deployment repository</li>
                        <li>Or your custom repository name</li>
                    </ul>

                    <div class="alert alert-warning">
                        <div class="alert-title">
                            <i class="fas fa-info-circle"></i>
                            Repository Structure Required
                        </div>
                        <p>The selected repository must contain:</p>
                        <ul>
                            <li><code>.github/workflows/</code> - GitHub Actions workflow files</li>
                            <li><code>scripts/</code> - Bash deployment scripts (template.sh, forms.sh, etc.)</li>
                            <li><code>wp-content/themes/</code> - WordPress themes</li>
                        </ul>
                        <p>See <a href="architecture.php">System Architecture</a> for details.</p>
                    </div>

                    <h3>Deployment Branch</h3>
                    <p>Select which Git branch triggers deployments. Common options:</p>
                    <ul>
                        <li><strong>main</strong> - Production deployments (recommended)</li>
                        <li><strong>develop</strong> - Staging/development deployments</li>
                        <li><strong>Custom branch</strong> - For testing or specific environments</li>
                    </ul>
                    <p>When configurations are uploaded, they're pushed to this branch, triggering GitHub Actions workflows.</p>
                </section>

                <!-- Server Connection -->
                <section class="content-section">
                    <h2><i class="fas fa-server"></i> Server Connection (SSH)</h2>

                    <p>These settings configure the SSH connection for uploading configuration files to the remote server before triggering GitHub Actions.</p>

                    <h3>Connection Fields</h3>

                    <div class="card">
                        <h4><i class="fas fa-network-wired"></i> Host</h4>
                        <p>The server hostname or IP address.</p>
                        <p><strong>Example:</strong> <code>your-site.kinsta.cloud</code> or <code>192.168.1.100</code></p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-user"></i> Username</h4>
                        <p>SSH username for authentication.</p>
                        <p><strong>Example:</strong> <code>yoursitename</code> or <code>root</code></p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-plug"></i> Port</h4>
                        <p>SSH port number.</p>
                        <p><strong>Default:</strong> <code>22</code> (standard SSH port)</p>
                        <p><strong>Kinsta:</strong> Custom port (provided in Kinsta dashboard)</p>
                    </div>

                    <div class="card">
                        <h4><i class="fas fa-folder"></i> Server Path</h4>
                        <p>Full path on the server where configuration files are uploaded.</p>
                        <p><strong>Example:</strong> <code>/www/yoursitename_123/public/wp-content/</code></p>
                        <p><strong>Kinsta:</strong> Path shown in Kinsta → Sites → Info → Server Paths</p>
                    </div>

                    <h3>SSH Authentication</h3>
                    <p>SSH authentication is typically configured in Local Config using:</p>
                    <ul>
                        <li><strong>SSH Key:</strong> Private key file for passwordless authentication (recommended)</li>
                        <li><strong>Password:</strong> Server password (less secure)</li>
                    </ul>
                    <p>See <strong>Local Config</strong> tab for SSH key setup.</p>
                </section>

                <!-- Configuration Workflow -->
                <section class="content-section">
                    <h2><i class="fas fa-project-diagram"></i> Configuration Workflow</h2>

                    <p>Here's how Git Configuration integrates with the deployment process:</p>

                    <h3>Step-by-Step Flow</h3>
                    <ol>
                        <li><strong>Configure Git Settings:</strong> Set up GitHub token, organization, repository, and branch</li>
                        <li><strong>Save Configuration:</strong> Click "Save Git Configuration" to store settings</li>
                        <li><strong>Upload Configs (Deployment Tab):</strong> When you click "Deploy", the system:
                            <ul>
                                <li>Generates JSON configuration files from your settings</li>
                                <li>Uploads files to the server via SSH (using these settings)</li>
                                <li>Commits and pushes to the GitHub repository</li>
                                <li>Triggers GitHub Actions workflow on the deployment branch</li>
                            </ul>
                        </li>
                        <li><strong>GitHub Actions Execution:</strong> Workflow runs deployment scripts on Kinsta</li>
                        <li><strong>Site Deployed:</strong> WordPress site is configured automatically</li>
                    </ol>
                </section>

                <!-- Testing Connection -->
                <section class="content-section">
                    <h2><i class="fas fa-vial"></i> Testing Your Configuration</h2>

                    <h3>Verify GitHub Connection</h3>
                    <ul>
                        <li>After entering your token, the organization dropdown should populate</li>
                        <li>Select an organization - repository dropdown should show available repos</li>
                        <li>Select a repo - branch dropdown should display branches</li>
                        <li>If dropdowns don't populate, check:
                            <ul>
                                <li>Token is valid and not expired</li>
                                <li>Token has correct permissions (repo, workflow, read:org)</li>
                                <li>Organization/repository exists and you have access</li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Verify SSH Connection</h3>
                    <p>Test SSH connection manually:</p>
                    <ol>
                        <li>Open Terminal</li>
                        <li>Run: <code>ssh -p [PORT] [USERNAME]@[HOST]</code></li>
                        <li>You should successfully connect to the server</li>
                        <li>If connection fails:
                            <ul>
                                <li>Check host, username, and port are correct</li>
                                <li>Verify SSH key is configured properly</li>
                                <li>Check firewall settings allow SSH connections</li>
                            </ul>
                        </li>
                    </ol>
                </section>

                <!-- Troubleshooting -->
                <section class="content-section">
                    <h2><i class="fas fa-wrench"></i> Troubleshooting</h2>

                    <h3>GitHub Token Not Working</h3>
                    <ul>
                        <li><strong>Check token expiration:</strong> Tokens expire - generate a new one if needed</li>
                        <li><strong>Verify permissions:</strong> Ensure token has repo, workflow, and read:org scopes</li>
                        <li><strong>Re-enter token:</strong> Click "Edit" button, clear field, paste token again</li>
                        <li><strong>Test with curl:</strong> <code>curl -H "Authorization: token YOUR_TOKEN" https://api.github.com/user</code></li>
                    </ul>

                    <h3>Repositories Not Showing</h3>
                    <ul>
                        <li>Click the Refresh button next to dropdowns</li>
                        <li>Verify you have access to the repository</li>
                        <li>Check organization membership if using org repos</li>
                        <li>Ensure repository exists on GitHub</li>
                    </ul>

                    <h3>SSH Connection Fails</h3>
                    <ul>
                        <li><strong>Wrong credentials:</strong> Double-check host, username, port, and path</li>
                        <li><strong>SSH key issues:</strong> Verify SSH key is added in Local Config and matches server</li>
                        <li><strong>Firewall blocking:</strong> Ensure SSH port is open on server</li>
                        <li><strong>Path doesn't exist:</strong> Create the path on the server or correct the path value</li>
                    </ul>

                    <h3>Deployment Not Triggering</h3>
                    <ul>
                        <li>Check GitHub Actions workflows exist in <code>.github/workflows/</code></li>
                        <li>Verify workflow is configured for the deployment branch</li>
                        <li>Check GitHub Actions tab for error logs</li>
                        <li>Ensure repository has Actions enabled (Settings → Actions)</li>
                    </ul>
                </section>

                <!-- Best Practices -->
                <section class="content-section">
                    <h2><i class="fas fa-lightbulb"></i> Best Practices</h2>

                    <h3>Security</h3>
                    <ul>
                        <li>Use fine-grained tokens with minimal required permissions</li>
                        <li>Set token expiration and renew regularly</li>
                        <li>Use SSH keys instead of passwords for server authentication</li>
                        <li>Restrict SSH access by IP address when possible</li>
                        <li>Never commit tokens or credentials to Git</li>
                    </ul>

                    <h3>Repository Management</h3>
                    <ul>
                        <li>Use <code>main</code> branch for production deployments</li>
                        <li>Create a <code>develop</code> branch for testing changes</li>
                        <li>Enable branch protection for <code>main</code> to prevent accidental changes</li>
                        <li>Use descriptive commit messages when uploading configs</li>
                    </ul>

                    <h3>Deployment Workflow</h3>
                    <ul>
                        <li>Test configurations on a development/staging site first</li>
                        <li>Keep GitHub repository private to protect sensitive data</li>
                        <li>Monitor GitHub Actions logs for deployment status</li>
                        <li>Document server paths and credentials for team members</li>
                    </ul>
                </section>

                <!-- Navigation -->
                <div class="btn-navigation">
                    <a href="index.php" class="btn-nav prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Documentation Home</span>
                    </a>
                    <a href="config-kinsta.php" class="btn-nav next">
                        <span>Kinsta Settings</span>
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
