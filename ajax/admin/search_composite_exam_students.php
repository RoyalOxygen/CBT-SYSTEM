<?php
/**
 * Search students assigned to a composite exam by matric number
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

header('Content-Type: application/json');

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$examId = intval($_GET['exam_id'] ?? 0);
$query = trim($_GET['q'] ?? '');

if ($examId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam ID']);
    exit;
}

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short', 'students' => []]);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT 
            ces.id AS assignment_id,
            ces.student_id,
            ces.status,
            s.matric_number,
            s.first_name,
            s.last_name,
            cea.status AS attempt_status,
            cea.id AS attempt_id
        FROM composite_exam_students ces
        JOIN students s ON s.id = ces.student_id
        LEFT JOIN composite_exam_attempts cea ON cea.composite_exam_id = ces.composite_exam_id AND cea.student_id = ces.student_id
        WHERE ces.composite_exam_id = ?
            AND (s.matric_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)
        ORDER BY s.matric_number ASC
        LIMIT 50
    ");
    
    $like = '%' . $query . '%';
    $stmt->execute([$examId, $like, $like, $like]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'count' => count($students)
    ]);
    
} catch (PDOException $e) {
    error_log("Search composite exam students error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>