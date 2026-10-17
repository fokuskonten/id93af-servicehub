<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/Service.php';

// Require customer login
requireCustomer();

$page_title = 'Buat Tiket Servis';
$serviceObj = new Service();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deviceType = sanitize($_POST['device_type']);
    $deviceBrand = sanitize($_POST['device_brand']);
    $deviceModel = sanitize($_POST['device_model']);
    $serialNumber = sanitize($_POST['serial_number']);
    $problemDescription = sanitize($_POST['problem_description']);
    $priority = sanitize($_POST['priority']);
    
    // Validation
    if (empty($deviceType)) {
        $errors[] = 'Jenis perangkat harus dipilih';
    }
    
    if (empty($deviceBrand)) {
        $errors[] = 'Merek perangkat harus diisi';
    }
    
    if (empty($deviceModel)) {
        $errors[] = 'Model perangkat harus diisi';
    }
    
    if (empty($problemDescription)) {
        $errors[] = 'Deskripsi masalah harus diisi';
    }
    
    if (empty($errors)) {
        $ticketData = [
            'customer_id' => getCurrentUserId(),
            'ticket_number' => generateTicketNumber(),
            'device_type' => $deviceType,
            'device_brand' => $deviceBrand,
            'device_model' => $deviceModel,
            'serial_number' => $serialNumber,
            'problem_description' => $problemDescription,
            'estimated_cost' => null,
            'estimated_completion' => null,
            'status' => 'pending'
        ];
        
        $serviceId = $serviceObj->createService($ticketData);
        if ($serviceId) {
            setFlashMessage('success', 'Tiket servis berhasil dibuat! Kami akan segera menghubungi Anda.');
            header('Location: ticket_detail.php?id=' . $serviceId);
            exit();
        } else {
            $errors[] = 'Terjadi kesalahan saat membuat tiket';
        }
    }
}

include '../includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Buat Tiket Servis</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-plus-circle me-2"></i>Buat Tiket Servis</h2>
                    <p class="text-muted">Isi form di bawah untuk membuat tiket servis baru</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Form Tiket Servis</h5>
                </div>
                <div class="card-body">
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
                        <!-- Device Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-laptop me-2"></i>Informasi Perangkat
                                </h6>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="device_type" class="form-label">Jenis Perangkat *</label>
                                <select class="form-select" id="device_type" name="device_type" required>
                                    <option value="">Pilih Jenis Perangkat</option>
                                    <option value="laptop" <?php echo (isset($_POST['device_type']) && $_POST['device_type'] === 'laptop') ? 'selected' : ''; ?>>
                                        Laptop
                                    </option>
                                    <option value="computer" <?php echo (isset($_POST['device_type']) && $_POST['device_type'] === 'computer') ? 'selected' : ''; ?>>
                                        Komputer/PC
                                    </option>
                                    <option value="handphone" <?php echo (isset($_POST['device_type']) && $_POST['device_type'] === 'handphone') ? 'selected' : ''; ?>>
                                        Handphone
                                    </option>
                                </select>
                                <div class="invalid-feedback">
                                    Pilih jenis perangkat
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="device_brand" class="form-label">Merek *</label>
                                <input type="text" class="form-control" id="device_brand" name="device_brand" 
                                       value="<?php echo isset($_POST['device_brand']) ? htmlspecialchars($_POST['device_brand']) : ''; ?>" 
                                       placeholder="Contoh: ASUS, HP, Samsung" required>
                                <div class="invalid-feedback">
                                    Merek perangkat harus diisi
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="device_model" class="form-label">Model *</label>
                                <input type="text" class="form-control" id="device_model" name="device_model" 
                                       value="<?php echo isset($_POST['device_model']) ? htmlspecialchars($_POST['device_model']) : ''; ?>" 
                                       placeholder="Contoh: VivoBook 14, Pavilion 15" required>
                                <div class="invalid-feedback">
                                    Model perangkat harus diisi
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="serial_number" class="form-label">Serial Number</label>
                                <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                       value="<?php echo isset($_POST['serial_number']) ? htmlspecialchars($_POST['serial_number']) : ''; ?>" 
                                       placeholder="Serial number (jika ada)">
                                <small class="form-text text-muted">Opsional - membantu identifikasi perangkat</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">Prioritas</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="medium" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'medium') ? 'selected' : ''; ?>>
                                        Normal
                                    </option>
                                    <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'high') ? 'selected' : ''; ?>>
                                        Tinggi
                                    </option>
                                    <option value="urgent" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'urgent') ? 'selected' : ''; ?>>
                                        Urgent
                                    </option>
                                    <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'low') ? 'selected' : ''; ?>>
                                        Rendah
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Problem Description -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Deskripsi Masalah
                                </h6>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="problem_description" class="form-label">Jelaskan masalah yang dialami *</label>
                                <textarea class="form-control" id="problem_description" name="problem_description" 
                                          rows="5" placeholder="Jelaskan secara detail masalah yang dialami perangkat Anda..." required><?php echo isset($_POST['problem_description']) ? htmlspecialchars($_POST['problem_description']) : ''; ?></textarea>
                                <div class="invalid-feedback">
                                    Deskripsi masalah harus diisi
                                </div>
                                <small class="form-text text-muted">
                                    Semakin detail informasi yang Anda berikan, semakin cepat kami dapat mendiagnosis masalah
                                </small>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Kembali
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Buat Tiket Servis
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <!-- Service Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Layanan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-clock me-2"></i>Estimasi Waktu</h6>
                        <ul class="list-unstyled mb-0">
                            <li><small>• Diagnosis: 1-2 jam</small></li>
                            <li><small>• Perbaikan ringan: 1-2 hari</small></li>
                            <li><small>• Perbaikan berat: 3-7 hari</small></li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-shield-alt me-2"></i>Garansi</h6>
                        <p class="small mb-0">Garansi 30 hari untuk semua jenis perbaikan</p>
                    </div>
                    
                    <div>
                        <h6><i class="fas fa-tools me-2"></i>Yang Kami Layani</h6>
                        <ul class="list-unstyled mb-0">
                            <li><small>• Perbaikan hardware</small></li>
                            <li><small>• Install ulang sistem</small></li>
                            <li><small>• Upgrade komponen</small></li>
                            <li><small>• Recovery data</small></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contact -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-phone me-2"></i>Butuh Bantuan?</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Hubungi kami jika ada pertanyaan:</p>
                    <div class="d-grid gap-2">
                        <a href="tel:<?php echo getContactPhone(); ?>" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-phone me-2"></i><?php echo getContactPhone(); ?>
                        </a>
                        <a href="https://wa.me/<?php echo str_replace(['+', '-', ' '], '', getContactPhone()); ?>" class="btn btn-outline-success btn-sm" target="_blank">
                            <i class="fab fa-whatsapp me-2"></i>WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>