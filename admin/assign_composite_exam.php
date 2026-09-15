<?php
/**
 * CBT System - Assign Students to Composite Exams
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Assign Students to Composite Exams';
$db = getDB();

// Get all composite exams
$exams = $db->query("SELECT ce.id, ce.exam_code, ce.exam_title, ce.department_id, ce.level_id, 
    ce.semester_id, ce.session_id, d.dept_name, l.level_name 
    FROM composite_exams ce 
    LEFT JOIN departments d ON ce.department_id = d.id 
    LEFT JOIN levels l ON ce.level_id = l.id 
    WHERE ce.status IN ('draft', 'published', 'running') 
    ORDER BY ce.created_at DESC")->fetchAll();

$selectedExamId = intval($_GET['exam_id'] ?? 0);
$selectedExam = null;

if ($selectedExamId) {
    foreach ($exams as $e) {
        if ($e['id'] == $selectedExamId) {
            $selectedExam = $e;
            break;
        }
    }
}

// Get filter options
$departments = $db->query("SELECT * FROM departments WHERE status = 1 ORDER BY dept_name")->fetchAll();
$levels = $db->query("SELECT * FROM levels WHERE status = 1 ORDER BY level_order")->fetchAll();
$semesters = $db->query("SELECT * FROM semesters WHERE status = 1 ORDER BY semester_order")->fetchAll();
$sessions = $db->query("SELECT * FROM sessions WHERE status = 1 ORDER BY session_name DESC")->fetchAll();

$csrf_token_js = generateCSRFToken();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.composite-badge {
    background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%);
    color: #e8c84c;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}
.exam-list-item {
    border-left: 4px solid transparent;
    transition: all 0.2s;
    cursor: pointer;
}
.exam-list-item:hover {
    background: #f8f9fa;
    border-left-color: #e8c84c;
}
.exam-list-item.active {
    background: #e8f0fe;
    border-left-color: #1a73e8;
}
.student-checkbox-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s;
}
.student-checkbox-card:hover {
    background: #e9ecef;
    border-color: #1a73e8;
}
.student-checkbox-card.selected {
    background: #e8f0fe;
    border-color: #1a73e8;
}
.student-checkbox-card input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #1a73e8;
    cursor: pointer;
    flex-shrink: 0;
}
.assigned-student-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-bottom: 1px solid #f0f0f0;
}
.assigned-student-row:last-child {
    border-bottom: none;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">
            <i class="bi bi-person-check-fill me-2" style="color: var(--tsu-gold);"></i>
            Assign Students to Composite Exams
        </h4>
        <small class="text-muted">Assign students to exams with nested sub-questions</small>
    </div>
    <a href="composite_exams.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Composite Exams
    </a>
</div>

<div class="row g-4">
    <!-- Left: Exam Selection -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0"><i class="bi bi-journal-check me-2" style="color: var(--tsu-gold);"></i>Select Composite Exam</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="examList" style="max-height: 500px; overflow-y: auto;">
                    <?php foreach ($exams as $e): ?>
                    <a href="?exam_id=<?php echo $e['id']; ?>" 
                       class="list-group-item list-group-item-action exam-list-item <?php echo $selectedExamId == $e['id'] ? 'active' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <span class="composite-badge mb-1 d-inline-block">
                                    <i class="bi bi-diagram-3 me-1"></i><?php echo e($e['exam_code']); ?>
                                </span>
                                <div class="fw-semibold small mt-1"><?php echo e(substr($e['exam_title'], 0, 40)); ?></div>
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    <?php echo e($e['dept_name'] ?? 'All'); ?> | <?php echo e($e['level_name'] ?? 'All'); ?>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php if (empty($exams)): ?>
                        <div class="list-group-item text-muted text-center py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No composite exams available
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right: Assignment Panel -->
    <div class="col-lg-8">
        <?php if ($selectedExam): ?>
        <!-- Exam Details Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; border-left: 4px solid var(--tsu-gold);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <span class="composite-badge mb-1 d-inline-block">
                            <i class="bi bi-diagram-3 me-1"></i><?php echo e($selectedExam['exam_code']); ?>
                        </span>
                        <h5 class="mt-2 mb-1"><?php echo e($selectedExam['exam_title']); ?></h5>
                        <div class="text-muted small">
                            <i class="bi bi-building me-1"></i><?php echo e($selectedExam['dept_name'] ?? 'All Departments'); ?>
                            &nbsp;|&nbsp;
                            <i class="bi bi-layer-forward me-1"></i><?php echo e($selectedExam['level_name'] ?? 'All Levels'); ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="badge bg-primary mb-1" id="assignedCountBadge">0 assigned</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs: Assign New / View Assigned -->
        <ul class="nav nav-tabs mb-3" id="assignTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="assignTab" data-bs-toggle="tab" data-bs-target="#assignPane" type="button" role="tab">
                    <i class="bi bi-person-plus me-1"></i> Assign Students
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assignedTab" data-bs-toggle="tab" data-bs-target="#assignedPane" type="button" role="tab">
                    <i class="bi bi-people-fill me-1"></i> Assigned Students
                    <span class="badge bg-secondary ms-1" id="assignedTabBadge">0</span>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Assign Students Tab -->
            <div class="tab-pane fade show active" id="assignPane">
                <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                    <div class="card-body">
                        <!-- Filter Row -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select id="filterDept" class="form-select form-select-sm">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $d): ?>
                                        <option value="<?php echo $d['id']; ?>" <?php echo ($selectedExam['department_id'] ?? '') == $d['id'] ? 'selected' : ''; ?>>
                                            <?php echo e($d['dept_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="filterLevel" class="form-select form-select-sm">
                                    <option value="">All Levels</option>
                                    <?php foreach ($levels as $l): ?>
                                        <option value="<?php echo $l['id']; ?>" <?php echo ($selectedExam['level_id'] ?? '') == $l['id'] ? 'selected' : ''; ?>>
                                            <?php echo e($l['level_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="filterSemester" class="form-select form-select-sm">
                                    <option value="">All Semesters</option>
                                    <?php foreach ($semesters as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo e($s['semester_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="filterSession" class="form-select form-select-sm">
                                    <option value="">All Sessions</option>
                                    <?php foreach ($sessions as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo e($s['session_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllStudents()">
                                    <i class="bi bi-check-all me-1"></i> Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllStudents()">
                                    <i class="bi bi-x-circle me-1"></i> Deselect All
                                </button>
                            </div>
                            <button type="button" class="btn btn-primary" id="assignBtn" onclick="assignSelectedStudents()" disabled>
                                <i class="bi bi-person-check me-1"></i> Assign Selected (<span id="selectedCount">0</span>)
                            </button>
                        </div>

                        <!-- Students List -->
                        <div id="studentsList" style="max-height: 450px; overflow-y: auto;">
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-funnel fs-3 d-block mb-2"></i>
                                <p class="mb-0">Select filters to load students</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assigned Students Tab -->
            <div class="tab-pane fade" id="assignedPane">
                <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                    <div class="card-body">
                        <div id="assignedStudentsList">
                            <div class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm me-2"></div>
                                Loading assigned students...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Empty State -->
        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-diagram-3 fs-1 d-block mb-3" style="color: #dadce0;"></i>
                <h5>Select a Composite Exam</h5>
                <p class="mb-0">Choose an exam from the list on the left to manage student assignments.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($selectedExam): ?>
<script>
const APP_URL = '<?php echo APP_URL; ?>';
const CSRF_TOKEN = '<?php echo $csrf_token_js; ?>';
const CSRF_TOKEN_NAME = '<?php echo CSRF_TOKEN_NAME; ?>';
const COMPOSITE_EXAM_ID = <?php echo $selectedExamId; ?>;

let currentStudents = [];

// Load students when filters change
document.querySelectorAll('#filterDept, #filterLevel, #filterSemester, #filterSession').forEach(el => {
    el.addEventListener('change', loadStudents);
});

async function loadStudents() {
    const dept = document.getElementById('filterDept').value;
    const level = document.getElementById('filterLevel').value;
    const semester = document.getElementById('filterSemester').value;
    const session = document.getElementById('filterSession').value;
    
    if (!dept && !level && !semester && !session) {
        document.getElementById('studentsList').innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="bi bi-funnel fs-3 d-block mb-2"></i>
                <p class="mb-0">Select at least one filter to load students</p>
            </div>
        `;
        return;
    }
    
    document.getElementById('studentsList').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary me-2"></div>
            <span class="text-muted">Loading students...</span>
        </div>
    `;
    
    try {
        const params = new URLSearchParams({
            department: dept,
            level: level,
            semester: semester,
            session: session,
            composite_exam_id: COMPOSITE_EXAM_ID
        });
        
        const response = await fetch(APP_URL + '/ajax/admin/get_composite_students.php?' + params, {
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const data = await response.json();
        
        if (data.success) {
            currentStudents = data.students || [];
            renderStudents(currentStudents);
        } else {
            document.getElementById('studentsList').innerHTML = `
                <div class="alert alert-danger">${data.message || 'Error loading students'}</div>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('studentsList').innerHTML = `
            <div class="alert alert-danger">Error loading students: ${error.message}</div>
        `;
    }
}

function renderStudents(students) {
    if (!students || students.length === 0) {
        document.getElementById('studentsList').innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                <p class="mb-0">No students found with selected filters</p>
            </div>
        `;
        return;
    }
    
    let html = `<div class="small text-muted mb-2">Found ${students.length} student(s) not yet assigned</div>`;
    html += students.map(s => `
        <label class="student-checkbox-card" id="studentCard_${s.id}">
            <input type="checkbox" class="student-checkbox" value="${s.id}" onchange="updateSelection()">
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong class="small">${escapeHtml(s.matric_number)}</strong>
                        <div class="small text-muted">${escapeHtml(s.last_name)}, ${escapeHtml(s.first_name)}</div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-light text-dark small">${escapeHtml(s.dept_name || 'N/A')}</span>
                        <span class="badge bg-light text-dark small">${escapeHtml(s.level_name || 'N/A')}</span>
                    </div>
                </div>
            </div>
        </label>
    `).join('');
    
    document.getElementById('studentsList').innerHTML = html;
    updateSelection();
}

function updateSelection() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    const selected = document.querySelectorAll('.student-checkbox:checked');
    
    // Update card styles
    checkboxes.forEach(cb => {
        const card = document.getElementById('studentCard_' + cb.value);
        if (card) {
            if (cb.checked) card.classList.add('selected');
            else card.classList.remove('selected');
        }
    });
    
    const count = selected.length;
    const countEl = document.getElementById('selectedCount');
    const assignBtn = document.getElementById('assignBtn');
    
    if (countEl) countEl.textContent = count;
    if (assignBtn) assignBtn.disabled = (count === 0);
}

function selectAllStudents() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = true);
    updateSelection();
}

function deselectAllStudents() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
    updateSelection();
}

async function assignSelectedStudents() {
    const selected = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        showToast('Please select at least one student', 'error');
        return;
    }
    
    const btn = document.getElementById('assignBtn');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Assigning...';
    
    try {
        const formData = new FormData();
        formData.append('composite_exam_id', COMPOSITE_EXAM_ID);
        formData.append(CSRF_TOKEN_NAME, CSRF_TOKEN);
        selected.forEach(id => formData.append('student_ids[]', id));
        
        const response = await fetch(APP_URL + '/ajax/admin/assign_composite_students.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            loadStudents();
            loadAssignedStudents();
        } else {
            showToast(data.message || 'Assignment failed', 'error');
        }
    } catch (error) {
        showToast('Error: ' + error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

async function loadAssignedStudents() {
    const container = document.getElementById('assignedStudentsList');
    if (!container) return;
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/get_assigned_composite_students.php?composite_exam_id=' + COMPOSITE_EXAM_ID, {
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const data = await response.json();
        
        if (data.success) {
            const total = data.total || 0;
            document.getElementById('assignedCountBadge').textContent = total + ' assigned';
            document.getElementById('assignedTabBadge').textContent = total;
            
            if (!data.students || data.students.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        <p class="mb-0">No students assigned yet</p>
                    </div>
                `;
                return;
            }
            
            let html = `
                <div class="small text-muted mb-3">${total} student(s) assigned to this exam</div>
                <div style="max-height: 500px; overflow-y: auto;">
            `;
            
            data.students.forEach(s => {
                const statusBadge = {
                    'assigned': '<span class="badge bg-info">Assigned</span>',
                    'started': '<span class="badge bg-warning text-dark">Started</span>',
                    'submitted': '<span class="badge bg-success">Submitted</span>',
                    'reset': '<span class="badge bg-secondary">Reset</span>'
                }[s.status] || '<span class="badge bg-secondary">' + s.status + '</span>';
                
                html += `
                    <div class="assigned-student-row">
                        <div class="flex-grow-1">
                            <strong class="small">${escapeHtml(s.matric_number)}</strong>
                            <div class="small text-muted">${escapeHtml(s.last_name)}, ${escapeHtml(s.first_name)}</div>
                        </div>
                        <div class="text-end me-2">
                            ${statusBadge}
                        </div>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeAssignedStudent(${s.assignment_id})" title="Remove">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
            });
            
            html += `</div>`;
            container.innerHTML = html;
        } else {
            container.innerHTML = `<div class="alert alert-danger">${data.message || 'Error loading'}</div>`;
        }
    } catch (error) {
        console.error('Error:', error);
        container.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
    }
}

async function removeAssignedStudent(assignmentId) {
    if (!confirm('Remove this student from the composite exam?')) return;
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/remove_composite_student.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ assignment_id: assignmentId })
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Student removed', 'success');
            loadAssignedStudents();
            loadStudents();
        } else {
            showToast(data.message || 'Failed to remove', 'error');
        }
    } catch (error) {
        showToast('Error: ' + error.message, 'error');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load assigned students on page load
document.addEventListener('DOMContentLoaded', function() {
    loadAssignedStudents();
});

// Reload assigned list when tab is clicked
document.getElementById('assignedTab')?.addEventListener('shown.bs.tab', function() {
    loadAssignedStudents();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>