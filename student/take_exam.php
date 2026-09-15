<?php
/**
 * CBT System - Take Exam Interface
 * With Keyboard Navigation Support & Force Auto-Submit on Timeout
 * 
 * FIX: Random question selection is now locked per attempt.
 *      Students see the same questions on reload/navigation.
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/student_auth.php';

$db = getDB();
$studentId = $_SESSION['student_id'];

$examId = intval($_GET['exam_id'] ?? 0);
$batchId = !empty($_GET['batch_id']) ? intval($_GET['batch_id']) : null;

if (!$examId) {
    redirect(APP_URL . '/student/dashboard.php', 'Invalid exam', 'error');
}

// =====================================================
// GET STUDENT INFO
// =====================================================
$stmt = $db->prepare("SELECT s.*, d.dept_name, l.level_name FROM students s 
    LEFT JOIN departments d ON s.department_id = d.id 
    LEFT JOIN levels l ON s.level_id = l.id WHERE s.id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

// =====================================================
// CHECK ASSIGNMENT
// =====================================================
$checkStmt = $db->prepare("SELECT * FROM exam_students WHERE exam_id = ? AND student_id = ? AND status IN ('assigned', 'started')");
$checkStmt->execute([$examId, $studentId]);
$assignment = $checkStmt->fetch();

if (!$assignment) {
    redirect(APP_URL . '/student/dashboard.php', 'You are not assigned to this exam', 'error');
}

// =====================================================
// GET EXAM
// =====================================================
$examStmt = $db->prepare("SELECT * FROM exams WHERE id = ? AND status IN ('published', 'running')");
$examStmt->execute([$examId]);
$exam = $examStmt->fetch();

if (!$exam) {
    redirect(APP_URL . '/student/dashboard.php', 'Exam not available', 'error');
}

// =====================================================
// GET OR CREATE ATTEMPT (must be done BEFORE question selection)
// =====================================================
$attemptStmt = $db->prepare("SELECT * FROM exam_attempts WHERE exam_id = ? AND student_id = ?");
$attemptStmt->execute([$examId, $studentId]);
$attempt = $attemptStmt->fetch();

$isNewAttempt = false;

if (!$attempt) {
    // Create a placeholder attempt first; we'll fill total_questions after picking
    $createStmt = $db->prepare("INSERT INTO exam_attempts 
        (exam_id, batch_id, student_id, start_time, total_questions, original_duration, status) 
        VALUES (?, ?, ?, NOW(), 0, ?, 'in_progress')");
    $createStmt->execute([$examId, $batchId, $studentId, $exam['duration']]);
    $attemptId = $db->lastInsertId();

    $db->prepare("UPDATE exam_students SET status = 'started' WHERE exam_id = ? AND student_id = ?")
        ->execute([$examId, $studentId]);
    $db->prepare("UPDATE students SET current_exam_id = ? WHERE id = ?")->execute([$examId, $studentId]);

    $attemptStmt->execute([$examId, $studentId]);
    $attempt = $attemptStmt->fetch();
    $isNewAttempt = true;
} elseif ($attempt['status'] !== 'in_progress') {
    redirect(APP_URL . '/student/results.php?submitted=' . $examId, 'You have already taken this exam');
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

if (!empty($attempt['question_ids'])) {
    $decoded = json_decode($attempt['question_ids'], true);
    if (is_array($decoded) && !empty($decoded)) {
        $lockedQuestionIds = $decoded;
        error_log("Attempt #$attemptId: reusing locked question IDs: " . $attempt['question_ids']);
    }
}

if ($lockedQuestionIds === null) {
    // First time: pick a random set of questions from the pool
    $poolStmt = $db->prepare("SELECT id FROM questions 
        WHERE exam_id = ? AND status = 1");
    $poolStmt->execute([$examId]);
    $pool = $poolStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($pool)) {
        die("<div class='container mt-5'><div class='alert alert-danger'>No questions found for this exam. Please contact the administrator.</div></div>");
    }
    
    // Shuffle the pool, then take up to question_limit
    shuffle($pool);
    $limit = max(1, intval($exam['question_limit']));
    $lockedQuestionIds = array_slice($pool, 0, $limit);
    
    // Save the locked list to the attempt
    $updateStmt = $db->prepare("UPDATE exam_attempts SET question_ids = ? WHERE id = ?");
    $updateStmt->execute([json_encode($lockedQuestionIds), $attemptId]);
    
    error_log("Attempt #$attemptId: selected new random question IDs: " . json_encode($lockedQuestionIds));
}

// =====================================================
// LOAD THE LOCKED QUESTIONS (in that exact order)
// =====================================================
$placeholders = implode(',', array_fill(0, count($lockedQuestionIds), '?'));
$questionsStmt = $db->prepare("SELECT * FROM questions 
    WHERE id IN ($placeholders) AND status = 1");
$questionsStmt->execute($lockedQuestionIds);
$fetchedQuestions = $questionsStmt->fetchAll();

// Re-order to match the locked ID sequence
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
    die("<div class='container mt-5'><div class='alert alert-danger'>No questions found for this exam. Please contact the administrator.</div></div>");
}

// =====================================================
// UPDATE ATTEMPT TOTAL IF JUST PICKED
// =====================================================
if ($isNewAttempt || intval($attempt['total_questions']) === 0) {
    $updateTotalsStmt = $db->prepare("UPDATE exam_attempts 
        SET total_questions = ? 
        WHERE id = ?");
    $updateTotalsStmt->execute([count($questions), $attemptId]);
    
    // Refresh attempt data
    $attemptStmt->execute([$examId, $studentId]);
    $attempt = $attemptStmt->fetch();
}

// =====================================================
// CALCULATE TIME REMAINING
// =====================================================
$totalDurationMinutes = intval($exam['duration']) + intval($attempt['time_extension_minutes'] ?? 0);
$totalDurationSeconds = $totalDurationMinutes * 60;

$elapsedStmt = $db->prepare("SELECT TIMESTAMPDIFF(SECOND, start_time, NOW()) AS elapsed FROM exam_attempts WHERE id = ?");
$elapsedStmt->execute([$attemptId]);
$elapsedSeconds = (int) $elapsedStmt->fetchColumn();
$timeRemainingSeconds = max(0, $totalDurationSeconds - $elapsedSeconds);

if ($timeRemainingSeconds <= 0) {
    $db->prepare("UPDATE exam_attempts SET status = 'timed_out', end_time = NOW() WHERE id = ?")->execute([$attemptId]);
    redirect(APP_URL . '/student/results.php?submitted=' . $examId, 'Time expired. Your exam has been auto-submitted.');
}

$timeRemainingMinutes = floor($timeRemainingSeconds / 60);
$timeRemainingSecondsDisplay = $timeRemainingSeconds % 60;

// =====================================================
// GET ALREADY ANSWERED QUESTIONS
// =====================================================
$answersStmt = $db->prepare("SELECT question_id, selected_answer FROM answers WHERE attempt_id = ?");
$answersStmt->execute([$attemptId]);
$answeredQuestions = [];
while ($row = $answersStmt->fetch()) {
    $answeredQuestions[$row['question_id']] = $row['selected_answer'];
}

$totalQuestionsCount = count($questions);
$currentAnsweredCount = count($answeredQuestions);

// =====================================================
// SESSION VARS
// =====================================================
if (!isset($_SESSION['student_name']) && $student) {
    $_SESSION['student_name'] = $student['last_name'] . ' ' . $student['first_name'];
}
if (!isset($_SESSION['matric_number']) && $student) {
    $_SESSION['matric_number'] = $student['matric_number'];
}
if (!isset($_SESSION['student_department']) && $student) {
    $_SESSION['student_department'] = $student['dept_name'] ?? 'N/A';
}

require_once __DIR__ . '/../includes/exam_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
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

/* ============================================
   MERGED HEADER + TIMER BAR
   ============================================ */
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
.merged-header .container-fluid {
    max-width: 1600px;
    padding: 0 20px;
}
.merged-header .uni-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.merged-header .uni-brand i {
    font-size: 1.6rem;
    color: #e8c84c;
}
.merged-header .uni-brand h1 {
    font-weight: 700;
    font-size: 1.1rem;
    margin: 0;
    letter-spacing: 0.5px;
    line-height: 1.2;
}
.merged-header .uni-brand small {
    display: block;
    font-size: 0.65rem;
    opacity: 0.75;
    font-weight: 300;
}
.merged-header .exam-info {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1;
    justify-content: center;
    flex-wrap: wrap;
}
.merged-header .exam-info .badge-pill {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.15);
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}
.merged-header .exam-info .badge-pill i {
    color: #e8c84c;
    margin-right: 5px;
}
.merged-header .timer-area {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
.merged-header .timer-area .timer-label {
    font-size: 0.7rem;
    opacity: 0.75;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: right;
    line-height: 1.2;
}
.merged-header .timer-area .timer-label strong {
    display: block;
    font-size: 0.8rem;
    opacity: 1;
}

/* Circular Timer in header */
.circular-timer {
    position: relative;
    width: 58px;
    height: 58px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.circular-timer svg {
    transform: rotate(-90deg);
    width: 58px;
    height: 58px;
}
.circular-timer .circle-bg {
    fill: none;
    stroke: rgba(255,255,255,0.15);
    stroke-width: 5;
}
.circular-timer .circle-progress {
    fill: none;
    stroke: #34a853;
    stroke-width: 5;
    stroke-linecap: round;
    transition: stroke-dashoffset 1s linear, stroke 0.5s ease;
}
.circular-timer .circle-progress.warning {
    stroke: #ff6b35;
}
.circular-timer .circle-progress.danger {
    stroke: #ff4444;
    animation: pulse-stroke 0.5s infinite;
}
@keyframes pulse-stroke {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}
.circular-timer .timer-text {
    position: absolute;
    font-size: 0.78rem;
    font-weight: 700;
    font-family: 'Courier New', monospace;
    color: white;
    transition: color 0.5s ease;
}
.circular-timer .timer-text.warning { color: #ff6b35; }
.circular-timer .timer-text.danger { 
    color: #ff4444; 
    animation: pulse-text 0.5s infinite;
}
@keyframes pulse-text {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Force Submit Overlay */
.force-submit-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.92);
    backdrop-filter: blur(12px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
}
.force-submit-overlay.show {
    display: flex;
}
.force-submit-overlay .modal-box {
    background: white;
    border-radius: 24px;
    width: 500px;
    max-width: 92vw;
    padding: 50px 40px;
    text-align: center;
    animation: slideUp 0.5s ease-out;
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(40px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.force-submit-overlay .icon-timeout {
    font-size: 64px;
    color: #dc3545;
    margin-bottom: 16px;
}
.force-submit-overlay h3 {
    color: #1a1a2e;
    font-weight: 700;
    margin-bottom: 8px;
}
.force-submit-overlay p {
    color: #6c757d;
    margin-bottom: 20px;
}
.force-submit-overlay .progress {
    height: 8px;
    border-radius: 10px;
    overflow: hidden;
    background: #e9ecef;
}
.force-submit-overlay .progress-bar {
    background: linear-gradient(90deg, #dc3545, #ff6b35);
    transition: width 0.3s ease;
}
.force-submit-overlay .status-text {
    margin-top: 12px;
    font-size: 0.85rem;
    color: #6c757d;
}

/* Main Layout */
.main-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 12px 20px 20px;
    display: flex;
    gap: 15px;
}

.content-col {
    flex: 1;
    min-width: 0;
}

/* Question Card */
.question-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    padding: 20px 25px;
    margin-bottom: 12px;
}
.question-number {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 600;
    margin-bottom: 8px;
}
.question-text {
    font-size: 1.05rem;
    font-weight: 500;
    color: #1a1a2e;
    margin-bottom: 16px;
    line-height: 1.55;
}
.question-image {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
    margin-bottom: 15px;
    display: none;
}

.options-list {
    display: flex;
    flex-direction: column;
    gap: 7px;
    margin: 16px 0;
}
.option-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 9px 14px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}
.option-row:hover {
    background: #e9ecef;
    border-color: #c0c4c8;
}
.option-row.selected {
    background: #e8f0fe;
    border-color: #1a73e8;
}
.option-row input[type="radio"] {
    width: 18px;
    height: 18px;
    accent-color: #1a73e8;
    cursor: pointer;
    flex-shrink: 0;
}
.option-row .option-label {
    font-weight: 600;
    color: #1a1a2e;
    margin-right: 4px;
}
.option-row .option-text {
    color: #333;
}

.nav-buttons {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e9ecef;
}
.btn-nav {
    padding: 8px 24px;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    background: #f1f3f4;
    color: #5f6368;
}
.btn-nav:hover:not(:disabled) {
    background: #e8eaed;
}
.btn-nav:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.btn-nav-primary {
    background: #1a73e8;
    color: white;
}
.btn-nav-primary:hover:not(:disabled) {
    background: #1557b0;
}

.palette-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    padding: 16px 20px;
}
.palette-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.palette-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: #5f6368;
}
.palette-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(36px, 1fr));
    gap: 5px;
    max-width: 100%;
}
.pal-btn {
    padding: 5px 0;
    border: 1px solid #dadce0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.78rem;
    font-weight: 500;
    transition: all 0.2s;
    text-align: center;
    min-height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.pal-btn:hover {
    border-color: #1a73e8;
    transform: scale(1.05);
}
.pal-btn.answered {
    background: #34a853;
    border-color: #34a853;
    color: white;
}
.pal-btn.current {
    background: #1a73e8;
    border-color: #1a73e8;
    color: white;
    box-shadow: 0 0 0 2px rgba(26,115,232,0.3);
}
.pal-btn.answered.current {
    background: #1a73e8;
    border-color: #1a73e8;
    color: white;
    box-shadow: 0 0 0 2px rgba(26,115,232,0.3);
}
.palette-legend {
    display: flex;
    justify-content: center;
    gap: 18px;
    margin-top: 10px;
    font-size: 0.7rem;
    color: #5f6368;
}
.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
}
.legend-dot {
    width: 11px;
    height: 11px;
    border-radius: 3px;
}
.legend-dot.answered { background: #34a853; }
.legend-dot.current { background: #1a73e8; }
.legend-dot.unanswered { background: white; border: 1px solid #dadce0; }

.sidebar-col {
    width: 260px;
    flex-shrink: 0;
}
.student-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    padding: 16px;
    text-align: center;
    margin-bottom: 12px;
}
.student-photo {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 8px;
    border: 3px solid #e8c84c;
}
.student-photo-placeholder {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    border: 3px solid #e8c84c;
}
.student-photo-placeholder i { font-size: 34px; color: white; }
.student-name {
    font-weight: 700;
    font-size: 0.9rem;
    color: #1a1a2e;
    margin-bottom: 2px;
}
.student-matric {
    font-size: 0.75rem;
    color: #6c757d;
    font-weight: 600;
}
.student-level {
    font-size: 0.75rem;
    color: #1a73e8;
    font-weight: 600;
    margin-top: 3px;
}
.student-dept {
    font-size: 0.7rem;
    color: #5f6368;
}
.student-divider {
    border: none;
    border-top: 1px solid #e9ecef;
    margin: 10px 0;
}
.student-stats {
    display: flex;
    justify-content: space-around;
}
.stat-item {
    text-align: center;
}
.stat-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a1a2e;
}
.stat-label {
    font-size: 0.6rem;
    color: #6c757d;
}

.progress-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    padding: 12px 16px;
    margin-bottom: 12px;
}
.progress-title {
    font-size: 0.7rem;
    font-weight: 600;
    color: #5f6368;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.progress-number {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1a73e8;
}
.progress-bar-bg {
    background: #e9ecef;
    border-radius: 20px;
    height: 6px;
    overflow: hidden;
    margin-top: 6px;
}
.progress-bar-fill {
    background: linear-gradient(90deg, #1a73e8, #0d47a1);
    height: 100%;
    border-radius: 20px;
    transition: width 0.3s;
}

.btn-submit {
    background: #dc3545;
    color: white;
    border: none;
    padding: 11px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 0.85rem;
    width: 100%;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-submit:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220,53,69,0.3);
}
.btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

