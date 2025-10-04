# 🚀 Zwicky License Management System - Server Installation Guide

## Complete Production Deployment Guide

**Version:** 1.0.0  
**Last Updated:** October 2025  
**Author:** Zwicky Technology

---

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Requirements](#server-requirements)
3. [Pre-Installation Checklist](#pre-installation-checklist)
4. [Installation Methods](#installation-methods)
   - [Method 1: cPanel Installation](#method-1-cpanel-installation)
   - [Method 2: Direct Server Installation](#method-2-direct-server-installation)
   - [Method 3: VPS/Dedicated Server](#method-3-vpsdedicated-server)
5. [Database Setup](#database-setup)
6. [Configuration](#configuration)
7. [Security Hardening](#security-hardening)
8. [SSL Certificate Setup](#ssl-certificate-setup)
9. [Testing & Verification](#testing-verification)
10. [Troubleshooting](#troubleshooting)
11. [Maintenance](#maintenance)

---

## 🔧 Prerequisites

### Required Knowledge
- Basic understanding of web hosting
- SSH access (for VPS/Dedicated servers)
- PHP and MySQL fundamentals
- File permissions management

### Access Requirements
- ✅ Web hosting account or VPS access
- ✅ SSH/FTP credentials
- ✅ Database access (MySQL/MariaDB)
- ✅ Domain name (recommended)
- ✅ Email account for SMTP (optional but recommended)

---

## 💻 Server Requirements

### Minimum Requirements
| Component | Specification |
|-----------|--------------|
| **PHP Version** | 7.4 or higher (8.0+ recommended) |
| **MySQL/MariaDB** | 5.7+ / 10.2+ |
| **Web Server** | Apache 2.4+ or Nginx 1.18+ |
| **Memory** | 256 MB RAM minimum |
| **Storage** | 100 MB disk space |
| **SSL** | Recommended for production |

### Required PHP Extensions
```bash
- PDO
- PDO_MySQL
- mbstring
- json
- openssl
- curl
- zip
- fileinfo
```

### Recommended Server Configuration
```ini
; PHP Configuration (php.ini)
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 10M
post_max_size = 10M
display_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
date.timezone = America/New_York
```

---

## ✅ Pre-Installation Checklist

Before starting the installation, ensure you have:

- [ ] Server access (SSH/cPanel/FTP)
- [ ] MySQL database created
- [ ] Database user with full privileges
- [ ] Domain pointed to server (A record configured)
- [ ] SSL certificate available (Let's Encrypt recommended)
- [ ] Backup of any existing data
- [ ] FTP client (FileZilla, WinSCP) or SSH access
- [ ] Text editor for configuration

---

## 📦 Installation Methods

## Method 1: cPanel Installation

### Step 1: Download Files

1. **Download the License Management System**
   ```bash
   # Download from GitHub or your source
   wget https://github.com/yourusername/license-management-system/archive/main.zip
   # OR upload via FTP
   ```

2. **Login to cPanel**
   - Navigate to your hosting cPanel
   - Go to File Manager

3. **Upload Files**
   - Navigate to `public_html` or your domain folder
   - Upload the ZIP file
   - Extract the ZIP file
   - Move contents to root directory

### Step 2: Create Database

1. **Navigate to MySQL Databases**
   - cPanel → Databases → MySQL Databases

2. **Create New Database**
   ```
   Database Name: yourusername_lms
   ```

3. **Create Database User**
   ```
   Username: yourusername_lmsuser
   Password: [Generate Strong Password]
   ```

4. **Grant Privileges**
   - Select the user
   - Select the database
   - Add user to database with ALL PRIVILEGES

5. **Note Down Credentials**
   ```
   Database Name: yourusername_lms
   Database User: yourusername_lmsuser
   Database Password: [your password]
   Database Host: localhost
   ```

### Step 3: Import Database Schema

1. **Access phpMyAdmin**
   - cPanel → Databases → phpMyAdmin
   - Select your database

2. **Import Schema**
   - Click "Import" tab
   - Choose file: `database_schema.sql`
   - Click "Go"
   - Wait for success message

### Step 4: Configure Application

1. **Open File Manager**
   - Navigate to `config/database.php`
   - Click "Edit"

2. **Update Database Configuration**
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'yourusername_lms');
   define('DB_USER', 'yourusername_lmsuser');
   define('DB_PASS', 'your_database_password');
   ```

3. **Edit config.php**
   - Navigate to `config/config.php`
   - Update settings:
   ```php
   define('SITE_URL', 'https://yourdomain.com');
   define('DEBUG_MODE', false); // Set to false for production
   ```

### Step 5: Set File Permissions

1. **Using cPanel File Manager**
   - Select `logs` folder → Right-click → Change Permissions → 755
   - Select `config` folder → Right-click → Change Permissions → 644

2. **Or via SSH**
   ```bash
   chmod 755 logs/
   chmod 644 config/*.php
   ```

### Step 6: Access Application

1. **Navigate to your domain**
   ```
   https://yourdomain.com/admin/login.php
   ```

2. **Default Login Credentials**
   ```
   Username: admin
   Email: admin@zwickytech.com
   Password: ZwickyAdmin2024
   ```

3. **⚠️ IMPORTANT: Change Password Immediately!**

---

## Method 2: Direct Server Installation

### Step 1: Connect via SSH

```bash
# Connect to your server
ssh username@your-server-ip

# Navigate to web root
cd /var/www/html/
# OR
cd /home/yourusername/public_html/
```

### Step 2: Download Application

```bash
# Clone from Git (if available)
git clone https://github.com/yourusername/license-management-system.git lms
cd lms

# OR upload via SCP
scp -r /local/path/to/lms username@server:/var/www/html/
```

### Step 3: Create Database

```bash
# Login to MySQL
mysql -u root -p

# Create database and user
CREATE DATABASE license_management_db;
CREATE USER 'lms_user'@'localhost' IDENTIFIED BY 'SecurePassword123!';
GRANT ALL PRIVILEGES ON license_management_db.* TO 'lms_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u lms_user -p license_management_db < database_schema.sql
```

### Step 4: Configure Application

```bash
# Edit database configuration
nano config/database.php

# Update with your credentials
# Save with Ctrl+X, Y, Enter

# Edit main configuration
nano config/config.php

# Update SITE_URL and DEBUG_MODE
# Save and exit
```

### Step 5: Set Correct Permissions

```bash
# Set owner
chown -R www-data:www-data /var/www/html/lms

# Set permissions
find /var/www/html/lms -type d -exec chmod 755 {} \;
find /var/www/html/lms -type f -exec chmod 644 {} \;
chmod 755 /var/www/html/lms/logs/
chmod 644 /var/www/html/lms/config/*.php
```

### Step 6: Configure Apache Virtual Host

```bash
# Create new virtual host
nano /etc/apache2/sites-available/lms.conf
```

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/html/lms
    
    <Directory /var/www/html/lms>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/lms_error.log
    CustomLog ${APACHE_LOG_DIR}/lms_access.log combined
</VirtualHost>
```

```bash
# Enable site and rewrite module
a2ensite lms.conf
a2enmod rewrite
systemctl restart apache2
```

---

## Method 3: VPS/Dedicated Server (Ubuntu/Debian)

### Complete Server Setup from Scratch

#### Step 1: Update System

```bash
# Update package list
sudo apt update && sudo apt upgrade -y

# Install essential tools
sudo apt install -y software-properties-common curl wget git unzip
```

#### Step 2: Install LAMP Stack

```bash
# Install Apache
sudo apt install -y apache2

# Install MySQL
sudo apt install -y mysql-server

# Secure MySQL installation
sudo mysql_secure_installation

# Install PHP 8.1 (or latest stable version)
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd

# Verify installations
apache2 -v
mysql --version
php -v
```

#### Step 3: Configure Firewall

```bash
# Enable UFW firewall
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw enable
sudo ufw status
```

#### Step 4: Install Application

```bash
# Create directory
sudo mkdir -p /var/www/yourdomain.com
cd /var/www/yourdomain.com

# Clone or upload files
sudo git clone https://github.com/yourusername/license-management-system.git .

# OR use SCP from local machine:
# scp -r /local/lms/* user@server:/var/www/yourdomain.com/
```

#### Step 5: Database Setup

```bash
# Create database
sudo mysql -u root -p << EOF
CREATE DATABASE license_management_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lms_user'@'localhost' IDENTIFIED BY 'YourSecurePassword123!';
GRANT ALL PRIVILEGES ON license_management_db.* TO 'lms_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF

# Import schema
sudo mysql -u lms_user -p license_management_db < database_schema.sql
```

#### Step 6: Application Configuration

```bash
# Copy and edit config files
cd /var/www/yourdomain.com
sudo cp config/database.php.example config/database.php
sudo nano config/database.php
```

Update with your credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'license_management_db');
define('DB_USER', 'lms_user');
define('DB_PASS', 'YourSecurePassword123!');
```

#### Step 7: Apache Configuration

```bash
# Create virtual host
sudo nano /etc/apache2/sites-available/yourdomain.com.conf
```

```apache
<VirtualHost *:80>
    ServerAdmin admin@yourdomain.com
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/yourdomain.com
    
    <Directory /var/www/yourdomain.com>
        Options -Indexes +FollowSymLinks +MultiViews
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/yourdomain_error.log
    CustomLog ${APACHE_LOG_DIR}/yourdomain_access.log combined
</VirtualHost>
```

```bash
# Enable site and modules
sudo a2ensite yourdomain.com.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Step 8: Set Permissions

```bash
# Set proper ownership and permissions
sudo chown -R www-data:www-data /var/www/yourdomain.com
sudo find /var/www/yourdomain.com -type d -exec chmod 755 {} \;
sudo find /var/www/yourdomain.com -type f -exec chmod 644 {} \;
sudo chmod 755 /var/www/yourdomain.com/logs/
```

---

## 🔒 Security Hardening

### 1. Change Default Credentials

```bash
# After first login, immediately change:
# - Admin password
# - Database password (if using default)
# - Any default email addresses
```

### 2. Secure Config Files

```bash
# Restrict config file access
chmod 600 config/database.php
chown www-data:www-data config/database.php
```

### 3. Create .htaccess for Config Directory

```bash
nano config/.htaccess
```

```apache
# Deny all access to config files
<Files "*">
    Order allow,deny
    Deny from all
</Files>
```

### 4. Hide PHP Version

```bash
# Edit php.ini
sudo nano /etc/php/8.1/apache2/php.ini
```

```ini
expose_php = Off
```

### 5. Disable Directory Listing

```apache
# In .htaccess (root directory)
Options -Indexes
```

### 6. Enable Security Headers

Create `.htaccess` in root:
```apache
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>
```

---

## 🔐 SSL Certificate Setup

### Using Let's Encrypt (Free SSL)

#### Step 1: Install Certbot

```bash
# Ubuntu/Debian
sudo apt install certbot python3-certbot-apache -y

# CentOS/RHEL
sudo yum install certbot python3-certbot-apache -y
```

#### Step 2: Obtain Certificate

```bash
# Automatic configuration
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Follow the prompts:
# - Enter email address
# - Agree to terms
# - Choose redirect option (2 - Redirect HTTP to HTTPS)
```

#### Step 3: Auto-Renewal Setup

```bash
# Test renewal
sudo certbot renew --dry-run

# Certbot automatically sets up cron job for renewal
# Verify cron job
sudo systemctl status certbot.timer
```

### Using cPanel SSL

1. **cPanel → SSL/TLS → Manage SSL Sites**
2. **Select Let's Encrypt or upload custom certificate**
3. **Install certificate**
4. **Enable "Force HTTPS Redirect"**

---

## ✅ Testing & Verification

### Post-Installation Tests

#### 1. Database Connection Test

```bash
# Create test file: test_db.php
<?php
require_once 'config/database.php';
try {
    $db = getLMSDatabase();
    echo "Database connection: SUCCESS\n";
} catch (Exception $e) {
    echo "Database connection: FAILED\n";
    echo "Error: " . $e->getMessage();
}
?>
```

```bash
php test_db.php
# Delete after testing: rm test_db.php
```

#### 2. PHP Extensions Check

```bash
php -m | grep -E 'PDO|mysql|mbstring|json|openssl'
```

#### 3. File Permissions Check

```bash
ls -la logs/
ls -la config/
```

#### 4. Application Access Test

```bash
# Test URLs
curl -I https://yourdomain.com/admin/login.php
curl -I https://yourdomain.com/api/
```

#### 5. Login Test

1. Navigate to `https://yourdomain.com/admin/login.php`
2. Login with default credentials
3. Verify dashboard loads
4. Check all menu items
5. Test license creation

---

## 🔧 Troubleshooting

### Common Issues and Solutions

#### Issue 1: "Database connection failed"

**Solution:**
```bash
# Verify MySQL is running
sudo systemctl status mysql

# Check database credentials
mysql -u lms_user -p license_management_db

# Verify PHP MySQL extension
php -m | grep -i mysql
```

#### Issue 2: "Permission denied" errors

**Solution:**
```bash
# Reset permissions
sudo chown -R www-data:www-data /var/www/yourdomain.com
sudo chmod 755 /var/www/yourdomain.com/logs/
```

#### Issue 3: "404 Not Found" on admin pages

**Solution:**
```bash
# Enable Apache rewrite module
sudo a2enmod rewrite
sudo systemctl restart apache2

# Check .htaccess exists
ls -la .htaccess
```

#### Issue 4: Blank white page

**Solution:**
```bash
# Enable error reporting temporarily
# Edit index.php, add at top:
error_reporting(E_ALL);
ini_set('display_errors', 1);

# Check error logs
tail -f /var/log/apache2/error.log
```

#### Issue 5: SSL certificate errors

**Solution:**
```bash
# Verify certificate
sudo certbot certificates

# Renew certificate
sudo certbot renew

# Check Apache SSL configuration
sudo apache2ctl configtest
```

---

## 🔄 Maintenance

### Regular Maintenance Tasks

#### Daily
- [ ] Monitor error logs
- [ ] Check system resources
- [ ] Verify backups completed

#### Weekly
- [ ] Review activity logs
- [ ] Check license expirations
- [ ] Test email notifications

#### Monthly
- [ ] Security updates
- [ ] Database optimization
- [ ] Performance review
- [ ] Backup verification

### Backup Strategy

#### Automated Daily Backups

```bash
# Create backup script
sudo nano /usr/local/bin/lms_backup.sh
```

```bash
#!/bin/bash
# LMS Backup Script

BACKUP_DIR="/backups/lms"
DATE=$(date +%Y-%m-%d_%H-%M-%S)
DB_NAME="license_management_db"
DB_USER="lms_user"
DB_PASS="YourPassword"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/yourdomain.com

# Delete backups older than 30 days
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup completed: $DATE"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/lms_backup.sh

# Add to cron (daily at 2 AM)
sudo crontab -e
0 2 * * * /usr/local/bin/lms_backup.sh >> /var/log/lms_backup.log 2>&1
```

### Database Optimization

```bash
# Create optimization script
sudo nano /usr/local/bin/lms_optimize.sh
```

```bash
#!/bin/bash
# Weekly database optimization

mysql -u lms_user -p'YourPassword' license_management_db << EOF
OPTIMIZE TABLE zwicky_licenses;
OPTIMIZE TABLE zwicky_activations;
OPTIMIZE TABLE zwicky_admin_users;
OPTIMIZE TABLE zwicky_logs;
OPTIMIZE TABLE zwicky_settings;
EOF

echo "Database optimization completed"
```

```bash
chmod +x /usr/local/bin/lms_optimize.sh

# Run weekly (Sunday at 3 AM)
sudo crontab -e
0 3 * * 0 /usr/local/bin/lms_optimize.sh >> /var/log/lms_optimize.log 2>&1
```

### Log Rotation

```bash
# Create logrotate config
sudo nano /etc/logrotate.d/lms
```

```
/var/www/yourdomain.com/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
}
```

---

## 📞 Support & Documentation

### Resources
- **Official Documentation**: [Link to docs]
- **GitHub Repository**: [Link to repo]
- **Support Email**: support@zwickytech.com
- **Community Forum**: [Link to forum]

### Getting Help
1. Check troubleshooting section above
2. Review error logs
3. Search GitHub issues
4. Contact support with:
   - Error messages
   - PHP version
   - Server configuration
   - Steps to reproduce issue

---

## ✅ Installation Complete Checklist

After installation, verify:

- [ ] Application accessible via HTTPS
- [ ] Admin login working
- [ ] Dashboard loads correctly
- [ ] License creation successful
- [ ] Email notifications working
- [ ] Database queries executing
- [ ] Logs directory writable
- [ ] SSL certificate valid
- [ ] Firewall configured
- [ ] Backups scheduled
- [ ] Default password changed
- [ ] Security headers enabled
- [ ] Error reporting disabled in production
- [ ] Documentation reviewed

---

## 🎉 Congratulations!

Your Zwicky License Management System is now installed and ready for production use!

**Next Steps:**
1. Change default admin password
2. Configure email settings
3. Customize system settings
4. Create your first license
5. Set up monitoring alerts
6. Review security checklist

**Important Reminders:**
- Keep system updated
- Monitor logs regularly
- Maintain regular backups
- Review security periodically
- Document any customizations

---

**Document Version:** 1.0.0  
**Last Updated:** October 4, 2025  
**Copyright © 2025 Zwicky Technology. All rights reserved.**
