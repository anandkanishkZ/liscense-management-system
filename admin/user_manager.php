<?php
/**
 * User Management and Debug Tool
 * Use this to check existing users and create/reset admin account
 */

// Load database credentials from .env file
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

$message = '';
$error = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['create_admin'])) {
            // Create/Update admin user
            $username = 'admin';
            $password = 'admin123';
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Check if admin user exists
            $stmt = $pdo->prepare("SELECT id FROM zwicky_admin_users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                // Update existing admin user
                $stmt = $pdo->prepare("UPDATE zwicky_admin_users SET password_hash = ?, status = 'active' WHERE username = ?");
                $stmt->execute([$passwordHash, $username]);
                $message = "Admin user password reset successfully! Username: admin, Password: admin123";
            } else {
                // Create new admin user
                $stmt = $pdo->prepare("INSERT INTO zwicky_admin_users (username, password_hash, email, full_name, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $passwordHash, 'admin@zwickytechnology.com', 'System Administrator', 'active']);
                $message = "Admin user created successfully! Username: admin, Password: admin123";
            }
        }
    }
    
    // Get all users
    $stmt = $pdo->query("SELECT id, username, email, full_name, status, created_at FROM zwicky_admin_users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Zwicky License Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #dc3545; }
        .form-section { margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ User Management - Zwicky License Manager</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-section">
            <h2>Reset/Create Admin User</h2>
            <p>Click the button below to create or reset the admin user account:</p>
            <form method="POST">
                <button type="submit" name="create_admin" class="btn">Create/Reset Admin User</button>
            </form>
            <p><small>This will create/update the admin user with username: <strong>admin</strong> and password: <strong>admin123</strong></small></p>
        </div>
        
        <h2>Existing Users</h2>
        <?php if (!empty($users)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td class="status-<?php echo $user['status']; ?>">
                                <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No users found in the database.</p>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="login_simple.php" class="btn">← Back to Login</a>
        </div>
    </div>
</body>
</html>