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

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36

if (!validateCSRFToken($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$attempt_id = intval($input['attempt_id'] ?? 0);
$auto_submit = !empty($input['auto_submit']);
$reason = strtolower(trim((string) ($input['reason'] ?? '')));
if (!in_array($reason, ['manual', 'timeout', 'tab_switch'], true)) {
    $reason = $auto_submit ? 'timeout' : 'manual';
}

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
    
    $overall_score = examOverallScore($attempt['overall_score'] ?? 100);
    $computed = calculateScaledExamScore(
        (int) $total_correct,
        (int) $total_questions,
        (float) $total_score,
        (float) $total_questions_marks,
        $overall_score
    );
    $score_display = $computed['score'];
    $total_marks_display = $computed['total_marks'];
    $percentage = $computed['percentage'];
    
    $grade = getGrade($percentage);
    if ($reason === 'timeout') {
        $attempt_status = 'timed_out';
        $submission_method = 'auto';
        $message = 'Time expired. Your exam was submitted automatically.';
        $action = 'timeout_submit_exam';
    } elseif ($reason === 'tab_switch') {
        $attempt_status = 'auto_submitted';
        $submission_method = 'auto';
        $message = 'Exam terminated because of repeated tab switching.';
        $action = 'tab_switch_submit_exam';
    } else {
        $attempt_status = 'submitted';
        $submission_method = 'manual';
        $message = 'Exam submitted successfully!';
        $action = 'submit_exam';
        $reason = 'manual';
    }

    $updateStmt = $db->prepare("
        UPDATE exam_attempts 
        SET score = ?, 
            percentage = ?, 
            grade = ?,
            correct_count = ?, 
            answered_count = ?,
            end_time = NOW(),
            status = ?,
            submission_method = ?
        WHERE id = ?
    ");
    $updateStmt->execute([$score_display, $percentage, $grade['grade'], $total_correct, $answered_count, $attempt_status, $submission_method, $attempt_id]);
    
    // Check if result already exists
    $checkResultStmt = $db->prepare("
        SELECT id FROM results WHERE exam_id = ? AND student_id = ?
    ");
    $checkResultStmt->execute([$attempt['exam_id'], $_SESSION['student_id']]);
    $existingResult = $checkResultStmt->fetch();
    
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
        
    }
    
    if (in_array($reason, ['timeout', 'tab_switch'], true)) {
        try {
            attempt_archive_save($db, $attempt_id, $reason);
        } catch (Throwable $e) {
            error_log('Attempt archive save failed: ' . $e->getMessage());
        }
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
    logActivity('student', $_SESSION['student_id'], $action, "Submitted exam ({$reason}): {$attempt['exam_code']}");
    
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
        'auto_submitted' => $reason !== 'manual',
        'reason' => $reason,
        'status' => $attempt_status,
        'submission_method' => $submission_method,
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
    
    if (function_exists('sync_request_immediate')) {
        sync_request_immediate();
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