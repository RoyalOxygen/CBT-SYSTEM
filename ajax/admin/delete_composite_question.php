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
    $id = intval($data['question_id'] ?? 0);
    
    if (!$id) jsonResponse(false, 'Invalid question ID');
    
    $db->beginTransaction();
    
    // Get sub-question IDs
    $subStmt = $db->prepare("SELECT id FROM composite_sub_questions WHERE composite_question_id = ?");
    $subStmt->execute([$id]);
    $subIds = $subStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Delete options
    if (!empty($subIds)) {
        $placeholders = implode(',', array_fill(0, count($subIds), '?'));
        $db->prepare("DELETE FROM composite_sub_options WHERE composite_sub_question_id IN ($placeholders)")->execute($subIds);
    }
    
    // Delete sub-questions
    $db->prepare("DELETE FROM composite_sub_questions WHERE composite_question_id = ?")->execute([$id]);
    
    // Delete main question
    $db->prepare("DELETE FROM composite_questions WHERE id = ?")->execute([$id]);
    
    $db->commit();
    
    logActivity('admin', $_SESSION['admin_id'], 'delete_composite_question', "Deleted composite question ID: $id");
    jsonResponse(true, 'Question deleted successfully');
    
} catch (PDOException $e) {
    if ($db->transaction) $db->rollBack();
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>