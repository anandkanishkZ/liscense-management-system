<?php
/**
 * Zwicky Technology License Management System
 * Database Configuration
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

// Security check
if (!defined('LMS_SECURE')) {
    die('Direct access not allowed');
}

// Load .env file if it exists
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $envVars = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envVars as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue; // Skip comments and invalid lines
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Remove quotes if present
        $value = trim($value, '"\'');
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Database Configuration - Use environment variables with fallbacks
define('LMS_DB_HOST', $_ENV['LMS_DB_HOST'] ?? getenv('LMS_DB_HOST') ?: 'localhost');
define('LMS_DB_NAME', $_ENV['LMS_DB_NAME'] ?? getenv('LMS_DB_NAME') ?: 'zwicky_license_system');
define('LMS_DB_USER', $_ENV['LMS_DB_USER'] ?? getenv('LMS_DB_USER') ?: 'root');
define('LMS_DB_PASS', $_ENV['LMS_DB_PASS'] ?? getenv('LMS_DB_PASS') ?: '');
define('LMS_DB_CHARSET', $_ENV['LMS_DB_CHARSET'] ?? getenv('LMS_DB_CHARSET') ?: 'utf8mb4');

// Table Names
define('LMS_TABLE_LICENSES', 'zwicky_licenses');
define('LMS_TABLE_ACTIVATIONS', 'zwicky_activations');
define('LMS_TABLE_ADMIN_USERS', 'zwicky_admin_users');
define('LMS_TABLE_LOGS', 'zwicky_logs');

// Database connection options
$db_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . LMS_DB_CHARSET,
    PDO::ATTR_PERSISTENT => false,
    PDO::ATTR_TIMEOUT => 30
];

// Create database connection
function getLMSDatabase() {
    global $db_options;
    
    try {
        $dsn = "mysql:host=" . LMS_DB_HOST . ";dbname=" . LMS_DB_NAME . ";charset=" . LMS_DB_CHARSET;
        $pdo = new PDO($dsn, LMS_DB_USER, LMS_DB_PASS, $db_options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}

// Test database connection
function testLMSConnection() {
    try {
        $db = getLMSDatabase();
        return true;
    } catch (Exception $e) {
        return false;
    }
}