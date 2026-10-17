<?php
require_once __DIR__ . '/../config/database.php';

class UserTracker {
    private $db;
    private $logFile;
    private $trackingEnabled;
    
    public function __construct() {
        $this->db = new Database();
        $this->logFile = __DIR__ . '/../security/logs/user_tracking.log';
        $this->trackingEnabled = true;
        
        // Create security directories if not exist
        $this->createSecurityDirectories();
        
        // Create tracking tables
        $this->createTrackingTables();
    }
    
    /**
     * Create security directories
     */
    private function createSecurityDirectories() {
        $dirs = [
            __DIR__ . '/../security/logs',
            __DIR__ . '/../security/tracking',
            __DIR__ . '/../security/protection'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Create tracking tables
     */
    private function createTrackingTables() {
        try {
            // User sessions tracking table
            $sql = "
                CREATE TABLE IF NOT EXISTS user_sessions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    session_id VARCHAR(255) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT,
                    browser VARCHAR(100),
                    os VARCHAR(100),
                    device VARCHAR(100),
                    location VARCHAR(255),
                    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    logout_time TIMESTAMP NULL,
                    is_active TINYINT(1) DEFAULT 1,
                    session_duration INT DEFAULT 0,
                    pages_visited INT DEFAULT 0,
                    actions_performed INT DEFAULT 0,
                    INDEX idx_user_id (user_id),
                    INDEX idx_session_id (session_id),
                    INDEX idx_ip_address (ip_address),
                    INDEX idx_login_time (login_time),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $this->db->getConnection()->exec($sql);
            
            // User activities tracking table
            $sql = "
                CREATE TABLE IF NOT EXISTS user_activities (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    session_id VARCHAR(255),
                    activity_type ENUM('login', 'logout', 'page_view', 'action', 'error', 'security_alert') NOT NULL,
                    page_url VARCHAR(500),
                    action_description TEXT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    additional_data JSON,
                    risk_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
                    INDEX idx_user_id (user_id),
                    INDEX idx_session_id (session_id),
                    INDEX idx_activity_type (activity_type),
                    INDEX idx_timestamp (timestamp),
                    INDEX idx_risk_level (risk_level),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $this->db->getConnection()->exec($sql);
            
            // System access log table
            $sql = "
                CREATE TABLE IF NOT EXISTS system_access_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    access_type ENUM('file_access', 'database_query', 'admin_action', 'config_change', 'security_event') NOT NULL,
                    user_id INT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    file_path VARCHAR(500),
                    query_type VARCHAR(100),
                    action_details TEXT,
                    success TINYINT(1) DEFAULT 1,
                    error_message TEXT,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
                    INDEX idx_access_type (access_type),
                    INDEX idx_user_id (user_id),
                    INDEX idx_timestamp (timestamp),
                    INDEX idx_severity (severity),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $this->db->getConnection()->exec($sql);
            
        } catch (Exception $e) {
            error_log("Failed to create tracking tables: " . $e->getMessage());
        }
    }
    
    /**
     * Track user login
     */
    public function trackLogin($userId, $sessionId) {
        if (!$this->trackingEnabled) return;
        
        try {
            $userInfo = $this->getUserInfo();
            
            // Insert session record
            $stmt = $this->db->query("
                INSERT INTO user_sessions 
                (user_id, session_id, ip_address, user_agent, browser, os, device, location) 
                VALUES (:user_id, :session_id, :ip_address, :user_agent, :browser, :os, :device, :location)
            ");
            
            $this->db->execute($stmt, [
                ':user_id' => $userId,
                ':session_id' => $sessionId,
                ':ip_address' => $userInfo['ip'],
                ':user_agent' => $userInfo['user_agent'],
                ':browser' => $userInfo['browser'],
                ':os' => $userInfo['os'],
                ':device' => $userInfo['device'],
                ':location' => $userInfo['location']
            ]);
            
            // Log activity
            $this->logActivity($userId, $sessionId, 'login', null, 'User logged in');
            
            // Log to file
            $this->logToFile("LOGIN", $userId, $userInfo['ip'], "User ID: $userId logged in");
            
        } catch (Exception $e) {
            error_log("Failed to track login: " . $e->getMessage());
        }
    }
    
    /**
     * Track user logout
     */
    public function trackLogout($userId, $sessionId) {
        if (!$this->trackingEnabled) return;
        
        try {
            // Update session record
            $stmt = $this->db->query("
                UPDATE user_sessions 
                SET logout_time = NOW(), is_active = 0, 
                    session_duration = TIMESTAMPDIFF(SECOND, login_time, NOW())
                WHERE user_id = :user_id AND session_id = :session_id
            ");
            
            $this->db->execute($stmt, [
                ':user_id' => $userId,
                ':session_id' => $sessionId
            ]);
            
            // Log activity
            $this->logActivity($userId, $sessionId, 'logout', null, 'User logged out');
            
            // Log to file
            $this->logToFile("LOGOUT", $userId, $_SERVER['REMOTE_ADDR'], "User ID: $userId logged out");
            
        } catch (Exception $e) {
            error_log("Failed to track logout: " . $e->getMessage());
        }
    }
    
    /**
     * Track page view
     */
    public function trackPageView($userId, $sessionId, $pageUrl) {
        if (!$this->trackingEnabled) return;
        
        try {
            // Update session activity
            $stmt = $this->db->query("
                UPDATE user_sessions 
                SET last_activity = NOW(), pages_visited = pages_visited + 1
                WHERE user_id = :user_id AND session_id = :session_id
            ");
            
            $this->db->execute($stmt, [
                ':user_id' => $userId,
                ':session_id' => $sessionId
            ]);
            
            // Log activity
            $this->logActivity($userId, $sessionId, 'page_view', $pageUrl, 'Page viewed: ' . $pageUrl);
            
        } catch (Exception $e) {
            error_log("Failed to track page view: " . $e->getMessage());
        }
    }
    
    /**
     * Track user action
     */
    public function trackAction($userId, $sessionId, $action, $details = null) {
        if (!$this->trackingEnabled) return;
        
        try {
            // Update session activity
            $stmt = $this->db->query("
                UPDATE user_sessions 
                SET last_activity = NOW(), actions_performed = actions_performed + 1
                WHERE user_id = :user_id AND session_id = :session_id
            ");
            
            $this->db->execute($stmt, [
                ':user_id' => $userId,
                ':session_id' => $sessionId
            ]);
            
            // Determine risk level
            $riskLevel = $this->assessRiskLevel($action, $details);
            
            // Log activity
            $this->logActivity($userId, $sessionId, 'action', null, $action, $details, $riskLevel);
            
            // Log high-risk actions to file
            if (in_array($riskLevel, ['high', 'critical'])) {
                $this->logToFile("HIGH_RISK_ACTION", $userId, $_SERVER['REMOTE_ADDR'], 
                    "Action: $action, Details: $details, Risk: $riskLevel");
            }
            
        } catch (Exception $e) {
            error_log("Failed to track action: " . $e->getMessage());
        }
    }
    
    /**
     * Track security alert
     */
    public function trackSecurityAlert($userId, $sessionId, $alertType, $details) {
        try {
            $this->logActivity($userId, $sessionId, 'security_alert', null, $alertType, $details, 'critical');
            
            // Always log security alerts to file
            $this->logToFile("SECURITY_ALERT", $userId, $_SERVER['REMOTE_ADDR'], 
                "Alert: $alertType, Details: $details");
            
            // Log to system access log
            $stmt = $this->db->query("
                INSERT INTO system_access_log 
                (access_type, user_id, ip_address, action_details, severity) 
                VALUES ('security_event', :user_id, :ip_address, :details, 'critical')
            ");
            
            $this->db->execute($stmt, [
                ':user_id' => $userId,
                ':ip_address' => $_SERVER['REMOTE_ADDR'],
                ':details' => "Security Alert: $alertType - $details"
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to track security alert: " . $e->getMessage());
        }
    }
    
    /**
     * Log activity to database
     */
    private function logActivity($userId, $sessionId, $type, $pageUrl = null, $description = null, $additionalData = null, $riskLevel = 'low') {
        try {
            $stmt = $this->db->query("
                INSERT INTO user_activities 
                (user_id, session_id, activity_type, page_url, action_description, ip_address, user_agent, additional_data, risk_level) 
                VALUES (:user_id, :session_id, :activity_type, :page_url, :action_description, :ip_address, :user_agent, :additional_data, :risk_level)
            ");
            
            $this->db->execute($stmt, [
                ':user_id' => $userId,
                ':session_id' => $sessionId,
                ':activity_type' => $type,
                ':page_url' => $pageUrl,
                ':action_description' => $description,
                ':ip_address' => $_SERVER['REMOTE_ADDR'],
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                ':additional_data' => $additionalData ? json_encode($additionalData) : null,
                ':risk_level' => $riskLevel
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
    
    /**
     * Log to file
     */
    private function logToFile($type, $userId, $ip, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$type] [User:$userId] [IP:$ip] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get user information
     */
    private function getUserInfo() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $userAgent,
            'browser' => $this->getBrowser($userAgent),
            'os' => $this->getOS($userAgent),
            'device' => $this->getDevice($userAgent),
            'location' => $this->getLocation($_SERVER['REMOTE_ADDR'] ?? '')
        ];
    }
    
    /**
     * Detect browser
     */
    private function getBrowser($userAgent) {
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        if (strpos($userAgent, 'Opera') !== false) return 'Opera';
        return 'Unknown';
    }
    
    /**
     * Detect OS
     */
    private function getOS($userAgent) {
        if (strpos($userAgent, 'Windows') !== false) return 'Windows';
        if (strpos($userAgent, 'Mac') !== false) return 'macOS';
        if (strpos($userAgent, 'Linux') !== false) return 'Linux';
        if (strpos($userAgent, 'Android') !== false) return 'Android';
        if (strpos($userAgent, 'iOS') !== false) return 'iOS';
        return 'Unknown';
    }
    
    /**
     * Detect device
     */
    private function getDevice($userAgent) {
        if (strpos($userAgent, 'Mobile') !== false) return 'Mobile';
        if (strpos($userAgent, 'Tablet') !== false) return 'Tablet';
        return 'Desktop';
    }
    
    /**
     * Get location (simplified)
     */
    private function getLocation($ip) {
        // For production, you might want to use a GeoIP service
        if ($ip === '127.0.0.1' || $ip === 'localhost') {
            return 'Local';
        }
        return 'Unknown';
    }
    
    /**
     * Assess risk level
     */
    private function assessRiskLevel($action, $details) {
        $highRiskActions = [
            'delete_user', 'delete_service', 'database_backup', 'database_restore',
            'change_password', 'update_settings', 'file_upload'
        ];
        
        $criticalActions = [
            'admin_login', 'database_migration', 'system_config_change',
            'security_setting_change', 'user_role_change'
        ];
        
        if (in_array($action, $criticalActions)) return 'critical';
        if (in_array($action, $highRiskActions)) return 'high';
        if (strpos($action, 'admin_') === 0) return 'medium';
        
        return 'low';
    }
    
    /**
     * Get active sessions
     */
    public function getActiveSessions() {
        try {
            $stmt = $this->db->query("
                SELECT us.*, u.name, u.email, u.role 
                FROM user_sessions us 
                JOIN users u ON us.user_id = u.id 
                WHERE us.is_active = 1 
                ORDER BY us.last_activity DESC
            ");
            $this->db->execute($stmt);
            return $this->db->resultSet($stmt);
        } catch (Exception $e) {
            error_log("Failed to get active sessions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user activity report
     */
    public function getUserActivityReport($userId = null, $dateFrom = null, $dateTo = null) {
        try {
            $sql = "
                SELECT ua.*, u.name, u.email 
                FROM user_activities ua 
                JOIN users u ON ua.user_id = u.id 
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($userId) {
                $sql .= " AND ua.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            if ($dateFrom) {
                $sql .= " AND DATE(ua.timestamp) >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND DATE(ua.timestamp) <= :date_to";
                $params[':date_to'] = $dateTo;
            }
            
            $sql .= " ORDER BY ua.timestamp DESC LIMIT 1000";
            
            $stmt = $this->db->query($sql);
            $this->db->execute($stmt, $params);
            return $this->db->resultSet($stmt);
            
        } catch (Exception $e) {
            error_log("Failed to get activity report: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get security alerts
     */
    public function getSecurityAlerts($limit = 50) {
        try {
            $stmt = $this->db->query("
                SELECT ua.*, u.name, u.email 
                FROM user_activities ua 
                LEFT JOIN users u ON ua.user_id = u.id 
                WHERE ua.activity_type = 'security_alert' OR ua.risk_level IN ('high', 'critical')
                ORDER BY ua.timestamp DESC 
                LIMIT :limit
            ");
            $this->db->execute($stmt, [':limit' => $limit]);
            return $this->db->resultSet($stmt);
        } catch (Exception $e) {
            error_log("Failed to get security alerts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean old tracking data
     */
    public function cleanOldData($days = 90) {
        try {
            // Clean old activities
            $stmt = $this->db->query("DELETE FROM user_activities WHERE timestamp < DATE_SUB(NOW(), INTERVAL :days DAY)");
            $this->db->execute($stmt, [':days' => $days]);
            
            // Clean old inactive sessions
            $stmt = $this->db->query("DELETE FROM user_sessions WHERE is_active = 0 AND login_time < DATE_SUB(NOW(), INTERVAL :days DAY)");
            $this->db->execute($stmt, [':days' => $days]);
            
            // Clean old system access logs
            $stmt = $this->db->query("DELETE FROM system_access_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL :days DAY)");
            $this->db->execute($stmt, [':days' => $days]);
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to clean old data: " . $e->getMessage());
            return false;
        }
    }
}
?>