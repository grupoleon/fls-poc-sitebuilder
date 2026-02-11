                    <div id="forms-content" class="tab-content">
                        <div class="page-header">
                            <h2>Forms Manager</h2>
                            <p class="text-muted">Create, edit, and manage Forminator forms with drag-and-drop interface
                            </p>
                        </div>

                        <div class="forms-manager">
                            <div class="forms-manager-header">
                                <h2>Forminator Forms Manager</h2>
                                <button id="add-form-btn" class="form-save-btn">+ New Form</button>
                            </div>

                            <div id="forms-list" class="forms-list">
                                <!-- Forms will be loaded here dynamically -->
                            </div>

                            <div id="form-editor" class="form-editor" style="display:none;">
                                <div class="form-editor-header">
                                    <h3 id="form-editor-title">Edit Form</h3>
                                    <button type="button" id="form-cancel-btn" class="btn btn-sm btn-outline-secondary">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18" />
                                            <line x1="6" y1="6" x2="18" y2="18" />
                                        </svg>
                                        Cancel
                                    </button>
                                </div>

                                <div id="form-success" class="form-success" style="display:none;"></div>
                                <div id="form-error" class="form-error" style="display:none;"></div>

                                <form id="form-edit-form">
                                    <label for="form-name">Form Name *</label>
                                    <input type="text" id="form-name" name="form-name" placeholder="Enter form name..."
                                        required>

                                    <label>Add Form Elements</label>
                                    <div class="form-add-element">
                                        <select id="element-type">
                                            <option value="text">Text Input</option>
                                            <option value="email">Email Input</option>
                                            <option value="textarea">Textarea</option>
                                            <option value="select">Select Dropdown</option>
                                            <option value="checkbox">Checkbox</option>
                                            <option value="radio">Radio Button</option>
                                            <option value="captcha">reCAPTCHA</option>
                                        </select>
                                        <input type="text" id="element-label"
                                            placeholder="Element Label (e.g., Full Name)">
                                        <button type="button" id="add-element-btn" class="btn btn-primary">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <line x1="12" y1="5" x2="12" y2="19" />
                                                <line x1="5" y1="12" x2="19" y2="12" />
                                            </svg>
                                            Add
                                        </button>
                                    </div>

                                    <label>Form Elements</label>
                                    <div class="form-elements-list" id="form-elements-list">
                                        <!-- Form elements will be rendered here -->
                                    </div>

                                    <label>Submit Button Settings</label>
                                    <div class="form-group">
                                        <input type="text" id="submit-button-text"
                                            placeholder="Submit button text (e.g., SUBMIT, Send Message)"
                                            value="SUBMIT">
                                        <small class="text-muted">This text will appear on the submit button</small>
                                    </div>

                                    <label>Form Placeholders</label>
                                    <div class="form-group">
                                        <small class="text-muted">Add placeholder tags that will be replaced with this
                                            form during auto-placement (e.g., "Contact Form" becomes
                                            [contact-form])</small>
                                        <div class="placeholder-input-wrapper">
                                            <input type="text" id="placeholder-input"
                                                placeholder="Enter placeholder (e.g., Contact Form)"
                                                title="Enter placeholder name (will be auto-slugified and wrapped in brackets)">
                                            <button type="button" id="add-placeholder-btn"
                                                class="btn btn-sm btn-success">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2">
                                                    <line x1="12" y1="5" x2="12" y2="19" />
                                                    <line x1="5" y1="12" x2="19" y2="12" />
                                                </svg>
                                                Add Placeholder
                                            </button>
                                        </div>
                                        <div id="placeholders-list" class="placeholders-list">
                                            <!-- Placeholders will be rendered here -->
                                        </div>
                                    </div>

                                    <div class="form-editor-actions">
                                        <button type="button" id="form-save-btn" class="form-save-btn">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <path
                                                    d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                                                <polyline points="17 21 17 13 7 13 7 21" />
                                                <polyline points="7 3 7 8 15 8" />
                                            </svg>
                                            Save Form
                                        </button>
                                        <button type="button" id="form-delete-btn" class="form-delete-btn"
                                            style="display:none;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                            </svg>
                                            Delete Form
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
