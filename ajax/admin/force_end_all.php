<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!isAdminLoggedIn()) jsonResponse(false, 'Not authenticated');

try {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    $examId = intval($data['exam_id'] ?? 0);
    $type = $data['type'] ?? 'regular';
    
    if ($type === 'composite') {
        $db->prepare("UPDATE composite_exam_attempts SET status = 'submitted', end_time = NOW(), submission_method = 'admin' 
                     WHERE composite_exam_id = ? AND status = 'in_progress'")
            ->execute([$examId]);
        logActivity('admin', $_SESSION['admin_id'], 'force_end_all_composite', "Force ended all composite attempts for exam $examId");
    } else {
        $db->prepare("UPDATE exam_attempts SET status = 'submitted', end_time = NOW(), submission_method = 'admin' 
                     WHERE exam_id = ? AND status = 'in_progress'")
            ->execute([$examId]);
        logActivity('admin', $_SESSION['admin_id'], 'force_end_all', "Force ended all attempts for exam $examId");
    }
    
    jsonResponse(true, 'Exam ended for all students');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>