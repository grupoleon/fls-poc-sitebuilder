<?php
/**
 * Deployment Manager
 * Handles deployment operations and integrates with background-deploy.php
 */

class DeploymentManager
{
    private $scriptDir;
    private $logsDir;

    public function __construct()
    {
        $this->scriptDir = dirname(dirname(dirname(__DIR__)));
        $this->logsDir   = $this->scriptDir . '/logs';
    }

    /**
     * Trigger deployment with specific steps
     */
    public function triggerDeployment($steps = null, $force = false)
    {
        $backgroundScript = $this->scriptDir . '/php/background-deploy.php';

        if (! file_exists($backgroundScript)) {
            throw new Exception("Background deployment script not found: {$backgroundScript}");
        }

        // Check if GitHub token is configured
        $gitConfig = $this->scriptDir . '/config/git.json';
        if (file_exists($gitConfig)) {
            $config = json_decode(file_get_contents($gitConfig), true);
            if (empty($config['token']) && empty(getenv('GITHUB_TOKEN'))) {
                throw new Exception("GitHub token not configured. Please set GITHUB_TOKEN environment variable or add 'token' to git.json configuration.");
            }
        }

        // Find a reliable PHP CLI binary (we must avoid selecting php-fpm)
        $phpBinary = $this->findPhpCliBinary();

        if (! $phpBinary) {
            // Be explicit: background tasks require the php CLI. Fail early with a clear message so users fix their environment.
            throw new Exception("No PHP CLI binary found. Please install PHP CLI and ensure 'php' (SAPI=cli) is available in PATH or at a standard location (/opt/homebrew/bin/php, /usr/local/bin/php, /usr/bin/php).");
        }

        $command = "cd " . escapeshellarg($this->scriptDir) . " && ";
        // Use an explicit path to the php binary so background execution doesn't depend on PATH
        $command .= escapeshellarg($phpBinary) . " " . escapeshellarg($backgroundScript);

        if ($steps && is_array($steps)) {
            $command .= " --steps " . escapeshellarg(implode(',', $steps));
        } elseif ($steps && is_string($steps)) {
            $command .= " --step " . escapeshellarg($steps);
        }

        // Log the command for debugging
        error_log("Executing deployment command: " . $command);

        // Create a simple shell script to ensure proper background execution
        $shellScript = $this->scriptDir . '/tmp/deploy_runner.sh';
        // Log which PHP binary will be used for background runners (helpful for debugging)
        error_log("DeploymentManager: Selected PHP binary for background runner: " . $phpBinary);

        $shellContent  = "#!/bin/bash\n" . $command . "\n";

        // Ensure tmp directory exists
        if (! is_dir($this->scriptDir . '/tmp')) {
            mkdir($this->scriptDir . '/tmp', 0755, true);
        }

        // Prepare logo for deployment if one exists
        $this->prepareLogo();

        file_put_contents($shellScript, $shellContent);
        chmod($shellScript, 0755);

        // Execute the shell script in background
        $output     = [];
        $return_var = 0;

        // Create initial deployment status
        $this->resetDeployment();

        // Update status to starting (preserve clickup_task_id if exists)
        $statusFile     = $this->scriptDir . '/tmp/deployment_status.json';
        $existingStatus = [];
        if (file_exists($statusFile)) {
            $existingStatus = json_decode(file_get_contents($statusFile), true) ?: [];
        }

        $initialStatus = [
            'status'    => 'starting',
            'step'      => 'initializing',
            'message'   => 'Deployment initialization...',
            'timestamp' => time(),
            'logs'      => ['Deployment requested from web interface'],
        ];

        // Preserve clickup_task_id if it exists
        if (isset($existingStatus['clickup_task_id']) && ! empty($existingStatus['clickup_task_id'])) {
            $initialStatus['clickup_task_id'] = $existingStatus['clickup_task_id'];
            error_log("DeploymentManager: Preserving ClickUp Task ID in initial status: {$existingStatus['clickup_task_id']}");
        }

        file_put_contents($statusFile, json_encode($initialStatus, JSON_PRETTY_PRINT));

        // Create log file for debugging
        $logFile = $this->logsDir . '/deployment/deployment.log';

        // Ensure logs directory exists
        if (! is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }

        // Use a simpler background execution without nohup to avoid terminal issues
        $fullCommand = "bash " . escapeshellarg($shellScript) . " >> " . escapeshellarg($logFile) . " 2>&1 & echo $!";

        // Execute in background without nohup
        exec($fullCommand, $output, $return_var);

        error_log("Deployment command executed: $fullCommand");
        error_log("Command output: " . implode("\n", $output));
        error_log("Return code: $return_var");

        // Write to deployment log immediately for debugging
        $debugMsg = "[" . date('Y-m-d H:i:s') . "] DeploymentManager: Starting background deployment with PID " . ($output[0] ?? 'unknown') . "\n";
        file_put_contents($logFile, $debugMsg, FILE_APPEND | LOCK_EX);

        // Don't clean up the script immediately - keep it for debugging
        // The script will be cleaned up by the next deployment or system cleanup

        return [
            'status'  => 'started',
            'message' => 'Deployment started in background',
            'steps'   => $steps ?: 'all',
            'output'  => $output,
            'command' => $command,
            'script'  => $shellScript,
        ];
    }

