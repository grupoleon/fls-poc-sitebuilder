<?php

require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Response.php';

/**
 * Error Handler
 * Centralized error and exception handling
 */
class ErrorHandler
{
    private static $registered = false;

    /**
     * Register error and exception handlers
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$registered = true;
    }

    /**
     * Handle PHP errors
     *
     * @param int $errno Error number
     * @param string $errstr Error message
     * @param string $errfile Error file
     * @param int $errline Error line
     * @return bool
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (! (error_reporting() & $errno)) {
            return false;
        }

        $errorType = self::getErrorType($errno);

        Logger::error("PHP Error [{$errorType}]: {$errstr}", [
            'file' => $errfile,
            'line' => $errline,
        ]);

        if (self::isCriticalError($errno)) {
            if (self::isAjaxRequest()) {
                Response::serverError("A critical error occurred: {$errstr}");
            }
        }

        return true;
    }

    /**
     * Handle uncaught exceptions
     *
     * @param \Throwable $exception The exception
     */
    public static function handleException(\Throwable $exception): void
    {
        Logger::exception($exception);

        if (self::isAjaxRequest()) {
            Response::serverError('An unexpected error occurred', $exception);
        } else {
            $message = getenv('APP_DEBUG') === 'true'
                ? "Exception: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}"
                : 'An unexpected error occurred. Please try again later.';

            http_response_code(500);
            echo "<h1>Error</h1><p>{$message}</p>";
        }

        exit(1);
    }

    /**
     * Handle fatal errors on shutdown
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && self::isFatalError($error['type'])) {
            Logger::error("Fatal Error: {$error['message']}", [
                'file' => $error['file'],
                'line' => $error['line'],
            ]);

            if (self::isAjaxRequest()) {
                Response::serverError('A fatal error occurred');
            }
        }
    }

    /**
     * Get error type name
     *
     * @param int $errno Error number
     * @return string
     */
    private static function getErrorType(int $errno): string
    {
        $types = [
            E_ERROR             => 'ERROR',
            E_WARNING           => 'WARNING',
            E_PARSE             => 'PARSE',
            E_NOTICE            => 'NOTICE',
            E_CORE_ERROR        => 'CORE_ERROR',
            E_CORE_WARNING      => 'CORE_WARNING',
            E_COMPILE_ERROR     => 'COMPILE_ERROR',
            E_COMPILE_WARNING   => 'COMPILE_WARNING',
            E_USER_ERROR        => 'USER_ERROR',
            E_USER_WARNING      => 'USER_WARNING',
            E_USER_NOTICE       => 'USER_NOTICE',
            E_STRICT            => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED        => 'DEPRECATED',
            E_USER_DEPRECATED   => 'USER_DEPRECATED',
        ];

        return $types[$errno] ?? 'UNKNOWN';
    }

    /**
     * Check if error is critical
     *
     * @param int $errno Error number
     * @return bool
     */
    private static function isCriticalError(int $errno): bool
    {
        return in_array($errno, [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR,
        ], true);
    }

    /**
     * Check if error is fatal
     *
     * @param int $type Error type
     * @return bool
     */
    private static function isFatalError(int $type): bool
    {
        return in_array($type, [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
        ], true);
    }

    /**
     * Check if current request is AJAX
     *
     * @return bool
     */
    private static function isAjaxRequest(): bool
    {
        return ! empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Wrap a callable with error handling
     *
     * @param callable $callback The callback to execute
     * @param mixed ...$args Arguments to pass to the callback
     * @return mixed
     */
    public static function wrap(callable $callback, ...$args)
    {
        try {
            return $callback(...$args);
        } catch (\Throwable $e) {
            self::handleException($e);
        }
    }
}
