<?php

require_once __DIR__ . '/Logger.php';

/**
 * Database
 * Simple database wrapper using PDO with logging
 */
class Database
{
    private static $instance = null;
    private $pdo;
    private $config;

    /**
     * Constructor
     *
     * @param array $config Database configuration
     */
    private function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Get database instance (singleton)
     *
     * @param array|null $config Database configuration
     * @return self
     */
    public static function getInstance(?array $config = null): self
    {
        if (self::$instance === null) {
            if ($config === null) {
                $config = self::getDefaultConfig();
            }
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * Get default database configuration.
     *
     * Supports both standard env var names (DB_USERNAME, DB_DATABASE) and
     * Kinsta-style names (DB_USER, DB_NAME, DB_PASS). When DB_HOST is set,
     * defaults to MySQL instead of SQLite so Kinsta deployments work without
     * needing DB_DRIVER explicitly.
     *
     * @return array
     */
    private static function getDefaultConfig(): array
    {
        $host     = getenv('DB_HOST') ?: 'localhost';
        $username = getenv('DB_USERNAME') ?: getenv('DB_USER') ?: '';
        $password = getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: '';
        $database = getenv('DB_DATABASE') ?: getenv('DB_NAME') ?: '';

        // Auto-detect driver: use MySQL when host credentials are available, else SQLite
        $hasCredentials = ! empty($username) && ! empty($password) && ! empty($database);
        $defaultDriver  = $hasCredentials ? 'mysql' : 'sqlite';
        $driver         = getenv('DB_DRIVER') ?: $defaultDriver;

        return [
            'driver'   => $driver,
            'database' => $driver === 'sqlite'
                ? (getenv('DB_DATABASE') ?: dirname(dirname(__DIR__)) . '/database/app.db')
                : $database,
            'host'     => $host,
            'port'     => getenv('DB_PORT') ?: 3306,
            'username' => $username,
            'password' => $password,
            'charset'  => getenv('DB_CHARSET') ?: 'utf8mb4',
        ];
    }

    /**
     * Connect to database
     */
    private function connect(): void
    {
        try {
            $driver = $this->config['driver'] ?? 'sqlite';

            if ($driver === 'sqlite') {
                $dbPath = $this->config['database'];
                $dbDir  = dirname($dbPath);

                if (! is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }

                $dsn       = "sqlite:{$dbPath}";
                $this->pdo = new PDO($dsn);
            } else {
                $host     = $this->config['host'];
                $port     = $this->config['port'];
                $database = $this->config['database'];
                $charset  = $this->config['charset'] ?? 'utf8mb4';

                $dsn       = "{$driver}:host={$host};port={$port};dbname={$database};charset={$charset}";
                $this->pdo = new PDO(
                    $dsn,
                    $this->config['username'],
                    $this->config['password']
                );
            }

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            Logger::debug("Database connected successfully");
        } catch (\PDOException $e) {
            Logger::error("Database connection failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get PDO instance
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a query
     *
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            Logger::debug("Executing query: {$sql}", ['params' => $params]);

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt;
        } catch (\PDOException $e) {
            Logger::error("Query failed: {$sql}", [
                'error'  => $e->getMessage(),
                'params' => $params,
            ]);
            throw $e;
        }
    }

    /**
     * Fetch all rows
     *
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch single row
     *
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|false
     */
    public function fetch(string $sql, array $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Fetch single value
     *
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return mixed
     */
    public function fetchColumn(string $sql, array $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Insert a record
     *
     * @param string $table Table name
     * @param array $data Data to insert
     * @return int Last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns      = array_keys($data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $params = [];
        foreach ($data as $key => $value) {
            $params[":{$key}"] = $value;
        }

        $this->query($sql, $params);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update records
     *
     * @param string $table Table name
     * @param array $data Data to update
     * @param array $where Where conditions
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, array $where): int
    {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }

        $whereClause = [];
        foreach (array_keys($where) as $column) {
            $whereClause[] = "{$column} = :where_{$column}";
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setClause),
            implode(' AND ', $whereClause)
        );

        $params = [];
        foreach ($data as $key => $value) {
            $params[":{$key}"] = $value;
        }
        foreach ($where as $key => $value) {
            $params[":where_{$key}"] = $value;
        }

        $stmt = $this->query($sql, $params);

        return $stmt->rowCount();
    }

    /**
     * Delete records
     *
     * @param string $table Table name
     * @param array $where Where conditions
     * @return int Number of affected rows
     */
    public function delete(string $table, array $where): int
    {
        $whereClause = [];
        foreach (array_keys($where) as $column) {
            $whereClause[] = "{$column} = :{$column}";
        }

        $sql = sprintf(
            "DELETE FROM %s WHERE %s",
            $table,
            implode(' AND ', $whereClause)
        );

        $params = [];
        foreach ($where as $key => $value) {
            $params[":{$key}"] = $value;
        }

        $stmt = $this->query($sql, $params);

        return $stmt->rowCount();
    }

    /**
     * Check if table exists
     *
     * @param string $table Table name
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        try {
            $driver = $this->config['driver'] ?? 'sqlite';

            if ($driver === 'sqlite') {
                $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=:table";
            } else {
                $sql = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_NAME = :table";
            }

            $result = $this->fetchColumn($sql, [':table' => $table]);
            return $result !== false;
        } catch (\PDOException $e) {
            Logger::error("Failed to check if table exists: {$table}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
        Logger::debug("Transaction started");
    }

    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->pdo->commit();
        Logger::debug("Transaction committed");
    }

    /**
     * Rollback transaction
     */
    public function rollback(): void
    {
        $this->pdo->rollBack();
        Logger::debug("Transaction rolled back");
    }

    /**
     * Execute SQL file (migrations, etc.)
     *
     * @param string $filePath SQL file path
     * @return bool
     */
    public function executeFile(string $filePath): bool
    {
        if (! file_exists($filePath)) {
            Logger::error("SQL file not found: {$filePath}");
            return false;
        }

        try {
            $sql = file_get_contents($filePath);
            $this->pdo->exec($sql);

            Logger::info("SQL file executed successfully: {$filePath}");
            return true;
        } catch (\PDOException $e) {
            Logger::error("Failed to execute SQL file: {$filePath}", ['error' => $e->getMessage()]);
            return false;
        }
    }
}
