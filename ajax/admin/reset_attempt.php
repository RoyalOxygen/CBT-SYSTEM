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
    $attemptId = intval($data['attempt_id'] ?? 0);
    $type = $data['type'] ?? 'regular';
    
    if ($type === 'composite') {
        $db->prepare("UPDATE composite_exam_attempts SET status = 'reset', end_time = NOW() WHERE id = ?")
            ->execute([$attemptId]);
        
        $stmt = $db->prepare("SELECT composite_exam_id, student_id FROM composite_exam_attempts WHERE id = ?");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch();
        
        if ($attempt) {
            $db->prepare("UPDATE composite_exam_students SET status = 'assigned' WHERE composite_exam_id = ? AND student_id = ?")
                ->execute([$attempt['composite_exam_id'], $attempt['student_id']]);
        }
        
        logActivity('admin', $_SESSION['admin_id'], 'reset_composite_attempt', "Reset composite attempt $attemptId");
    } else {
        $db->prepare("UPDATE exam_attempts SET status = 'reset', end_time = NOW() WHERE id = ?")
            ->execute([$attemptId]);
        
        $stmt = $db->prepare("SELECT exam_id, student_id FROM exam_attempts WHERE id = ?");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch();
        
        if ($attempt) {
            $db->prepare("UPDATE exam_students SET status = 'assigned' WHERE exam_id = ? AND student_id = ?")
                ->execute([$attempt['exam_id'], $attempt['student_id']]);
        }
        
        logActivity('admin', $_SESSION['admin_id'], 'reset_attempt', "Reset attempt $attemptId");
    }
    
    jsonResponse(true, 'Attempt reset successfully');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>