.auto-save-indicator {
    text-align: center;
    font-size: 0.68rem;
    color: #6c757d;
    padding: 6px 0;
}

.auto-submit-warning {
    background: #ff6b35;
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 12px;
    display: none;
}

.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-backdrop-custom.hidden { display: none; }
.modal-box {
    background: white;
    border-radius: 16px;
    width: 420px;
    max-width: 90vw;
    padding: 30px;
}

@media (max-width: 1200px) {
    .merged-header .exam-info .badge-pill.hide-md { display: none; }
}
@media (max-width: 992px) {
    .main-container { flex-direction: column; }
    .sidebar-col { width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .sidebar-col > * { margin-bottom: 0; }
    .merged-header .exam-info { display: none; }
}
@media (max-width: 768px) {
    .merged-header { padding: 8px 0; }
    .merged-header .uni-brand h1 { font-size: 0.9rem; }
    .merged-header .uni-brand i { font-size: 1.3rem; }
    .main-container { padding: 10px; }
    .question-card { padding: 16px; }
    .sidebar-col { grid-template-columns: 1fr; }
    .palette-grid { grid-template-columns: repeat(auto-fill, minmax(30px, 1fr)); }
    .option-row { padding: 8px 12px; }
    .question-text { font-size: 0.95rem; }
    .circular-timer { width: 50px; height: 50px; }
    .circular-timer svg { width: 50px; height: 50px; }
    .circular-timer .timer-text { font-size: 0.7rem; }
    .merged-header .timer-area .timer-label { display: none; }
}

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; display: inline-block; }
</style>
</head>
<body>

