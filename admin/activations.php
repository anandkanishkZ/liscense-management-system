<?php
/**
 * Zwicky Technology License Management System
 * Activations Management
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

// Get all activations with filters
$db = getLMSDatabase();
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($status_filter === 'active') {
    $where_conditions[] = "a.status = 'active'";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "a.status = 'inactive'";
}

if (!empty($search_filter)) {
    $where_conditions[] = "(a.domain LIKE ? OR l.license_key LIKE ? OR l.product_name LIKE ? OR l.customer_name LIKE ? OR a.ip_address LIKE ?)";
    $search_term = "%{$search_filter}%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$stmt = $db->prepare("
    SELECT a.*, l.license_key, l.product_name, l.customer_name, l.status as license_status
    FROM " . LMS_TABLE_ACTIVATIONS . " a
    JOIN " . LMS_TABLE_LICENSES . " l ON a.license_id = l.id
    {$where_clause}
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $per_page;
$params[] = $offset;
$stmt->execute($params);
$activations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count with filters
$count_params = array_slice($params, 0, -2); // Remove limit and offset
$stmt = $db->prepare("SELECT COUNT(*) FROM " . LMS_TABLE_ACTIVATIONS . " a JOIN " . LMS_TABLE_LICENSES . " l ON a.license_id = l.id {$where_clause}");
$stmt->execute($count_params);
$total_activations = $stmt->fetchColumn();
$total_pages = ceil($total_activations / $per_page);

// Get statistics
$stats = $license_manager->getStatistics();

// Get active activations count
$active_stmt = $db->query("SELECT COUNT(*) FROM " . LMS_TABLE_ACTIVATIONS . " WHERE status = 'active'");
$active_activations = $active_stmt->fetchColumn();

// Get unique domains count
$domains_stmt = $db->query("SELECT COUNT(DISTINCT domain) FROM " . LMS_TABLE_ACTIVATIONS . " WHERE status = 'active'");
$unique_domains = $domains_stmt->fetchColumn();

$page_title = 'Activations';
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
                                <i class="fas fa-plug" style="margin-right: 10px;"></i>
                                License Activations
                            </h1>
                            <p class="page-description" style="color: rgba(255,255,255,0.85); font-size: 14px; margin: 0;">Track and manage all license activations and device registrations</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Total Activations</div>
                                <div style="font-size: 28px; font-weight: 600; color: #1d4dd4;"><?php echo number_format($total_activations); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-plug" style="font-size: 20px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Active Activations</div>
                                <div style="font-size: 28px; font-weight: 600; color: #1d4dd4;"><?php echo number_format($active_activations); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-check-circle" style="font-size: 20px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Unique Domains</div>
                                <div style="font-size: 28px; font-weight: 600; color: #1d4dd4;"><?php echo number_format($unique_domains); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-globe" style="font-size: 20px; color: #1d4dd4;"></i>
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
                                <i class="fas fa-certificate" style="font-size: 20px; color: #1d4dd4;"></i>
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
                                    <i class="fas fa-filter" style="margin-right: 6px;"></i> Filter by Status
                                </label>
                                <select onchange="window.location.href='?status='+this.value+'&search=<?php echo urlencode($search_filter); ?>'" 
                                    style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background: white; cursor: pointer; transition: all 0.2s;">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Activations</option>
                                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active Only</option>
                                    <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                                </select>
                            </div>
                            
                            <div style="flex: 2; min-width: 300px;">
                                <form method="GET" action="" style="display: flex; gap: 8px;">
                                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                                    <div style="flex: 1; position: relative;">
                                        <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 13px;"></i>
                                        <input type="text" name="search" placeholder="Search by domain, license key, product, IP..." 
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

                <!-- Activations Table -->
                <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden; margin-top: 24px;">
                    <div class="card-body" style="padding: 0;">
                        <div style="overflow-x: auto;">
                            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-globe" style="margin-right: 6px;"></i> Domain
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-key" style="margin-right: 6px;"></i> License Key
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-box" style="margin-right: 6px;"></i> Product
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-user" style="margin-right: 6px;"></i> Customer
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-info-circle" style="margin-right: 6px;"></i> Status
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-network-wired" style="margin-right: 6px;"></i> IP Address
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-calendar" style="margin-right: 6px;"></i> Activated
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-clock" style="margin-right: 6px;"></i> Last Check
                                        </th>
                                        <th style="padding: 14px 20px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-cog" style="margin-right: 6px;"></i> Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($activations)): ?>
                                <tr>
                                    <td colspan="9" style="padding: 60px 20px; text-align: center;">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                            <div style="width: 64px; height: 64px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-plug" style="font-size: 28px; color: #9ca3af;"></i>
                                            </div>
                                            <div style="color: #6b7280; font-size: 16px; font-weight: 500;">No activations found</div>
                                            <div style="color: #9ca3af; font-size: 14px;">Try adjusting your filters or search terms</div>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($activations as $activation): ?>
                                <tr style="border-bottom: 1px solid #f3f4f6; transition: background-color 0.15s ease;" 
                                    onmouseover="this.style.backgroundColor='#fafbfc'" 
                                    onmouseout="this.style.backgroundColor='white'">
                                    <td style="padding: 14px 20px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div style="width: 32px; height: 32px; background: #eff6ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                <i class="fas fa-globe" style="color: #1d4dd4; font-size: 13px;"></i>
                                            </div>
                                            <span style="font-weight: 500; color: #111827; font-size: 14px;"><?php echo htmlspecialchars($activation['domain']); ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 5px; font-size: 12px; color: #374151; font-family: 'Courier New', monospace;">
                                            <?php echo htmlspecialchars($activation['license_key']); ?>
                                        </code>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <span style="color: #374151; font-size: 14px;"><?php echo htmlspecialchars($activation['product_name']); ?></span>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <span style="color: #374151; font-size: 14px;"><?php echo htmlspecialchars($activation['customer_name']); ?></span>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <?php if ($activation['status'] === 'active'): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; background: #1d4dd4; color: white;">
                                                <i class="fas fa-check-circle"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; background: #6b7280; color: white;">
                                                <i class="fas fa-ban"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <span style="color: #6b7280; font-size: 13px; font-family: 'Courier New', monospace;">
                                            <?php echo htmlspecialchars($activation['ip_address']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <span style="color: #6b7280; font-size: 13px;">
                                            <?php echo date('M d, Y', strtotime($activation['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <span style="color: #6b7280; font-size: 13px;">
                                            <?php echo date('M d, Y H:i', strtotime($activation['last_check'])); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px; text-align: center;">
                                        <?php if ($activation['status'] === 'active'): ?>
                                            <button onclick="deactivateActivation(<?php echo $activation['id']; ?>, '<?php echo htmlspecialchars($activation['domain']); ?>', '<?php echo htmlspecialchars($activation['license_key']); ?>')" 
                                                style="padding: 7px 14px; background: #ef4444; color: white; border: none; border-radius: 7px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;"
                                                onmouseover="this.style.background='#dc2626'" 
                                                onmouseout="this.style.background='#ef4444'">
                                                <i class="fas fa-ban"></i> Deactivate
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #9ca3af; font-size: 13px; font-style: italic;">Inactive</span>
                                        <?php endif; ?>
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
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-top: 1px solid #e5e7eb;">
                        <div style="color: #6b7280; font-size: 14px;">
                            Showing <span style="font-weight: 600; color: #374151;"><?php echo (($page - 1) * $per_page) + 1; ?></span> to 
                            <span style="font-weight: 600; color: #374151;"><?php echo min($page * $per_page, $total_activations); ?></span> 
                            of <span style="font-weight: 600; color: #374151;"><?php echo $total_activations; ?></span> activations
                        </div>
                        <div style="display: flex; gap: 4px;">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_filter); ?>" 
                                    style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 7px; color: #374151; text-decoration: none; font-size: 13px; transition: all 0.2s; display: inline-flex; align-items: center;"
                                    onmouseover="this.style.borderColor='#1d4dd4'; this.style.color='#1d4dd4';" 
                                    onmouseout="this.style.borderColor='#d1d5db'; this.style.color='#374151';">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_filter); ?>" 
                                    style="padding: 8px 14px; border: 1px solid <?php echo $i == $page ? '#1d4dd4' : '#d1d5db'; ?>; border-radius: 7px; color: <?php echo $i == $page ? 'white' : '#374151'; ?>; background: <?php echo $i == $page ? '#1d4dd4' : 'white'; ?>; text-decoration: none; font-size: 13px; font-weight: <?php echo $i == $page ? '600' : '400'; ?>; transition: all 0.2s; display: inline-flex; align-items: center;"
                                    <?php if ($i != $page): ?>
                                    onmouseover="this.style.borderColor='#1d4dd4'; this.style.color='#1d4dd4';" 
                                    onmouseout="this.style.borderColor='#d1d5db'; this.style.color='#374151';"
                                    <?php endif; ?>>
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_filter); ?>" 
                                    style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 7px; color: #374151; text-decoration: none; font-size: 13px; transition: all 0.2s; display: inline-flex; align-items: center;"
                                    onmouseover="this.style.borderColor='#1d4dd4'; this.style.color='#1d4dd4';" 
                                    onmouseout="this.style.borderColor='#d1d5db'; this.style.color='#374151';">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function deactivateActivation(activationId, domain, licenseKey) {
        if (confirm('⚠️ Deactivate Domain?\n\nDomain: ' + domain + '\nLicense: ' + licenseKey + '\n\nThis will remove the license activation from this domain.')) {
            // Show loading state
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            button.disabled = true;
            button.style.opacity = '0.6';
            button.style.cursor = 'not-allowed';
            
            // Call API to deactivate
            fetch('api/activation-actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    action: 'deactivate',
                    activation_id: activationId,
                    license_key: licenseKey,
                    domain: domain
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const row = button.closest('tr');
                    row.style.background = '#dcfce7';
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    alert('❌ Error: ' + (data.message || 'Failed to deactivate domain'));
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                    button.style.opacity = '1';
                    button.style.cursor = 'pointer';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Network error: Failed to deactivate domain');
                button.innerHTML = originalHTML;
                button.disabled = false;
                button.style.opacity = '1';
                button.style.cursor = 'pointer';
            });
        }
    }
    </script>
</body>
</html>
