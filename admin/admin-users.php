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
$db = getLMSDatabase();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add_user':
            try {
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $full_name = trim($_POST['full_name']);
                $password = $_POST['password'];
                $role = $_POST['role'];
                
                // Validate
                if (empty($username) || empty($email) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    exit;
                }
                
                // Check if username exists
                $stmt = $db->prepare("SELECT id FROM " . LMS_TABLE_ADMIN_USERS . " WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Username already exists']);
                    exit;
                }
                
                // Check if email exists
                $stmt = $db->prepare("SELECT id FROM " . LMS_TABLE_ADMIN_USERS . " WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Email already exists']);
                    exit;
                }
                
                // Insert new user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO " . LMS_TABLE_ADMIN_USERS . " 
                    (username, email, full_name, password_hash, role, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'active', NOW())
                ");
                $stmt->execute([$username, $email, $full_name, $password_hash, $role]);
                
                echo json_encode(['success' => true, 'message' => 'Admin user created successfully']);
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
                exit;
            }
            break;
            
        case 'update_user':
            try {
                $user_id = intval($_POST['user_id']);
                $email = trim($_POST['email']);
                $full_name = trim($_POST['full_name']);
                $role = $_POST['role'];
                $status = $_POST['status'];
                
                // Check if email exists for other users
                $stmt = $db->prepare("SELECT id FROM " . LMS_TABLE_ADMIN_USERS . " WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Email already exists']);
                    exit;
                }
                
                // Update user
                $stmt = $db->prepare("
                    UPDATE " . LMS_TABLE_ADMIN_USERS . " 
                    SET email = ?, full_name = ?, role = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$email, $full_name, $role, $status, $user_id]);
                
                // Update password if provided
                if (!empty($_POST['password'])) {
                    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE " . LMS_TABLE_ADMIN_USERS . " SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$password_hash, $user_id]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Admin user updated successfully']);
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
                exit;
            }
            break;
            
        case 'delete_user':
            try {
                $user_id = intval($_POST['user_id']);
                
                // Don't allow deleting current user
                if ($user_id == $current_user['id']) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
                    exit;
                }
                
                // Delete user
                $stmt = $db->prepare("DELETE FROM " . LMS_TABLE_ADMIN_USERS . " WHERE id = ?");
                $stmt->execute([$user_id]);
                
                echo json_encode(['success' => true, 'message' => 'Admin user deleted successfully']);
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
                exit;
            }
            break;
            
        case 'get_user':
            try {
                $user_id = intval($_POST['user_id']);
                $stmt = $db->prepare("
                    SELECT id, username, email, full_name, role, status 
                    FROM " . LMS_TABLE_ADMIN_USERS . " 
                    WHERE id = ?
                ");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    echo json_encode(['success' => true, 'user' => $user]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
                exit;
            }
            break;
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$query = "SELECT id, username, full_name, email, role, status, created_at, last_login 
          FROM " . LMS_TABLE_ADMIN_USERS . " WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($role_filter) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

if ($status_filter) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY created_at DESC";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as active_today
        FROM " . LMS_TABLE_ADMIN_USERS;
    $admin_stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching admin users: " . $e->getMessage());
    $admin_users = [];
    $admin_stats = ['total' => 0, 'active' => 0, 'admins' => 0, 'active_today' => 0];
}

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
            
            <div class="content-area" style="padding: 24px;">
                <!-- Page Header -->
                <div style="background: linear-gradient(135deg, #1d4dd4 0%, #1a3fb8 100%); padding: 32px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 6px rgba(29, 77, 212, 0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <div>
                            <h1 style="color: white; font-size: 28px; font-weight: 700; margin: 0 0 8px 0; display: flex; align-items: center; gap: 12px;">
                                <i class="fas fa-user-shield"></i>
                                Admin Users Management
                            </h1>
                            <p style="color: rgba(255,255,255,0.85); font-size: 14px; margin: 0;">Manage administrator accounts and access permissions</p>
                        </div>
                        <button onclick="openAddUserModal()" style="background: white; color: #1d4dd4; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all 0.2s;"
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                            onmouseout="this.style.transform=''; this.style.boxShadow=''">
                            <i class="fas fa-plus"></i> Add Admin User
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Total Admin Users</div>
                                <div style="font-size: 32px; font-weight: 700; color: #1d4dd4;"><?php echo number_format($admin_stats['total']); ?></div>
                            </div>
                            <div style="width: 56px; height: 56px; background: #eff6ff; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-users" style="font-size: 24px; color: #1d4dd4;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Active Users</div>
                                <div style="font-size: 32px; font-weight: 700; color: #10b981;"><?php echo number_format($admin_stats['active']); ?></div>
                            </div>
                            <div style="width: 56px; height: 56px; background: #f0fdf4; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user-check" style="font-size: 24px; color: #10b981;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Super Admins</div>
                                <div style="font-size: 32px; font-weight: 700; color: #ef4444;"><?php echo number_format($admin_stats['admins']); ?></div>
                            </div>
                            <div style="width: 56px; height: 56px; background: #fef2f2; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user-shield" style="font-size: 24px; color: #ef4444;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">Active Today</div>
                                <div style="font-size: 32px; font-weight: 700; color: #8b5cf6;"><?php echo number_format($admin_stats['active_today']); ?></div>
                            </div>
                            <div style="width: 56px; height: 56px; background: #faf5ff; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-clock" style="font-size: 24px; color: #8b5cf6;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; margin-bottom: 24px;">
                    <div class="card-body" style="padding: 20px;">
                        <form method="GET" action="" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
                            <div style="flex: 1; min-width: 250px;">
                                <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    <i class="fas fa-search" style="margin-right: 6px;"></i> Search
                                </label>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                    placeholder="Search by username, email, or name..." 
                                    style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                            </div>
                            
                            <div style="min-width: 150px;">
                                <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    <i class="fas fa-user-tag" style="margin-right: 6px;"></i> Role
                                </label>
                                <select name="role" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                                    <option value="">All Roles</option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="manager" <?php echo $role_filter === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="viewer" <?php echo $role_filter === 'viewer' ? 'selected' : ''; ?>>Viewer</option>
                                </select>
                            </div>
                            
                            <div style="min-width: 150px;">
                                <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                                    <i class="fas fa-toggle-on" style="margin-right: 6px;"></i> Status
                                </label>
                                <select name="status" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <button type="submit" style="background: #1d4dd4; color: white; padding: 10px 24px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; font-size: 14px;"
                                onmouseover="this.style.background='#1a3fb8'" 
                                onmouseout="this.style.background='#1d4dd4'">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            
                            <?php if ($search || $role_filter || $status_filter): ?>
                            <a href="admin-users.php" style="padding: 10px 20px; background: #6b7280; color: white; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500;"
                                onmouseover="this.style.background='#4b5563'" 
                                onmouseout="this.style.background='#6b7280'">
                                <i class="fas fa-times"></i> Reset
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card" style="border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                    <div class="card-body" style="padding: 0;">
                        <?php if (count($admin_users) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                                <thead>
                                    <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                        <th style="padding: 14px 20px; text-align: left; font-weight: 600; color: #374151; white-space: nowrap;">
                                            <i class="fas fa-user" style="margin-right: 6px; color: #1d4dd4;"></i> Username
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-weight: 600; color: #374151; white-space: nowrap;">
                                            <i class="fas fa-id-card" style="margin-right: 6px; color: #1d4dd4;"></i> Full Name
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-weight: 600; color: #374151; white-space: nowrap;">
                                            <i class="fas fa-envelope" style="margin-right: 6px; color: #1d4dd4;"></i> Email
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-weight: 600; color: #374151; white-space: nowrap;">
                                            <i class="fas fa-user-tag" style="margin-right: 6px; color: #1d4dd4;"></i> Role
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-weight: 600; color: #374151; white-space: nowrap;">
                                            <i class="fas fa-toggle-on" style="margin-right: 6px; color: #1d4dd4;"></i> Status
                                        </th>
                                        <th style="padding: 14px 20px; text-align: left; font-weight: 600; color: #374151; white-space: nowrap;">
                                            <i class="fas fa-clock" style="margin-right: 6px; color: #1d4dd4;"></i> Last Login
                                        </th>
                                        <th style="padding: 14px 20px; text-align: center; font-weight: 600; color: #374151; white-space: nowrap;">
                                            <i class="fas fa-cog" style="margin-right: 6px; color: #1d4dd4;"></i> Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admin_users as $user): ?>
                                    <tr style="border-bottom: 1px solid #e5e7eb; transition: background 0.2s;"
                                        onmouseover="this.style.background='#f9fafb'" 
                                        onmouseout="this.style.background='white'">
                                        <td style="padding: 16px 20px; color: #1f2937; font-weight: 500;">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </td>
                                        <td style="padding: 16px 20px; color: #1f2937;">
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </td>
                                        <td style="padding: 16px 20px; color: #6b7280;">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </td>
                                        <td style="padding: 16px 20px;">
                                            <?php
                                            $role_color = [
                                                'admin' => '#ef4444',
                                                'manager' => '#f59e0b',
                                                'viewer' => '#6b7280'
                                            ];
                                            $role_bg = [
                                                'admin' => '#fef2f2',
                                                'manager' => '#fffbeb',
                                                'viewer' => '#f9fafb'
                                            ];
                                            $role_icon = [
                                                'admin' => 'fa-user-shield',
                                                'manager' => 'fa-user-tie',
                                                'viewer' => 'fa-user'
                                            ];
                                            $color = $role_color[$user['role']] ?? '#6b7280';
                                            $bg = $role_bg[$user['role']] ?? '#f9fafb';
                                            $icon = $role_icon[$user['role']] ?? 'fa-user';
                                            ?>
                                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; font-weight: 500; font-size: 13px; background: <?php echo $bg; ?>; color: <?php echo $color; ?>;">
                                                <i class="fas <?php echo $icon; ?>"></i>
                                                <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 16px 20px;">
                                            <?php if ($user['status'] === 'active'): ?>
                                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; font-weight: 500; font-size: 13px; background: #f0fdf4; color: #10b981;">
                                                    <i class="fas fa-check-circle"></i> Active
                                                </span>
                                            <?php else: ?>
                                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; font-weight: 500; font-size: 13px; background: #fef2f2; color: #ef4444;">
                                                    <i class="fas fa-times-circle"></i> Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 16px 20px; color: #6b7280; font-size: 13px;">
                                            <?php echo $user['last_login'] ? date('M d, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                                        </td>
                                        <td style="padding: 16px 20px; text-align: center;">
                                            <div style="display: inline-flex; gap: 8px;">
                                                <button onclick="editUser(<?php echo $user['id']; ?>)" 
                                                    style="background: #eff6ff; color: #1d4dd4; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.2s;"
                                                    onmouseover="this.style.background='#dbeafe'; this.style.transform='translateY(-1px)'"
                                                    onmouseout="this.style.background='#eff6ff'; this.style.transform=''">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <?php if ($user['id'] != $current_user['id']): ?>
                                                <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                    style="background: #fef2f2; color: #ef4444; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.2s;"
                                                    onmouseover="this.style.background='#fee2e2'; this.style.transform='translateY(-1px)'"
                                                    onmouseout="this.style.background='#fef2f2'; this.style.transform=''">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div style="text-align: center; padding: 60px 20px; color: #9ca3af;">
                            <i class="fas fa-users" style="font-size: 64px; color: #d1d5db; margin-bottom: 16px;"></i>
                            <h3 style="font-size: 18px; font-weight: 600; color: #6b7280; margin: 0 0 8px 0;">No admin users found</h3>
                            <p style="font-size: 14px; margin: 0;">Try adjusting your search or filter criteria</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                <h2 style="margin: 0; font-size: 20px; font-weight: 600; color: #1f2937;">
                    <i class="fas fa-user-plus" style="color: #1d4dd4; margin-right: 8px;"></i>
                    Add New Admin User
                </h2>
            </div>
            <form id="addUserForm" style="padding: 24px;">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                        Username <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="username" required 
                        style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;"
                        placeholder="Enter username">
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                        Full Name <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="full_name" required 
                        style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;"
                        placeholder="Enter full name">
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                        Email <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="email" name="email" required 
                        style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;"
                        placeholder="Enter email address">
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                        Password <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="password" name="password" required 
                        style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;"
                        placeholder="Enter password (min 8 characters)">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                        Role <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="role" required 
                        style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                        <option value="viewer">Viewer</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="closeAddUserModal()" 
                        style="padding: 10px 20px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; font-size: 14px;"
                        onmouseover="this.style.background='#e5e7eb'"
                        onmouseout="this.style.background='#f3f4f6'">
                        Cancel
                    </button>
                    <button type="submit" 
                        style="padding: 10px 20px; background: #1d4dd4; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; font-size: 14px;"
                        onmouseover="this.style.background='#1a3fb8'"
                        onmouseout="this.style.background='#1d4dd4'">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                <h2 style="margin: 0; font-size: 20px; font-weight: 600; color: #1f2937;">
                    <i class="fas fa-user-edit" style="color: #1d4dd4; margin-right: 8px;"></i>
                    Edit Admin User
                </h2>
            </div>
            <form id="editUserForm" style="padding: 24px;">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                        Username
                    </label>
                    <input type="text" id="edit_username" disabled 
                        style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; background: #f9fafb; color: #6b7280;">
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                        Full Name <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="full_name" id="edit_full_name" required 
                        style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                        Email <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="email" name="email" id="edit_email" required 
                        style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                        Password
                        <small style="color: #6b7280; font-weight: 400;">(Leave blank to keep current)</small>
                    </label>
                    <input type="password" name="password" id="edit_password" 
                        style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;"
                        placeholder="Enter new password to change">
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                        Role <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="role" id="edit_role" required 
                        style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                        <option value="viewer">Viewer</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                        Status <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="status" id="edit_status" required 
                        style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px;">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="closeEditUserModal()" 
                        style="padding: 10px 20px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; font-size: 14px;"
                        onmouseover="this.style.background='#e5e7eb'"
                        onmouseout="this.style.background='#f3f4f6'">
                        Cancel
                    </button>
                    <button type="submit" 
                        style="padding: 10px 20px; background: #1d4dd4; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; font-size: 14px;"
                        onmouseover="this.style.background='#1a3fb8'"
                        onmouseout="this.style.background='#1d4dd4'">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Add User Modal Functions
    function openAddUserModal() {
        document.getElementById('addUserModal').style.display = 'flex';
        document.getElementById('addUserForm').reset();
    }

    function closeAddUserModal() {
        document.getElementById('addUserModal').style.display = 'none';
    }

    // Edit User Modal Functions
    function closeEditUserModal() {
        document.getElementById('editUserModal').style.display = 'none';
    }

    // Add User Form Submission
    document.getElementById('addUserForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'add_user');
        
        try {
            const response = await fetch('admin-users.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Admin user added successfully!', 'success');
                closeAddUserModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(result.message || 'Failed to add user', 'error');
            }
        } catch (error) {
            showNotification('An error occurred while adding user', 'error');
            console.error('Error:', error);
        }
    });

    // Edit User Function
    async function editUser(userId) {
        try {
            const formData = new FormData();
            formData.append('action', 'get_user');
            formData.append('user_id', userId);
            
            const response = await fetch('admin-users.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success && result.user) {
                const user = result.user;
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_username').value = user.username;
                document.getElementById('edit_full_name').value = user.full_name;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_role').value = user.role;
                document.getElementById('edit_status').value = user.status;
                document.getElementById('edit_password').value = '';
                
                document.getElementById('editUserModal').style.display = 'flex';
            } else {
                showNotification('Failed to load user data', 'error');
            }
        } catch (error) {
            showNotification('An error occurred while loading user', 'error');
            console.error('Error:', error);
        }
    }

    // Update User Form Submission
    document.getElementById('editUserForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'update_user');
        
        try {
            const response = await fetch('admin-users.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Admin user updated successfully!', 'success');
                closeEditUserModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(result.message || 'Failed to update user', 'error');
            }
        } catch (error) {
            showNotification('An error occurred while updating user', 'error');
            console.error('Error:', error);
        }
    });

    // Delete User Function
    async function deleteUser(userId, username) {
        if (!confirm(`Are you sure you want to delete admin user "${username}"?\n\nThis action cannot be undone.`)) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'delete_user');
        formData.append('user_id', userId);
        
        try {
            const response = await fetch('admin-users.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Admin user deleted successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(result.message || 'Failed to delete user', 'error');
            }
        } catch (error) {
            showNotification('An error occurred while deleting user', 'error');
            console.error('Error:', error);
        }
    }

    // Notification System
    function showNotification(message, type = 'info') {
        const colors = {
            success: { bg: '#10b981', icon: 'fa-check-circle' },
            error: { bg: '#ef4444', icon: 'fa-exclamation-circle' },
            info: { bg: '#1d4dd4', icon: 'fa-info-circle' }
        };
        
        const color = colors[type] || colors.info;
        
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${color.bg};
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        `;
        
        notification.innerHTML = `
            <i class="fas ${color.icon}" style="font-size: 18px;"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Close modals on outside click
    document.getElementById('addUserModal').addEventListener('click', function(e) {
        if (e.target === this) closeAddUserModal();
    });

    document.getElementById('editUserModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditUserModal();
    });

    // Animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>
