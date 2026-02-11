                    <div id="contents-content" class="tab-content">
                        <div class="page-header">
                            <h1 class="page-title">Other Contents</h1>
                            <p class="page-description">Manage Issues, Endorsements, and News content</p>
                        </div>

                        <div class="card">
                            <div class="card-header flex items-center justify-between">
                                <h2 class="card-title">Content Management</h2>
                                <button type="button" class="btn btn-primary save-contents-btn"
                                    onclick="saveAllContents()">
                                    Save All Changes
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="tabs">
                                    <ul class="tab-list" id="contents-tabs">
                                        <li class="tab-item">
                                            <a href="#" class="tab-link active" data-content-tab="issues">
                                                <i class="fas fa-clipboard-list"></i>
                                                Issues
                                            </a>
                                        </li>
                                        <li class="tab-item">
                                            <a href="#" class="tab-link" data-content-tab="endorsements">
                                                <i class="fas fa-star"></i>
                                                Endorsements
                                            </a>
                                        </li>
                                        <li class="tab-item">
                                            <a href="#" class="tab-link" data-content-tab="news">
                                                <i class="fas fa-newspaper"></i>
                                                News
                                            </a>
                                        </li>
                                        <li class="tab-item">
                                            <a href="#" class="tab-link" data-content-tab="posts">
                                                <i class="fas fa-file-alt"></i>
                                                Posts
                                            </a>
                                        </li>
                                        <li class="tab-item">
                                            <a href="#" class="tab-link" data-content-tab="testimonials">
                                                <i class="fas fa-quote-left"></i>
                                                Testimonials
                                            </a>
                                        </li>
                                        <li class="tab-item">
                                            <a href="#" class="tab-link" data-content-tab="sliders">
                                                <i class="fas fa-images"></i>
                                                Sliders
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="tab-content-area mt-6">
                                    <!-- Issues Tab -->
                                    <div id="issues-tab" class="content-tab-panel active">
                                        <div class="content-header mb-4">
                                            <div class="flex items-center justify-between">
                                                <h3>Issues Management</h3>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm add-issue-btn"
                                                    onclick="addNewIssue()">
                                                    <i class="fas fa-plus"></i> Add New Issue
                                                </button>
                                            </div>
                                        </div>
                                        <div id="issues-list" class="content-list">
                                            <!-- Issues will be loaded here dynamically -->
                                        </div>
                                    </div>

                                    <!-- Endorsements Tab -->
                                    <div id="endorsements-tab" class="content-tab-panel">
                                        <div class="content-header mb-4">
                                            <div class="flex items-center justify-between">
                                                <h3>Endorsements Management</h3>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm add-endorsement-btn"
                                                    onclick="addNewEndorsement()">
                                                    <i class="fas fa-plus"></i> Add New Endorsement
                                                </button>
                                            </div>
                                        </div>
                                        <div id="endorsements-list" class="content-list">
                                            <!-- Endorsements will be loaded here dynamically -->
                                        </div>
                                    </div>

                                    <!-- News Tab -->
                                    <div id="news-tab" class="content-tab-panel">
                                        <div class="content-header mb-4">
                                            <div class="flex items-center justify-between">
                                                <h3>News Management</h3>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm add-news-btn"
                                                    onclick="addNewNews()">
                                                    <i class="fas fa-plus"></i> Add New News Article
                                                </button>
                                            </div>
                                        </div>
                                        <div id="news-list" class="content-list">
                                            <!-- News will be loaded here dynamically -->
                                        </div>
                                    </div>

                                    <!-- Posts Tab -->
                                    <div id="posts-tab" class="content-tab-panel">
                                        <div class="content-header mb-4">
                                            <div class="flex items-center justify-between">
                                                <h3>Posts Management</h3>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm add-post-btn"
                                                    onclick="addNewPost()">
                                                    <i class="fas fa-plus"></i> Add New Post
                                                </button>
                                            </div>
                                        </div>
                                        <div id="posts-list" class="content-list">
                                            <!-- Posts will be loaded here dynamically -->
                                        </div>
                                    </div>

                                    <!-- Testimonials Tab -->
                                    <div id="testimonials-tab" class="content-tab-panel">
                                        <div class="content-header mb-4">
                                            <div class="flex items-center justify-between">
                                                <h3>Testimonials Management</h3>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm add-testimonial-btn"
                                                    onclick="addNewTestimonial()">
                                                    <i class="fas fa-plus"></i> Add New Testimonial
                                                </button>
                                            </div>
                                        </div>
                                        <div id="testimonials-list" class="content-list">
                                            <!-- Testimonials will be loaded here dynamically -->
                                        </div>
                                    </div>

                                    <!-- Sliders Tab -->
                                    <div id="sliders-tab" class="content-tab-panel">
                                        <div class="content-header mb-4">
                                            <div class="flex items-center justify-between">
                                                <h3>Slider Management</h3>
                                                <button type="button"
                                                    class="btn btn-outline-primary btn-sm add-slider-btn"
                                                    onclick="addNewSlider()">
                                                    <i class="fas fa-plus"></i> Add New Slide
                                                </button>
                                            </div>
                                        </div>
                                        <div id="sliders-list" class="content-list">
                                            <!-- Sliders will be loaded here dynamically -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
