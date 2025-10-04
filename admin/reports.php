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

// Get statistics
$stats = $license_manager->getStatistics();

// Get licenses by status
$stmt = $db->query("SELECT status, COUNT(*) as count FROM " . LMS_TABLE_LICENSES . " GROUP BY status");
$licenses_by_status = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get licenses by product
$stmt = $db->query("SELECT product_name, COUNT(*) as count FROM " . LMS_TABLE_LICENSES . " GROUP BY product_name ORDER BY count DESC LIMIT 10");
$licenses_by_product = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent activations (last 30 days)
$stmt = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM " . LMS_TABLE_ACTIVATIONS . " 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
$recent_activations = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <i class="fas fa-chart-bar"></i>
                            Reports & Analytics
                        </h1>
                        <p class="page-description">Comprehensive analytics and visual reports for license data</p>
                    </div>
                </div>

                <!-- Overview Stats -->
                <div class="stats-grid" style="margin-bottom: 20px;">
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fas fa-key"></i></div>
                        <div class="stat-value"><?php echo number_format($stats['total_licenses']); ?></div>
                        <div class="stat-label">Total Licenses</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-value"><?php echo number_format($stats['active_licenses']); ?></div>
                        <div class="stat-label">Active Licenses</div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-value"><?php echo number_format($stats['expired_licenses']); ?></div>
                        <div class="stat-label">Expired Licenses</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-globe"></i></div>
                        <div class="stat-value"><?php echo number_format($stats['total_activations']); ?></div>
                        <div class="stat-label">Active Domains</div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <!-- License Status Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie"></i>
                                License Status Distribution
                            </h3>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart" style="max-height: 300px;"></canvas>
                        </div>
                    </div>

                    <!-- Activations Trend Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line"></i>
                                Activations (Last 30 Days)
                            </h3>
                        </div>
                        <div class="card-body">
                            <canvas id="activationsChart" style="max-height: 300px;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Product Performance -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-box"></i>
                            Top Products by License Count
                        </h3>
                    </div>
                    <div class="table-responsive">
                        <table class="license-table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Total Licenses</th>
                                    <th>Percentage</th>
                                    <th>Visual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($licenses_by_product)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 40px;">
                                        <p>No product data available</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($licenses_by_product as $product): ?>
                                <?php $percentage = ($product['count'] / $stats['total_licenses']) * 100; ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                    <td><?php echo number_format($product['count']); ?></td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                    <td>
                                        <div style="background: #e9ecef; border-radius: 4px; height: 20px; overflow: hidden;">
                                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%; width: <?php echo $percentage; ?>%;"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Export Options -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-download"></i>
                            Export Reports
                        </h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button class="btn btn-primary" onclick="alert('CSV export coming soon!')">
                                <i class="fas fa-file-csv"></i> Export to CSV
                            </button>
                            <button class="btn btn-primary" onclick="alert('PDF export coming soon!')">
                                <i class="fas fa-file-pdf"></i> Export to PDF
                            </button>
                            <button class="btn btn-primary" onclick="alert('Excel export coming soon!')">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map('ucfirst', array_keys($licenses_by_status))); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($licenses_by_status)); ?>,
                backgroundColor: [
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#6c757d'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Activations Chart
    const activationsCtx = document.getElementById('activationsChart').getContext('2d');
    new Chart(activationsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_reverse(array_column($recent_activations, 'date'))); ?>,
            datasets: [{
                label: 'Activations',
                data: <?php echo json_encode(array_reverse(array_column($recent_activations, 'count'))); ?>,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    </script>
</body>
</html>
