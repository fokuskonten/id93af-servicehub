<?php
// FokusKonten PHP 8.2 Compatibility Shield
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', '0');

// Debug information
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if we can access this file
echo "<!-- Index.php loaded successfully -->";

// Check if config files exist
$config_files = [
    'config/database.php',
    'config/session.php'
];

foreach ($config_files as $file) {
    if (!file_exists($file)) {
        die("Error: $file not found. Current directory: " . __DIR__);
    }
}

try {
    require_once 'config/database.php';
    require_once 'config/session.php';
} catch (Exception $e) {
    die("Error loading config files: " . $e->getMessage());
}

$page_title = 'Beranda';

// Redirect if already logged in
if (function_exists('isLoggedIn') && isLoggedIn()) {
    if (function_exists('isAdmin') && isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('customer/dashboard.php');
    }
}

include 'includes/header.php';
?>

<style>
/* Override any sidebar styles for homepage */
body {
    margin-left: 0 !important;
}

.main-wrapper {
    margin-left: 0 !important;
    width: 100% !important;
}

.sidebar {
    display: none !important;
}
</style>

<div class="hero-section bg-primary text-white py-5 min-vh-100 d-flex align-items-center">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-lg-6 px-5">
                <h1 class="display-4 fw-bold mb-4">
                    <i class="fas fa-tools me-3"></i>
                    <?php echo getSiteName(); ?>
                </h1>
                <p class="lead mb-4"><?php echo getSiteDescription(); ?></p>
                <p class="mb-4">Kami melayani perbaikan komputer, laptop, dan handphone dengan teknisi berpengalaman dan garansi resmi.</p>
                <div class="d-flex gap-3">
                    <a href="register.php" class="btn btn-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                    </a>
                    <a href="public/track.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-search me-2"></i>Lacak Servis
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center px-5">
                <div class="service-icon mx-auto mb-4" style="width: 200px; height: 200px; font-size: 5rem;">
                    <i class="fas fa-laptop"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Services Section -->
<section class="py-5 bg-light">
    <div class="container-fluid px-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Layanan Kami</h2>
            <p class="text-muted">Berbagai jenis layanan perbaikan perangkat elektronik</p>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="service-icon mx-auto mb-3">
                            <i class="fas fa-laptop"></i>
                        </div>
                        <h5 class="card-title">Servis Laptop</h5>
                        <p class="card-text">Perbaikan laptop semua merk dengan teknisi berpengalaman dan spare part original.</p>
                        <ul class="list-unstyled text-start">
                            <li><i class="fas fa-check text-success me-2"></i>Ganti LCD/LED</li>
                            <li><i class="fas fa-check text-success me-2"></i>Perbaikan Motherboard</li>
                            <li><i class="fas fa-check text-success me-2"></i>Upgrade RAM/SSD</li>
                            <li><i class="fas fa-check text-success me-2"></i>Install Ulang OS</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="service-icon mx-auto mb-3">
                            <i class="fas fa-desktop"></i>
                        </div>
                        <h5 class="card-title">Servis Komputer</h5>
                        <p class="card-text">Perbaikan PC desktop, all-in-one, dan workstation untuk kebutuhan rumah dan kantor.</p>
                        <ul class="list-unstyled text-start">
                            <li><i class="fas fa-check text-success me-2"></i>Perbaikan Hardware</li>
                            <li><i class="fas fa-check text-success me-2"></i>Cleaning & Maintenance</li>
                            <li><i class="fas fa-check text-success me-2"></i>Upgrade Komponen</li>
                            <li><i class="fas fa-check text-success me-2"></i>Troubleshooting</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="service-icon mx-auto mb-3">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h5 class="card-title">Servis Handphone</h5>
                        <p class="card-text">Perbaikan smartphone Android dan iPhone dengan garansi dan harga terjangkau.</p>
                        <ul class="list-unstyled text-start">
                            <li><i class="fas fa-check text-success me-2"></i>Ganti LCD Touchscreen</li>
                            <li><i class="fas fa-check text-success me-2"></i>Perbaikan IC/Chipset</li>
                            <li><i class="fas fa-check text-success me-2"></i>Ganti Baterai</li>
                            <li><i class="fas fa-check text-success me-2"></i>Unlock & Root</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="py-5">
    <div class="container-fluid px-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Mengapa Pilih Kami?</h2>
            <p class="text-muted">Keunggulan layanan yang kami berikan</p>
        </div>
        
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="text-center">
                    <div class="service-icon mx-auto mb-3" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h5>Teknisi Berpengalaman</h5>
                    <p class="text-muted">Tim teknisi profesional dengan pengalaman lebih dari 5 tahun</p>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="text-center">
                    <div class="service-icon mx-auto mb-3" style="background: linear-gradient(135deg, #17a2b8, #6f42c1);">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h5>Garansi Resmi</h5>
                    <p class="text-muted">Garansi 30 hari untuk semua jenis perbaikan yang kami lakukan</p>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="text-center">
                    <div class="service-icon mx-auto mb-3" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h5>Pengerjaan Cepat</h5>
                    <p class="text-muted">Estimasi pengerjaan 1-3 hari kerja tergantung tingkat kerusakan</p>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="text-center">
                    <div class="service-icon mx-auto mb-3" style="background: linear-gradient(135deg, #dc3545, #e83e8c);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h5>Harga Terjangkau</h5>
                    <p class="text-muted">Harga kompetitif dengan kualitas terbaik dan tanpa biaya tersembunyi</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-primary text-white min-vh-50 d-flex align-items-center">
    <div class="container-fluid text-center px-5">
        <h2 class="fw-bold mb-4">Siap Memperbaiki Perangkat Anda?</h2>
        <p class="lead mb-4">Daftar sekarang dan buat tiket servis untuk memulai proses perbaikan</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="register.php" class="btn btn-light btn-lg">
                <i class="fas fa-user-plus me-2"></i>Daftar Gratis
            </a>
            <a href="login.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>