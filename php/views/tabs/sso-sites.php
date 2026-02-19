                    <div id="sso-sites-content" class="tab-content">
                        <div class="page-header">
                            <h2>SSO Sites</h2>
                            <p class="text-muted">Manage WordPress sites registered for Single Sign-On</p>
                        </div>

                        <!-- Register New Site -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h2 class="card-title mb-0">Register New Site</h2>
                            </div>
                            <div class="card-body">
                                <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                                    <div style="flex:1;min-width:220px;">
                                        <label class="form-label" for="sso-new-domain">Domain <span style="color:#dc2626;">*</span></label>
                                        <input type="text" id="sso-new-domain" class="form-input"
                                            placeholder="site.kinsta.cloud" autocomplete="off" spellcheck="false">
                                    </div>
                                    <div style="flex:2;min-width:260px;">
                                        <label class="form-label" for="sso-new-notes">Notes</label>
                                        <input type="text" id="sso-new-notes" class="form-input"
                                            placeholder="e.g. Deployment ID, site name..." autocomplete="off">
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-primary" id="sso-register-btn"
                                            onclick="window.adminInterface?.ssoRegisterSite()">
                                            <i class="fas fa-plus me-1"></i> Register
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sites List -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h2 class="card-title mb-0">Registered Sites</h2>
                                <div style="display:flex;gap:8px;">
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                        onclick="window.adminInterface?.loadSsoSites()">
                                        <i class="fas fa-sync-alt me-1"></i> Refresh
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        onclick="window.adminInterface?.ssoCleanupTokens()">
                                        <i class="fas fa-broom me-1"></i> Cleanup Tokens
                                    </button>
                                </div>
                            </div>
                            <div class="card-body" style="padding:0;">
                                <div id="sso-sites-list">
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-spinner fa-spin me-2"></i> Loading SSO sites...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
