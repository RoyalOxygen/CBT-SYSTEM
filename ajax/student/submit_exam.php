<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response
ini_set('log_errors', 1);

header('Content-Type: application/json');

if (!isStudentLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Refresh session activity to keep student session alive during exam
refreshStudentSessionActivity();

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';

if (!validateCSRFToken($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$attempt_id = intval($input['attempt_id'] ?? 0);
$auto_submit = isset($input['auto_submit']) ? $input['auto_submit'] : false;

if ($attempt_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid attempt ID']);
    exit;
}

try {
    $db = getDB();
    
    // Get attempt details with exam info
    $stmt = $db->prepare("
        SELECT ea.*, e.overall_score, e.has_test_assessment, e.pass_mark, e.exam_code, e.exam_title, e.id as exam_id, e.question_limit, e.show_result
        FROM exam_attempts ea
        JOIN exams e ON ea.exam_id = e.id
        WHERE ea.id = ? AND ea.student_id = ?
    ");
    $stmt->execute([$attempt_id, $_SESSION['student_id']]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attempt) {
        echo json_encode(['success' => false, 'message' => 'Attempt not found']);
        exit;
    }
    
    // Check if already submitted
    if ($attempt['status'] !== 'in_progress') {
        echo json_encode(['success' => false, 'message' => 'Exam already submitted']);
        exit;
    }
    
    // Get all answers for this attempt
    $answersStmt = $db->prepare("
        SELECT a.*, q.correct_answer, q.marks 
        FROM answers a
        JOIN questions q ON a.question_id = q.id
        WHERE a.attempt_id = ?
    ");
    $answersStmt->execute([$attempt_id]);
    $answers = $answersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate scores
    $total_questions = $attempt['total_questions'];
    $total_correct = 0;
    $total_score = 0;
    $total_questions_marks = 0;
    $answered_count = count($answers);
    
    // Store answer details for possible later use
    $answer_details = [];
    
    foreach ($answers as $answer) {
        $total_questions_marks += floatval($answer['marks']);
        $is_correct = ($answer['selected_answer'] === $answer['correct_answer']) ? 1 : 0;
        
        if ($is_correct) {
            $total_correct++;
            $total_score += floatval($answer['marks']);
        }
        
        $answer_details[] = [
            'question_id' => $answer['question_id'],
            'selected_answer' => $answer['selected_answer'],
            'correct_answer' => $answer['correct_answer'],
            'is_correct' => $is_correct,
            'marks_obtained' => $is_correct ? $answer['marks'] : 0
        ];
    }
    
    $wrong_count = $answered_count - $total_correct;
    
    // Calculate percentage of correct answers
    $percentage_correct = ($total_correct / max(1, $total_questions)) * 100;
    
    // Calculate score based on overall_score setting
    $overall_score = intval($attempt['overall_score'] ?? 100);
    
    if ($overall_score == 70) {
        // Questions contribute to 70% of overall score
        $score_from_questions = ($total_correct / max(1, $total_questions)) * 70;
        $score_display = $score_from_questions;
        $total_marks_display = 70;
        $percentage = $score_from_questions;
    } else {
        // Full 100% from questions
        $score_from_questions = ($total_score / max(1, $total_questions_marks)) * 100;
        $score_display = $score_from_questions;
        $total_marks_display = 100;
        $percentage = $score_from_questions;
    }
    
    // Update exam attempt
    $updateStmt = $db->prepare("
        UPDATE exam_attempts 
        SET score = ?, 
            percentage = ?, 
            correct_count = ?, 
            answered_count = ?,
            end_time = NOW(),
            status = 'submitted',
            submission_method = ?
        WHERE id = ?
    ");
    $submission_method = $auto_submit ? 'auto' : 'manual';
    $updateStmt->execute([$score_display, $percentage, $total_correct, $answered_count, $submission_method, $attempt_id]);
    
    // Check if result already exists
    $checkResultStmt = $db->prepare("
        SELECT id FROM results WHERE exam_id = ? AND student_id = ?
    ");
    $checkResultStmt->execute([$attempt['exam_id'], $_SESSION['student_id']]);
    $existingResult = $checkResultStmt->fetch();
    
    $grade = getGrade($percentage);
    
    if ($existingResult) {
        // UPDATE existing result
        $resultStmt = $db->prepare("
            UPDATE results SET 
                attempt_id = ?,
                total_questions = ?,
                answered = ?,
                correct = ?,
                wrong = ?,
                score = ?,
                total_marks = ?,
                percentage = ?,
                grade = ?,
                remark = ?,
                total_questions_score = ?,
                published = 0
            WHERE exam_id = ? AND student_id = ?
        ");
        
        $resultStmt->execute([
            $attempt_id,
            $total_questions,
            $answered_count,
            $total_correct,
            $wrong_count,
            $score_display,
            $total_marks_display,
            $percentage,
            $grade['grade'],
            $grade['remark'],
            $total_correct,
            $attempt['exam_id'],
            $_SESSION['student_id']
        ]);
        
        $message = $auto_submit ? 'Exam auto-submitted successfully!' : 'Exam submitted successfully!';
    } else {
        // INSERT new result
        $resultStmt = $db->prepare("
            INSERT INTO results (
                exam_id, student_id, attempt_id, total_questions, answered, 
                correct, wrong, score, total_marks, percentage, grade, remark, 
                total_questions_score, published, published_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NULL
            )
        ");
        
        $resultStmt->execute([
            $attempt['exam_id'], $_SESSION['student_id'], $attempt_id,
            $total_questions, $answered_count, $total_correct,
            $wrong_count, $score_display, $total_marks_display, $percentage,
            $grade['grade'], $grade['remark'], $total_correct
        ]);
        
        $message = $auto_submit ? 'Exam auto-submitted successfully!' : 'Exam submitted successfully!';
    }
    
    // Delete answers from the answers table after successful submission
    // This clears the student's answers but results are preserved in results table
    $deleteAnswersStmt = $db->prepare("DELETE FROM answers WHERE attempt_id = ?");
    $deleteAnswersStmt->execute([$attempt_id]);
    $deletedCount = $deleteAnswersStmt->rowCount();
    
    error_log("Deleted $deletedCount answers for attempt_id: $attempt_id");
    
    // Update exam_students status
    $db->prepare("UPDATE exam_students SET status = 'submitted' WHERE exam_id = ? AND student_id = ?")
        ->execute([$attempt['exam_id'], $_SESSION['student_id']]);
    
    // Update student current exam
    $db->prepare("UPDATE students SET current_exam_id = NULL WHERE id = ?")
        ->execute([$_SESSION['student_id']]);
    
    // Clear session stored questions for this exam
    if (isset($_SESSION['exam_questions_' . $attempt['exam_id']])) {
        unset($_SESSION['exam_questions_' . $attempt['exam_id']]);
    }
    
    // Log activity
    $action = $auto_submit ? 'auto_submit_exam' : 'submit_exam';
    logActivity('student', $_SESSION['student_id'], $action, "Submitted exam: {$attempt['exam_code']}");
    
    // Prepare response data
    $response_data = [
        'success' => true, 
        'message' => $message,
        'score' => round($score_display, 2),
        'percentage' => round($percentage, 2),
        'grade' => $grade['grade'],
        'total_correct' => $total_correct,
        'total_questions' => $total_questions,
        'wrong_count' => $wrong_count,
        'answered_count' => $answered_count,
        'auto_submitted' => $auto_submit,
        'show_result' => $attempt['show_result'] == 1
    ];
    
    // If exam allows immediate result display, include result details
    if ($attempt['show_result'] == 1) {
        $response_data['result'] = [
            'score' => round($score_display, 2),
            'percentage' => round($percentage, 2),
            'grade' => $grade['grade'],
            'remark' => $grade['remark'],
            'total_correct' => $total_correct,
            'total_wrong' => $wrong_count,
            'total_skipped' => $total_questions - $answered_count
        ];
    }
    
    echo json_encode($response_data);
    
} catch (PDOException $e) {
    error_log("Submit exam error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Submit exam general error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>