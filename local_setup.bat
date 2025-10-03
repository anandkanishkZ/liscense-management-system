@echo off
echo ================================
echo License Management System
echo Local Development Setup
echo ================================
echo.

REM Check if PHP is available
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH
    echo Please install PHP or XAMPP first
    pause
    exit
)

echo PHP is available!
echo.

REM Check if .env file exists
if not exist ".env" (
    if exist ".env.local" (
        echo Creating .env file from .env.local...
        copy ".env.local" ".env"
        echo .env file created!
    ) else (
        echo WARNING: No .env file found. Please create one from .env.example
    )
) else (
    echo .env file already exists
)

echo.
echo ================================
echo Setup Options:
echo ================================
echo 1. Start PHP Built-in Server (Port 8000)
echo 2. Install via Browser (Recommended)
echo 3. Manual Database Setup
echo 4. Exit
echo.
set /p choice="Choose option (1-4): "

if "%choice%"=="1" goto start_server
if "%choice%"=="2" goto browser_install
if "%choice%"=="3" goto manual_setup
if "%choice%"=="4" goto exit

:start_server
echo.
echo Starting PHP Development Server...
echo Open your browser to: http://localhost:8000
echo Press Ctrl+C to stop the server
echo.
php -S localhost:8000
goto exit

:browser_install
echo.
echo ================================
echo Browser Installation Steps:
echo ================================
echo 1. Start your web server (XAMPP/WAMP)
echo 2. Copy this project to htdocs folder
echo 3. Open: http://localhost/license-management-system/install.php
echo 4. Follow the installation wizard
echo.
pause
goto exit

:manual_setup
echo.
echo ================================
echo Manual Database Setup:
echo ================================
echo 1. Open phpMyAdmin: http://localhost/phpmyadmin
echo 2. Create database: license_system
echo 3. Import: database_schema.sql
echo 4. Create .env file with your database credentials
echo 5. Run: http://localhost/license-management-system/
echo.
pause
goto exit

:exit
echo.
echo Setup complete! Happy coding! 🚀
pause