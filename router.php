<?php
/**
 * Router for PHP Built-in Server
 * Handles serving static files and routing requests properly
 */

// Get the requested URI
$requestUri  = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Remove query parameters for file path checking
$filePath = __DIR__ . $requestPath;

// Special handling for admin assets - they're in the php/admin directory
if (strpos($requestPath, '/admin/assets/') === 0) {
    $filePath = __DIR__ . '/php' . $requestPath;
}

// Special handling for uploads directory - use consolidated uploads
if (strpos($requestPath, '/uploads/') === 0) {
    // Use the consolidated uploads directory
    $filePath = __DIR__ . $requestPath;

    // Fallback to php/uploads directory for backward compatibility
    if (! file_exists($filePath)) {
        $phpUploadsPath = __DIR__ . '/php' . $requestPath;
        if (file_exists($phpUploadsPath)) {
            $filePath = $phpUploadsPath;
        }
    }
}

// Handle static files (CSS, JS, images, etc.)
if (file_exists($filePath) && is_file($filePath)) {
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);

    switch ($extension) {
        case 'css':
            header('Content-Type: text/css');
            readfile($filePath);
            return true;

        case 'js':
            header('Content-Type: application/javascript');
            readfile($filePath);
            return true;

        case 'png':
            header('Content-Type: image/png');
            readfile($filePath);
            return true;

        case 'jpg':
        case 'jpeg':
            header('Content-Type: image/jpeg');
            readfile($filePath);
            return true;

        case 'gif':
            header('Content-Type: image/gif');
            readfile($filePath);
            return true;

        case 'svg':
            header('Content-Type: image/svg+xml');
            readfile($filePath);
            return true;

        case 'ico':
            header('Content-Type: image/x-icon');
            readfile($filePath);
            return true;

        case 'woff':
        case 'woff2':
            header('Content-Type: font/woff' . ($extension === 'woff2' ? '2' : ''));
            readfile($filePath);
            return true;

        case 'ttf':
            header('Content-Type: font/ttf');
            readfile($filePath);
            return true;

        case 'json':
            header('Content-Type: application/json');
            readfile($filePath);
            return true;

        default:
            // For other file types, let PHP handle or return 404
            if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'php') {
                header('Content-Type: ' . mime_content_type($filePath));
                readfile($filePath);
                return true;
            }
    }
}

// Handle PHP file routing
if ($requestPath === '/' || $requestPath === '') {
    // Root request goes to web-admin.php
    require_once __DIR__ . '/php/web-admin.php';
    return true;
}

// Handle requests to specific PHP files via GET parameter
if (isset($_GET['file'])) {
    $requestedFile = $_GET['file'];

    // Security: Only allow files within the php directory and its subdirectories
    $allowedPath = __DIR__ . '/php/';
    $targetFile  = $allowedPath . $requestedFile;

    // Resolve the path and ensure it's within allowed directory
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

// Handle requests to other PHP files in the php directory
$phpFilePath = __DIR__ . '/php' . $requestPath;
if (file_exists($phpFilePath) && pathinfo($phpFilePath, PATHINFO_EXTENSION) === 'php') {
    require_once $phpFilePath;
    return true;
}

// If no file found, try to route to web-admin.php (SPA behavior)
if (! file_exists($filePath)) {
    require_once __DIR__ . '/php/web-admin.php';
    return true;
}

// Return false to let PHP's built-in server handle the request
return false;
