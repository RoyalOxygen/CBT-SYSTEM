<?php
/**
 * Assign students to a composite exam
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

header('Content-Type: application/json');

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$compositeExamId = intval($_POST['composite_exam_id'] ?? 0);
$studentIds = $_POST['student_ids'] ?? [];

if ($compositeExamId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam ID']);
    exit;
}

if (empty($studentIds) || !is_array($studentIds)) {
    echo json_encode(['success' => false, 'message' => 'No students selected']);
    exit;
}

try {
    $db = getDB();
    
    // Verify exam exists
    $examStmt = $db->prepare("SELECT id, exam_code FROM composite_exams WHERE id = ?");
    $examStmt->execute([$compositeExamId]);
    $exam = $examStmt->fetch();
    
    if (!$exam) {
        echo json_encode(['success' => false, 'message' => 'Composite exam not found']);
        exit;
    }
    
    $assignedCount = 0;
    $skippedCount = 0;
    
    foreach ($studentIds as $studentId) {
        $studentId = intval($studentId);
        if ($studentId <= 0) continue;
        
        // Check if already assigned
        $check = $db->prepare("SELECT id FROM composite_exam_students WHERE composite_exam_id = ? AND student_id = ?");
        $check->execute([$compositeExamId, $studentId]);
        if ($check->fetch()) {
            $skippedCount++;
            continue;
        }
        
        // Insert assignment
        $insert = $db->prepare("INSERT INTO composite_exam_students 
            (composite_exam_id, student_id, status, assigned_at) 
            VALUES (?, ?, 'assigned', NOW())");
        
        if ($insert->execute([$compositeExamId, $studentId])) {
            $assignedCount++;
            logActivity('admin', $_SESSION['admin_id'], 'assign_composite_student', 
                       "Assigned student ID: $studentId to composite exam: {$exam['exam_code']}");
        }
    }
    
    $message = "$assignedCount student(s) assigned successfully";
    if ($skippedCount > 0) $message .= ", $skippedCount already assigned";
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'assigned' => $assignedCount,
        'skipped' => $skippedCount
    ]);
    
} catch (PDOException $e) {
    error_log("Assign composite students error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>