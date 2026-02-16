<?php

require_once __DIR__ . '/../core/FileSystem.php';
require_once __DIR__ . '/../core/Logger.php';

/**
 * UploadHelper
 * Handle file uploads and processing
 */
class UploadHelper
{
    /**
     * Normalize uploaded files array
     *
     * @param array $fileInput $_FILES array input
     * @return array Normalized array
     */
    public static function normalizeFiles(array $fileInput): array
    {
        $normalized = [];

        if (! isset($fileInput['tmp_name'])) {
            return $normalized;
        }

        if (is_array($fileInput['tmp_name'])) {
            foreach ($fileInput['tmp_name'] as $index => $tmpName) {
                if (! empty($tmpName)) {
                    $normalized[] = [
                        'name'     => $fileInput['name'][$index],
                        'type'     => $fileInput['type'][$index],
                        'tmp_name' => $tmpName,
                        'error'    => $fileInput['error'][$index],
                        'size'     => $fileInput['size'][$index],
                    ];
                }
            }
        } else {
            if (! empty($fileInput['tmp_name'])) {
                $normalized[] = $fileInput;
            }
        }

        return $normalized;
    }

    /**
     * Validate uploaded file
     *
     * @param array $file File array
     * @param array $options Validation options
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public static function validate(array $file, array $options = []): array
    {
        $maxSize           = $options['max_size'] ?? 10 * 1024 * 1024; // 10MB default
        $allowedTypes      = $options['allowed_types'] ?? [];
        $allowedExtensions = $options['allowed_extensions'] ?? [];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => self::getUploadErrorMessage($file['error']),
            ];
        }

        if ($file['size'] > $maxSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed size of ' . FileSystem::getHumanFileSize((string) $maxSize),
            ];
        }

        if (! empty($allowedTypes) && ! in_array($file['type'], $allowedTypes, true)) {
            return [
                'valid' => false,
                'error' => 'File type not allowed: ' . $file['type'],
            ];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (! empty($allowedExtensions) && ! in_array($extension, $allowedExtensions, true)) {
            return [
                'valid' => false,
                'error' => 'File extension not allowed: ' . $extension,
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Get upload error message
     *
     * @param int $errorCode Error code
     * @return string Error message
     */
    private static function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension',
        ];

        return $errors[$errorCode] ?? 'Unknown upload error';
    }

    /**
     * Move uploaded file to destination
     *
     * @param array $file File array
     * @param string $destination Destination path
     * @return bool Success status
     */
    public static function move(array $file, string $destination): bool
    {
        $dir = dirname($destination);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (! move_uploaded_file($file['tmp_name'], $destination)) {
            Logger::error("Failed to move uploaded file to {$destination}");
            return false;
        }

        Logger::info("File uploaded successfully: {$destination}");
        return true;
    }

    /**
     * Process image upload with optional resizing
     *
     * @param array $file File array
     * @param string $destination Destination path
     * @param array $options Processing options (max_width, max_height, quality)
     * @return bool Success status
     */
    public static function processImage(array $file, string $destination, array $options = []): bool
    {
        if (! self::move($file, $destination)) {
            return false;
        }

        $maxWidth  = $options['max_width'] ?? null;
        $maxHeight = $options['max_height'] ?? null;
        $quality   = $options['quality'] ?? 85;

        if ($maxWidth || $maxHeight) {
            return self::resizeImage($destination, $maxWidth, $maxHeight, $quality);
        }

        return true;
    }

    /**
     * Resize image
     *
     * @param string $imagePath Image path
     * @param int|null $maxWidth Maximum width
     * @param int|null $maxHeight Maximum height
     * @param int $quality JPEG quality
     * @return bool Success status
     */
    private static function resizeImage(string $imagePath, ?int $maxWidth, ?int $maxHeight, int $quality): bool
    {
        if (! file_exists($imagePath)) {
            return false;
        }

        $imageInfo = getimagesize($imagePath);
        if ($imageInfo === false) {
            return false;
        }

        [$width, $height, $type] = $imageInfo;

        if (! $maxWidth && ! $maxHeight) {
            return true;
        }

        if ($maxWidth && $width <= $maxWidth && $maxHeight && $height <= $maxHeight) {
            return true;
        }

        $ratio = $width / $height;

        if ($maxWidth && $maxHeight) {
            if ($width > $height) {
                $newWidth  = $maxWidth;
                $newHeight = (int) ($maxWidth / $ratio);
            } else {
                $newHeight = $maxHeight;
                $newWidth  = (int) ($maxHeight * $ratio);
            }
        } elseif ($maxWidth) {
            $newWidth  = $maxWidth;
            $newHeight = (int) ($maxWidth / $ratio);
        } else {
            $newHeight = $maxHeight;
            $newWidth  = (int) ($maxHeight * $ratio);
        }

        $source = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($imagePath);
                break;
            default:
                return false;
        }

        if (! $source) {
            return false;
        }

        $dest = imagecreatetruecolor($newWidth, $newHeight);

        if ($type === IMAGETYPE_PNG) {
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
        }

        imagecopyresampled($dest, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($dest, $imagePath, $quality);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($dest, $imagePath, (int) (9 - ($quality / 100 * 9)));
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($dest, $imagePath);
                break;
        }

        imagedestroy($source);
        imagedestroy($dest);

        return $result;
    }

    /**
     * Extract and import configs from ZIP file
     *
     * @param string $zipPath Path to ZIP file
     * @param string $zipName Original filename
     * @param string $configDir Config directory path
     * @return array ['success' => bool, 'message' => string, 'imported' => array]
     */
    public static function importConfigsFromZip(string $zipPath, string $zipName, string $configDir): array
    {
        if (! class_exists('ZipArchive')) {
            return [
                'success'  => false,
                'message'  => 'ZipArchive extension not available',
                'imported' => [],
            ];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return [
                'success'  => false,
                'message'  => 'Failed to open ZIP archive',
                'imported' => [],
            ];
        }

        $imported = [];
        $tempDir  = sys_get_temp_dir() . '/config_import_' . uniqid();
        mkdir($tempDir, 0755, true);

        try {
            $zip->extractTo($tempDir);
            $zip->close();

            $configFiles = FileSystem::listFiles($tempDir, true, 'json');

            foreach ($configFiles as $file) {
                $filename = basename($file);
                $content  = file_get_contents($file);

                $result = self::importConfigContent($filename, $content, $configDir, $zipName);

                if ($result['success']) {
                    $imported[] = $result['file'];
                }
            }

            FileSystem::deleteDir($tempDir);

            $message = count($imported) > 0
                ? 'Successfully imported ' . count($imported) . ' configuration file(s)'
                : 'No valid configuration files found in ZIP';

            return [
                'success'  => count($imported) > 0,
                'message'  => $message,
                'imported' => $imported,
            ];
        } catch (\Exception $e) {
            Logger::exception($e);
            FileSystem::deleteDir($tempDir);

            return [
                'success'  => false,
                'message'  => 'Import failed: ' . $e->getMessage(),
                'imported' => [],
            ];
        }
    }

    /**
     * Import single config file content
     *
     * @param string $filename Filename
     * @param string $content File content
     * @param string $configDir Config directory
     * @param string|null $source Source identifier
     * @return array ['success' => bool, 'file' => string, 'message' => string]
     */
    public static function importConfigContent(string $filename, string $content, string $configDir, ?string $source = null): array
    {
        require_once __DIR__ . '/ConfigHelper.php';

        $configType = ConfigHelper::getTypeFromFilename($filename);

        if ($configType === 'unknown') {
            return [
                'success' => false,
                'file'    => $filename,
                'message' => 'Unknown config type',
            ];
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'file'    => $filename,
                'message' => 'Invalid JSON: ' . json_last_error_msg(),
            ];
        }

        if (! ConfigHelper::validateImported($configType, $data)) {
            return [
                'success' => false,
                'file'    => $filename,
                'message' => 'Invalid config structure',
            ];
        }

        $targetFile = $configDir . '/' . $filename;

        if (FileSystem::writeJson($targetFile, $data)) {
            Logger::info("Config imported: {$filename}" . ($source ? " from {$source}" : ''));

            return [
                'success' => true,
                'file'    => $filename,
                'message' => 'Imported successfully',
            ];
        }

        return [
            'success' => false,
            'file'    => $filename,
            'message' => 'Failed to write file',
        ];
    }
}
