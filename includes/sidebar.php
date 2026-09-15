<?php
/**
 * CBT System - Admin Sidebar Navigation
 */
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

$currentPage = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['admin_role'] ?? 'admin';
?>
<nav id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo APP_URL; ?>/admin/dashboard.php" class="text-decoration-none">
            <h4 class="text-white mb-0"><i class="bi bi-laptop-fill me-2"></i>CBT Pro</h4>
        </a>
    </div>
    
    <ul class="list-unstyled components">
        <!-- Dashboard - Visible to all roles -->
        <li class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <a href="<?php echo APP_URL; ?>/admin/dashboard.php">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <?php if ($role === 'super_admin' || $role === 'admin'): ?>
        <!-- Academic Section - Super Admin & Admin only -->
        <li class="sidebar-heading">Academic</li>
        
        <li class="<?php echo in_array($currentPage, ['students.php', 'bulk_upload.php']) ? 'active' : ''; ?>">
            <a href="<?php echo APP_URL; ?>/admin/students.php">
                <i class="bi bi-people-fill"></i>
                <span>Students</span>
            </a>
        </li>
        
        <li class="<?php echo $currentPage === 'exams.php' ? 'active' : ''; ?>">
            <a href="<?php echo APP_URL; ?>/admin/exams.php">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>Exams</span>
            </a>
        </li>
        
        <li class="<?php echo $currentPage === 'questions.php' ? 'active' : ''; ?>">
            <a href="<?php echo APP_URL; ?>/admin/questions.php">
                <i class="bi bi-question-circle-fill"></i>
                <span>Questions</span>
            </a>
        </li>
        
        <li class="<?php echo $currentPage === 'assign_exam.php' ? 'active' : ''; ?>">
            <a href="<?php echo APP_URL; ?>/admin/assign_exam.php">
                <i class="bi bi-person-check-fill"></i>
                <span>Assign Students</span>
            </a>
        </li>
        <?php endif; ?>
        
        <!-- Fetch from CMS - Visible to ALL roles -->
        <li class="<?php echo $currentPage === 'fetch_student.php' ? 'active' : ''; ?>">
            <a href="<?php echo APP_URL; ?>/admin/fetch_student.php">
                <i class="bi bi-cloud-download"></i>
                <span>Fetch from CMS</span>
            </a>
        </li>

        <li class="sidebar-heading">Composite Exams</li>

<li class="<?php echo $currentPage === 'composite_exams.php' ? 'active' : ''; ?>">
    <a href="<?php echo APP_URL; ?>/admin/composite_exams.php">
        <i class="bi bi-diagram-3"></i>
        <span>Composite Exams</span>
    </a>
</li>
        
        <?php if ($role === 'super_admin' || $role === 'admin' || $role === 'invigilator'): ?>
        <!-- Operations Section - All roles -->
        <li class="sidebar-heading">Operations</li>
        
        <li class="<?php echo $currentPage === 'monitoring.php' ? 'active' : ''; ?>">
            <a href="<?php echo APP_URL; ?>/admin/monitoring.php">
                <i class="bi bi-tv-fill"></i>
                <span>Live Monitoring</span>
                <span class="badge bg-danger ms-auto d-none" id="liveCount">0</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($role === 'super_admin' || $role === 'admin'): ?>
        <!-- Results & Biometric - Admin & Super Admin only -->
        <li class="<?php echo $currentPage === 'results.php' ? 'active' : ''; ?>">
            <a href="<?php echo APP_URL; ?>/admin/results.php">
                <i class="bi bi-clipboard-data-fill"></i>
                <span>Results</span>
            </a>
        </li>
        
        <li class="<?php echo $currentPage === 'biometric_enroll.php' ? 'active' : ''; ?>">
            <a href="<?php echo APP_URL; ?>/admin/biometric_enroll.php">
                <i class="bi bi-fingerprint"></i>
                <span>Biometric</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($role === 'super_admin'): ?>
        <!-- System Section - Super Admin only -->
        <li class="sidebar-heading">System</li>
        
        <li class="<?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
            <a href="<?php echo APP_URL; ?>/admin/settings.php">
                <i class="bi bi-gear-fill"></i>
                <span>Settings</span>
            </a>
        </li>
        <?php endif; ?>
        
        <!-- Logout - Visible to all -->
        <li class="mt-auto">
            <a href="<?php echo APP_URL; ?>/admin/logout.php" class="text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>