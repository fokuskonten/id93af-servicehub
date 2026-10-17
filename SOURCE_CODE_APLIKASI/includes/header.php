<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header .brand {
            font-size: 1.2rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-header .brand i {
            margin-right: 0.5rem;
            font-size: 1.5rem;
        }
        
        .sidebar.collapsed .sidebar-header .brand-text {
            display: none;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .sidebar-nav .nav-item {
            margin-bottom: 0.25rem;
        }
        
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0;
        }
        
        .sidebar-nav .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar-nav .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            border-left: 4px solid #fff;
        }
        
        .sidebar-nav .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }
        
        .sidebar.collapsed .sidebar-nav .nav-link {
            padding: 0.75rem;
            justify-content: center;
        }
        
        .sidebar.collapsed .sidebar-nav .nav-link span {
            display: none;
        }
        
        .sidebar.collapsed .sidebar-nav .nav-link i {
            margin-right: 0;
        }
        
        /* Main Content */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
            min-height: 100vh;
        }
        
        .main-wrapper.collapsed {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        .top-navbar {
            background: white;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--secondary-color);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.25rem;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            background-color: #f8f9fa;
            color: var(--primary-color);
        }
        
        .main-content {
            padding: 1.5rem;
        }
        
        /* User Profile in Sidebar */
        .sidebar-user {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.2);
        }
        
        .sidebar-user .user-info {
            display: flex;
            align-items: center;
        }
        
        .sidebar-user .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .sidebar-user .user-details h6 {
            margin: 0;
            font-size: 0.9rem;
            color: white;
        }
        
        .sidebar-user .user-details small {
            color: rgba(255,255,255,0.7);
        }
        
        .sidebar.collapsed .sidebar-user .user-details {
            display: none;
        }
        
        .sidebar.collapsed .sidebar-user .user-avatar {
            margin-right: 0;
        }
        
        /* Cards and Components */
        .service-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.15);
        }
        
        .stats-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .stats-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-card .label {
            font-size: 1rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-wrapper {
                margin-left: 0;
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                display: none;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }
        
        /* Public pages without sidebar */
        .no-sidebar {
            margin-left: 0;
        }
        
        .no-sidebar .main-wrapper {
            margin-left: 0 !important;
        }
        
        .no-sidebar .sidebar {
            display: none !important;
        }
        
        /* Full width hero sections */
        .min-vh-100 {
            min-height: 100vh;
        }
        
        .min-vh-50 {
            min-height: 50vh;
        }
        
        /* Enhanced hero section */
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="1000,100 1000,0 0,100"/></svg>');
            background-size: cover;
            background-position: bottom;
        }
        
        .hero-section .container-fluid {
            position: relative;
            z-index: 2;
        }
        
        /* Enhanced service cards */
        .card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        /* Animated elements */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-section .col-lg-6:first-child {
            animation: fadeInUp 1s ease-out;
        }
        
        .hero-section .col-lg-6:last-child {
            animation: fadeInUp 1s ease-out 0.3s both;
        }
    </style>
