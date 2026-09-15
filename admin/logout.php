<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Log the logout activity
if (isset($_SESSION['admin_id'])) {
    logActivity('admin', $_SESSION['admin_id'], 'logout', 'Admin logged out');
}

// Clear all session data
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

session_destroy();

// Redirect to login page
header('Location: ' . APP_URL . '/admin/index.php?msg=logged_out');
exit;