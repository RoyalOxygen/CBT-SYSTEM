<?php
/**
 * CBT System - Update Student Results
 * Admin can search for a student and update their exam results
 * Grade is calculated based on SCORE (actual marks obtained), not percentage
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Update Student Results';
$db = getDB();

// Helper function to get grade based on SCORE (actual marks)
function getGradeFromScore($score, $totalMarks) {
    if ($totalMarks <= 0) return ['grade' => 'F', 'remark' => 'Fail'];
    
    $ratio = $score / $totalMarks;
    
    if ($ratio >= 0.7) return ['grade' => 'A', 'remark' => 'Excellent'];
    if ($ratio >= 0.6) return ['grade' => 'B', 'remark' => 'Very Good'];
    if ($ratio >= 0.5) return ['grade' => 'C', 'remark' => 'Good'];
    if ($ratio >= 0.45) return ['grade' => 'D', 'remark' => 'Pass'];
    if ($ratio >= 0.4) return ['grade' => 'E', 'remark' => 'Pass'];
    return ['grade' => 'F', 'remark' => 'Fail'];
}

// Handle result update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_result') {
    $csrf_token = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    if (!validateCSRFToken($csrf_token)) {
        redirect(APP_URL . '/admin/update_results.php', 'Invalid security token', 'error');
    }
    
    $result_id = intval($_POST['result_id']);
    $correct = intval($_POST['correct'] ?? 0);
    $total_questions = intval($_POST['total_questions'] ?? 0);
    $total_marks = floatval($_POST['total_marks'] ?? 0);
    $published = isset($_POST['published']) ? 1 : 0;
    
    // Get existing answered count (preserve skipped questions)
    $resultInfoStmt = $db->prepare("SELECT answered FROM results WHERE id = ?");
    $resultInfoStmt->execute([$result_id]);
    $existing = $resultInfoStmt->fetch();
    
    // Calculate based on correct answers only
    $answered = $existing ? $existing['answered'] : $total_questions;
    $wrong = $answered - $correct;
    
    // Calculate SCORE based on correct answers (proportional to total marks)
    $score = ($total_questions > 0) ? ($correct / $total_questions) * $total_marks : 0;
    
    // Calculate percentage (for display only)
    $percentage = ($total_marks > 0) ? ($score / $total_marks) * 100 : 0;
    
    // Get grade based on SCORE (actual marks)
    $grade_array = getGradeFromScore($score, $total_marks);
    
    try {
        // Get the exam_id and attempt_id for this result
        $stmt = $db->prepare("SELECT exam_id, attempt_id FROM results WHERE id = ?");
        $stmt->execute([$result_id]);
        $resultInfo = $stmt->fetch();
        
        if (!$resultInfo) {
            throw new Exception('Result not found');
        }
        
        // Update results table
        $updateStmt = $db->prepare("
            UPDATE results SET 
                correct = ?,
                wrong = ?,
                score = ?,
                percentage = ?,
                grade = ?,
                remark = ?,
                published = ?
            WHERE id = ?
        ");
        
        $updateStmt->execute([
            $correct, 
            $wrong,
            round($score, 2), 
            round($percentage, 2), 
            $grade_array['grade'], 
            $grade_array['remark'], 
            $published, 
            $result_id
        ]);
        
        // Also update exam_attempts if it exists
        if ($resultInfo['attempt_id']) {
            $attemptStmt = $db->prepare("
                UPDATE exam_attempts SET 
                    score = ?,
                    percentage = ?,
                    correct_count = ?
                WHERE id = ?
            ");
            $attemptStmt->execute([round($score, 2), round($percentage, 2), $correct, $resultInfo['attempt_id']]);
        }
        
        logActivity('admin', $_SESSION['admin_id'], 'update_result', 
                   "Updated result ID: $result_id (Correct: $correct, Score: " . round($score, 2) . ", Grade: {$grade_array['grade']})");
        redirect(APP_URL . '/admin/update_results.php', 'Result updated successfully');
        
    } catch (PDOException $e) {
        error_log("Update result error: " . $e->getMessage());
        redirect(APP_URL . '/admin/update_results.php', 'Error: ' . $e->getMessage(), 'error');
    } catch (Exception $e) {
        error_log("Update result error: " . $e->getMessage());
        redirect(APP_URL . '/admin/update_results.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Handle delete result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_result') {
    $csrf_token = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    if (!validateCSRFToken($csrf_token)) {
        redirect(APP_URL . '/admin/update_results.php', 'Invalid security token', 'error');
    }
    
    $result_id = intval($_POST['result_id']);
    
    try {
        $stmt = $db->prepare("SELECT r.*, e.exam_code, s.matric_number FROM results r 
                              JOIN exams e ON r.exam_id = e.id 
                              JOIN students s ON r.student_id = s.id 
                              WHERE r.id = ?");
        $stmt->execute([$result_id]);
        $result = $stmt->fetch();
        
        if ($result) {
            $db->prepare("DELETE FROM results WHERE id = ?")->execute([$result_id]);
            logActivity('admin', $_SESSION['admin_id'], 'delete_result', 
                       "Deleted result for student: {$result['matric_number']} on exam: {$result['exam_code']}");
            redirect(APP_URL . '/admin/update_results.php', 'Result deleted successfully');
        } else {
            redirect(APP_URL . '/admin/update_results.php', 'Result not found', 'error');
        }
    } catch (PDOException $e) {
        error_log("Delete result error: " . $e->getMessage());
        redirect(APP_URL . '/admin/update_results.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Get all exams for dropdown
$exams = $db->query("SELECT id, exam_code, exam_title FROM exams ORDER BY created_at DESC")->fetchAll();

// Get search parameters
$search_matric = isset($_GET['matric']) ? trim($_GET['matric']) : '';
$selected_exam = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$search_performed = false;
$student_results = [];
$student_info = null;

if (!empty($search_matric) || $selected_exam > 0) {
    $search_performed = true;
    
    $sql = "
        SELECT r.*, e.exam_code, e.exam_title, e.duration, e.pass_mark,
               s.matric_number, s.first_name, s.last_name, s.other_name, s.email, s.phone, s.photo
        FROM results r
        JOIN exams e ON r.exam_id = e.id
        JOIN students s ON r.student_id = s.id
        WHERE 1=1
    ";
    $params = [];
    
    if (!empty($search_matric)) {
        $sql .= " AND s.matric_number LIKE ?";
        $params[] = "%$search_matric%";
    }
    
    if ($selected_exam > 0) {
        $sql .= " AND r.exam_id = ?";
        $params[] = $selected_exam;
    }
    
    $sql .= " ORDER BY r.id DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $student_results = $stmt->fetchAll();
    
    if (!empty($student_results)) {
        $first_result = $student_results[0];
        $student_info = [
            'matric_number' => $first_result['matric_number'],
            'first_name' => $first_result['first_name'],
            'last_name' => $first_result['last_name'],
            'other_name' => $first_result['other_name'],
            'email' => $first_result['email'],
            'phone' => $first_result['phone'],
            'photo' => $first_result['photo']
        ];
    } elseif (!empty($search_matric)) {
        $studentStmt = $db->prepare("SELECT * FROM students WHERE matric_number LIKE ?");
        $studentStmt->execute(["%$search_matric%"]);
        $student_info = $studentStmt->fetch();
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
.editable-field {
    background: #fff3cd;
    border: 2px solid #ffc107;
}
.editable-field:focus {
    border-color: #ffc107;
    box-shadow: 0 0 0 0.2rem rgba(255,193,7,0.25);
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Update Student Results</h4>
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
                    <div class="stats-box">
                        <small class="text-muted">Total Results</small>
                        <h3 class="mb-0 text-primary"><?php echo count($student_results); ?></h3>
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
                // Calculate grade based on SCORE for display
                $scoreGrade = getGradeFromScore($result['score'], $result['total_marks']);
                $gradeColor = $scoreGrade['grade'] == 'A' ? 'success' : ($scoreGrade['grade'] == 'B' ? 'info' : ($scoreGrade['grade'] == 'C' ? 'primary' : ($scoreGrade['grade'] == 'D' || $scoreGrade['grade'] == 'E' ? 'warning' : 'danger')));
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
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Score</small>
                                <strong><?php echo $result['score']; ?> / <?php echo $result['total_marks']; ?></strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <?php 
                                $progressPercent = ($result['total_marks'] > 0) ? ($result['score'] / $result['total_marks']) * 100 : 0;
                                ?>
                                <div class="progress-bar bg-<?php echo $gradeColor; ?>" style="width: <?php echo $progressPercent; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="row text-center small mb-3">
                            <div class="col-4">
                                <div class="border-end">
                                    <div class="fw-bold text-success"><?php echo $result['correct']; ?></div>
                                    <span class="text-muted">Correct</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border-end">
                                    <div class="fw-bold text-danger"><?php echo $result['wrong']; ?></div>
                                    <span class="text-muted">Wrong</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-warning"><?php echo $result['total_questions'] - $result['answered']; ?></div>
                                <span class="text-muted">Skipped</span>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="grade-badge bg-<?php echo $gradeColor; ?> bg-opacity-10 text-<?php echo $gradeColor; ?> fw-bold">
                                    Grade: <?php echo $scoreGrade['grade']; ?>
                                </span>
                            </div>
                            <div>
                                <span class="fw-bold"><?php echo number_format($result['percentage'], 1); ?>%</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary flex-grow-1" onclick="editResult(<?php echo $result['id']; ?>)">
                                <i class="bi bi-pencil me-1"></i> Edit
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_result">
                <input type="hidden" name="result_id" id="edit_result_id">
                <div class="modal-body" id="editResultBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2">Loading result data...</p>
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

<script>
// Make sure global variables are defined
const APP_URL = '<?php echo APP_URL; ?>';
const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';
const CSRF_TOKEN_NAME = '<?php echo CSRF_TOKEN_NAME; ?>';

function editResult(resultId) {
    console.log('Edit result called for ID:', resultId);
    
    const modal = new bootstrap.Modal(document.getElementById('editResultModal'));
    const modalBody = document.getElementById('editResultBody');
    
    modalBody.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Loading result data...</p>
        </div>
    `;
    modal.show();
    
    fetch(APP_URL + '/ajax/admin/get_result.php?id=' + resultId, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                document.getElementById('edit_result_id').value = resultId;
                modalBody.innerHTML = data.html;
                updateCalculation();
            } else {
                modalBody.innerHTML = `
                    <div class="alert alert-danger text-center m-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        ${data.message}
                    </div>
                `;
            }
        } catch (e) {
            console.error('JSON Parse error:', e, 'Response:', text);
            modalBody.innerHTML = `
                <div class="alert alert-danger text-center m-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Invalid response from server. Please check the error log.
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        modalBody.innerHTML = `
            <div class="alert alert-danger text-center m-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Failed to load result data: ${error.message}
            </div>
        `;
    });
}

function deleteResult(resultId, examCode) {
    if (confirm(`Are you sure you want to delete the result for exam "${examCode}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = CSRF_TOKEN_NAME;
        csrfInput.value = CSRF_TOKEN;
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_result';
        
        const resultIdInput = document.createElement('input');
        resultIdInput.type = 'hidden';
        resultIdInput.name = 'result_id';
        resultIdInput.value = resultId;
        
        form.appendChild(csrfInput);
        form.appendChild(actionInput);
        form.appendChild(resultIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-calculate everything based ONLY on correct answers
function updateCalculation() {
    const correct = parseInt(document