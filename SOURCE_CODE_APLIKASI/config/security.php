<?php
/**
 * Security Protection Layer
 * Additional protection for critical system components
 */

// File integrity monitoring
class SecurityMonitor {
    private static $protectedFiles = [
        'config/copyright.php',
        'includes/footer.php'
    ];
    
    private static $checksums = [
        'config/copyright.php' => null, // Will be calculated on first run
        'includes/footer.php' => null
    ];
    
    /**
     * Check file integrity
     */
    public static function checkIntegrity() {
        foreach (self::$protectedFiles as $file) {
            if (file_exists($file)) {
                $currentHash = md5_file($file);
                $storedHash = self::$checksums[$file];
                
                if ($storedHash && $currentHash !== $storedHash) {
                    error_log("Security Alert: Protected file {$file} has been modified");
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * Initialize checksums
     */
    public static function initializeChecksums() {
        foreach (self::$protectedFiles as $file) {
            if (file_exists($file)) {
                self::$checksums[$file] = md5_file($file);
            }
        }
    }
}

// Initialize security monitoring
SecurityMonitor::initializeChecksums();

// Runtime protection
register_shutdown_function(function() {
    if (!SecurityMonitor::checkIntegrity()) {
        // Log security breach
        error_log("SECURITY BREACH: Protected copyright files have been tampered with");
    }
});
?>