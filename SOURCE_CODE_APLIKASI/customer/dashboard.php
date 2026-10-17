<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/Service.php';

// Require customer login
requireCustomer();

$page_title = 'Customer Dashboard';
$serviceObj = new Service();

// Get customer's tickets
$customerId = getCurrentUserId();
$tickets = $serviceObj->getServicesByCustomer($customerId);

// Get statistics
$totalTickets = count($tickets);
$pendingTickets = $serviceObj->getCustomerTicketCount($customerId, 'pending');
$inProgressTickets = $serviceObj->getCustomerTicketCount($customerId, 'in_progress');
$completedTickets = $serviceObj->getCustomerTicketCount($customerId, 'completed');

include '../includes/header.php';
?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
                    <p class="text-muted">Selamat datang, <?php echo getCurrentUserName(); ?>!</p>
                </div>
                <a href="create_ticket.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Buat Tiket Baru
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card text-white bg-secondary">
                <div class="card-body text-center">
                    <i class="fas fa-list fa-3x mb-3"></i>
                    <div class="h2"><?php echo $totalTickets; ?></div>
                    <div>Total Tiket</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card text-white bg-warning">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-3x mb-3"></i>
                    <div class="h2"><?php echo $pendingTickets; ?></div>
                    <div>Menunggu</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body text-center">
                    <i class="fas fa-tools fa-3x mb-3"></i>
                    <div class="h2"><?php echo $inProgressTickets; ?></div>
                    <div>Dikerjakan</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card text-white bg-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <div class="h2"><?php echo $completedTickets; ?></div>
                    <div>Selesai</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tickets -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Tiket Terbaru</h5>
                    <a href="tickets.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($tickets)): ?>
                    <?php $recentTickets = array_slice($tickets, 0, 3); ?>
                    <?php foreach ($recentTickets as $ticket): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-hashtag me-1"></i><?php echo $ticket['ticket_number']; ?>
                                </h6>
                                <span class="badge <?php echo getStatusBadge($ticket['status']); ?>">
                                    <?php echo getStatusText($ticket['status']); ?>
                                </span>
                            </div>
                            
                            <p class="text-muted mb-2">
                                <i class="fas fa-<?php echo $ticket['device_type'] === 'laptop' ? 'laptop' : ($ticket['device_type'] === 'computer' ? 'desktop' : 'mobile-alt'); ?> me-2"></i>
                                <?php echo htmlspecialchars($ticket['device_brand'] . ' ' . $ticket['device_model']); ?>
                            </p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">Progress:</small>
                                    <div class="progress mb-2">
                                        <div class="progress-bar <?php echo getStatusBadge($ticket['status']); ?>" 
                                             style="width: <?php echo $serviceObj->getStatusProgress($ticket['status']); ?>%">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <small class="text-muted"><?php echo formatDate($ticket['created_at']); ?></small><br>
                                    <a href="ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                        <h5>Belum Ada Tiket</h5>
                        <p class="text-muted mb-3">Anda belum memiliki tiket servis. Buat tiket baru untuk memulai.</p>
                        <a href="create_ticket.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Buat Tiket Pertama
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions & Info -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="create_ticket.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Buat Tiket Baru
                        </a>
                        <a href="tickets.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Lihat Semua Tiket
                        </a>
                        <a href="../public/track.php" class="btn btn-outline-info">
                            <i class="fas fa-search me-2"></i>Lacak Servis
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-phone me-2"></i>Butuh Bantuan?</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Hubungi kami jika ada pertanyaan:</p>
                    <div class="d-grid gap-2">
                        <a href="tel:<?php echo getContactPhone(); ?>" class="btn btn-outline-success">
                            <i class="fas fa-phone me-2"></i><?php echo getContactPhone(); ?>
                        </a>
                        <a href="https://wa.me/<?php echo str_replace(['+', '-', ' '], '', getContactPhone()); ?>" class="btn btn-outline-success" target="_blank">
                            <i class="fab fa-whatsapp me-2"></i>WhatsApp
                        </a>
                        <a href="mailto:<?php echo getContactEmail(); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-2"></i>Email
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Service Hours -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Jam Operasional</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td>Senin - Jumat</td>
                            <td class="text-end">08:00 - 17:00</td>
                        </tr>
                        <tr>
                            <td>Sabtu</td>
                            <td class="text-end">08:00 - 14:00</td>
                        </tr>
                        <tr>
                            <td>Minggu</td>
                            <td class="text-end text-danger">Tutup</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>