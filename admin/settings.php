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

// Get statistics
$stats = $license_manager->getStatistics();

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
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="content-area">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="page-header-content">
                        <h1 class="page-title">
                            <i class="fas fa-cog"></i>
                            Settings
                        </h1>
                        <p class="page-description">Configure system settings and preferences</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
                    <!-- General Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-cog"></i>
                                General Settings
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>System Name</label>
                                <input type="text" class="form-control" value="Zwicky License Manager" readonly>
                            </div>
                            <div class="form-group">
                                <label>Version</label>
                                <input type="text" class="form-control" value="<?php echo LMS_VERSION; ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Environment</label>
                                <input type="text" class="form-control" value="<?php echo LMS_ENVIRONMENT; ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Email Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-envelope"></i>
                                Email Settings
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>SMTP Host</label>
                                <input type="text" class="form-control" placeholder="smtp.example.com">
                            </div>
                            <div class="form-group">
                                <label>SMTP Port</label>
                                <input type="number" class="form-control" placeholder="587">
                            </div>
                            <div class="form-group">
                                <label>From Email</label>
                                <input type="email" class="form-control" placeholder="noreply@example.com">
                            </div>
                            <button class="btn btn-primary" onclick="alert('Save feature coming soon!')">
                                <i class="fas fa-save"></i> Save Email Settings
                            </button>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-shield-alt"></i>
                                Security Settings
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Session Timeout (seconds)</label>
                                <input type="number" class="form-control" value="<?php echo LMS_SESSION_LIFETIME; ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Rate Limit Window (seconds)</label>
                                <input type="number" class="form-control" value="60" readonly>
                            </div>
                            <div class="form-group">
                                <label>Rate Limit Max Requests</label>
                                <input type="number" class="form-control" value="60" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- License Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-key"></i>
                                License Settings
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Default License Validity (days)</label>
                                <input type="number" class="form-control" value="365" placeholder="365">
                            </div>
                            <div class="form-group">
                                <label>Default Max Activations</label>
                                <input type="number" class="form-control" value="1" placeholder="1">
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" checked> Enable Domain Restrictions
                                </label>
                            </div>
                            <button class="btn btn-primary" onclick="alert('Save feature coming soon!')">
                                <i class="fas fa-save"></i> Save License Settings
                            </button>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bell"></i>
                                Notification Settings
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" checked> Email on License Creation
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" checked> Email on License Expiration
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox"> Email on License Activation
                                </label>
                            </div>
                            <div class="form-group">
                                <label>Expiration Warning (days before)</label>
                                <input type="number" class="form-control" value="7" placeholder="7">
                            </div>
                            <button class="btn btn-primary" onclick="alert('Save feature coming soon!')">
                                <i class="fas fa-save"></i> Save Notification Settings
                            </button>
                        </div>
                    </div>

                    <!-- Database Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-database"></i>
                                Database Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Database Host</label>
                                <input type="text" class="form-control" value="<?php echo LMS_DB_HOST; ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Database Name</label>
                                <input type="text" class="form-control" value="<?php echo LMS_DB_NAME; ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Database User</label>
                                <input type="text" class="form-control" value="<?php echo LMS_DB_USER; ?>" readonly>
                            </div>
                            <button class="btn btn-secondary" onclick="window.location.href='diagnostic.php'">
                                <i class="fas fa-stethoscope"></i> Run Diagnostics
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
