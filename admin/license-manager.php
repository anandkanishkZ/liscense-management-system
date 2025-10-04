<?php
/**
 * Zwicky Technology License Management System
 * License Activation/Expiration Management
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

$license_manager = new LMSLicenseManager();
$logger = new LMSLogger();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create_license':
                $data = [
                    'product_name' => trim($_POST['product_name'] ?? ''),
                    'customer_name' => trim($_POST['customer_name'] ?? ''),
                    'customer_email' => trim($_POST['customer_email'] ?? ''),
                    'max_activations' => (int)($_POST['max_activations'] ?? 1),
                    'expires_at' => $_POST['expires_at'] ?? null,
                    'allowed_domains' => $_POST['allowed_domains'] ?? '',
                    'features' => $_POST['features'] ?? '',
                    'notes' => $_POST['notes'] ?? ''
                ];
                
                $license_id = $license_manager->createLicense($data);
                $logger->log('license_created', "License created with ID: $license_id", $auth->getCurrentUser()['id']);
                
                echo json_encode(['success' => true, 'license_id' => $license_id, 'message' => 'License created successfully']);
                exit;
                
            case 'update_license':
                $license_id = (int)$_POST['license_id'];
                $data = [
                    'product_name' => trim($_POST['product_name'] ?? ''),
                    'customer_name' => trim($_POST['customer_name'] ?? ''),
                    'customer_email' => trim($_POST['customer_email'] ?? ''),
                    'max_activations' => (int)($_POST['max_activations'] ?? 1),
                    'expires_at' => $_POST['expires_at'] ?? null,
                    'allowed_domains' => $_POST['allowed_domains'] ?? '',
                    'features' => $_POST['features'] ?? '',
                    'notes' => $_POST['notes'] ?? '',
                    'status' => $_POST['status'] ?? 'active'
                ];
                
                $license_manager->updateLicense($license_id, $data);
                $logger->log('license_updated', "License updated: $license_id", $auth->getCurrentUser()['id']);
                
                echo json_encode(['success' => true, 'message' => 'License updated successfully']);
                exit;
                
            case 'extend_license':
                $license_id = (int)$_POST['license_id'];
                $extend_days = (int)$_POST['extend_days'];
                
                $license_manager->extendLicense($license_id, $extend_days);
                $logger->log('license_extended', "License extended: $license_id by $extend_days days", $auth->getCurrentUser()['id']);
                
                echo json_encode(['success' => true, 'message' => "License extended by $extend_days days"]);
                exit;
                
            case 'revoke_license':
                $license_id = (int)$_POST['license_id'];
                
                $license_manager->revokeLicense($license_id);
                $logger->log('license_revoked', "License revoked: $license_id", $auth->getCurrentUser()['id']);
                
                echo json_encode(['success' => true, 'message' => 'License revoked successfully']);
                exit;
                
            case 'regenerate_key':
                $license_id = (int)$_POST['license_id'];
                
                $new_key = $license_manager->regenerateLicenseKey($license_id);
                $logger->log('license_key_regenerated', "License key regenerated: $license_id", $auth->getCurrentUser()['id']);
                
                echo json_encode(['success' => true, 'new_key' => $new_key, 'message' => 'License key regenerated']);
                exit;
                
            case 'get_license_details':
                $license_id = (int)$_POST['license_id'];
                $license = $license_manager->getLicenseById($license_id);
                
                if ($license) {
                    echo json_encode(['success' => true, 'license' => $license]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'License not found']);
                }
                exit;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Get filters
$status_filter = $_GET['status'] ?? 'all';
$search_filter = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;

// Get licenses with filters
$licenses = $license_manager->getLicensesWithFilters($status_filter, $search_filter, $page, $per_page);
$total_licenses = $license_manager->countLicensesWithFilters($status_filter, $search_filter);
$total_pages = ceil($total_licenses / $per_page);

// Get expiring licenses (next 30 days)
$expiring_licenses = $license_manager->getExpiringLicenses(30);

$page_title = 'License Manager';
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
                <!-- Header Section -->
                <div class="page-header">
                    <div class="page-header-content">
                        <h1 class="page-title">
                            <i class="fas fa-key"></i>
                            License Manager
                        </h1>
                        <p class="page-description">Manage license activation, expiration dates and monitor license status</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="openCreateLicenseModal()">
                            <i class="fas fa-plus"></i>
                            Create New License
                        </button>
                    </div>
                </div>

                <!-- Expiring Licenses Alert -->
                <?php if (!empty($expiring_licenses)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong><?php echo count($expiring_licenses); ?> license(s) expiring soon!</strong>
                        <p>Some licenses will expire within the next 30 days. Please review and take action.</p>
                        <button class="btn btn-sm btn-warning-outline" onclick="showExpiringLicenses()">
                            View Expiring Licenses
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-grid license-stats">
                    <div class="stat-card active">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $license_manager->countLicensesByStatus('active'); ?></div>
                            <div class="stat-label">Active Licenses</div>
                        </div>
                    </div>
                    
                    <div class="stat-card expired">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $license_manager->countLicensesByStatus('expired'); ?></div>
                            <div class="stat-label">Expired Licenses</div>
                        </div>
                    </div>
                    
                    <div class="stat-card suspended">
                        <div class="stat-icon">
                            <i class="fas fa-pause-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $license_manager->countLicensesByStatus('suspended'); ?></div>
                            <div class="stat-label">Suspended Licenses</div>
                        </div>
                    </div>
                    
                    <div class="stat-card expiring">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo count($expiring_licenses); ?></div>
                            <div class="stat-label">Expiring Soon</div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="filters-section">
                    <div class="filters-left">
                        <div class="filter-group">
                            <label for="status-filter">Status:</label>
                            <select id="status-filter" onchange="applyFilters()">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="revoked" <?php echo $status_filter === 'revoked' ? 'selected' : ''; ?>>Revoked</option>
                            </select>
                        </div>
                    </div>
                    <div class="filters-right">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="search-input" placeholder="Search licenses..." 
                                   value="<?php echo htmlspecialchars($search_filter); ?>" 
                                   onkeyup="handleSearchKeyup(event)">
                        </div>
                    </div>
                </div>

                <!-- Licenses Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list"></i>
                            License List
                        </h3>
                        <div class="card-actions">
                            <button class="btn btn-secondary btn-sm" onclick="exportLicenses()">
                                <i class="fas fa-download"></i>
                                Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($licenses)): ?>
                            <div class="empty-state">
                                <i class="fas fa-key"></i>
                                <h3>No licenses found</h3>
                                <p>Create your first license to get started.</p>
                                <button class="btn btn-primary" onclick="openCreateLicenseModal()">
                                    <i class="fas fa-plus"></i>
                                    Create License
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>License Key</th>
                                            <th>Product</th>
                                            <th>Customer</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Expires</th>
                                            <th>Days Left</th>
                                            <th>Activations</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($licenses as $license): ?>
                                        <?php 
                                            $expires_at = new DateTime($license['expires_at']);
                                            $now = new DateTime();
                                            $days_left = $now->diff($expires_at)->days;
                                            $is_expired = $expires_at < $now;
                                            
                                            if ($is_expired) {
                                                $days_left = -$days_left;
                                            }
                                        ?>
                                        <tr class="license-row" data-license-id="<?php echo $license['id']; ?>">
                                            <td>
                                                <div class="license-key-cell">
                                                    <code class="license-key"><?php echo htmlspecialchars($license['license_key']); ?></code>
                                                    <button class="btn-copy" onclick="copyLicenseKey('<?php echo $license['license_key']; ?>')">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="product-info">
                                                    <strong><?php echo htmlspecialchars($license['product_name']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="customer-info">
                                                    <div class="customer-name"><?php echo htmlspecialchars($license['customer_name']); ?></div>
                                                    <div class="customer-email"><?php echo htmlspecialchars($license['customer_email']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $license['status']; ?>">
                                                    <?php echo ucfirst($license['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    <?php echo date('M d, Y', strtotime($license['created_at'])); ?>
                                                    <small><?php echo date('H:i', strtotime($license['created_at'])); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    <?php echo date('M d, Y', strtotime($license['expires_at'])); ?>
                                                    <small><?php echo date('H:i', strtotime($license['expires_at'])); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="days-left <?php echo $is_expired ? 'expired' : ($days_left <= 7 ? 'warning' : ''); ?>">
                                                    <?php if ($is_expired): ?>
                                                        <i class="fas fa-times-circle"></i>
                                                        Expired <?php echo abs($days_left); ?> days ago
                                                    <?php else: ?>
                                                        <i class="fas fa-clock"></i>
                                                        <?php echo $days_left; ?> days
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="activations-info">
                                                    <span class="activations-count">
                                                        <?php echo $license['activation_count']; ?>/<?php echo $license['max_activations']; ?>
                                                    </span>
                                                    <div class="activation-bar">
                                                        <div class="activation-progress" 
                                                             style="width: <?php echo ($license['activation_count'] / $license['max_activations']) * 100; ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="editLicense(<?php echo $license['id']; ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-info" onclick="viewLicenseDetails(<?php echo $license['id']; ?>)" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-secondary dropdown-toggle" onclick="toggleDropdown(this)">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <a href="#" onclick="extendLicense(<?php echo $license['id']; ?>)">
                                                                <i class="fas fa-calendar-plus"></i> Extend License
                                                            </a>
                                                            <a href="#" onclick="regenerateKey(<?php echo $license['id']; ?>)">
                                                                <i class="fas fa-sync"></i> Regenerate Key
                                                            </a>
                                                            <?php if ($license['status'] === 'active'): ?>
                                                            <a href="#" onclick="suspendLicense(<?php echo $license['id']; ?>)">
                                                                <i class="fas fa-pause"></i> Suspend
                                                            </a>
                                                            <?php endif; ?>
                                                            <a href="#" onclick="revokeLicense(<?php echo $license['id']; ?>)" class="text-danger">
                                                                <i class="fas fa-ban"></i> Revoke
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <div class="pagination-wrapper">
                                <div class="pagination-info">
                                    Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_licenses); ?> 
                                    of <?php echo $total_licenses; ?> licenses
                                </div>
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_filter); ?>" class="page-link">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_filter); ?>" 
                                           class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_filter); ?>" class="page-link">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Redesigned Simple Create/Edit License Modal -->
    <div id="licenseModal" class="modal simple-modal">
        <div class="modal-content simple-modal-content">
            <!-- Modal Header -->
            <div class="simple-modal-header">
                <div class="header-left">
                    <div class="modal-icon-badge">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <h3 id="modalTitle">Create New License</h3>
                        <p class="modal-subtitle">Fill in the details below to generate a new license</p>
                    </div>
                </div>
                <button class="modal-close-btn" onclick="closeLicenseModal()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="licenseForm" onsubmit="saveLicense(event)" novalidate>
                <div class="simple-modal-body">
                    <input type="hidden" id="licenseId" name="license_id">
                    
                    <!-- Form validation messages -->
                    <div id="formMessages" class="form-messages" style="display: none;"></div>
                    
                    <!-- Customer Information Section -->
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fas fa-user-circle"></i>
                            <span>Customer Information</span>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="customerName">Customer Name <span class="required">*</span></label>
                                <input type="text" id="customerName" name="customer_name" 
                                       placeholder="Enter customer full name"
                                       required>
                                <div class="field-error" id="customerName-error"></div>
                            </div>
                            
                            <div class="form-field">
                                <label for="customerEmail">Email Address <span class="required">*</span></label>
                                <input type="email" id="customerEmail" name="customer_email" 
                                       placeholder="customer@example.com"
                                       required>
                                <div class="field-error" id="customerEmail-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- License Configuration Section -->
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fas fa-cog"></i>
                            <span>License Configuration</span>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="productName">Product Name <span class="required">*</span></label>
                                <input type="text" id="productName" name="product_name" 
                                       placeholder="e.g., Premium WordPress Theme"
                                       required>
                                <div class="field-error" id="productName-error"></div>
                            </div>
                            
                            <div class="form-field">
                                <label for="maxActivations">Max Activations <span class="required">*</span></label>
                                <div class="number-input-group">
                                    <button type="button" class="num-btn" onclick="adjustNumber('maxActivations', -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" id="maxActivations" name="max_activations" 
                                           value="1" min="1" max="999" required>
                                    <button type="button" class="num-btn" onclick="adjustNumber('maxActivations', 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div class="field-error" id="maxActivations-error"></div>
                            </div>
                            
                            <div class="form-field full-width">
                                <label for="expiresAt">Expiration Date <span class="required">*</span></label>
                                <input type="datetime-local" id="expiresAt" name="expires_at" required>
                                <div class="quick-dates">
                                    <button type="button" class="quick-btn" onclick="setQuickDate(30)">30 days</button>
                                    <button type="button" class="quick-btn" onclick="setQuickDate(365)">1 year</button>
                                    <button type="button" class="quick-btn" onclick="setQuickDate(1095)">3 years</button>
                                    <button type="button" class="quick-btn" onclick="setQuickDate(0)">Lifetime</button>
                                </div>
                                <div class="field-error" id="expiresAt-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advanced Options Section (Collapsible) -->
                    <div class="form-section collapsible">
                        <div class="section-header clickable" onclick="toggleAdvancedOptions()">
                            <i class="fas fa-sliders-h"></i>
                            <span>Advanced Options</span>
                            <i class="fas fa-chevron-down toggle-icon" id="advancedToggle"></i>
                        </div>
                        
                        <div class="advanced-content" id="advancedOptions" style="display: none;">
                            <div class="form-grid">
                                <div class="form-field full-width">
                                    <label for="allowedDomains">Allowed Domains <span class="optional">(Optional)</span></label>
                                    <input type="text" id="allowedDomains" name="allowed_domains" 
                                           placeholder="example.com, *.example.com">
                                    <small class="field-hint">Comma-separated list. Use * for wildcards</small>
                                </div>
                                
                                <div class="form-field full-width">
                                    <label for="features">Enabled Features <span class="optional">(Optional)</span></label>
                                    <input type="text" id="features" name="features" 
                                           placeholder="premium-support, advanced-widgets">
                                    <small class="field-hint">Comma-separated list of features</small>
                                </div>
                                
                                <div class="form-field full-width" id="statusField" style="display: none;">
                                    <label for="status">Status</label>
                                    <select id="status" name="status">
                                        <option value="active">Active</option>
                                        <option value="suspended">Suspended</option>
                                        <option value="revoked">Revoked</option>
                                    </select>
                                </div>
                                
                                <div class="form-field full-width">
                                    <label for="notes">Internal Notes <span class="optional">(Optional)</span></label>
                                    <textarea id="notes" name="notes" rows="3" 
                                             placeholder="Add any internal notes..."></textarea>
                                    <small class="field-hint">For internal use only</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="simple-modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeLicenseModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-create" id="submitBtn">
                        <i class="fas fa-check"></i>
                        <span class="btn-text">Create License</span>
                        <div class="btn-loader" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Extend License Modal -->
    <div id="extendModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Extend License</h3>
                <button class="modal-close" onclick="closeExtendModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="extendForm" onsubmit="saveExtendLicense(event)">
                <div class="modal-body">
                    <input type="hidden" id="extendLicenseId" name="license_id">
                    
                    <div class="form-group">
                        <label for="extendDays">Extend by (days) *</label>
                        <select id="extendDays" name="extend_days" required>
                            <option value="30">30 days</option>
                            <option value="90">90 days (3 months)</option>
                            <option value="180">180 days (6 months)</option>
                            <option value="365">365 days (1 year)</option>
                            <option value="custom">Custom days</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="customDaysGroup" style="display: none;">
                        <label for="customDays">Custom Days *</label>
                        <input type="number" id="customDays" name="custom_days" min="1" max="3650">
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <span>The license expiration date will be extended from the current expiration date.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeExtendModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i>
                        Extend License
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- License Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>License Details</h3>
                <button class="modal-close" onclick="closeDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="licenseDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <script src="../assets/js/license-manager.js"></script>
</body>
</html>