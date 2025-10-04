# 🔐 Zwicky Technology License Management System

A professional PHP-based license management system for software products and themes. This system provides secure license validation, domain-based restrictions, and comprehensive admin management.

## 🎉 New: Interactive Installation Wizard!

**First-time users** will automatically see a beautiful, step-by-step installation wizard when they open the application!

### ✨ Installation is now as easy as 1-2-3:

1. **Upload files** to your server
2. **Create a MySQL database**
3. **Open your website** → Wizard starts automatically!

No more manual configuration! The wizard will:
- ✅ Check system requirements
- 🗄️ Configure database connection
- 📊 Import database structure
- 👤 Create your admin account
- 🚀 Get you started in minutes!

[See Installation Wizard Documentation](INSTALLATION_WIZARD_README.md)

---

## ✨ Features

- **🔑 License Management**: Create, validate, activate, and deactivate licenses
- **🌐 Domain Restrictions**: Restrict licenses to specific domains
- **📊 Admin Dashboard**: Comprehensive admin panel with statistics and management
- **🔒 Secure Authentication**: Role-based access control with account lockout protection
- **📝 Activity Logging**: Complete audit trail of all system activities
- **🚀 RESTful API**: Clean API endpoints for license validation
- **📱 Responsive UI**: Modern, mobile-friendly admin interface
- **⚡ Rate Limiting**: Built-in API rate limiting for security
- **🔧 Environment Configuration**: Flexible environment-based configuration

## 📋 Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher (or MariaDB 10.2+)
- **Extensions**: PDO, PDO_MySQL, OpenSSL, JSON, cURL
- **Web Server**: Apache/Nginx with mod_rewrite

## 🚀 Quick Installation

### Step 1: Download and Extract
```bash
# Extract the system to your web directory
unzip license-management-system.zip
cd license-management-system
```

### Step 2: Configure Environment
```bash
# Option A: Use the setup wizard (Recommended)
# Visit: http://yourdomain.com/path-to-system/setup.php

# Option B: Manual configuration
cp .env.example .env
# Edit .env with your settings
```

### Step 3: Set Permissions
```bash
# Make logs directory writable
chmod 755 logs/
chmod 755 assets/

# Ensure config files are readable
chmod 644 config/*.php
```

### Step 4: Install Database
```bash
# Visit the installer
# http://yourdomain.com/path-to-system/install.php
```

### Step 5: Access Admin Panel
```bash
# Default credentials:
# Username: admin
# Password: ZwickyAdmin2024
# URL: http://yourdomain.com/path-to-system/admin/login.php
```

## ⚙️ Configuration Options

### Environment Variables

The system supports configuration through environment variables or `.env` file:

```bash
# Database Configuration
LMS_DB_HOST=localhost
LMS_DB_NAME=zwicky_license_system
LMS_DB_USER=your_username
LMS_DB_PASS=your_password

# Application URLs
LMS_BASE_URL=https://yourdomain.com/license-system

# Email Settings
LMS_EMAIL_FROM=noreply@yourdomain.com
LMS_ADMIN_EMAIL=admin@yourdomain.com

# SMTP Configuration (Optional)
LMS_SMTP_HOST=smtp.gmail.com
LMS_SMTP_PORT=587
LMS_SMTP_USERNAME=your-smtp-user
LMS_SMTP_PASSWORD=your-smtp-password

# Security Keys (Auto-generated)
LMS_JWT_SECRET=your-secret-key
LMS_ENCRYPTION_KEY=your-encryption-key

# System Settings
LMS_DEBUG_MODE=false
LMS_LOG_LEVEL=INFO
```

### Database Configuration

Create a MySQL database and user:

```sql
CREATE DATABASE zwicky_license_system;
CREATE USER 'zwicky_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON zwicky_license_system.* TO 'zwicky_user'@'localhost';
FLUSH PRIVILEGES;
```

## 🔗 API Endpoints

### License Validation
```bash
POST /api/validate
{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "domain": "example.com"
}
```

