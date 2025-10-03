<?php
echo "Testing MySQL Connection...\n";
try {
    $pdo = new PDO('mysql:host=localhost', 'root', 'admin123');
    echo "✅ MySQL connection successful!\n";
    
    // Test creating database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS license_system");
    echo "✅ Database 'license_system' ready!\n";
    
    // Switch to the database
    $pdo->exec("USE license_system");
    echo "✅ Connected to license_system database!\n";
    
} catch(Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
?>