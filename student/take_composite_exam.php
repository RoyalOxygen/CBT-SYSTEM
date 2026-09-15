<?php
/**
 * CBT System - Take Composite Exam
 * Fully self-contained with flexible multi-column sub-question layout
 * 
 * FIX: Random question selection is now locked per attempt.
 *      Students see the same questions on reload/navigation.
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/student_auth.php';

$db = getDB();
$studentId = $_SESSION['student_id'];

$examId = intval($_GET['exam_id'] ?? 0);
if (!$examId) redirect(APP_URL . '/student/dashboard.php', 'Invalid exam', 'error');

// =====================================================
// GET STUDENT
// =====================================================
$stmt = $db->prepare("SELECT s.*, d.dept_name, l.level_name FROM students s 
    LEFT JOIN departments d ON s.department_id = d.id 
    LEFT JOIN levels l ON s.level_id = l.id WHERE s.id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

// =====================================================
// CHECK ASSIGNMENT
// =====================================================
$checkStmt = $db->prepare("SELECT * FROM composite_exam_students WHERE composite_exam_id = ? AND student_id = ? AND status IN ('assigned', 'started')");
$checkStmt->execute([$examId, $studentId]);
$assignment = $checkStmt->fetch();

if (!$assignment) redirect(APP_URL . '/student/dashboard.php', 'Not assigned to this exam', 'error');

// =====================================================
// GET EXAM
// =====================================================
$examStmt = $db->prepare("SELECT * FROM composite_exams WHERE id = ? AND status IN ('published', 'running')");
$examStmt->execute([$examId]);
$exam = $examStmt->fetch();

if (!$exam) redirect(APP_URL . '/student/dashboard.php', 'Exam not available', 'error');

// =====================================================
// GET OR CREATE ATTEMPT (must be done BEFORE question selection)
// =====================================================
$attemptStmt = $db->prepare("SELECT * FROM composite_exam_attempts WHERE composite_exam_id = ? AND student_id = ?");
$attemptStmt->execute([$examId, $studentId]);
$attempt = $attemptStmt->fetch();

$isNewAttempt = false;

if (!$attempt) {
    // First time - create a placeholder attempt
    // We'll update total_questions after we pick the questions
    $createStmt = $db->prepare("INSERT INTO composite_exam_attempts 
        (composite_exam_id, student_id, start_time, total_questions, total_sub_questions, original_duration, status) 
        VALUES (?, ?, NOW(), 0, 0, ?, 'in_progress')");
    $createStmt->execute([$examId, $studentId, $exam['duration']]);
    $attemptId = $db->lastInsertId();
    
    $db->prepare("UPDATE composite_exam_students SET status = 'started' WHERE composite_exam_id = ? AND student_id = ?")
        ->execute([$examId, $studentId]);
    
    $attemptStmt->execute([$examId, $studentId]);
    $attempt = $attemptStmt->fetch();
    $isNewAttempt = true;
} elseif ($attempt['status'] !== 'in_progress') {
    redirect(APP_URL . '/student/composite_results.php?submitted=' . $examId, 'You have already taken this exam');
}

$attemptId = $attempt['id'];

// =====================================================
// DETERMINE WHICH QUESTIONS TO SHOW
// =====================================================
// If new attempt OR no locked question_ids yet:
//    → Randomly pick questions from pool and LOCK them
// If existing attempt with locked question_ids:
//    → Load those exact questions in that exact order
// =====================================================

$lockedQuestionIds = null;

// Check if this attempt already has locked question IDs
if (!empty($attempt['question_ids'])) {
    $decoded = json_decode($attempt['question_ids'], true);
    if (is_array($decoded) && !empty($decoded)) {
        $lockedQuestionIds = $decoded;
        error_log("Composite attempt #$attemptId: reusing locked question IDs: " . $attempt['question_ids']);
    }
}

if ($lockedQuestionIds === null) {
    // First time: pick a random set of questions from the pool
    // Pool = all active questions for this exam
    $poolStmt = $db->prepare("SELECT id FROM composite_questions 
        WHERE composite_exam_id = ? AND status = 1");
    $poolStmt->execute([$examId]);
    $pool = $poolStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($pool)) {
        die("<div class='container mt-5'><div class='alert alert-danger'>No questions found for this composite exam. Please contact the administrator.</div></div>");
    }
    
    // Shuffle the pool, then take up to question_limit
    shuffle($pool);
    $limit = max(1, intval($exam['question_limit']));
    $lockedQuestionIds = array_slice($pool, 0, $limit);
    
    // Save the locked list to the attempt
    $updateStmt = $db->prepare("UPDATE composite_exam_attempts SET question_ids = ? WHERE id = ?");
    $updateStmt->execute([json_encode($lockedQuestionIds), $attemptId]);
    
    error_log("Composite attempt #$attemptId: selected new random question IDs: " . json_encode($lockedQuestionIds));
}

// =====================================================
// LOAD THE LOCKED QUESTIONS (in that exact order)
// =====================================================
$placeholders = implode(',', array_fill(0, count($lockedQuestionIds), '?'));
$questionsStmt = $db->prepare("SELECT * FROM composite_questions 
    WHERE id IN ($placeholders) AND status = 1");
$questionsStmt->execute($lockedQuestionIds);
$fetchedQuestions = $questionsStmt->fetchAll();

// Re-order results to match the locked ID order
$questionMap = [];
foreach ($fetchedQuestions as $q) {
    $questionMap[$q['id']] = $q;
}
$questions = [];
foreach ($lockedQuestionIds as $qid) {
    if (isset($questionMap[$qid])) {
        $questions[] = $questionMap[$qid];
    }
}

if (empty($questions)) {
    die("<div class='container mt-5'><div class='alert alert-danger'>No questions found for this composite exam. Please contact the administrator.</div></div>");
}

// =====================================================
// BUILD SUB-QUESTIONS AND OPTIONS
// =====================================================
$enrichedQuestions = [];
foreach ($questions as $q) {
    $subStmt = $db->prepare("SELECT * FROM composite_sub_questions 
        WHERE composite_question_id = ? ORDER BY sub_order, id");
    $subStmt->execute([$q['id']]);
    $subQuestions = $subStmt->fetchAll();
    
    if ($exam['shuffle_sub_questions']) shuffle($subQuestions);
    
    $enrichedSubs = [];
    foreach ($subQuestions as $sq) {
        $optStmt = $db->prepare("SELECT * FROM composite_sub_options 
            WHERE composite_sub_question_id = ? ORDER BY option_order, id");
        $optStmt->execute([$sq['id']]);
        $sq['options'] = $optStmt->fetchAll();
        $enrichedSubs[] = $sq;
    }
    
    $q['sub_questions'] = $enrichedSubs;
    $enrichedQuestions[] = $q;
}

$questions = $enrichedQuestions;

// =====================================================
// CALCULATE TOTAL SUB-QUESTIONS
// =====================================================
$totalSubQuestions = 0;
foreach ($questions as $q) {
    $totalSubQuestions += count($q['sub_questions']);
}

// =====================================================
// UPDATE ATTEMPT TOTALS (only if we just picked them)
// =====================================================
if ($isNewAttempt || intval($attempt['total_questions']) === 0) {
    $updateTotalsStmt = $db->prepare("UPDATE composite_exam_attempts 
        SET total_questions = ?, total_sub_questions = ? 
        WHERE id = ?");
    $updateTotalsStmt->execute([count($questions), $totalSubQuestions, $attemptId]);
    
    // Refresh attempt data
    $attemptStmt->execute([$examId, $studentId]);
    $attempt = $attemptStmt->fetch();
}

// =====================================================
// CALCULATE TIME REMAINING
// =====================================================
$totalDurationMinutes = intval($exam['duration']) + intval($attempt['time_extension_minutes'] ?? 0);
$totalDurationSeconds = $totalDurationMinutes * 60;

$elapsedStmt = $db->prepare("SELECT TIMESTAMPDIFF(SECOND, start_time, NOW()) AS elapsed FROM composite_exam_attempts WHERE id = ?");
$elapsedStmt->execute([$attemptId]);
$elapsedSeconds = (int) $elapsedStmt->fetchColumn();
$timeRemainingSeconds = max(0, $totalDurationSeconds - $elapsedSeconds);

if ($timeRemainingSeconds <= 0) {
    $db->prepare("UPDATE composite_exam_attempts SET status = 'timed_out', end_time = NOW() WHERE id = ?")->execute([$attemptId]);
    redirect(APP_URL . '/student/composite_results.php?submitted=' . $examId, 'Time expired. Your exam has been auto-submitted.');
}

// =====================================================
// GET ALREADY ANSWERED SUB-QUESTIONS
// =====================================================
$answersStmt = $db->prepare("SELECT composite_sub_question_id, selected_answer FROM composite_exam_answers WHERE attempt_id = ?");
$answersStmt->execute([$attemptId]);
$answeredQuestions = [];
while ($row = $answersStmt->fetch()) {
    $answeredQuestions[$row['composite_sub_question_id']] = $row['selected_answer'];
}

$answeredCount = count($answeredQuestions);

// =====================================================
// SESSION VARS
// =====================================================
if (!isset($_SESSION['student_name']) && $student) {
    $_SESSION['student_name'] = $student['last_name'] . ' ' . $student['first_name'];
}
if (!isset($_SESSION['matric_number']) && $student) {
    $_SESSION['matric_number'] = $student['matric_number'];
}

// =====================================================
// JSON ENCODING
// =====================================================
$questionsJson = json_encode($questions);
if ($questionsJson === false) {
    error_log("JSON encode error for composite questions: " . json_last_error_msg());
    $questionsJson = '[]';
}

$answeredJson = json_encode($answeredQuestions);
if ($answeredJson === false) {
    $answeredJson = '{}';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
<title><?php echo e($exam['exam_code']); ?> - <?php echo e($exam['exam_title']); ?></title>
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/font/bootstrap-icons.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { 
    background: #f0f2f5; 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    min-height: 100vh;
    padding: 0;
}

.merged-header {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    color: white;
    padding: 12px 0;
    border-bottom: 4px solid #e8c84c;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
}
.merged-header .container-fluid { max-width: 1600px; padding: 0 20px; }
.merged-header .uni-brand { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.merged-header .uni-brand i { font-size: 1.6rem; color: #e8c84c; }
.merged-header .uni-brand h1 { font-weight: 700; font-size: 1.1rem; margin: 0; letter-spacing: 0.5px; line-height: 1.2; }
.merged-header .uni-brand small { display: block; font-size: 0.65rem; opacity: 0.75; font-weight: 300; }
.merged-header .exam-info { display: flex; align-items: center; gap: 15px; flex: 1; justify-content: center; flex-wrap: wrap; }
.merged-header .exam-info .badge-pill {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.15);
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}
.merged-header .exam-info .badge-pill i { color: #e8c84c; margin-right: 5px; }
.merged-header .timer-area { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.merged-header .timer-area .timer-label { font-size: 0.7rem; opacity: 0.75; text-transform: uppercase; letter-spacing: 0.5px; text-align: right; line-height: 1.2; }
.merged-header .timer-area .timer-label strong { display: block; font-size: 0.8rem; opacity: 1; }

.circular-timer { position: relative; width: 58px; height: 58px; display: flex; align-items: center; justify-content: center; }
.circular-timer svg { transform: rotate(-90deg); width: 58px; height: 58px; }
.circular-timer .circle-bg { fill: none; stroke: rgba(255,255,255,0.15); stroke-width: 5; }
.circular-timer .circle-progress { fill: none; stroke: #34a853; stroke-width: 5; stroke-linecap: round; transition: stroke-dashoffset 1s linear, stroke 0.5s ease; }
.circular-timer .circle-progress.warning { stroke: #ff6b35; }
.circular-timer .circle-progress.danger { stroke: #ff4444; animation: pulse-stroke 0.5s infinite; }
@keyframes pulse-stroke { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
.circular-timer .timer-text { position: absolute; font-size: 0.78rem; font-weight: 700; font-family: 'Courier New', monospace; color: white; transition: color 0.5s ease; }
.circular-timer .timer-text.warning { color: #ff6b35; }
.circular-timer .timer-text.danger { color: #ff4444; animation: pulse-text 0.5s infinite; }
@keyframes pulse-text { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

.force-submit-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.92); backdrop-filter: blur(12px); z-index: 99999; display: none; align-items: center; justify-content: center; }
.force-submit-overlay.show { display: flex; }
.force-submit-overlay .modal-box { background: white; border-radius: 24px; width: 500px; max-width: 92vw; padding: 50px 40px; text-align: center; animation: slideUp 0.5s ease-out; }
@keyframes slideUp { from { opacity: 0; transform: translateY(40px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
.force-submit-overlay .icon-timeout { font-size: 64px; color: #dc3545; margin-bottom: 16px; }
.force-submit-overlay h3 { color: #1a1a2e; font-weight: 700; margin-bottom: 8px; }
.force-submit-overlay p { color: #6c757d; margin-bottom: 20px; }
.force-submit-overlay .progress { height: 8px; border-radius: 10px; overflow: hidden; background: #e9ecef; }
.force-submit-overlay .progress-bar { background: linear-gradient(90deg, #dc3545, #ff6b35); transition: width 0.3s ease; }
.force-submit-overlay .status-text { margin-top: 12px; font-size: 0.85rem; color: #6c757d; }

.main-container { max-width: 1600px; margin: 0 auto; padding: 12px 20px 20px; display: flex; gap: 15px; }
.content-col { flex: 1; min-width: 0; }

.question-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 20px 25px; margin-bottom: 12px; }
.question-number { font-size: 0.8rem; color: #6c757d; font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; }
.question-text { font-size: 1.05rem; font-weight: 500; color: #1a1a2e; margin-bottom: 16px; line-height: 1.55; white-space: pre-wrap; padding: 12px 15px; background: #f8f9fa; border-left: 3px solid #1a73e8; border-radius: 5px; }
.question-image { max-width: 100%; max-height: 200px; border-radius: 8px; margin-bottom: 15px; display: none; }

.sub-questions-grid {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.sub-questions-grid.cols-1 { grid-template-columns: 1fr; }
.sub-questions-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.sub-questions-grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.sub-questions-grid.cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

.sub-question-card { 
    background: #fafbfc; 
    border-radius: 10px; 
    border: 1px solid #e9ecef; 
    padding: 12px 14px; 
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.sub-question-card:hover { border-color: #1a73e8; background: #f8f9fc; }
.sub-question-card.full-width { grid-column: 1 / -1; }

.sub-question-header { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 10px; }
.sub-question-label { background: #1a73e8; color: white; padding: 3px 9px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; flex-shrink: 0; }
.sub-question-text { font-weight: 500; color: #1a1a2e; flex: 1; font-size: 0.88rem; line-height: 1.45; word-wrap: break-word; overflow-wrap: break-word; }

.options-list { display: flex; flex-direction: column; gap: 6px; flex: 1; }
.option-row { 
    display: flex; 
    align-items: center; 
    gap: 8px; 
    padding: 7px 10px; 
    background: white; 
    border: 1px solid #e9ecef; 
    border-radius: 8px; 
    cursor: pointer; 
    transition: all 0.2s; 
    font-size: 0.85rem;
    min-width: 0;
}
.option-row:hover { background: #e9ecef; border-color: #c0c4c8; }
.option-row.selected { background: #e8f0fe; border-color: #1a73e8; }
.option-row input[type="radio"] { width: 16px; height: 16px; accent-color: #1a73e8; cursor: pointer; flex-shrink: 0; }
.option-row .option-label { font-weight: 600; color: #1a1a2e; margin-right: 2px; flex-shrink: 0; }
.option-row .option-text { color: #333; word-wrap: break-word; overflow-wrap: break-word; min-width: 0; }

.sub-questions-grid.cols-3 .sub-question-card,
.sub-questions-grid.cols-4 .sub-question-card { padding: 10px 12px; }
.sub-questions-grid.cols-3 .sub-question-text,
.sub-questions-grid.cols-4 .sub-question-text { font-size: 0.82rem; }
.sub-questions-grid.cols-3 .option-row,
.sub-questions-grid.cols-4 .option-row { padding: 6px 8px; font-size: 0.8rem; gap: 6px; }
.sub-questions-grid.cols-3 .sub-question-label,
.sub-questions-grid.cols-4 .sub-question-label { padding: 2px 7px; font-size: 0.7rem; }

.nav-buttons { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e9ecef; }
.btn-nav { padding: 8px 24px; border-radius: 25px; font-size: 0.85rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; background: #f1f3f4; color: #5f6368; }
.btn-nav:hover:not(:disabled) { background: #e8eaed; }
.btn-nav:disabled { opacity: 0.4; cursor: not-allowed; }
.btn-nav-primary { background: #1a73e8; color: white; }
.btn-nav-primary:hover:not(:disabled) { background: #1557b0; }

.palette-section { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 16px 20px; }
.palette-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.palette-title { font-size: 0.8rem; font-weight: 600; color: #5f6368; }
.palette-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(50px, 1fr)); gap: 6px; max-width: 100%; }
.pal-btn { padding: 5px 4px; border: 1px solid #dadce0; background: white; border-radius: 6px; cursor: pointer; font-size: 0.75rem; font-weight: 600; transition: all 0.2s; text-align: center; min-height: 38px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.pal-btn:hover { border-color: #1a73e8; transform: scale(1.05); }
.pal-btn.answered { background: #34a853; border-color: #34a853; color: white; }
.pal-btn.current { background: #1a73e8; border-color: #1a73e8; color: white; box-shadow: 0 0 0 2px rgba(26,115,232,0.3); }
.pal-btn.partial { background: #fbbc04; border-color: #fbbc04; color: #1a1a2e; }
.pal-btn .sub-count { display: block; font-size: 0.6rem; opacity: 0.85; margin-top: 1px; font-weight: 500; }
.palette-legend { display: flex; justify-content: center; gap: 18px; margin-top: 10px; font-size: 0.7rem; color: #5f6368; flex-wrap: wrap; }
.legend-item { display: flex; align-items: center; gap: 5px; }
.legend-dot { width: 11px; height: 11px; border-radius: 3px; }
.legend-dot.answered { background: #34a853; }
.legend-dot.partial { background: #fbbc04; }
.legend-dot.current { background: #1a73e8; }
.legend-dot.unanswered { background: white; border: 1px solid #dadce0; }

.sidebar-col { width: 260px; flex-shrink: 0; }
.student-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 16px; text-align: center; margin-bottom: 12px; }
.student-photo { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; margin: 0 auto 8px; border: 3px solid #e8c84c; }
.student-photo-placeholder { width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; border: 3px solid #e8c84c; }
.student-photo-placeholder i { font-size: 34px; color: white; }
.student-name { font-weight: 700; font-size: 0.9rem; color: #1a1a2e; margin-bottom: 2px; }
.student-matric { font-size: 0.75rem; color: #6c757d; font-weight: 600; }
.student-level { font-size: 0.75rem; color: #1a73e8; font-weight: 600; margin-top: 3px; }
.student-dept { font-size: 0.7rem; color: #5f6368; }
.student-divider { border: none; border-top: 1px solid #e9ecef; margin: 10px 0; }
.student-stats { display: flex; justify-content: space-around; }
.stat-item { text-align: center; }
.stat-value { font-size: 1.1rem; font-weight: 700; color: #1a1a2e; }
.stat-label { font-size: 0.6rem; color: #6c757d; }

.progress-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 12px 16px; margin-bottom: 12px; }
.progress-title { font-size: 0.7rem; font-weight: 600; color: #5f6368; text-transform: uppercase; letter-spacing: 0.5px; }
.progress-number { font-size: 1.4rem; font-weight: 700; color: #1a73e8; }
.progress-bar-bg { background: #e9ecef; border-radius: 20px; height: 6px; overflow: hidden; margin-top: 6px; }
.progress-bar-fill { background: linear-gradient(90deg, #1a73e8, #0d47a1); height: 100%; border-radius: 20px; transition: width 0.3s; }

.btn-submit { background: #dc3545; color: white; border: none; padding: 11px; border-radius: 30px; font-weight: 700; font-size: 0.85rem; width: 100%; cursor: pointer; transition: all 0.2s; }
.btn-submit:hover { background: #c82333; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(220,53,69,0.3); }
.btn-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; }

.auto-save-indicator { text-align: center; font-size: 0.68rem; color: #6c757d; padding: 6px 0; }

.auto-submit-warning { background: #ff6b35; color: white; padding: 8px 16px; border-radius: 8px; text-align: center; font-weight: 600; font-size: 0.85rem; margin-bottom: 12px; display: none; }
.auto-submit-warning.critical { background: #dc3545; }

.modal-backdrop-custom { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1050; display: flex; align-items: center; justify-content: center; }
.modal-backdrop-custom.hidden { display: none; }
.modal-box { background: white; border-radius: 16px; width: 420px; max-width: 90vw; padding: 30px; }

@media (max-width: 1200px) {
    .merged-header .exam-info .badge-pill.hide-md { display: none; }
    .sub-questions-grid.cols-4 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 992px) {
    .main-container { flex-direction: column; }
    .sidebar-col { width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .sidebar-col > * { margin-bottom: 0; }
    .merged-header .exam-info { display: none; }
    .sub-questions-grid.cols-3,
    .sub-questions-grid.cols-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .merged-header { padding: 8px 0; }
    .merged-header .uni-brand h1 { font-size: 0.9rem; }
    .merged-header .uni-brand i { font-size: 1.3rem; }
    .main-container { padding: 10px; }
    .question-card { padding: 16px; }
    .sidebar-col { grid-template-columns: 1fr; }
    .palette-grid { grid-template-columns: repeat(auto-fill, minmax(40px, 1fr)); }
    .option-row { padding: 8px 12px; }
    .question-text { font-size: 0.95rem; }
    .circular-timer { width: 50px; height: 50px; }
    .circular-timer svg { width: 50px; height: 50px; }
    .circular-timer .timer-text { font-size: 0.7rem; }
    .merged-header .timer-area .timer-label { display: none; }
    .sub-questions-grid,
    .sub-questions-grid.cols-1,
    .sub-questions-grid.cols-2,
    .sub-questions-grid.cols-3,
    .sub-questions-grid.cols-4 {
        grid-template-columns: 1fr;
    }
}
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; display: inline-block; }
</style>
</head>
<body>

<!-- MERGED HEADER + TIMER -->
<div class="merged-header">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <div class="uni-brand">
                <i class="bi bi-mortarboard-fill"></i>
                <div>
                    <h1>OXYGEN CBT SYSTEM</h1>
                    <small>Composite Examination Portal</small>
                </div>
            </div>
            <div class="exam-info">
                <span class="badge-pill">
                    <i class="bi bi-diagram-3"></i>
                    <?php echo strtoupper(e($exam['exam_code'])); ?>
                </span>
                <span class="badge-pill hide-md">
                    <i class="bi bi-journal-text"></i>
                    <?php echo e(substr($exam['exam_title'], 0, 30)) . (strlen($exam['exam_title']) > 30 ? '...' : ''); ?>
                </span>
                <span class="badge-pill hide-md">
                    <i class="bi bi-person-badge"></i>
                    <?php echo e($student['matric_number']); ?>
                </span>
                <span class="badge-pill hide-md">
                    <i class="bi bi-layers"></i>
                    <?php echo strtoupper(e($student['level_name'] ?? 'N/A')); ?>
                </span>
            </div>
            <div class="timer-area">
                <div class="timer-label">
                    <span>Time Left</span>
                    <strong><?php echo $totalDurationMinutes; ?> min total</strong>
                </div>
                <div class="circular-timer" id="circularTimer">
                    <svg viewBox="0 0 58 58">
                        <circle class="circle-bg" cx="29" cy="29" r="25" />
                        <circle class="circle-progress" id="circleProgress" cx="29" cy="29" r="25" stroke-dasharray="157.08" stroke-dashoffset="0" />
                    </svg>
                    <span class="timer-text" id="timerText">--:--</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Force Submit Overlay -->
<div id="forceSubmitOverlay" class="force-submit-overlay">
    <div class="modal-box">
        <div class="icon-timeout"><i class="bi bi-clock-fill"></i></div>
        <h3>⏰ Time's Up!</h3>
        <p>Your exam time has expired. Your answers are being submitted automatically.</p>
        <div class="progress">
            <div class="progress-bar" id="forceSubmitProgress" style="width: 0%;"></div>
        </div>
        <div class="status-text" id="forceSubmitStatus">Preparing submission...</div>
        <div class="mt-3">
            <div class="spinner-border spinner-border-sm text-danger me-2" id="forceSubmitSpinner"></div>
            <span class="text-muted small">Please wait, do not refresh the page.</span>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-container" id="mainContainer">
    <div class="content-col">
        <div id="autoSubmitWarning" class="auto-submit-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span id="warningMessage">Time is running out!</span>
        </div>

        <div class="question-card" id="mainQuestionCard">
            <div class="question-number">
                <span>Main Question <span id="mainQNum">1</span> of <?php echo count($questions); ?></span>
                <span class="badge bg-primary" id="subQCount">0 sub-questions</span>
            </div>
            <div class="question-text" id="mainQuestionText">
                <div style="text-align: center; padding: 20px; color: #6c757d;">
                    <i class="bi bi-arrow-repeat spin d-block" style="font-size: 1.5rem;"></i>
                    Loading question...
                </div>
            </div>
            <img id="mainQImage" class="question-image" src="" alt="">
            <div id="subQuestionsContainer"></div>

            <div class="nav-buttons">
                <button class="btn-nav" id="prevBtn" onclick="prevMainQuestion()" disabled>
                    <i class="bi bi-chevron-left me-1"></i> Previous
                </button>
                <button class="btn-nav btn-nav-primary" id="nextBtn" onclick="nextMainQuestion()">
                    Next <i class="bi bi-chevron-right ms-1"></i>
                </button>
            </div>
        </div>

        <div class="palette-section">
            <div class="palette-header">
                <span class="palette-title">📋 Question Navigator</span>
                <span class="text-muted small" id="answeredCountDisplay"><?php echo $answeredCount; ?> / <?php echo $totalSubQuestions; ?> answered</span>
            </div>
            <div class="palette-grid" id="questionPalette"></div>
            <div class="palette-legend">
                <span class="legend-item"><span class="legend-dot answered"></span> Complete</span>
                <span class="legend-item"><span class="legend-dot partial"></span> Partial</span>
                <span class="legend-item"><span class="legend-dot current"></span> Current</span>
                <span class="legend-item"><span class="legend-dot unanswered"></span> Unanswered</span>
            </div>
        </div>

        <div class="auto-save-indicator" id="saveStatus">✓ Auto-save enabled</div>
    </div>

    <div class="sidebar-col">
        <div class="student-card">
            <?php if ($student['photo']): ?>
                <img src="<?php echo APP_URL; ?>/assets/uploads/student_photos/<?php echo e($student['photo']); ?>" class="student-photo">
            <?php else: ?>
                <div class="student-photo-placeholder"><i class="bi bi-person-fill"></i></div>
            <?php endif; ?>
            <div class="student-name"><?php echo strtoupper(e($student['last_name'] . ' ' . $student['first_name'])); ?></div>
            <div class="student-matric"><?php echo e($student['matric_number']); ?></div>
            <div class="student-level">LEVEL: <?php echo strtoupper(e($student['level_name'] ?? 'N/A')); ?></div>
            <div class="student-dept"><?php echo e($student['dept_name'] ?? 'N/A'); ?></div>
            <hr class="student-divider">
            <div class="student-stats">
                <div class="stat-item">
                    <div class="stat-value" id="answerCount"><?php echo $answeredCount; ?></div>
                    <div class="stat-label">Answered</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="unansweredCount"><?php echo $totalSubQuestions - $answeredCount; ?></div>
                    <div class="stat-label">Unanswered</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $totalSubQuestions; ?></div>
                    <div class="stat-label">Total Sub-Q</div>
                </div>
            </div>
        </div>

        <div class="progress-card">
            <div class="d-flex justify-content-between align-items-center">
                <span class="progress-title">Progress</span>
                <span class="progress-number" id="progressPercent"><?php echo round(($answeredCount / max(1, $totalSubQuestions)) * 100); ?>%</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" id="progressFill" style="width: <?php echo ($answeredCount / max(1, $totalSubQuestions)) * 100; ?>%"></div>
            </div>
        </div>

        <button class="btn-submit" id="submitBtn" onclick="confirmSubmit()">
            <i class="bi bi-check2-circle me-2"></i> Submit Exam
        </button>
    </div>
</div>

<!-- Submit Modal -->
<div class="modal-backdrop-custom hidden" id="submitModal">
    <div class="modal-box" id="submitModalBox">
        <div id="submitModalContent">
            <h5 class="mb-3">⚠️ Submit Exam</h5>
            <p>Are you sure you want to submit your exam?</p>
            <div class="bg-light p-3 rounded-3 my-3">
                <strong>Summary</strong><br>
                Total Main Questions: <strong><?php echo count($questions); ?></strong><br>
                Total Sub-Questions: <strong><?php echo $totalSubQuestions; ?></strong><br>
                Answered: <strong id="sumAnswered"><?php echo $answeredCount; ?></strong><br>
                Unanswered: <strong id="sumUnanswered"><?php echo $totalSubQuestions - $answeredCount; ?></strong>
            </div>
            <div class="text-danger small mb-3">⚠️ You cannot change answers after submission.</div>
            <div class="d-flex gap-2 justify-content-end">
                <button onclick="closeSubmitModal()" class="btn btn-secondary px-4" id="cancelSubmitBtn">Cancel</button>
                <button onclick="submitExam()" class="btn btn-danger px-4" id="confirmSubmitBtn">Submit</button>
            </div>
        </div>
    </div>
</div>

<script>
// =====================================================
// EXAM DATA
// =====================================================
const mainQuestions = <?php echo $questionsJson; ?>;
const answeredQuestions = <?php echo $answeredJson; ?>;
const attemptId = <?php echo $attemptId; ?>;
const examId = <?php echo $examId; ?>;
const totalDurationSeconds = <?php echo $totalDurationSeconds; ?>;
let timeRemaining = <?php echo $timeRemainingSeconds; ?>;
const APP_URL = '<?php echo APP_URL; ?>';
const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';
const CSRF_TOKEN_NAME = '<?php echo CSRF_TOKEN_NAME; ?>';
const TOTAL_SUB_QUESTIONS = <?php echo $totalSubQuestions; ?>;

// =====================================================
// STATE
// =====================================================
let currentMainIndex = 0;
let timerInterval = null;
let autoSaveInterval = null;
let autoSubmitTriggered = false;
let isSubmitting = false;
let forceSubmitInProgress = false;
let examPageInProgress = true;

const CIRCUMFERENCE = 157.08;

// Debug logs
console.log('=== COMPOSITE EXAM INITIALIZED ===');
console.log('Main Questions:', mainQuestions.length);
console.log('Total Sub Questions:', TOTAL_SUB_QUESTIONS);
console.log('Answered:', Object.keys(answeredQuestions).length);
console.log('Time Remaining (sec):', timeRemaining);

// =====================================================
// INITIALIZATION
// =====================================================
function initExam() {
    if (!mainQuestions || mainQuestions.length === 0) {
        document.getElementById('mainQuestionText').innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No questions found for this composite exam. Please contact the administrator.
            </div>
        `;
        return;
    }
    
    try {
        renderPalette();
        loadMainQuestion(0);
        startTimer();
        setupKeyboardShortcuts();
        
        if (timeRemaining <= 60) {
            showWarningBanner('🔴 Time is running out!', true);
        }
        
        console.log('Exam initialized successfully');
    } catch (error) {
        console.error('Init error:', error);
        document.getElementById('mainQuestionText').innerHTML = `
            <div class="alert alert-danger">Error initializing exam: ${error.message}</div>
        `;
    }
}

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        if (isSubmitting || autoSubmitTriggered || e.target.tagName === 'INPUT') return;
        const key = e.key.toUpperCase();
        switch(key) {
            case 'N': e.preventDefault(); if (currentMainIndex < mainQuestions.length - 1) nextMainQuestion(); break;
            case 'P': e.preventDefault(); if (currentMainIndex > 0) prevMainQuestion(); break;
            case 'S': e.preventDefault(); confirmSubmit(); break;
        }
    });
}

// =====================================================
// TIMER
// =====================================================
function startTimer() {
    updateTimerDisplay();
    timerInterval = setInterval(() => {
        if (timeRemaining > 0 && !autoSubmitTriggered && !isSubmitting && !forceSubmitInProgress) {
            timeRemaining--;
            updateTimerDisplay();
            
            if (timeRemaining === 300) showWarningBanner('⚠️ 5 minutes remaining!', false);
            else if (timeRemaining === 180) showWarningBanner('🔴 3 minutes remaining!', true);
            else if (timeRemaining === 60) showWarningBanner('🔴 LAST MINUTE!', true);
            else if (timeRemaining === 30) showWarningBanner('🔴 30 SECONDS REMAINING!', true);
            else if (timeRemaining === 10) showWarningBanner('🔴 FINAL 10 SECONDS!', true);
            
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                forceAutoSubmit();
            }
        }
    }, 1000);
}

function showWarningBanner(message, critical) {
    const banner = document.getElementById('autoSubmitWarning');
    const msg = document.getElementById('warningMessage');
    if (banner && msg) {
        msg.innerHTML = message;
        banner.style.display = 'block';
        if (critical) banner.classList.add('critical');
        if (!critical) setTimeout(() => banner.style.display = 'none', 5000);
    }
}

function updateTimerDisplay() {
    const mins = Math.floor(timeRemaining / 60);
    const secs = timeRemaining % 60;
    const timerText = document.getElementById('timerText');
    const circleProgress = document.getElementById('circleProgress');
    
    if (timerText) {
        timerText.textContent = `${String(mins).padStart(2,'0')}:${String(secs).padStart(2,'0')}`;
        timerText.classList.remove('warning', 'danger');
        if (timeRemaining < 180) timerText.classList.add('danger');
        else if (timeRemaining < 300) timerText.classList.add('warning');
    }
    
    if (circleProgress && totalDurationSeconds > 0) {
        const progress = Math.max(0, timeRemaining / totalDurationSeconds);
        circleProgress.style.strokeDashoffset = CIRCUMFERENCE * (1 - progress);
        circleProgress.classList.remove('warning', 'danger');
        if (timeRemaining < 180) circleProgress.classList.add('danger');
        else if (timeRemaining < 300) circleProgress.classList.add('warning');
    }
}

// =====================================================
// QUESTION LOADING (with smart column detection)
// =====================================================
function loadMainQuestion(index) {
    if (index < 0 || index >= mainQuestions.length) return;
    currentMainIndex = index;
    const q = mainQuestions[index];
    
    document.getElementById('mainQNum').textContent = index + 1;
    document.getElementById('mainQuestionText').textContent = q.question_text || 'No question text';
    
    const img = document.getElementById('mainQImage');
    if (q.question_image && q.question_image !== '') {
        img.src = APP_URL + '/assets/uploads/question_images/' + q.question_image;
        img.style.display = 'block';
    } else {
        img.style.display = 'none';
    }
    
    const subQuestions = q.sub_questions || [];
    document.getElementById('subQCount').textContent = subQuestions.length + ' sub-questions';
    
    const container = document.getElementById('subQuestionsContainer');
    let html = '';
    
    if (subQuestions.length === 0) {
        html = `<div class="alert alert-warning">No sub-questions found.</div>`;
    } else {
        // SMART COLUMN DETECTION
        const avgLength = subQuestions.reduce((sum, sq) => 
            sum + (sq.sub_question_text || '').length, 0) / subQuestions.length;
        
        let totalOptLen = 0, optCount = 0;
        subQuestions.forEach(sq => {
            (sq.options || []).forEach(opt => {
                totalOptLen += (opt.option_text || '').length;
                optCount++;
            });
        });
        const avgOptionLength = optCount > 0 ? totalOptLen / optCount : 0;
        const contentWeight = Math.max(avgLength, avgOptionLength);
        
        let columnCount = 2;
        if (contentWeight < 30) columnCount = 4;
        else if (contentWeight < 60) columnCount = 3;
        else if (contentWeight < 150) columnCount = 2;
        else columnCount = 1;
        
        columnCount = Math.min(columnCount, subQuestions.length);
        columnCount = Math.max(columnCount, 1);
        
        console.log(`Sub-questions: ${subQuestions.length}, Avg text: ${avgLength.toFixed(0)}, Avg option: ${avgOptionLength.toFixed(0)} → ${columnCount} columns`);
        
        html += `<div class="sub-questions-grid cols-${columnCount}">`;
        
        subQuestions.forEach(sq => {
            const selected = answeredQuestions[sq.id] || '';
            const options = sq.options || [];
            const textLength = (sq.sub_question_text || '').length;
            
            const cardClass = textLength > 180 ? 'sub-question-card full-width' : 'sub-question-card';
            
            html += `
                <div class="${cardClass}">
                    <div class="sub-question-header">
                        <span class="sub-question-label">${escapeHtml(sq.sub_question_label)}</span>
                        <span class="sub-question-text">${escapeHtml(sq.sub_question_text)}</span>
                    </div>
                    <div class="options-list">
            `;
            
            if (options.length === 0) {
                html += `<div class="text-muted small">No options available</div>`;
            } else {
                options.forEach(opt => {
                    const isSelected = (selected === opt.option_label);
                    html += `
                        <div class="option-row ${isSelected ? 'selected' : ''}" 
                             onclick="selectSubOption(this, ${sq.id}, '${escapeHtml(opt.option_label)}')">
                            <input type="radio" name="subq_${sq.id}" value="${escapeHtml(opt.option_label)}" ${isSelected ? 'checked' : ''}>
                            <span class="option-label">${escapeHtml(opt.option_label)}.</span>
                            <span class="option-text">${escapeHtml(opt.option_text)}</span>
                        </div>
                    `;
                });
            }
            
            html += `</div></div>`;
        });
        
        html += `</div>`;
    }
    
    container.innerHTML = html;
    
    document.getElementById('prevBtn').disabled = (index === 0);
    const nextBtn = document.getElementById('nextBtn');
    if (index === mainQuestions.length - 1) {
        nextBtn.innerHTML = 'Submit <i class="bi bi-check2-circle ms-1"></i>';
        nextBtn.onclick = confirmSubmit;
    } else {
        nextBtn.innerHTML = 'Next <i class="bi bi-chevron-right ms-1"></i>';
        nextBtn.onclick = nextMainQuestion;
    }
    
    updatePalette();
}

function selectSubOption(element, subQuestionId, optionLabel) {
    if (isSubmitting || autoSubmitTriggered) return;
    
    answeredQuestions[subQuestionId] = optionLabel;
    
    const parent = element.parentElement;
    parent.querySelectorAll('.option-row').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    const radio = element.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
    
    saveAnswer(subQuestionId, optionLabel);
    updatePalette();
    updateProgress();
}

function saveAnswer(subQuestionId, answer) {
    const saveEl = document.getElementById('saveStatus');
    if (saveEl) saveEl.innerHTML = '<span class="spin">↻</span> Saving...';
    
    fetch(APP_URL + '/ajax/student/save_composite_answer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({
            attempt_id: attemptId,
            composite_sub_question_id: subQuestionId,
            answer: answer
        })
    })
    .then(r => r.json())
    .then(data => {
        if (saveEl) saveEl.innerHTML = data.success ? '✓ Saved' : '⚠️ Failed';
        if (data.success) setTimeout(() => { if (saveEl) saveEl.innerHTML = '✓ Auto-save enabled'; }, 2000);
    })
    .catch(() => { if (saveEl) saveEl.innerHTML = '⚠️ Failed'; });
}

// =====================================================
// PALETTE
// =====================================================
function renderPalette() {
    const wrap = document.getElementById('questionPalette');
    if (!wrap) return;
    let html = '';
    
    mainQuestions.forEach((q, i) => {
        const subs = q.sub_questions || [];
        const answeredSubs = subs.filter(sq => answeredQuestions[sq.id]).length;
        const totalSubs = subs.length;
        let cls = '';
        if (answeredSubs === totalSubs && totalSubs > 0) cls = 'answered';
        else if (answeredSubs > 0) cls = 'partial';
        
        html += `<button class="pal-btn ${cls}" id="pal${i}" onclick="loadMainQuestion(${i})">
            Q${i + 1}
            <span class="sub-count">${answeredSubs}/${totalSubs}</span>
        </button>`;
    });
    wrap.innerHTML = html;
}

function updatePalette() {
    mainQuestions.forEach((q, i) => {
        const btn = document.getElementById('pal' + i);
        if (!btn) return;
        const subs = q.sub_questions || [];
        const answeredSubs = subs.filter(sq => answeredQuestions[sq.id]).length;
        const totalSubs = subs.length;
        
        btn.className = 'pal-btn';
        if (i === currentMainIndex) btn.classList.add('current');
        if (answeredSubs === totalSubs && totalSubs > 0) btn.classList.add('answered');
        else if (answeredSubs > 0) btn.classList.add('partial');
    });
}

function updateProgress() {
    const total = Object.keys(answeredQuestions).length;
    const unanswered = TOTAL_SUB_QUESTIONS - total;
    const percentage = TOTAL_SUB_QUESTIONS > 0 ? (total / TOTAL_SUB_QUESTIONS) * 100 : 0;
    
    const answerCount = document.getElementById('answerCount');
    const unansweredCount = document.getElementById('unansweredCount');
    const progressFill = document.getElementById('progressFill');
    const progressPercent = document.getElementById('progressPercent');
    const answeredDisplay = document.getElementById('answeredCountDisplay');
    const sumAnswered = document.getElementById('sumAnswered');
    const sumUnanswered = document.getElementById('sumUnanswered');
    
    if (answerCount) answerCount.textContent = total;
    if (unansweredCount) unansweredCount.textContent = unanswered;
    if (progressFill) progressFill.style.width = percentage + '%';
    if (progressPercent) progressPercent.textContent = Math.round(percentage) + '%';
    if (answeredDisplay) answeredDisplay.textContent = total + ' / ' + TOTAL_SUB_QUESTIONS + ' answered';
    if (sumAnswered) sumAnswered.textContent = total;
    if (sumUnanswered) sumUnanswered.textContent = unanswered;
}

// =====================================================
// NAVIGATION
// =====================================================
function prevMainQuestion() {
    if (currentMainIndex > 0) loadMainQuestion(currentMainIndex - 1);
}

function nextMainQuestion() {
    if (currentMainIndex < mainQuestions.length - 1) loadMainQuestion(currentMainIndex + 1);
}

// =====================================================
// SUBMIT
// =====================================================
function confirmSubmit() {
    if (isSubmitting || autoSubmitTriggered) return;
    updateProgress();
    const modal = document.getElementById('submitModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.getElementById('submitModalContent').style.display = 'block';
        document.getElementById('cancelSubmitBtn').disabled = false;
        document.getElementById('confirmSubmitBtn').disabled = false;
        const existingSpinner = document.getElementById('submitSpinner');
        if (existingSpinner) existingSpinner.remove();
    }
}

function closeSubmitModal() {
    const modal = document.getElementById('submitModal');
    if (modal) modal.classList.add('hidden');
}

async function submitExam() {
    if (autoSubmitTriggered || isSubmitting || forceSubmitInProgress) return;
    isSubmitting = true;
    examPageInProgress = false;
    clearInterval(timerInterval);
    clearInterval(autoSaveInterval);
    
    document.getElementById('cancelSubmitBtn').disabled = true;
    document.getElementById('confirmSubmitBtn').disabled = true;
    
    const modalBox = document.getElementById('submitModalBox');
    const modalContent = document.getElementById('submitModalContent');
    if (modalContent) modalContent.style.display = 'none';
    
    const existingSpinner = document.getElementById('submitSpinner');
    if (existingSpinner) existingSpinner.remove();
    
    if (modalBox) {
        const spinner = document.createElement('div');
        spinner.id = 'submitSpinner';
        spinner.className = 'text-center';
        spinner.innerHTML = `
            <div class="spin" style="font-size: 32px; margin: 10px 0;">↻</div>
            <p class="mt-2">Submitting your exam...</p>
            <div class="progress mt-3" style="height: 6px;">
                <div class="progress-bar bg-primary" id="submitProgress" style="width: 0%;"></div>
            </div>
        `;
        modalBox.appendChild(spinner);
    }
    
    try {
        const response = await fetch(APP_URL + '/ajax/student/submit_composite_exam.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ attempt_id: attemptId, auto_submit: false })
        });
        const data = await response.json();
        
        if (data.success) {
            setTimeout(() => {
                window.location.href = APP_URL + '/student/composite_results.php?submitted=' + examId;
            }, 800);
        } else {
            alert(data.message || 'Submission failed');
            const spinner = document.getElementById('submitSpinner');
            if (spinner) spinner.remove();
            if (modalContent) modalContent.style.display = 'block';
            closeSubmitModal();
            isSubmitting = false;
            examPageInProgress = true;
        }
    } catch (error) {
        alert('Submission failed: ' + error.message);
        const spinner = document.getElementById('submitSpinner');
        if (spinner) spinner.remove();
        if (modalContent) modalContent.style.display = 'block';
        closeSubmitModal();
        isSubmitting = false;
        examPageInProgress = true;
    }
}

async function forceAutoSubmit() {
    if (autoSubmitTriggered || forceSubmitInProgress) return;
    autoSubmitTriggered = true;
    forceSubmitInProgress = true;
    isSubmitting = true;
    examPageInProgress = false;
    
    clearInterval(timerInterval);
    clearInterval(autoSaveInterval);
    
    document.getElementById('mainContainer').style.opacity = '0.5';
    document.getElementById('submitBtn').disabled = true;
    
    const overlay = document.getElementById('forceSubmitOverlay');
    if (overlay) overlay.classList.add('show');
    
    const progressBar = document.getElementById('forceSubmitProgress');
    const statusText = document.getElementById('forceSubmitStatus');
    const spinner = document.getElementById('forceSubmitSpinner');
    
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 6 + 2;
        if (progress > 95) progress = 95;
        if (progressBar) progressBar.style.width = progress + '%';
        
        const statuses = ['⏳ Submitting...', '📝 Processing...', '🧮 Calculating...', '💾 Saving...'];
        const idx = Math.min(Math.floor(progress / 25), statuses.length - 1);
        if (statusText) statusText.textContent = statuses[idx];
    }, 250);
    
    try {
        const response = await fetch(APP_URL + '/ajax/student/submit_composite_exam.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ attempt_id: attemptId, auto_submit: true })
        });
        const data = await response.json();
        
        clearInterval(progressInterval);
        if (progressBar) progressBar.style.width = '100%';
        if (statusText) statusText.textContent = '✅ Exam submitted successfully!';
        if (spinner) spinner.style.display = 'none';
        
        if (data.success) {
            setTimeout(() => {
                window.location.href = APP_URL + '/student/composite_results.php?submitted=' + examId;
            }, 1500);
        } else {
            if (statusText) statusText.textContent = '⚠️ Submission completed with issues.';
            setTimeout(() => {
                window.location.href = APP_URL + '/student/dashboard.php?error=submission_issue';
            }, 3000);
        }
    } catch (error) {
        console.error('Force auto-submit error:', error);
        clearInterval(progressInterval);
        if (statusText) statusText.textContent = '❌ Submission failed.';
        setTimeout(() => {
            window.location.href = APP_URL + '/student/dashboard.php?error=submission_failed';
        }, 3000);
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', initExam);
</script>
</body>
</html>