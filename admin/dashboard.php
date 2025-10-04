<?php
/**
 * Zwicky Technology License Management System
 * Admin Dashboard
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
$logger = new LMSLogger();

// Get statistics
$stats = $license_manager->getStatistics();

// Get recent licenses
$recent_licenses = $license_manager->getAllLicenses(1, 5);

// Get recent logs
$recent_logs = $logger->getLogs(10);

// Page title
$page_title = 'Dashboard';
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
                <!-- Header Section -->
                <div class="page-header">
                    <div class="page-header-content">
                        <h1 class="page-title">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </h1>
                        <p class="page-description">Monitor your license system overview and key metrics</p>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_licenses']); ?></div>
                        <div class="stat-label">Total Licenses</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['active_licenses']); ?></div>
                        <div class="stat-label">Active Licenses</div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['expired_licenses']); ?></div>
                        <div class="stat-label">Expired Licenses</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_activations']); ?></div>
                        <div class="stat-label">Active Domains</div>
                    </div>
                </div>
                
                <!-- License Expiration Monitoring Widgets -->
                <?php include 'includes/license-expiration-widget.php'; ?>
                
                <!-- Recent Licenses -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-key"></i>
                            Recent Licenses
                        </h3>
                        <a href="licenses.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i>
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_licenses['licenses'])): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>License Key</th>
                                            <th>Product</th>
                                            <th>Customer</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Expires</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_licenses['licenses'] as $license): ?>
                                            <tr>
                                                <td>
                                                    <code><?php echo htmlspecialchars($license['license_key']); ?></code>
                                                </td>
                                                <td><?php echo htmlspecialchars($license['product_name']); ?></td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($license['customer_name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($license['customer_email']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $license['status']; ?>">
                                                        <?php echo ucfirst($license['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M j, Y', strtotime($license['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($license['expires_at']): ?>
                                                        <small><?php echo date('M j, Y', strtotime($license['expires_at'])); ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">Never</small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px;">
                                <i class="fas fa-key" style="font-size: 48px; color: #e9ecef; margin-bottom: 15px;"></i>
                                <p class="text-muted">No licenses found</p>
                                <a href="licenses.php?action=create" class="btn btn-primary">
                                    <i class="fas fa-plus"></i>
                                    Create First License
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- System Activity -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history"></i>
                            Recent Activity
                        </h3>
                        <a href="logs.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-list"></i>
                            View All Logs
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_logs)): ?>
                            <div class="activity-feed">
                                <?php foreach ($recent_logs as $log): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <?php
                                            $icon_class = 'fas fa-info-circle';
                                            $icon_color = '#17a2b8';
                                            
                                            switch ($log['level']) {
                                                case 'ERROR':
                                                    $icon_class = 'fas fa-exclamation-triangle';
                                                    $icon_color = '#dc3545';
                                                    break;
                                                case 'WARNING':
                                                    $icon_class = 'fas fa-exclamation-circle';
                                                    $icon_color = '#ffc107';
                                                    break;
                                                case 'INFO':
                                                    $icon_class = 'fas fa-info-circle';
                                                    $icon_color = '#17a2b8';
                                                    break;
                                            }
                                            ?>
                                            <i class="<?php echo $icon_class; ?>" style="color: <?php echo $icon_color; ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-message">
                                                <?php echo htmlspecialchars($log['message']); ?>
                                            </div>
                                            <div class="activity-meta">
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?>
                                                    <?php if ($log['license_key']): ?>
                                                        • License: <?php echo htmlspecialchars($log['license_key']); ?>
                                                    <?php endif; ?>
                                                    <?php if ($log['ip_address']): ?>
                                                        • IP: <?php echo htmlspecialchars($log['ip_address']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px;">
                                <i class="fas fa-history" style="font-size: 48px; color: #e9ecef; margin-bottom: 15px;"></i>
                                <p class="text-muted">No recent activity</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-server"></i>
                            System Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>System Version:</strong></td>
                                        <td><?php echo LMS_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>API Endpoint:</strong></td>
                                        <td><code><?php echo LMS_API_URL; ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Database Status:</strong></td>
                                        <td>
                                            <?php if (testLMSConnection()): ?>
                                                <span class="badge badge-active">Connected</span>
                                            <?php else: ?>
                                                <span class="badge badge-inactive">Disconnected</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>PHP Version:</strong></td>
                                        <td><?php echo PHP_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Server Time:</strong></td>
                                        <td><?php echo date('M j, Y g:i A T'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Uptime:</strong></td>
                                        <td>
                                            <?php
                                            $uptime = time() - strtotime('2024-01-01'); // Approximate
                                            echo number_format($uptime / 86400, 0) . ' days';
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: 2px;
        }
        
        .activity-content {
            flex: 1;
            margin-left: 10px;
        }
        
        .activity-message {
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 5px;
        }
        
        .activity-meta {
            font-size: 12px;
        }
        
        .row {
            display: flex;
            margin: -15px;
        }
        
        .col-md-6 {
            flex: 0 0 50%;
            padding: 15px;
        }
        
        .table-borderless td {
            border: none;
            padding: 8px 0;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .row {
                flex-direction: column;
            }
            
            .col-md-6 {
                flex: none;
            }
        }
    </style>
    
    <script>
        // Auto-refresh stats every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
        
        // Add loading states for interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.href || this.type === 'submit') {
                        const spinner = '<i class="fas fa-spinner fa-spin"></i>';
                        const originalContent = this.innerHTML;
                        this.innerHTML = spinner + ' Loading...';
                        this.disabled = true;
                        
                        // Re-enable after 5 seconds (fallback)
                        setTimeout(() => {
                            this.innerHTML = originalContent;
                            this.disabled = false;
                        }, 5000);
                    }
                });
            });
        });
    </script>
</body>
</html>