### License Activation
```bash
POST /api/activate
{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "domain": "example.com"
}
```

### License Deactivation
```bash
POST /api/deactivate
{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "domain": "example.com"
}
```

### License Status
```bash
GET /api/status?license_key=XXXX-XXXX-XXXX-XXXX
```

## 🛡️ Security Features

- **Password Hashing**: Secure bcrypt password hashing
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Protection**: Input sanitization and output encoding
- **CSRF Protection**: Security headers and token validation
- **Rate Limiting**: API request limiting by IP
- **Account Lockout**: Automatic lockout after failed attempts
- **Session Security**: Secure session configuration
- **SSL Support**: HTTPS enforcement in production

## 📁 File Structure

```
license-management-system/
├── admin/                  # Admin panel files
│   ├── dashboard.php      # Main dashboard
│   ├── login.php          # Admin login
│   ├── logout.php         # Logout handler
│   └── includes/          # Shared admin components
├── api/                   # API endpoints
│   └── index.php          # API router
├── assets/                # Static assets
│   ├── css/               # Stylesheets
│   └── js/                # JavaScript files
├── classes/               # PHP classes
│   ├── LMSAdminAuth.php   # Authentication
│   ├── LMSLicenseManager.php # License management
│   └── LMSLogger.php      # Logging system
├── config/                # Configuration files
│   ├── config.php         # Main configuration
│   └── database.php       # Database configuration
├── logs/                  # Log files (auto-created)
├── .env.example           # Environment template
├── install.php            # Database installer
├── setup.php              # Configuration wizard
└── README.md              # This file
```

## 🔧 Customization

### Adding Custom License Types
1. Extend the `LMSLicenseManager` class
2. Add custom validation logic
3. Update the database schema if needed

### Custom Email Templates
1. Create email template files
2. Configure SMTP settings
3. Extend the notification system

### Custom Admin Pages
1. Create new PHP files in the `admin/` directory
2. Add navigation links in `admin/includes/sidebar.php`
3. Follow the existing authentication pattern

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check database credentials in `.env`
- Verify MySQL service is running
- Ensure database and user exist

**Permission Denied**
- Set proper file permissions: `chmod 755 logs/`
- Ensure web server can write to logs directory

**API Returns 500 Error**
- Enable debug mode: `LMS_DEBUG_MODE=true`
- Check error logs in the `logs/` directory
- Verify all PHP extensions are installed

**License Validation Fails**
- Check API endpoint URL configuration
- Verify rate limiting isn't blocking requests
- Ensure license key format is correct

### Debug Mode

Enable debug mode for development:
```bash
# In .env file
LMS_DEBUG_MODE=true
LMS_LOG_LEVEL=DEBUG
```

## 📊 Performance Optimization

- **Database Indexing**: Indexes on frequently queried columns
- **Query Optimization**: Efficient database queries with pagination
- **Caching**: File-based caching for rate limiting
- **Compression**: Enable gzip compression on web server
- **CDN**: Use CDN for static assets in production

## 🔄 Backup and Maintenance

### Database Backup
```bash
mysqldump -u username -p zwicky_license_system > backup.sql
```

### Log Rotation
The system automatically rotates logs when they exceed the configured size.

### Regular Maintenance
- Clean old logs: Built-in log cleanup functionality
- Monitor disk space: Logs and cache files
- Update dependencies: Keep PHP and MySQL updated

## 📞 Support

- **Documentation**: Check this README and inline code comments
- **Issues**: Report bugs through your preferred method
- **Updates**: Check for system updates regularly

## 📄 License

This license management system is proprietary software developed by Zwicky Technology.

## 🏷️ Version

**Version**: 1.0.0  
**Last Updated**: October 2024  
**Compatibility**: PHP 7.4+, MySQL 5.7+

---

**🔐 Secure • 🚀 Fast • 🛡️ Reliable**

For more information, visit: [Zwicky Technology](https://zwickytechnology.com)