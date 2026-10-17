<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/Service.php';
require_once '../classes/User.php';

// Require admin login
requireAdmin();

$page_title = 'Admin Dashboard';
$serviceObj = new Service();
$userObj = new User();

// Get statistics
try {
    $serviceStats = $serviceObj->getServiceStats();
    $userStats = $userObj->getUserStats();
    
    // Get recent services
    $recentServices = $serviceObj->getAllServices(5);
} catch(Exception $e) {
    // Handle error gracefully
    $serviceStats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'delivered' => 0,
        'revenue' => 0
    ];
    $userStats = [
        'total' => 0,
        'customers' => 0,
        'admins' => 0
    ];
    $recentServices = [];
    
    // Show error message
    setFlashMessage('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
}


include '../includes/header.php';
?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard Admin</h2>
                    <p class="text-muted">Selamat datang, <?php echo getCurrentUserName(); ?>!</p>
                </div>
                <div>
                    <span class="badge bg-success fs-6">
                        <i class="fas fa-circle me-1"></i>Online
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-white bg-primary">
                <div class="card-body text-center">
                    <i class="fas fa-tools fa-3x mb-3"></i>
                    <div class="h2"><?php echo $serviceStats['total']; ?></div>
                    <div>Total Servis</div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-white bg-warning">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-3x mb-3"></i>
                    <div class="h2"><?php echo $serviceStats['pending'] + $serviceStats['in_progress']; ?></div>
                    <div>Sedang Proses</div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-white bg-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <div class="h2"><?php echo $serviceStats['completed'] + $serviceStats['delivered']; ?></div>
                    <div>Selesai</div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-white bg-info">
                <div class="card-body text-center">
                    <i class="fas fa-money-bill-wave fa-3x mb-3"></i>
                    <div class="h2"><?php echo formatRupiah($serviceStats['revenue']); ?></div>
                    <div>Total Pendapatan</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Services -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Servis Terbaru</h5>
                    <a href="services.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentServices)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ticket</th>
                                    <th>Customer</th>
                                    <th>Device</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentServices as $service): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $service['ticket_number']; ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($service['customer_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($service['customer_email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="fas fa-<?php echo $service['device_type'] === 'laptop' ? 'laptop' : ($service['device_type'] === 'computer' ? 'desktop' : 'mobile-alt'); ?> me-2"></i>
                                        <?php echo htmlspecialchars($service['device_brand'] . ' ' . $service['device_model']); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadge($service['status']); ?>">
                                            <?php echo getStatusText($service['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($service['created_at']); ?></td>
                                    <td>
                                        <a href="service_detail.php?id=<?php echo $service['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada servis terbaru</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="services.php?action=create" class="btn btn-outline-primary w-100 py-3">
                                <i class="fas fa-plus-circle fa-2x mb-2 d-block"></i>
                                Buat Servis Baru
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="services.php?status=pending" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-clock fa-2x mb-2 d-block"></i>
                                Servis Pending
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="customers.php" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-users fa-2x mb-2 d-block"></i>
                                Kelola Customer
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="#" class="btn btn-outline-success w-100 py-3">
                                <i class="fas fa-chart-line fa-2x mb-2 d-block"></i>
                                Laporan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>