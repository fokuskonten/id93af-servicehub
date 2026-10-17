<?php
/**
 * Protected Copyright System
 * DO NOT MODIFY THIS FILE
 */

class CopyrightProtection {
    // Encrypted copyright data
    private static $encryptedData = 'U2VnaXRpZ2EgQ3JlYXRpdmU='; // Base64 encoded
    private static $key = 'TF2024_SECURE_COPYRIGHT_KEY_PROTECTION';
    private static $salt = 'segitiga_creative_2024_salt';
    
    /**
     * Get protected copyright text
     * @return string
     */
    public static function getCopyrightText() {
        try {
            // Decode base64
            $decoded = base64_decode(self::$encryptedData);
            
            // Verify integrity
            if (self::verifyIntegrity($decoded)) {
                return $decoded;
            }
            
            // Fallback if tampered
            return self::getFallbackText();
        } catch (Exception $e) {
            return self::getFallbackText();
        }
    }
    
    /**
     * Get copyright year
     * @return string
     */
    public static function getCopyrightYear() {
        return date('Y');
    }
    
    /**
     * Get full copyright notice
     * @return string
     */
    public static function getFullCopyright() {
        return '&copy; ' . self::getCopyrightYear() . ' <a href="https://segitigacreative.tech" target="_blank" class="text-light text-decoration-none">' . self::getCopyrightText() . '</a>. All rights reserved.';
    }
    
    /**
     * Get copyright link URL
     * @return string
     */
    public static function getCopyrightUrl() {
        // Encrypted URL - Base64 encoded
        $encryptedUrl = 'aHR0cHM6Ly9zZWdpdGlnYWNyZWF0aXZlLnRlY2g='; // https://segitigacreative.tech
        
        try {
            $decoded = base64_decode($encryptedUrl);
            
            // Verify URL integrity
            if (self::verifyUrlIntegrity($decoded)) {
                return $decoded;
            }
            
            // Fallback if tampered
            return self::getFallbackUrl();
        } catch (Exception $e) {
            return self::getFallbackUrl();
        }
    }
    
    /**
     * Get copyright as clickable link
     * @return string
     */
    public static function getCopyrightLink() {
        $url = self::getCopyrightUrl();
        $text = self::getCopyrightText();
        return '<a href="' . $url . '" target="_blank" class="text-light text-decoration-none" title="Visit ' . $text . '">' . $text . '</a>';
    }
    
    /**
     * Verify data integrity
     * @param string $data
     * @return bool
     */
    private static function verifyIntegrity($data) {
        $expectedHash = hash('sha256', $data . self::$salt);
        $storedHash = hash('sha256', 'Segitiga Creative' . self::$salt);
        return hash_equals($expectedHash, $storedHash);
    }
    
    /**
     * Verify URL integrity
     * @param string $url
     * @return bool
     */
    private static function verifyUrlIntegrity($url) {
        $expectedHash = hash('sha256', $url . self::$salt);
        $storedHash = hash('sha256', 'https://segitigacreative.tech' . self::$salt);
        return hash_equals($expectedHash, $storedHash);
    }
    
    /**
     * Fallback URL if system is compromised
     * @return string
     */
    private static function getFallbackUrl() {
        // Multiple layers of obfuscation
        $parts = ['https://', 'segitiga', 'creative', '.tech'];
        return implode('', $parts);
    }
    
    /**
     * Fallback text if system is compromised
     * @return string
     */
    private static function getFallbackText() {
        // Multiple layers of obfuscation
        $parts = ['Seg', 'iti', 'ga ', 'Cre', 'ati', 've'];
        return implode('', $parts);
    }
    
    /**
     * Check if system is tampered
     * @return bool
     */
    public static function isSystemIntact() {
        $checksum = md5(self::$encryptedData . self::$key . self::$salt);
        $expected = md5('U2VnaXRpZ2EgQ3JlYXRpdmU=' . 'TF2024_SECURE_COPYRIGHT_KEY_PROTECTION' . 'segitiga_creative_2024_salt');
        return hash_equals($checksum, $expected);
    }
}

// Additional protection functions
function getProtectedCopyright() {
    return CopyrightProtection::getFullCopyright();
}

function getCopyrightOwner() {
    return CopyrightProtection::getCopyrightText();
}

function validateCopyrightIntegrity() {
    return CopyrightProtection::isSystemIntact();
}

// Anti-tampering check
if (!validateCopyrightIntegrity()) {
    error_log('Copyright protection system may have been tampered with');
}
?>