<?php
/**
 * Activation Actions API
 * Handles activation management operations (deactivate)
 */

require_once '../../config/database.php';
require_once '../../classes/LMSLicenseManager.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is authenticated
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $input['action'];

try {
    $licenseManager = new LMSLicenseManager();
    
    switch ($action) {
        case 'deactivate':
            // Validate required fields
            if (!isset($input['license_key']) || !isset($input['domain'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required fields: license_key and domain'
                ]);
                exit;
            }
            
            $license_key = $input['license_key'];
            $domain = $input['domain'];
            
            // Deactivate the license for this domain
            $result = $licenseManager->deactivateLicense($license_key, $domain);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Domain deactivated successfully',
                    'data' => [
                        'license_key' => $license_key,
                        'domain' => $domain
                    ]
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to deactivate domain'
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action: ' . $action
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
