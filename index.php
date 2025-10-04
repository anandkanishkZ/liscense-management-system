<?php
/**
 * Zwicky Technology License Management System
 * Public Index Page - Smart Redirector
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 */

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer-when-downgrade");

// Check if installation is complete
$lockFile = __DIR__ . '/install.lock';

if (!file_exists($lockFile)) {
    // First time setup, redirect to installation wizard
    header("Location: wizard.php");
    exit;
}

// Installation complete - show welcome page with quick links
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Zwicky License Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .welcome-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            width: 100%;
            overflow: hidden;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .welcome-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            padding: 50px 40px;
            text-align: center;
            color: white;
        }
        
        .welcome-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .welcome-header .subtitle {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .welcome-header .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 15px;
        }
        
        .welcome-content {
            padding: 40px;
        }
        
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .link-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .link-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
            border-color: #2563eb;
        }
        
        .link-card.primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border-color: #1d4ed8;
            color: white;
        }
        
        .link-card.primary:hover {
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
        }
        
        .link-card .icon {
            width: 60px;
            height: 60px;
            background: rgba(37, 99, 235, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #2563eb;
            margin-bottom: 15px;
        }
        
        .link-card.primary .icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .link-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e293b;
        }
        
        .link-card.primary h3 {
            color: white;
        }
        
        .link-card p {
            font-size: 14px;
            color: #64748b;
            line-height: 1.5;
        }
        
        .link-card.primary p {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .info-section {
            background: #f8fafc;
            border-left: 4px solid #2563eb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .info-section h3 {
            color: #1e293b;
            font-size: 18px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-section ul {
            margin-left: 20px;
            color: #64748b;
            line-height: 1.8;
        }
        
        .info-section ul li {
            margin-bottom: 8px;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="welcome-header">
            <h1><i class="fas fa-shield-halved"></i> Zwicky License Manager</h1>
            <p class="subtitle">Professional License Management System</p>
            <div class="badge">
                <i class="fas fa-check-circle"></i> System Ready
            </div>
        </div>
        
        <div class="welcome-content">
            <div class="quick-links">
                <a href="admin/login.php" class="link-card primary">
                    <div class="icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <h3>Admin Login</h3>
                    <p>Access the admin dashboard to manage licenses and settings</p>
                </a>
                
                <a href="admin/dashboard.php" class="link-card">
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Dashboard</h3>
                    <p>View analytics, statistics, and system overview</p>
                </a>
                
                <a href="api/" class="link-card">
                    <div class="icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <h3>API Endpoint</h3>
                    <p>License validation and activation API</p>
                </a>
                
                <a href="admin/api-docs.php" class="link-card">
                    <div class="icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>API Documentation</h3>
                    <p>Complete API reference and examples</p>
                </a>
            </div>
            
            <div class="info-section">
                <h3>
                    <i class="fas fa-rocket"></i>
                    System Features
                </h3>
                <ul>
                    <li><strong>License Management:</strong> Create, activate, and manage software licenses</li>
                    <li><strong>Domain Restrictions:</strong> Control where licenses can be activated</li>
                    <li><strong>Admin Dashboard:</strong> Comprehensive control panel with analytics</li>
                    <li><strong>RESTful API:</strong> Secure license validation endpoints</li>
                    <li><strong>Activity Logging:</strong> Complete audit trail of all actions</li>
                    <li><strong>Role-Based Access:</strong> Multiple admin permission levels</li>
                </ul>
            </div>
            
            <div class="info-section" style="border-left-color: #10b981;">
                <h3>
                    <i class="fas fa-info-circle"></i>
                    Quick Links
                </h3>
                <ul>
                    <li><a href="README.md" style="color: #2563eb; text-decoration: none;">System Documentation</a></li>
                    <li><a href="SERVER_INSTALLATION_GUIDE.md" style="color: #2563eb; text-decoration: none;">Installation Guide</a></li>
                    <li><a href="INSTALLATION_WIZARD_README.md" style="color: #2563eb; text-decoration: none;">Wizard Documentation</a></li>
                    <li><a href="admin/settings.php" style="color: #2563eb; text-decoration: none;">System Settings</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p>
                <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> Zwicky Technology - Professional License Management System
            </p>
            <p style="margin-top: 5px; font-size: 12px;">
                Installed on: <strong><?php echo date('F j, Y', filemtime($lockFile)); ?></strong>
            </p>
        </div>
    </div>
</body>
</html>