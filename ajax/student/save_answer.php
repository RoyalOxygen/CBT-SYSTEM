<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!isStudentLoggedIn()) jsonResponse(false, 'Not authenticated');

// Refresh session activity to keep student session alive during exam
refreshStudentSessionActivity();

try {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    $attemptId = intval($data['attempt_id'] ?? 0);
    $questionId = intval($data['question_id'] ?? 0);
    $answer = sanitize(strtoupper($data['answer'] ?? ''));
    
    // Verify attempt belongs to student
    $check = $db->prepare("SELECT id FROM exam_attempts WHERE id = ? AND student_id = ? AND status = 'in_progress'");
    $check->execute([$attemptId, $_SESSION['student_id']]);
    if (!$check->fetch()) jsonResponse(false, 'Invalid attempt');
    
    // Get correct answer
    $correctStmt = $db->prepare("SELECT correct_answer, marks FROM questions WHERE id = ?");
    $correctStmt->execute([$questionId]);
    $question = $correctStmt->fetch();
    
    $isCorrect = $question && $answer === $question['correct_answer'];
    $marks = $isCorrect ? floatval($question['marks']) : 0;
    
    // Upsert answer
    $db->prepare("INSERT INTO answers (attempt_id, question_id, selected_answer, is_correct, marks_obtained, answered_at) 
        VALUES (?, ?, ?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE selected_answer = VALUES(selected_answer), is_correct = VALUES(is_correct), marks_obtained = VALUES(marks_obtained), answered_at = NOW()")
        ->execute([$attemptId, $questionId, $answer, $isCorrect ? 1 : 0, $marks]);
    
    jsonResponse(true, 'Answer saved');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
