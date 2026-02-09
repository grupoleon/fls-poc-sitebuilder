<?php
/**
 * Fix Permissions Script
 *
 * Ensures proper permissions for uploads and config directories
 * Run this script if you encounter permission issues with file uploads
 */

$baseDir    = dirname(__DIR__);
$uploadsDir = $baseDir . '/uploads/images';
$configDir  = $baseDir . '/config';
$logsDir    = $baseDir . '/logs';

echo "=== Checking and Fixing Permissions ===\n\n";

// Directories that need to be writable
$directories = [
    'Uploads Directory' => $uploadsDir,
    'Config Directory'  => $configDir,
    'Logs Directory'    => $logsDir,
];

foreach ($directories as $name => $dir) {
    echo "Checking $name: $dir\n";

    // Create directory if it doesn't exist
    if (! is_dir($dir)) {
        echo "  → Directory does not exist. Creating...\n";
        if (mkdir($dir, 0755, true)) {
            echo "  ✓ Directory created successfully\n";
        } else {
            echo "  ✗ Failed to create directory\n";
            continue;
        }
    } else {
        echo "  ✓ Directory exists\n";
    }

    // Check current permissions
    $perms = substr(sprintf('%o', fileperms($dir)), -4);
    echo "  → Current permissions: $perms\n";

    // Check if writable
    if (is_writable($dir)) {
        echo "  ✓ Directory is writable\n";
    } else {
        echo "  ✗ Directory is NOT writable. Attempting to fix...\n";
        if (chmod($dir, 0755)) {
            echo "  ✓ Permissions updated to 0755\n";
        } else {
            echo "  ✗ Failed to update permissions\n";
            echo "  → Please run: chmod 755 $dir\n";
        }
    }

    // Check ownership
    $owner = posix_getpwuid(fileowner($dir));
    $group = posix_getgrgid(filegroup($dir));
    echo "  → Owner: {$owner['name']} | Group: {$group['name']}\n";

    // Get current user
    $currentUser = posix_getpwuid(posix_geteuid());
    echo "  → Current PHP user: {$currentUser['name']}\n";

    if ($owner['name'] !== $currentUser['name']) {
        echo "  ⚠ Warning: Directory owner differs from PHP user. This may cause permission issues.\n";
        echo "  → Consider running: sudo chown -R {$currentUser['name']}:{$group['name']} $dir\n";
    }

    echo "\n";
}

echo "=== Testing File Creation ===\n\n";

// Test if we can actually write a file
$testFile = $uploadsDir . '/test_' . time() . '.txt';
echo "Attempting to write test file: $testFile\n";

if (file_put_contents($testFile, 'Test content') !== false) {
    echo "✓ Successfully wrote test file\n";

    // Check file permissions
    $filePerms = substr(sprintf('%o', fileperms($testFile)), -4);
    echo "  → File permissions: $filePerms\n";

    // Clean up
    if (unlink($testFile)) {
        echo "✓ Successfully deleted test file\n";
    } else {
        echo "✗ Failed to delete test file (you may need to remove it manually)\n";
    }
} else {
    $error = error_get_last();
    echo "✗ Failed to write test file\n";
    echo "  → Error: " . ($error['message'] ?? 'Unknown error') . "\n";
    echo "\n";
    echo "=== RECOMMENDED FIX ===\n";
    echo "Run these commands to fix permissions:\n\n";
    echo "cd " . escapeshellarg($baseDir) . "\n";
    echo "chmod -R 755 uploads config logs\n";
    echo "chmod -R 644 uploads/images/* config/* 2>/dev/null || true\n";

    $currentUser = posix_getpwuid(posix_geteuid());
    echo "\nIf that doesn't work, you may need to fix ownership:\n";
    echo "sudo chown -R {$currentUser['name']}:www-data uploads config logs\n";
}

echo "\n=== Done ===\n";
