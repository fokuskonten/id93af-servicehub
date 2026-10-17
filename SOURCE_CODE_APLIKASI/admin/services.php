<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/Service.php';

// Require admin login
requireAdmin();

$page_title = 'Kelola Servis';
$serviceObj = new Service();

// Handle actions
$action = $_GET['action'] ?? 'list';
$serviceId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $data = [
            'customer_id' => (int)$_POST['customer_id'],
            'ticket_number' => generateTicketNumber(),
            'device_type' => sanitize($_POST['device_type']),
            'device_brand' => sanitize($_POST['device_brand']),
            'device_model' => sanitize($_POST['device_model']),
            'serial_number' => sanitize($_POST['serial_number']),
            'problem_description' => sanitize($_POST['problem_description']),
            'estimated_cost' => !empty($_POST['estimated_cost']) ? (float)$_POST['estimated_cost'] : null,
            'estimated_completion' => !empty($_POST['estimated_completion']) ? $_POST['estimated_completion'] : null,
            'status' => 'pending'
        ];
        
        if ($serviceObj->createService($data)) {
            setFlashMessage('success', 'Servis berhasil dibuat');
            header('Location: services.php');
            exit();
        } else {
            setFlashMessage('error', 'Gagal membuat servis');
        }
    } elseif (($action === 'edit' || $action === 'update') && $serviceId) {
        $data = [
            'device_type' => sanitize($_POST['device_type']),
            'device_brand' => sanitize($_POST['device_brand']),
            'device_model' => sanitize($_POST['device_model']),
            'serial_number' => sanitize($_POST['serial_number']),
            'problem_description' => sanitize($_POST['problem_description']),
            'estimated_cost' => !empty($_POST['estimated_cost']) ? (float)$_POST['estimated_cost'] : null,
            'final_cost' => !empty($_POST['final_cost']) ? (float)$_POST['final_cost'] : null,
            'estimated_completion' => !empty($_POST['estimated_completion']) ? $_POST['estimated_completion'] : null,
            'status' => sanitize($_POST['status']),
            'notes' => sanitize($_POST['notes'])
        ];
        
        if ($serviceObj->updateService($serviceId, $data)) {
            setFlashMessage('success', 'Servis berhasil diperbarui');
            header('Location: services.php');
            exit();
        } else {
            setFlashMessage('error', 'Gagal memperbarui servis');
        }
    }
}

// Handle delete
if ($action === 'delete' && $serviceId) {
    if ($serviceObj->deleteService($serviceId)) {
        setFlashMessage('success', 'Servis berhasil dihapus');
    } else {
        setFlashMessage('error', 'Gagal menghapus servis');
    }
    header('Location: services.php');
    exit();
}

// Get data based on action
if ($action === 'list') {
    $services = $serviceObj->getAllServices();
} elseif ($action === 'edit' && $serviceId) {
    $service = $serviceObj->getServiceById($serviceId);
    if (!$service) {
        setFlashMessage('error', 'Servis tidak ditemukan');
        header('Location: services.php');
        exit();
    }
}

// Get customers for dropdown
require_once '../classes/User.php';
$userObj = new User();
$customers = $userObj->getAllCustomers();