    /**
     * Trigger deployment again - only runs deploy.sh with existing credentials
     */
    public function triggerDeploymentAgain()
    {
        $deployScript = $this->scriptDir . '/scripts/deploy.sh';

        if (! file_exists($deployScript)) {
            throw new Exception("Deployment script not found: {$deployScript}");
        }

        // Check if credentials exist in tmp directory or config
        $credsExist = false;

        // Check for credentials in tmp directory (from previous run)
        $tmpCredsFiles = [
            $this->scriptDir . '/tmp/kinsta_token.txt',
            $this->scriptDir . '/tmp/site_id.txt',
            $this->scriptDir . '/tmp/github_run_id.txt',
        ];

        foreach ($tmpCredsFiles as $file) {
            if (file_exists($file) && ! empty(trim(file_get_contents($file)))) {
                $credsExist = true;
                break;
            }
        }

        // Also check config files
        if (! $credsExist) {
            $gitConfig  = $this->scriptDir . '/config/git.json';
            $siteConfig = $this->scriptDir . '/config/site.json';

            if (file_exists($gitConfig) && file_exists($siteConfig)) {
                $gitConfigData  = json_decode(file_get_contents($gitConfig), true);
                $siteConfigData = json_decode(file_get_contents($siteConfig), true);

                if (! empty($gitConfigData['token']) && ! empty($siteConfigData['kinsta_token'])) {
                    $credsExist = true;
                }
            }
        }

        if (! $credsExist) {
            throw new Exception("No credentials found. Please run a full deployment first to generate the necessary credentials.");
        }

        $command = "cd " . escapeshellarg($this->scriptDir) . " && ";
        $command .= "bash " . escapeshellarg($deployScript);

        // Log the command for debugging
        error_log("Executing deploy again command: " . $command);

        // Create a simple shell script to ensure proper background execution
        $shellScript  = $this->scriptDir . '/tmp/deploy_again_runner.sh';
        $shellContent = "#!/bin/bash\n" . $command . "\n";

        // Ensure tmp directory exists
        if (! is_dir($this->scriptDir . '/tmp')) {
            mkdir($this->scriptDir . '/tmp', 0755, true);
        }

        // Prepare logo for deployment if one exists
        $this->prepareLogo();

        file_put_contents($shellScript, $shellContent);
        chmod($shellScript, 0755);

        // Execute the shell script in background
        $output     = [];
        $return_var = 0;

        // Create initial deployment status
        $this->resetDeployment();

        // Update status to starting (preserve clickup_task_id if exists)
        $statusFile     = $this->scriptDir . '/tmp/deployment_status.json';
        $existingStatus = [];
        if (file_exists($statusFile)) {
            $existingStatus = json_decode(file_get_contents($statusFile), true) ?: [];
        }

        $initialStatus = [
            'status'    => 'starting',
            'step'      => 'deploy',
            'message'   => 'Running deploy.sh with existing credentials...',
            'timestamp' => time(),
            'logs'      => ['Deploy Again requested from web interface'],
        ];

        // Preserve clickup_task_id if it exists
        if (isset($existingStatus['clickup_task_id']) && ! empty($existingStatus['clickup_task_id'])) {
            $initialStatus['clickup_task_id'] = $existingStatus['clickup_task_id'];
            error_log("DeploymentManager: Preserving ClickUp Task ID in deploy again: {$existingStatus['clickup_task_id']}");
        }

        file_put_contents($statusFile, json_encode($initialStatus, JSON_PRETTY_PRINT));

        // Create log file for debugging
        $logFile = $this->logsDir . '/deployment/deployment.log';

        // Ensure logs directory exists
        if (! is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }

        // Use a simpler background execution without nohup to avoid terminal issues
        $fullCommand = "bash " . escapeshellarg($shellScript) . " >> " . escapeshellarg($logFile) . " 2>&1 & echo $!";

        // Execute in background without nohup
        exec($fullCommand, $output, $return_var);

        error_log("Deploy Again command executed: $fullCommand");
        error_log("Command output: " . implode("\n", $output));
        error_log("Return code: $return_var");

        // Write to deployment log immediately for debugging
        $debugMsg = "[" . date('Y-m-d H:i:s') . "] DeploymentManager: Starting deploy again with PID " . ($output[0] ?? 'unknown') . "\n";
        file_put_contents($logFile, $debugMsg, FILE_APPEND | LOCK_EX);

        return [
            'status'  => 'started',
            'message' => 'Deploy again started in background',
            'steps'   => 'deploy',
            'output'  => $output,
            'command' => $command,
            'script'  => $shellScript,
        ];
    }

