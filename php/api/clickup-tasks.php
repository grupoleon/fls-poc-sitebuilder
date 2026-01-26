<?php
header('Content-Type: application/json');

// Get action from query parameter
$action = $_GET['action'] ?? 'list';
$taskId = $_GET['id'] ?? null;

$tasksDir = __DIR__ . '/../../webhook/tasks';

if ($action === 'list') {
    // List all processed task files
    if (! is_dir($tasksDir)) {
        echo json_encode([
            'success' => true,
            'tasks'   => [],
        ]);
        exit;
    }

    $tasks = [];
    $files = glob($tasksDir . '/*.json');

    foreach ($files as $file) {
        // Skip raw files
        if (strpos(basename($file), '-raw.json') !== false) {
            continue;
        }

        $taskData = json_decode(file_get_contents($file), true);
        if ($taskData && isset($taskData['task_id'])) {
            $tasks[] = [
                'task_id'     => $taskData['task_id'],
                'task_name'   => $taskData['task_name'] ?? 'Untitled',
                'status'      => $taskData['status'] ?? 'unknown',
                'website_url' => $taskData['website_url'] ?? null,
                'theme'       => $taskData['theme'] ?? null,
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'tasks'   => $tasks,
    ]);

} elseif ($action === 'get' && $taskId) {
    // Get specific task details
    $filename = $tasksDir . '/' . $taskId . '.json';

    if (! file_exists($filename)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Task not found',
        ]);
        exit;
    }

    $taskData = json_decode(file_get_contents($filename), true);

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
