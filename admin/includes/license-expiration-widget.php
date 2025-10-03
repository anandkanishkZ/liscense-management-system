<?php
/**
 * Zwicky Technology License Management System
 * License Activation/Expiration Dashboard Widget
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

if (!defined('LMS_SECURE')) {
    die('Direct access not allowed');
}

$license_manager = new LMSLicenseManager();

// Get expiring licenses (next 30 days)
$expiring_licenses = $license_manager->getExpiringLicenses(30);
$critical_expiring = $license_manager->getExpiringLicenses(7); // Next 7 days

// Get recently expired licenses (last 30 days)
$recently_expired_sql = "SELECT * FROM " . LMS_TABLE_LICENSES . " 
                        WHERE expires_at IS NOT NULL 
                        AND expires_at < NOW() 
                        AND expires_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        ORDER BY expires_at DESC 
                        LIMIT 10";
$stmt = $license_manager->db->query($recently_expired_sql);
$recently_expired = $stmt->fetchAll();

// Get activation statistics
$activation_stats = [
    'total_activations' => $license_manager->db->query("SELECT COUNT(*) FROM " . LMS_TABLE_ACTIVATIONS . " WHERE status = 'active'")->fetchColumn(),
    'activations_today' => $license_manager->db->query("SELECT COUNT(*) FROM " . LMS_TABLE_ACTIVATIONS . " WHERE status = 'active' AND DATE(created_at) = CURDATE()")->fetchColumn(),
    'activations_this_week' => $license_manager->db->query("SELECT COUNT(*) FROM " . LMS_TABLE_ACTIVATIONS . " WHERE status = 'active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
    'activations_this_month' => $license_manager->db->query("SELECT COUNT(*) FROM " . LMS_TABLE_ACTIVATIONS . " WHERE status = 'active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn()
];
?>

<!-- License Activation/Expiration Dashboard Widget -->
<div class="dashboard-widget license-expiration-widget">
    <div class="widget-header">
        <h3>
            <i class="fas fa-exclamation-triangle"></i>
            License Expiration Monitor
        </h3>
        <div class="widget-actions">
            <a href="license-manager.php?expiring=7" class="btn btn-sm btn-warning">
                <i class="fas fa-eye"></i>
                View All
            </a>
        </div>
    </div>
    
    <div class="widget-content">
        <!-- Critical Expiring Licenses Alert -->
        <?php if (!empty($critical_expiring)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong><?php echo count($critical_expiring); ?> license(s) expiring within 7 days!</strong>
                <p>Immediate attention required for these licenses.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Expiration Summary Cards -->
        <div class="expiration-summary">
            <div class="summary-card critical">
                <div class="summary-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-number"><?php echo count($critical_expiring); ?></div>
                    <div class="summary-label">Expiring in 7 days</div>
                </div>
            </div>
            
            <div class="summary-card warning">
                <div class="summary-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-number"><?php echo count($expiring_licenses); ?></div>
                    <div class="summary-label">Expiring in 30 days</div>
                </div>
            </div>
            
            <div class="summary-card expired">
                <div class="summary-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-number"><?php echo count($recently_expired); ?></div>
                    <div class="summary-label">Expired recently</div>
                </div>
            </div>
        </div>
        
        <!-- Expiring Licenses List -->
        <?php if (!empty($expiring_licenses)): ?>
        <div class="expiring-licenses-list">
            <h4>
                <i class="fas fa-list"></i>
                Upcoming Expirations
            </h4>
            
            <div class="license-list">
                <?php foreach (array_slice($expiring_licenses, 0, 5) as $license): ?>
                <?php 
                    $expires_at = new DateTime($license['expires_at']);
                    $now = new DateTime();
                    $days_left = $now->diff($expires_at)->days;
                    $urgency_class = $days_left <= 7 ? 'critical' : ($days_left <= 14 ? 'warning' : 'normal');
                ?>
                <div class="license-item <?php echo $urgency_class; ?>">
                    <div class="license-info">
                        <div class="license-customer">
                            <strong><?php echo htmlspecialchars($license['customer_name']); ?></strong>
                            <span class="license-product"><?php echo htmlspecialchars($license['product_name']); ?></span>
                        </div>
                        <div class="license-key">
                            <code><?php echo substr($license['license_key'], 0, 16) . '...'; ?></code>
                        </div>
                    </div>
                    <div class="license-expiry">
                        <div class="days-remaining">
                            <span class="days-number"><?php echo $days_left; ?></span>
                            <span class="days-text">days</span>
                        </div>
                        <div class="expiry-date">
                            <?php echo $expires_at->format('M d, Y'); ?>
                        </div>
                    </div>
                    <div class="license-actions">
                        <button class="btn btn-xs btn-primary" onclick="extendLicense(<?php echo $license['id']; ?>)" title="Extend License">
                            <i class="fas fa-calendar-plus"></i>
                        </button>
                        <button class="btn btn-xs btn-info" onclick="viewLicenseDetails(<?php echo $license['id']; ?>)" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($expiring_licenses) > 5): ?>
            <div class="license-list-footer">
                <a href="license-manager.php?expiring=30" class="btn btn-sm btn-outline">
                    View all <?php echo count($expiring_licenses); ?> expiring licenses
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="no-expiring-licenses">
            <i class="fas fa-check-circle"></i>
            <h4>All Good!</h4>
            <p>No licenses expiring in the next 30 days.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Activation Statistics Widget -->
<div class="dashboard-widget activation-stats-widget">
    <div class="widget-header">
        <h3>
            <i class="fas fa-chart-line"></i>
            Activation Statistics
        </h3>
        <div class="widget-actions">
            <a href="activations.php" class="btn btn-sm btn-primary">
                <i class="fas fa-eye"></i>
                View Details
            </a>
        </div>
    </div>
    
    <div class="widget-content">
        <div class="activation-stats-grid">
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($activation_stats['total_activations']); ?></div>
                    <div class="stat-label">Total Active</div>
                </div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($activation_stats['activations_today']); ?></div>
                    <div class="stat-label">Today</div>
                </div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($activation_stats['activations_this_week']); ?></div>
                    <div class="stat-label">This Week</div>
                </div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($activation_stats['activations_this_month']); ?></div>
                    <div class="stat-label">This Month</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard Widget Styles */
