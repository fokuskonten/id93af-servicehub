<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/LicenseManager.php';

// Secret access verification
$hasValidAccess = false;

// Check session access token
if (isset($_SESSION['license_generator_access']) && isset($_SESSION['license_access_time'])) {
    $accessTime = $_SESSION['license_access_time'];
    $currentTime = time();
    
    // Access token valid for 1 hour
    if (($currentTime - $accessTime) < 3600) {
        $accessToken = $_SESSION['license_generator_access'];
        $tokenData = json_decode(base64_decode($accessToken), true);
        
        if ($tokenData && 
            $tokenData['user_id'] == getCurrentUserId() && 
            isset($tokenData['access_granted']) && 
            $tokenData['access_granted'] === true) {
            $hasValidAccess = true;
        }
    }
}

// Check URL access token
if (!$hasValidAccess && isset($_GET['access_token'])) {
    $urlToken = $_GET['access_token'];
    if (isset($_SESSION['license_generator_access']) && $_SESSION['license_generator_access'] === $urlToken) {
        $hasValidAccess = true;
    }
}

// If no valid access, show fake error page
if (!$hasValidAccess) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
        <title>Access Denied</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                            <h3>Access Denied</h3>
                            <p class="text-muted">You don't have permission to access this resource.</p>
                            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
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

// Require admin login
requireAdmin();

$page_title = 'License Generator';
$licenseManager = new LicenseManager();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'generate_licenses':
            $type = $_POST['type'] ?? 'monthly';
            $count = (int)($_POST['count'] ?? 100);
            $result = generateLicenses($type, $count);
            echo json_encode($result);
            exit;
            
        case 'export_licenses':
            $type = $_POST['type'] ?? 'all';
            $result = exportLicenses($type);
            echo json_encode($result);
            exit;
            
        case 'clear_all_licenses':
            $result = clearAllLicenses();
            echo json_encode($result);
            exit;
            
        case 'get_license_stats':
            $stats = getLicenseGenerationStats();
            echo json_encode(['success' => true, 'data' => $stats]);
            exit;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

