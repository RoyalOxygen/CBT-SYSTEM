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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
