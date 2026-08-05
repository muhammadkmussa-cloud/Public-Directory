<?php
/**
 * Umma Directory — PDO Database wrapper (singleton)
 * All SQL goes through prepared statements.
 */

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Raw PDO access (needed for prepared statements with LIMIT binds etc.) */
    public function pdo()
    {
        return $this->pdo;
    }

    /** Prepare + execute, returns PDOStatement */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch a single row (assoc) or null */
    public function fetchOne($sql, $params = [])
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Fetch all rows */
    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /** Fetch a single scalar value */
    public function fetchValue($sql, $params = [])
    {
        $val = $this->query($sql, $params)->fetchColumn();
        return $val === false ? null : $val;
    }

    /** Insert, returns last insert id */
    public function insert($sql, $params = [])
    {
        $this->query($sql, $params);
        return (int)$this->pdo->lastInsertId();
    }

    /** Update / delete, returns affected rows */
    public function execute($sql, $params = [])
    {
        return $this->query($sql, $params)->rowCount();
    }

    /** Escape a string for LIKE patterns */
    public function escapeLike($s)
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $s);
    }

    /* ---- transactions ---- */
    public function begin()  { return $this->pdo->beginTransaction(); }
    public function commit() { return $this->pdo->commit(); }
    public function rollback(){ return $this->pdo->rollBack(); }

    private function __clone() {}
    public function __wakeup() { throw new Exception('Cannot unserialize singleton'); }
}
