<?php
/**
 * Zwicky Technology License Management System
 * Activity Logs
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
$logger = new LMSLogger();
$license_manager = new LMSLicenseManager();

// Get filters
$level_filter = $_GET['level'] ?? 'all';
$search_filter = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($level_filter != 'all') {
    $where_conditions[] = "level = ?";
    $params[] = $level_filter;
}

if (!empty($search_filter)) {
    $where_conditions[] = "(license_key LIKE ? OR message LIKE ? OR ip_address LIKE ?)";
    $search_term = "%{$search_filter}%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get logs
$db = getLMSDatabase();
$offset = ($page - 1) * $per_page;
$sql = "SELECT * FROM " . LMS_TABLE_LOGS . " $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
if (!empty($params)) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
} else {
    $stmt = $db->query($sql);
}
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count
$count_sql = "SELECT COUNT(*) FROM " . LMS_TABLE_LOGS . " " . $where_clause;
if (!empty($params)) {
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
} else {
    $stmt = $db->query($count_sql);
}
$total_logs = $stmt->fetchColumn();
$total_pages = ceil($total_logs / $per_page);

// Get statistics (30-day period)
$stats_sql = "SELECT 
    COUNT(*) as total_logs,
    SUM(CASE WHEN level = 'INFO' THEN 1 ELSE 0 END) as info_logs,
    SUM(CASE WHEN level = 'WARNING' THEN 1 ELSE 0 END) as warning_logs,
    SUM(CASE WHEN level = 'ERROR' THEN 1 ELSE 0 END) as error_logs,
    COUNT(DISTINCT ip_address) as unique_ips,
    COUNT(DISTINCT DATE(created_at)) as active_days
    FROM " . LMS_TABLE_LOGS . " 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$log_stats = $db->query($stats_sql)->fetch(PDO::FETCH_ASSOC);

$page_title = 'Activity Logs';
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
                                <i class="fas fa-history" style="margin-right: 10px;"></i>
                                Activity Logs
                            </h1>
                            <p class="page-description" style="color: rgba(255,255,255,0.85); font-size: 14px; margin: 0;">Monitor system activities, security events, and audit trails</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Total Logs (30d)</div>
                                <div style="font-size: 28px; font-weight: 600; color: #1d4dd4;"><?php echo number_format($log_stats['total_logs']); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-history" style="font-size: 20px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Info Logs</div>
                                <div style="font-size: 28px; font-weight: 600; color: #1d4dd4;"><?php echo number_format($log_stats['info_logs']); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-info-circle" style="font-size: 20px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Warnings</div>
                                <div style="font-size: 28px; font-weight: 600; color: #f59e0b;"><?php echo number_format($log_stats['warning_logs']); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #fef3c7; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 20px; color: #f59e0b;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Errors</div>
                                <div style="font-size: 28px; font-weight: 600; color: #ef4444;"><?php echo number_format($log_stats['error_logs']); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #fee2e2; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-times-circle" style="font-size: 20px; color: #ef4444;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Unique IPs</div>
                                <div style="font-size: 28px; font-weight: 600; color: #1d4dd4;"><?php echo number_format($log_stats['unique_ips']); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-network-wired" style="font-size: 20px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 24px;">
                    <div class="card-body" style="padding: 20px;">
                        <form method="GET" action="">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 12px;">
                                <div>
                                    <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                        <i class="fas fa-filter" style="margin-right: 6px;"></i> Log Level
                                    </label>
                                    <select name="level" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background: white; cursor: pointer;">
                                        <option value="all" <?php echo $level_filter == 'all' ? 'selected' : ''; ?>>All Levels</option>
                                        <option value="DEBUG" <?php echo $level_filter == 'DEBUG' ? 'selected' : ''; ?>>Debug</option>
                                        <option value="INFO" <?php echo $level_filter == 'INFO' ? 'selected' : ''; ?>>Info</option>
                                        <option value="WARNING" <?php echo $level_filter == 'WARNING' ? 'selected' : ''; ?>>Warning</option>
                                        <option value="ERROR" <?php echo $level_filter == 'ERROR' ? 'selected' : ''; ?>>Error</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                        <i class="fas fa-calendar" style="margin-right: 6px;"></i> From Date
                                    </label>
                                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                                        style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                                </div>
                                
                                <div>
                                    <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                        <i class="fas fa-calendar" style="margin-right: 6px;"></i> To Date
                                    </label>
                                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                                        style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 12px; align-items: end;">
                                <div style="flex: 1; position: relative;">
                                    <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 13px;"></i>
                                    <input type="text" name="search" placeholder="Search by license key, message, or IP..." 
                                        value="<?php echo htmlspecialchars($search_filter); ?>" 
                                        style="width: 100%; padding: 10px 14px 10px 40px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                                </div>
                                <button type="submit" style="background: #1d4dd4; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px;"
                                    onmouseover="this.style.background='#1a3fb8'" 
                                    onmouseout="this.style.background='#1d4dd4'">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if (!empty($search_filter) || $level_filter != 'all' || !empty($date_from) || !empty($date_to)): ?>
                                <a href="logs.php" style="padding: 10px 20px; background: #6b7280; color: white; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px;"
                                    onmouseover="this.style.background='#4b5563'" 
                                    onmouseout="this.style.background='#6b7280'">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden;">
                    <div class="card-body" style="padding: 0;">
                        <div style="overflow-x: auto;">
                            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-clock" style="margin-right: 6px;"></i> Timestamp
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-layer-group" style="margin-right: 6px;"></i> Level
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-key" style="margin-right: 6px;"></i> License Key
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-comment-alt" style="margin-right: 6px;"></i> Message
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-network-wired" style="margin-right: 6px;"></i> IP Address
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" style="padding: 60px 20px; text-align: center;">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                            <div style="width: 64px; height: 64px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-history" style="font-size: 28px; color: #9ca3af;"></i>
                                            </div>
                                            <div style="color: #6b7280; font-size: 16px; font-weight: 500;">No logs found</div>
                                            <div style="color: #9ca3af; font-size: 14px;">Try adjusting your filters or date range</div>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr style="border-bottom: 1px solid #f3f4f6; transition: background-color 0.15s ease;" 
                                    onmouseover="this.style.backgroundColor='#fafbfc'" 
                                    onmouseout="this.style.backgroundColor='white'">
                                    <td style="padding: 14px 20px; white-space: nowrap;">
                                        <div style="display: flex; flex-direction: column; gap: 2px;">
                                            <span style="font-weight: 500; color: #374151; font-size: 13px;">
                                                <?php echo date('M d, Y', strtotime($log['created_at'])); ?>
                                            </span>
                                            <span style="color: #9ca3af; font-size: 12px;">
                                                <?php echo date('H:i:s', strtotime($log['created_at'])); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <?php
                                        $level_config = [
                                            'DEBUG' => ['icon' => 'fa-bug', 'color' => '#6b7280'],
                                            'INFO' => ['icon' => 'fa-info-circle', 'color' => '#1d4dd4'],
                                            'WARNING' => ['icon' => 'fa-exclamation-triangle', 'color' => '#f59e0b'],
                                            'ERROR' => ['icon' => 'fa-times-circle', 'color' => '#ef4444'],
                                        ];
                                        
                                        $config = $level_config[$log['level']] ?? ['icon' => 'fa-circle', 'color' => '#9ca3af'];
                                        ?>
                                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; background: <?php echo $config['color']; ?>; color: white;">
                                            <i class="fas <?php echo $config['icon']; ?>"></i>
                                            <?php echo htmlspecialchars($log['level']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <?php if (!empty($log['license_key'])): ?>
                                            <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 5px; font-size: 12px; color: #374151; font-family: 'Courier New', monospace;">
                                                <?php echo htmlspecialchars($log['license_key']); ?>
                                            </code>
                                        <?php else: ?>
                                            <span style="color: #9ca3af; font-size: 13px;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <span style="color: #6b7280; font-size: 13px; max-width: 400px; display: block; overflow: hidden; text-overflow: ellipsis;" 
                                            title="<?php echo htmlspecialchars($log['message']); ?>">
                                            <?php echo htmlspecialchars($log['message']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <span style="color: #6b7280; font-size: 13px; font-family: 'Courier New', monospace;">
                                            <?php echo htmlspecialchars($log['ip_address']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>


                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding: 0 4px;">
                        <div style="color: #6b7280; font-size: 14px;">
                            Showing <strong style="color: #374151;"><?php echo (($page - 1) * $per_page) + 1; ?></strong> 
                            to <strong style="color: #374151;"><?php echo min($page * $per_page, $total_logs); ?></strong> 
                            of <strong style="color: #374151;"><?php echo number_format($total_logs); ?></strong> logs
                        </div>
                        
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?><?php echo $level_filter ? '&level=' . urlencode($level_filter) : ''; ?><?php echo $search_filter ? '&search=' . urlencode($search_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?>" 
                               style="padding: 8px 14px; border: 1px solid #e5e7eb; border-radius: 6px; background: white; color: #374151; text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.15s ease; display: inline-flex; align-items: center; gap: 6px;"
                               onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db';"
                               onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb';">
                                <i class="fas fa-chevron-left" style="font-size: 11px;"></i>
                                Previous
                            </a>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            
                            if ($start > 1): ?>
                                <a href="?page=1<?php echo $level_filter ? '&level=' . urlencode($level_filter) : ''; ?><?php echo $search_filter ? '&search=' . urlencode($search_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?>" 
                                   style="padding: 8px 14px; border: 1px solid #e5e7eb; border-radius: 6px; background: white; color: #374151; text-decoration: none; font-size: 13px; font-weight: 500; min-width: 42px; text-align: center; transition: all 0.15s ease;"
                                   onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db';"
                                   onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb';">
                                    1
                                </a>
                                <?php if ($start > 2): ?>
                                    <span style="color: #9ca3af; padding: 0 4px;">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span style="padding: 8px 14px; border: 1px solid #1d4dd4; border-radius: 6px; background: #1d4dd4; color: white; font-size: 13px; font-weight: 600; min-width: 42px; text-align: center;">
                                        <?php echo $i; ?>
                                    </span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $level_filter ? '&level=' . urlencode($level_filter) : ''; ?><?php echo $search_filter ? '&search=' . urlencode($search_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?>" 
                                       style="padding: 8px 14px; border: 1px solid #e5e7eb; border-radius: 6px; background: white; color: #374151; text-decoration: none; font-size: 13px; font-weight: 500; min-width: 42px; text-align: center; transition: all 0.15s ease;"
                                       onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db';"
                                       onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb';">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($end < $total_pages): ?>
                                <?php if ($end < $total_pages - 1): ?>
                                    <span style="color: #9ca3af; padding: 0 4px;">...</span>
                                <?php endif; ?>
                                <a href="?page=<?php echo $total_pages; ?><?php echo $level_filter ? '&level=' . urlencode($level_filter) : ''; ?><?php echo $search_filter ? '&search=' . urlencode($search_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?>" 
                                   style="padding: 8px 14px; border: 1px solid #e5e7eb; border-radius: 6px; background: white; color: #374151; text-decoration: none; font-size: 13px; font-weight: 500; min-width: 42px; text-align: center; transition: all 0.15s ease;"
                                   onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db';"
                                   onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb';">
                                    <?php echo $total_pages; ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page + 1); ?><?php echo $level_filter ? '&level=' . urlencode($level_filter) : ''; ?><?php echo $search_filter ? '&search=' . urlencode($search_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?>" 
                               style="padding: 8px 14px; border: 1px solid #e5e7eb; border-radius: 6px; background: white; color: #374151; text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.15s ease; display: inline-flex; align-items: center; gap: 6px;"
                               onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db';"
                               onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb';">
                                Next
                                <i class="fas fa-chevron-right" style="font-size: 11px;"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>