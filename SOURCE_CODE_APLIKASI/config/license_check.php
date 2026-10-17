<?php
/**
 * License Check - Must be included in every page
 * This file validates the application license before allowing access
 */

// Prevent direct access
if (!defined('LICENSE_CHECK_INCLUDED')) {
    define('LICENSE_CHECK_INCLUDED', true);
}

// Only check license if LicenseManager class exists
if (file_exists(__DIR__ . '/../classes/LicenseManager.php')) {
    require_once __DIR__ . '/../classes/LicenseManager.php';
    
    // Initialize license manager
    try {
        $licenseManager = new LicenseManager();
        
        // Check if we're on license activation page or other exempt pages
        $currentScript = basename($_SERVER['PHP_SELF']);
        $exemptPages = [
            'license_activation.php', 
            'license_manager.php',
            'index.php',
            'login.php',
            'register.php',
            'track.php'
        ];
        
        $isExemptPage = in_array($currentScript, $exemptPages) || 
                       strpos($_SERVER['REQUEST_URI'], '/public/') !== false;
        
        // Skip license check for exempt pages and if not logged in
        if (!$isExemptPage && function_exists('isLoggedIn') && isLoggedIn()) {
            // Validate license only for logged in users on protected pages
            if (!$licenseManager->canApplicationRun()) {
                // Redirect to license activation
                $licenseInfo = $licenseManager->getLicenseInfo();
                
                if (!$licenseInfo) {
                    // No license found
                    header('Location: ' . (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || strpos($_SERVER['REQUEST_URI'], '/customer/') !== false ? '../' : '') . 'license_activation.php');
                    exit();
                } elseif ($licenseInfo['is_expired']) {
                    // License expired
                    header('Location: ' . (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || strpos($_SERVER['REQUEST_URI'], '/customer/') !== false ? '../' : '') . 'license_activation.php?expired=1');
                    exit();
                } else {
                    // Other license issues
                    header('Location: ' . (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || strpos($_SERVER['REQUEST_URI'], '/customer/') !== false ? '../' : '') . 'license_activation.php?error=1');
                    exit();
                }
            }
            
            // Track license usage
            try {
                $licenseInfo = $licenseManager->getLicenseInfo();
                if ($licenseInfo) {
                    $db = new Database();
                    
                    // Update daily usage stats
                    $stmt = $db->query("
                        INSERT INTO license_usage_stats (license_key, stat_date, total_logins) 
                        VALUES (:license_key, CURDATE(), 1)
                        ON DUPLICATE KEY UPDATE 
                        total_logins = total_logins + 1
                    ");
                    $db->execute($stmt, [':license_key' => $licenseInfo['license_type']]);
                }
            } catch (Exception $e) {
                error_log("Failed to update license usage: " . $e->getMessage());
            }
        }
        
    } catch (Exception $e) {
        error_log("License system error: " . $e->getMessage());
        // Don't block application if license system has errors
    }
}
?>