// Generate licenses function
function generateLicenses($type, $count) {
    global $licenseManager;
    
    try {
        $db = new Database();
        $generated = [];
        $failed = 0;
        
        // Determine license parameters
        $licenseParams = getLicenseParams($type);
        
        for ($i = 0; $i < $count; $i++) {
            // Generate unique token
            $token = generateUniqueToken($type, $i + 1);
            
            // Check if token already exists
            $stmt = $db->query("SELECT id FROM generated_licenses WHERE license_key = :token");
            $db->execute($stmt, [':token' => $token]);
            if ($db->single($stmt)) {
                $failed++;
                continue;
            }
            
            // Create license data
            $licenseData = [
                'license_key' => $token,
                'license_type' => $licenseParams['type'],
                'duration_months' => $licenseParams['duration'],
                'max_users' => $licenseParams['max_users'],
                'max_services' => $licenseParams['max_services'],
                'features' => json_encode($licenseParams['features']),
                'price' => $licenseParams['price'],
                'currency' => 'IDR',
                'status' => 'available',
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => getCurrentUserId()
            ];
            
            // Save to database
            $stmt = $db->query("
                INSERT INTO generated_licenses 
                (license_key, license_type, duration_months, max_users, max_services, features, price, currency, status, generated_at, generated_by) 
                VALUES (:license_key, :license_type, :duration_months, :max_users, :max_services, :features, :price, :currency, :status, :generated_at, :generated_by)
            ");
            
            if ($db->execute($stmt, [
                ':license_key' => $licenseData['license_key'],
                ':license_type' => $licenseData['license_type'],
                ':duration_months' => $licenseData['duration_months'],
                ':max_users' => $licenseData['max_users'],
                ':max_services' => $licenseData['max_services'],
                ':features' => $licenseData['features'],
                ':price' => $licenseData['price'],
                ':currency' => $licenseData['currency'],
                ':status' => $licenseData['status'],
                ':generated_at' => $licenseData['generated_at'],
                ':generated_by' => $licenseData['generated_by']
            ])) {
                $generated[] = $licenseData;
            } else {
                $failed++;
            }
        }
        
        return [
            'success' => true,
            'message' => count($generated) . ' licenses generated successfully',
            'generated' => count($generated),
            'failed' => $failed,
            'data' => array_slice($generated, 0, 10) // Return first 10 for preview
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to generate licenses: ' . $e->getMessage()
        ];
    }
}

// Clear all licenses function
function clearAllLicenses() {
    try {
        $db = new Database();
        
        // Delete all generated licenses
        $stmt = $db->query("DELETE FROM generated_licenses");
        $db->execute($stmt);
        
        return [
            'success' => true,
            'message' => 'All licenses cleared successfully'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to clear licenses: ' . $e->getMessage()
        ];
    }
}

// Get license parameters
function getLicenseParams($type) {
    $params = [
        'monthly' => [
            'type' => 'monthly',
            'duration' => 1,
            'max_users' => 3,
            'max_services' => 100,
            'features' => ['basic_features'],
            'price' => 150000 // Rp 150,000
        ],
        'yearly' => [
            'type' => 'yearly',
            'duration' => 12,
            'max_users' => 10,
            'max_services' => 1000,
            'features' => ['basic_features', 'reports', 'advanced_tracking'],
            'price' => 1500000 // Rp 1,500,000
        ]
    ];
    
    return $params[$type] ?? $params['monthly'];
}

// Generate unique token
function generateUniqueToken($type, $sequence) {
    $prefix = $type === 'yearly' ? 'TFPRO' : 'TFMON';
    $timestamp = date('ymd');
    $random1 = strtoupper(substr(md5(uniqid() . $sequence), 0, 5));
    $random2 = strtoupper(substr(md5(time() . $sequence), 0, 5));
    $seqPad = str_pad($sequence, 5, '0', STR_PAD_LEFT);
    
    return $prefix . '-' . $timestamp . '-' . $random1 . '-' . $random2 . '-' . $seqPad;
}

// Export licenses
function exportLicenses($type) {
    try {
        $db = new Database();
        
        $sql = "SELECT * FROM generated_licenses WHERE status = 'available'";
        $params = [];
        
        if ($type !== 'all') {
            $sql .= " AND license_type = :type";
            $params[':type'] = $type;
        }
        
        $sql .= " ORDER BY generated_at DESC";
        
        $stmt = $db->query($sql);
        $db->execute($stmt, $params);
        $licenses = $db->resultSet($stmt);
        
        return [
            'success' => true,
            'data' => $licenses,
            'count' => count($licenses)
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to export licenses: ' . $e->getMessage()
        ];
    }
}

// Get generation statistics
function getLicenseGenerationStats() {
    try {
        $db = new Database();
        
        $stats = [];
        
        // Total generated
        $stmt = $db->query("SELECT COUNT(*) as count FROM generated_licenses");
        $db->execute($stmt);
        $stats['total'] = $db->single($stmt)['count'];
        
        // Available licenses
        $stmt = $db->query("SELECT COUNT(*) as count FROM generated_licenses WHERE status = 'available'");
        $db->execute($stmt);
        $stats['available'] = $db->single($stmt)['count'];
        
        // Used licenses
        $stmt = $db->query("SELECT COUNT(*) as count FROM generated_licenses WHERE status = 'used'");
        $db->execute($stmt);
        $stats['used'] = $db->single($stmt)['count'];
        
        // Monthly licenses
        $stmt = $db->query("SELECT COUNT(*) as count FROM generated_licenses WHERE license_type = 'monthly'");
        $db->execute($stmt);
        $stats['monthly'] = $db->single($stmt)['count'];
        
        // Yearly licenses
        $stmt = $db->query("SELECT COUNT(*) as count FROM generated_licenses WHERE license_type = 'yearly'");
        $db->execute($stmt);
        $stats['yearly'] = $db->single($stmt)['count'];
        
        return $stats;
        
    } catch (Exception $e) {
        return [
            'total' => 0,
            'available' => 0,
            'used' => 0,
            'monthly' => 0,
            'yearly' => 0
        ];
    }
}

// Create generated_licenses table if not exists
try {
    $db = new Database();
    $sql = "
        CREATE TABLE IF NOT EXISTS generated_licenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            license_key VARCHAR(255) NOT NULL UNIQUE,
            license_type ENUM('monthly', 'yearly', 'trial', 'basic', 'premium') NOT NULL,
            duration_months INT NOT NULL,
            max_users INT NOT NULL,
            max_services INT NOT NULL,
            features JSON,
            price DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'IDR',
            status ENUM('available', 'used', 'expired', 'revoked') DEFAULT 'available',
            generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            generated_by INT,
            sold_at TIMESTAMP NULL,
            sold_to VARCHAR(255) NULL,
            activated_at TIMESTAMP NULL,
            activated_by INT NULL,
            notes TEXT,
            INDEX idx_license_key (license_key),
            INDEX idx_status (status),
            INDEX idx_license_type (license_type),
            INDEX idx_generated_at (generated_at),
            FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (activated_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $db->getConnection()->exec($sql);
} catch (Exception $e) {
    error_log("Failed to create generated_licenses table: " . $e->getMessage());
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-key me-2"></i>License Generator</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="settings.php">Pengaturan</a></li>
                        <li class="breadcrumb-item active">License Generator</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4" id="statsCards">
    <div class="col-md-2 mb-3">
        <div class="card text-center bg-primary text-white">
            <div class="card-body">
                <i class="fas fa-key fa-2x mb-2"></i>
                <h4 id="totalLicenses">0</h4>
                <small>Total Generated</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card text-center bg-success text-white">
            <div class="card-body">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h4 id="availableLicenses">0</h4>
                <small>Available</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card text-center bg-info text-white">
            <div class="card-body">
                <i class="fas fa-calendar fa-2x mb-2"></i>
                <h4 id="monthlyLicenses">0</h4>
                <small>Monthly</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card text-center bg-warning text-dark">
            <div class="card-body">
                <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                <h4 id="yearlyLicenses">0</h4>
                <small>Yearly</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card text-center bg-secondary text-white">
            <div class="card-body">
                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                <h4 id="usedLicenses">0</h4>
                <small>Used</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card text-center bg-dark text-white">
            <div class="card-body">
                <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                <h4 id="totalValue">Rp 0</h4>
                <small>Total Value</small>
            </div>
        </div>
    </div>
</div>

<!-- Quick Generation -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Generation</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar fa-3x text-info mb-3"></i>
                                <h5>Generate 500 Monthly Licenses</h5>
                                <p class="text-muted">1 bulan, Rp 150,000 per license</p>
                                <button class="btn btn-info" onclick="generateBulkLicenses('monthly', 500)">
                                    <i class="fas fa-magic me-2"></i>Generate 500 Monthly
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-warning mb-3"></i>
                                <h5>Generate 500 Yearly Licenses</h5>
                                <p class="text-muted">1 tahun, Rp 1,500,000 per license</p>
                                <button class="btn btn-warning" onclick="generateBulkLicenses('yearly', 500)">
                                    <i class="fas fa-magic me-2"></i>Generate 500 Yearly
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Generation -->
<div class="row mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Custom Generation</h5>
            </div>
            <div class="card-body">
                <form id="customGenerationForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="licenseType" class="form-label">License Type</label>
                            <select class="form-select" id="licenseType" name="type">
                                <option value="monthly">Monthly (1 bulan)</option>
                                <option value="yearly">Yearly (1 tahun)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="licenseCount" class="form-label">Jumlah License</label>
                            <input type="number" class="form-control" id="licenseCount" name="count" 
                                   value="100" min="1" max="1000">
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-primary" onclick="generateCustomLicenses()">
                        <i class="fas fa-plus me-2"></i>Generate Licenses
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-download me-2"></i>Export Licenses</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Export generated licenses to various formats</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-success" onclick="exportToExcel('all')">
                        <i class="fas fa-file-excel me-2"></i>Export All to Excel
                    </button>
                    <button class="btn btn-info" onclick="exportToExcel('monthly')">
                        <i class="fas fa-file-excel me-2"></i>Export Monthly Only
                    </button>
                    <button class="btn btn-warning" onclick="exportToExcel('yearly')">
                        <i class="fas fa-file-excel me-2"></i>Export Yearly Only
                    </button>
                    <button class="btn btn-outline-primary" onclick="exportToCSV()">
                        <i class="fas fa-file-csv me-2"></i>Export to CSV
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generated Licenses Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Generated Licenses</h5>
                    <div>
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshLicenseList()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="clearAllLicenses()">
                            <i class="fas fa-trash me-1"></i>Clear All
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="licensesTable">
                    <div class="text-center py-4">
                        <i class="fas fa-key fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No licenses generated yet. Use the buttons above to generate licenses.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Load initial stats
    loadStats();
    loadLicenseList();
});

// Generate bulk licenses
function generateBulkLicenses(type, count) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
    
    Swal.fire({
        title: 'Generate Licenses?',
        text: `Generate ${count} ${type} licenses?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Generate!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('license_generator.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=generate_licenses&type=${type}&count=${count}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Licenses Generated!',
                        text: data.message,
                        timer: 3000
                    });
                    loadStats();
                    loadLicenseList();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Network error occurred', 'error');
            });
        }
        
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

// Generate custom licenses
function generateCustomLicenses() {
    const form = document.getElementById('customGenerationForm');
    const formData = new FormData(form);
    formData.append('action', 'generate_licenses');
    
    const type = formData.get('type');
    const count = formData.get('count');
    
    if (count < 1 || count > 1000) {
        Swal.fire('Error', 'Jumlah license harus antara 1-1000', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Generate Custom Licenses?',
        text: `Generate ${count} ${type} licenses?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Generate!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('license_generator.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Licenses Generated!',
                        text: data.message,
                        timer: 3000
                    });
                    loadStats();
                    loadLicenseList();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

// Load statistics
function loadStats() {
    fetch('license_generator.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_license_stats'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const stats = data.data;
            document.getElementById('totalLicenses').textContent = stats.total;
            document.getElementById('availableLicenses').textContent = stats.available;
            document.getElementById('monthlyLicenses').textContent = stats.monthly;
            document.getElementById('yearlyLicenses').textContent = stats.yearly;
            document.getElementById('usedLicenses').textContent = stats.used;
            
            // Calculate total value
            const totalValue = (stats.monthly * 150000) + (stats.yearly * 1500000);
            document.getElementById('totalValue').textContent = formatRupiah(totalValue);
        }
    });
}

