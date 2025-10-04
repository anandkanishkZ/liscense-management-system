<?php
/**
 * Install Settings Table
 * Run this file once to create the settings table and insert default values
 */

require_once 'config/config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>Installing Settings Table...</h2>";
    
    // Create settings table
    $sql = "CREATE TABLE IF NOT EXISTS `" . LMS_TABLE_PREFIX . "settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `setting_key` varchar(100) NOT NULL,
      `setting_value` text NOT NULL,
      `setting_group` varchar(50) NOT NULL,
      `setting_type` enum('text','number','boolean','email','textarea') DEFAULT 'text',
      `description` varchar(255) DEFAULT NULL,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `updated_by` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `setting_key` (`setting_key`),
      KEY `setting_group` (`setting_group`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->exec($sql);
    echo "<p style='color: green;'>✓ Settings table created successfully</p>";
    
    // Insert default settings
    $defaultSettings = [
        // Email Settings
        ['smtp_host', '', 'email', 'text', 'SMTP server hostname'],
        ['smtp_port', '587', 'email', 'number', 'SMTP server port'],
        ['smtp_username', '', 'email', 'text', 'SMTP username'],
        ['smtp_password', '', 'email', 'text', 'SMTP password (encrypted)'],
        ['smtp_encryption', 'tls', 'email', 'text', 'SMTP encryption (tls/ssl)'],
        ['from_email', '', 'email', 'email', 'From email address'],
        ['from_name', 'Zwicky License Manager', 'email', 'text', 'From name'],
        
        // License Settings
        ['default_license_validity', '365', 'license', 'number', 'Default license validity in days'],
        ['default_max_activations', '1', 'license', 'number', 'Default maximum activations'],
        ['enable_domain_restrictions', '1', 'license', 'boolean', 'Enable domain restrictions'],
        ['license_key_length', '32', 'license', 'number', 'License key length'],
        ['license_key_format', 'XXXXX-XXXXX-XXXXX-XXXXX', 'license', 'text', 'License key format'],
        
        // Notification Settings
        ['notify_license_creation', '1', 'notification', 'boolean', 'Send email on license creation'],
        ['notify_license_expiration', '1', 'notification', 'boolean', 'Send email on license expiration'],
        ['notify_license_activation', '0', 'notification', 'boolean', 'Send email on license activation'],
        ['expiration_warning_days', '7', 'notification', 'number', 'Days before expiration to send warning'],
        ['notify_admin_on_activation', '1', 'notification', 'boolean', 'Notify admin on new activation'],
        
        // Security Settings
        ['enable_rate_limiting', '1', 'security', 'boolean', 'Enable API rate limiting'],
        ['rate_limit_window', '60', 'security', 'number', 'Rate limit window in seconds'],
        ['rate_limit_max_requests', '60', 'security', 'number', 'Maximum requests per window'],
        ['enable_ip_whitelist', '0', 'security', 'boolean', 'Enable IP whitelist'],
        ['allowed_ips', '', 'security', 'textarea', 'Allowed IP addresses (one per line)'],
        
        // System Settings
        ['maintenance_mode', '0', 'system', 'boolean', 'Enable maintenance mode'],
        ['maintenance_message', 'System is under maintenance. Please try again later.', 'system', 'textarea', 'Maintenance mode message'],
        ['enable_logging', '1', 'system', 'boolean', 'Enable system logging'],
        ['log_retention_days', '30', 'system', 'number', 'Log retention period in days'],
        ['timezone', 'UTC', 'system', 'text', 'System timezone']
    ];
    
    $stmt = $conn->prepare("
        INSERT INTO " . LMS_TABLE_PREFIX . "settings 
        (setting_key, setting_value, setting_group, setting_type, description) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE setting_key=setting_key
    ");
    
    $inserted = 0;
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
        $inserted++;
    }
    
    echo "<p style='color: green;'>✓ Inserted $inserted default settings</p>";
    echo "<h3 style='color: green;'>Installation Complete!</h3>";
    echo "<p><a href='admin/settings.php' style='background: #1d4dd4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block;'>Go to Settings Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?>
