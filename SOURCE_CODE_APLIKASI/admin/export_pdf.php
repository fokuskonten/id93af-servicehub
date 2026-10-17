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

// For now, we'll create an HTML version that can be printed as PDF
// In production, you might want to use libraries like TCPDF or mPDF
?>
<!DOCTYPE html>
<html>
<head>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta charset="UTF-8">
    <title>Laporan Servis PDF</title>
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
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .summary table {
            width: 100%;
            margin-bottom: 0;
        }
        .summary th {
            background-color: #e9ecef;
            text-align: center;
            padding: 10px;
        }
        .summary td {
            text-align: center;
            padding: 10px;
            font-weight: bold;
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
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            🖨️ Print PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            ❌ Tutup
        </button>
    </div>

    <div class="header">
        <div class="company-name"><?php echo getCopyrightOwner(); ?></div>
        <div class="report-title">LAPORAN SERVIS</div>
        <div class="period">Periode: <?php echo formatDate($dateFrom); ?> - <?php echo formatDate($dateTo); ?></div>
    </div>
    
    <div class="summary">
        <h4>Ringkasan</h4>
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
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Ticket</th>
                <th style="width: 20%;">Customer</th>
                <th style="width: 20%;">Device</th>
                <th style="width: 15%;">Status</th>
                <th style="width: 15%;">Biaya</th>
                <th style="width: 10%;">Tanggal</th>
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
                    <td><?php echo date('d/m/Y', strtotime($service['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Tidak ada data untuk periode yang dipilih</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></p>
        <p><?php echo getCopyrightOwner(); ?> (<?php echo CopyrightProtection::getCopyrightUrl(); ?>) - <?php echo getSiteDescription(); ?></p>
    </div>
</body>
</html>