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
-- TABLE 5: DATABASE VERSION TRACKING
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
('1.0.0', 'Initial database schema with complete license management system');

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