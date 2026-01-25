<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/webhook_errors.log');

header('Content-Type: application/json');

function sendResponse($success, $message, $data = null, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode([
        'success'   => $success,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('c'),
    ], JSON_PRETTY_PRINT);
    exit;
}

function logWebhook($message, $data = null)
{
    $logFile = __DIR__ . '/../logs/webhook.log';
    $logDir  = dirname($logFile);

    if (! is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logEntry = sprintf(
        "[%s] %s\n%s\n\n",
        date('Y-m-d H:i:s'),
        $message,
        $data ? json_encode($data, JSON_PRETTY_PRINT) : ''
    );

    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

function getClickUpConfig()
{
    $configFile = __DIR__ . '/../config/local-config.json';

    if (! file_exists($configFile)) {
        sendResponse(false, 'Configuration file not found', null, 500);
    }

    $config = json_decode(file_get_contents($configFile), true);

    if (! isset($config['integrations']['clickup']['api_token'])) {
        sendResponse(false, 'ClickUp API token not configured', null, 500);
    }

    return [
        'api_token' => $config['integrations']['clickup']['api_token'],
        'team_id'   => $config['integrations']['clickup']['team_id'] ?? null,
    ];
}

function fetchClickUpTask($taskId, $apiToken)
{
    $url = "https://api.clickup.com/api/v2/task/{$taskId}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            "Authorization: {$apiToken}",
            "Content-Type: application/json",
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        logWebhook("cURL error fetching task {$taskId}", ['error' => $error]);
        sendResponse(false, 'Failed to fetch task from ClickUp API', ['error' => $error], 500);
    }

    if ($httpCode !== 200) {
        logWebhook("ClickUp API error for task {$taskId}", [
            'http_code' => $httpCode,
            'response'  => $response,
        ]);
        sendResponse(false, "ClickUp API returned HTTP {$httpCode}", [
            'http_code' => $httpCode,
            'response'  => json_decode($response, true),
        ], 502);
    }

    $taskData = json_decode($response, true);

    if (! $taskData || ! isset($taskData['id'])) {
        logWebhook("Invalid task data received for {$taskId}", ['response' => $response]);
        sendResponse(false, 'Invalid task data received from ClickUp', null, 500);
    }

    return $taskData;
}

function sanitizeFilename($name)
{
    $name = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $name);
    $name = preg_replace('/\s+/', '-', trim($name));
    $name = strtolower($name);
    return $name ?: 'untitled-task';
}

function saveTaskToFile($taskData)
{
    $tasksDir = __DIR__ . '/tasks';

    if (! is_dir($tasksDir)) {
        if (! mkdir($tasksDir, 0755, true)) {
            sendResponse(false, 'Failed to create tasks directory', null, 500);
        }
    }

    $taskName = sanitizeFilename($taskData['name'] ?? 'task-' . $taskData['id']);
    $filename = "{$tasksDir}/{$taskName}.json";

    // If file exists, append timestamp
    if (file_exists($filename)) {
        $taskName = $taskName . '-' . time();
        $filename = "{$tasksDir}/{$taskName}.json";
    }

    $success = file_put_contents($filename, json_encode($taskData, JSON_PRETTY_PRINT));

    if (! $success) {
        sendResponse(false, 'Failed to save task data to file', null, 500);
    }

    return [
        'filename' => basename($filename),
        'path'     => $filename,
        'size'     => filesize($filename),
    ];
}

// Main execution
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse(false, 'Only GET requests are supported', null, 405);
    }

    if (! isset($_GET['id']) || empty($_GET['id'])) {
        sendResponse(false, 'Task ID parameter is required', [
            'usage' => '/task-created?id={task_id}',
        ], 400);
    }

    $taskId = trim($_GET['id']);

    logWebhook("Task webhook triggered", ['task_id' => $taskId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

    $clickupConfig = getClickUpConfig();

    logWebhook("Fetching task from ClickUp API", ['task_id' => $taskId]);
    $taskData = fetchClickUpTask($taskId, $clickupConfig['api_token']);

    logWebhook("Saving task data to file", ['task_id' => $taskId, 'task_name' => $taskData['name'] ?? 'N/A']);
    $fileInfo = saveTaskToFile($taskData);

    logWebhook("Task successfully processed", [
        'task_id' => $taskId,
        'file'    => $fileInfo['filename'],
    ]);

    sendResponse(true, 'Task data fetched and saved successfully', [
        'task' => [
            'id'     => $taskData['id'],
            'name'   => $taskData['name'] ?? 'N/A',
            'status' => $taskData['status']['status'] ?? 'N/A',
            'url'    => $taskData['url'] ?? null,
        ],
        'file' => $fileInfo,
    ], 200);

} catch (Exception $e) {
    logWebhook("Unexpected error", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    sendResponse(false, 'An unexpected error occurred', [
        'error' => $e->getMessage(),
    ], 500);
}
