<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/Service.php';

// Require customer login
requireCustomer();

$page_title = 'Tiket Servis Saya';
$serviceObj = new Service();

// Get customer's tickets
$customerId = getCurrentUserId();
$tickets = $serviceObj->getServicesByCustomer($customerId);

include '../includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-ticket-alt me-2"></i>Tiket Servis Saya</h2>
                    <p class="text-muted">Pantau progress perbaikan perangkat Anda</p>
                </div>
                <a href="create_ticket.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Buat Tiket Baru
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($tickets)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card text-center py-5">
                <div class="card-body">
                    <i class="fas fa-ticket-alt fa-5x text-muted mb-4"></i>
                    <h4>Belum Ada Tiket Servis</h4>
                    <p class="text-muted mb-4">Anda belum memiliki tiket servis. Buat tiket baru untuk memulai proses servis perangkat Anda.</p>
                    <a href="create_ticket.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Buat Tiket Pertama
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($tickets as $ticket): ?>
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="card ticket-card <?php echo strtolower(str_replace('_', '-', $ticket['status'])); ?> h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-hashtag me-1"></i><?php echo $ticket['ticket_number']; ?>
                    </h6>
                    <span class="badge status-badge <?php echo getStatusBadge($ticket['status']); ?>">
                        <?php echo getStatusText($ticket['status']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="card-title">
                            <i class="fas fa-<?php echo $ticket['device_type'] === 'laptop' ? 'laptop' : ($ticket['device_type'] === 'computer' ? 'desktop' : 'mobile-alt'); ?> me-2"></i>
                            <?php echo ucfirst($ticket['device_type']); ?> - <?php echo htmlspecialchars($ticket['device_brand']); ?>
                        </h6>
                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($ticket['device_model']); ?></p>
                    </div>

                    <div class="mb-3">
                        <strong>Keluhan:</strong>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars(substr($ticket['problem_description'], 0, 100)); ?><?php echo strlen($ticket['problem_description']) > 100 ? '...' : ''; ?></p>
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

                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Estimasi Biaya</small>
                            <strong class="text-primary">
                                <?php echo $ticket['estimated_cost'] ? formatRupiah($ticket['estimated_cost']) : 'Belum ditentukan'; ?>
                            </strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Tanggal Masuk</small>
                            <strong><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></strong>
                        </div>
                    </div>

                    <?php if ($ticket['estimated_completion']): ?>
                    <div class="alert alert-info py-2 mb-3">
                        <small>
                            <i class="fas fa-clock me-1"></i>
                            Estimasi selesai: <strong><?php echo formatDate($ticket['estimated_completion']); ?></strong>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo formatDate($ticket['updated_at']); ?>
                        </small>
                        <div>
                            <a href="https://wa.me/<?php echo str_replace(['+', '-', ' '], '', getContactPhone()); ?>?text=Halo, saya ingin bertanya tentang tiket <?php echo $ticket['ticket_number']; ?>" 
                            <a href="tel:<?php echo getContactPhone(); ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>Detail
                            </a>
                            <?php if ($ticket['status'] === 'completed'): ?>
                            <button class="btn btn-sm btn-success" 
                                    onclick="confirmDelivery(<?php echo $ticket['id']; ?>)">
                                <i class="fas fa-check me-1"></i>Konfirmasi Terima
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Statistics Row -->
    <div class="row mt-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card warning text-center">
                <div class="card-body">
                    <i class="fas fa-clock icon"></i>
                    <div class="number"><?php echo $serviceObj->getCustomerTicketCount($customerId, 'pending'); ?></div>
                    <div class="label">Menunggu</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card primary text-center">
                <div class="card-body">
                    <i class="fas fa-tools icon"></i>
                    <div class="number"><?php echo $serviceObj->getCustomerTicketCount($customerId, 'in_progress'); ?></div>
                    <div class="label">Dikerjakan</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card success text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle icon"></i>
                    <div class="number"><?php echo $serviceObj->getCustomerTicketCount($customerId, 'completed'); ?></div>
                    <div class="label">Selesai</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card text-center" style="background: linear-gradient(135deg, #6c757d, #495057);">
                <div class="card-body">
                    <i class="fas fa-list icon"></i>
                    <div class="number"><?php echo count($tickets); ?></div>
                    <div class="label">Total Tiket</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
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
let currentTicketId = null;

function confirmDelivery(ticketId) {
    currentTicketId = ticketId;
    $('#confirmDeliveryModal').modal('show');
}

$('#confirmDeliveryBtn').on('click', function() {
    if (currentTicketId) {
        updateTicketStatus(currentTicketId, 'delivered', $(this));
        $('#confirmDeliveryModal').modal('hide');
    }
});

// Auto-refresh every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php include '../includes/footer.php'; ?>