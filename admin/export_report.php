<?php
/**
 * Report Export Handler
 * Generates CSV, PDF, and Excel exports
 */

require_once '../config/config.php';

$auth = new LMSAdminAuth();

// Check authentication
if (!$auth->isAuthenticated()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized');
}

$db = getLMSDatabase();
$format = $_GET['format'] ?? 'csv';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Fetch all data
$license_stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
        SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended
    FROM " . LMS_TABLE_LICENSES
)->fetch(PDO::FETCH_ASSOC);

$activation_stats = $db->query("
    SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT license_id) as unique_licenses,
        COUNT(DISTINCT domain) as unique_domains,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_activations
    FROM " . LMS_TABLE_ACTIVATIONS
)->fetch(PDO::FETCH_ASSOC);

// Get detailed license data
$licenses = $db->query("
    SELECT 
        l.license_key,
        l.product_name,
        l.customer_name,
        l.customer_email,
        l.status,
        l.max_activations,
        l.current_activations,
        l.expires_at,
        l.created_at,
        COUNT(a.id) as total_activations
    FROM " . LMS_TABLE_LICENSES . " l
    LEFT JOIN " . LMS_TABLE_ACTIVATIONS . " a ON l.id = a.license_id
    GROUP BY l.id
    ORDER BY l.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get activation data
$activations = $db->query("
    SELECT 
        l.license_key,
        l.product_name,
        a.domain,
        a.ip_address,
        a.status,
        a.created_at,
        a.last_check
    FROM " . LMS_TABLE_ACTIVATIONS . " a
    JOIN " . LMS_TABLE_LICENSES . " l ON a.license_id = l.id
    ORDER BY a.created_at DESC
    LIMIT 1000
")->fetchAll(PDO::FETCH_ASSOC);

// Product statistics
$products = $db->query("
    SELECT 
        product_name,
        COUNT(*) as license_count,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count
    FROM " . LMS_TABLE_LICENSES . "
    GROUP BY product_name
    ORDER BY license_count DESC
")->fetchAll(PDO::FETCH_ASSOC);

switch ($format) {
    case 'csv':
        exportCSV($license_stats, $activation_stats, $licenses, $activations, $products);
        break;
    case 'excel':
        exportExcel($license_stats, $activation_stats, $licenses, $activations, $products);
        break;
    case 'pdf':
        exportPDF($license_stats, $activation_stats, $licenses, $products);
        break;
    default:
        header('HTTP/1.1 400 Bad Request');
        exit('Invalid format');
}

/**
 * Export as CSV
 */
function exportCSV($license_stats, $activation_stats, $licenses, $activations, $products) {
    $filename = 'license_report_' . date('Y-m-d_His') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Summary Statistics
    fputcsv($output, ['LICENSE MANAGEMENT SYSTEM - REPORT']);
    fputcsv($output, ['Generated:', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    fputcsv($output, ['OVERVIEW STATISTICS']);
    fputcsv($output, ['Total Licenses', $license_stats['total']]);
    fputcsv($output, ['Active Licenses', $license_stats['active']]);
    fputcsv($output, ['Expired Licenses', $license_stats['expired']]);
    fputcsv($output, ['Suspended Licenses', $license_stats['suspended']]);
    fputcsv($output, ['Total Activations', $activation_stats['total']]);
    fputcsv($output, ['Active Activations', $activation_stats['active_activations']]);
    fputcsv($output, ['Unique Domains', $activation_stats['unique_domains']]);
    fputcsv($output, []);
    
    // Product Statistics
    fputcsv($output, ['PRODUCT STATISTICS']);
    fputcsv($output, ['Product Name', 'Total Licenses', 'Active Licenses']);
    foreach ($products as $product) {
        fputcsv($output, [
            $product['product_name'],
            $product['license_count'],
            $product['active_count']
        ]);
    }
    fputcsv($output, []);
    
    // License Details
    fputcsv($output, ['LICENSE DETAILS']);
    fputcsv($output, ['License Key', 'Product', 'Customer Name', 'Customer Email', 'Status', 'Max Activations', 'Current Activations', 'Expires At', 'Created At']);
    foreach ($licenses as $license) {
        fputcsv($output, [
            $license['license_key'],
            $license['product_name'],
            $license['customer_name'],
            $license['customer_email'],
            strtoupper($license['status']),
            $license['max_activations'],
            $license['current_activations'],
            $license['expires_at'] ?? 'Never',
            $license['created_at']
        ]);
    }
    fputcsv($output, []);
    
    // Activation Details
    fputcsv($output, ['ACTIVATION DETAILS']);
    fputcsv($output, ['License Key', 'Product', 'Domain', 'IP Address', 'Status', 'Created At', 'Last Check']);
    foreach ($activations as $activation) {
        fputcsv($output, [
            $activation['license_key'],
            $activation['product_name'],
            $activation['domain'],
            $activation['ip_address'],
            strtoupper($activation['status']),
            $activation['created_at'],
            $activation['last_check']
        ]);
    }
    
    fclose($output);
    exit;
}

/**
 * Export as Excel (HTML table that Excel can open)
 */
function exportExcel($license_stats, $activation_stats, $licenses, $activations, $products) {
    $filename = 'license_report_' . date('Y-m-d_His') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta charset="utf-8">
        <style>
            table { border-collapse: collapse; width: 100%; }
            th { background: #1d4dd4; color: white; padding: 8px; border: 1px solid #ccc; font-weight: bold; }
            td { padding: 6px; border: 1px solid #ccc; }
            .header { font-size: 18px; font-weight: bold; margin: 20px 0; }
            .stats { background: #f0f9ff; }
        </style>
    </head>
    <body>
        <h1>License Management System - Report</h1>
        <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <h2>Overview Statistics</h2>
        <table>
            <tr class="stats"><td><strong>Total Licenses</strong></td><td><?php echo $license_stats['total']; ?></td></tr>
            <tr class="stats"><td><strong>Active Licenses</strong></td><td><?php echo $license_stats['active']; ?></td></tr>
            <tr class="stats"><td><strong>Expired Licenses</strong></td><td><?php echo $license_stats['expired']; ?></td></tr>
            <tr class="stats"><td><strong>Suspended Licenses</strong></td><td><?php echo $license_stats['suspended']; ?></td></tr>
            <tr class="stats"><td><strong>Total Activations</strong></td><td><?php echo $activation_stats['total']; ?></td></tr>
            <tr class="stats"><td><strong>Active Activations</strong></td><td><?php echo $activation_stats['active_activations']; ?></td></tr>
            <tr class="stats"><td><strong>Unique Domains</strong></td><td><?php echo $activation_stats['unique_domains']; ?></td></tr>
        </table>
        
        <h2>Product Statistics</h2>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Total Licenses</th>
                    <th>Active Licenses</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                    <td><?php echo $product['license_count']; ?></td>
                    <td><?php echo $product['active_count']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>License Details</h2>
        <table>
            <thead>
                <tr>
                    <th>License Key</th>
                    <th>Product</th>
                    <th>Customer Name</th>
                    <th>Customer Email</th>
                    <th>Status</th>
                    <th>Max Activations</th>
                    <th>Current Activations</th>
                    <th>Expires At</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($licenses as $license): ?>
                <tr>
                    <td><?php echo htmlspecialchars($license['license_key']); ?></td>
                    <td><?php echo htmlspecialchars($license['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($license['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($license['customer_email']); ?></td>
                    <td><?php echo strtoupper($license['status']); ?></td>
                    <td><?php echo $license['max_activations']; ?></td>
                    <td><?php echo $license['current_activations']; ?></td>
                    <td><?php echo $license['expires_at'] ?? 'Never'; ?></td>
                    <td><?php echo $license['created_at']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Activation Details (Last 1000)</h2>
        <table>
            <thead>
                <tr>
                    <th>License Key</th>
                    <th>Product</th>
                    <th>Domain</th>
                    <th>IP Address</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Last Check</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activations as $activation): ?>
                <tr>
                    <td><?php echo htmlspecialchars($activation['license_key']); ?></td>
                    <td><?php echo htmlspecialchars($activation['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($activation['domain']); ?></td>
                    <td><?php echo htmlspecialchars($activation['ip_address']); ?></td>
                    <td><?php echo strtoupper($activation['status']); ?></td>
                    <td><?php echo $activation['created_at']; ?></td>
                    <td><?php echo $activation['last_check']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Export as PDF (HTML that can be printed as PDF)
 */
function exportPDF($license_stats, $activation_stats, $licenses, $products) {
    $filename = 'license_report_' . date('Y-m-d_His') . '.pdf';
    
    // For now, generate HTML optimized for PDF printing
    // In future, can integrate libraries like TCPDF or FPDF
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>License Report</title>
        <style>
            @media print {
                @page { margin: 1cm; }
                body { margin: 0; }
            }
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
                color: #333;
            }
            .header {
                background: #1d4dd4;
                color: white;
                padding: 20px;
                margin-bottom: 20px;
            }
            .header h1 {
                margin: 0 0 8px 0;
                font-size: 24px;
            }
            .section {
                margin-bottom: 30px;
                page-break-inside: avoid;
            }
            .section h2 {
                color: #1d4dd4;
                border-bottom: 2px solid #1d4dd4;
                padding-bottom: 5px;
                margin-bottom: 15px;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
                margin-bottom: 20px;
            }
            .stat-card {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                border-left: 4px solid #1d4dd4;
            }
            .stat-label {
                color: #6b7280;
                font-size: 11px;
                margin-bottom: 5px;
            }
            .stat-value {
                font-size: 28px;
                font-weight: bold;
                color: #1d4dd4;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            th {
                background: #1d4dd4;
                color: white;
                padding: 10px;
                text-align: left;
                font-size: 11px;
            }
            td {
                padding: 8px 10px;
                border-bottom: 1px solid #e5e7eb;
                font-size: 11px;
            }
            tr:nth-child(even) {
                background: #f9fafb;
            }
            .footer {
                margin-top: 30px;
                padding-top: 15px;
                border-top: 1px solid #ccc;
                text-align: center;
                color: #6b7280;
                font-size: 10px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>License Management System Report</h1>
            <p style="margin: 0;">Generated: <?php echo date('F d, Y - H:i:s'); ?></p>
        </div>
        
        <div class="section">
            <h2>Overview Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Licenses</div>
                    <div class="stat-value"><?php echo number_format($license_stats['total']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Active Licenses</div>
                    <div class="stat-value"><?php echo number_format($license_stats['active']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Expired Licenses</div>
                    <div class="stat-value"><?php echo number_format($license_stats['expired']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Suspended Licenses</div>
                    <div class="stat-value"><?php echo number_format($license_stats['suspended']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Activations</div>
                    <div class="stat-value"><?php echo number_format($activation_stats['total']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Unique Domains</div>
                    <div class="stat-value"><?php echo number_format($activation_stats['unique_domains']); ?></div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Product Performance</h2>
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Total Licenses</th>
                        <th>Active Licenses</th>
                        <th>Success Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                        <td><?php echo number_format($product['license_count']); ?></td>
                        <td><?php echo number_format($product['active_count']); ?></td>
                        <td><?php echo $product['license_count'] > 0 ? number_format(($product['active_count'] / $product['license_count']) * 100, 1) : 0; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>License Details (First 50)</h2>
            <table>
                <thead>
                    <tr>
                        <th>License Key</th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Activations</th>
                        <th>Expires</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($licenses, 0, 50) as $license): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($license['license_key']); ?></td>
                        <td><?php echo htmlspecialchars($license['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($license['customer_name']); ?></td>
                        <td><strong><?php echo strtoupper($license['status']); ?></strong></td>
                        <td><?php echo $license['current_activations']; ?>/<?php echo $license['max_activations']; ?></td>
                        <td><?php echo $license['expires_at'] ? date('Y-m-d', strtotime($license['expires_at'])) : 'Never'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($licenses) > 50): ?>
            <p style="margin-top: 10px; color: #6b7280; font-style: italic;">
                Showing first 50 of <?php echo count($licenses); ?> licenses. Export to CSV or Excel for complete data.
            </p>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>Zwicky Technology License Management System | Confidential Report</p>
            <p>This report contains sensitive business information. Handle with care.</p>
        </div>
        
        <script>
            // Auto-print on load
            window.onload = function() {
                window.print();
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}
