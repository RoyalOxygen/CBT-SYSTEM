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
        $db->prepare("UPDATE composite_exam_attempts SET status = 'submitted', end_time = NOW(), submission_method = 'admin' WHERE id = ?")
            ->execute([$attemptId]);
        logActivity('admin', $_SESSION['admin_id'], 'force_end_composite', "Force ended composite attempt $attemptId");
    } else {
        $db->prepare("UPDATE exam_attempts SET status = 'submitted', end_time = NOW(), submission_method = 'admin' WHERE id = ?")
            ->execute([$attemptId]);
        logActivity('admin', $_SESSION['admin_id'], 'force_end', "Force ended attempt $attemptId");
    }
    
    jsonResponse(true, 'Exam ended for student');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>