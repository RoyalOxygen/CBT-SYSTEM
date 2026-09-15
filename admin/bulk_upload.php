<?php
/**
 * CBT System - Bulk Upload Students
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Bulk Upload Students';

$db = getDB();
$departments = $db->query("SELECT * FROM departments WHERE status = 1 ORDER BY dept_name")->fetchAll();
$levels = $db->query("SELECT * FROM levels WHERE status = 1 ORDER BY level_order")->fetchAll();
$semesters = $db->query("SELECT * FROM semesters WHERE status = 1 ORDER BY semester_order")->fetchAll();
$sessions = $db->query("SELECT * FROM sessions WHERE status = 1 ORDER BY session_name DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
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
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo e($d['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Level *</label>
                            <select name="level_id" class="form-select" required>
                                <option value="">Select</option>
                                <?php foreach ($levels as $l): ?>
                                    <option value="<?php echo $l['id']; ?>"><?php echo e($l['level_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Semester *</label>
                            <select name="semester_id" class="form-select" required>
                                <option value="">Select</option>
                                <?php foreach ($semesters as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo e($s['semester_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Session *</label>
                            <select name="session_id" class="form-select" required>
                                <option value="">Select</option>
                                <?php foreach ($sessions as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo e($s['session_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
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
    
    document.getElementById('loadingOverlay').classList.remove('d-none');
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/bulk_upload_students.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast(`Upload complete: ${data.imported} imported, ${data.failed} failed`, 'success');
            
            // Show detailed results
            const tbody = document.querySelector('#resultsTable tbody');
            tbody.innerHTML = data.results.map(r => `
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
        document.getElementById('loadingOverlay').classList.add('d-none');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