<!-- MERGED HEADER + TIMER BAR -->
<div class="merged-header">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between gap-3">
            
            <div class="uni-brand">
                <i class="bi bi-mortarboard-fill"></i>
                <div>
                    <h1>OXYGEN CBT SYSTEM</h1>
                    <small>Examination Portal</small>
                </div>
            </div>
            
            <div class="exam-info">
                <span class="badge-pill">
                    <i class="bi bi-journal-bookmark-fill"></i>
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
                        <circle class="circle-progress" id="circleProgress" cx="29" cy="29" r="25" 
                            stroke-dasharray="157.08" stroke-dashoffset="0" />
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
            <span id="warningMessage">Time is running out! Your exam will auto-submit when time reaches zero.</span>
        </div>

        <div class="question-card">
            <div class="question-number" id="questionNumber">Question <span id="qNumDisplay">1</span> of <?php echo $totalQuestionsCount; ?></div>
            <div class="question-text" id="questionText">Loading...</div>
            <img id="qImage" class="question-image" src="" alt="">
            <div class="options-list" id="optionsList"></div>
            
            <div class="nav-buttons">
                <button class="btn-nav" id="prevBtn" onclick="prevQuestion()" disabled>
                    <i class="bi bi-chevron-left me-1"></i> Previous
                </button>
                <button class="btn-nav btn-nav-primary" id="nextBtn" onclick="nextQuestion()">
                    Next <i class="bi bi-chevron-right ms-1"></i>
                </button>
            </div>
        </div>

        <div class="palette-section">
            <div class="palette-header">
                <span class="palette-title">📋 Question Navigator</span>
                <span class="text-muted small" id="answeredCountDisplay"><?php echo $currentAnsweredCount; ?> / <?php echo $totalQuestionsCount; ?> answered</span>
            </div>
            <div class="palette-grid" id="questionPalette"></div>
            <div class="palette-legend">
                <span class="legend-item"><span class="legend-dot answered"></span> Answered</span>
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
                <div class="student-photo-placeholder">
                    <i class="bi bi-person-fill"></i>
                </div>
            <?php endif; ?>
            <div class="student-name"><?php echo strtoupper(e($student['last_name'] . ' ' . $student['first_name'])); ?></div>
            <div class="student-matric"><?php echo e($student['matric_number']); ?></div>
            <div class="student-level">LEVEL: <?php echo strtoupper(e($student['level_name'] ?? 'N/A')); ?></div>
            <div class="student-dept"><?php echo e($student['dept_name'] ?? 'N/A'); ?></div>
            
            <hr class="student-divider">
            
            <div class="student-stats">
                <div class="stat-item">
                    <div class="stat-value" id="answerCount"><?php echo $currentAnsweredCount; ?></div>
                    <div class="stat-label">Answered</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="unansweredCount"><?php echo $totalQuestionsCount - $currentAnsweredCount; ?></div>
                    <div class="stat-label">Unanswered</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $totalQuestionsCount; ?></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
        </div>

        <div class="progress-card">
            <div class="d-flex justify-content-between align-items-center">
                <span class="progress-title">Progress</span>
                <span class="progress-number" id="progressPercent"><?php echo round(($currentAnsweredCount / max(1, $totalQuestionsCount)) * 100); ?>%</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" id="progressFill" style="width: <?php echo ($currentAnsweredCount / max(1, $totalQuestionsCount)) * 100; ?>%"></div>
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
                Total: <strong><?php echo $totalQuestionsCount; ?></strong><br>
                Answered: <strong id="sumAnswered"><?php echo $currentAnsweredCount; ?></strong><br>
                Unanswered: <strong id="sumUnanswered"><?php echo $totalQuestionsCount - $currentAnsweredCount; ?></strong>
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
// Exam data
const questions = <?php echo json_encode($questions); ?>;
const answeredQuestions = <?php echo json_encode($answeredQuestions); ?>;
const attemptId = <?php echo $attemptId; ?>;
const examId = <?php echo $examId; ?>;

