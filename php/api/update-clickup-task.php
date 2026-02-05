<?php
/**
 * ClickUp Task Update API
 * Updates a ClickUp task with deployment details after successful deployment
 */

header('Content-Type: application/json');

// Get configuration
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

// Main execution
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are supported',
    ]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (! isset($input['task_id']) || empty($input['task_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Task ID is required',
    ]);
    exit;
}

$taskId         = $input['task_id'];
$siteUrl        = $input['site_url'] ?? null;
$adminUrl       = $input['admin_url'] ?? null;
$adminUser      = $input['admin_user'] ?? null;
$adminPass      = $input['admin_pass'] ?? null;
$deploymentDate = $input['deployment_date'] ?? date('Y-m-d H:i:s');

function normalizeUrl($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return $url;
    }
    if (preg_match('~^https?://~i', $url)) {
        return $url;
    }
    return 'https://' . $url;
}

function addCommentPart(array &$comment, $text, array $attributes = [])
{
    $comment[] = [
        'text'       => $text,
        'attributes' => empty($attributes) ? (object) [] : $attributes,
    ];
}

// Get ClickUp config
$config = getClickUpConfig();
if (! $config['success']) {
    http_response_code(500);
    echo json_encode($config);
    exit;
}

$apiToken = $config['api_token'];

// Prepare formatted comment with deployment information (ClickUp comment formatting)
$commentParts = [];
addCommentPart($commentParts, '✅ DEPLOYMENT COMPLETED', ['bold' => true]);
addCommentPart($commentParts, "\n\n");
addCommentPart($commentParts, 'Deployment Date: ', ['bold' => true]);
addCommentPart($commentParts, $deploymentDate);
addCommentPart($commentParts, "\n");

$plainLines = [];
$plainLines[] = '✅ DEPLOYMENT COMPLETED';
$plainLines[] = '';
$plainLines[] = 'Deployment Date: ' . $deploymentDate;
$plainLines[] = '';

if ($siteUrl) {
    $siteUrl = trim($siteUrl);
    $siteLink = normalizeUrl($siteUrl);
    addCommentPart($commentParts, '🌐 Site URL: ', ['bold' => true]);
    addCommentPart($commentParts, $siteUrl, ['link' => $siteLink]);
    addCommentPart($commentParts, "\n");
    $plainLines[] = '🌐 Site URL: ' . $siteLink;
}

if ($adminUrl) {
    $adminUrl = trim($adminUrl);
    $adminLink = normalizeUrl($adminUrl);
    addCommentPart($commentParts, '🔒 Admin URL: ', ['bold' => true]);
    addCommentPart($commentParts, $adminUrl, ['link' => $adminLink]);
    addCommentPart($commentParts, "\n");
    $plainLines[] = '🔒 Admin URL: ' . $adminLink;
}

if ($adminUser || $adminPass) {
    addCommentPart($commentParts, "\n");
    addCommentPart($commentParts, 'Login Credentials', ['bold' => true]);
    addCommentPart($commentParts, "\n");
    $plainLines[] = '';
    $plainLines[] = 'Login Credentials';
    if ($adminUser) {
        addCommentPart($commentParts, 'Username: ', ['bold' => true]);
        addCommentPart($commentParts, $adminUser, ['code' => true]);
        addCommentPart($commentParts, "\n");
        $plainLines[] = 'Username: ' . $adminUser;
    }
    if ($adminPass) {
        addCommentPart($commentParts, 'Password: ', ['bold' => true]);
        addCommentPart($commentParts, $adminPass, ['code' => true]);
        addCommentPart($commentParts, "\n");
        $plainLines[] = 'Password: ' . $adminPass;
    }
}

addCommentPart($commentParts, "\n");
addCommentPart($commentParts, "—");
addCommentPart($commentParts, "\n");
addCommentPart($commentParts, 'This is an automated message from the Site Builder.', ['italic' => true]);
$plainLines[] = '';
$plainLines[] = '—';
$plainLines[] = 'This is an automated message from the Site Builder.';

$commentTextPlain = implode("\n", $plainLines);

// Post comment to task
$commentUrl = "https://api.clickup.com/api/v2/task/{$taskId}/comment";

$commentData = [
    'comment_text' => $commentTextPlain,
    'comment'      => $commentParts,
    'notify_all'   => true,
];

$ch = curl_init($commentUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        "Authorization: {$apiToken}",
        "Content-Type: application/json",
    ],
    CURLOPT_POSTFIELDS   => json_encode($commentData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    CURLOPT_TIMEOUT      => 30,
    CURLOPT_FORBID_REUSE => true,
]);

$commentResponse = curl_exec($ch);
$commentHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError       = curl_error($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'cURL error posting comment',
        'error'   => $curlError,
    ]);
    exit;
}

if ($commentHttpCode !== 200) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => "ClickUp API returned HTTP {$commentHttpCode} when posting comment",
        'details' => json_decode($commentResponse, true),
    ]);
    exit;
}

// Optionally update Website URL custom field
$customFieldUpdated = false;
// Optionally update Website URL custom field
$customFieldUpdated = false;

// Get task details to find custom fields
$getUrl = "https://api.clickup.com/api/v2/task/{$taskId}";

$ch = curl_init($getUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        "Authorization: {$apiToken}",
        "Content-Type: application/json",
    ],
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $siteUrl) {
    $taskData           = json_decode($response, true);
    $customFieldUpdates = [];

    // Find and update custom fields
    if (isset($taskData['custom_fields']) && is_array($taskData['custom_fields'])) {
        foreach ($taskData['custom_fields'] as $field) {
            $fieldId   = $field['id'] ?? null;
            $fieldName = $field['name'] ?? '';

            // Update Website URL field if it exists
            if ($fieldName === 'Website URL' && $fieldId) {
                $customFieldUpdates[$fieldId] = ['value' => $siteUrl];
            }
        }
    }

    // Update custom fields if any found
    if (! empty($customFieldUpdates)) {
        $updateUrl  = "https://api.clickup.com/api/v2/task/{$taskId}";
        $updateData = ['custom_fields' => []];

        foreach ($customFieldUpdates as $fieldId => $update) {
            $updateData['custom_fields'][] = array_merge(['id' => $fieldId], $update);
        }

        $ch = curl_init($updateUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_HTTPHEADER     => [
                "Authorization: {$apiToken}",
                "Content-Type: application/json",
            ],
            CURLOPT_POSTFIELDS => json_encode($updateData),
            CURLOPT_TIMEOUT    => 30,
        ]);

        $updateResponse = curl_exec($ch);
        $updateHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $customFieldUpdated = ($updateHttpCode === 200);
    }
}

// Success response
echo json_encode([
    'success' => true,
    'message' => 'ClickUp task updated successfully with comment',
    'task_id' => $taskId,
    'updated' => [
        'comment'       => true,
        'custom_fields' => $customFieldUpdated,
    ],
]);
