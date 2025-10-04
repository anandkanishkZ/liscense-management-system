-- ================================================
-- Zwicky Technology License Management System
-- Complete Database Schema for Production
-- Version: 1.0.0
-- Author: Zwicky Technology
-- Date: October 2025
-- ================================================

-- Set character set and collation
SET NAMES utf8mb4;
SET character_set_client = utf8mb4;

-- ================================================
-- TABLE 1: LICENSE MANAGEMENT
-- Core table for managing software licenses
-- ================================================

CREATE TABLE IF NOT EXISTS `zwicky_licenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `license_key` VARCHAR(35) UNIQUE NOT NULL COMMENT 'Unique license key identifier',
    `product_name` VARCHAR(100) NOT NULL COMMENT 'Product this license is for',
    `customer_name` VARCHAR(100) NOT NULL COMMENT 'Customer full name',
    `customer_email` VARCHAR(100) NOT NULL COMMENT 'Customer email address',
    `allowed_domains` TEXT COMMENT 'JSON array of allowed domains',
    `max_activations` INT DEFAULT 1 COMMENT 'Maximum number of activations allowed',
    `current_activations` INT DEFAULT 0 COMMENT 'Current number of active activations',
    `status` ENUM('active', 'suspended', 'expired') DEFAULT 'active' COMMENT 'License status',
    `expires_at` DATETIME NULL COMMENT 'License expiration date',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    `notes` TEXT COMMENT 'Additional notes about the license',
    
    -- Performance Indexes
    INDEX `idx_license_key` (`license_key`),
    INDEX `idx_customer_email` (`customer_email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_expires_at` (`expires_at`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_product_name` (`product_name`),
    
    -- Composite indexes for common queries
    INDEX `idx_status_expires` (`status`, `expires_at`),
    INDEX `idx_customer_status` (`customer_email`, `status`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Main license management table';

-- ================================================
-- TABLE 2: LICENSE ACTIVATIONS
-- Track where and how licenses are activated
-- ================================================

CREATE TABLE IF NOT EXISTS `zwicky_activations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `license_id` INT NOT NULL COMMENT 'Reference to license table',
    `domain` VARCHAR(255) NOT NULL COMMENT 'Domain where license is activated',
    `ip_address` VARCHAR(45) COMMENT 'IP address of activation',
    `user_agent` TEXT COMMENT 'Browser/client user agent string',
    `activation_token` VARCHAR(64) UNIQUE COMMENT 'Unique activation token',
    `status` ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Activation status',
    `last_check` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Last validation check',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Activation timestamp',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    -- Foreign key constraints
    FOREIGN KEY (`license_id`) REFERENCES `zwicky_licenses`(`id`) ON DELETE CASCADE,
    
    -- Unique constraints
    UNIQUE KEY `unique_license_domain` (`license_id`, `domain`),
    
    -- Performance indexes
    INDEX `idx_license_id` (`license_id`),
    INDEX `idx_domain` (`domain`),
    INDEX `idx_activation_token` (`activation_token`),
    INDEX `idx_status` (`status`),
    INDEX `idx_last_check` (`last_check`),
    INDEX `idx_ip_address` (`ip_address`),
    
    -- Composite indexes
    INDEX `idx_license_status` (`license_id`, `status`),
    INDEX `idx_domain_status` (`domain`, `status`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='License activation tracking table';

-- ================================================
-- TABLE 3: ADMIN USERS
-- System administrators and their access control
-- ================================================

CREATE TABLE IF NOT EXISTS `zwicky_admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Admin username for login',
    `email` VARCHAR(100) UNIQUE NOT NULL COMMENT 'Admin email address',
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hashed password',
    `full_name` VARCHAR(100) COMMENT 'Admin full name',
    `role` ENUM('admin', 'manager', 'viewer') DEFAULT 'admin' COMMENT 'User role and permissions',
    `status` ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Account status',
    `last_login` TIMESTAMP NULL COMMENT 'Last successful login',
    `login_attempts` INT DEFAULT 0 COMMENT 'Failed login attempt counter',
    `locked_until` TIMESTAMP NULL COMMENT 'Account lock expiration',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Account creation timestamp',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    -- Security and performance indexes
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_role` (`role`),
    INDEX `idx_last_login` (`last_login`),
    INDEX `idx_locked_until` (`locked_until`),
    
    -- Composite indexes for authentication
    INDEX `idx_username_status` (`username`, `status`),
    INDEX `idx_email_status` (`email`, `status`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin user management table';

-- ================================================
-- TABLE 4: SYSTEM ACTIVITY LOGS
-- Complete audit trail of all system activities
-- ================================================

CREATE TABLE IF NOT EXISTS `zwicky_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL COMMENT 'Reference to admin user (NULL for system actions)',
    `action` VARCHAR(100) NOT NULL COMMENT 'Action performed (CREATE, UPDATE, DELETE, etc.)',
    `table_name` VARCHAR(50) COMMENT 'Database table affected',
    `record_id` INT NULL COMMENT 'ID of affected record',
    `old_values` JSON NULL COMMENT 'Previous values before change',
    `new_values` JSON NULL COMMENT 'New values after change',
    `ip_address` VARCHAR(45) COMMENT 'IP address of user',
    `user_agent` TEXT COMMENT 'Browser/client user agent',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Log entry timestamp',
    
    -- Foreign key for user tracking (allows NULL for system actions)
    FOREIGN KEY (`user_id`) REFERENCES `zwicky_admin_users`(`id`) ON DELETE SET NULL,
    
    -- Performance indexes for log queries
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_table_name` (`table_name`),
    INDEX `idx_record_id` (`record_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_ip_address` (`ip_address`),
    
    -- Composite indexes for common log searches
    INDEX `idx_user_action_date` (`user_id`, `action`, `created_at`),
    INDEX `idx_table_record_date` (`table_name`, `record_id`, `created_at`),
    INDEX `idx_action_date` (`action`, `created_at`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System activity and audit log table';

-- ================================================
-- TABLE 5: SYSTEM SETTINGS
-- Application configuration and preferences
-- ================================================

CREATE TABLE IF NOT EXISTS `zwicky_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) UNIQUE NOT NULL COMMENT 'Unique setting identifier',
    `setting_value` TEXT COMMENT 'Setting value (can be string, number, or JSON)',
    `setting_type` ENUM('text', 'number', 'boolean', 'json', 'email', 'url') DEFAULT 'text' COMMENT 'Data type of the setting',
    `setting_group` VARCHAR(50) NOT NULL COMMENT 'Group/category (system, email, license, notification, security)',
    `setting_label` VARCHAR(100) COMMENT 'Human-readable label',
    `setting_description` TEXT COMMENT 'Description of what this setting controls',
    `is_editable` TINYINT(1) DEFAULT 1 COMMENT 'Whether setting can be edited via UI',
    `is_sensitive` TINYINT(1) DEFAULT 0 COMMENT 'Whether setting contains sensitive data',
    `default_value` TEXT COMMENT 'Default value for this setting',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Setting creation timestamp',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    -- Performance indexes
    INDEX `idx_setting_key` (`setting_key`),
    INDEX `idx_setting_group` (`setting_group`),
    INDEX `idx_setting_type` (`setting_type`),
    INDEX `idx_is_editable` (`is_editable`),
    
    -- Composite indexes for common queries
    INDEX `idx_group_key` (`setting_group`, `setting_key`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System configuration and settings table';

-- ================================================
-- DEFAULT SETTINGS INSERTION
-- Initialize system with default configuration values
-- ================================================

INSERT IGNORE INTO `zwicky_settings` (`setting_key`, `setting_value`, `setting_type`, `setting_group`, `setting_label`, `setting_description`, `is_editable`, `is_sensitive`, `default_value`) VALUES

-- SYSTEM SETTINGS
('site_name', 'Zwicky License Manager', 'text', 'system', 'Site Name', 'The name of your license management system', 1, 0, 'Zwicky License Manager'),
('site_url', 'http://localhost/license-management-system', 'url', 'system', 'Site URL', 'The base URL of your application', 1, 0, 'http://localhost/license-management-system'),
('timezone', 'America/New_York', 'text', 'system', 'Timezone', 'Default timezone for the system', 1, 0, 'America/New_York'),
('date_format', 'Y-m-d H:i:s', 'text', 'system', 'Date Format', 'PHP date format for displaying dates', 1, 0, 'Y-m-d H:i:s'),
('records_per_page', '25', 'number', 'system', 'Records Per Page', 'Number of records to display per page in listings', 1, 0, '25'),
('maintenance_mode', '0', 'boolean', 'system', 'Maintenance Mode', 'Enable maintenance mode (disable public access)', 1, 0, '0'),
('debug_mode', '0', 'boolean', 'system', 'Debug Mode', 'Enable debug mode for troubleshooting', 1, 0, '0'),

-- EMAIL SETTINGS
('smtp_host', 'smtp.gmail.com', 'text', 'email', 'SMTP Host', 'SMTP server hostname', 1, 0, 'smtp.gmail.com'),
('smtp_port', '587', 'number', 'email', 'SMTP Port', 'SMTP server port (typically 587 for TLS, 465 for SSL)', 1, 0, '587'),
('smtp_username', '', 'email', 'email', 'SMTP Username', 'SMTP authentication username', 1, 1, ''),
('smtp_password', '', 'text', 'email', 'SMTP Password', 'SMTP authentication password (encrypted)', 1, 1, ''),
('smtp_encryption', 'tls', 'text', 'email', 'SMTP Encryption', 'Encryption type (tls, ssl, or none)', 1, 0, 'tls'),
('from_email', 'noreply@zwickytech.com', 'email', 'email', 'From Email', 'Default sender email address', 1, 0, 'noreply@zwickytech.com'),
('from_name', 'Zwicky License Manager', 'text', 'email', 'From Name', 'Default sender name', 1, 0, 'Zwicky License Manager'),

-- LICENSE SETTINGS
('license_key_format', 'ZWICKY-XXXX-XXXX-XXXX-XXXX', 'text', 'license', 'License Key Format', 'Format template for generating license keys', 1, 0, 'ZWICKY-XXXX-XXXX-XXXX-XXXX'),
('default_max_activations', '1', 'number', 'license', 'Default Max Activations', 'Default maximum number of activations per license', 1, 0, '1'),
('default_license_duration', '365', 'number', 'license', 'Default License Duration (days)', 'Default license validity period in days', 1, 0, '365'),
('allow_domain_wildcard', '1', 'boolean', 'license', 'Allow Domain Wildcards', 'Allow wildcard domains (e.g., *.example.com)', 1, 0, '1'),
('strict_domain_check', '1', 'boolean', 'license', 'Strict Domain Check', 'Enforce strict domain matching', 1, 0, '1'),

-- NOTIFICATION SETTINGS
('enable_email_notifications', '1', 'boolean', 'notification', 'Enable Email Notifications', 'Send email notifications for events', 1, 0, '1'),
('notify_license_created', '1', 'boolean', 'notification', 'Notify on License Creation', 'Send notification when new license is created', 1, 0, '1'),
('notify_license_activated', '1', 'boolean', 'notification', 'Notify on License Activation', 'Send notification when license is activated', 1, 0, '1'),
('notify_license_expired', '1', 'boolean', 'notification', 'Notify on License Expiration', 'Send notification when license expires', 1, 0, '1'),
('expiration_warning_days', '7', 'number', 'notification', 'Expiration Warning (days)', 'Days before expiration to send warning email', 1, 0, '7'),

-- SECURITY SETTINGS
('session_lifetime', '7200', 'number', 'security', 'Session Lifetime (seconds)', 'Admin session timeout in seconds (2 hours default)', 1, 0, '7200'),
('max_login_attempts', '5', 'number', 'security', 'Max Login Attempts', 'Maximum failed login attempts before account lock', 1, 0, '5'),
('lockout_duration', '1800', 'number', 'security', 'Lockout Duration (seconds)', 'Account lockout duration after max attempts (30 minutes default)', 1, 0, '1800'),
('require_strong_password', '1', 'boolean', 'security', 'Require Strong Passwords', 'Enforce strong password requirements', 1, 0, '1'),
('password_min_length', '8', 'number', 'security', 'Password Minimum Length', 'Minimum password length requirement', 1, 0, '8'),
('enable_logging', '1', 'boolean', 'security', 'Enable Activity Logging', 'Log all system activities for audit trail', 1, 0, '1'),
('log_retention_days', '90', 'number', 'security', 'Log Retention (days)', 'Number of days to keep activity logs', 1, 0, '90');

-- ================================================
-- TABLE 6: DATABASE VERSION TRACKING
-- Track database schema versions for migrations
-- ================================================

CREATE TABLE IF NOT EXISTS `zwicky_db_version` (
    `version` VARCHAR(20) PRIMARY KEY COMMENT 'Database schema version',
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When this version was applied',
    `description` TEXT COMMENT 'Description of changes in this version'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Database version tracking';

-- ================================================
-- INITIAL DATA INSERTION
-- Default admin user and system configuration
-- ================================================

-- Insert default admin user
-- Password: ZwickyAdmin2024 (CHANGE IMMEDIATELY IN PRODUCTION!)
INSERT IGNORE INTO `zwicky_admin_users` (
    `username`, 
    `email`, 
    `password_hash`, 
    `full_name`, 
    `role`, 
    `status`
) VALUES (
    'admin', 
    'admin@zwickytech.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- ZwickyAdmin2024
    'System Administrator', 
    'admin', 
    'active'
);

-- Insert current database version
INSERT IGNORE INTO `zwicky_db_version` (`version`, `description`) VALUES 
('1.0.0', 'Initial database schema with complete license management system'),
('1.1.0', 'Added system settings table with comprehensive configuration management');

-- ================================================
-- SAMPLE DATA (OPTIONAL - REMOVE IN PRODUCTION)
-- Uncomment below to insert sample licenses for testing
-- ================================================

/*
-- Sample license data for testing (REMOVE IN PRODUCTION)
INSERT INTO `zwicky_licenses` (
    `license_key`, 
    `product_name`, 
    `customer_name`, 
    `customer_email`, 
    `max_activations`, 
    `status`, 
    `expires_at`,
    `notes`
) VALUES 
('ZWICKY-DEMO-2024-SAMPLE-001', 'Demo Product', 'John Doe', 'john@example.com', 3, 'active', DATE_ADD(NOW(), INTERVAL 365 DAY), 'Sample license for demonstration'),
('ZWICKY-DEMO-2024-SAMPLE-002', 'Pro Product', 'Jane Smith', 'jane@example.com', 5, 'active', DATE_ADD(NOW(), INTERVAL 180 DAY), 'Sample pro license'),
('ZWICKY-DEMO-2024-EXPIRED-003', 'Test Product', 'Test User', 'test@example.com', 1, 'expired', DATE_SUB(NOW(), INTERVAL 30 DAY), 'Expired license for testing');

-- Sample activation data
INSERT INTO `zwicky_activations` (
    `license_id`, 
    `domain`, 
    `ip_address`, 
    `activation_token`, 
    `status`
) VALUES 
(1, 'demo.example.com', '192.168.1.100', 'demo_token_123456789', 'active'),
(2, 'pro.example.com', '192.168.1.101', 'pro_token_987654321', 'active');
*/

-- ================================================
-- ADDITIONAL PRODUCTION OPTIMIZATIONS
-- Run these after table creation for better performance
-- ================================================

-- Update table statistics for query optimizer
ANALYZE TABLE `zwicky_licenses`;
ANALYZE TABLE `zwicky_activations`;
ANALYZE TABLE `zwicky_admin_users`;
ANALYZE TABLE `zwicky_logs`;
ANALYZE TABLE `zwicky_settings`;

-- ================================================
-- VERIFICATION QUERIES
-- Run these to verify installation success
-- ================================================

-- Check all tables were created
SELECT 
    TABLE_NAME as 'Table',
    TABLE_ROWS as 'Rows',
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as 'Size (MB)'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME LIKE 'zwicky_%'
ORDER BY TABLE_NAME;

-- Verify admin user was created
SELECT 
    username, 
    email, 
    full_name, 
    role, 
    status, 
    created_at 
FROM `zwicky_admin_users` 
WHERE username = 'admin';

-- Check database version
SELECT * FROM `zwicky_db_version` ORDER BY applied_at DESC;

-- ================================================
-- SECURITY RECOMMENDATIONS
-- ================================================

/*
1. CHANGE DEFAULT ADMIN PASSWORD IMMEDIATELY:
   - Login to admin panel with: admin / ZwickyAdmin2024
   - Go to user settings and change password
   - Use strong password with mixed case, numbers, symbols

2. CREATE DEDICATED DATABASE USER:
   CREATE USER 'lms_user'@'localhost' IDENTIFIED BY 'secure_password_here';
   GRANT SELECT, INSERT, UPDATE, DELETE ON your_db.zwicky_* TO 'lms_user'@'localhost';
   FLUSH PRIVILEGES;

3. BACKUP STRATEGY:
   - Set up automated daily backups
   - Test restoration procedures
   - Store backups securely off-site

4. MONITORING:
   - Monitor database performance
   - Set up alerts for license expirations
   - Regular security audits

5. SSL/TLS:
   - Use HTTPS for all admin access
   - Enable database SSL connections if possible
   - Secure file permissions on config files
*/

-- ================================================
-- INSTALLATION COMPLETE
-- ================================================

SELECT 'Database schema installation completed successfully!' as 'Status';
SELECT 'Default admin login: admin / ZwickyAdmin2024' as 'Admin_Credentials';
SELECT 'IMPORTANT: Change default password immediately!' as 'Security_Warning';