// Total duration in SECONDS
const totalDurationMinutes = <?php echo $totalDurationMinutes; ?>;
const totalDurationSeconds = totalDurationMinutes * 60;

// Time remaining in SECONDS
let timeRemaining = <?php echo $timeRemainingSeconds; ?>;

let currentIndex = 0;
let timerInterval = null;
let autoSaveInterval = null;
let autoSubmitTriggered = false;
let isSubmitting = false;
let forceSubmitInProgress = false;

const CIRCUMFERENCE = 157.08;

function initExam() { 
    console.log('Initializing exam...');
    console.log('Questions loaded:', questions.length);
    
    if (!questions || questions.length === 0) {
        console.error('No questions available!');
        alert('Error: No questions found. Please contact your administrator.');
        return;
    }
    
    renderPalette(); 
    loadQuestion(0); 
    startTimer(); 
    startAutoSave(); 
    setupKeyboardShortcuts(); 
    updateBeforeUnload(true);
    
    if (timeRemaining <= 60) {
        showWarningBanner('🔴 Time is running out! Your exam will auto-submit when time reaches zero.', 'critical');
    }
    
    console.log('Exam initialized successfully');
}

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        if (isSubmitting || autoSubmitTriggered || e.target.tagName === 'INPUT') return;
        
        const key = e.key.toUpperCase();
        switch(key) {
            case 'N': e.preventDefault(); if (currentIndex < questions.length - 1) { nextQuestion(); } break;
            case 'P': e.preventDefault(); if (currentIndex > 0) { prevQuestion(); } break;
            case 'A': e.preventDefault(); selectOptionByKey('A'); break;
            case 'B': e.preventDefault(); selectOptionByKey('B'); break;
            case 'C': e.preventDefault(); selectOptionByKey('C'); break;
            case 'D': e.preventDefault(); selectOptionByKey('D'); break;
            case 'S': e.preventDefault(); confirmSubmit(); break;
        }
    });
}

