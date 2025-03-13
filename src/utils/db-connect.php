<?php
/**
 * Database Connection Class
 * Last Modified: 2025-03-05 17:06:29
 * Author: Devinbeater
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Configuration constants - Move these to a separate config file in production
    private const CONFIG = [
        'host' => 'localhost',
        'database' => 'netshield_pro',
        'username' => 'root',
        'password' => '', // Use environment variable in production
        'charset' => 'utf8mb4',
        'timezone' => '+00:00'
    ];

    // Current timestamp and user tracking
    private $currentDate;
    private $currentUser;

    private function __construct() {
        $this->currentDate = '2025-03-05 17:06:29';
        $this->currentUser = 'Devinbeater';

        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                self::CONFIG['host'],
                self::CONFIG['database'],
                self::CONFIG['charset']
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '" . self::CONFIG['timezone'] . "'",
                PDO::ATTR_PERSISTENT => true
            ];

            $this->connection = new PDO(
                $dsn,
                self::CONFIG['username'],
                self::CONFIG['password'],
                $options
            );

            // Log successful connection
            $this->logConnection();

        } catch (PDOException $e) {
            $this->logError($e);
            throw new Exception("Database connection failed. Please contact system administrator.");
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get database connection
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Log database connections
     */
    private function logConnection(): void {
        try {
            $query = '
                INSERT INTO connection_logs (
                    user_id,
                    connection_time,
                    ip_address,
                    user_agent,
                    environment
                ) VALUES (?, ?, ?, ?, ?)
            ';

            $stmt = $this->connection->prepare($query);
            
            $stmt->execute([
                $_SESSION['user']['id'] ?? null,
                $this->currentDate,
                $this->sanitizeInput($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
                $this->sanitizeInput($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'),
                PHP_SAPI
            ]);

        } catch (PDOException $e) {
            error_log("Connection log failed: " . $e->getMessage());
        }
    }

    /**
     * Log database errors
     */
    private function logError(PDOException $e): void {
        $errorLog = [
            'timestamp' => $this->currentDate,
            'user' => $this->currentUser,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'ip' => $this->sanitizeInput($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'environment' => PHP_SAPI,
            'php_version' => PHP_VERSION
        ];

        error_log(
            json_encode($errorLog, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
    }

    /**
     * Basic input sanitization
     */
    private function sanitizeInput(string $input): string {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Create the connection_logs table if it doesn't exist
     */
    public function initializeLogsTable(): void {
        try {
            $query = "
                CREATE TABLE IF NOT EXISTS connection_logs (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id BIGINT UNSIGNED NULL,
                    connection_time DATETIME NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent VARCHAR(255) NOT NULL,
                    environment VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_connection_time (connection_time)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $this->connection->exec($query);
        } catch (PDOException $e) {
            $this->logError($e);
        }
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialize
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Usage example:
try {
    $db = Database::getInstance();
    $db->initializeLogsTable();
} catch (Exception $e) {
    // Handle the error appropriately
    error_log($e->getMessage());
    die("A database error occurred. Please try again later.");
}