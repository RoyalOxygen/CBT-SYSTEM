<?php
/**
 * Get assigned students for a composite exam
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

header('Content-Type: application/json');

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$compositeExamId = intval($_GET['composite_exam_id'] ?? 0);

if ($compositeExamId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam ID']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT 
            ces.id as assignment_id,
            ces.status,
            ces.assigned_at,
            s.id as student_id,
            s.matric_number,
            s.first_name,
            s.last_name,
            s.email,
            d.dept_name,
            l.level_name
        FROM composite_exam_students ces
        JOIN students s ON ces.student_id = s.id
        LEFT JOIN departments d ON s.department_id = d.id
        LEFT JOIN levels l ON s.level_id = l.id
        WHERE ces.composite_exam_id = ?
        ORDER BY ces.assigned_at DESC
    ");
    $stmt->execute([$compositeExamId]);
    $students = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'total' => count($students)
    ]);
    
} catch (PDOException $e) {
    error_log("Get assigned composite students error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>