function selectOptionByKey(key) {
    const q = questions[currentIndex];
    let optionExists = false;
    if (key === 'A' && q.option_a) optionExists = true;
    else if (key === 'B' && q.option_b) optionExists = true;
    else if (key === 'C' && q.option_c) optionExists = true;
    else if (key === 'D' && q.option_d) optionExists = true;
    if (!optionExists) return;
    
    const options = document.querySelectorAll('.option-row');
    for (let i = 0; i < options.length; i++) {
        if (options[i].textContent.trim().startsWith(key + '.')) {
            options[i].click();
            break;
        }
    }
}

function startTimer() { 
    updateTimerDisplay(); 
    timerInterval = setInterval(() => { 
        if (timeRemaining > 0 && !autoSubmitTriggered && !isSubmitting && !forceSubmitInProgress) { 
            timeRemaining--; 
            updateTimerDisplay(); 
            
            if (timeRemaining === 300) {
                showWarningBanner('⚠️ 5 minutes remaining!', 'warning');
            } else if (timeRemaining === 180) {
                showWarningBanner('🔴 3 minutes remaining!', 'critical');
            } else if (timeRemaining === 60) {
                showWarningBanner('🔴 LAST MINUTE!', 'critical');
            } else if (timeRemaining === 30) {
                showWarningBanner('🔴 30 SECONDS REMAINING!', 'critical');
            } else if (timeRemaining === 10) {
                showWarningBanner('🔴 FINAL 10 SECONDS!', 'critical');
            }
            
            if (timeRemaining <= 0) { 
                clearInterval(timerInterval); 
                forceAutoSubmit(); 
            } 
        } 
    }, 1000); 
}