// Load license list
function loadLicenseList() {
    fetch('license_generator.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=export_licenses&type=all'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateLicenseTable(data.data);
        }
    });
}

// Update license table
function updateLicenseTable(licenses) {
    const container = document.getElementById('licensesTable');
    
    if (licenses.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-key fa-3x text-muted mb-3"></i>
                <p class="text-muted">No licenses generated yet.</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>License Key</th>
                        <th>Type</th>
                        <th>Duration</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Generated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    licenses.slice(0, 50).forEach(license => {
        const statusBadge = license.status === 'available' ? 'bg-success' : 
                           license.status === 'used' ? 'bg-secondary' : 'bg-danger';
        
        html += `
            <tr>
                <td><code>${license.license_key}</code></td>
                <td><span class="badge ${license.license_type === 'monthly' ? 'bg-info' : 'bg-warning'}">${license.license_type}</span></td>
                <td>${license.duration_months} bulan</td>
                <td>${formatRupiah(license.price)}</td>
                <td><span class="badge ${statusBadge}">${license.status}</span></td>
                <td>${formatDate(license.generated_at)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="copyToken('${license.license_key}')" title="Copy Token">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-info" onclick="testToken('${license.license_key}')" title="Test Token">
                        <i class="fas fa-vial"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    if (licenses.length > 50) {
        html += `<p class="text-muted text-center">Showing first 50 of ${licenses.length} licenses</p>`;
    }
    
    container.innerHTML = html;
}

// Export to Excel
function exportToExcel(type) {
    fetch('license_generator.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=export_licenses&type=${type}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Create and download Excel file
            downloadExcel(data.data, type);
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}

// Download Excel file
function downloadExcel(licenses, type) {
    let csvContent = "License Key,Type,Duration (Months),Max Users,Max Services,Price,Currency,Status,Generated At\n";
    
    licenses.forEach(license => {
        csvContent += `"${license.license_key}","${license.license_type}",${license.duration_months},${license.max_users},${license.max_services},${license.price},"${license.currency}","${license.status}","${license.generated_at}"\n`;
    });
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `licenses_${type}_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    Swal.fire({
        icon: 'success',
        title: 'Export Complete!',
        text: `${licenses.length} licenses exported to CSV`,
        timer: 3000
    });
}

// Copy token to clipboard
function copyToken(token) {
    navigator.clipboard.writeText(token).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: 'Token copied to clipboard',
            timer: 1500,
            showConfirmButton: false
        });
    });
}

// Test token
function testToken(token) {
    Swal.fire({
        title: 'Test Token',
        html: `
            <p>Token: <code>${token}</code></p>
            <p>Click the button below to test this token in license activation:</p>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Test in Activation',
        cancelButtonText: 'Close'
    }).then((result) => {
        if (result.isConfirmed) {
            // Open license activation with pre-filled token
            const url = `../license_activation.php?test_token=${encodeURIComponent(token)}`;
            window.open(url, '_blank');
        }
    });
}

// Clear all licenses
function clearAllLicenses() {
    Swal.fire({
        title: 'Clear All Licenses?',
        text: 'This will delete ALL generated licenses. This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Clear All!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Execute clear operation
            fetch('license_generator.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=clear_all_licenses'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Cleared!', 'All licenses have been deleted.', 'success');
                    loadStats();
                    loadLicenseList();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

// Refresh license list
function refreshLicenseList() {
    loadStats();
    loadLicenseList();
    Swal.fire({
        icon: 'success',
        title: 'Refreshed!',
        timer: 1000,
        showConfirmButton: false
    });
}

// Helper functions
function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleString('id-ID');
}

// Auto-refresh stats every 30 seconds
setInterval(loadStats, 30000);
</script>

<?php include '../includes/footer.php'; ?>