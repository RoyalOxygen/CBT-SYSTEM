<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$examId = intval($_GET['composite_exam_id'] ?? 0);

if ($examId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam ID']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT 
            ces.id as assignment_id,
            ces.student_id,
            ces.status,
            s.matric_number,
            s.first_name,
            s.last_name
        FROM composite_exam_students ces
        JOIN students s ON ces.student_id = s.id
        WHERE ces.composite_exam_id = ?
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->execute([$examId]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'students' => $students
    ]);
    
} catch (PDOException $e) {
    error_log("Get composite exam students error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>