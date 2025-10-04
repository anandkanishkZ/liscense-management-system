# Quick Setup Guide for Local Development

## Option 1: Fix Current PHP Installation

1. Open: C:\Program Files\php-8.4.7\php.ini
2. Find and uncomment these lines (remove semicolon):
   extension=pdo_mysql
   extension=mysqli
3. Restart any running PHP processes
4. Run: php test_mysql.php

## Option 2: Use XAMPP (Recommended for beginners)

1. Download XAMPP: https://www.apachefriends.org/
2. Install and start Apache + MySQL
3. Copy project to: C:\xampp\htdocs\license-management-system
4. Access: http://localhost/license-management-system/install.php

## Option 3: Use with MySQL Workbench + PHP Built-in Server

After fixing PHP extensions:
1. Create database in MySQL Workbench:
   - Database name: license_system
2. Import schema: database_schema.sql
3. Start PHP server: php -S localhost:8000
4. Access: http://localhost:8000/install.php

## Current Database Settings (.env):
- Host: localhost
- Database: license_system  
- Username: root
- Password: admin123