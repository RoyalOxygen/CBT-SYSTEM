<?php
/**
 * Remove a student from a composite exam
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

header('Content-Type: application/json');

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$assignmentId = intval($input['assignment_id'] ?? 0);

if ($assignmentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid assignment ID']);
    exit;
}

try {
    $db = getDB();
    
    // Get details for logging
    $stmt = $db->prepare("
        SELECT ces.composite_exam_id, ces.student_id, s.matric_number
        FROM composite_exam_students ces
        JOIN students s ON ces.student_id = s.id
        WHERE ces.id = ?
    ");
    $stmt->execute([$assignmentId]);
    $assignment = $stmt->fetch();
    
    if (!$assignment) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit;
    }
    
    // Delete the assignment
    $delete = $db->prepare("DELETE FROM composite_exam_students WHERE id = ?");
    $delete->execute([$assignmentId]);
    
    logActivity('admin', $_SESSION['admin_id'], 'remove_composite_student',
               "Removed student {$assignment['matric_number']} from composite exam ID: {$assignment['composite_exam_id']}");
    
    echo json_encode(['success' => true, 'message' => 'Student removed successfully']);
    
} catch (PDOException $e) {
    error_log("Remove composite student error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>