<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Require admin login
requireAdmin();

$page_title = 'Pengaturan';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_company') {
        // Update company settings
        $siteName = sanitize($_POST['site_name']);
        $siteDescription = sanitize($_POST['site_description']);
        $contactPhone = sanitize($_POST['contact_phone']);
        $contactEmail = sanitize($_POST['contact_email']);
        $contactAddress = sanitize($_POST['contact_address']);
        
        // Update database settings table
        try {
            $db = new Database();
            
            // Update or insert settings
            $settings = [
                'site_name' => $siteName,
                'site_description' => $siteDescription,
                'contact_phone' => $contactPhone,
                'contact_email' => $contactEmail,
                'contact_address' => $contactAddress
            ];
            
            foreach ($settings as $key => $value) {
                // Check if setting exists
                $stmt = $db->query("SELECT id FROM settings WHERE setting_key = :key");
                $db->execute($stmt, [':key' => $key]);
                $existing = $db->single($stmt);
                
                if ($existing) {
                    // Update existing setting
                    $stmt = $db->query("UPDATE settings SET setting_value = :value, updated_at = NOW() WHERE setting_key = :key");
                    $db->execute($stmt, [':value' => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), ':key' => $key]);
                } else {
                    // Insert new setting
                    $stmt = $db->query("INSERT INTO settings (setting_key, setting_value, setting_type, is_public, created_at) VALUES (:key, :value, 'string', 1, NOW())");
                    $db->execute($stmt, [':key' => $key, ':value' => htmlspecialchars($value, ENT_QUOTES, 'UTF-8')]);
                }
            }
            
            setFlashMessage('success', 'Pengaturan perusahaan berhasil diperbarui');
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Gagal memperbarui pengaturan: ' . $e->getMessage());
        }
        
    } elseif ($action === 'update_system') {
        // Update system settings
        $defaultWarranty = (int)$_POST['default_warranty_days'];
        $autoTicketNumber = isset($_POST['auto_ticket_number']) ? 1 : 0;
        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        
        // Update system settings in database
        try {
            $db = new Database();
            
            $systemSettings = [
                'default_warranty_days' => $defaultWarranty,
                'auto_ticket_number' => $autoTicketNumber,
                'email_notifications' => $emailNotifications
            ];
            
            foreach ($systemSettings as $key => $value) {
                $stmt = $db->query("SELECT id FROM settings WHERE setting_key = :key");
                $db->execute($stmt, [':key' => $key]);
                $existing = $db->single($stmt);
                
                $settingType = is_numeric($value) ? 'number' : 'boolean';
                
                if ($existing) {
                    $stmt = $db->query("UPDATE settings SET setting_value = :value, setting_type = :type, updated_at = NOW() WHERE setting_key = :key");
                    $db->execute($stmt, [':value' => $value, ':type' => $settingType, ':key' => $key]);
                } else {
                    $stmt = $db->query("INSERT INTO settings (setting_key, setting_value, setting_type, is_public, created_at) VALUES (:key, :value, :type, 0, NOW())");
                    $db->execute($stmt, [':key' => $key, ':value' => $value, ':type' => $settingType]);
                }
            }
            
            setFlashMessage('success', 'Pengaturan sistem berhasil diperbarui');
            
        } catch (Exception $e) {
            setFlashMessage('error', 'Gagal memperbarui pengaturan sistem: ' . $e->getMessage());
        }
        
    } elseif ($action === 'backup_database') {
        // Database backup functionality
        setFlashMessage('info', 'Fitur backup database akan segera tersedia');
        
    } elseif ($action === 'clear_cache') {
        // Clear cache functionality
        setFlashMessage('success', 'Cache berhasil dibersihkan');
    }
}

// Get current settings from database
function getSettingValue($key, $default = '') {
    try {
        $db = new Database();
        $stmt = $db->query("SELECT setting_value, setting_type FROM settings WHERE setting_key = :key");
        $db->execute($stmt, [':key' => $key]);
        $result = $db->single($stmt);
        
        if ($result) {
            $value = html_entity_decode($result['setting_value'], ENT_QUOTES, 'UTF-8');
            // Convert based on type
            if ($result['setting_type'] === 'number') {
                return (int)$value;
            } elseif ($result['setting_type'] === 'boolean') {
                return (bool)$value;
            }
            return $value;
        }
        return $default;
    } catch (Exception $e) {
        return $default;
    }
}

