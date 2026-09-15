<?php
/**
 * CBT System - Student Logout
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

if (isset($_SESSION['student_id'])) {
    try {
        $db = getDB();
        $db->prepare("UPDATE students SET login_status = 0, current_exam_id = NULL WHERE id = ?")
            ->execute([$_SESSION['student_id']]);
    } catch (PDOException $e) {
        error_log("Logout error: " . $e->getMessage());
    }
    logActivity('student', $_SESSION['student_id'], 'logout', 'Student logged out');
}

$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
session_destroy();

header('Location: ' . APP_URL . '/student/login.php');
exit;
