<?php
/**
 * Notifications API Endpoint
 */

header('Content-Type: application/json');

require_once '../../config/config.php';

try {
    $auth = new LMSAdminAuth();
    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);
        exit;
    }

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $limit = max(1, min($limit, 50));

    $level = isset($_GET['level']) ? strtoupper(trim($_GET['level'])) : null;
    if ($level && !in_array($level, ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'SUCCESS'])) {
        $level = null;
    }

    $logger = new LMSLogger();
    $logs = $logger->getLogs($limit, $level ?? null);
    $total = $logger->getLogCount($level ?? null);

    $data = array_map(function($log) {
        $context = [];
        if (!empty($log['context'])) {
            $decoded = json_decode($log['context'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $context = $decoded;
            }
        }

        return [
            'id' => $log['id'] ?? null,
            'level' => $log['level'] ?? 'INFO',
            'message' => $log['message'] ?? '',
            'created_at' => $log['created_at'] ?? null,
            'context' => $context,
            'license_key' => $log['license_key'] ?? null
        ];
    }, $logs ?? []);

    echo json_encode([
        'success' => true,
        'logs' => $data,
        'limit' => $limit,
        'total' => $total,
        'has_more' => $total > count($data)
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
