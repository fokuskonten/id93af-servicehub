<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'classes/User.php';

$page_title = 'Daftar';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('customer/dashboard.php');
    }
}

$userObj = new User();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Nama harus diisi';
    }
    
    if (empty($email)) {
        $errors[] = 'Email harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    } elseif ($userObj->emailExists($email)) {
        $errors[] = 'Email sudah terdaftar';
    }
    
    if (empty($phone)) {
        $errors[] = 'Nomor telepon harus diisi';
    }
    
    if (empty($password)) {
        $errors[] = 'Password harus diisi';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Konfirmasi password tidak cocok';
    }
    
    if (empty($errors)) {
        $userData = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'customer'
        ];
        
        $userId = $userObj->createUser($userData);
        if ($userId) {
            setFlashMessage('success', 'Pendaftaran berhasil! Silakan login dengan akun Anda');
            redirect('login.php');
        } else {
            $errors[] = 'Terjadi kesalahan saat mendaftar';
        }
    }
}

include 'includes/header.php';
?>

<style>
/* Ensure full width for register page */
.main-wrapper {
    margin-left: 0 !important;
    width: 100% !important;
}
</style>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header text-center">
                    <h3 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Daftar Akun
                    </h3>
                    <p class="text-muted mb-0">Buat akun baru untuk menggunakan layanan kami</p>
                </div>
                <div class="card-body p-4">
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
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nama Lengkap *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        Nama lengkap harus diisi
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        Email harus diisi dengan format yang benar
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Nomor Telepon *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                       placeholder="Contoh: +6281234567890" required>
                                <div class="invalid-feedback">
                                    Nomor telepon harus diisi
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Alamat</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <textarea class="form-control" id="address" name="address" rows="2" 
                                          placeholder="Alamat lengkap (opsional)"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="6" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        Password minimal 6 karakter
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           minlength="6" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        Konfirmasi password harus sama dengan password
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                Saya setuju dengan <a href="#" class="text-decoration-none">Syarat dan Ketentuan</a> 
                                serta <a href="#" class="text-decoration-none">Kebijakan Privasi</a>
                            </label>
                            <div class="invalid-feedback">
                                Anda harus menyetujui syarat dan ketentuan
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">Sudah punya akun?</p>
                        <a href="login.php" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePasswordVisibility(inputId, buttonId) {
    document.getElementById(buttonId).addEventListener('click', function() {
        const password = document.getElementById(inputId);
        const icon = this.querySelector('i');
        
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
}

togglePasswordVisibility('password', 'togglePassword');
togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Password tidak cocok');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include 'includes/footer.php'; ?>