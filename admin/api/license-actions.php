<?php
/**
 * Zwicky Technology License Management System
 * Admin License Actions API
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

header('Content-Type: application/json');

require_once '../../config/config.php';

// Check authentication
$auth = new LMSAdminAuth();
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

$current_user = $auth->getCurrentUser();
$license_manager = new LMSLicenseManager();
$logger = new LMSLogger();

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $license_key = $input['license_key'] ?? '';
    
    if (empty($license_key)) {
        throw new Exception('License key is required');
    }
    
    $response = ['success' => false];
    
    switch ($action) {
        case 'suspend':
            // Suspend license
            $reason = $input['reason'] ?? 'Suspended by admin';
            $license_manager->suspendLicense($license_key, $reason);
            
            $logger->warning("License suspended by admin", [
                'license_key' => $license_key,
                'admin_user' => $current_user['username'],
                'admin_id' => $current_user['id'],
                'reason' => $reason
            ]);
            
            $response = [
                'success' => true,
                'message' => 'License suspended successfully',
                'action' => 'suspended'
            ];
            break;
            
        case 'unsuspend':
        case 'reactivate':
            // Unsuspend/Reactivate license
            $license_manager->unsuspendLicense($license_key);
            
            $logger->info("License unsuspended by admin", [
                'license_key' => $license_key,
                'admin_user' => $current_user['username'],
                'admin_id' => $current_user['id']
            ]);
            
            $response = [
                'success' => true,
                'message' => 'License reactivated successfully',
                'action' => 'reactivated'
            ];
            break;
            
        case 'delete':
            // Check if user has admin permission
            if (!$auth->hasPermission('admin')) {
                throw new Exception('Insufficient permissions to delete licenses');
            }
            
            $license_manager->deleteLicense($license_key);
            
            $logger->warning("License deleted by admin", [
                'license_key' => $license_key,
                'admin_user' => $current_user['username'],
                'admin_id' => $current_user['id']
            ]);
            
            $response = [
                'success' => true,
                'message' => 'License deleted successfully',
                'action' => 'deleted'
            ];
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    $logger->error("License action failed", [
        'action' => $action ?? 'unknown',
        'license_key' => $license_key ?? 'unknown',
        'error' => $e->getMessage(),
        'admin_user' => $current_user['username'] ?? 'unknown'
    ]);
}
