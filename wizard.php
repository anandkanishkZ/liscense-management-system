<?php
/**
 * Zwicky Technology License Management System
 * Interactive Installation Wizard
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 */

session_start();

// Check if already installed
$lockFile = __DIR__ . '/install.lock';
if (file_exists($lockFile)) {
    header('Location: admin/login.php');
    exit;
}

// Installation step
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            $step = 2;
            break;
            
        case 2:
            $_SESSION['db_config'] = [
                'host' => $_POST['db_host'] ?? 'localhost',
                'name' => $_POST['db_name'] ?? '',
                'user' => $_POST['db_user'] ?? '',
                'pass' => $_POST['db_pass'] ?? '',
                'prefix' => $_POST['db_prefix'] ?? 'zwicky_'
            ];
            
            // Test database connection
            try {
                $config = $_SESSION['db_config'];
                $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $config['user'], $config['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $_SESSION['db_test'] = 'success';
                $step = 3;
            } catch (PDOException $e) {
                $_SESSION['db_error'] = $e->getMessage();
            }
            break;
            
        case 3:
            if (isset($_POST['import_db'])) {
                try {
                    $config = $_SESSION['db_config'];
                    $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $config['user'], $config['pass']);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Read and execute SQL file
                    $sql = file_get_contents(__DIR__ . '/database_schema.sql');
                    // Remove comments and split by semicolon
                    $sql = preg_replace('/--.*$/m', '', $sql);
                    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    
                    foreach ($statements as $statement) {
                        if (!empty($statement)) {
                            $pdo->exec($statement);
                        }
                    }
                    
                    $_SESSION['db_imported'] = true;
                    $step = 4;
                } catch (Exception $e) {
                    $_SESSION['import_error'] = $e->getMessage();
                }
            }
            break;
            
        case 4:
            $_SESSION['admin_config'] = [
                'username' => $_POST['admin_username'] ?? 'admin',
                'email' => $_POST['admin_email'] ?? '',
                'password' => $_POST['admin_password'] ?? '',
                'full_name' => $_POST['admin_fullname'] ?? ''
            ];
            
            // Create admin user
            try {
                $config = $_SESSION['db_config'];
                $admin = $_SESSION['admin_config'];
                
                $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $config['user'], $config['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Delete default admin if exists
                $pdo->exec("DELETE FROM zwicky_admin_users WHERE username = 'admin'");
                
                // Insert new admin
                $stmt = $pdo->prepare("
                    INSERT INTO zwicky_admin_users (username, email, password_hash, full_name, role, status) 
                    VALUES (?, ?, ?, ?, 'admin', 'active')
                ");
                $stmt->execute([
                    $admin['username'],
                    $admin['email'],
                    password_hash($admin['password'], PASSWORD_DEFAULT),
                    $admin['full_name']
                ]);
                
                $_SESSION['admin_created'] = true;
                $step = 5;
            } catch (Exception $e) {
                $_SESSION['admin_error'] = $e->getMessage();
            }
            break;
            
        case 5:
            if (isset($_POST['finalize'])) {
                $config = $_SESSION['db_config'];
                
                // Update database.php
                $dbConfigPath = __DIR__ . '/config/database.php';
                $dbConfigContent = file_get_contents($dbConfigPath);
                
                $dbConfigContent = preg_replace(
                    "/define\('DB_HOST',\s*'[^']*'\);/",
                    "define('DB_HOST', '{$config['host']}');",
                    $dbConfigContent
                );
                $dbConfigContent = preg_replace(
                    "/define\('DB_NAME',\s*'[^']*'\);/",
                    "define('DB_NAME', '{$config['name']}');",
                    $dbConfigContent
                );
                $dbConfigContent = preg_replace(
                    "/define\('DB_USER',\s*'[^']*'\);/",
                    "define('DB_USER', '{$config['user']}');",
                    $dbConfigContent
                );
                $dbConfigContent = preg_replace(
                    "/define\('DB_PASS',\s*'[^']*'\);/",
                    "define('DB_PASS', '{$config['pass']}');",
                    $dbConfigContent
                );
                
                file_put_contents($dbConfigPath, $dbConfigContent);
                
                // Create lock file
                file_put_contents($lockFile, date('Y-m-d H:i:s'));
                
                // Clear session
                session_destroy();
                
                // Redirect to login
                header('Location: admin/login.php?installed=1');
                exit;
            }
            break;
    }
}

// Check system requirements
function checkRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Driver' => extension_loaded('pdo_mysql'),
        'mbstring Extension' => extension_loaded('mbstring'),
        'JSON Extension' => extension_loaded('json'),
        'OpenSSL Extension' => extension_loaded('openssl'),
        'cURL Extension' => extension_loaded('curl'),
        'Config Directory Writable' => is_writable(__DIR__ . '/config'),
        'Logs Directory Writable' => is_writable(__DIR__ . '/logs') || mkdir(__DIR__ . '/logs', 0755, true)
    ];
    
    return $requirements;
}

