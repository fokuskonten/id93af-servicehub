<?php
/**
 * Hidden Access Instructions
 * This file contains secret access methods - DO NOT SHARE
 */

require_once '../config/database.php';
require_once '../config/session.php';

// Require admin login
requireAdmin();

$page_title = 'Hidden Access Methods';

// Check if user is super admin (user ID 1)
if (getCurrentUserId() != 1) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/svg+xml" href="favicon.svg"><title>404 Not Found</title></head>
    <body>
        <h1>404 Not Found</h1>
        <p>The requested page could not be found.</p>
    </body>
    </html>
    <?php
    exit();
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>TOP SECRET:</strong> Jangan bagikan informasi ini kepada siapapun!
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user-secret me-2"></i>Hidden Access Methods
                </h5>
            </div>
            <div class="card-body">
                <h6 class="text-danger">🔒 SECRET ACCESS METHODS:</h6>
                
                <!-- Method 1: Secret Gateway -->
                <div class="card mb-3 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <strong>Method 1: Secret Gateway</strong>
                    </div>
                    <div class="card-body">
                        <p><strong>URL:</strong></p>
                        <code class="d-block bg-light p-2 mb-2">
                            /admin/secret_access.php?secret=license_gen_access_2024&code=SEGITIGA_CREATIVE_2024_MASTER_ACCESS&verify=<?php echo md5('SEGITIGA_CREATIVE_2024_MASTER_ACCESS' . getCurrentUserId() . date('Y-m-d')); ?>
                        </code>
                        <small class="text-muted">Verify hash berubah setiap hari</small>
                    </div>
                </div>
                
                <!-- Method 2: Math Formula -->
                <div class="card mb-3 border-info">
                    <div class="card-header bg-info text-white">
                        <strong>Method 2: Math Formula</strong>
                    </div>
                    <div class="card-body">
                        <p><strong>URL Pattern:</strong></p>
                        <code class="d-block bg-light p-2 mb-2">
                            /admin/secret_access.php?x=44&y=46&z=90
                        </code>
                        <p><strong>Formula:</strong> x + y = z AND x * y = 2024</p>
                        <p><strong>Valid Combinations:</strong></p>
                        <ul>
                            <li>x=44, y=46, z=90 (44+46=90, 44*46=2024)</li>
                            <li>x=46, y=44, z=90 (46+44=90, 46*44=2024)</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Method 3: Ultra Secret -->
                <div class="card mb-3 border-danger">
                    <div class="card-header bg-danger text-white">
                        <strong>Method 3: Ultra Secret Generator</strong>
                    </div>
                    <div class="card-body">
                        <p><strong>URL:</strong></p>
                        <code class="d-block bg-light p-2 mb-2">
                            /admin/secret_generator.php?key=SEGITIGA2024&hash=<?php echo md5('TECHFIX_PRO_' . getCurrentUserId() . '_' . date('Ymd')); ?>&user=<?php echo getCurrentUserId(); ?>
                        </code>
                        <p><strong>Features:</strong></p>
                        <ul>
                            <li>Matrix-style interface</li>
                            <li>Bulk generation (500+500)</li>
                            <li>Real-time stats</li>
                            <li>Export capabilities</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Method 4: Direct Database -->
                <div class="card mb-3 border-success">
                    <div class="card-header bg-success text-white">
                        <strong>Method 4: Direct Database Access</strong>
                    </div>
                    <div class="card-body">
                        <p><strong>SQL Commands:</strong></p>
                        <pre class="bg-light p-2"><code>-- Generate 500 Monthly Licenses
INSERT INTO generated_licenses 
(license_key, license_type, duration_months, max_users, max_services, features, price, currency, status, generated_at, generated_by)
SELECT 
    CONCAT('TFMON-', DATE_FORMAT(NOW(), '%y%m%d'), '-', 
           UPPER(SUBSTRING(MD5(RAND()), 1, 5)), '-',
           UPPER(SUBSTRING(MD5(RAND()), 1, 5)), '-',
           LPAD(@row_number := @row_number + 1, 5, '0')) as license_key,
    'monthly' as license_type,
    1 as duration_months,
    3 as max_users,
    100 as max_services,
    '["basic_features"]' as features,
    150000 as price,
    'IDR' as currency,
    'available' as status,
    NOW() as generated_at,
    1 as generated_by
FROM 
    (SELECT @row_number := 0) r,
    information_schema.tables 
LIMIT 500;</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-link me-2"></i>Quick Access Links</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="secret_access.php?secret=license_gen_access_2024&code=SEGITIGA_CREATIVE_2024_MASTER_ACCESS&verify=<?php echo md5('SEGITIGA_CREATIVE_2024_MASTER_ACCESS' . getCurrentUserId() . date('Y-m-d')); ?>" 
                       class="btn btn-warning btn-sm">
                        <i class="fas fa-key me-1"></i>Secret Gateway
                    </a>
                    
                    <a href="secret_access.php?x=44&y=46&z=90" 
                       class="btn btn-info btn-sm">
                        <i class="fas fa-calculator me-1"></i>Math Formula
                    </a>
                    
                    <a href="secret_generator.php?key=SEGITIGA2024&hash=<?php echo md5('TECHFIX_PRO_' . getCurrentUserId() . '_' . date('Ymd')); ?>&user=<?php echo getCurrentUserId(); ?>" 
                       class="btn btn-danger btn-sm">
                        <i class="fas fa-user-secret me-1"></i>Ultra Secret
                    </a>
                </div>
                
                <hr>
                
                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Hash berubah setiap hari untuk keamanan
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Security Notes -->
        <div class="card mt-3">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security Notes</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i>Access terbatas untuk admin</li>
                    <li><i class="fas fa-check text-success me-2"></i>Hash verification harian</li>
                    <li><i class="fas fa-check text-success me-2"></i>Fake 404/403 untuk unauthorized</li>
                    <li><i class="fas fa-check text-success me-2"></i>Multiple access methods</li>
                    <li><i class="fas fa-check text-success me-2"></i>Session-based protection</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>