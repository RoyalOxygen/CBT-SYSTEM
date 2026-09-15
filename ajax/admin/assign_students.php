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

$exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;
$student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
$batch_id = isset($_POST['batch_id']) ? intval($_POST['batch_id']) : null;

// Also check for array format
if (empty($student_ids) && isset($_POST['student_ids_array'])) {
    $student_ids = $_POST['student_ids_array'];
}

if ($exam_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam ID']);
    exit;
}

if (empty($student_ids)) {
    echo json_encode(['success' => false, 'message' => 'No students selected']);
    exit;
}

// Ensure student_ids is an array
if (!is_array($student_ids)) {
    $student_ids = [$student_ids];
}

try {
    $db = getDB();
    
    // Check if exam exists
    $examStmt = $db->prepare("SELECT id, exam_code, exam_title FROM exams WHERE id = ?");
    $examStmt->execute([$exam_id]);
    $exam = $examStmt->fetch();
    
    if (!$exam) {
        echo json_encode(['success' => false, 'message' => 'Exam not found']);
        exit;
    }
    
    $assigned_count = 0;
    $skipped_count = 0;
    
    foreach ($student_ids as $student_id) {
        $student_id = intval($student_id);
        if ($student_id <= 0) continue;
        
        // Check if already assigned
        $checkStmt = $db->prepare("SELECT id FROM exam_students WHERE exam_id = ? AND student_id = ?");
        $checkStmt->execute([$exam_id, $student_id]);
        
        if ($checkStmt->fetch()) {
            $skipped_count++;
            continue;
        }
        
        // Assign student
        $insertStmt = $db->prepare("
            INSERT INTO exam_students (exam_id, batch_id, student_id, status, assigned_at) 
            VALUES (?, ?, ?, 'assigned', NOW())
        ");
        
        if ($insertStmt->execute([$exam_id, $batch_id, $student_id])) {
            $assigned_count++;
            
            // Log activity
            logActivity('admin', $_SESSION['admin_id'], 'assign_student', 
                       "Assigned student ID: $student_id to exam: {$exam['exam_code']}");
        }
    }
    
    $message = "$assigned_count student(s) assigned successfully";
    if ($skipped_count > 0) {
        $message .= ", $skipped_count were already assigned";
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'assigned' => $assigned_count,
        'skipped' => $skipped_count
    ]);
    
} catch (PDOException $e) {
    error_log("Assign students error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>