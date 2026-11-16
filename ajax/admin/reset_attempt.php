<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
node_require('exam_edit');
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
<<<<<<< HEAD
            $db->prepare("DELETE FROM composite_exam_answers WHERE attempt_id = ?")->execute([$attemptId]);
            $db->prepare("DELETE FROM composite_exam_results WHERE attempt_id = ? OR (composite_exam_id = ? AND student_id = ?)")
                ->execute([$attemptId, $attempt['composite_exam_id'], $attempt['student_id']]);
            try {
                composite_attempt_archive_delete($attemptId);
            } catch (Throwable $e) {
                error_log('Composite attempt archive delete failed: ' . $e->getMessage());
            }
=======
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
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
<<<<<<< HEAD
            $db->prepare("DELETE FROM answers WHERE attempt_id = ?")->execute([$attemptId]);
            $db->prepare("DELETE FROM results WHERE attempt_id = ? OR (exam_id = ? AND student_id = ?)")
                ->execute([$attemptId, $attempt['exam_id'], $attempt['student_id']]);
            try {
                attempt_archive_delete($attemptId);
            } catch (Throwable $e) {
                error_log('Attempt archive delete failed: ' . $e->getMessage());
            }
=======
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
        }
        
        logActivity('admin', $_SESSION['admin_id'], 'reset_attempt', "Reset attempt $attemptId");
    }
    
    jsonResponse(true, 'Attempt reset successfully');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>