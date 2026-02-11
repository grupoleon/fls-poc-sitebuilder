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
