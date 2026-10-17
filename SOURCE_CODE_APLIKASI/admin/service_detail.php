<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/Service.php';

// Require admin login
requireAdmin();

$page_title = 'Detail Servis';
$serviceObj = new Service();

// Get service ID from URL
$serviceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$serviceId) {
    setFlashMessage('error', 'ID servis tidak valid');
    redirect('admin/services.php');
}

// Get service details
$service = $serviceObj->getServiceById($serviceId);

if (!$service) {
    setFlashMessage('error', 'Servis tidak ditemukan');
    redirect('admin/services.php');
}

// Get service timeline
$timeline = $serviceObj->getServiceTimeline($serviceId);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = sanitize($_POST['status']);
    $notes = sanitize($_POST['notes']);
    
    // Debug: Check if data is received
    error_log("Status update attempt - Service ID: $serviceId, Status: $newStatus, Notes: $notes");
    
    try {
        $result = $serviceObj->updateServiceStatus($serviceId, $newStatus, $notes);
        error_log("Update result: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            setFlashMessage('success', 'Status berhasil diperbarui');
            // Use direct header redirect instead of redirect function
            header('Location: service_detail.php?id=' . $serviceId);
            exit();
        } else {
            setFlashMessage('error', 'Gagal memperbarui status');
        }
    } catch (Exception $e) {
        error_log("Update error: " . $e->getMessage());
        setFlashMessage('error', 'Terjadi kesalahan: ' . $e->getMessage());
    }
}

include '../includes/header.php';
?>

