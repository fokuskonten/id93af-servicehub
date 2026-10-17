<?php
require_once __DIR__ . '/../config/database.php';

class Service {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Get all services
    public function getAllServices($limit = null, $offset = null) {
        $sql = "SELECT s.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                FROM services s 
                JOIN users u ON s.customer_id = u.id 
                ORDER BY s.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }
        
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt);
        return $this->db->resultSet($stmt);
    }
    
    // Get services by customer
    public function getServicesByCustomer($customerId) {
        $sql = "SELECT * FROM services WHERE customer_id = :customer_id ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt, [':customer_id' => $customerId]);
        return $this->db->resultSet($stmt);
    }
    
    // Get service by ID
    public function getServiceById($id) {
        $sql = "SELECT s.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                FROM services s 
                JOIN users u ON s.customer_id = u.id 
                WHERE s.id = :id";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt, [':id' => $id]);
        return $this->db->single($stmt);
    }
    
    // Create new service
    public function createService($data) {
        $sql = "INSERT INTO services (
                    customer_id, ticket_number, device_type, device_brand, device_model, 
                    serial_number, problem_description, estimated_cost, estimated_completion, 
                    status, created_at
                ) VALUES (
                    :customer_id, :ticket_number, :device_type, :device_brand, :device_model,
                    :serial_number, :problem_description, :estimated_cost, :estimated_completion,
                    :status, NOW()
                )";
        
        $stmt = $this->db->query($sql);
        $result = $this->db->execute($stmt, $data);
        
        if ($result) {
            $serviceId = $this->db->lastInsertId();
            
            // Add initial timeline entry
            $this->addTimelineEntry($serviceId, 'Tiket Dibuat', 'Tiket servis berhasil dibuat dan menunggu konfirmasi', 'pending');
            
            return $serviceId;
        }
        
        return false;
    }
    
    // Update service
    public function updateService($id, $data) {
        error_log("updateService called - ID: $id, Data: " . print_r($data, true));
        
        $setParts = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        
        $sql = "UPDATE services SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE id = :id";
        error_log("Update SQL: $sql");
        error_log("Update Params: " . print_r($params, true));
        
        $stmt = $this->db->query($sql);
        
        try {
            $result = $this->db->execute($stmt, $params);
            error_log("SQL execution result: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                $rowCount = $stmt->rowCount();
                error_log("Rows affected: $rowCount");
                return $rowCount > 0;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("updateService SQL error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Update service status
    public function updateServiceStatus($id, $status, $notes = null) {
        // Debug logging
        error_log("updateServiceStatus called - ID: $id, Status: $status, Notes: $notes");
        
        $updateData = [
            'status' => $status
        ];
        
        if ($notes) {
            $updateData['notes'] = $notes;
        }
        
        try {
            // First, check if service exists
            $existingService = $this->getServiceById($id);
            if (!$existingService) {
                error_log("Service not found with ID: $id");
                return false;
            }
            
            error_log("Existing service found: " . $existingService['ticket_number']);
            
            $result = $this->updateService($id, $updateData);
            error_log("updateService result: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                // Add timeline entry
                $statusTexts = [
                    'pending' => 'Menunggu Konfirmasi',
                    'in_progress' => 'Sedang Dikerjakan',
                    'waiting_parts' => 'Menunggu Spare Part',
                    'testing' => 'Tahap Testing',
                    'completed' => 'Selesai',
                    'delivered' => 'Sudah Diambil',
                    'cancelled' => 'Dibatalkan'
                ];
                
                $title = 'Status Diperbarui: ' . ($statusTexts[$status] ?? $status);
                $description = $notes ?: 'Status tiket diperbarui menjadi ' . ($statusTexts[$status] ?? $status);
                
                $timelineResult = $this->addTimelineEntry($id, $title, $description, $status);
                error_log("Timeline entry result: " . ($timelineResult ? 'true' : 'false'));
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("updateServiceStatus error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Delete service
    public function deleteService($id) {
        // Delete timeline entries first
        $sql = "DELETE FROM service_timeline WHERE service_id = :id";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt, [':id' => $id]);
        
        // Delete service
        $sql = "DELETE FROM services WHERE id = :id";
        $stmt = $this->db->query($sql);
        return $this->db->execute($stmt, [':id' => $id]);
    }
    
    // Get service timeline
    public function getServiceTimeline($serviceId) {
        $sql = "SELECT * FROM service_timeline WHERE service_id = :service_id ORDER BY created_at ASC";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt, [':service_id' => $serviceId]);
        $timeline = $this->db->resultSet($stmt);
        
        // Mark current and completed items
        $currentStatus = $this->getCurrentServiceStatus($serviceId);
        $statusOrder = ['pending', 'in_progress', 'waiting_parts', 'testing', 'completed', 'delivered'];
        $currentIndex = array_search($currentStatus, $statusOrder);
        
        foreach ($timeline as &$item) {
            $itemIndex = array_search($item['status'], $statusOrder);
            $item['is_completed'] = $itemIndex !== false && $itemIndex < $currentIndex;
            $item['is_current'] = $item['status'] === $currentStatus;
        }
        
        return $timeline;
    }
    
    // Add timeline entry
    public function addTimelineEntry($serviceId, $title, $description, $status) {
        $sql = "INSERT INTO service_timeline (service_id, title, description, status, created_at) 
                VALUES (:service_id, :title, :description, :status, NOW())";
        $stmt = $this->db->query($sql);
        return $this->db->execute($stmt, [
            ':service_id' => $serviceId,
            ':title' => $title,
            ':description' => $description,
            ':status' => $status
        ]);
    }
    
    // Get current service status
    private function getCurrentServiceStatus($serviceId) {
        $sql = "SELECT status FROM services WHERE id = :id";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt, [':id' => $serviceId]);
        $result = $this->db->single($stmt);
        return $result ? $result['status'] : 'pending';
    }
    
    // Get status progress percentage
    public function getStatusProgress($status) {
        $progress = [
            'pending' => 10,
            'in_progress' => 40,
            'waiting_parts' => 60,
            'testing' => 80,
            'completed' => 100,
            'delivered' => 100,
            'cancelled' => 0
        ];
        
        return $progress[$status] ?? 0;
    }
    
    // Get service statistics
    public function getServiceStats() {
        $stats = [];
        
        // Total services
        $sql = "SELECT COUNT(*) as total FROM services";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt);
        $result = $this->db->single($stmt);
        $stats['total'] = $result['total'];
        
        // Services by status
        $statuses = ['pending', 'in_progress', 'waiting_parts', 'testing', 'completed', 'delivered', 'cancelled'];
        foreach ($statuses as $status) {
            $sql = "SELECT COUNT(*) as count FROM services WHERE status = :status";
            $stmt = $this->db->query($sql);
            $this->db->execute($stmt, [':status' => $status]);
            $result = $this->db->single($stmt);
            $stats[$status] = $result['count'];
        }
        
        // Today's services
        $sql = "SELECT COUNT(*) as count FROM services WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt);
        $result = $this->db->single($stmt);
        $stats['today'] = $result['count'];
        
        // This month's services
        $sql = "SELECT COUNT(*) as count FROM services WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt);
        $result = $this->db->single($stmt);
        $stats['this_month'] = $result['count'];
        
        // Revenue this month
        $sql = "SELECT SUM(final_cost) as revenue FROM services WHERE final_cost IS NOT NULL AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt);
        $result = $this->db->single($stmt);
        $stats['revenue'] = $result['revenue'] ?: 0;
        
        return $stats;
    }
    
    // Get customer ticket count by status
    public function getCustomerTicketCount($customerId, $status = null) {
        if ($status) {
            $sql = "SELECT COUNT(*) as count FROM services WHERE customer_id = :customer_id AND status = :status";
            $stmt = $this->db->query($sql);
            $this->db->execute($stmt, [':customer_id' => $customerId, ':status' => $status]);
        } else {
            $sql = "SELECT COUNT(*) as count FROM services WHERE customer_id = :customer_id";
            $stmt = $this->db->query($sql);
            $this->db->execute($stmt, [':customer_id' => $customerId]);
        }
        
        $result = $this->db->single($stmt);
        return $result['count'];
    }
    
    // Search services
    public function searchServices($keyword, $status = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT s.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                FROM services s 
                JOIN users u ON s.customer_id = u.id 
                WHERE (s.ticket_number LIKE :keyword 
                   OR s.device_brand LIKE :keyword 
                   OR s.device_model LIKE :keyword 
                   OR u.name LIKE :keyword 
                   OR u.email LIKE :keyword)";
        
        $params = [':keyword' => "%$keyword%"];
        
        if ($status) {
            $sql .= " AND s.status = :status";
            $params[':status'] = $status;
        }
        
        if ($dateFrom) {
            $sql .= " AND DATE(s.created_at) >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(s.created_at) <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        $sql .= " ORDER BY s.created_at DESC";
        
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt, $params);
        return $this->db->resultSet($stmt);
    }
    
    // Get service by ticket number
    public function getServiceByTicketNumber($ticketNumber) {
        $sql = "SELECT s.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                FROM services s 
                JOIN users u ON s.customer_id = u.id 
                WHERE s.ticket_number = :ticket_number";
        $stmt = $this->db->query($sql);
        $this->db->execute($stmt, [':ticket_number' => $ticketNumber]);
        return $this->db->single($stmt);
    }
}
?>