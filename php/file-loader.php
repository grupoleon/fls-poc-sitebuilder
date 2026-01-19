<?php
/**
 * Dynamic File Loader
 * Allows loading PHP files dynamically via URL parameters
 */

// Security: Only allow loading files within the php directory
$allowedBasePath = __DIR__;
$allowedPhpPath  = $allowedBasePath . '/admin/';

// Get the requested file from URL parameter
$requestedFile = $_GET['file'] ?? '';

if (empty($requestedFile)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No file specified. Use ?file=path/to/file.php',
    ]);
    exit;
}

// Security: Prevent directory traversal attacks
if (strpos($requestedFile, '..') !== false ||
    strpos($requestedFile, './') !== false ||
    strpos($requestedFile, '\\') !== false) {

    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid file path',
    ]);
    exit;
}

// Build the full file path
$filePath = $allowedPhpPath . $requestedFile;

// Security: Ensure the resolved path is within allowed directory
$realAllowedPath = realpath($allowedPhpPath);
$realFilePath    = realpath($filePath);

if (! $realFilePath || strpos($realFilePath, $realAllowedPath) !== 0) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'File access denied',
    ]);
    exit;
}

// Check if file exists and is a PHP file
if (! file_exists($realFilePath)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'File not found: ' . basename($requestedFile),
    ]);
    exit;
}

if (pathinfo($realFilePath, PATHINFO_EXTENSION) !== 'php') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Only PHP files are allowed',
    ]);
    exit;
}

// Load and execute the requested PHP file
try {
    // Buffer output in case the included file outputs content
    ob_start();

    // Include the file
    include $realFilePath;

    // Get any output
    $output = ob_get_clean();

    // If there's output, send it
    if (! empty($output)) {
        echo $output;
    }

} catch (Exception $e) {
    // Clean any buffered output
    if (ob_get_level()) {
        ob_end_clean();
    }

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error loading file: ' . $e->getMessage(),
    ]);
}
