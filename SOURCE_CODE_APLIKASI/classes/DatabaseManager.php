<?php
require_once __DIR__ . '/../config/database.php';

class DatabaseManager {
    private $db;
    private $backupDir;
    private $migrationDir;
    private $logFile;
    
    public function __construct() {
        $this->db = new Database();
        $this->backupDir = __DIR__ . '/../backups/';
        $this->migrationDir = __DIR__ . '/../migrations/';
        $this->logFile = __DIR__ . '/../logs/database.log';
        
        // Create directories if not exist
        $this->createDirectories();
    }
    
    /**
     * Create necessary directories
     */
    private function createDirectories() {
        $dirs = [$this->backupDir, $this->migrationDir, dirname($this->logFile)];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Create database backup
     */
    public function createBackup($description = '') {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_{$timestamp}.sql";
            $filepath = $this->backupDir . $filename;
            
            // Get database connection details
            $host = DB_HOST;
            $username = DB_USER;
            $password = DB_PASS;
            $database = DB_NAME;
            
            // Create mysqldump command
            $command = "mysqldump --host={$host} --user={$username} --password={$password} --single-transaction --routines --triggers {$database}";
            
            // Execute mysqldump
            $output = shell_exec($command . " 2>&1");
            
            if ($output === null) {
                throw new Exception('Failed to execute mysqldump command');
            }
            
            // Check if output contains error
            if (strpos($output, 'ERROR') !== false || strpos($output, 'mysqldump: ') !== false) {
                throw new Exception('Mysqldump error: ' . $output);
            }
            
            // Write backup to file
            if (file_put_contents($filepath, $output) === false) {
                throw new Exception('Failed to write backup file');
            }
            
            // Create backup info
            $backupInfo = [
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'created_at' => date('Y-m-d H:i:s'),
                'description' => $description,
                'database' => $database,
                'tables' => $this->getTableCount()
            ];
            
            // Save backup info to database
            $this->saveBackupInfo($backupInfo);
            
            // Log success
            $this->log("Backup created successfully: {$filename}");
            
            return [
                'success' => true,
                'message' => 'Backup berhasil dibuat',
                'filename' => $filename,
                'size' => $this->formatBytes($backupInfo['size'])
            ];
            
        } catch (Exception $e) {
            $this->log("Backup failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Backup gagal: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get list of backups
     */
    public function getBackups() {
        try {
            $stmt = $this->db->query("
                SELECT * FROM database_backups 
                ORDER BY created_at DESC 
                LIMIT 20
            ");
            $this->db->execute($stmt);
            $backups = $this->db->resultSet($stmt);
            
            // Check if files still exist
            foreach ($backups as &$backup) {
                $backup['file_exists'] = file_exists($backup['filepath']);
                $backup['size_formatted'] = $this->formatBytes($backup['size']);
            }
            
            return $backups;
            
        } catch (Exception $e) {
            $this->log("Failed to get backups: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Restore database from backup
     */
    public function restoreBackup($backupId) {
        try {
            // Get backup info
            $stmt = $this->db->query("SELECT * FROM database_backups WHERE id = :id");
            $this->db->execute($stmt, [':id' => $backupId]);
            $backup = $this->db->single($stmt);
            
            if (!$backup) {
                throw new Exception('Backup not found');
            }
            
            if (!file_exists($backup['filepath'])) {
                throw new Exception('Backup file not found');
            }
            
            // Read backup file
            $sql = file_get_contents($backup['filepath']);
            if ($sql === false) {
                throw new Exception('Failed to read backup file');
            }
            
            // Execute restore
            $this->db->getConnection()->exec($sql);
            
            $this->log("Database restored from backup: {$backup['filename']}");
            
            return [
                'success' => true,
                'message' => 'Database berhasil direstore'
            ];
            
        } catch (Exception $e) {
            $this->log("Restore failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Restore gagal: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Run database migrations
     */
    public function runMigrations() {
        try {
            // Create migrations table if not exists
            $this->createMigrationsTable();
            
            // Get migration files
            $migrationFiles = $this->getMigrationFiles();
            $executedMigrations = $this->getExecutedMigrations();
            
            $newMigrations = [];
            
            foreach ($migrationFiles as $file) {
                $filename = basename($file);
                
                // Skip if already executed
                if (in_array($filename, $executedMigrations)) {
                    continue;
                }
                
                // Execute migration
                $result = $this->executeMigration($file);
                if ($result['success']) {
                    $newMigrations[] = $filename;
                    $this->log("Migration executed: {$filename}");
                } else {
                    throw new Exception("Migration failed: {$filename} - " . $result['message']);
                }
            }
            
            if (empty($newMigrations)) {
                return [
                    'success' => true,
                    'message' => 'Tidak ada migration baru untuk dijalankan',
                    'migrations' => []
                ];
            }
            
            return [
                'success' => true,
                'message' => count($newMigrations) . ' migration berhasil dijalankan',
                'migrations' => $newMigrations
            ];
            
        } catch (Exception $e) {
            $this->log("Migration failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Migration gagal: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get migration status
     */
    public function getMigrationStatus() {
        try {
            $this->createMigrationsTable();
            
            $migrationFiles = $this->getMigrationFiles();
            $executedMigrations = $this->getExecutedMigrations();
            
            $status = [];
            foreach ($migrationFiles as $file) {
                $filename = basename($file);
                $status[] = [
                    'filename' => $filename,
                    'executed' => in_array($filename, $executedMigrations),
                    'created_at' => date('Y-m-d H:i:s', filemtime($file))
                ];
            }
            
            return $status;
            
        } catch (Exception $e) {
            $this->log("Failed to get migration status: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create migrations table
     */
    private function createMigrationsTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS database_migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_filename (filename)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->db->getConnection()->exec($sql);
        
        // Also create backups table
        $sql = "
            CREATE TABLE IF NOT EXISTS database_backups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL,
                filepath VARCHAR(500) NOT NULL,
                size BIGINT NOT NULL,
                description TEXT,
                database_name VARCHAR(100) NOT NULL,
                tables_count INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->db->getConnection()->exec($sql);
    }
    
    /**
     * Get migration files
     */
    private function getMigrationFiles() {
        $files = glob($this->migrationDir . '*.sql');
        sort($files);
        return $files;
    }
    
    /**
     * Get executed migrations
     */
    private function getExecutedMigrations() {
        try {
            $stmt = $this->db->query("SELECT filename FROM database_migrations ORDER BY executed_at");
            $this->db->execute($stmt);
            $results = $this->db->resultSet($stmt);
            
            return array_column($results, 'filename');
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Execute single migration
     */
    private function executeMigration($filepath) {
        try {
            $filename = basename($filepath);
            $sql = file_get_contents($filepath);
            
            if ($sql === false) {
                throw new Exception('Failed to read migration file');
            }
            
            // Execute SQL
            $this->db->getConnection()->exec($sql);
            
            // Record migration
            $stmt = $this->db->query("INSERT INTO database_migrations (filename) VALUES (:filename)");
            $this->db->execute($stmt, [':filename' => $filename]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Save backup info to database
     */
    private function saveBackupInfo($info) {
        $stmt = $this->db->query("
            INSERT INTO database_backups 
            (filename, filepath, size, description, database_name, tables_count) 
            VALUES (:filename, :filepath, :size, :description, :database, :tables)
        ");
        
        $this->db->execute($stmt, [
            ':filename' => $info['filename'],
            ':filepath' => $info['filepath'],
            ':size' => $info['size'],
            ':description' => $info['description'],
            ':database' => $info['database'],
            ':tables' => $info['tables']
        ]);
    }
    
    /**
     * Get table count
     */
    private function getTableCount() {
        try {
            $stmt = $this->db->query("SHOW TABLES");
            $this->db->execute($stmt);
            return count($this->db->resultSet($stmt));
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Log message
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Clean old backups (keep last 10)
     */
    public function cleanOldBackups() {
        try {
            $stmt = $this->db->query("
                SELECT * FROM database_backups 
                ORDER BY created_at DESC 
                LIMIT 10, 1000
            ");
            $this->db->execute($stmt);
            $oldBackups = $this->db->resultSet($stmt);
            
            $deleted = 0;
            foreach ($oldBackups as $backup) {
                // Delete file
                if (file_exists($backup['filepath'])) {
                    unlink($backup['filepath']);
                }
                
                // Delete record
                $stmt = $this->db->query("DELETE FROM database_backups WHERE id = :id");
                $this->db->execute($stmt, [':id' => $backup['id']]);
                
                $deleted++;
            }
            
            if ($deleted > 0) {
                $this->log("Cleaned {$deleted} old backups");
            }
            
            return [
                'success' => true,
                'deleted' => $deleted
            ];
            
        } catch (Exception $e) {
            $this->log("Failed to clean old backups: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
?>