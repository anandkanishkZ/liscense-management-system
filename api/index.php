<?php
/**
 * Zwicky Technology License Management System
 * API Router
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/config.php';

class LMSAPIRouter {
    private $license_manager;
    private $logger;
    private $rate_limiter;
    
    public function __construct() {
        $this->license_manager = new LMSLicenseManager();
        $this->logger = new LMSLogger();
        $this->rate_limiter = new LMSRateLimiter();
    }
    
    public function route() {
        try {
            // Rate limiting
            if (!$this->rate_limiter->checkLimit()) {
                $this->sendResponse(429, [
                    'success' => false,
                    'message' => 'Rate limit exceeded'
                ]);
                return;
            }
            
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $path = str_replace('/api', '', $path);
            $method = $_SERVER['REQUEST_METHOD'];
            
            // Route requests
            switch ($path) {
                case '/validate':
                    $this->handleValidate();
                    break;
                    
                case '/activate':
                    $this->handleActivate();
                    break;
                    
                case '/deactivate':
                    $this->handleDeactivate();
                    break;
                    
                case '/status':
                    $this->handleStatus();
                    break;
                    
                case '/heartbeat':
                    $this->handleHeartbeat();
                    break;
                    
                default:
                    $this->sendResponse(404, [
                        'success' => false,
                        'message' => 'Endpoint not found'
                    ]);
            }
        } catch (Exception $e) {
            $this->logger->error("API Error: " . $e->getMessage(), [
                'path' => $path ?? '',
                'method' => $method ?? '',
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->sendResponse(500, [
                'success' => false,
                'message' => LMS_DEBUG_MODE ? $e->getMessage() : 'Internal server error'
            ]);
        }
    }
    
    private function handleValidate() {
        $input = $this->getInput();
        
        if (empty($input['license_key'])) {
            $this->sendResponse(400, [
                'success' => false,
                'message' => 'License key is required'
            ]);
            return;
        }
        
        $domain = $input['domain'] ?? $_SERVER['HTTP_HOST'] ?? null;
        $validation = $this->license_manager->validateLicense($input['license_key'], $domain);
        
        if ($validation['valid']) {
            $this->sendResponse(200, [
                'success' => true,
                'valid' => true,
                'license' => [
                    'product_name' => $validation['license']['product_name'],
                    'expires_at' => $validation['license']['expires_at'],
                    'status' => $validation['license']['status']
                ],
                'message' => $validation['message']
            ]);
        } else {
            $this->sendResponse(200, [
                'success' => true,
                'valid' => false,
                'message' => $validation['message']
            ]);
        }
    }
    
    private function handleActivate() {
        $input = $this->getInput();
        
        if (empty($input['license_key']) || empty($input['domain'])) {
            $this->sendResponse(400, [
                'success' => false,
                'message' => 'License key and domain are required'
            ]);
            return;
        }
        
        $result = $this->license_manager->activateLicense(
            $input['license_key'],
            $input['domain'],
            $input
        );
        
        $status_code = $result['success'] ? 200 : 400;
        $this->sendResponse($status_code, $result);
    }
    
    private function handleDeactivate() {
        $input = $this->getInput();
        
        if (empty($input['license_key']) || empty($input['domain'])) {
            $this->sendResponse(400, [
                'success' => false,
                'message' => 'License key and domain are required'
            ]);
            return;
        }
        
        $result = $this->license_manager->deactivateLicense(
            $input['license_key'],
            $input['domain']
        );
        
        $status_code = $result['success'] ? 200 : 400;
        $this->sendResponse($status_code, $result);
    }
    
    private function handleStatus() {
        $input = $this->getInput();
        
        if (empty($input['license_key'])) {
            $this->sendResponse(400, [
                'success' => false,
                'message' => 'License key is required'
            ]);
            return;
        }
        
        $license = $this->license_manager->getLicense($input['license_key']);
        
        if (!$license) {
            $this->sendResponse(404, [
                'success' => false,
                'message' => 'License not found'
            ]);
            return;
        }
        
        // Get activations
        $sql = "SELECT domain, status, last_check, created_at FROM " . LMS_TABLE_ACTIVATIONS . " 
                WHERE license_id = :license_id ORDER BY created_at DESC";
        
        $db = getLMSDatabase();
        $stmt = $db->prepare($sql);
        $stmt->execute(['license_id' => $license['id']]);
        $activations = $stmt->fetchAll();
        
        $this->sendResponse(200, [
            'success' => true,
            'license' => [
                'license_key' => $license['license_key'],
                'product_name' => $license['product_name'],
                'status' => $license['status'],
                'expires_at' => $license['expires_at'],
                'max_activations' => $license['max_activations'],
                'current_activations' => $license['current_activations']
            ],
            'activations' => $activations
        ]);
    }
    
    private function handleHeartbeat() {
        $input = $this->getInput();
        
        if (empty($input['activation_token'])) {
            $this->sendResponse(400, [
                'success' => false,
                'message' => 'Activation token is required'
            ]);
            return;
        }
        
        // Update last check time
        $sql = "UPDATE " . LMS_TABLE_ACTIVATIONS . " 
                SET last_check = CURRENT_TIMESTAMP 
                WHERE activation_token = :token AND status = 'active'";
        
        $db = getLMSDatabase();
        $stmt = $db->prepare($sql);
        $result = $stmt->execute(['token' => $input['activation_token']]);
        
        if ($stmt->rowCount() > 0) {
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Heartbeat recorded'
            ]);
        } else {
            $this->sendResponse(404, [
                'success' => false,
                'message' => 'Invalid activation token'
            ]);
        }
    }
    
    private function getInput() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Fallback to POST data
            $input = $_POST;
        }
        
        // Merge with GET parameters
        return array_merge($_GET, $input ?: []);
    }
    
    private function sendResponse($status_code, $data) {
        http_response_code($status_code);
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
}

/**
 * Simple Rate Limiter Class
 */
