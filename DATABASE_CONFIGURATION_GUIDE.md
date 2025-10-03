# 🗄️ Database Configuration Guide

## Required Database Fields for Installation

### **Compulsory Fields (Required)**

When configuring your database during installation, these fields are **mandatory**:

#### 1. **Database Host** ⭐ *Required*
- **Field**: `db_host`
- **Default**: `localhost`
- **Description**: The server where your MySQL database is hosted
- **Common Values**:
  - `localhost` (most shared hosting)
  - `127.0.0.1` (local development)
  - `your-database-server.com` (remote database)

#### 2. **Database Name** ⭐ *Required*
- **Field**: `db_name`
- **Description**: The name of your MySQL database
- **Examples**:
  - `license_management_system`
  - `cpanelusername_license_db` (cPanel format)
  - `your_custom_db_name`

#### 3. **Database Username** ⭐ *Required*
- **Field**: `db_user`
- **Description**: MySQL user with privileges to access the database
- **Examples**:
  - `root` (local development - not recommended for production)
  - `cpanelusername_lms_user` (cPanel format)
  - `your_db_user`

#### 4. **Database Password** ⭐ *Required*
- **Field**: `db_pass`
- **Description**: Password for the MySQL user
- **Security**: Use a strong, unique password
- **Note**: Cannot be empty for production installations

---

### **Optional Fields**

#### 5. **Character Set** ⚪ *Optional*
- **Field**: `db_charset`
- **Default**: `utf8mb4` (recommended)
- **Options**:
  - `utf8mb4` - Full Unicode support (recommended)
  - `utf8` - Basic Unicode support
- **Why utf8mb4**: Supports emojis and full Unicode characters

---

## 🔧 cPanel Hosting Configuration

### **Typical cPanel Database Settings**

For most cPanel hosting providers, your configuration will look like:

```
Database Host: localhost
Database Name: cpanelusername_license_system
Database User: cpanelusername_lms_user
Database Password: your_secure_password_here
Character Set: utf8mb4
```

**Replace `cpanelusername` with your actual cPanel username**

---

## 📋 Pre-Installation Database Setup

### **Step 1: Create Database in cPanel**
1. Log into cPanel
2. Go to **MySQL Databases**
3. Create new database (e.g., `license_system`)
4. Full name will be: `cpanelusername_license_system`

### **Step 2: Create Database User**
1. In MySQL Databases, create new user
2. Use strong password (mix of letters, numbers, symbols)
3. Full username will be: `cpanelusername_username`

### **Step 3: Assign User to Database**
1. Add user to database
2. Select **ALL PRIVILEGES**
3. This gives the user full access to manage the database

### **Step 4: Test Connection**
- Use phpMyAdmin to verify you can connect
- Or test the connection during installation

---

## ✅ Database Requirements

### **MySQL Version Requirements**
- **Minimum**: MySQL 5.7+ or MariaDB 10.3+
- **Recommended**: MySQL 8.0+ or MariaDB 10.6+

### **Required Privileges**
Your database user must have these privileges:
- ✅ **SELECT** - Read data
- ✅ **INSERT** - Add new records
- ✅ **UPDATE** - Modify existing records
- ✅ **DELETE** - Remove records
- ✅ **CREATE** - Create new tables
- ✅ **DROP** - Remove tables (for uninstallation)
- ✅ **INDEX** - Create and modify indexes
- ✅ **ALTER** - Modify table structure

### **Storage Requirements**
- **Initial**: ~5MB for tables and indexes
- **Recommended**: 100MB+ for production use
- **Growth**: Depends on number of licenses (approximately 1KB per license)

---

## 🚀 Installation Process

### **What Happens During Installation**

When you provide the database configuration, the system will:

1. **Test Connection**: Verify credentials work
2. **Create Tables**: Set up 4 optimized database tables:
   - `zwicky_licenses` - Core license management
   - `zwicky_activations` - License activation tracking
   - `zwicky_admin_users` - Admin user management
   - `zwicky_logs` - System activity logging
3. **Create Indexes**: Add performance indexes for fast queries
4. **Insert Default Data**: Create default admin user and sample license
5. **Generate .env File**: Create secure configuration file
6. **Verify Installation**: Run tests to ensure everything works

---

## 🔒 Security Best Practices

### **Database Security**
- ✅ Use strong, unique passwords (12+ characters)
- ✅ Create dedicated database user (don't use root)
- ✅ Grant only necessary privileges
- ✅ Use localhost for database host when possible
- ✅ Enable database firewall if available

### **Configuration Security**
- ✅ Never share database credentials
- ✅ Keep .env file secure (chmod 600)
- ✅ Don't commit credentials to version control
- ✅ Change default admin password immediately
- ✅ Use HTTPS for admin access

---

## 🆘 Troubleshooting Common Issues

### **Connection Failed**
**Error**: "Database connection failed"
**Solutions**:
- Verify database host is correct (usually localhost)
- Check database name format (cPanel uses cpanelusername_dbname)
- Confirm username and password are correct
- Ensure database user has been added to the database

### **Access Denied**
**Error**: "Access denied for user"
**Solutions**:
- Verify database user exists
- Check password is correct
- Ensure user has been assigned to the database
- Confirm user has necessary privileges

### **Database Not Found**
**Error**: "Unknown database"
**Solutions**:
- Verify database name is correct
- Check database exists in cPanel
- Confirm database name includes cPanel username prefix

---

## 📞 Quick Reference

### **Minimum Required Information**
To install successfully, you need:
1. ⭐ Database host (usually `localhost`)
2. ⭐ Database name (created in cPanel)
3. ⭐ Database username (created in cPanel)
4. ⭐ Database password (set when creating user)

### **Optional But Recommended**
- Character set: `utf8mb4` for full Unicode support

### **After Installation**
- Default admin login: `admin` / `ZwickyAdmin2024`
- **⚠️ Change password immediately!**
- Access admin panel: `/admin/login.php`

---

*This guide ensures you have all the necessary information to configure your database correctly for a successful License Management System installation.*