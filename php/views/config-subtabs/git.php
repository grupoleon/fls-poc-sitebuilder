                        <div id="git-config-tab" class="subtab-content active">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Git & Deployment Configuration</h2>
                                </div>
                                <div class="card-body">
                                    <form id="git-config-form">
                                        <div class="config-section mb-8">
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

                                        <div class="config-section mb-8">
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

                                        <div class="config-section mb-8">
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
