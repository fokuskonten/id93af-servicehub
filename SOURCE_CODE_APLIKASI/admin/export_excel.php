<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/Service.php';

// Require admin login
requireAdmin();

$serviceObj = new Service();

// Get date range from POST
$dateFrom = $_POST['date_from'] ?? date('Y-m-01');
$dateTo = $_POST['date_to'] ?? date('Y-m-d');

// Get services for the date range
$services = $serviceObj->searchServices('', null, $dateFrom, $dateTo);

// Calculate totals
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

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Laporan_Servis_' . date('Y-m-d', strtotime($dateFrom)) . '_to_' . date('Y-m-d', strtotime($dateTo)) . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

?>
<!DOCTYPE html>
<html>
<head>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta charset="UTF-8">
    <title>Laporan Servis</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .summary {
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2><?php echo getCopyrightOwner(); ?></h2>
        <h3>LAPORAN SERVIS</h3>
        <p>Periode: <?php echo formatDate($dateFrom); ?> - <?php echo formatDate($dateTo); ?></p>
    </div>
    
    <div class="summary">
        <table>
            <tr>
                <th>Total Servis</th>
                <th>Servis Selesai</th>
                <th>Total Pendapatan</th>
            </tr>
            <tr>
                <td><?php echo count($services); ?></td>
                <td><?php echo $completedServices; ?></td>
                <td><?php echo formatRupiah($totalRevenue); ?></td>
            </tr>
        </table>
    </div>
    
    <h4>Detail Servis</h4>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Ticket</th>
                <th>Customer</th>
                <th>Device</th>
                <th>Status</th>
                <th>Biaya</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($services)): ?>
                <?php $no = 1; ?>
                <?php foreach ($services as $service): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo $service['ticket_number']; ?></td>
                    <td><?php echo htmlspecialchars($service['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($service['device_brand'] . ' ' . $service['device_model']); ?></td>
                    <td><?php echo getStatusText($service['status']); ?></td>
                    <td><?php echo $service['final_cost'] ? formatRupiah($service['final_cost']) : 
                                  ($service['estimated_cost'] ? formatRupiah($service['estimated_cost']) . ' (est)' : '-'); ?></td>
                    <td><?php echo formatDate($service['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Tidak ada data untuk periode yang dipilih</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 30px; text-align: center; font-size: 12px;">
        <p>Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></p>
        <p><?php echo getCopyrightOwner(); ?> (<?php echo CopyrightProtection::getCopyrightUrl(); ?>) - <?php echo getSiteDescription(); ?></p>
    </div>
</body>
</html>