    /**
     * Find a usable PHP CLI binary on the system and verify it is SAPI=cli.
     * Returns the absolute path to the php CLI or null if none found.
     *
     * This makes it safer for the web UI to generate background runner scripts that won't
     * accidentally call php-fpm or another non-CLI binary which prints usage/help text.
     */
    private function findPhpCliBinary()
    {
        $candidates = [];

        // If PHP_BINARY is defined (the current binary used by this process), add it first
        if (defined('PHP_BINARY') && PHP_BINARY) {
            $candidates[] = PHP_BINARY;
        }

        // If there's a 'php' on PATH, prefer that (but we'll verify SAPI below)
        $whichPhp = trim(@shell_exec('command -v php 2>/dev/null') ?: '');
        if ($whichPhp) {
            array_unshift($candidates, $whichPhp);
        }

        // Common locations to try as a fallback
        $candidates = array_merge($candidates, [
            '/opt/homebrew/bin/php',
            '/usr/local/bin/php',
            '/usr/local/opt/php/bin/php',
            '/usr/bin/php',
            '/bin/php',
        ]);

        // Normalize and remove empties/duplicates
        $candidates = array_values(array_unique(array_filter($candidates)));

        foreach ($candidates as $candidate) {
            if (! is_executable($candidate)) {
                continue;
            }

            // Verify the candidate is a CLI sapi by asking it to print its SAPI
            $cmd = escapeshellcmd($candidate) . ' -r ' . escapeshellarg('echo PHP_SAPI;') . ' 2>&1';
            $out = [];
            $ret = 1;
            @exec($cmd, $out, $ret);
            $sapi = trim(implode("\n", $out));

            if (strtolower($sapi) === 'cli') {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Get deployment status
     */
    public function getDeploymentStatus()
    {
        $statusFile = $this->scriptDir . '/tmp/deployment_status.json';

        if (! file_exists($statusFile)) {
            return [
                'status'  => 'idle',
                'message' => 'No deployment in progress',
            ];
        }

        $content = file_get_contents($statusFile);
        $status  = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status'  => 'error',
                'message' => 'Failed to read deployment status',
            ];
        }

        return $status;
    }

    /**
     * Get deployment logs
     */
    public function getDeploymentLogs($lines = 50, $lastReadTime = null)
    {
        $logFile = $this->logsDir . '/deployment/deployment.log';

        if (! file_exists($logFile)) {
            return [];
        }

        $logs   = [];
        $handle = fopen($logFile, 'r');

        if ($handle) {
            // Read all lines
            $logLines = [];
            while (($line = fgets($handle)) !== false) {
                $logLines[] = trim($line);
            }
            fclose($handle);

            // Filter by timestamp if lastReadTime is provided
            if ($lastReadTime) {
                $filteredLines = [];
                foreach ($logLines as $line) {
                    if (empty($line)) {
                        continue;
                    }

                    // Extract timestamp from log line
                    if (preg_match('/\[([^\]]+)\]/', $line, $matches)) {
                        $logTime = strtotime($matches[1]);
                        if ($logTime > $lastReadTime) {
                            $filteredLines[] = $line;
                        }
                    }
                }
                $logLines = $filteredLines;
            } else {
                // Take last N lines and reverse to show newest first
                $logLines = array_slice($logLines, -$lines);
                $logLines = array_reverse($logLines);
            }

            foreach ($logLines as $line) {
                if (empty($line)) {
                    continue;
                }

                // Parse log line format: [timestamp] LEVEL | message
                if (preg_match('/\[([^\]]+)\]\s+(\w+)(?:\s+\|\s+([^|]+))?\s+\|\s+(.+)/', $line, $matches)) {
                    $logs[] = [
                        'timestamp' => $matches[1],
                        'level'     => $matches[2],
                        'step'      => isset($matches[3]) ? trim($matches[3]) : null,
                        'message'   => $matches[4],
                    ];
                } else {
                    // Fallback for non-standard log lines
                    $logs[] = [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'level'     => 'INFO',
                        'step'      => null,
                        'message'   => $line,
                    ];
                }
            }
        }

        return $logs;
    }

    /**
     * Get available deployment steps
     */
    public function getAvailableSteps()
    {
        return [
            'create-site'    => [
                'name'        => 'Create Site',
                'description' => 'Initiate site creation on Kinsta',
            ],
            'get-cred'       => [
                'name'        => 'Get Credentials',
                'description' => 'Retrieve site credentials and wait for completion',
            ],
            'trigger-deploy' => [
                'name'        => 'Trigger Deploy',
                'description' => 'Upload configs and trigger GitHub Actions deployment',
            ],
            'github-actions' => [
                'name'        => 'GitHub Actions',
                'description' => 'Monitor GitHub Actions deployment status and logs',
            ],
        ];
    }

    /**
     * Run individual deployment step
     */
    public function runStep($step)
    {
        $availableSteps = $this->getAvailableSteps();

        if (! isset($availableSteps[$step])) {
            throw new Exception("Invalid deployment step: {$step}");
        }

        return $this->triggerDeployment($step);
    }

    /**
     * Get system status for dashboard
     */
    public function getSystemStatus()
    {
        $status = [
            'deployment' => $this->getDeploymentStatus(),
            'scripts'    => $this->checkScriptStatus(),
            'configs'    => $this->checkConfigStatus(),
            'uploads'    => $this->checkUploadsStatus(),
        ];

        return $status;
    }

    /**
     * Check if required scripts exist and are executable
     */
    private function checkScriptStatus()
    {
        $requiredScripts = [
            'deploy.sh'             => 'scripts/deploy.sh',
            'site.sh'               => 'scripts/site.sh',
            'creds.sh'              => 'scripts/creds.sh',
            'background-deploy.php' => 'php/background-deploy.php',
        ];

        $status = [];

        foreach ($requiredScripts as $name => $path) {
            $fullPath      = $this->scriptDir . '/' . $path;
            $status[$name] = [
                'exists'     => file_exists($fullPath),
                'executable' => file_exists($fullPath) && is_executable($fullPath),
                'path'       => $path,
            ];
        }

        return $status;
    }

    /**
     * Check configuration files status
     */
    private function checkConfigStatus()
    {
        $configDir       = $this->scriptDir . '/config';
        $requiredConfigs = ['config.json', 'theme-config.json', 'git.json', 'site.json'];

        $status = [];

        foreach ($requiredConfigs as $config) {
            $filePath = $configDir . '/' . $config;
            $exists   = file_exists($filePath);
            $valid    = false;

            if ($exists) {
                $content = file_get_contents($filePath);
                $decoded = json_decode($content, true);
                $valid   = json_last_error() === JSON_ERROR_NONE;
            }

            $status[$config] = [
                'exists' => $exists,
                'valid'  => $valid,
                'size'   => $exists ? filesize($filePath) : 0,
            ];
        }

        return $status;
    }

    /**
     * Check uploads directory status
     */
    private function checkUploadsStatus()
    {
        $uploadsDir = $this->scriptDir . '/uploads/images';

        $status = [
            'exists'      => is_dir($uploadsDir),
            'writable'    => is_dir($uploadsDir) && is_writable($uploadsDir),
            'image_count' => 0,
            'total_size'  => 0,
        ];

        if (is_dir($uploadsDir)) {
            $files = scandir($uploadsDir);
            foreach ($files as $file) {
                if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                    $status['image_count']++;
                    $status['total_size'] += filesize($uploadsDir . '/' . $file);
                }
            }
        }

        return $status;
    }

