                        <div id="security-config-tab" class="subtab-content">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Security Configuration</h2>
                                </div>
                                <div class="card-body">
                                    <form id="security-config-form">
                                        <div class="config-section mb-8">
                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                                <h3 class="font-semibold mb-0">Master Security Control</h3>
                                                <div class="toggle-container" style="margin-left: auto;">
                                                    <div class="toggle-wrapper">
                                                        <input type="checkbox" class="config-input toggle-input"
                                                            id="security-enabled-toggle" data-path="security.enabled">
                                                        <div class="toggle-switch"></div>
                                                    </div>
                                                    <label class="toggle-label" for="security-enabled-toggle">
                                                        <i class="fas fa-shield-alt toggle-icon"></i>
                                                        Enable Security Features
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-help mb-4">Master toggle to enable/disable all security
                                                features</div>
                                        </div>

                                        <!-- Note: Admin credentials are auto-generated during deployment for enhanced security -->

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1);">

                                        <div class="config-section mb-8">
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

                                            <div class="grid grid-cols-3 gap-4 mt-3">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="geo-block-admin-toggle"
                                                                data-path="security.geo_blocking.block_admin_area">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="geo-block-admin-toggle">
                                                            <i class="fas fa-lock toggle-icon"></i>
                                                            Block Admin Area
                                                        </label>
                                                    </div>
                                                    <div class="form-help">Apply geo-blocking to admin area</div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="geo-block-frontend-toggle"
                                                                data-path="security.geo_blocking.block_frontend">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="geo-block-frontend-toggle">
                                                            <i class="fas fa-desktop toggle-icon"></i>
                                                            Block Frontend
                                                        </label>
                                                    </div>
                                                    <div class="form-help">Apply geo-blocking to frontend</div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="geo-emergency-access-toggle"
                                                                data-path="security.geo_blocking.emergency_access">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="geo-emergency-access-toggle">
                                                            <i class="fas fa-exclamation-triangle toggle-icon"></i>
                                                            Emergency Access
                                                        </label>
                                                    </div>
                                                    <div class="form-help">Allow emergency access bypass</div>
                                                </div>
                                            </div>

                                            <div class="form-group mt-3">
                                                <label class="form-label">Emergency Access Code</label>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <input type="text" class="form-input config-input"
                                                        id="emergency-access-code-input"
                                                        data-path="security.geo_blocking.emergency_access_code"
                                                        placeholder="fls-1718" style="flex: 1;">
                                                    <button type="button" class="btn btn-secondary"
                                                        onclick="generateRandomAccessCode()"
                                                        style="white-space: nowrap; padding: 0.5rem 1rem;">
                                                        <i class="fas fa-random"></i> Generate
                                                    </button>
                                                </div>
                                                <div class="form-help">Access code to bypass geo-blocking (e.g., add
                                                    ?access=code to URL)</div>
                                                <div class="alert alert-warning mt-2"
                                                    style="padding: 8px 12px; background: #fff3cd; border-left: 3px solid #ffc107; border-radius: 4px; font-size: 0.875rem;">
                                                    <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                                                    <strong>Developer Note:</strong> This option is for development and
                                                    testing purposes only. Not intended for client use.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="config-section mb-8">
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
                                                                <option value="PE">🇵🇪 Peru</option>
                                                                <option value="RU">🇷🇺 Russia</option>
                                                                <option value="ZA">🇿🇦 South Africa</option>
                                                                <option value="CN">🇨🇳 China</option>
                                                                <option value="MX">🇲🇽 Mexico</option>
                                                                <option value="IT">🇮🇹 Italy</option>
                                                                <option value="ES">🇪🇸 Spain</option>
                                                                <option value="NL">🇳🇱 Netherlands</option>
                                                                <option value="SE">🇸🇪 Sweden</option>
                                                                <option value="CH">🇨🇭 Switzerland</option>
                                                                <option value="KR">🇰🇷 Korea</option>
                                                                <option value="TR">🇹🇷 Turkey</option>
                                                                <option value="AR">🇦🇷 Argentina</option>
                                                                <option value="BE">🇧🇪 Belgium</option>
                                                                <option value="DK">🇩🇰 Denmark</option>
                                                                <option value="FI">🇫🇮 Finland</option>
                                                                <option value="GR">🇬🇷 Greece</option>
                                                                <option value="IE">🇮🇪 Ireland</option>
                                                                <option value="NO">🇳🇴 Norway</option>
                                                                <option value="PL">🇵🇱 Poland</option>
                                                                <option value="PT">🇵🇹 Portugal</option>
                                                                <option value="AT">🇦🇹 Austria</option>
                                                                <option value="CZ">🇨🇿 Czech Republic</option>
                                                                <option value="HU">🇭🇺 Hungary</option>
                                                                <option value="NZ">🇳🇿 New Zealand</option>
                                                                <option value="RO">🇷🇴 Romania</option>
                                                                <option value="SK">🇸🇰 Slovakia</option>
                                                                <option value="TW">🇹🇼 Taiwan</option>
                                                                <option value="UA">🇺🇦 Ukraine</option>
                                                                <option value="TH">🇹🇭 Thailand</option>
                                                                <option value="VN">🇻🇳 Vietnam</option>
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

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1);">

                                        <div class="config-section mb-8">
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

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1); margin: 2rem 0;">

                                        <div class="config-section mb-8">
                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                                <h3 class="font-semibold mb-0">WP Security Audit Log</h3>
                                                <div class="toggle-container" style="margin-left: auto;">
                                                    <div class="toggle-wrapper">
                                                        <input type="checkbox" class="config-input toggle-input"
                                                            id="audit-log-enabled-toggle"
                                                            data-path="wp_security_audit_log.enabled">
                                                        <div class="toggle-switch"></div>
                                                    </div>
                                                    <label class="toggle-label" for="audit-log-enabled-toggle">
                                                        <i class="fas fa-clipboard-list toggle-icon"></i>
                                                        Enable Security Audit Logging
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-help mb-4">Master toggle to enable/disable all security
                                                audit logging features</div>

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

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1);">

                                        <div class="config-section mb-8">
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
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <input type="text" class="form-input config-input"
                                                        id="custom-login-slug-input"
                                                        data-path="security.login_protection.custom_login_url"
                                                        placeholder="fls-login" style="flex: 1;">
                                                    <button type="button" class="btn btn-secondary"
                                                        onclick="generateRandomLoginSlug()"
                                                        style="white-space: nowrap; padding: 0.5rem 1rem;">
                                                        <i class="fas fa-random"></i> Generate
                                                    </button>
                                                </div>
                                                <div class="form-help">Enter custom slug for login page (e.g., fls-login
                                                    becomes
                                                    yoursite.com/fls-login)</div>
                                            </div>
                                        </div>

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1); margin: 2rem 0;">

                                        <div class="config-section mb-8">
                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                                <h3 class="font-semibold mb-0">WordPress Hardening</h3>
                                                <div class="toggle-container" style="margin-left: auto;">
                                                    <div class="toggle-wrapper">
                                                        <input type="checkbox" class="config-input toggle-input"
                                                            id="wordpress-hardening-toggle"
                                                            data-path="security.wordpress_hardening.enabled">
                                                        <div class="toggle-switch"></div>
                                                    </div>
                                                    <label class="toggle-label" for="wordpress-hardening-toggle">
                                                        <i class="fas fa-hammer toggle-icon"></i>
                                                        Enable WordPress Hardening
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-help mb-4">Master toggle for WordPress hardening features
                                            </div>

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

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1); margin: 2rem 0;">

                                        <div class="config-section mb-8">
                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                                <h3 class="font-semibold mb-0">reCAPTCHA Protection</h3>
                                                <div class="toggle-container" style="margin-left: auto;">
                                                    <div class="toggle-wrapper">
                                                        <input type="checkbox" class="config-input toggle-input"
                                                            id="recaptcha-protection-toggle"
                                                            data-path="security.recaptcha_protection.enabled">
                                                        <div class="toggle-switch"></div>
                                                    </div>
                                                    <label class="toggle-label" for="recaptcha-protection-toggle">
                                                        <i class="fas fa-robot toggle-icon"></i>
                                                        Enable reCAPTCHA Protection
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-help mb-4">Protect your site with Google reCAPTCHA</div>

                                            <div class="grid grid-cols-2 gap-4 mb-4">
                                                <div class="form-group">
                                                    <label class="form-label">
                                                        <i class="fas fa-key text-gray-500 mr-2"></i>
                                                        reCAPTCHA Site Key
                                                    </label>
                                                    <input type="text" class="form-input config-input"
                                                        data-path="authentication.api_keys.recaptcha.site_key"
                                                        placeholder="6LcybYwrAAAAAJpqHkRj-Q0vPdLKOAISfEj-p_g6">
                                                    <div class="form-help">Your Google reCAPTCHA site key</div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">
                                                        <i class="fas fa-lock text-gray-500 mr-2"></i>
                                                        reCAPTCHA Secret Key
                                                    </label>
                                                    <div
                                                        style="position: relative; display: flex; align-items: center; gap: 8px;">
                                                        <input type="password" id="recaptcha-secret-input"
                                                            class="form-input config-input token-field"
                                                            data-path="authentication.api_keys.recaptcha.secret_key"
                                                            disabled
                                                            placeholder="••••••••••••••••••••••••••••••••••••••••"
                                                            style="flex: 1;">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary edit-field-btn"
                                                            data-target="recaptcha-secret-input"
                                                            title="Edit Secret Key">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                    </div>
                                                    <div class="form-help mt-1">
                                                        <small class="text-muted">
                                                            Your Google reCAPTCHA secret key. <a
                                                                href="https://www.google.com/recaptcha/admin"
                                                                target="_blank" class="text-blue-600">Get keys here</a>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-3 gap-4">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="recaptcha-protect-forms-toggle"
                                                                data-path="security.recaptcha_protection.protect_forms">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label"
                                                            for="recaptcha-protect-forms-toggle">
                                                            <i class="fas fa-file-alt toggle-icon"></i>
                                                            Protect Forms
                                                        </label>
                                                    </div>
                                                    <div class="form-help">Add reCAPTCHA to forms</div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="recaptcha-protect-login-toggle"
                                                                data-path="security.recaptcha_protection.protect_login">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label"
                                                            for="recaptcha-protect-login-toggle">
                                                            <i class="fas fa-sign-in-alt toggle-icon"></i>
                                                            Protect Login
                                                        </label>
                                                    </div>
                                                    <div class="form-help">Add reCAPTCHA to login page</div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="recaptcha-protect-comments-toggle"
                                                                data-path="security.recaptcha_protection.protect_comments">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label"
                                                            for="recaptcha-protect-comments-toggle">
                                                            <i class="fas fa-comment toggle-icon"></i>
                                                            Protect Comments
                                                        </label>
                                                    </div>
                                                    <div class="form-help">Add reCAPTCHA to comments</div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1); margin: 2rem 0;">

                                        <div class="config-section mb-8">
                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                                <h3 class="font-semibold mb-0">Vulnerability Monitoring</h3>
                                                <div class="toggle-container" style="margin-left: auto;">
                                                    <div class="toggle-wrapper">
                                                        <input type="checkbox" class="config-input toggle-input"
                                                            id="vulnerability-monitoring-toggle"
                                                            data-path="security.vulnerability_monitoring.enabled">
                                                        <div class="toggle-switch"></div>
                                                    </div>
                                                    <label class="toggle-label" for="vulnerability-monitoring-toggle">
                                                        <i class="fas fa-bug toggle-icon"></i>
                                                        Enable Vulnerability Monitoring
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-help mb-4">Monitor plugins and themes for known
                                                vulnerabilities</div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">Notification Email</label>
                                                    <input type="email" class="form-input config-input"
                                                        data-path="security.vulnerability_monitoring.notification_email"
                                                        placeholder="security@example.com">
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Check Frequency</label>
                                                    <select class="form-select config-input"
                                                        data-path="security.vulnerability_monitoring.check_frequency">
                                                        <option value="daily">Daily</option>
                                                        <option value="weekly">Weekly</option>
                                                        <option value="monthly">Monthly</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-3 gap-4 mt-3">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="vuln-check-plugins-toggle"
                                                                data-path="security.vulnerability_monitoring.check_plugins">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="vuln-check-plugins-toggle">
                                                            <i class="fas fa-plug toggle-icon"></i>
                                                            Check Plugins
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="vuln-check-themes-toggle"
                                                                data-path="security.vulnerability_monitoring.check_themes">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="vuln-check-themes-toggle">
                                                            <i class="fas fa-palette toggle-icon"></i>
                                                            Check Themes
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="vuln-auto-update-toggle"
                                                                data-path="security.vulnerability_monitoring.auto_update_minor">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="vuln-auto-update-toggle">
                                                            <i class="fas fa-sync toggle-icon"></i>
                                                            Auto-Update Minor
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group mt-3">
                                                <label class="form-label">Severity Threshold</label>
                                                <select class="form-select config-input"
                                                    data-path="security.vulnerability_monitoring.severity_threshold">
                                                    <option value="low">Low</option>
                                                    <option value="medium">Medium</option>
                                                    <option value="high">High</option>
                                                    <option value="critical">Critical</option>
                                                </select>
                                                <div class="form-help">Only alert on vulnerabilities at or above this
                                                    severity</div>
                                            </div>
                                        </div>

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1); margin: 2rem 0;">

                                        <div class="config-section mb-8">
                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                                <h3 class="font-semibold mb-0">Malware Protection</h3>
                                                <div class="toggle-container" style="margin-left: auto;">
                                                    <div class="toggle-wrapper">
                                                        <input type="checkbox" class="config-input toggle-input"
                                                            id="malware-protection-toggle"
                                                            data-path="security.malware_protection.enabled">
                                                        <div class="toggle-switch"></div>
                                                    </div>
                                                    <label class="toggle-label" for="malware-protection-toggle">
                                                        <i class="fas fa-virus toggle-icon"></i>
                                                        Enable Malware Protection
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-help mb-4">Comprehensive malware scanning and protection
                                            </div>

                                            <div class="grid grid-cols-2 gap-4 mb-3">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="realtime-scanning-toggle"
                                                                data-path="security.malware_protection.real_time_scanning.enabled">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="realtime-scanning-toggle">
                                                            <i class="fas fa-eye toggle-icon"></i>
                                                            Real-Time Scanning
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Sensitivity Level</label>
                                                    <select class="form-select config-input"
                                                        data-path="security.malware_protection.real_time_scanning.sensitivity">
                                                        <option value="low">Low</option>
                                                        <option value="medium">Medium</option>
                                                        <option value="high">High</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-3 gap-4 mb-3">
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="scheduled-scans-toggle"
                                                                data-path="security.malware_protection.scheduled_scans.enabled">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="scheduled-scans-toggle">
                                                            <i class="fas fa-calendar toggle-icon"></i>
                                                            Scheduled Scans
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Scan Frequency</label>
                                                    <select class="form-select config-input"
                                                        data-path="security.malware_protection.scheduled_scans.frequency">
                                                        <option value="daily">Daily</option>
                                                        <option value="weekly">Weekly</option>
                                                        <option value="monthly">Monthly</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Scan Hour (0-23)</label>
                                                    <input type="number" class="form-input config-input"
                                                        data-path="security.malware_protection.scheduled_scans.scan_hour"
                                                        min="0" max="23" placeholder="2">
                                                </div>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label class="form-label">Scan Options</label>
                                                <div class="grid grid-cols-4 gap-2">
                                                    <label class="checkbox-label">
                                                        <input type="checkbox" class="config-checkbox"
                                                            data-path="security.malware_protection.scan_options.scan_malware">
                                                        Scan Malware
                                                    </label>
                                                    <label class="checkbox-label">
                                                        <input type="checkbox" class="config-checkbox"
                                                            data-path="security.malware_protection.scan_options.scan_file_changes">
                                                        File Changes
                                                    </label>
                                                    <label class="checkbox-label">
                                                        <input type="checkbox" class="config-checkbox"
                                                            data-path="security.malware_protection.scan_options.scan_core_files">
                                                        Core Files
                                                    </label>
                                                    <label class="checkbox-label">
                                                        <input type="checkbox" class="config-checkbox"
                                                            data-path="security.malware_protection.scan_options.scan_plugins">
                                                        Plugins
                                                    </label>
                                                    <label class="checkbox-label">
                                                        <input type="checkbox" class="config-checkbox"
                                                            data-path="security.malware_protection.scan_options.scan_themes">
                                                        Themes
                                                    </label>
                                                    <label class="checkbox-label">
                                                        <input type="checkbox" class="config-checkbox"
                                                            data-path="security.malware_protection.scan_options.scan_images">
                                                        Images
                                                    </label>
                                                    <label class="checkbox-label">
                                                        <input type="checkbox" class="config-checkbox"
                                                            data-path="security.malware_protection.scan_options.scan_comments">
                                                        Comments
                                                    </label>
                                                    <label class="checkbox-label">
                                                        <input type="checkbox" class="config-checkbox"
                                                            data-path="security.malware_protection.scan_options.scan_posts">
                                                        Posts
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group mb-3">
                                                <div class="toggle-container">
                                                    <div class="toggle-wrapper">
                                                        <input type="checkbox" class="config-input toggle-input"
                                                            id="email-alerts-toggle"
                                                            data-path="security.malware_protection.email_alerts.enabled">
                                                        <div class="toggle-switch"></div>
                                                    </div>
                                                    <label class="toggle-label" for="email-alerts-toggle">
                                                        <i class="fas fa-envelope toggle-icon"></i>
                                                        Enable Email Alerts
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-4 gap-2 mb-3">
                                                <label class="checkbox-label">
                                                    <input type="checkbox" class="config-checkbox"
                                                        data-path="security.malware_protection.email_alerts.scan_issues">
                                                    Scan Issues
                                                </label>
                                                <label class="checkbox-label">
                                                    <input type="checkbox" class="config-checkbox"
                                                        data-path="security.malware_protection.email_alerts.blocking_events">
                                                    Blocking Events
                                                </label>
                                                <label class="checkbox-label">
                                                    <input type="checkbox" class="config-checkbox"
                                                        data-path="security.malware_protection.email_alerts.login_lockouts">
                                                    Login Lockouts
                                                </label>
                                                <label class="checkbox-label">
                                                    <input type="checkbox" class="config-checkbox"
                                                        data-path="security.malware_protection.email_alerts.admin_logins">
                                                    Admin Logins
                                                </label>
                                                <label class="checkbox-label">
                                                    <input type="checkbox" class="config-checkbox"
                                                        data-path="security.malware_protection.email_alerts.breach_attempts">
                                                    Breach Attempts
                                                </label>
                                                <label class="checkbox-label">
                                                    <input type="checkbox" class="config-checkbox"
                                                        data-path="security.malware_protection.email_alerts.plugin_deactivation">
                                                    Plugin Deactivation
                                                </label>
                                                <label class="checkbox-label">
                                                    <input type="checkbox" class="config-checkbox"
                                                        data-path="security.malware_protection.email_alerts.file_changes">
                                                    File Changes
                                                </label>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4 mb-3">
                                                <div class="form-group">
                                                    <label class="form-label">Alert Frequency</label>
                                                    <select class="form-select config-input"
                                                        data-path="security.malware_protection.email_alerts.alert_frequency">
                                                        <option value="immediately">Immediately</option>
                                                        <option value="hourly">Hourly</option>
                                                        <option value="daily">Daily</option>
                                                        <option value="weekly">Weekly</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Alert Threshold</label>
                                                    <select class="form-select config-input"
                                                        data-path="security.malware_protection.email_alerts.alert_threshold">
                                                        <option value="low">Low</option>
                                                        <option value="medium">Medium</option>
                                                        <option value="high">High</option>
                                                        <option value="critical">Critical</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <div class="toggle-container">
                                                    <div class="toggle-wrapper">
                                                        <input type="checkbox" class="config-input toggle-input"
                                                            id="auto-cleaning-toggle"
                                                            data-path="security.malware_protection.auto_cleaning.enabled">
                                                        <div class="toggle-switch"></div>
                                                    </div>
                                                    <label class="toggle-label" for="auto-cleaning-toggle">
                                                        <i class="fas fa-broom toggle-icon"></i>
                                                        Enable Auto-Cleaning
                                                    </label>
                                                </div>
                                                <div class="form-help text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    <strong>Warning:</strong> This option must be used carefully.
                                                    Auto-cleaning can remove infected files automatically.
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1); margin: 2rem 0;">

                                        <div class="config-section mb-8">
                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                                <h3 class="font-semibold mb-0">Brute Force Protection</h3>
                                                <div class="toggle-container" style="margin-left: auto;">
                                                    <div class="toggle-wrapper">
                                                        <input type="checkbox" class="config-input toggle-input"
                                                            id="brute-force-protection-toggle"
                                                            data-path="security.brute_force_protection.enabled">
                                                        <div class="toggle-switch"></div>
                                                    </div>
                                                    <label class="toggle-label" for="brute-force-protection-toggle">
                                                        <i class="fas fa-user-shield toggle-icon"></i>
                                                        Enable Brute Force Protection
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-help mb-4">Protect against brute force login attacks</div>

                                            <div class="grid grid-cols-3 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">Login Attempt Threshold</label>
                                                    <input type="number" class="form-input config-input"
                                                        data-path="security.brute_force_protection.login_attempt_threshold"
                                                        min="1" max="10" placeholder="3">
                                                    <div class="form-help">Failed attempts before blocking</div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Block Duration (hours)</label>
                                                    <input type="number" class="form-input config-input"
                                                        data-path="security.brute_force_protection.block_duration_hours"
                                                        min="1" max="168" placeholder="24">
                                                    <div class="form-help">How long to block IP</div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="toggle-container">
                                                        <div class="toggle-wrapper">
                                                            <input type="checkbox" class="config-input toggle-input"
                                                                id="immediate-ip-blocking-toggle"
                                                                data-path="security.brute_force_protection.immediate_ip_blocking">
                                                            <div class="toggle-switch"></div>
                                                        </div>
                                                        <label class="toggle-label" for="immediate-ip-blocking-toggle">
                                                            <i class="fas fa-ban toggle-icon"></i>
                                                            Immediate IP Blocking
                                                        </label>
                                                    </div>
                                                    <div class="form-help">Block immediately on threshold</div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1);">

                                        <div class="config-section mb-8">
                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                                <h3 class="font-semibold mb-0">IP Blocking</h3>
                                                <div class="toggle-container" style="margin-left: auto;">
                                                    <div class="toggle-wrapper">
                                                        <input type="checkbox" class="config-input toggle-input"
                                                            id="ip-blocking-toggle"
                                                            data-path="security.ip_blocking.enabled">
                                                        <div class="toggle-switch"></div>
                                                    </div>
                                                    <label class="toggle-label" for="ip-blocking-toggle">
                                                        <i class="fas fa-shield-alt toggle-icon"></i>
                                                        Enable IP Blocking
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-help mb-4">Block or allow specific IP addresses from
                                                accessing your site</div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">Allowed IP Addresses</label>
                                                    <textarea class="form-input config-input" rows="5"
                                                        data-path="security.ip_blocking.allowed_ips"
                                                        placeholder="Enter one IP per line&#10;192.168.1.100&#10;10.0.0.50&#10;203.0.113.0/24"></textarea>
                                                    <div class="form-help">Whitelist IPs that should always have access.
                                                        Supports CIDR notation (e.g., 192.168.1.0/24)</div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Blocked IP Addresses</label>
                                                    <textarea class="form-input config-input" rows="5"
                                                        data-path="security.ip_blocking.blocked_ips"
                                                        placeholder="Enter one IP per line&#10;192.168.1.200&#10;172.16.0.100&#10;198.51.100.0/24"></textarea>
                                                    <div class="form-help">Blacklist IPs that should be blocked.
                                                        Supports CIDR notation</div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="form-help text-info">
                                                    <i class="fas fa-info-circle"></i>
                                                    <strong>Note:</strong> Allowed IPs take precedence over blocked IPs.
                                                    Use carefully to avoid locking yourself out.
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1);">

                                        <div class="config-section mb-8">
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
