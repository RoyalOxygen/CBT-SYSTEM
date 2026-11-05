<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if (!isAdminLoggedIn()) jsonResponse(false, 'Not authenticated');

try {
    $db = getDB();
    $examId = intval($_GET['exam_id'] ?? 0);
    $type = $_GET['type'] ?? 'regular';
    
    if ($type === 'composite') {
        // Composite exam stats
        $active = $db->prepare("SELECT COUNT(*) FROM composite_exam_attempts WHERE composite_exam_id = ? AND status = 'in_progress'");
        $active->execute([$examId]);
        
        $submitted = $db->prepare("SELECT COUNT(*) FROM composite_exam_attempts WHERE composite_exam_id = ? AND status IN ('submitted','auto_submitted')");
        $submitted->execute([$examId]);
        
        $stmt = $db->prepare("SELECT cea.*, s.matric_number, s.first_name, s.last_name, s.photo,
            (SELECT COUNT(*) FROM composite_exam_answers WHERE attempt_id = cea.id) as answered_count
            FROM composite_exam_attempts cea 
            JOIN students s ON cea.student_id = s.id 
            WHERE cea.composite_exam_id = ? AND cea.status = 'in_progress' 
            ORDER BY cea.start_time DESC");
        $stmt->execute([$examId]);
    } else {
        // Regular exam stats
        $active = $db->prepare("SELECT COUNT(*) FROM exam_attempts WHERE exam_id = ? AND status = 'in_progress'");
        $active->execute([$examId]);
        
        $submitted = $db->prepare("SELECT COUNT(*) FROM exam_attempts WHERE exam_id = ? AND status IN ('submitted','auto_submitted')");
        $submitted->execute([$examId]);
        
        $stmt = $db->prepare("SELECT ea.*, s.matric_number, s.first_name, s.last_name, s.photo,
            (SELECT COUNT(*) FROM answers WHERE attempt_id = ea.id) as answered_count
            FROM exam_attempts ea 
            JOIN students s ON ea.student_id = s.id 
            WHERE ea.exam_id = ? AND ea.status = 'in_progress' 
            ORDER BY ea.start_time DESC");
        $stmt->execute([$examId]);
    }
    
    $students = $stmt->fetchAll();
    
    jsonResponse(true, 'Data refreshed', [
        'active_count' => $active->fetchColumn(),
        'submitted_count' => $submitted->fetchColumn(),
        'students' => $students,
        'type' => $type,
        'reload_required' => false // Set to true if you want to force reload
    ]);
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>