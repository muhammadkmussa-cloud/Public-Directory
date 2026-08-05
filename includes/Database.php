<?php
/**
 * Umma Directory - Database Connection Class
 * PDO-based database connection with prepared statements
 */

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error but don't display details in production
            error_log("Database connection failed: " . $e->getMessage());
            if (APP_ENV === 'development') {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please contact support.");
            }
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Execute a query with parameters
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            if (APP_ENV === 'development') {
                throw $e;
            }
            return false;
        }
    }
    
    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return false;
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }
    
    /**
     * Insert and return last insert ID
     */
    public function insert($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $this->connection->lastInsertId();
        }
        return false;
    }
    
    /**
     * Update and return affected rows
     */
    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->rowCount();
        }
        return 0;
    }
    
    /**
     * Delete and return affected rows
     */
    public function delete($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->rowCount();
        }
        return 0;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Escape string for LIKE queries
     */
    public function escapeLike($string) {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $string);
    }
}
