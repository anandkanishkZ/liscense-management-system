<?php
/**
 * Zwicky Technology License Management System
 * Logger Class
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

class LMSLogger {
    private $db;
    private $log_levels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
    
    public function __construct() {
        try {
            $this->db = getLMSDatabase();
        } catch (Exception $e) {
            // Fallback to file logging if database is not available
            $this->logToFile('ERROR', 'Database connection failed for logger: ' . $e->getMessage());
        }
    }
    
    public function log($level, $message, $context = []) {
        $level = strtoupper($level);
        
        // Check if level should be logged
        if (!isset($this->log_levels[$level])) {
            $level = 'INFO';
        }
        
        $current_level = $this->log_levels[LMS_LOG_LEVEL] ?? 1;
        if ($this->log_levels[$level] < $current_level) {
            return;
        }
        
        $log_data = [
            'level' => $level,
            'message' => $message,
            'context' => json_encode($context),
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'license_key' => $context['license_key'] ?? null,
            'admin_user_id' => $_SESSION['admin_user_id'] ?? null
        ];
        
        // Try database logging first
        if ($this->db) {
            try {
                $this->logToDatabase($log_data);
            } catch (Exception $e) {
                $this->logToFile('ERROR', 'Failed to log to database: ' . $e->getMessage());
                $this->logToFile($level, $message, $context);
            }
        } else {
            $this->logToFile($level, $message, $context);
        }
    }
    
    private function logToDatabase($log_data) {
        $sql = "INSERT INTO " . LMS_TABLE_LOGS . " 
                (level, message, context, ip_address, user_agent, license_key, admin_user_id) 
                VALUES (:level, :message, :context, :ip_address, :user_agent, :license_key, :admin_user_id)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($log_data);
    }
    
    private function logToFile($level, $message, $context = []) {
        $log_file = LMS_LOGS_DIR . '/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $this->getClientIP();
        $context_str = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        
        $log_entry = "[$timestamp] [$level] [$ip] $message$context_str" . PHP_EOL;
        
        // Ensure logs directory exists
        if (!is_dir(LMS_LOGS_DIR)) {
            mkdir(LMS_LOGS_DIR, 0755, true);
        }
        
        // Rotate log if too large
        if (file_exists($log_file) && filesize($log_file) > LMS_LOG_MAX_SIZE) {
            $this->rotateLogs($log_file);
        }
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    private function rotateLogs($log_file) {
        $base_name = pathinfo($log_file, PATHINFO_FILENAME);
        $extension = pathinfo($log_file, PATHINFO_EXTENSION);
        $dir = dirname($log_file);
        
        // Move existing rotated logs
        for ($i = LMS_LOG_MAX_FILES - 1; $i > 0; $i--) {
            $old_file = "$dir/$base_name.$i.$extension";
            $new_file = "$dir/$base_name." . ($i + 1) . ".$extension";
            
            if (file_exists($old_file)) {
                if ($i == LMS_LOG_MAX_FILES - 1) {
                    unlink($old_file); // Delete oldest log
                } else {
                    rename($old_file, $new_file);
                }
            }
        }
        
        // Move current log to .1
        rename($log_file, "$dir/$base_name.1.$extension");
    }
    
    private function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    public function debug($message, $context = []) {
        $this->log('DEBUG', $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function getLogs($limit = 100, $level = null, $license_key = null) {
        if (!$this->db) {
            throw new Exception("Database not available for log retrieval");
        }
        
        $where_conditions = [];
        $params = [];
        
        if ($level) {
            $where_conditions[] = "level = :level";
            $params['level'] = $level;
        }
        
        if ($license_key) {
            $where_conditions[] = "license_key = :license_key";
            $params['license_key'] = $license_key;
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        $sql = "SELECT * FROM " . LMS_TABLE_LOGS . " $where_clause ORDER BY created_at DESC LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function clearOldLogs($days = 30) {
        if (!$this->db) {
            return false;
        }
        
        $sql = "DELETE FROM " . LMS_TABLE_LOGS . " WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':days', (int)$days, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function getLogCount($level = null) {
        if (!$this->db) {
            return 0;
        }

        try {
            $sql = "SELECT COUNT(*) as total FROM " . LMS_TABLE_LOGS;
            $params = [];

            if ($level) {
                $sql .= " WHERE level = :level";
                $params['level'] = strtoupper($level);
            }

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            $this->logToFile('ERROR', 'Failed to count logs: ' . $e->getMessage());
            return 0;
        }
    }
}