<?php
/**
 * Zwicky Technology License Management System
 * Reports & Analytics
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
$db = getLMSDatabase();

// Get date range filters
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'overview';

// 1. License Statistics
$license_stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
        SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended,
        SUM(CASE WHEN DATE(expires_at) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon
    FROM " . LMS_TABLE_LICENSES
)->fetch(PDO::FETCH_ASSOC);

// 2. Activation Statistics
$activation_stats = $db->query("
    SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT license_id) as unique_licenses,
        COUNT(DISTINCT domain) as unique_domains,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_activations,
        SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as this_week
    FROM " . LMS_TABLE_ACTIVATIONS
)->fetch(PDO::FETCH_ASSOC);

// 3. Customer Statistics (from licenses table)
$customer_stats = $db->query("
    SELECT 
        COUNT(DISTINCT customer_email) as total,
        COUNT(DISTINCT CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN customer_email END) as new_last_30_days,
        COUNT(DISTINCT CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN customer_email END) as new_this_week
    FROM " . LMS_TABLE_LICENSES
)->fetch(PDO::FETCH_ASSOC);

// 4. License Status Distribution
$status_distribution = $db->query("
    SELECT status, COUNT(*) as count 
    FROM " . LMS_TABLE_LICENSES . "
    GROUP BY status
")->fetchAll(PDO::FETCH_ASSOC);

// 5. Top Products
$top_products = $db->query("
    SELECT product_name, COUNT(*) as count 
    FROM " . LMS_TABLE_LICENSES . "
    GROUP BY product_name 
    ORDER BY count DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// 6. Activations Trend (Last 30 Days)
$activations_trend = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM " . LMS_TABLE_ACTIVATIONS . "
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 7. License Creation Trend (Last 30 Days)
$license_trend = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM " . LMS_TABLE_LICENSES . "
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 8. Recent Activity Summary
$recent_activity = $db->query("
    SELECT 
        level,
        COUNT(*) as count,
        MAX(created_at) as last_occurrence
    FROM " . LMS_TABLE_LOGS . "
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY level
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// 9. Top Domains by Activation Count
$top_domains = $db->query("
    SELECT domain, COUNT(*) as activation_count
    FROM " . LMS_TABLE_ACTIVATIONS . "
    WHERE status = 'active'
    GROUP BY domain
    ORDER BY activation_count DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// 10. Expiration Report (Next 90 Days)
$expiration_breakdown = $db->query("
    SELECT 
        CASE 
            WHEN DATE(expires_at) < CURDATE() THEN 'Expired'
            WHEN DATE(expires_at) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'This Week'
            WHEN DATE(expires_at) BETWEEN DATE_ADD(CURDATE(), INTERVAL 8 DAY) AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'This Month'
            WHEN DATE(expires_at) BETWEEN DATE_ADD(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 'Next 3 Months'
            ELSE 'Future'
        END as period,
        COUNT(*) as count
    FROM " . LMS_TABLE_LICENSES . "
    GROUP BY period
    ORDER BY FIELD(period, 'Expired', 'This Week', 'This Month', 'Next 3 Months', 'Future')
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Reports & Analytics';
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="content-area" style="padding: 24px;">
                <!-- Page Header -->
                <div style="background: linear-gradient(135deg, #1d4dd4 0%, #1a3fb8 100%); padding: 32px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 6px rgba(29, 77, 212, 0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h1 style="color: white; font-size: 28px; font-weight: 700; margin: 0 0 8px 0; display: flex; align-items: center; gap: 12px;">
                                <i class="fas fa-chart-line"></i>
                                Reports & Analytics
                            </h1>
                            <p style="color: rgba(255,255,255,0.85); font-size: 14px; margin: 0;">Comprehensive insights and performance metrics</p>
                        </div>
                        <div style="display: flex; gap: 12px;">
                            <button onclick="window.print()" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: all 0.2s;"
                                onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                                onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                            <button onclick="exportReport()" style="background: white; color: #1d4dd4; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all 0.2s;"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                                onmouseout="this.style.transform=''; this.style.boxShadow=''">
                                <i class="fas fa-download"></i> Export Data
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; margin-bottom: 24px;">
                    <div class="card-body" style="padding: 20px;">
                        <form method="GET" action="" style="display: flex; gap: 12px; align-items: end; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px;">
                                <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    <i class="fas fa-calendar" style="margin-right: 6px;"></i> From Date
                                </label>
                                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                                    style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                            </div>
                            
                            <div style="flex: 1; min-width: 200px;">
                                <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    <i class="fas fa-calendar" style="margin-right: 6px;"></i> To Date
                                </label>
                                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                                    style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                            </div>
                            
                            <button type="submit" style="background: #1d4dd4; color: white; padding: 10px 24px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 8px;"
                                onmouseover="this.style.background='#1a3fb8'" 
                                onmouseout="this.style.background='#1d4dd4'">
                                <i class="fas fa-filter"></i> Apply Filter
                            </button>
                            
                            <?php if ($date_from != date('Y-m-d', strtotime('-30 days')) || $date_to != date('Y-m-d')): ?>
                            <a href="reports.php" style="padding: 10px 20px; background: #6b7280; color: white; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px;"
                                onmouseover="this.style.background='#4b5563'" 
                                onmouseout="this.style.background='#6b7280'">
                                <i class="fas fa-times"></i> Reset
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Total Licenses</div>
                                <div style="font-size: 32px; font-weight: 700; color: #1d4dd4;"><?php echo number_format($license_stats['total']); ?></div>
                                <div style="font-size: 12px; color: #10b981; margin-top: 4px;">
                                    <i class="fas fa-check-circle"></i> <?php echo number_format($license_stats['active']); ?> Active
                                </div>
                            </div>
                            <div style="width: 56px; height: 56px; background: #eff6ff; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-key" style="font-size: 24px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Total Activations</div>
                                <div style="font-size: 32px; font-weight: 700; color: #10b981;"><?php echo number_format($activation_stats['total']); ?></div>
                                <div style="font-size: 12px; color: #1d4dd4; margin-top: 4px;">
                                    <i class="fas fa-arrow-up"></i> <?php echo number_format($activation_stats['this_week']); ?> This Week
                                </div>
                            </div>
                            <div style="width: 56px; height: 56px; background: #f0fdf4; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-power-off" style="font-size: 24px; color: #10b981;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Active Domains</div>
                                <div style="font-size: 32px; font-weight: 700; color: #8b5cf6;"><?php echo number_format($activation_stats['unique_domains']); ?></div>
                                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                                    <i class="fas fa-globe"></i> Unique Installations
                                </div>
                            </div>
                            <div style="width: 56px; height: 56px; background: #faf5ff; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-globe" style="font-size: 24px; color: #8b5cf6;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Expiring Soon</div>
                                <div style="font-size: 32px; font-weight: 700; color: #f59e0b;"><?php echo number_format($license_stats['expiring_soon']); ?></div>
                                <div style="font-size: 12px; color: #ef4444; margin-top: 4px;">
                                    <i class="fas fa-exclamation-triangle"></i> Next 30 Days
                                </div>
                            </div>
                            <div style="width: 56px; height: 56px; background: #fef3c7; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-clock" style="font-size: 24px; color: #f59e0b;"></i>
                            </div>
                        </div>
                    </div>

                    <div style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Total Customers</div>
                                <div style="font-size: 32px; font-weight: 700; color: #06b6d4;"><?php echo number_format($customer_stats['total']); ?></div>
                                <div style="font-size: 12px; color: #10b981; margin-top: 4px;">
                                    <i class="fas fa-user-plus"></i> <?php echo number_format($customer_stats['new_this_week']); ?> This Week
                                </div>
                            </div>
                            <div style="width: 56px; height: 56px; background: #ecfeff; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-users" style="font-size: 24px; color: #06b6d4;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Expired Licenses</div>
                                <div style="font-size: 32px; font-weight: 700; color: #ef4444;"><?php echo number_format($license_stats['expired']); ?></div>
                                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                                    <i class="fas fa-ban"></i> Require Renewal
                                </div>
                            </div>
                            <div style="width: 56px; height: 56px; background: #fee2e2; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-calendar-times" style="font-size: 24px; color: #ef4444;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <!-- License Status Distribution -->
                    <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                        <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-chart-pie" style="color: #1d4dd4;"></i>
                                License Status Distribution
                            </h3>
                        </div>
                        <div style="padding: 24px;">
                            <canvas id="statusChart" style="max-height: 280px;"></canvas>
                        </div>
                    </div>

                    <!-- Activations Trend -->
                    <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                        <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-chart-line" style="color: #10b981;"></i>
                                Activations Trend (Last 30 Days)
                            </h3>
                        </div>
                        <div style="padding: 24px;">
                            <canvas id="activationsChart" style="max-height: 280px;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- License Creation Trend -->
                <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; margin-bottom: 24px;">
                    <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-chart-area" style="color: #8b5cf6;"></i>
                            License Creation Trend (Last 30 Days)
                        </h3>
                    </div>
                    <div style="padding: 24px;">
                        <canvas id="licenseChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>

                <!-- Two Column Layout for Tables -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <!-- Top Products -->
                    <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                        <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-box" style="color: #1d4dd4;"></i>
                                Top Products by License Count
                            </h3>
                        </div>
                        <div style="padding: 0;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                        <th style="padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase;">#</th>
                                        <th style="padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Product</th>
                                        <th style="padding: 12px 20px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Licenses</th>
                                        <th style="padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Share</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_products)): ?>
                                    <tr>
                                        <td colspan="4" style="padding: 40px 20px; text-align: center; color: #9ca3af;">
                                            <i class="fas fa-inbox" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                                            No product data available
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($top_products as $index => $product): ?>
                                    <?php $percentage = ($product['count'] / $license_stats['total']) * 100; ?>
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 14px 20px; font-weight: 600; color: #9ca3af;"><?php echo ($index + 1); ?></td>
                                        <td style="padding: 14px 20px; font-weight: 500; color: #374151;"><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td style="padding: 14px 20px; text-align: right; font-weight: 600; color: #1d4dd4;"><?php echo number_format($product['count']); ?></td>
                                        <td style="padding: 14px 20px; min-width: 120px;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <div style="flex: 1; background: #e5e7eb; border-radius: 4px; height: 8px; overflow: hidden;">
                                                    <div style="background: linear-gradient(135deg, #1d4dd4 0%, #1a3fb8 100%); height: 100%; width: <?php echo $percentage; ?>%;"></div>
                                                </div>
                                                <span style="font-size: 12px; color: #6b7280; font-weight: 500; min-width: 40px; text-align: right;"><?php echo number_format($percentage, 1); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top Domains -->
                    <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                        <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-globe" style="color: #8b5cf6;"></i>
                                Top Active Domains
                            </h3>
                        </div>
                        <div style="padding: 0;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                        <th style="padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase;">#</th>
                                        <th style="padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Domain</th>
                                        <th style="padding: 12px 20px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Activations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_domains)): ?>
                                    <tr>
                                        <td colspan="3" style="padding: 40px 20px; text-align: center; color: #9ca3af;">
                                            <i class="fas fa-inbox" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                                            No domain data available
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($top_domains as $index => $domain): ?>
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 14px 20px; font-weight: 600; color: #9ca3af;"><?php echo ($index + 1); ?></td>
                                        <td style="padding: 14px 20px;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class="fas fa-globe" style="color: #8b5cf6; font-size: 12px;"></i>
                                                <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 5px; font-size: 12px; color: #374151;"><?php echo htmlspecialchars($domain['domain']); ?></code>
                                            </div>
                                        </td>
                                        <td style="padding: 14px 20px; text-align: right;">
                                            <span style="background: #faf5ff; color: #8b5cf6; padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                                                <?php echo number_format($domain['activation_count']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Expiration Breakdown -->
                <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; margin-bottom: 24px;">
                    <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-calendar-check" style="color: #f59e0b;"></i>
                            License Expiration Breakdown
                        </h3>
                    </div>
                    <div style="padding: 24px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                            <?php foreach ($expiration_breakdown as $exp): ?>
                            <?php
                            $colors = [
                                'Expired' => ['bg' => '#fee2e2', 'text' => '#ef4444', 'icon' => 'fa-times-circle'],
                                'This Week' => ['bg' => '#fed7aa', 'text' => '#f59e0b', 'icon' => 'fa-exclamation-triangle'],
                                'This Month' => ['bg' => '#fef3c7', 'text' => '#f59e0b', 'icon' => 'fa-clock'],
                                'Next 3 Months' => ['bg' => '#dbeafe', 'text' => '#3b82f6', 'icon' => 'fa-calendar'],
                                'Future' => ['bg' => '#d1fae5', 'text' => '#10b981', 'icon' => 'fa-check-circle']
                            ];
                            $color = $colors[$exp['period']] ?? ['bg' => '#f3f4f6', 'text' => '#6b7280', 'icon' => 'fa-circle'];
                            ?>
                            <div style="background: <?php echo $color['bg']; ?>; padding: 20px; border-radius: 10px; text-align: center;">
                                <i class="fas <?php echo $color['icon']; ?>" style="font-size: 24px; color: <?php echo $color['text']; ?>; margin-bottom: 8px;"></i>
                                <div style="font-size: 28px; font-weight: 700; color: <?php echo $color['text']; ?>; margin-bottom: 4px;">
                                    <?php echo number_format($exp['count']); ?>
                                </div>
                                <div style="font-size: 12px; font-weight: 500; color: <?php echo $color['text']; ?>; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <?php echo htmlspecialchars($exp['period']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Summary -->
                <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                    <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-history" style="color: #06b6d4;"></i>
                            System Activity (Last 7 Days)
                        </h3>
                    </div>
                    <div style="padding: 24px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <?php foreach ($recent_activity as $activity): ?>
                            <?php
                            $level_colors = [
                                'DEBUG' => ['bg' => '#f3f4f6', 'text' => '#6b7280', 'icon' => 'fa-bug'],
                                'INFO' => ['bg' => '#dbeafe', 'text' => '#3b82f6', 'icon' => 'fa-info-circle'],
                                'WARNING' => ['bg' => '#fef3c7', 'text' => '#f59e0b', 'icon' => 'fa-exclamation-triangle'],
                                'ERROR' => ['bg' => '#fee2e2', 'text' => '#ef4444', 'icon' => 'fa-times-circle']
                            ];
                            $lc = $level_colors[$activity['level']] ?? ['bg' => '#f3f4f6', 'text' => '#6b7280', 'icon' => 'fa-circle'];
                            ?>
                            <div style="background: white; border: 2px solid <?php echo $lc['bg']; ?>; padding: 16px; border-radius: 10px;">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                    <div style="width: 40px; height: 40px; background: <?php echo $lc['bg']; ?>; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas <?php echo $lc['icon']; ?>" style="color: <?php echo $lc['text']; ?>; font-size: 18px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-size: 24px; font-weight: 700; color: <?php echo $lc['text']; ?>;">
                                            <?php echo number_format($activity['count']); ?>
                                        </div>
                                        <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase;">
                                            <?php echo htmlspecialchars($activity['level']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="font-size: 11px; color: #9ca3af; padding-top: 8px; border-top: 1px solid #f3f4f6;">
                                    <i class="fas fa-clock"></i> <?php echo date('M d, H:i', strtotime($activity['last_occurrence'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Status Distribution Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map('ucfirst', array_column($status_distribution, 'status'))); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($status_distribution, 'count')); ?>,
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#6b7280'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: { size: 13, family: 'Inter' }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // Activations Trend Chart
    const activationsCtx = document.getElementById('activationsChart').getContext('2d');
    new Chart(activationsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($activations_trend, 'date')); ?>,
            datasets: [{
                label: 'Daily Activations',
                data: <?php echo json_encode(array_column($activations_trend, 'count')); ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14, family: 'Inter' },
                    bodyFont: { size: 13, family: 'Inter' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: { size: 12, family: 'Inter' }
                    },
                    grid: { color: '#f3f4f6' }
                },
                x: {
                    ticks: {
                        font: { size: 11, family: 'Inter' },
                        maxRotation: 45,
                        minRotation: 45
                    },
                    grid: { display: false }
                }
            }
        }
    });

    // License Creation Trend Chart
    const licenseCtx = document.getElementById('licenseChart').getContext('2d');
    new Chart(licenseCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($license_trend, 'date')); ?>,
            datasets: [{
                label: 'Licenses Created',
                data: <?php echo json_encode(array_column($license_trend, 'count')); ?>,
                backgroundColor: 'rgba(139, 92, 246, 0.8)',
                borderColor: '#8b5cf6',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14, family: 'Inter' },
                    bodyFont: { size: 13, family: 'Inter' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: { size: 12, family: 'Inter' }
                    },
                    grid: { color: '#f3f4f6' }
                },
                x: {
                    ticks: {
                        font: { size: 11, family: 'Inter' },
                        maxRotation: 45,
                        minRotation: 45
                    },
                    grid: { display: false }
                }
            }
        }
    });

    // Export Report Function
    function exportReport() {
        const options = `
            <div style="padding: 20px;">
                <h3 style="margin: 0 0 16px 0; color: #374151;">Export Report</h3>
                <p style="color: #6b7280; margin-bottom: 20px; font-size: 14px;">Choose your preferred export format:</p>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <button onclick="exportCSV()" style="background: #10b981; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; justify-content: center;">
                        <i class="fas fa-file-csv"></i> Export as CSV
                    </button>
                    <button onclick="exportPDF()" style="background: #ef4444; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; justify-content: center;">
                        <i class="fas fa-file-pdf"></i> Export as PDF
                    </button>
                    <button onclick="exportExcel()" style="background: #3b82f6; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; justify-content: center;">
                        <i class="fas fa-file-excel"></i> Export as Excel
                    </button>
                </div>
            </div>
        `;
        
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;';
        modal.innerHTML = '<div style="background: white; border-radius: 12px; max-width: 400px; width: 90%;">' + options + '</div>';
        modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
        document.body.appendChild(modal);
    }

    function exportCSV() {
        // Close modal
        const modal = document.querySelector('div[style*="position: fixed"]');
        if (modal) modal.remove();
        
        // Get current filter values
        const urlParams = new URLSearchParams(window.location.search);
        const dateFrom = urlParams.get('date_from') || '<?php echo $date_from; ?>';
        const dateTo = urlParams.get('date_to') || '<?php echo $date_to; ?>';
        
        // Show loading
        showExportLoading('Generating CSV...');
        
        // Redirect to export handler
        setTimeout(() => {
            window.location.href = 'export_report.php?format=csv&date_from=' + dateFrom + '&date_to=' + dateTo;
            hideExportLoading();
        }, 500);
    }

    function exportPDF() {
        // Close modal
        const modal = document.querySelector('div[style*="position: fixed"]');
        if (modal) modal.remove();
        
        // Get current filter values
        const urlParams = new URLSearchParams(window.location.search);
        const dateFrom = urlParams.get('date_from') || '<?php echo $date_from; ?>';
        const dateTo = urlParams.get('date_to') || '<?php echo $date_to; ?>';
        
        // Show loading
        showExportLoading('Generating PDF...');
        
        // Open in new window for PDF print
        setTimeout(() => {
            window.open('export_report.php?format=pdf&date_from=' + dateFrom + '&date_to=' + dateTo, '_blank');
            hideExportLoading();
        }, 500);
    }

    function exportExcel() {
        // Close modal
        const modal = document.querySelector('div[style*="position: fixed"]');
        if (modal) modal.remove();
        
        // Get current filter values
        const urlParams = new URLSearchParams(window.location.search);
        const dateFrom = urlParams.get('date_from') || '<?php echo $date_from; ?>';
        const dateTo = urlParams.get('date_to') || '<?php echo $date_to; ?>';
        
        // Show loading
        showExportLoading('Generating Excel...');
        
        // Redirect to export handler
        setTimeout(() => {
            window.location.href = 'export_report.php?format=excel&date_from=' + dateFrom + '&date_to=' + dateTo;
            hideExportLoading();
        }, 500);
    }

    function showExportLoading(message) {
        const loader = document.createElement('div');
        loader.id = 'exportLoader';
        loader.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 10001;';
        loader.innerHTML = `
            <div style="background: white; padding: 30px 40px; border-radius: 12px; text-align: center;">
                <div style="width: 50px; height: 50px; border: 4px solid #e5e7eb; border-top-color: #1d4dd4; border-radius: 50%; margin: 0 auto 20px; animation: spin 1s linear infinite;"></div>
                <p style="margin: 0; font-size: 16px; font-weight: 600; color: #374151;">${message}</p>
                <p style="margin: 10px 0 0 0; font-size: 13px; color: #6b7280;">Please wait...</p>
            </div>
        `;
        document.body.appendChild(loader);
        
        // Add animation
        const style = document.createElement('style');
        style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    }

    function hideExportLoading() {
        const loader = document.getElementById('exportLoader');
        if (loader) loader.remove();
    }
    </script>
</body>
</html>
