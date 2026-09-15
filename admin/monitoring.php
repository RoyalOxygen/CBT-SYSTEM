<?php
/**
 * CBT System - Live Exam Monitoring
 * Supports both regular and composite exams
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Live Monitoring';
$db = getDB();

$examId = intval($_GET['exam_id'] ?? 0);
$examType = $_GET['type'] ?? 'regular'; // 'regular' or 'composite'
$exam = null;
$activeAttempts = [];

// =====================================================
// LOAD SELECTED EXAM DATA
// =====================================================
if ($examId) {
    if ($examType === 'composite') {
        // Load composite exam
        $stmt = $db->prepare("SELECT ce.*, d.dept_name FROM composite_exams ce 
            LEFT JOIN departments d ON ce.department_id = d.id WHERE ce.id = ?");
        $stmt->execute([$examId]);
        $exam = $stmt->fetch();
        
        if ($exam) {
            $stmt = $db->prepare("SELECT 
                cea.*, 
                s.matric_number, s.first_name, s.last_name, s.photo,
                (SELECT COUNT(*) FROM composite_exam_answers WHERE attempt_id = cea.id) as answered_count
                FROM composite_exam_attempts cea
                JOIN students s ON cea.student_id = s.id
                WHERE cea.composite_exam_id = ? AND cea.status = 'in_progress'
                ORDER BY cea.start_time DESC");
            $stmt->execute([$examId]);
            $activeAttempts = $stmt->fetchAll();
        }
    } else {
        // Load regular exam
        $stmt = $db->prepare("SELECT e.*, d.dept_name FROM exams e 
            LEFT JOIN departments d ON e.department_id = d.id WHERE e.id = ?");
        $stmt->execute([$examId]);
        $exam = $stmt->fetch();
        
        if ($exam) {
            $stmt = $db->prepare("SELECT ea.*, s.matric_number, s.first_name, s.last_name, s.photo,
                (SELECT COUNT(*) FROM answers WHERE attempt_id = ea.id) as answered_count
                FROM exam_attempts ea
                JOIN students s ON ea.student_id = s.id
                WHERE ea.exam_id = ? AND ea.status = 'in_progress'
                ORDER BY ea.start_time DESC");
            $stmt->execute([$examId]);
            $activeAttempts = $stmt->fetchAll();
        }
    }
}

// =====================================================
// LOAD RUNNING EXAMS (both types)
// =====================================================
$runningRegularExams = $db->query("
    SELECT e.id, e.exam_code, e.exam_title, e.status, d.dept_name,
    (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id AND status = 'in_progress') as active_count
    FROM exams e 
    LEFT JOIN departments d ON e.department_id = d.id 
    WHERE e.status = 'running' 
    ORDER BY e.start_time DESC
")->fetchAll();

$runningCompositeExams = $db->query("
    SELECT ce.id, ce.exam_code, ce.exam_title, ce.status, d.dept_name,
    (SELECT COUNT(*) FROM composite_exam_attempts WHERE composite_exam_id = ce.id AND status = 'in_progress') as active_count
    FROM composite_exams ce 
    LEFT JOIN departments d ON ce.department_id = d.id 
    WHERE ce.status = 'running' 
    ORDER BY ce.start_time DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.exam-selector-group {
    min-width: 400px;
}
.exam-selector-group optgroup {
    font-weight: 600;
    color: #1a1a2e;
}
.composite-badge {
    background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);
    color: white;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-block;
}
.regular-badge {
    background: #1a1a2e;
    color: white;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-block;
}
.type-indicator {
    font-size: 0.7rem;
    padding: 3px 10px;
    border-radius: 12px;
    font-weight: 600;
}
.type-indicator.regular { background: #e8f0fe; color: #1a73e8; }
.type-indicator.composite { background: #f3e8fd; color: #9c27b0; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-broadcast me-2 text-danger"></i>Live Monitoring</h4>
        <small class="text-muted">Monitor regular and composite exams in real-time</small>
    </div>
    <select class="form-select exam-selector-group" onchange="switchExam(this.value)">
        <option value="">— Select Running Exam —</option>
        
        <?php if (!empty($runningRegularExams)): ?>
        <optgroup label="📘 Regular Exams">
            <?php foreach ($runningRegularExams as $e): ?>
                <option value="regular:<?php echo $e['id']; ?>" 
                    <?php echo ($examId == $e['id'] && $examType === 'regular') ? 'selected' : ''; ?>>
                    <?php echo e($e['exam_code'] . ' - ' . $e['exam_title']); ?>
                    (<?php echo $e['active_count']; ?> active)
                </option>
            <?php endforeach; ?>
        </optgroup>
        <?php endif; ?>
        
        <?php if (!empty($runningCompositeExams)): ?>
        <optgroup label="🔷 Composite Exams">
            <?php foreach ($runningCompositeExams as $e): ?>
                <option value="composite:<?php echo $e['id']; ?>"
                    <?php echo ($examId == $e['id'] && $examType === 'composite') ? 'selected' : ''; ?>>
                    <?php echo e($e['exam_code'] . ' - ' . $e['exam_title']); ?>
                    (<?php echo $e['active_count']; ?> active)
                </option>
            <?php endforeach; ?>
        </optgroup>
        <?php endif; ?>
        
        <?php if (empty($runningRegularExams) && empty($runningCompositeExams)): ?>
            <option value="" disabled>No running exams found</option>
        <?php endif; ?>
    </select>
</div>

<?php if ($exam): ?>

<!-- Exam Info Banner -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <?php if ($examType === 'composite'): ?>
                    <span class="composite-badge">
                        <i class="bi bi-diagram-3 me-1"></i>COMPOSITE
                    </span>
                <?php else: ?>
                    <span class="regular-badge">
                        <i class="bi bi-journal-bookmark-fill me-1"></i>REGULAR
                    </span>
                <?php endif; ?>
                <div>
                    <strong><?php echo e($exam['exam_code']); ?></strong>
                    <span class="text-muted ms-2"><?php echo e($exam['exam_title']); ?></span>
                </div>
            </div>
            <div class="text-muted small">
                <i class="bi bi-clock me-1"></i>Duration: <?php echo $exam['duration']; ?> min
                &nbsp;|&nbsp;
                <i class="bi bi-people me-1"></i>
                <?php echo $examType === 'composite' ? 'Sub-questions per attempt' : 'Questions per attempt'; ?>
            </div>
        </div>
    </div>
</div>

<!-- Exam Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <h3 class="text-primary mb-0" id="activeCount"><?php echo count($activeAttempts); ?></h3>
                <small class="text-muted">Active Students</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <?php 
                if ($examType === 'composite') {
                    $submitted = $db->prepare("SELECT COUNT(*) FROM composite_exam_attempts WHERE composite_exam_id = ? AND status IN ('submitted', 'auto_submitted')");
                } else {
                    $submitted = $db->prepare("SELECT COUNT(*) FROM exam_attempts WHERE exam_id = ? AND status IN ('submitted', 'auto_submitted')");
                }
                $submitted->execute([$examId]);
                ?>
                <h3 class="text-success mb-0" id="submittedCount"><?php echo $submitted->fetchColumn(); ?></h3>
                <small class="text-muted">Submitted</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <?php 
                if ($examType === 'composite') {
                    $timedOut = $db->prepare("SELECT COUNT(*) FROM composite_exam_attempts WHERE composite_exam_id = ? AND status = 'timed_out'");
                } else {
                    $timedOut = $db->prepare("SELECT COUNT(*) FROM exam_attempts WHERE exam_id = ? AND status = 'timed_out'");
                }
                $timedOut->execute([$examId]);
                ?>
                <h3 class="text-warning mb-0" id="timeUpCount"><?php echo $timedOut->fetchColumn(); ?></h3>
                <small class="text-muted">Timed Out</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <?php 
                if ($examType === 'composite') {
                    $assigned = $db->prepare("SELECT COUNT(*) FROM composite_exam_students WHERE composite_exam_id = ?");
                } else {
                    $assigned = $db->prepare("SELECT COUNT(*) FROM exam_students WHERE exam_id = ?");
                }
                $assigned->execute([$examId]);
                ?>
                <h3 class="text-info mb-0" id="totalAssigned"><?php echo $assigned->fetchColumn(); ?></h3>
                <small class="text-muted">Total Assigned</small>
            </div>
        </div>
    </div>
</div>

<!-- Control Panel -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-sliders me-2"></i>Exam Controls</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <button class="btn btn-warning w-100" onclick="extendTimeForAll()">
                    <i class="bi bi-clock-history me-1"></i>Extend All +10 Min
                </button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-danger w-100" onclick="endExamForAll()">
                    <i class="bi bi-stop-fill me-1"></i>End Exam For All
                </button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-info w-100" onclick="addCustomTime()">
                    <i class="bi bi-plus-circle me-1"></i>Custom Time Extension
                </button>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh Data
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Active Students Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-people-fill me-2"></i>Active Students
            <span class="type-indicator <?php echo $examType; ?> ms-2">
                <?php echo $examType === 'composite' ? 'Composite' : 'Regular'; ?>
            </span>
        </h5>
        <div>
            <span class="badge bg-success me-2" style="animation: pulse 2s infinite;">● LIVE</span>
            <small class="text-muted">Auto-refresh every 30s</small>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="activeStudentsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Matric</th>
                        <th>Start Time</th>
                        <th>Time Remaining</th>
                        <th>Progress</th>
                        <th>IP Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activeAttempts as $attempt): 
                        $timeRemaining = max(0, strtotime($attempt['start_time']) + ($exam['duration'] * 60) - time() + (($attempt['time_extension_minutes'] ?? 0) * 60));
                        $elapsed = time() - strtotime($attempt['start_time']);
                        $total = $exam['duration'] * 60;
                        $progress = min(100, $total > 0 ? ($elapsed / $total) * 100 : 0);
                        $totalQ = $examType === 'composite' ? ($attempt['total_sub_questions'] ?? 0) : ($attempt['total_questions'] ?? 0);
                    ?>
                    <tr data-attempt-id="<?php echo $attempt['id']; ?>">
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if ($attempt['photo']): ?>
                                    <img src="<?php echo APP_URL; ?>/assets/uploads/student_photos/<?php echo e($attempt['photo']); ?>" class="rounded-circle me-2" width="32" height="32" style="object-fit:cover;">
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;"><i class="bi bi-person"></i></div>
                                <?php endif; ?>
                                <?php echo e($attempt['last_name'] . ', ' . $attempt['first_name']); ?>
                            </div>
                        </td>
                        <td><?php echo e($attempt['matric_number']); ?></td>
                        <td><?php echo formatDateTime($attempt['start_time']); ?></td>
                        <td>
                            <span class="timer-badge <?php echo $timeRemaining < 300 ? 'text-danger' : ($timeRemaining < 600 ? 'text-warning' : 'text-success'); ?>">
                                <?php echo formatTimer($timeRemaining); ?>
                            </span>
                        </td>
                        <td>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-<?php echo $progress > 80 ? 'danger' : ($progress > 50 ? 'warning' : 'success'); ?>" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <small><?php echo $attempt['answered_count']; ?>/<?php echo $totalQ; ?> answered</small>
                        </td>
                        <td><small class="text-muted"><?php echo e($attempt['ip_address'] ?? 'N/A'); ?></small></td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-warning" onclick="extendTime(<?php echo $attempt['id']; ?>)" title="Add Time">
                                    <i class="bi bi-clock"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="endExam(<?php echo $attempt['id']; ?>)" title="Force End">
                                    <i class="bi bi-stop-fill"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="resetAttempt(<?php echo $attempt['id']; ?>)" title="Reset">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($activeAttempts)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No active students currently</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>

<script>
const CURRENT_EXAM_TYPE = '<?php echo $examType; ?>';
const CURRENT_EXAM_ID = <?php echo $examId; ?>;
let refreshInterval;

// =====================================================
// SWITCH EXAM (handles both types)
// =====================================================
function switchExam(value) {
    if (!value) return;
    const [type, id] = value.split(':');
    location.href = '?exam_id=' + id + '&type=' + type;
}

// =====================================================
// AUTO REFRESH
// =====================================================
function startAutoRefresh() {
    refreshInterval = setInterval(() => {
        refreshData();
    }, 30000);
}

async function refreshData() {
    try {
        const url = APP_URL + '/ajax/admin/get_live_students.php?exam_id=' + CURRENT_EXAM_ID + '&type=' + CURRENT_EXAM_TYPE;
        const response = await fetch(url, {
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('activeCount').textContent = data.active_count || 0;
            document.getElementById('submittedCount').textContent = data.submitted_count || 0;
            // For simplicity, reload the page to refresh the table
            if (data.reload_required) {
                location.reload();
            }
        }
    } catch (error) {
        console.error('Refresh error:', error);
    }
}

// =====================================================
// INDIVIDUAL STUDENT ACTIONS
// =====================================================
async function extendTime(attemptId) {
    const minutes = prompt('Enter extra minutes:', '10');
    if (!minutes || isNaN(minutes)) return;
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/extend_time.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ 
                attempt_id: attemptId, 
                extra_minutes: parseInt(minutes),
                type: CURRENT_EXAM_TYPE
            })
        });
        const data = await response.json();
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 1000);
    } catch (error) {
        showToast('Failed to extend time', 'error');
    }
}

async function endExam(attemptId) {
    if (!confirm('Force end this student\'s exam?')) return;
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/force_end_exam.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ 
                attempt_id: attemptId,
                type: CURRENT_EXAM_TYPE
            })
        });
        const data = await response.json();
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 1000);
    } catch (error) {
        showToast('Failed', 'error');
    }
}

async function resetAttempt(attemptId) {
    if (!confirm('Reset this student\'s attempt? They will be able to retake the exam.')) return;
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/reset_attempt.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ 
                attempt_id: attemptId,
                type: CURRENT_EXAM_TYPE
            })
        });
        const data = await response.json();
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 1000);
    } catch (error) {
        showToast('Failed', 'error');
    }
}

// =====================================================
// BULK ACTIONS
// =====================================================
async function extendTimeForAll() {
    if (!confirm('Add 10 minutes to ALL students taking this exam?')) return;
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/extend_time_all.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ 
                exam_id: CURRENT_EXAM_ID, 
                extra_minutes: 10,
                type: CURRENT_EXAM_TYPE
            })
        });
        const data = await response.json();
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 1000);
    } catch (error) {
        showToast('Failed', 'error');
    }
}

async function endExamForAll() {
    if (!confirm('END EXAM FOR ALL STUDENTS? This cannot be undone!')) return;
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/force_end_all.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ 
                exam_id: CURRENT_EXAM_ID,
                type: CURRENT_EXAM_TYPE
            })
        });
        const data = await response.json();
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 1000);
    } catch (error) {
        showToast('Failed', 'error');
    }
}

function addCustomTime() {
    const minutes = prompt('Enter minutes to add for ALL students:', '5');
    if (!minutes || isNaN(minutes)) return;
    
    fetch(APP_URL + '/ajax/admin/extend_time_all.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ 
            exam_id: CURRENT_EXAM_ID, 
            extra_minutes: parseInt(minutes),
            type: CURRENT_EXAM_TYPE
        })
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 1000);
    })
    .catch(() => showToast('Failed', 'error'));
}

// Start auto-refresh
startAutoRefresh();
</script>

<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-tv fs-1"></i>
        <p class="mt-2 mb-1">Select a running exam to monitor students in real-time</p>
        <small>
            <?php if (!empty($runningRegularExams) || !empty($runningCompositeExams)): ?>
                Available: <?php echo count($runningRegularExams); ?> regular, <?php echo count($runningCompositeExams); ?> composite
            <?php else: ?>
                No running exams available at the moment
            <?php endif; ?>
        </small>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>