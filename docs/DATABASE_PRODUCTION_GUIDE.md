# 🗄️ Database Production Deployment Guide

## 📋 Overview

This guide covers comprehensive database setup and management for the License Management System in production environments, including table creation, configuration, security, and maintenance.

---

## 🚀 Production Database Setup

### 1. Database Requirements

#### Minimum Requirements
- **MySQL Version**: 5.7+ or MariaDB 10.3+
- **PHP Version**: 7.4+ with PDO extension
- **Storage**: Minimum 100MB for initial setup
- **Character Set**: UTF8MB4 (for full Unicode support)
- **Collation**: utf8mb4_unicode_ci

#### Recommended Production Specs
- **MySQL Version**: 8.0+ or MariaDB 10.6+
- **Storage**: 1GB+ for production use
- **Memory**: 512MB+ allocated to MySQL
- **Connections**: Max 100+ concurrent connections

---

## 📊 Database Schema

### Table Structure Overview

```sql
-- Main Tables
zwicky_licenses          -- Core license management
zwicky_activations       -- License activation tracking  
zwicky_admin_users       -- Admin user management
zwicky_logs             -- System activity logging
```

### Complete Table Creation Script

The system includes 4 main tables with optimized indexes for production performance:

#### 1. Licenses Table (`zwicky_licenses`)
```sql
CREATE TABLE zwicky_licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_key VARCHAR(35) UNIQUE NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    allowed_domains TEXT,
    max_activations INT DEFAULT 1,
    current_activations INT DEFAULT 0,
    status ENUM('active', 'suspended', 'expired') DEFAULT 'active',
    expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT,
    -- Optimized indexes for production
    INDEX idx_license_key (license_key),
    INDEX idx_customer_email (customer_email),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2. Activations Table (`zwicky_activations`)
```sql
CREATE TABLE zwicky_activations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    domain VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    activation_token VARCHAR(64) UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Foreign key constraints
    FOREIGN KEY (license_id) REFERENCES zwicky_licenses(id) ON DELETE CASCADE,
    -- Unique constraints
    UNIQUE KEY unique_license_domain (license_id, domain),
    -- Performance indexes
    INDEX idx_domain (domain),
    INDEX idx_activation_token (activation_token),
    INDEX idx_status (status),
    INDEX idx_last_check (last_check)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3. Admin Users Table (`zwicky_admin_users`)
