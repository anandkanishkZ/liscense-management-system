<?php
/**
 * Zwicky Technology License Management System
 * Customers Management
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

// Get search filter
$search_filter = $_GET['search'] ?? '';

// Get customers (grouped by email)
$db = getLMSDatabase();

// Build query with search
$where_clause = '';
$params = [];
if (!empty($search_filter)) {
    $where_clause = "WHERE customer_email LIKE ? OR customer_name LIKE ?";
    $search_term = "%{$search_filter}%";
    $params = [$search_term, $search_term];
}

$sql = "
    SELECT 
        customer_email,
        customer_name,
        COUNT(*) as total_licenses,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_licenses,
        SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_licenses,
        SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_licenses,
        MIN(created_at) as first_purchase,
        MAX(updated_at) as last_activity
    FROM " . LMS_TABLE_LICENSES . "
    $where_clause
    GROUP BY customer_email, customer_name
    ORDER BY first_purchase DESC
";

if (!empty($params)) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
} else {
    $stmt = $db->query($sql);
}
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate additional statistics
$total_customers = count($customers);
$customers_with_active = count(array_filter($customers, fn($c) => $c['active_licenses'] > 0));
$avg_licenses = $total_customers > 0 ? array_sum(array_column($customers, 'total_licenses')) / $total_customers : 0;

// Get statistics
$stats = $license_manager->getStatistics();

$page_title = 'Customers';
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
                                <i class="fas fa-users" style="margin-right: 10px;"></i>
                                Customer Management
                            </h1>
                            <p class="page-description" style="color: rgba(255,255,255,0.85); font-size: 14px; margin: 0;">Manage customer accounts, licenses, and engagement</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Total Customers</div>
                                <div style="font-size: 28px; font-weight: 600; color: #1d4dd4;"><?php echo number_format($total_customers); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-users" style="font-size: 20px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Active Customers</div>
                                <div style="font-size: 28px; font-weight: 600; color: #1d4dd4;"><?php echo number_format($customers_with_active); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user-check" style="font-size: 20px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Total Licenses</div>
                                <div style="font-size: 28px; font-weight: 600; color: #1d4dd4;"><?php echo number_format($stats['total_licenses']); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-key" style="font-size: 20px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card" style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Avg Licenses/Customer</div>
                                <div style="font-size: 28px; font-weight: 600; color: #1d4dd4;"><?php echo number_format($avg_licenses, 1); ?></div>
                            </div>
                            <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-chart-line" style="font-size: 20px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden; margin-bottom: 24px;">
                    <div class="card-body" style="padding: 20px;">
                        <form method="GET" action="" style="display: flex; gap: 12px; align-items: center;">
                            <div style="flex: 1; position: relative;">
                                <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 13px;"></i>
                                <input type="text" name="search" placeholder="Search by customer name or email..." 
                                    value="<?php echo htmlspecialchars($search_filter); ?>" 
                                    style="width: 100%; padding: 10px 14px 10px 40px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; transition: all 0.2s;">
                            </div>
                            <button type="submit" class="btn" style="background: #1d4dd4; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; font-size: 14px;"
                                onmouseover="this.style.background='#1a3fb8'" 
                                onmouseout="this.style.background='#1d4dd4'">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <?php if (!empty($search_filter)): ?>
                            <a href="customers.php" style="padding: 10px 20px; background: #6b7280; color: white; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: all 0.2s;"
                                onmouseover="this.style.background='#4b5563'" 
                                onmouseout="this.style.background='#6b7280'">
                                <i class="fas fa-times"></i> Clear
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Customers Table -->
                <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden;">
                    <div class="card-body" style="padding: 0;">
                        <div style="overflow-x: auto;">
                            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-user" style="margin-right: 6px;"></i> Customer Name
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-envelope" style="margin-right: 6px;"></i> Email Address
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-key" style="margin-right: 6px;"></i> Total Licenses
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-check-circle" style="margin-right: 6px;"></i> Active
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-clock" style="margin-right: 6px;"></i> Expired
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-calendar" style="margin-right: 6px;"></i> First Purchase
                                        </th>
                                        <th style="padding: 14px 20px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-cog" style="margin-right: 6px;"></i> Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="7" style="padding: 60px 20px; text-align: center;">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                            <div style="width: 64px; height: 64px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-users" style="font-size: 28px; color: #9ca3af;"></i>
                                            </div>
                                            <div style="color: #6b7280; font-size: 16px; font-weight: 500;">No customers found</div>
                                            <div style="color: #9ca3af; font-size: 14px;">
                                                <?php if (!empty($search_filter)): ?>
                                                    Try adjusting your search terms
                                                <?php else: ?>
                                                    No customer data available yet
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                <tr style="border-bottom: 1px solid #f3f4f6; transition: background-color 0.15s ease;" 
                                    onmouseover="this.style.backgroundColor='#fafbfc'" 
                                    onmouseout="this.style.backgroundColor='white'">
                                    <td style="padding: 14px 20px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div style="width: 32px; height: 32px; background: #eff6ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                <i class="fas fa-user" style="color: #1d4dd4; font-size: 13px;"></i>
                                            </div>
                                            <span style="font-weight: 500; color: #111827; font-size: 14px;"><?php echo htmlspecialchars($customer['customer_name']); ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <i class="fas fa-envelope" style="color: #9ca3af; font-size: 12px;"></i>
                                            <a href="mailto:<?php echo htmlspecialchars($customer['customer_email']); ?>" 
                                                style="color: #1d4dd4; text-decoration: none; font-size: 14px;"
                                                onmouseover="this.style.textDecoration='underline'" 
                                                onmouseout="this.style.textDecoration='none'">
                                                <?php echo htmlspecialchars($customer['customer_email']); ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <span style="font-weight: 600; color: #374151; font-size: 14px;"><?php echo $customer['total_licenses']; ?></span>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; background: #1d4dd4; color: white;">
                                            <?php echo $customer['active_licenses']; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <?php if ($customer['expired_licenses'] > 0): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; background: #f59e0b; color: white;">
                                                <?php echo $customer['expired_licenses']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af; font-size: 13px;">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 20px;">
                                        <span style="color: #6b7280; font-size: 13px;">
                                            <?php echo date('M d, Y', strtotime($customer['first_purchase'])); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 20px; text-align: center;">
                                        <div style="display: flex; gap: 6px; justify-content: center;">
                                            <button onclick="viewCustomer('<?php echo urlencode($customer['customer_email']); ?>')" 
                                                style="padding: 7px 12px; background: #1d4dd4; color: white; border: none; border-radius: 7px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;"
                                                onmouseover="this.style.background='#1a3fb8'" 
                                                onmouseout="this.style.background='#1d4dd4'"
                                                title="View Licenses">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button onclick="emailCustomer('<?php echo htmlspecialchars($customer['customer_email']); ?>')" 
                                                style="padding: 7px 12px; background: #6b7280; color: white; border: none; border-radius: 7px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;"
                                                onmouseover="this.style.background='#4b5563'" 
                                                onmouseout="this.style.background='#6b7280'"
                                                title="Send Email">
                                                <i class="fas fa-envelope"></i> Email
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function viewCustomer(email) {
        window.location.href = 'licenses.php?search=' + email;
    }

    function emailCustomer(email) {
        window.location.href = 'mailto:' + email;
    }
    </script>
</body>
</html>
