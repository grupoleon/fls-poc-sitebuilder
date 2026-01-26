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

    $apiToken = trim($config['integrations']['clickup']['api_token']);

    if (empty($apiToken)) {
        sendResponse(false, 'ClickUp API token is empty', null, 500);
    }

    return [
        'api_token' => $apiToken,
        'team_id'   => isset($config['integrations']['clickup']['team_id'])
            ? trim($config['integrations']['clickup']['team_id'])
            : null,
    ];
}

function fetchClickUpTask($taskId, $apiToken)
{
    $url = "https://api.clickup.com/api/v2/task/{$taskId}";

    // Log the token length for debugging (never log the full token)
    logWebhook("Making ClickUp API request", [
        'url'          => $url,
        'token_length' => strlen($apiToken),
        'token_prefix' => substr($apiToken, 0, 8) . '...',
    ]);

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

    if ($error) {
        logWebhook("cURL error fetching task {$taskId}", ['error' => $error]);
        sendResponse(false, 'Failed to fetch task from ClickUp API', ['error' => $error], 500);
    }

    if ($httpCode !== 200) {
        $responseData = json_decode($response, true);
        logWebhook("ClickUp API error for task {$taskId}", [
            'http_code' => $httpCode,
            'response'  => $responseData,
        ]);

        // Provide more helpful error message for authentication issues
        if ($httpCode === 401) {
            sendResponse(false, "ClickUp API authentication failed. Please verify your API token is valid.", [
                'http_code' => $httpCode,
                'response'  => $responseData,
                'hint'      => 'Get your token from ClickUp Settings > Apps > API Token',
            ], 502);
        }

        sendResponse(false, "ClickUp API returned HTTP {$httpCode}", [
            'http_code' => $httpCode,
            'response'  => $responseData,
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

function processTaskData($taskData)
{
    $processed = [
        'task_id'           => $taskData['id'] ?? null,
        'task_name'         => $taskData['name'] ?? null,
        'task_url'          => $taskData['url'] ?? null,
        'status'            => $taskData['status']['status'] ?? null,
        'website_url'       => null,
        'theme'             => null,
        'selected_services' => [],
        'credentials'       => [],
        'required_inputs'   => [],
    ];

    // Extract custom fields
    if (isset($taskData['custom_fields']) && is_array($taskData['custom_fields'])) {
        foreach ($taskData['custom_fields'] as $field) {
            $fieldName  = $field['name'] ?? '';
            $fieldValue = $field['value'] ?? null;

            switch ($fieldName) {
                case 'Website URL':
                    $processed['website_url'] = $fieldValue;
                    break;

                case 'Template Selection':
                    // Map dropdown value to theme name
                    if (isset($field['type_config']['options']) && is_numeric($fieldValue)) {
                        foreach ($field['type_config']['options'] as $option) {
                            if ($option['orderindex'] === $fieldValue) {
                                $processed['theme'] = $option['name'] ?? null;
                                break;
                            }
                        }
                    }
                    break;

                case 'Services Needed':
                    // Extract service labels from IDs
                    if (is_array($fieldValue) && isset($field['type_config']['options'])) {
                        $serviceMap = [];
                        foreach ($field['type_config']['options'] as $option) {
                            $serviceMap[$option['id']] = $option['label'];
                        }
                        foreach ($fieldValue as $serviceId) {
                            if (isset($serviceMap[$serviceId])) {
                                $processed['selected_services'][] = $serviceMap[$serviceId];
                            }
                        }
                    }
                    break;

                case 'Email':
                case 'Google Drive':
                case 'Privacy or Policy Information':
                    $processed['credentials'][$fieldName] = $fieldValue;
                    break;

                default:
                    // Store other custom fields as required inputs
                    if ($fieldValue !== null && $fieldValue !== '') {
                        $processed['required_inputs'][$fieldName] = $fieldValue;
                    }
                    break;
            }
        }
    }

    return $processed;
}

function saveTaskToFile($taskData)
{
    $tasksDir = __DIR__ . '/tasks';

    if (! is_dir($tasksDir)) {
        if (! mkdir($tasksDir, 0755, true)) {
            sendResponse(false, 'Failed to create tasks directory', null, 500);
        }
    }

    $taskId = $taskData['id'] ?? null;
    if (! $taskId) {
        sendResponse(false, 'Task ID not found in task data', null, 500);
    }

    // Save raw JSON with task-id
    $rawFilename = "{$tasksDir}/{$taskId}-raw.json";
    $rawSuccess  = file_put_contents($rawFilename, json_encode($taskData, JSON_PRETTY_PRINT));

    if (! $rawSuccess) {
        sendResponse(false, 'Failed to save raw task data to file', null, 500);
    }

    // Process and save simplified JSON
    $processedData     = processTaskData($taskData);
    $processedFilename = "{$tasksDir}/{$taskId}.json";
    $processedSuccess  = file_put_contents($processedFilename, json_encode($processedData, JSON_PRETTY_PRINT));

    if (! $processedSuccess) {
        sendResponse(false, 'Failed to save processed task data to file', null, 500);
    }

    return [
        'task_id'        => $taskId,
        'raw_file'       => basename($rawFilename),
        'processed_file' => basename($processedFilename),
        'raw_path'       => $rawFilename,
        'processed_path' => $processedFilename,
        'raw_size'       => filesize($rawFilename),
        'processed_size' => filesize($processedFilename),
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
        'task'  => [
            'id'     => $taskData['id'],
            'name'   => $taskData['name'] ?? 'N/A',
            'status' => $taskData['status']['status'] ?? 'N/A',
            'url'    => $taskData['url'] ?? null,
        ],
        'files' => $fileInfo,
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