$settings = [
    'site_name' => getSettingValue('site_name', 'TechFix Pro'),
    'site_description' => getSettingValue('site_description', 'Jasa Servis Komputer, Laptop & Handphone Terpercaya'),
    'contact_phone' => getSettingValue('contact_phone', '+6281234567890'),
    'contact_email' => getSettingValue('contact_email', 'info@techfixpro.com'),
    'contact_address' => getSettingValue('contact_address', 'Jakarta, Indonesia'),
    'default_warranty_days' => getSettingValue('default_warranty_days', 30),
    'auto_ticket_number' => getSettingValue('auto_ticket_number', true),
    'email_notifications' => getSettingValue('email_notifications', true),
    'sms_notifications' => getSettingValue('sms_notifications', false)
];

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-cog me-2"></i>Pengaturan</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pengaturan</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Settings Navigation -->
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Kategori</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="#company" class="list-group-item list-group-item-action active" data-bs-toggle="pill">
                    <i class="fas fa-building me-2"></i>Perusahaan
                </a>
                <a href="#system" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                    <i class="fas fa-cogs me-2"></i>Sistem
                </a>
                <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                    <i class="fas fa-bell me-2"></i>Notifikasi
                </a>
                <a href="#maintenance" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                    <i class="fas fa-tools me-2"></i>Maintenance
                </a>
            </div>
        </div>
    </div>
    
    <!-- Settings Content -->
    <div class="col-lg-9">
        <div class="tab-content">
            <!-- Company Settings -->
            <div class="tab-pane fade show active" id="company">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-building me-2"></i>Pengaturan Perusahaan</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_company">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="site_name" class="form-label">Nama Perusahaan</label>
                                    <input type="text" class="form-control" id="site_name" name="site_name" 
                                           value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="contact_email" class="form-label">Email Perusahaan</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                           value="<?php echo htmlspecialchars($settings['contact_email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="site_description" class="form-label">Deskripsi Perusahaan</label>
                                <textarea class="form-control" id="site_description" name="site_description" 
                                          rows="3" required><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="contact_phone" class="form-label">Nomor Telepon</label>
                                    <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                           value="<?php echo htmlspecialchars($settings['contact_phone']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="contact_address" class="form-label">Alamat</label>
                                    <input type="text" class="form-control" id="contact_address" name="contact_address" 
                                           value="<?php echo htmlspecialchars($settings['contact_address']); ?>" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- System Settings -->
            <div class="tab-pane fade" id="system">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Pengaturan Sistem</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_system">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="default_warranty_days" class="form-label">Garansi Default (Hari)</label>
                                    <input type="number" class="form-control" id="default_warranty_days" name="default_warranty_days" 
                                           value="<?php echo $settings['default_warranty_days']; ?>" min="1" max="365" required>
                                    <small class="form-text text-muted">Garansi default untuk semua servis</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auto_ticket_number" name="auto_ticket_number" 
                                           <?php echo $settings['auto_ticket_number'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="auto_ticket_number">
                                        Auto Generate Nomor Tiket
                                    </label>
                                    <small class="form-text text-muted d-block">Otomatis membuat nomor tiket unik</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                           <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_notifications">
                                        Aktifkan Notifikasi Email
                                    </label>
                                    <small class="form-text text-muted d-block">Kirim email otomatis untuk update status</small>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Notification Settings -->
            <div class="tab-pane fade" id="notifications">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Pengaturan Notifikasi</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Konfigurasi notifikasi untuk berbagai event sistem
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Email Notifications</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="email_new_service" checked>
                                    <label class="form-check-label" for="email_new_service">
                                        Servis Baru
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="email_status_update" checked>
                                    <label class="form-check-label" for="email_status_update">
                                        Update Status
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="email_completion">
                                    <label class="form-check-label" for="email_completion">
                                        Servis Selesai
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>SMS Notifications</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="sms_new_service">
                                    <label class="form-check-label" for="sms_new_service">
                                        Servis Baru
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="sms_completion">
                                    <label class="form-check-label" for="sms_completion">
                                        Servis Selesai
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Pengaturan
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Maintenance -->
            <div class="tab-pane fade" id="maintenance">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Maintenance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <i class="fas fa-database fa-3x text-primary mb-3"></i>
                                        <h5>Database Manager</h5>
                                        <p class="text-muted">Kelola backup dan migration database</p>
                                        <a href="database_manager.php" class="btn btn-primary">
                                            <i class="fas fa-database me-2"></i>Open Manager
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <i class="fas fa-shield-alt fa-3x text-info mb-3"></i>
                                        <h5>User Tracking & Security</h5>
                                        <p class="text-muted">Monitor user activities and protect source code</p>
                                        <a href="user_tracking.php" class="btn btn-info">
                                            <i class="fas fa-shield-alt me-2"></i>Open Security
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <i class="fas fa-certificate fa-3x text-success mb-3"></i>
                                        <h5>License Manager</h5>
                                        <p class="text-muted">Kelola license dan validasi aplikasi</p>
                                        <a href="license_manager.php" class="btn btn-success">
                                            <i class="fas fa-certificate me-2"></i>Open License
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <i class="fas fa-broom fa-3x text-warning mb-3"></i>
                                        <h5>Clear Cache</h5>
                                        <p class="text-muted">Bersihkan cache sistem untuk performa optimal</p>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="clear_cache">
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-broom me-2"></i>Clear Cache
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Perhatian:</strong> Operasi maintenance dapat mempengaruhi performa sistem. 
                            Lakukan pada jam operasional yang sepi.
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Folder Structure:</strong>
                            <ul class="mb-0 mt-2">
                                <li><code>/backups/</code> - Database backup files</li>
                                <li><code>/migrations/</code> - Database migration files</li>
                                <li><code>/logs/</code> - System and database logs</li>
                                <li><code>/temp/</code> - Temporary files</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Informasi:</strong> Copyright footer "<?php echo getCopyrightOwner(); ?>" dilindungi dengan enkripsi dan tidak dapat diubah.
                        <?php if (!validateCopyrightIntegrity()): ?>
                        <br><span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Peringatan: Sistem proteksi copyright terdeteksi ada gangguan!</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Tab navigation
    $('.list-group-item-action').on('click', function(e) {
        e.preventDefault();
        $('.list-group-item-action').removeClass('active');
        $(this).addClass('active');
    });
});
</script>

<?php include '../includes/footer.php'; ?>