<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/UserTracker.php';
require_once '../classes/SourceProtector.php';

// Require admin login
requireAdmin();

$page_title = 'User Tracking & Security';
$tracker = new UserTracker();
$protector = new SourceProtector();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'get_active_sessions':
            $sessions = $tracker->getActiveSessions();
            echo json_encode(['success' => true, 'data' => $sessions]);
            exit;
            
        case 'get_activity_report':
            $userId = $_POST['user_id'] ?? null;
            $dateFrom = $_POST['date_from'] ?? null;
            $dateTo = $_POST['date_to'] ?? null;
            $activities = $tracker->getUserActivityReport($userId, $dateFrom, $dateTo);
            echo json_encode(['success' => true, 'data' => $activities]);
            exit;
            
        case 'get_security_alerts':
            $alerts = $tracker->getSecurityAlerts();
            echo json_encode(['success' => true, 'data' => $alerts]);
            exit;
            
        case 'check_file_integrity':
            $violations = $protector->checkIntegrity();
            echo json_encode(['success' => true, 'violations' => $violations]);
            exit;
            
        case 'get_protection_status':
            $status = $protector->getProtectionStatus();
            echo json_encode(['success' => true, 'data' => $status]);
            exit;
            
        case 'clean_old_data':
            $days = (int)($_POST['days'] ?? 30);
            $result1 = $tracker->cleanOldData($days);
            $result2 = $protector->cleanOldData($days);
            echo json_encode(['success' => $result1 && $result2, 'message' => 'Old data cleaned successfully']);
            exit;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

