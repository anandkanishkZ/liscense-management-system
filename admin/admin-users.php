<?php
/**
 * Zwicky Technology License Management System
 * Admin Users Management
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

// Fetch admin users from database
try {
    $db = getLMSDatabase();
    $query = "SELECT id, username, full_name, email, role, status, created_at, last_login 
                FROM " . LMS_TABLE_ADMIN_USERS . " 
                ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching admin users: " . $e->getMessage());
    $admin_users = [];
}

// Get statistics
$stats = $license_manager->getStatistics();

$page_title = 'Admin Users';
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
                            <i class="fas fa-user-shield"></i>
                            Admin Users
                        </h1>
                        <p class="page-description">Manage administrator accounts and access permissions</p>
                    </div>
                </div>

                <!-- Admin Users Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-shield"></i>
                            Admin Users
                        </h3>
                        <button class="btn btn-primary" onclick="alert('Add admin user feature coming soon!')">
                            <i class="fas fa-plus"></i> Add Admin User
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="license-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admin_users as $user): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-user-circle" style="color: #667eea; margin-right: 5px;"></i>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['last_login']): ?>
                                            <?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?>
                                        <?php else: ?>
                                            <span style="color: #6c757d;">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action" onclick="editUser(<?php echo $user['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['id'] != $current_user['id']): ?>
                                            <button class="btn-action danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function editUser(userId) {
        alert('Edit user feature coming soon! User ID: ' + userId);
    }

    function deleteUser(userId, username) {
        if (confirm('Are you sure you want to delete admin user: ' + username + '?')) {
            alert('Delete user feature coming soon! User ID: ' + userId);
        }
    }
    </script>
</body>
</html>
