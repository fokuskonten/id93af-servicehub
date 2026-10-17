<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'classes/User.php';

$page_title = 'Login';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('customer/dashboard.php');
    }
}

$userObj = new User();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } else {
        $user = $userObj->login($email, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            setFlashMessage('success', 'Login berhasil! Selamat datang ' . $user['name']);
            
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
                exit();
            } else {
                header('Location: customer/dashboard.php');
                exit();
            }
        } else {
            $error = 'Email atau password salah';
        }
    }
}

include 'includes/header.php';
?>

<style>
/* Ensure full width for login page */
.main-wrapper {
    margin-left: 0 !important;
    width: 100% !important;
}
</style>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-header text-center">
                    <h3 class="mb-0">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </h3>
                    <p class="text-muted mb-0">Masuk ke akun Anda</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       required>
                                <div class="invalid-feedback">
                                    Masukkan email yang valid
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="invalid-feedback">
                                    Password harus diisi
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">
                                Ingat saya
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                    </form>
<div class="text-center" style="margin-top: 15px; font-size: 12px; color: #64748b; font-family: sans-serif;">Powered by <a href="https://fokuskonten.my.id" target="_blank" style="color: #2563eb; font-weight: 600; text-decoration: none;">FokusKonten</a></div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">Belum punya akun?</p>
                        <a href="register.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                        </a>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="public/track.php" class="text-decoration-none">
                            <i class="fas fa-search me-1"></i>Lacak Servis Tanpa Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
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
</script>

<?php include 'includes/footer.php'; ?>