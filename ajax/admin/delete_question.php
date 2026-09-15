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
    
    $db->prepare("UPDATE questions SET status = 0 WHERE id = ?")->execute([$id]);
    logActivity('admin', $_SESSION['admin_id'], 'delete_question', "Deleted question ID: $id");
    jsonResponse(true, 'Question deleted');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
