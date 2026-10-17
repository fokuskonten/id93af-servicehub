<?php
require_once __DIR__ . '/../config/database.php';

class LicenseManager {
    private $db;
    private $licenseFile;
    private $keyFile;
    private $logFile;
    private $encryptionKey;
    private $validationUrl;
    
    public function __construct() {
        $this->db = new Database();
        $this->licenseFile = __DIR__ . '/../license/license.key';
        $this->keyFile = __DIR__ . '/../license/keys/app.key';
        $this->logFile = __DIR__ . '/../license/logs/license.log';
        $this->encryptionKey = 'TECHFIX_PRO_2024_LICENSE_ENCRYPTION_KEY_SEGITIGA_CREATIVE';
        $this->validationUrl = 'https://segitigacreative.tech/api/validate-license';
        
        // Create license tables
        $this->createLicenseTables();
        
        // Create directories
        $this->createDirectories();
    }
    
    /**
     * Create necessary directories
     */
    private function createDirectories() {
        $dirs = [
            dirname($this->licenseFile),
            dirname($this->keyFile),
            dirname($this->logFile)
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Create license tables
     */
    private function createLicenseTables() {
        try {
            // License information table
            $sql = "
                CREATE TABLE IF NOT EXISTS license_info (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    license_key VARCHAR(255) NOT NULL UNIQUE,
                    license_type ENUM('trial', 'basic', 'premium', 'enterprise') NOT NULL DEFAULT 'trial',
                    customer_name VARCHAR(255) NOT NULL,
                    customer_email VARCHAR(255) NOT NULL,
                    company_name VARCHAR(255),
                    purchase_date DATE NOT NULL,
                    expiry_date DATE NOT NULL,
                    max_users INT DEFAULT 5,
                    max_services INT DEFAULT 100,
                    features JSON,
                    is_active TINYINT(1) DEFAULT 1,
                    activation_date TIMESTAMP NULL,
                    last_validation TIMESTAMP NULL,
                    validation_count INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_license_key (license_key),
                    INDEX idx_expiry_date (expiry_date),
                    INDEX idx_is_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $this->db->getConnection()->exec($sql);
            
            // License validation log table
            $sql = "
                CREATE TABLE IF NOT EXISTS license_validation_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    license_key VARCHAR(255) NOT NULL,
                    validation_type ENUM('startup', 'periodic', 'manual', 'api') NOT NULL,
                    validation_result ENUM('valid', 'expired', 'invalid', 'error') NOT NULL,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    server_info JSON,
                    error_message TEXT,
                    response_time INT,
                    validated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_license_key (license_key),
                    INDEX idx_validation_result (validation_result),
                    INDEX idx_validated_at (validated_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $this->db->getConnection()->exec($sql);
            
            // License usage statistics table
            $sql = "
                CREATE TABLE IF NOT EXISTS license_usage_stats (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    license_key VARCHAR(255) NOT NULL,
                    stat_date DATE NOT NULL,
                    total_users INT DEFAULT 0,
                    total_services INT DEFAULT 0,
                    total_logins INT DEFAULT 0,
                    total_actions INT DEFAULT 0,
                    peak_concurrent_users INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_license_date (license_key, stat_date),
                    INDEX idx_license_key (license_key),
                    INDEX idx_stat_date (stat_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $this->db->getConnection()->exec($sql);
            
        } catch (Exception $e) {
            error_log("Failed to create license tables: " . $e->getMessage());
        }
    }
    
    /**
     * Validate license
     */
    public function validateLicense($showErrors = true) {
        try {
            // Check if license file exists
            if (!file_exists($this->licenseFile)) {
                if ($showErrors) {
                    $this->showLicenseError('License file not found', 'MISSING_LICENSE');
                }
                return false;
            }
            
            // Read and decrypt license
            $encryptedLicense = file_get_contents($this->licenseFile);
            $licenseData = $this->decryptLicense($encryptedLicense);
            
            if (!$licenseData) {
                if ($showErrors) {
                    $this->showLicenseError('Invalid license format', 'INVALID_FORMAT');
                }
                return false;
            }
            
            // Validate license data
            $validation = $this->validateLicenseData($licenseData);
            
            // Log validation attempt
            $this->logValidation($licenseData['license_key'] ?? 'unknown', 'startup', $validation['result'], $validation['error'] ?? null);
            
            if (!$validation['valid']) {
                if ($showErrors) {
                    $this->showLicenseError($validation['error'], $validation['code']);
                }
                return false;
            }
            
            // Update last validation
            $this->updateLastValidation($licenseData['license_key']);
            
            return $licenseData;
            
        } catch (Exception $e) {
            error_log("License validation error: " . $e->getMessage());
            if ($showErrors) {
                $this->showLicenseError('License validation failed: ' . $e->getMessage(), 'VALIDATION_ERROR');
            }
            return false;
        }
    }
    
    /**
     * Activate license with token
     */
    public function activateLicense($token, $customerInfo) {
        try {
            // Validate token format
            if (!$this->isValidTokenFormat($token)) {
                return ['success' => false, 'message' => 'Format token tidak valid'];
            }
            
            // Validate with remote server (if available)
            $remoteValidation = $this->validateTokenRemote($token, $customerInfo);
            
            if (!$remoteValidation['valid']) {
                return ['success' => false, 'message' => $remoteValidation['error']];
            }
            
            // Generate license data
            $licenseData = [
                'license_key' => $token,
                'license_type' => $remoteValidation['type'] ?? 'basic',
                'customer_name' => $customerInfo['name'],
                'customer_email' => $customerInfo['email'],
                'company_name' => $customerInfo['company'] ?? '',
                'purchase_date' => date('Y-m-d'),
                'expiry_date' => $remoteValidation['expiry_date'] ?? date('Y-m-d', strtotime('+1 year')),
                'max_users' => $remoteValidation['max_users'] ?? 5,
                'max_services' => $remoteValidation['max_services'] ?? 1000,
                'features' => $remoteValidation['features'] ?? ['basic_features'],
                'server_info' => [
                    'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
                    'ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
                    'php_version' => phpversion(),
                    'activation_time' => time()
                ]
            ];
            
            // Encrypt and save license
            $encryptedLicense = $this->encryptLicense($licenseData);
            if (file_put_contents($this->licenseFile, $encryptedLicense) === false) {
                return ['success' => false, 'message' => 'Gagal menyimpan license file'];
            }
            
            // Save to database
            $this->saveLicenseToDatabase($licenseData);
            
            // Log activation
            $this->logToFile("LICENSE_ACTIVATED", $token, "License activated for: " . $customerInfo['name']);
            
            return [
                'success' => true, 
                'message' => 'License berhasil diaktivasi',
                'license_type' => $licenseData['license_type'],
                'expiry_date' => $licenseData['expiry_date']
            ];
            
        } catch (Exception $e) {
            error_log("License activation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal mengaktivasi license: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check if application can run
     */
    public function canApplicationRun() {
        $license = $this->validateLicense(false);
        
        if (!$license) {
            return false;
        }
        
        // Check expiry
        if (strtotime($license['expiry_date']) < time()) {
            return false;
        }
        
        // Check usage limits
        if (!$this->checkUsageLimits($license)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get license information
     */
    public function getLicenseInfo() {
        $license = $this->validateLicense(false);
        
        if (!$license) {
            return null;
        }
        
        return [
            'license_type' => $license['license_type'],
            'customer_name' => $license['customer_name'],
            'company_name' => $license['company_name'],
            'expiry_date' => $license['expiry_date'],
            'days_remaining' => max(0, floor((strtotime($license['expiry_date']) - time()) / 86400)),
            'max_users' => $license['max_users'],
            'max_services' => $license['max_services'],
            'features' => $license['features'],
            'is_expired' => strtotime($license['expiry_date']) < time()
        ];
    }
    
    /**
     * Encrypt license data
     */
    private function encryptLicense($data) {
        $jsonData = json_encode($data);
        $encrypted = openssl_encrypt($jsonData, 'AES-256-CBC', $this->encryptionKey, 0, substr(md5($this->encryptionKey), 0, 16));
        return base64_encode($encrypted);
    }
    
    /**
     * Decrypt license data
     */
    private function decryptLicense($encryptedData) {
        try {
            $encrypted = base64_decode($encryptedData);
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->encryptionKey, 0, substr(md5($this->encryptionKey), 0, 16));
            return json_decode($decrypted, true);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Validate license data
     */
    private function validateLicenseData($licenseData) {
        // Check required fields
        $requiredFields = ['license_key', 'customer_name', 'customer_email', 'expiry_date'];
        foreach ($requiredFields as $field) {
            if (!isset($licenseData[$field]) || empty($licenseData[$field])) {
                return ['valid' => false, 'error' => "Missing required field: $field", 'code' => 'MISSING_FIELD'];
            }
        }
        
        // Check expiry date
        if (strtotime($licenseData['expiry_date']) < time()) {
            return ['valid' => false, 'error' => 'License has expired', 'code' => 'EXPIRED'];
        }
        
        // Check server binding (optional)
        if (isset($licenseData['server_info']['domain'])) {
            $currentDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
            if ($licenseData['server_info']['domain'] !== $currentDomain && $licenseData['server_info']['domain'] !== 'localhost') {
                // Allow localhost for development
                if ($currentDomain !== 'localhost' && $currentDomain !== '127.0.0.1') {
                    return ['valid' => false, 'error' => 'License not valid for this domain', 'code' => 'DOMAIN_MISMATCH'];
                }
            }
        }
        
        return ['valid' => true, 'result' => 'valid'];
    }
    
    /**
     * Check usage limits
     */
    private function checkUsageLimits($license) {
        try {
            // Check user count
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
            $this->db->execute($stmt);
            $userCount = $this->db->single($stmt)['count'];
            
            if ($userCount > $license['max_users']) {
                return false;
            }
            
            // Check service count (this month)
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM services WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
            $this->db->execute($stmt);
            $serviceCount = $this->db->single($stmt)['count'];
            
            if ($serviceCount > $license['max_services']) {
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to check usage limits: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate token format
     */
    private function isValidTokenFormat($token) {
        // Token format: TFPRO-XXXXX-XXXXX-XXXXX-XXXXX (flexible length 3-6 chars per segment)
        return preg_match('/^TFPRO-[A-Z0-9]{3,6}-[A-Z0-9]{3,6}-[A-Z0-9]{3,6}-[A-Z0-9]{3,6}$/', $token);
    }
    
    /**
     * Validate token with remote server
     */
    private function validateTokenRemote($token, $customerInfo) {
        try {
            // Prepare validation data
            $validationData = [
                'token' => $token,
                'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
                'ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
                'customer_email' => $customerInfo['email'],
                'php_version' => phpversion(),
                'app_version' => '1.0.0'
            ];
            
            // For demo purposes, we'll simulate remote validation
            // In production, this would make an actual HTTP request to validation server
            
            // Simulate different token types
            if (strpos($token, 'TFPRO-TRIAL') === 0) {
                return [
                    'valid' => true,
                    'type' => 'trial',
                    'expiry_date' => date('Y-m-d', strtotime('+30 days')),
                    'max_users' => 2,
                    'max_services' => 50,
                    'features' => ['basic_features']
                ];
            } elseif (strpos($token, 'TFPRO-BASIC') === 0) {
                return [
                    'valid' => true,
                    'type' => 'basic',
                    'expiry_date' => date('Y-m-d', strtotime('+1 year')),
                    'max_users' => 5,
                    'max_services' => 500,
                    'features' => ['basic_features', 'reports']
                ];
            } elseif (strpos($token, 'TFPRO-PREMI') === 0) {
                return [
                    'valid' => true,
                    'type' => 'premium',
                    'expiry_date' => date('Y-m-d', strtotime('+1 year')),
                    'max_users' => 20,
                    'max_services' => 2000,
                    'features' => ['basic_features', 'reports', 'advanced_tracking', 'api_access']
                ];
            } elseif (preg_match('/^TFPRO-[A-Z0-9]{4,6}-[A-Z0-9]{4,6}-[A-Z0-9]{4,6}-[A-Z0-9]{4,6}$/', $token)) {
                // Generic valid token format - default to basic
                return [
                    'valid' => true,
                    'type' => 'basic',
                    'expiry_date' => date('Y-m-d', strtotime('+1 year')),
                    'max_users' => 5,
                    'max_services' => 500,
                    'features' => ['basic_features', 'reports']
                ];
            } else {
                return [
                    'valid' => false,
                    'error' => 'Token tidak valid atau tidak terdaftar'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => 'Gagal validasi dengan server: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Save license to database
     */
    private function saveLicenseToDatabase($licenseData) {
        try {
            $stmt = $this->db->query("
                INSERT INTO license_info 
                (license_key, license_type, customer_name, customer_email, company_name, 
                 purchase_date, expiry_date, max_users, max_services, features, activation_date) 
                VALUES (:license_key, :license_type, :customer_name, :customer_email, :company_name,
                        :purchase_date, :expiry_date, :max_users, :max_services, :features, NOW())
                ON DUPLICATE KEY UPDATE
                license_type = VALUES(license_type),
                customer_name = VALUES(customer_name),
                customer_email = VALUES(customer_email),
                company_name = VALUES(company_name),
                expiry_date = VALUES(expiry_date),
                max_users = VALUES(max_users),
                max_services = VALUES(max_services),
                features = VALUES(features),
                updated_at = NOW()
            ");
            
            $this->db->execute($stmt, [
                ':license_key' => $licenseData['license_key'],
                ':license_type' => $licenseData['license_type'],
                ':customer_name' => $licenseData['customer_name'],
                ':customer_email' => $licenseData['customer_email'],
                ':company_name' => $licenseData['company_name'],
                ':purchase_date' => $licenseData['purchase_date'],
                ':expiry_date' => $licenseData['expiry_date'],
                ':max_users' => $licenseData['max_users'],
                ':max_services' => $licenseData['max_services'],
                ':features' => json_encode($licenseData['features'])
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to save license to database: " . $e->getMessage());
        }
    }
    
    /**
     * Update last validation time
     */
    private function updateLastValidation($licenseKey) {
        try {
            $stmt = $this->db->query("
                UPDATE license_info 
                SET last_validation = NOW(), validation_count = validation_count + 1 
                WHERE license_key = :license_key
            ");
            $this->db->execute($stmt, [':license_key' => $licenseKey]);
        } catch (Exception $e) {
            error_log("Failed to update last validation: " . $e->getMessage());
        }
    }
    
    /**
     * Log validation attempt
     */
    private function logValidation($licenseKey, $type, $result, $error = null) {
        try {
            $stmt = $this->db->query("
                INSERT INTO license_validation_log 
                (license_key, validation_type, validation_result, ip_address, user_agent, server_info, error_message) 
                VALUES (:license_key, :validation_type, :validation_result, :ip_address, :user_agent, :server_info, :error_message)
            ");
            
            $serverInfo = [
                'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown',
                'php_version' => phpversion(),
                'timestamp' => time()
            ];
            
            $this->db->execute($stmt, [
                ':license_key' => $licenseKey,
                ':validation_type' => $type,
                ':validation_result' => $result,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                ':server_info' => json_encode($serverInfo),
                ':error_message' => $error
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to log validation: " . $e->getMessage());
        }
    }
    
    /**
     * Show license error page
     */
    private function showLicenseError($message, $code) {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>License Required - TechFix Pro</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                body {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                .license-card {
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                .license-header {
                    background: linear-gradient(135deg, #dc3545, #c82333);
                    color: white;
                    padding: 2rem;
                    text-align: center;
                }
                .license-icon {
                    font-size: 4rem;
                    margin-bottom: 1rem;
                    opacity: 0.9;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="license-card">
                            <div class="license-header">
                                <i class="fas fa-lock license-icon"></i>
                                <h2>License Required</h2>
                                <p class="mb-0">TechFix Pro memerlukan license yang valid</p>
                            </div>
                            <div class="p-4">
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Error:</strong> <?php echo htmlspecialchars($message); ?>
                                    <br><small>Code: <?php echo htmlspecialchars($code); ?></small>
                                </div>
                                
                                <h5>Untuk menggunakan aplikasi ini:</h5>
                                <ol>
                                    <li>Beli license di <a href="https://segitigacreative.tech" target="_blank" class="text-decoration-none">segitigacreative.tech</a></li>
                                    <li>Dapatkan token license Anda</li>
                                    <li>Aktivasi melalui halaman license</li>
                                </ol>
                                
                                <div class="d-grid gap-2 mt-4">
                                    <a href="license_activation.php" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Aktivasi License
                                    </a>
                                    <a href="https://segitigacreative.tech" target="_blank" class="btn btn-outline-primary">
                                        <i class="fas fa-shopping-cart me-2"></i>Beli License
                                    </a>
                                </div>
                                
                                <hr class="my-4">
                                
                                <div class="text-center">
                                    <small class="text-muted">
                                        © <?php echo date('Y'); ?> <a href="https://segitigacreative.tech" target="_blank" class="text-decoration-none">Segitiga Creative</a>. All rights reserved.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
    
    /**
     * Log to file
     */
    private function logToFile($type, $token, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $logEntry = "[$timestamp] [$type] [Token:$token] [IP:$ip] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Generate demo tokens for testing
     */
    public function generateDemoTokens() {
        return [
            'trial' => 'TFPRO-TRIAL-DEMO1-TEST1-TRIAL',
            'basic' => 'TFPRO-BASIC-DEMO2-TEST2-BASIC',
            'premium' => 'TFPRO-PREMI-DEMO3-TEST3-PREMI'
        ];
    }
    
    /**
     * Get license statistics
     */
    public function getLicenseStats() {
        try {
            $stats = [];
            
            // Total validations today
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM license_validation_log WHERE DATE(validated_at) = CURDATE()");
            $this->db->execute($stmt);
            $stats['validations_today'] = $this->db->single($stmt)['count'];
            
            // Failed validations today
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM license_validation_log WHERE DATE(validated_at) = CURDATE() AND validation_result != 'valid'");
            $this->db->execute($stmt);
            $stats['failed_today'] = $this->db->single($stmt)['count'];
            
            // License info
            $license = $this->getLicenseInfo();
            if ($license) {
                $stats['license_type'] = $license['license_type'];
                $stats['days_remaining'] = $license['days_remaining'];
                $stats['is_expired'] = $license['is_expired'];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Failed to get license stats: " . $e->getMessage());
            return [];
        }
    }
}
?>