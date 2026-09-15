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
        $db->prepare("UPDATE composite_exam_results SET published = 1, published_at = NOW(), published_by = ? 
                     WHERE composite_exam_id = ? AND published = 0")
            ->execute([$_SESSION['admin_id'], $examId]);
        
        $count = $db->prepare("SELECT COUNT(*) FROM composite_exam_results WHERE composite_exam_id = ? AND published = 1");
        $count->execute([$examId]);
        $published = $count->fetchColumn();
        
        logActivity('admin', $_SESSION['admin_id'], 'publish_composite_results', "Published $published composite results for exam $examId");
        jsonResponse(true, "$published composite results published");
    } else {
        $db->prepare("UPDATE results SET published = 1, published_at = NOW(), published_by = ? 
                     WHERE exam_id = ? AND published = 0")
            ->execute([$_SESSION['admin_id'], $examId]);
        
        $count = $db->prepare("SELECT COUNT(*) FROM results WHERE exam_id = ? AND published = 1");
        $count->execute([$examId]);
        $published = $count->fetchColumn();
        
        logActivity('admin', $_SESSION['admin_id'], 'publish_results', "Published $published results for exam $examId");
        jsonResponse(true, "$published results published");
    }
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>