<?php
require_once 'config/database.php';
require_once 'classes/LicenseManager.php';

$page_title = 'Aktivasi License';
$licenseManager = new LicenseManager();

// Check if already licensed
$currentLicense = $licenseManager->getLicenseInfo();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'activate') {
        $token = strtoupper(trim($_POST['token']));
        $customerName = sanitize($_POST['customer_name']);
        $customerEmail = sanitize($_POST['customer_email']);
        $companyName = sanitize($_POST['company_name']);
        
        // Validation
        if (empty($token)) {
            $errors[] = 'Token license harus diisi';
        }
        
        if (empty($customerName)) {
            $errors[] = 'Nama customer harus diisi';
        }
        
        if (empty($customerEmail)) {
            $errors[] = 'Email customer harus diisi';
        } elseif (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid';
        }
        
        if (empty($errors)) {
            $customerInfo = [
                'name' => $customerName,
                'email' => $customerEmail,
                'company' => $companyName
            ];
            
            $result = $licenseManager->activateLicense($token, $customerInfo);
            
            if ($result['success']) {
                $success = true;
                $successMessage = $result['message'];
                $licenseType = $result['license_type'];
                $expiryDate = $result['expiry_date'];
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

// Get demo tokens for testing
$demoTokens = $licenseManager->generateDemoTokens();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - TechFix Pro</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .license-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        
        .license-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .license-header {
            background: linear-gradient(135deg, #0d6efd, #0056b3);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .license-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        .token-input {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            text-align: center;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .demo-token {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .demo-token:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .license-type-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="license-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <?php if ($success): ?>
                    <!-- Success Message -->
                    <div class="license-card">
                        <div class="license-header">
                            <i class="fas fa-check-circle license-icon"></i>
                            <h2>License Berhasil Diaktivasi!</h2>
                            <p class="mb-0">TechFix Pro siap digunakan</p>
                        </div>
                        <div class="p-4 text-center">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $successMessage; ?>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>License Type:</h6>
                                    <span class="badge bg-primary license-type-badge"><?php echo ucfirst($licenseType); ?></span>
                                </div>
                                <div class="col-md-6">
                                    <h6>Berlaku Sampai:</h6>
                                    <strong><?php echo date('d/m/Y', strtotime($expiryDate)); ?></strong>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <a href="index.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-home me-2"></i>Masuk ke Aplikasi
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php elseif ($currentLicense && !$currentLicense['is_expired']): ?>
                    <!-- Already Licensed -->
                    <div class="license-card">
                        <div class="license-header">
                            <i class="fas fa-shield-alt license-icon"></i>
                            <h2>License Aktif</h2>
                            <p class="mb-0">Aplikasi sudah memiliki license yang valid</p>
                        </div>
                        <div class="p-4">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Aplikasi sudah memiliki license yang aktif dan valid.
                            </div>
                            
                            <div class="row text-center">
                                <div class="col-md-4 mb-3">
                                    <h6 class="text-muted">License Type</h6>
                                    <span class="badge bg-primary license-type-badge"><?php echo ucfirst($currentLicense['license_type']); ?></span>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <h6 class="text-muted">Customer</h6>
                                    <strong><?php echo htmlspecialchars($currentLicense['customer_name']); ?></strong>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <h6 class="text-muted">Sisa Hari</h6>
                                    <strong class="<?php echo $currentLicense['days_remaining'] < 30 ? 'text-warning' : 'text-success'; ?>">
                                        <?php echo $currentLicense['days_remaining']; ?> hari
                                    </strong>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-home me-2"></i>Masuk ke Aplikasi
                                </a>
                                <button class="btn btn-outline-warning" onclick="showRenewForm()">
                                    <i class="fas fa-sync-alt me-2"></i>Perbarui License
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <!-- License Activation Form -->
                    <div class="license-card">
                        <div class="license-header">
                            <i class="fas fa-key license-icon"></i>
                            <h2>Aktivasi License</h2>
                            <p class="mb-0">Masukkan token license untuk menggunakan TechFix Pro</p>
                        </div>
                        <div class="p-4">
                            <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="activate">
                                
                                <div class="mb-4">
                                    <label for="token" class="form-label">Token License *</label>
                                    <input type="text" class="form-control token-input" id="token" name="token" 
                                           placeholder="TFPRO-XXXXX-XXXXX-XXXXX-XXXXX" 
                                           value="<?php echo isset($_POST['token']) ? htmlspecialchars($_POST['token']) : ''; ?>" 
                                           required maxlength="29" pattern="^TFPRO-[A-Z0-9]{4,6}-[A-Z0-9]{4,6}-[A-Z0-9]{4,6}-[A-Z0-9]{4,6}$">
                                    <div class="invalid-feedback">Token license harus diisi</div>
                                    <small class="form-text text-muted">
                                        Format: TFPRO-XXXXX-XXXXX-XXXXX-XXXXX
                                    </small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="customer_name" class="form-label">Nama Customer *</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                               value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : ''; ?>" 
                                               required>
                                        <div class="invalid-feedback">Nama customer harus diisi</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="customer_email" class="form-label">Email Customer *</label>
                                        <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                               value="<?php echo isset($_POST['customer_email']) ? htmlspecialchars($_POST['customer_email']) : ''; ?>" 
                                               required>
                                        <div class="invalid-feedback">Email customer harus diisi</div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="company_name" class="form-label">Nama Perusahaan</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" 
                                           value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>" 
                                           placeholder="Opsional">
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-key me-2"></i>Aktivasi License
                                    </button>
                                </div>
                            </form>
                            
                            <hr class="my-4">
                            
                            <!-- Demo Tokens for Testing -->
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-flask me-2"></i>Demo Tokens (Testing)</h6>
                                </div>
                                <div class="card-body">
                                    <p class="small text-muted mb-3">Gunakan token demo berikut untuk testing:</p>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <div class="demo-token" onclick="fillToken('<?php echo $demoTokens['trial']; ?>')">
                                                <strong>Trial (30 hari)</strong><br>
                                                <code><?php echo $demoTokens['trial']; ?></code>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="demo-token" onclick="fillToken('<?php echo $demoTokens['basic']; ?>')">
                                                <strong>Basic (1 tahun)</strong><br>
                                                <code><?php echo $demoTokens['basic']; ?></code>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="demo-token" onclick="fillToken('<?php echo $demoTokens['premium']; ?>')">
                                                <strong>Premium (1 tahun)</strong><br>
                                                <code><?php echo $demoTokens['premium']; ?></code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <p class="mb-2">Belum punya license?</p>
                                <a href="https://segitigacreative.tech" target="_blank" class="btn btn-outline-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>Beli License
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <script>
        // Fill token from demo
        function fillToken(token) {
            document.getElementById('token').value = token;
            
            // Auto-fill demo customer info
            if (!document.getElementById('customer_name').value) {
                document.getElementById('customer_name').value = 'Demo Customer';
            }
            if (!document.getElementById('customer_email').value) {
                document.getElementById('customer_email').value = 'demo@customer.com';
            }
            if (!document.getElementById('company_name').value) {
                document.getElementById('company_name').value = 'Demo Company';
            }
        }
        
        // Show renew form
        function showRenewForm() {
            Swal.fire({
                title: 'Perbarui License',
                html: `
                    <div class="text-start">
                        <p>Untuk memperbarui license:</p>
                        <ol>
                            <li>Kunjungi <a href="https://segitigacreative.tech" target="_blank">segitigacreative.tech</a></li>
                            <li>Beli license renewal</li>
                            <li>Dapatkan token baru</li>
                            <li>Aktivasi dengan token baru</li>
                        </ol>
                    </div>
                `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Beli License',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#0d6efd'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.open('https://segitigacreative.tech', '_blank');
                }
            });
        }
        
        // Auto-format token input
        document.getElementById('token').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^A-Z0-9\-]/g, '').toUpperCase();
            let formatted = '';
            
            // Remove existing dashes first
            value = value.replace(/-/g, '');
            
            // Add TFPRO prefix if not present
            if (value.length > 0 && value.indexOf('TFPRO') !== 0) {
                // Only add TFPRO if user hasn't started typing it
                if (value.length <= 10 && !value.match(/^T?F?P?R?O?/)) {
                    value = 'TFPRO' + value;
                }
            }
            
            // Format with dashes
            for (let i = 0; i < value.length && formatted.length < 29; i++) {
                if ((i === 5 || i === 10 || i === 15 || i === 20) && formatted.charAt(formatted.length - 1) !== '-') {
                    formatted += '-';
                }
                formatted += value[i];
            }
            
            e.target.value = formatted;
        });
        
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>
```