function showWarningBanner(message, type) {
    const banner = document.getElementById('autoSubmitWarning');
    const msgSpan = document.getElementById('warningMessage');
    if (banner && msgSpan) {
        msgSpan.innerHTML = message;
        banner.style.display = 'block';
        if (type === 'critical') {
            banner.style.background = '#dc3545';
        }
        if (type !== 'critical') {
            setTimeout(() => {
                if (banner) banner.style.display = 'none';
            }, 5000);
        }
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
        if (timeRemaining < 180) {
            timerText.classList.add('danger');
        } else if (timeRemaining < 300) {
            timerText.classList.add('warning');
        }
    }
    
    if (circleProgress) {
        const progress = Math.max(0, timeRemaining / totalDurationSeconds);
        const offset = CIRCUMFERENCE * (1 - progress);
        circleProgress.style.strokeDashoffset = offset;
        
        circleProgress.classList.remove('warning', 'danger');
        if (timeRemaining < 180) {
            circleProgress.classList.add('danger');
        } else if (timeRemaining < 300) {
            circleProgress.classList.add('warning');
        }
    }
}

function loadQuestion(index) { 
    if (autoSubmitTriggered || isSubmitting) return;
    if (!questions.length || index >= questions.length || index < 0) return;
    
    currentIndex = index; 
    const q = questions[index];
    
    document.getElementById('questionNumber').innerHTML = `Question <span id="qNumDisplay">${index + 1}</span> of ${questions.length}`;
    document.getElementById('questionText').textContent = q.question_text;
    
    const img = document.getElementById('qImage');
    if (q.question_image && q.question_image !== '') { 
        img.src = APP_URL + '/assets/uploads/question_images/' + q.question_image; 
        img.style.display = 'block'; 
    } else { 
        img.style.display = 'none'; 
    }
    
    let opts = [{ key: 'A', text: q.option_a }, { key: 'B', text: q.option_b }];
    if (q.option_c && q.option_c !== '') opts.push({ key: 'C', text: q.option_c });
    if (q.option_d && q.option_d !== '') opts.push({ key: 'D', text: q.option_d });
    
    const savedAnswer = answeredQuestions[q.id];
    const listContainer = document.getElementById('optionsList');
    if (listContainer) {
        let optionsHtml = '';
        for (let i = 0; i < opts.length; i++) {
            const o = opts[i];
            const isSelected = (savedAnswer === o.key);
            optionsHtml += `<div class="option-row ${isSelected ? 'selected' : ''}" onclick="selectOption(this, '${o.key}')">`;
            optionsHtml += `<input type="radio" name="answer_${q.id}" value="${o.key}" ${isSelected ? 'checked' : ''}>`;
            optionsHtml += `<span class="option-label">${o.key}.</span>`;
            optionsHtml += `<span class="option-text">${escapeHtml(o.text)}</span>`;
            optionsHtml += `</div>`;
        }
        listContainer.innerHTML = optionsHtml;
    }
    
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    if (prevBtn) prevBtn.disabled = (index === 0);
    
    if (nextBtn) {
        if (index === questions.length - 1) { 
            nextBtn.innerHTML = 'Submit <i class="bi bi-check2-circle ms-1"></i>'; 
            nextBtn.onclick = function() { confirmSubmit(); }; 
            nextBtn.className = 'btn-nav btn-nav-primary';
        } else { 
            nextBtn.innerHTML = 'Next <i class="bi bi-chevron-right ms-1"></i>'; 
            nextBtn.onclick = function() { nextQuestion(); }; 
            nextBtn.className = 'btn-nav btn-nav-primary';
        }
    }
    
    updatePalette(); 
    updateProgress(); 
}

