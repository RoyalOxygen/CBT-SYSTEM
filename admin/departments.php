<?php
/**
 * CBT System - Department Management
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

if ($_SESSION['admin_role'] !== 'super_admin') {
    redirect(APP_URL . '/admin/dashboard.php', 'Access denied. Super Admin only.', 'error');
}

$pageTitle = 'Departments';
$db = getDB();
academic_ensure_schema($db);

$faculties = $db->query("SELECT * FROM faculties WHERE status = 1 ORDER BY faculty_name")->fetchAll();

$filters = [];
$params = [];
if (!empty($_GET['faculty'])) {
    $filters[] = 'd.faculty_id = ?';
    $params[] = intval($_GET['faculty']);
}
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $filters[] = 'd.status = ?';
    $params[] = intval($_GET['status']);
}
if (!empty($_GET['search'])) {
    $filters[] = '(d.dept_code LIKE ? OR d.dept_name LIKE ? OR f.faculty_name LIKE ?)';
    $p = '%' . $_GET['search'] . '%';
    $params[] = $p;
    $params[] = $p;
    $params[] = $p;
}
$where = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = DEFAULT_PAGE_SIZE;
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) FROM departments d LEFT JOIN faculties f ON d.faculty_id = f.id $where");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$stmt = $db->prepare("SELECT d.*, f.faculty_name,
        (SELECT COUNT(*) FROM students s WHERE s.department_id = d.id) AS student_count
    FROM departments d
    LEFT JOIN faculties f ON d.faculty_id = f.id
    $where
    ORDER BY f.faculty_name ASC, d.dept_name ASC
    LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$departments = $stmt->fetchAll();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Departments</h4>
    <button class="btn btn-primary" onclick="openDepartmentModal()">
        <i class="bi bi-plus-lg me-1"></i>Add Department
    </button>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search department or faculty..." value="<?php echo e($_GET['search'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <select name="faculty" class="form-select">
                    <option value="">All Faculties</option>
                    <?php foreach ($faculties as $f): ?>
                        <option value="<?php echo (int) $f['id']; ?>" <?php echo ($_GET['faculty'] ?? '') == $f['id'] ? 'selected' : ''; ?>><?php echo e($f['faculty_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="1" <?php echo ($_GET['status'] ?? '') === '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo ($_GET['status'] ?? '') === '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Department</th>
                        <th>Faculty</th>
                        <th>Students</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$departments): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No departments yet. Add a faculty first, then a department.</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($departments as $dept): ?>
                    <tr>
                        <td><strong><?php echo e($dept['dept_code']); ?></strong></td>
                        <td><?php echo e($dept['dept_name']); ?></td>
                        <td><?php echo e($dept['faculty_name'] ?? $dept['faculty'] ?? 'N/A'); ?></td>
                        <td><?php echo (int) $dept['student_count']; ?></td>
                        <td>
                            <?php if ((int) $dept['status'] === 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick='openDepartmentModal(<?php echo json_encode([
                                'id' => (int) $dept['id'],
                                'dept_code' => $dept['dept_code'],
                                'dept_name' => $dept['dept_name'],
                                'faculty_id' => (int) ($dept['faculty_id'] ?? 0),
                                'status' => (int) $dept['status'],
                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteDepartment(<?php echo (int) $dept['id']; ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-end mb-0">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>&faculty=<?php echo e($_GET['faculty'] ?? ''); ?>&status=<?php echo e($_GET['status'] ?? ''); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="departmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentModalTitle">Add Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="departmentForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="id" id="departmentId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Faculty *</label>
                        <select name="faculty_id" id="departmentFaculty" class="form-select" required>
                            <option value="">Select Faculty</option>
                            <?php foreach ($faculties as $f): ?>
                                <option value="<?php echo (int) $f['id']; ?>"><?php echo e($f['faculty_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$faculties): ?>
                        <div class="form-text">Add a faculty first on the Faculties page.</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department Name *</label>
                        <input type="text" name="dept_name" id="departmentName" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department Code</label>
                        <input type="text" name="dept_code" id="departmentCode" class="form-control" maxlength="20" placeholder="Auto-generated if empty">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="status" value="1" id="departmentStatus" checked>
                        <label class="form-check-label" for="departmentStatus">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openDepartmentModal(dept) {
    document.getElementById('departmentForm').reset();
    document.getElementById('departmentId').value = dept ? dept.id : '';
    document.getElementById('departmentName').value = dept ? dept.dept_name : '';
    document.getElementById('departmentCode').value = dept ? dept.dept_code : '';
    document.getElementById('departmentFaculty').value = dept && dept.faculty_id ? String(dept.faculty_id) : '';
    document.getElementById('departmentStatus').checked = dept ? Number(dept.status) === 1 : true;
    document.getElementById('departmentModalTitle').textContent = dept ? 'Edit Department' : 'Add Department';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('departmentModal')).show();
}

document.getElementById('departmentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    if (!formData.get('status')) {
        formData.set('status', '0');
    }
    try {
        const response = await fetch(APP_URL + '/ajax/admin/save_department.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('departmentModal')).hide();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('An error occurred', 'error');
    }
});

async function deleteDepartment(id) {
    if (!confirm('Delete this department? Students assigned to it must be moved first.')) return;
    try {
        const response = await fetch(APP_URL + '/ajax/admin/delete_department.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({ id: id })
        });
        const data = await response.json();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Failed to delete department', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
