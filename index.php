<?php
/**
 * Front controller + built-in-server router
 *
 * This file acts both as the front controller for Apache/Nginx (includes web-admin)
 * and as the router script when using the PHP built-in server (php -S). It mirrors
 * the old router.php behaviour so the built-in server will serve static files and
 * route other requests to php/web-admin.php.
 */

// Get requested URI / path
$requestUri  = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Map to filesystem
$filePath = __DIR__ . $requestPath;

// Admin assets live under php/admin/assets
if (strpos($requestPath, '/admin/assets/') === 0) {
    $filePath = __DIR__ . '/php' . $requestPath;
}

// Consolidated uploads handling (repo-level uploads/ with fallback to php/uploads)
if (strpos($requestPath, '/uploads/') === 0) {
    $filePath = __DIR__ . $requestPath;
    if (! file_exists($filePath)) {
        $phpUploadsPath = __DIR__ . '/php' . $requestPath;
        if (file_exists($phpUploadsPath)) {
            $filePath = $phpUploadsPath;
        }
    }
}

// Serve static files directly if present
if (file_exists($filePath) && is_file($filePath)) {
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    switch (strtolower($extension)) {
        case 'css':header('Content-Type: text/css');
            readfile($filePath);return true;
        case 'js':header('Content-Type: application/javascript');
            readfile($filePath);return true;
        case 'png':header('Content-Type: image/png');
            readfile($filePath);return true;
        case 'jpg':case 'jpeg':header('Content-Type: image/jpeg');
            readfile($filePath);return true;
        case 'gif':header('Content-Type: image/gif');
            readfile($filePath);return true;
        case 'svg':header('Content-Type: image/svg+xml');
            readfile($filePath);return true;
        case 'ico':header('Content-Type: image/x-icon');
            readfile($filePath);return true;
        case 'woff':case 'woff2':header('Content-Type: font/woff' . ($extension === 'woff2' ? '2' : ''));
            readfile($filePath);return true;
        case 'ttf':header('Content-Type: font/ttf');
            readfile($filePath);return true;
        case 'json':header('Content-Type: application/json');
            readfile($filePath);return true;
        default:
            if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'php') {
                header('Content-Type: ' . mime_content_type($filePath));
                readfile($filePath);
                return true;
            }
    }
}

// Root request goes to web-admin.php
if ($requestPath === '/' || $requestPath === '') {
    require_once __DIR__ . '/php/web-admin.php';
    return true;
}

// Allow requesting specific php files inside php/ via ?file=path (safe path guard)
if (isset($_GET['file'])) {
    $requestedFile = $_GET['file'];
    $allowedPath   = __DIR__ . '/php/';
    $targetFile    = $allowedPath . $requestedFile;

    $realAllowedPath = realpath($allowedPath);
    $realTargetFile  = realpath($targetFile);

    if ($realTargetFile &&
        strpos($realTargetFile, $realAllowedPath) === 0 &&
        pathinfo($realTargetFile, PATHINFO_EXTENSION) === 'php' &&
        file_exists($realTargetFile)) {

        require_once $realTargetFile;
        return true;
    } else {
        http_response_code(404);
        echo "File not found or access denied";
        return false;
    }
}

// Handle direct requests to web-admin.php or route to it
if (strpos($requestPath, '/php/web-admin.php') !== false ||
    strpos($requestPath, 'web-admin.php') !== false ||
    $requestPath === '/') {

    require_once __DIR__ . '/php/web-admin.php';
    return true;
}

// If request points to a php file inside php/ directory, include it
$phpFilePath = __DIR__ . '/php' . $requestPath;
if (file_exists($phpFilePath) && pathinfo($phpFilePath, PATHINFO_EXTENSION) === 'php') {
    require_once $phpFilePath;
    return true;
}

// If no file found, try to route to web-admin.php (SPA behaviour)
if (! file_exists($filePath)) {
    require_once __DIR__ . '/php/web-admin.php';
    return true;
}

// Let the server handle the request (if called as router)
return false;
