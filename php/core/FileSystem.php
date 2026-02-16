<?php

require_once __DIR__ . '/Logger.php';

/**
 * FileSystem
 * Unified file system operations with error handling
 */
class FileSystem
{
    /**
     * Read file contents
     *
     * @param string $path File path
     * @return string|false
     */
    public static function read(string $path)
    {
        if (! file_exists($path)) {
            Logger::warning("File not found: {$path}");
            return false;
        }

        if (! is_readable($path)) {
            Logger::error("File not readable: {$path}");
            return false;
        }

        return file_get_contents($path);
    }

    /**
     * Write contents to file
     *
     * @param string $path File path
     * @param string $contents Contents to write
     * @param bool $createDir Create directory if not exists
     * @return bool
     */
    public static function write(string $path, string $contents, bool $createDir = true): bool
    {
        if ($createDir) {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                if (! mkdir($dir, 0755, true)) {
                    Logger::error("Failed to create directory: {$dir}");
                    return false;
                }
            }
        }

        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            Logger::error("Failed to write file: {$path}");
            return false;
        }

        Logger::debug("File written: {$path}");
        return true;
    }

    /**
     * Append contents to file
     *
     * @param string $path File path
     * @param string $contents Contents to append
     * @return bool
     */
    public static function append(string $path, string $contents): bool
    {
        if (file_put_contents($path, $contents, FILE_APPEND | LOCK_EX) === false) {
            Logger::error("Failed to append to file: {$path}");
            return false;
        }

        return true;
    }

    /**
     * Delete a file
     *
     * @param string $path File path
     * @return bool
     */
    public static function delete(string $path): bool
    {
        if (! file_exists($path)) {
            return true;
        }

        if (! unlink($path)) {
            Logger::error("Failed to delete file: {$path}");
            return false;
        }

        Logger::debug("File deleted: {$path}");
        return true;
    }

    /**
     * Copy a file
     *
     * @param string $source Source path
     * @param string $destination Destination path
     * @param bool $createDir Create directory if not exists
     * @return bool
     */
    public static function copy(string $source, string $destination, bool $createDir = true): bool
    {
        if (! file_exists($source)) {
            Logger::error("Source file not found: {$source}");
            return false;
        }

        if ($createDir) {
            $dir = dirname($destination);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        if (! copy($source, $destination)) {
            Logger::error("Failed to copy file from {$source} to {$destination}");
            return false;
        }

        return true;
    }

    /**
     * Move/rename a file
     *
     * @param string $source Source path
     * @param string $destination Destination path
     * @return bool
     */
    public static function move(string $source, string $destination): bool
    {
        if (! file_exists($source)) {
            Logger::error("Source file not found: {$source}");
            return false;
        }

        if (! rename($source, $destination)) {
            Logger::error("Failed to move file from {$source} to {$destination}");
            return false;
        }

        return true;
    }

    /**
     * Read and decode JSON file
     *
     * @param string $path File path
     * @param bool $assoc Return associative array
     * @return mixed
     */
    public static function readJson(string $path, bool $assoc = true)
    {
        $contents = self::read($path);

        if ($contents === false) {
            return null;
        }

        $data = json_decode($contents, $assoc);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error("Invalid JSON in file: {$path}", ['error' => json_last_error_msg()]);
            return null;
        }

        return $data;
    }

    /**
     * Write data to JSON file
     *
     * @param string $path File path
     * @param mixed $data Data to write
     * @param bool $prettyPrint Use pretty print
     * @return bool
     */
    public static function writeJson(string $path, $data, bool $prettyPrint = true): bool
    {
        $options = $prettyPrint ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : JSON_UNESCAPED_SLASHES;
        $json    = json_encode($data, $options);

        if ($json === false) {
            Logger::error("Failed to encode JSON for file: {$path}", ['error' => json_last_error_msg()]);
            return false;
        }

        return self::write($path, $json);
    }

    /**
     * Create a directory
     *
     * @param string $path Directory path
     * @param int $permissions Directory permissions
     * @param bool $recursive Create recursively
     * @return bool
     */
    public static function createDir(string $path, int $permissions = 0755, bool $recursive = true): bool
    {
        if (is_dir($path)) {
            return true;
        }

        if (! mkdir($path, $permissions, $recursive)) {
            Logger::error("Failed to create directory: {$path}");
            return false;
        }

        return true;
    }

    /**
     * Delete a directory recursively
     *
     * @param string $path Directory path
     * @return bool
     */
    public static function deleteDir(string $path): bool
    {
        if (! is_dir($path)) {
            return true;
        }

        $files = array_diff(scandir($path), ['.', '..']);

        foreach ($files as $file) {
            $filePath = $path . '/' . $file;

            if (is_dir($filePath)) {
                self::deleteDir($filePath);
            } else {
                unlink($filePath);
            }
        }

        if (! rmdir($path)) {
            Logger::error("Failed to delete directory: {$path}");
            return false;
        }

        return true;
    }

    /**
     * Get directory size
     *
     * @param string $path Directory path
     * @return int Size in bytes
     */
    public static function getDirSize(string $path): int
    {
        $size = 0;

        if (! is_dir($path)) {
            return 0;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * List files in directory
     *
     * @param string $path Directory path
     * @param bool $recursive List recursively
     * @param string|null $extension Filter by extension
     * @return array
     */
    public static function listFiles(string $path, bool $recursive = false, ?string $extension = null): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $files = [];

        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );
        } else {
            $iterator = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);
        }

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                if ($extension === null || $file->getExtension() === $extension) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Ensure path is within base directory (security)
     *
     * @param string $path Path to validate
     * @param string $baseDir Base directory
     * @return bool
     */
    public static function isPathSafe(string $path, string $baseDir): bool
    {
        $realBase = realpath($baseDir);
        $realPath = realpath($path);

        if ($realPath === false) {
            return false;
        }

        return strpos($realPath, $realBase) === 0;
    }

    /**
     * Get file extension
     *
     * @param string $path File path
     * @return string
     */
    public static function getExtension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get filename without extension
     *
     * @param string $path File path
     * @return string
     */
    public static function getFilename(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Get file size in human-readable format
     *
     * @param string $path File path
     * @return string
     */
    public static function getHumanFileSize(string $path): string
    {
        if (! file_exists($path)) {
            return '0 B';
        }

        $bytes = filesize($path);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
