<?php
/**
 * Zwicky Technology License Management System
 * Settings
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

require_once '../config/config.php';

$auth = new LMSAdminAuth();

// Check authentication and admin permission
if (!$auth->isAuthenticated() || !$auth->hasPermission('admin')) {
    header('Location: dashboard.php');
    exit;
}

$current_user = $auth->getCurrentUser();
$license_manager = new LMSLicenseManager();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        switch ($_POST['action']) {
            case 'save_settings':
                if (!isset($_POST['settings']) || !is_array($_POST['settings'])) {
                    echo json_encode(['success' => false, 'message' => 'Invalid settings data']);
                    exit;
                }
                
                $settings = $_POST['settings'];
                $updated = 0;
                $skipped = 0;
                
                foreach ($settings as $key => $value) {
                    // Sanitize input
                    $key = trim($key);
                    
                    // Handle checkbox values (unchecked checkboxes are not sent)
                    // Convert boolean values properly
                    if (is_array($value)) {
                        $value = implode("\n", $value);
                    } else {
                        $value = trim($value);
                    }
                    
                    // Check if setting exists before updating
                    $checkStmt = $conn->prepare("SELECT id, setting_type FROM " . LMS_TABLE_PREFIX . "settings WHERE setting_key = ?");
                    $checkStmt->execute([$key]);
                    $existingSetting = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existingSetting) {
                        // Update existing setting
                        $stmt = $conn->prepare("
                            UPDATE " . LMS_TABLE_PREFIX . "settings 
                            SET setting_value = ?, updated_by = ?, updated_at = NOW()
                            WHERE setting_key = ?
                        ");
                        $stmt->execute([$value, $current_user['id'], $key]);
                        $updated++;
                    } else {
                        // Setting doesn't exist, skip it
                        $skipped++;
                    }
                }
                
                $message = "Successfully updated $updated setting(s)";
                if ($skipped > 0) {
                    $message .= " ($skipped setting(s) skipped - not found in database)";
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => $message
                ]);
                exit;
                
            case 'test_email':
                if (!isset($_POST['email'])) {
                    echo json_encode(['success' => false, 'message' => 'Email address required']);
                    exit;
                }
                
                // Get email settings
                $stmt = $conn->prepare("SELECT setting_key, setting_value FROM " . LMS_TABLE_PREFIX . "settings WHERE setting_group = 'email'");
                $stmt->execute();
                $emailSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                
                // Validate SMTP settings
                if (empty($emailSettings['smtp_host']) || empty($emailSettings['from_email'])) {
                    echo json_encode(['success' => false, 'message' => 'Please configure SMTP settings first']);
                    exit;
                }
                
                // Here you would send a test email using the configured SMTP settings
                // For now, we'll just validate the configuration
                echo json_encode([
                    'success' => true, 
                    'message' => 'Email configuration validated. Test email functionality coming soon.'
                ]);
                exit;
                
            case 'clear_cache':
                // Clear any cached settings or temporary files
                $cacheDir = '../cache/';
                if (is_dir($cacheDir)) {
                    $files = glob($cacheDir . '*');
                    $cleared = 0;
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                            $cleared++;
                        }
                    }
                    echo json_encode(['success' => true, 'message' => "Cleared $cleared cache file(s)"]);
                } else {
                    echo json_encode(['success' => true, 'message' => 'No cache to clear']);
                }
                exit;
                
            case 'backup_database':
                // Trigger database backup
                echo json_encode([
                    'success' => true, 
                    'message' => 'Database backup initiated. This feature is coming soon.'
                ]);
                exit;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// Fetch all settings grouped by category
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT setting_key, setting_value, setting_group, setting_type FROM " . LMS_TABLE_PREFIX . "settings ORDER BY setting_group, setting_key");
    $stmt->execute();
    $allSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group settings by category
    $settings = [];
    foreach ($allSettings as $setting) {
        $group = $setting['setting_group'];
        if (!isset($settings[$group])) {
            $settings[$group] = [];
        }
        $settings[$group][$setting['setting_key']] = $setting['setting_value'];
    }
    
    // Get statistics
    $stats = $license_manager->getStatistics();
    
} catch (Exception $e) {
    $settings = [];
    $error_message = "Error loading settings: " . $e->getMessage();
}

$page_title = 'Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Zwicky License Manager</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .settings-tab {
            display: inline-block;
            padding: 12px 24px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px 8px 0 0;
            margin-right: 4px;
            cursor: pointer;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.2s;
        }
        .settings-tab:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        .settings-tab.active {
            background: #1d4dd4;
            color: white;
            border-color: #1d4dd4;
        }
        .settings-section {
            display: none;
        }
        .settings-section.active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group input[type="password"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            cursor: pointer;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }
        .settings-description {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="content-area" style="padding: 24px;">
                <!-- Page Header -->
                <div style="background: linear-gradient(135deg, #1d4dd4 0%, #1a3fb8 100%); padding: 32px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 6px rgba(29, 77, 212, 0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <div>
                            <h1 style="color: white; font-size: 28px; font-weight: 700; margin: 0 0 8px 0; display: flex; align-items: center; gap: 12px;">
                                <i class="fas fa-cog"></i>
                                System Settings
                            </h1>
                            <p style="color: rgba(255,255,255,0.85); font-size: 14px; margin: 0;">Configure system preferences and behavior</p>
                        </div>
                        <div style="display: flex; gap: 12px;">
                            <button onclick="clearCache()" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: all 0.2s;"
                                onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                                onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                <i class="fas fa-broom"></i> Clear Cache
                            </button>
                            <button onclick="window.location.href='diagnostic.php'" style="background: white; color: #1d4dd4; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all 0.2s;"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                                onmouseout="this.style.transform=''; this.style.boxShadow=''">
                                <i class="fas fa-stethoscope"></i> Run Diagnostics
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (isset($error_message)): ?>
                <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>

                <!-- Settings Tabs -->
                <div style="margin-bottom: 24px; border-bottom: 2px solid #e5e7eb;">
                    <div class="settings-tab active" onclick="switchTab('general')">
                        <i class="fas fa-cog"></i> General
                    </div>
                    <div class="settings-tab" onclick="switchTab('email')">
                        <i class="fas fa-envelope"></i> Email
                    </div>
                    <div class="settings-tab" onclick="switchTab('license')">
                        <i class="fas fa-key"></i> License
                    </div>
                    <div class="settings-tab" onclick="switchTab('notification')">
                        <i class="fas fa-bell"></i> Notifications
                    </div>
                    <div class="settings-tab" onclick="switchTab('security')">
                        <i class="fas fa-shield-alt"></i> Security
                    </div>
                    <div class="settings-tab" onclick="switchTab('system')">
                        <i class="fas fa-server"></i> System
                    </div>
                </div>

                <!-- General Settings Section -->
                <div id="general-section" class="settings-section active">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
                        <!-- System Information -->
                        <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                            <div style="padding: 20px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1f2937;">
                                    <i class="fas fa-info-circle" style="color: #1d4dd4; margin-right: 8px;"></i>
                                    System Information
                                </h3>
                            </div>
                            <div style="padding: 20px;">
                                <div class="form-group">
                                    <label>System Name</label>
                                    <input type="text" value="Zwicky License Manager" readonly style="background: #f9fafb;">
                                </div>
                                <div class="form-group">
                                    <label>Version</label>
                                    <input type="text" value="<?php echo LMS_VERSION; ?>" readonly style="background: #f9fafb;">
                                </div>
                                <div class="form-group">
                                    <label>Environment</label>
                                    <input type="text" value="<?php echo LMS_ENVIRONMENT; ?>" readonly style="background: #f9fafb;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>PHP Version</label>
                                    <input type="text" value="<?php echo phpversion(); ?>" readonly style="background: #f9fafb;">
                                </div>
                            </div>
                        </div>

                        <!-- Database Information -->
                        <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                            <div style="padding: 20px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1f2937;">
                                    <i class="fas fa-database" style="color: #1d4dd4; margin-right: 8px;"></i>
                                    Database Information
                                </h3>
                            </div>
                            <div style="padding: 20px;">
                                <div class="form-group">
                                    <label>Database Host</label>
                                    <input type="text" value="<?php echo LMS_DB_HOST; ?>" readonly style="background: #f9fafb;">
                                </div>
                                <div class="form-group">
                                    <label>Database Name</label>
                                    <input type="text" value="<?php echo LMS_DB_NAME; ?>" readonly style="background: #f9fafb;">
                                </div>
                                <div class="form-group">
                                    <label>Database User</label>
                                    <input type="text" value="<?php echo LMS_DB_USER; ?>" readonly style="background: #f9fafb;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>Table Prefix</label>
                                    <input type="text" value="<?php echo LMS_TABLE_PREFIX; ?>" readonly style="background: #f9fafb;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Settings Section -->
                <div id="email-section" class="settings-section">
                    <form id="emailSettingsForm">
                        <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                            <div style="padding: 20px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1f2937;">
                                    <i class="fas fa-envelope" style="color: #1d4dd4; margin-right: 8px;"></i>
                                    Email (SMTP) Configuration
                                </h3>
                            </div>
                            <div style="padding: 20px;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                                    <div class="form-group">
                                        <label>SMTP Host</label>
                                        <input type="text" name="settings[smtp_host]" value="<?php echo htmlspecialchars($settings['email']['smtp_host'] ?? ''); ?>" placeholder="smtp.example.com">
                                        <div class="settings-description">SMTP server hostname</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>SMTP Port</label>
                                        <input type="number" name="settings[smtp_port]" value="<?php echo htmlspecialchars($settings['email']['smtp_port'] ?? '587'); ?>" placeholder="587">
                                        <div class="settings-description">Usually 587 (TLS) or 465 (SSL)</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>SMTP Username</label>
                                        <input type="text" name="settings[smtp_username]" value="<?php echo htmlspecialchars($settings['email']['smtp_username'] ?? ''); ?>" placeholder="username@example.com">
                                        <div class="settings-description">SMTP authentication username</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>SMTP Password</label>
                                        <input type="password" name="settings[smtp_password]" value="<?php echo htmlspecialchars($settings['email']['smtp_password'] ?? ''); ?>" placeholder="••••••••">
                                        <div class="settings-description">SMTP authentication password</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>From Email</label>
                                        <input type="email" name="settings[from_email]" value="<?php echo htmlspecialchars($settings['email']['from_email'] ?? ''); ?>" placeholder="noreply@example.com">
                                        <div class="settings-description">Email address for outgoing messages</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>From Name</label>
                                        <input type="text" name="settings[from_name]" value="<?php echo htmlspecialchars($settings['email']['from_name'] ?? 'Zwicky License Manager'); ?>" placeholder="Zwicky License Manager">
                                        <div class="settings-description">Sender name for outgoing messages</div>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                                    <button type="submit" style="background: #1d4dd4; color: white; padding: 10px 24px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; font-size: 14px;"
                                        onmouseover="this.style.background='#1a3fb8'" 
                                        onmouseout="this.style.background='#1d4dd4'">
                                        <i class="fas fa-save"></i> Save Email Settings
                                    </button>
                                    <button type="button" onclick="testEmail()" style="background: #10b981; color: white; padding: 10px 24px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; font-size: 14px;"
                                        onmouseover="this.style.background='#059669'" 
                                        onmouseout="this.style.background='#10b981'">
                                        <i class="fas fa-paper-plane"></i> Test Email
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- License Settings Section -->
                <div id="license-section" class="settings-section">
                    <form id="licenseSettingsForm">
                        <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                            <div style="padding: 20px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1f2937;">
                                    <i class="fas fa-key" style="color: #1d4dd4; margin-right: 8px;"></i>
                                    License Configuration
                                </h3>
                            </div>
                            <div style="padding: 20px;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                                    <div class="form-group">
                                        <label>Default License Validity (days)</label>
                                        <input type="number" name="settings[default_license_validity]" value="<?php echo htmlspecialchars($settings['license']['default_license_validity'] ?? '365'); ?>" min="1">
                                        <div class="settings-description">Default validity period for new licenses</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Default Max Activations</label>
                                        <input type="number" name="settings[default_max_activations]" value="<?php echo htmlspecialchars($settings['license']['default_max_activations'] ?? '1'); ?>" min="1">
                                        <div class="settings-description">Default maximum activations per license</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>License Key Length</label>
                                        <input type="number" name="settings[license_key_length]" value="<?php echo htmlspecialchars($settings['license']['license_key_length'] ?? '32'); ?>" min="16" max="64">
                                        <div class="settings-description">Length of generated license keys</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>License Key Format</label>
                                        <input type="text" name="settings[license_key_format]" value="<?php echo htmlspecialchars($settings['license']['license_key_format'] ?? 'XXXXX-XXXXX-XXXXX-XXXXX'); ?>" placeholder="XXXXX-XXXXX-XXXXX-XXXXX">
                                        <div class="settings-description">Format pattern for license keys (X = character)</div>
                                    </div>
                                </div>
                                
                                <div style="margin: 20px 0; padding: 20px; background: #f9fafb; border-radius: 8px;">
                                    <div class="form-group" style="margin-bottom: 12px;">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="settings[enable_domain_restrictions]" value="1" <?php echo (!empty($settings['license']['enable_domain_restrictions']) ? 'checked' : ''); ?>>
                                            <span>Enable Domain Restrictions</span>
                                        </label>
                                        <div class="settings-description">Restrict license activations to specific domains</div>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 12px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                                    <button type="submit" style="background: #1d4dd4; color: white; padding: 10px 24px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; font-size: 14px;"
                                        onmouseover="this.style.background='#1a3fb8'" 
                                        onmouseout="this.style.background='#1d4dd4'">
                                        <i class="fas fa-save"></i> Save License Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Notification Settings Section -->
                <div id="notification-section" class="settings-section">
                    <form id="notificationSettingsForm">
                        <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                            <div style="padding: 20px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1f2937;">
                                    <i class="fas fa-bell" style="color: #1d4dd4; margin-right: 8px;"></i>
                                    Notification Preferences
                                </h3>
                            </div>
                            <div style="padding: 20px;">
                                <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                    <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #374151;">Email Notifications</h4>
                                    
                                    <div class="form-group" style="margin-bottom: 12px;">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="settings[notify_license_creation]" value="1" <?php echo (!empty($settings['notification']['notify_license_creation']) ? 'checked' : ''); ?>>
                                            <span>Send email on license creation</span>
                                        </label>
                                        <div class="settings-description">Notify customer when a new license is created</div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 12px;">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="settings[notify_license_expiration]" value="1" <?php echo (!empty($settings['notification']['notify_license_expiration']) ? 'checked' : ''); ?>>
                                            <span>Send email on license expiration</span>
                                        </label>
                                        <div class="settings-description">Notify customer when their license expires</div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 12px;">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="settings[notify_license_activation]" value="1" <?php echo (!empty($settings['notification']['notify_license_activation']) ? 'checked' : ''); ?>>
                                            <span>Send email on license activation</span>
                                        </label>
                                        <div class="settings-description">Notify customer when their license is activated</div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="settings[notify_admin_on_activation]" value="1" <?php echo (!empty($settings['notification']['notify_admin_on_activation']) ? 'checked' : ''); ?>>
                                            <span>Notify admin on new activation</span>
                                        </label>
                                        <div class="settings-description">Send notification to admin when a license is activated</div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Expiration Warning (days before)</label>
                                    <input type="number" name="settings[expiration_warning_days]" value="<?php echo htmlspecialchars($settings['notification']['expiration_warning_days'] ?? '7'); ?>" min="1" max="90">
                                    <div class="settings-description">Send expiration warning email X days before license expires</div>
                                </div>
                                
                                <div style="display: flex; gap: 12px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                                    <button type="submit" style="background: #1d4dd4; color: white; padding: 10px 24px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; font-size: 14px;"
                                        onmouseover="this.style.background='#1a3fb8'" 
                                        onmouseout="this.style.background='#1d4dd4'">
                                        <i class="fas fa-save"></i> Save Notification Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Security Settings Section -->
                <div id="security-section" class="settings-section">
                    <form id="securitySettingsForm">
                        <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                            <div style="padding: 20px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1f2937;">
                                    <i class="fas fa-shield-alt" style="color: #1d4dd4; margin-right: 8px;"></i>
                                    Security Configuration
                                </h3>
                            </div>
                            <div style="padding: 20px;">
                                <div style="background: #fff7ed; border: 1px solid #fed7aa; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                                    <div style="display: flex; align-items: start; gap: 12px;">
                                        <i class="fas fa-exclamation-triangle" style="color: #ea580c; font-size: 20px; margin-top: 2px;"></i>
                                        <div>
                                            <div style="font-weight: 600; color: #9a3412; margin-bottom: 4px;">Security Notice</div>
                                            <div style="font-size: 13px; color: #9a3412;">Changes to security settings may affect system functionality. Test thoroughly after making changes.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #374151;">Rate Limiting</h4>
                                
                                <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                    <div class="form-group" style="margin-bottom: 16px;">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="settings[enable_rate_limiting]" value="1" <?php echo (!empty($settings['security']['enable_rate_limiting']) ? 'checked' : ''); ?>>
                                            <span>Enable API Rate Limiting</span>
                                        </label>
                                        <div class="settings-description">Protect API endpoints from abuse</div>
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label>Rate Limit Window (seconds)</label>
                                            <input type="number" name="settings[rate_limit_window]" value="<?php echo htmlspecialchars($settings['security']['rate_limit_window'] ?? '60'); ?>" min="10">
                                            <div class="settings-description">Time window for rate limiting</div>
                                        </div>
                                        
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label>Max Requests per Window</label>
                                            <input type="number" name="settings[rate_limit_max_requests]" value="<?php echo htmlspecialchars($settings['security']['rate_limit_max_requests'] ?? '60'); ?>" min="1">
                                            <div class="settings-description">Maximum allowed requests</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <h4 style="margin: 20px 0 16px 0; font-size: 14px; font-weight: 600; color: #374151;">IP Access Control</h4>
                                
                                <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                    <div class="form-group" style="margin-bottom: 16px;">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="settings[enable_ip_whitelist]" value="1" <?php echo (!empty($settings['security']['enable_ip_whitelist']) ? 'checked' : ''); ?>>
                                            <span>Enable IP Whitelist</span>
                                        </label>
                                        <div class="settings-description">Restrict API access to specific IP addresses</div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label>Allowed IP Addresses</label>
                                        <textarea name="settings[allowed_ips]" rows="5" placeholder="Enter one IP address per line&#10;Example:&#10;192.168.1.100&#10;10.0.0.0/8"><?php echo htmlspecialchars($settings['security']['allowed_ips'] ?? ''); ?></textarea>
                                        <div class="settings-description">One IP address or CIDR range per line</div>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 12px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                                    <button type="submit" style="background: #1d4dd4; color: white; padding: 10px 24px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; font-size: 14px;"
                                        onmouseover="this.style.background='#1a3fb8'" 
                                        onmouseout="this.style.background='#1d4dd4'">
                                        <i class="fas fa-save"></i> Save Security Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- System Settings Section -->
                <div id="system-section" class="settings-section">
                    <form id="systemSettingsForm">
                        <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                            <div style="padding: 20px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1f2937;">
                                    <i class="fas fa-server" style="color: #1d4dd4; margin-right: 8px;"></i>
                                    System Configuration
                                </h3>
                            </div>
                            <div style="padding: 20px;">
                                <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #374151;">Maintenance Mode</h4>
                                
                                <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                    <div class="form-group" style="margin-bottom: 16px;">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="settings[maintenance_mode]" value="1" <?php echo (!empty($settings['system']['maintenance_mode']) ? 'checked' : ''); ?>>
                                            <span>Enable Maintenance Mode</span>
                                        </label>
                                        <div class="settings-description">Display maintenance message to users</div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label>Maintenance Message</label>
                                        <textarea name="settings[maintenance_message]" rows="3"><?php echo htmlspecialchars($settings['system']['maintenance_message'] ?? 'System is under maintenance. Please try again later.'); ?></textarea>
                                        <div class="settings-description">Message shown to users during maintenance</div>
                                    </div>
                                </div>
                                
                                <h4 style="margin: 20px 0 16px 0; font-size: 14px; font-weight: 600; color: #374151;">Logging</h4>
                                
                                <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                    <div class="form-group" style="margin-bottom: 16px;">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="settings[enable_logging]" value="1" <?php echo (!empty($settings['system']['enable_logging']) ? 'checked' : ''); ?>>
                                            <span>Enable System Logging</span>
                                        </label>
                                        <div class="settings-description">Log system events and errors</div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label>Log Retention Period (days)</label>
                                        <input type="number" name="settings[log_retention_days]" value="<?php echo htmlspecialchars($settings['system']['log_retention_days'] ?? '30'); ?>" min="1" max="365">
                                        <div class="settings-description">Automatically delete logs older than this period</div>
                                    </div>
                                </div>
                                
                                <h4 style="margin: 20px 0 16px 0; font-size: 14px; font-weight: 600; color: #374151;">Regional Settings</h4>
                                
                                <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label>System Timezone</label>
                                        <select name="settings[timezone]" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                                            <?php
                                            $timezones = ['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Tokyo', 'Asia/Shanghai', 'Australia/Sydney'];
                                            $currentTz = $settings['system']['timezone'] ?? 'UTC';
                                            foreach ($timezones as $tz) {
                                                $selected = ($tz === $currentTz) ? 'selected' : '';
                                                echo "<option value=\"$tz\" $selected>$tz</option>";
                                            }
                                            ?>
                                        </select>
                                        <div class="settings-description">Timezone for date/time display</div>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 12px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                                    <button type="submit" style="background: #1d4dd4; color: white; padding: 10px 24px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; font-size: 14px;"
                                        onmouseover="this.style.background='#1a3fb8'" 
                                        onmouseout="this.style.background='#1d4dd4'">
                                        <i class="fas fa-save"></i> Save System Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Tab switching
    function switchTab(tabName) {
        // Hide all sections
        document.querySelectorAll('.settings-section').forEach(section => {
            section.classList.remove('active');
        });
        
        // Remove active class from all tabs
        document.querySelectorAll('.settings-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show selected section
        document.getElementById(tabName + '-section').classList.add('active');
        
        // Add active class to clicked tab
        event.target.closest('.settings-tab').classList.add('active');
    }

    // Handle form submissions
    async function saveSettings(formId) {
        const form = document.getElementById(formId);
        const formData = new FormData(form);
        
        // Handle checkboxes - unchecked checkboxes don't appear in FormData
        // We need to explicitly set them to '0' for boolean settings
        const checkboxes = form.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            const name = checkbox.name;
            if (name) {
                // Remove any existing entry for this checkbox
                formData.delete(name);
                // Set to '1' if checked, '0' if unchecked
                formData.append(name, checkbox.checked ? '1' : '0');
            }
        });
        
        formData.append('action', 'save_settings');
        
        try {
            const response = await fetch('settings.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification(result.message, 'success');
            } else {
                showNotification(result.message || 'Failed to save settings', 'error');
            }
        } catch (error) {
            showNotification('An error occurred while saving settings', 'error');
            console.error('Error:', error);
        }
    }

    // Email Settings Form
    document.getElementById('emailSettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await saveSettings('emailSettingsForm');
    });

    // License Settings Form
    document.getElementById('licenseSettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await saveSettings('licenseSettingsForm');
    });

    // Notification Settings Form
    document.getElementById('notificationSettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await saveSettings('notificationSettingsForm');
    });

    // Security Settings Form
    document.getElementById('securitySettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (confirm('Are you sure you want to update security settings? This may affect API access.')) {
            await saveSettings('securitySettingsForm');
        }
    });

    // System Settings Form
    document.getElementById('systemSettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await saveSettings('systemSettingsForm');
    });

    // Test Email Function
    async function testEmail() {
        const email = prompt('Enter email address to send test email:');
        if (!email) return;
        
        const formData = new FormData();
        formData.append('action', 'test_email');
        formData.append('email', email);
        
        try {
            const response = await fetch('settings.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification(result.message, 'success');
            } else {
                showNotification(result.message || 'Failed to send test email', 'error');
            }
        } catch (error) {
            showNotification('An error occurred while sending test email', 'error');
            console.error('Error:', error);
        }
    }

    // Clear Cache Function
    async function clearCache() {
        if (!confirm('Are you sure you want to clear the cache?')) return;
        
        const formData = new FormData();
        formData.append('action', 'clear_cache');
        
        try {
            const response = await fetch('settings.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification(result.message, 'success');
            } else {
                showNotification(result.message || 'Failed to clear cache', 'error');
            }
        } catch (error) {
            showNotification('An error occurred while clearing cache', 'error');
            console.error('Error:', error);
        }
    }

    // Notification System
    function showNotification(message, type = 'info') {
        const colors = {
            success: { bg: '#10b981', icon: 'fa-check-circle' },
            error: { bg: '#ef4444', icon: 'fa-exclamation-circle' },
            info: { bg: '#1d4dd4', icon: 'fa-info-circle' }
        };
        
        const color = colors[type] || colors.info;
        
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${color.bg};
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
            max-width: 400px;
        `;
        
        notification.innerHTML = `
            <i class="fas ${color.icon}" style="font-size: 18px;"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }

    // Animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>
