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
<<<<<<< HEAD
        try {
            composite_attempt_archive_save($db, $attemptId, 'admin');
        } catch (Throwable $e) {
            error_log('Composite attempt archive save failed: ' . $e->getMessage());
        }
        $db->prepare("UPDATE composite_exam_attempts SET status = 'auto_submitted', end_time = NOW(), submission_method = 'admin' WHERE id = ?")
            ->execute([$attemptId]);
        $stmt = $db->prepare("SELECT composite_exam_id, student_id FROM composite_exam_attempts WHERE id = ?");
        $stmt->execute([$attemptId]);
        $ended = $stmt->fetch();
        if ($ended) {
            try {
                composite_attempt_archive_write_result($db, $attemptId);
            } catch (Throwable $e) {
                error_log('Composite force-end result save failed: ' . $e->getMessage());
            }
            $db->prepare("DELETE FROM composite_exam_answers WHERE attempt_id = ?")->execute([$attemptId]);
            $db->prepare("UPDATE composite_exam_students SET status = 'submitted' WHERE composite_exam_id = ? AND student_id = ?")
                ->execute([$ended['composite_exam_id'], $ended['student_id']]);
        }
        logActivity('admin', $_SESSION['admin_id'], 'force_end_composite', "Force ended composite attempt $attemptId");
    } else {
        try {
            attempt_archive_save($db, $attemptId, 'admin');
        } catch (Throwable $e) {
            error_log('Attempt archive save failed: ' . $e->getMessage());
        }
        $db->prepare("UPDATE exam_attempts SET status = 'auto_submitted', end_time = NOW(), submission_method = 'admin' WHERE id = ?")
            ->execute([$attemptId]);
        $stmt = $db->prepare("SELECT exam_id, student_id FROM exam_attempts WHERE id = ?");
        $stmt->execute([$attemptId]);
        $ended = $stmt->fetch();
        if ($ended) {
            try {
                attempt_archive_write_result($db, $attemptId);
            } catch (Throwable $e) {
                error_log('Force-end result save failed: ' . $e->getMessage());
            }
            $db->prepare("DELETE FROM answers WHERE attempt_id = ?")->execute([$attemptId]);
            $db->prepare("UPDATE exam_students SET status = 'submitted' WHERE exam_id = ? AND student_id = ?")
                ->execute([$ended['exam_id'], $ended['student_id']]);
            $db->prepare("UPDATE students SET current_exam_id = NULL WHERE id = ?")->execute([$ended['student_id']]);
        }
=======
        $db->prepare("UPDATE composite_exam_attempts SET status = 'submitted', end_time = NOW(), submission_method = 'admin' WHERE id = ?")
            ->execute([$attemptId]);
        logActivity('admin', $_SESSION['admin_id'], 'force_end_composite', "Force ended composite attempt $attemptId");
    } else {
        $db->prepare("UPDATE exam_attempts SET status = 'submitted', end_time = NOW(), submission_method = 'admin' WHERE id = ?")
            ->execute([$attemptId]);
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
        logActivity('admin', $_SESSION['admin_id'], 'force_end', "Force ended attempt $attemptId");
    }
    
    jsonResponse(true, 'Exam ended for student');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>