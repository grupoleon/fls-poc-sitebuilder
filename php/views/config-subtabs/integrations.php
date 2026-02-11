                        <div id="integrations-config-tab" class="subtab-content">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Integrations Configuration</h2>
                                </div>
                                <div class="card-body">
                                    <form id="integrations-config-form">
                                        <div class="config-section mb-8">
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

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1); margin: 2rem 0;">

                                        <div class="config-section mb-8">
                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                                <h3 class="font-semibold mb-0">Theme Customization</h3>
                                                <div class="toggle-container" style="margin-left: auto;">
                                                    <div class="toggle-wrapper">
                                                        <input type="checkbox" class="config-input toggle-input"
                                                            id="theme-customization-toggle"
                                                            data-path="integrations.theme_customization.enabled">
                                                        <div class="toggle-switch"></div>
                                                    </div>
                                                    <label class="toggle-label" for="theme-customization-toggle">
                                                        <i class="fas fa-paint-brush toggle-icon"></i>
                                                        Enable Theme Customization
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-help mb-4">Apply custom theme modifications and branding
                                            </div>
                                        </div>

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1);">

                                        <div class="config-section mb-8">
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

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1);">

                                        <div class="config-section mb-8">
                                            <h3 class="font-semibold mb-3">Social Media Links</h3>
                                            <p class="text-sm text-gray-600 mb-4">Social icons will be displayed in the
                                                footer across all themes</p>

                                            <div class="grid grid-cols-1 gap-4 mb-4">
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
                                                        <label class="form-label">YouTube URL</label>
                                                        <input type="url" class="form-input config-input"
                                                            data-path="integrations.social_links.youtube"
                                                            placeholder="https://youtube.com/@yourchannel">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1);">

                                        <div class="config-section mb-8">
                                            <h3 class="font-semibold mb-3">Donation Platform</h3>
                                            <p class="text-sm text-gray-600 mb-4">WinRed integration is handled by a
                                                separate extension</p>

                                            <div class="grid grid-cols-1 gap-4">
                                                <div class="form-group">
                                                    <label class="form-label">WinRed URL</label>
                                                    <input type="url" class="form-input config-input"
                                                        data-path="integrations.donation.winred"
                                                        placeholder="https://secure.winred.com/yourpage">
                                                    <p class="text-xs text-gray-500 mt-1">Configure WinRed donation page
                                                        URL for the extension</p>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-6" style="border-color: rgba(0,0,0,0.1);">

                                        <div class="config-section mb-8">
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

                        <!-- Plugins Configuration -->
