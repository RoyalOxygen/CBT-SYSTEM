<?php
// Authentication check for student pages
if (!defined('CBT_SYSTEM')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in as student
if (!isStudentLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    redirect(APP_URL . '/student/login.php');
}

// Refresh last activity time to keep the student session alive
refreshStudentSessionActivity();

// Verify student still exists and is active
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, matric_number, first_name, last_name, status FROM students WHERE id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    $student = $stmt->fetch();

    if (!$student || $student['status'] != 1) {
        session_destroy();
        redirect(APP_URL . '/student/login.php', 'Account disabled or not found', 'error');
    }

    $_SESSION['student_name'] = $student['last_name'] . ', ' . $student['first_name'];
    $_SESSION['matric_number'] = $student['matric_number'];

} catch (PDOException $e) {
    error_log("Student auth verification error: " . $e->getMessage());
    redirect(APP_URL . '/student/login.php', 'System error', 'error');
}
