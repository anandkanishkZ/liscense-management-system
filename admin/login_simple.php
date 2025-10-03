<?php
/**
 * Simple Login Page for Zwicky License Management System
 * Minimal version for compatibility with shared hosting
 */

// Suppress all errors initially
@error_reporting(0);
@ini_set('display_errors', 0);

// Start session first
session_start();

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Username and password are required';
    } else {
        // Try to connect to database
        try {
            // Load database credentials from .env file
            $envFile = dirname(__DIR__) . '/.env';
            $dbHost = 'localhost';
            $dbName = 'license_system';
            $dbUser = 'root';
            $dbPass = '';
            
            // Debug: Check if .env file exists
            if (!file_exists($envFile)) {
                throw new Exception('.env file not found at: ' . $envFile);
            }
            
            $envContent = file_get_contents($envFile);
            if ($envContent === false) {
                throw new Exception('Could not read .env file');
            }
            
            // Parse .env file with better error checking
            if (preg_match('/LMS_DB_HOST=([^\r\n#]+)/', $envContent, $matches)) {
                $dbHost = trim($matches[1], '"\'');
            }
            if (preg_match('/LMS_DB_NAME=([^\r\n#]+)/', $envContent, $matches)) {
                $dbName = trim($matches[1], '"\'');
            }
            if (preg_match('/LMS_DB_USER=([^\r\n#]+)/', $envContent, $matches)) {
                $dbUser = trim($matches[1], '"\'');
            }
            if (preg_match('/LMS_DB_PASS=([^\r\n#]+)/', $envContent, $matches)) {
                $dbPass = trim($matches[1], '"\'');
            }
            
            // Connect to database
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check user credentials
            $stmt = $pdo->prepare("SELECT id, username, password_hash, status FROM zwicky_admin_users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error_message = 'Invalid username or password';
            }
        } catch (Exception $e) {
            $error_message = 'Database connection error: ' . $e->getMessage() . ' (Host: ' . $dbHost . ', DB: ' . $dbName . ', User: ' . $dbUser . ')';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zwicky License Manager - Admin Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🛡️ Zwicky License Manager</h1>
            <p>Admin Login</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars(isset($_POST['username']) ? $_POST['username'] : ''); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Sign In</button>
        </form>
        
        <div class="footer">
            <p>© <?php echo date('Y'); ?> Zwicky Technology</p>
        </div>
    </div>
</body>
</html>