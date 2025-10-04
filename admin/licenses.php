<?php
/**
 * Zwicky Technology License Management System
 * License List View
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

// Get filters
$status_filter = $_GET['status'] ?? 'all';
$search_filter = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;

// Build filters array
$filters = [];
if ($status_filter !== 'all') {
    $filters['status'] = $status_filter;
}
if (!empty($search_filter)) {
    $filters['customer_email'] = $search_filter;
}

// Get licenses
$result = $license_manager->getAllLicenses($page, $per_page, $filters);
$licenses = $result['licenses'];
$total_licenses = $result['total'];
$total_pages = $result['pages'];

// Get statistics
$stats = $license_manager->getStatistics();

$page_title = 'License List';
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
                <div class="page-header" style="background: #1d4dd4; border-radius: 12px; padding: 28px 32px; margin-bottom: 32px; box-shadow: 0 2px 8px rgba(29, 77, 212, 0.15);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                        <div class="page-header-content">
                            <h1 class="page-title" style="color: white; font-size: 28px; font-weight: 600; margin-bottom: 6px;">
                                <i class="fas fa-certificate" style="margin-right: 10px;"></i>
                                License Management
                            </h1>
                            <p class="page-description" style="color: rgba(255,255,255,0.85); font-size: 14px; margin: 0;">Manage and monitor all software licenses</p>
                        </div>
                        <a href="license-manager.php" class="btn" style="background: white; color: #1d4dd4; padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); transition: all 0.3s; font-size: 14px;">
                            <i class="fas fa-plus-circle"></i> Create New License
                        </a>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Total Licenses</div>
                                <div style="font-size: 28px; font-weight: 600; color: #1d4dd4;"><?php echo number_format($stats['total_licenses']); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-certificate" style="font-size: 20px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Active Licenses</div>
                                <div style="font-size: 28px; font-weight: 600; color: #1d4dd4;"><?php echo number_format($stats['active_licenses']); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-check-circle" style="font-size: 20px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Expired Licenses</div>
                                <div style="font-size: 28px; font-weight: 600; color: #6b7280;"><?php echo number_format($stats['expired_licenses']); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #f9fafb; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-clock" style="font-size: 20px; color: #6b7280;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Total Activations</div>
                                <div style="font-size: 28px; font-weight: 600; color: #1d4dd4;"><?php echo number_format($stats['total_activations']); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-plug" style="font-size: 20px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden;">
                    <div class="card-body" style="padding: 20px;">
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: end;">
                            <div style="flex: 1; min-width: 200px;">
                                <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    <i class="fas fa-filter" style="margin-right: 6px;"></i> Status Filter
                                </label>
                                <select onchange="window.location.href='?status='+this.value+'&search=<?php echo urlencode($search_filter); ?>'" 
                                    style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background: white; cursor: pointer; transition: all 0.2s;">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Licenses</option>
                                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active Only</option>
                                    <option value="expired" <?php echo $status_filter == 'expired' ? 'selected' : ''; ?>>Expired Only</option>
                                    <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Suspended Only</option>
                                </select>
                            </div>
                            
                            <div style="flex: 2; min-width: 300px;">
                                <form method="GET" action="" style="display: flex; gap: 8px;">
                                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                                    <div style="flex: 1; position: relative;">
                                        <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 13px;"></i>
                                        <input type="text" name="search" placeholder="Search by email, product, or license key..." 
                                            value="<?php echo htmlspecialchars($search_filter); ?>" 
                                            style="width: 100%; padding: 10px 14px 10px 40px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; transition: all 0.2s;">
                                    </div>
                                    <button type="submit" class="btn" style="background: #1d4dd4; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; font-size: 14px;">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Licenses Table -->
                <div class="card" style="margin-top: 24px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden;">
                    <div class="card-header" style="background: white; padding: 20px; border-bottom: 1px solid #e5e7eb;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0; display: flex; align-items: center; gap: 10px;">
                                <div style="width: 40px; height: 40px; background: #1d4dd4; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-list" style="font-size: 16px;"></i>
                                </div>
                                <div>
                                    <div>All Licenses</div>
                                    <div style="font-size: 13px; font-weight: 400; color: #6b7280; margin-top: 2px;">
                                        <?php echo number_format($total_licenses); ?> licenses found
                                    </div>
                                </div>
                            </h3>
                        </div>
                    </div>
                    
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="license-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f9fafb;">
                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid #e5e7eb;">
                                        <i class="fas fa-key" style="margin-right: 6px;"></i> License Key
                                    </th>
                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid #e5e7eb;">
                                        <i class="fas fa-box" style="margin-right: 6px;"></i> Product
                                    </th>
                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid #e5e7eb;">
                                        <i class="fas fa-user" style="margin-right: 6px;"></i> Customer
                                    </th>
                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid #e5e7eb;">
                                        <i class="fas fa-toggle-on" style="margin-right: 6px;"></i> Status
                                    </th>
                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid #e5e7eb;">
                                        <i class="fas fa-calendar-alt" style="margin-right: 6px;"></i> Expires
                                    </th>
                                    <th style="padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid #e5e7eb;">
                                        <i class="fas fa-chart-line" style="margin-right: 6px;"></i> Activations
                                    </th>
                                    <th style="padding: 14px 16px; text-align: center; font-size: 12px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid #e5e7eb;">
                                        <i class="fas fa-cog" style="margin-right: 6px;"></i> Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($licenses)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 60px 40px; background: #fafbfc;">
                                        <div style="max-width: 400px; margin: 0 auto;">
                                            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                                <i class="fas fa-inbox" style="font-size: 36px; color: #94a3b8;"></i>
                                            </div>
                                            <h4 style="font-size: 18px; font-weight: 600; color: #334155; margin: 0 0 8px;">No Licenses Found</h4>
                                            <p style="font-size: 14px; color: #64748b; margin: 0;">Try adjusting your filters or create a new license to get started.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($licenses as $license): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9; transition: all 0.3s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                                    <td style="padding: 20px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <code style="background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); color: #667eea; padding: 8px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; font-family: 'Courier New', monospace;">
                                                <?php echo htmlspecialchars($license['license_key']); ?>
                                            </code>
                                            <button onclick="copyToClipboard('<?php echo htmlspecialchars($license['license_key']); ?>')" 
                                                style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; transition: all 0.3s;" 
                                                title="Copy to clipboard">
                                                <i class="fas fa-copy" style="color: #64748b; font-size: 12px;"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td style="padding: 20px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px;">
                                                <?php echo strtoupper(substr($license['product_name'], 0, 2)); ?>
                                            </div>
                                            <span style="font-weight: 600; color: #1e293b; font-size: 14px;"><?php echo htmlspecialchars($license['product_name']); ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 20px;">
                                        <div>
                                            <div style="font-weight: 600; color: #1e293b; font-size: 14px; margin-bottom: 4px;">
                                                <?php echo htmlspecialchars($license['customer_name']); ?>
                                            </div>
                                            <div style="font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                                                <i class="fas fa-envelope" style="font-size: 11px;"></i>
                                                <?php echo htmlspecialchars($license['customer_email']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 20px;">
                                        <?php
                                        $status_config = [
                                            'active' => ['icon' => 'fa-check-circle', 'gradient' => 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)', 'shadow' => 'rgba(17, 153, 142, 0.3)'],
                                            'expired' => ['icon' => 'fa-clock', 'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)', 'shadow' => 'rgba(240, 147, 251, 0.3)'],
                                            'suspended' => ['icon' => 'fa-ban', 'gradient' => 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)', 'shadow' => 'rgba(255, 107, 107, 0.3)'],
                                        ];
                                        $config = $status_config[$license['status']] ?? ['icon' => 'fa-circle', 'gradient' => 'linear-gradient(135deg, #94a3b8 0%, #64748b 100%)', 'shadow' => 'rgba(148, 163, 184, 0.3)'];
                                        ?>
                                        <span style="background: <?php echo $config['gradient']; ?>; color: white; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px <?php echo $config['shadow']; ?>;">
                                            <i class="fas <?php echo $config['icon']; ?>"></i>
                                            <?php echo ucfirst($license['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 20px;">
                                        <?php if ($license['expires_at']): ?>
                                            <?php
                                            $expires = strtotime($license['expires_at']);
                                            $now = time();
                                            $days_left = floor(($expires - $now) / 86400);
                                            ?>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class="fas fa-calendar" style="color: #94a3b8; font-size: 14px;"></i>
                                                <div>
                                                    <div style="font-weight: 600; color: #1e293b; font-size: 14px;">
                                                        <?php echo date('M d, Y', $expires); ?>
                                                    </div>
                                                    <?php if ($days_left > 0): ?>
                                                        <div style="font-size: 12px; color: <?php echo $days_left <= 7 ? '#f5576c' : '#64748b'; ?>;">
                                                            <?php echo $days_left; ?> days left
                                                        </div>
                                                    <?php else: ?>
                                                        <div style="font-size: 12px; color: #f5576c;">Expired</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #64748b; font-weight: 500; display: flex; align-items: center; gap: 6px;">
                                                <i class="fas fa-infinity"></i> Never Expires
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 20px;">
                                        <?php
                                        $current = $license['current_activations'];
                                        $max = $license['max_activations'];
                                        $percentage = $max > 0 ? ($current / $max) * 100 : 0;
                                        $bar_color = $percentage >= 90 ? '#f5576c' : ($percentage >= 70 ? '#fee140' : '#38ef7d');
                                        ?>
                                        <div style="min-width: 120px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                                <span style="font-size: 13px; font-weight: 600; color: #1e293b;">
                                                    <?php echo $current; ?> / <?php echo $max; ?>
                                                </span>
                                                <span style="font-size: 12px; color: #64748b;">
                                                    <?php echo round($percentage); ?>%
                                                </span>
                                            </div>
                                            <div style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden;">
                                                <div style="width: <?php echo $percentage; ?>%; height: 100%; background: <?php echo $bar_color; ?>; border-radius: 10px; transition: all 0.3s;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 20px; text-align: center;">
                                        <div style="display: flex; gap: 8px; justify-content: center;">
                                            <button onclick="viewLicense('<?php echo $license['license_key']; ?>')" 
                                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; width: 36px; height: 36px; border-radius: 10px; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);" 
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="editLicense('<?php echo $license['license_key']; ?>')" 
                                                style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; border: none; width: 36px; height: 36px; border-radius: 10px; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(17, 153, 142, 0.3);" 
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($license['status'] == 'active'): ?>
                                            <button onclick="suspendLicense('<?php echo htmlspecialchars($license['license_key']); ?>', '<?php echo htmlspecialchars($license['product_name']); ?>')" 
                                                style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none; width: 36px; height: 36px; border-radius: 10px; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);" 
                                                title="Suspend License">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                            <?php elseif ($license['status'] == 'suspended'): ?>
                                            <button onclick="unsuspendLicense('<?php echo htmlspecialchars($license['license_key']); ?>', '<?php echo htmlspecialchars($license['product_name']); ?>')" 
                                                style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; border: none; width: 36px; height: 36px; border-radius: 10px; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(250, 112, 154, 0.3);" 
                                                title="Reactivate License">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div style="padding: 16px 20px; background: #fafbfc; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-top: 1px solid #e5e7eb;">
                        <div style="font-size: 13px; color: #6b7280; font-weight: 500;">
                            <i class="fas fa-info-circle" style="margin-right: 6px;"></i>
                            Showing <strong style="color: #111827;"><?php echo (($page - 1) * $per_page) + 1; ?></strong> to 
                            <strong style="color: #111827;"><?php echo min($page * $per_page, $total_licenses); ?></strong> 
                            of <strong style="color: #111827;"><?php echo number_format($total_licenses); ?></strong> licenses
                        </div>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_filter); ?>" 
                                   style="background: white; border: 1px solid #d1d5db; color: #6b7280; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; transition: all 0.2s; display: flex; align-items: center; gap: 6px; font-size: 13px;">
                                    <i class="fas fa-chevron-left" style="font-size: 11px;"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <div style="display: flex; gap: 4px;">
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_filter); ?>" 
                                       style="<?php echo $i == $page ? 
                                           'background: #1d4dd4; color: white; border: 1px solid #1d4dd4;' : 
                                           'background: white; border: 1px solid #d1d5db; color: #6b7280;'; ?> 
                                           padding: 8px 12px; border-radius: 6px; text-decoration: none; font-weight: 500; min-width: 38px; text-align: center; transition: all 0.2s; font-size: 13px;">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_filter); ?>" 
                                   style="background: white; border: 1px solid #d1d5db; color: #6b7280; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; transition: all 0.2s; display: flex; align-items: center; gap: 6px; font-size: 13px;">
                                    Next <i class="fas fa-chevron-right" style="font-size: 11px;"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/license-manager.js"></script>
    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Show success feedback
            const btn = event.target.closest('button');
            const icon = btn.querySelector('i');
            icon.className = 'fas fa-check';
            btn.style.background = '#1d4dd4';
            btn.style.color = 'white';
            
            setTimeout(() => {
                icon.className = 'fas fa-copy';
                btn.style.background = '#f3f4f6';
                btn.style.color = '#6b7280';
            }, 2000);
        });
    }
    
    function viewLicense(licenseKey) {
        window.location.href = 'license-manager.php?view=' + licenseKey;
    }

    function editLicense(licenseKey) {
        window.location.href = 'license-manager.php?edit=' + licenseKey;
    }

    function suspendLicense(licenseKey, productName) {
        if (confirm(`🚫 Suspend License?\n\nProduct: ${productName}\nLicense: ${licenseKey}\n\nThis will immediately prevent the license from being used until it is reactivated.\n\nAre you sure you want to continue?`)) {
            // Show loading state
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Send AJAX request
            fetch('api/license-actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'suspend',
                    license_key: licenseKey,
                    reason: 'Suspended by administrator'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('✅ License Suspended Successfully!\n\nThe license has been suspended and can no longer be used.\n\nYou can reactivate it anytime from the license list.');
                    location.reload();
                } else {
                    throw new Error(data.message || 'Failed to suspend license');
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
        }
    }
    
    function unsuspendLicense(licenseKey, productName) {
        if (confirm(`✅ Reactivate License?\n\nProduct: ${productName}\nLicense: ${licenseKey}\n\nThis will reactivate the license and allow it to be used again.\n\nAre you sure you want to continue?`)) {
            // Show loading state
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Send AJAX request
            fetch('api/license-actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'unsuspend',
                    license_key: licenseKey
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('✅ License Reactivated Successfully!\n\nThe license is now active and can be used again.');
                    location.reload();
                } else {
                    throw new Error(data.message || 'Failed to reactivate license');
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
        }
    }
    
    // Add hover effects to buttons
    document.querySelectorAll('button[onclick^="viewLicense"], button[onclick^="editLicense"], button[onclick^="suspendLicense"], button[onclick^="unsuspendLicense"]').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-1px)';
            this.style.opacity = '0.9';
        });
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.opacity = '1';
        });
    });
    
    // Add focus effect to search input
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            this.style.borderColor = '#1d4dd4';
            this.style.outline = 'none';
        });
        searchInput.addEventListener('blur', function() {
            this.style.borderColor = '#d1d5db';
        });
    }
    
    // Add hover effect to select
    const statusSelect = document.querySelector('select');
    if (statusSelect) {
        statusSelect.addEventListener('focus', function() {
            this.style.borderColor = '#1d4dd4';
            this.style.outline = 'none';
        });
        statusSelect.addEventListener('blur', function() {
            this.style.borderColor = '#d1d5db';
        });
    }
    
    // Add hover effects to pagination links
    document.querySelectorAll('a[href*="page="]').forEach(link => {
        if (!link.style.background.includes('#1d4dd4')) {
            link.addEventListener('mouseenter', function() {
                this.style.borderColor = '#1d4dd4';
                this.style.color = '#1d4dd4';
            });
            link.addEventListener('mouseleave', function() {
                this.style.borderColor = '#d1d5db';
                this.style.color = '#6b7280';
            });
        }
    });
    </script>
        });
    }
    </script>
</body>
</html>
