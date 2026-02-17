<?php
    require_once __DIR__ . '/bootstrap.php';
    require_once __DIR__ . '/admin/includes/Auth.php';

    // Require Google Workspace authentication
    Auth::requireAuth();
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <?php require_once __DIR__ . '/views/head.php'; ?>
    </head>

    <body>
        <div class="admin-container">
            <!-- Modern Sidebar Navigation -->
            <nav class="admin-sidebar">
                <?php require_once __DIR__ . '/views/sidebar.php'; ?>
            </nav>

            <!-- Modern Main Content -->
            <main class="admin-main">

                <div class="main-content">
                    <?php require_once __DIR__ . '/views/tabs/deployment.php'; ?>
                    <?php require_once __DIR__ . '/views/tabs/configuration.php'; ?>
                    <?php require_once __DIR__ . '/views/tabs/pages.php'; ?>
                    <?php require_once __DIR__ . '/views/tabs/contents.php'; ?>
                    <?php require_once __DIR__ . '/views/tabs/forms.php'; ?>
                    <?php require_once __DIR__ . '/views/tabs/local-config.php'; ?>
                    <?php require_once __DIR__ . '/views/tabs/deployment-history.php'; ?>
                    <?php require_once __DIR__ . '/views/tabs/raw-configs.php'; ?>
                </div> <!-- Close main-content -->
            </main>
        </div>


        <!-- Help Modal -->
        <?php include 'admin/includes/help.php'; ?>

        <!-- Core JS Modules -->
        <script src="/php/admin/assets/js/core/Logger.js"></script>
        <script src="/php/admin/assets/js/core/ApiClient.js"></script>
        <script src="/php/admin/assets/js/core/Utils.js"></script>

        <!-- Application JS Files -->
        <script src="/php/admin/assets/js/tools.js"></script>
        <script src="/php/admin/assets/js/forms.js"></script>
        <script src="/php/admin/assets/js/raw-config.js"></script>
        <script src="/php/admin/assets/js/admin.js"></script>
        <script src="/php/admin/assets/js/local-config.js"></script>
    </body>

</html>
