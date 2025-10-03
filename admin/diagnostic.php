<?php
/**
 * Basic PHP Diagnostic Page
 * Use this to test if PHP is working correctly
 */

echo "<h1>PHP Diagnostic</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Check if .env file exists
$envFile = dirname(__DIR__) . '/.env';
echo "<h2>File System Check</h2>";
echo "<p>.env file exists: " . (file_exists($envFile) ? 'YES' : 'NO') . "</p>";
echo "<p>Current directory: " . __DIR__ . "</p>";
echo "<p>Parent directory: " . dirname(__DIR__) . "</p>";

// Try to read .env file
if (file_exists($envFile)) {
    echo "<h2>.env File Contents</h2>";
    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);
    echo "<pre>";
    foreach($lines as $line) {
        if (strpos($line, 'PASS') === false) { // Don't show password
            echo htmlspecialchars($line) . "\n";
        } else {
            echo "LMS_DB_PASS=****\n";
        }
    }
    echo "</pre>";
}

// Test database connection
try {
    $envFile = dirname(__DIR__) . '/.env';
    $dbHost = 'localhost';
    $dbName = 'license_system';
    $dbUser = 'root';
    $dbPass = '';
    
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        if (preg_match('/LMS_DB_HOST=(.+)/', $envContent, $matches)) {
            $dbHost = trim($matches[1], '"\'');
        }
        if (preg_match('/LMS_DB_NAME=(.+)/', $envContent, $matches)) {
            $dbName = trim($matches[1], '"\'');
        }
        if (preg_match('/LMS_DB_USER=(.+)/', $envContent, $matches)) {
            $dbUser = trim($matches[1], '"\'');
        }
        if (preg_match('/LMS_DB_PASS=(.+)/', $envContent, $matches)) {
            $dbPass = trim($matches[1], '"\'');
        }
    }
    
    echo "<h2>Database Connection Test</h2>";
    echo "<p>Host: $dbHost</p>";
    echo "<p>Database: $dbName</p>";
    echo "<p>User: $dbUser</p>";
    
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check if admin users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'zwicky_admin_users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Admin users table exists!</p>";
        
        // Count admin users
        $stmt = $pdo->query("SELECT COUNT(*) FROM zwicky_admin_users");
        $count = $stmt->fetchColumn();
        echo "<p>Admin users count: $count</p>";
    } else {
        echo "<p style='color: red;'>❌ Admin users table does not exist!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}
?>