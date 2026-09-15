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
    $minutes = intval($data['extra_minutes'] ?? 0);
    $type = $data['type'] ?? 'regular';
    
    if ($type === 'composite') {
        $db->prepare("UPDATE composite_exam_attempts SET time_extension_minutes = COALESCE(time_extension_minutes, 0) + ? 
                     WHERE composite_exam_id = ? AND status = 'in_progress'")
            ->execute([$minutes, $examId]);
        
        logActivity('admin', $_SESSION['admin_id'], 'extend_composite_time_all', 
                   "Added $minutes min to all students in composite exam $examId");
    } else {
        $db->prepare("UPDATE exam_attempts SET time_extension_minutes = COALESCE(time_extension_minutes, 0) + ? 
                     WHERE exam_id = ? AND status = 'in_progress'")
            ->execute([$minutes, $examId]);
        
        logActivity('admin', $_SESSION['admin_id'], 'extend_time_all', 
                   "Added $minutes min to all students in exam $examId");
    }
    
    jsonResponse(true, "Added $minutes minutes to all active students");
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>