<?php
/**
 * CBT System - Faculty Management
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

if ($_SESSION['admin_role'] !== 'super_admin') {
    redirect(APP_URL . '/admin/dashboard.php', 'Access denied. Super Admin only.', 'error');
}

$pageTitle = 'Faculties';
$db = getDB();
academic_ensure_schema($db);

$filters = [];
$params = [];
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $filters[] = 'f.status = ?';
    $params[] = intval($_GET['status']);
}
if (!empty($_GET['search'])) {
    $filters[] = '(f.faculty_code LIKE ? OR f.faculty_name LIKE ?)';
    $p = '%' . $_GET['search'] . '%';
    $params[] = $p;
    $params[] = $p;
}
$where = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = DEFAULT_PAGE_SIZE;
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) FROM faculties f $where");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$stmt = $db->prepare("SELECT f.*,
        (SELECT COUNT(*) FROM departments d WHERE d.faculty_id = f.id) AS department_count
    FROM faculties f
    $where
    ORDER BY f.faculty_name ASC
    LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$faculties = $stmt->fetchAll();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Faculties</h4>
    <button class="btn btn-primary" onclick="openFacultyModal()">
        <i class="bi bi-plus-lg me-1"></i>Add Faculty
    </button>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Search by name or code..." value="<?php echo e($_GET['search'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
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
                        <th>Faculty</th>
                        <th>Departments</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$faculties): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No faculties yet. Add one to get started.</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($faculties as $faculty): ?>
                    <tr>
                        <td><strong><?php echo e($faculty['faculty_code']); ?></strong></td>
                        <td><?php echo e($faculty['faculty_name']); ?></td>
                        <td><?php echo (int) $faculty['department_count']; ?></td>
                        <td>
                            <?php if ((int) $faculty['status'] === 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick='openFacultyModal(<?php echo json_encode([
                                'id' => (int) $faculty['id'],
                                'faculty_code' => $faculty['faculty_code'],
                                'faculty_name' => $faculty['faculty_name'],
                                'status' => (int) $faculty['status'],
                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteFaculty(<?php echo (int) $faculty['id']; ?>)">
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
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>&status=<?php echo e($_GET['status'] ?? ''); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="facultyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="facultyModalTitle">Add Faculty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="facultyForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="id" id="facultyId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Faculty Name *</label>
                        <input type="text" name="faculty_name" id="facultyName" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Faculty Code</label>
                        <input type="text" name="faculty_code" id="facultyCode" class="form-control" maxlength="20" placeholder="Auto-generated if empty">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="status" value="1" id="facultyStatus" checked>
                        <label class="form-check-label" for="facultyStatus">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Faculty</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openFacultyModal(faculty) {
    document.getElementById('facultyForm').reset();
    document.getElementById('facultyId').value = faculty ? faculty.id : '';
    document.getElementById('facultyName').value = faculty ? faculty.faculty_name : '';
    document.getElementById('facultyCode').value = faculty ? faculty.faculty_code : '';
    document.getElementById('facultyStatus').checked = faculty ? Number(faculty.status) === 1 : true;
    document.getElementById('facultyModalTitle').textContent = faculty ? 'Edit Faculty' : 'Add Faculty';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('facultyModal')).show();
}

document.getElementById('facultyForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    if (!formData.get('status')) {
        formData.set('status', '0');
    }
    try {
        const response = await fetch(APP_URL + '/ajax/admin/save_faculty.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('facultyModal')).hide();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('An error occurred', 'error');
    }
});

async function deleteFaculty(id) {
    if (!confirm('Delete this faculty? Departments assigned to it must be moved first.')) return;
    try {
        const response = await fetch(APP_URL + '/ajax/admin/delete_faculty.php', {
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
        showToast('Failed to delete faculty', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
