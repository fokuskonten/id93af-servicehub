-- ========================================================
-- FOKUSKONTEN OFFICIAL DATABASE SCHEME
-- SKU: ID93AF | Aplikasi: ServiceHub
-- Kurasi & Distribusi: FokusKonten Digital (fokuskonten.my.id)
-- Kompatibilitas: MySQL 5.7+ / MariaDB 10.4+ / XAMPP / Laragon
-- ========================================================

-- Database: service_repair_db
-- TechFix Pro - Service Repair Management System

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database creation
-- [SANITASI FOKUSKONTEN] CREATE DATABASE IF NOT EXISTS `service_repair_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- [SANITASI FOKUSKONTEN] USE `service_repair_db`;

-- --------------------------------------------------------

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `services`
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `device_type` enum('laptop','computer','handphone') NOT NULL,
  `device_brand` varchar(50) NOT NULL,
  `device_model` varchar(100) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `problem_description` text NOT NULL,
  `status` enum('pending','in_progress','waiting_parts','testing','completed','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `final_cost` decimal(10,2) DEFAULT NULL,
  `estimated_completion` datetime DEFAULT NULL,
  `actual_completion` datetime DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `warranty_period` int(11) DEFAULT NULL COMMENT 'Warranty in days',
  `parts_used` text DEFAULT NULL COMMENT 'JSON array of parts used',
  `labor_hours` decimal(4,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_device_type` (`device_type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `services_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `service_timeline`
CREATE TABLE `service_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in_progress','waiting_parts','testing','completed','delivered','cancelled') NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `service_timeline_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_timeline_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `service_attachments`
CREATE TABLE `service_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `attachment_type` enum('before','after','document','receipt') NOT NULL DEFAULT 'document',
  `description` text DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `idx_attachment_type` (`attachment_type`),
  CONSTRAINT `service_attachments_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `service_parts`
CREATE TABLE `service_parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `part_name` varchar(200) NOT NULL,
  `part_number` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `warranty_period` int(11) DEFAULT NULL COMMENT 'Warranty in days',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`),
  KEY `idx_part_name` (`part_name`),
  CONSTRAINT `service_parts_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `notifications`
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `related_type` enum('service','user','system') DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `settings`
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Insert default data

-- Default admin user
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `address`, `is_active`, `email_verified_at`) VALUES
('Administrator', 'admin@techfix.com', '081234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Jakarta, Indonesia', 1, NOW()),
('John Customer', 'customer@test.com', '081234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Bandung, Indonesia', 1, NOW());

-- Default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_public`) VALUES
('site_name', 'TechFix Pro', 'string', 'Website name', 1),
('site_description', 'Jasa Servis Komputer, Laptop & Handphone Terpercaya', 'string', 'Website description', 1),
('contact_phone', '081234567890', 'string', 'Contact phone number', 1),
('contact_email', 'info@techfixpro.com', 'string', 'Contact email address', 1),
('contact_address', 'Jakarta, Indonesia', 'string', 'Business address', 1),
('business_hours', '{"monday":"08:00-17:00","tuesday":"08:00-17:00","wednesday":"08:00-17:00","thursday":"08:00-17:00","friday":"08:00-17:00","saturday":"08:00-14:00","sunday":"closed"}', 'json', 'Business operating hours', 1),
('default_warranty_days', '30', 'number', 'Default warranty period in days', 0),
('auto_ticket_number', '1', 'boolean', 'Auto generate ticket numbers', 0),
('email_notifications', '1', 'boolean', 'Enable email notifications', 0),
('sms_notifications', '0', 'boolean', 'Enable SMS notifications', 0);

-- Sample service data
INSERT INTO `services` (`customer_id`, `ticket_number`, `device_type`, `device_brand`, `device_model`, `serial_number`, `problem_description`, `status`, `estimated_cost`, `estimated_completion`, `priority`) VALUES
(2, 'TF20241201001', 'laptop', 'ASUS', 'VivoBook 14', 'ASU123456789', 'Laptop tidak bisa menyala, kemungkinan masalah pada power supply atau motherboard', 'in_progress', 500000.00, '2024-12-05 17:00:00', 'high'),
(2, 'TF20241201002', 'handphone', 'Samsung', 'Galaxy A54', 'SAM987654321', 'Layar pecah dan touch screen tidak responsif', 'waiting_parts', 350000.00, '2024-12-07 15:00:00', 'medium');

