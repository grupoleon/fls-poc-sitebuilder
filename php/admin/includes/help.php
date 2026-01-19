<div id="help-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Help & Keyboard Shortcuts</h3>
            <button type="button" class="close-modal modal-close-btn" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="help-section">
                <h4><i class="fas fa-keyboard"></i> Keyboard Shortcuts</h4>
                <ul class="shortcuts-list">
                    <li><kbd>Ctrl/Cmd + S</kbd> - Save current configuration</li>
                    <li><kbd>Ctrl/Cmd + /</kbd> - Toggle this help modal</li>
                    <li><kbd>Tab</kbd> - Navigate between form fields</li>
                    <li><kbd>Enter</kbd> - Submit forms or activate buttons</li>
                </ul>
            </div>

            <div class="help-section">
                <h4><i class="fas fa-cogs"></i> Configuration Sections</h4>
                <ul class="config-sections">
                    <li><strong>Git Config:</strong> Repository settings, server SSH connection details</li>
                    <li><strong>Site Config:</strong> WordPress site details, admin credentials, themes</li>
                    <li><strong>Security:</strong> WordPress hardening, geo-blocking, 2FA settings</li>
                    <li><strong>Integrations:</strong> Analytics, forms, social media, Google Maps</li>
                    <li><strong>Navigation:</strong> Custom menu items and navigation structure</li>
                    <li><strong>Plugins:</strong> Plugin management for installation and preservation</li>
                    <li><strong>Policies:</strong> Password policies and 2FA enforcement rules</li>
                </ul>
            </div>

            <div class="help-section">
                <h4><i class="fas fa-lightbulb"></i> Essential Tips</h4>
                <ul class="tips-list">
                    <li>Configure Git credentials before attempting deployment</li>
                    <li>Test server SSH connection in Git Config before deploying</li>
                    <li>Use the interactive map preview to place location markers</li>
                    <li>Plugin slugs must match WordPress.org directory names exactly</li>
                    <li>Upload logos in Pages tab - supports PNG, SVG, JPG (max 2MB)</li>
                    <li>Enable 2FA policies for enhanced admin security</li>
                </ul>
            </div>

            <div class="help-section">
                <h4><i class="fas fa-exclamation-triangle"></i> Common Issues</h4>
                <ul class="troubleshooting-list">
                    <li><strong>Deployment fails:</strong> Verify Git credentials, SSH keys, and server
                        connectivity</li>
                    <li><strong>Configuration not saving:</strong> Check browser console for errors and fill
                        required fields</li>
                    <li><strong>Map not loading:</strong> Verify Google Maps API key and enable required APIs
                    </li>
                    <li><strong>Plugin errors:</strong> Ensure plugin slugs are correct and publicly available
                    </li>
                    <li><strong>Theme issues:</strong> Select theme in Pages tab before editing content</li>
                </ul>
            </div>
        </div>
        <!-- <div class="modal-footer">
            <p class="text-center">&copy;                                                                                   <?php echo date('Y') ?> FrontlineStrategies. All rights reserved.
            </p>
        </div> -->
    </div>
</div>
<div class="modal-overlay"></div>
