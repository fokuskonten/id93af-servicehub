<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/Service.php';
require_once '../classes/User.php';

// Require admin login
requireAdmin();

$page_title = 'Laporan';
$serviceObj = new Service();
$userObj = new User();

// Get date range from request
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Get statistics
$serviceStats = $serviceObj->getServiceStats();
$userStats = $userObj->getUserStats();

// Get services for the date range
$services = $serviceObj->searchServices('', null, $dateFrom, $dateTo);

// Calculate revenue
$totalRevenue = 0;
$completedServices = 0;
foreach ($services as $service) {
    if ($service['final_cost']) {
        $totalRevenue += $service['final_cost'];
    }
    if ($service['status'] === 'delivered') {
        $completedServices++;
    }
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-chart-line me-2"></i>Laporan</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Laporan</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Filter Date Range -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-4 mb-3">
                        <label for="date_from" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo $dateFrom; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="date_to" class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo $dateTo; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Filter
                        </button>
                        <button type="button" onclick="exportReport()" class="btn btn-success ms-2">
                            <i class="fas fa-download me-2"></i>Export Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body text-center">
                <i class="fas fa-tools fa-3x mb-3"></i>
                <div class="h2"><?php echo count($services); ?></div>
                <div>Total Servis</div>
                <small>Periode: <?php echo date('d/m/Y', strtotime($dateFrom)); ?> - <?php echo date('d/m/Y', strtotime($dateTo)); ?></small>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <div class="h2"><?php echo $completedServices; ?></div>
                <div>Servis Selesai</div>
                <small><?php echo count($services) > 0 ? round(($completedServices / count($services)) * 100, 1) : 0; ?>% dari total</small>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body text-center">
                <i class="fas fa-money-bill-wave fa-3x mb-3"></i>
                <div class="h2"><?php echo formatRupiah($totalRevenue); ?></div>
                <div>Total Pendapatan</div>
                <small>Periode ini</small>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card text-white bg-warning">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x mb-3"></i>
                <div class="h2"><?php echo $userStats['customers']; ?></div>
                <div>Total Pelanggan</div>
                <small><?php echo $userStats['new_this_month']; ?> baru bulan ini</small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Status Servis</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Jenis Perangkat</h5>
            </div>
            <div class="card-body">
                <canvas id="deviceChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Services Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Detail Servis Periode</h5>
                    <div class="btn-group">
                        <button onclick="printReport()" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                        <button onclick="exportExcel()" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-file-excel me-1"></i>Excel
                        </button>
                        <button onclick="exportPDF()" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($services)): ?>
                <div class="table-responsive" id="reportTable">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Customer</th>
                                <th>Device</th>
                                <th>Status</th>
                                <th>Biaya</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                            <tr>
                                <td><strong><?php echo $service['ticket_number']; ?></strong></td>
                                <td><?php echo htmlspecialchars($service['customer_name']); ?></td>
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
                                    <?php echo $service['final_cost'] ? formatRupiah($service['final_cost']) : 
                                              ($service['estimated_cost'] ? formatRupiah($service['estimated_cost']) . ' (est)' : '-'); ?>
                                </td>
                                <td><?php echo formatDate($service['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Tidak ada data untuk periode yang dipilih</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
    
    // Status Chart
    const statusData = <?php 
        $statusCounts = [];
        foreach ($services as $service) {
            $status = $service['status'];
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }
        echo json_encode($statusCounts);
    ?>;
    
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData).map(status => {
                const statusTexts = {
                    'pending': 'Menunggu',
                    'in_progress': 'Dikerjakan',
                    'waiting_parts': 'Tunggu Part',
                    'testing': 'Testing',
                    'completed': 'Selesai',
                    'delivered': 'Diambil',
                    'cancelled': 'Dibatalkan'
                };
                return statusTexts[status] || status;
            }),
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: [
                    '#ffc107', '#17a2b8', '#6c757d', '#007bff', 
                    '#28a745', '#343a40', '#dc3545'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Device Chart
    const deviceData = <?php 
        $deviceCounts = [];
        foreach ($services as $service) {
            $device = $service['device_type'];
            $deviceCounts[$device] = ($deviceCounts[$device] ?? 0) + 1;
        }
        echo json_encode($deviceCounts);
    ?>;
    
    const deviceCtx = document.getElementById('deviceChart').getContext('2d');
    new Chart(deviceCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(deviceData).map(device => ucfirst(device)),
            datasets: [{
                label: 'Jumlah Servis',
                data: Object.values(deviceData),
                backgroundColor: ['#007bff', '#28a745', '#ffc107']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function exportReport() {
    // Simple export functionality
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    
    Swal.fire({
        title: 'Export Laporan',
        text: `Export laporan periode ${dateFrom} sampai ${dateTo}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Export',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Here you would typically send to a PHP script that generates Excel
            Swal.fire('Info', 'Fitur export akan segera tersedia', 'info');
        }
    });
}

// Print report function
function printReport() {
    const printWindow = window.open('', '_blank');
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    
    // Get table data
    const table = document.querySelector('#reportTable table');
    const tableHTML = table ? table.outerHTML : '<p>Tidak ada data untuk dicetak</p>';
    
    // Calculate totals
    const totalServices = <?php echo count($services); ?>;
    const completedServices = <?php echo $completedServices; ?>;
    const totalRevenue = <?php echo $totalRevenue; ?>;
    
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
            <title>Laporan Servis - ${dateFrom} s/d ${dateTo}</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    font-size: 12px; 
                    margin: 20px;
                    color: #333;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    border-bottom: 2px solid #333;
                    padding-bottom: 15px;
                }
                .company-name { 
                    font-size: 18px; 
                    font-weight: bold; 
                    margin-bottom: 5px;
                }
                .report-title { 
                    font-size: 16px; 
                    margin-bottom: 10px;
                }
                .period { 
                    font-size: 14px; 
                    color: #666;
                }
                .summary {
                    display: flex;
                    justify-content: space-around;
                    margin: 20px 0;
                    padding: 15px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                .summary-item {
                    text-align: center;
                }
                .summary-number {
                    font-size: 24px;
                    font-weight: bold;
                    color: #0066cc;
                }
                .summary-label {
                    font-size: 12px;
                    color: #666;
                    margin-top: 5px;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 20px;
                }
                th, td { 
                    border: 1px solid #ddd; 
                    padding: 8px; 
                    text-align: left;
                }
                th { 
                    background-color: #f2f2f2; 
                    font-weight: bold;
                }
                .status-badge {
                    padding: 2px 6px;
                    border-radius: 3px;
                    font-size: 10px;
                    font-weight: bold;
                }
                .bg-warning { background-color: #ffc107; color: #000; }
                .bg-info { background-color: #17a2b8; color: #fff; }
                .bg-secondary { background-color: #6c757d; color: #fff; }
                .bg-primary { background-color: #007bff; color: #fff; }
                .bg-success { background-color: #28a745; color: #fff; }
                .bg-dark { background-color: #343a40; color: #fff; }
                .bg-danger { background-color: #dc3545; color: #fff; }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #ddd;
                    padding-top: 15px;
                }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company-name"><?php echo getSiteName(); ?></div>
                <div class="report-title">LAPORAN SERVIS</div>
                <div class="period">Periode: ${formatDate(dateFrom)} - ${formatDate(dateTo)}</div>
            </div>
            
            <div class="summary">
                <div class="summary-item">
                    <div class="summary-number">${totalServices}</div>
                    <div class="summary-label">Total Servis</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number">${completedServices}</div>
                    <div class="summary-label">Selesai</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number">${formatRupiah(totalRevenue)}</div>
                    <div class="summary-label">Total Pendapatan</div>
                </div>
            </div>
            
            ${tableHTML}
            
            <div class="footer">
                <p>Dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
                <p><?php echo getSiteName(); ?> - <?php echo getSiteDescription(); ?></p>
            </div>
        </body>
        </html>
    `;
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}

// Export to Excel function
function exportExcel() {
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    
    Swal.fire({
        title: 'Export Excel',
        text: `Export laporan ke Excel untuk periode ${formatDate(dateFrom)} - ${formatDate(dateTo)}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Export',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create form and submit to export script
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_excel.php';
            form.target = '_blank';
            
            const dateFromInput = document.createElement('input');
            dateFromInput.type = 'hidden';
            dateFromInput.name = 'date_from';
            dateFromInput.value = dateFrom;
            
            const dateToInput = document.createElement('input');
            dateToInput.type = 'hidden';
            dateToInput.name = 'date_to';
            dateToInput.value = dateTo;
            
            form.appendChild(dateFromInput);
            form.appendChild(dateToInput);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    });
}

// Export to PDF function
function exportPDF() {
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    
    Swal.fire({
        title: 'Export PDF',
        text: `Export laporan ke PDF untuk periode ${formatDate(dateFrom)} - ${formatDate(dateTo)}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Export',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create form and submit to export script
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_pdf.php';
            form.target = '_blank';
            
            const dateFromInput = document.createElement('input');
            dateFromInput.type = 'hidden';
            dateFromInput.name = 'date_from';
            dateFromInput.value = dateFrom;
            
            const dateToInput = document.createElement('input');
            dateToInput.type = 'hidden';
            dateToInput.name = 'date_to';
            dateToInput.value = dateTo;
            
            form.appendChild(dateFromInput);
            form.appendChild(dateToInput);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    });
}

// Helper functions for formatting
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID');
}

function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}
</script>

<?php include '../includes/footer.php'; ?>