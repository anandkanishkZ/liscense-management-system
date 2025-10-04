<?php
/**
 * Zwicky Technology License Management System
 * Database Connection Class
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

// Security check
if (!defined('LMS_SECURE')) {
    die('Direct access not allowed');
}

/**
 * Database connection wrapper class
 * Provides a consistent interface for database operations
 */
class Database {
    private $connection;
    
    /**
     * Constructor - establishes database connection
     */
    public function __construct() {
        try {
            $this->connection = getLMSDatabase();
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Unable to connect to database");
        }
    }
    
    /**
     * Get the PDO connection object
     * @return PDO Database connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Close the database connection
     */
    public function close() {
        $this->connection = null;
    }
    
    /**
     * Execute a query and return the statement
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Database query failed");
        }
    }
    
    /**
     * Execute a query and fetch all results
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array Results
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Execute a query and fetch a single row
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array|false Single row or false
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Execute an insert/update/delete query
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return int Number of affected rows
     */
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Get the last inserted ID
     * @return string Last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Begin a transaction
     * @return bool
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * @return bool
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback a transaction
     * @return bool
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
}
