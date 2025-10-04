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
    <style>
        .profile-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 24px;
            color: white;
            display: flex;
            align-items: center;
            gap: 30px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.2);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            flex-shrink: 0;
        }
        
        .profile-info h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .profile-meta {
            display: flex;
            gap: 24px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        
        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            opacity: 0.95;
        }
        
        .profile-meta-item i {
            width: 20px;
            text-align: center;
        }
        
        .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .profile-card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .profile-card-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }
        
        .profile-card-title i {
            color: #2563eb;
            font-size: 20px;
        }
        
        .profile-card-body {
            padding: 28px 24px 24px 24px;
            text-align: left;
        }
        
        .profile-card-body > *:last-child {
            margin-bottom: 0;
        }
        
        .profile-card-body .form-group:first-child {
            margin-top: 0;
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
            width: 100%;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 10px;
            position: relative;
            z-index: 0;
            background: transparent;
            padding: 0;
            padding-left: 0;
            margin-left: 0;
            text-align: left;
            width: 100%;
        }
        
        /* Special styling for password field labels to align with input text */
        .password-field-label {
            padding-left: 0px !important;
            margin-left: 0px !important;
            text-indent: 0 !important;
        }
        
        .form-control-profile {
            width: 100% !important;
            padding: 14px 16px !important;
            border: 2px solid #cbd5e1 !important;
            border-radius: 8px !important;
            font-size: 15px !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
            transition: all 0.2s !important;
            background: white !important;
            color: #1e293b !important;
            display: block !important;
            height: auto !important;
            min-height: 48px !important;
            line-height: 1.5 !important;
            box-sizing: border-box !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin-top: 0 !important;
            position: relative !important;
            z-index: 1 !important;
        }
        
        /* Prevent autofill styling issues */
        .form-control-profile:-webkit-autofill,
        .form-control-profile:-webkit-autofill:hover,
        .form-control-profile:-webkit-autofill:focus {
            -webkit-text-fill-color: #1e293b !important;
            -webkit-box-shadow: 0 0 0 1000px white inset !important;
            box-shadow: 0 0 0 1000px white inset !important;
            background-color: white !important;
            transition: background-color 5000s ease-in-out 0s;
        }
        
        .form-control-profile::placeholder {
            color: #94a3b8 !important;
            opacity: 1 !important;
        }
        
        .form-control-profile:focus {
            outline: none !important;
            border-color: #2563eb !important;
            background: white !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
        }
        
        .form-control-profile:hover {
            border-color: #94a3b8 !important;
        }
        
        .form-control-profile:disabled,
        .form-control-profile[readonly] {
            background: #f8fafc !important;
            cursor: not-allowed !important;
            color: #64748b !important;
            border-color: #e2e8f0 !important;
            -webkit-text-fill-color: #64748b !important;
        }
        
        .form-control-profile[readonly]:hover {
            border-color: #e2e8f0 !important;
        }
        
        .btn-profile {
            padding: 12px 24px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-profile:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }
        
        .btn-profile:active {
            transform: translateY(0);
        }
        
        .btn-profile-secondary {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
        }
        
        .btn-profile-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .password-input-wrapper {
            position: relative !important;
            width: 100% !important;
            margin-bottom: 0 !important;
            display: block !important;
            min-height: 48px !important;
            margin-top: 0 !important;
        }
        
        .password-input-wrapper .form-control-profile {
            padding-left: 48px !important;
            padding-right: 48px !important;
            position: relative !important;
            z-index: 1 !important;
            width: 100% !important;
        }
        
        .password-icon {
            position: absolute !important;
            left: 16px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: #64748b !important;
            font-size: 18px !important;
            pointer-events: none !important;
            z-index: 3 !important;
            line-height: 1 !important;
        }
        
        .password-toggle-btn {
            position: absolute !important;
            right: 8px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            background: transparent !important;
            border: none !important;
            color: #64748b !important;
            cursor: pointer !important;
            padding: 10px !important;
            border-radius: 6px !important;
            transition: all 0.2s !important;
            z-index: 3 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 36px !important;
            height: 36px !important;
        }
        
        .password-toggle-btn:hover {
            color: #2563eb !important;
            background: #f1f5f9 !important;
        }
        
        .password-toggle-btn:focus {
            outline: 2px solid #2563eb !important;
            outline-offset: 2px !important;
        }
        
        .input-group {
            position: relative;
            width: 100%;
            display: block;
        }
        
        .password-strength {
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            margin-top: 10px;
            overflow: hidden;
            position: relative;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 3px;
            position: absolute;
            left: 0;
            top: 0;
        }
        
        .password-strength-weak {
            width: 33%;
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
        }
        
        .password-strength-medium {
            width: 66%;
            background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
        }
        
        .password-strength-strong {
            width: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        }
        
        .password-hint {
            font-size: 13px;
            color: #64748b;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            line-height: 1.5;
            text-align: left;
            width: 100%;
            padding-left: 0;
            margin-left: 0;
        }
        
        .password-hint i {
            font-size: 12px;
            flex-shrink: 0;
        }
        
        .password-hint span {
            flex: 1;
            text-align: left;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 12px;
            transition: all 0.2s;
        }
        
        .checkbox-wrapper:hover {
            background: #f1f5f9;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            cursor: pointer;
        }
        
        .checkbox-label {
            flex: 1;
            text-align: left;
        }
        
        .checkbox-label strong {
            display: block;
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 4px;
            text-align: left;
        }
        
        .checkbox-label small {
            display: block;
            color: #64748b;
            font-size: 13px;
            text-align: left;
        }
        
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 20px;
        }
        
        .stat-mini {
            text-align: center;
            padding: 16px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 8px;
        }
        
        .stat-mini-value {
            font-size: 24px;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 4px;
        }
        
        .stat-mini-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-mini {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="content-area">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($current_user['full_name']); ?></h2>
                        <div class="profile-meta">
                            <div class="profile-meta-item">
                                <i class="fas fa-at"></i>
                                <span><?php echo htmlspecialchars($current_user['username']); ?></span>
                            </div>
                            <div class="profile-meta-item">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($current_user['email']); ?></span>
                            </div>
                            <div class="profile-meta-item">
                                <i class="fas fa-calendar-plus"></i>
                                <span>Joined <?php echo date('M d, Y', strtotime($current_user['created_at'])); ?></span>
                            </div>
                            <?php if ($current_user['last_login']): ?>
                            <div class="profile-meta-item">
                                <i class="fas fa-clock"></i>
                                <span>Last login <?php echo date('M d, Y H:i', strtotime($current_user['last_login'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="profile-badge">
                            <i class="fas fa-<?php echo $current_user['role'] == 'admin' ? 'shield-halved' : 'user'; ?>"></i>
                            <?php echo ucfirst($current_user['role']); ?>
                        </div>
                    </div>
                </div>

                <!-- Statistics Mini -->
                <div class="stats-mini">
                    <div class="stat-mini">
                        <div class="stat-mini-value"><?php echo number_format($stats['total_licenses']); ?></div>
                        <div class="stat-mini-label">Total Licenses</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-value"><?php echo number_format($stats['active_licenses']); ?></div>
                        <div class="stat-mini-label">Active Licenses</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-value"><?php echo number_format($stats['total_activations']); ?></div>
                        <div class="stat-mini-label">Activations</div>
                    </div>
                </div>

                <!-- Profile Cards Grid -->
                <div class="profile-grid">
                    <!-- Profile Information Card -->
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h3 class="profile-card-title">
                                <i class="fas fa-user-edit"></i>
                                Profile Information
                            </h3>
                        </div>
                        <div class="profile-card-body">
                            <div class="form-group">
                                <label for="fullName">Full Name</label>
                                <input type="text" id="fullName" class="form-control-profile" value="<?php echo htmlspecialchars($current_user['full_name']); ?>" placeholder="Enter your full name">
                            </div>
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" class="form-control-profile" value="<?php echo htmlspecialchars($current_user['username']); ?>" placeholder="Username (cannot be changed)" readonly>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" class="form-control-profile" value="<?php echo htmlspecialchars($current_user['email']); ?>" placeholder="Enter your email address">
                            </div>
                            <button class="btn-profile" onclick="alert('Update profile feature coming soon!')">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </div>

                    <!-- Change Password Card -->
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h3 class="profile-card-title">
                                <i class="fas fa-lock"></i>
                                Change Password
                            </h3>
                        </div>
                        <div class="profile-card-body">
                            <div class="form-group">
                                <label for="currentPassword" class="password-field-label">Current Password</label>
                                <div class="password-input-wrapper">
                                    <i class="fas fa-lock password-icon"></i>
                                    <input type="password" id="currentPassword" class="form-control-profile" placeholder="Enter your current password">
                                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('currentPassword', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="newPassword" class="password-field-label">New Password</label>
                                <div class="password-input-wrapper">
                                    <i class="fas fa-key password-icon"></i>
                                    <input type="password" id="newPassword" class="form-control-profile" placeholder="Enter a strong new password" oninput="checkPasswordStrength(this.value)">
                                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('newPassword', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="strengthBar"></div>
                                </div>
                                <div class="password-hint">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Use at least 8 characters with letters, numbers, and symbols</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword" class="password-field-label">Confirm New Password</label>
                                <div class="password-input-wrapper">
                                    <i class="fas fa-check-circle password-icon"></i>
                                    <input type="password" id="confirmPassword" class="form-control-profile" placeholder="Re-enter your new password">
                                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('confirmPassword', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <button class="btn-profile" onclick="validateAndChangePassword()">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </div>

                    <!-- Security Settings Card -->
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h3 class="profile-card-title">
                                <i class="fas fa-shield-alt"></i>
                                Security Settings
                            </h3>
                        </div>
                        <div class="profile-card-body">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="2fa">
                                <label class="checkbox-label" for="2fa">
                                    <strong>Two-Factor Authentication</strong>
                                    <small>Add an extra layer of security to your account</small>
                                </label>
                            </div>
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="emailNotif" checked>
                                <label class="checkbox-label" for="emailNotif">
                                    <strong>Email Login Notifications</strong>
                                    <small>Get notified about login attempts via email</small>
                                </label>
                            </div>
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="passExpiry">
                                <label class="checkbox-label" for="passExpiry">
                                    <strong>Password Expiration</strong>
                                    <small>Require password change every 90 days</small>
                                </label>
                            </div>
                            <button class="btn-profile" onclick="alert('Update security settings feature coming soon!')">
                                <i class="fas fa-save"></i> Update Security Settings
                            </button>
                        </div>
                    </div>

                    <!-- Account Settings Card -->
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h3 class="profile-card-title">
                                <i class="fas fa-cog"></i>
                                Account Settings
                            </h3>
                        </div>
                        <div class="profile-card-body">
                            <div class="form-group">
                                <label>Account Type</label>
                                <input type="text" class="form-control-profile" value="<?php echo ucfirst($current_user['role']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Account Status</label>
                                <input type="text" class="form-control-profile" value="Active" readonly>
                            </div>
                            <div class="form-group">
                                <label>Member Since</label>
                                <input type="text" class="form-control-profile" value="<?php echo date('F d, Y', strtotime($current_user['created_at'])); ?>" readonly>
                            </div>
                            <button class="btn-profile btn-profile-secondary" onclick="alert('Export data feature coming soon!')">
                                <i class="fas fa-download"></i> Export Account Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePasswordVisibility(fieldId, button) {
            const field = document.getElementById(fieldId);
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Check password strength
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('strengthBar');
            
            if (!password) {
                strengthBar.className = 'password-strength-bar';
                strengthBar.style.width = '0';
                return;
            }
            
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Character variety checks
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            // Set strength class
            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('password-strength-weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('password-strength-medium');
            } else {
                strengthBar.classList.add('password-strength-strong');
            }
        }
        
        // Validate and change password
        function validateAndChangePassword() {
            const currentPass = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            
            if (!currentPass) {
                alert('Please enter your current password');
                return;
            }
            
            if (!newPass) {
                alert('Please enter a new password');
                return;
            }
            
            if (newPass.length < 8) {
                alert('Password must be at least 8 characters long');
                return;
            }
            
            if (newPass !== confirmPass) {
                alert('New password and confirmation do not match');
                return;
            }
            
            if (currentPass === newPass) {
                alert('New password must be different from current password');
                return;
            }
            
            alert('Change password feature coming soon!');
        }
    </script>
</body>
</html>
