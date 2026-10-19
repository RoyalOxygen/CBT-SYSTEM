<?php
/**
 * CBT System - Download and apply signed project updates (leaf only)
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

if (!node_is_leaf()) {
    redirect(APP_URL . '/admin/dashboard.php', 'File updates are managed from the hub.', 'info');
}
if (($_SESSION['admin_role'] ?? '') !== 'super_admin') {
    redirect(APP_URL . '/admin/dashboard.php', 'Access denied. Super Admin only.', 'error');
}
node_require('file_updates');

$pageTitle = 'Project Updates';
$packages = sync_pending_packages();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-cloud-arrow-down me-2"></i>Signed project updates</h5>
                <a href="<?php echo APP_URL; ?>/admin/file_updates.php" class="btn btn-sm btn-outline-secondary">Refresh</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Version</th>
                            <th>Package</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$packages): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No updates queued by the online server.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($packages as $package):
                        $local = sync_state_get('package_' . (int) $package['id'], []);
                        $status = is_array($local) && !empty($local['status']) ? $local['status'] : $package['status'];
                        $downloaded = is_array($local) && !empty($local['sha256']);
                    ?>
                        <tr>
                            <td><?php echo (int) $package['id']; ?></td>
                            <td><?php echo e($package['version']); ?></td>
                            <td>
                                <div><?php echo e($package['filename']); ?></div>
                                <div class="small text-muted"><?php echo number_format(((int) $package['size']) / 1024, 1); ?> KB</div>
                                <?php if (!empty($package['notes'])): ?>
                                    <div class="small text-muted"><?php echo e($package['notes']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo e($status); ?></span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary download-package" data-id="<?php echo (int) $package['id']; ?>">
                                    <i class="bi bi-download me-1"></i> Download
                                </button>
                                <button class="btn btn-sm btn-warning apply-package" data-id="<?php echo (int) $package['id']; ?>" <?php echo $downloaded ? '' : 'disabled'; ?>>
                                    <i class="bi bi-shield-check me-1"></i> Approve &amp; apply
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6><i class="bi bi-info-circle me-1"></i> How updates work</h6>
                <p class="small text-muted mb-2">The online server can queue signed project files, but this server never applies them automatically.</p>
                <ul class="small text-muted ps-3 mb-0">
                    <li>Download verifies the package checksum and signature.</li>
                    <li>Apply creates a backup under <code>storage/sync/backups</code>.</li>
                    <li>Files under <code>config/</code>, <code>storage/</code> and <code>.git/</code> are never replaced.</li>
                    <li>No files are deleted by an update.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.download-package').forEach(button => {
        button.addEventListener('click', () => {
            const form = new FormData();
            form.append('csrf_token', CSRF_TOKEN);
            form.append('package_id', button.dataset.id);
            showLoading();
            fetch(APP_URL + '/ajax/admin/download_package.php', { method: 'POST', body: form })
                .then(r => r.json())
                .then(data => {
                    hideLoading();
                    if (!data.success) throw new Error(data.message || 'Download failed');
                    showToast(data.message || 'Package downloaded');
                    setTimeout(() => location.reload(), 700);
                })
                .catch(err => { hideLoading(); showToast(err.message, 'error'); });
        });
    });

    document.querySelectorAll('.apply-package').forEach(button => {
        button.addEventListener('click', () => {
            if (!confirm('Apply this update now? Existing files will be backed up first. The update cannot delete files.')) return;
            const form = new FormData();
            form.append('csrf_token', CSRF_TOKEN);
            form.append('package_id', button.dataset.id);
            showLoading();
            fetch(APP_URL + '/ajax/admin/apply_package.php', { method: 'POST', body: form })
                .then(r => r.json())
                .then(data => {
                    hideLoading();
                    if (!data.success) throw new Error(data.message || 'Apply failed');
                    showToast(data.message || 'Package applied');
                    setTimeout(() => location.reload(), 900);
                })
                .catch(err => { hideLoading(); showToast(err.message, 'error'); });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
