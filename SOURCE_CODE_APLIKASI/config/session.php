<?php
// Load tracking system
require_once __DIR__ . '/../classes/UserTracker.php';
require_once __DIR__ . '/../classes/SourceProtector.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize tracking
$userTracker = null;
$sourceProtector = null;

try {
    $userTracker = new UserTracker();
    $sourceProtector = new SourceProtector();
} catch (Exception $e) {
    error_log("Failed to initialize tracking systems: " . $e->getMessage());
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is customer
function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user role
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

// Get current user name
function getCurrentUserName() {
    return $_SESSION['name'] ?? 'Guest';
}

// Logout function
function logout() {
    global $userTracker;
    
    // Track logout
    if ($userTracker && isLoggedIn()) {
        $userTracker->trackLogout(getCurrentUserId(), session_id());
    }
    
    session_destroy();
    redirect('login.php');
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// Require admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('403.php');
    }
}

// Require customer
function requireCustomer() {
    requireLogin();
    if (!isCustomer()) {
        redirect('403.php');
    }
}

// Set flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

// Get and clear flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Track page view
function trackPageView() {
    global $userTracker;
    
    if ($userTracker && isLoggedIn()) {
        $pageUrl = $_SERVER['REQUEST_URI'] ?? '';
        $userTracker->trackPageView(getCurrentUserId(), session_id(), $pageUrl);
    }
}

// Track user action
function trackUserAction($action, $details = null) {
    global $userTracker;
    
    if ($userTracker && isLoggedIn()) {
        $userTracker->trackAction(getCurrentUserId(), session_id(), $action, $details);
    }
}

// Track security alert
function trackSecurityAlert($alertType, $details) {
    global $userTracker;
    
    if ($userTracker) {
        $userId = isLoggedIn() ? getCurrentUserId() : null;
        $sessionId = session_id();
        $userTracker->trackSecurityAlert($userId, $sessionId, $alertType, $details);
    }
}

// Auto-track page views
if (isLoggedIn()) {
    trackPageView();
}
?>