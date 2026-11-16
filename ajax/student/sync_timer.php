<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!isStudentLoggedIn()) jsonResponse(false, 'Not authenticated');

// Refresh session activity to keep student session alive during exam
refreshStudentSessionActivity();

try {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    $attemptId = intval($data['attempt_id'] ?? 0);
    
    $stmt = $db->prepare("SELECT start_time, original_duration, time_extension_minutes FROM exam_attempts WHERE id = ? AND student_id = ? AND status = 'in_progress'");
    $stmt->execute([$attemptId, $_SESSION['student_id']]);
    $attempt = $stmt->fetch();
    
    if (!$attempt) jsonResponse(false, 'Invalid attempt');
    
    $duration = (($attempt['original_duration'] ?? 60) + ($attempt['time_extension_minutes'] ?? 0)) * 60;
    $elapsed = time() - strtotime($attempt['start_time']);
    $remaining = max(0, $duration - $elapsed);
    
    jsonResponse(true, 'Timer synced', ['time_remaining' => $remaining]);
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
