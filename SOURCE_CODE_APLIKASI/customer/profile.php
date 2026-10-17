<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/User.php';

// Require customer login
requireCustomer();

$page_title = 'Profil Saya';
$userObj = new User();

// Get current user data
$userId = getCurrentUserId();
$user = $userObj->getUserById($userId);

if (!$user) {
    setFlashMessage('error', 'Data pengguna tidak ditemukan');
    header('Location: dashboard.php');
    exit();
}

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload_avatar') {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $result = $userObj->uploadAvatar($userId, $_FILES['avatar']);
            if ($result['success']) {
                setFlashMessage('success', $result['message']);
            } else {
                setFlashMessage('error', $result['message']);
            }
        } else {
            setFlashMessage('error', 'Pilih file avatar terlebih dahulu');
        }
        header('Location: profile.php');
        exit();
        
    } elseif ($action === 'delete_avatar') {
        if ($userObj->deleteAvatar($userId)) {
            setFlashMessage('success', 'Avatar berhasil dihapus');
        } else {
            setFlashMessage('error', 'Gagal menghapus avatar');
        }
        header('Location: profile.php');
        exit();
        
    } elseif ($action === 'update_profile') {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        
        // Validation
        if (empty($name)) {
            $errors[] = 'Nama harus diisi';
        }
        
        if (empty($email)) {
            $errors[] = 'Email harus diisi';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid';
        } elseif ($email !== $user['email'] && $userObj->emailExists($email)) {
            $errors[] = 'Email sudah digunakan oleh pengguna lain';
        }
        
        if (empty($errors)) {
            $data = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address
            ];
            
            if ($userObj->updateUser($userId, $data)) {
                // Update session data
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                
                setFlashMessage('success', 'Profil berhasil diperbarui');
                header('Location: profile.php');
                exit();
            } else {
                $errors[] = 'Gagal memperbarui profil';
            }
        }
        
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validation
        if (empty($currentPassword)) {
            $errors[] = 'Password saat ini harus diisi';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errors[] = 'Password saat ini salah';
        }
        
        if (empty($newPassword)) {
            $errors[] = 'Password baru harus diisi';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'Password baru minimal 6 karakter';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Konfirmasi password tidak cocok';
        }
        
        if (empty($errors)) {
            $data = [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT)
            ];
            
            if ($userObj->updateUser($userId, $data)) {
                setFlashMessage('success', 'Password berhasil diubah');
                header('Location: profile.php');
                exit();
            } else {
                $errors[] = 'Gagal mengubah password';
            }
        }
    }
    
    // Refresh user data if there were errors
    if (!empty($errors)) {
        $user = $userObj->getUserById($userId);
    }
}

