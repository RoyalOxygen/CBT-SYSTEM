<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isAdminLoggedIn()) {
    jsonResponse(false, 'Unauthorized');
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    jsonResponse(false, 'Invalid security token');
}

$student_id = intval($input['student_id'] ?? 0);
if ($student_id <= 0) {
    jsonResponse(false, 'Invalid student ID');
}

try {
    $db = getDB();
    
    // Get student details for logging
    $stmt = $db->prepare("SELECT matric_number FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        jsonResponse(false, 'Student not found');
    }
    
    // Delete related records first (foreign key constraints)
    $db->prepare("DELETE FROM answers WHERE attempt_id IN (SELECT id FROM exam_attempts WHERE student_id = ?)")->execute([$student_id]);
    $db->prepare("DELETE FROM exam_attempts WHERE student_id = ?")->execute([$student_id]);
    $db->prepare("DELETE FROM exam_students WHERE student_id = ?")->execute([$student_id]);
    $db->prepare("DELETE FROM fingerprint_templates WHERE student_id = ?")->execute([$student_id]);
    $db->prepare("DELETE FROM results WHERE student_id = ?")->execute([$student_id]);
    $db->prepare("DELETE FROM time_extensions WHERE student_id = ?")->execute([$student_id]);
    
    // Delete student
    $db->prepare("DELETE FROM students WHERE id = ?")->execute([$student_id]);
    
    logActivity('admin', $_SESSION['admin_id'], 'delete_student', "Deleted student: " . $student['matric_number']);
    
    jsonResponse(true, 'Student deleted successfully');
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>