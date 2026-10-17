<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/Service.php';

// Require admin login
requireAdmin();

$serviceObj = new Service();

// Get service ID from URL
$serviceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$serviceId) {
    die('ID servis tidak valid');
}

// Get service details
$service = $serviceObj->getServiceById($serviceId);

if (!$service) {
    die('Servis tidak ditemukan');
}

// Get service timeline
$timeline = $serviceObj->getServiceTimeline($serviceId);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Servis - <?php echo $service['ticket_number']; ?></title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .document-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .ticket-number {
            font-size: 14px;
            font-weight: bold;
            color: #0066cc;
        }
        
        .section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            background-color: #f0f0f0;
            padding: 8px;
            border-left: 4px solid #0066cc;
            margin-bottom: 10px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .info-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        
        .info-table td:first-child {
            font-weight: bold;
            width: 150px;
            background-color: #f9f9f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }
        
        .status-pending { background-color: #ffc107; color: #000; }
        .status-in-progress { background-color: #17a2b8; }
        .status-waiting-parts { background-color: #6c757d; }
        .status-testing { background-color: #007bff; }
        .status-completed { background-color: #28a745; }
        .status-delivered { background-color: #343a40; }
        .status-cancelled { background-color: #dc3545; }
        
        .timeline {
            margin-left: 0;
        }
        
        .timeline-item {
            margin-bottom: 15px;
            padding-left: 20px;
            position: relative;
        }
        
        .timeline-item::before {
            content: "•";
            position: absolute;
            left: 0;
            top: 0;
            font-weight: bold;
            color: #0066cc;
        }
        
        .timeline-date {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
        }
        
        .problem-description {
            background-color: #f8f9fa;
            padding: 10px;
            border-left: 4px solid #0066cc;
            margin: 10px 0;
        }
        
        .notes {
            background-color: #fff3cd;
            padding: 10px;
            border-left: 4px solid #ffc107;
            margin: 10px 0;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .no-print {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 5px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                padding: 0;
                font-size: 11px;
            }
            
            .header {
                padding-bottom: 10px;
                margin-bottom: 15px;
            }
            
            .section {
                margin-bottom: 15px;
                page-break-inside: avoid;
            }
            
            .timeline-item {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="no-print">
        <button onclick="window.print()" class="btn">
            🖨️ Cetak
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            ❌ Tutup
        </button>
    </div>
    
    <!-- Header -->
    <div class="header">
        <div class="company-name"><?php echo getSiteName(); ?></div>
        <div class="document-title">DETAIL SERVIS</div>
        <div class="ticket-number"><?php echo $service['ticket_number']; ?></div>
    </div>
    
    <!-- Customer Information -->
    <div class="section">
        <div class="section-title">👤 INFORMASI PELANGGAN</div>
        <table class="info-table">
            <tr>
                <td>Nama</td>
                <td><?php echo htmlspecialchars($service['customer_name']); ?></td>
            </tr>
            <tr>
                <td>Email</td>
                <td><?php echo htmlspecialchars($service['customer_email']); ?></td>
            </tr>
            <tr>
                <td>Telepon</td>
                <td><?php echo htmlspecialchars($service['customer_phone'] ?: '-'); ?></td>
            </tr>
        </table>
    </div>
    
    <!-- Device Information -->
    <div class="section">
        <div class="section-title">💻 INFORMASI PERANGKAT</div>
        <table class="info-table">
            <tr>
                <td>Jenis Perangkat</td>
                <td><?php echo ucfirst($service['device_type']); ?></td>
            </tr>
            <tr>
                <td>Merek</td>
                <td><?php echo htmlspecialchars($service['device_brand']); ?></td>
            </tr>
            <tr>
                <td>Model</td>
                <td><?php echo htmlspecialchars($service['device_model']); ?></td>
            </tr>
            <tr>
                <td>Serial Number</td>
                <td><?php echo htmlspecialchars($service['serial_number'] ?: '-'); ?></td>
            </tr>
        </table>
    </div>
    
    <!-- Service Information -->
    <div class="section">
        <div class="section-title">🔧 INFORMASI SERVIS</div>
        <table class="info-table">
            <tr>
                <td>Status</td>
                <td>
                    <span class="status-badge status-<?php echo str_replace('_', '-', $service['status']); ?>">
                        <?php echo getStatusText($service['status']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td>Tanggal Masuk</td>
                <td><?php echo formatDate($service['created_at']); ?></td>
            </tr>
            <tr>
                <td>Estimasi Selesai</td>
                <td><?php echo $service['estimated_completion'] ? formatDate($service['estimated_completion']) : 'Belum ditentukan'; ?></td>
            </tr>
            <tr>
                <td>Estimasi Biaya</td>
                <td><?php echo $service['estimated_cost'] ? formatRupiah($service['estimated_cost']) : 'Belum ditentukan'; ?></td>
            </tr>
            <?php if ($service['final_cost']): ?>
            <tr>
                <td>Biaya Final</td>
                <td><strong><?php echo formatRupiah($service['final_cost']); ?></strong></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- Problem Description -->
    <div class="section">
        <div class="section-title">⚠️ DESKRIPSI MASALAH</div>
        <div class="problem-description">
            <?php echo nl2br(htmlspecialchars($service['problem_description'])); ?>
        </div>
    </div>
    
    <!-- Timeline -->
    <?php if (!empty($timeline)): ?>
    <div class="section">
        <div class="section-title">📋 TIMELINE PERBAIKAN</div>
        <div class="timeline">
            <?php foreach ($timeline as $item): ?>
            <div class="timeline-item">
                <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                <span class="status-badge status-<?php echo str_replace('_', '-', $item['status']); ?>">
                    <?php echo getStatusText($item['status']); ?>
                </span>
                <br>
                <?php echo htmlspecialchars($item['description']); ?>
                <div class="timeline-date">
                    📅 <?php echo formatDate($item['created_at']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Notes -->
    <?php if ($service['notes']): ?>
    <div class="section">
        <div class="section-title">📝 CATATAN TEKNISI</div>
        <div class="notes">
            <?php echo nl2br(htmlspecialchars($service['notes'])); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <div class="footer">
        <p>Dokumen ini dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></p>
        <p><?php echo getCopyrightOwner(); ?> (<?php echo CopyrightProtection::getCopyrightUrl(); ?>) - <?php echo getSiteDescription(); ?></p>
    </div>
</body>
</html>
```