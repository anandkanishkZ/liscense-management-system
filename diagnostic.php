<?php
/**
 * Simple PHP Diagnostic Tool
 * Use this if install.php is showing 500 errors
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Diagnostic - License Management System</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 5px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 5px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin: 5px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 4px; margin: 5px 0; }
        h1, h2 { color: #333; }
        .button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 5px; }
        .button:hover { background: #005a87; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 License Management System - PHP Diagnostic</h1>
        <p>This diagnostic tool helps identify issues preventing the installation from working.</p>
        
        <h2>📊 Server Information</h2>
        <div class="info">
            <ul>
                <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                <li><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></li>
                <li><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></li>
                <li><strong>Current Directory:</strong> <?php echo __DIR__; ?></li>
                <li><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
            </ul>
        </div>
        
        <h2>🔍 Required PHP Extensions</h2>
        <?php
        $required_extensions = [
            'pdo' => 'PDO Database Extension',
            'pdo_mysql' => 'MySQL PDO Driver',
            'json' => 'JSON Support',
            'mbstring' => 'Multibyte String Support',
            'openssl' => 'OpenSSL Support',
            'curl' => 'cURL Support'
        ];
        
        foreach ($required_extensions as $ext => $description) {
            $loaded = extension_loaded($ext);
            $class = $loaded ? 'success' : 'error';
            $icon = $loaded ? '✅' : '❌';
            echo "<div class='$class'>$icon <strong>$ext</strong> - $description</div>";
        }
        ?>
        
        <h2>📁 File System Check</h2>
        <?php
        $files_to_check = [
            'install.php' => 'Installation Script',
            'config/config.php' => 'Main Configuration',
            'config/database.php' => 'Database Configuration',
            'admin/login.php' => 'Admin Login Page',
            'assets/css/admin.css' => 'Admin Styles',
            'classes/LMSLicenseManager.php' => 'License Manager Class'
        ];
        
        foreach ($files_to_check as $file => $description) {
            $exists = file_exists($file);
            $class = $exists ? 'success' : 'warning';
            $icon = $exists ? '✅' : '⚠️';
            echo "<div class='$class'>$icon <strong>$file</strong> - $description</div>";
        }
        ?>
        
        <h2>🔧 Directory Permissions</h2>
        <?php
        $dirs_to_check = [
            '.' => 'Root Directory',
            'config' => 'Configuration Directory',
            'logs' => 'Logs Directory',
            'admin' => 'Admin Directory'
        ];
        
        foreach ($dirs_to_check as $dir => $description) {
            if (is_dir($dir)) {
                $writable = is_writable($dir);
                $class = $writable ? 'success' : 'warning';
                $icon = $writable ? '✅' : '⚠️';
                $perm = substr(sprintf('%o', fileperms($dir)), -4);
                echo "<div class='$class'>$icon <strong>$dir/</strong> - $description (Permissions: $perm)</div>";
            } else {
                echo "<div class='warning'>⚠️ <strong>$dir/</strong> - Directory not found</div>";
            }
        }
        ?>
        
        <h2>🗄️ Database Connection Test</h2>
        <?php
        // Try to test database connection
        try {
            // Check if we can load database configuration
            $config_loaded = false;
            $db_host = 'localhost';
            $db_name = 'license_system';
            $db_user = 'root';
            $db_pass = '';
            
            if (file_exists('config/database.php')) {
                include 'config/database.php';
                $config_loaded = true;
                echo "<div class='success'>✅ Database configuration file found</div>";
            } else {
                echo "<div class='warning'>⚠️ Database configuration file not found - using defaults</div>";
            }
            
            // Try to connect
            if (extension_loaded('pdo_mysql')) {
                try {
                    $dsn = "mysql:host=$db_host;charset=utf8mb4";
                    $pdo = new PDO($dsn, $db_user, $db_pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5
                    ]);
                    echo "<div class='success'>✅ Database connection successful</div>";
                    
                    // Check if database exists
                    try {
                        $pdo->exec("USE `$db_name`");
                        echo "<div class='success'>✅ Database '$db_name' exists and accessible</div>";
                    } catch (Exception $e) {
                        echo "<div class='warning'>⚠️ Database '$db_name' not found - will be created during installation</div>";
                    }
                    
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            } else {
                echo "<div class='error'>❌ PDO MySQL extension not available</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Database test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
        
        <h2>📝 Error Log Information</h2>
        <div class="info">
            <ul>
                <li><strong>Error Reporting Level:</strong> <?php echo error_reporting(); ?></li>
                <li><strong>Display Errors:</strong> <?php echo ini_get('display_errors') ? 'On' : 'Off'; ?></li>
                <li><strong>Log Errors:</strong> <?php echo ini_get('log_errors') ? 'On' : 'Off'; ?></li>
                <li><strong>Error Log File:</strong> <?php echo ini_get('error_log') ?: 'System default'; ?></li>
            </ul>
        </div>
        
        <h2>🚀 Next Steps</h2>
        <div class="info">
            <p><strong>If you see any ❌ red errors above:</strong></p>
            <ol>
                <li>Contact your hosting provider to enable missing PHP extensions</li>
                <li>Check file permissions (should be 644 for files, 755 for directories)</li>
                <li>Verify database credentials in cPanel</li>
                <li>Create the database if it doesn't exist</li>
            </ol>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="install.php" class="button">← Back to Installation</a>
            <a href="?" class="button" style="background: #28a745;">🔄 Refresh Diagnostic</a>
        </div>
        
        <h2>📞 Common Solutions</h2>
        <div class="warning">
            <h4>500 Internal Server Error Solutions:</h4>
            <ul>
                <li><strong>File Permissions:</strong> Set files to 644, directories to 755</li>
                <li><strong>PHP Version:</strong> Ensure PHP 7.4+ is enabled in cPanel</li>
                <li><strong>Missing Extensions:</strong> Enable PDO and MySQL extensions</li>
                <li><strong>Syntax Errors:</strong> Check PHP error logs in cPanel</li>
                <li><strong>Memory Limit:</strong> Increase PHP memory limit if needed</li>
            </ul>
        </div>
    </div>
</body>
</html>