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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$assignment_id = isset($input['assignment_id']) ? intval($input['assignment_id']) : 0;

if ($assignment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid assignment ID']);
    exit;
}

try {
    $db = getDB();
    
    // Get assignment details before deletion
    $stmt = $db->prepare("SELECT exam_id, student_id FROM exam_students WHERE id = ?");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch();
    
    if (!$assignment) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit;
    }
    
    // Delete assignment
    $deleteStmt = $db->prepare("DELETE FROM exam_students WHERE id = ?");
    $deleteStmt->execute([$assignment_id]);
    
    // Log activity
    logActivity('admin', $_SESSION['admin_id'], 'remove_student_assignment', 
               "Removed student ID: {$assignment['student_id']} from exam ID: {$assignment['exam_id']}");
    
    echo json_encode(['success' => true, 'message' => 'Student removed from exam successfully']);
    
} catch (PDOException $e) {
    error_log("Remove student error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>