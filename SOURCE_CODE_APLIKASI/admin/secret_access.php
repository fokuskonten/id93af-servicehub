<?php
/**
 * Secret Access Gateway
 * Hidden entry point for license generator
 */

require_once '../config/database.php';
require_once '../config/session.php';

// Require admin login
requireAdmin();

// Secret access codes (change these to your own secret codes)
$SECRET_CODES = [
    'master_key' => 'SEGITIGA_CREATIVE_2024_MASTER_ACCESS',
    'license_key' => 'TECHFIX_PRO_LICENSE_GENERATOR_SECRET',
    'admin_key' => 'ADMIN_SUPER_SECRET_ACCESS_KEY_2024'
];

// Check for secret parameter
$secretParam = $_GET['secret'] ?? '';
$accessCode = $_GET['code'] ?? '';
$verify = $_GET['verify'] ?? '';

// Multi-layer verification
$isValidAccess = false;

// Layer 1: Secret parameter check
if ($secretParam === 'license_gen_access_2024') {
    // Layer 2: Access code check
    if (in_array($accessCode, $SECRET_CODES)) {
        // Layer 3: Verification hash check
        $expectedHash = md5($accessCode . getCurrentUserId() . date('Y-m-d'));
        if ($verify === $expectedHash) {
            $isValidAccess = true;
        }
    }
}

// Alternative access method: Special URL pattern
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, 'admin/secret_access.php') !== false) {
    // Check for special access pattern in URL
    if (isset($_GET['x']) && isset($_GET['y']) && isset($_GET['z'])) {
        $x = $_GET['x'];
        $y = $_GET['y']; 
        $z = $_GET['z'];
        
        // Secret formula: x + y = z and x * y = specific number
        if (is_numeric($x) && is_numeric($y) && is_numeric($z)) {
            if (($x + $y) == $z && ($x * $y) == 2024) {
                $isValidAccess = true;
            }
        }
    }
}

// If access is not valid, show fake 404 page
if (!$isValidAccess) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
        <title>404 Not Found</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error { color: #666; }
        </style>
    </head>
    <body>
        <h1>404 Not Found</h1>
        <p class="error">The requested page could not be found.</p>
        <a href="../dashboard.php">Back to Dashboard</a>
    </body>
    </html>
    <?php
    exit();
}

// If valid access, redirect to license generator with special token
$specialToken = base64_encode(json_encode([
    'user_id' => getCurrentUserId(),
    'timestamp' => time(),
    'access_granted' => true,
    'hash' => md5('license_generator_access_' . getCurrentUserId() . time())
]));

// Set session flag for license generator access
$_SESSION['license_generator_access'] = $specialToken;
$_SESSION['license_access_time'] = time();

// Redirect to license generator
header('Location: license_generator.php?access_token=' . urlencode($specialToken));
exit();
?>