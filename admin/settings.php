<?php
/**
 * CBT System - System Settings
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

// Only super admin can access
if ($_SESSION['admin_role'] !== 'super_admin') {
    redirect(APP_URL . '/admin/dashboard.php', 'Access denied. Super Admin only.', 'error');
}

$pageTitle = 'System Settings';
$db = getDB();

// Get current settings
$settings = [];
$stmt = $db->query("SELECT * FROM settings ORDER BY setting_group, setting_key");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-gear-fill me-2"></i>System Settings</h5>
            </div>
            <div class="card-body">
                <form id="settingsForm">
                    <?php echo csrfField(); ?>
                    
                    <h6 class="text-muted mb-3">General Settings</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Institution Name</label>
                            <input type="text" name="institution_name" class="form-control" value="<?php echo e($settings['institution_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Timezone</label>
                            <select name="timezone" class="form-select">
                                <option value="Africa/Lagos" <?php echo ($settings['timezone'] ?? '') === 'Africa/Lagos' ? 'selected' : ''; ?>>Africa/Lagos (WAT)</option>
                                <option value="UTC" <?php echo ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                <option value="Europe/London" <?php echo ($settings['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>Europe/London</option>
                            </select>
                        </div>
                    </div>
                    
                    <h6 class="text-muted mb-3">Exam Defaults</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Default Duration (min)</label>
                            <input type="number" name="default_duration" class="form-control" value="<?php echo e($settings['default_duration'] ?? '60'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default Pass Mark (%)</label>
                            <input type="number" name="default_pass_mark" class="form-control" value="<?php echo e($settings['default_pass_mark'] ?? '40'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default Question Limit</label>
                            <input type="number" name="default_question_limit" class="form-control" value="<?php echo e($settings['default_question_limit'] ?? '50'); ?>">
                        </div>
                    </div>
                    
                    <h6 class="text-muted mb-3">Security Settings</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Session Timeout (minutes)</label>
                            <input type="number" name="session_timeout" class="form-control" value="<?php echo e($settings['session_timeout'] ?? '30'); ?>">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" id="maintMode" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintMode">Maintenance Mode</label>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="text-muted mb-3">Biometric Settings</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="biometric_enabled" value="1" id="bioEnabled" <?php echo ($settings['biometric_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="bioEnabled">Enable Biometric Verification</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Match Threshold (%)</label>
                            <input type="number" name="biometric_threshold" class="form-control" value="<?php echo e($settings['biometric_threshold'] ?? '90'); ?>" min="50" max="100">
                        </div>
                    </div>

                    <h6 class="text-muted mb-3">Face login (SAMS ArcFace)</h6>
                    <p class="small text-muted">Point this node at the InsightFace FastAPI service. Offline halls typically use <code>http://127.0.0.1:9000</code>; the online hub can use a remote URL.</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label">Face API URL</label>
                            <input type="url" name="face_api_url" class="form-control" placeholder="http://127.0.0.1:9000" value="<?php echo e($settings['face_api_url'] ?? 'http://127.0.0.1:9000'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Timeout (seconds)</label>
                            <input type="number" name="face_timeout" class="form-control" min="5" max="180" value="<?php echo e($settings['face_timeout'] ?? '60'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Match threshold</label>
                            <input type="number" name="face_match_threshold" class="form-control" step="0.01" min="0.1" max="1" value="<?php echo e($settings['face_match_threshold'] ?? '0.50'); ?>">
                            <div class="form-text">Cosine similarity, 0.50 default.</div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="face_login_enabled" value="1" id="faceLoginEnabled" <?php echo ($settings['face_login_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="faceLoginEnabled">Enable student face login</label>
                            </div>
                            <div class="form-text">When enabled, students can only sign in on the face login page. Matric login is hidden.</div>
                        </div>
                    </div>

                    <h6 class="text-muted mb-3">Exam attendance requirement</h6>
                    <p class="small text-muted">Overall SAMS attendance rate is stored on each student during hub pull and hub/leaf sync. When the check is on, students below the threshold cannot start exams.</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Minimum attendance (%)</label>
                            <input type="number" name="attendance_threshold" class="form-control" min="0" max="100" step="0.1" value="<?php echo e($settings['attendance_threshold'] ?? '75'); ?>">
                        </div>
                        <div class="col-md-8">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="attendance_check_enabled" value="1" id="attendanceCheckEnabled" <?php echo ($settings['attendance_check_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="attendanceCheckEnabled">Block exams below this attendance rate</label>
                            </div>
                        </div>
                    </div>

                    <?php if (node_is_hub()): ?>
                    <h6 class="text-muted mb-3">Attendance (SAMS) sync</h6>
                    <p class="small text-muted">The online hub pulls students, passport photos and ArcFace templates from TSU-SAMS. Offline halls receive the same records through CBT hub/leaf sync.</p>
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label">SAMS base URL</label>
                            <input type="url" name="sams_url" class="form-control" placeholder="https://attendance.example.edu" value="<?php echo e($settings['sams_url'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sync token</label>
                            <input type="text" name="sams_sync_token" class="form-control" autocomplete="off" value="<?php echo e($settings['sams_sync_token'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sams_sync_enabled" value="1" id="samsSyncEnabled" <?php echo ($settings['sams_sync_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="samsSyncEnabled">Enable SAMS student pull on this hub</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="samsSyncNowBtn">
                            <i class="bi bi-arrow-repeat me-1"></i>Sync from SAMS now
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="samsSyncFullBtn">Full resync</button>
                        <span id="samsSyncStatus" class="small text-muted ms-2"></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/save_settings.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        showToast(data.message, data.success ? 'success' : 'error');
    } catch (error) {
        showToast('Failed to save settings', 'error');
    }
});

async function samsSync(full) {
    const status = document.getElementById('samsSyncStatus');
    const nowBtn = document.getElementById('samsSyncNowBtn');
    const fullBtn = document.getElementById('samsSyncFullBtn');
    if (!status || !nowBtn) return;
    nowBtn.disabled = true;
    if (fullBtn) fullBtn.disabled = true;
    status.textContent = 'Syncing...';
    const formData = new FormData();
    formData.append('<?php echo CSRF_TOKEN_NAME; ?>', CSRF_TOKEN);
    if (full) formData.append('full', '1');
    try {
        const response = await fetch(APP_URL + '/ajax/admin/sams_sync_now.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        status.textContent = data.message || '';
        showToast(data.message, data.success ? 'success' : 'error');
    } catch (error) {
        status.textContent = 'SAMS sync failed';
        showToast('SAMS sync failed', 'error');
    }
    nowBtn.disabled = false;
    if (fullBtn) fullBtn.disabled = false;
}
const samsNow = document.getElementById('samsSyncNowBtn');
const samsFull = document.getElementById('samsSyncFullBtn');
if (samsNow) samsNow.addEventListener('click', function() { samsSync(false); });
if (samsFull) samsFull.addEventListener('click', function() { samsSync(true); });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
