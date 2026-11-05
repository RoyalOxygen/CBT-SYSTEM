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
<<<<<<< HEAD
                <form id="settingsForm" enctype="multipart/form-data">
=======
                <form id="settingsForm">
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
                    <?php echo csrfField(); ?>
                    
                    <h6 class="text-muted mb-3">General Settings</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Institution Name</label>
                            <input type="text" name="institution_name" class="form-control" value="<?php echo e($settings['institution_name'] ?? ''); ?>">
<<<<<<< HEAD
                            <div class="form-text">Shown on student login, face login, and student headers.</div>
=======
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Timezone</label>
                            <select name="timezone" class="form-select">
                                <option value="Africa/Lagos" <?php echo ($settings['timezone'] ?? '') === 'Africa/Lagos' ? 'selected' : ''; ?>>Africa/Lagos (WAT)</option>
                                <option value="UTC" <?php echo ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                <option value="Europe/London" <?php echo ($settings['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>Europe/London</option>
                            </select>
                        </div>
<<<<<<< HEAD
                        <div class="col-md-6">
                            <label class="form-label">Institution Logo</label>
                            <input type="file" name="institution_logo" class="form-control" accept="image/png,image/jpeg,image/gif,image/webp">
                            <div class="form-text">Used on student login, face login, headers, and student favicons. PNG, JPG, GIF or WebP, max 5MB.</div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <?php $logoUrl = institution_logo_url(); ?>
                            <?php if ($logoUrl !== ''): ?>
                                <div>
                                    <div class="form-text mb-1">Current logo</div>
                                    <img src="<?php echo e($logoUrl); ?>" alt="Institution logo" style="max-height:64px;max-width:160px;object-fit:contain;background:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;padding:6px;">
                                </div>
                            <?php endif; ?>
                        </div>
=======
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
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

<<<<<<< HEAD
                    <h6 class="text-muted mb-3">Groq question import</h6>
                    <p class="small text-muted">Used to extract multiple-choice questions from PDF, Word, and Excel uploads. Get a key from <a href="https://console.groq.com/keys" target="_blank" rel="noopener">console.groq.com</a>.</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label">Groq API key</label>
                            <input type="password" name="groq_api_key" class="form-control" autocomplete="off" value="<?php echo e($settings['groq_api_key'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Model</label>
                            <input type="text" name="groq_model" class="form-control" value="<?php echo e($settings['groq_model'] ?? groq_default_model()); ?>" placeholder="openai/gpt-oss-20b">
                        </div>
                    </div>


=======
<<<<<<< HEAD
                    <h6 class="text-muted mb-3">Gemini question import</h6>
                    <p class="small text-muted">Used to extract multiple-choice questions from PDF, Word, and Excel uploads. Get a key from Google AI Studio.</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label">Gemini API key</label>
                            <input type="password" name="gemini_api_key" class="form-control" autocomplete="off" value="<?php echo e($settings['gemini_api_key'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Model</label>
                            <input type="text" name="gemini_model" class="form-control" value="<?php echo e($settings['gemini_model'] ?? 'gemini-3.8-flash'); ?>" placeholder="gemini-3.8-flash">
                        </div>
                    </div>

=======
>>>>>>> c2f3db94eeb05dddf4e2a299051e40ec27455b93
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
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
