<?php
/**
 * Zwicky Technology License Management System
 * User Profile
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

require_once '../config/config.php';

$auth = new LMSAdminAuth();

// Check authentication
if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$current_user = $auth->getCurrentUser();
$license_manager = new LMSLicenseManager();

// Get statistics
$stats = $license_manager->getStatistics();

$page_title = 'My Profile';
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
                            <i class="fas fa-user"></i>
                            My Profile
                        </h1>
                        <p class="page-description">Manage your account settings and preferences</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                    <!-- Profile Card -->
                    <div class="card">
                        <div class="card-body" style="text-align: center;">
                            <div style="width: 120px; height: 120px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px; margin: 0 auto 20px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($current_user['full_name']); ?></h3>
                            <p style="color: #6c757d; margin-bottom: 20px;">@<?php echo htmlspecialchars($current_user['username']); ?></p>
                            <span class="badge badge-<?php echo $current_user['role'] == 'admin' ? 'danger' : 'primary'; ?>" style="font-size: 14px;">
                                <?php echo ucfirst($current_user['role']); ?>
                            </span>
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                                <p style="margin: 5px 0;"><i class="fas fa-envelope" style="color: #667eea; width: 20px;"></i> <?php echo htmlspecialchars($current_user['email']); ?></p>
                                <p style="margin: 5px 0;"><i class="fas fa-calendar" style="color: #667eea; width: 20px;"></i> Joined <?php echo date('M d, Y', strtotime($current_user['created_at'])); ?></p>
                                <?php if ($current_user['last_login']): ?>
                                <p style="margin: 5px 0;"><i class="fas fa-clock" style="color: #667eea; width: 20px;"></i> Last login <?php echo date('M d, Y H:i', strtotime($current_user['last_login'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Settings -->
                    <div>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-user-edit"></i>
                                    Profile Information
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['full_name']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['username']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($current_user['email']); ?>">
                                </div>
                                <button class="btn btn-primary" onclick="alert('Update profile feature coming soon!')">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </div>
                        </div>

                        <div class="card" style="margin-top: 20px;">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-lock"></i>
                                    Change Password
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input type="password" class="form-control" placeholder="Enter current password">
                                </div>
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" class="form-control" placeholder="Enter new password">
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" class="form-control" placeholder="Confirm new password">
                                </div>
                                <button class="btn btn-primary" onclick="alert('Change password feature coming soon!')">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </div>
                        </div>

                        <div class="card" style="margin-top: 20px;">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-shield-alt"></i>
                                    Security Settings
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox"> Enable Two-Factor Authentication
                                    </label>
                                    <small style="display: block; color: #6c757d; margin-top: 5px;">
                                        Add an extra layer of security to your account
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" checked> Email notifications for login attempts
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox"> Require password change every 90 days
                                    </label>
                                </div>
                                <button class="btn btn-primary" onclick="alert('Update security settings feature coming soon!')">
                                    <i class="fas fa-save"></i> Update Security Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
