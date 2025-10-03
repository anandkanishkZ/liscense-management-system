<?php
/**
 * Zwicky Technology License Management System
 * Configuration Setup Wizard
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

// Check if .env file already exists
$env_file = __DIR__ . '/.env';
$env_exists = file_exists($env_file);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$env_exists) {
    try {
        $config = [
            'LMS_DB_HOST' => $_POST['db_host'] ?? 'localhost',
            'LMS_DB_NAME' => $_POST['db_name'] ?? 'zwicky_license_system',
            'LMS_DB_USER' => $_POST['db_user'] ?? 'root',
            'LMS_DB_PASS' => $_POST['db_pass'] ?? '',
            'LMS_BASE_URL' => $_POST['base_url'] ?? '',
            'LMS_EMAIL_FROM' => $_POST['email_from'] ?? '',
            'LMS_EMAIL_FROM_NAME' => $_POST['email_from_name'] ?? 'License Management System',
            'LMS_ADMIN_EMAIL' => $_POST['admin_email'] ?? '',
            'LMS_SMTP_HOST' => $_POST['smtp_host'] ?? '',
            'LMS_SMTP_PORT' => $_POST['smtp_port'] ?? '587',
            'LMS_SMTP_USERNAME' => $_POST['smtp_username'] ?? '',
            'LMS_SMTP_PASSWORD' => $_POST['smtp_password'] ?? '',
            'LMS_SMTP_ENCRYPTION' => $_POST['smtp_encryption'] ?? 'tls',
            'LMS_JWT_SECRET' => bin2hex(random_bytes(32)),
            'LMS_ENCRYPTION_KEY' => bin2hex(random_bytes(32)),
            'LMS_DEBUG_MODE' => $_POST['debug_mode'] ?? 'false',
            'LMS_LOG_LEVEL' => $_POST['log_level'] ?? 'INFO'
        ];
        
        $env_content = "# Zwicky Technology License Management System\n";
        $env_content .= "# Generated Configuration - " . date('Y-m-d H:i:s') . "\n\n";
        
        $env_content .= "# Database Configuration\n";
        $env_content .= "LMS_DB_HOST=" . $config['LMS_DB_HOST'] . "\n";
        $env_content .= "LMS_DB_NAME=" . $config['LMS_DB_NAME'] . "\n";
        $env_content .= "LMS_DB_USER=" . $config['LMS_DB_USER'] . "\n";
        $env_content .= "LMS_DB_PASS=" . $config['LMS_DB_PASS'] . "\n\n";
        
        $env_content .= "# Application URLs\n";
        $env_content .= "LMS_BASE_URL=" . $config['LMS_BASE_URL'] . "\n\n";
        
        $env_content .= "# Email Configuration\n";
        $env_content .= "LMS_EMAIL_FROM=" . $config['LMS_EMAIL_FROM'] . "\n";
        $env_content .= "LMS_EMAIL_FROM_NAME=" . $config['LMS_EMAIL_FROM_NAME'] . "\n";
        $env_content .= "LMS_ADMIN_EMAIL=" . $config['LMS_ADMIN_EMAIL'] . "\n\n";
        
        $env_content .= "# SMTP Settings\n";
        $env_content .= "LMS_SMTP_HOST=" . $config['LMS_SMTP_HOST'] . "\n";
        $env_content .= "LMS_SMTP_PORT=" . $config['LMS_SMTP_PORT'] . "\n";
        $env_content .= "LMS_SMTP_USERNAME=" . $config['LMS_SMTP_USERNAME'] . "\n";
        $env_content .= "LMS_SMTP_PASSWORD=" . $config['LMS_SMTP_PASSWORD'] . "\n";
        $env_content .= "LMS_SMTP_ENCRYPTION=" . $config['LMS_SMTP_ENCRYPTION'] . "\n\n";
        
        $env_content .= "# Security Keys (Auto-generated)\n";
        $env_content .= "LMS_JWT_SECRET=" . $config['LMS_JWT_SECRET'] . "\n";
        $env_content .= "LMS_ENCRYPTION_KEY=" . $config['LMS_ENCRYPTION_KEY'] . "\n\n";
        
        $env_content .= "# System Settings\n";
        $env_content .= "LMS_DEBUG_MODE=" . $config['LMS_DEBUG_MODE'] . "\n";
        $env_content .= "LMS_LOG_LEVEL=" . $config['LMS_LOG_LEVEL'] . "\n";
        
        if (file_put_contents($env_file, $env_content)) {
            $message = "Configuration saved successfully! You can now run the installer.";
        } else {
            $error = "Failed to save configuration. Please check file permissions.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Auto-detect base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$auto_base_url = $protocol . $host . $script_dir;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Management System - Configuration Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .btn {
            background: #007cba;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background: #005a87;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .section h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        small {
            color: #6c757d;
        }
        .nav-links {
            text-align: center;
            margin-top: 20px;
        }
        .nav-links a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .nav-links a:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ License Management System Setup</h1>
        
        <?php if ($env_exists): ?>
            <div class="alert alert-info">
                <strong>Configuration Already Exists!</strong><br>
                Your system is already configured. The .env file exists in your system directory.
            </div>
            
            <div class="nav-links">
                <a href="install.php">📦 Run Installer</a>
                <a href="admin/login.php">🔐 Admin Login</a>
            </div>
            
            <div class="section">
                <h3>⚙️ Manual Configuration</h3>
                <p>If you need to update your configuration, you can:</p>
                <ul>
                    <li>Edit the <code>.env</code> file directly</li>
                    <li>Delete the <code>.env</code> file and run this setup again</li>
                    <li>Use environment variables on your server</li>
                </ul>
            </div>
            
        <?php else: ?>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <div class="nav-links">
                    <a href="install.php">📦 Run Installer</a>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="section">
                    <h3>🗄️ Database Configuration</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="db_host">Database Host</label>
                            <input type="text" id="db_host" name="db_host" value="localhost" required>
                            <small>Usually 'localhost' for local development</small>
                        </div>
                        <div class="form-group">
                            <label for="db_name">Database Name</label>
                            <input type="text" id="db_name" name="db_name" value="zwicky_license_system" required>
                            <small>Name of your MySQL database</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="db_user">Database Username</label>
                            <input type="text" id="db_user" name="db_user" value="root" required>
                        </div>
                        <div class="form-group">
                            <label for="db_pass">Database Password</label>
                            <input type="password" id="db_pass" name="db_pass">
                            <small>Leave empty if no password is set</small>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <h3>🌐 Application URLs</h3>
                    <div class="form-group">
                        <label for="base_url">Base URL</label>
                        <input type="url" id="base_url" name="base_url" value="<?php echo htmlspecialchars($auto_base_url); ?>">
                        <small>Leave empty for auto-detection. Include http:// or https://</small>
                    </div>
                </div>
                
                <div class="section">
                    <h3>📧 Email Configuration</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email_from">From Email</label>
                            <input type="email" id="email_from" name="email_from" value="noreply@<?php echo $_SERVER['HTTP_HOST'] ?? 'localhost'; ?>">
                        </div>
                        <div class="form-group">
                            <label for="admin_email">Admin Email</label>
                            <input type="email" id="admin_email" name="admin_email" value="admin@<?php echo $_SERVER['HTTP_HOST'] ?? 'localhost'; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email_from_name">From Name</label>
                        <input type="text" id="email_from_name" name="email_from_name" value="License Management System">
                    </div>
                </div>
                
                <div class="section">
                    <h3>📨 SMTP Configuration (Optional)</h3>
                    <p><small>Configure SMTP settings if you want to send email notifications</small></p>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="smtp_host">SMTP Host</label>
                            <input type="text" id="smtp_host" name="smtp_host" placeholder="smtp.gmail.com">
                        </div>
                        <div class="form-group">
                            <label for="smtp_port">SMTP Port</label>
                            <input type="number" id="smtp_port" name="smtp_port" value="587">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="smtp_username">SMTP Username</label>
                            <input type="text" id="smtp_username" name="smtp_username">
                        </div>
                        <div class="form-group">
                            <label for="smtp_password">SMTP Password</label>
                            <input type="password" id="smtp_password" name="smtp_password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="smtp_encryption">SMTP Encryption</label>
                        <select id="smtp_encryption" name="smtp_encryption">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="">None</option>
                        </select>
                    </div>
                </div>
                
                <div class="section">
                    <h3>🔧 System Settings</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="debug_mode">Debug Mode</label>
                            <select id="debug_mode" name="debug_mode">
                                <option value="false">Disabled (Production)</option>
                                <option value="true">Enabled (Development)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="log_level">Log Level</label>
                            <select id="log_level" name="log_level">
                                <option value="ERROR">ERROR</option>
                                <option value="WARNING">WARNING</option>
                                <option value="INFO" selected>INFO</option>
                                <option value="DEBUG">DEBUG</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn">💾 Save Configuration</button>
            </form>
        <?php endif; ?>
        
        <div class="section">
            <h3>📋 Next Steps</h3>
            <ol>
                <li><strong>Save Configuration:</strong> Fill out the form above and click "Save Configuration"</li>
                <li><strong>Run Installer:</strong> Go to <code>install.php</code> to create database tables</li>
                <li><strong>Access Admin Panel:</strong> Login at <code>admin/login.php</code> with default credentials</li>
                <li><strong>Secure Your System:</strong> Change default passwords and review security settings</li>
            </ol>
        </div>
    </div>
</body>
</html>