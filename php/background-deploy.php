<?php
// Background deployment script
// This script runs the deployment process in the background and logs output

// IMMEDIATE DEBUG - Write to log as soon as script starts
error_log("BACKGROUND-DEPLOY: Script started at " . date('Y-m-d H:i:s'));

// Set execution time limit for background process
set_time_limit(0); // No time limit for background deployment
ini_set('max_execution_time', 0);

define('SCRIPT_DIR', dirname(__DIR__));

// Write immediate debug log
$debugLogDir = SCRIPT_DIR . '/logs/deployment';
if (! is_dir($debugLogDir)) {
    mkdir($debugLogDir, 0755, true);
}
file_put_contents($debugLogDir . '/deployment.log', "[" . date('Y-m-d H:i:s') . "] BACKGROUND-DEPLOY: Script execution started\n", FILE_APPEND | LOCK_EX);

function writeDeploymentLog($message, $level = 'INFO', $step = null)
{
    // Ensure logs directory exists
    $logsDir = SCRIPT_DIR . '/logs/deployment';
    if (! is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }

    // Use simple naming for latest deployment only
    $logFile = $logsDir . '/deployment.log';
    // Use UTC timezone for consistent logging across all operations
    $timestamp = gmdate('Y-m-d H:i:s');

    // Use simple format for consistency with shell scripts
    $stepInfo = $step ? " | $step" : "";
    $logEntry = "[$timestamp] $level$stepInfo | $message\n";

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function updateDeploymentStatus($status, $step = null)
{
    if (! is_dir(SCRIPT_DIR . '/tmp')) {
        mkdir(SCRIPT_DIR . '/tmp', 0755, true);
    }
    $statusFile    = SCRIPT_DIR . '/tmp/deployment_status.json';
    $currentStatus = [];

    if (file_exists($statusFile)) {
        $currentStatus = json_decode(file_get_contents($statusFile), true) ?: [];
    }

    $currentStatus['status']    = $status;
    $currentStatus['timestamp'] = time();
    // Use UTC timezone for consistent logging across all operations
    $currentStatus['last_update'] = gmdate('Y-m-d H:i:s');

    if ($step) {
        $currentStatus['current_step'] = $step;

        // Track step timing information
        if (! isset($currentStatus['step_timings'])) {
            $currentStatus['step_timings'] = [];
        }

        // Record step start time
        if ($status === 'running' && ! isset($currentStatus['step_timings'][$step])) {
            $currentStatus['step_timings'][$step] = [
                'start_time'           => time(),
                'start_time_formatted' => gmdate('Y-m-d H:i:s'),
                'status'               => 'running',
            ];
        }

        // Record step completion time
        if ($status === 'completed' && isset($currentStatus['step_timings'][$step])) {
            $currentStatus['step_timings'][$step]['end_time']           = time();
            $currentStatus['step_timings'][$step]['end_time_formatted'] = gmdate('Y-m-d H:i:s');
            $currentStatus['step_timings'][$step]['duration']           = time() - $currentStatus['step_timings'][$step]['start_time'];
            $currentStatus['step_timings'][$step]['status']             = 'completed';
        }
    }

    // Track deployment start time
    if ($status === 'running' && ! isset($currentStatus['deployment_start_time'])) {
        $currentStatus['deployment_start_time']           = time();
        $currentStatus['deployment_start_time_formatted'] = gmdate('Y-m-d H:i:s');
    }

    // Track deployment completion time
    if ($status === 'completed') {
        $currentStatus['deployment_end_time']           = time();
        $currentStatus['deployment_end_time_formatted'] = gmdate('Y-m-d H:i:s');
        if (isset($currentStatus['deployment_start_time'])) {
            $currentStatus['total_duration'] = time() - $currentStatus['deployment_start_time'];
        }
    }

    file_put_contents($statusFile, json_encode($currentStatus, JSON_PRETTY_PRINT));
}

// Parse command line arguments for selective step execution
$singleStep    = null;
$multipleSteps = null;
$testMode      = false;

if ($argc > 1) {
    for ($i = 1; $i < $argc; $i++) {
        switch ($argv[$i]) {
            case '--step':
                if (isset($argv[$i + 1])) {
                    $singleStep = $argv[$i + 1];
                    $i++; // Skip next argument
                }
                break;
            case '--steps':
                if (isset($argv[$i + 1])) {
                    $multipleSteps = explode(',', $argv[$i + 1]);
                    $i++; // Skip next argument
                }
                break;
            case '--test':
                $testMode = true;
                break;
        }
    }
}

// Check for GitHub token (required for real deployment)
$hasGitHubToken = ! empty(getenv('GITHUB_TOKEN'));
if (! $hasGitHubToken) {
    $gitConfigFile = SCRIPT_DIR . '/config/git.json';
    if (file_exists($gitConfigFile)) {
        $gitConfig      = json_decode(file_get_contents($gitConfigFile), true);
        $hasGitHubToken = ! empty($gitConfig['token']);
    }
}

// If no GitHub token and not in test mode, run in demo mode
if (! $hasGitHubToken && ! $testMode) {
    writeDeploymentLog('No GitHub token found - running in demo mode', 'WARNING');
    $testMode = true;
}

// Main deployment process
try {
    updateDeploymentStatus('running');
    writeDeploymentLog('Starting background deployment process...', 'INFO');

    $scriptPath = SCRIPT_DIR;
    // Core steps for web interface deployment
    $allSteps = [
        'create-site'    => [
            'script' => 'scripts/site.sh', // Initiates site creation asynchronously
            'name'   => 'Initiate Site Creation',
        ],
        'get-cred'       => [
            'script' => 'scripts/creds.sh', // Handles waiting internally when needed
            'name'   => 'Get Credentials',
        ],
        'trigger-deploy' => [
            'script' => 'scripts/deploy.sh',
            'name'   => 'Trigger Deployment',
        ],
        'github-actions' => [
            'script' => 'scripts/github-actions-monitor.sh',
            'name'   => 'GitHub Actions',
        ],
    ];

    // Determine which steps to run
    $steps = $allSteps;
    if ($singleStep) {
        if (isset($allSteps[$singleStep])) {
            $steps = [$singleStep => $allSteps[$singleStep]];
            writeDeploymentLog("Running single step: $singleStep", 'INFO');
        } else {
            writeDeploymentLog("Invalid step: $singleStep", 'ERROR');
            exit(1);
        }
    } elseif ($multipleSteps) {
        $steps = [];
        foreach ($multipleSteps as $stepKey) {
            $stepKey = trim($stepKey);
            if (isset($allSteps[$stepKey])) {
                $steps[$stepKey] = $allSteps[$stepKey];
            } else {
                writeDeploymentLog("Invalid step: $stepKey", 'ERROR');
                exit(1);
            }
        }
        writeDeploymentLog("Running selected steps: " . implode(', ', array_keys($steps)), 'INFO');
    }

    foreach ($steps as $stepKey => $stepInfo) {
        updateDeploymentStatus('running', $stepKey);
        writeDeploymentLog("Starting step: {$stepInfo['name']}", 'INFO', $stepKey);

        // Handle test/demo mode
        if ($testMode) {
            writeDeploymentLog("DEMO MODE: Simulating {$stepInfo['name']}", 'INFO', $stepKey);
            sleep(2); // Simulate processing time
            writeDeploymentLog("DEMO MODE: {$stepInfo['name']} completed successfully", 'SUCCESS', $stepKey);
            continue;
        }

        // All steps now use external scripts - no special internal functions needed

        // Parse script command to extract just the script filename for validation
        $scriptCommand  = $stepInfo['script'];
        $scriptParts    = explode(' ', $scriptCommand);
        $scriptFilename = $scriptParts[0]; // Get just the script name without arguments

        // Check if script exists and is executable
        $scriptFile = $scriptPath . '/' . $scriptFilename;
        if (! file_exists($scriptFile)) {
            writeDeploymentLog("Script not found: {$scriptFile}", 'ERROR', $stepKey);
            updateDeploymentStatus('failed', $stepKey);
            exit(1);
        }

        if (! is_executable($scriptFile)) {
            writeDeploymentLog("Script not executable: {$scriptFile}", 'ERROR', $stepKey);
            updateDeploymentStatus('failed', $stepKey);
            exit(1);
        }

        // Execute the script and capture output
        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"], // stderr
        ];

        // Set up environment for better network connectivity
        $envVars = [
            'PATH'          => '/usr/local/bin:/usr/bin:/bin',
            'HOME'          => '/Users/vishnu', // Ensure HOME is set correctly for SSH key access
            'USER'          => 'vishnu',        // Override numeric USER to prevent SSH confusion
            'LOGNAME'       => 'vishnu',        // Additional user environment variable
            'SSH_AUTH_SOCK' => '',              // Prevent SSH agent confusion
        ];
        // Add network debugging for DNS resolution issues
        $debugCommands = [
            'echo "=== Network Debug Info ==="',
            'echo "PATH: $PATH"',
            'echo "HOME: $HOME"',
            'echo "USER: $(whoami)"',
            'curl --version | head -1',
            'nslookup api.kinsta.com 2>/dev/null || echo "DNS resolution failed"',
            'ping -c 1 api.kinsta.com 2>/dev/null && echo "Ping successful" || echo "Ping failed"',
            'curl -I https://api.kinsta.com 2>/dev/null | head -1 || echo "Direct curl test failed"',
            'curl -v -I https://api.kinsta.com 2>&1 | grep -E "(SSL|TLS|Certificate|Connected)" | head -5 || echo "SSL debug failed"',
            'echo "=== End Debug ==="',
        ];

        $envString = '';
        foreach ($envVars as $key => $value) {
            $envString .= "export $key='$value'; ";
        }

        // Explicitly unset problematic environment variables
        $envString .= 'unset SUDO_USER SUDO_UID SUDO_GID; ';

        $debugString = implode('; ', $debugCommands) . '; ';
        $command     = "cd $scriptPath && $envString $debugString /bin/bash {$stepInfo['script']}";
        #shorten command for logging upto 100 chars and add ... if longer
        $commandTrimmed = strlen($command) > 100 ? substr($command, 0, 100) . '...' : $command;
        # writeDeploymentLog("Executing: $commandTrimmed", 'INFO', $stepKey);

        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            // Close stdin
            fclose($pipes[0]);

            // Read stdout
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // Read stderr
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // Get the exit status
            $exitStatus = proc_close($process);

            // Log all output line by line for better readability
            if (! empty($stdout)) {
                writeDeploymentLog("STDOUT:", 'INFO', $stepKey);
                $stdoutLines = explode("\n", trim($stdout));
                foreach ($stdoutLines as $line) {
                    if (! empty(trim($line))) {
                        writeDeploymentLog($line, 'INFO', $stepKey);
                    }
                }
            }

            if (! empty($stderr)) {
                // Filter out curl progress output and other verbose noise
                $stderrLines   = explode("\n", trim($stderr));
                $filteredLines = [];

                foreach ($stderrLines as $line) {
                    $trimmedLine = trim($line);
                    if (empty($trimmedLine)) {
                        continue;
                    }

                    // Skip curl progress output
                    if (preg_match('/^\s*%\s+Total\s+%\s+Received/', $trimmedLine)) {
                        continue;
                    }

                    if (preg_match('/^\s*Dload\s+Upload\s+Total/', $trimmedLine)) {
                        continue;
                    }

                    if (preg_match('/^\s*\d+\s+\d+\s+\d+\s+\d+/', $trimmedLine)) {
                        continue;
                    }

                    if (preg_match('/^[\s\d:%-]+$/', $trimmedLine)) {
                        continue;
                    }

                    $filteredLines[] = $trimmedLine;
                }

                if (! empty($filteredLines)) {
                    writeDeploymentLog("STDERR:", $exitStatus === 0 ? 'WARNING' : 'ERROR', $stepKey);
                    foreach ($filteredLines as $line) {
                        writeDeploymentLog($line, $exitStatus === 0 ? 'WARNING' : 'ERROR', $stepKey);
                    }
                }
            }

            writeDeploymentLog("Exit status: $exitStatus", $exitStatus === 0 ? 'SUCCESS' : ($exitStatus === 2 ? 'WARNING' : 'ERROR'), $stepKey);

            if ($exitStatus !== 0) {
                // Handle monitoring timeout (exit code 2) differently for monitor-creation step
                if ($stepKey === 'monitor-creation' && $exitStatus === 2) {
                    writeDeploymentLog("Monitoring timed out for step: {$stepInfo['name']}. Checking final status...", 'WARNING', $stepKey);

                    // Do a final status check to see if the operation completed despite timeout
                    $finalCheckCommand = "cd $scriptPath && ./status.sh";
                    $finalCheckOutput  = shell_exec($finalCheckCommand);
                    $finalStatus       = json_decode(trim($finalCheckOutput), true);

                    if ($finalStatus && isset($finalStatus['status']) && $finalStatus['status'] === 'completed') {
                        writeDeploymentLog("Operation completed successfully despite monitoring timeout!", 'SUCCESS', $stepKey);
                        writeDeploymentLog("Site ID: " . ($finalStatus['site_id'] ?? 'Unknown'), 'INFO', $stepKey);
                        // Continue with deployment
                    } else {
                        writeDeploymentLog("Operation still not complete after timeout. Continuing with next steps anyway.", 'WARNING', $stepKey);
                        // Don't fail the deployment, just continue
                    }
                } else {
                    // For other steps or other exit codes, treat as failure
                    updateDeploymentStatus('failed', $stepKey);
                    writeDeploymentLog("Deployment failed at step: {$stepInfo['name']} (exit code: $exitStatus)", 'ERROR', $stepKey);
                    exit(1);
                }
            }
        } else {
            writeDeploymentLog("Failed to execute: $command", 'ERROR', $stepKey);
            updateDeploymentStatus('failed', $stepKey);
            exit(1);
        }

        writeDeploymentLog("Completed step: {$stepInfo['name']}", 'SUCCESS', $stepKey);
    }

    updateDeploymentStatus('completed');
    writeDeploymentLog('Deployment completed successfully!', 'SUCCESS');
    error_log("BACKGROUND-DEPLOY: Script completed successfully at " . date('Y-m-d H:i:s'));

} catch (Exception $e) {
    writeDeploymentLog('Deployment failed with exception: ' . $e->getMessage(), 'ERROR');
    updateDeploymentStatus('failed');
    error_log("BACKGROUND-DEPLOY: Script failed with exception at " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
}

// Final debug log
file_put_contents($debugLogDir . '/deployment.log', "[" . date('Y-m-d H:i:s') . "] BACKGROUND-DEPLOY: Script execution finished\n", FILE_APPEND | LOCK_EX);