```sql
CREATE TABLE zwicky_admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'manager', 'viewer') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Security indexes
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_last_login (last_login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 4. System Logs Table (`zwicky_logs`)
```sql
CREATE TABLE zwicky_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Foreign key for user tracking
    FOREIGN KEY (user_id) REFERENCES zwicky_admin_users(id) ON DELETE SET NULL,
    -- Performance indexes
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at),
    INDEX idx_record_id (record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🔧 Production Deployment Methods

### Method 1: Automated Installation (Recommended)

#### Step 1: Upload Files
```bash
# Upload all system files to your production server
# Ensure proper file permissions
chmod 755 /path/to/license-system/
chmod 644 /path/to/license-system/*.php
chmod 600 /path/to/license-system/config/*.php
```

#### Step 2: Configure Database Connection
Create `.env` file or set environment variables:
```bash
# Production Environment Variables
export LMS_DB_HOST="your-production-db-host"
export LMS_DB_NAME="your_production_db_name"
export LMS_DB_USER="your_db_user"
export LMS_DB_PASS="your_secure_password"
export LMS_DB_CHARSET="utf8mb4"
```

#### Step 3: Run Installation
```bash
# Access installation page
https://yourdomain.com/license-system/install.php
```

**Installation Process:**
1. System checks requirements automatically
2. Tests database connection
3. Creates all tables with proper indexes
4. Inserts default admin user
5. Sets up system configuration

### Method 2: Manual SQL Deployment

#### For Advanced Users or Custom Deployments

1. **Create Database**
```sql
CREATE DATABASE your_production_db_name 
DEFAULT CHARACTER SET utf8mb4 
DEFAULT COLLATE utf8mb4_unicode_ci;
```

2. **Create Database User**
```sql
CREATE USER 'lms_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON your_production_db_name.* TO 'lms_user'@'localhost';
FLUSH PRIVILEGES;
```

3. **Import Tables**
```bash
# Use the provided SQL file
mysql -u lms_user -p your_production_db_name < database_schema.sql
```

---

## 🔒 Production Security Configuration

### Database Security Best Practices

#### 1. Database User Permissions
```sql
-- Create dedicated user with minimal required permissions
CREATE USER 'lms_prod_user'@'localhost' IDENTIFIED BY 'very_secure_password';

-- Grant only necessary permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON license_db.zwicky_licenses TO 'lms_prod_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON license_db.zwicky_activations TO 'lms_prod_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON license_db.zwicky_admin_users TO 'lms_prod_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON license_db.zwicky_logs TO 'lms_prod_user'@'localhost';

FLUSH PRIVILEGES;
```

#### 2. Connection Security
```php
// Production database configuration
define('LMS_DB_HOST', 'localhost'); // Use localhost for local DB
define('LMS_DB_NAME', 'production_license_db');
define('LMS_DB_USER', 'lms_prod_user');
define('LMS_DB_PASS', 'very_secure_password_123!@#');

// Enhanced security options
$db_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
    PDO::ATTR_PERSISTENT => false,
    PDO::ATTR_TIMEOUT => 30
];
```

#### 3. Environment Variables (Recommended)
```bash
# .env file (not in web root)
LMS_DB_HOST=localhost
LMS_DB_NAME=production_license_db
LMS_DB_USER=lms_prod_user
LMS_DB_PASS=very_secure_password_123!@#
LMS_DB_CHARSET=utf8mb4

# SSL Certificate paths (if using SSL)
LMS_DB_SSL_CERT=/path/to/client-cert.pem
LMS_DB_SSL_KEY=/path/to/client-key.pem
LMS_DB_SSL_CA=/path/to/ca-cert.pem
```

---

## 📈 Performance Optimization

### Database Optimization for Production

#### 1. MySQL Configuration (`my.cnf`)
```ini
[mysqld]
# InnoDB Configuration
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_lock_wait_timeout = 50

# Query Cache
query_cache_type = 1
query_cache_size = 32M
query_cache_limit = 1M

# Connection Settings
max_connections = 100
connect_timeout = 10
wait_timeout = 600

# Character Set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

#### 2. Index Optimization
```sql
-- Monitor slow queries
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Add additional indexes for heavy usage
CREATE INDEX idx_license_status_expires ON zwicky_licenses(status, expires_at);
CREATE INDEX idx_activation_license_status ON zwicky_activations(license_id, status);
CREATE INDEX idx_logs_user_action_date ON zwicky_logs(user_id, action, created_at);
```

#### 3. Regular Maintenance
```sql
-- Weekly maintenance tasks
OPTIMIZE TABLE zwicky_licenses;
OPTIMIZE TABLE zwicky_activations;
OPTIMIZE TABLE zwicky_admin_users;
OPTIMIZE TABLE zwicky_logs;

-- Analyze tables for query optimization
ANALYZE TABLE zwicky_licenses;
ANALYZE TABLE zwicky_activations;
ANALYZE TABLE zwicky_admin_users;
ANALYZE TABLE zwicky_logs;
```

---

## 💾 Backup & Recovery

### Automated Backup Strategy

#### 1. Daily Backup Script
```bash
#!/bin/bash
# daily_backup.sh

DB_NAME="production_license_db"
DB_USER="backup_user"
DB_PASS="backup_password"
BACKUP_DIR="/backups/license-system"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/license_backup_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/license_backup_$DATE.sql

# Remove backups older than 30 days
find $BACKUP_DIR -name "license_backup_*.sql.gz" -mtime +30 -delete
```

#### 2. Restoration Process
```bash
# Restore from backup
gunzip license_backup_20251003_120000.sql.gz
mysql -u root -p production_license_db < license_backup_20251003_120000.sql
```

---

## 🔍 Monitoring & Maintenance

### Health Check Queries

#### 1. System Status Check
```sql
-- Check table sizes
SELECT 
    table_name,
    table_rows,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = 'production_license_db';

-- Check license statistics
SELECT 
    status,
    COUNT(*) as count,
    COUNT(*) * 100.0 / (SELECT COUNT(*) FROM zwicky_licenses) as percentage
FROM zwicky_licenses 
GROUP BY status;

-- Check expiring licenses
SELECT COUNT(*) as expiring_soon
FROM zwicky_licenses 
WHERE expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
AND status = 'active';
```

#### 2. Performance Monitoring
```sql
-- Slow queries
SELECT * FROM mysql.slow_log 
WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 DAY)
ORDER BY query_time DESC;

