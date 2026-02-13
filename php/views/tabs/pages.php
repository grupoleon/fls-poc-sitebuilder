                    <div id="pages-content" class="tab-content">
                        <div class="page-header">
                            <h1 class="page-title">Page Editor</h1>
                            <p class="page-description">Edit page content and layouts for your themes</p>
                        </div>

                        <div class="card mb-6">
                            <div class="card-header">
                                <h2 class="card-title">Theme Selection</h2>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-4">
                                    <label class="form-label">Active Theme</label>
                                    <div class="d-flex gap-2">
                                        <select id="page-theme-select" class="form-select" style="flex: 1;">
                                            <option value="">Loading themes...</option>
                                        </select>
                                        <button type="button" id="refresh-theme-list-btn" class="btn btn-outline"
                                            title="Refresh theme list from folder structure">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                        <button type="button" id="clean-uploads-btn" class="btn btn-outline"
                                            title="Delete unused uploads from the repo" style="white-space:nowrap;"
                                            onclick="window.adminInterface.cleanUnusedUploads()">
                                            <i class="fas fa-trash-alt"></i> Clean Uploads
                                        </button>
                                    </div>
                                    <small class="text-muted mt-1 d-block">Themes are loaded from pages/themes
                                        folder</small>
                                </div>

                                <h4 class="mb-3">Override Settings - <small>Select which custom content will override
                                        theme defaults during deployment</small></h4>

                                <div class="form-group">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" id="slides-override-toggle"
                                                    class="form-check-input override-toggle"
                                                    data-override-type="slides_override">
                                                <label class="form-check-label"
                                                    for="slides-override-toggle">Slides</label>
                                            </div>
                                            <small id="slides-override-status" class="text-muted d-block mt-1">
                                                <i class="fas fa-circle-notch fa-spin"></i> Loading...
                                            </small>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" id="pages-override-toggle"
                                                    class="form-check-input override-toggle"
                                                    data-override-type="pages_override">
                                                <label class="form-check-label"
                                                    for="pages-override-toggle">Pages</label>
                                            </div>
                                            <small id="pages-override-status" class="text-muted d-block mt-1">
                                                <i class="fas fa-circle-notch fa-spin"></i> Loading...
                                            </small>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" id="cpt-override-toggle"
                                                    class="form-check-input override-toggle"
                                                    data-override-type="cpt_override">
                                                <label class="form-check-label" for="cpt-override-toggle">CPT</label>
                                            </div>
                                            <small id="cpt-override-status" class="text-muted d-block mt-1">
                                                <i class="fas fa-circle-notch fa-spin"></i> Loading...
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-6">
                            <div class="card-header">
                                <h2 class="card-title">Site Logo</h2>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Upload Logo</label>
                                    <div class="logo-upload-container">
                                        <div class="logo-upload" id="logo-upload">
                                            <input type="file" class="file-input logo-input" accept="image/*"
                                                id="logo-file-input">
                                            <div class="logo-preview">
                                                <img id="logo-preview-img" src="" alt="Logo Preview"
                                                    style="display: none;">
                                            </div>
                                            <div class="logo-upload-text" id="logo-upload-text">
                                                Click to upload or drag logo here<br>
                                                <small class="text-muted">Recommended: PNG, SVG, or JPG (Max
                                                    2MB)</small>
                                            </div>
                                        </div>
                                        <div class="logo-actions mt-3" style="display: none;" id="logo-actions">
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                id="remove-logo-btn">
                                                Remove Logo
                                            </button>
                                            <button type="button" class="btn btn-primary btn-sm" id="save-logo-btn">
                                                Save Logo
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            Logo will be deployed when you deploy the site. Current logos are
                                            managed in
                                            the
                                            uploads directory.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header flex items-center justify-between">
                                <h2 class="card-title">Page Content</h2>
                                <button type="button" class="btn btn-primary save-page-btn">
                                    Save All Changes
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="tabs">
                                    <ul id="page-tabs" class="tab-list">
                                        <li class="tab-item">
                                            <span class="text-muted">Select a theme to load pages</span>
                                        </li>
                                    </ul>
                                </div>

                                <div id="page-sections" class="mt-6">
                                    <div class="text-center text-muted">
                                        <p>Select a theme and page to start editing</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
