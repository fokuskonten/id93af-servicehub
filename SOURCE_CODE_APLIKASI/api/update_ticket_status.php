<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../classes/Service.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
$status = isset($_POST['status']) ? sanitize($_POST['status']) : '';
$notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';

if (!$ticketId || !$status) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$serviceObj = new Service();

// Validate status
$validStatuses = ['pending', 'in_progress', 'waiting_parts', 'testing', 'completed', 'delivered', 'cancelled'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Update status
$result = $serviceObj->updateServiceStatus($ticketId, $status, $notes);

if ($result) {
    // Get updated service data
    $updatedService = $serviceObj->getServiceById($ticketId);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Status updated successfully',
        'data' => [
            'ticket_id' => $ticketId,
            'status' => $status,
            'status_text' => getStatusText($status),
            'updated_at' => $updatedService['updated_at'] ?? date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}
?>