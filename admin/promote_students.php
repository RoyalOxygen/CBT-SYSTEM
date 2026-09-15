<?php
/**
 * CBT System - Promote Students
 * Admin can promote students to next level, semester, and session
 * Supports bulk, single, selected, and department-based promotions
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Promote Students';
$db = getDB();

// Get all departments, levels, semesters, sessions for dropdowns
$departments = $db->query("SELECT * FROM departments WHERE status = 1 ORDER BY dept_name")->fetchAll();
$levels = $db->query("SELECT * FROM levels WHERE status = 1 ORDER BY level_order")->fetchAll();
$semesters = $db->query("SELECT * FROM semesters WHERE status = 1 ORDER BY semester_order")->fetchAll();
$sessions = $db->query("SELECT * FROM sessions WHERE status = 1 ORDER BY session_name DESC")->fetchAll();

// Handle promotion action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'promote_students') {
    $csrf_token = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    if (!validateCSRFToken($csrf_token)) {
        redirect(APP_URL . '/admin/promote_students.php', 'Invalid security token', 'error');
    }
    
    $promotion_type = $_POST['promotion_type'] ?? 'selected';
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
    $department_id = intval($_POST['department_id'] ?? 0);
    $level_id = intval($_POST['level_id'] ?? 0);
    $semester_id = intval($_POST['semester_id'] ?? 0);
    $session_id = intval($_POST['session_id'] ?? 0);
    $to_level_id = intval($_POST['to_level_id'] ?? 0);
    $to_semester_id = intval($_POST['to_semester_id'] ?? 0);
    $to_session_id = intval($_POST['to_session_id'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');
    
    try {
        $db->beginTransaction();
        
        $promoted_count = 0;
        $failed_count = 0;
        $student_list = [];
        $errors = [];
        
        // Build the student list based on promotion type
        if ($promotion_type === 'single' && !empty($student_ids)) {
            $student_list = $student_ids;
        } elseif ($promotion_type === 'selected' && !empty($student_ids)) {
            $student_list = $student_ids;
        } elseif ($promotion_type === 'all') {
            $allStmt = $db->prepare("SELECT id FROM students WHERE status = 1");
            $allStmt->execute();
            $student_list = $allStmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($promotion_type === 'department') {
            if ($department_id > 0) {
                $deptStmt = $db->prepare("SELECT id FROM students WHERE department_id = ? AND status = 1");
                $deptStmt->execute([$department_id]);
                $student_list = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
            }
        } elseif ($promotion_type === 'level') {
            if ($level_id > 0) {
                $levelStmt = $db->prepare("SELECT id FROM students WHERE level_id = ? AND status = 1");
                $levelStmt->execute([$level_id]);
                $student_list = $levelStmt->fetchAll(PDO::FETCH_COLUMN);
            }
        } elseif ($promotion_type === 'semester') {
            if ($semester_id > 0) {
                $semesterStmt = $db->prepare("SELECT id FROM students WHERE semester_id = ? AND status = 1");
                $semesterStmt->execute([$semester_id]);
                $student_list = $semesterStmt->fetchAll(PDO::FETCH_COLUMN);
            }
        } elseif ($promotion_type === 'session') {
            if ($session_id > 0) {
                $sessionStmt = $db->prepare("SELECT id FROM students WHERE session_id = ? AND status = 1");
                $sessionStmt->execute([$session_id]);
                $student_list = $sessionStmt->fetchAll(PDO::FETCH_COLUMN);
            }
        } elseif ($promotion_type === 'department_level') {
            if ($department_id > 0 && $level_id > 0) {
                $deptLevelStmt = $db->prepare("SELECT id FROM students WHERE department_id = ? AND level_id = ? AND status = 1");
                $deptLevelStmt->execute([$department_id, $level_id]);
                $student_list = $deptLevelStmt->fetchAll(PDO::FETCH_COLUMN);
            }
        } elseif ($promotion_type === 'department_semester') {
            if ($department_id > 0 && $semester_id > 0) {
                $deptSemesterStmt = $db->prepare("SELECT id FROM students WHERE department_id = ? AND semester_id = ? AND status = 1");
                $deptSemesterStmt->execute([$department_id, $semester_id]);
                $student_list = $deptSemesterStmt->fetchAll(PDO::FETCH_COLUMN);
            }
        } elseif ($promotion_type === 'level_semester') {
            if ($level_id > 0 && $semester_id > 0) {
                $levelSemesterStmt = $db->prepare("SELECT id FROM students WHERE level_id = ? AND semester_id = ? AND status = 1");
                $levelSemesterStmt->execute([$level_id, $semester_id]);
                $student_list = $levelSemesterStmt->fetchAll(PDO::FETCH_COLUMN);
            }
        }
        
        if (empty($student_list)) {
            throw new Exception('No students selected for promotion');
        }
        
        // Get promotion targets info for preview
        $target_level_name = '';
        $target_semester_name = '';
        $target_session_name = '';
        
        if ($to_level_id > 0) {
            $levelNameStmt = $db->prepare("SELECT level_name FROM levels WHERE id = ?");
            $levelNameStmt->execute([$to_level_id]);
            $target_level_name = $levelNameStmt->fetchColumn();
        }
        
        if ($to_semester_id > 0) {
            $semesterNameStmt = $db->prepare("SELECT semester_name FROM semesters WHERE id = ?");
            $semesterNameStmt->execute([$to_semester_id]);
            $target_semester_name = $semesterNameStmt->fetchColumn();
        }
        
        if ($to_session_id > 0) {
            $sessionNameStmt = $db->prepare("SELECT session_name FROM sessions WHERE id = ?");
            $sessionNameStmt->execute([$to_session_id]);
            $target_session_name = $sessionNameStmt->fetchColumn();
        }
        
        // Promote each student
        foreach ($student_list as $student_id) {
            // Get current student details
            $studentStmt = $db->prepare("
                SELECT s.*, l.level_name, sem.semester_name, ses.session_name
                FROM students s
                LEFT JOIN levels l ON s.level_id = l.id
                LEFT JOIN semesters sem ON s.semester_id = sem.id
                LEFT JOIN sessions ses ON s.session_id = ses.id
                WHERE s.id = ?
            ");
            $studentStmt->execute([$student_id]);
            $student = $studentStmt->fetch();
            
            if (!$student) {
                $failed_count++;
                $errors[] = "Student ID $student_id not found";
                continue;
            }
            
            // Determine promotion targets
            $current_level_id = $student['level_id'];
            $current_semester_id = $student['semester_id'];
            $current_session_id = $student['session_id'];
            
            // Get next level, semester, session
            $next_level_id = $to_level_id > 0 ? $to_level_id : getNextLevel($current_level_id, $db);
            $next_semester_id = $to_semester_id > 0 ? $to_semester_id : getNextSemester($current_semester_id, $db);
            $next_session_id = $to_session_id > 0 ? $to_session_id : getNextSession($current_session_id, $db);
            
            // Check if there's any actual change
            $has_change = ($next_level_id != $current_level_id) || 
                          ($next_semester_id != $current_semester_id) || 
                          ($next_session_id != $current_session_id);
            
            if (!$has_change) {
                $failed_count++;
                $errors[] = "Student {$student['matric_number']} - No promotion target found (already at highest level/semester/session)";
                continue;
            }
            
            // Update student
            $updateStmt = $db->prepare("
                UPDATE students SET 
                    level_id = ?,
                    semester_id = ?,
                    session_id = ?,
                    last_promotion_date = NOW(),
                    promotion_status = 'promoted'
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                $next_level_id,
                $next_semester_id,
                $next_session_id,
                $student_id
            ]);
            
            // Log promotion history
            $historyStmt = $db->prepare("
                INSERT INTO promotion_history (
                    student_id,
                    from_level_id,
                    to_level_id,
                    from_semester_id,
                    to_semester_id,
                    from_session_id,
                    to_session_id,
                    promoted_by,
                    promotion_type,
                    notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $historyStmt->execute([
                $student_id,
                $current_level_id,
                $next_level_id,
                $current_semester_id,
                $next_semester_id,
                $current_session_id,
                $next_session_id,
                $_SESSION['admin_id'],
                $promotion_type,
                $notes
            ]);
            
            $promoted_count++;
        }
        
        $db->commit();
        
        logActivity('admin', $_SESSION['admin_id'], 'promote_students', 
                   "Promoted $promoted_count students. Type: $promotion_type");
        
        $message = "$promoted_count students promoted successfully!";
        if ($failed_count > 0) {
            $message .= " $failed_count students failed. Check logs for details.";
        }
        
        redirect(APP_URL . '/admin/promote_students.php?success=1&count=' . $promoted_count . '&failed=' . $failed_count, $message);
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Promotion error: " . $e->getMessage());
        redirect(APP_URL . '/admin/promote_students.php', 'Error: ' . $e->getMessage(), 'error');
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Promotion error: " . $e->getMessage());
        redirect(APP_URL . '/admin/promote_students.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Helper functions
function getNextLevel($current_level_id, $db) {
    $stmt = $db->prepare("
        SELECT id FROM levels 
        WHERE level_order > (SELECT level_order FROM levels WHERE id = ?)
        ORDER BY level_order ASC LIMIT 1
    ");
    $stmt->execute([$current_level_id]);
    $result = $stmt->fetch();
    return $result ? $result['id'] : $current_level_id;
}

function getNextSemester($current_semester_id, $db) {
    $stmt = $db->prepare("
        SELECT id FROM semesters 
        WHERE semester_order > (SELECT semester_order FROM semesters WHERE id = ?)
        ORDER BY semester_order ASC LIMIT 1
    ");
    $stmt->execute([$current_semester_id]);
    $result = $stmt->fetch();
    return $result ? $result['id'] : $current_semester_id;
}

function getNextSession($current_session_id, $db) {
    $stmt = $db->prepare("
        SELECT id FROM sessions 
        WHERE id > ? AND status = 1
        ORDER BY session_name ASC LIMIT 1
    ");
    $stmt->execute([$current_session_id]);
    $result = $stmt->fetch();
    return $result ? $result['id'] : $current_session_id;
}

// Get existing students for display
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filter_department = isset($_GET['department']) ? intval($_GET['department']) : 0;
$filter_level = isset($_GET['level']) ? intval($_GET['level']) : 0;
$filter_semester = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
$filter_session = isset($_GET['session']) ? intval($_GET['session']) : 0;

$students = [];
$sql = "
    SELECT s.*, d.dept_name, l.level_name, sem.semester_name, ses.session_name
    FROM students s
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN levels l ON s.level_id = l.id
    LEFT JOIN semesters sem ON s.semester_id = sem.id
    LEFT JOIN sessions ses ON s.session_id = ses.id
    WHERE s.status = 1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (s.matric_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($filter_department > 0) {
    $sql .= " AND s.department_id = ?";
    $params[] = $filter_department;
}

if ($filter_level > 0) {
    $sql .= " AND s.level_id = ?";
    $params[] = $filter_level;
}

if ($filter_semester > 0) {
    $sql .= " AND s.semester_id = ?";
    $params[] = $filter_semester;
}

if ($filter_session > 0) {
    $sql .= " AND s.session_id = ?";
    $params[] = $filter_session;
}

$sql .= " ORDER BY s.last_name, s.first_name LIMIT 500";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get statistics
$total_students = count($students);
$selected_count = 0;

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.promotion-card {
    border-left: 4px solid #667eea;
    transition: all 0.2s;
}
.promotion-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.promotion-type-card {
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    background: white;
    height: 100%;
}
.promotion-type-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102,126,234,0.15);
}
.promotion-type-card.active {
    border-color: #667eea;
    background: #f0f3ff;
    box-shadow: 0 4px 15px rgba(102,126,234,0.2);
}
.promotion-type-card i {
    font-size: 2.5rem;
}
.student-checkbox:checked + label {
    font-weight: bold;
    color: #667eea;
}
.preview-box {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 20px;
    border-left: 4px solid #667eea;
}
.stats-badge {
    font-size: 0.8rem;
    padding: 5px 12px;
    border-radius: 20px;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-arrow-up-circle me-2 text-primary"></i>Promote Students</h4>
    <div>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show m-0 py-2" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> 
                <?php 
                $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
                $failed = isset($_GET['failed']) ? intval($_GET['failed']) : 0;
                echo $count . ' students promoted successfully!';
                if ($failed > 0) {
                    echo ' <span class="text-danger">(' . $failed . ' failed)</span>';
                }
                ?>
                <button type="button" class="btn-close small" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-primary mb-0"><?php echo $total_students; ?></h3>
                <small class="text-muted">Total Students</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-success mb-0" id="selectedCountDisplay">0</h3>
                <small class="text-muted">Selected</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-info mb-0"><?php echo count($levels); ?></h3>
                <small class="text-muted">Levels</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-warning mb-0"><?php echo count($semesters); ?></h3>
                <small class="text-muted">Semesters</small>
            </div>
        </div>
    </div>
</div>

<!-- Promotion Type Selection -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-options me-2"></i>Select Promotion Type</h6>
                <span class="badge bg-info">Select one option</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="promotion-type-card p-3 rounded text-center active" onclick="setPromotionType('selected')" id="type_selected">
                            <i class="bi bi-check2-square text-primary"></i>
                            <h6 class="mt-2 mb-0">Selected Students</h6>
                            <small class="text-muted">Pick individual students</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="promotion-type-card p-3 rounded text-center" onclick="setPromotionType('all')" id="type_all">
                            <i class="bi bi-people-fill text-success"></i>
                            <h6 class="mt-2 mb-0">All Students</h6>
                            <small class="text-muted">Promote everyone</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="promotion-type-card p-3 rounded text-center" onclick="setPromotionType('department')" id="type_department">
                            <i class="bi bi-building text-info"></i>
                            <h6 class="mt-2 mb-0">By Department</h6>
                            <small class="text-muted">Select a department</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="promotion-type-card p-3 rounded text-center" onclick="setPromotionType('level')" id="type_level">
                            <i class="bi bi-layer-forward text-warning"></i>
                            <h6 class="mt-2 mb-0">By Level</h6>
                            <small class="text-muted">Select a level</small>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <div class="promotion-type-card p-3 rounded text-center" onclick="setPromotionType('semester')" id="type_semester">
                            <i class="bi bi-calendar-week text-secondary"></i>
                            <h6 class="mt-2 mb-0">By Semester</h6>
                            <small class="text-muted">Select a semester</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="promotion-type-card p-3 rounded text-center" onclick="setPromotionType('session')" id="type_session">
                            <i class="bi bi-calendar3 text-dark"></i>
                            <h6 class="mt-2 mb-0">By Session</h6>
                            <small class="text-muted">Select a session</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="promotion-type-card p-3 rounded text-center" onclick="setPromotionType('department_level')" id="type_department_level">
                            <i class="bi bi-layers text-purple" style="color:#6f42c1;"></i>
                            <h6 class="mt-2 mb-0">Dept + Level</h6>
                            <small class="text-muted">Department & Level</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="promotion-type-card p-3 rounded text-center" onclick="setPromotionType('single')" id="type_single">
                            <i class="bi bi-person text-primary"></i>
                            <h6 class="mt-2 mb-0">Single Student</h6>
                            <small class="text-muted">One student</small>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <div class="promotion-type-card p-3 rounded text-center" onclick="setPromotionType('department_semester')" id="type_department_semester">
                            <i class="bi bi-building-add text-teal" style="color:#20c997;"></i>
                            <h6 class="mt-2 mb-0">Dept + Semester</h6>
                            <small class="text-muted">Department & Semester</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="promotion-type-card p-3 rounded text-center" onclick="setPromotionType('level_semester')" id="type_level_semester">
                            <i class="bi bi-layers-half text-pink" style="color:#d63384;"></i>
                            <h6 class="mt-2 mb-0">Level + Semester</h6>
                            <small class="text-muted">Level & Semester</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Promotion Form -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-arrow-up-short me-2"></i>Promotion Form</h6>
        <span class="badge bg-secondary">Step 1: Select Students & Step 2: Choose Targets</span>
    </div>
    <div class="card-body">
        <form method="POST" action="" id="promotionForm">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="promote_students">
            <input type="hidden" name="promotion_type" id="promotion_type" value="selected">

            <div class="row g-3">
                <!-- Filter/Search Section -->
                <div class="col-12">
                    <div class="bg-light p-3 rounded">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Search by name or matric..." 
                                       value="<?php echo e($search); ?>" id="searchInput">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Department</label>
                                <select name="department" class="form-select" id="filterDepartment">
                                    <option value="0">All</option>
                                    <?php foreach ($departments as $d): ?>
                                        <option value="<?php echo $d['id']; ?>" <?php echo $filter_department == $d['id'] ? 'selected' : ''; ?>>
                                            <?php echo e($d['dept_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Level</label>
                                <select name="level" class="form-select" id="filterLevel">
                                    <option value="0">All</option>
                                    <?php foreach ($levels as $l): ?>
                                        <option value="<?php echo $l['id']; ?>" <?php echo $filter_level == $l['id'] ? 'selected' : ''; ?>>
                                            <?php echo e($l['level_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Semester</label>
                                <select name="semester" class="form-select" id="filterSemester">
                                    <option value="0">All</option>
                                    <?php foreach ($semesters as $s): ?>
                                        <option value="<?php echo $s['id']; ?>" <?php echo $filter_semester == $s['id'] ? 'selected' : ''; ?>>
                                            <?php echo e($s['semester_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Session</label>
                                <select name="session" class="form-select" id="filterSession">
                                    <option value="0">All</option>
                                    <?php foreach ($sessions as $s): ?>
                                        <option value="<?php echo $s['id']; ?>" <?php echo $filter_session == $s['id'] ? 'selected' : ''; ?>>
                                            <?php echo e($s['session_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-primary w-100" onclick="filterStudents()">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Promotion Targets -->
                <div class="col-md-6">
                    <div class="card bg-light border-0 h-100">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="bi bi-arrow-right-circle me-2 text-primary"></i>Promotion Targets</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Level</label>
                                    <select name="level_id" class="form-select form-select-sm" id="currentLevel">
                                        <option value="0">All Levels</option>
                                        <?php foreach ($levels as $l): ?>
                                            <option value="<?php echo $l['id']; ?>"><?php echo e($l['level_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Promote To</label>
                                    <select name="to_level_id" class="form-select form-select-sm" id="targetLevel">
                                        <option value="0">Auto Next Level</option>
                                        <?php foreach ($levels as $l): ?>
                                            <option value="<?php echo $l['id']; ?>"><?php echo e($l['level_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Semester</label>
                                    <select name="semester_id" class="form-select form-select-sm" id="currentSemester">
                                        <option value="0">All Semesters</option>
                                        <?php foreach ($semesters as $s): ?>
                                            <option value="<?php echo $s['id']; ?>"><?php echo e($s['semester_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Promote To</label>
                                    <select name="to_semester_id" class="form-select form-select-sm" id="targetSemester">
                                        <option value="0">Auto Next Semester</option>
                                        <?php foreach ($semesters as $s): ?>
                                            <option value="<?php echo $s['id']; ?>"><?php echo e($s['semester_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Session</label>
                                    <select name="session_id" class="form-select form-select-sm" id="currentSession">
                                        <option value="0">All Sessions</option>
                                        <?php foreach ($sessions as $s): ?>
                                            <option value="<?php echo $s['id']; ?>"><?php echo e($s['session_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Promote To</label>
                                    <select name="to_session_id" class="form-select form-select-sm" id="targetSession">
                                        <option value="0">Auto Next Session</option>
                                        <?php foreach ($sessions as $s): ?>
                                            <option value="<?php echo $s['id']; ?>"><?php echo e($s['session_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-2 text-muted small">
                                <i class="bi bi-info-circle me-1"></i>
                                <span id="targetPreview">Auto: Next Level, Next Semester, Next Session</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Options (shown based on promotion type) -->
                <div class="col-md-6" id="filterOptionsContainer">
                    <div class="card bg-light border-0 h-100">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="bi bi-funnel me-2 text-warning"></i>Filter Options</h6>
                            <div id="dynamicFilters">
                                <div class="row g-2">
                                    <div class="col-12" id="departmentFilterDiv">
                                        <label class="form-label small fw-bold">Department</label>
                                        <select name="department_id" class="form-select" id="promoteDepartment">
                                            <option value="0">Select Department</option>
                                            <?php foreach ($departments as $d): ?>
                                                <option value="<?php echo $d['id']; ?>"><?php echo e($d['dept_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12" id="levelFilterDiv" style="display:none;">
                                        <label class="form-label small fw-bold">Level</label>
                                        <select name="level_id_filter" class="form-select" id="promoteLevel">
                                            <option value="0">Select Level</option>
                                            <?php foreach ($levels as $l): ?>
                                                <option value="<?php echo $l['id']; ?>"><?php echo e($l['level_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12" id="semesterFilterDiv" style="display:none;">
                                        <label class="form-label small fw-bold">Semester</label>
                                        <select name="semester_id_filter" class="form-select" id="promoteSemester">
                                            <option value="0">Select Semester</option>
                                            <?php foreach ($semesters as $s): ?>
                                                <option value="<?php echo $s['id']; ?>"><?php echo e($s['semester_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12" id="sessionFilterDiv" style="display:none;">
                                        <label class="form-label small fw-bold">Session</label>
                                        <select name="session_id_filter" class="form-select" id="promoteSession">
                                            <option value="0">Select Session</option>
                                            <?php foreach ($sessions as $s): ?>
                                                <option value="<?php echo $s['id']; ?>"><?php echo e($s['session_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div id="filterHelpText" class="mt-2 text-muted small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Select the criteria above to filter students
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student List -->
                <div class="col-12">
                    <div class="card border-0">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-people me-2"></i>Student List</h6>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                                    <i class="bi bi-check-all me-1"></i> Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                                    <i class="bi bi-x-circle me-1"></i> Deselect All
                                </button>
                                <span class="badge bg-primary stats-badge" id="selectedCount">0 selected</span>
                                <span class="badge bg-secondary stats-badge"><?php echo $total_students; ?> total</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th width="40">
                                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleAllStudents()">
                                            </th>
                                            <th>Matric</th>
                                            <th>Name</th>
                                            <th>Department</th>
                                            <th>Level</th>
                                            <th>Semester</th>
                                            <th>Session</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($students)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">
                                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                                    No students found matching the filters
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>" 
                                                               class="student-checkbox" onchange="updateSelectedCount()">
                                                    </td>
                                                    <td><strong><?php echo e($student['matric_number']); ?></strong></td>
                                                    <td><?php echo e($student['last_name'] . ', ' . $student['first_name']); ?></td>
                                                    <td><?php echo e($student['dept_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo e($student['level_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo e($student['semester_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo e($student['session_name'] ?? 'N/A'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="col-12">
                    <label class="form-label fw-bold"><i class="bi bi-pencil me-2"></i>Promotion Notes (Optional)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Add notes about this promotion (e.g., reason, batch, special conditions)..."></textarea>
                </div>

                <!-- Submit -->
                <div class="col-12">
                    <div class="preview-box">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div id="promotionPreview">
                                    <i class="bi bi-info-circle me-1 text-primary"></i>
                                    <span id="previewText">Select students and promotion targets above</span>
                                </div>
                                <div class="mt-2" id="previewDetails" style="font-size: 0.85rem; color: #6c757d;">
                                    <span id="previewCount">0 students selected</span>
                                    <span class="mx-2">|</span>
                                    <span id="previewTarget">Target: Not set</span>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="submit" class="btn btn-success btn-lg w-100" id="promoteBtn" onclick="return confirmPromotion()">
                                    <i class="bi bi-arrow-up me-2"></i> Promote Students
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Promotion History -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Promotion History</h6>
        <span class="badge bg-secondary">Last 50 records</span>
    </div>
    <div class="card-body p-0">
        <?php
        $historyStmt = $db->query("
            SELECT ph.*, s.matric_number, s.first_name, s.last_name,
                   a.full_name as admin_name,
                   fl.level_name as from_level,
                   tl.level_name as to_level,
                   fs.semester_name as from_semester,
                   ts.semester_name as to_semester,
                   fss.session_name as from_session,
                   tss.session_name as to_session
            FROM promotion_history ph
            JOIN students s ON ph.student_id = s.id
            JOIN admins a ON ph.promoted_by = a.id
            LEFT JOIN levels fl ON ph.from_level_id = fl.id
            LEFT JOIN levels tl ON ph.to_level_id = tl.id
            LEFT JOIN semesters fs ON ph.from_semester_id = fs.id
            LEFT JOIN semesters ts ON ph.to_semester_id = ts.id
            LEFT JOIN sessions fss ON ph.from_session_id = fss.id
            LEFT JOIN sessions tss ON ph.to_session_id = tss.id
            ORDER BY ph.promoted_at DESC
            LIMIT 50
        ");
        $history = $historyStmt->fetchAll();
        ?>
        
        <?php if (empty($history)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-clock fs-3 d-block mb-2"></i>
                No promotion history yet
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Promotion Changes</th>
                            <th>Type</th>
                            <th>Promoted By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($item['last_name'] . ', ' . $item['first_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo e($item['matric_number']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $changes = [];
                                    if ($item['from_level'] && $item['to_level'] && $item['from_level'] != $item['to_level']) {
                                        $changes[] = '<span class="text-primary">' . $item['from_level'] . '</span> → <span class="text-success">' . $item['to_level'] . '</span>';
                                    }
                                    if ($item['from_semester'] && $item['to_semester'] && $item['from_semester'] != $item['to_semester']) {
                                        $changes[] = '<span class="text-primary">' . $item['from_semester'] . '</span> → <span class="text-success">' . $item['to_semester'] . '</span>';
                                    }
                                    if ($item['from_session'] && $item['to_session'] && $item['from_session'] != $item['to_session']) {
                                        $changes[] = '<span class="text-primary">' . $item['from_session'] . '</span> → <span class="text-success">' . $item['to_session'] . '</span>';
                                    }
                                    echo !empty($changes) ? implode(', ', $changes) : '<span class="text-muted">No changes</span>';
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $item['promotion_type'] == 'all' ? 'success' : ($item['promotion_type'] == 'department' ? 'info' : ($item['promotion_type'] == 'single' ? 'primary' : 'secondary')); ?>">
                                        <?php echo ucfirst($item['promotion_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo e($item['admin_name']); ?></td>
                                <td><?php echo formatDateTime($item['promoted_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Promotion type management
function setPromotionType(type) {
    document.getElementById('promotion_type').value = type;
    
    // Update active state
    document.querySelectorAll('.promotion-type-card').forEach(el => {
        el.classList.remove('active');
    });
    const target = document.getElementById('type_' + type);
    if (target) target.classList.add('active');
    
    // Show/hide filter options based on type
    const departmentDiv = document.getElementById('departmentFilterDiv');
    const levelDiv = document.getElementById('levelFilterDiv');
    const semesterDiv = document.getElementById('semesterFilterDiv');
    const sessionDiv = document.getElementById('sessionFilterDiv');
    const helpText = document.getElementById('filterHelpText');
    
    departmentDiv.style.display = 'none';
    levelDiv.style.display = 'none';
    semesterDiv.style.display = 'none';
    sessionDiv.style.display = 'none';
    
    let showHelp = false;
    
    if (type === 'department') {
        departmentDiv.style.display = 'block';
        document.getElementById('promoteDepartment').required = true;
        showHelp = true;
    } else if (type === 'level') {
        levelDiv.style.display = 'block';
        showHelp = true;
    } else if (type === 'semester') {
        semesterDiv.style.display = 'block';
        showHelp = true;
    } else if (type === 'session') {
        sessionDiv.style.display = 'block';
        showHelp = true;
    } else if (type === 'department_level') {
        departmentDiv.style.display = 'block';
        levelDiv.style.display = 'block';
        showHelp = true;
    } else if (type === 'department_semester') {
        departmentDiv.style.display = 'block';
        semesterDiv.style.display = 'block';
        showHelp = true;
    } else if (type === 'level_semester') {
        levelDiv.style.display = 'block';
        semesterDiv.style.display = 'block';
        showHelp = true;
    } else if (type === 'single') {
        showHelp = false;
    } else if (type === 'selected') {
        showHelp = false;
    } else if (type === 'all') {
        showHelp = false;
    }
    
    if (helpText) {
        helpText.style.display = showHelp ? 'block' : 'none';
        if (showHelp) {
            helpText.innerHTML = '<i class="bi bi-info-circle me-1"></i> Students matching the selected criteria will be promoted';
        }
    }
    
    // Update preview
    updatePreview();
}

// Update selected count
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.student-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count + ' selected';
    document.getElementById('selectedCountDisplay').textContent = count;
    document.getElementById('previewCount').textContent = count + ' students selected';
    
    // Update select all checkbox
    const allCheckboxes = document.querySelectorAll('.student-checkbox');
    const selectAll = document.getElementById('selectAllCheckbox');
    if (selectAll) {
        if (allCheckboxes.length > 0) {
            selectAll.checked = count === allCheckboxes.length;
        }
    }
    
    updatePreview();
}

// Toggle all students
function toggleAllStudents() {
    const checked = document.getElementById('selectAllCheckbox').checked;
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = checked);
    updateSelectedCount();
}

// Select all / Deselect all
function selectAll() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = true);
    document.getElementById('selectAllCheckbox').checked = true;
    updateSelectedCount();
}

function deselectAll() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAllCheckbox').checked = false;
    updateSelectedCount();
}

// Filter students
function filterStudents() {
    const search = document.getElementById('searchInput').value;
    const department = document.getElementById('filterDepartment').value;
    const level = document.getElementById('filterLevel').value;
    const semester = document.getElementById('filterSemester').value;
    const session = document.getElementById('filterSession').value;
    window.location.href = APP_URL + '/admin/promote_students.php?search=' + encodeURIComponent(search) + 
                          '&department=' + department + '&level=' + level + '&semester=' + semester + '&session=' + session;
}

// Update promotion preview
function updatePreview() {
    const type = document.getElementById('promotion_type').value;
    const count = document.querySelectorAll('.student-checkbox:checked').length;
    const targetLevel = document.getElementById('targetLevel');
    const targetSemester = document.getElementById('targetSemester');
    const targetSession = document.getElementById('targetSession');
    
    let previewText = '';
    let typeLabel = '';
    
    const typeLabels = {
        'all': 'All Students',
        'selected': count + ' Selected Students',
        'single': 'Single Student',
        'department': 'Department Filter',
        'level': 'Level Filter',
        'semester': 'Semester Filter',
        'session': 'Session Filter',
        'department_level': 'Department + Level Filter',
        'department_semester': 'Department + Semester Filter',
        'level_semester': 'Level + Semester Filter'
    };
    typeLabel = typeLabels[type] || 'Selected Students';
    
    let targetLabel = '';
    if (targetLevel.value > 0) {
        targetLabel += ' to Level ' + targetLevel.options[targetLevel.selectedIndex].text;
    } else {
        targetLabel += ' to Next Level';
    }
    
    if (targetSemester.value > 0) {
        targetLabel += ', Semester ' + targetSemester.options[targetSemester.selectedIndex].text;
    } else {
        targetLabel += ', Next Semester';
    }
    
    if (targetSession.value > 0) {
        targetLabel += ', Session ' + targetSession.options[targetSession.selectedIndex].text;
    } else {
        targetLabel += ', Next Session';
    }
    
    previewText = typeLabel + ' will be promoted ' + targetLabel;
    document.getElementById('previewText').textContent = previewText;
    document.getElementById('previewTarget').textContent = 'Target: ' + targetLabel.replace(' to ', '');
}

// Confirm promotion
function confirmPromotion() {
    const type = document.getElementById('promotion_type').value;
    const count = document.querySelectorAll('.student-checkbox:checked').length;
    
    if (type === 'selected' && count === 0) {
        alert('Please select at least one student to promote.');
        return false;
    }
    
    if (type === 'single' && count !== 1) {
        alert('Please select exactly one student for single promotion.');
        return false;
    }
    
    // Validate filter selections for specific types
    const filterValidations = {
        'department': { field: 'promoteDepartment', label: 'department' },
        'level': { field: 'promoteLevel', label: 'level' },
        'semester': { field: 'promoteSemester', label: 'semester' },
        'session': { field: 'promoteSession', label: 'session' },
        'department_level': { fields: ['promoteDepartment', 'promoteLevel'], labels: ['department', 'level'] },
        'department_semester': { fields: ['promoteDepartment', 'promoteSemester'], labels: ['department', 'semester'] },
        'level_semester': { fields: ['promoteLevel', 'promoteSemester'], labels: ['level', 'semester'] }
    };
    
    const validation = filterValidations[type];
    if (validation) {
        if (validation.field) {
            const val = document.getElementById(validation.field).value;
            if (val == 0) {
                alert('Please select a ' + validation.label + '.');
                return false;
            }
        } else if (validation.fields) {
            for (let i = 0; i < validation.fields.length; i++) {
                const val = document.getElementById(validation.fields[i]).value;
                if (val == 0) {
                    alert('Please select a ' + validation.labels[i] + '.');
                    return false;
                }
            }
        }
    }
    
    // Build confirmation message
    let message = '⚠️ PROMOTION CONFIRMATION\n\n';
    message += 'You are about to promote ';
    
    if (type === 'selected') {
        message += count + ' selected students';
    } else if (type === 'all') {
        message += 'ALL students';
    } else if (type === 'single') {
        message += '1 student';
    } else if (type === 'department') {
        message += 'students in the selected department';
    } else if (type === 'level') {
        message += 'students in the selected level';
    } else if (type === 'semester') {
        message += 'students in the selected semester';
    } else if (type === 'session') {
        message += 'students in the selected session';
    } else if (type === 'department_level') {
        message += 'students in the selected department and level';
    } else if (type === 'department_semester') {
        message += 'students in the selected department and semester';
    } else if (type === 'level_semester') {
        message += 'students in the selected level and semester';
    } else {
        message += 'the selected students';
    }
    
    // Add promotion targets
    const targetLevel = document.getElementById('targetLevel');
    const targetSemester = document.getElementById('targetSemester');
    const targetSession = document.getElementById('targetSession');
    
    message += '\n\nTo the following:';
    message += '\n• Level: ' + (targetLevel.value > 0 ? targetLevel.options[targetLevel.selectedIndex].text : 'Next Level');
    message += '\n• Semester: ' + (targetSemester.value > 0 ? targetSemester.options[targetSemester.selectedIndex].text : 'Next Semester');
    message += '\n• Session: ' + (targetSession.value > 0 ? targetSession.options[targetSession.selectedIndex].text : 'Next Session');
    
    message += '\n\nThis action cannot be undone. Continue?';
    
    return confirm(message);
}

// Attach event listeners for real-time preview
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.student-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });
    
    const previewElements = ['targetLevel', 'targetSemester', 'targetSession'];
    previewElements.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', updatePreview);
    });
    
    const filterElements = ['promoteDepartment', 'promoteLevel', 'promoteSemester', 'promoteSession'];
    filterElements.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', updatePreview);
    });
    
    updateSelectedCount();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>