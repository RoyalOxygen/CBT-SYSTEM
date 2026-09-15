<?php
/**
 * CBT System - Edit Student Result
 * Admin can search student, select exam, and edit correct answers count
 * All other values (score, percentage, grade) are calculated automatically
 * Uses the same grading logic as exam submission
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Update Student Results';
$db = getDB();

// Get all exams for dropdown
$exams = $db->query("SELECT id, exam_code, exam_title FROM exams ORDER BY created_at DESC")->fetchAll();

// Handle search
$search_matric = isset($_GET['matric']) ? sanitize($_GET['matric']) : '';
$selected_exam = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$search_performed = !empty($search_matric) || $selected_exam > 0;

$student_info = null;
$student_results = [];

if ($search_performed) {
    // Search for student by matric number
    if (!empty($search_matric)) {
        $studentStmt = $db->prepare("
            SELECT s.*, d.dept_name, l.level_name 
            FROM students s
            LEFT JOIN departments d ON s.department_id = d.id
            LEFT JOIN levels l ON s.level_id = l.id
            WHERE s.matric_number LIKE ? AND s.status = 1
            LIMIT 1
        ");
        $studentStmt->execute(["%{$search_matric}%"]);
        $student_info = $studentStmt->fetch();
        
        if ($student_info) {
            // Get student results
            $resultsSql = "
                SELECT r.*, e.exam_code, e.exam_title, e.duration, e.pass_mark, e.overall_score,
                       ea.submission_method, ea.status as attempt_status
                FROM results r
                JOIN exams e ON r.exam_id = e.id
                LEFT JOIN exam_attempts ea ON ea.id = r.attempt_id
                WHERE r.student_id = ?
            ";
            $params = [$student_info['id']];
            
            if ($selected_exam > 0) {
                $resultsSql .= " AND r.exam_id = ?";
                $params[] = $selected_exam;
            }
            
            $resultsSql .= " ORDER BY r.published_at DESC";
            $resultsStmt = $db->prepare($resultsSql);
            $resultsStmt->execute($params);
            $student_results = $resultsStmt->fetchAll();
        }
    }
}

// Helper function to get grade from percentage - matches exam submission logic
function getGradeFromPercentage($percentage) {
    if ($percentage >= 70) return ['grade' => 'A', 'remark' => 'Excellent'];
    if ($percentage >= 60) return ['grade' => 'B', 'remark' => 'Very Good'];
    if ($percentage >= 50) return ['grade' => 'C', 'remark' => 'Good'];
    if ($percentage >= 45) return ['grade' => 'D', 'remark' => 'Pass'];
    if ($percentage >= 40) return ['grade' => 'E', 'remark' => 'Pass'];
    return ['grade' => 'F', 'remark' => 'Fail'];
}

// Handle result update via POST - Only correct answers count is editable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_result') {
    $result_id = intval($_POST['result_id']);
    $correct_count = intval($_POST['correct_count']);
    
    try {
        // Get result details with exam info
        $resultStmt = $db->prepare("
            SELECT r.*, e.overall_score, e.question_limit, e.exam_code, e.exam_title
            FROM results r
            JOIN exams e ON r.exam_id = e.id
            WHERE r.id = ?
        ");
        $resultStmt->execute([$result_id]);
        $result = $resultStmt->fetch();
        
        if (!$result) {
            throw new Exception('Result not found');
        }
        
        // Get all questions with their marks for this exam
        $questionsStmt = $db->prepare("
            SELECT id, marks 
            FROM questions 
            WHERE exam_id = ? AND status = 1
            ORDER BY marks DESC, id
            LIMIT ?
        ");
        $questionsStmt->execute([$result['exam_id'], $result['total_questions']]);
        $questions = $questionsStmt->fetchAll();
        
        $total_questions = count($questions);
        $total_marks = 0;
        $marks_list = [];
        
        foreach ($questions as $q) {
            $marks = floatval($q['marks']);
            $total_marks += $marks;
            $marks_list[] = $marks;
        }
        
        // Ensure correct count doesn't exceed total questions
        $correct_count = min($correct_count, $total_questions);
        $wrong_count = $total_questions - $correct_count;
        
        // Sort marks in descending order to assign correct answers to highest value questions
        $sorted_marks = $marks_list;
        rsort($sorted_marks);
        
        // Calculate earned marks based on correct count (best possible score)
        $earned_marks = 0;
        for ($i = 0; $i < $correct_count && $i < count($sorted_marks); $i++) {
            $earned_marks += $sorted_marks[$i];
        }
        
        // Calculate percentage - matches exam submission logic
        $overall_score_setting = intval($result['overall_score'] ?? 100);
        
        if ($overall_score_setting == 70) {
            // Questions contribute to 70% of overall score
            $percentage = ($correct_count / max(1, $total_questions)) * 70;
            $score_display = $percentage;
            $total_marks_display = 70;
        } else {
            // Full 100% from questions
            $percentage = $total_marks > 0 ? ($earned_marks / $total_marks) * 100 : 0;
            $score_display = $earned_marks;
            $total_marks_display = $total_marks;
        }
        
        $grade = getGradeFromPercentage($percentage);
        
        // Start transaction
        $db->beginTransaction();
        
        // Update results table
        $updateResult = $db->prepare("
            UPDATE results 
            SET correct = ?, 
                wrong = ?, 
                score = ?, 
                total_marks = ?,
                percentage = ?, 
                grade = ?, 
                remark = ?,
                total_questions_score = ?
            WHERE id = ?
        ");
        $updateResult->execute([
            $correct_count, 
            $wrong_count, 
            $score_display, 
            $total_marks_display,
            $percentage,
            $grade['grade'], 
            $grade['remark'],
            $correct_count,
            $result_id
        ]);
        
        // Update exam_attempts if exists
        if ($result['attempt_id']) {
            $updateAttempt = $db->prepare("
                UPDATE exam_attempts 
                SET correct_count = ?, 
                    score = ?, 
                    percentage = ?, 
                    answered_count = ?
                WHERE id = ?
            ");
            $updateAttempt->execute([$correct_count, $score_display, $percentage, $total_questions, $result['attempt_id']]);
        }
        
        // If there are answers in the answers table, update them to reflect the new correct count
        $answersStmt = $db->prepare("
            SELECT a.id, q.marks
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.attempt_id = ?
            ORDER BY q.marks DESC, a.id
        ");
        $answersStmt->execute([$result['attempt_id']]);
        $answers = $answersStmt->fetchAll();
        
        if (count($answers) > 0) {
            // Mark first $correct_count answers as correct (highest marks first)
            for ($i = 0; $i < count($answers); $i++) {
                $is_correct = ($i < $correct_count) ? 1 : 0;
                $marks_obtained = $is_correct ? $answers[$i]['marks'] : 0;
                $updateAnswer = $db->prepare("UPDATE answers SET is_correct = ?, marks_obtained = ? WHERE id = ?");
                $updateAnswer->execute([$is_correct, $marks_obtained, $answers[$i]['id']]);
            }
        }
        
        // Commit transaction
        $db->commit();
        
        logActivity('admin', $_SESSION['admin_id'], 'update_result', "Updated result ID: $result_id - Correct count: $correct_count");
        redirect(APP_URL . '/admin/edit_result.php?updated=1&matric=' . urlencode($search_matric) . '&exam_id=' . $selected_exam, 'Result updated successfully!');
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Update result error: " . $e->getMessage());
        redirect(APP_URL . '/admin/edit_result.php', 'Error updating result: ' . $e->getMessage(), 'error');
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Update result general error: " . $e->getMessage());
        redirect(APP_URL . '/admin/edit_result.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Handle delete result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_result') {
    $result_id = intval($_POST['result_id']);
    
    try {
        $db->beginTransaction();
        
        // Get result details for logging
        $resultStmt = $db->prepare("SELECT * FROM results WHERE id = ?");
        $resultStmt->execute([$result_id]);
        $result = $resultStmt->fetch();
        
        if ($result) {
            // Delete associated answers if they exist
            if ($result['attempt_id']) {
                $deleteAnswers = $db->prepare("DELETE FROM answers WHERE attempt_id = ?");
                $deleteAnswers->execute([$result['attempt_id']]);
            }
            
            // Delete exam_attempt if exists
            if ($result['attempt_id']) {
                $deleteAttempt = $db->prepare("DELETE FROM exam_attempts WHERE id = ?");
                $deleteAttempt->execute([$result['attempt_id']]);
            }
            
            // Delete the result
            $deleteStmt = $db->prepare("DELETE FROM results WHERE id = ?");
            $deleteStmt->execute([$result_id]);
            
            $db->commit();
            
            logActivity('admin', $_SESSION['admin_id'], 'delete_result', "Deleted result ID: $result_id");
            redirect(APP_URL . '/admin/edit_result.php?deleted=1&matric=' . urlencode($search_matric) . '&exam_id=' . $selected_exam, 'Result deleted successfully!');
        }
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Delete result error: " . $e->getMessage());
        redirect(APP_URL . '/admin/edit_result.php', 'Error deleting result', 'error');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.result-card {
    transition: all 0.2s;
    border-left: 4px solid #667eea;
}
.result-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.grade-badge {
    font-size: 0.85rem;
    padding: 5px 12px;
    border-radius: 20px;
}
.search-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 25px;
    color: white;
}
.search-section .form-control, .search-section .form-select {
    border-radius: 10px;
    border: none;
}
.search-section .btn {
    border-radius: 10px;
    padding: 10px 25px;
}
.student-avatar-lg {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.stats-box {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px;
    text-align: center;
    transition: all 0.2s;
}
.stats-box:hover {
    background: #e9ecef;
}
.number-input {
    width: 150px;
    text-align: center;
    font-size: 1.5rem;
    font-weight: bold;
    border: 2px solid #667eea;
    border-radius: 10px;
    padding: 10px;
}
.number-input:focus {
    outline: none;
    border-color: #764ba2;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.2);
}
.result-stats {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    padding: 10px;
}
.preview-box {
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
    border-radius: 12px;
    padding: 20px;
}
.preview-item {
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    background: white;
}
.preview-label {
    font-size: 11px;
    text-transform: uppercase;
    color: #6c757d;
    letter-spacing: 0.5px;
}
.preview-value {
    font-size: 24px;
    font-weight: 700;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Update Student Results</h4>
    <div>
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success alert-dismissible fade show m-0 py-2" role="alert" id="successAlert">
                <i class="bi bi-check-circle-fill me-2"></i> Result updated successfully!
                <button type="button" class="btn-close small" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif (isset($_GET['deleted'])): ?>
            <div class="alert alert-danger alert-dismissible fade show m-0 py-2" role="alert" id="successAlert">
                <i class="bi bi-trash-fill me-2"></i> Result deleted successfully!
                <button type="button" class="btn-close small" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Search Section -->
<div class="search-section mb-4">
    <form method="GET" action="">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label text-white mb-1">
                    <i class="bi bi-search me-1"></i> Search by Matric Number
                </label>
                <input type="text" name="matric" class="form-control form-control-lg" 
                       placeholder="Enter matric number..." 
                       value="<?php echo e($search_matric); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label text-white mb-1">
                    <i class="bi bi-journal-bookmark-fill me-1"></i> Filter by Exam
                </label>
                <select name="exam_id" class="form-select form-select-lg">
                    <option value="0">All Exams</option>
                    <?php foreach ($exams as $exam): ?>
                        <option value="<?php echo $exam['id']; ?>" <?php echo $selected_exam == $exam['id'] ? 'selected' : ''; ?>>
                            <?php echo e($exam['exam_code'] . ' - ' . $exam['exam_title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-light btn-lg w-100 fw-bold">
                    <i class="bi bi-search me-2"></i> Search Results
                </button>
            </div>
        </div>
    </form>
</div>

<?php if ($search_performed): ?>
    <?php if ($student_info): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <?php if (!empty($student_info['photo'])): ?>
                        <img src="<?php echo APP_URL; ?>/assets/uploads/student_photos/<?php echo e($student_info['photo']); ?>" class="student-avatar-lg">
                    <?php else: ?>
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 120px; height: 120px;">
                            <i class="bi bi-person-fill fs-1"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h4 class="mb-1"><?php echo e($student_info['last_name'] . ', ' . $student_info['first_name']); ?></h4>
                    <p class="text-muted mb-2">
                        <i class="bi bi-person-badge me-1"></i> <?php echo e($student_info['matric_number']); ?>
                    </p>
                    <p class="mb-0 small text-muted">
                        <i class="bi bi-envelope me-1"></i> <?php echo e($student_info['email'] ?? 'No email'); ?> &nbsp;|&nbsp;
                        <i class="bi bi-telephone me-1"></i> <?php echo e($student_info['phone'] ?? 'No phone'); ?>
                    </p>
                </div>
                <div class="col-md-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stats-box">
                                <small class="text-muted">Department</small>
                                <h6 class="mb-0"><?php echo e($student_info['dept_name'] ?? 'N/A'); ?></h6>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-box">
                                <small class="text-muted">Level</small>
                                <h6 class="mb-0"><?php echo e($student_info['level_name'] ?? 'N/A'); ?></h6>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="stats-box">
                                <small class="text-muted">Total Results</small>
                                <h3 class="mb-0 text-primary"><?php echo count($student_results); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php elseif (!empty($search_matric)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        No student found with matric number containing "<?php echo e($search_matric); ?>"
    </div>
    <?php endif; ?>
    
    <?php if (empty($student_results) && !empty($search_matric) && $student_info): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-2"></i>
            This student has no exam results yet.
        </div>
    <?php elseif (!empty($student_results)): ?>
        <div class="row g-4">
            <?php foreach ($student_results as $result): 
                $gradeColor = $result['grade'] == 'A' ? 'success' : ($result['grade'] == 'B' ? 'info' : ($result['grade'] == 'C' ? 'primary' : ($result['grade'] == 'D' || $result['grade'] == 'E' ? 'warning' : 'danger')));
            ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100 result-card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="badge bg-dark"><?php echo e($result['exam_code']); ?></span>
                        <span class="badge bg-<?php echo $result['published'] ? 'success' : 'warning'; ?>">
                            <?php echo $result['published'] ? 'Published' : 'Draft'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title mb-2"><?php echo e($result['exam_title']); ?></h6>
                        
                        <div class="result-stats mb-3">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="fw-bold text-success"><?php echo $result['correct']; ?></div>
                                    <small class="text-muted">Correct</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-danger"><?php echo $result['wrong']; ?></div>
                                    <small class="text-muted">Wrong</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-warning"><?php echo $result['total_questions'] - $result['answered']; ?></div>
                                    <small class="text-muted">Skipped</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Score</small>
                                <strong><?php echo number_format($result['score'], 1); ?> / <?php echo number_format($result['total_marks'], 1); ?></strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <?php 
                                $progressPercent = ($result['total_marks'] > 0) ? ($result['score'] / $result['total_marks']) * 100 : 0;
                                ?>
                                <div class="progress-bar bg-<?php echo $gradeColor; ?>" style="width: <?php echo $progressPercent; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="grade-badge bg-<?php echo $gradeColor; ?> bg-opacity-10 text-<?php echo $gradeColor; ?> fw-bold">
                                    Grade: <?php echo $result['grade']; ?>
                                </span>
                            </div>
                            <div>
                                <span class="fw-bold"><?php echo number_format($result['percentage'], 1); ?>%</span>
                            </div>
                        </div>
                        
                        <?php if ($result['submission_method']): ?>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> 
                                    <?php echo $result['submission_method'] === 'manual' ? 'Manually submitted' : 'Auto-submitted'; ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary flex-grow-1" onclick="editResult(<?php echo $result['id']; ?>, <?php echo $result['total_questions']; ?>, <?php echo $result['score']; ?>, <?php echo $result['total_marks']; ?>, <?php echo $result['correct']; ?>, <?php echo $result['overall_score'] ?? 100; ?>)">
                                <i class="bi bi-pencil me-1"></i> Edit Score
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteResult(<?php echo $result['id']; ?>, '<?php echo e($result['exam_code']); ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-search fs-1"></i>
            <p class="mt-3 mb-0">Use the search form above to find a student and update their results.</p>
            <small>Search by matric number or filter by exam to view results.</small>
        </div>
    </div>
<?php endif; ?>

<!-- Edit Result Modal -->
<div class="modal fade" id="editResultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Exam Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="editResultForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_result">
                <input type="hidden" name="result_id" id="edit_result_id">
                <div class="modal-body">
                    <!-- Live Preview Section -->
                    <div class="preview-box mb-4">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="preview-item">
                                    <div class="preview-label">Correct Answers</div>
                                    <div class="preview-value text-success" id="previewCorrect">0</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="preview-item">
                                    <div class="preview-label">Wrong Answers</div>
                                    <div class="preview-value text-danger" id="previewWrong">0</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="preview-item">
                                    <div class="preview-label">Score</div>
                                    <div class="preview-value text-primary" id="previewScore">0 / 0</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="preview-item">
                                    <div class="preview-label">Percentage</div>
                                    <div class="preview-value text-info" id="previewPercent">0%</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="preview-item">
                                    <div class="preview-label">Grade & Remark</div>
                                    <div class="preview-value" id="previewGrade">F</div>
                                    <small class="text-muted" id="previewRemark">Fail</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group text-center">
                        <label class="form-label fw-bold mb-2">Number of Correct Answers</label>
                        <input type="number" name="correct_count" id="correct_count" 
                               class="form-control number-input mx-auto" 
                               min="0" step="1" required>
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Enter the number of questions the student answered correctly.<br>
                            Score, percentage, and grade will be calculated automatically.
                        </small>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-calculator-fill me-2"></i>
                        <strong>Calculation Method:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Wrong answers = Total questions - Correct answers</li>
                            <li>Score is calculated based on exam's overall_score setting</li>
                            <li>Grade is determined by the percentage score</li>
                            <li>Correct answers are assigned to highest-mark questions first</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Result Modal -->
<div class="modal fade" id="deleteResultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Delete Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete_result">
                <input type="hidden" name="result_id" id="delete_result_id">
                <div class="modal-body">
                    <p>Are you sure you want to delete this result?</p>
                    <div class="alert alert-warning">
                        <strong>Exam:</strong> <span id="delete_exam_name"></span><br>
                        <strong>Warning:</strong> This action cannot be undone. The result and all associated data will be permanently removed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Result</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentTotalQuestions = 0;
let currentTotalMarks = 0;
let currentOverallScore = 100;
let currentOriginalCorrect = 0;

// Edit Result Function
function editResult(resultId, totalQuestions, currentScore, totalMarks, currentCorrect, overallScore) {
    currentTotalQuestions = totalQuestions;
    currentTotalMarks = totalMarks;
    currentOverallScore = overallScore || 100;
    currentOriginalCorrect = currentCorrect;
    
    document.getElementById('edit_result_id').value = resultId;
    
    const correctInput = document.getElementById('correct_count');
    correctInput.value = currentCorrect;
    correctInput.max = totalQuestions;
    correctInput.min = 0;
    
    updatePreview(currentCorrect);
    
    new bootstrap.Modal(document.getElementById('editResultModal')).show();
}

// Update preview in real-time - matches exam submission logic
function updatePreview(correctCount) {
    const totalQuestions = currentTotalQuestions;
    const totalMarks = currentTotalMarks;
    const overallScore = currentOverallScore;
    
    const wrongCount = totalQuestions - correctCount;
    
    let score = 0;
    let percentage = 0;
    let totalMarksDisplay = totalMarks;
    
    // Calculate based on overall_score setting - matches exam submission logic
    if (overallScore == 70) {
        // Questions contribute to 70% of overall score
        percentage = (correctCount / Math.max(1, totalQuestions)) * 70;
        score = percentage;
        totalMarksDisplay = 70;
    } else {
        // Full 100% from questions - use proportional scoring
        score = (correctCount / Math.max(1, totalQuestions)) * totalMarks;
        percentage = totalMarks > 0 ? (score / totalMarks) * 100 : 0;
        totalMarksDisplay = totalMarks;
    }
    
    // Determine grade - matches exam submission logic
    let grade = 'F';
    let remark = 'Fail';
    let gradeClass = 'text-danger';
    
    if (percentage >= 70) {
        grade = 'A';
        remark = 'Excellent';
        gradeClass = 'text-success';
    } else if (percentage >= 60) {
        grade = 'B';
        remark = 'Very Good';
        gradeClass = 'text-info';
    } else if (percentage >= 50) {
        grade = 'C';
        remark = 'Good';
        gradeClass = 'text-primary';
    } else if (percentage >= 45) {
        grade = 'D';
        remark = 'Pass';
        gradeClass = 'text-warning';
    } else if (percentage >= 40) {
        grade = 'E';
        remark = 'Pass';
        gradeClass = 'text-warning';
    }
    
    // Update preview display
    document.getElementById('previewCorrect').textContent = correctCount;
    document.getElementById('previewWrong').textContent = wrongCount;
    document.getElementById('previewScore').innerHTML = `${score.toFixed(1)} / ${totalMarksDisplay.toFixed(1)}`;
    document.getElementById('previewPercent').innerHTML = `${percentage.toFixed(1)}%`;
    document.getElementById('previewGrade').innerHTML = grade;
    document.getElementById('previewRemark').innerHTML = remark;
    document.getElementById('previewGrade').className = `preview-value ${gradeClass}`;
}

// Add event listener to correct count input
document.getElementById('correct_count')?.addEventListener('input', function(e) {
    let value = parseInt(e.target.value) || 0;
    if (isNaN(value)) value = 0;
    if (value > currentTotalQuestions) value = currentTotalQuestions;
    if (value < 0) value = 0;
    e.target.value = value;
    updatePreview(value);
});

// Delete Result Function
function deleteResult(resultId, examCode) {
    document.getElementById('delete_result_id').value = resultId;
    document.getElementById('delete_exam_name').textContent = examCode;
    new bootstrap.Modal(document.getElementById('deleteResultModal')).show();
}

// Auto-hide success alert after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.getElementById('successAlert');
    if (alert) {
        setTimeout(function() {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 3000);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>