<?php
if (!isset($auth) || !($auth instanceof LMSAdminAuth)) {
    try {
        $auth = new LMSAdminAuth();
    } catch (Exception $e) {
        $auth = null;
    }
}

if (!isset($current_user) || !is_array($current_user)) {
    try {
        $current_user = $auth ? $auth->getCurrentUser() : [];
    } catch (Exception $e) {
        $current_user = [];
    }
}

$current_user = array_merge([
    'full_name' => 'System Administrator',
    'role' => 'admin',
    'email' => 'admin@example.com'
], array_filter($current_user ?? []));

if (!isset($license_manager) || !($license_manager instanceof LMSLicenseManager)) {
    try {
        $license_manager = new LMSLicenseManager();
    } catch (Exception $e) {
        $license_manager = null;
    }
}

try {
    $stats = array_merge([
        'active_licenses' => 0,
        'total_activations' => 0
    ], is_array($stats ?? null) ? $stats : []);
} catch (Exception $e) {
    $stats = [
        'active_licenses' => 0,
        'total_activations' => 0
    ];
}

if (!isset($logger) || !($logger instanceof LMSLogger)) {
    try {
        $logger = new LMSLogger();
    } catch (Exception $e) {
        $logger = null;
    }
}

$notification_limit = 5;

if (!isset($recent_logs) || !is_array($recent_logs)) {
    try {
        $recent_logs = $logger ? $logger->getLogs($notification_limit) : [];
    } catch (Exception $e) {
        $recent_logs = [];
        $notifications_error = $e->getMessage();
    }
} else {
    $recent_logs = array_slice($recent_logs, 0, $notification_limit);
}

try {
    $notification_count = $logger ? $logger->getLogCount() : count($recent_logs);
} catch (Exception $e) {
    $notification_count = count($recent_logs);
    $notifications_error = $notifications_error ?? $e->getMessage();
}

$notification_styles = [
    'ERROR' => ['class' => 'danger', 'icon' => 'fas fa-times-circle'],
    'WARNING' => ['class' => 'warning', 'icon' => 'fas fa-exclamation-triangle'],
    'INFO' => ['class' => 'info', 'icon' => 'fas fa-info-circle'],
    'DEBUG' => ['class' => 'info', 'icon' => 'fas fa-info-circle'],
    'SUCCESS' => ['class' => 'success', 'icon' => 'fas fa-check-circle']
];

if (!function_exists('lms_time_elapsed_string')) {
    function lms_time_elapsed_string($datetime)
    {
        $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime((string)$datetime);
        if (!$timestamp) {
            return '';
        }

        $diff = time() - $timestamp;
        if ($diff < 0) {
            $diff = 0;
        }

        $units = [
            31536000 => 'year',
            2592000  => 'month',
            604800   => 'week',
            86400    => 'day',
            3600     => 'hour',
            60       => 'minute',
            1        => 'second'
        ];

        foreach ($units as $seconds => $label) {
            if ($diff >= $seconds) {
                $value = (int)floor($diff / $seconds);
                return $value . ' ' . $label . ($value !== 1 ? 's' : '') . ' ago';
            }
        }

        return 'Just now';
    }
}