    /**
     * Clear deployment logs
     */
    public function clearLogs()
    {
        $logFile = $this->logsDir . '/deployment/deployment.log';

        if (file_exists($logFile)) {
            return unlink($logFile);
        }

        return true;
    }

    /**
     * Get recent deployment history
     */
    public function getDeploymentHistory($limit = 10)
    {
        $logs              = $this->getDeploymentLogs(200); // Get more logs to find completion markers
        $deployments       = [];
        $currentDeployment = null;

        foreach (array_reverse($logs) as $log) { // Process in chronological order
            if (strpos($log['message'], 'Starting background deployment') !== false) {
                // Start of new deployment
                if ($currentDeployment) {
                    $deployments[] = $currentDeployment;
                }

                $currentDeployment = [
                    'start_time' => $log['timestamp'],
                    'end_time'   => null,
                    'status'     => 'running',
                    'steps'      => [],
                    'error'      => null,
                ];
            } elseif ($currentDeployment) {
                // Add step to current deployment
                if ($log['step']) {
                    $currentDeployment['steps'][] = [
                        'step'      => $log['step'],
                        'message'   => $log['message'],
                        'level'     => $log['level'],
                        'timestamp' => $log['timestamp'],
                    ];
                }

                // Check for completion or failure
                if (strpos($log['message'], 'Deployment completed successfully') !== false) {
                    $currentDeployment['status']   = 'completed';
                    $currentDeployment['end_time'] = $log['timestamp'];
                } elseif (strpos($log['message'], 'Deployment failed') !== false) {
                    $currentDeployment['status']   = 'failed';
                    $currentDeployment['end_time'] = $log['timestamp'];
                    $currentDeployment['error']    = $log['message'];
                }
            }
        }

        // Add current deployment if exists
        if ($currentDeployment) {
            $deployments[] = $currentDeployment;
        }

        return array_slice(array_reverse($deployments), 0, $limit);
    }

