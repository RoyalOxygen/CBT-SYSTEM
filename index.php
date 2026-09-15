<?php
/**
 * CBT System - Entry Point
 * Redirects to appropriate login page
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

// Check if admin is logged in
if (isAdminLoggedIn()) {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}

// Check if student is logged in
if (isStudentLoggedIn()) {
    header('Location: ' . APP_URL . '/student/dashboard.php');
    exit;
}

// Default redirect to admin login
header('Location: ' . APP_URL . '/student/login.php');
exit;