include '../includes/header.php';
?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-tools me-2"></i>Kelola Servis</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Servis</li>
                        </ol>
                    </nav>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="services.php?action=create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tambah Servis
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($action === 'list'): ?>
    <!-- Services List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Servis</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($services)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Ticket</th>
                                    <th>Customer</th>
                                    <th>Device</th>
                                    <th>Status</th>
                                    <th>Estimasi Biaya</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
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
                                    <td>
                                        <?php echo $service['estimated_cost'] ? formatRupiah($service['estimated_cost']) : '-'; ?>
                                    </td>
                                    <td><?php echo formatDate($service['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="service_detail.php?id=<?php echo $service['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="services.php?action=edit&id=<?php echo $service['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="printServiceDetail(<?php echo $service['id']; ?>)" 
                                                    class="btn btn-sm btn-outline-secondary" title="Cetak Detail">
                                                <i class="fas fa-file-alt"></i>
                                            </button>
                                            <button onclick="printTicket('<?php echo $service['ticket_number']; ?>')" 
                                                    class="btn btn-sm btn-outline-info" title="Cetak Tiket">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <a href="services.php?action=delete&id=<?php echo $service['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger btn-delete" 
                                               title="Hapus"
                                               data-title="Hapus Servis"
                                               data-text="Apakah Anda yakin ingin menghapus servis ini?">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada servis</p>
                        <a href="services.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Servis Pertama
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($action === 'create' || $action === 'edit'): ?>
    <!-- Create/Edit Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-<?php echo $action === 'create' ? 'plus' : 'edit'; ?> me-2"></i>
                        <?php echo $action === 'create' ? 'Tambah' : 'Edit'; ?> Servis
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <!-- Customer Selection -->
                        <?php if ($action === 'create'): ?>
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Customer *</label>
                            <select class="form-select" id="customer_id" name="customer_id" required>
                                <option value="">Pilih Customer</option>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>">
                                    <?php echo htmlspecialchars($customer['name'] . ' - ' . $customer['email']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Pilih customer</div>
                        </div>
                        <?php endif; ?>

                        <!-- Device Information -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="device_type" class="form-label">Jenis Perangkat *</label>
                                <select class="form-select" id="device_type" name="device_type" required>
                                    <option value="">Pilih Jenis</option>
                                    <option value="laptop" <?php echo (isset($service) && $service['device_type'] === 'laptop') ? 'selected' : ''; ?>>Laptop</option>
                                    <option value="computer" <?php echo (isset($service) && $service['device_type'] === 'computer') ? 'selected' : ''; ?>>Komputer</option>
                                    <option value="handphone" <?php echo (isset($service) && $service['device_type'] === 'handphone') ? 'selected' : ''; ?>>Handphone</option>
                                </select>
                                <div class="invalid-feedback">Pilih jenis perangkat</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="device_brand" class="form-label">Merek *</label>
                                <input type="text" class="form-control" id="device_brand" name="device_brand" 
                                       value="<?php echo isset($service) ? htmlspecialchars($service['device_brand']) : ''; ?>" required>
                                <div class="invalid-feedback">Merek harus diisi</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="device_model" class="form-label">Model *</label>
                                <input type="text" class="form-control" id="device_model" name="device_model" 
                                       value="<?php echo isset($service) ? htmlspecialchars($service['device_model']) : ''; ?>" required>
                                <div class="invalid-feedback">Model harus diisi</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="serial_number" class="form-label">Serial Number</label>
                            <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                   value="<?php echo isset($service) ? htmlspecialchars($service['serial_number']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="problem_description" class="form-label">Deskripsi Masalah *</label>
                            <textarea class="form-control" id="problem_description" name="problem_description" 
                                      rows="4" required><?php echo isset($service) ? htmlspecialchars($service['problem_description']) : ''; ?></textarea>
                            <div class="invalid-feedback">Deskripsi masalah harus diisi</div>
                        </div>

                        <!-- Cost and Status -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="estimated_cost" class="form-label">Estimasi Biaya</label>
                                <input type="number" class="form-control" id="estimated_cost" name="estimated_cost" 
                                       value="<?php echo isset($service) ? $service['estimated_cost'] : ''; ?>" min="0" step="1000">
                            </div>
                            <?php if ($action === 'edit'): ?>
                            <div class="col-md-6 mb-3">
                                <label for="final_cost" class="form-label">Biaya Final</label>
                                <input type="number" class="form-control" id="final_cost" name="final_cost" 
                                       value="<?php echo isset($service) ? $service['final_cost'] : ''; ?>" min="0" step="1000">
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="estimated_completion" class="form-label">Estimasi Selesai</label>
                                <input type="datetime-local" class="form-control" id="estimated_completion" name="estimated_completion" 
                                       value="<?php echo isset($service) && $service['estimated_completion'] ? date('Y-m-d\TH:i', strtotime($service['estimated_completion'])) : ''; ?>">
                            </div>
                            <?php if ($action === 'edit'): ?>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="pending" <?php echo (isset($service) && $service['status'] === 'pending') ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                                    <option value="in_progress" <?php echo (isset($service) && $service['status'] === 'in_progress') ? 'selected' : ''; ?>>Sedang Dikerjakan</option>
                                    <option value="waiting_parts" <?php echo (isset($service) && $service['status'] === 'waiting_parts') ? 'selected' : ''; ?>>Menunggu Spare Part</option>
                                    <option value="testing" <?php echo (isset($service) && $service['status'] === 'testing') ? 'selected' : ''; ?>>Tahap Testing</option>
                                    <option value="completed" <?php echo (isset($service) && $service['status'] === 'completed') ? 'selected' : ''; ?>>Selesai</option>
                                    <option value="delivered" <?php echo (isset($service) && $service['status'] === 'delivered') ? 'selected' : ''; ?>>Sudah Diambil</option>
                                    <option value="cancelled" <?php echo (isset($service) && $service['status'] === 'cancelled') ? 'selected' : ''; ?>>Dibatalkan</option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($action === 'edit'): ?>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan Teknisi</label>
                            <textarea class="form-control" id="notes" name="notes" 
                                      rows="3"><?php echo isset($service) ? htmlspecialchars($service['notes']) : ''; ?></textarea>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between">
                            <a href="services.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?php echo $action === 'create' ? 'Simpan' : 'Perbarui'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">Tips untuk mengisi form:</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Pilih customer yang tepat</li>
                        <li><i class="fas fa-check text-success me-2"></i>Isi deskripsi masalah dengan detail</li>
                        <li><i class="fas fa-check text-success me-2"></i>Estimasi biaya membantu customer</li>
                        <li><i class="fas fa-check text-success me-2"></i>Set estimasi waktu yang realistis</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('.data-table').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        },
        order: [[5, 'desc']] // Sort by date
    });
});

// Print ticket function
function printTicket(ticketNumber) {
    // Open print ticket page in new window
    const printUrl = 'print_ticket.php?id=' + getServiceIdByTicket(ticketNumber);
    window.open(printUrl, '_blank', 'width=400,height=600,scrollbars=yes,resizable=yes');
}

// Helper function to get service ID by ticket number (if needed)
function getServiceIdByTicket(ticketNumber) {
    // Find the service ID from the current page data
    const rows = document.querySelectorAll('table tbody tr');
    for (let row of rows) {
        const ticketCell = row.querySelector('td:first-child strong');
        if (ticketCell && ticketCell.textContent.trim() === ticketNumber) {
            // Get the service ID from the detail link
            const detailLink = row.querySelector('a[href*="service_detail.php?id="]');
            if (detailLink) {
                const href = detailLink.getAttribute('href');
                const match = href.match(/id=(\d+)/);
                return match ? match[1] : '';
            }
        }
    }
    return '';
}

// Print service detail function
function printServiceDetail(serviceId) {
    const printUrl = 'print_service_detail.php?id=' + serviceId;
    window.open(printUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
}
</script>

<?php include '../includes/footer.php'; ?>