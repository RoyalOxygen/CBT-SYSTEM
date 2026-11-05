<?php
/**
 * CBT System - Bulk Upload Students
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';
node_require('student_edit');

$pageTitle = 'Bulk Upload Students';

$db = getDB();
$departments = $db->query("SELECT * FROM departments WHERE status = 1 ORDER BY dept_name")->fetchAll();
$levels = $db->query("SELECT * FROM levels WHERE status = 1 ORDER BY level_order")->fetchAll();
$semesters = $db->query("SELECT * FROM semesters WHERE status = 1 ORDER BY semester_order")->fetchAll();
$sessions = $db->query("SELECT * FROM sessions WHERE status = 1 ORDER BY session_name DESC")->fetchAll();
<<<<<<< HEAD
$uploadMethod = ($_GET['method'] ?? 'csv') === 'ai' ? 'ai' : 'csv';
=======
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
<<<<<<< HEAD
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-upload me-2"></i>Bulk Upload Students</h5>
                <a href="students.php" class="btn btn-sm btn-outline-secondary">Back to Students</a>
            </div>
            <div class="card-body">
                <ul class="nav nav-pills mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $uploadMethod === 'csv' ? 'active' : ''; ?>" href="?method=csv">
                            <i class="bi bi-filetype-csv me-1"></i>CSV upload
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $uploadMethod === 'ai' ? 'active' : ''; ?>" href="?method=ai">
                            <i class="bi bi-stars me-1"></i>AI upload
                        </a>
                    </li>
                </ul>

                <?php if ($uploadMethod === 'csv'): ?>
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle me-2"></i>CSV instructions</h6>
                    <ol class="mb-0">
                        <li>Download the CSV template</li>
                        <li>Fill in student details without changing column headers</li>
                        <li>Save as CSV and upload below</li>
                    </ol>
                </div>
                <a href="<?php echo APP_URL; ?>/ajax/admin/download_template.php" class="btn btn-outline-primary mb-4">
                    <i class="bi bi-download me-1"></i>Download CSV Template
                </a>
                <?php else: ?>
                <div class="alert alert-info">
                    <h6><i class="bi bi-stars me-2"></i>AI instructions</h6>
                    <p class="mb-2">Upload a PDF or Word document that lists students. Groq extracts first name, last name, optional other name, and either a matric number or registration number. Remaining fields stay empty and status is set to active.</p>
                    <p class="mb-0 small">A Groq API key is required in Settings. Full names are split into first, last, and other names when separate columns are not present.</p>
                </div>
                <?php endif; ?>

                <form id="bulkUploadForm" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="upload_method" value="<?php echo e($uploadMethod); ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">Leave empty</option>
=======
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-upload me-2"></i>Bulk Upload Students</h5>
            </div>
            <div class="card-body">
                <!-- Template Download -->
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle me-2"></i>Instructions</h6>
                    <ol class="mb-0">
                        <li>Download the CSV template below</li>
                        <li>Fill in student details (do not change column headers)</li>
                        <li>Save as CSV file</li>
                        <li>Upload using the form below</li>
                    </ol>
                </div>
                
                <a href="<?php echo APP_URL; ?>/ajax/admin/download_template.php" class="btn btn-outline-primary mb-4">
                    <i class="bi bi-download me-1"></i>Download CSV Template
                </a>
                
                <!-- Upload Form -->
                <form id="bulkUploadForm" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Department *</label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Select</option>
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo e($d['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
<<<<<<< HEAD
                            <label class="form-label">Level</label>
                            <select name="level_id" class="form-select">
                                <option value="">Leave empty</option>
=======
                            <label class="form-label">Level *</label>
                            <select name="level_id" class="form-select" required>
                                <option value="">Select</option>
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
                                <?php foreach ($levels as $l): ?>
                                    <option value="<?php echo $l['id']; ?>"><?php echo e($l['level_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
<<<<<<< HEAD
                            <label class="form-label">Semester</label>
                            <select name="semester_id" class="form-select">
                                <option value="">Leave empty</option>
=======
                            <label class="form-label">Semester *</label>
                            <select name="semester_id" class="form-select" required>
                                <option value="">Select</option>
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
                                <?php foreach ($semesters as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo e($s['semester_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
<<<<<<< HEAD
                            <label class="form-label">Session</label>
                            <select name="session_id" class="form-select">
                                <option value="">Leave empty</option>
=======
                            <label class="form-label">Session *</label>
                            <select name="session_id" class="form-select" required>
                                <option value="">Select</option>
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
                                <?php foreach ($sessions as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo e($s['session_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
<<<<<<< HEAD
                            <?php if ($uploadMethod === 'csv'): ?>
                                <label class="form-label">CSV File *</label>
                                <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                                <small class="text-muted">Max 5MB. Columns: matric_number, first_name, last_name, other_name, email, phone, gender, date_of_birth</small>
                            <?php else: ?>
                                <label class="form-label">PDF or Word file *</label>
                                <input type="file" name="document" class="form-control" accept=".pdf,.docx,.doc,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                                <small class="text-muted">Max 20MB. Required fields extracted: first name, last name, optional other name, and matric or registration number.</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary" id="bulkUploadBtn">
                            <i class="bi bi-cloud-upload me-1"></i><?php echo $uploadMethod === 'ai' ? 'Extract & Import' : 'Upload & Process'; ?>
                        </button>
                    </div>
                </form>

=======
                            <label class="form-label">CSV File *</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                            <small class="text-muted">Max 5MB. Columns: matric_number, first_name, last_name, other_name, email, phone, gender, date_of_birth</small>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-upload me-1"></i>Upload & Process
                        </button>
                        <a href="students.php" class="btn btn-secondary ms-2">Back to Students</a>
                    </div>
                </form>
                
                <!-- Results -->
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
                <div id="uploadResults" class="mt-4 d-none">
                    <h6>Upload Results:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm" id="resultsTable">
                            <thead><tr><th>Row</th><th>Matric</th><th>Status</th><th>Message</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('bulkUploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
<<<<<<< HEAD
    const btn = document.getElementById('bulkUploadBtn');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    document.getElementById('loadingOverlay').classList.remove('d-none');

=======
    
    document.getElementById('loadingOverlay').classList.remove('d-none');
    
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
    try {
        const response = await fetch(APP_URL + '/ajax/admin/bulk_upload_students.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
<<<<<<< HEAD

        if (data.success) {
            showToast(`Upload complete: ${data.imported} imported, ${data.failed} failed`, 'success');
            const tbody = document.querySelector('#resultsTable tbody');
            tbody.innerHTML = (data.results || []).map(r => `
=======
        
        if (data.success) {
            showToast(`Upload complete: ${data.imported} imported, ${data.failed} failed`, 'success');
            
            // Show detailed results
            const tbody = document.querySelector('#resultsTable tbody');
            tbody.innerHTML = data.results.map(r => `
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
                <tr class="${r.status === 'success' ? 'table-success' : 'table-danger'}">
                    <td>${r.row}</td>
                    <td>${r.matric || 'N/A'}</td>
                    <td>${r.status}</td>
                    <td>${r.message}</td>
                </tr>
            `).join('');
            document.getElementById('uploadResults').classList.remove('d-none');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Upload failed', 'error');
    } finally {
<<<<<<< HEAD
        btn.disabled = false;
        btn.innerHTML = originalHtml;
=======
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
        document.getElementById('loadingOverlay').classList.add('d-none');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
