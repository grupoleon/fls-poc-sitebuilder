<?php
/**
 * Database Migration Runner
 *
 * Runs all SQL migrations in the migrations/ directory
 * Safe to run multiple times - uses CREATE TABLE IF NOT EXISTS
 */

// Load DatabaseLogger for connection management
require_once __DIR__ . '/admin/includes/DatabaseLogger.php';

// Set up logging
$logFile = dirname(__DIR__) . '/logs/deployment/migrations.log';
$logDir  = dirname($logFile);

if (! is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logMigration($message, $level = 'INFO')
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry  = "[$timestamp] [$level] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    echo $logEntry;
}

try {
    logMigration('=== Starting Database Migrations ===');

    // Get database connection info from environment
    $dbHost = getenv('DB_HOST');
    $dbUser = getenv('DB_USER');
    $dbPass = getenv('DB_PASS') ?: getenv('DB_PASSWORD');
    $dbName = getenv('DB_NAME') ?: getenv('DB_DATABASE') ?: 'frontline_poc';

    // Validate credentials
    if (empty($dbHost) || empty($dbUser) || empty($dbPass)) {
        $errorMsg  = 'Missing database credentials. ';
        $errorMsg .= 'DB_HOST=' . (empty($dbHost) ? 'MISSING' : 'SET') . ', ';
        $errorMsg .= 'DB_USER=' . (empty($dbUser) ? 'MISSING' : 'SET') . ', ';
        $errorMsg .= 'DB_PASS/DB_PASSWORD=' . (empty($dbPass) ? 'MISSING' : 'SET');
        throw new Exception($errorMsg);
    }

    logMigration("Connecting to database: $dbHost/$dbName as $dbUser");

    // Create PDO connection
    $dsn     = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    logMigration('Database connection established successfully');

    // Get migrations directory
    $migrationsDir = dirname(__DIR__) . '/migrations';

    if (! is_dir($migrationsDir)) {
        throw new Exception("Migrations directory not found: $migrationsDir");
    }

    // Get all SQL files
    $sqlFiles = glob($migrationsDir . '/*.sql');

    if (empty($sqlFiles)) {
        logMigration('No migration files found', 'WARNING');
        exit(0);
    }

    sort($sqlFiles); // Run in alphabetical order

    logMigration('Found ' . count($sqlFiles) . ' migration file(s)');

    // Run each migration
    foreach ($sqlFiles as $sqlFile) {
        $filename = basename($sqlFile);
        logMigration("Running migration: $filename");

        $sql = file_get_contents($sqlFile);

        if (empty($sql)) {
            logMigration("Migration file is empty: $filename", 'WARNING');
            continue;
        }

        // Split by semicolons to handle multiple statements
        // This is a simple split - doesn't handle comments properly but works for our case
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function ($stmt) {
                // Filter out empty statements and comments
                return ! empty($stmt) &&
                ! preg_match('/^\s*--/', $stmt) &&
                strlen(trim($stmt)) > 0;
            }
        );

        $executed = 0;
        $skipped  = 0;

        foreach ($statements as $statement) {
            // Skip comments and empty statements
            if (preg_match('/^\s*(--|\/\*|$)/', $statement)) {
                continue;
            }

            try {
                $pdo->exec($statement . ';');
                $executed++;
            } catch (PDOException $e) {
                // Check if it's a "table already exists" error - that's okay
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    $skipped++;
                    logMigration("  - Statement skipped (already exists)", 'INFO');
                } else {
                    logMigration("  - Failed to execute statement: " . $e->getMessage(), 'ERROR');
                    throw $e;
                }
            }
        }

        logMigration("  - Executed: $executed statement(s), Skipped: $skipped statement(s)", 'SUCCESS');
    }

    logMigration('=== All migrations completed successfully ===', 'SUCCESS');

    // Verify tables were created
    logMigration('Verifying tables...');
    $tables         = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $expectedTables = ['users', 'user_sessions', 'deployments', 'deployment_steps'];

    $foundTables   = [];
    $missingTables = [];

    foreach ($expectedTables as $table) {
        if (in_array($table, $tables)) {
            $foundTables[] = $table;
            logMigration("  ✓ Table exists: $table", 'SUCCESS');
        } else {
            $missingTables[] = $table;
            logMigration("  ✗ Table missing: $table", 'WARNING');
        }
    }

    if (! empty($missingTables)) {
        logMigration('WARNING: Some expected tables are missing: ' . implode(', ', $missingTables), 'WARNING');
    } else {
        logMigration('All expected tables are present', 'SUCCESS');
    }

    exit(0);

} catch (PDOException $e) {
    logMigration('Database error: ' . $e->getMessage(), 'ERROR');
    logMigration('Error code: ' . $e->getCode(), 'ERROR');
    exit(1);
} catch (Exception $e) {
    logMigration('Migration failed: ' . $e->getMessage(), 'ERROR');
    exit(1);
}
