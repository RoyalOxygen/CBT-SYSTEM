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
    $minutes = intval($data['extra_minutes'] ?? 0);
    $type = $data['type'] ?? 'regular';
    
    if ($type === 'composite') {
        $stmt = $db->prepare("SELECT time_extension_minutes, composite_exam_id, student_id FROM composite_exam_attempts WHERE id = ?");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch();
        
        if (!$attempt) jsonResponse(false, 'Attempt not found');
        
        $newExtension = ($attempt['time_extension_minutes'] ?? 0) + $minutes;
        $db->prepare("UPDATE composite_exam_attempts SET time_extension_minutes = ? WHERE id = ?")
            ->execute([$newExtension, $attemptId]);
        
        logActivity('admin', $_SESSION['admin_id'], 'extend_composite_time', 
                   "Added $minutes min to composite attempt $attemptId");
        jsonResponse(true, "Added $minutes minutes");
    } else {
        $stmt = $db->prepare("SELECT time_extension_minutes, exam_id, student_id FROM exam_attempts WHERE id = ?");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch();
        
        if (!$attempt) jsonResponse(false, 'Attempt not found');
        
        $newExtension = ($attempt['time_extension_minutes'] ?? 0) + $minutes;
        $db->prepare("UPDATE exam_attempts SET time_extension_minutes = ? WHERE id = ?")
            ->execute([$newExtension, $attemptId]);
        
        $db->prepare("INSERT INTO time_extensions (exam_id, student_id, attempt_id, extra_minutes, reason, granted_by) 
                      VALUES (?, ?, ?, ?, 'Admin extension', ?)")
            ->execute([$attempt['exam_id'], $attempt['student_id'], $attemptId, $minutes, $_SESSION['admin_id']]);
        
        logActivity('admin', $_SESSION['admin_id'], 'extend_time', "Added $minutes min to attempt $attemptId");
        jsonResponse(true, "Added $minutes minutes");
    }
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>