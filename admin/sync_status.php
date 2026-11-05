<?php
/**
 * CBT System - Synchronisation status (leaf only)
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

if (!node_is_leaf()) {
    redirect(APP_URL . '/admin/dashboard.php', 'This page is only available on an offline node.', 'info');
}

$pageTitle = 'Sync Status';
$db = getDB();

$pendingPush = (int) $db->query("SELECT COUNT(*) FROM sync_change WHERE synced = 0")->fetchColumn();
$errors = (int) $db->query("SELECT COUNT(*) FROM sync_error")->fetchColumn();
$recentErrors = $db->query("SELECT * FROM sync_error ORDER BY id DESC LIMIT 10")->fetchAll();

$state = [
    'last_success_at' => sync_state_get('last_success_at', null),
    'last_attempt_at' => sync_state_get('last_attempt_at', null),
    'last_push_at' => sync_state_get('last_push_at', null),
    'last_pull_at' => sync_state_get('last_pull_at', null),
    'last_error' => sync_state_get('last_error', ''),
    'cursor' => sync_state_get('cursor', 0),
];
$policy = node_policy();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Online Sync</h4>
        <p class="text-muted mb-0">Data synchronises automatically in the background whenever the internet is available.</p>
    </div>
    <button class="btn btn-primary" id="syncNow"><i class="bi bi-arrow-repeat me-1"></i> Sync now</button>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i>Connection</h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal">Node</dt><dd class="col-7"><?php echo e(NODE_NAME); ?><div class="small text-muted"><code><?php echo e(NODE_UID); ?></code></div></dd>
                    <dt class="col-5 text-muted fw-normal">Hub</dt><dd class="col-7 text-break"><?php echo NODE_HUB_URL !== '' ? e(NODE_HUB_URL) : '<span class="text-danger">Not configured</span>'; ?></dd>
                    <dt class="col-5 text-muted fw-normal">Status</dt>
                    <dd class="col-7">
                        <?php if (node_is_suspended()): ?><span class="badge bg-danger">Suspended</span>
                        <?php elseif (!empty($state['last_error'])): ?><span class="badge bg-warning text-dark">Last sync failed</span>
                        <?php else: ?><span class="badge bg-success">Idle / connected</span><?php endif; ?>
                    </dd>
                    <dt class="col-5 text-muted fw-normal">Last success</dt>
                    <dd class="col-7"><?php echo $state['last_success_at'] ? e(date('Y-m-d H:i:s', (int) $state['last_success_at'])) : '<span class="text-muted">Never</span>'; ?></dd>
                    <dt class="col-5 text-muted fw-normal">Last attempt</dt>
                    <dd class="col-7"><?php echo $state['last_attempt_at'] ? e(date('Y-m-d H:i:s', (int) $state['last_attempt_at'])) : '<span class="text-muted">Never</span>'; ?></dd>
                    <dt class="col-5 text-muted fw-normal">Push cursor</dt><dd class="col-7"><?php echo (int) $state['cursor']; ?></dd>
                    <dt class="col-5 text-muted fw-normal">Pending upload</dt><dd class="col-7"><?php echo $pendingPush; ?> change(s)</dd>
                </dl>
                <?php if (!empty($state['last_error'])): ?>
                    <div class="alert alert-warning mt-3 mb-0 small"><?php echo e((string) $state['last_error']); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Restrictions from the online server</h5>
                <span class="small text-muted">Updated <?php echo $policy['updated_at'] ? e($policy['updated_at']) : 'never'; ?></span>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <?php
                    $labels = [
                        'student_edit' => 'Student editing',
                        'question_edit' => 'Question editing',
                        'exam_edit' => 'Exam management',
                        'exam_access' => 'Students can take exams',
                        'results_view' => 'Result viewing',
                        'results_edit' => 'Result editing',
                        'file_updates' => 'Signed project updates',
                    ];
                    foreach ($labels as $key => $label): ?>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between border rounded px-3 py-2">
                                <span class="small"><?php echo e($label); ?></span>
                                <?php if (!empty($policy[$key])): ?>
                                    <span class="badge bg-success">Allowed</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Blocked</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="small text-muted mt-3 mb-0">Restrictions are set by the online administrator and are enforced locally on this server.</p>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Recent sync errors (<?php echo $errors; ?>)</h5></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>When</th><th>Table</th><th>Direction</th><th>Message</th></tr></thead>
                    <tbody>
                    <?php if (!$recentErrors): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No sync errors. Everything is in sync.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentErrors as $error): ?>
                        <tr>
                            <td class="small text-muted"><?php echo e($error['created_at']); ?></td>
                            <td class="small"><?php echo e($error['table_name'] ?? '-'); ?></td>
                            <td class="small"><?php echo e($error['direction']); ?></td>
                            <td class="small"><?php echo e($error['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('syncNow').addEventListener('click', function () {
        const form = new FormData();
        form.append('csrf_token', CSRF_TOKEN);
        showLoading();
        fetch(APP_URL + '/ajax/admin/sync_now.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                hideLoading();
                if (!data.success) throw new Error(data.message || 'Sync failed');
                showToast(data.message || 'Sync complete');
                setTimeout(() => location.reload(), 800);
            })
            .catch(err => { hideLoading(); showToast(err.message, 'error'); });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
