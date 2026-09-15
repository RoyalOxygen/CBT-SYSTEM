<?php
/**
 * CBT System - Student Header Template
 * For student dashboard and regular pages
 */
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

$flash = getFlashMessage();
$pageTitle = $pageTitle ?? 'Dashboard';

// Get student info for header if not already available
if (!isset($student) && isset($_SESSION['student_id'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT s.*, d.dept_name, l.level_name FROM students s LEFT JOIN departments d ON s.department_id = d.id LEFT JOIN levels l ON s.level_id = l.id WHERE s.id = ?");
        $stmt->execute([$_SESSION['student_id']]);
        $student = $stmt->fetch();
    } catch (Exception $e) {
        $student = null;
    }
}
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
    <style>
        :root {
            --tsu-navy: #1a1a2e;
            --tsu-dark: #16213e;
            --tsu-blue: #0f3460;
            --tsu-gold: #e8c84c;
            --tsu-gold-light: #f5d742;
            --tsu-primary: #1a73e8;
            --tsu-success: #34a853;
            --tsu-danger: #ea4335;
            --tsu-warning: #fbbc04;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* ===== Taraba State University Header ===== */
        .dashboard-header {
            background: linear-gradient(135deg, var(--tsu-navy) 0%, var(--tsu-dark) 50%, var(--tsu-blue) 100%);
            color: white;
            padding: 30px 0 25px;
            border-bottom: 4px solid var(--tsu-gold);
            border-radius: 0 0 20px 20px;
            margin-bottom: 30px;
        }
        .dashboard-header h2 {
            font-weight: 700;
            font-size: 1.8rem;
            margin: 0;
            letter-spacing: 1px;
        }
        .dashboard-header .subtitle {
            font-size: 0.95rem;
            opacity: 0.8;
            font-weight: 300;
        }
        .dashboard-header .student-badge {
            background: rgba(255,255,255,0.12);
            padding: 10px 25px;
            border-radius: 30px;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .dashboard-header .student-badge .label {
            font-size: 0.7rem;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .dashboard-header .student-badge .value {
            font-weight: 600;
            font-size: 1rem;
        }
        
        /* ===== Student Navbar ===== */
        .student-navbar {
            background: linear-gradient(135deg, var(--tsu-navy) 0%, var(--tsu-dark) 50%, var(--tsu-blue) 100%);
            padding: 0.75rem 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-bottom: 2px solid var(--tsu-gold);
        }
        
        .student-navbar .navbar-brand {
            color: white;
            font-weight: bold;
        }
        
        .student-navbar .navbar-brand:hover {
            color: rgba(255,255,255,0.9);
        }
        
        .nav-link-custom {
            color: white !important;
            opacity: 0.9;
            transition: opacity 0.2s;
        }
        
        .nav-link-custom:hover {
            opacity: 1;
        }
        
        .nav-link-custom.active {
            opacity: 1;
            font-weight: 600;
            border-bottom: 2px solid var(--tsu-gold);
        }
        
        .user-dropdown .dropdown-toggle {
            color: white;
            text-decoration: none;
        }
        
        .user-dropdown .dropdown-toggle::after {
            display: inline-block;
            margin-left: 0.255em;
            vertical-align: 0.255em;
            content: "";
            border-top: 0.3em solid;
            border-right: 0.3em solid transparent;
            border-bottom: 0;
            border-left: 0.3em solid transparent;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }
        
        /* Stats Cards */
        .stat-card {
            border-radius: 12px;
            overflow: hidden;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        /* Exam List Items */
        .exam-list-item {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            cursor: pointer;
        }
        
        .exam-list-item:hover {
            background-color: #f8f9fc;
            transform: translateX(5px);
        }
        
        .exam-list-item.available {
            border-left-color: var(--tsu-success);
        }
        
        .exam-list-item.upcoming {
            border-left-color: var(--tsu-primary);
        }
        
        .exam-list-item.expired {
            border-left-color: #6c757d;
        }
        
        .exam-list-item.bio-pending {
            border-left-color: var(--tsu-warning);
        }
        
        .exam-list-item.submitted {
            border-left-color: #6c757d;
            opacity: 0.8;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
        
        /* Toast Container */
        .toast-container {
            z-index: 9999;
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 9998;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-header h2 {
                font-size: 1.3rem;
            }
            .dashboard-header .student-badge {
                padding: 6px 16px;
            }
            .student-navbar .navbar-brand {
                font-size: 1rem;
            }
            .stat-card h3 {
                font-size: 1.25rem;
            }
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeInUp 0.5s ease-out forwards;
        }
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="mainToast" class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay d-none">
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-white">Processing...</p>
        </div>
    </div>

    <!-- Student Navbar -->
    <nav class="navbar navbar-expand-lg student-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>/student/dashboard.php">
                <i class="bi bi-mortarboard-fill me-2"></i>
                <strong><?php echo APP_NAME; ?></strong>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="studentNavbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" 
                           href="<?php echo APP_URL; ?>/student/dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom <?php echo basename($_SERVER['PHP_SELF']) === 'results.php' ? 'active' : ''; ?>" 
                           href="<?php echo APP_URL; ?>/student/results.php">
                            <i class="bi bi-clipboard-check me-1"></i> Results
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom <?php echo basename($_SERVER['PHP_SELF']) === 'biometric_enroll.php' ? 'active' : ''; ?>" 
                           href="<?php echo APP_URL; ?>/student/biometric_enroll.php">
                            <i class="bi bi-fingerprint me-1"></i> Biometric
                        </a>
                    </li>
                </ul>
                
                <!-- User Dropdown -->
                <div class="dropdown ms-3 user-dropdown">
                    <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <?php if (isset($student['photo']) && $student['photo']): ?>
                            <img src="<?php echo APP_URL; ?>/assets/uploads/student_photos/<?php echo e($student['photo']); ?>" class="rounded-circle" width="35" height="35" style="object-fit:cover;">
                        <?php else: ?>
                            <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width:35px;height:35px;">
                                <i class="bi bi-person fs-5"></i>
                            </div>
                        <?php endif; ?>
                        <span class="ms-2 text-white d-none d-md-inline"><?php echo e($student['first_name'] ?? 'Student'); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="bi bi-person me-2"></i> My Profile
                        </a></li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#passwordModal">
                            <i class="bi bi-key me-2"></i> Change Password
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo APP_URL; ?>/student/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>


    <!-- Main Content -->
    <div class="container py-4">
        <!-- Flash Messages -->
        <?php if ($flash['message']): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-<?php echo $flash['type'] === 'error' ? 'exclamation-circle' : 'check-circle'; ?> me-2"></i>
                <?php echo e($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <script>
            // Global variables
            const APP_URL = '<?php echo APP_URL; ?>';
            const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';
            
            // Toast notification function
            function showToast(message, type = 'success') {
                const toastEl = document.getElementById('mainToast');
                const toastBody = document.getElementById('toastMessage');
                const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
                
                toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info');
                
                if (type === 'success') {
                    toastEl.classList.add('bg-success', 'text-white');
                } else if (type === 'error') {
                    toastEl.classList.add('bg-danger', 'text-white');
                } else if (type === 'warning') {
                    toastEl.classList.add('bg-warning');
                } else {
                    toastEl.classList.add('bg-info', 'text-white');
                }
                
                toastBody.textContent = message;
                toast.show();
            }
            
            // Loading overlay
            function showLoading() {
                document.getElementById('loadingOverlay').classList.remove('d-none');
            }
            
            function hideLoading() {
                document.getElementById('loadingOverlay').classList.add('d-none');
            }
            
            // Session keepalive heartbeat - keeps student sessions alive as long as
            // possible unless explicitly logged out or a new login occurs.
            let keepaliveTimer = null;
            function startKeepalive() {
                if (keepaliveTimer) clearInterval(keepaliveTimer);
                keepaliveTimer = setInterval(function() {
                    fetch(APP_URL + '/ajax/student/keepalive.php', {
                        method: 'GET',
                        credentials: 'same-origin'
                    }).catch(function() {});
                }, 120000); // every 2 minutes
            }
            document.addEventListener('DOMContentLoaded', startKeepalive);
        </script>