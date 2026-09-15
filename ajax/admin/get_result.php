<?php
// Enable maximum error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Don't send JSON header yet - let's see the error first
// header('Content-Type: application/json');

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
// FIXED: Added missing opening parenthesis before the closing bracket
require_once __DIR__ . '/../../config/functions.php';

// Create a simple error handler
function sendError($message) {
    // Clean any output buffers
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_id']) {
    sendError('Unauthorized access - Please login as admin');
}

// Get result ID
$result_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($result_id <= 0) {
    sendError('Invalid result ID');
}

try {
    // Test database connection first
    $db = getDB();
    if (!$db) {
        sendError('Database connection failed');
    }
    
    // Test if results table exists
    $testStmt = $db->query("SHOW TABLES LIKE 'results'");
    if ($testStmt->rowCount() == 0) {
        sendError('Results table does not exist');
    }
    
    // Get the result
    $stmt = $db->prepare("
        SELECT r.*, e.exam_code, e.exam_title, e.duration, e.pass_mark,
               s.matric_number, s.first_name, s.last_name, s.other_name
        FROM results r
        JOIN exams e ON r.exam_id = e.id
        JOIN students s ON r.student_id = s.id
        WHERE r.id = ?
    ");
    $stmt->execute([$result_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        sendError("Result with ID $result_id not found");
    }
    
    // Helper function for grade
    function getGradeFromScore($score, $totalMarks) {
        if ($totalMarks <= 0) return ['grade' => 'F', 'remark' => 'Fail', 'color' => 'danger'];
        
        $ratio = $score / $totalMarks;
        
        if ($ratio >= 0.7) return ['grade' => 'A', 'remark' => 'Excellent', 'color' => 'success'];
        if ($ratio >= 0.6) return ['grade' => 'B', 'remark' => 'Very Good', 'color' => 'info'];
        if ($ratio >= 0.5) return ['grade' => 'C', 'remark' => 'Good', 'color' => 'primary'];
        if ($ratio >= 0.45) return ['grade' => 'D', 'remark' => 'Pass', 'color' => 'warning'];
        if ($ratio >= 0.4) return ['grade' => 'E', 'remark' => 'Pass', 'color' => 'warning'];
        return ['grade' => 'F', 'remark' => 'Fail', 'color' => 'danger'];
    }
    
    $skipped = $result['total_questions'] - $result['answered'];
    $progressPercent = ($result['total_marks'] > 0) ? ($result['score'] / $result['total_marks']) * 100 : 0;
    
    // Calculate grade based on SCORE
    $scoreGrade = getGradeFromScore($result['score'], $result['total_marks']);
    $gradeColor = $scoreGrade['color'];
    
    // Build HTML
    $html = '
    <div class="row g-3">
        <div class="col-12">
            <div class="alert alert-info small">
                <strong>Student:</strong> ' . htmlspecialchars($result['last_name'] . ', ' . $result['first_name'] . ' (' . $result['matric_number'] . ')') . '<br>
                <strong>Exam:</strong> ' . htmlspecialchars($result['exam_code'] . ' - ' . $result['exam_title']) . '
            </div>
        </div>
        
        <div class="col-12">
            <div class="alert alert-warning text-center">
                <i class="bi bi-pencil-square me-2"></i>
                <strong>Only Correct Answers can be edited.</strong> Grade is calculated based on SCORE.
            </div>
        </div>
        
        <div class="col-md-6 mx-auto">
            <label class="form-label fw-bold">
                <i class="bi bi-check-circle-fill text-success me-1"></i> Correct Answers <span class="text-danger">*</span>
            </label>
            <input type="number" name="correct" id="edit_correct" class="form-control form-control-lg" 
                   value="' . $result['correct'] . '" min="0" max="' . $result['total_questions'] . '" required>
            <small class="text-muted">Change this value to automatically recalculate everything</small>
        </div>
        
        <div class="col-12">
            <div class="bg-light p-3 rounded">
                <h6 class="mb-3"><i class="bi bi-calculator-fill me-2"></i>Automatically Calculated Results</h6>
                <div class="row text-center">
                    <div class="col-md-3 col-6 mb-2">
                        <div class="bg-white p-2 rounded border">
                            <small class="text-muted d-block">Total Questions</small>
                            <strong>' . $result['total_questions'] . '</strong>
                            <input type="hidden" name="total_questions" id="edit_total_questions" value="' . $result['total_questions'] . '">
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="bg-white p-2 rounded border">
                            <small class="text-muted d-block">Total Marks</small>
                            <strong>' . $result['total_marks'] . '</strong>
                            <input type="hidden" name="total_marks" id="edit_total_marks" value="' . $result['total_marks'] . '">
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="bg-white p-2 rounded border">
                            <small class="text-muted d-block">Answered</small>
                            <strong id="edit_answered_display">' . $result['answered'] . '</strong>
                            <input type="hidden" name="answered" id="edit_answered" value="' . $result['answered'] . '">
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="bg-white p-2 rounded border">
                            <small class="text-muted d-block">Wrong</small>
                            <strong id="edit_wrong_display">' . $result['wrong'] . '</strong>
                            <input type="hidden" name="wrong" id="edit_wrong" value="' . $result['wrong'] . '">
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="bg-white p-2 rounded border">
                            <small class="text-muted d-block">Skipped</small>
                            <strong id="edit_skipped_display">' . $skipped . '</strong>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="bg-white p-2 rounded border">
                            <small class="text-muted d-block">Calculated Score</small>
                            <strong id="edit_score_display">' . $result['score'] . ' / ' . $result['total_marks'] . '</strong>
                            <input type="hidden" name="score" id="edit_score" value="' . $result['score'] . '">
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="bg-white p-2 rounded border">
                            <small class="text-muted d-block">Percentage</small>
                            <strong id="edit_percentage_display">' . round($result['percentage'], 2) . '%</strong>
                            <input type="hidden" name="percentage" id="edit_percentage" value="' . round($result['percentage'], 2) . '">
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="bg-white p-2 rounded border">
                            <small class="text-muted d-block">Grade (based on SCORE)</small>
                            <div id="grade_preview">
                                <span class="badge bg-' . $gradeColor . ' fs-6 px-3 py-2">Grade: ' . $scoreGrade['grade'] . '</span>
                                <br><small>' . $scoreGrade['remark'] . '</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted d-block">Progress Preview (Score/Total Marks)</small>
                    <div class="progress mt-1" style="height: 10px;">
                        <div class="progress-bar bg-' . ($progressPercent >= 50 ? 'success' : ($progressPercent >= 40 ? 'warning' : 'danger')) . '" 
                             id="progress_preview" style="width: ' . $progressPercent . '%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="published" value="1" id="edit_published" ' . ($result['published'] ? 'checked' : '') . '>
                <label class="form-check-label" for="edit_published">
                    <i class="bi bi-globe me-1"></i> Publish Result (visible to student)
                </label>
            </div>
        </div>
        
        <div class="col-12">
            <div class="alert alert-success small">
                <i class="bi bi-calculator-fill me-1"></i>
                <strong>How it works:</strong><br>
                • Score = (Correct Answers / Total Questions) × Total Marks<br>
                • Grade = Based on SCORE/Total Marks ratio (NOT percentage)<br>
                • Example: 35/50 = 70% score ratio → Grade A<br>
                • Grade thresholds: ≥70% = A, ≥60% = B, ≥50% = C, ≥45% = D, ≥40% = E, &lt;40% = F
            </div>
        </div>
    </div>';
    
    // Clear any output buffers and send JSON
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage());
}
?>