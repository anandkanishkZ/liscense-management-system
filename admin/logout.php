<?php
/**
 * Zwicky Technology License Management System
 * Admin Logout
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

require_once '../config/config.php';

$auth = new LMSAdminAuth();
$result = $auth->logout();

// Redirect to login page
header('Location: login.php?message=' . urlencode('You have been logged out successfully'));
exit;