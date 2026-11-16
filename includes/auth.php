<?php
// Authentication check for admin pages
if (!defined('CBT_SYSTEM')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in as admin
if (!isAdminLoggedIn()) {
    // Store the requested URL to redirect back after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    redirect(APP_URL . '/admin/index.php');
}

// Refresh last activity time
$_SESSION['last_activity'] = time();

// Verify admin still exists and is active
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, full_name, email, role, photo, status FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    
    if (!$admin || $admin['status'] != 1) {
        session_destroy();
        redirect(APP_URL . '/admin/index.php', 'Account disabled or not found', 'error');
    }
    
    // Update session with latest info
    $_SESSION['admin_name'] = $admin['full_name'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_photo'] = $admin['photo'];
    
} catch (PDOException $e) {
    error_log("Auth verification error: " . $e->getMessage());
    redirect(APP_URL . '/admin/logout.php', 'System error', 'error');
}