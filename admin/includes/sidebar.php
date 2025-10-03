<!-- Sidebar Navigation -->
<div class="sidebar">
    <div class="sidebar-header">
        <h3>
            <i class="fas fa-shield-alt"></i>
            Zwicky License Manager
        </h3>
        <div class="version">Version <?php echo LMS_VERSION; ?></div>
    </div>
    
    <div class="sidebar-nav">
        <div class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </div>
        
        <div class="nav-item">
            <a href="license-manager.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'license-manager.php' ? 'active' : ''; ?>">
                <i class="fas fa-key"></i>
                License Manager
            </a>
        </div>
        
        <div class="nav-item">
            <a href="licenses.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'licenses.php' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i>
                License List
            </a>
        </div>
        
        <div class="nav-item">
            <a href="activations.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'activations.php' ? 'active' : ''; ?>">
                <i class="fas fa-globe"></i>
                Activations
            </a>
        </div>
        
        <div class="nav-item">
            <a href="customers.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                Customers
            </a>
        </div>
        
        <div class="nav-item">
            <a href="logs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                Activity Logs
            </a>
        </div>
        
        <div class="nav-item">
            <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                Reports
            </a>
        </div>
        
        <?php if ($auth->hasPermission('admin')): ?>
        <div class="nav-item">
            <a href="admin-users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin-users.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i>
                Admin Users
            </a>
        </div>
        
        <div class="nav-item">
            <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                Settings
            </a>
        </div>
        <?php endif; ?>
        
        <div class="nav-item">
            <a href="api-docs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'api-docs.php' ? 'active' : ''; ?>">
                <i class="fas fa-code"></i>
                API Documentation
            </a>
        </div>
        
        <div class="nav-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                My Profile
            </a>
        </div>
        
        <div class="nav-item">
            <a href="logout.php" class="nav-link" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</div>