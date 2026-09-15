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
    $resultId = intval($data['result_id'] ?? 0);
    $publish = intval($data['publish'] ?? 0);
    $type = $data['type'] ?? 'regular';
    
    if ($type === 'composite') {
        $db->prepare("UPDATE composite_exam_results SET published = ?, published_at = " . ($publish ? 'NOW()' : 'NULL') . ", published_by = ? WHERE id = ?")
            ->execute([$publish, $publish ? $_SESSION['admin_id'] : null, $resultId]);
    } else {
        $db->prepare("UPDATE results SET published = ?, published_at = " . ($publish ? 'NOW()' : 'NULL') . ", published_by = ? WHERE id = ?")
            ->execute([$publish, $publish ? $_SESSION['admin_id'] : null, $resultId]);
    }
    
    jsonResponse(true, $publish ? 'Result published' : 'Result unpublished');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>