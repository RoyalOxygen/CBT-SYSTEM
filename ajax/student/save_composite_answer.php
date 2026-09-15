<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!isStudentLoggedIn()) jsonResponse(false, 'Not authenticated');

try {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    $attemptId = intval($data['attempt_id'] ?? 0);
    $subQuestionId = intval($data['composite_sub_question_id'] ?? 0);
    $answer = strtoupper(sanitize($data['answer'] ?? ''));
    
    if (!$attemptId || !$subQuestionId) jsonResponse(false, 'Missing parameters');
    
    // Verify attempt belongs to student
    $check = $db->prepare("SELECT id FROM composite_exam_attempts WHERE id = ? AND student_id = ? AND status = 'in_progress'");
    $check->execute([$attemptId, $_SESSION['student_id']]);
    if (!$check->fetch()) jsonResponse(false, 'Invalid attempt');
    
    // Get correct answer
    $correctStmt = $db->prepare("SELECT correct_answer, marks FROM composite_sub_questions WHERE id = ?");
    $correctStmt->execute([$subQuestionId]);
    $question = $correctStmt->fetch();
    
    if (!$question) jsonResponse(false, 'Question not found');
    
    $isCorrect = ($answer === $question['correct_answer']) ? 1 : 0;
    $marks = $isCorrect ? floatval($question['marks']) : 0;
    
    // Upsert answer
    $db->prepare("INSERT INTO composite_exam_answers 
        (attempt_id, composite_sub_question_id, selected_answer, is_correct, marks_obtained, answered_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            selected_answer = VALUES(selected_answer), 
            is_correct = VALUES(is_correct), 
            marks_obtained = VALUES(marks_obtained), 
            answered_at = NOW()")
        ->execute([$attemptId, $subQuestionId, $answer, $isCorrect, $marks]);
    
    jsonResponse(true, 'Answer saved');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>