function selectOption(element, key) {
    if (isSubmitting || autoSubmitTriggered) return;
    const q = questions[currentIndex];
    if (answeredQuestions[q.id] === key) return;
    answeredQuestions[q.id] = key;
    
    const radio = element.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
    
    const container = document.getElementById('optionsList');
    if (container) {
        const options = container.querySelectorAll('.option-row');
        options.forEach(opt => opt.classList.remove('selected'));
    }
    element.classList.add('selected');
    
    saveAnswer(q.id, key);
    updatePalette(); 
    updateProgress();
}

function saveAnswer(qid, answer) {
    const saveEl = document.getElementById('saveStatus');
    if(!saveEl) return;
    saveEl.innerHTML = '<span class="spin">↻</span> Saving...';
    
    fetch(APP_URL + '/ajax/student/save_answer.php', { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, 
        body: JSON.stringify({ attempt_id: attemptId, question_id: qid, answer: answer }) 
    })
    .then(r => r.json())
    .then(data => { 
        saveEl.innerHTML = data.success ? '✓ Saved' : '⚠️ Save failed'; 
        if (data.success) {
            setTimeout(() => { 
                if (saveEl.innerHTML === '✓ Saved') saveEl.innerHTML = '✓ Auto-save enabled'; 
            }, 2000); 
        }
    })
    .catch(() => { saveEl.innerHTML = '⚠️ Save failed'; });
}

function prevQuestion() { 
    if(!autoSubmitTriggered && !isSubmitting && currentIndex > 0) loadQuestion(currentIndex - 1); 
}

function nextQuestion() { 
    if(!autoSubmitTriggered && !isSubmitting && currentIndex < questions.length - 1) loadQuestion(currentIndex + 1); 
}

function renderPalette() { 
    const wrap = document.getElementById('questionPalette'); 
    if(!wrap) return; 
    let html = ''; 
    for (let i = 0; i < questions.length; i++) {
        const isAnswered = answeredQuestions[questions[i].id] ? 'answered' : '';
        html += `<button class="pal-btn ${isAnswered}" id="pal${i}" onclick="loadQuestion(${i})">${i + 1}</button>`;
    }
    wrap.innerHTML = html; 
}

function updatePalette() { 
    for (let i = 0; i < questions.length; i++) { 
        const btn = document.getElementById('pal' + i); 
        if(!btn) continue; 
        btn.className = 'pal-btn'; 
        if(i === currentIndex) btn.classList.add('current'); 
        else if(answeredQuestions[questions[i].id]) btn.classList.add('answered'); 
    } 
}

function updateProgress() { 
    const answered = Object.keys(answeredQuestions).length; 
    const unanswered = questions.length - answered;
    const percentage = (answered / questions.length) * 100;
    
    const answerCount = document.getElementById('answerCount');
    const unansweredCount = document.getElementById('unansweredCount');
    const progressFill = document.getElementById('progressFill');
    const progressPercent = document.getElementById('progressPercent');
    const answeredDisplay = document.getElementById('answeredCountDisplay');
    const sumAnswered = document.getElementById('sumAnswered');
    const sumUnanswered = document.getElementById('sumUnanswered');
    
    if(answerCount) answerCount.textContent = answered;
    if(unansweredCount) unansweredCount.textContent = unanswered;
    if(progressFill) progressFill.style.width = percentage + '%';
    if(progressPercent) progressPercent.textContent = Math.round(percentage) + '%';
    if(answeredDisplay) answeredDisplay.textContent = answered + ' / ' + questions.length + ' answered';
    if(sumAnswered) sumAnswered.textContent = answered;
    if(sumUnanswered) sumUnanswered.textContent = unanswered;
}

function confirmSubmit() { 
    if (isSubmitting || autoSubmitTriggered) return;
    updateProgress(); 
    const modal = document.getElementById('submitModal');
    if(modal) {
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
    if(modal) modal.classList.add('hidden'); 
}

function updateBeforeUnload(enable) {
    examPageInProgress = enable;
}

async function forceAutoSubmit() {
    if (autoSubmitTriggered || forceSubmitInProgress) return;
    
    console.log('⏰ FORCE AUTO-SUBMIT TRIGGERED!');
    autoSubmitTriggered = true;
    forceSubmitInProgress = true;
    isSubmitting = true;
    examPageInProgress = false;
    
    clearInterval(timerInterval);
    clearInterval(autoSaveInterval);
    
    document.getElementById('mainContainer').style.opacity = '0.5';
    document.getElementById('submitBtn').disabled = true;
    
    const overlay = document.getElementById('forceSubmitOverlay');
    overlay.classList.add('show');
    
    const progressBar = document.getElementById('forceSubmitProgress');
    const statusText = document.getElementById('forceSubmitStatus');
    const spinner = document.getElementById('forceSubmitSpinner');
    
    let progress = 0;
    
    const progressInterval = setInterval(() => {
        progress += Math.random() * 6 + 2;
        if (progress > 95) progress = 95;
        if (progressBar) progressBar.style.width = progress + '%';
        
        const statuses = [
            '⏳ Submitting your answers...',
            '📝 Processing responses...',
            '🧮 Calculating your score...',
            '💾 Saving your results...'
        ];
        const idx = Math.min(Math.floor(progress / 25), statuses.length - 1);
        if (statusText) statusText.textContent = statuses[idx];
    }, 250);
    
    try {
        const q = questions[currentIndex];
        if (q && answeredQuestions[q.id]) {
            await saveAnswer(q.id, answeredQuestions[q.id]);
        }
        
        const response = await fetch(APP_URL + '/ajax/student/submit_exam.php', { 
            method: 'POST', 
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': CSRF_TOKEN 
            }, 
            body: JSON.stringify({ 
                attempt_id: attemptId, 
                auto_submit: true 
            }) 
        });
        
        const data = await response.json();
        
        clearInterval(progressInterval);
        if (progressBar) progressBar.style.width = '100%';
        if (statusText) statusText.textContent = '✅ Exam submitted successfully!';
        if (spinner) spinner.style.display = 'none';
        
        if (data.success) {
            setTimeout(() => {
                window.location.href = APP_URL + '/student/results.php?submitted=' + examId; 
            }, 1500);
        } else {
            await fallbackSubmit();
        }
        
    } catch(error) { 
        console.error('Force auto-submit error:', error);
        clearInterval(progressInterval);
        await fallbackSubmit();
    }
}

