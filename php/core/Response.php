<?php

/**
 * Response Handler
 * Standardizes JSON responses across the application
 */
class Response
{
    /**
     * Send a success response
     *
     * @param mixed $data Response data
     * @param string $message Optional success message
     * @param int $statusCode HTTP status code
     * @return never
     */
    public static function success($data = null, string $message = '', int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        $response = ['success' => true];

        if ($message !== '') {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send an error response
     *
     * @param string $message Error message
     * @param mixed $errors Detailed errors
     * @param int $statusCode HTTP status code
     * @return never
     */
    public static function error(string $message, $errors = null, int $statusCode = 400): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error'   => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send a not found response
     *
     * @param string $message Optional message
     * @return never
     */
    public static function notFound(string $message = 'Resource not found'): never
    {
        self::error($message, null, 404);
    }

    /**
     * Send an unauthorized response
     *
     * @param string $message Optional message
     * @return never
     */
    public static function unauthorized(string $message = 'Unauthorized'): never
    {
        self::error($message, null, 401);
    }

    /**
     * Send a forbidden response
     *
     * @param string $message Optional message
     * @return never
     */
    public static function forbidden(string $message = 'Forbidden'): never
    {
        self::error($message, null, 403);
    }

    /**
     * Send a server error response
     *
     * @param string $message Error message
     * @param \Throwable|null $exception Optional exception
     * @return never
     */
    public static function serverError(string $message = 'Internal server error',  ? \Throwable $exception = null) : never
    {
        $errors = null;

        if ($exception && (getenv('APP_DEBUG') === 'true' || getenv('APP_ENV') === 'development')) {
            $errors = [
                'exception' => get_class($exception),
                'message'   => $exception->getMessage(),
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'trace'     => $exception->getTraceAsString(),
            ];
        }

        self::error($message, $errors, 500);
    }

    /**
     * Send a validation error response
     *
     * @param array $errors Validation errors
     * @param string $message Optional message
     * @return never
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): never
    {
        self::error($message, $errors, 422);
    }
}
