<?php
/**
 * CBT System - Exam Management
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Exam Management';
$db = getDB();

// Get filter options
$departments = $db->query("SELECT * FROM departments WHERE status = 1 ORDER BY dept_name")->fetchAll();
$levels = $db->query("SELECT * FROM levels WHERE status = 1 ORDER BY level_order")->fetchAll();
$semesters = $db->query("SELECT * FROM semesters WHERE status = 1 ORDER BY semester_order")->fetchAll();
$sessions = $db->query("SELECT * FROM sessions WHERE status = 1 ORDER BY session_name DESC")->fetchAll();

// Handle Reset Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_action'])) {
    $resetAction = $_POST['reset_action'];
    $csrf_token = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    if (!validateCSRFToken($csrf_token)) {
        redirect(APP_URL . '/admin/exams.php', 'Invalid security token', 'error');
    }
    
    try {
        switch ($resetAction) {
            case 'reset_single_student':
                $examId = intval($_POST['exam_id']);
                $studentId = intval($_POST['student_id']);
                $assignmentId = intval($_POST['assignment_id']);
                
                if ($studentId <= 0) {
                    redirect(APP_URL . '/admin/exams.php', 'No student selected', 'error');
                }
                
                // Get details for logging
                $stmt = $db->prepare("SELECT s.matric_number, s.first_name, s.last_name, e.exam_code 
                                      FROM students s, exams e 
                                      WHERE s.id = ? AND e.id = ?");
                $stmt->execute([$studentId, $examId]);
                $details = $stmt->fetch();
                
                if (!$details) {
                    redirect(APP_URL . '/admin/exams.php', 'Student or exam not found', 'error');
                }
                
                // Delete exam attempt and answers
                $db->prepare("DELETE FROM answers WHERE attempt_id IN (SELECT id FROM exam_attempts WHERE exam_id = ? AND student_id = ?)")->execute([$examId, $studentId]);
                $db->prepare("DELETE FROM exam_attempts WHERE exam_id = ? AND student_id = ?")->execute([$examId, $studentId]);
                
                // Reset assignment status
                $db->prepare("UPDATE exam_students SET status = 'assigned' WHERE exam_id = ? AND student_id = ?")->execute([$examId, $studentId]);
                
                // Update student current exam
                $db->prepare("UPDATE students SET current_exam_id = NULL WHERE id = ?")->execute([$studentId]);
                
                logActivity('admin', $_SESSION['admin_id'], 'reset_student_exam', 
                           "Reset exam for student: {$details['matric_number']} on exam: {$details['exam_code']}");
                redirect(APP_URL . '/admin/exams.php', "Exam reset for student: {$details['matric_number']}");
                break;
                
            case 'reset_all_students':
                $examId = intval($_POST['exam_id']);
                
                // Get exam details for logging
                $stmt = $db->prepare("SELECT exam_code, exam_title FROM exams WHERE id = ?");
                $stmt->execute([$examId]);
                $exam = $stmt->fetch();
                
                if (!$exam) {
                    redirect(APP_URL . '/admin/exams.php', 'Exam not found', 'error');
                }
                
                // Get count of students to reset
                $countStmt = $db->prepare("SELECT COUNT(*) FROM exam_students WHERE exam_id = ?");
                $countStmt->execute([$examId]);
                $studentCount = $countStmt->fetchColumn();
                
                // Delete all attempts and answers for this exam
                $db->prepare("DELETE FROM answers WHERE attempt_id IN (SELECT id FROM exam_attempts WHERE exam_id = ?)")->execute([$examId]);
                $db->prepare("DELETE FROM exam_attempts WHERE exam_id = ?")->execute([$examId]);
                
                // Reset all assignment statuses
                $db->prepare("UPDATE exam_students SET status = 'assigned' WHERE exam_id = ?")->execute([$examId]);
                
                // Clear current exam for affected students
                $db->prepare("UPDATE students SET current_exam_id = NULL WHERE current_exam_id = ?")->execute([$examId]);
                
                logActivity('admin', $_SESSION['admin_id'], 'reset_all_students_exam', 
                           "Reset exam for all $studentCount students on exam: {$exam['exam_code']}");
                redirect(APP_URL . '/admin/exams.php', "$studentCount students have been reset for exam: {$exam['exam_code']}");
                break;
        }
    } catch (PDOException $e) {
        error_log("Reset exam error: " . $e->getMessage());
        redirect(APP_URL . '/admin/exams.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['exam_id']) && !isset($_POST['reset_action'])) {
    $examId = intval($_POST['exam_id']);
    $action = sanitize($_POST['action']);
    
    try {
        switch ($action) {
            case 'publish':
                $db->prepare("UPDATE exams SET status = 'published' WHERE id = ? AND status = 'draft'")->execute([$examId]);
                logActivity('admin', $_SESSION['admin_id'], 'publish_exam', "Published exam ID: $examId");
                redirect(APP_URL . '/admin/exams.php', 'Exam published successfully');
                break;
            case 'start':
                $db->prepare("UPDATE exams SET status = 'running' WHERE id = ? AND status = 'published'")->execute([$examId]);
                logActivity('admin', $_SESSION['admin_id'], 'start_exam', "Started exam ID: $examId");
                redirect(APP_URL . '/admin/exams.php', 'Exam started successfully');
                break;
            case 'end':
                $db->prepare("UPDATE exams SET status = 'ended' WHERE id = ? AND status = 'running'")->execute([$examId]);
                logActivity('admin', $_SESSION['admin_id'], 'end_exam', "Ended exam ID: $examId");
                redirect(APP_URL . '/admin/exams.php', 'Exam ended successfully');
                break;
            case 'archive':
                $db->prepare("UPDATE exams SET status = 'archived' WHERE id = ? AND status = 'ended'")->execute([$examId]);
                logActivity('admin', $_SESSION['admin_id'], 'archive_exam', "Archived exam ID: $examId");
                redirect(APP_URL . '/admin/exams.php', 'Exam archived');
                break;
            case 'republish':
                $db->prepare("UPDATE exams SET status = 'published' WHERE id = ? AND status = 'archived'")->execute([$examId]);
                logActivity('admin', $_SESSION['admin_id'], 'republish_exam', "Republished exam ID: $examId");
                redirect(APP_URL . '/admin/exams.php', 'Exam republished successfully');
                break;
            case 'delete':
                $db->prepare("DELETE FROM exams WHERE id = ? AND status = 'draft'")->execute([$examId]);
                redirect(APP_URL . '/admin/exams.php', 'Exam deleted');
                break;
        }
    } catch (PDOException $e) {
        error_log("Exam action error: " . $e->getMessage());
        redirect(APP_URL . '/admin/exams.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Handle update exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_exam') {
    $examId = intval($_POST['exam_id']);
    $exam_code = sanitize($_POST['exam_code']);
    $exam_title = sanitize($_POST['exam_title']);
    $department_id = intval($_POST['department_id'] ?? 0);
    $level_id = intval($_POST['level_id'] ?? 0);
    $semester_id = intval($_POST['semester_id'] ?? 0);
    $session_id = intval($_POST['session_id'] ?? 0);
    $exam_date = $_POST['exam_date'];
    $start_time = $_POST['start_time'];
    $duration = intval($_POST['duration']);
    $question_limit = intval($_POST['question_limit']);
    $pass_mark = intval($_POST['pass_mark']);
    $has_batches = isset($_POST['has_batches']) ? 1 : 0;
    $instruction = sanitize($_POST['instruction']);
    $fingerprint_required = isset($_POST['fingerprint_required']) ? 1 : 0;
    $shuffle_questions = isset($_POST['shuffle_questions']) ? 1 : 0;
    $shuffle_options = isset($_POST['shuffle_options']) ? 1 : 0;
    $show_result = isset($_POST['show_result']) ? 1 : 0;
    
    try {
        $stmt = $db->prepare("
            UPDATE exams SET 
                exam_code = ?,
                exam_title = ?,
                department_id = ?,
                level_id = ?,
                semester_id = ?,
                session_id = ?,
                exam_date = ?,
                start_time = ?,
                duration = ?,
                question_limit = ?,
                pass_mark = ?,
                has_batches = ?,
                instruction = ?,
                fingerprint_required = ?,
                shuffle_questions = ?,
                shuffle_options = ?,
                show_result = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $exam_code, $exam_title, $department_id, $level_id, $semester_id, $session_id,
            $exam_date, $start_time, $duration, $question_limit, $pass_mark, $has_batches,
            $instruction, $fingerprint_required, $shuffle_questions, $shuffle_options, $show_result,
            $examId
        ]);
        
        logActivity('admin', $_SESSION['admin_id'], 'update_exam', "Updated exam ID: $examId");
        redirect(APP_URL . '/admin/exams.php', 'Exam updated successfully');
    } catch (PDOException $e) {
        error_log("Update exam error: " . $e->getMessage());
        redirect(APP_URL . '/admin/exams.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Get exams with filters
$filters = [];
$params = [];
if (!empty($_GET['status'])) { 
    $filters[] = "e.status = ?"; 
    $params[] = $_GET['status']; 
}
if (!empty($_GET['department'])) { $filters[] = "e.department_id = ?"; $params[] = $_GET['department']; }
if (!empty($_GET['search'])) { $filters[] = "(e.exam_code LIKE ? OR e.exam_title LIKE ?)"; $p = "%{$_GET['search']}%"; $params = array_merge($params, [$p, $p]); }

$where = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

$exams = $db->prepare("SELECT e.*, d.dept_name, l.level_name, sem.semester_name, ses.session_name,
    (SELECT COUNT(*) FROM questions WHERE exam_id = e.id AND status = 1) as question_count,
    (SELECT COUNT(*) FROM exam_students WHERE exam_id = e.id) as assigned_students
    FROM exams e 
    LEFT JOIN departments d ON e.department_id = d.id 
    LEFT JOIN levels l ON e.level_id = l.id 
    LEFT JOIN semesters sem ON e.semester_id = sem.id 
    LEFT JOIN sessions ses ON e.session_id = ses.id 
    $where 
    ORDER BY e.created_at DESC");
$exams->execute($params);
$examsList = $exams->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.exam-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.exam-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
}
.btn-group .btn {
    transition: all 0.2s;
}
.reset-student-result {
    cursor: pointer;
    transition: all 0.2s;
    background: white;
}
.reset-student-result:hover {
    background: #f0f6ff !important;
}
.reset-student-result.selected {
    border-color: #1a73e8 !important;
    background: #e8f0fe !important;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Exams</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createExamModal">
        <i class="bi bi-plus-lg me-1"></i>Create Exam
    </button>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by code or title..." value="<?php echo e($_GET['search'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="draft" <?php echo ($_GET['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo ($_GET['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="running" <?php echo ($_GET['status'] ?? '') === 'running' ? 'selected' : ''; ?>>Running</option>
                    <option value="ended" <?php echo ($_GET['status'] ?? '') === 'ended' ? 'selected' : ''; ?>>Ended</option>
                    <option value="archived" <?php echo ($_GET['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="department" class="form-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($_GET['department'] ?? '') == $d['id'] ? 'selected' : ''; ?>><?php echo e($d['dept_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

<!-- Exams Grid -->
<div class="row g-4">
    <?php foreach ($examsList as $exam): ?>
    <div class="col-lg-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100 exam-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="badge bg-dark"><?php echo e($exam['exam_code']); ?></span>
                <?php echo getExamStatusBadge($exam['status']); ?>
            </div>
            <div class="card-body">
                <h5 class="card-title"><?php echo e($exam['exam_title']); ?></h5>
                <p class="text-muted mb-2">
                    <i class="bi bi-building me-1"></i><?php echo e($exam['dept_name'] ?? 'N/A'); ?><br>
                    <i class="bi bi-layer-forward me-1"></i><?php echo e($exam['level_name'] ?? 'N/A'); ?> | 
                    <i class="bi bi-calendar me-1"></i><?php echo formatDate($exam['exam_date']); ?><br>
                    <i class="bi bi-clock me-1"></i><?php echo date('h:i A', strtotime($exam['start_time'])); ?> | 
                    <?php echo formatDuration($exam['duration']); ?><br>
                    <i class="bi bi-question-circle me-1"></i><?php echo $exam['question_count']; ?> questions | 
                    <i class="bi bi-people me-1"></i><?php echo $exam['assigned_students']; ?> assigned
                </p>
                
                <?php if ($exam['fingerprint_required']): ?>
                    <span class="badge bg-info"><i class="bi bi-fingerprint me-1"></i>Biometric Required</span>
                <?php endif; ?>
                <?php if ($exam['shuffle_questions']): ?>
                    <span class="badge bg-secondary"><i class="bi bi-shuffle me-1"></i>Shuffled</span>
                <?php endif; ?>
                <?php if ($exam['has_batches']): ?>
                    <span class="badge bg-warning text-dark"><i class="bi bi-collection me-1"></i>Has Batches</span>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white border-top-0">
                <div class="btn-group w-100">
                    <a href="questions.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-outline-primary" title="Questions">
                        <i class="bi bi-question-circle"></i>
                    </a>
                    <a href="assign_exam.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-outline-success" title="Assign Students">
                        <i class="bi bi-person-check"></i>
                    </a>
                    <a href="monitoring.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-outline-info" title="Monitor">
                        <i class="bi bi-tv"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-warning" onclick="editExam(<?php echo $exam['id']; ?>)" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="showResetModal(<?php echo $exam['id']; ?>, '<?php echo e($exam['exam_code']); ?>')" title="Reset Exam">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                    
                    <?php if ($exam['status'] === 'draft'): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Publish this exam?')">
                            <input type="hidden" name="action" value="publish">
                            <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-success" title="Publish"><i class="bi bi-check-lg"></i></button>
                        </form>
                    <?php elseif ($exam['status'] === 'published'): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Start this exam now?')">
                            <input type="hidden" name="action" value="start">
                            <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-primary" title="Start"><i class="bi bi-play-fill"></i></button>
                        </form>
                    <?php elseif ($exam['status'] === 'running'): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('End this exam for all students?')">
                            <input type="hidden" name="action" value="end">
                            <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="End"><i class="bi bi-stop-fill"></i></button>
                        </form>
                    <?php elseif ($exam['status'] === 'ended'): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Archive this exam?')">
                            <input type="hidden" name="action" value="archive">
                            <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-secondary" title="Archive"><i class="bi bi-archive"></i></button>
                        </form>
                    <?php elseif ($exam['status'] === 'archived'): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Republish this exam? It will become available for students again.')">
                            <input type="hidden" name="action" value="republish">
                            <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-success" title="Republish"><i class="bi bi-arrow-repeat"></i></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if (empty($examsList)): ?>
    <div class="col-12">
        <div class="text-center py-5 text-muted">
            <i class="bi bi-journal-x fs-1"></i>
            <p class="mt-2">No exams found. Create your first exam!</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Reset Exam Modal -->
<div class="modal fade" id="resetExamModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2 text-warning"></i>Reset Exam</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="exam_id" id="reset_exam_id">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Warning!</strong> Resetting an exam will delete all student attempts and allow students to retake the exam.
                    </div>
                    
                    <ul class="nav nav-tabs mb-3" id="resetTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link active" id="resetAllTab" data-bs-toggle="tab" data-bs-target="#resetAll" role="tab">
                                <i class="bi bi-people-fill me-1"></i> Reset All Students
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link" id="resetSingleTab" data-bs-toggle="tab" data-bs-target="#resetSingle" role="tab">
                                <i class="bi bi-person me-1"></i> Reset Single Student
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <!-- Reset All Students Tab -->
                        <div class="tab-pane fade show active" id="resetAll" role="tabpanel">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                This will reset ALL students assigned to this exam. They will be able to retake the exam from the beginning.
                            </div>
                            <div class="text-center mt-3">
                                <button type="submit" name="reset_action" value="reset_all_students" class="btn btn-danger btn-lg" onclick="return confirm('WARNING: This will reset ALL students for this exam. This action cannot be undone. Continue?')">
                                    <i class="bi bi-exclamation-triangle me-2"></i> Reset All Students for This Exam
                                </button>
                            </div>
                        </div>
                        
                        <!-- Reset Single Student Tab -->
                        <div class="tab-pane fade" id="resetSingle" role="tabpanel">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Search by matric number to find and select a student, then reset their exam attempt.
                            </div>
                            
                            <!-- Search Input -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-search me-1"></i>Search Student by Matric Number
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                    <input type="text" id="reset_matric_search" class="form-control" 
                                           placeholder="Enter matric number (e.g., TSU/FED/CS/20/1009)" 
                                           autocomplete="off">
                                    <button type="button" class="btn btn-primary" onclick="searchStudentsForReset()">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                </div>
                                <small class="text-muted">Type part of the matric number and click Search, or press Enter</small>
                            </div>
                            
                            <!-- Search Results -->
                            <div id="resetSearchResults" class="mb-3" style="max-height: 250px; overflow-y: auto;">
                                <div class="text-center py-3 text-muted small">
                                    <i class="bi bi-info-circle d-block fs-4 mb-2"></i>
                                    Start typing a matric number to search for a student
                                </div>
                            </div>
                            
                            <!-- Selected Student Info -->
                            <div id="resetSelectedStudent" class="d-none mb-3">
                                <div class="card border-primary">
                                    <div class="card-body py-2 px-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong id="selectedStudentMatric" class="text-primary"></strong>
                                                <div class="small text-muted" id="selectedStudentName"></div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelectedStudent()">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="student_id" id="reset_student_id">
                                <input type="hidden" name="assignment_id" id="reset_assignment_id">
                            </div>
                            
                            <div class="text-center mt-3">
                                <button type="submit" name="reset_action" value="reset_single_student" 
                                        id="resetSingleBtn" class="btn btn-warning" disabled
                                        onclick="return confirm('Reset this student? They will be able to retake the exam.')">
                                    <i class="bi bi-arrow-repeat me-2"></i> Reset Selected Student
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Exam Modal -->
<div class="modal fade" id="createExamModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Create New Exam</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createExamForm">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Exam Code *</label>
                            <input type="text" name="exam_code" class="form-control" value="<?php echo generateExamCode(); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Exam Title *</label>
                            <input type="text" name="exam_title" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo e($d['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Level</label>
                            <select name="level_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($levels as $l): ?>
                                    <option value="<?php echo $l['id']; ?>"><?php echo e($l['level_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Semester</label>
                            <select name="semester_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($semesters as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo e($s['semester_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Session</label>
                            <select name="session_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($sessions as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo e($s['session_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Exam Date *</label>
                            <input type="date" name="exam_date" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Start Time *</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Duration (min) *</label>
                            <input type="number" name="duration" class="form-control" value="60" min="5" max="300" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Question Limit</label>
                            <input type="number" name="question_limit" class="form-control" value="50" min="1" max="200">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pass Mark (%)</label>
                            <input type="number" name="pass_mark" class="form-control" value="40" min="0" max="100">
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="has_batches" value="1" id="hasBatches">
                                <label class="form-check-label" for="hasBatches">Has Batches</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Instructions</label>
                            <textarea name="instruction" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="fingerprint_required" value="1" id="fpRequired">
                                <label class="form-check-label" for="fpRequired">Require Biometric Verification</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="shuffle_questions" value="1" id="shuffleQ" checked>
                                <label class="form-check-label" for="shuffleQ">Shuffle Questions</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="shuffle_options" value="1" id="shuffleO" checked>
                                <label class="form-check-label" for="shuffleO">Shuffle Options</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="show_result" value="1" id="showR" checked>
                                <label class="form-check-label" for="showR">Show Result to Students</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create Exam</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Exam Modal -->
<div class="modal fade" id="editExamModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Exam</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editExamForm" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_exam">
                <input type="hidden" name="exam_id" id="edit_exam_id">
                <div class="modal-body" id="editExamBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2">Loading exam data...</p>
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
const APP_URL = '<?php echo APP_URL; ?>';
const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';

// =====================================================
// RESET MODAL
// =====================================================
function showResetModal(examId, examCode) {
    document.getElementById('reset_exam_id').value = examId;
    
    // Clear previous state
    clearSelectedStudent();
    document.getElementById('reset_matric_search').value = '';
    document.getElementById('resetSearchResults').innerHTML = `
        <div class="text-center py-3 text-muted small">
            <i class="bi bi-info-circle d-block fs-4 mb-2"></i>
            Start typing a matric number to search for a student
        </div>
    `;
    
    // Reset to first tab
    const firstTab = document.querySelector('#resetAllTab');
    if (firstTab) {
        const tab = new bootstrap.Tab(firstTab);
        tab.show();
    }
    
    // Store exam ID in a data attribute for the search function
    document.getElementById('resetExamModal').dataset.examId = examId;
    
    new bootstrap.Modal(document.getElementById('resetExamModal')).show();
}

// Search students by matric number
async function searchStudentsForReset() {
    const examId = document.getElementById('resetExamModal').dataset.examId;
    const searchTerm = document.getElementById('reset_matric_search').value.trim();
    const resultsContainer = document.getElementById('resetSearchResults');
    
    if (searchTerm.length < 2) {
        resultsContainer.innerHTML = `
            <div class="alert alert-warning small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Please enter at least 2 characters to search
            </div>
        `;
        return;
    }
    
    resultsContainer.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
            <span class="text-muted">Searching...</span>
        </div>
    `;
    
    try {
        const url = APP_URL + '/ajax/admin/search_exam_students.php?exam_id=' + examId + 
                    '&q=' + encodeURIComponent(searchTerm);
        const response = await fetch(url, {
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const data = await response.json();
        
        if (data.success && data.students && data.students.length > 0) {
            let html = `<div class="small text-muted mb-2">Found ${data.students.length} student(s)</div>`;
            
            data.students.forEach(s => {
                const statusBadge = {
                    'assigned': '<span class="badge bg-info">Assigned</span>',
                    'started': '<span class="badge bg-warning text-dark">Started</span>',
                    'submitted': '<span class="badge bg-success">Submitted</span>',
                    'reset': '<span class="badge bg-secondary">Reset</span>'
                }[s.status] || `<span class="badge bg-secondary">${s.status}</span>`;
                
                const hasAttempt = s.attempt_status ? ' <span class="badge bg-danger">Has Attempt</span>' : '';
                
                html += `
                    <div class="border rounded p-2 mb-2 reset-student-result" 
                         onclick="selectStudentForReset(this, ${s.student_id}, '${escapeHtml(s.matric_number)}', '${escapeHtml(s.last_name + ', ' + s.first_name)}', ${s.assignment_id || 'null'})">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="text-dark">${escapeHtml(s.matric_number)}</strong>
                                <div class="small text-muted">${escapeHtml(s.last_name)}, ${escapeHtml(s.first_name)}</div>
                            </div>
                            <div class="text-end">
                                ${statusBadge}${hasAttempt}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            resultsContainer.innerHTML = html;
        } else {
            resultsContainer.innerHTML = `
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    No students found matching "<strong>${escapeHtml(searchTerm)}</strong>" 
                    assigned to this exam
                </div>
            `;
        }
    } catch (error) {
        console.error('Search error:', error);
        resultsContainer.innerHTML = `
            <div class="alert alert-danger small mb-0">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Error searching students: ${error.message}
            </div>
        `;
    }
}

// Select a student from the search results
function selectStudentForReset(element, studentId, matric, name, assignmentId) {
    document.getElementById('reset_student_id').value = studentId;
    document.getElementById('reset_assignment_id').value = assignmentId || '';
    document.getElementById('selectedStudentMatric').textContent = matric;
    document.getElementById('selectedStudentName').textContent = name;
    document.getElementById('resetSelectedStudent').classList.remove('d-none');
    document.getElementById('resetSingleBtn').disabled = false;
    
    // Highlight the selected item
    document.querySelectorAll('.reset-student-result').forEach(el => {
        el.classList.remove('selected');
    });
    if (element) element.classList.add('selected');
}

// Clear selected student
function clearSelectedStudent() {
    document.getElementById('reset_student_id').value = '';
    document.getElementById('reset_assignment_id').value = '';
    document.getElementById('resetSelectedStudent').classList.add('d-none');
    document.getElementById('resetSingleBtn').disabled = true;
    document.querySelectorAll('.reset-student-result').forEach(el => {
        el.classList.remove('selected');
    });
}

// Handle Enter key in search input
document.getElementById('reset_matric_search')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchStudentsForReset();
    }
});

// =====================================================
// CREATE EXAM
// =====================================================
document.getElementById('createExamForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalHtml = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/create_exam.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('createExamModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred: ' + error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHtml;
    }
});

// =====================================================
// EDIT EXAM
// =====================================================
function editExam(id) {
    const modalEl = document.getElementById('editExamModal');
    const modal = new bootstrap.Modal(modalEl);
    
    document.getElementById('editExamBody').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Loading exam data...</p>
        </div>
    `;
    modal.show();
    
    fetch(APP_URL + '/ajax/admin/get_exam.php?id=' + id, {
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('edit_exam_id').value = id;
            document.getElementById('editExamBody').innerHTML = data.html;
        } else {
            document.getElementById('editExamBody').innerHTML = `
                <div class="alert alert-danger text-center m-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('editExamBody').innerHTML = `
            <div class="alert alert-danger text-center m-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Failed to load exam data: ${error.message}
            </div>
        `;
    });
}

// =====================================================
// UTILITY
// =====================================================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>