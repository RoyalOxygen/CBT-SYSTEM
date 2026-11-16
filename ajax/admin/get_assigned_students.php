<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Ensure user is admin
if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if ($exam_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam ID']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT es.id, es.student_id, es.status, es.assigned_at,
               s.matric_number, s.first_name, s.last_name, s.email,
               eb.id as batch_id, eb.batch_name,
               d.dept_name, l.level_name
        FROM exam_students es
        JOIN students s ON es.student_id = s.id
        LEFT JOIN exam_batches eb ON es.batch_id = eb.id
        LEFT JOIN departments d ON s.department_id = d.id
        LEFT JOIN levels l ON s.level_id = l.id
        WHERE es.exam_id = ?
        ORDER BY es.assigned_at DESC
    ");
    $stmt->execute([$exam_id]);
    $students = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'message' => 'Assigned students loaded',
        'students' => $students,
        'total' => count($students)
    ]);
    
} catch (PDOException $e) {
    error_log("Get assigned students error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>