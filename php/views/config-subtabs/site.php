                        <div id="site-config-tab" class="subtab-content">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Kinsta Settings</h2>
                                </div>
                                <div class="card-body">
                                    <form id="site-config-form">
                                        <div class="config-section mb-8">
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

                                        <div class="config-section mb-8">
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

                                        <div class="config-section mb-8">
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

                                        <div class="config-section mb-8">
                                            <div class="grid grid-cols-1 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">Region</label>
                                                    <select id="kinsta-region-select" class="form-select config-input"
                                                        data-path="region">
                                                        <option value="">Loading regions...</option>
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

                                        <div class="config-section mb-8">
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
