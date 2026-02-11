                <div class="sidebar-header">
                    <a href="#" class="logo">
                        <img id="fls-logo" src="/php/admin/assets/img/logo.png" alt="Framework Interface Logo">
                    </a>
                </div>

                <div class="sidebar-nav">
                    <div class="nav-item">
                        <a href="#" class="nav-link active" data-tab="deployment">
                            <i class="fas fa-cloud-upload-alt nav-icon"></i>
                            Deployment
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-tab="configuration">
                            <i class="fas fa-cogs nav-icon"></i>
                            Configuration
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-tab="pages">
                            <i class="fas fa-edit nav-icon"></i>
                            Page Editor
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-tab="contents">
                            <i class="fas fa-list-alt nav-icon"></i>
                            Other Contents
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-tab="forms">
                            <i class="fas fa-file-lines nav-icon"></i>
                            Forms Manager
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-tab="local-config">
                            <i class="fas fa-cog nav-icon"></i>
                            Local Config
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="#" class="nav-link" data-tab="raw-configs">
                            <i class="fas fa-code nav-icon"></i>
                            Raw Configs
                        </a>
                    </div>
                </div>

                <!-- User Info and Logout -->
                <div class="sidebar-footer"
                    style="padding: var(--space-4); border-top: 1px solid rgba(255, 255, 255, 0.1);">

                    <!-- User Profile -->
                    <div class="user-info mb-3"
                        style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                        <?php if (Auth::getPicture()): ?>
                        <img src="<?php echo htmlspecialchars(Auth::getPicture()); ?>" alt="Profile"
                            style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size: 28px; color: rgba(255,255,255,0.7);"></i>
                        <?php endif; ?>
                        <div style="flex: 1; min-width: 0;">
                            <div
                                style="font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.9); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars(Auth::getName() ?: 'User'); ?>
                            </div>
                            <div
                                style="font-size: 10px; color: rgba(255,255,255,0.5); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars(Auth::getEmail() ?: ''); ?>
                            </div>
                        </div>
                        <a href="/php/login.php?action=logout" class="logout-btn" title="Logout"
                            style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: rgba(239,68,68,0.1); border-radius: 6px; color: #ef4444; transition: all 0.2s;">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>

                    <a href="/php/admin/docs/index.php" class="btn btn-primary btn-sm w-full mb-3" target="_blank"
                        style="text-decoration: none; background: linear-gradient(135deg, #14b8a6, #0d9488); border: none; box-shadow: 0 4px 6px rgba(20, 184, 166, 0.3); font-weight: 600;">
                        <i class="fas fa-book"></i> Documentation
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm w-full" id="help-btn"
                        style="background: transparent; border: 2px solid rgba(255, 255, 255, 0.2); color: rgba(255, 255, 255, 0.8); font-weight: 500;">
                        <i class="fas fa-question-circle"></i> Help & Shortcuts
                    </button>
                </div>
