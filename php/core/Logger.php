<?php

/**
 * Logger
 * Centralized logging for the application
 */
class Logger
{
    private static $logsDir;
    private static $initialized = false;

    /**
     * Initialize the logger
     *
     * @param string|null $logsDir Optional logs directory path
     */
    private static function init(?string $logsDir = null): void
    {
        if (self::$initialized) {
            return;
        }

        self::$logsDir = $logsDir ?? dirname(dirname(__DIR__)) . '/logs';

        if (! is_dir(self::$logsDir)) {
            mkdir(self::$logsDir, 0755, true);
        }

        self::$initialized = true;
    }

    /**
     * Log an info message
     *
     * @param string $message Message to log
     * @param array $context Additional context
     * @param string $category Log category/file
     */
    public static function info(string $message, array $context = [], string $category = 'app'): void
    {
        self::log('INFO', $message, $context, $category);
    }

    /**
     * Log a warning message
     *
     * @param string $message Message to log
     * @param array $context Additional context
     * @param string $category Log category/file
     */
    public static function warning(string $message, array $context = [], string $category = 'app'): void
    {
        self::log('WARNING', $message, $context, $category);
    }

    /**
     * Log an error message
     *
     * @param string $message Message to log
     * @param array $context Additional context
     * @param string $category Log category/file
     */
    public static function error(string $message, array $context = [], string $category = 'app'): void
    {
        self::log('ERROR', $message, $context, $category);
    }

    /**
     * Log a debug message
     *
     * @param string $message Message to log
     * @param array $context Additional context
     * @param string $category Log category/file
     */
    public static function debug(string $message, array $context = [], string $category = 'app'): void
    {
        if (getenv('APP_DEBUG') !== 'true' && getenv('APP_ENV') !== 'development') {
            return;
        }

        self::log('DEBUG', $message, $context, $category);
    }

    /**
     * Log a message to file
     *
     * @param string $level Log level
     * @param string $message Message to log
     * @param array $context Additional context
     * @param string $category Log category/file
     */
    private static function log(string $level, string $message, array $context, string $category): void
    {
        self::init();

        $timestamp  = gmdate('Y-m-d H:i:s');
        $contextStr = ! empty($context) ? ' ' . json_encode($context) : '';
        $logLine    = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";

        $categoryDir = self::$logsDir . '/' . $category;
        if (! is_dir($categoryDir)) {
            mkdir($categoryDir, 0755, true);
        }

        $logFile = $categoryDir . '/' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

        error_log("[{$category}] [{$level}] {$message}");
    }

    /**
     * Log an exception
     *
     * @param \Throwable $exception The exception to log
     * @param string $category Log category
     */
    public static function exception(\Throwable $exception, string $category = 'app'): void
    {
        $context = [
            'exception' => get_class($exception),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'trace'     => $exception->getTraceAsString(),
        ];

        self::error($exception->getMessage(), $context, $category);
    }

    /**
     * Log deployment related messages
     *
     * @param string $message Message to log
     * @param string $level Log level
     * @param array $context Additional context
     */
    public static function deployment(string $message, string $level = 'INFO', array $context = []): void
    {
        $method = strtolower($level);
        if (method_exists(self::class, $method)) {
            self::$method($message, $context, 'deployment');
        } else {
            self::info($message, $context, 'deployment');
        }
    }

    /**
     * Log API related messages
     *
     * @param string $message Message to log
     * @param string $level Log level
     * @param array $context Additional context
     */
    public static function api(string $message, string $level = 'INFO', array $context = []): void
    {
        $method = strtolower($level);
        if (method_exists(self::class, $method)) {
            self::$method($message, $context, 'api');
        } else {
            self::info($message, $context, 'api');
        }
    }

    /**
     * Get log files for a category
     *
     * @param string $category Log category
     * @return array List of log files
     */
    public static function getLogFiles(string $category = ''): array
    {
        self::init();

        $logFiles = [];
        $scanDir  = $category ? self::$logsDir . '/' . $category : self::$logsDir;

        if (! is_dir($scanDir)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($scanDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'log') {
                $logFiles[] = [
                    'path'     => $file->getPathname(),
                    'name'     => $file->getFilename(),
                    'size'     => $file->getSize(),
                    'modified' => $file->getMTime(),
                ];
            }
        }

        usort($logFiles, fn($a, $b) => $b['modified'] <=> $a['modified']);

        return $logFiles;
    }

    /**
     * Clear old log files
     *
     * @param int $days Number of days to keep
     */
    public static function clearOldLogs(int $days = 30): int
    {
        self::init();

        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $deleted    = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::$logsDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() < $cutoffTime) {
                unlink($file->getPathname());
                $deleted++;
            }
        }

        return $deleted;
    }
}
