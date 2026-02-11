                    <!-- Configuration Tab -->
                    <div id="configuration-content" class="tab-content">
                        <div class="page-header">
                            <h1 class="page-title">Configuration</h1>
                            <p class="page-description">Manage all system configurations</p>
                        </div>

                        <div class="tab-container">
                            <div class="tab-nav">
                                <a href="#" class="tab-link active" data-subtab="git-config">Git Config</a>
                                <a href="#" class="tab-link" data-subtab="site-config">Kinsta Settings</a>
                                <a href="#" class="tab-link" data-subtab="security-config">Security</a>
                                <a href="#" class="tab-link" data-subtab="integrations-config">Integrations</a>
                                <a href="#" class="tab-link" data-subtab="plugins-config">Plugins</a>
                                <a href="#" class="tab-link" data-subtab="policies-config">Policies</a>
                            </div>
                        </div>

                        <!-- Git Configuration -->
                        <?php require_once __DIR__ . '/../config-subtabs/git.php'; ?>

                        <!-- Kinsta Settings -->
                        <?php require_once __DIR__ . '/../config-subtabs/site.php'; ?>

                        <!-- Security Configuration -->
                        <?php require_once __DIR__ . '/../config-subtabs/security.php'; ?>

                        <!-- Integrations Configuration -->
                        <?php require_once __DIR__ . '/../config-subtabs/integrations.php'; ?>

                        <!-- Plugins Configuration -->
                        <?php require_once __DIR__ . '/../config-subtabs/plugins.php'; ?>

                        <!-- Policies Configuration -->
                        <?php require_once __DIR__ . '/../config-subtabs/policies.php'; ?>

                    </div>
