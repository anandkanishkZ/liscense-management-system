<?php
/**
 * Zwicky Technology License Management System
 * Public Index Page - Smart Redirector
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 */

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer-when-downgrade");

// Check if installation is complete
$lockFile = __DIR__ . '/install.lock';

if (file_exists($lockFile)) {
    // Installation complete, redirect to admin login
    header("Location: admin/login.php");
} else {
    // First time setup, redirect to installation wizard
    header("Location: wizard.php");
}

exit;