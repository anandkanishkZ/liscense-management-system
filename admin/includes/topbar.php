<!-- Top Navigation Bar -->
<div class="top-bar">
    <div class="page-title-section">
        <button class="mobile-menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title">
            <?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?>
        </h1>
    </div>
    
    <div class="user-menu">
        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="quick-stat">
                <span class="stat-value"><?php echo $stats['active_licenses'] ?? 0; ?></span>
                <span class="stat-label">Active</span>
            </div>
            <div class="quick-stat">
                <span class="stat-value"><?php echo $stats['total_activations'] ?? 0; ?></span>
                <span class="stat-label">Domains</span>
            </div>
        </div>
        
        <!-- Notifications -->
        <div class="notification-bell" onclick="toggleNotifications()">
            <i class="fas fa-bell"></i>
            <span class="notification-count">3</span>
        </div>
        
        <!-- User Info -->
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?></div>
                <div class="user-role"><?php echo ucfirst($current_user['role']); ?></div>
            </div>
        </div>
        
        <!-- User Dropdown -->
        <div class="user-dropdown" onclick="toggleUserDropdown()">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
</div>

<!-- User Dropdown Menu -->
<div class="dropdown-menu user-dropdown-menu" id="userDropdownMenu">
    <div class="dropdown-header">
        <div class="user-info-full">
            <div class="user-avatar-large">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <div class="dropdown-user-name"><?php echo htmlspecialchars($current_user['full_name']); ?></div>
                <div class="dropdown-user-email"><?php echo htmlspecialchars($current_user['email']); ?></div>
            </div>
        </div>
    </div>
    
    <div class="dropdown-body">
        <a href="profile.php" class="dropdown-item">
            <i class="fas fa-user"></i>
            My Profile
        </a>
        <a href="settings.php" class="dropdown-item">
            <i class="fas fa-cog"></i>
            Settings
        </a>
        <a href="api-docs.php" class="dropdown-item">
            <i class="fas fa-code"></i>
            API Docs
        </a>
        <div class="dropdown-divider"></div>
        <a href="logout.php" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to logout?')">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>
</div>

<!-- Notifications Dropdown -->
<div class="dropdown-menu notifications-dropdown" id="notificationsDropdown">
    <div class="dropdown-header">
        <h6>Notifications</h6>
        <button class="btn-clear-all">Clear All</button>
    </div>
    
    <div class="dropdown-body">
        <div class="notification-item">
            <div class="notification-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">5 licenses expiring soon</div>
                <div class="notification-time">2 hours ago</div>
            </div>
        </div>
        
        <div class="notification-item">
            <div class="notification-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">New license activated</div>
                <div class="notification-time">1 day ago</div>
            </div>
        </div>
        
        <div class="notification-item">
            <div class="notification-icon info">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">System backup completed</div>
                <div class="notification-time">2 days ago</div>
            </div>
        </div>
    </div>
    
    <div class="dropdown-footer">
        <a href="notifications.php" class="view-all-link">View All Notifications</a>
    </div>
</div>

<style>
/* Top Bar Styles */
.top-bar {
    background: white;
    padding: 15px 30px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
}

.page-title-section {
    display: flex;
    align-items: center;
    gap: 15px;
}

.mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 18px;
    color: #6c757d;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.mobile-menu-toggle:hover {
    background-color: #f8f9fa;
    color: #495057;
}

.page-title {
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.user-menu {
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
}

.quick-stats {
    display: flex;
    gap: 20px;
    padding-right: 20px;
    border-right: 1px solid #e9ecef;
}

.quick-stat {
    text-align: center;
}

.quick-stat .stat-value {
    display: block;
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    line-height: 1;
}

.quick-stat .stat-label {
    display: block;
    font-size: 11px;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 2px;
}

.notification-bell {
    position: relative;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.3s ease;
    color: #6c757d;
}

.notification-bell:hover {
    background-color: #f8f9fa;
    color: #495057;
}

.notification-count {
    position: absolute;
    top: 0;
    right: 0;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.user-info:hover {
    background-color: #f8f9fa;
}

.user-avatar {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}

.user-details {
    line-height: 1.2;
}

.user-name {
    font-size: 14px;
    font-weight: 500;
    color: #2c3e50;
}

.user-role {
    font-size: 12px;
    color: #6c757d;
    text-transform: capitalize;
}

.user-dropdown {
    cursor: pointer;
    padding: 8px;
    color: #6c757d;
    transition: all 0.3s ease;
}

.user-dropdown:hover {
    color: #495057;
}

/* Dropdown Menus */
.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    min-width: 250px;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
}

.dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-header {
    padding: 20px;
    border-bottom: 1px solid #f1f3f4;
    background-color: #f8f9fa;
}

.dropdown-header h6 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.btn-clear-all {
    background: none;
    border: none;
    color: #667eea;
    font-size: 12px;
    cursor: pointer;
    text-decoration: underline;
}

.user-info-full {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-avatar-large {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}

.dropdown-user-name {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 2px;
}

.dropdown-user-email {
    font-size: 13px;
    color: #6c757d;
}

.dropdown-body {
    padding: 10px 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 20px;
    color: #495057;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    color: #2c3e50;
}

.dropdown-item.text-danger {
    color: #dc3545;
}

.dropdown-item.text-danger:hover {
    background-color: #f8d7da;
    color: #721c24;
}

.dropdown-item i {
    width: 16px;
    text-align: center;
}

.dropdown-divider {
    height: 1px;
    background-color: #f1f3f4;
    margin: 8px 0;
}

.dropdown-footer {
    padding: 15px 20px;
    border-top: 1px solid #f1f3f4;
    background-color: #f8f9fa;
}

.view-all-link {
    color: #667eea;
    font-size: 13px;
    text-decoration: none;
    font-weight: 500;
}

.view-all-link:hover {
    text-decoration: underline;
}

/* Notification Items */
.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid #f1f3f4;
    transition: all 0.3s ease;
    cursor: pointer;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

.notification-icon.success {
    background-color: #d4edda;
    color: #155724;
}

.notification-icon.warning {
    background-color: #fff3cd;
    color: #856404;
}

.notification-icon.info {
    background-color: #d1ecf1;
    color: #0c5460;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-size: 13px;
    font-weight: 500;
    color: #2c3e50;
    margin-bottom: 2px;
    line-height: 1.3;
}

.notification-time {
    font-size: 11px;
    color: #6c757d;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .top-bar {
        padding: 15px 20px;
    }
    
    .mobile-menu-toggle {
        display: block;
    }
    
    .page-title {
        font-size: 20px;
    }
    
    .quick-stats {
        display: none;
    }
    
    .user-menu {
        gap: 15px;
    }
}
</style>

<script>
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdownMenu');
    const notifications = document.getElementById('notificationsDropdown');
    
    // Close notifications if open
    notifications.classList.remove('show');
    
    // Toggle user dropdown
    dropdown.classList.toggle('show');
}

function toggleNotifications() {
    const notifications = document.getElementById('notificationsDropdown');
    const userDropdown = document.getElementById('userDropdownMenu');
    
    // Close user dropdown if open
    userDropdown.classList.remove('show');
    
    // Toggle notifications
    notifications.classList.toggle('show');
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const userMenu = document.querySelector('.user-menu');
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    
    if (!userMenu.contains(event.target)) {
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});

// Close dropdowns on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});
</script>