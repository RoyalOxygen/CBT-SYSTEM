<?php
/**
 * CBT System - Student Management
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Student Management';

$db = getDB();

// Get filter options
$departments = $db->query("SELECT * FROM departments WHERE status = 1 ORDER BY dept_name")->fetchAll();
$levels = $db->query("SELECT * FROM levels WHERE status = 1 ORDER BY level_order")->fetchAll();
$semesters = $db->query("SELECT * FROM semesters WHERE status = 1 ORDER BY semester_order")->fetchAll();
$sessions = $db->query("SELECT * FROM sessions WHERE status = 1 ORDER BY session_name DESC")->fetchAll();

// Handle filters
$filters = [];
$params = [];

if (!empty($_GET['department'])) { $filters[] = "s.department_id = ?"; $params[] = $_GET['department']; }
if (!empty($_GET['level'])) { $filters[] = "s.level_id = ?"; $params[] = $_GET['level']; }
if (!empty($_GET['semester'])) { $filters[] = "s.semester_id = ?"; $params[] = $_GET['semester']; }
if (!empty($_GET['session'])) { $filters[] = "s.session_id = ?"; $params[] = $_GET['session']; }
if (!empty($_GET['status'])) { $filters[] = "s.status = ?"; $params[] = $_GET['status']; }
if (!empty($_GET['search'])) { $filters[] = "(s.matric_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?)"; $p = "%{$_GET['search']}%"; $params = array_merge($params, [$p, $p, $p, $p]); }

$where = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = DEFAULT_PAGE_SIZE;
$offset = ($page - 1) * $perPage;

// Get students with pagination
$countStmt = $db->prepare("SELECT COUNT(*) FROM students s $where");
$countStmt->execute($params);
$totalStudents = $countStmt->fetchColumn();

$stmt = $db->prepare("SELECT s.*, d.dept_name, l.level_name, sem.semester_name, ses.session_name 
    FROM students s 
    LEFT JOIN departments d ON s.department_id = d.id 
    LEFT JOIN levels l ON s.level_id = l.id 
    LEFT JOIN semesters sem ON s.semester_id = sem.id 
    LEFT JOIN sessions ses ON s.session_id = ses.id 
    $where 
    ORDER BY s.created_at DESC 
    LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$students = $stmt->fetchAll();

$totalPages = ceil($totalStudents / $perPage);

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.student-view-tabs .nav-link {
    color: #1a1a2e;
    font-weight: 500;
}
.student-view-tabs .nav-link.active {
    color: #1a73e8;
    border-bottom-color: #1a73e8;
}
.exam-item {
    padding: 12px 15px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    transition: all 0.2s;
    margin-bottom: 8px;
}
.exam-item:hover {
    background: #f8f9fa;
    border-color: #1a73e8;
}
.exam-item .exam-code {
    font-size: 0.7rem;
    color: #6c757d;
    font-weight: 600;
}
.exam-item .exam-title {
    font-weight: 500;
    color: #1a1a2e;
}
.exam-item .exam-score {
    font-weight: 700;
}
.exam-item .exam-score.pass { color: #34a853; }
.exam-item .exam-score.fail { color: #ea4335; }
.exam-item .exam-status {
    font-size: 0.65rem;
    padding: 2px 10px;
    border-radius: 12px;
}
.exam-item .exam-status.assigned { background: #e8f0fe; color: #1a73e8; }
.exam-item .exam-status.started { background: #fef9e7; color: #f9ab00; }
.exam-item .exam-status.submitted { background: #e6f4ea; color: #34a853; }
.exam-item .exam-status.auto_submitted { background: #fce8e6; color: #ea4335; }
.exam-item .exam-status.timed_out { background: #fce8e6; color: #ea4335; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Students</h4>
    <div>
        <a href="bulk_upload.php" class="btn btn-info me-2">
            <i class="bi bi-upload me-1"></i>Bulk Upload
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
            <i class="bi bi-plus-lg me-1"></i>Add Student
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search by name, matric, email..." value="<?php echo e($_GET['search'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <select name="department" class="form-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($_GET['department'] ?? '') == $d['id'] ? 'selected' : ''; ?>><?php echo e($d['dept_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="level" class="form-select">
                    <option value="">All Levels</option>
                    <?php foreach ($levels as $l): ?>
                        <option value="<?php echo $l['id']; ?>" <?php echo ($_GET['level'] ?? '') == $l['id'] ? 'selected' : ''; ?>><?php echo e($l['level_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="session" class="form-select">
                    <option value="">All Sessions</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($_GET['session'] ?? '') == $s['id'] ? 'selected' : ''; ?>><?php echo e($s['session_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="1" <?php echo ($_GET['status'] ?? '') === '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo ($_GET['status'] ?? '') === '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table" id="studentsTable">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Matric No</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Level</th>
                        <th>Session</th>
                        <th>Biometric</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td>
                            <?php if ($student['photo']): ?>
                                <img src="<?php echo APP_URL; ?>/assets/uploads/student_photos/<?php echo e($student['photo']); ?>" class="rounded-circle" width="40" height="40" style="object-fit:cover;">
                            <?php else: ?>
                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                    <i class="bi bi-person"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo e($student['matric_number']); ?></strong></td>
                        <td><?php echo e($student['last_name'] . ', ' . $student['first_name']); ?></td>
                        <td><?php echo e($student['dept_name'] ?? 'N/A'); ?></td>
                        <td><?php echo e($student['level_name'] ?? 'N/A'); ?></td>
                        <td><?php echo e($student['session_name'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($student['fingerprint_enrolled']): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Enrolled</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Not Enrolled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $student['status'] ? 'success' : 'danger'; ?>">
                                <?php echo $student['status'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-info" onclick="viewStudent(<?php echo $student['id']; ?>)" title="View">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="editStudent(<?php echo $student['id']; ?>)" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteStudent(<?php echo $student['id']; ?>)" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No students found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page-1; ?>&<?php echo http_build_query($_GET); ?>">Previous</a>
                </li>
                <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => 1])); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page+1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => 1])); ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addStudentForm" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Matric Number *</label>
                            <input type="text" name="matric_number" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Other Name</label>
                            <input type="text" name="other_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department *</label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo e($d['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Level *</label>
                            <select name="level_id" class="form-select" required>
                                <option value="">Select Level</option>
                                <?php foreach ($levels as $l): ?>
                                    <option value="<?php echo $l['id']; ?>"><?php echo e($l['level_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Semester *</label>
                            <select name="semester_id" class="form-select" required>
                                <option value="">Select Semester</option>
                                <?php foreach ($semesters as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo e($s['semester_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Session *</label>
                            <select name="session_id" class="form-select" required>
                                <option value="">Select Session</option>
                                <?php foreach ($sessions as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo e($s['session_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="auto_password" value="1" id="autoPassword" checked>
                                <label class="form-check-label" for="autoPassword">Auto-generate password (matric number as default)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editStudentForm" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="student_id" id="editStudentId">
                <div class="modal-body" id="editStudentBody">
                    <!-- Loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person me-2"></i>Student Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewStudentBody">
                <!-- Loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('addStudentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/add_student.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('addStudentModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('An error occurred', 'error');
    }
});

async function editStudent(id) {
    try {
        const response = await fetch(APP_URL + '/ajax/admin/get_student.php?id=' + id, {
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('editStudentId').value = id;
            document.getElementById('editStudentBody').innerHTML = data.html;
            new bootstrap.Modal(document.getElementById('editStudentModal')).show();
        }
    } catch (error) {
        showToast('Failed to load student data', 'error');
    }
}

document.getElementById('editStudentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/update_student.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('editStudentModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('An error occurred', 'error');
    }
});

async function viewStudent(id) {
    try {
        const response = await fetch(APP_URL + '/ajax/admin/view_student.php?id=' + id, {
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('viewStudentBody').innerHTML = data.html;
            new bootstrap.Modal(document.getElementById('viewStudentModal')).show();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Failed to load student profile', 'error');
    }
}

async function deleteStudent(id) {
    if (!confirm('Are you sure? This will also delete all associated records.')) return;
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/delete_student.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({ student_id: id })
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Student deleted successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Failed to delete student', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>