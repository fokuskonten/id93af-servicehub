<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/Service.php';

$page_title = 'Lacak Servis';
$serviceObj = new Service();

// Initialize variables
$error = '';
$ticket = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketNumber = sanitize($_POST['ticket_number']);
    
    if (empty($ticketNumber)) {
        $error = 'Nomor tiket harus diisi';
    } else {
        $ticket = $serviceObj->getServiceByTicketNumber($ticketNumber);
        if (!$ticket) {
            $error = 'Tiket tidak ditemukan. Pastikan nomor tiket benar.';
        }
    }
}

include '../includes/header.php';
?>

<style>
/* Override sidebar styles for public pages */
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

<div class="container-fluid px-5 my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="text-center mb-5">
                <h2 class="fw-bold">
                    <i class="fas fa-search me-2"></i>Lacak Status Servis
                </h2>
                <p class="text-muted">Masukkan nomor tiket untuk melihat status perbaikan perangkat Anda</p>
            </div>
            
            <!-- Search Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Cari Tiket Servis</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row align-items-end">
                            <div class="col-md-8 mb-3">
                                <label for="ticket_number" class="form-label">Nomor Tiket</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                    <input type="text" class="form-control form-control-lg" id="ticket_number" 
                                           name="ticket_number" placeholder="Contoh: TF20241201001" 
                                           value="<?php echo isset($_POST['ticket_number']) ? htmlspecialchars($_POST['ticket_number']) : ''; ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        Nomor tiket harus diisi
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-search me-2"></i>Lacak
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Ticket Result -->
            <?php if ($ticket): ?>
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-ticket-alt me-2"></i><?php echo $ticket['ticket_number']; ?>
                        </h5>
                        <span class="badge <?php echo getStatusBadge($ticket['status']); ?> fs-6">
                            <?php echo getStatusText($ticket['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Device Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Informasi Perangkat</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td><strong>Jenis:</strong></td>
                                    <td>
                                        <i class="fas fa-<?php echo $ticket['device_type'] === 'laptop' ? 'laptop' : ($ticket['device_type'] === 'computer' ? 'desktop' : 'mobile-alt'); ?> me-2"></i>
                                        <?php echo ucfirst($ticket['device_type']); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Merek:</strong></td>
                                    <td><?php echo htmlspecialchars($ticket['device_brand']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Model:</strong></td>
                                    <td><?php echo htmlspecialchars($ticket['device_model']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Status & Waktu</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td><strong>Tanggal Masuk:</strong></td>
                                    <td><?php echo formatDate($ticket['created_at']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Estimasi Selesai:</strong></td>
                                    <td><?php echo $ticket['estimated_completion'] ? formatDate($ticket['estimated_completion']) : 'Belum ditentukan'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Estimasi Biaya:</strong></td>
                                    <td><?php echo $ticket['estimated_cost'] ? formatRupiah($ticket['estimated_cost']) : 'Belum ditentukan'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-primary mb-0">Progress Perbaikan</h6>
                            <span class="text-muted"><?php echo $serviceObj->getStatusProgress($ticket['status']); ?>%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar <?php echo getStatusBadge($ticket['status']); ?>" 
                                 role="progressbar" 
                                 style="width: <?php echo $serviceObj->getStatusProgress($ticket['status']); ?>%"
                                 aria-valuenow="<?php echo $serviceObj->getStatusProgress($ticket['status']); ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Problem Description -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-2">Deskripsi Masalah</h6>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($ticket['problem_description'])); ?></p>
                    </div>
                    
                    <!-- Timeline -->
                    <?php 
                    $timeline = $serviceObj->getServiceTimeline($ticket['id']);
                    if (!empty($timeline)): 
                    ?>
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">Timeline Perbaikan</h6>
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
                    </div>
                    <?php endif; ?>
                    
                    <!-- Contact Info -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Butuh Informasi Lebih Lanjut?</h6>
                        <p class="mb-2">Hubungi kami untuk pertanyaan tentang tiket ini:</p>
                        <div class="d-flex gap-2">
                            <a href="tel:<?php echo getContactPhone(); ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-phone me-1"></i>Telepon
                            </a>
                            <a href="https://wa.me/<?php echo str_replace(['+', '-', ' '], '', getContactPhone()); ?>?text=Halo, saya ingin bertanya tentang tiket <?php echo $ticket['ticket_number']; ?>" 
                               class="btn btn-sm btn-outline-success" target="_blank">
                                <i class="fab fa-whatsapp me-1"></i>WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function fillTicketNumber(ticketNumber) {
    document.getElementById('ticket_number').value = ticketNumber;
}
</script>

<?php include '../includes/footer.php'; ?>