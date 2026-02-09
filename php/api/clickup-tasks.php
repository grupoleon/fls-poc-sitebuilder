<?php
/**
 * ClickUp Tasks API
 *
 * Actions:
 *   list  - List all tasks (from filesystem + DB sync, auto-fetches missing)
 *   get   - Get a specific task by ID (auto-recovers from API if missing)
 */

header('Content-Type: application/json');

$action   = $_GET['action'] ?? 'list';
$taskId   = $_GET['id'] ?? null;
$tasksDir = __DIR__ . '/../../webhook/tasks';

// Include shared ClickUp helper functions (guarded with CLICKUP_HELPERS_ONLY)
if (! defined('CLICKUP_HELPERS_ONLY')) {
    define('CLICKUP_HELPERS_ONLY', true);
}
require_once __DIR__ . '/fetch-clickup-task.php';

// Database helper
require_once __DIR__ . '/../admin/includes/DatabaseLogger.php';

/**
 * Get all tasks from the filesystem (keyed by task_id)
 */
function getTasksFromFilesystem($tasksDir)
{
    $tasks = [];

    if (! is_dir($tasksDir)) {
        return $tasks;
    }

    $files = glob($tasksDir . '/*.json');
    foreach ($files as $file) {
        if (strpos(basename($file), '-raw.json') !== false) {
            continue;
        }

        $taskData = json_decode(file_get_contents($file), true);
        if ($taskData && isset($taskData['task_id'])) {
            $tasks[$taskData['task_id']] = [
                'task_id'     => $taskData['task_id'],
                'task_name'   => $taskData['task_name'] ?? 'Untitled',
                'status'      => $taskData['status'] ?? 'unknown',
                'website_url' => $taskData['website_url'] ?? null,
                'theme'       => $taskData['theme'] ?? null,
            ];
        }
    }

    return $tasks;
}

/**
 * Save all filesystem tasks to database (ensures DB is populated)
 */
function syncFilesystemToDb($tasks, $db)
{
    if (! $db->isAvailable()) {
        return;
    }

    foreach ($tasks as $task) {
        $db->saveClickUpTaskId(
            $task['task_id'],
            $task['task_name'] ?? null,
            $task['status'] ?? null
        );
    }
}

/**
 * Fetch a missing task from ClickUp API and save to filesystem + DB
 */
function fetchAndSaveMissingTask($taskId, $apiToken, $tasksDir, $db)
{
    $fetchResult = fetchClickUpTask($taskId, $apiToken);

    if (! $fetchResult['success']) {
        error_log("clickup-tasks sync: Failed to fetch task {$taskId} from API");
        return null;
    }

    $rawData       = $fetchResult['data'];
    $processedData = processTaskData($rawData);

    try {
        saveTaskToFile($taskId, $processedData, $rawData);
    } catch (Exception $e) {
        error_log("clickup-tasks sync: Failed to save task {$taskId}: " . $e->getMessage());
    }

    if ($db->isAvailable()) {
        $db->saveClickUpTaskId(
            $taskId,
            $processedData['task_name'] ?? null,
            $processedData['status'] ?? null
        );
    }

    return [
        'task_id'     => $processedData['task_id'],
        'task_name'   => $processedData['task_name'] ?? 'Untitled',
        'status'      => $processedData['status'] ?? 'unknown',
        'website_url' => $processedData['website_url'] ?? null,
        'theme'       => $processedData['theme'] ?? null,
    ];
}

// ========== Route Actions ==========

$db = DatabaseLogger::getInstance();

if ($action === 'list') {
    // 1. Get tasks from filesystem
    $fsTasks = getTasksFromFilesystem($tasksDir);

    // 2. Save filesystem tasks to DB (populate DB from existing files)
    syncFilesystemToDb($fsTasks, $db);

    // 3. Find task IDs in DB that don't have JSON files
    $dbTasks    = $db->isAvailable() ? $db->getAllClickUpTaskIds() : [];
    $missingIds = [];

    foreach ($dbTasks as $dbTask) {
        if (! isset($fsTasks[$dbTask['task_id']])) {
            $missingIds[] = $dbTask['task_id'];
        }
    }

    // 4. Auto-fetch missing tasks from ClickUp API
    $recovered = 0;
    if (! empty($missingIds)) {
        $config = getClickUpConfig();
        if ($config['success']) {
            foreach ($missingIds as $missingId) {
                $task = fetchAndSaveMissingTask($missingId, $config['api_token'], $tasksDir, $db);
                if ($task) {
                    $fsTasks[$task['task_id']] = $task;
                    $recovered++;
                }
            }
        }
    }

    echo json_encode([
        'success'   => true,
        'tasks'     => array_values($fsTasks),
        'recovered' => $recovered,
    ]);

} elseif ($action === 'get' && $taskId) {
    $filename = $tasksDir . '/' . $taskId . '.json';

    // Try filesystem first
    if (! file_exists($filename)) {
        // Auto-recover: fetch from ClickUp API
        $config = getClickUpConfig();
        if ($config['success']) {
            $task = fetchAndSaveMissingTask($taskId, $config['api_token'], $tasksDir, $db);
            if ($task && file_exists($filename)) {
                $taskData = json_decode(file_get_contents($filename), true);
                echo json_encode([
                    'success'   => true,
                    'task'      => $taskData,
                    'recovered' => true,
                ]);
                exit;
            }
        }

        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Task not found',
        ]);
        exit;
    }

    $taskData = json_decode(file_get_contents($filename), true);

    // Ensure this task is in the DB
    if ($db->isAvailable() && $taskData) {
        $db->saveClickUpTaskId(
            $taskData['task_id'] ?? $taskId,
            $taskData['task_name'] ?? null,
            $taskData['status'] ?? null
        );
    }

    echo json_encode([
        'success' => true,
        'task'    => $taskData,
    ]);

} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action or missing task ID',
    ]);
}