$requirements = checkRequirements();
$allRequirementsMet = !in_array(false, $requirements, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Wizard - Zwicky License Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .install-container {
            width: 100%;
            max-width: 800px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .install-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        
        .install-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .install-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .progress-bar {
            display: flex;
            justify-content: space-between;
            padding: 30px 40px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .progress-step::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e2e8f0;
            z-index: 0;
        }
        
        .progress-step:last-child::after {
            display: none;
        }
        
        .progress-number {
            width: 40px;
            height: 40px;
            background: #e2e8f0;
            color: #64748b;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            position: relative;
            z-index: 1;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }
        
        .progress-step.active .progress-number {
            background: #2563eb;
            color: white;
            transform: scale(1.1);
        }
        
        .progress-step.completed .progress-number {
            background: #10b981;
            color: white;
        }
        
        .progress-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }
        
        .install-content {
            padding: 40px;
        }
        
        .step-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .step-description {
            color: #64748b;
            margin-bottom: 30px;
        }
        
        .requirement-list {
            list-style: none;
        }
        
        .requirement-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }
        
        .requirement-item:hover {
            transform: translateX(5px);
        }
        
        .requirement-item i {
            font-size: 18px;
            margin-right: 12px;
        }
        
        .requirement-item.success {
            background: #f0fdf4;
            color: #15803d;
        }
        
        .requirement-item.error {
            background: #fef2f2;
            color: #dc2626;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-help {
            font-size: 13px;
            color: #64748b;
            margin-top: 6px;
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #86efac;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fca5a5;
        }
        
        .alert-info {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #93c5fd;
        }
        
        .alert-warning {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fcd34d;
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            flex: 1;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn:disabled:hover {
            transform: none;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 40px;
            animation: scaleIn 0.5s ease;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        
        .info-box {
            background: #f8fafc;
            border-left: 4px solid #2563eb;
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .info-box h4 {
            color: #1e293b;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .info-box p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .info-box ul {
            margin: 10px 0 0 20px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <!-- Header -->
        <div class="install-header">
            <h1><i class="fas fa-shield-halved"></i> Zwicky License Manager</h1>
            <p>Installation Wizard v1.0.0</p>
        </div>
        
        <!-- Progress Bar -->
        <div class="progress-bar">
            <div class="progress-step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                <div class="progress-number">1</div>
                <div class="progress-label">Requirements</div>
            </div>
            <div class="progress-step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                <div class="progress-number">2</div>
                <div class="progress-label">Database</div>
            </div>
            <div class="progress-step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                <div class="progress-number">3</div>
                <div class="progress-label">Import</div>
            </div>
            <div class="progress-step <?php echo $step >= 4 ? 'active' : ''; ?> <?php echo $step > 4 ? 'completed' : ''; ?>">
                <div class="progress-number">4</div>
                <div class="progress-label">Admin</div>
            </div>
            <div class="progress-step <?php echo $step >= 5 ? 'active' : ''; ?>">
                <div class="progress-number">5</div>
                <div class="progress-label">Finish</div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="install-content">
            <?php if ($step == 1): ?>
                <!-- Step 1: Requirements Check -->
                <h2 class="step-title">System Requirements Check</h2>
                <p class="step-description">Before we begin, let's make sure your server meets all the requirements.</p>
                
                <ul class="requirement-list">
                    <?php foreach ($requirements as $name => $met): ?>
                        <li class="requirement-item <?php echo $met ? 'success' : 'error'; ?>">
                            <i class="fas fa-<?php echo $met ? 'check-circle' : 'times-circle'; ?>"></i>
                            <span><?php echo $name; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if (!$allRequirementsMet): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Some requirements are not met. Please fix the issues above before continuing.</span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span>All requirements are met! You're ready to proceed.</span>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary" <?php echo !$allRequirementsMet ? 'disabled' : ''; ?>>
                            Next: Database Configuration
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step == 2): ?>
                <!-- Step 2: Database Configuration -->
                <h2 class="step-title">Database Configuration</h2>
                <p class="step-description">Enter your database connection details. Make sure the database exists before continuing.</p>
                
                <?php if (isset($_SESSION['db_error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Database Connection Failed: <?php echo htmlspecialchars($_SESSION['db_error']); unset($_SESSION['db_error']); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" id="db_host" name="db_host" class="form-control" value="localhost" required>
                        <div class="form-help">Usually "localhost" for shared hosting</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Database Name</label>
                        <input type="text" id="db_name" name="db_name" class="form-control" value="<?php echo $_SESSION['db_config']['name'] ?? ''; ?>" required>
                        <div class="form-help">The name of your MySQL database</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">Database Username</label>
                        <input type="text" id="db_user" name="db_user" class="form-control" value="<?php echo $_SESSION['db_config']['user'] ?? ''; ?>" required>
                        <div class="form-help">MySQL username with full database privileges</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">Database Password</label>
                        <input type="password" id="db_pass" name="db_pass" class="form-control" value="<?php echo $_SESSION['db_config']['pass'] ?? ''; ?>">
                        <div class="form-help">MySQL user password (leave empty if none)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_prefix">Table Prefix</label>
                        <input type="text" id="db_prefix" name="db_prefix" class="form-control" value="zwicky_" required>
                        <div class="form-help">Prefix for all database tables (default: zwicky_)</div>
                    </div>
                    
                    <div class="btn-group">
                        <a href="?step=1" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Test Connection & Continue
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step == 3): ?>
                <!-- Step 3: Database Import -->
                <h2 class="step-title">Import Database Schema</h2>
                <p class="step-description">Click the button below to import the database structure.</p>
                
                <?php if (isset($_SESSION['db_test']) && $_SESSION['db_test'] === 'success'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span>Database connection successful!</span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['import_error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Import Failed: <?php echo htmlspecialchars($_SESSION['import_error']); unset($_SESSION['import_error']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['db_imported']) && $_SESSION['db_imported']): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span>Database imported successfully! All tables have been created.</span>
                    </div>
                    
                    <div class="btn-group">
                        <a href="?step=4" class="btn btn-primary">
                            Next: Create Admin Account
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="info-box">
                        <h4><i class="fas fa-info-circle"></i> What will be imported?</h4>
                        <ul>
                            <li>License management tables</li>
                            <li>License activation tracking</li>
                            <li>Admin user management</li>
                            <li>Activity logging system</li>
                            <li>System settings configuration</li>
                            <li>Default data and indexes</li>
                        </ul>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="import_db" value="1">
                        <div class="btn-group">
                            <a href="?step=2" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-database"></i>
                                Import Database Schema
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
                
            <?php elseif ($step == 4): ?>
                <!-- Step 4: Admin Account -->
                <h2 class="step-title">Create Admin Account</h2>
                <p class="step-description">Create your administrator account to access the system.</p>
                
                <?php if (isset($_SESSION['admin_error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Error: <?php echo htmlspecialchars($_SESSION['admin_error']); unset($_SESSION['admin_error']); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" onsubmit="return validatePasswords()">
                    <div class="form-group">
                        <label for="admin_fullname">Full Name</label>
                        <input type="text" id="admin_fullname" name="admin_fullname" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_username">Username</label>
                        <input type="text" id="admin_username" name="admin_username" class="form-control" value="admin" required>
                        <div class="form-help">Used for login</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">Email Address</label>
                        <input type="email" id="admin_email" name="admin_email" class="form-control" required>
                        <div class="form-help">For notifications and password recovery</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">Password</label>
                        <input type="password" id="admin_password" name="admin_password" class="form-control" minlength="8" required>
                        <div class="form-help">Minimum 8 characters (strong password recommended)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password_confirm">Confirm Password</label>
                        <input type="password" id="admin_password_confirm" name="admin_password_confirm" class="form-control" required>
                    </div>
                    
                    <div class="btn-group">
                        <a href="?step=3" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Create Admin Account
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
                
                <script>
                function validatePasswords() {
                    const pass = document.getElementById('admin_password').value;
                    const confirm = document.getElementById('admin_password_confirm').value;
                    
                    if (pass !== confirm) {
                        alert('Passwords do not match!');
                        return false;
                    }
                    
                    if (pass.length < 8) {
                        alert('Password must be at least 8 characters long!');
                        return false;
                    }
                    
                    return true;
                }
                </script>
                
            <?php elseif ($step == 5): ?>
                <!-- Step 5: Completion -->
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                
                <h2 class="step-title" style="text-align: center;">Installation Complete!</h2>
                <p class="step-description" style="text-align: center;">Your Zwicky License Manager has been successfully installed.</p>
                
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>All installation steps completed successfully!</span>
                </div>
                
                <div class="info-box">
                    <h4><i class="fas fa-user-shield"></i> Your Admin Credentials</h4>
                    <p>
                        <strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['admin_config']['username']); ?><br>
                        <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['admin_config']['email']); ?>
                    </p>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><strong>Security Notice:</strong> For security reasons, please delete wizard.php after installation.</span>
                </div>
                
                <div class="info-box">
                    <h4><i class="fas fa-list-check"></i> Next Steps</h4>
                    <ul>
                        <li>Click "Complete Installation" to finalize setup</li>
                        <li>Login with your admin credentials</li>
                        <li>Configure system settings (email, SMTP, etc.)</li>
                        <li>Create your first license</li>
                        <li>Review security settings</li>
                        <li><strong>Delete wizard.php file</strong></li>
                    </ul>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="finalize" value="1">
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-rocket"></i>
                            Complete Installation & Go to Login
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>