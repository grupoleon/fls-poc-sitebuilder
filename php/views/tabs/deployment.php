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
                                <button onclick="window.adminInterface.deployNewSite()"
                                    class="btn btn-sm btn-outline-light">
                                    <i class="fas fa-rocket me-2"></i>Start Over
                                </button>
                            </div>
                        </div>

                        <div class="card mb-6" id="quick-deploy-card">
                            <div class="card-header">
                                <h2 class="card-title">Deploy Your Website</h2>
                            </div>
                            <div class="card-body" id="quick-deploy-body">
                                <!-- Selected Task Display (shown when task is selected) -->
                                <div id="selected-task-display" class="mb-3" style="display: none;">
                                    <div
                                        style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #bbf7d0; border-radius: 8px;">
                                        <i class="fas fa-check-circle"
                                            style="color: #16a34a; font-size: 16px; flex-shrink: 0;"></i>
                                        <div style="flex: 1; min-width: 0;">
                                            <span id="selected-task-title"
                                                style="display: block; font-weight: 600; font-size: 13px; color: #15803d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></span>
                                            <span id="selected-task-id"
                                                style="display: block; font-size: 11px; color: #6b7280; font-family: monospace;"></span>
                                        </div>
                                        <button type="button" id="remove-selected-task-btn" class="btn"
                                            style="padding: 4px 10px; font-size: 12px; background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; cursor: pointer; flex-shrink: 0;"
                                            title="Remove task selection">
                                            <i class="fas fa-times" style="margin-right: 4px;"></i>Remove
                                        </button>
                                    </div>
                                </div>

                                <!-- ClickUp Task Selection - 2-column compact layout -->
                                <div id="clickup-task-section" class="mb-4" style="display: block;">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                        <div class="form-group mb-0">
                                            <label class="form-label"
                                                style="font-size: 12px; margin-bottom: 4px;">ClickUp Task</label>
                                            <select id="clickup-task-select" class="form-select"
                                                style="font-size: 13px; padding: 20px 10px;">
                                                <option value="">-- Select a task --</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Fetch
                                                by Task ID</label>
                                            <div style="display: flex; gap: 6px;">
                                                <input type="text" id="manual-task-id-input" class="form-input"
                                                    placeholder="e.g., 86dzf0rkn"
                                                    style="font-size: 13px; padding: 6px 10px; flex: 1;">
                                                <button type="button" class="btn btn-outline-primary"
                                                    id="fetch-manual-task-btn"
                                                    style="white-space: nowrap; padding: 6px 12px; font-size: 12px;">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="manual-task-status" class="mt-2" style="display: none;">
                                        <div class="alert" id="manual-task-alert"
                                            style="font-size: 13px; padding: 8px 12px;"></div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label">Site Title</label>
                                        <input type="text" id="deployment-site-title" class="form-input"
                                            placeholder="Enter site title">
                                        <div class="form-help">Enter your website's name (e.g., "My Campaign Site")
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
                        </div>

                        <!-- Deployment Progress Card (separate, hidden by default) -->
                        <div class="card mb-6" id="deployment-progress-card" style="display: none;">
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
                                                <h3 class="status-step-title text-lg font-semibold text-gray-600 mb-1">
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
                                                <h3 class="status-step-title text-lg font-semibold text-gray-600 mb-1">
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

                        <div class="card" id="deployment-logs-card" style="display: none;">
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
