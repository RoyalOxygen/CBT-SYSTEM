<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if (!isAdminLoggedIn()) jsonResponse(false, 'Not authenticated');

try {
    $db = getDB();
    $q = sanitize($_GET['q'] ?? '');
    if (strlen($q) < 2) jsonResponse(true, 'Query too short', ['students' => []]);
    
    $stmt = $db->prepare("SELECT id, matric_number, first_name, last_name FROM students 
        WHERE status = 1 AND (matric_number LIKE ? OR first_name LIKE ? OR last_name LIKE ?) LIMIT 20");
    $p = "%$q%";
    $stmt->execute([$p, $p, $p]);
    jsonResponse(true, 'Found', ['students' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
