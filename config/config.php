<?php
/**
 * Zwicky Technology License Management System
 * Main Configuration File
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

// Prevent direct access
define('LMS_SECURE', true);

// Load environment variables from .env file if it exists
function loadEnvironmentFile($file) {
    if (!file_exists($file)) {
        return;
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load .env file from the root directory
loadEnvironmentFile(dirname(__DIR__) . '/.env');

// System Information
define('LMS_VERSION', '1.0.0');
define('LMS_NAME', 'Zwicky License Manager');
define('LMS_AUTHOR', 'Zwicky Technology');
define('LMS_URL', 'https://zwickytechnology.com');

// File Paths
define('LMS_ROOT', dirname(__DIR__));
define('LMS_CONFIG_DIR', LMS_ROOT . '/config');
define('LMS_CLASSES_DIR', LMS_ROOT . '/classes');
define('LMS_ADMIN_DIR', LMS_ROOT . '/admin');
define('LMS_API_DIR', LMS_ROOT . '/api');
define('LMS_LOGS_DIR', LMS_ROOT . '/logs');
define('LMS_ASSETS_DIR', LMS_ROOT . '/assets');

// URLs - Auto-detect with environment variable overrides
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$base_path = str_replace('/config', '', $script_dir);
$auto_base_url = $protocol . $host . $base_path;

define('LMS_BASE_URL', $_ENV['LMS_BASE_URL'] ?? getenv('LMS_BASE_URL') ?: $auto_base_url);
define('LMS_API_URL', LMS_BASE_URL . '/api');
define('LMS_ADMIN_URL', LMS_BASE_URL . '/admin');

// Security Settings - Use environment variables with secure fallbacks
define('LMS_JWT_SECRET', $_ENV['LMS_JWT_SECRET'] ?? getenv('LMS_JWT_SECRET') ?: 'Zw1cky_JWT_S3cr3t_K3y_2024_V3ry_L0ng_4nd_S3cur3_' . uniqid());
define('LMS_JWT_ALGORITHM', 'HS256');
define('LMS_JWT_EXPIRE_TIME', (int)($_ENV['LMS_JWT_EXPIRE_TIME'] ?? getenv('LMS_JWT_EXPIRE_TIME') ?: 3600)); // 1 hour
define('LMS_SESSION_LIFETIME', (int)($_ENV['LMS_SESSION_LIFETIME'] ?? getenv('LMS_SESSION_LIFETIME') ?: 7200)); // 2 hours
define('LMS_MAX_LOGIN_ATTEMPTS', (int)($_ENV['LMS_MAX_LOGIN_ATTEMPTS'] ?? getenv('LMS_MAX_LOGIN_ATTEMPTS') ?: 5));
define('LMS_LOCKOUT_TIME', (int)($_ENV['LMS_LOCKOUT_TIME'] ?? getenv('LMS_LOCKOUT_TIME') ?: 900)); // 15 minutes

// License Settings - Use environment variables with defaults
define('LMS_DEFAULT_LICENSE_DURATION', (int)($_ENV['LMS_DEFAULT_LICENSE_DURATION'] ?? getenv('LMS_DEFAULT_LICENSE_DURATION') ?: 365)); // Days
define('LMS_MAX_ACTIVATIONS_PER_LICENSE', (int)($_ENV['LMS_MAX_ACTIVATIONS_PER_LICENSE'] ?? getenv('LMS_MAX_ACTIVATIONS_PER_LICENSE') ?: 3));
define('LMS_GRACE_PERIOD_DAYS', (int)($_ENV['LMS_GRACE_PERIOD_DAYS'] ?? getenv('LMS_GRACE_PERIOD_DAYS') ?: 7));
define('LMS_CHECK_FREQUENCY', 86400); // 24 hours in seconds

// Encryption Settings - Use environment variables with secure fallbacks
define('LMS_ENCRYPTION_METHOD', 'AES-256-CBC');
define('LMS_ENCRYPTION_KEY', $_ENV['LMS_ENCRYPTION_KEY'] ?? getenv('LMS_ENCRYPTION_KEY') ?: 'Zw1cky_3ncrypt10n_K3y_2024_V3ry_S3cur3_L0ng_' . uniqid());

// Email Settings - Use environment variables with fallbacks
define('LMS_EMAIL_FROM', $_ENV['LMS_EMAIL_FROM'] ?? getenv('LMS_EMAIL_FROM') ?: 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('LMS_EMAIL_FROM_NAME', $_ENV['LMS_EMAIL_FROM_NAME'] ?? getenv('LMS_EMAIL_FROM_NAME') ?: 'License Management System');
define('LMS_ADMIN_EMAIL', $_ENV['LMS_ADMIN_EMAIL'] ?? getenv('LMS_ADMIN_EMAIL') ?: 'admin@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

// SMTP Configuration (optional - for email notifications)
define('LMS_SMTP_HOST', $_ENV['LMS_SMTP_HOST'] ?? getenv('LMS_SMTP_HOST') ?: '');
define('LMS_SMTP_PORT', $_ENV['LMS_SMTP_PORT'] ?? getenv('LMS_SMTP_PORT') ?: 587);
define('LMS_SMTP_USERNAME', $_ENV['LMS_SMTP_USERNAME'] ?? getenv('LMS_SMTP_USERNAME') ?: '');
define('LMS_SMTP_PASSWORD', $_ENV['LMS_SMTP_PASSWORD'] ?? getenv('LMS_SMTP_PASSWORD') ?: '');
define('LMS_SMTP_ENCRYPTION', $_ENV['LMS_SMTP_ENCRYPTION'] ?? getenv('LMS_SMTP_ENCRYPTION') ?: 'tls');

// Logging Settings - Use environment variables with defaults
define('LMS_LOG_LEVEL', $_ENV['LMS_LOG_LEVEL'] ?? getenv('LMS_LOG_LEVEL') ?: 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LMS_LOG_MAX_SIZE', (int)($_ENV['LMS_LOG_MAX_SIZE'] ?? getenv('LMS_LOG_MAX_SIZE') ?: 10485760)); // 10MB
define('LMS_LOG_MAX_FILES', (int)($_ENV['LMS_LOG_MAX_FILES'] ?? getenv('LMS_LOG_MAX_FILES') ?: 5));

// API Rate Limiting - Use environment variables with defaults
define('LMS_API_RATE_LIMIT', (int)($_ENV['LMS_API_RATE_LIMIT'] ?? getenv('LMS_API_RATE_LIMIT') ?: 100)); // Requests per hour per IP
define('LMS_API_BURST_LIMIT', (int)($_ENV['LMS_API_BURST_LIMIT'] ?? getenv('LMS_API_BURST_LIMIT') ?: 10)); // Requests per minute per IP

// Theme Integration Settings
define('LMS_THEME_CHECK_URL', LMS_API_URL . '/validate');
define('LMS_THEME_ACTIVATE_URL', LMS_API_URL . '/activate');
define('LMS_THEME_DEACTIVATE_URL', LMS_API_URL . '/deactivate');
define('LMS_THEME_STATUS_URL', LMS_API_URL . '/status');

// Development/Production Mode - Use environment variables with safe defaults
define('LMS_DEBUG_MODE', filter_var($_ENV['LMS_DEBUG_MODE'] ?? getenv('LMS_DEBUG_MODE') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('LMS_MAINTENANCE_MODE', filter_var($_ENV['LMS_MAINTENANCE_MODE'] ?? getenv('LMS_MAINTENANCE_MODE') ?: 'false', FILTER_VALIDATE_BOOLEAN));

// Time Zone
date_default_timezone_set('UTC');

// Error Reporting
if (LMS_DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
}

// Include database configuration
require_once LMS_CONFIG_DIR . '/database.php';

// Auto-load classes
spl_autoload_register(function ($class_name) {
    $class_file = LMS_CLASSES_DIR . '/' . $class_name . '.php';
    if (file_exists($class_file)) {
        require_once $class_file;
    }
});

// Utility Functions
function lms_encrypt($data) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(LMS_ENCRYPTION_METHOD));
    $encrypted = openssl_encrypt($data, LMS_ENCRYPTION_METHOD, LMS_ENCRYPTION_KEY, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function lms_decrypt($data) {
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, LMS_ENCRYPTION_METHOD, LMS_ENCRYPTION_KEY, 0, $iv);
}

function lms_generate_license_key() {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 8) . '-' . 
                     substr(md5(uniqid(rand(), true)), 0, 8) . '-' . 
                     substr(md5(uniqid(rand(), true)), 0, 8) . '-' . 
                     substr(md5(uniqid(rand(), true)), 0, 8));
}

function lms_sanitize_domain($domain) {
    $domain = strtolower(trim($domain));
    $domain = preg_replace('/^https?:\/\//', '', $domain);
    $domain = preg_replace('/^www\./', '', $domain);
    $domain = preg_replace('/\/.*$/', '', $domain);
    return $domain;
}

function lms_log($level, $message, $context = []) {
    $logger = new LMSLogger();
    $logger->log($level, $message, $context);
}

// Initialize system
function lms_init() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
    
    // Set security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (isset($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Initialize the system
lms_init();