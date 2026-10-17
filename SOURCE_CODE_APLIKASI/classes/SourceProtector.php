<?php
require_once __DIR__ . '/../config/database.php';

class SourceProtector {
    private $db;
    private $protectedFiles;
    private $checksums;
    private $logFile;
    private $alertFile;
    
    public function __construct() {
        $this->db = new Database();
        $this->logFile = __DIR__ . '/../security/logs/source_protection.log';
        $this->alertFile = __DIR__ . '/../security/logs/security_alerts.log';
        
        // Define protected files
        $this->protectedFiles = [
            'config/database.php',
            'config/copyright.php',
            'config/security.php',
            'classes/User.php',
            'classes/Service.php',
            'classes/UserTracker.php',
            'classes/SourceProtector.php',
            'includes/header.php',
            'includes/footer.php',
            'admin/settings.php',
            'admin/database_manager.php'
        ];
        
        // Initialize protection
        $this->initializeProtection();
    }
    
    /**
     * Initialize protection system
     */
    private function initializeProtection() {
        // Create protection table
        $this->createProtectionTable();
        
        // Load or generate checksums
        $this->loadChecksums();
        
        // Register shutdown function for final check
        register_shutdown_function([$this, 'finalIntegrityCheck']);
    }
    
    /**
     * Create protection table
     */
    private function createProtectionTable() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS file_protection (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    file_path VARCHAR(500) NOT NULL UNIQUE,
                    checksum VARCHAR(64) NOT NULL,
                    file_size BIGINT NOT NULL,
                    last_modified TIMESTAMP NOT NULL,
                    protection_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
                    is_monitored TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_file_path (file_path),
                    INDEX idx_checksum (checksum),
                    INDEX idx_protection_level (protection_level)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $this->db->getConnection()->exec($sql);
            
            // Create file changes log table
            $sql = "
                CREATE TABLE IF NOT EXISTS file_changes_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    file_path VARCHAR(500) NOT NULL,
                    change_type ENUM('modified', 'deleted', 'created', 'permission_changed') NOT NULL,
                    old_checksum VARCHAR(64),
                    new_checksum VARCHAR(64),
                    old_size BIGINT,
                    new_size BIGINT,
                    user_id INT NULL,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    severity ENUM('info', 'warning', 'critical') DEFAULT 'warning',
                    is_authorized TINYINT(1) DEFAULT 0,
                    notes TEXT,
                    INDEX idx_file_path (file_path),
                    INDEX idx_change_type (change_type),
                    INDEX idx_detected_at (detected_at),
                    INDEX idx_severity (severity),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $this->db->getConnection()->exec($sql);
            
        } catch (Exception $e) {
            error_log("Failed to create protection tables: " . $e->getMessage());
        }
    }
    
    /**
     * Load checksums from database or generate new ones
     */
    private function loadChecksums() {
        try {
            $stmt = $this->db->query("SELECT file_path, checksum FROM file_protection");
            $this->db->execute($stmt);
            $results = $this->db->resultSet($stmt);
            
            $this->checksums = [];
            foreach ($results as $row) {
                $this->checksums[$row['file_path']] = $row['checksum'];
            }
            
            // Generate checksums for new files
            $this->generateMissingChecksums();
            
        } catch (Exception $e) {
            error_log("Failed to load checksums: " . $e->getMessage());
            $this->checksums = [];
            $this->generateAllChecksums();
        }
    }
    
    /**
     * Generate checksums for all protected files
     */
    private function generateAllChecksums() {
        foreach ($this->protectedFiles as $file) {
            if (file_exists($file)) {
                $this->generateChecksum($file);
            }
        }
    }
    
    /**
     * Generate checksums for missing files
     */
    private function generateMissingChecksums() {
        foreach ($this->protectedFiles as $file) {
            if (file_exists($file) && !isset($this->checksums[$file])) {
                $this->generateChecksum($file);
            }
        }
    }
    
    /**
     * Generate checksum for a file
     */
    private function generateChecksum($filePath) {
        try {
            if (!file_exists($filePath)) {
                return false;
            }
            
            $checksum = hash_file('sha256', $filePath);
            $fileSize = filesize($filePath);
            $lastModified = date('Y-m-d H:i:s', filemtime($filePath));
            
            // Determine protection level
            $protectionLevel = $this->getProtectionLevel($filePath);
            
            // Store in database
            $stmt = $this->db->query("
                INSERT INTO file_protection (file_path, checksum, file_size, last_modified, protection_level) 
                VALUES (:file_path, :checksum, :file_size, :last_modified, :protection_level)
                ON DUPLICATE KEY UPDATE 
                checksum = VALUES(checksum), 
                file_size = VALUES(file_size), 
                last_modified = VALUES(last_modified),
                updated_at = NOW()
            ");
            
            $this->db->execute($stmt, [
                ':file_path' => $filePath,
                ':checksum' => $checksum,
                ':file_size' => $fileSize,
                ':last_modified' => $lastModified,
                ':protection_level' => $protectionLevel
            ]);
            
            $this->checksums[$filePath] = $checksum;
            
            $this->logToFile("CHECKSUM_GENERATED", $filePath, "Generated checksum: $checksum");
            
            return $checksum;
            
        } catch (Exception $e) {
            error_log("Failed to generate checksum for $filePath: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get protection level for file
     */
    private function getProtectionLevel($filePath) {
        $criticalFiles = [
            'config/database.php',
            'config/copyright.php',
            'classes/SourceProtector.php'
        ];
        
        $highFiles = [
            'config/security.php',
            'classes/User.php',
            'classes/UserTracker.php',
            'admin/settings.php'
        ];
        
        if (in_array($filePath, $criticalFiles)) return 'critical';
        if (in_array($filePath, $highFiles)) return 'high';
        if (strpos($filePath, 'admin/') === 0) return 'medium';
        
        return 'low';
    }
    
    /**
     * Check file integrity
     */
    public function checkIntegrity($filePath = null) {
        $violations = [];
        
        $filesToCheck = $filePath ? [$filePath] : $this->protectedFiles;
        
        foreach ($filesToCheck as $file) {
            if (!file_exists($file)) {
                $violations[] = [
                    'file' => $file,
                    'type' => 'deleted',
                    'severity' => 'critical',
                    'message' => "Protected file deleted: $file"
                ];
                continue;
            }
            
            $currentChecksum = hash_file('sha256', $file);
            $storedChecksum = $this->checksums[$file] ?? null;
            
            if ($storedChecksum && $currentChecksum !== $storedChecksum) {
                $violations[] = [
                    'file' => $file,
                    'type' => 'modified',
                    'severity' => $this->getProtectionLevel($file) === 'critical' ? 'critical' : 'warning',
                    'message' => "File modified: $file",
                    'old_checksum' => $storedChecksum,
                    'new_checksum' => $currentChecksum
                ];
                
                // Log the change
                $this->logFileChange($file, 'modified', $storedChecksum, $currentChecksum);
            }
        }
        
        return $violations;
    }
    
    /**
     * Log file change
     */
    private function logFileChange($filePath, $changeType, $oldChecksum = null, $newChecksum = null) {
        try {
            $oldSize = null;
            $newSize = file_exists($filePath) ? filesize($filePath) : null;
            
            // Get old size from database
            if ($oldChecksum) {
                $stmt = $this->db->query("SELECT file_size FROM file_protection WHERE file_path = :file_path AND checksum = :checksum");
                $this->db->execute($stmt, [':file_path' => $filePath, ':checksum' => $oldChecksum]);
                $result = $this->db->single($stmt);
                $oldSize = $result ? $result['file_size'] : null;
            }
            
            $severity = $this->getProtectionLevel($filePath) === 'critical' ? 'critical' : 'warning';
            
            $stmt = $this->db->query("
                INSERT INTO file_changes_log 
                (file_path, change_type, old_checksum, new_checksum, old_size, new_size, 
                 user_id, ip_address, user_agent, severity) 
                VALUES (:file_path, :change_type, :old_checksum, :new_checksum, :old_size, :new_size,
                        :user_id, :ip_address, :user_agent, :severity)
            ");
            
            $this->db->execute($stmt, [
                ':file_path' => $filePath,
                ':change_type' => $changeType,
                ':old_checksum' => $oldChecksum,
                ':new_checksum' => $newChecksum,
                ':old_size' => $oldSize,
                ':new_size' => $newSize,
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                ':severity' => $severity
            ]);
            
            // Log to file
            $this->logToFile("FILE_CHANGE", $filePath, 
                "Type: $changeType, Old: $oldChecksum, New: $newChecksum, Severity: $severity");
            
            // Send alert for critical changes
            if ($severity === 'critical') {
                $this->sendSecurityAlert($filePath, $changeType);
            }
            
        } catch (Exception $e) {
            error_log("Failed to log file change: " . $e->getMessage());
        }
    }
    
    /**
     * Send security alert
     */
    private function sendSecurityAlert($filePath, $changeType) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userId = $_SESSION['user_id'] ?? 'unknown';
        
        $alertMessage = "CRITICAL SECURITY ALERT: File $filePath was $changeType by user $userId from IP $ip at $timestamp";
        
        // Log to alert file
        file_put_contents($this->alertFile, "[$timestamp] $alertMessage" . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        // Log to system
        error_log($alertMessage);
        
        // Track security alert if UserTracker is available
        if (class_exists('UserTracker')) {
            $tracker = new UserTracker();
            $tracker->trackSecurityAlert($userId, session_id(), 'file_modification', 
                "Critical file modified: $filePath ($changeType)");
        }
    }
    
    /**
     * Update file checksum (for authorized changes)
     */
    public function updateChecksum($filePath, $authorized = false) {
        if (!in_array($filePath, $this->protectedFiles)) {
            return false;
        }
        
        $oldChecksum = $this->checksums[$filePath] ?? null;
        $newChecksum = $this->generateChecksum($filePath);
        
        if ($authorized) {
            // Mark the change as authorized
            $stmt = $this->db->query("
                UPDATE file_changes_log 
                SET is_authorized = 1, notes = 'Authorized update'
                WHERE file_path = :file_path AND new_checksum = :new_checksum
            ");
            $this->db->execute($stmt, [
                ':file_path' => $filePath,
                ':new_checksum' => $newChecksum
            ]);
        }
        
        return $newChecksum;
    }
    
    /**
     * Get file changes report
     */
    public function getFileChangesReport($dateFrom = null, $dateTo = null) {
        try {
            $sql = "
                SELECT fcl.*, u.name as user_name, u.email as user_email 
                FROM file_changes_log fcl 
                LEFT JOIN users u ON fcl.user_id = u.id 
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($dateFrom) {
                $sql .= " AND DATE(fcl.detected_at) >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND DATE(fcl.detected_at) <= :date_to";
                $params[':date_to'] = $dateTo;
            }
            
            $sql .= " ORDER BY fcl.detected_at DESC LIMIT 500";
            
            $stmt = $this->db->query($sql);
            $this->db->execute($stmt, $params);
            return $this->db->resultSet($stmt);
            
        } catch (Exception $e) {
            error_log("Failed to get file changes report: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get protection status
     */
    public function getProtectionStatus() {
        try {
            $status = [
                'total_files' => count($this->protectedFiles),
                'monitored_files' => 0,
                'violations' => 0,
                'last_check' => date('Y-m-d H:i:s'),
                'files' => []
            ];
            
            foreach ($this->protectedFiles as $file) {
                $fileStatus = [
                    'path' => $file,
                    'exists' => file_exists($file),
                    'protected' => isset($this->checksums[$file]),
                    'size' => file_exists($file) ? filesize($file) : 0,
                    'modified' => file_exists($file) ? date('Y-m-d H:i:s', filemtime($file)) : null,
                    'protection_level' => $this->getProtectionLevel($file)
                ];
                
                if ($fileStatus['exists'] && $fileStatus['protected']) {
                    $currentChecksum = hash_file('sha256', $file);
                    $fileStatus['integrity'] = $currentChecksum === $this->checksums[$file];
                    if (!$fileStatus['integrity']) {
                        $status['violations']++;
                    }
                    $status['monitored_files']++;
                }
                
                $status['files'][] = $fileStatus;
            }
            
            return $status;
            
        } catch (Exception $e) {
            error_log("Failed to get protection status: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Final integrity check (called on shutdown)
     */
    public function finalIntegrityCheck() {
        $violations = $this->checkIntegrity();
        
        if (!empty($violations)) {
            $this->logToFile("SHUTDOWN_CHECK", "system", 
                "Integrity violations detected: " . count($violations) . " files affected");
            
            foreach ($violations as $violation) {
                if ($violation['severity'] === 'critical') {
                    $this->sendSecurityAlert($violation['file'], $violation['type']);
                }
            }
        }
    }
    
    /**
     * Log to file
     */
    private function logToFile($type, $file, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userId = $_SESSION['user_id'] ?? 'system';
        
        $logEntry = "[$timestamp] [$type] [User:$userId] [IP:$ip] [File:$file] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Clean old protection data
     */
    public function cleanOldData($days = 30) {
        try {
            $stmt = $this->db->query("DELETE FROM file_changes_log WHERE detected_at < DATE_SUB(NOW(), INTERVAL :days DAY)");
            $this->db->execute($stmt, [':days' => $days]);
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to clean old protection data: " . $e->getMessage());
            return false;
        }
    }
}
?>