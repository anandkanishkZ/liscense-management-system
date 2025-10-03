<?php
/**
 * Zwicky Technology License Management System
 * Admin Login Page
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

// Enable error reporting for debugging (disable in production)
@ini_set('display_errors', 1);
@ini_set('display_startup_errors', 1);
@error_reporting(E_ALL);

// Start output buffering to prevent header issues
ob_start();

// Basic diagnostic (comment out in production)
// echo "<!-- Login page loading... -->";

// Try to include config file - handle missing gracefully
$configPaths = [
    '../config/config.php',
    dirname(__DIR__) . '/config/config.php',
    '../config.php'
];

$configLoaded = false;
foreach ($configPaths as $configPath) {
    if (file_exists($configPath)) {
        require_once $configPath;
        $configLoaded = true;
        break;
    }
}

// If config not found, define basic constants
if (!$configLoaded) {
    if (!defined('LMS_SECURE')) define('LMS_SECURE', true);
    if (!defined('LMS_VERSION')) define('LMS_VERSION', '1.0.0');
    
    // Include database config
    $dbConfigPaths = [
        '../config/database.php',
        dirname(__DIR__) . '/config/database.php'
    ];
    
    $dbConfigLoaded = false;
    foreach ($dbConfigPaths as $dbPath) {
        if (file_exists($dbPath)) {
            require_once $dbPath;
            $dbConfigLoaded = true;
            break;
        }
    }
    
    // If database config not found, try to load .env file directly
    if (!$dbConfigLoaded) {
        $envPaths = [
            '../.env',
            dirname(__DIR__) . '/.env',
            $_SERVER['DOCUMENT_ROOT'] . '/.env'
        ];
        
        foreach($envPaths as $envPath) {
            if (file_exists($envPath) && is_readable($envPath)) {
                try {
                    $envContent = file_get_contents($envPath);
                    if ($envContent !== false) {
                        $lines = explode("\n", $envContent);
                        foreach($lines as $line) {
                            $line = trim($line);
                            if (!empty($line) && strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
                                $parts = explode('=', $line, 2);
                                if (count($parts) === 2) {
                                    $key = trim($parts[0]);
                                    $value = trim($parts[1], '"\'');
                                    if (!defined($key) && !empty($key)) {
                                        define($key, $value);
                                    }
                                }
                            }
                        }
                        break;
                    }
                } catch (Exception $e) {
                    // Silently continue if .env loading fails
                    continue;
                }
            }
        }
    }
}

// Ensure basic database constants are defined
if (!defined('LMS_DB_HOST')) define('LMS_DB_HOST', 'localhost');
if (!defined('LMS_DB_NAME')) define('LMS_DB_NAME', 'license_system');
if (!defined('LMS_DB_USER')) define('LMS_DB_USER', 'root');
if (!defined('LMS_DB_PASS')) define('LMS_DB_PASS', '');
if (!defined('LMS_TABLE_ADMIN_USERS')) define('LMS_TABLE_ADMIN_USERS', 'zwicky_admin_users');

// Try to include the auth class
$authClassPaths = [
    '../classes/LMSAdminAuth.php',
    dirname(__DIR__) . '/classes/LMSAdminAuth.php'
];

$authClassLoaded = false;
foreach ($authClassPaths as $authPath) {
    if (file_exists($authPath)) {
        require_once $authPath;
        $authClassLoaded = true;
        break;
    }
}

// Initialize auth if class is available
if ($authClassLoaded && class_exists('LMSAdminAuth')) {
    $auth = new LMSAdminAuth();
} else {
    // Create a simple fallback auth system
    $auth = null;
}

// Redirect if already logged in (only if auth class is working)
if ($authClassLoaded && $auth && method_exists($auth, 'isAuthenticated') && $auth->isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

// Also check session-based authentication
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error_message = 'Username and password are required';
    } else {
        if ($authClassLoaded && $auth && method_exists($auth, 'login')) {
            // Use the proper auth class
            $result = $auth->login($username, $password, $remember);
            
            if ($result['success']) {
                header('Location: dashboard.php');
                exit;
            } else {
                $error_message = $result['message'];
            }
        } else {
            // Fallback authentication using direct database connection
            try {
                // Get database connection
                if (function_exists('getLMSDatabase')) {
                    $db = getLMSDatabase();
                } else {
                    // Create database connection directly
                    $dsn = "mysql:host=" . (defined('LMS_DB_HOST') ? LMS_DB_HOST : 'localhost') . 
                           ";dbname=" . (defined('LMS_DB_NAME') ? LMS_DB_NAME : 'license_system') . 
                           ";charset=utf8mb4";
                    $db = new PDO($dsn, 
                        defined('LMS_DB_USER') ? LMS_DB_USER : 'root', 
                        defined('LMS_DB_PASS') ? LMS_DB_PASS : '', 
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                }
                
                // Check user credentials
                $table = defined('LMS_TABLE_ADMIN_USERS') ? LMS_TABLE_ADMIN_USERS : 'zwicky_admin_users';
                $stmt = $db->prepare("SELECT id, username, password_hash, status FROM $table WHERE username = ? AND status = 'active'");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Start session and set authentication
                    if (session_status() !== PHP_SESSION_ACTIVE) {
                        session_start();
                    }
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_user_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error_message = 'Invalid username or password';
                }
            } catch (Exception $e) {
                $error_message = 'Authentication system error. Please check your database configuration.';
            }
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
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <!-- Background Elements -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="logo-container">
                    <div class="logo-icon">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div class="logo-text">
                        <h1>Zwicky License Manager</h1>
                        <p>Secure License Management System</p>
                    </div>
                </div>
            </div>
            
            <div class="login-content">
                <div class="welcome-text">
                    <h2>Welcome Back</h2>
                    <p>Please sign in to your admin account</p>
                </div>
                
                <form method="POST" class="login-form">
                    <?php if ($error_message): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><?php echo htmlspecialchars($error_message); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo htmlspecialchars($success_message); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="username" name="username" 
                                   placeholder="Enter your username"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                   required autocomplete="username">
                            <label for="username">Username</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" 
                                   placeholder="Enter your password"
                                   required autocomplete="current-password">
                            <label for="password">Password</label>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="password-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="remember-checkbox">
                            <input type="checkbox" name="remember" id="remember">
                            <span class="checkmark"></span>
                            <span class="checkbox-text">Remember me for 30 days</span>
                        </label>
                        
                        <a href="#" class="forgot-password">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <span class="btn-text">
                            <i class="fas fa-sign-in-alt"></i>
                            Sign In to Dashboard
                        </span>
                        <div class="btn-loader">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </button>
                </form>
                
                <div class="login-divider">
                    <span>or</span>
                </div>
                
                <div class="quick-access">
                    <button type="button" class="btn btn-secondary-outline">
                        <i class="fas fa-fingerprint"></i>
                        Use Biometric Login
                    </button>
                </div>
            </div>
            
            <div class="login-footer">
                <div class="security-badges">
                    <div class="security-badge">
                        <i class="fas fa-shield-halved"></i>
                        <span>SSL Secured</span>
                    </div>
                    <div class="security-badge">
                        <i class="fas fa-lock"></i>
                        <span>256-bit Encryption</span>
                    </div>
                    <div class="security-badge">
                        <i class="fas fa-clock"></i>
                        <span>Auto Logout</span>
                    </div>
                </div>
                
                <div class="system-info">
                    <p>Zwicky License Manager v<?php echo LMS_VERSION; ?></p>
                    <p>© <?php echo date('Y'); ?> Zwicky Technology. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const passwordEye = document.getElementById('password-eye');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordEye.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                passwordEye.className = 'fas fa-eye';
            }
        }
        
        // Enhanced form interactions
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
            const loginBtn = document.querySelector('.btn-login');
            const form = document.querySelector('.login-form');
            
            // Auto-focus on username field
            document.getElementById('username').focus();
            
            // Handle input focus/blur for floating labels
            inputs.forEach(input => {
                // Check if input has value on load
                if (input.value.trim() !== '') {
                    input.classList.add('has-value');
                }
                
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                    if (this.value.trim() !== '') {
                        this.classList.add('has-value');
                    } else {
                        this.classList.remove('has-value');
                    }
                });
                
                input.addEventListener('input', function() {
                    if (this.value.trim() !== '') {
                        this.classList.add('has-value');
                    } else {
                        this.classList.remove('has-value');
                    }
                });
            });
            
            // Enhanced form submission with loading state
            form.addEventListener('submit', function(e) {
                loginBtn.classList.add('loading');
                loginBtn.disabled = true;
                
                // Re-enable button after 5 seconds as fallback
                setTimeout(() => {
                    loginBtn.classList.remove('loading');
                    loginBtn.disabled = false;
                }, 5000);
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl+Enter to submit form
                if (e.ctrlKey && e.key === 'Enter') {
                    if (form.checkValidity()) {
                        form.submit();
                    }
                }
                
                // Escape to clear form
                if (e.key === 'Escape') {
                    form.reset();
                    inputs.forEach(input => {
                        input.classList.remove('has-value');
                        input.parentElement.classList.remove('focused');
                    });
                    document.getElementById('username').focus();
                }
            });
            
            // Animate shapes
            const shapes = document.querySelectorAll('.shape');
            shapes.forEach((shape, index) => {
                shape.style.animationDelay = `${index * 0.5}s`;
            });
        });
        
        // Security: Clear sensitive data
        window.addEventListener('beforeunload', function() {
            document.getElementById('password').value = '';
        });
        
        // Add some interactive effects
        document.addEventListener('mousemove', function(e) {
            const shapes = document.querySelectorAll('.shape');
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 0.02;
                const x = (mouseX - 0.5) * 20 * speed;
                const y = (mouseY - 0.5) * 20 * speed;
                shape.style.transform = `translate(${x}px, ${y}px) rotate(${x}deg)`;
            });
        });
    </script>
</body>
</html>