-- Connection monitoring
SHOW PROCESSLIST;
SHOW STATUS LIKE 'Connections';
SHOW STATUS LIKE 'Max_used_connections';
```

---

## 🚨 Troubleshooting Common Issues

### Connection Issues

#### Problem: "Database connection failed"
**Solutions:**
1. Check database credentials in `config/database.php`
2. Verify database server is running
3. Test connection manually:
```bash
mysql -h host -u username -p database_name
```

#### Problem: "Access denied for user"
**Solutions:**
1. Verify user exists and has correct permissions
2. Check password is correct
3. Ensure user can connect from the application server

### Performance Issues

#### Problem: Slow queries
**Solutions:**
1. Check for missing indexes
2. Analyze slow query log
3. Optimize database configuration
4. Consider query optimization

#### Problem: High memory usage
**Solutions:**
1. Adjust `innodb_buffer_pool_size`
2. Optimize query cache settings
3. Monitor connection pool usage

---

## 🔄 Migration & Updates

### Database Migration Process

#### 1. Version Control
```sql
-- Track database version
CREATE TABLE IF NOT EXISTS zwicky_db_version (
    version VARCHAR(20) PRIMARY KEY,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO zwicky_db_version (version) VALUES ('1.0.0');
```

#### 2. Safe Migration Steps
```bash
# 1. Backup current database
mysqldump -u user -p database > backup_before_migration.sql

# 2. Test migration on staging
mysql -u user -p staging_database < migration_script.sql

# 3. Apply to production (during maintenance window)
mysql -u user -p production_database < migration_script.sql

# 4. Verify migration success
mysql -u user -p production_database -e "SELECT * FROM zwicky_db_version;"
```

---

## 📝 Quick Reference

### Essential Commands

```bash
# Check database size
mysql -u user -p -e "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema='your_db_name';"

# Check table status
mysql -u user -p -e "SHOW TABLE STATUS FROM your_db_name;"

# Repair tables if needed
mysql -u user -p -e "REPAIR TABLE zwicky_licenses, zwicky_activations, zwicky_admin_users, zwicky_logs;"
```

### Default Admin Credentials
- **Username**: admin
- **Password**: ZwickyAdmin2024
- **⚠️ Change immediately after installation!**

### Environment Variables Template
```bash
# Copy to .env file
LMS_DB_HOST=localhost
LMS_DB_NAME=your_production_db
LMS_DB_USER=your_db_user
LMS_DB_PASS=your_secure_password
LMS_DB_CHARSET=utf8mb4
```

---

## 📞 Production Support

### Pre-Installation Checklist
- ✅ PHP 7.4+ with PDO extension
- ✅ MySQL 5.7+ or MariaDB 10.3+
- ✅ Secure database credentials ready
- ✅ Backup strategy planned
- ✅ SSL certificates (if required)
- ✅ Monitoring system configured

### Post-Installation Verification
- ✅ All tables created successfully
- ✅ Admin login works
- ✅ License creation/management functional
- ✅ Expiration monitoring active
- ✅ Backup process working
- ✅ Performance metrics baseline established

This comprehensive guide ensures your License Management System database is properly configured, secured, and optimized for production use with automatic table creation, monitoring, and maintenance procedures.