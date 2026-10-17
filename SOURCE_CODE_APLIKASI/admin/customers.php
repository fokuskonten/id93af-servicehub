<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/User.php';

// Require admin login
requireAdmin();

$page_title = 'Kelola Pelanggan';
$userObj = new User();

// Handle actions
$action = $_GET['action'] ?? 'list';
$customerId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $data = [
            'name' => sanitize($_POST['name']),
            'email' => sanitize($_POST['email']),
            'phone' => sanitize($_POST['phone']),
            'address' => sanitize($_POST['address']),
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'role' => 'customer'
        ];
        
        // Check if email exists
        if ($userObj->emailExists($data['email'])) {
            setFlashMessage('error', 'Email sudah terdaftar');
        } else {
            if ($userObj->createUser($data)) {
                setFlashMessage('success', 'Pelanggan berhasil ditambahkan');
                redirect('customers.php');
            } else {
                setFlashMessage('error', 'Gagal menambahkan pelanggan');
            }
        }
    } elseif ($action === 'edit' && $customerId) {
        $data = [
            'name' => sanitize($_POST['name']),
            'email' => sanitize($_POST['email']),
            'phone' => sanitize($_POST['phone']),
            'address' => sanitize($_POST['address'])
        ];
        
        // Update password if provided
        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        // Check if email exists for other users
        $existingUser = $userObj->getUserById($customerId);
        if ($data['email'] !== $existingUser['email'] && $userObj->emailExists($data['email'])) {
            setFlashMessage('error', 'Email sudah digunakan oleh pengguna lain');
        } else {
            if ($userObj->updateUser($customerId, $data)) {
                setFlashMessage('success', 'Data pelanggan berhasil diperbarui');
                redirect('customers.php');
            } else {
                setFlashMessage('error', 'Gagal memperbarui data pelanggan');
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && $customerId) {
    if ($userObj->deleteUser($customerId)) {
        setFlashMessage('success', 'Pelanggan berhasil dihapus');
    } else {
        setFlashMessage('error', 'Gagal menghapus pelanggan. Mungkin masih ada servis yang terkait.');
    }
    redirect('customers.php');
}

// Handle toggle status
if ($action === 'toggle_status' && $customerId) {
    if ($userObj->toggleUserStatus($customerId)) {
        setFlashMessage('success', 'Status pelanggan berhasil diubah');
    } else {
        setFlashMessage('error', 'Gagal mengubah status pelanggan');
    }
    redirect('customers.php');
}

// Get data based on action
if ($action === 'list') {
    $customers = $userObj->getAllCustomers();
} elseif ($action === 'edit' && $customerId) {
    $customer = $userObj->getUserById($customerId);
    if (!$customer || $customer['role'] !== 'customer') {
        setFlashMessage('error', 'Pelanggan tidak ditemukan');
        redirect('customers.php');
    }
}

include '../includes/header.php';
?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-users me-2"></i>Kelola Pelanggan</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Pelanggan</li>
                        </ol>
                    </nav>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="customers.php?action=create" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i>Tambah Pelanggan
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($action === 'list'): ?>
    <!-- Customers List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Pelanggan</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($customers)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Telepon</th>
                                    <th>Alamat</th>
                                    <th>Status</th>
                                    <th>Terdaftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?: '-'); ?></td>
                                    <td>
                                        <span class="text-truncate" style="max-width: 150px; display: inline-block;" 
                                              title="<?php echo htmlspecialchars($customer['address'] ?: '-'); ?>">
                                            <?php echo htmlspecialchars($customer['address'] ?: '-'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $customer['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $customer['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($customer['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="customers.php?action=edit&id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="customers.php?action=toggle_status&id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-sm btn-outline-<?php echo $customer['is_active'] ? 'warning' : 'success'; ?>" 
                                               title="<?php echo $customer['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                                <i class="fas fa-<?php echo $customer['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            </a>
                                            <a href="customers.php?action=delete&id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger btn-delete" 
                                               title="Hapus"
                                               data-title="Hapus Pelanggan"
                                               data-text="Apakah Anda yakin ingin menghapus pelanggan ini? Data servis yang terkait juga akan terhapus.">
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
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada pelanggan</p>
                        <a href="customers.php?action=create" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Tambah Pelanggan Pertama
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($action === 'create' || $action === 'edit'): ?>
    <!-- Create/Edit Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-<?php echo $action === 'create' ? 'user-plus' : 'user-edit'; ?> me-2"></i>
                        <?php echo $action === 'create' ? 'Tambah' : 'Edit'; ?> Pelanggan
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nama Lengkap *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo isset($customer) ? htmlspecialchars($customer['name']) : ''; ?>" required>
                                <div class="invalid-feedback">Nama lengkap harus diisi</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($customer) ? htmlspecialchars($customer['email']) : ''; ?>" required>
                                <div class="invalid-feedback">Email harus diisi dengan format yang benar</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Nomor Telepon</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo isset($customer) ? htmlspecialchars($customer['phone']) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">
                                    Password <?php echo $action === 'edit' ? '(kosongkan jika tidak diubah)' : '*'; ?>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       <?php echo $action === 'create' ? 'required' : ''; ?> minlength="6">
                                <div class="invalid-feedback">Password minimal 6 karakter</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Alamat</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($customer) ? htmlspecialchars($customer['address']) : ''; ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="customers.php" class="btn btn-secondary">
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
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Customer</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">Panduan mengelola customer:</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Email harus unik</li>
                        <li><i class="fas fa-check text-success me-2"></i>Password minimal 6 karakter</li>
                        <li><i class="fas fa-check text-success me-2"></i>Nomor telepon untuk komunikasi</li>
                        <li><i class="fas fa-check text-success me-2"></i>Status aktif/nonaktif untuk kontrol akses</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
$(document).ready(function() {
    // Initialize DataTable
    $('.data-table').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        },
        order: [[5, 'desc']] // Sort by registration date
    });
});
</script>

<?php include '../includes/footer.php'; ?>