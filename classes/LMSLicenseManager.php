<?php
/**
 * Zwicky Technology License Management System
 * License Manager Class
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

class LMSLicenseManager {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = getLMSDatabase();
        $this->logger = new LMSLogger();
    }
    
    /**
     * Create a new license
     */
    public function createLicense($data) {
        $required_fields = ['product_name', 'customer_name', 'customer_email'];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Generate license key
        $license_key = lms_generate_license_key();
        
        // Set defaults
        $data['license_key'] = $license_key;
        $data['max_activations'] = $data['max_activations'] ?? LMS_MAX_ACTIVATIONS_PER_LICENSE;
        $data['status'] = $data['status'] ?? 'active';
        $data['expires_at'] = $data['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+' . LMS_DEFAULT_LICENSE_DURATION . ' days'));
        
        // Validate email
        if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address");
        }
        
        // Prepare domains
        if (!empty($data['allowed_domains'])) {
            if (is_array($data['allowed_domains'])) {
                $data['allowed_domains'] = implode(',', array_map('lms_sanitize_domain', $data['allowed_domains']));
            } else {
                $domains = explode(',', $data['allowed_domains']);
                $data['allowed_domains'] = implode(',', array_map('lms_sanitize_domain', $domains));
            }
        }
        
        $sql = "INSERT INTO " . LMS_TABLE_LICENSES . " 
                (license_key, product_name, customer_name, customer_email, allowed_domains, 
                 max_activations, status, expires_at, notes) 
                VALUES (:license_key, :product_name, :customer_name, :customer_email, 
                        :allowed_domains, :max_activations, :status, :expires_at, :notes)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'license_key' => $data['license_key'],
            'product_name' => $data['product_name'],
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'allowed_domains' => $data['allowed_domains'] ?? null,
            'max_activations' => $data['max_activations'],
            'status' => $data['status'],
            'expires_at' => $data['expires_at'],
            'notes' => $data['notes'] ?? null
        ]);
        
        if ($result) {
            $license_id = $this->db->lastInsertId();
            $this->logger->info("License created successfully", [
                'license_key' => $license_key,
                'customer_email' => $data['customer_email'],
                'product' => $data['product_name']
            ]);
            
            return [
                'success' => true,
                'license_id' => $license_id,
                'license_key' => $license_key,
                'message' => 'License created successfully'
            ];
        }
        
        throw new Exception("Failed to create license");
    }
    
    /**
     * Validate a license
     */
    public function validateLicense($license_key, $domain = null) {
        if (empty($license_key)) {
            return ['valid' => false, 'message' => 'License key is required'];
        }
        
        $license = $this->getLicense($license_key);
        
        if (!$license) {
            $this->logger->warning("License validation failed - key not found", [
                'license_key' => $license_key,
                'domain' => $domain
            ]);
            return ['valid' => false, 'message' => 'Invalid license key'];
        }
        
        // Check if license is active
        if ($license['status'] !== 'active') {
            $this->logger->warning("License validation failed - license not active", [
                'license_key' => $license_key,
                'status' => $license['status']
            ]);
            return ['valid' => false, 'message' => 'License is not active'];
        }
        
        // Check expiration
        if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
            $this->logger->warning("License validation failed - license expired", [
                'license_key' => $license_key,
                'expires_at' => $license['expires_at']
            ]);
            return ['valid' => false, 'message' => 'License has expired'];
        }
        
        // Check domain restrictions
        if ($domain && !empty($license['allowed_domains'])) {
            $allowed_domains = array_map('trim', explode(',', $license['allowed_domains']));
            $domain = lms_sanitize_domain($domain);
            
            $domain_allowed = false;
            foreach ($allowed_domains as $allowed_domain) {
                if ($this->matchesDomain($domain, $allowed_domain)) {
                    $domain_allowed = true;
                    break;
                }
            }
            
            if (!$domain_allowed) {
                $this->logger->warning("License validation failed - domain not allowed", [
                    'license_key' => $license_key,
                    'domain' => $domain,
                    'allowed_domains' => $allowed_domains
                ]);
                return [
                    'valid' => false, 
                    'message' => 'Domain not authorized for this license. Allowed domains: ' . implode(', ', $allowed_domains)
                ];
            }
        }
        
        // IMPORTANT: If allowed_domains is set, domain parameter is REQUIRED
        if (!empty($license['allowed_domains']) && empty($domain)) {
            $this->logger->warning("License validation failed - domain required but not provided", [
                'license_key' => $license_key
            ]);
            return [
                'valid' => false,
                'message' => 'Domain is required for this license. This license has domain restrictions.'
            ];
        }
        
        $this->logger->info("License validated successfully", [
            'license_key' => $license_key,
            'domain' => $domain
        ]);
        
        return [
            'valid' => true,
            'license' => $license,
            'message' => 'License is valid'
        ];
    }
    
    /**
     * Activate a license for a domain
     */
    public function activateLicense($license_key, $domain, $additional_data = []) {
        $domain = lms_sanitize_domain($domain);
        
        // Validate license first
        $validation = $this->validateLicense($license_key, $domain);
        if (!$validation['valid']) {
            return $validation;
        }
        
        $license = $validation['license'];
        
        // Check if already activated for this domain
        $existing_activation = $this->getActivation($license['id'], $domain);
        if ($existing_activation) {
            if ($existing_activation['status'] === 'active') {
                return [
                    'success' => true,
                    'activation_token' => $existing_activation['activation_token'],
                    'message' => 'License already activated for this domain'
                ];
            } else {
                // Reactivate
                return $this->reactivateLicense($license_key, $domain);
            }
        }
        
        // Check activation limits
        if ($license['current_activations'] >= $license['max_activations']) {
            $this->logger->warning("License activation failed - max activations reached", [
                'license_key' => $license_key,
                'current_activations' => $license['current_activations'],
                'max_activations' => $license['max_activations']
            ]);
            return ['success' => false, 'message' => 'Maximum activations reached'];
        }
        
        // Create activation
        $activation_token = bin2hex(random_bytes(32));
        
        $sql = "INSERT INTO " . LMS_TABLE_ACTIVATIONS . " 
                (license_id, domain, ip_address, user_agent, activation_token, status) 
                VALUES (:license_id, :domain, :ip_address, :user_agent, :activation_token, 'active')";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'license_id' => $license['id'],
            'domain' => $domain,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'activation_token' => $activation_token
        ]);
        
        if ($result) {
            // Update license activation count
            $this->updateActivationCount($license['id']);
            
            $this->logger->info("License activated successfully", [
                'license_key' => $license_key,
                'domain' => $domain,
                'activation_token' => $activation_token
            ]);
            
            return [
                'success' => true,
                'activation_token' => $activation_token,
                'license' => $license,
                'message' => 'License activated successfully'
            ];
        }
        
        throw new Exception("Failed to activate license");
    }
    
    /**
     * Deactivate a license for a domain
     */
    public function deactivateLicense($license_key, $domain) {
        $domain = lms_sanitize_domain($domain);
        
        $license = $this->getLicense($license_key);
        if (!$license) {
            return ['success' => false, 'message' => 'Invalid license key'];
        }
        
        $sql = "UPDATE " . LMS_TABLE_ACTIVATIONS . " 
                SET status = 'inactive', updated_at = CURRENT_TIMESTAMP 
                WHERE license_id = :license_id AND domain = :domain";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'license_id' => $license['id'],
            'domain' => $domain
        ]);
        
        if ($result && $stmt->rowCount() > 0) {
            $this->updateActivationCount($license['id']);
            
            $this->logger->info("License deactivated successfully", [
                'license_key' => $license_key,
                'domain' => $domain
            ]);
            
            return ['success' => true, 'message' => 'License deactivated successfully'];
        }
        
        return ['success' => false, 'message' => 'No active license found for this domain'];
    }
    
    /**
     * Get license information
     */
    public function getLicense($license_key) {
        $sql = "SELECT * FROM " . LMS_TABLE_LICENSES . " WHERE license_key = :license_key";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['license_key' => $license_key]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get all licenses with pagination
     */
    public function getAllLicenses($page = 1, $per_page = 20, $filters = []) {
        $offset = ($page - 1) * $per_page;
        $where_conditions = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where_conditions[] = "status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['customer_email'])) {
            $where_conditions[] = "customer_email LIKE :customer_email";
            $params['customer_email'] = '%' . $filters['customer_email'] . '%';
        }
        
        if (!empty($filters['product_name'])) {
            $where_conditions[] = "product_name LIKE :product_name";
            $params['product_name'] = '%' . $filters['product_name'] . '%';
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM " . LMS_TABLE_LICENSES . " $where_clause";
        $count_stmt = $this->db->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetchColumn();
        
        // Get licenses
        $sql = "SELECT * FROM " . LMS_TABLE_LICENSES . " $where_clause 
                ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        $licenses = $stmt->fetchAll();
        
        return [
            'licenses' => $licenses,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'pages' => ceil($total / $per_page)
        ];
    }
    
    /**
     * Update license
     */
    public function updateLicense($license_key, $data) {
        $license = $this->getLicense($license_key);
        if (!$license) {
            throw new Exception("License not found");
        }
        
        $allowed_fields = ['product_name', 'customer_name', 'customer_email', 'allowed_domains', 
                          'max_activations', 'status', 'expires_at', 'notes'];
        
        $update_fields = [];
        $params = ['license_key' => $license_key];
        
        foreach ($allowed_fields as $field) {
            if (array_key_exists($field, $data)) {
                $update_fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($update_fields)) {
            throw new Exception("No valid fields to update");
        }
        
        $sql = "UPDATE " . LMS_TABLE_LICENSES . " SET " . implode(", ", $update_fields) . 
               ", updated_at = CURRENT_TIMESTAMP WHERE license_key = :license_key";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            $this->logger->info("License updated successfully", [
                'license_key' => $license_key,
                'updated_fields' => array_keys($data)
            ]);
        }
        
        return $result;
    }
    
    /**
     * Delete license
     */
    public function deleteLicense($license_key) {
        $license = $this->getLicense($license_key);
        if (!$license) {
            throw new Exception("License not found");
        }
        
        $sql = "DELETE FROM " . LMS_TABLE_LICENSES . " WHERE license_key = :license_key";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(['license_key' => $license_key]);
        
        if ($result) {
            $this->logger->info("License deleted successfully", [
                'license_key' => $license_key
            ]);
        }
        
        return $result;
    }
    
    /**
     * Get activation details
     */
    private function getActivation($license_id, $domain) {
        $sql = "SELECT * FROM " . LMS_TABLE_ACTIVATIONS . " 
                WHERE license_id = :license_id AND domain = :domain";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['license_id' => $license_id, 'domain' => $domain]);
        
        return $stmt->fetch();
    }
    
    /**
     * Update activation count for a license
     */
    private function updateActivationCount($license_id) {
        $sql = "UPDATE " . LMS_TABLE_LICENSES . " 
                SET current_activations = (
                    SELECT COUNT(*) FROM " . LMS_TABLE_ACTIVATIONS . " 
                    WHERE license_id = :license_id AND status = 'active'
                ) 
                WHERE id = :license_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['license_id' => $license_id]);
    }
    
    /**
     * Reactivate a license
     */
    private function reactivateLicense($license_key, $domain) {
        $license = $this->getLicense($license_key);
        
        $sql = "UPDATE " . LMS_TABLE_ACTIVATIONS . " 
                SET status = 'active', updated_at = CURRENT_TIMESTAMP 
                WHERE license_id = :license_id AND domain = :domain";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(['license_id' => $license['id'], 'domain' => $domain]);
        
        if ($result) {
            $this->updateActivationCount($license['id']);
            
            $activation = $this->getActivation($license['id'], $domain);
            
            return [
                'success' => true,
                'activation_token' => $activation['activation_token'],
                'message' => 'License reactivated successfully'
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to reactivate license'];
    }
    
    /**
     * Get license statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total licenses
        $sql = "SELECT COUNT(*) as total FROM " . LMS_TABLE_LICENSES;
        $stmt = $this->db->query($sql);
        $stats['total_licenses'] = $stmt->fetchColumn();
        
        // Active licenses
        $sql = "SELECT COUNT(*) as active FROM " . LMS_TABLE_LICENSES . " WHERE status = 'active'";
        $stmt = $this->db->query($sql);
        $stats['active_licenses'] = $stmt->fetchColumn();
        
        // Expired licenses
        $sql = "SELECT COUNT(*) as expired FROM " . LMS_TABLE_LICENSES . " 
                WHERE expires_at IS NOT NULL AND expires_at < NOW()";
        $stmt = $this->db->query($sql);
        $stats['expired_licenses'] = $stmt->fetchColumn();
        
        // Total activations
        $sql = "SELECT COUNT(*) as total FROM " . LMS_TABLE_ACTIVATIONS . " WHERE status = 'active'";
        $stmt = $this->db->query($sql);
        $stats['total_activations'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    /**
     * Get license by ID
     */
    public function getLicenseById($license_id) {
        $sql = "SELECT l.*, 
                   COALESCE(a.activation_count, 0) as activation_count
                FROM " . LMS_TABLE_LICENSES . " l
                LEFT JOIN (
                    SELECT license_id, COUNT(*) as activation_count 
                    FROM " . LMS_TABLE_ACTIVATIONS . " 
                    WHERE status = 'active' 
                    GROUP BY license_id
                ) a ON l.id = a.license_id
                WHERE l.id = :license_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['license_id' => $license_id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get licenses with filters
     */
    public function getLicensesWithFilters($status_filter = 'all', $search_filter = '', $page = 1, $per_page = 20) {
        $offset = ($page - 1) * $per_page;
        $where_conditions = [];
        $params = [];
        
        // Status filter
        if ($status_filter !== 'all') {
            if ($status_filter === 'expired') {
                $where_conditions[] = "l.expires_at IS NOT NULL AND l.expires_at < NOW()";
            } else {
                $where_conditions[] = "l.status = :status";
                $params['status'] = $status_filter;
            }
        }
        
        // Search filter
        if (!empty($search_filter)) {
            $where_conditions[] = "(l.license_key LIKE :search OR l.product_name LIKE :search OR l.customer_name LIKE :search OR l.customer_email LIKE :search)";
            $params['search'] = '%' . $search_filter . '%';
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        $sql = "SELECT l.*, 
                   COALESCE(a.activation_count, 0) as activation_count
                FROM " . LMS_TABLE_LICENSES . " l
                LEFT JOIN (
                    SELECT license_id, COUNT(*) as activation_count 
                    FROM " . LMS_TABLE_ACTIVATIONS . " 
                    WHERE status = 'active' 
                    GROUP BY license_id
                ) a ON l.id = a.license_id
                $where_clause
                ORDER BY l.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Count licenses with filters
     */
    public function countLicensesWithFilters($status_filter = 'all', $search_filter = '') {
        $where_conditions = [];
        $params = [];
        
        // Status filter
        if ($status_filter !== 'all') {
            if ($status_filter === 'expired') {
                $where_conditions[] = "expires_at IS NOT NULL AND expires_at < NOW()";
            } else {
                $where_conditions[] = "status = :status";
                $params['status'] = $status_filter;
            }
        }
        
        // Search filter
        if (!empty($search_filter)) {
            $where_conditions[] = "(license_key LIKE :search OR product_name LIKE :search OR customer_name LIKE :search OR customer_email LIKE :search)";
            $params['search'] = '%' . $search_filter . '%';
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        $sql = "SELECT COUNT(*) FROM " . LMS_TABLE_LICENSES . " $where_clause";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * Count licenses by status
     */
    public function countLicensesByStatus($status) {
        if ($status === 'expired') {
            $sql = "SELECT COUNT(*) FROM " . LMS_TABLE_LICENSES . " 
                    WHERE expires_at IS NOT NULL AND expires_at < NOW()";
            $stmt = $this->db->query($sql);
        } else {
            $sql = "SELECT COUNT(*) FROM " . LMS_TABLE_LICENSES . " WHERE status = :status";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['status' => $status]);
        }
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Get expiring licenses
     */
    public function getExpiringLicenses($days = 30) {
        $sql = "SELECT * FROM " . LMS_TABLE_LICENSES . " 
                WHERE status = 'active' 
                AND expires_at IS NOT NULL 
                AND expires_at > NOW() 
                AND expires_at <= DATE_ADD(NOW(), INTERVAL :days DAY)
                ORDER BY expires_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['days' => $days]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Update license by ID
     */
    public function updateLicenseById($license_id, $data) {
        $allowed_fields = ['product_name', 'customer_name', 'customer_email', 'allowed_domains', 
                          'max_activations', 'status', 'expires_at', 'features', 'notes'];
        
        $update_fields = [];
        $params = ['license_id' => $license_id];
        
        foreach ($allowed_fields as $field) {
            if (array_key_exists($field, $data)) {
                $update_fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($update_fields)) {
            throw new Exception("No valid fields to update");
        }
        
        $sql = "UPDATE " . LMS_TABLE_LICENSES . " SET " . implode(", ", $update_fields) . 
               ", updated_at = CURRENT_TIMESTAMP WHERE id = :license_id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            $this->logger->info("License updated successfully", [
                'license_id' => $license_id,
                'updated_fields' => array_keys($data)
            ]);
        }
        
        return $result;
    }
    
    /**
     * Extend license
     */
    public function extendLicense($license_id, $extend_days) {
        $license = $this->getLicenseById($license_id);
        if (!$license) {
            throw new Exception("License not found");
        }
        
        $current_expiry = new DateTime($license['expires_at']);
        $current_expiry->add(new DateInterval("P{$extend_days}D"));
        $new_expiry = $current_expiry->format('Y-m-d H:i:s');
        
        $sql = "UPDATE " . LMS_TABLE_LICENSES . " 
                SET expires_at = :expires_at, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :license_id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'expires_at' => $new_expiry,
            'license_id' => $license_id
        ]);
        
        if ($result) {
            $this->logger->info("License extended successfully", [
                'license_id' => $license_id,
                'extend_days' => $extend_days,
                'new_expiry' => $new_expiry
            ]);
        }
        
        return $result;
    }
    
    /**
     * Revoke license
     */
    public function revokeLicense($license_id) {
        $sql = "UPDATE " . LMS_TABLE_LICENSES . " 
                SET status = 'revoked', updated_at = CURRENT_TIMESTAMP 
                WHERE id = :license_id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(['license_id' => $license_id]);
        
        if ($result) {
            // Deactivate all activations for this license
            $sql = "UPDATE " . LMS_TABLE_ACTIVATIONS . " 
                    SET status = 'inactive', updated_at = CURRENT_TIMESTAMP 
                    WHERE license_id = :license_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['license_id' => $license_id]);
            
            $this->logger->info("License revoked successfully", [
                'license_id' => $license_id
            ]);
        }
        
        return $result;
    }
    
    /**
     * Regenerate license key
     */
    public function regenerateLicenseKey($license_id) {
        $license = $this->getLicenseById($license_id);
        if (!$license) {
            throw new Exception("License not found");
        }
        
        $new_key = lms_generate_license_key();
        
        $sql = "UPDATE " . LMS_TABLE_LICENSES . " 
                SET license_key = :license_key, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :license_id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'license_key' => $new_key,
            'license_id' => $license_id
        ]);
        
        if ($result) {
            $this->logger->info("License key regenerated successfully", [
                'license_id' => $license_id,
                'old_key' => $license['license_key'],
                'new_key' => $new_key
            ]);
            
            return $new_key;
        }
        
        throw new Exception("Failed to regenerate license key");
    }
    
    /**
     * Suspend a license (change status to suspended)
     * 
     * @param string $license_key The license key to suspend
     * @param string $reason Optional reason for suspension
     * @return bool Success status
     * @throws Exception If license not found or already suspended
     */
    public function suspendLicense($license_key, $reason = null) {
        // Get current license
        $license = $this->getLicense($license_key);
        if (!$license) {
            throw new Exception("License not found");
        }
        
        if ($license['status'] === 'suspended') {
            throw new Exception("License is already suspended");
        }
        
        // Update status to suspended
        $sql = "UPDATE " . LMS_TABLE_LICENSES . " 
                SET status = 'suspended', updated_at = CURRENT_TIMESTAMP 
                WHERE license_key = :license_key";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(['license_key' => $license_key]);
        
        if ($result) {
            $this->logger->warning("License suspended", [
                'license_key' => $license_key,
                'previous_status' => $license['status'],
                'customer_email' => $license['customer_email'],
                'product' => $license['product_name'],
                'reason' => $reason ?? 'No reason provided'
            ]);
            
            return true;
        }
        
        throw new Exception("Failed to suspend license");
    }
    
    /**
     * Unsuspend/Reactivate a license (change status from suspended to active)
     * 
     * @param string $license_key The license key to unsuspend
     * @return bool Success status
     * @throws Exception If license not found or not suspended
     */
    public function unsuspendLicense($license_key) {
        // Get current license
        $license = $this->getLicense($license_key);
        if (!$license) {
            throw new Exception("License not found");
        }
        
        if ($license['status'] !== 'suspended') {
            throw new Exception("License is not suspended (current status: {$license['status']})");
        }
        
        // Check if license has expired
        $new_status = 'active';
        if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
            $new_status = 'expired';
        }
        
        // Update status to active or expired
        $sql = "UPDATE " . LMS_TABLE_LICENSES . " 
                SET status = :status, updated_at = CURRENT_TIMESTAMP 
                WHERE license_key = :license_key";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'status' => $new_status,
            'license_key' => $license_key
        ]);
        
        if ($result) {
            $this->logger->info("License unsuspended", [
                'license_key' => $license_key,
                'new_status' => $new_status,
                'customer_email' => $license['customer_email'],
                'product' => $license['product_name']
            ]);
            
            return true;
        }
        
        throw new Exception("Failed to unsuspend license");
    }
    
    /**
     * Check if a domain matches an allowed domain pattern
     * Supports wildcards (*.example.com)
     * 
     * @param string $domain The domain to check
     * @param string $pattern The allowed domain pattern
     * @return bool True if domain matches pattern
     */
    private function matchesDomain($domain, $pattern) {
        // Exact match
        if ($domain === $pattern) {
            return true;
        }
        
        // Wildcard match (*.example.com)
        if (strpos($pattern, '*') !== false) {
            // Convert wildcard pattern to regex
            $regex = '/^' . str_replace(['.', '*'], ['\.', '.*'], $pattern) . '$/i';
            return preg_match($regex, $domain) === 1;
        }
        
        // Subdomain match - if pattern doesn't have wildcard but domain is subdomain
        // e.g., pattern: example.com, domain: sub.example.com
        if (strpos($domain, '.' . $pattern) !== false) {
            return substr($domain, -(strlen($pattern) + 1)) === '.' . $pattern;
        }
        
        return false;
    }
}