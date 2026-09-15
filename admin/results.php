<?php
/**
 * CBT System - Results & Analytics
 * Includes both regular and composite exams
 * Supports Excel, CSV, and PDF exports
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Results & Analytics';
$db = getDB();

// =====================================================
// HANDLE DELETE ACTIONS (both types)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_action'])) {
    $deleteAction = $_POST['delete_action'];
    $resultType = $_POST['result_type'] ?? 'regular';
    $csrf_token = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    if (!validateCSRFToken($csrf_token)) {
        redirect(APP_URL . '/admin/results.php', 'Invalid security token', 'error');
    }
    
    try {
        switch ($deleteAction) {
            case 'delete_single':
                $resultId = intval($_POST['result_id']);
                
                if ($resultType === 'composite') {
                    $stmt = $db->prepare("SELECT r.*, ce.exam_code, s.matric_number FROM composite_exam_results r 
                                          JOIN composite_exams ce ON r.composite_exam_id = ce.id 
                                          JOIN students s ON r.student_id = s.id 
                                          WHERE r.id = ?");
                    $stmt->execute([$resultId]);
                    $result = $stmt->fetch();
                    
                    if ($result) {
                        $db->prepare("DELETE FROM composite_exam_results WHERE id = ?")->execute([$resultId]);
                        logActivity('admin', $_SESSION['admin_id'], 'delete_single_composite_result', 
                                   "Deleted composite result for student: {$result['matric_number']} on exam: {$result['exam_code']}");
                        redirect(APP_URL . '/admin/results.php', 'Composite result deleted successfully');
                    }
                } else {
                    $stmt = $db->prepare("SELECT r.*, e.exam_code, s.matric_number FROM results r 
                                          JOIN exams e ON r.exam_id = e.id 
                                          JOIN students s ON r.student_id = s.id 
                                          WHERE r.id = ?");
                    $stmt->execute([$resultId]);
                    $result = $stmt->fetch();
                    
                    if ($result) {
                        $db->prepare("DELETE FROM results WHERE id = ?")->execute([$resultId]);
                        logActivity('admin', $_SESSION['admin_id'], 'delete_single_result', 
                                   "Deleted result for student: {$result['matric_number']} on exam: {$result['exam_code']}");
                        redirect(APP_URL . '/admin/results.php', 'Result deleted successfully');
                    }
                }
                redirect(APP_URL . '/admin/results.php', 'Result not found', 'error');
                break;
                
            case 'delete_exam_results':
                $examId = intval($_POST['exam_id']);
                $examType = $_POST['exam_type'] ?? 'regular';
                
                if ($examType === 'composite') {
                    $stmt = $db->prepare("SELECT exam_code, exam_title FROM composite_exams WHERE id = ?");
                    $stmt->execute([$examId]);
                    $exam = $stmt->fetch();
                    
                    if ($exam) {
                        $countStmt = $db->prepare("SELECT COUNT(*) FROM composite_exam_results WHERE composite_exam_id = ?");
                        $countStmt->execute([$examId]);
                        $count = $countStmt->fetchColumn();
                        
                        $db->prepare("DELETE FROM composite_exam_results WHERE composite_exam_id = ?")->execute([$examId]);
                        logActivity('admin', $_SESSION['admin_id'], 'delete_composite_exam_results', 
                                   "Deleted $count composite results for exam: {$exam['exam_code']} - {$exam['exam_title']}");
                        redirect(APP_URL . '/admin/results.php', "$count composite results deleted successfully for exam: {$exam['exam_code']}");
                    }
                } else {
                    $stmt = $db->prepare("SELECT exam_code, exam_title FROM exams WHERE id = ?");
                    $stmt->execute([$examId]);
                    $exam = $stmt->fetch();
                    
                    if ($exam) {
                        $countStmt = $db->prepare("SELECT COUNT(*) FROM results WHERE exam_id = ?");
                        $countStmt->execute([$examId]);
                        $count = $countStmt->fetchColumn();
                        
                        $db->prepare("DELETE FROM results WHERE exam_id = ?")->execute([$examId]);
                        logActivity('admin', $_SESSION['admin_id'], 'delete_exam_results', 
                                   "Deleted $count results for exam: {$exam['exam_code']} - {$exam['exam_title']}");
                        redirect(APP_URL . '/admin/results.php', "$count results deleted successfully for exam: {$exam['exam_code']}");
                    }
                }
                redirect(APP_URL . '/admin/results.php', 'Exam not found', 'error');
                break;
                
            case 'delete_all_regular':
                $countStmt = $db->query("SELECT COUNT(*) FROM results");
                $count = $countStmt->fetchColumn();
                $db->exec("DELETE FROM results");
                logActivity('admin', $_SESSION['admin_id'], 'delete_all_results', 
                           "Deleted all $count regular results from the system");
                redirect(APP_URL . '/admin/results.php', "All $count regular results have been deleted successfully");
                break;
                
            case 'delete_all_composite':
                $countStmt = $db->query("SELECT COUNT(*) FROM composite_exam_results");
                $count = $countStmt->fetchColumn();
                $db->exec("DELETE FROM composite_exam_results");
                logActivity('admin', $_SESSION['admin_id'], 'delete_all_composite_results', 
                           "Deleted all $count composite results from the system");
                redirect(APP_URL . '/admin/results.php', "All $count composite results have been deleted successfully");
                break;
                
            case 'delete_all_results':
                $countRegular = $db->query("SELECT COUNT(*) FROM results")->fetchColumn();
                $countComposite = $db->query("SELECT COUNT(*) FROM composite_exam_results")->fetchColumn();
                
                $db->exec("DELETE FROM results");
                $db->exec("DELETE FROM composite_exam_results");
                
                logActivity('admin', $_SESSION['admin_id'], 'delete_all_results', 
                           "Deleted all $countRegular regular and $countComposite composite results");
                redirect(APP_URL . '/admin/results.php', "All results deleted: $countRegular regular, $countComposite composite");
                break;
        }
    } catch (PDOException $e) {
        error_log("Delete results error: " . $e->getMessage());
        redirect(APP_URL . '/admin/results.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// =====================================================
// LOAD FILTER OPTIONS
// =====================================================
$regularExams = $db->query("SELECT id, exam_code, exam_title FROM exams ORDER BY created_at DESC")->fetchAll();
$compositeExams = $db->query("SELECT id, exam_code, exam_title FROM composite_exams ORDER BY created_at DESC")->fetchAll();
$departments = $db->query("SELECT * FROM departments WHERE status = 1 ORDER BY dept_name")->fetchAll();

// =====================================================
// FILTERS
// =====================================================
$selectedExam = intval($_GET['exam_id'] ?? 0);
$selectedExamType = $_GET['exam_type'] ?? '';
$filterPublished = $_GET['published'] ?? '';
$resultType = $_GET['result_type'] ?? 'all';

// =====================================================
// GET REGULAR RESULTS
// =====================================================
$regularResults = [];
$totalRegularResults = 0;

if ($resultType === 'all' || $resultType === 'regular') {
    $where = ['1=1'];
    $params = [];
    
    if ($selectedExam && $selectedExamType !== 'composite') { 
        $where[] = "r.exam_id = ?"; 
        $params[] = $selectedExam; 
    }
    if ($filterPublished !== '') { 
        $where[] = "r.published = ?"; 
        $params[] = $filterPublished; 
    }
    
    $whereStr = implode(' AND ', $where);
    
    $countStmt = $db->prepare("SELECT COUNT(*) FROM results r WHERE $whereStr");
    $countStmt->execute($params);
    $totalRegularResults = $countStmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT r.*, e.exam_code, e.exam_title, e.duration, d.dept_name,
        s.matric_number, s.first_name, s.last_name, s.other_name, s.photo, l.level_name,
        'regular' as result_source
        FROM results r
        JOIN exams e ON r.exam_id = e.id
        JOIN students s ON r.student_id = s.id
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN levels l ON e.level_id = l.id
        WHERE $whereStr
        ORDER BY r.id DESC
        LIMIT 500");
    $stmt->execute($params);
    $regularResults = $stmt->fetchAll();
}

// =====================================================
// GET COMPOSITE RESULTS
// =====================================================
$compositeResults = [];
$totalCompositeResults = 0;

if ($resultType === 'all' || $resultType === 'composite') {
    $where = ['1=1'];
    $params = [];
    
    if ($selectedExam && $selectedExamType === 'composite') { 
        $where[] = "r.composite_exam_id = ?"; 
        $params[] = $selectedExam; 
    }
    if ($filterPublished !== '') { 
        $where[] = "r.published = ?"; 
        $params[] = $filterPublished; 
    }
    
    $whereStr = implode(' AND ', $where);
    
    $countStmt = $db->prepare("SELECT COUNT(*) FROM composite_exam_results r WHERE $whereStr");
    $countStmt->execute($params);
    $totalCompositeResults = $countStmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT 
        r.*, 
        r.composite_exam_id as exam_id,
        ce.exam_code, ce.exam_title, ce.duration, d.dept_name,
        s.matric_number, s.first_name, s.last_name, s.other_name, s.photo, l.level_name,
        'composite' as result_source
        FROM composite_exam_results r
        JOIN composite_exams ce ON r.composite_exam_id = ce.id
        JOIN students s ON r.student_id = s.id
        LEFT JOIN departments d ON ce.department_id = d.id
        LEFT JOIN levels l ON ce.level_id = l.id
        WHERE $whereStr
        ORDER BY r.id DESC
        LIMIT 500");
    $stmt->execute($params);
    $compositeResults = $stmt->fetchAll();
}

// Merge and sort
$allResults = array_merge($regularResults, $compositeResults);
usort($allResults, function($a, $b) {
    return $b['id'] - $a['id'];
});

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = DEFAULT_PAGE_SIZE;
$totalResults = count($allResults);
$totalPages = max(1, ceil($totalResults / $perPage));
$offset = ($page - 1) * $perPage;
$paginatedResults = array_slice($allResults, $offset, $perPage);

// Totals
$totalAllResults = (int) $db->query("SELECT COUNT(*) FROM results")->fetchColumn();
$totalAllCompositeResults = (int) $db->query("SELECT COUNT(*) FROM composite_exam_results")->fetchColumn();
$grandTotal = $totalAllResults + $totalAllCompositeResults;

// =====================================================
// ANALYTICS (for selected exam)
// =====================================================
$analytics = null;
$gradeDistribution = [];

if ($selectedExam && $selectedExamType === 'composite') {
    $analytics = $db->prepare("SELECT 
        COUNT(*) as total_students,
        COALESCE(AVG(percentage), 0) as avg_percentage,
        COALESCE(MAX(percentage), 0) as max_percentage,
        COALESCE(MIN(percentage), 0) as min_percentage,
        COALESCE(SUM(CASE WHEN percentage >= 40 THEN 1 ELSE 0 END), 0) as passed,
        COALESCE(SUM(CASE WHEN percentage < 40 THEN 1 ELSE 0 END), 0) as failed
        FROM composite_exam_results WHERE composite_exam_id = ?");
    $analytics->execute([$selectedExam]);
    $analytics = $analytics->fetch();
    
    $gradeDist = $db->prepare("SELECT grade, COUNT(*) as count FROM composite_exam_results WHERE composite_exam_id = ? AND grade IS NOT NULL GROUP BY grade ORDER BY count DESC");
    $gradeDist->execute([$selectedExam]);
    $gradeDistribution = $gradeDist->fetchAll();
} elseif ($selectedExam) {
    $analytics = $db->prepare("SELECT 
        COUNT(*) as total_students,
        COALESCE(AVG(percentage), 0) as avg_percentage,
        COALESCE(MAX(percentage), 0) as max_percentage,
        COALESCE(MIN(percentage), 0) as min_percentage,
        COALESCE(SUM(CASE WHEN percentage >= 40 THEN 1 ELSE 0 END), 0) as passed,
        COALESCE(SUM(CASE WHEN percentage < 40 THEN 1 ELSE 0 END), 0) as failed
        FROM results WHERE exam_id = ?");
    $analytics->execute([$selectedExam]);
    $analytics = $analytics->fetch();
    
    $gradeDist = $db->prepare("SELECT grade, COUNT(*) as count FROM results WHERE exam_id = ? AND grade IS NOT NULL GROUP BY grade ORDER BY count DESC");
    $gradeDist->execute([$selectedExam]);
    $gradeDistribution = $gradeDist->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
:root {
    --tsu-navy: #1a1a2e;
    --tsu-dark: #16213e;
    --tsu-blue: #0f3460;
    --tsu-gold: #e8c84c;
    --tsu-primary: #1a73e8;
    --tsu-success: #34a853;
    --tsu-danger: #ea4335;
    --tsu-warning: #fbbc04;
    --composite-purple: #9c27b0;
}

.delete-btn { transition: all 0.2s; }
.delete-btn:hover { transform: scale(1.05); }
.modal-footer .btn { padding: 8px 20px; }
.export-dropdown .dropdown-item:hover { background: #e8f0fe; }
.export-dropdown .dropdown-item i { width: 20px; }

.result-type-badge {
    font-size: 0.65rem;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 600;
}
.result-type-badge.regular { background: #e8f0fe; color: var(--tsu-primary); }
.result-type-badge.composite { background: #f3e8fd; color: var(--composite-purple); }

.score-badge { font-weight: 600; font-size: 0.95rem; }
.score-badge.pass { color: var(--tsu-success); }
.score-badge.fail { color: var(--tsu-danger); }
.score-detail { font-size: 0.7rem; color: #6c757d; }

.percentage-badge {
    font-size: 0.75rem;
    padding: 3px 10px;
    border-radius: 12px;
    font-weight: 600;
}
.percentage-badge.high { background: #e6f4ea; color: var(--tsu-success); }
.percentage-badge.medium { background: #fef9e7; color: #f9ab00; }
.percentage-badge.low { background: #fce8e6; color: var(--tsu-danger); }

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.04);
    transition: all 0.3s ease;
    height: 100%;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}
.stat-card .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}
.stat-card .stat-icon.blue { background: #e8f0fe; color: var(--tsu-primary); }
.stat-card .stat-icon.green { background: #e6f4ea; color: var(--tsu-success); }
.stat-card .stat-icon.gold { background: #fef9e7; color: var(--tsu-gold); }
.stat-card .stat-icon.red { background: #fce8e6; color: var(--tsu-danger); }
.stat-card .stat-icon.purple { background: #f3e8fd; color: var(--composite-purple); }
.stat-card .stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--tsu-navy);
    line-height: 1.2;
}
.stat-card .stat-label {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 500;
}

.exam-card {
    background: white;
    border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.04);
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}
.exam-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}

.table-results th {
    font-weight: 600;
    color: var(--tsu-navy);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e9ecef;
}
.table-results td {
    vertical-align: middle;
    font-size: 0.9rem;
}
.table-results .student-name {
    font-weight: 600;
    color: var(--tsu-navy);
}

tr.composite-row { background: #fdf7ff; }
tr.composite-row:hover { background: #f7ecfc; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-clipboard-data-fill me-2" style="color: var(--tsu-gold);"></i>Results Management</h4>
    <div class="d-flex gap-2 flex-wrap">
        <!-- Export Dropdown -->
        <div class="dropdown export-dropdown">
            <button class="btn btn-info dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download me-1"></i>Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="#" onclick="exportResults('excel')">
                        <i class="bi bi-file-earmark-excel text-success"></i> Excel (.xls)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" onclick="exportResults('csv')">
                        <i class="bi bi-file-earmark-text text-primary"></i> CSV
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="#" onclick="exportResults('pdf')">
                        <i class="bi bi-file-earmark-pdf text-danger"></i> PDF (Print)
                    </a>
                </li>
            </ul>
        </div>
        
        <?php if ($selectedExam): ?>
            <button class="btn btn-success" onclick="publishResults()">
                <i class="bi bi-check-circle me-1"></i>Publish All
            </button>
            <button class="btn btn-danger" onclick="showDeleteExamModal(<?php echo $selectedExam; ?>, '<?php echo e($selectedExamType); ?>')">
                <i class="bi bi-trash me-1"></i>Delete All
            </button>
        <?php endif; ?>
        <?php if ($grandTotal > 0): ?>
            <button class="btn btn-dark" onclick="showDeleteAllModal()">
                <i class="bi bi-database-x me-1"></i>Delete All Results
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon blue"><i class="bi bi-table"></i></div>
                <div>
                    <div class="stat-value"><?php echo number_format($grandTotal); ?></div>
                    <div class="stat-label">Total Results</div>
                    <small class="text-muted" style="font-size: 0.7rem;">
                        <?php echo $totalAllResults; ?> regular · <?php echo $totalAllCompositeResults; ?> composite
                    </small>
                </div>
            </div>
        </div>
    </div>
    <?php if ($selectedExam && $analytics): 
        $avgPct = floatval($analytics['avg_percentage'] ?? 0);
        $passedCount = intval($analytics['passed'] ?? 0);
        $failedCount = intval($analytics['failed'] ?? 0);
    ?>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon gold"><i class="bi bi-graph-up"></i></div>
                <div>
                    <div class="stat-value"><?php echo number_format($avgPct, 1); ?>%</div>
                    <div class="stat-label">Average Score</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-value"><?php echo $passedCount; ?></div>
                    <div class="stat-label">Passed</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
                <div>
                    <div class="stat-value"><?php echo $failedCount; ?></div>
                    <div class="stat-label">Failed</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="exam-card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small text-muted">Result Type</label>
                <select name="result_type" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $resultType === 'all' ? 'selected' : ''; ?>>All Results</option>
                    <option value="regular" <?php echo $resultType === 'regular' ? 'selected' : ''; ?>>Regular Only</option>
                    <option value="composite" <?php echo $resultType === 'composite' ? 'selected' : ''; ?>>Composite Only</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small text-muted">Select Exam</label>
                <select name="exam_id" id="examSelect" class="form-select">
                    <option value="">All Exams</option>
                    
                    <?php if ($resultType !== 'composite' && !empty($regularExams)): ?>
                    <optgroup label="📘 Regular Exams">
                        <?php foreach ($regularExams as $e): ?>
                            <option value="<?php echo $e['id']; ?>" 
                                data-type="regular"
                                <?php echo ($selectedExam == $e['id'] && $selectedExamType === 'regular') ? 'selected' : ''; ?>>
                                <?php echo e($e['exam_code'] . ' - ' . $e['exam_title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                    
                    <?php if ($resultType !== 'regular' && !empty($compositeExams)): ?>
                    <optgroup label="🔷 Composite Exams">
                        <?php foreach ($compositeExams as $e): ?>
                            <option value="<?php echo $e['id']; ?>"
                                data-type="composite"
                                <?php echo ($selectedExam == $e['id'] && $selectedExamType === 'composite') ? 'selected' : ''; ?>>
                                <?php echo e($e['exam_code'] . ' - ' . $e['exam_title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                </select>
                <input type="hidden" name="exam_type" id="exam_type_hidden" value="<?php echo e($selectedExamType); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small text-muted">Status</label>
                <select name="published" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="1" <?php echo $filterPublished === '1' ? 'selected' : ''; ?>>Published</option>
                    <option value="0" <?php echo $filterPublished === '0' ? 'selected' : ''; ?>>Not Published</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedExam && $analytics): 
    $passedChart = intval($analytics['passed'] ?? 0);
    $failedChart = intval($analytics['failed'] ?? 0);
?>
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="exam-card">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0"><i class="bi bi-pie-chart me-2" style="color: var(--tsu-gold);"></i>Pass/Fail Distribution</h6>
            </div>
            <div class="card-body">
                <canvas id="passFailChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="exam-card">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0"><i class="bi bi-bar-chart me-2" style="color: var(--tsu-gold);"></i>Grade Distribution</h6>
            </div>
            <div class="card-body">
                <canvas id="gradeChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Results Table -->
<div class="exam-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="bi bi-table me-2" style="color: var(--tsu-gold);"></i>Results List
            <span class="text-muted ms-2" style="font-size: 0.8rem;">
                <?php 
                if ($resultType === 'regular') echo '(Regular Exams only)';
                elseif ($resultType === 'composite') echo '(Composite Exams only)';
                else echo '(All Exam Types)';
                ?>
            </span>
        </h6>
        <span class="badge" style="background: var(--tsu-gold); color: var(--tsu-navy);"><?php echo $totalResults; ?> records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-results mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Student</th>
                        <th>Matric No</th>
                        <th>Type</th>
                        <th>Exam</th>
                        <th>Score</th>
                        <th>%</th>
                        <th>Grade</th>
                        <th>Published</th>
                        <th width="140">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = $offset + 1; ?>
                    <?php foreach ($paginatedResults as $result): 
                        $isComposite = ($result['result_source'] === 'composite');
                        
                        $percentage = floatval($result['percentage'] ?? 0);
                        $correct = intval($result['correct'] ?? 0);
                        $wrong = intval($result['wrong'] ?? 0);
                        $answered = intval($result['answered'] ?? 0);
                        $totalMarks = floatval($result['total_marks'] ?? 0);
                        $scoreVal = floatval($result['score'] ?? 0);
                        $published = intval($result['published'] ?? 0);
                        
                        if ($isComposite) {
                            $totalQ = intval($result['total_sub_questions'] ?? 0);
                        } else {
                            $totalQ = intval($result['total_questions'] ?? 0);
                        }
                        
                        $grade = getGrade($percentage);
                        $isPass = ($percentage >= 40);
                        $scoreDisplay = $correct . '/' . $totalQ;
                        $totalMarksDisplay = number_format($totalMarks, 1);
                        $marksEarned = number_format($scoreVal, 1);
                        $gradeColor = $grade['grade'] <= 'C' ? 'success' : ($grade['grade'] === 'D' ? 'warning' : 'danger');
                    ?>
                    <tr class="<?php echo $isComposite ? 'composite-row' : ''; ?>">
                        <td><?php echo $counter++; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if (!empty($result['photo'])): ?>
                                    <img src="<?php echo APP_URL; ?>/assets/uploads/student_photos/<?php echo e($result['photo']); ?>" class="rounded-circle me-2" width="32" height="32" style="object-fit:cover;">
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;"><i class="bi bi-person"></i></div>
                                <?php endif; ?>
                                <div>
                                    <span class="student-name"><?php echo e(($result['last_name'] ?? '') . ', ' . ($result['first_name'] ?? '')); ?></span>
                                </div>
                            </div>
                        </td>
                        <td><small><?php echo e($result['matric_number'] ?? 'N/A'); ?></small></td>
                        <td>
                            <?php if ($isComposite): ?>
                                <span class="result-type-badge composite">
                                    <i class="bi bi-diagram-3 me-1"></i>Composite
                                </span>
                            <?php else: ?>
                                <span class="result-type-badge regular">
                                    <i class="bi bi-journal-bookmark-fill me-1"></i>Regular
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge" style="background: <?php echo $isComposite ? 'var(--composite-purple)' : 'var(--tsu-navy)'; ?>;">
                                <?php echo e($result['exam_code'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <div>
                                <span class="score-badge <?php echo $isPass ? 'pass' : 'fail'; ?>">
                                    <?php echo $scoreDisplay; ?>
                                </span>
                                <div class="score-detail"><?php echo $marksEarned; ?> / <?php echo $totalMarksDisplay; ?> marks</div>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $percent = number_format($percentage, 1);
                            $percentClass = $percentage >= 70 ? 'high' : ($percentage >= 40 ? 'medium' : 'low');
                            ?>
                            <span class="percentage-badge <?php echo $percentClass; ?>">
                                <?php echo $percent; ?>%
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $gradeColor; ?>">
                                <?php echo $grade['grade']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($published): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Published</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bi bi-hourglass"></i> Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" 
                                    onclick="viewResult(<?php echo $result['id']; ?>, '<?php echo $result['result_source']; ?>')" title="View">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-outline-warning" 
                                    onclick="togglePublish(<?php echo $result['id']; ?>, <?php echo $published ? 0 : 1; ?>, '<?php echo $result['result_source']; ?>')" title="Toggle Publish">
                                    <i class="bi bi-<?php echo $published ? 'eye-slash' : 'eye'; ?>"></i>
                                </button>
                                <button class="btn btn-outline-danger delete-btn" 
                                    onclick="showDeleteSingleModal(<?php echo $result['id']; ?>, '<?php echo e(($result['last_name'] ?? '') . ', ' . ($result['first_name'] ?? '')); ?>', '<?php echo e($result['exam_code'] ?? 'N/A'); ?>', '<?php echo $result['result_source']; ?>')" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($paginatedResults)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No results found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-0 pt-3">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&exam_id=<?php echo $selectedExam; ?>&exam_type=<?php echo e($selectedExamType); ?>&published=<?php echo $filterPublished; ?>&result_type=<?php echo $resultType; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Single Result Modal -->
<div class="modal fade" id="deleteSingleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 3px solid var(--tsu-danger);">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Delete Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="delete_action" value="delete_single">
                <input type="hidden" name="result_id" id="delete_single_result_id">
                <input type="hidden" name="result_type" id="delete_single_result_type">
                <div class="modal-body">
                    <p>Are you sure you want to delete this result?</p>
                    <div class="alert alert-warning">
                        <strong>Student:</strong> <span id="delete_student_name"></span><br>
                        <strong>Exam:</strong> <span id="delete_exam_name"></span><br>
                        <strong>Type:</strong> <span id="delete_result_type_label"></span><br>
                        <strong>Action:</strong> This will permanently remove this result from the system.
                    </div>
                    <p class="text-danger mb-0"><i class="bi bi-info-circle me-1"></i> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete Result</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Exam Results Modal -->
<div class="modal fade" id="deleteExamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 3px solid var(--tsu-danger);">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Delete Exam Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="delete_action" value="delete_exam_results">
                <input type="hidden" name="exam_id" id="delete_exam_id">
                <input type="hidden" name="exam_type" id="delete_exam_type">
                <div class="modal-body">
                    <p>Are you sure you want to delete ALL results for this exam?</p>
                    <div class="alert alert-warning">
                        <strong>Exam:</strong> <span id="delete_exam_code"></span><br>
                        <strong>Action:</strong> This will delete all results associated with this exam.
                    </div>
                    <p class="text-danger mb-0"><i class="bi bi-info-circle me-1"></i> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete All</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete All Results Modal -->
<div class="modal fade" id="deleteAllModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 3px solid var(--tsu-danger);">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Delete Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>Warning!</strong> You are about to delete results from the system.
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body text-center">
                                <h3 class="text-primary mb-0"><?php echo number_format($totalAllResults); ?></h3>
                                <small class="text-muted">Regular Results</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body text-center">
                                <h3 class="mb-0" style="color: var(--composite-purple);"><?php echo number_format($totalAllCompositeResults); ?></h3>
                                <small class="text-muted">Composite Results</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p>Choose what to delete:</p>
                
                <div class="d-grid gap-2">
                    <form method="POST" onsubmit="return confirm('Delete ALL REGULAR results? This cannot be undone!')">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="delete_action" value="delete_all_regular">
                        <button type="submit" class="btn btn-warning w-100" <?php echo $totalAllResults == 0 ? 'disabled' : ''; ?>>
                            <i class="bi bi-journal-bookmark me-2"></i>Delete All Regular Results (<?php echo $totalAllResults; ?>)
                        </button>
                    </form>
                    
                    <form method="POST" onsubmit="return confirm('Delete ALL COMPOSITE results? This cannot be undone!')">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="delete_action" value="delete_all_composite">
                        <button type="submit" class="btn w-100" style="background: var(--composite-purple); color: white;" <?php echo $totalAllCompositeResults == 0 ? 'disabled' : ''; ?>>
                            <i class="bi bi-diagram-3 me-2"></i>Delete All Composite Results (<?php echo $totalAllCompositeResults; ?>)
                        </button>
                    </form>
                    
                    <hr>
                    
                    <form method="POST" id="deleteAllForm" onsubmit="return confirmDeleteAll(event)">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="delete_action" value="delete_all_results">
                        <div class="alert alert-danger mb-2">
                            <strong>⚠️ IRREVERSIBLE!</strong> This will delete ALL <strong><?php echo $grandTotal; ?></strong> results from the system.
                        </div>
                        <div class="mb-2">
                            <input type="text" class="form-control" id="confirm_delete_all" placeholder='Type "DELETE ALL" to confirm' required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100" id="confirm_delete_btn" disabled>
                            <i class="bi bi-database-x me-2"></i>Delete ALL Results (<?php echo $grandTotal; ?>)
                        </button>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const APP_URL = '<?php echo APP_URL; ?>';
const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';

const regularExamsData = <?php echo json_encode($regularExams); ?>;
const compositeExamsData = <?php echo json_encode($compositeExams); ?>;
const grandTotal = <?php echo $grandTotal; ?>;

// =====================================================
// DELETE MODALS
// =====================================================
function showDeleteSingleModal(resultId, studentName, examCode, resultType) {
    document.getElementById('delete_single_result_id').value = resultId;
    document.getElementById('delete_single_result_type').value = resultType;
    document.getElementById('delete_student_name').textContent = studentName;
    document.getElementById('delete_exam_name').textContent = examCode;
    document.getElementById('delete_result_type_label').innerHTML = resultType === 'composite' 
        ? '<span class="badge" style="background: var(--composite-purple);">Composite</span>' 
        : '<span class="badge bg-primary">Regular</span>';
    new bootstrap.Modal(document.getElementById('deleteSingleModal')).show();
}

function showDeleteExamModal(examId, examType) {
    const examsData = examType === 'composite' ? compositeExamsData : regularExamsData;
    const exam = examsData.find(e => e.id == examId);
    
    if (exam) {
        document.getElementById('delete_exam_id').value = examId;
        document.getElementById('delete_exam_type').value = examType;
        document.getElementById('delete_exam_code').textContent = exam.exam_code + ' - ' + exam.exam_title;
        new bootstrap.Modal(document.getElementById('deleteExamModal')).show();
    }
}

function showDeleteAllModal() {
    const modal = new bootstrap.Modal(document.getElementById('deleteAllModal'));
    modal.show();
    
    const confirmInput = document.getElementById('confirm_delete_all');
    const confirmBtn = document.getElementById('confirm_delete_btn');
    
    if (confirmInput && confirmBtn) {
        confirmInput.oninput = function() {
            confirmBtn.disabled = (this.value !== 'DELETE ALL');
        };
    }
}

function confirmDeleteAll(event) {
    const input = document.getElementById('confirm_delete_all');
    if (input.value !== 'DELETE ALL') {
        event.preventDefault();
        return false;
    }
    return confirm('FINAL WARNING: Delete ALL ' + grandTotal + ' results? This CANNOT be undone!');
}

// =====================================================
// EXAM TYPE AUTO-DETECTION
// =====================================================
document.getElementById('examSelect')?.addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const type = option.getAttribute('data-type') || '';
    document.getElementById('exam_type_hidden').value = type;
    this.form.submit();
});

// =====================================================
// PUBLISHING
// =====================================================
async function publishResults() {
    const examType = '<?php echo e($selectedExamType); ?>';
    if (!confirm('Publish all results for this exam? This will make results visible to students.')) return;
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/publish_results.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ 
                exam_id: <?php echo $selectedExam; ?>,
                type: examType
            })
        });
        const data = await response.json();
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 1000);
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to publish results', 'error');
    }
}

async function togglePublish(resultId, publish, resultType) {
    const action = publish ? 'publish' : 'unpublish';
    if (!confirm(`Are you sure you want to ${action} this result?`)) return;
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/toggle_publish.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ 
                result_id: resultId, 
                publish: publish,
                type: resultType
            })
        });
        const data = await response.json();
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 500);
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to toggle publish status', 'error');
    }
}

// =====================================================
// VIEW RESULT
// =====================================================
function viewResult(resultId, resultType) {
    const type = resultType || 'regular';
    window.open(APP_URL + '/ajax/admin/view_result.php?result_id=' + resultId + '&type=' + type, '_blank', 'width=900,height=700');
}

// =====================================================
// CHARTS
// =====================================================
<?php if ($selectedExam && $analytics): 
    $passedChart = intval($analytics['passed'] ?? 0);
    $failedChart = intval($analytics['failed'] ?? 0);
?>
new Chart(document.getElementById('passFailChart'), {
    type: 'pie',
    data: {
        labels: ['Passed', 'Failed'],
        datasets: [{
            data: [<?php echo $passedChart; ?>, <?php echo $failedChart; ?>],
            backgroundColor: ['#34a853', '#ea4335']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

const gradeData = <?php echo json_encode($gradeDistribution ?? []); ?>;
if (gradeData.length > 0) {
    new Chart(document.getElementById('gradeChart'), {
        type: 'bar',
        data: {
            labels: gradeData.map(g => 'Grade ' + g.grade),
            datasets: [{
                label: 'Number of Students',
                data: gradeData.map(g => parseInt(g.count) || 0),
                backgroundColor: '#1a73e8',
                borderRadius: 5
            }]
        },
        options: { 
            responsive: true,
            scales: { 
                y: { 
                    beginAtZero: true, 
                    ticks: { precision: 0 },
                    title: { display: true, text: 'Number of Students' }
                },
                x: { title: { display: true, text: 'Grade' } }
            },
            plugins: { legend: { display: false } }
        }
    });
}
<?php endif; ?>

// =====================================================
// EXPORT (Excel, CSV, PDF)
// =====================================================
function exportResults(format) {
    let url = APP_URL + '/ajax/admin/export_results.php?';
    
    <?php if ($selectedExam): ?>
    url += 'exam_id=<?php echo $selectedExam; ?>&';
    url += 'exam_type=<?php echo e($selectedExamType); ?>&';
    <?php endif; ?>
    
    <?php if ($filterPublished !== ''): ?>
    url += 'published=<?php echo $filterPublished; ?>&';
    <?php endif; ?>
    
    url += 'result_type=<?php echo $resultType; ?>&';
    url += 'format=' + format;
    
    if (format === 'pdf') {
        // Open PDF preview in new window (user clicks "Save as PDF" button)
        window.open(url, '_blank', 'width=1100,height=850,scrollbars=yes');
    } else {
        // Download Excel/CSV
        window.open(url, '_blank');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>