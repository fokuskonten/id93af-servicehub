<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/Service.php';

// Require customer login
requireCustomer();

$page_title = 'Detail Tiket Servis';
$serviceObj = new Service();

// Get ticket ID from URL
$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$ticketId) {
    setFlashMessage('error', 'ID tiket tidak valid');
    redirect('customer/tickets.php');
}

// Get ticket details
$ticket = $serviceObj->getServiceById($ticketId);

if (!$ticket || $ticket['customer_id'] != getCurrentUserId()) {
    setFlashMessage('error', 'Tiket tidak ditemukan atau Anda tidak memiliki akses');
    redirect('customer/tickets.php');
}

// Get service history/timeline
$timeline = $serviceObj->getServiceTimeline($ticketId);

include '../includes/header.php';
?>

<div class="container my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="tickets.php">Tiket Saya</a></li>
                    <li class="breadcrumb-item active"><?php echo $ticket['ticket_number']; ?></li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-ticket-alt me-2"></i><?php echo $ticket['ticket_number']; ?></h2>
                    <p class="text-muted mb-0">Detail informasi tiket servis Anda</p>
                </div>
                <div>
                    <span class="badge status-badge <?php echo getStatusBadge($ticket['status']); ?> fs-6 me-2">
                        <?php echo getStatusText($ticket['status']); ?>
                    </span>
                    <button class="btn btn-outline-primary btn-sm" onclick="copyToClipboard('<?php echo $ticket['ticket_number']; ?>')">
                        <i class="fas fa-copy me-1"></i>Salin No. Tiket
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Ticket Details -->
        <div class="col-lg-8">
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
                                        <i class="fas fa-<?php echo $ticket['device_type'] === 'laptop' ? 'laptop' : ($ticket['device_type'] === 'computer' ? 'desktop' : 'mobile-alt'); ?> me-2"></i>
                                        <?php echo ucfirst($ticket['device_type']); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Merek:</td>
                                    <td><?php echo htmlspecialchars($ticket['device_brand']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Model:</td>
                                    <td><?php echo htmlspecialchars($ticket['device_model']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Serial Number:</td>
                                    <td><?php echo htmlspecialchars($ticket['serial_number'] ?: '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Tanggal Masuk:</td>
                                    <td><?php echo formatDate($ticket['created_at']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Estimasi Selesai:</td>
                                    <td><?php echo $ticket['estimated_completion'] ? formatDate($ticket['estimated_completion']) : 'Belum ditentukan'; ?></td>
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
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($ticket['problem_description'])); ?></p>
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
                        <?php foreach ($timeline as $index => $item): ?>
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
                        <p class="text-muted">Belum ada update timeline</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notes/Comments -->
            <?php if ($ticket['notes']): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Catatan Teknisi</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($ticket['notes'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column - Status & Actions -->
        <div class="col-lg-4">
            <!-- Status Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Status Servis</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <span class="badge status-badge <?php echo getStatusBadge($ticket['status']); ?> fs-5">
                            <?php echo getStatusText($ticket['status']); ?>
                        </span>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">Progress</small>
                            <small class="text-muted"><?php echo $serviceObj->getStatusProgress($ticket['status']); ?>%</small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar <?php echo getStatusBadge($ticket['status']); ?>" 
                                 role="progressbar" 
                                 style="width: <?php echo $serviceObj->getStatusProgress($ticket['status']); ?>%"
                                 aria-valuenow="<?php echo $serviceObj->getStatusProgress($ticket['status']); ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                    </div>

                    <?php if ($ticket['status'] === 'completed'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        Perangkat Anda sudah selesai diperbaiki dan siap diambil!
                    </div>
                    <button class="btn btn-success w-100" onclick="confirmDelivery(<?php echo $ticket['id']; ?>)">
                        <i class="fas fa-check me-2"></i>Konfirmasi Sudah Diterima
                    </button>
                    <?php elseif ($ticket['status'] === 'delivered'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-check-double me-2"></i>
                        Perangkat sudah diterima. Terima kasih!
                    </div>
                    <?php endif; ?>
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
                                <?php echo $ticket['estimated_cost'] ? formatRupiah($ticket['estimated_cost']) : 'Belum ditentukan'; ?>
                            </h4>
                        </div>
                        <?php if ($ticket['final_cost']): ?>
                        <div class="col-12">
                            <small class="text-muted d-block">Biaya Final</small>
                            <h4 class="text-success mb-0">
                                <?php echo formatRupiah($ticket['final_cost']); ?>
                            </h4>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($ticket['status'] === 'completed' && !$ticket['final_cost']): ?>
                    <div class="alert alert-warning mt-3">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Biaya final akan dikonfirmasi saat pengambilan
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-phone me-2"></i>Butuh Bantuan?</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Hubungi kami jika ada pertanyaan:</p>
                    <div class="d-grid gap-2">
                        <a href="tel:<?php echo getContactPhone(); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-phone me-2"></i>Telepon
                        </a>
                        <a href="https://wa.me/<?php echo str_replace(['+', '-', ' '], '', getContactPhone()); ?>?text=Halo, saya ingin bertanya tentang tiket <?php echo $ticket['ticket_number']; ?>" 
                           class="btn btn-outline-success" target="_blank">
                            <i class="fab fa-whatsapp me-2"></i>WhatsApp
                        </a>
                        <a href="mailto:<?php echo getContactEmail(); ?>" class="btn btn-outline-primary">
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Aksi</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-print" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Cetak Detail
                        </button>
                        <a href="tickets.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Tiket
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delivery Modal -->
<div class="modal fade" id="confirmDeliveryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Penerimaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda sudah menerima perangkat yang telah diperbaiki?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Setelah konfirmasi, status tiket akan berubah menjadi "Sudah Diambil" dan tidak dapat diubah lagi.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="confirmDeliveryBtn">
                    <i class="fas fa-check me-2"></i>Ya, Sudah Diterima
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentTicketId = <?php echo $ticketId; ?>;

function confirmDelivery(ticketId) {
    $('#confirmDeliveryModal').modal('show');
}

$('#confirmDeliveryBtn').on('click', function() {
    updateTicketStatus(currentTicketId, 'delivered', $(this));
    $('#confirmDeliveryModal').modal('hide');
});

// Auto-refresh every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php include '../includes/footer.php'; ?>