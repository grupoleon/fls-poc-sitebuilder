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
                                        <div class="config-info-value" id="php-path">/nix/store/7fkkvqn2Qa8Fjdw5uawZ
                                        </div>
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
                                                <button class="btn btn-sm btn-outline-primary"
                                                    onclick="window.localConfigManager.testPHPPath()">
                                                    <i class="fas fa-vial"></i> Test
                                                </button>
                                            </div>
                                        </div>
                                        <div class="config-button-group">
                                            <button class="btn btn-sm btn-primary"
                                                onclick="window.localConfigManager.savePHPConfig()">
                                                <i class="fas fa-save"></i> Save
                                            </button>
                                            <button class="btn btn-sm btn-secondary"
                                                onclick="window.localConfigManager.resetPHPConfig()">
                                                <i class="fas fa-redo"></i> Reset
                                            </button>
                                            <button class="btn btn-sm btn-secondary"
                                                onclick="window.localConfigManager.refreshPHPConfig()">
                                                <i class="fas fa-sync-alt"></i> Refresh
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ClickUp Integration Section -->
                            <div class="config-card">
                                <div class="config-card-header">
                                    <h3><i class="fas fa-tasks"></i> ClickUp Integration</h3>
                                </div>
                                <div class="config-card-body">
                                    <div class="config-info-row">
                                        <div class="config-info-label">
                                            <i class="fas fa-key"></i> API Token:
                                        </div>
                                        <div class="config-info-value" id="clickup-api-token-display">
                                            <span class="text-muted">Not configured</span>
                                        </div>
                                        <div class="config-info-badge" id="clickup-status-badge">
                                            <i class="fas fa-times-circle"></i> Inactive
                                        </div>
                                    </div>
                                    <div class="config-info-row">
                                        <div class="config-info-label">
                                            <i class="fas fa-users"></i> Team ID:
                                        </div>
                                        <div class="config-info-value" id="clickup-team-id-display">
                                            <span class="text-muted">Not configured</span>
                                        </div>
                                    </div>
                                    <div class="config-info-row">
                                        <div class="config-info-label">
                                            <i class="fas fa-webhook"></i> Webhook URL:
                                        </div>
                                        <div class="config-info-value">
                                            <code id="clickup-webhook-url" style="font-size: 0.875rem; color: #14b8a6;">
                                                /webhook/index.php?id={task_id}
                                            </code>
                                        </div>
                                    </div>

                                    <div class="config-actions-row mt-4">
                                        <div class="config-manual-override">
                                            <label class="config-info-label">
                                                <i class="fas fa-key"></i> API Token
                                            </label>
                                            <div class="config-input-group">
                                                <input type="password" id="clickup-api-token-input" class="form-input"
                                                    name="clickup_api_token" autocomplete="off"
                                                    placeholder="Enter your ClickUp API Token" value="">
                                                <button class="btn btn-sm btn-outline-primary"
                                                    onclick="window.localConfigManager.toggleClickUpTokenVisibility()">
                                                    <i class="fas fa-eye" id="clickup-token-eye-icon"></i>
                                                </button>
                                            </div>
                                            <small class="form-help">
                                                Get your token from ClickUp Settings → Apps → API Token
                                            </small>
                                        </div>
                                    </div>

                                    <div class="config-actions-row">
                                        <div class="config-manual-override">
                                            <label class="config-info-label">
                                                <i class="fas fa-users"></i> Team ID (Optional)
                                            </label>
                                            <input type="text" id="clickup-team-id-input" class="form-input"
                                                name="clickup_team_id" autocomplete="off"
                                                placeholder="Enter your ClickUp Team ID" value="">
                                            <small class="form-help">
                                                Find in ClickUp Settings → Workspace → Team ID
                                            </small>
                                        </div>
                                    </div>

                                    <div class="config-button-group mt-3">
                                        <button class="btn btn-sm btn-primary"
                                            onclick="window.localConfigManager.saveClickUpConfig()">
                                            <i class="fas fa-save"></i> Save ClickUp Settings
                                        </button>
                                        <button class="btn btn-sm btn-secondary"
                                            onclick="window.localConfigManager.testClickUpConnection()">
                                            <i class="fas fa-plug"></i> Test Connection
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary"
                                            onclick="window.localConfigManager.clearClickUpConfig()">
                                            <i class="fas fa-trash"></i> Clear
                                        </button>
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
                                                <input type="checkbox" id="console-logging-toggle" class="toggle-input"
                                                    checked>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <p class="debug-setting-description">Enable/disable console.log output for debugging
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Log Viewer Section (Full Width) -->
                        <div class="config-card">
                            <div class="config-card-header">
                                <h3><i class="fas fa-file-alt"></i> Log Viewer</h3>
                            </div>
                            <div class="config-card-body">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-list"></i> Select Log File
                                    </label>
                                    <div class="d-flex gap-2">
                                        <select id="log-file-select" class="form-select" style="flex: 1;">
                                            <option value="">Loading log files...</option>
                                        </select>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="window.localConfigManager.refreshLogFiles()">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                    </div>
                                </div>

                                <div id="log-viewer-container" class="log-viewer-container" style="display: none;">
                                    <div class="log-viewer-header">
                                        <div class="log-file-info">
                                            <span class="log-file-name" id="log-current-file"></span>
                                            <span class="log-file-meta" id="log-file-meta"></span>
                                        </div>
                                        <div class="log-viewer-actions">
                                            <button type="button" class="btn btn-sm btn-outline"
                                                onclick="window.localConfigManager.downloadLogFile()">
                                                <i class="fas fa-download"></i> Download
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline"
                                                onclick="window.localConfigManager.copyLogContent()">
                                                <i class="fas fa-copy"></i> Copy
                                            </button>
                                        </div>
                                    </div>
                                    <div class="log-viewer-content">
                                        <pre id="log-content-display" class="log-content"></pre>
                                    </div>
                                </div>

                                <div id="log-viewer-empty" class="log-viewer-empty">
                                    <i class="fas fa-folder-open"></i>
                                    <p>Select a log file to view its content</p>
                                </div>
                            </div>
                        </div>
                    </div>
