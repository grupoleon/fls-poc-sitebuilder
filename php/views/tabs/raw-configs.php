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
                                    <button class="btn btn-sm btn-outline-primary"
                                        onclick="window.rawConfigManager.refreshFileList()">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                </div>
                            </div>

                            <div class="config-file-actions">
                                <button class="btn btn-primary"
                                    onclick="document.getElementById('config-import-input').click()">
                                    <i class="fas fa-upload"></i> Import JSON/ZIP
                                </button>
                                <input type="file" id="config-import-input" accept=".json,.zip" multiple
                                    style="display: none;">
                                <button class="btn btn-info" id="config-load-all-defaults-btn"
                                    onclick="window.rawConfigManager.loadAllDefaults()"
                                    title="Load all default configurations from database">
                                    <i class="fas fa-cloud-download-alt"></i> Load All Defaults
                                </button>
                                <button class="btn btn-success" id="config-save-default-btn"
                                    onclick="window.rawConfigManager.saveAsDefault()" style="display: none;">
                                    <i class="fas fa-save"></i> Save as Default
                                </button>
                                <button class="btn btn-warning" id="config-load-default-btn"
                                    onclick="window.rawConfigManager.loadDefault()" style="display: none;">
                                    <i class="fas fa-download"></i> Load Default
                                </button>
                                <button class="btn btn-secondary" id="config-copy-btn"
                                    onclick="window.rawConfigManager.copyToClipboard()">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                                <button class="btn btn-secondary" id="config-download-btn"
                                    onclick="window.rawConfigManager.downloadCurrentConfig()">
                                    <i class="fas fa-download"></i> Download
                                </button>
                                <span class="text-muted" style="margin-left: 8px; font-size: 0.875rem;">
                                    Import multiple `.json` files or a `.zip` containing JSONs.
                                </span>
                            </div>

                            <div id="config-import-summary" class="config-import-summary is-hidden" aria-live="polite">
                            </div>

                            <div class="config-file-info">
                                <div id="config-file-metadata" class="file-metadata">
                                    <span><i class="fas fa-file"></i> File Size: <span id="file-size">-</span></span>
                                    <span><i class="fas fa-code"></i> Lines: <span id="file-lines">-</span></span>
                                    <span><i class="fas fa-clock"></i> Last Modified: <span
                                            id="file-modified">-</span></span>
                                </div>
                            </div>

                            <div class="code-viewer-wrapper">
                                <div class="code-viewer">
                                    <pre><code id="raw-config-viewer" class="language-json">Select a file to view its contents</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