.dashboard-widget {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    margin-bottom: 1.5rem;
}

.widget-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--border-color);
}

.widget-header h3 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.widget-actions {
    display: flex;
    gap: 0.5rem;
}

.widget-content {
    padding: 1.5rem 2rem;
}

/* Expiration Summary Cards */
.expiration-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.summary-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
}

.summary-card.critical {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border-color: var(--error-color);
}

.summary-card.warning {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border-color: var(--warning-color);
}

.summary-card.expired {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    border-color: #9ca3af;
}

.summary-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.summary-card.critical .summary-icon {
    background: var(--error-color);
    color: var(--white);
}

.summary-card.warning .summary-icon {
    background: var(--warning-color);
    color: var(--white);
}

.summary-card.expired .summary-icon {
    background: #6b7280;
    color: var(--white);
}

.summary-number {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
}

.summary-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 500;
}

/* Expiring Licenses List */
.expiring-licenses-list h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.license-list {
    space-y: 0.75rem;
}

.license-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
    background: var(--white);
    margin-bottom: 0.75rem;
    transition: all 0.3s ease;
}

.license-item:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-1px);
}

.license-item.critical {
    border-left: 4px solid var(--error-color);
    background: linear-gradient(135deg, #fef2f2 0%, var(--white) 100%);
}

.license-item.warning {
    border-left: 4px solid var(--warning-color);
    background: linear-gradient(135deg, #fffbeb 0%, var(--white) 100%);
}

.license-info {
    flex: 1;
}

.license-customer strong {
    color: var(--text-primary);
    font-weight: 600;
    font-size: 0.875rem;
}

.license-product {
    color: var(--text-secondary);
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

.license-key {
    margin-top: 0.25rem;
}

.license-key code {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.75rem;
    background: var(--background-light);
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    color: var(--text-secondary);
}

.license-expiry {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    margin: 0 1rem;
}

.days-remaining {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.days-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
}

.days-text {
    font-size: 0.75rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.expiry-date {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.license-actions {
    display: flex;
    gap: 0.25rem;
}

.btn-xs {
    padding: 0.375rem;
    font-size: 0.75rem;
    min-width: auto;
}

.license-list-footer {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
    margin-top: 1rem;
}

.btn-outline {
    background: transparent;
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
}

.btn-outline:hover {
    background: var(--background-light);
    color: var(--text-primary);
}

/* No Expiring Licenses */
.no-expiring-licenses {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}

.no-expiring-licenses i {
    font-size: 3rem;
    color: var(--success-color);
    margin-bottom: 1rem;
}

.no-expiring-licenses h4 {
    font-size: 1.25rem;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

/* Activation Statistics */
.activation-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--background-light);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
}

.stat-item .stat-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-gradient);
    color: var(--white);
    border-radius: 50%;
    font-size: 1rem;
    flex-shrink: 0;
}

.stat-item .stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
}

.stat-item .stat-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .widget-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .widget-content {
        padding: 1rem;
    }
    
    .expiration-summary {
        grid-template-columns: 1fr;
    }
    
    .license-item {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
        gap: 1rem;
    }
    
    .license-expiry {
        margin: 0;
    }
    
    .activation-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .activation-stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>