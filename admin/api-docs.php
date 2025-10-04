<?php
/**
 * Zwicky Technology License Management System
 * API Documentation
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

// Get statistics
$stats = $license_manager->getStatistics();

$page_title = 'API Documentation';
$api_base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) . "/api";
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
    <style>
    .api-endpoint {
        background: #f8f9fa;
        border-left: 4px solid #667eea;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    .api-method {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 12px;
        margin-right: 10px;
    }
    .method-get { background: #28a745; color: white; }
    .method-post { background: #007bff; color: white; }
    .code-block {
        background: #2d2d2d;
        color: #f8f8f2;
        padding: 15px;
        border-radius: 4px;
        overflow-x: auto;
        font-family: 'Courier New', monospace;
        font-size: 14px;
    }
    </style>
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
                            <i class="fas fa-code"></i>
                            API Documentation
                        </h1>
                        <p class="page-description">Complete API reference for license validation and management</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-code"></i>
                            API Documentation
                        </h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 30px;">
                            <h4>Base URL</h4>
                            <div class="code-block">
                                <?php echo $api_base_url; ?>
                            </div>
                        </div>

                        <!-- Validate License -->
                        <div class="api-endpoint">
                            <h4>
                                <span class="api-method method-post">POST</span>
                                /validate
                            </h4>
                            <p>Validate a license key</p>
                            <h5>Request Body:</h5>
                            <div class="code-block">
{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "domain": "example.com"
}
                            </div>
                            <h5>Response:</h5>
                            <div class="code-block">
{
    "success": true,
    "valid": true,
    "message": "License is valid",
    "data": {
        "license_key": "XXXX-XXXX-XXXX-XXXX",
        "product_name": "My Product",
        "status": "active",
        "expires_at": "2025-12-31 23:59:59"
    }
}
                            </div>
                        </div>

                        <!-- Activate License -->
                        <div class="api-endpoint">
                            <h4>
                                <span class="api-method method-post">POST</span>
                                /activate
                            </h4>
                            <p>Activate a license on a domain</p>
                            <h5>Request Body:</h5>
                            <div class="code-block">
{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "domain": "example.com"
}
                            </div>
                            <h5>Response:</h5>
                            <div class="code-block">
{
    "success": true,
    "message": "License activated successfully",
    "data": {
        "activation_id": 123,
        "domain": "example.com",
        "activated_at": "2025-10-04 12:00:00"
    }
}
                            </div>
                        </div>

                        <!-- Deactivate License -->
                        <div class="api-endpoint">
                            <h4>
                                <span class="api-method method-post">POST</span>
                                /deactivate
                            </h4>
                            <p>Deactivate a license from a domain</p>
                            <h5>Request Body:</h5>
                            <div class="code-block">
{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "domain": "example.com"
}
                            </div>
                            <h5>Response:</h5>
                            <div class="code-block">
{
    "success": true,
    "message": "License deactivated successfully"
}
                            </div>
                        </div>

                        <!-- Error Responses -->
                        <div style="margin-top: 30px;">
                            <h4>Error Responses</h4>
                            <div class="code-block">
{
    "success": false,
    "message": "Error description",
    "error": "ERROR_CODE"
}
                            </div>
                            
                            <h5 style="margin-top: 20px;">Common Error Codes:</h5>
                            <ul>
                                <li><code>INVALID_LICENSE</code> - License key is invalid or not found</li>
                                <li><code>LICENSE_EXPIRED</code> - License has expired</li>
                                <li><code>LICENSE_SUSPENDED</code> - License is suspended</li>
                                <li><code>MAX_ACTIVATIONS</code> - Maximum activations reached</li>
                                <li><code>DOMAIN_NOT_AUTHORIZED</code> - Domain not authorized for this license</li>
                                <li><code>RATE_LIMIT_EXCEEDED</code> - Too many requests</li>
                            </ul>
                        </div>

                        <!-- Code Examples -->
                        <div style="margin-top: 30px;">
                            <h4>Code Examples</h4>
                            
                            <h5>PHP Example:</h5>
                            <div class="code-block">
&lt;?php
$api_url = '<?php echo $api_base_url; ?>/validate';
$data = [
    'license_key' => 'XXXX-XXXX-XXXX-XXXX',
    'domain' => 'example.com'
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$result = json_decode($response, true);

if ($result['success'] && $result['valid']) {
    echo "License is valid!";
} else {
    echo "License validation failed: " . $result['message'];
}
?&gt;
                            </div>

                            <h5 style="margin-top: 20px;">JavaScript Example:</h5>
                            <div class="code-block">
fetch('<?php echo $api_base_url; ?>/validate', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        license_key: 'XXXX-XXXX-XXXX-XXXX',
        domain: 'example.com'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success && data.valid) {
        console.log('License is valid!');
    } else {
        console.log('License validation failed:', data.message);
    }
})
.catch(error => console.error('Error:', error));
                            </div>

                            <h5 style="margin-top: 20px;">cURL Example:</h5>
                            <div class="code-block">
curl -X POST <?php echo $api_base_url; ?>/validate \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "domain": "example.com"
  }'
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
