<?php
echo "Setting up License Management System Database...\n";

try {
    // Connect to database
    $pdo = new PDO('mysql:host=localhost;dbname=license_system', 'root', 'admin123');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully!\n";
    
    // Read and execute schema
    if (file_exists('database_schema.sql')) {
        $sql = file_get_contents('database_schema.sql');
        $pdo->exec($sql);
        echo "✅ Database schema imported successfully!\n";
    } else {
        echo "❌ database_schema.sql file not found!\n";
    }
    
    // Create default admin user
    $username = 'admin';
    $password = 'admin123';
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO zwicky_admin_users (username, password_hash, email, full_name, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$username, $passwordHash, 'admin@localhost.com', 'System Administrator', 'active']);
    
    echo "✅ Default admin user created!\n";
    echo "   Username: admin\n";
    echo "   Password: admin123\n";
    
    echo "\n🚀 Setup complete! You can now:\n";
    echo "   1. Open: http://localhost:8000/install.php\n";
    echo "   2. Or go directly to: http://localhost:8000/admin/login_simple.php\n";
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>