async function fallbackSubmit() {
    const statusText = document.getElementById('forceSubmitStatus');
    const progressBar = document.getElementById('forceSubmitProgress');
    const spinner = document.getElementById('forceSubmitSpinner');
    
    if (statusText) statusText.textContent = '⚠️ Attempting fallback submission...';
    if (spinner) spinner.style.display = 'inline-block';
    
    try {
        const formData = new FormData();
        formData.append('attempt_id', attemptId);
        formData.append('auto_submit', '1');
        formData.append('csrf_token', CSRF_TOKEN);
        
        const response = await fetch(APP_URL + '/ajax/student/submit_exam.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        
        const data = await response.json();
        
        if (progressBar) progressBar.style.width = '100%';
        if (statusText) statusText.textContent = '✅ Exam submitted successfully!';
        if (spinner) spinner.style.display = 'none';
        
        if (data.success) {
            setTimeout(() => {
                window.location.href = APP_URL + '/student/results.php?submitted=' + examId; 
            }, 1500);
        } else {
            if (statusText) statusText.textContent = '⚠️ Submission completed with issues.';
            setTimeout(() => {
                window.location.href = APP_URL + '/student/dashboard.php?error=submission_issue';
            }, 3000);
        }
    } catch (error) {
        console.error('Fallback submission failed:', error);
        if (statusText) statusText.textContent = '❌ Submission failed. Please contact admin.';
        setTimeout(() => {
            window.location.href = APP_URL + '/student/dashboard.php?error=submission_failed';
        }, 3000);
    }
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
    if (modalContent) {
        modalContent.style.display = 'none';
    }
    
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
        
        let progress = 0;
        const submitProgress = setInterval(() => {
            progress += Math.random() * 15 + 5;
            if (progress > 95) progress = 95;
            const el = document.getElementById('submitProgress');
            if (el) el.style.width = progress + '%';
        }, 200);
    }
    
    try { 
        const q = questions[currentIndex];
        if (q && answeredQuestions[q.id]) {
            await saveAnswer(q.id, answeredQuestions[q.id]);
        }
        
        const response = await fetch(APP_URL + '/ajax/student/submit_exam.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, 
            body: JSON.stringify({ attempt_id: attemptId, auto_submit: false }) 
        }); 
        const data = await response.json(); 
        
        const el = document.getElementById('submitProgress');
        if (el) el.style.width = '100%';
        
        if (data.success) {
            setTimeout(() => { 
                window.location.href = APP_URL + '/student/results.php?submitted=' + examId; 
            }, 800);
        } else { 
            alert(data.message || 'Submission failed. Please contact admin.');
            const spinner = document.getElementById('submitSpinner');
            if (spinner) spinner.remove();
            if (modalContent) modalContent.style.display = 'block';
            closeSubmitModal(); 
            isSubmitting = false;
            examPageInProgress = true;
        } 
    } catch(error) { 
        alert('Submission failed. Please contact admin.');
        const spinner = document.getElementById('submitSpinner');
        if (spinner) spinner.remove();
        if (modalContent) modalContent.style.display = 'block';
        closeSubmitModal(); 
        isSubmitting = false;
        examPageInProgress = true;
    } 
}

function startAutoSave() { 
    autoSaveInterval = setInterval(() => { 
        const q = questions[currentIndex]; 
        if(q && answeredQuestions[q.id]) saveAnswer(q.id, answeredQuestions[q.id]); 
    }, 30000); 
}

function escapeHtml(text) { 
    if(!text) return ''; 
    const div = document.createElement('div'); 
    div.textContent = text; 
    return div.innerHTML; 
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Starting exam initialization');
    initExam();
});
</script>
</body>
</html>