<?php
/**
 * Secret License Generator
 * Ultra-hidden license generation tool
 * Access: /admin/secret_generator.php?key=SEGITIGA2024&hash=MD5_HASH&user=ADMIN_ID
 */

require_once '../config/database.php';
require_once '../config/session.php';

// Require admin login
requireAdmin();

// Ultra-secret access verification
$secretKey = $_GET['key'] ?? '';
$hashVerify = $_GET['hash'] ?? '';
$userVerify = $_GET['user'] ?? '';

// Secret access formula
$expectedKey = 'SEGITIGA2024';
$expectedHash = md5('TECHFIX_PRO_' . getCurrentUserId() . '_' . date('Ymd'));
$expectedUser = getCurrentUserId();

$hasAccess = ($secretKey === $expectedKey && 
              $hashVerify === $expectedHash && 
              $userVerify == $expectedUser);

if (!$hasAccess) {
    // Show fake maintenance page
    ?>
    <!DOCTYPE html>
    <html>
    <head>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
        <title>Under Maintenance</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
            .maintenance { max-width: 500px; margin: 0 auto; }
            .icon { font-size: 4rem; color: #ffc107; margin-bottom: 1rem; }
        </style>
    </head>
    <body>
        <div class="maintenance">
            <div class="icon">🔧</div>
            <h2>Under Maintenance</h2>
            <p>This page is currently under maintenance. Please try again later.</p>
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Handle license generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_bulk') {
        $monthlyCount = (int)($_POST['monthly_count'] ?? 500);
        $yearlyCount = (int)($_POST['yearly_count'] ?? 500);
        
        $results = [];
        
        // Generate monthly licenses
        if ($monthlyCount > 0) {
            $monthlyResult = generateBulkLicenses('monthly', $monthlyCount);
            $results['monthly'] = $monthlyResult;
        }
        
        // Generate yearly licenses  
        if ($yearlyCount > 0) {
            $yearlyResult = generateBulkLicenses('yearly', $yearlyCount);
            $results['yearly'] = $yearlyResult;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Bulk generation completed',
            'results' => $results
        ]);
        exit();
    }
}