-- Sample timeline data
INSERT INTO `service_timeline` (`service_id`, `title`, `description`, `status`) VALUES
(1, 'Tiket Dibuat', 'Tiket servis berhasil dibuat dan menunggu konfirmasi', 'pending'),
(1, 'Konfirmasi Diterima', 'Tiket dikonfirmasi dan perangkat siap untuk diperiksa', 'in_progress'),
(1, 'Diagnosis Awal', 'Melakukan pemeriksaan awal pada perangkat. Ditemukan masalah pada power supply.', 'in_progress'),
(2, 'Tiket Dibuat', 'Tiket servis berhasil dibuat dan menunggu konfirmasi', 'pending'),
(2, 'Konfirmasi Diterima', 'Tiket dikonfirmasi dan perangkat siap untuk diperiksa', 'in_progress'),
(2, 'Menunggu Spare Part', 'Spare part layar Samsung Galaxy A54 sedang dipesan', 'waiting_parts');

COMMIT;

-- --------------------------------------------------------

-- Create indexes for better performance
CREATE INDEX idx_services_customer_status ON services(customer_id, status);
CREATE INDEX idx_services_created_date ON services(DATE(created_at));
CREATE INDEX idx_timeline_service_date ON service_timeline(service_id, created_at);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);

-- --------------------------------------------------------

-- Create views for reporting
CREATE VIEW service_summary AS
SELECT 
    s.id,
    s.ticket_number,
    s.device_type,
    s.device_brand,
    s.device_model,
    s.status,
    s.estimated_cost,
    s.final_cost,
    s.created_at,
    s.updated_at,
    u.name as customer_name,
    u.email as customer_email,
    u.phone as customer_phone,
    DATEDIFF(COALESCE(s.actual_completion, NOW()), s.created_at) as days_in_service
FROM services s
JOIN users u ON s.customer_id = u.id;

-- --------------------------------------------------------

-- Create stored procedures
DELIMITER //

CREATE PROCEDURE GetServiceStats()
BEGIN
    SELECT 
        COUNT(*) as total_services,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
        SUM(CASE WHEN status = 'waiting_parts' THEN 1 ELSE 0 END) as waiting_parts_count,
        SUM(CASE WHEN status = 'testing' THEN 1 ELSE 0 END) as testing_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_count,
        SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as this_month_count,
        COALESCE(SUM(final_cost), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN final_cost ELSE 0 END), 0) as this_month_revenue
    FROM services;
END //

DELIMITER ;

-- --------------------------------------------------------

-- Create triggers
DELIMITER //

CREATE TRIGGER update_service_timestamp 
    BEFORE UPDATE ON services
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //

CREATE TRIGGER create_notification_on_status_change
    AFTER UPDATE ON services
    FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO notifications (user_id, title, message, type, related_type, related_id)
        VALUES (
            NEW.customer_id,
            CONCAT('Status Update: ', NEW.ticket_number),
            CONCAT('Status tiket Anda telah diperbarui menjadi: ', NEW.status),
            'info',
            'service',
            NEW.id
        );
    END IF;
END //

DELIMITER ;
-- INJEKSI RESMI KREDENSIAL DEFAULT FOKUSKONTEN (admin / admin)
UPDATE users SET password = '$2y$10$Auftywg2fH3ZRo/6wNuwLO8K2XSwUzeV46nJJx96VorTCe.tnl5MK' WHERE id = 1 OR role = 'admin';
UPDATE users SET password = '$2y$10$Auftywg2fH3ZRo/6wNuwLO8K2XSwUzeV46nJJx96VorTCe.tnl5MK' WHERE id = 2;
