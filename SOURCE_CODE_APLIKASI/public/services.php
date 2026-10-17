<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'Layanan Kami';

include '../includes/header.php';
?>

<style>
/* Ensure full width for public pages */
.main-wrapper {
    margin-left: 0 !important;
    width: 100% !important;
}
</style>

<div class="container-fluid px-5 my-5">
    <div class="row">
        <div class="col-12 text-center mb-5">
            <h2 class="fw-bold">Layanan Kami</h2>
            <p class="text-muted">Berbagai jenis layanan perbaikan perangkat elektronik dengan kualitas terbaik</p>
        </div>
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
                <div class="card-footer">
                    <a href="../register.php" class="btn btn-primary">Mulai Servis</a>
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
                <div class="card-footer">
                    <a href="../register.php" class="btn btn-primary">Mulai Servis</a>
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
                <div class="card-footer">
                    <a href="../register.php" class="btn btn-primary">Mulai Servis</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pricing Section -->
    <div class="row mt-5">
        <div class="col-12 text-center mb-4">
            <h3 class="fw-bold">Estimasi Harga</h3>
            <p class="text-muted">Harga dapat berubah tergantung tingkat kerusakan dan spare part yang dibutuhkan</p>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header text-center">
                    <h5>Laptop</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li>Cleaning & Maintenance: <strong>Rp 50.000</strong></li>
                        <li>Install Ulang OS: <strong>Rp 75.000</strong></li>
                        <li>Ganti LCD: <strong>Rp 500.000 - 2.000.000</strong></li>
                        <li>Perbaikan Motherboard: <strong>Rp 300.000 - 1.500.000</strong></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header text-center">
                    <h5>Komputer</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li>Cleaning & Maintenance: <strong>Rp 75.000</strong></li>
                        <li>Install Ulang OS: <strong>Rp 100.000</strong></li>
                        <li>Upgrade RAM/SSD: <strong>Rp 50.000 + part</strong></li>
                        <li>Perbaikan Hardware: <strong>Rp 150.000 - 800.000</strong></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header text-center">
                    <h5>Handphone</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li>Ganti Baterai: <strong>Rp 150.000 - 400.000</strong></li>
                        <li>Ganti LCD: <strong>Rp 200.000 - 1.200.000</strong></li>
                        <li>Perbaikan IC: <strong>Rp 300.000 - 800.000</strong></li>
                        <li>Software/Root: <strong>Rp 100.000 - 200.000</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>