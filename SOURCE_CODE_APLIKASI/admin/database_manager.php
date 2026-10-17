<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/DatabaseManager.php';

// Require admin login
requireAdmin();

$page_title = 'Database Manager';
$dbManager = new DatabaseManager();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'create_backup':
            $description = sanitize($_POST['description'] ?? '');
            $result = $dbManager->createBackup($description);
            echo json_encode($result);
            exit;
            
        case 'restore_backup':
            $backupId = (int)($_POST['backup_id'] ?? 0);
            $result = $dbManager->restoreBackup($backupId);
            echo json_encode($result);
            exit;
            
        case 'run_migrations':
            $result = $dbManager->runMigrations();
            echo json_encode($result);
            exit;
            
        case 'clean_backups':
            $result = $dbManager->cleanOldBackups();
            echo json_encode($result);
            exit;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

// Get data for display
$backups = $dbManager->getBackups();
$migrationStatus = $dbManager->getMigrationStatus();

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-database me-2"></i>Database Manager</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="settings.php">Pengaturan</a></li>
                        <li class="breadcrumb-item active">Database Manager</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Action Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-download fa-3x text-primary mb-3"></i>
                <h5>Create Backup</h5>
                <p class="text-muted">Buat backup database</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#backupModal">
                    <i class="fas fa-download me-2"></i>Backup Now
                </button>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-sync-alt fa-3x text-success mb-3"></i>
                <h5>Run Migrations</h5>
                <p class="text-muted">Jalankan migration database</p>
                <button class="btn btn-success" onclick="runMigrations()">
                    <i class="fas fa-sync-alt me-2"></i>Migrate
                </button>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-broom fa-3x text-warning mb-3"></i>
                <h5>Clean Backups</h5>
                <p class="text-muted">Hapus backup lama</p>
                <button class="btn btn-warning" onclick="cleanBackups()">
                    <i class="fas fa-broom me-2"></i>Clean
                </button>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
                <h5>Status</h5>
                <p class="text-muted">Lihat status database</p>
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#statusModal">
                    <i class="fas fa-info-circle me-2"></i>View Status
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Backups List -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-archive me-2"></i>Database Backups</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($backups)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Description</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-file-archive me-2"></i>
                                    <?php echo htmlspecialchars($backup['filename']); ?>
                                    <?php if (!$backup['file_exists']): ?>
                                    <span class="badge bg-danger ms-2">File Missing</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $backup['size_formatted']; ?></td>
                                <td><?php echo htmlspecialchars($backup['description'] ?: '-'); ?></td>
                                <td><?php echo formatDate($backup['created_at']); ?></td>
                                <td>
                                    <?php if ($backup['file_exists']): ?>
                                    <button class="btn btn-sm btn-success" 
                                            onclick="restoreBackup(<?php echo $backup['id']; ?>)"
                                            title="Restore">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                    <a href="../backups/<?php echo $backup['filename']; ?>" 
                                       class="btn btn-sm btn-primary" 
                                       download title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-archive fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Belum ada backup database</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Migration Status -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-code-branch me-2"></i>Migration Status</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($migrationStatus)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($migrationStatus as $migration): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted"><?php echo htmlspecialchars($migration['filename']); ?></small>
                        </div>
                        <span class="badge <?php echo $migration['executed'] ? 'bg-success' : 'bg-warning'; ?>">
                            <?php echo $migration['executed'] ? 'Executed' : 'Pending'; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-code-branch fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Tidak ada migration file</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Backup Modal -->
<div class="modal fade" id="backupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-download me-2"></i>Create Database Backup
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="backupForm">
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Describe this backup..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Info:</strong> Backup akan disimpan di folder <code>/backups/</code> 
                        dan hanya 10 backup terbaru yang akan disimpan.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createBackup()">
                    <i class="fas fa-download me-2"></i>Create Backup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Database Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Database Info</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Database:</td>
                                <td><code><?php echo DB_NAME; ?></code></td>
                            </tr>
                            <tr>
                                <td>Host:</td>
                                <td><code><?php echo DB_HOST; ?></code></td>
                            </tr>
                            <tr>
                                <td>Backups:</td>
                                <td><?php echo count($backups); ?> files</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Folder Structure</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-folder me-2"></i><code>/backups/</code> - Database backups</li>
                            <li><i class="fas fa-folder me-2"></i><code>/migrations/</code> - Migration files</li>
                            <li><i class="fas fa-folder me-2"></i><code>/logs/</code> - Database logs</li>
                            <li><i class="fas fa-folder me-2"></i><code>/temp/</code> - Temporary files</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function createBackup() {
    const description = document.getElementById('description').value;
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
    
    fetch('database_manager.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=create_backup&description=${encodeURIComponent(description)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Backup Created!',
                text: data.message + ' (' + data.size + ')',
                timer: 3000
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Backup Failed',
                text: data.message
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Network error occurred'
        });
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        $('#backupModal').modal('hide');
    });
}

function restoreBackup(backupId) {
    Swal.fire({
        title: 'Restore Database?',
        text: 'This will replace current database with backup data. This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Restore!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('database_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=restore_backup&backup_id=${backupId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Restored!',
                        text: data.message,
                        timer: 3000
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Restore Failed',
                        text: data.message
                    });
                }
            });
        }
    });
}

function runMigrations() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Running...';
    
    fetch('database_manager.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=run_migrations'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Migrations Complete!',
                text: data.message,
                timer: 3000
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Migration Failed',
                text: data.message
            });
        }
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function cleanBackups() {
    Swal.fire({
        title: 'Clean Old Backups?',
        text: 'This will delete old backup files (keeping last 10)',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Clean!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('database_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clean_backups'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cleaned!',
                        text: `${data.deleted} old backups deleted`,
                        timer: 3000
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Clean Failed',
                        text: data.message
                    });
                }
            });
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>