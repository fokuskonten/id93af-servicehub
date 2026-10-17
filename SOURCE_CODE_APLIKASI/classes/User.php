<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Create new user
    public function createUser($data) {
        $sql = "INSERT INTO users (name, email, phone, password, role, address, created_at) 
                VALUES (:name, :email, :phone, :password, :role, :address, NOW())";
        
        $stmt = $this->db->query($sql);
        $result = $this->db->execute($stmt, $data);
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    // Login user
    public function login($email, $password) {
        global $userTracker;
        
        $sql = "SELECT * FROM users WHERE email = :email AND is_active = 1";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt, [':email' => $email]);
        $user = $this->db->single($stmt);
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Track login
            if ($userTracker) {
                $userTracker->trackLogin($user['id'], session_id());
            }
            
            return $user;
        }
        
        return false;
    }
    
    // Check if email exists
    public function emailExists($email) {
        $sql = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt, [':email' => $email]);
        return $this->db->single($stmt) !== false;
    }
    
    // Get user by ID
    public function getUserById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt, [':id' => $id]);
        return $this->db->single($stmt);
    }
    
    // Update user
    public function updateUser($id, $data) {
        $setParts = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->query($sql);
        return $this->db->execute($stmt, $params);
    }
    
    // Update last login
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt, [':id' => $userId]);
    }
    
    // Get all customers
    public function getAllCustomers() {
        $sql = "SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt);
        return $this->db->resultSet($stmt);
    }
    
    // Get user statistics
    public function getUserStats() {
        $stats = [];
        
        // Total users
        $sql = "SELECT COUNT(*) as total FROM users";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt);
        $result = $this->db->single($stmt);
        $stats['total'] = $result['total'];
        
        // Total customers
        $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt);
        $result = $this->db->single($stmt);
        $stats['customers'] = $result['total'];
        
        // Total admins
        $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'admin'";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt);
        $result = $this->db->single($stmt);
        $stats['admins'] = $result['total'];
        
        // New users this month
        $sql = "SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt);
        $result = $this->db->single($stmt);
        $stats['new_this_month'] = $result['total'];
        
        return $stats;
    }
    
    // Search users
    public function searchUsers($keyword, $role = null) {
        $sql = "SELECT * FROM users WHERE (name LIKE :keyword OR email LIKE :keyword OR phone LIKE :keyword)";
        $params = [':keyword' => "%$keyword%"];
        
        if ($role) {
            $sql .= " AND role = :role";
            $params[':role'] = $role;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt, $params);
        return $this->db->resultSet($stmt);
    }
    
    // Toggle user status
    public function toggleUserStatus($id) {
        $sql = "UPDATE users SET is_active = NOT is_active, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->query($sql);
        return $this->db->execute($stmt, [':id' => $id]);
    }
    
    // Delete user
    public function deleteUser($id) {
        // Check if user has services
        $sql = "SELECT COUNT(*) as count FROM services WHERE customer_id = :id";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt, [':id' => $id]);
        $result = $this->db->single($stmt);
        
        if ($result['count'] > 0) {
            return false; // Cannot delete user with services
        }
        
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->query($sql);
        return $this->db->execute($stmt, [':id' => $id]);
    }
    
    // Upload avatar
    public function uploadAvatar($userId, $file) {
        // Check if avatar column exists first
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'avatar'");
            $this->db->execute($stmt);
            $columnExists = $this->db->single($stmt);
            
            if (!$columnExists) {
                return ['success' => false, 'message' => 'Avatar feature not available. Please run database migration first.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF.'];
        }
        
        // Validate file size
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 2MB.'];
        }
        
        // Create upload directory if not exists
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Get current avatar to delete old one
        $currentUser = $this->getUserById($userId);
        $oldAvatar = $currentUser['avatar'] ?? null;
        
        // Upload file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Update database
            $avatarPath = 'uploads/avatars/' . $filename;
            $sql = "UPDATE users SET avatar = :avatar, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->query($sql);
            $result = $this->db->execute($stmt, [':avatar' => $avatarPath, ':id' => $userId]);
            
            if ($result) {
                // Delete old avatar file if exists
                if ($oldAvatar && file_exists(__DIR__ . '/../' . $oldAvatar)) {
                    unlink(__DIR__ . '/../' . $oldAvatar);
                }
                
                return ['success' => true, 'message' => 'Avatar berhasil diupload', 'avatar_path' => $avatarPath];
            } else {
                // Delete uploaded file if database update failed
                unlink($filepath);
                return ['success' => false, 'message' => 'Gagal menyimpan avatar ke database'];
            }
        } else {
            return ['success' => false, 'message' => 'Gagal mengupload file'];
        }
    }
    
    // Delete avatar
    public function deleteAvatar($userId) {
        // Check if avatar column exists first
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'avatar'");
            $this->db->execute($stmt);
            $columnExists = $this->db->single($stmt);
            
            if (!$columnExists) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
        
        $user = $this->getUserById($userId);
        if ($user && $user['avatar']) {
            $avatarPath = __DIR__ . '/../' . $user['avatar'];
            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }
            
            // Update database
            $sql = "UPDATE users SET avatar = NULL, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->query($sql);
            return $this->db->execute($stmt, [':id' => $userId]);
        }
        
        return false;
    }
    
    // Get avatar URL
    public function getAvatarUrl($userId) {
        // Check if avatar column exists first
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'avatar'");
            $this->db->execute($stmt);
            $columnExists = $this->db->single($stmt);
            
            if (!$columnExists) {
                return 'assets/img/default-avatar.png';
            }
        } catch (Exception $e) {
            return 'assets/img/default-avatar.png';
        }
        
        $user = $this->getUserById($userId);
        if ($user && $user['avatar'] && file_exists(__DIR__ . '/../' . $user['avatar'])) {
            return $user['avatar'];
        }
        
        // Return default avatar
        return 'assets/img/default-avatar.png';
    }
}
?>