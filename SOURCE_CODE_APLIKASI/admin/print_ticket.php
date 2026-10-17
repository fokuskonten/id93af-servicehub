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
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket - <?php echo $service['ticket_number']; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                font-size: 12px;
                line-height: 1.4;
                margin: 0;
                padding: 0;
            }
            
            .ticket-container {
                border: 1px solid #000;
                padding: 10px;
                margin: 0;
                page-break-inside: avoid;
                width: 100%;
                max-width: none;
            }
        }
        
        body {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
        }
        
        .ticket-container {
            max-width: 250px;
            margin: 2rem auto;
            background: white;
            border: 1px dashed #333;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .ticket-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #0d6efd;
            margin: 0.5rem 0;
            letter-spacing: 1px;
            word-break: break-all;
        }
        
        .company-name {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .ticket-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .print-date {
            font-size: 0.7rem;
            color: #999;
            margin-top: 1rem;
            border-top: 1px dashed #ccc;
            padding-top: 0.5rem;
        }
        
        .ticket-icon {
            font-size: 2rem;
            color: #0d6efd;
            margin-bottom: 0.5rem;
        }
        
        /* Narrow paper specific styles */
        @media print {
            @page {
                size: 80mm auto;
                margin: 5mm;
            }
            
            .ticket-container {
                max-width: none;
                width: 100%;
                margin: 0;
                padding: 5mm;
                border: none;
                box-shadow: none;
            }
            
            .ticket-number {
                font-size: 1.5rem;
                margin: 3mm 0;
            }
            
            .company-name {
                font-size: 0.9rem;
                margin-bottom: 2mm;
            }
            
            .ticket-label {
                font-size: 0.7rem;
                margin-bottom: 2mm;
            }
            
            .ticket-icon {
                font-size: 1.5rem;
                margin-bottom: 2mm;
            }
            
            .print-date {
                font-size: 0.6rem;
                margin-top: 3mm;
                padding-top: 2mm;
            }
        }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="no-print mb-4">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-ticket-alt me-2"></i>Print Nomor Tiket</h4>
                <div>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print me-2"></i>Cetak
                    </button>
                    <button onclick="window.close()" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ticket -->
    <div class="ticket-container">
        <div class="ticket-icon">
            <i class="fas fa-ticket-alt"></i>
        </div>
        
        <div class="company-name">
            <?php echo getCopyrightOwner(); ?> (<?php echo CopyrightProtection::getCopyrightUrl(); ?>)
        </div>
        
        <div class="ticket-label">
            Nomor Tiket Servis
        </div>
        
        <div class="ticket-number">
            <?php echo $service['ticket_number']; ?>
        </div>
        
        <div class="print-date">
            Dicetak: <?php echo date('d/m/Y H:i'); ?>
        </div>
    </div>
    
    <script>
        // Auto focus for better print experience
        window.onload = function() {
            window.focus();
        }
    </script>
</body>
</html>