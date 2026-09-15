<?php
/**
 * CBT System - Submit Composite Exam
 * 
 * FIX: Uses the attempt's locked question_ids to determine
 *      exactly which sub-questions were presented to the student.
 * 
 * NEW: Deletes the student's answers after successful submission
 *      to maximize database space. Results are preserved in 
 *      composite_exam_results.
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if (!isStudentLoggedIn()) jsonResponse(false, 'Unauthorized');

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) jsonResponse(false, 'Invalid token');

$attemptId = intval($input['attempt_id'] ?? 0);
$autoSubmit = !empty($input['auto_submit']);

if (!$attemptId) jsonResponse(false, 'Invalid attempt');

try {
    $db = getDB();
    
    // ============================================================
    // 1. Load the attempt
    // ============================================================
    $stmt = $db->prepare("
        SELECT a.*, e.pass_mark, e.exam_code, e.exam_title, e.show_result, 
               e.id AS exam_id, e.question_limit
        FROM composite_exam_attempts a
        JOIN composite_exams e ON a.composite_exam_id = e.id
        WHERE a.id = ? AND a.student_id = ?
    ");
    $stmt->execute([$attemptId, $_SESSION['student_id']]);
    $attempt = $stmt->fetch();
    
    if (!$attempt) jsonResponse(false, 'Attempt not found');
    if ($attempt['status'] !== 'in_progress') jsonResponse(false, 'Already submitted');
    
    // ============================================================
    // 2. Determine the presented main question IDs
    // ============================================================
    $lockedMainQuestionIds = [];
    
    if (!empty($attempt['question_ids'])) {
        $decoded = json_decode($attempt['question_ids'], true);
        if (is_array($decoded) && !empty($decoded)) {
            $lockedMainQuestionIds = array_map('intval', $decoded);
        }
    }
    
    // Fallback for legacy attempts (pre-locking)
    if (empty($lockedMainQuestionIds)) {
        error_log("WARNING: Attempt #$attemptId has no locked question_ids — falling back");
        
        $fallbackStmt = $db->prepare("
            SELECT id FROM composite_questions 
            WHERE composite_exam_id = ? AND status = 1 
            ORDER BY question_order, id 
            LIMIT ?
        ");
        $fallbackStmt->execute([$attempt['composite_exam_id'], intval($attempt['question_limit'] ?: 100)]);
        $lockedMainQuestionIds = array_map('intval', $fallbackStmt->fetchAll(PDO::FETCH_COLUMN));
    }
    
    if (empty($lockedMainQuestionIds)) {
        jsonResponse(false, 'No questions found for this attempt');
    }
    
    // ============================================================
    // 3. Get all sub-questions under those main questions
    // ============================================================
    $mainQPlaceholders = implode(',', array_fill(0, count($lockedMainQuestionIds), '?'));
    
    $subQStmt = $db->prepare("
        SELECT 
            sq.id AS sub_question_id,
            sq.marks,
            sq.correct_answer,
            sq.composite_question_id
        FROM composite_sub_questions sq
        WHERE sq.composite_question_id IN ($mainQPlaceholders)
    ");
    $subQStmt->execute($lockedMainQuestionIds);
    $subQuestionsData = $subQStmt->fetchAll();
    
    // Build lookup map
    $subQuestionMap = [];
    $totalMarksAvailable = 0;
    $totalSubQuestionsActual = 0;
    
    foreach ($subQuestionsData as $sq) {
        $subQuestionMap[$sq['sub_question_id']] = [
            'marks' => floatval($sq['marks']),
            'correct_answer' => $sq['correct_answer']
        ];
        $totalMarksAvailable += floatval($sq['marks']);
        $totalSubQuestionsActual++;
    }
    
    if ($totalSubQuestionsActual === 0) {
        jsonResponse(false, 'No sub-questions found for the presented questions');
    }
    
    // ============================================================
    // 4. Get student's answers (before deleting them)
    // ============================================================
    $answersStmt = $db->prepare("
        SELECT * FROM composite_exam_answers 
        WHERE attempt_id = ?
    ");
    $answersStmt->execute([$attemptId]);
    $answers = $answersStmt->fetchAll();
    
    $answeredCount = 0;
    $correctCount = 0;
    $wrongCount = 0;
    $totalScore = 0;
    
    // ============================================================
    // 5. Recalculate correctness using server-side truth
    // ============================================================
    foreach ($answers as $a) {
        $subId = intval($a['composite_sub_question_id']);
        
        // Only count answers for sub-questions that were actually presented
        if (!isset($subQuestionMap[$subId])) {
            continue;
        }
        
        $answeredCount++;
        $marksForThisQ = $subQuestionMap[$subId]['marks'];
        $correctAnswer = $subQuestionMap[$subId]['correct_answer'];
        $selectedAnswer = trim($a['selected_answer'] ?? '');
        
        if ($selectedAnswer === $correctAnswer) {
            $correctCount++;
            $totalScore += $marksForThisQ;
        } else {
            $wrongCount++;
        }
    }
    
    // ============================================================
    // 6. Correct totals
    // ============================================================
    $skippedCount = $totalSubQuestionsActual - $answeredCount;
    
    $percentage = $totalMarksAvailable > 0 
        ? ($totalScore / $totalMarksAvailable) * 100 
        : 0;
    
    $grade = getGrade($percentage);
    
    // ============================================================
    // 7. Debug logging
    // ============================================================
    error_log("=== COMPOSITE SUBMIT (FIXED) ===");
    error_log("Attempt ID: $attemptId");
    error_log("Exam ID: {$attempt['composite_exam_id']}");
    error_log("Locked Main Questions: " . json_encode($lockedMainQuestionIds));
    error_log("Presented Sub-Questions: $totalSubQuestionsActual");
    error_log("Total Marks Available: $totalMarksAvailable");
    error_log("Answered: $answeredCount");
    error_log("Correct: $correctCount");
    error_log("Wrong: $wrongCount");
    error_log("Skipped: $skippedCount");
    error_log("Score Earned: $totalScore");
    error_log("Percentage: " . round($percentage, 2) . "%");
    error_log("Grade: {$grade['grade']}");
    
    // ============================================================
    // 8. Save everything in a transaction
    // ============================================================
    $db->beginTransaction();
    
    // Update attempt
    $db->prepare("
        UPDATE composite_exam_attempts SET 
            score = ?, 
            percentage = ?, 
            correct_count = ?, 
            answered_count = ?,
            total_questions = ?,
            total_sub_questions = ?,
            end_time = NOW(), 
            status = 'submitted', 
            submission_method = ?
        WHERE id = ?
    ")->execute([
        $totalScore,
        $percentage,
        $correctCount,
        $answeredCount,
        count($lockedMainQuestionIds),
        $totalSubQuestionsActual,
        $autoSubmit ? 'auto' : 'manual',
        $attemptId
    ]);
    
    // Upsert result
    $checkResult = $db->prepare("
        SELECT id FROM composite_exam_results 
        WHERE composite_exam_id = ? AND student_id = ?
    ");
    $checkResult->execute([$attempt['composite_exam_id'], $_SESSION['student_id']]);
    $existing = $checkResult->fetch();
    
    if ($existing) {
        $db->prepare("
            UPDATE composite_exam_results SET 
                attempt_id = ?, 
                total_questions = ?, 
                total_sub_questions = ?, 
                answered = ?,
                correct = ?, 
                wrong = ?, 
                score = ?, 
                total_marks = ?, 
                percentage = ?,
                grade = ?, 
                remark = ?, 
                published = 0
            WHERE composite_exam_id = ? AND student_id = ?
        ")->execute([
            $attemptId,
            count($lockedMainQuestionIds),
            $totalSubQuestionsActual,
            $answeredCount,
            $correctCount,
            $wrongCount,
            $totalScore,
            $totalMarksAvailable,
            $percentage,
            $grade['grade'],
            $grade['remark'],
            $attempt['composite_exam_id'],
            $_SESSION['student_id']
        ]);
    } else {
        $db->prepare("
            INSERT INTO composite_exam_results 
                (composite_exam_id, student_id, attempt_id, total_questions, total_sub_questions,
                 answered, correct, wrong, score, total_marks, percentage, grade, remark, published)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
        ")->execute([
            $attempt['composite_exam_id'],
            $_SESSION['student_id'],
            $attemptId,
            count($lockedMainQuestionIds),
            $totalSubQuestionsActual,
            $answeredCount,
            $correctCount,
            $wrongCount,
            $totalScore,
            $totalMarksAvailable,
            $percentage,
            $grade['grade'],
            $grade['remark']
        ]);
    }
    
    // Update assignment
    $db->prepare("
        UPDATE composite_exam_students 
        SET status = 'submitted' 
        WHERE composite_exam_id = ? AND student_id = ?
    ")->execute([$attempt['composite_exam_id'], $_SESSION['student_id']]);
    
    // ============================================================
    // 9. DELETE STUDENT'S ANSWERS TO FREE DATABASE SPACE
    //    (Results are preserved in composite_exam_results)
    // ============================================================
    $deleteAnswersStmt = $db->prepare("
        DELETE FROM composite_exam_answers 
        WHERE attempt_id = ?
    ");
    $deleteAnswersStmt->execute([$attemptId]);
    $deletedAnswerCount = $deleteAnswersStmt->rowCount();
    
    error_log("Deleted $deletedAnswerCount answer rows for attempt #$attemptId (space cleanup)");
    
    // ============================================================
    // 10. Commit
    // ============================================================
    $db->commit();
    
    logActivity(
        'student', 
        $_SESSION['student_id'], 
        $autoSubmit ? 'auto_submit_composite' : 'submit_composite',
        "Submitted composite exam: {$attempt['exam_code']} — Score: $totalScore/$totalMarksAvailable ($percentage%) — $deletedAnswerCount answer rows cleaned"
    );
    
    // ============================================================
    // 11. Response
    // ============================================================
    jsonResponse(true, $autoSubmit ? 'Exam auto-submitted' : 'Exam submitted successfully', [
        'score' => round($totalScore, 2),
        'total_marks' => round($totalMarksAvailable, 2),
        'percentage' => round($percentage, 2),
        'grade' => $grade['grade'],
        'remark' => $grade['remark'],
        'correct' => $correctCount,
        'wrong' => $wrongCount,
        'skipped' => $skippedCount,
        'answered' => $answeredCount,
        'total' => $totalSubQuestionsActual,
        'total_main_questions' => count($lockedMainQuestionIds),
        'answers_cleaned' => $deletedAnswerCount,
        'show_result' => $attempt['show_result'] == 1
    ]);
    
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log("Composite submit error: " . $e->getMessage());
    jsonResponse(false, 'Database error: ' . $e->getMessage());
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log("Composite submit general error: " . $e->getMessage());
    jsonResponse(false, 'Error: ' . $e->getMessage());
}