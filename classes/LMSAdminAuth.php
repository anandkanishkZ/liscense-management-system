<?php
/**
 * Zwicky Technology License Management System
 * Admin Authentication Class
 * 
 * @author Zwicky Technology
 * @version 1.0.0
 * @since 2024
 */

class LMSAdminAuth {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = getLMSDatabase();
        $this->logger = new LMSLogger();
    }
    
    /**
     * Authenticate admin user
     */
    public function login($username, $password, $remember = false) {
        // Check for lockout
        if ($this->isLocked($username)) {
            $lockout_time = $this->getLockoutTime($username);
            $remaining = $lockout_time - time();
            
            $this->logger->warning("Login attempt on locked account", [
                'username' => $username,
                'remaining_lockout' => $remaining
            ]);
            
            return [
                'success' => false,
                'message' => "Account locked. Try again in " . ceil($remaining / 60) . " minutes."
            ];
        }
        
        $user = $this->getUser($username);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt($username);
            
            $this->logger->warning("Failed login attempt", [
                'username' => $username,
                'user_exists' => $user ? true : false
            ]);
            
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        }
        
        if ($user['status'] !== 'active') {
            $this->logger->warning("Login attempt on inactive account", [
                'username' => $username,
                'status' => $user['status']
            ]);
            
            return [
                'success' => false,
                'message' => 'Account is inactive'
            ];
        }
        
        // Successful login
        $this->resetFailedAttempts($user['id']);
        $this->updateLastLogin($user['id']);
        
        // Create session
        $this->createSession($user, $remember);
        
        $this->logger->info("Admin login successful", [
            'username' => $username,
            'user_id' => $user['id']
        ]);
        
        return [
            'success' => true,
            'user' => $this->sanitizeUser($user),
            'message' => 'Login successful'
        ];
    }
    
    /**
     * Logout admin user
     */
    public function logout() {
        $user_id = $_SESSION['admin_user_id'] ?? null;
        
        if ($user_id) {
            $this->logger->info("Admin logout", ['user_id' => $user_id]);
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Start new session
        session_start();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_username'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > LMS_SESSION_LIFETIME)) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Get current authenticated user
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $user = $this->getUserById($_SESSION['admin_user_id']);
        return $user ? $this->sanitizeUser($user) : null;
    }
    
    /**
     * Check if user has permission
     */
    public function hasPermission($permission) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        // Admin has all permissions
        if ($user['role'] === 'admin') {
            return true;
        }
        
        // Define role permissions
        $permissions = [
            'manager' => [
                'view_licenses', 'create_licenses', 'edit_licenses',
                'view_activations', 'manage_activations',
                'view_logs', 'view_stats'
            ],
            'viewer' => [
                'view_licenses', 'view_activations', 'view_logs', 'view_stats'
            ]
        ];
        
        $role_permissions = $permissions[$user['role']] ?? [];
        return in_array($permission, $role_permissions);
    }
    
    /**
     * Create new admin user
     */
    public function createUser($data) {
        $required_fields = ['username', 'email', 'password', 'full_name'];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address");
        }
        
        // Check for existing username/email
        if ($this->getUser($data['username']) || $this->getUserByEmail($data['email'])) {
            throw new Exception("Username or email already exists");
        }
        
        // Validate password strength
        if (!$this->isPasswordStrong($data['password'])) {
            throw new Exception("Password must be at least 8 characters with uppercase, lowercase, number, and special character");
        }
        
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $role = $data['role'] ?? 'viewer';
        
        $sql = "INSERT INTO " . LMS_TABLE_ADMIN_USERS . " 
                (username, email, password_hash, full_name, role, status) 
                VALUES (:username, :email, :password_hash, :full_name, :role, 'active')";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $password_hash,
            'full_name' => $data['full_name'],
            'role' => $role
        ]);
        
        if ($result) {
            $user_id = $this->db->lastInsertId();
            
            $this->logger->info("Admin user created", [
                'username' => $data['username'],
                'email' => $data['email'],
                'role' => $role,
                'created_by' => $_SESSION['admin_user_id'] ?? null
            ]);
            
            return $user_id;
        }
        
        throw new Exception("Failed to create user");
    }
    
    /**
     * Update user
     */
    public function updateUser($user_id, $data) {
        $user = $this->getUserById($user_id);
        if (!$user) {
            throw new Exception("User not found");
        }
        
        $allowed_fields = ['email', 'full_name', 'role', 'status'];
        $update_fields = [];
        $params = ['user_id' => $user_id];
        
        foreach ($allowed_fields as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'email' && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email address");
                }
                
                $update_fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($update_fields)) {
            throw new Exception("No valid fields to update");
        }
        
        $sql = "UPDATE " . LMS_TABLE_ADMIN_USERS . " SET " . implode(", ", $update_fields) . 
               ", updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            $this->logger->info("Admin user updated", [
                'user_id' => $user_id,
                'updated_fields' => array_keys($data),
                'updated_by' => $_SESSION['admin_user_id'] ?? null
            ]);
        }
        
        return $result;
    }
    
    /**
     * Change password
     */
    public function changePassword($user_id, $current_password, $new_password) {
        $user = $this->getUserById($user_id);
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Verify current password
        if (!password_verify($current_password, $user['password_hash'])) {
            throw new Exception("Current password is incorrect");
        }
        
        // Validate new password
        if (!$this->isPasswordStrong($new_password)) {
            throw new Exception("New password must be at least 8 characters with uppercase, lowercase, number, and special character");
        }
        
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE " . LMS_TABLE_ADMIN_USERS . " 
                SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'password_hash' => $password_hash,
            'user_id' => $user_id
        ]);
        
        if ($result) {
            $this->logger->info("Password changed", [
                'user_id' => $user_id,
                'changed_by' => $_SESSION['admin_user_id'] ?? null
            ]);
        }
        
        return $result;
    }
    
    /**
     * Get all users
     */
    public function getAllUsers() {
        $sql = "SELECT * FROM " . LMS_TABLE_ADMIN_USERS . " ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        $users = $stmt->fetchAll();
        
        return array_map([$this, 'sanitizeUser'], $users);
    }
    
    /**
     * Delete user
     */
    public function deleteUser($user_id) {
        // Prevent deleting own account
        if ($user_id == $_SESSION['admin_user_id']) {
            throw new Exception("Cannot delete your own account");
        }
        
        $user = $this->getUserById($user_id);
        if (!$user) {
            throw new Exception("User not found");
        }
        
        $sql = "DELETE FROM " . LMS_TABLE_ADMIN_USERS . " WHERE id = :user_id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(['user_id' => $user_id]);
        
        if ($result) {
            $this->logger->info("Admin user deleted", [
                'deleted_user_id' => $user_id,
                'deleted_username' => $user['username'],
                'deleted_by' => $_SESSION['admin_user_id'] ?? null
            ]);
        }
        
        return $result;
    }
    
    // Private helper methods
    
    private function getUser($username) {
        $sql = "SELECT * FROM " . LMS_TABLE_ADMIN_USERS . " WHERE username = :username";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }
    
    private function getUserById($id) {
        $sql = "SELECT * FROM " . LMS_TABLE_ADMIN_USERS . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    private function getUserByEmail($email) {
        $sql = "SELECT * FROM " . LMS_TABLE_ADMIN_USERS . " WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }
    
    private function isLocked($username) {
        $user = $this->getUser($username);
        if (!$user) {
            return false;
        }
        
        return $user['locked_until'] && strtotime($user['locked_until']) > time();
    }
    
    private function getLockoutTime($username) {
        $user = $this->getUser($username);
        return $user ? strtotime($user['locked_until']) : 0;
    }
    
    private function recordFailedAttempt($username) {
        $user = $this->getUser($username);
        if (!$user) {
            return;
        }
        
        $attempts = $user['login_attempts'] + 1;
        $locked_until = null;
        
        if ($attempts >= LMS_MAX_LOGIN_ATTEMPTS) {
            $locked_until = date('Y-m-d H:i:s', time() + LMS_LOCKOUT_TIME);
        }
        
        $sql = "UPDATE " . LMS_TABLE_ADMIN_USERS . " 
                SET login_attempts = :attempts, locked_until = :locked_until 
                WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'attempts' => $attempts,
            'locked_until' => $locked_until,
            'user_id' => $user['id']
        ]);
    }
    
    private function resetFailedAttempts($user_id) {
        $sql = "UPDATE " . LMS_TABLE_ADMIN_USERS . " 
                SET login_attempts = 0, locked_until = NULL 
                WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
    }
    
    private function updateLastLogin($user_id) {
        $sql = "UPDATE " . LMS_TABLE_ADMIN_USERS . " 
                SET last_login = CURRENT_TIMESTAMP 
                WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
    }
    
    private function createSession($user, $remember = false) {
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['admin_full_name'] = $user['full_name'];
        $_SESSION['last_activity'] = time();
        
        if ($remember) {
            // Set remember me cookie (optional implementation)
            $token = bin2hex(random_bytes(32));
            setcookie('lms_remember', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
        }
    }
    
    private function sanitizeUser($user) {
        unset($user['password_hash']);
        return $user;
    }
    
    private function isPasswordStrong($password) {
        return strlen($password) >= 8 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/\d/', $password) &&
               preg_match('/[^A-Za-z0-9]/', $password);
    }
}