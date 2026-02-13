#!/usr/bin/env php
<?php
    /**
 * Helper script to update deployment status from bash scripts
 * Usage: php update-status.php <status> <step> [message]
 *
 * Examples:
 *   php update-status.php running github-actions "Monitoring GitHub Actions"
 *   php update-status.php completed github-actions "GitHub Actions completed"
 */

    // Get script directory
    define('SCRIPT_DIR', dirname(__DIR__));

    // Load existing status
    $statusFile = SCRIPT_DIR . '/tmp/deployment_status.json';

    if (! is_dir(SCRIPT_DIR . '/tmp')) {
    mkdir(SCRIPT_DIR . '/tmp', 0755, true);
    }

    $currentStatus = [];

    if (file_exists($statusFile)) {
    $content = @file_get_contents($statusFile);
    if ($content !== false) {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $currentStatus = $decoded;
        }
    }
    }

    // Parse arguments
    $status  = $argv[1] ?? null;
    $step    = $argv[2] ?? null;
    $message = $argv[3] ?? null;

    if (! $status) {
    fwrite(STDERR, "Error: status parameter required\n");
    exit(1);
    }

    // Define deployment step order for auto-progression
    $deploymentSteps = [
    'create-site',
    'get-cred',
    'trigger-deploy',
    'github-actions',
    ];

    // Update status based on parameters
    if ($step) {
    $currentStatus['current_step'] = $step;

    // Initialize step_timings if not exists
    if (! isset($currentStatus['step_timings'])) {
        $currentStatus['step_timings'] = [];
    }

    // Handle step-level status updates
    if ($status === 'running' && ! isset($currentStatus['step_timings'][$step])) {
        // Starting a new step
        $currentStatus['step_timings'][$step] = [
            'start_time'           => time(),
            'start_time_formatted' => gmdate('Y-m-d H:i:s'),
            'status'               => 'running',
        ];

        // Only update overall status to 'running' when first step starts
        if (! isset($currentStatus['status']) || $currentStatus['status'] === 'pending') {
            $currentStatus['status'] = 'running';
        }
    } elseif ($status === 'completed' && isset($currentStatus['step_timings'][$step])) {
        // Completing a step
        $currentStatus['step_timings'][$step]['end_time']           = time();
        $currentStatus['step_timings'][$step]['end_time_formatted'] = gmdate('Y-m-d H:i:s');
        $currentStatus['step_timings'][$step]['duration']           = time() - $currentStatus['step_timings'][$step]['start_time'];
        $currentStatus['step_timings'][$step]['status']             = 'completed';

        // CRITICAL FIX: Advance to next step or mark deployment complete
        $currentStepIndex = array_search($step, $deploymentSteps);
        if ($currentStepIndex !== false) {
            if ($currentStepIndex < count($deploymentSteps) - 1) {
                // Move to next step
                $nextStep                      = $deploymentSteps[$currentStepIndex + 1];
                $currentStatus['current_step'] = $nextStep;
                echo "Advanced to next step: $nextStep\n";
            } else {
                // This was the last step - mark deployment as completed
                $currentStatus['status']       = 'completed';
                $currentStatus['current_step'] = $step; // Keep on last completed step
                echo "Deployment completed - all steps finished\n";
            }
        }
    } elseif ($status === 'failed' && isset($currentStatus['step_timings'][$step])) {
        // Step failed
        $currentStatus['step_timings'][$step]['end_time']           = time();
        $currentStatus['step_timings'][$step]['end_time_formatted'] = gmdate('Y-m-d H:i:s');
        $currentStatus['step_timings'][$step]['duration']           = time() - $currentStatus['step_timings'][$step]['start_time'];
        $currentStatus['step_timings'][$step]['status']             = 'failed';
        $currentStatus['status']                                    = 'failed';
    }
    } else {
    // No step provided - deployment-level status update
    $currentStatus['status'] = $status;
    }

    // Add message if provided
    if ($message) {
    $currentStatus['message'] = $message;
    }

    // Update timestamp
    $currentStatus['timestamp']   = time();
    $currentStatus['last_update'] = gmdate('Y-m-d H:i:s');

    // Write to file with locking
    $result = file_put_contents($statusFile, json_encode($currentStatus, JSON_PRETTY_PRINT), LOCK_EX);

    if ($result === false) {
    fwrite(STDERR, "Error: Failed to write status file\n");
    exit(1);
    }

    // Output success message to stdout
    echo "Status updated: step=$step, status=$status\n";
exit(0);