// Bulk generation function
function generateBulkLicenses($type, $count) {
    try {
        $db = new Database();
        $generated = [];
        $failed = 0;
        
        // License parameters
        $params = [
            'monthly' => [
                'type' => 'monthly',
                'duration' => 1,
                'max_users' => 3,
                'max_services' => 100,
                'features' => ['basic_features'],
                'price' => 150000
            ],
            'yearly' => [
                'type' => 'yearly', 
                'duration' => 12,
                'max_users' => 10,
                'max_services' => 1000,
                'features' => ['basic_features', 'reports', 'advanced_tracking'],
                'price' => 1500000
            ]
        ];
        
        $licenseParams = $params[$type];
        
        for ($i = 1; $i <= $count; $i++) {
            // Generate unique token
            $prefix = $type === 'yearly' ? 'TFPRO' : 'TFMON';
            $timestamp = date('ymd');
            $random1 = strtoupper(substr(md5(uniqid() . $i), 0, 5));
            $random2 = strtoupper(substr(md5(time() . $i), 0, 5));
            $sequence = str_pad($i, 5, '0', STR_PAD_LEFT);
            
            $token = $prefix . '-' . $timestamp . '-' . $random1 . '-' . $random2 . '-' . $sequence;
            
            // Check if token already exists
            $stmt = $db->query("SELECT id FROM generated_licenses WHERE license_key = :token");
            $db->execute($stmt, [':token' => $token]);
            if ($db->single($stmt)) {
                $failed++;
                continue;
            }
            
            // Insert license
            $stmt = $db->query("
                INSERT INTO generated_licenses 
                (license_key, license_type, duration_months, max_users, max_services, features, price, currency, status, generated_at, generated_by) 
                VALUES (:license_key, :license_type, :duration_months, :max_users, :max_services, :features, :price, :currency, :status, NOW(), :generated_by)
            ");
            
            if ($db->execute($stmt, [
                ':license_key' => $token,
                ':license_type' => $licenseParams['type'],
                ':duration_months' => $licenseParams['duration'],
                ':max_users' => $licenseParams['max_users'],
                ':max_services' => $licenseParams['max_services'],
                ':features' => json_encode($licenseParams['features']),
                ':price' => $licenseParams['price'],
                ':currency' => 'IDR',
                ':status' => 'available',
                ':generated_by' => getCurrentUserId()
            ])) {
                $generated[] = $token;
            } else {
                $failed++;
            }
        }
        
        return [
            'success' => true,
            'generated' => count($generated),
            'failed' => $failed,
            'tokens' => array_slice($generated, 0, 5) // Return first 5 for preview
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secret License Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: white;
            min-height: 100vh;
            font-family: 'Courier New', monospace;
        }
        .secret-container {
            padding: 2rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .secret-card {
            background: rgba(0,0,0,0.8);
            border: 1px solid #00ff00;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,255,0,0.3);
        }
        .secret-header {
            background: linear-gradient(135deg, #00ff00, #00cc00);
            color: black;
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px 10px 0 0;
        }
        .matrix-text {
            color: #00ff00;
            font-family: 'Courier New', monospace;
            text-shadow: 0 0 10px #00ff00;
        }
        .btn-matrix {
            background: linear-gradient(135deg, #00ff00, #00cc00);
            border: none;
            color: black;
            font-weight: bold;
            box-shadow: 0 0 10px rgba(0,255,0,0.5);
        }
        .btn-matrix:hover {
            background: linear-gradient(135deg, #00cc00, #009900);
            color: black;
            box-shadow: 0 0 20px rgba(0,255,0,0.8);
        }
        .stats-card {
            background: rgba(0,255,0,0.1);
            border: 1px solid #00ff00;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="secret-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="secret-card">
                        <div class="secret-header">
                            <h2 class="mb-0">
                                <i class="fas fa-user-secret me-2"></i>
                                SECRET LICENSE GENERATOR
                            </h2>
                            <p class="mb-0">Ultra-Hidden License Generation System</p>
                        </div>
                        
                        <div class="p-4">
                            <!-- Warning -->
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>TOP SECRET:</strong> This is a hidden system. Do not share access methods.
                            </div>
                            
                            <!-- Quick Stats -->
                            <div class="row mb-4">
                                <div class="col-md-3 mb-3">
                                    <div class="stats-card p-3 text-center">
                                        <h4 class="matrix-text" id="totalGenerated">0</h4>
                                        <small>Total Generated</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stats-card p-3 text-center">
                                        <h4 class="matrix-text" id="monthlyCount">0</h4>
                                        <small>Monthly Licenses</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stats-card p-3 text-center">
                                        <h4 class="matrix-text" id="yearlyCount">0</h4>
                                        <small>Yearly Licenses</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stats-card p-3 text-center">
                                        <h4 class="matrix-text" id="totalValue">Rp 0</h4>
                                        <small>Total Value</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Bulk Generation -->
                            <div class="card bg-dark border-success mb-4">
                                <div class="card-header">
                                    <h5 class="matrix-text mb-0">
                                        <i class="fas fa-magic me-2"></i>BULK LICENSE GENERATION
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form id="bulkGenerationForm">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label matrix-text">Monthly Licenses (Rp 150,000 each)</label>
                                                <input type="number" class="form-control bg-dark text-white border-success" 
                                                       id="monthlyCount" name="monthly_count" value="500" min="0" max="1000">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label matrix-text">Yearly Licenses (Rp 1,500,000 each)</label>
                                                <input type="number" class="form-control bg-dark text-white border-success" 
                                                       id="yearlyCount" name="yearly_count" value="500" min="0" max="1000">
                                            </div>
                                        </div>
                                        
                                        <div class="text-center">
                                            <button type="button" class="btn btn-matrix btn-lg" onclick="generateBulkLicenses()">
                                                <i class="fas fa-rocket me-2"></i>GENERATE 1000 LICENSES
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <button class="btn btn-matrix w-100" onclick="generateQuick('monthly', 100)">
                                        <i class="fas fa-calendar me-2"></i>
                                        Generate 100 Monthly
                                    </button>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <button class="btn btn-matrix w-100" onclick="generateQuick('yearly', 100)">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        Generate 100 Yearly
                                    </button>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <button class="btn btn-outline-light w-100" onclick="exportAllLicenses()">
                                        <i class="fas fa-download me-2"></i>
                                        Export All
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Navigation -->
                            <div class="text-center mt-4">
                                <a href="dashboard.php" class="btn btn-outline-light me-2">
                                    <i class="fas fa-arrow-left me-1"></i>Dashboard
                                </a>
                                <a href="license_generator.php?access_token=<?php echo urlencode($_SESSION['license_generator_access'] ?? ''); ?>" class="btn btn-outline-success">
                                    <i class="fas fa-cog me-1"></i>Full Generator
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <script>
        $(document).ready(function() {
            loadStats();
        });
        
        // Generate bulk licenses
        function generateBulkLicenses() {
            const monthlyCount = document.getElementById('monthlyCount').value;
            const yearlyCount = document.getElementById('yearlyCount').value;
            const totalCount = parseInt(monthlyCount) + parseInt(yearlyCount);
            
            if (totalCount === 0) {
                Swal.fire('Error', 'Set at least one license count', 'error');
                return;
            }
            
            Swal.fire({
                title: 'Generate Bulk Licenses?',
                html: `
                    <div class="text-start">
                        <p><strong>Monthly:</strong> ${monthlyCount} licenses (Rp ${formatRupiah(monthlyCount * 150000)})</p>
                        <p><strong>Yearly:</strong> ${yearlyCount} licenses (Rp ${formatRupiah(yearlyCount * 1500000)})</p>
                        <hr>
                        <p><strong>Total:</strong> ${totalCount} licenses</p>
                        <p><strong>Total Value:</strong> Rp ${formatRupiah((monthlyCount * 150000) + (yearlyCount * 1500000))}</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Generate!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#00cc00'
            }).then((result) => {
                if (result.isConfirmed) {
                    const btn = document.querySelector('.btn-matrix');
                    const originalText = btn.innerHTML;
                    
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>GENERATING...';
                    
                    fetch('secret_generator.php?key=SEGITIGA2024&hash=<?php echo $expectedHash; ?>&user=<?php echo $expectedUser; ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=generate_bulk&monthly_count=${monthlyCount}&yearly_count=${yearlyCount}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Generation Complete!',
                                html: `
                                    <div class="text-start">
                                        <p><strong>Monthly:</strong> ${data.results.monthly?.generated || 0} generated</p>
                                        <p><strong>Yearly:</strong> ${data.results.yearly?.generated || 0} generated</p>
                                        <p><strong>Total:</strong> ${(data.results.monthly?.generated || 0) + (data.results.yearly?.generated || 0)} licenses</p>
                                    </div>
                                `,
                                timer: 5000
                            });
                            loadStats();
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    });
                }
            });
        }
        
        // Quick generation
        function generateQuick(type, count) {
            const btn = event.target;
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
            
            const formData = new FormData();
            formData.append('action', 'generate_bulk');
            formData.append(type + '_count', count);
            formData.append((type === 'monthly' ? 'yearly' : 'monthly') + '_count', 0);
            
            fetch('secret_generator.php?key=SEGITIGA2024&hash=<?php echo $expectedHash; ?>&user=<?php echo $expectedUser; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Generated!',
                        text: `${count} ${type} licenses generated successfully`,
                        timer: 3000
                    });
                    loadStats();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        }
        
        // Load statistics
        function loadStats() {
            // Simulate stats loading
            fetch('../admin/license_generator.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_license_stats'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.data;
                    document.getElementById('totalGenerated').textContent = stats.total;
                    document.getElementById('monthlyCount').textContent = stats.monthly;
                    document.getElementById('yearlyCount').textContent = stats.yearly;
                    
                    const totalValue = (stats.monthly * 150000) + (stats.yearly * 1500000);
                    document.getElementById('totalValue').textContent = formatRupiah(totalValue);
                }
            })
            .catch(() => {
                // Fallback if stats loading fails
                document.getElementById('totalGenerated').textContent = '---';
            });
        }
        
        // Export all licenses
        function exportAllLicenses() {
            window.open('../admin/export_licenses.php?type=all&format=excel', '_blank');
        }
        
        // Format rupiah
        function formatRupiah(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(amount);
        }
    </script>
</body>
</html>