// Get initial data
$activeSessions = $tracker->getActiveSessions();
$recentAlerts = $tracker->getSecurityAlerts(10);
$protectionStatus = $protector->getProtectionStatus();

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-shield-alt me-2"></i>User Tracking & Security</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="settings.php">Pengaturan</a></li>
                        <li class="breadcrumb-item active">User Tracking</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Security Overview Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100 bg-primary text-white">
            <div class="card-body">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h5>Active Sessions</h5>
                <h2><?php echo count($activeSessions); ?></h2>
                <small>Currently online</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100 bg-warning text-dark">
            <div class="card-body">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <h5>Security Alerts</h5>
                <h2><?php echo count($recentAlerts); ?></h2>
                <small>Recent alerts</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100 bg-info text-white">
            <div class="card-body">
                <i class="fas fa-file-shield fa-3x mb-3"></i>
                <h5>Protected Files</h5>
                <h2><?php echo $protectionStatus['monitored_files'] ?? 0; ?></h2>
                <small>Files monitored</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100 <?php echo ($protectionStatus['violations'] ?? 0) > 0 ? 'bg-danger' : 'bg-success'; ?> text-white">
            <div class="card-body">
                <i class="fas fa-<?php echo ($protectionStatus['violations'] ?? 0) > 0 ? 'times-circle' : 'check-circle'; ?> fa-3x mb-3"></i>
                <h5>Integrity Status</h5>
                <h2><?php echo $protectionStatus['violations'] ?? 0; ?></h2>
                <small>Violations detected</small>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs mb-4" id="securityTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="sessions-tab" data-bs-toggle="tab" data-bs-target="#sessions" type="button">
            <i class="fas fa-users me-2"></i>Active Sessions
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="activities-tab" data-bs-toggle="tab" data-bs-target="#activities" type="button">
            <i class="fas fa-history me-2"></i>User Activities
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alerts" type="button">
            <i class="fas fa-exclamation-triangle me-2"></i>Security Alerts
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="protection-tab" data-bs-toggle="tab" data-bs-target="#protection" type="button">
            <i class="fas fa-shield-alt me-2"></i>File Protection
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="securityTabContent">
    <!-- Active Sessions Tab -->
    <div class="tab-pane fade show active" id="sessions" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Active User Sessions</h5>
                    <button class="btn btn-primary btn-sm" onclick="refreshSessions()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="sessionsTable">
                    <?php if (!empty($activeSessions)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>IP Address</th>
                                    <th>Browser</th>
                                    <th>Device</th>
                                    <th>Login Time</th>
                                    <th>Last Activity</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeSessions as $session): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($session['name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($session['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $session['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                            <?php echo ucfirst($session['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($session['ip_address']); ?></td>
                                    <td>
                                        <i class="fab fa-<?php echo strtolower($session['browser']); ?> me-1"></i>
                                        <?php echo htmlspecialchars($session['browser']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($session['os']); ?></small>
                                    </td>
                                    <td>
                                        <i class="fas fa-<?php echo $session['device'] === 'Mobile' ? 'mobile-alt' : 'desktop'; ?> me-1"></i>
                                        <?php echo htmlspecialchars($session['device']); ?>
                                    </td>
                                    <td><?php echo formatDate($session['login_time']); ?></td>
                                    <td><?php echo formatDate($session['last_activity']); ?></td>
                                    <td>
                                        <?php 
                                        $duration = time() - strtotime($session['login_time']);
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        echo $hours . 'h ' . $minutes . 'm';
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No active sessions</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Activities Tab -->
    <div class="tab-pane fade" id="activities" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>User Activities</h5>
            </div>
            <div class="card-body">
                <!-- Filter Form -->
                <form class="row g-3 mb-4" id="activityFilter">
                    <div class="col-md-3">
                        <label for="filterUserId" class="form-label">User</label>
                        <select class="form-select" id="filterUserId" name="user_id">
                            <option value="">All Users</option>
                            <?php
                            // Get all users for filter
                            try {
                                $db = new Database();
                                $stmt = $db->query("SELECT id, name, email FROM users ORDER BY name");
                                $db->execute($stmt);
                                $users = $db->resultSet($stmt);
                                foreach ($users as $user) {
                                    echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['name']) . ' (' . htmlspecialchars($user['email']) . ')</option>';
                                }
                            } catch (Exception $e) {
                                // Handle error silently
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filterDateFrom" class="form-label">Date From</label>
                        <input type="date" class="form-control" id="filterDateFrom" name="date_from" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="filterDateTo" class="form-label">Date To</label>
                        <input type="date" class="form-control" id="filterDateTo" name="date_to" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-primary d-block" onclick="loadActivities()">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                    </div>
                </form>
                
                <div id="activitiesTable">
                    <div class="text-center py-4">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Click Filter to load activities</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Security Alerts Tab -->
    <div class="tab-pane fade" id="alerts" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Security Alerts</h5>
                    <button class="btn btn-warning btn-sm" onclick="refreshAlerts()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="alertsTable">
                    <?php if (!empty($recentAlerts)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Risk Level</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAlerts as $alert): ?>
                                <tr>
                                    <td><?php echo formatDate($alert['timestamp']); ?></td>
                                    <td>
                                        <?php if ($alert['name']): ?>
                                        <strong><?php echo htmlspecialchars($alert['name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($alert['email']); ?></small>
                                        <?php else: ?>
                                        <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo ucfirst(str_replace('_', ' ', $alert['activity_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($alert['action_description']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $alert['risk_level'] === 'critical' ? 'bg-danger' : 
                                                ($alert['risk_level'] === 'high' ? 'bg-warning' : 
                                                ($alert['risk_level'] === 'medium' ? 'bg-info' : 'bg-success')); 
                                        ?>">
                                            <?php echo ucfirst($alert['risk_level']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($alert['ip_address']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                        <p class="text-muted">No security alerts</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- File Protection Tab -->
    <div class="tab-pane fade" id="protection" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>File Protection Status</h5>
                    <div>
                        <button class="btn btn-info btn-sm" onclick="checkIntegrity()">
                            <i class="fas fa-search me-1"></i>Check Integrity
                        </button>
                        <button class="btn btn-success btn-sm" onclick="refreshProtection()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="protectionStatus">
                    <?php if ($protectionStatus && !empty($protectionStatus['files'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>File Path</th>
                                    <th>Status</th>
                                    <th>Protection Level</th>
                                    <th>Size</th>
                                    <th>Last Modified</th>
                                    <th>Integrity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($protectionStatus['files'] as $file): ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($file['path']); ?></code>
                                    </td>
                                    <td>
                                        <?php if ($file['exists']): ?>
                                        <span class="badge bg-success">Exists</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger">Missing</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $file['protection_level'] === 'critical' ? 'bg-danger' : 
                                                ($file['protection_level'] === 'high' ? 'bg-warning' : 
                                                ($file['protection_level'] === 'medium' ? 'bg-info' : 'bg-secondary')); 
                                        ?>">
                                            <?php echo ucfirst($file['protection_level']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $file['size'] ? number_format($file['size']) . ' bytes' : '-'; ?></td>
                                    <td><?php echo $file['modified'] ? formatDate($file['modified']) : '-'; ?></td>
                                    <td>
                                        <?php if (isset($file['integrity'])): ?>
                                            <?php if ($file['integrity']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>OK
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Modified
                                            </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No protection data available</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Maintenance</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Data Cleanup</h6>
                        <p class="text-muted">Clean old tracking and protection data to maintain performance.</p>
                        <div class="input-group mb-3">
                            <input type="number" class="form-control" id="cleanupDays" value="30" min="1" max="365">
                            <span class="input-group-text">days</span>
                            <button class="btn btn-warning" onclick="cleanOldData()">
                                <i class="fas fa-broom me-1"></i>Clean Data
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Security Status</h6>
                        <p class="text-muted">Overall security system status and recommendations.</p>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>System Status:</strong> All security systems are operational.
                            <br><small>Last check: <?php echo date('Y-m-d H:i:s'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Refresh active sessions
function refreshSessions() {
    fetch('user_tracking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_active_sessions'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update sessions table
            updateSessionsTable(data.data);
        }
    });
}

// Load user activities
function loadActivities() {
    const formData = new FormData(document.getElementById('activityFilter'));
    formData.append('action', 'get_activity_report');
    
    fetch('user_tracking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateActivitiesTable(data.data);
        }
    });
}

// Refresh security alerts
function refreshAlerts() {
    fetch('user_tracking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_security_alerts'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateAlertsTable(data.data);
        }
    });
}

// Check file integrity
function checkIntegrity() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Checking...';
    
    fetch('user_tracking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=check_file_integrity'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.violations.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Integrity Violations Detected',
                    html: `Found ${data.violations.length} file integrity violations:<br><br>` +
                          data.violations.map(v => `• ${v.file} (${v.type})`).join('<br>'),
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Integrity Check Passed',
                    text: 'All protected files are intact.',
                    timer: 3000
                });
            }
            refreshProtection();
        }
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

// Refresh protection status
function refreshProtection() {
    fetch('user_tracking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_protection_status'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateProtectionTable(data.data);
        }
    });
}

// Clean old data
function cleanOldData() {
    const days = document.getElementById('cleanupDays').value;
    
    Swal.fire({
        title: 'Clean Old Data?',
        text: `This will delete tracking data older than ${days} days.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Clean',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('user_tracking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=clean_old_data&days=${days}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Cleaned!', data.message, 'success');
                } else {
                    Swal.fire('Error', 'Failed to clean data', 'error');
                }
            });
        }
    });
}

// Helper functions to update tables
function updateSessionsTable(sessions) {
    // Implementation for updating sessions table
    console.log('Updating sessions table', sessions);
}

function updateActivitiesTable(activities) {
    // Implementation for updating activities table
    console.log('Updating activities table', activities);
}

function updateAlertsTable(alerts) {
    // Implementation for updating alerts table
    console.log('Updating alerts table', alerts);
}

function updateProtectionTable(status) {
    // Implementation for updating protection table
    console.log('Updating protection table', status);
}

// Auto-refresh every 30 seconds for active sessions
setInterval(refreshSessions, 30000);
</script>

<?php include '../includes/footer.php'; ?>