    /**
     * Stop any running deployment
     */
    public function stopDeployment()
    {
        // Kill any running deployment processes
        $command = "pkill -f 'background-deploy.php' 2>/dev/null || true";
        exec($command);

        // Clear deployment status file
        $statusFile = $this->scriptDir . '/tmp/deployment_status.json';
        if (file_exists($statusFile)) {
            unlink($statusFile);
        }

        // Clear deploy trigger file
        $triggerFile = $this->scriptDir . '/tmp/deploy_trigger.json';
        if (file_exists($triggerFile)) {
            unlink($triggerFile);
        }
    }

    /**
     * Prepare logo for deployment by copying the most recent logo to tmp directory
     */
    private function prepareLogo()
    {
        try {
            $uploadsDir = $this->scriptDir . '/uploads/images';

            if (! is_dir($uploadsDir)) {
                return; // No uploads directory, skip logo preparation
            }

            // Look for the most recent logo file
            $logoFiles = glob($uploadsDir . '/logo_*.*');

            if (empty($logoFiles)) {
                return; // No logo files found
            }

            // Sort by modification time (newest first)
            usort($logoFiles, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            $latestLogo = $logoFiles[0];
            $extension  = pathinfo($latestLogo, PATHINFO_EXTENSION);

            // Copy to tmp directory as logo.png (or preserve extension)
            $tmpLogo = $this->scriptDir . '/tmp/logo.' . $extension;

            if (copy($latestLogo, $tmpLogo)) {
                error_log("Logo prepared for deployment: " . basename($latestLogo) . " -> " . basename($tmpLogo));
            } else {
                error_log("Failed to copy logo for deployment: " . $latestLogo);
            }

        } catch (Exception $e) {
            error_log("Logo preparation failed: " . $e->getMessage());
        }
    }

    /**
     * Reset deployment status to idle
     */
    public function resetDeployment()
    {
        $statusFile = $this->scriptDir . '/tmp/deployment_status.json';

        // IMPORTANT: Preserve clickup_task_id if it exists
        // This is set by the web interface before deployment starts
        $clickupTaskId = null;
        if (file_exists($statusFile)) {
            $existingStatus = json_decode(file_get_contents($statusFile), true);
            if (isset($existingStatus['clickup_task_id']) && ! empty($existingStatus['clickup_task_id'])) {
                $clickupTaskId = $existingStatus['clickup_task_id'];
                error_log("DeploymentManager: Preserving ClickUp Task ID during reset: {$clickupTaskId}");
            }
        }

        $resetStatus = [
            'status'    => 'idle',
            'step'      => '',
            'message'   => 'Ready for deployment',
            'timestamp' => time(),
            'logs'      => [],
        ];

        // Add clickup_task_id back if it was present
        if ($clickupTaskId) {
            $resetStatus['clickup_task_id'] = $clickupTaskId;
        }

        file_put_contents($statusFile, json_encode($resetStatus, JSON_PRETTY_PRINT));

        // Clear any existing GitHub run ID tracking
        $runIdFile = $this->scriptDir . '/tmp/github_run_id.txt';
        if (file_exists($runIdFile)) {
            unlink($runIdFile);
        }
    }

    /**
     * Check GitHub Actions workflow status
     */
    public function checkGitHubActionsStatus()
    {
        try {
            // Check if we have a specific run ID to monitor from current deployment
            $runIdFile       = $this->scriptDir . '/tmp/github_run_id.txt';
            $monitoringRunId = null;

            if (file_exists($runIdFile)) {
                $monitoringRunId = trim(file_get_contents($runIdFile));
                if (empty($monitoringRunId) || $monitoringRunId === 'null') {
                    $monitoringRunId = null;
                }
            }

            // Get GitHub configuration
            $gitConfigFile = $this->scriptDir . '/config/git.json';
            if (! file_exists($gitConfigFile)) {
                throw new Exception('Git configuration not found');
            }

            $gitConfig = json_decode(file_get_contents($gitConfigFile), true);
            if (! $gitConfig || ! isset($gitConfig['org']) || ! isset($gitConfig['repo'])) {
                throw new Exception('Invalid git configuration');
            }

            $owner = $gitConfig['org'];
            $repo  = $gitConfig['repo'];
            $token = $gitConfig['token'] ?? getenv('GITHUB_TOKEN');

            if (empty($token)) {
                throw new Exception('GitHub token not configured');
            }

            $headers = [
                "Authorization: token {$token}",
                "Accept: application/vnd.github.v3+json",
                "User-Agent: Framework-Interface/1.0",
            ];

            // If we have a specific run ID to monitor, check that run specifically
            if ($monitoringRunId) {
                $url = "https://api.github.com/repos/{$owner}/{$repo}/actions/runs/{$monitoringRunId}";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200) {
                    $runData = json_decode($response, true);
                    if ($runData) {
                        $status     = $runData['status'] ?? 'unknown';
                        $conclusion = $runData['conclusion'];
                        $htmlUrl    = $runData['html_url'] ?? null;
                        $createdAt  = $runData['created_at'] ?? null;

                        // Fetch job ID for direct link to deployment logs
                        $jobId = $this->getGitHubActionsJobId($monitoringRunId, $token, $owner, $repo);

                        // Include repository owner/repo info so the UI can build fallback links if needed
                        $result          = $this->mapGitHubStatusToDeployment($status, $conclusion, $htmlUrl, $createdAt, $monitoringRunId, true);
                        $result['owner'] = $owner;
                        $result['repo']  = $repo;
                        $result['job_id'] = $jobId;

                        return $result;
                    }
                }

                // If we can't find the specific run, it might be too new or there's an error
                // Fall back to checking recent runs
            }

            // If no specific run ID or couldn't find it, check recent runs
            $url = "https://api.github.com/repos/{$owner}/{$repo}/actions/workflows/deploy.yml/runs?per_page=5";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception("GitHub API returned status {$httpCode}");
            }

            $data = json_decode($response, true);
            if (! $data || ! isset($data['workflow_runs']) || empty($data['workflow_runs'])) {
                return [
                    'status'            => 'pending',
                    'message'           => 'Waiting for GitHub Actions workflow to start...',
                    'url'               => null,
                    'created_at'        => null,
                    'run_id'            => null,
                    'github_status'     => null,
                    'github_conclusion' => null,
                ];
            }

            // Look for a recent run (within last 10 minutes) that might be ours
            $recentRuns    = $data['workflow_runs'];
            $tenMinutesAgo = time() - 600;

            foreach ($recentRuns as $run) {
                $runCreatedAt = strtotime($run['created_at']);

                // If we have a specific run ID, only return that one
                if ($monitoringRunId && $run['id'] == $monitoringRunId) {
                    $status     = $run['status'] ?? 'unknown';
                    $conclusion = $run['conclusion'];
                    $htmlUrl    = $run['html_url'] ?? null;
                    $createdAt  = $run['created_at'] ?? null;

                    // Fetch job ID for direct link to deployment logs
                    $jobId = $this->getGitHubActionsJobId($monitoringRunId, $token, $owner, $repo);

                    $result          = $this->mapGitHubStatusToDeployment($status, $conclusion, $htmlUrl, $createdAt, $run['id'], true);
                    $result['owner'] = $owner;
                    $result['repo']  = $repo;
                    $result['job_id'] = $jobId;

                    return $result;
                }

                // If no specific run ID, look for recent runs
                if (! $monitoringRunId && $runCreatedAt >= $tenMinutesAgo) {
                    $status     = $run['status'] ?? 'unknown';
                    $conclusion = $run['conclusion'];
                    $htmlUrl    = $run['html_url'] ?? null;
                    $createdAt  = $run['created_at'] ?? null;

                    // Store this run ID for future monitoring
                    file_put_contents($runIdFile, $run['id']);

                    // Fetch job ID for direct link to deployment logs
                    $jobId = $this->getGitHubActionsJobId($run['id'], $token, $owner, $repo);

                    $result          = $this->mapGitHubStatusToDeployment($status, $conclusion, $htmlUrl, $createdAt, $run['id'], false);
                    $result['owner'] = $owner;
                    $result['repo']  = $repo;
                    $result['job_id'] = $jobId;

                    return $result;
                }
            }

            // No recent runs found - still waiting for GitHub Actions to start
            return [
                'status'  => 'pending',
                'message' => 'Waiting for GitHub Actions workflow to start...',
                'url'     => "https://github.com/{$owner}/{$repo}/actions",
                'created_at' => null,
                'run_id' => null,
                'github_status' => null,
                'github_conclusion' => null,
                'owner' => $owner,
                'repo' => $repo,
            ];

        } catch (Exception $e) {
            error_log("GitHub Actions status check failed: " . $e->getMessage());
            return [
                'status'  => 'error',
                'message' => 'Failed to check GitHub Actions status: ' . $e->getMessage(),
                'url'     => null,
            ];
        }
    }

    /**
     * Map GitHub workflow status to deployment status
     */
    private function mapGitHubStatusToDeployment($status, $conclusion, $htmlUrl, $createdAt, $runId, $isMonitoring)
    {
        // Map GitHub status to our deployment status
        $deploymentStatus = 'running';
        $message          = 'GitHub Actions workflow in progress';

        if ($status === 'queued') {
            $deploymentStatus = 'running';
            $message          = 'GitHub Actions workflow queued, waiting to start...';
        } elseif ($status === 'in_progress') {
            $deploymentStatus = 'running';
            $message          = 'GitHub Actions deployment in progress...';
        } elseif ($status === 'completed') {
            if ($conclusion === 'success') {
                $deploymentStatus = 'completed';
                $message          = 'GitHub Actions deployment completed successfully';
            } elseif ($conclusion === 'failure') {
                $deploymentStatus = 'failed';
                $message          = 'GitHub Actions deployment failed';
            } elseif ($conclusion === 'cancelled') {
                $deploymentStatus = 'cancelled';
                $message          = 'GitHub Actions deployment was cancelled';
            } else {
                $deploymentStatus = 'failed';
                $message          = "GitHub Actions deployment completed with status: {$conclusion}";
            }
        }

        return [
            'status'            => $deploymentStatus,
            'message'           => $message,
            'url'               => $htmlUrl,
            'created_at'        => $createdAt,
            'run_id'            => $runId,
            'github_status'     => $status,
            'github_conclusion' => $conclusion,
            'is_monitoring'     => $isMonitoring,
        ];
    }/**
     * Get GitHub Actions workflow logs
     */
    public function getGitHubActionsLogs($runId = null)
    {
        try {
            // Get GitHub configuration
            $gitConfigFile = $this->scriptDir . '/config/git.json';
            if (! file_exists($gitConfigFile)) {
                throw new Exception('Git configuration not found');
            }

            $gitConfig = json_decode(file_get_contents($gitConfigFile), true);
            if (! $gitConfig || ! isset($gitConfig['org']) || ! isset($gitConfig['repo'])) {
                throw new Exception('Invalid git configuration');
            }

            $owner = $gitConfig['org'];
            $repo  = $gitConfig['repo'];
            $token = $gitConfig['token'] ?? getenv('GITHUB_TOKEN');

            if (empty($token)) {
                throw new Exception('GitHub token not configured');
            }

            // If no run ID provided, get the latest one
            if (! $runId) {
                $statusData = $this->checkGitHubActionsStatus();
                $runId      = $statusData['run_id'] ?? null;

                if (! $runId) {
                    return ['logs' => [], 'message' => 'No workflow run found'];
                }
            }

            // Get workflow run logs
            $url     = "https://api.github.com/repos/{$owner}/{$repo}/actions/runs/{$runId}/logs";
            $headers = [
                "Authorization: token {$token}",
                "Accept: application/vnd.github.v3+json",
                "User-Agent: Framework-Interface/1.0",
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return [
                    'logs'    => [],
                    'message' => "Could not retrieve logs (HTTP {$httpCode})",
                ];
            }

            // Parse log content (it comes as a ZIP file, but we'll try to extract basic info)
            $logs = [];
            if ($response) {
                // For now, return a simple message indicating logs are available
                $logs[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'level'     => 'INFO',
                    'step'      => 'github-actions',
                    'message'   => 'GitHub Actions logs downloaded successfully. View detailed logs in GitHub.',
                ];
            }

            return [
                'logs'    => $logs,
                'message' => 'GitHub Actions logs retrieved',
            ];

        } catch (Exception $e) {
            error_log("GitHub Actions logs retrieval failed: " . $e->getMessage());
            return [
                'logs'    => [],
                'message' => 'Failed to retrieve GitHub Actions logs: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get GitHub Actions job ID for a specific run
     * This allows us to link directly to the deployment logs
     */
    private function getGitHubActionsJobId($runId, $token, $owner, $repo)
    {
        try {
            if (!$runId) {
                return null;
            }

            $url = "https://api.github.com/repos/{$owner}/{$repo}/actions/runs/{$runId}/jobs";
            $headers = [
                "Authorization: token {$token}",
                "Accept: application/vnd.github.v3+json",
                "User-Agent: Framework-Interface/1.0",
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                error_log("Failed to fetch GitHub job ID: HTTP {$httpCode}");
                return null;
            }

            $data = json_decode($response, true);
            if ($data && isset($data['jobs']) && !empty($data['jobs'])) {
                // Get the first job (usually "deploy" job)
                $firstJob = $data['jobs'][0];
                return $firstJob['id'] ?? null;
            }

            return null;
        } catch (Exception $e) {
            error_log("GitHub Actions job ID retrieval failed: " . $e->getMessage());
            return null;
        }
    }
}
