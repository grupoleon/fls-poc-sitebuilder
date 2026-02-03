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
        'task_id'                   => $taskData['id'] ?? null,
        'task_name'                 => $taskData['name'] ?? null,
        'task_url'                  => $taskData['url'] ?? null,
        'status'                    => $taskData['status']['status'] ?? null,
        'website_url'               => null,
        'theme'                     => null,
        'email'                     => null,
        'facebook_link'             => null,
        'instagram_link'            => null,
        'twitter_link'              => null,
        'youtube_link'              => null,
        'winred_link'               => null,
        'google_analytics_token'    => null,
        'google_map_key'            => null,
        'privacy_policy_info'       => null,
        'recaptcha_secret'          => null,
        'recaptcha_site_key'        => null,
        'google_drive'              => null,
        'selected_services'         => [],
        'security_options'          => [],
        'website_brief_attachments' => [],
    ];

    // Extract custom fields (robust, case-insensitive matching)
    if (isset($taskData['custom_fields']) && is_array($taskData['custom_fields'])) {
        foreach ($taskData['custom_fields'] as $field) {
            $fieldName  = $field['name'] ?? '';
            $fieldValue = $field['value'] ?? null;
            $nameLower  = strtolower($fieldName);

            // Website URL
            if (stripos($nameLower, 'website url') !== false || stripos($nameLower, 'websiteurl') !== false) {
                $processed['website_url'] = $fieldValue;
                continue;
            }

            // Template Selection / Theme
            if (stripos($nameLower, 'template') !== false) {
                if (isset($field['type_config']['options']) && is_array($field['type_config']['options'])) {
                    foreach ($field['type_config']['options'] as $option) {
                        if ((isset($option['orderindex']) && (int) $option['orderindex'] === (int) $fieldValue)
                            || (isset($option['id']) && (string) $option['id'] === (string) $fieldValue)
                            || (isset($option['name']) && strtolower($option['name']) === strtolower((string) $fieldValue))
                        ) {
                            $processed['theme'] = $option['name'] ?? $option['label'] ?? null;
                            break;
                        }
                    }
                }
                continue;
            }

            // Services Needed
            if (stripos($nameLower, 'services needed') !== false) {
                if (is_array($fieldValue) && isset($field['type_config']['options'])) {
                    $serviceMap = [];
                    foreach ($field['type_config']['options'] as $option) {
                        $id    = $option['id'] ?? null;
                        $label = $option['label'] ?? $option['name'] ?? null;
                        if ($id && $label) {
                            $serviceMap[$id] = $label;
                        } elseif (isset($option['orderindex'])) {
                            $serviceMap[(string) $option['orderindex']] = $label;
                        }
                    }
                    foreach ($fieldValue as $serviceId) {
                        if (isset($serviceMap[$serviceId])) {
                            $processed['selected_services'][] = $serviceMap[$serviceId];
                        }
                    }
                }
                continue;
            }

            // Security Options (labels)
            if (stripos($nameLower, 'security') !== false) {
                if (is_array($fieldValue) && isset($field['type_config']['options'])) {
                    $optionMap = [];
                    foreach ($field['type_config']['options'] as $option) {
                        $id    = $option['id'] ?? null;
                        $label = $option['label'] ?? $option['name'] ?? null;
                        if ($id && $label) {
                            $optionMap[$id] = $label;
                        }
                    }
                    foreach ($fieldValue as $id) {
                        if (isset($optionMap[$id])) {
                            $processed['security_options'][] = $optionMap[$id];
                        }
                    }
                }
                continue;
            }

            // Email (prefer SiteBuild-DestinationEmail)
            if (stripos($nameLower, 'destination') !== false || stripos($nameLower, 'destinationemail') !== false) {
                $processed['email'] = $fieldValue;
                continue;
            }
            if (stripos($nameLower, 'email') !== false && empty($processed['email'])) {
                $processed['email'] = $fieldValue;
                continue;
            }

            // Social links
            if (stripos($nameLower, 'fb') !== false || stripos($nameLower, 'facebook') !== false) {
                $processed['facebook_link'] = $fieldValue;
                continue;
            }
            if (stripos($nameLower, 'insta') !== false || stripos($nameLower, 'instagram') !== false) {
                $processed['instagram_link'] = $fieldValue;
                continue;
            }
            if (stripos($nameLower, 'xlink') !== false || stripos($nameLower, 'twitter') !== false) {
                $processed['twitter_link'] = $fieldValue;
                continue;
            }
            if (stripos($nameLower, 'youtube') !== false) {
                $processed['youtube_link'] = $fieldValue;
                continue;
            }
            if (stripos($nameLower, 'winred') !== false) {
                $processed['winred_link'] = $fieldValue;
                continue;
            }

            // Google Analytics Token
            if (stripos($nameLower, 'google analytics') !== false) {
                $processed['google_analytics_token'] = $fieldValue;
                continue;
            }

            // Google Map key
            if (stripos($nameLower, 'google map') !== false) {
                $processed['google_map_key'] = $fieldValue;
                continue;
            }

            // Privacy Policy Info
            if (stripos($nameLower, 'privacy') !== false || stripos($nameLower, 'policy') !== false) {
                $processed['privacy_policy_info'] = $fieldValue;
                continue;
            }

            // reCAPTCHA keys
            if (stripos($nameLower, 'recaptcha') !== false) {
                if (stripos($nameLower, 'site') !== false || stripos($nameLower, 'site key') !== false) {
                    $processed['recaptcha_site_key'] = $fieldValue;
                } else {
                    $processed['recaptcha_secret'] = $fieldValue;
                }
                continue;
            }

            // Google Drive
            if (stripos($nameLower, 'google drive') !== false) {
                $processed['google_drive'] = $fieldValue;
                continue;
            }

            // Website Brief attachments
            if (stripos($nameLower, 'website brief') !== false || stripos($nameLower, 'websitebrief') !== false) {
                if (is_array($fieldValue)) {
                    foreach ($fieldValue as $attachment) {
                        $processed['website_brief_attachments'][] = [
                            'url'       => $attachment['url'] ?? null,
                            'title'     => $attachment['title'] ?? $attachment['name'] ?? null,
                            'extension' => $attachment['extension'] ?? null,
                            'mimetype'  => $attachment['mimetype'] ?? null,
                            'is_folder' => $attachment['is_folder'] ?? null,
                            'size'      => $attachment['size'] ?? null,
                        ];
                    }
                }
                continue;
            }
        }
    }

    // Also include top-level attachments if present
    if (isset($taskData['attachments']) && is_array($taskData['attachments'])) {
        foreach ($taskData['attachments'] as $attachment) {
            $processed['website_brief_attachments'][] = [
                'name'      => $attachment['title'] ?? $attachment['name'] ?? 'Untitled',
                'url'       => $attachment['url'] ?? null,
                'extension' => $attachment['extension'] ?? null,
                'mimetype'  => $attachment['mimetype'] ?? null,
                'size'      => $attachment['size'] ?? null,
            ];
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Only POST requests are supported', null, 405);
    }

    $payload = json_decode(file_get_contents("php://input"), true);

    if (! $payload) {
        sendResponse(false, 'Invalid JSON payload', null, 400);
    }

    $taskId = $payload['task_id'] ?? null;

    if (! preg_match('/^[a-zA-Z0-9]+$/', $taskId)) {
        sendResponse(false, 'Invalid Task ID format', null, 400);
    }

    logWebhook("Task webhook triggered", ['task_id' => $taskId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

    $clickupConfig = getClickUpConfig();

    logWebhook("Fetching task from ClickUp API", ['task_id' => $taskId]);
    $taskData = fetchClickUpTask($taskId, $clickupConfig['api_token']);

    logWebhook("Saving task data to file", ['task_id' => $taskId, 'task_name' => $taskData['name'] ?? 'N/A']);
    $fileInfo = saveTaskToFile($taskData);

    logWebhook("Task successfully processed", [
        'task_id' => $taskId,
        'file'    => $fileInfo['processed_file'],
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
