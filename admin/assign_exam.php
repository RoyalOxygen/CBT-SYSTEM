<?php
/**
 * CBT System - Assign Students to Exams
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Assign Students to Exams';
$db = getDB();

// Get all published/draft exams
$exams = $db->query("SELECT e.id, e.exam_code, e.exam_title, e.has_batches, e.department_id, e.level_id, e.semester_id, e.session_id, d.dept_name, l.level_name FROM exams e LEFT JOIN departments d ON e.department_id = d.id LEFT JOIN levels l ON e.level_id = l.id WHERE e.status IN ('draft', 'published') ORDER BY e.created_at DESC")->fetchAll();

$selectedExamId = intval($_GET['exam_id'] ?? 0);
$selectedExam = null;
$batches = [];

if ($selectedExamId) {
    foreach ($exams as $e) {
        if ($e['id'] == $selectedExamId) {
            $selectedExam = $e;
            break;
        }
    }
    if ($selectedExam) {
        $stmt = $db->prepare("SELECT * FROM exam_batches WHERE exam_id = ? ORDER BY batch_date, start_time");
        $stmt->execute([$selectedExamId]);
        $batches = $stmt->fetchAll();
    }
}

// Get filter options
$departments = $db->query("SELECT * FROM departments WHERE status = 1 ORDER BY dept_name")->fetchAll();
$levels = $db->query("SELECT * FROM levels WHERE status = 1 ORDER BY level_order")->fetchAll();
$semesters = $db->query("SELECT * FROM semesters WHERE status = 1 ORDER BY semester_order")->fetchAll();
$sessions = $db->query("SELECT * FROM sessions WHERE status = 1 ORDER BY session_name DESC")->fetchAll();

// Generate CSRF token for JavaScript
$csrf_token_js = generateCSRFToken();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-journal-check me-2"></i>Select Exam</h5>
            </div>
            <div class="card-body">
                <div class="list-group" id="examList">
                    <?php foreach ($exams as $e): ?>
                    <a href="?exam_id=<?php echo $e['id']; ?>" class="list-group-item list-group-item-action <?php echo $selectedExamId == $e['id'] ? 'active' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo e($e['exam_code']); ?></strong><br>
                                <small><?php echo e(substr($e['exam_title'], 0, 40)); ?></small>
                            </div>
                            <span class="badge bg-<?php echo $e['has_batches'] ? 'info' : 'secondary'; ?>">
                                <?php echo $e['has_batches'] ? 'Batched' : 'No Batch'; ?>
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php if (empty($exams)): ?>
                        <div class="list-group-item text-muted">No available exams</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <?php if ($selectedExam): ?>
        <!-- Exam Details -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo e($selectedExam['exam_code'] . ' - ' . $selectedExam['exam_title']); ?></h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createBatchModal">
                    <i class="bi bi-plus-lg me-1"></i>Create Batch
                </button>
            </div>
            <div class="card-body">
                <p><strong>Department:</strong> <?php echo e($selectedExam['dept_name'] ?? 'All'); ?> | 
                   <strong>Level:</strong> <?php echo e($selectedExam['level_name'] ?? 'All'); ?></p>
                
                <!-- Batch Tabs -->
                <?php if ($selectedExam['has_batches'] && !empty($batches)): ?>
                <ul class="nav nav-tabs mb-3" id="batchTabs">
                    <?php foreach ($batches as $i => $batch): ?>
                    <li class="nav-item">
                        <button class="nav-link <?php echo $i === 0 ? 'active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#batch<?php echo $batch['id']; ?>">
                            <?php echo e($batch['batch_name']); ?><br>
                            <small><?php echo date('M d h:i A', strtotime($batch['batch_date'] . ' ' . $batch['start_time'])); ?></small>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                
                <!-- Student Filter & Assignment -->
                <form id="assignForm" class="mb-3">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="exam_id" value="<?php echo $selectedExamId; ?>">
                    
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <select id="filterDept" class="form-select form-select-sm">
                                <option value="">Department</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo ($selectedExam['department_id'] ?? '') == $d['id'] ? 'selected' : ''; ?>><?php echo e($d['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterLevel" class="form-select form-select-sm">
                                <option value="">Level</option>
                                <?php foreach ($levels as $l): ?>
                                    <option value="<?php echo $l['id']; ?>" <?php echo ($selectedExam['level_id'] ?? '') == $l['id'] ? 'selected' : ''; ?>><?php echo e($l['level_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterSemester" class="form-select form-select-sm">
                                <option value="">Semester</option>
                                <?php foreach ($semesters as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo e($s['semester_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterSession" class="form-select form-select-sm">
                                <option value="">Session</option>
                                <?php foreach ($sessions as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo e($s['session_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllStudents()">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllStudents()">Deselect All</button>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-check me-1"></i>Assign Selected Students
                        </button>
                    </div>
                    
                    <div id="studentsList" class="border rounded p-3" style="max-height:400px;overflow-y:auto;">
                        <p class="text-muted text-center">Select filters to load students</p>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Currently Assigned -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Assigned Students (<span id="assignedCount">0</span>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive" id="assignedStudentsTable">
                    <p class="text-muted text-center">Loading...</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-arrow-left-circle fs-1"></i>
                <p class="mt-2">Select an exam from the list to manage student assignments</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Batch Modal -->
<div class="modal fade" id="createBatchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Create Exam Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createBatchForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="exam_id" value="<?php echo $selectedExamId; ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Batch Name *</label>
                            <input type="text" name="batch_name" class="form-control" placeholder="e.g., Batch A" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batch Date *</label>
                            <input type="date" name="batch_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Time *</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Time *</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Duration (min) *</label>
                            <input type="number" name="duration" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Capacity</label>
                            <input type="number" name="capacity" class="form-control" placeholder="Max students">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="biometric_required" value="1" id="batchBio" checked>
                                <label class="form-check-label" for="batchBio">Require Biometric Verification</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Batch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($selectedExam): ?>
<script>
// Check if variables are already defined to avoid redeclaration
if (typeof window.csrfTokenInitialized === 'undefined') {
    window.csrfTokenInitialized = true;
    window.CSRF_TOKEN = '<?php echo $csrf_token_js; ?>';
    window.CSRF_TOKEN_NAME = '<?php echo CSRF_TOKEN_NAME; ?>';
    window.APP_URL = '<?php echo APP_URL; ?>';
}

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
        document.getElementById('studentsList').innerHTML = '<p class="text-muted text-center">Select at least one filter</p>';
        return;
    }
    
    // Show loading
    document.getElementById('studentsList').innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading students...</div>';
    
    try {
        const params = new URLSearchParams({ 
            department: dept, 
            level: level, 
            semester: semester, 
            session: session,
            exam_id: '<?php echo $selectedExamId; ?>' 
        });
        const response = await fetch(window.APP_URL + '/ajax/admin/get_students.php?' + params, {
            headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN }
        });
        const data = await response.json();
        
        if (data.success) {
            currentStudents = data.students;
            renderStudents(data.students);
        } else {
            document.getElementById('studentsList').innerHTML = '<p class="text-danger text-center">' + (data.message || 'Error loading students') + '</p>';
        }
    } catch (error) {
        console.error('Error loading students:', error);
        document.getElementById('studentsList').innerHTML = '<p class="text-danger text-center">Error loading students. Check console for details.</p>';
    }
}

function renderStudents(students) {
    if (!students || !students.length) {
        document.getElementById('studentsList').innerHTML = '<p class="text-muted text-center">No students found with selected filters</p>';
        return;
    }
    
    let html = '<div class="small text-muted mb-2">Found ' + students.length + ' student(s)</div>';
    html += students.map(s => `
        <div class="form-check mb-2">
            <input class="form-check-input student-checkbox" type="checkbox" value="${s.id}" id="student${s.id}">
            <label class="form-check-label w-100" for="student${s.id}">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>${escapeHtml(s.matric_number)}</strong><br>
                        <small>${escapeHtml(s.last_name)}, ${escapeHtml(s.first_name)}</small>
                    </div>
                    <small class="text-muted">${escapeHtml(s.dept_name || 'N/A')} | ${escapeHtml(s.level_name || 'N/A')}</small>
                </div>
            </label>
        </div>
    `).join('');
    html += '<div class="mt-2 pt-2 border-top"><small class="text-muted"><i class="bi bi-info-circle me-1"></i><span id="selectedCount">0</span> student(s) selected</small></div>';
    
    document.getElementById('studentsList').innerHTML = html;
    
    // Add event listeners to update selected count
    document.querySelectorAll('.student-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const count = document.querySelectorAll('.student-checkbox:checked').length;
    const countSpan = document.getElementById('selectedCount');
    if (countSpan) {
        countSpan.textContent = count;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function selectAllStudents() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = true);
    updateSelectedCount();
}

function deselectAllStudents() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
    updateSelectedCount();
}

// Handle assignment
document.getElementById('assignForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const selectedStudents = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
    if (!selectedStudents.length) {
        if (typeof showToast !== 'undefined') {
            showToast('Please select at least one student', 'error');
        } else {
            alert('Please select at least one student');
        }
        return;
    }
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalHtml = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Assigning...';
    
    const formData = new FormData();
    formData.append('exam_id', '<?php echo $selectedExamId; ?>');
    formData.append(window.CSRF_TOKEN_NAME, window.CSRF_TOKEN);
    selectedStudents.forEach(id => formData.append('student_ids[]', id));
    
    <?php if ($selectedExam['has_batches'] && !empty($batches)): ?>
    formData.append('batch_id', '<?php echo $batches[0]['id']; ?>');
    <?php endif; ?>
    
    try {
        const response = await fetch(window.APP_URL + '/ajax/admin/assign_students.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            if (typeof showToast !== 'undefined') {
                showToast(data.message, 'success');
            } else {
                alert(data.message);
            }
            loadAssignedStudents();
            loadStudents();
        } else {
            if (typeof showToast !== 'undefined') {
                showToast(data.message, 'error');
            } else {
                alert(data.message);
            }
        }
    } catch (error) {
        console.error('Assignment error:', error);
        if (typeof showToast !== 'undefined') {
            showToast('Assignment failed: ' + error.message, 'error');
        } else {
            alert('Assignment failed: ' + error.message);
        }
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHtml;
    }
});

// Load assigned students
async function loadAssignedStudents() {
    try {
        const response = await fetch(window.APP_URL + '/ajax/admin/get_assigned_students.php?exam_id=<?php echo $selectedExamId; ?>', {
            headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN }
        });
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('assignedCount').textContent = data.total || 0;
            
            if (!data.students || !data.students.length) {
                document.getElementById('assignedStudentsTable').innerHTML = '<p class="text-muted text-center">No students assigned yet</p>';
                return;
            }
            
            let tableHtml = `
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Matric</th>
                            <th>Name</th>
                            <th>Batch</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.students.forEach(s => {
                tableHtml += `
                    <tr>
                        <td><strong>${escapeHtml(s.matric_number)}</strong></td>
                        <td>${escapeHtml(s.last_name)}, ${escapeHtml(s.first_name)}</td>
                        <td>${escapeHtml(s.batch_name || 'N/A')}</td>
                        <td><span class="badge bg-${s.status === 'assigned' ? 'success' : 'warning'}">${s.status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="removeStudent(${s.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tableHtml += '</tbody></table>';
            document.getElementById('assignedStudentsTable').innerHTML = tableHtml;
        } else {
            document.getElementById('assignedStudentsTable').innerHTML = '<p class="text-danger text-center">' + (data.message || 'Error loading assigned students') + '</p>';
        }
    } catch (error) {
        console.error('Error loading assigned students:', error);
        document.getElementById('assignedStudentsTable').innerHTML = '<p class="text-danger text-center">Error loading assigned students</p>';
    }
}

async function removeStudent(assignmentId) {
    if (!confirm('Remove this student from the exam?')) return;
    
    try {
        const response = await fetch(window.APP_URL + '/ajax/admin/remove_student.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN },
            body: JSON.stringify({ assignment_id: assignmentId })
        });
        const data = await response.json();
        
        if (data.success) {
            if (typeof showToast !== 'undefined') {
                showToast('Student removed', 'success');
            } else {
                alert('Student removed');
            }
            loadAssignedStudents();
            loadStudents();
        } else {
            if (typeof showToast !== 'undefined') {
                showToast(data.message, 'error');
            } else {
                alert(data.message);
            }
        }
    } catch (error) {
        console.error('Remove error:', error);
        if (typeof showToast !== 'undefined') {
            showToast('Failed to remove student', 'error');
        } else {
            alert('Failed to remove student');
        }
    }
}

// Create batch
document.getElementById('createBatchForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalHtml = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
    
    const formData = new FormData(this);
    formData.append(window.CSRF_TOKEN_NAME, window.CSRF_TOKEN);
    
    try {
        const response = await fetch(window.APP_URL + '/ajax/admin/create_batch.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            if (typeof showToast !== 'undefined') {
                showToast(data.message, 'success');
            } else {
                alert(data.message);
            }
            bootstrap.Modal.getInstance(document.getElementById('createBatchModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            if (typeof showToast !== 'undefined') {
                showToast(data.message, 'error');
            } else {
                alert(data.message);
            }
        }
    } catch (error) {
        console.error('Create batch error:', error);
        if (typeof showToast !== 'undefined') {
            showToast('Failed to create batch', 'error');
        } else {
            alert('Failed to create batch');
        }
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHtml;
    }
});

// Initial load
<?php if ($selectedExam): ?>
loadAssignedStudents();
<?php endif; ?>
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>