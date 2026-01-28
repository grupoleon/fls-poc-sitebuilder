<?php
/**
 * Fetch ClickUp Task by Manual Task ID
 * This API allows fetching a task directly from ClickUp API by task ID
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../bootstrap.php';

/**
 * Get ClickUp configuration from local-config.json
 */
function getClickUpConfig()
{
    $configFile = __DIR__ . '/../../config/local-config.json';

    if (! file_exists($configFile)) {
        return [
            'success' => false,
            'message' => 'Configuration file not found',
        ];
    }

    $config = json_decode(file_get_contents($configFile), true);

    if (! isset($config['integrations']['clickup']['api_token'])) {
        return [
            'success' => false,
            'message' => 'ClickUp API token not configured',
        ];
    }

    $apiToken = trim($config['integrations']['clickup']['api_token']);

    if (empty($apiToken)) {
        return [
            'success' => false,
            'message' => 'ClickUp API token is empty',
        ];
    }

    return [
        'success'   => true,
        'api_token' => $apiToken,
    ];
}

/**
 * Fetch task from ClickUp API
 */
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
        return [
            'success' => false,
            'message' => 'Failed to fetch task from ClickUp API',
            'error'   => $error,
        ];
    }

    if ($httpCode !== 200) {
        $responseData = json_decode($response, true);

        if ($httpCode === 401) {
            return [
                'success' => false,
                'message' => 'ClickUp API authentication failed. Please verify your API token.',
                'error'   => 'Invalid API token',
            ];
        }

        if ($httpCode === 404) {
            return [
                'success' => false,
                'message' => 'Task not found. Please verify the task ID.',
                'error'   => 'Task not found',
            ];
        }

        return [
            'success' => false,
            'message' => "ClickUp API returned HTTP {$httpCode}",
            'error' => $responseData['err'] ?? 'Unknown error',
        ];
    }

    $taskData = json_decode($response, true);

    if (! $taskData || ! isset($taskData['id'])) {
        return [
            'success' => false,
            'message' => 'Invalid task data received from ClickUp',
            'error'   => 'Invalid response format',
        ];
    }

    return [
        'success' => true,
        'data'    => $taskData,
    ];
}

/**
 * Process and extract relevant fields from ClickUp task data
 */
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

    // Extract custom fields (case-insensitive matching)
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

            // reCAPTCHA keys (explicit checks to avoid matching 'site' from 'SiteBuild' prefix)
            if (stripos($nameLower, 'recaptcha') !== false) {
                // Explicit patterns for the site key
                if (stripos($nameLower, 'site key') !== false || stripos($nameLower, 'site_key') !== false || stripos($nameLower, 'sitekey') !== false || stripos($nameLower, 'site-key') !== false) {
                    $processed['recaptcha_site_key'] = $fieldValue;
                } elseif (stripos($nameLower, 'secret') !== false || stripos($nameLower, 'secret key') !== false) {
                    $processed['recaptcha_secret'] = $fieldValue;
                } else {
                    // Fallback: if both 'site' and 'key' tokens appear separately, treat as site key
                    if (stripos($nameLower, 'site') !== false && stripos($nameLower, 'key') !== false) {
                        $processed['recaptcha_site_key'] = $fieldValue;
                    } else {
                        // Unknown variant: prefer 'recaptcha_secret'
                        $processed['recaptcha_secret'] = $fieldValue;
                    }
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
                            'name'      => $attachment['title'] ?? $attachment['name'] ?? 'Untitled',
                            'url'       => $attachment['url'] ?? null,
                            'extension' => $attachment['extension'] ?? null,
                            'mimetype'  => $attachment['mimetype'] ?? null,
                            'size'      => $attachment['size'] ?? null,
                        ];
                    }
                }
                continue;
            }
        }
    }

    // Extract top-level attachments
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

/**
 * Save task data to local file system
 */
function saveTaskToFile($taskId, $processedData, $rawData)
{
    $tasksDir = __DIR__ . '/../../webhook/tasks';

    if (! is_dir($tasksDir)) {
        mkdir($tasksDir, 0755, true);
    }

    // Save processed task data
    $processedFile = $tasksDir . '/' . $taskId . '.json';
    file_put_contents($processedFile, json_encode($processedData, JSON_PRETTY_PRINT));

    // Save raw task data for reference
    $rawFile = $tasksDir . '/' . $taskId . '-raw.json';
    file_put_contents($rawFile, json_encode($rawData, JSON_PRETTY_PRINT));

    return true;
}

// ========== Main Execution ==========

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are supported',
    ]);
    exit;
}

// Get task ID from query parameter
$taskId = $_GET['task_id'] ?? null;

if (empty($taskId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Task ID is required',
    ]);
    exit;
}

// Validate task ID format (alphanumeric, can include hyphens)
if (! preg_match('/^[a-z0-9\-]+$/i', $taskId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid task ID format',
    ]);
    exit;
}

// Get ClickUp config
$config = getClickUpConfig();
if (! $config['success']) {
    http_response_code(500);
    echo json_encode($config);
    exit;
}

$apiToken = $config['api_token'];

// Fetch task from ClickUp API
$fetchResult = fetchClickUpTask($taskId, $apiToken);

if (! $fetchResult['success']) {
    http_response_code(502);
    echo json_encode($fetchResult);
    exit;
}

$rawTaskData = $fetchResult['data'];

// Process task data
$processedData = processTaskData($rawTaskData);

// Save task data to file system
try {
    saveTaskToFile($taskId, $processedData, $rawTaskData);
} catch (Exception $e) {
    // Continue even if saving fails
    error_log("Failed to save task {$taskId}: " . $e->getMessage());
}

// Return processed task data
echo json_encode([
    'success' => true,
    'message' => 'Task fetched successfully from ClickUp',
    'task'    => $processedData,
]);