class LMSRateLimiter {
    private $cache_file;
    
    public function __construct() {
        $this->cache_file = LMS_LOGS_DIR . '/rate_limit_' . date('Y-m-d-H') . '.json';
    }
    
    public function checkLimit() {
        $ip = $this->getClientIP();
        $current_time = time();
        
        // Load existing data
        $data = $this->loadData();
        
        // Clean old entries
        $data = $this->cleanOldEntries($data, $current_time);
        
        // Check hourly limit
        $hourly_count = $this->getCount($data, $ip, 'hourly', $current_time);
        if ($hourly_count >= LMS_API_RATE_LIMIT) {
            return false;
        }
        
        // Check burst limit
        $burst_count = $this->getCount($data, $ip, 'burst', $current_time);
        if ($burst_count >= LMS_API_BURST_LIMIT) {
            return false;
        }
        
        // Record request
        $this->recordRequest($data, $ip, $current_time);
        
        return true;
    }
    
    private function loadData() {
        if (!file_exists($this->cache_file)) {
            return [];
        }
        
        $content = file_get_contents($this->cache_file);
        return json_decode($content, true) ?: [];
    }
    
    private function saveData($data) {
        if (!is_dir(LMS_LOGS_DIR)) {
            mkdir(LMS_LOGS_DIR, 0755, true);
        }
        
        file_put_contents($this->cache_file, json_encode($data), LOCK_EX);
    }
    
    private function cleanOldEntries($data, $current_time) {
        $hour_ago = $current_time - 3600;
        $minute_ago = $current_time - 60;
        
        foreach ($data as $ip => $requests) {
            $data[$ip]['hourly'] = array_filter($requests['hourly'] ?? [], function($time) use ($hour_ago) {
                return $time > $hour_ago;
            });
            
            $data[$ip]['burst'] = array_filter($requests['burst'] ?? [], function($time) use ($minute_ago) {
                return $time > $minute_ago;
            });
            
            if (empty($data[$ip]['hourly']) && empty($data[$ip]['burst'])) {
                unset($data[$ip]);
            }
        }
        
        return $data;
    }
    
    private function getCount($data, $ip, $type, $current_time) {
        return count($data[$ip][$type] ?? []);
    }
    
    private function recordRequest($data, $ip, $current_time) {
        if (!isset($data[$ip])) {
            $data[$ip] = ['hourly' => [], 'burst' => []];
        }
        
        $data[$ip]['hourly'][] = $current_time;
        $data[$ip]['burst'][] = $current_time;
        
        $this->saveData($data);
    }
    
    private function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

// Initialize and route
$router = new LMSAPIRouter();
$router->route();