if (!function_exists('lms_summarize_log_context')) {
    function lms_summarize_log_context($log)
    {
        $context = [];
        if (!empty($log['context'])) {
            $decoded = json_decode($log['context'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $context = $decoded;
            }
        }

        if (!empty($context['license_key'])) {
            return 'License: ' . $context['license_key'];
        }

        if (!empty($context['admin_user'])) {
            return 'By: ' . $context['admin_user'];
        }

        if (!empty($context['customer_email'])) {
            return 'Customer: ' . $context['customer_email'];
        }

        if (!empty($log['level'])) {
            return ucwords(str_replace('_', ' ', strtolower($log['level'])));
        }

        return '';
    }
}

$notification_count = (int)$notification_count;
?>
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
            <?php if ($notification_count > 0): ?>
            <span class="notification-count"><?php echo $notification_count; ?></span>
            <?php endif; ?>
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
        <a href="logs.php" class="btn-clear-all">View Logs</a>
    </div>
    
    <div 
        class="dropdown-body notifications-body" 
        id="notificationsList" 
        data-limit="<?php echo $notification_limit; ?>" 
        data-has-more="<?php echo ($notification_count > count($recent_logs)) ? '1' : '0'; ?>"
        data-total="<?php echo $notification_count; ?>">
        <?php if (!empty($recent_logs)): ?>
            <?php foreach ($recent_logs as $log): ?>
                <?php
                    $level = strtoupper($log['level'] ?? 'INFO');
                    $style = $notification_styles[$level] ?? ['class' => 'info', 'icon' => 'fas fa-info-circle'];
                    $meta = lms_summarize_log_context($log);
                ?>
                <div class="notification-item" data-id="<?php echo $log['id'] ?? ''; ?>">
                    <div class="notification-icon <?php echo $style['class']; ?>">
                        <i class="<?php echo $style['icon']; ?>"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title"><?php echo htmlspecialchars($log['message']); ?></div>
                        <?php if (!empty($meta)): ?>
                        <div class="notification-meta"><?php echo htmlspecialchars($meta); ?></div>
                        <?php endif; ?>
                        <div class="notification-time"><?php echo lms_time_elapsed_string($log['created_at'] ?? ''); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-notifications">
                <i class="fas fa-bell-slash"></i>
                <p>No recent notifications</p>
                <?php if (!empty($notifications_error)): ?>
                <small><?php echo htmlspecialchars($notifications_error); ?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="dropdown-footer notifications-footer">
        <button type="button" class="btn-load-more" id="loadMoreNotifications" onclick="loadMoreNotifications()">
            <i class="fas fa-chevron-down"></i>
            <span>Load More</span>
        </button>
        <a href="logs.php" class="view-all-link">View All Notifications</a>
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

.notifications-body {
    max-height: 320px;
    overflow-y: auto;
}

.notifications-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.btn-load-more {
    background: #e9ecef;
    border: none;
    color: #495057;
    font-size: 12px;
    font-weight: 600;
    padding: 8px 12px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-load-more:hover {
    background: #dde1e5;
}

.btn-load-more i {
    font-size: 10px;
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

.notification-icon.danger {
    background-color: #f8d7da;
    color: #721c24;
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

.notification-meta {
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 2px;
}

.empty-notifications {
    padding: 30px 20px;
    text-align: center;
    color: #6c757d;
}

.empty-notifications i {
    font-size: 24px;
    margin-bottom: 10px;
}

.empty-notifications p {
    margin: 0;
    font-weight: 500;
    color: #495057;
}

.empty-notifications small {
    display: block;
    margin-top: 6px;
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
const notificationStylesMap = {
    'ERROR': { className: 'danger', icon: 'fas fa-times-circle' },
    'WARNING': { className: 'warning', icon: 'fas fa-exclamation-triangle' },
    'INFO': { className: 'info', icon: 'fas fa-info-circle' },
    'DEBUG': { className: 'info', icon: 'fas fa-info-circle' },
    'SUCCESS': { className: 'success', icon: 'fas fa-check-circle' }
};

const notificationsListElement = document.getElementById('notificationsList');
const notificationBellElement = document.querySelector('.notification-bell');
const loadMoreNotificationsButton = document.getElementById('loadMoreNotifications');

const notificationsState = {
    limit: notificationsListElement ? parseInt(notificationsListElement.dataset.limit || '5', 10) : 5,
    total: notificationsListElement ? parseInt(notificationsListElement.dataset.total || '0', 10) : 0,
    loading: false,
    loaded: !!(notificationsListElement && notificationsListElement.children.length && !notificationsListElement.querySelector('.empty-notifications')),
    maxLimit: 50
};

if (loadMoreNotificationsButton) {
    const hasMoreInitial = notificationsListElement && notificationsListElement.dataset.hasMore === '1';
    loadMoreNotificationsButton.style.display = hasMoreInitial ? 'inline-flex' : 'none';
}

function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdownMenu');
    const notifications = document.getElementById('notificationsDropdown');
    if (!dropdown || !notifications) return;

    notifications.classList.remove('show');
    dropdown.classList.toggle('show');
}

function toggleNotifications() {
    const notifications = document.getElementById('notificationsDropdown');
    const userDropdown = document.getElementById('userDropdownMenu');
    if (!notifications || !userDropdown) return;

    userDropdown.classList.remove('show');
    const willShow = !notifications.classList.contains('show');
    notifications.classList.toggle('show');

    if (willShow) {
        refreshNotifications(true);
    }
}

function refreshNotifications(force = false) {
    if (notificationsState.loading) {
        return;
    }

    if (notificationsState.loaded && !force) {
        return;
    }

    loadNotifications(notificationsState.limit);
}

async function loadNotifications(requestLimit) {
    if (!notificationsListElement) {
        return;
    }

    const limit = Math.min(Math.max(requestLimit || notificationsState.limit, 1), notificationsState.maxLimit);

    notificationsState.loading = true;
    notificationsListElement.classList.add('loading');
    if (loadMoreNotificationsButton) {
        loadMoreNotificationsButton.disabled = true;
    }

    try {
        const response = await fetch(`api/notifications.php?limit=${limit}`);
        if (!response.ok) {
            throw new Error('Failed to load notifications');
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Unable to load notifications');
        }

        notificationsState.limit = data.limit;
        notificationsState.total = data.total;
        notificationsState.loaded = true;

        renderNotifications(data.logs || []);
        updateNotificationCount(notificationsState.total);

        if (notificationsListElement) {
            notificationsListElement.dataset.limit = data.limit;
            notificationsListElement.dataset.total = data.total;
            notificationsListElement.dataset.hasMore = data.has_more ? '1' : '0';
        }

        if (loadMoreNotificationsButton) {
            loadMoreNotificationsButton.style.display = data.has_more ? 'inline-flex' : 'none';
            loadMoreNotificationsButton.disabled = !data.has_more;
        }
    } catch (error) {
        renderNotificationError(error.message);
    } finally {
        notificationsState.loading = false;
        notificationsListElement.classList.remove('loading');
        if (loadMoreNotificationsButton && loadMoreNotificationsButton.style.display !== 'none') {
            loadMoreNotificationsButton.disabled = false;
        }
    }
}

function renderNotifications(logs) {
    if (!notificationsListElement) {
        return;
    }

    if (!logs.length) {
        notificationsListElement.innerHTML = `
            <div class="empty-notifications">
                <i class="fas fa-bell-slash"></i>
                <p>No recent notifications</p>
            </div>
        `;
        return;
    }

    notificationsListElement.innerHTML = logs.map(renderNotificationItem).join('');
}

function renderNotificationItem(log) {
    const level = (log.level || 'INFO').toUpperCase();
    const style = notificationStylesMap[level] || notificationStylesMap['INFO'];
    const meta = summarizeLogContext(log);
    const time = formatTimeAgo(log.created_at);

    return `
        <div class="notification-item" data-id="${escapeHtml(log.id ?? '')}">
            <div class="notification-icon ${style.className}">
                <i class="${style.icon}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">${escapeHtml(log.message || '')}</div>
                ${meta ? `<div class="notification-meta">${escapeHtml(meta)}</div>` : ''}
                <div class="notification-time">${escapeHtml(time)}</div>
            </div>
        </div>
    `;
}

function renderNotificationError(message) {
    if (!notificationsListElement) {
        return;
    }

    notificationsListElement.innerHTML = `
        <div class="empty-notifications">
            <i class="fas fa-exclamation-triangle"></i>
            <p>Unable to load notifications</p>
            <small>${escapeHtml(message || 'Please try again later.')}</small>
        </div>
    `;
}

function updateNotificationCount(count) {
    if (!notificationBellElement) {
        return;
    }

    let badge = notificationBellElement.querySelector('.notification-count');
    const displayValue = count > 99 ? '99+' : count;

    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'notification-count';
            notificationBellElement.appendChild(badge);
        }
        badge.textContent = displayValue;
    } else if (badge) {
        badge.remove();
    }
}

function loadMoreNotifications() {
    if (notificationsState.loading) {
        return;
    }

    const hasMore = notificationsListElement && notificationsListElement.dataset.hasMore === '1';
    if (!hasMore) {
        if (loadMoreNotificationsButton) {
            loadMoreNotificationsButton.style.display = 'none';
        }
        return;
    }

    const newLimit = Math.min(notificationsState.limit + 5, notificationsState.maxLimit);
    if (newLimit === notificationsState.limit) {
        if (loadMoreNotificationsButton) {
            loadMoreNotificationsButton.style.display = 'none';
        }
        return;
    }

    loadNotifications(newLimit);
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

function formatTimeAgo(dateString) {
    if (!dateString) {
        return '';
    }

    const timestamp = Date.parse(dateString);
    if (Number.isNaN(timestamp)) {
        return dateString;
    }

    const now = Date.now();
    let diff = Math.max(0, Math.floor((now - timestamp) / 1000));
    const units = [
        { seconds: 31536000, label: 'year' },
        { seconds: 2592000, label: 'month' },
        { seconds: 604800, label: 'week' },
        { seconds: 86400, label: 'day' },
        { seconds: 3600, label: 'hour' },
        { seconds: 60, label: 'minute' },
        { seconds: 1, label: 'second' }
    ];

    for (const unit of units) {
        if (diff >= unit.seconds) {
            const value = Math.floor(diff / unit.seconds);
            return `${value} ${unit.label}${value !== 1 ? 's' : ''} ago`;
        }
    }

    return 'Just now';
}

function summarizeLogContext(log) {
    if (!log || !log.context) {
        return log && log.level ? log.level.toLowerCase() : '';
    }

    const context = log.context;
    if (context.license_key) {
        return `License: ${context.license_key}`;
    }
    if (context.admin_user) {
        return `By: ${context.admin_user}`;
    }
    if (context.customer_email) {
        return `Customer: ${context.customer_email}`;
    }
    if (log.level) {
        return log.level.toLowerCase();
    }
    return '';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('click', function(event) {
    const userMenu = document.querySelector('.user-menu');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const userDropdown = document.getElementById('userDropdownMenu');

    const isInsideUserMenu = userMenu && userMenu.contains(event.target);
    const isInsideNotifications = notificationsDropdown && notificationsDropdown.contains(event.target);

    if (!isInsideUserMenu && !isInsideNotifications) {
        document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});

window.refreshNotifications = () => refreshNotifications(true);
window.loadMoreNotifications = loadMoreNotifications;
</script>