<?php
/**
 * CBT System - Admin Header Template
 */
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

$flash = getFlashMessage();
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title><?php echo e($pageTitle); ?> - <?php echo APP_NAME; ?></title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/custom.css">
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="mainToast" class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="wrapper">
        <?php require_once __DIR__ . '/sidebar.php'; ?>
        
        <div class="main-content">
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-link d-lg-none" id="sidebarToggle">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    
                    <h5 class="mb-0 d-none d-md-block"><?php echo e($pageTitle); ?></h5>
                    
                    <div class="d-flex align-items-center gap-3">
                        <!-- Theme Toggle -->
                        <button class="btn btn-link text-dark" id="themeToggle" title="Toggle Dark Mode">
                            <i class="bi bi-moon-fill"></i>
                        </button>
                        
                        <!-- Notifications -->
                        <div class="dropdown">
                            <button class="btn btn-link text-dark position-relative" data-bs-toggle="dropdown">
                                <i class="bi bi-bell-fill"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notifCount">
                                    0
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" id="notifDropdown">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-muted" href="#">No new notifications</a></li>
                            </ul>
                        </div>
                        
                        <!-- Admin Profile -->
                        <div class="dropdown">
                            <button class="btn btn-link text-dark dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <span class="d-none d-md-inline"><?php echo e($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo APP_URL; ?>/admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <div class="container-fluid py-4">
                <?php if ($flash['message']): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo e($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