// Get customer statistics
require_once '../classes/Service.php';
$serviceObj = new Service();
$totalTickets = $serviceObj->getCustomerTicketCount($userId);
$pendingTickets = $serviceObj->getCustomerTicketCount($userId, 'pending');
$completedTickets = $serviceObj->getCustomerTicketCount($userId, 'completed');

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-user-circle me-2"></i>Profil Saya</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Profil</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Profile Info -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="position-relative d-inline-block mb-3">
                    <?php 
                    try {
                        $avatarUrl = $userObj->getAvatarUrl($userId);
                        if ($avatarUrl !== 'assets/img/default-avatar.png' && file_exists($avatarUrl)): 
                    ?>
                        <img src="../<?php echo $avatarUrl; ?>" alt="Avatar" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <?php else: ?>
                        <div class="avatar-circle mx-auto" style="width: 120px; height: 120px; font-size: 3rem;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php 
                        endif;
                    } catch (Exception $e) {
                    ?>
                        <div class="avatar-circle mx-auto" style="width: 120px; height: 120px; font-size: 3rem;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php } ?>
                    
                    <!-- Avatar Upload Button -->
                    <button class="btn btn-success btn-sm position-absolute bottom-0 end-0 rounded-circle" 
                            style="width: 35px; height: 35px; padding: 0;" 
                            data-bs-toggle="modal" 
                            data-bs-target="#avatarModal">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                
                <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge bg-success fs-6">Customer</span>
                
                <hr class="my-3">
                
                <div class="row text-center">
                    <div class="col-6">
                        <h6 class="text-muted mb-1">Bergabung</h6>
                        <small><?php echo date('M Y', strtotime($user['created_at'])); ?></small>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted mb-1">Status</h6>
                        <small class="text-success">
                            <i class="fas fa-circle me-1"></i>Aktif
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ticket Stats -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistik Tiket</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Total Tiket</span>
                    <strong class="text-primary"><?php echo $totalTickets; ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Menunggu</span>
                    <strong class="text-warning"><?php echo $pendingTickets; ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Selesai</span>
                    <strong class="text-success"><?php echo $completedTickets; ?></strong>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Profile Forms -->
    <div class="col-lg-8">
        <!-- Update Profile -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Profil</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="profile.php" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nama Lengkap *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            <div class="invalid-feedback">Nama lengkap harus diisi</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <div class="invalid-feedback">Email harus diisi dengan format yang benar</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Nomor Telepon</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>" 
                                   placeholder="+6281234567890">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Tipe Akun</label>
                            <input type="text" class="form-control" value="Customer" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Alamat</label>
                        <textarea class="form-control" id="address" name="address" rows="3" 
                                  placeholder="Alamat lengkap"><?php echo htmlspecialchars($user['address'] ?: ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Ubah Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="profile.php" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Saat Ini *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="invalid-feedback">Password saat ini harus diisi</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new_password" class="form-label">Password Baru *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="invalid-feedback">Password baru minimal 6 karakter</div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="invalid-feedback">Konfirmasi password harus sama</div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key me-2"></i>Ubah Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Avatar Upload Modal -->
<div class="modal fade" id="avatarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-camera me-2"></i>Ubah Avatar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Current Avatar Preview -->
                <div class="text-center mb-4">
                    <h6>Avatar Saat Ini:</h6>
                    <?php if ($avatarUrl !== 'assets/img/default-avatar.png' && file_exists($avatarUrl)): ?>
                        <img src="../<?php echo $avatarUrl; ?>" alt="Current Avatar" class="rounded-circle mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                        <br>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_avatar">
                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus avatar?')">
                                <i class="fas fa-trash me-1"></i>Hapus Avatar
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="avatar-circle mx-auto mb-2" style="width: 80px; height: 80px; font-size: 2rem;">
                            <i class="fas fa-user"></i>
                        </div>
                        <br>
                        <small class="text-muted">Belum ada avatar</small>
                    <?php endif; ?>
                </div>
                
                <!-- Upload Form -->
                <form method="POST" enctype="multipart/form-data" id="avatarForm">
                    <input type="hidden" name="action" value="upload_avatar">
                    
                    <div class="mb-3">
                        <label for="avatar" class="form-label">Pilih Avatar Baru:</label>
                        <input type="file" class="form-control" id="avatar" name="avatar" 
                               accept="image/jpeg,image/jpg,image/png,image/gif" required>
                        <small class="form-text text-muted">
                            Format: JPG, PNG, GIF. Maksimal 2MB.
                        </small>
                    </div>
                    
                    <!-- Preview -->
                    <div id="imagePreview" class="text-center mb-3" style="display: none;">
                        <h6>Preview:</h6>
                        <img id="previewImg" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="avatarForm" class="btn btn-success">
                    <i class="fas fa-upload me-2"></i>Upload Avatar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}
</style>

<script>
// Toggle password visibility
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Password tidak cocok');
    } else {
        this.setCustomValidity('');
    }
});

// Avatar preview
document.getElementById('avatar').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('imagePreview').style.display = 'none';
    }
});
</script>

<?php include '../includes/footer.php'; ?>