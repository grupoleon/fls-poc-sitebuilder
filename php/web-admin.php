<?php
    require_once __DIR__ . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Frontline Framework</title>

        <link rel="icon" href="/php/admin/assets/img/favicon.ico">
        <link rel="stylesheet" href="/php/admin/assets/css/main.css">
        <link rel="stylesheet" href="/php/admin/assets/css/components.css">
        <link rel="stylesheet" href="//fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
        <link rel="stylesheet" href="//fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap">
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
        <link rel="stylesheet" href="/php/admin/assets/css/tools.css">
        <link rel="stylesheet" href="/php/admin/assets/css/forms.css">
        <link rel="stylesheet" href="/php/admin/assets/css/local-config.css">
        <link rel="stylesheet" href="/php/admin/assets/css/raw-configs.css">
        <script src="https://cdn.ckeditor.com/ckeditor5/41.2.1/classic/ckeditor.js"></script>
    </head>

    <body>
        <div class="admin-container">
            <!-- Modern Sidebar Navigation -->
            <nav class="admin-sidebar">
                <div class="sidebar-header">
                    <a href="#" class="logo">
                        <img id="fls-logo" src="/php/admin/assets/img/logo.png" alt="Framework Interface Logo">
                    </a>
                </div>

                <div class="sidebar-nav">
                    <div class="nav-item">
                        <a href="#" class="nav-link active" data-tab="deployment">
                            <i class="fas fa-cloud-upload-alt nav-icon"></i>
                            Deployment
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-tab="configuration">
                            <i class="fas fa-cogs nav-icon"></i>
                            Configuration
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-tab="pages">
                            <i class="fas fa-edit nav-icon"></i>
                            Page Editor
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-tab="contents">
                            <i class="fas fa-list-alt nav-icon"></i>
                            Other Contents
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-tab="forms">
                            <i class="fas fa-file-lines nav-icon"></i>
                            Forms Manager
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-tab="local-config">
                            <i class="fas fa-cog nav-icon"></i>
                            Local Config
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-tab="raw-configs">
                            <i class="fas fa-code nav-icon"></i>
                            Raw Configs
                        </a>
                    </div>
                </div>

                <!-- Help Button -->
                <div class="sidebar-footer"
                    style="padding: var(--space-4); border-top: 1px solid rgba(255, 255, 255, 0.1);">
                    <button type="button" class="btn btn-secondary btn-sm w-full" id="help-btn">
                        <i class="fas fa-question-circle"></i> Help & Shortcuts
                    </button>
                </div>
            </nav>

            <!-- Modern Main Content -->
            <main class="admin-main">
                <!-- <header class="main-header">
                    <p class="header-title">Site In progress : <span id="site-title"> Site Title </span></p>
                    <p class="header-subtitle">Manage your website deployment, configuration, and content</p>
                </header> -->

                <div class="main-content">
                    <!-- Deployment Tab -->
                    <div id="deployment-content" class="tab-content active animate-fadeIn">

                        <!-- Deployment Progress Notice (shown when quick deploy is hidden) -->
                        <div id="deployment-progress-notice" class="deployment-progress-notice">
                            <div class="notice-title">
                                <i class="fas fa-ship"></i>
                                Website Deployment in Progress
                            </div>
                            <div class="notice-text">
                                Your website is being deployed. You can monitor the progress below.
                            </div>
                            <div class="notice-actions mt-3">
                                <button onclick="window.forceGitHubCompletion()" class="btn btn-sm btn-outline-light">
                                    <i class="fas fa-refresh me-2"></i>Check Status
                                </button>
                                <button onclick="window.adminInterface.deployNewSite()"
                                    class="btn btn-sm btn-outline-light ml-2">
                                    <i class="fas fa-rocket me-2"></i>Start Over
                                </button>
                            </div>
                        </div>

                        <div class="card mb-6" id="quick-deploy-card">
                            <div class="card-header">
                                <h2 class="card-title">Deploy Your Website</h2>
                            </div>
                            <div class="card-body" id="quick-deploy-body">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label">Site Title</label>
                                        <input type="text" id="deployment-site-title" class="form-input"
                                            placeholder="Enter site title">
                                        <div class="form-help">Enter your website's name (e.g., "My Campaign Site")
                                        </div>
                                        <div id="site-title-warning" class="form-warning"
                                            style="display: none; margin-top: 8px; padding: 8px 12px; background-color: #fffbeb; border-left: 3px solid #f59e0b; border-radius: 4px; font-size: 0.875rem; color: #92400e;">
                                            <i class="fas fa-exclamation-triangle" style="margin-right: 6px;"></i>
                                            <span id="site-title-warning-text"></span>
                                        </div>
                                        <div id="delete-existing-site-option"
                                            style="display: none; margin-top: 12px; padding: 12px; background-color: #fee2e2; border-left: 3px solid #dc2626; border-radius: 4px;">
                                            <label
                                                style="display: flex; align-items: center; cursor: pointer; font-size: 0.875rem; color: #991b1b;">
                                                <input type="checkbox" id="delete-existing-site-checkbox"
                                                    style="margin-right: 8px; cursor: pointer;">
                                                <span style="font-weight: 600;">
                                                    <i class="fas fa-trash-alt" style="margin-right: 4px;"></i>
                                                    Delete existing site before deploying
                                                </span>
                                            </label>
                                            <div
                                                style="margin-top: 8px; font-size: 0.8125rem; color: #7f1d1d; line-height: 1.4;">
                                                <i class="fas fa-exclamation-circle" style="margin-right: 4px;"></i>
                                                <strong>Warning:</strong> This will permanently delete the existing site
                                                and all its data from Kinsta before creating a new one. This action
                                                cannot be undone.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Theme</label>
                                        <select id="deployment-theme-select" class="form-select">
                                            <option value="">Loading themes...</option>
                                        </select>
                                        <div class="form-help">Choose the visual design for your website</div>
                                    </div>
                                </div>
                                <div class="mt-4 d-flex gap-3 align-items-center flex-wrap">
                                    <button type="button" class="btn btn-primary deploy-btn" data-action="full">
                                        <i class="fas fa-desktop me-2"></i>Deploy Website
                                    </button>
                                    <button type="button" class="btn btn-outline-danger reset-btn">
                                        <i class="fas fa-redo me-2"></i>Reset System
                                    </button>
                                </div>
                            </div>


                            <div class="card mb-6">
                                <div class="card-header text-white">
                                    <h2 class="card-title text-white mb-0">Deployment Progress</h2>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="status-badge bg-gray-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                            READY</div>
                                    </div>
                                </div>
                                <div class="card-body p-6">
                                    <!-- Compact Horizontal Steps (Default View) -->
                                    <div id="deployment-status-compact" class="deployment-status-compact mb-4">
                                        <div class="compact-steps-container">
                                            <div class="compact-step pending" data-step="create-site">
                                                <div class="step-icon"><i class="fas fa-server"></i></div>
                                                <div class="step-label">Setup Kinsta</div>
                                                <div class="step-status">pending</div>
                                                <div class="step-time">Waiting...</div>
                                            </div>
                                            <div class="step-connector"></div>
                                            <div class="compact-step pending" data-step="get-cred">
                                                <div class="step-icon"><i class="fas fa-key"></i></div>
                                                <div class="step-label">Credentials</div>
                                                <div class="step-status">pending</div>
                                                <div class="step-time">Waiting...</div>
                                            </div>
                                            <div class="step-connector"></div>
                                            <div class="compact-step pending" data-step="trigger-deploy">
                                                <div class="step-icon"><i class="fas fa-computer"></i></div>
                                                <div class="step-label">Deploy</div>
                                                <div class="step-status">pending</div>
                                                <div class="step-time">Waiting...</div>
                                            </div>
                                            <div class="step-connector"></div>
                                            <div class="compact-step pending" data-step="github-actions">
                                                <div class="step-icon"><i class="fab fa-github"></i></div>
                                                <div class="step-label">Actions</div>
                                                <div class="step-status">pending</div>
                                                <div class="step-time">Waiting...</div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Detailed Vertical Steps (Hidden by Default) -->
                                    <div id="deployment-status-list" class="space-y-4" style="display: none;">

                                        <!-- Create Site Step -->
                                        <div class="status-step-card pending bg-gradient-to-r from-gray-50 to-slate-50 border border-gray-200 rounded-xl p-4 shadow-sm opacity-60"
                                            data-step="create-site">
                                            <div class="flex items-center space-x-4">
                                                <div
                                                    class="status-icon-large bg-gradient-to-r from-gray-400 to-slate-400 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold shadow-lg">
                                                    <i class="fas fa-plus-circle"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <h3
                                                        class="status-step-title text-lg font-semibold text-emerald-800 mb-1">
                                                        Initiate Site Creation</h3>
                                                    <p class="status-step-desc text-gray-500 text-sm mb-2">Creating
                                                        WordPress site on Kinsta platform</p>
                                                    <div
                                                        class="status-step-time text-xs text-gray-400 font-mono bg-gray-100 px-2 py-1 rounded">
                                                        Waiting...</div>
                                                </div>
                                                <div class="status-pending-icon text-gray-400 text-xl">WAIT</div>
                                            </div>
                                        </div>

                                        <!-- Get Credentials Step -->
                                        <div class="status-step-card pending bg-gradient-to-r from-gray-50 to-slate-50 border border-gray-200 rounded-xl p-4 shadow-sm opacity-60"
                                            data-step="get-cred">
                                            <div class="flex items-center space-x-4">
                                                <div
                                                    class="status-icon-large bg-gradient-to-r from-gray-400 to-slate-400 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold shadow-lg">
                                                    <i class="fas fa-key"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <h3
                                                        class="status-step-title text-lg font-semibold text-emerald-800 mb-1">
                                                        Get Credentials</h3>
                                                    <p class="status-step-desc text-gray-500 text-sm mb-2">Retrieving
                                                        site
                                                        access credentials</p>
                                                    <div
                                                        class="status-step-time text-xs text-gray-400 font-mono bg-gray-100 px-2 py-1 rounded">
                                                        Waiting...</div>
                                                </div>
                                                <div class="status-pending-icon text-gray-400 text-xl">WAIT</div>
                                            </div>
                                        </div>

                                        <!-- Trigger Deploy Step -->
                                        <div class="status-step-card pending bg-gradient-to-r from-gray-50 to-slate-50 border border-gray-200 rounded-xl p-4 shadow-sm opacity-60"
                                            data-step="trigger-deploy">
                                            <div class="flex items-center space-x-4">
                                                <div
                                                    class="status-icon-large bg-gradient-to-r from-gray-400 to-slate-400 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold shadow-lg">
                                                    <i class="fas fa-computer"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <h3
                                                        class="status-step-title text-lg font-semibold text-gray-600 mb-1">
                                                        Trigger Deployment</h3>
                                                    <p class="status-step-desc text-gray-500 text-sm mb-2">Deploying
                                                        theme
                                                        and content to site</p>
                                                    <div
                                                        class="status-step-time text-xs text-gray-400 font-mono bg-gray-100 px-2 py-1 rounded">
                                                        Waiting...</div>
                                                </div>
                                                <div class="status-pending-icon text-gray-400 text-xl">WAIT</div>
                                            </div>
                                        </div>

                                        <!-- GitHub Actions Step -->
                                        <div class="status-step-card pending bg-gradient-to-r from-gray-50 to-slate-50 border border-gray-200 rounded-xl p-4 shadow-sm opacity-60"
                                            data-step="github-actions">
                                            <div class="flex items-center space-x-4">
                                                <div
                                                    class="status-icon-large bg-gradient-to-r from-gray-400 to-slate-400 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold shadow-lg">
                                                    <i class="fab fa-github"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <h3
                                                        class="status-step-title text-lg font-semibold text-gray-600 mb-1">
                                                        GitHub Actions</h3>
                                                    <p class="status-step-desc text-gray-500 text-sm mb-2">Monitoring
                                                        GitHub
                                                        Actions deployment status</p>
                                                    <div
                                                        class="status-step-time text-xs text-gray-400 font-mono bg-gray-100 px-2 py-1 rounded">
                                                        Waiting...</div>
                                                </div>
                                                <div class="flex flex-col items-center space-y-2">
                                                    <div class="status-pending-icon text-gray-400 text-xl">WAIT</div>
                                                    <button onclick="window.adminInterface.forceRefreshGitHubStatus()"
                                                        class="btn btn-xs btn-outline-primary github-refresh-btn"
                                                        title="Refresh GitHub Actions status">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <!-- Manual Control Panel -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h2 class="card-title">Manual Controls</h2>
                                    <p class="text-muted mb-0">Use these buttons if deployment appears stuck</p>
                                </div>
                                <div class="card-body">
                                    <div class="flex gap-3 flex-wrap">
                                        <button onclick="window.forceGitHubCompletion()" class="btn btn-primary btn-sm">
                                            <i class="fas fa-refresh me-2"></i>Force Check GitHub Status
                                        </button>
                                        <button onclick="window.adminInterface.forceRefreshGitHubStatus()"
                                            class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-sync-alt me-2"></i>Refresh Actions Status
                                        </button>
                                        <button onclick="window.forceHideDeploymentForm()"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-eye-slash me-2"></i>Hide Form (Fix UI)
                                        </button>
                                        <button onclick="window.resetGitHubState()"
                                            class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-undo me-2"></i>Reset GitHub State
                                        </button>
                                        <button onclick="window.adminInterface.deployNewSite()"
                                            class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-rocket me-2"></i>Start Over (Deploy New Site)
                                        </button>
                                    </div>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <strong>Tip:</strong> If both form and progress are visible, click "Hide
                                            Form
                                            (Fix UI)" first, then "Force Check GitHub Status"
                                        </small>
                                    </div>
                                    </small>
                                </div>
                            </div>
                        </div>


                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center gap-4">
                                <h2 class="card-title mb-0">Deployment Logs</h2>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm realtime-toggle-btn"
                                        id="realtime-toggle-btn" onclick="window.adminInterface.toggleRealtimeLogs()">
                                        <i class="fas fa-play me-1"></i>
                                        <span class="toggle-text">Start Real-time</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm copy-logs-btn">
                                        Copy Logs
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="log-container" id="deployment-logs">
                                    <div class="text-center text-muted">Loading logs...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuration Tab -->
                    <div id="configuration-content" class="tab-content">
                        <div class="page-header">
                            <h1 class="page-title">Configuration</h1>
                            <p class="page-description">Manage all system configurations</p>
                        </div>

                        <div class="tab-container">
                            <div class="tab-nav">
                                <a href="#" class="tab-link active" data-subtab="git-config">Git Config</a>
                                <a href="#" class="tab-link" data-subtab="site-config">Kinsta Settings</a>
                                <a href="#" class="tab-link" data-subtab="security-config">Security</a>
                                <a href="#" class="tab-link" data-subtab="integrations-config">Integrations</a>
                                <a href="#" class="tab-link" data-subtab="navigation-config">Navigation</a>
                                <a href="#" class="tab-link" data-subtab="plugins-config">Plugins</a>
                                <a href="#" class="tab-link" data-subtab="policies-config">Policies</a>
                            </div>
                        </div>

                        <!-- Git Configuration -->
                        <div id="git-config-tab" class="subtab-content active">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Git & Deployment Configuration</h2>
                                </div>
                                <div class="card-body">
                                    <form id="git-config-form">
                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">API Tokens</h3>
                                            <div class="alert alert-info mb-3"
                                                style="padding: 12px; background: #e8f4fd; border-left: 4px solid #1e90ff; border-radius: 4px;">
                                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                                <strong>Important:</strong> Configure these tokens first before using
                                                the repository dropdowns below.
                                            </div>
                                            <div class="grid grid-cols-1 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">
                                                        GitHub Personal Access Token
                                                        <i class="fas fa-info-circle text-blue-500 ml-1"
                                                            title="Required for GitHub API access. Keep this secure!"></i>
                                                    </label>
                                                    <div
                                                        style="position: relative; display: flex; align-items: center; gap: 8px;">
                                                        <input type="password" id="git-token-input"
                                                            class="form-input config-input token-field"
                                                            data-path="token" data-config-type="git" disabled
                                                            placeholder="ghp_••••••••••••••••••••••••••••••••••••••••"
                                                            style="flex: 1;">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary edit-field-btn"
                                                            data-target="git-token-input" title="Edit Git Token">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                    </div>
                                                    <div class="form-help mt-1">
                                                        <small class="text-muted">
                                                            GitHub Personal Access Token for repository access.
                                                            <a href="https://github.com/settings/tokens" target="_blank"
                                                                class="text-blue-600">
                                                                Generate one here
                                                            </a>
                                                        </small>
                                                    </div>
                                                </div>


                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">Repository Settings</h3>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">
                                                        GitHub Organization
                                                        <i class="fas fa-info-circle text-blue-500 ml-1"
                                                            title="Select your GitHub organization or username"></i>
                                                    </label>
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <select id="git-org-select" class="form-select config-input"
                                                            data-path="org" style="flex: 1;">
                                                            <option value="">Loading organizations...</option>
                                                        </select>
                                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                            onclick="window.adminInterface.refreshGitOrgs()"
                                                            title="Refresh organization list from GitHub">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">
                                                        Repository Name
                                                        <i class="fas fa-info-circle text-blue-500 ml-1"
                                                            title="Select repository from the chosen organization"></i>
                                                    </label>
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <select id="git-repo-select" class="form-select config-input"
                                                            data-path="repo" style="flex: 1;">
                                                            <option value="">Select organization first...</option>
                                                        </select>
                                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                            onclick="window.adminInterface.refreshGitRepos()"
                                                            title="Refresh repository list from GitHub">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 gap-4 mt-3">
                                                <div class="form-group">
                                                    <label class="form-label">
                                                        Deployment Branch
                                                        <i class="fas fa-info-circle text-blue-500 ml-1"
                                                            title="Git branch to trigger workflow on"></i>
                                                    </label>
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <select id="git-branch-select" class="form-select config-input"
                                                            data-path="branch" style="flex: 1;">
                                                            <option value="">Loading branches...</option>
                                                        </select>
                                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                            onclick="window.adminInterface.refreshGitBranches()"
                                                            title="Refresh branch list from GitHub">
                                                            <i class="fas fa-sync-alt"></i> Refresh
                                                        </button>
                                                    </div>
                                                    <div class="form-help">
                                                        <small class="text-muted">
                                                            Select which branch to use for triggering GitHub Actions
                                                            workflows. Click Refresh to fetch latest branches from
                                                            GitHub.
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">Server Connection</h3>
                                            <div class="grid grid-cols-3 gap-4 mb-4">
                                                <div class="form-group">
                                                    <label class="form-label">Host</label>
                                                    <input type="text" class="form-input config-input" data-path="host">
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Username</label>
                                                    <input type="text" class="form-input config-input" data-path="user">
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Port</label>
                                                    <input type="number" class="form-input config-input"
                                                        data-path="port">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Server Path</label>
                                                <input type="text" class="form-input config-input" data-path="path">
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-primary save-config-btn" data-type="git">
                                            Save Git Configuration
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Kinsta Settings -->
                        <div id="site-config-tab" class="subtab-content">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Kinsta Settings</h2>
                                </div>
                                <div class="card-body">
                                    <form id="site-config-form">
                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">API Access</h3>
                                            <div class="alert alert-info mb-3"
                                                style="padding: 12px; background: #e8f4fd; border-left: 4px solid #1e90ff; border-radius: 4px;">
                                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                                <strong>Important:</strong> Configure the Kinsta API token to enable
                                                site deployment and management.
                                            </div>
                                            <div class="grid grid-cols-1 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">
                                                        Kinsta API Token
                                                        <i class="fas fa-info-circle text-blue-500 ml-1"
                                                            title="Kinsta API token for server management. Keep this secure!"></i>
                                                    </label>
                                                    <div
                                                        style="position: relative; display: flex; align-items: center; gap: 8px;">
                                                        <input type="password" id="kinsta-token-input"
                                                            class="form-input config-input token-field"
                                                            data-path="site.kinsta_token" data-config-type="main"
                                                            disabled
                                                            placeholder="••••••••••••••••••••••••••••••••••••••••"
                                                            style="flex: 1;">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary edit-field-btn"
                                                            data-target="kinsta-token-input" title="Edit Kinsta Token">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                    </div>
                                                    <div class="form-help mt-1">
                                                        <small class="text-muted">
                                                            Kinsta API token for server management.
                                                            <a href="https://my.kinsta.com/company/apiKeys"
                                                                id="kinsta-token-link" target="_blank"
                                                                class="text-blue-600">
                                                                Generate one here
                                                            </a>
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">
                                                        Company ID
                                                        <i class="fas fa-info-circle text-blue-500 ml-1"
                                                            title="Your Kinsta Company ID. Handle with care!"></i>
                                                    </label>
                                                    <div
                                                        style="position: relative; display: flex; align-items: center; gap: 8px;">
                                                        <div style="flex: 1; position: relative;">
                                                            <input type="text" id="company-id-input"
                                                                class="form-input config-input" data-path="company"
                                                                disabled placeholder="Enter your Kinsta Company ID"
                                                                style="width: 100%; padding-right: 40px;">
                                                            <span id="company-validation-icon"
                                                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); display: none;">
                                                            </span>
                                                        </div>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary edit-field-btn"
                                                            data-target="company-id-input" title="Edit Company ID">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                    </div>
                                                    <div id="company-name-display"
                                                        style="margin-top: 8px; font-size: 0.875rem; color: #059669; display: none;">
                                                        <i class="fas fa-building mr-1"></i>
                                                        <span id="company-name-text"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">Site Title</label>
                                                    <input type="text" class="form-input config-input"
                                                        data-path="site_title">
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Display Name</label>
                                                    <input type="text" class="form-input config-input"
                                                        data-path="display_name">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">Admin Email</label>
                                                    <input type="email" class="form-input config-input"
                                                        data-path="admin_email">
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Admin Username</label>
                                                    <input type="text" class="form-input config-input"
                                                        data-path="admin_user">
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">Admin Password</label>
                                                <div class="password-input-container"
                                                    style="position: relative; display: flex; align-items: center; gap: 8px;">
                                                    <input type="password" id="admin-password"
                                                        class="form-input config-input" data-path="admin_password"
                                                        style="flex: 1; padding-right: 80px;">
                                                    <button type="button" id="toggle-password" class="btn btn-sm"
                                                        style="position: absolute; right: 120px; padding: 4px 8px; background: transparent; border: 1px solid #ddd; border-radius: 4px;"
                                                        title="Toggle password visibility">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" id="generate-password"
                                                        class="btn btn-sm btn-secondary"
                                                        style="padding: 4px 12px; white-space: nowrap;"
                                                        title="Generate secure password">
                                                        Generate
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <div class="grid grid-cols-1 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">Region</label>
                                                    <select class="form-select config-input" data-path="region">
                                                        <option value="us-central1">US Central</option>
                                                        <option value="europe-west1">Europe West</option>
                                                        <option value="asia-southeast1">Asia Southeast</option>
                                                    </select>
                                                </div>
                                            </div>



                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">WordPress Language</label>
                                                    <select class="form-select config-input" data-path="wp_language">
                                                        <option value="en_US">English (US)</option>
                                                        <option value="en_GB">English (UK)</option>
                                                        <option value="es_ES">Spanish</option>
                                                        <option value="fr_FR">French</option>
                                                        <option value="de_DE">German</option>
                                                        <option value="it_IT">Italian</option>
                                                        <option value="pt_BR">Portuguese (Brazil)</option>
                                                        <option value="nl_NL">Dutch</option>
                                                        <option value="ja">Japanese</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Install Mode</label>
                                                    <select class="form-select config-input" data-path="install_mode">
                                                        <option value="new">New Installation</option>
                                                        <option value="existing">Existing Installation</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="multisite-toggle" data-path="is_multisite">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="multisite-toggle">
                                                            <i class="fas fa-network-wired toggle-icon"></i>
                                                            Enable Multisite
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="subdomain-multisite-toggle"
                                                                data-path="is_subdomain_multisite">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="subdomain-multisite-toggle">
                                                            <i class="fas fa-sitemap toggle-icon"></i>
                                                            Subdomain Multisite
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="woocommerce-toggle" data-path="woocommerce">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="woocommerce-toggle">
                                                            <i class="fas fa-shopping-cart toggle-icon"></i>
                                                            Install WooCommerce
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="wordpress-seo-toggle" data-path="wordpressseo">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="wordpress-seo-toggle">
                                                            <i class="fas fa-search toggle-icon"></i>
                                                            Install WordPress SEO
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-primary save-config-btn" data-type="site">
                                            Save Kinsta Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Security Configuration -->
                        <div id="security-config-tab" class="subtab-content">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Security Configuration</h2>
                                </div>
                                <div class="card-body">
                                    <form id="security-config-form">
                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">Admin Credentials</h3>
                                            <div
                                                class="form-help mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                                These credentials are used to create the WordPress admin user when
                                                security
                                                hardening is enabled.
                                                They replace the default admin user for enhanced security.
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">
                                                        <i class="fas fa-user text-gray-500 mr-2"></i>
                                                        Admin Username
                                                    </label>
                                                    <input type="text" class="form-input config-input"
                                                        data-path="site.admin.username"
                                                        placeholder="Enter admin username" required>
                                                    <div class="form-help">This will be the WordPress admin username
                                                        after
                                                        security hardening</div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">
                                                        <i class="fas fa-envelope text-gray-500 mr-2"></i>
                                                        Admin Email
                                                    </label>
                                                    <input type="email" class="form-input config-input"
                                                        data-path="site.admin.email"
                                                        placeholder="Enter admin email address" required>
                                                    <div class="form-help">Used for security notifications and admin
                                                        account
                                                        recovery</div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-key text-gray-500 mr-2"></i>
                                                    Admin Password
                                                </label>
                                                <div class="password-input-wrapper">
                                                    <input type="password" class="form-input config-input"
                                                        data-path="site.admin.password"
                                                        placeholder="Enter secure admin password"
                                                        id="admin-password-input" required>
                                                    <button type="button" class="password-toggle-btn"
                                                        onclick="togglePasswordVisibility('admin-password-input')">
                                                        <i class="fas fa-eye" id="admin-password-toggle-icon"></i>
                                                    </button>
                                                    <button type="button" class="password-generate-btn"
                                                        onclick="generateSecurePassword('admin-password-input')">
                                                        <i class="fas fa-random"></i> Generate
                                                    </button>
                                                </div>
                                                <div class="form-help">Use a strong password with at least 12 characters
                                                    including uppercase, lowercase, numbers, and special characters
                                                </div>
                                                <div class="password-strength" id="admin-password-strength"></div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">Access Control</h3>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="geo-blocking-toggle"
                                                                data-path="security.geo_blocking.enabled">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="geo-blocking-toggle">
                                                            <i class="fas fa-globe-americas toggle-icon"></i>
                                                            Enable Geo-Blocking
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="ip-whitelist-toggle"
                                                                data-path="security.ip_whitelist.enabled">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="ip-whitelist-toggle">
                                                            <i class="fas fa-shield-alt toggle-icon"></i>
                                                            Enable IP Whitelisting
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <div class="form-group">
                                                <label class="form-label">Allowed Countries</label>
                                                <div class="dynamic-list country-list" id="countries-list"
                                                    data-path="security.geo_blocking.allowed_countries">
                                                    <div class="dynamic-list-header">
                                                        <span class="dynamic-list-title">Allowed Countries</span>
                                                        <button type="button" class="dynamic-list-add"
                                                            onclick="toggleCountryInput()">
                                                            <i class="fas fa-plus dynamic-list-add-icon"></i>
                                                            Add Country
                                                        </button>
                                                    </div>
                                                    <div class="dynamic-list-input-group" id="country-input-group"
                                                        style="display: none;">
                                                        <div class="dynamic-list-input-wrapper">
                                                            <select class="dynamic-list-select" id="countries-select">
                                                                <option value="">Select a country to add</option>
                                                                <option value="US">🇺🇸 United States</option>
                                                                <option value="CA">🇨🇦 Canada</option>
                                                                <option value="GB">🇬🇧 United Kingdom</option>
                                                                <option value="AU">🇦🇺 Australia</option>
                                                                <option value="DE">🇩🇪 Germany</option>
                                                                <option value="FR">🇫🇷 France</option>
                                                                <option value="JP">🇯🇵 Japan</option>
                                                                <option value="BR">🇧🇷 Brazil</option>
                                                                <option value="IN">🇮🇳 India</option>
                                                                <option value="SG">🇸🇬 Singapore</option>
                                                            </select>
                                                        </div>
                                                        <button type="button" class="dynamic-list-add-btn"
                                                            onclick="addCountryToList()">
                                                            <i class="fas fa-plus"></i> Add
                                                        </button>
                                                    </div>
                                                    <div class="dynamic-list-items" id="countries-items">
                                                        <div class="dynamic-list-empty">
                                                            <div class="dynamic-list-empty-icon">🌍</div>
                                                            <div>No countries selected. Click "Add Country" to begin.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-help">Select countries from the dropdown and click Add.
                                                    Click × to remove.</div>
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">Whitelisted IPs</label>
                                                <div class="dynamic-list ip-list" id="ip-list"
                                                    data-path="security.ip_whitelist.ips">
                                                    <div class="dynamic-list-header">
                                                        <span class="dynamic-list-title">Whitelisted IP Addresses</span>
                                                        <button type="button" class="dynamic-list-add"
                                                            onclick="toggleIPInput()">
                                                            <i class="fas fa-plus dynamic-list-add-icon"></i>
                                                            Add IP
                                                        </button>
                                                    </div>
                                                    <div class="dynamic-list-input-group" id="ip-input-group"
                                                        style="display: none;">
                                                        <div class="dynamic-list-input-wrapper">
                                                            <input type="text" class="dynamic-list-input" id="ip-input"
                                                                placeholder="Enter IP address (e.g., 192.168.1.1 or 10.0.0.0/24)">
                                                        </div>
                                                        <button type="button" class="dynamic-list-add-btn"
                                                            onclick="addIPToList()">
                                                            <i class="fas fa-plus"></i> Add
                                                        </button>
                                                    </div>
                                                    <div class="dynamic-list-items" id="ip-items">
                                                        <div class="dynamic-list-empty">
                                                            <div class="dynamic-list-empty-icon"><i
                                                                    class="fas fa-shield-alt"></i></div>
                                                            <div>No IP addresses added. Click "Add IP" to begin.</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-help">Add IP addresses or CIDR blocks (e.g.,
                                                    192.168.1.0/24). Click × to remove.</div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">Admin Protection</h3>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="admin-protection-toggle"
                                                                data-path="security.admin_protection.enabled">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="admin-protection-toggle">
                                                            <i class="fas fa-user-shield toggle-icon"></i>
                                                            Enable Admin Protection
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="login-protection-toggle"
                                                                data-path="security.login_protection.enabled">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="login-protection-toggle">
                                                            <i class="fas fa-lock toggle-icon"></i>
                                                            Protect Login Page
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">Maximum Login Attempts</label>
                                                    <input type="number" class="form-input config-input"
                                                        data-path="security.login_protection.max_attempts" min="3"
                                                        max="10">
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Lockout Duration (minutes)</label>
                                                    <input type="number" class="form-input config-input"
                                                        data-path="security.login_protection.lockout_duration" min="5"
                                                        max="60">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">WP Security Audit Log</h3>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">Log Viewer Access</label>
                                                    <select class="form-select config-input"
                                                        data-path="wp_security_audit_log.restrict_log_viewer">
                                                        <option value="only_admins">Only Administrators</option>
                                                        <option value="only_superadmins">Only Super Administrators
                                                        </option>
                                                        <option value="custom_capability">Custom Capability</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="incognito-mode-toggle"
                                                                data-path="wp_security_audit_log.incognito_mode">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="incognito-mode-toggle">
                                                            <i class="fas fa-user-secret toggle-icon"></i>
                                                            Enable Incognito Mode
                                                        </label>
                                                    </div>
                                                    <div class="form-help">Hide plugin from WordPress admin menu</div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-3 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="frontend-events-toggle"
                                                                data-path="wp_security_audit_log.frontend_events">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="frontend-events-toggle">
                                                            <i class="fas fa-desktop toggle-icon"></i>
                                                            Log Frontend Events
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="backend-events-toggle"
                                                                data-path="wp_security_audit_log.backend_events">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="backend-events-toggle">
                                                            <i class="fas fa-cogs toggle-icon"></i>
                                                            Log Backend Events
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="login-notification-toggle"
                                                                data-path="wp_security_audit_log.login_page_notification">
                                                            <div class="toggle-switch">
                                                            </div>
                                                        </div>
                                                        <label class="toggle-label" for="login-notification-toggle">
                                                            <i class="fas fa-bell toggle-icon"></i>
                                                            Show Login Page Notification
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">Pruning Date (days)</label>
                                                    <input type="number" class="form-input config-input"
                                                        data-path="wp_security_audit_log.pruning_date_e" min="30"
                                                        max="3650" placeholder="365">
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Pruning Limit (entries)</label>
                                                    <input type="number" class="form-input config-input"
                                                        data-path="wp_security_audit_log.pruning_limit_e" min="1000"
                                                        max="100000" placeholder="10000">
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-3 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="log-404-toggle"
                                                                data-path="wp_security_audit_log.log_404">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="log-404-toggle">
                                                            <i class="fas fa-exclamation-triangle toggle-icon"></i>
                                                            Log 404 Errors
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="purge-404-toggle"
                                                                data-path="wp_security_audit_log.purge_404_log">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="purge-404-toggle">
                                                            <i class="fas fa-trash-alt toggle-icon"></i>
                                                            Auto-Purge 404 Log
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="visitor-404-toggle"
                                                                data-path="wp_security_audit_log.log_visitor_404">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="visitor-404-toggle">
                                                            <i class="fas fa-users toggle-icon"></i>
                                                            Log Visitor 404s
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">WPS Hide Login Protection</h3>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="hide-login-toggle"
                                                                data-path="security.login_protection.hide_login_page">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="hide-login-toggle">
                                                            <i class="fas fa-eye-slash toggle-icon"></i>
                                                            Hide WordPress Login Page
                                                        </label>
                                                    </div>
                                                    <div class="form-help">Redirect wp-login.php to 404 page</div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="limit-attempts-toggle"
                                                                data-path="security.login_protection.limit_failed_attempts">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="limit-attempts-toggle">
                                                            <i class="fas fa-ban toggle-icon"></i>
                                                            Limit Failed Login Attempts
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">Custom Login URL Slug</label>
                                                <input type="text" class="form-input config-input"
                                                    data-path="security.login_protection.custom_login_url"
                                                    placeholder="fls-login">
                                                <div class="form-help">Enter custom slug for login page (e.g., fls-login
                                                    becomes
                                                    yoursite.com/fls-login)</div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">WordPress Hardening</h3>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="disable-editing-toggle"
                                                                data-path="security.wordpress_hardening.disable_file_editing">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="disable-editing-toggle">
                                                            <i class="fas fa-edit toggle-icon"></i>
                                                            Disable File Editing
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="disable-installer-toggle"
                                                                data-path="security.wordpress_hardening.disable_installer">
                                                            <div class="toggle-switch">
                                                            </div>
                                                        </div>
                                                        <label class="toggle-label" for="disable-installer-toggle">
                                                            <i class="fas fa-download toggle-icon"></i>
                                                            Disable WordPress Installer
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-3 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="hide-version-toggle"
                                                                data-path="security.wordpress_hardening.hide_wp_version">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="hide-version-toggle">
                                                            <i class="fas fa-info-circle toggle-icon"></i>
                                                            Hide WordPress Version
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="disable-xmlrpc-toggle"
                                                                data-path="security.wordpress_hardening.disable_xmlrpc">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="disable-xmlrpc-toggle">
                                                            <i class="fas fa-times-circle toggle-icon"></i>
                                                            Disable XML-RPC
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="security-headers-toggle"
                                                                data-path="security.wordpress_hardening.security_headers">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="security-headers-toggle">
                                                            <i class="fas fa-shield-alt toggle-icon"></i>
                                                            Enable Security Headers
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">Two-Factor Authentication</h3>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="two-factor-toggle"
                                                                data-path="security.two_factor_auth.enabled">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="two-factor-toggle">
                                                            <i class="fas fa-mobile-alt toggle-icon"></i>
                                                            Enable Two-Factor Authentication
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="require-all-users-toggle"
                                                                data-path="security.two_factor_auth.required_for_all_users">
                                                            <div class="toggle-switch">
                                                            </div>
                                                        </div>
                                                        <label class="toggle-label" for="require-all-users-toggle">
                                                            <i class="fas fa-users toggle-icon"></i>
                                                            Required for All Users
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">Grace Period (days)</label>
                                                    <input type="number" class="form-input config-input"
                                                        data-path="security.two_factor_auth.grace_period_days" min="0"
                                                        max="30" placeholder="0">
                                                    <div class="form-help">Number of days users have to setup 2FA before
                                                        being forced</div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Required User Roles</label>
                                                    <div class="checkbox-group grid grid-cols-2 gap-2">
                                                        <label class="checkbox-label">
                                                            <input type="checkbox" class="config-checkbox"
                                                                data-path="security.two_factor_auth.required_roles"
                                                                data-value="administrator">
                                                            Administrator
                                                        </label>
                                                        <label class="checkbox-label">
                                                            <input type="checkbox" class="config-checkbox"
                                                                data-path="security.two_factor_auth.required_roles"
                                                                data-value="editor">
                                                            Editor
                                                        </label>
                                                        <label class="checkbox-label">
                                                            <input type="checkbox" class="config-checkbox"
                                                                data-path="security.two_factor_auth.required_roles"
                                                                data-value="author">
                                                            Author
                                                        </label>
                                                        <label class="checkbox-label">
                                                            <input type="checkbox" class="config-checkbox"
                                                                data-path="security.two_factor_auth.required_roles"
                                                                data-value="contributor">
                                                            Contributor
                                                        </label>
                                                        <label class="checkbox-label">
                                                            <input type="checkbox" class="config-checkbox"
                                                                data-path="security.two_factor_auth.required_roles"
                                                                data-value="subscriber">
                                                            Subscriber
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-center">
                                            <button type="button" class="btn btn-primary save-config-btn px-8 py-3"
                                                data-type="security">
                                                <i class="fas fa-shield-alt mr-2"></i>
                                                Save Security Configuration
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Integrations Configuration -->
                        <div id="integrations-config-tab" class="subtab-content">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Integrations Configuration</h2>
                                </div>
                                <div class="card-body">
                                    <form id="integrations-config-form">
                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">Analytics Integration</h3>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="analytics-toggle"
                                                                data-path="integrations.analytics.enabled">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="analytics-toggle">
                                                            <i class="fas fa-chart-bar toggle-icon"></i>
                                                            Enable Analytics Integration
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Google Analytics Tracking ID</label>
                                                    <input type="text" class="form-input config-input"
                                                        data-path="authentication.api_keys.google_analytics"
                                                        placeholder="G-XXXXXXXXXX">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">Forms Integration</h3>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="forms-integration-toggle"
                                                                data-path="integrations.forms.enabled">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="forms-integration-toggle">
                                                            <i class="fas fa-envelope toggle-icon"></i>
                                                            Enable Forms Integration
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="forms-auto-find-toggle"
                                                                data-path="integrations.forms.auto_find_placements">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="forms-auto-find-toggle">
                                                            <i class="fas fa-search-location toggle-icon"></i>
                                                            Auto-Find Form Placements
                                                        </label>
                                                    </div>
                                                    <div class="form-help">Automatically detect placeholders and place
                                                        forms accordingly</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Dynamic Forms Container -->
                                        <div id="dynamic-forms-container">
                                            <div class="text-center py-4">
                                                <i class="fas fa-spinner fa-spin"></i> Loading forms...
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">Social Media Links</h3>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="social-links-toggle"
                                                                data-path="integrations.social_links.enabled">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="social-links-toggle">
                                                            <i class="fas fa-share-alt toggle-icon"></i>
                                                            Enable Social Media Links
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Placement</label>
                                                    <select class="form-select config-input"
                                                        data-path="integrations.social_links.placement">
                                                        <option value="">Loading pages...</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div id="social-links-container">
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Facebook URL</label>
                                                        <input type="url" class="form-input config-input"
                                                            data-path="integrations.social_links.facebook"
                                                            placeholder="https://facebook.com/yourpage">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Twitter/X URL</label>
                                                        <input type="url" class="form-input config-input"
                                                            data-path="integrations.social_links.twitter"
                                                            placeholder="https://twitter.com/youraccount">
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Instagram URL</label>
                                                        <input type="url" class="form-input config-input"
                                                            data-path="integrations.social_links.instagram"
                                                            placeholder="https://instagram.com/youraccount">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">LinkedIn URL</label>
                                                        <input type="url" class="form-input config-input"
                                                            data-path="integrations.social_links.linkedin"
                                                            placeholder="https://linkedin.com/in/yourprofile">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="config-section">
                                            <h3 class="font-semibold mb-3">Google Maps Integration</h3>

                                            <div class="grid grid-cols-3 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="maps-toggle" data-path="integrations.maps.enabled">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="maps-toggle">
                                                            <i class="fas fa-map-marker-alt toggle-icon"></i>
                                                            Enable Google Maps
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Google Maps API Key</label>
                                                    <input type="text" class="form-input config-input"
                                                        data-path="authentication.api_keys.google_maps">
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Map Placement</label>
                                                    <select class="form-select config-input"
                                                        data-path="integrations.maps.placement">
                                                        <option value="">Loading pages...</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 gap-4 mt-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="maps-auto-find-toggle"
                                                                data-path="integrations.maps.auto_find_placements">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="maps-auto-find-toggle">
                                                            <i class="fas fa-search-location toggle-icon"></i>
                                                            Auto-Find Map Placements
                                                        </label>
                                                    </div>
                                                    <div class="form-help">Automatically detect placeholders and place
                                                        maps</div>
                                                </div>
                                            </div>
                                        </div> <!-- Interactive Map Configuration -->
                                        <div class="card mt-4">
                                            <div class="card-header">
                                                <h4 class="card-title">Map Configuration</h4>
                                            </div>
                                            <div class="card-body">
                                                <div class="grid grid-cols-3 gap-4 mb-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Center Latitude</label>
                                                        <input type="number" step="any" class="form-input config-input"
                                                            data-path="integrations.maps.center.lat"
                                                            placeholder="38.8977">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Center Longitude</label>
                                                        <input type="number" step="any" class="form-input config-input"
                                                            data-path="integrations.maps.center.lng"
                                                            placeholder="-77.0365">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Zoom Level</label>
                                                        <input type="number" class="form-input config-input"
                                                            data-path="integrations.maps.zoom" min="1" max="20"
                                                            placeholder="10">
                                                    </div>
                                                </div>

                                                <!-- Map Preview -->
                                                <div class="form-group">
                                                    <label class="form-label">Map Preview</label>
                                                    <div class="text-muted mb-2">
                                                        <small><i class="fas fa-info-circle"></i> Click on the map to
                                                            add
                                                            markers, or use the "Add Marker" button below</small>
                                                    </div>
                                                    <div id="map-preview" class="position-relative"
                                                        style="height: 300px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                        <div class="text-muted">Enter API key to load map preview</div>
                                                    </div>
                                                </div>

                                                <!-- Markers Management -->
                                                <div class="form-group">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <label class="form-label">Map Markers</label>
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            id="add-marker-btn">
                                                            <i class="fas fa-plus"></i> Add Marker
                                                        </button>
                                                    </div>
                                                    <div id="markers-container">
                                                        <!-- Markers will be dynamically populated -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-primary save-config-btn"
                                            data-type="integrations">
                                            Save Integrations Configuration
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Configuration -->
                        <!-- Note: Navigation is saved to config.json (main config) to avoid duplication in site.json -->
                        <div id="navigation-config-tab" class="subtab-content">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Navigation Configuration</h2>
                                </div>
                                <div class="card-body">
                                    <form id="main-config-form">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="form-group">
                                                <div class="toggle-container">
                                                    <div class="toggle-wrapper">
                                                        <input type="checkbox" class="config-input toggle-input"
                                                            id="custom-navigation-toggle"
                                                            data-path="site.navigation.enabled">
                                                        <div class="toggle-switch"></div>
                                                    </div>
                                                    <label class="toggle-label" for="custom-navigation-toggle">
                                                        <i class="fas fa-bars toggle-icon"></i>
                                                        Enable Custom Navigation
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="toggle-container">
                                                    <div class="toggle-wrapper">
                                                        <input type="checkbox" class="config-input toggle-input"
                                                            id="replace-menus-toggle"
                                                            data-path="site.navigation.replace_existing">
                                                        <div class="toggle-switch"></div>
                                                    </div>
                                                    <label class="toggle-label" for="replace-menus-toggle">
                                                        <i class="fas fa-exchange-alt toggle-icon"></i>
                                                        Replace Existing Menus
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <label class="form-label">Menu Items</label>
                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                    id="add-menu-item-btn">
                                                    <i class="fas fa-plus"></i> Add Menu Item
                                                </button>
                                            </div>
                                            <div id="menu-items-container">
                                                <!-- Menu items will be dynamically populated -->
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-primary save-config-btn" data-type="main">
                                            Save Navigation Configuration
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Plugins Configuration -->
                        <div id="plugins-config-tab" class="subtab-content">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Plugins Configuration</h2>
                                </div>
                                <div class="card-body">
                                    <form id="plugins-config-form">
                                        <!-- Keep Plugins -->
                                        <div class="card mt-4">
                                            <div class="card-header">
                                                <h4 class="card-title">Plugins to Keep</h4>
                                                <p class="text-muted">These plugins will not be removed during
                                                    deployment
                                                </p>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <label class="form-label">Keep Plugins</label>
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            id="add-keep-plugin-btn">
                                                            <i class="fas fa-plus"></i> Add Plugin
                                                        </button>
                                                    </div>
                                                    <div id="keep-plugins-container">
                                                        <!-- Keep plugins will be dynamically populated -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Install Plugins -->
                                        <div class="card mt-4">
                                            <div class="card-header">
                                                <h4 class="card-title">Plugins to Install</h4>
                                                <p class="text-muted">These plugins will be installed during deployment
                                                </p>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <label class="form-label">Install Plugins</label>
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            id="add-install-plugin-btn">
                                                            <i class="fas fa-plus"></i> Add Plugin
                                                        </button>
                                                    </div>
                                                    <div id="install-plugins-container">
                                                        <!-- Install plugins will be dynamically populated -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-primary save-config-btn"
                                            data-type="plugins">
                                            Save Plugins Configuration
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Policies Configuration -->
                        <div id="policies-config-tab" class="subtab-content">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Policies Configuration</h2>
                                </div>
                                <div class="card-body">
                                    <form id="policies-config-form">
                                        <!-- Password Policy -->
                                        <div class="card mt-4">
                                            <div class="card-header">
                                                <h4 class="card-title">Password Policy</h4>
                                            </div>
                                            <div class="card-body">
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Minimum Length</label>
                                                        <input type="number" class="form-input config-input"
                                                            data-path="password_policy.min_length" min="8" max="50"
                                                            placeholder="12">
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="toggle-container">
                                                            <div class="toggle-wrapper">
                                                                <input type="checkbox" class="config-input toggle-input"
                                                                    id="require-uppercase-toggle"
                                                                    data-path="password_policy.require_uppercase">
                                                                <div class="toggle-switch"></div>
                                                            </div>
                                                            <label class="toggle-label" for="require-uppercase-toggle">
                                                                <i class="fas fa-font toggle-icon"></i>
                                                                Require Uppercase Letters
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="form-group">
                                                        <div class="toggle-container">
                                                            <div class="toggle-wrapper">
                                                                <input type="checkbox" class="config-input toggle-input"
                                                                    id="require-lowercase-toggle"
                                                                    data-path="password_policy.require_lowercase">
                                                                <div class="toggle-switch"></div>
                                                            </div>
                                                            <label class="toggle-label" for="require-lowercase-toggle">
                                                                <i class="fas fa-font toggle-icon"></i>
                                                                Require Lowercase Letters
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="toggle-container">
                                                            <div class="toggle-wrapper">
                                                                <input type="checkbox" class="config-input toggle-input"
                                                                    id="require-numbers-toggle"
                                                                    data-path="password_policy.require_numbers">
                                                                <div class="toggle-switch"></div>
                                                            </div>
                                                            <label class="toggle-label" for="require-numbers-toggle">
                                                                <i class="fas fa-hashtag toggle-icon"></i>
                                                                Require Numbers
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="form-group">
                                                        <div class="toggle-container">
                                                            <div class="toggle-wrapper">
                                                                <input type="checkbox" class="config-input toggle-input"
                                                                    id="require-special-chars-toggle"
                                                                    data-path="password_policy.require_special_chars">
                                                                <div class="toggle-switch"></div>
                                                            </div>
                                                            <label class="toggle-label"
                                                                for="require-special-chars-toggle">
                                                                <i class="fas fa-asterisk toggle-icon"></i>
                                                                Require Special Characters
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="toggle-container">
                                                            <div class="toggle-wrapper">
                                                                <input type="checkbox" class="config-input toggle-input"
                                                                    id="prevent-username-password-toggle"
                                                                    data-path="password_policy.prevent_username_password">
                                                                <div class="toggle-switch"></div>
                                                            </div>
                                                            <label class="toggle-label"
                                                                for="prevent-username-password-toggle">
                                                                <i class="fas fa-user-slash toggle-icon"></i>
                                                                Prevent Username in Password
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="prevent-common-passwords-toggle"
                                                                data-path="password_policy.prevent_common_passwords">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label"
                                                            for="prevent-common-passwords-toggle">
                                                            <i class="fas fa-ban toggle-icon"></i>
                                                            Prevent Common Passwords
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- WP 2FA Configuration -->
                                        <div class="card mt-4">
                                            <div class="card-header">
                                                <h4 class="card-title">Two-Factor Authentication Policy</h4>
                                            </div>
                                            <div class="card-body">
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="form-group">
                                                        <div class="toggle-container">
                                                            <div class="toggle-wrapper">
                                                                <input type="checkbox" class="config-input toggle-input"
                                                                    id="wp-2fa-enabled-toggle"
                                                                    data-path="wp_2fa_config.enabled">
                                                                <div class="toggle-switch"></div>
                                                            </div>
                                                            <label class="toggle-label" for="wp-2fa-enabled-toggle">
                                                                <i class="fas fa-shield-alt toggle-icon"></i>
                                                                Enable 2FA Plugin Configuration
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="toggle-container">
                                                            <div class="toggle-wrapper">
                                                                <input type="checkbox" class="config-input toggle-input"
                                                                    id="enforce-multisite-toggle"
                                                                    data-path="wp_2fa_config.enforce_on_multisite">
                                                                <div class="toggle-switch"></div>
                                                            </div>
                                                            <label class="toggle-label" for="enforce-multisite-toggle">
                                                                <i class="fas fa-network-wired toggle-icon"></i>
                                                                Enforce on Multisite
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Enforcement Policy</label>
                                                        <select class="form-select config-input"
                                                            data-path="wp_2fa_config.enforcement_policy">
                                                            <option value="enforce-on-login">Enforce on Login</option>
                                                            <option value="enforce-immediately">Enforce Immediately
                                                            </option>
                                                            <option value="do-not-enforce">Do Not Enforce</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Grace Policy</label>
                                                        <select class="form-select config-input"
                                                            data-path="wp_2fa_config.grace_policy">
                                                            <option value="use-grace-policy">Use Grace Policy</option>
                                                            <option value="no-grace-policy">No Grace Policy</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Grace Period (days)</label>
                                                        <input type="number" class="form-input config-input"
                                                            data-path="wp_2fa_config.grace_period" min="0" max="365"
                                                            placeholder="7">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Login Code Expiry (minutes)</label>
                                                        <input type="number" class="form-input config-input"
                                                            data-path="wp_2fa_config.login_code_expiry_time" min="1"
                                                            max="60" placeholder="5">
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="form-group">
                                                        <div class="toggle-container">
                                                            <div class="toggle-wrapper">
                                                                <input type="checkbox" class="config-input toggle-input"
                                                                    id="backup-codes-toggle"
                                                                    data-path="wp_2fa_config.backup_codes_enabled">
                                                                <div class="toggle-switch"></div>
                                                            </div>
                                                            <label class="toggle-label" for="backup-codes-toggle">
                                                                <i class="fas fa-key toggle-icon"></i>
                                                                Enable Backup Codes
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Backup Codes Count</label>
                                                        <input type="number" class="form-input config-input"
                                                            data-path="wp_2fa_config.backup_codes_wrapper" min="5"
                                                            max="20" placeholder="10">
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Enforced User Roles</label>
                                                    <div class="checkbox-group">
                                                        <label class="checkbox-label">
                                                            <input type="checkbox" class="config-checkbox"
                                                                data-path="wp_2fa_config.enforced_roles"
                                                                data-value="administrator">
                                                            Administrator
                                                        </label>
                                                        <label class="checkbox-label">
                                                            <input type="checkbox" class="config-checkbox"
                                                                data-path="wp_2fa_config.enforced_roles"
                                                                data-value="editor">
                                                            Editor
                                                        </label>
                                                        <label class="checkbox-label">
                                                            <input type="checkbox" class="config-checkbox"
                                                                data-path="wp_2fa_config.enforced_roles"
                                                                data-value="author">
                                                            Author
                                                        </label>
                                                        <label class="checkbox-label">
                                                            <input type="checkbox" class="config-checkbox"
                                                                data-path="wp_2fa_config.enforced_roles"
                                                                data-value="contributor">
                                                            Contributor
                                                        </label>
                                                        <label class="checkbox-label">
                                                            <input type="checkbox" class="config-checkbox"
                                                                data-path="wp_2fa_config.enforced_roles"
                                                                data-value="subscriber">
                                                            Subscriber
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="form-group">
                                                        <div class="toggle-container">
                                                            <div class="toggle-wrapper">
                                                                <input type="checkbox" class="config-input toggle-input"
                                                                    id="destroy-session-toggle"
                                                                    data-path="wp_2fa_config.enable_destroy_session">
                                                                <div class="toggle-switch"></div>
                                                            </div>
                                                            <label class="toggle-label" for="destroy-session-toggle">
                                                                <i class="fas fa-sign-out-alt toggle-icon"></i>
                                                                Enable Session Destroy on 2FA Setup
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="toggle-container">
                                                            <div class="toggle-wrapper">
                                                                <input type="checkbox" class="config-input toggle-input"
                                                                    id="custom-user-page-toggle"
                                                                    data-path="wp_2fa_config.create_custom_user_page">
                                                                <div class="toggle-switch"></div>
                                                            </div>
                                                            <label class="toggle-label" for="custom-user-page-toggle">
                                                                <i class="fas fa-file-alt toggle-icon"></i>
                                                                Create Custom User Setup Page
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Custom User Page URL</label>
                                                    <input type="text" class="form-input config-input"
                                                        data-path="wp_2fa_config.custom_user_page_url"
                                                        placeholder="/2fa-setup">
                                                </div>
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-primary save-config-btn"
                                            data-type="policies">
                                            Save Policies Configuration
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>




                    <!-- Pages Tab -->
                    <div id="pages-content" class="tab-content">
                        <div class="page-header">
                            <h1 class="page-title">Page Editor</h1>
                            <p class="page-description">Edit page content and layouts for your themes</p>
                        </div>

                        <div class="card mb-6">
                            <div class="card-header">
                                <h2 class="card-title">Theme Selection</h2>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Active Theme</label>
                                    <div class="d-flex gap-2">
                                        <select id="page-theme-select" class="form-select" style="flex: 1;">
                                            <option value="">Loading themes...</option>
                                        </select>
                                        <button type="button" id="refresh-theme-list-btn" class="btn btn-outline"
                                            title="Refresh theme list from folder structure">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                        <button type="button" id="clean-uploads-btn" class="btn btn-outline"
                                            title="Delete unused uploads from the repo" style="white-space:nowrap;"
                                            onclick="window.adminInterface.cleanUnusedUploads()">
                                            <i class="fas fa-trash-alt"></i> Clean Uploads
                                        </button>
                                    </div>
                                    <small class="text-muted mt-1 d-block">Themes are loaded from pages/themes
                                        folder</small>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-6">
                            <div class="card-header">
                                <h2 class="card-title">Site Logo</h2>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Upload Logo</label>
                                    <div class="logo-upload-container">
                                        <div class="logo-upload" id="logo-upload">
                                            <input type="file" class="file-input logo-input" accept="image/*"
                                                id="logo-file-input">
                                            <div class="logo-preview">
                                                <img id="logo-preview-img" src="" alt="Logo Preview"
                                                    style="display: none;">
                                            </div>
                                            <div class="logo-upload-text" id="logo-upload-text">
                                                Click to upload or drag logo here<br>
                                                <small class="text-muted">Recommended: PNG, SVG, or JPG (Max
                                                    2MB)</small>
                                            </div>
                                        </div>
                                        <div class="logo-actions mt-3" style="display: none;" id="logo-actions">
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                id="remove-logo-btn">
                                                Remove Logo
                                            </button>
                                            <button type="button" class="btn btn-primary btn-sm" id="save-logo-btn">
                                                Save Logo
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            Logo will be deployed when you deploy the site. Current logos are
                                            managed in
                                            the
                                            uploads directory.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header flex items-center justify-between">
                                <h2 class="card-title">Page Content</h2>
                                <button type="button" class="btn btn-primary save-page-btn">
                                    Save All Changes
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="tabs">
                                    <ul id="page-tabs" class="tab-list">
                                        <li class="tab-item">
                                            <span class="text-muted">Select a theme to load pages</span>
                                        </li>
                                    </ul>
                                </div>

                                <div id="page-sections" class="mt-6">
                                    <div class="text-center text-muted">
                                        <p>Select a theme and page to start editing</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Other Contents Tab -->
                    <div id="contents-content" class="tab-content">
                        <div class="page-header">
                            <h1 class="page-title">Other Contents</h1>
                            <p class="page-description">Manage Issues, Endorsements, and News content</p>
                        </div>

                        <div class="card">
                            <div class="card-header flex items-center justify-between">
                                <h2 class="card-title">Content Management</h2>
                                <button type="button" class="btn btn-primary save-contents-btn"
                                    onclick="saveAllContents()">
                                    Save All Changes
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="tabs">
                                    <ul class="tab-list" id="contents-tabs">
                                        <li class="tab-item">
                                            <a href="#" class="tab-link active" data-content-tab="issues">
                                                <i class="fas fa-clipboard-list"></i>
                                                Issues
                                            </a>
                                        </li>
                                        <li class="tab-item">
                                            <a href="#" class="tab-link" data-content-tab="endorsements">
                                                <i class="fas fa-star"></i>
                                                Endorsements
                                            </a>
                                        </li>
                                        <li class="tab-item">
                                            <a href="#" class="tab-link" data-content-tab="news">
                                                <i class="fas fa-newspaper"></i>
                                                News
                                            </a>
                                        </li>
                                        <li class="tab-item">
                                            <a href="#" class="tab-link" data-content-tab="posts">
                                                <i class="fas fa-file-alt"></i>
                                                Posts
                                            </a>
                                        </li>
                                        <li class="tab-item">
                                            <a href="#" class="tab-link" data-content-tab="testimonials">
                                                <i class="fas fa-quote-left"></i>
                                                Testimonials
                                            </a>
                                        </li>
                                        <li class="tab-item">
                                            <a href="#" class="tab-link" data-content-tab="sliders">
                                                <i class="fas fa-images"></i>
                                                Sliders
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="tab-content-area mt-6">
                                    <!-- Issues Tab -->
                                    <div id="issues-tab" class="content-tab-panel active">
                                        <div class="content-header mb-4">
                                            <div class="flex items-center justify-between">
                                                <h3>Issues Management</h3>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm add-issue-btn"
                                                    onclick="addNewIssue()">
                                                    <i class="fas fa-plus"></i> Add New Issue
                                                </button>
                                            </div>
                                        </div>
                                        <div id="issues-list" class="content-list">
                                            <!-- Issues will be loaded here dynamically -->
                                        </div>
                                    </div>

                                    <!-- Endorsements Tab -->
                                    <div id="endorsements-tab" class="content-tab-panel">
                                        <div class="content-header mb-4">
                                            <div class="flex items-center justify-between">
                                                <h3>Endorsements Management</h3>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm add-endorsement-btn"
                                                    onclick="addNewEndorsement()">
                                                    <i class="fas fa-plus"></i> Add New Endorsement
                                                </button>
                                            </div>
                                        </div>
                                        <div id="endorsements-list" class="content-list">
                                            <!-- Endorsements will be loaded here dynamically -->
                                        </div>
                                    </div>

                                    <!-- News Tab -->
                                    <div id="news-tab" class="content-tab-panel">
                                        <div class="content-header mb-4">
                                            <div class="flex items-center justify-between">
                                                <h3>News Management</h3>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm add-news-btn"
                                                    onclick="addNewNews()">
                                                    <i class="fas fa-plus"></i> Add New News Article
                                                </button>
                                            </div>
                                        </div>
                                        <div id="news-list" class="content-list">
                                            <!-- News will be loaded here dynamically -->
                                        </div>
                                    </div>

                                    <!-- Posts Tab -->
                                    <div id="posts-tab" class="content-tab-panel">
                                        <div class="content-header mb-4">
                                            <div class="flex items-center justify-between">
                                                <h3>Posts Management</h3>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm add-post-btn"
                                                    onclick="addNewPost()">
                                                    <i class="fas fa-plus"></i> Add New Post
                                                </button>
                                            </div>
                                        </div>
                                        <div id="posts-list" class="content-list">
                                            <!-- Posts will be loaded here dynamically -->
                                        </div>
                                    </div>

                                    <!-- Testimonials Tab -->
                                    <div id="testimonials-tab" class="content-tab-panel">
                                        <div class="content-header mb-4">
                                            <div class="flex items-center justify-between">
                                                <h3>Testimonials Management</h3>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm add-testimonial-btn"
                                                    onclick="addNewTestimonial()">
                                                    <i class="fas fa-plus"></i> Add New Testimonial
                                                </button>
                                            </div>
                                        </div>
                                        <div id="testimonials-list" class="content-list">
                                            <!-- Testimonials will be loaded here dynamically -->
                                        </div>
                                    </div>

                                    <!-- Sliders Tab -->
                                    <div id="sliders-tab" class="content-tab-panel">
                                        <div class="content-header mb-4">
                                            <div class="flex items-center justify-between">
                                                <h3>Slider Management</h3>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm add-slider-btn"
                                                    onclick="addNewSlider()">
                                                    <i class="fas fa-plus"></i> Add New Slide
                                                </button>
                                            </div>
                                        </div>
                                        <div id="sliders-list" class="content-list">
                                            <!-- Sliders will be loaded here dynamically -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Forms Tab -->
                    <div id="forms-content" class="tab-content">
                        <div class="page-header">
                            <h2>Forms Manager</h2>
                            <p class="text-muted">Create, edit, and manage Forminator forms with drag-and-drop interface
                            </p>
                        </div>

                        <div class="forms-manager">
                            <div class="forms-manager-header">
                                <h2>Forminator Forms Manager</h2>
                                <button id="add-form-btn" class="form-save-btn">+ New Form</button>
                            </div>

                            <div id="forms-list" class="forms-list">
                                <!-- Forms will be loaded here dynamically -->
                            </div>

                            <div id="form-editor" class="form-editor" style="display:none;">
                                <div class="form-editor-header">
                                    <h3 id="form-editor-title">Edit Form</h3>
                                    <button type="button" id="form-cancel-btn" class="btn btn-sm btn-outline-secondary">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18" />
                                            <line x1="6" y1="6" x2="18" y2="18" />
                                        </svg>
                                        Cancel
                                    </button>
                                </div>

                                <div id="form-success" class="form-success" style="display:none;"></div>
                                <div id="form-error" class="form-error" style="display:none;"></div>

                                <form id="form-edit-form">
                                    <label for="form-name">Form Name *</label>
                                    <input type="text" id="form-name" name="form-name" placeholder="Enter form name..."
                                        required>

                                    <label>Add Form Elements</label>
                                    <div class="form-add-element">
                                        <select id="element-type">
                                            <option value="text">Text Input</option>
                                            <option value="email">Email Input</option>
                                            <option value="textarea">Textarea</option>
                                            <option value="select">Select Dropdown</option>
                                            <option value="checkbox">Checkbox</option>
                                            <option value="radio">Radio Button</option>
                                            <option value="captcha">reCAPTCHA</option>
                                        </select>
                                        <input type="text" id="element-label"
                                            placeholder="Element Label (e.g., Full Name)">
                                        <button type="button" id="add-element-btn" class="btn btn-primary">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <line x1="12" y1="5" x2="12" y2="19" />
                                                <line x1="5" y1="12" x2="19" y2="12" />
                                            </svg>
                                            Add
                                        </button>
                                    </div>

                                    <label>Form Elements</label>
                                    <div class="form-elements-list" id="form-elements-list">
                                        <!-- Form elements will be rendered here -->
                                    </div>

                                    <label>Submit Button Settings</label>
                                    <div class="form-group">
                                        <input type="text" id="submit-button-text"
                                            placeholder="Submit button text (e.g., SUBMIT, Send Message)"
                                            value="SUBMIT">
                                        <small class="text-muted">This text will appear on the submit button</small>
                                    </div>

                                    <label>Form Placeholders</label>
                                    <div class="form-group">
                                        <small class="text-muted">Add placeholder tags that will be replaced with this form during auto-placement (e.g., "Contact Form" becomes [contact-form])</small>
                                        <div class="placeholder-input-wrapper">
                                            <input type="text" id="placeholder-input"
                                                placeholder="Enter placeholder (e.g., Contact Form)"
                                                title="Enter placeholder name (will be auto-slugified and wrapped in brackets)">
                                            <button type="button" id="add-placeholder-btn" class="btn btn-sm btn-success">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2">
                                                    <line x1="12" y1="5" x2="12" y2="19" />
                                                    <line x1="5" y1="12" x2="19" y2="12" />
                                                </svg>
                                                Add Placeholder
                                            </button>
                                        </div>
                                        <div id="placeholders-list" class="placeholders-list">
                                            <!-- Placeholders will be rendered here -->
                                        </div>
                                    </div>

                                    <div class="form-editor-actions">
                                        <button type="button" id="form-save-btn" class="form-save-btn">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <path
                                                    d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                                                <polyline points="17 21 17 13 7 13 7 21" />
                                                <polyline points="7 3 7 8 15 8" />
                                            </svg>
                                            Save Form
                                        </button>
                                        <button type="button" id="form-delete-btn" class="form-delete-btn"
                                            style="display:none;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                            </svg>
                                            Delete Form
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Local Config Tab -->
                    <div id="local-config-content" class="tab-content">
                        <div class="page-header">
                            <h2>Local Configuration</h2>
                            <p class="text-muted">Manage environment settings, PHP configuration, and debug options</p>
                        </div>

                        <!-- PHP Detection Success Notice -->
                        <div class="alert alert-success" id="php-status-alert">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <strong>PHP Configured Successfully!</strong><br>
                                <small id="php-detection-info">Auto-detected PHP 8.3.15 (fpm-fcgi)</small>
                            </div>
                        </div>

                        <div class="config-sections-grid">
                            <!-- PHP Configuration Section -->
                            <div class="config-card">
                                <div class="config-card-header">
                                    <h3><i class="fas fa-chevron-right"></i> PHP Configuration</h3>
                                </div>
                                <div class="config-card-body">
                                    <div class="config-info-row">
                                        <div class="config-info-label">
                                            <i class="fas fa-folder"></i> Path:
                                        </div>
                                        <div class="config-info-value" id="php-path">/nix/store/7fkkvqn2Qa8Fjdw5uawZ</div>
                                        <div class="config-info-badge">? Unknown</div>
                                    </div>
                                    <div class="config-info-row">
                                        <div class="config-info-label">
                                            <i class="fas fa-code-branch"></i> Version:
                                        </div>
                                        <div class="config-info-value" id="php-version">8.3.15</div>
                                    </div>
                                    <div class="config-info-row">
                                        <div class="config-info-label">
                                            <i class="fas fa-server"></i> SAPI:
                                        </div>
                                        <div class="config-info-value" id="php-sapi">fpm-fcgi</div>
                                    </div>

                                    <div class="config-actions-row">
                                        <div class="config-manual-override">
                                            <label class="config-info-label">
                                                <i class="fas fa-edit"></i> Manual Override
                                            </label>
                                            <div class="config-input-group">
                                                <input type="text" id="php-manual-path" class="form-input"
                                                       placeholder="/usr/bin/php or /usr/">
                                                <button class="btn btn-sm btn-outline-primary" onclick="window.localConfigManager.testPHPPath()">
                                                    <i class="fas fa-vial"></i> Test
                                                </button>
                                            </div>
                                        </div>
                                        <div class="config-button-group">
                                            <button class="btn btn-sm btn-primary" onclick="window.localConfigManager.savePHPConfig()">
                                                <i class="fas fa-save"></i> Save
                                            </button>
                                            <button class="btn btn-sm btn-secondary" onclick="window.localConfigManager.resetPHPConfig()">
                                                <i class="fas fa-redo"></i> Reset
                                            </button>
                                            <button class="btn btn-sm btn-secondary" onclick="window.localConfigManager.refreshPHPConfig()">
                                                <i class="fas fa-sync-alt"></i> Refresh
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Debug Settings Section -->
                            <div class="config-card">
                                <div class="config-card-header">
                                    <h3><i class="fas fa-bug"></i> Debug Settings</h3>
                                </div>
                                <div class="config-card-body">
                                    <div class="debug-setting-item">
                                        <div class="debug-setting-header">
                                            <div class="debug-setting-icon">
                                                <i class="fas fa-chevron-right"></i>
                                            </div>
                                            <h4>Console Logging</h4>
                                        </div>
                                        <div class="debug-setting-toggle">
                                            <label class="toggle-switch-wrapper">
                                                <input type="checkbox" id="console-logging-toggle" class="toggle-input" checked>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <p class="debug-setting-description">Enable/disable console.log output for debugging</p>
                                </div>
                            </div>
                        </div>

                        <!-- Configuration Help Section -->
                        <div class="config-help-section">
                            <button class="config-help-toggle" onclick="this.parentElement.classList.toggle('expanded')">
                                <i class="fas fa-question-circle"></i> Configuration Help
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="config-help-content">
                                <h4>About Local Configuration</h4>
                                <p>The local configuration file (local-config.json) stores environment-specific settings that should not be committed to version control.</p>

                                <h5>PHP Configuration</h5>
                                <ul>
                                    <li><strong>Auto-detection:</strong> The system automatically detects your PHP installation</li>
                                    <li><strong>Manual Override:</strong> Specify a custom PHP path if auto-detection fails</li>
                                    <li><strong>Test:</strong> Verify your PHP configuration before saving</li>
                                </ul>

                                <h5>Debug Settings</h5>
                                <ul>
                                    <li><strong>Console Logging:</strong> Control browser console output for development</li>
                                    <li><strong>Error Reporting:</strong> Enable detailed error messages during development</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Raw Configs Tab -->
                    <div id="raw-configs-content" class="tab-content">
                        <div class="page-header">
                            <h2>Raw Configuration Files</h2>
                            <p class="text-muted">View all configuration JSON files as raw text</p>
                        </div>

                        <div class="raw-config-section">
                            <div class="config-file-selector">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-file-code"></i> Select File:
                                    </label>
                                    <select id="config-file-select" class="form-select">
                                        <option value="">-- Choose a configuration file --</option>
                                        <option value="config.json">config.json</option>
                                        <option value="forms-config.json">forms-config.json</option>
                                        <option value="git.json">git.json</option>
                                        <option value="local-config.json">local-config.json</option>
                                        <option value="site.json">site.json</option>
                                        <option value="theme-config.json">theme-config.json</option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary" onclick="window.rawConfigManager.refreshFileList()">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                </div>
                            </div>

                            <div class="config-file-actions">
                                <button class="btn btn-primary" onclick="document.getElementById('config-import-input').click()">
                                    <i class="fas fa-upload"></i> Import Config File
                                </button>
                                <input type="file" id="config-import-input" accept=".json" style="display: none;">
                                <button class="btn btn-secondary" id="config-copy-btn" onclick="window.rawConfigManager.copyToClipboard()">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                                <button class="btn btn-secondary" id="config-download-btn" onclick="window.rawConfigManager.downloadCurrentConfig()">
                                    <i class="fas fa-download"></i> Download
                                </button>
                            </div>

                            <div class="config-file-info">
                                <div id="config-file-metadata" class="file-metadata">
                                    <span><i class="fas fa-file"></i> File Size: <span id="file-size">-</span></span>
                                    <span><i class="fas fa-code"></i> Lines: <span id="file-lines">-</span></span>
                                    <span><i class="fas fa-clock"></i> Last Modified: <span id="file-modified">-</span></span>
                                </div>
                            </div>

                            <div class="code-viewer-wrapper">
                                <div class="code-viewer">
                                    <pre><code id="raw-config-viewer" class="language-json">Select a file to view its contents</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- Close main-content -->
            </main>
        </div>


        <!-- Help Modal -->
        <?php include 'admin/includes/help.php'; ?>

        <script src="/php/admin/assets/js/tools.js"></script>
        <script src="/php/admin/assets/js/forms.js"></script>
        <script src="/php/admin/assets/js/raw-config.js"></script>
        <script src="/php/admin/assets/js/local-config.js"></script>
        <script src="/php/admin/assets/js/admin.js"></script>
    </body>

</html>