</head>
<body>
    <?php
    // Determine current directory and user type
    $script_path = $_SERVER['PHP_SELF'];
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    
    // More precise path detection
    $is_admin_page = strpos($script_path, '/admin/') !== false && strpos($script_path, '/public/') === false;
    $is_customer_page = strpos($script_path, '/customer/') !== false && strpos($script_path, '/public/') === false;
    $is_public_page = strpos($script_path, '/public/') !== false;
    $is_logged_in = function_exists('isLoggedIn') && isLoggedIn();
    $is_admin = function_exists('isAdmin') && isAdmin();
    $is_customer = function_exists('isCustomer') && isCustomer();
    
    // Determine base path
    $base_path = '';
    if ($is_admin_page || $is_customer_page || $is_public_page) {
        $base_path = '../';
    }
    
    // Show sidebar only for logged in users
    $show_sidebar = $is_logged_in && ($is_admin || $is_customer);
    ?>
    
    <?php if ($show_sidebar): ?>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <a href="<?php echo $base_path; ?>index.php" class="brand">
                <i class="fas fa-tools"></i>
                <span class="brand-text"><?php echo getSiteName(); ?></span>
            </a>
        </div>
        
        <!-- Sidebar Navigation -->
        <nav class="sidebar-nav">
            <?php if ($is_admin): ?>
            <!-- Admin Navigation -->
            <div class="nav-item">
                <a href="<?php echo $is_admin_page ? '' : '../admin/'; ?>dashboard.php" 
                   class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo $is_admin_page ? '' : '../admin/'; ?>services.php" 
                   class="nav-link <?php echo $current_page === 'services' || $current_page === 'service_detail' ? 'active' : ''; ?>">
                    <i class="fas fa-tools"></i>
                    <span>Kelola Servis</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo $is_admin_page ? '' : '../admin/'; ?>customers.php" 
                   class="nav-link <?php echo $current_page === 'customers' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Kelola Pelanggan</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo $is_admin_page ? '' : '../admin/'; ?>reports.php" 
                   class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Laporan</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo $is_admin_page ? '' : '../admin/'; ?>settings.php" 
                   class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Pengaturan</span>
                </a>
            </div>
            
            <?php elseif ($is_customer): ?>
            <!-- Customer Navigation -->
            <div class="nav-item">
                <a href="<?php echo $is_customer_page ? '' : '../customer/'; ?>dashboard.php" 
                   class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo $is_customer_page ? '' : '../customer/'; ?>tickets.php" 
                   class="nav-link <?php echo $current_page === 'tickets' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Tiket Saya</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo $is_customer_page ? '' : '../customer/'; ?>create_ticket.php" 
                   class="nav-link <?php echo $current_page === 'create_ticket' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>Buat Tiket</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../public/track.php" class="nav-link">
                    <i class="fas fa-search"></i>
                    <span>Lacak Servis</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?php echo $is_customer_page ? '' : '../customer/'; ?>profile.php" class="nav-link">
                    <i class="fas fa-user-edit"></i>
                    <span>Profil</span>
                </a>
            </div>
            <?php endif; ?>
        </nav>
        
        <!-- User Profile -->
        <div class="sidebar-user">
            <div class="user-info">
                <div class="user-avatar" style="background: none; padding: 0;">
                    <?php 
                    if (function_exists('getCurrentUserId')) {
                        try {
                            require_once $base_path . 'classes/User.php';
                            $userObj = new User();
                            $avatarUrl = $userObj->getAvatarUrl(getCurrentUserId());
                            if ($avatarUrl !== 'assets/img/default-avatar.png' && file_exists($base_path . $avatarUrl)): 
                    ?>
                        <img src="<?php echo $base_path . $avatarUrl; ?>" alt="Avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php 
                        endif;
                        } catch (Exception $e) {
                            // Show default avatar on error
                            echo '<div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;"><i class="fas fa-user"></i></div>';
                        }
                    } else {
                        // Default avatar when function not available
                        echo '<div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;"><i class="fas fa-user"></i></div>';
                    }
                    ?>
                </div>
                <div class="user-details">
                    <h6><?php echo function_exists('getCurrentUserName') ? getCurrentUserName() : 'User'; ?></h6>
                    <small><?php echo $is_admin ? 'Administrator' : 'Customer'; ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php endif; ?>
    
    <!-- Main Wrapper -->
    <div class="main-wrapper <?php echo $show_sidebar ? '' : 'no-sidebar'; ?>" id="mainWrapper">
        <?php if ($show_sidebar): ?>
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="d-flex align-items-center flex-grow-1">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h5 class="mb-0 ms-3"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h5>
            </div>
            <div class="d-flex align-items-center ms-auto">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>
                        <?php echo function_exists('getCurrentUserName') ? getCurrentUserName() : 'User'; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo $is_admin ? ($is_admin_page ? 'profile.php' : 'admin/profile.php') : ($is_customer_page ? 'profile.php' : 'customer/profile.php'); ?>">
                            <i class="fas fa-user-edit me-2"></i>Profil
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Public Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow">
            <div class="container">
                <a class="navbar-brand fw-bold" href="<?php echo $base_path; ?>index.php">
                    <i class="fas fa-tools me-2"></i><?php echo getSiteName(); ?>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>index.php">
                                <i class="fas fa-home me-1"></i>Beranda
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>public/services.php">
                                <i class="fas fa-cogs me-1"></i>Layanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>public/track.php">
                                <i class="fas fa-search me-1"></i>Lacak Servis
                            </a>
                        </li>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_path; ?>register.php">
                                <i class="fas fa-user-plus me-1"></i>Daftar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <?php endif; ?>

        <!-- Flash Messages -->
        <?php 
        if (function_exists('getFlashMessage')) {
            $flash = getFlashMessage();
            if ($flash): 
        ?>
        <div class="<?php echo $show_sidebar ? 'main-content' : 'container'; ?> mt-3">
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php 
            endif; 
        }
        ?>

        <!-- Main Content -->
        <div class="<?php echo $show_sidebar ? 'main-content' : ''; ?>">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Sidebar Toggle Script -->
    <script>
        $(document).ready(function() {
            // Sidebar toggle functionality
            $('#sidebarToggle').on('click', function() {
                $('#sidebar').toggleClass('collapsed');
                $('#mainWrapper').toggleClass('collapsed');
            });
            
            // Mobile sidebar toggle
            $('#sidebarToggle').on('click', function() {
                if (window.innerWidth <= 768) {
                    $('#sidebar').toggleClass('show');
                    $('#sidebarOverlay').toggleClass('show');
                }
            });
            
            // Close sidebar on overlay click (mobile)
            $('#sidebarOverlay').on('click', function() {
                $('#sidebar').removeClass('show');
                $('#sidebarOverlay').removeClass('show');
            });
            
            // Auto-hide alerts after 5 seconds
            $('.alert').delay(5000).fadeOut('slow');
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Confirm delete actions
            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                const title = $(this).data('title') || 'Hapus Data';
                const text = $(this).data('text') || 'Apakah Anda yakin ingin menghapus data ini?';
                
                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });
    </script>
</body>
</html>