<div class="container-fluid my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="services.php">Servis</a></li>
                    <li class="breadcrumb-item active"><?php echo $service['ticket_number']; ?></li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-ticket-alt me-2"></i><?php echo $service['ticket_number']; ?></h2>
                    <p class="text-muted mb-0">Detail informasi servis</p>
                </div>
                <div>
                    <span class="badge status-badge <?php echo getStatusBadge($service['status']); ?> fs-6 me-2">
                        <?php echo getStatusText($service['status']); ?>
                    </span>
                    <a href="services.php?action=edit&id=<?php echo $service['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Service Details -->
        <div class="col-lg-8">
            <!-- Customer Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Informasi Pelanggan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Nama:</td>
                                    <td><?php echo htmlspecialchars($service['customer_name']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Email:</td>
                                    <td><?php echo htmlspecialchars($service['customer_email']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Telepon:</td>
                                    <td><?php echo htmlspecialchars($service['customer_phone'] ?: '-'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Device Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-laptop me-2"></i>Informasi Perangkat</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Jenis Perangkat:</td>
                                    <td>
                                        <i class="fas fa-<?php echo $service['device_type'] === 'laptop' ? 'laptop' : ($service['device_type'] === 'computer' ? 'desktop' : 'mobile-alt'); ?> me-2"></i>
                                        <?php echo ucfirst($service['device_type']); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Merek:</td>
                                    <td><?php echo htmlspecialchars($service['device_brand']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Model:</td>
                                    <td><?php echo htmlspecialchars($service['device_model']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Serial Number:</td>
                                    <td><?php echo htmlspecialchars($service['serial_number'] ?: '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Tanggal Masuk:</td>
                                    <td><?php echo formatDate($service['created_at']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Estimasi Selesai:</td>
                                    <td><?php echo $service['estimated_completion'] ? formatDate($service['estimated_completion']) : 'Belum ditentukan'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Problem Description -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Deskripsi Masalah</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($service['problem_description'])); ?></p>
                </div>
            </div>

            <!-- Service Timeline -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Timeline Servis</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($timeline)): ?>
                    <div class="timeline">
                        <?php foreach ($timeline as $item): ?>
                        <div class="timeline-item <?php echo $item['is_current'] ? 'current' : ($item['is_completed'] ? 'completed' : ''); ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                    <p class="text-muted mb-1"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo formatDate($item['created_at']); ?>
                                    </small>
                                </div>
                                <span class="badge <?php echo getStatusBadge($item['status']); ?>">
                                    <?php echo getStatusText($item['status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada timeline</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notes -->
            <?php if ($service['notes']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Catatan Teknisi</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($service['notes'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column - Status & Actions -->
        <div class="col-lg-4">
            <!-- Status Update -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Update Status</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="update_status" value="1">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending" <?php echo $service['status'] === 'pending' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                                <option value="in_progress" <?php echo $service['status'] === 'in_progress' ? 'selected' : ''; ?>>Sedang Dikerjakan</option>
                                <option value="waiting_parts" <?php echo $service['status'] === 'waiting_parts' ? 'selected' : ''; ?>>Menunggu Spare Part</option>
                                <option value="testing" <?php echo $service['status'] === 'testing' ? 'selected' : ''; ?>>Tahap Testing</option>
                                <option value="completed" <?php echo $service['status'] === 'completed' ? 'selected' : ''; ?>>Selesai</option>
                                <option value="delivered" <?php echo $service['status'] === 'delivered' ? 'selected' : ''; ?>>Sudah Diambil</option>
                                <option value="cancelled" <?php echo $service['status'] === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Tambahkan catatan untuk update ini..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Update Status
                        </button>
                    </form>
                </div>
            </div>

            <!-- Cost Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Informasi Biaya</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-12 mb-3">
                            <small class="text-muted d-block">Estimasi Biaya</small>
                            <h4 class="text-primary mb-0">
                                <?php echo $service['estimated_cost'] ? formatRupiah($service['estimated_cost']) : 'Belum ditentukan'; ?>
                            </h4>
                        </div>
                        <?php if ($service['final_cost']): ?>
                        <div class="col-12">
                            <small class="text-muted d-block">Biaya Final</small>
                            <h4 class="text-success mb-0">
                                <?php echo formatRupiah($service['final_cost']); ?>
                            </h4>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Progress -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Progress</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar <?php echo getStatusBadge($service['status']); ?>" 
                                 role="progressbar" 
                                 style="width: <?php echo $serviceObj->getStatusProgress($service['status']); ?>%"
                                 aria-valuenow="<?php echo $serviceObj->getStatusProgress($service['status']); ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?php echo $serviceObj->getStatusProgress($service['status']); ?>%
                            </div>
                        </div>
                    </div>
                    <p class="text-muted mb-0">
                        <?php echo getStatusText($service['status']); ?>
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Aksi</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="services.php?action=edit&id=<?php echo $service['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Servis
                        </a>
                        <button onclick="printTicket('<?php echo $service['ticket_number']; ?>')" class="btn btn-outline-info">
                            <i class="fas fa-print me-2"></i>Cetak Tiket
                        </button>
                        <button onclick="printServiceDetail(<?php echo $serviceId; ?>)" class="btn btn-outline-primary">
                            <i class="fas fa-print me-2"></i>Cetak Detail
                        </button>
                        <a href="services.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--primary-color);
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
    padding-left: 2rem;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -0.5rem;
    top: 0.5rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: var(--primary-color);
    border: 3px solid white;
    box-shadow: 0 0 0 3px var(--primary-color);
}

.timeline-item.completed::before {
    background: var(--success-color);
    box-shadow: 0 0 0 3px var(--success-color);
}

.timeline-item.current::before {
    background: var(--warning-color);
    box-shadow: 0 0 0 3px var(--warning-color);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
    }
}
</style>

<script>
// Print ticket function
function printTicket(ticketNumber) {
    // Open print ticket page in new window  
    const printUrl = 'print_ticket.php?id=<?php echo $serviceId; ?>';
    window.open(printUrl, '_blank', 'width=400,height=600,scrollbars=yes,resizable=yes');
}

// Print service detail function
function printServiceDetail(serviceId) {
    const printUrl = 'print_service_detail.php?id=' + serviceId;
    window.open(printUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
}
</script>

<?php include '../includes/footer.php'; ?>