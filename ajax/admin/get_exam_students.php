<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isAdminLoggedIn()) {
    jsonResponse(false, 'Unauthorized access');
}

$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_GET['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    jsonResponse(false, 'Invalid security token');
}

$exam_id = intval($_GET['exam_id'] ?? 0);

if ($exam_id <= 0) {
    jsonResponse(false, 'Invalid exam ID');
}

try {
    $db = getDB();
    
    // Get students who have taken this exam (have attempts)
    $stmt = $db->prepare("
        SELECT DISTINCT 
            es.id, 
            es.student_id, 
            es.status as assignment_status,
            s.matric_number, 
            s.first_name, 
            s.last_name,
            ea.status as attempt_status,
            ea.start_time,
            ea.end_time
        FROM exam_students es
        JOIN students s ON es.student_id = s.id
        LEFT JOIN exam_attempts ea ON ea.exam_id = es.exam_id AND ea.student_id = es.student_id
        WHERE es.exam_id = ?
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->execute([$exam_id]);
    $students = $stmt->fetchAll();
    
    jsonResponse(true, 'Students loaded', ['students' => $students]);
    
} catch (PDOException $e) {
    error_log("Get exam students error: " . $e->getMessage());
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>