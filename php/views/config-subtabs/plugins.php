                        <div id="plugins-config-tab" class="subtab-content">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">Plugins Configuration</h2>
                                </div>
                                <div class="card-body">
                                    <form id="plugins-config-form">
                                        <!-- Keep Plugins -->
                                        <div class="card mt-4">
                                            <div class="card-header">
                                                <h4 class="card-title">Plugins to Keep</h4>
                                                <p class="text-muted">These plugins will not be removed during
                                                    deployment
                                                </p>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <label class="form-label">Keep Plugins</label>
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            id="add-keep-plugin-btn">
                                                            <i class="fas fa-plus"></i> Add Plugin
                                                        </button>
                                                    </div>
                                                    <div id="keep-plugins-container">
                                                        <!-- Keep plugins will be dynamically populated -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Install Plugins -->
                                        <div class="card mt-4">
                                            <div class="card-header">
                                                <h4 class="card-title">Plugins to Install</h4>
                                                <p class="text-muted">These plugins will be installed during deployment
                                                </p>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <label class="form-label">Install Plugins</label>
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            id="add-install-plugin-btn">
                                                            <i class="fas fa-plus"></i> Add Plugin
                                                        </button>
                                                    </div>
                                                    <div id="install-plugins-container">
                                                        <!-- Install plugins will be dynamically populated -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-primary save-config-btn"
                                            data-type="plugins">
                                            Save Plugins Configuration
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Policies Configuration -->
