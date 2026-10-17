<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'service_repair_db');

// Konfigurasi Aplikasi
define('BASE_URL', '/service-repair/');

// Dynamic site settings - will be loaded from database or config file
function getSiteName() {
    // Try to get from settings table first
    try {
        $db = new Database();
        $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name' LIMIT 1");
        $db->execute($stmt);
        $result = $db->single($stmt);
        return $result ? $result['setting_value'] : 'TechFix Pro';
    } catch (Exception $e) {
        return 'TechFix Pro'; // fallback
    }
}

function getSiteDescription() {
    try {
        $db = new Database();
        $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'site_description' LIMIT 1");
        $db->execute($stmt);
        $result = $db->single($stmt);
        return $result ? html_entity_decode($result['setting_value'], ENT_QUOTES, 'UTF-8') : 'Jasa Servis Komputer, Laptop & Handphone Terpercaya';
    } catch (Exception $e) {
        return 'Jasa Servis Komputer, Laptop & Handphone Terpercaya'; // fallback
    }
}

function getContactPhone() {
    try {
        $db = new Database();
        $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'contact_phone' LIMIT 1");
        $db->execute($stmt);
        $result = $db->single($stmt);
        return $result ? html_entity_decode($result['setting_value'], ENT_QUOTES, 'UTF-8') : '+6281234567890';
    } catch (Exception $e) {
        return '+6281234567890'; // fallback
    }
}

function getContactEmail() {
    try {
        $db = new Database();
        $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'contact_email' LIMIT 1");
        $db->execute($stmt);
        $result = $db->single($stmt);
        return $result ? html_entity_decode($result['setting_value'], ENT_QUOTES, 'UTF-8') : 'info@techfixpro.com';
    } catch (Exception $e) {
        return 'info@techfixpro.com'; // fallback
    }
}

function getContactAddress() {
    try {
        $db = new Database();
        $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'contact_address' LIMIT 1");
        $db->execute($stmt);
        $result = $db->single($stmt);
        return $result ? html_entity_decode($result['setting_value'], ENT_QUOTES, 'UTF-8') : 'Jakarta, Indonesia';
    } catch (Exception $e) {
        return 'Jakarta, Indonesia'; // fallback
    }
}

// Define constants using functions
define('SITE_NAME', getSiteName());
define('SITE_DESCRIPTION', getSiteDescription());

// Timezone
date_default_timezone_set('Asia/Jakarta');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Koneksi Database
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $dbh;
    private $error;

    public function __construct() {
        // Set DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        
        // Set options
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        );

        // Create PDO instance
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            // For development - remove in production
            die('Database connection failed: ' . $this->error);
        }
    }

    // Get database connection
    public function getConnection() {
        return $this->dbh;
    }

    // Prepare statement
    public function query($query) {
        return $this->dbh->prepare($query);
    }

    // Execute prepared statement
    public function execute($stmt, $params = []) {
        try {
            return $stmt->execute($params);
        } catch(PDOException $e) {
            // Log error for debugging
            error_log("SQL Error: " . $e->getMessage());
            throw $e;
        }
    }

    // Get single record
    public function single($stmt) {
        return $stmt->fetch();
    }

    // Get multiple records
    public function resultSet($stmt) {
        return $stmt->fetchAll();
    }

    // Get row count
    public function rowCount($stmt) {
        return $stmt->rowCount();
    }

    // Get last insert ID
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }

    // Begin transaction
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }

    // Commit transaction
    public function commit() {
        return $this->dbh->commit();
    }

    // Rollback transaction
    public function rollback() {
        return $this->dbh->rollback();
    }
}

// Helper Functions
function redirect($url) {
    // Handle relative URLs properly
    if (strpos($url, 'http') !== 0) {
        // If it's a relative URL, make sure it's properly formatted
        $url = ltrim($url, '/');
    }
    header("Location: " . $url);
    exit();
}

// Load protected copyright system
require_once __DIR__ . '/copyright.php';

// Load license check system (only if not on public pages)
$currentScript = basename($_SERVER['PHP_SELF']);
$isPublicPage = in_array($currentScript, ['index.php', 'login.php', 'register.php']) || 
                strpos($_SERVER['REQUEST_URI'], '/public/') !== false;

if (!$isPublicPage) {
    require_once __DIR__ . '/license_check.php';
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function generateTicketNumber() {
    return 'TF' . date('Ymd') . rand(1000, 9999);
}

function getStatusBadge($status) {
    $badges = [
        'pending' => 'bg-warning',
        'in_progress' => 'bg-info',
        'waiting_parts' => 'bg-secondary',
        'testing' => 'bg-primary',
        'completed' => 'bg-success',
        'delivered' => 'bg-dark',
        'cancelled' => 'bg-danger'
    ];
    
    return $badges[$status] ?? 'bg-secondary';
}

function getStatusText($status) {
    $texts = [
        'pending' => 'Menunggu Konfirmasi',
        'in_progress' => 'Sedang Dikerjakan',
        'waiting_parts' => 'Menunggu Spare Part',
        'testing' => 'Tahap Testing',
        'completed' => 'Selesai',
        'delivered' => 'Sudah Diambil',
        'cancelled' => 'Dibatalkan'
    ];
    
    return $texts[$status] ?? 'Unknown';
}
?>