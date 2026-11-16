<?php
/**
 * CBT System - Offline node management (hub only)
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

if (!node_is_hub()) {
    redirect(APP_URL . '/admin/dashboard.php', 'Node management is only available on the online hub.', 'error');
}
if (($_SESSION['admin_role'] ?? '') !== 'super_admin') {
    redirect(APP_URL . '/admin/dashboard.php', 'Access denied. Super Admin only.', 'error');
}

$pageTitle = 'Offline Nodes';
$db = getDB();

$nodes = $db->query("SELECT * FROM sync_node WHERE role = 'leaf' ORDER BY name ASC")->fetchAll();
$packages = $db->query("SELECT * FROM sync_file_package ORDER BY id DESC LIMIT 20")->fetchAll();

$policyKeys = ['student_edit', 'question_edit', 'exam_edit', 'exam_access', 'results_view', 'results_edit', 'file_updates', 'resync_requested'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Offline Nodes</h4>
        <p class="text-muted mb-0">Control data synchronisation, restrictions and signed project updates for every offline server.</p>
    </div>
    <a href="<?php echo APP_URL; ?>/admin/nodes.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
    </a>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i>Registered offline servers</h5>
                <div>
                    <span class="badge bg-primary me-2"><?php echo count($nodes); ?> node(s)</span>
                    <button type="button" class="btn btn-sm btn-primary" id="addNodeBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add exam hall
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Node</th>
                            <th>Status</th>
                            <th>Last seen</th>
                            <th>Restrictions</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$nodes): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No offline exam hall yet. Click <strong>Add exam hall</strong> and paste the pairing code into the installer on that computer.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($nodes as $node):
                        $policy = json_decode((string) $node['policy_json'], true) ?: [];
                        $statusClass = $node['status'] === 'active' ? 'success' : ($node['status'] === 'suspended' ? 'warning' : 'secondary');
                        $disabled = [];
                        foreach ($policyKeys as $key) {
                            if (empty($policy[$key])) {
                                $disabled[] = str_replace('_', ' ', $key);
                            }
                        }
                    ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo e($node['name']); ?></div>
                                <div class="small text-muted"><code><?php echo e($node['node_uid']); ?></code></div>
                            </td>
                            <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo e($node['status']); ?></span></td>
                            <td>
                                <?php echo $node['last_seen_at'] ? e($node['last_seen_at']) : '<span class="text-muted">Never</span>'; ?>
                                <div class="small text-muted"><?php echo $node['last_ip'] ? e($node['last_ip']) : ''; ?></div>
                            </td>
                            <td>
                                <?php if (!$disabled): ?>
                                    <span class="text-success small">Full access</span>
                                <?php else: ?>
                                    <span class="text-danger small"><?php echo e(implode(', ', $disabled)); ?> disabled</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary manage-node"
                                        data-node='<?php echo e(json_encode($node, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>'>
                                    <i class="bi bi-sliders me-1"></i> Manage
                                </button>
                                <?php if ($node['status'] === 'active'): ?>
                                    <button class="btn btn-sm btn-outline-warning node-action" data-uid="<?php echo e($node['node_uid']); ?>" data-action="suspend">Suspend</button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-success node-action" data-uid="<?php echo e($node['node_uid']); ?>" data-action="activate">Activate</button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-secondary node-action" data-uid="<?php echo e($node['node_uid']); ?>" data-action="retire">Retire</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-file-earmark-zip me-2"></i>Queue signed project update</h5></div>
            <div class="card-body">
                <p class="text-muted small">Upload a ZIP containing project files. The offline administrator must approve the update locally; existing files are backed up and nothing is deleted.</p>
                <form id="packageForm" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label class="form-label">Target node</label>
                        <select name="node_uid" class="form-select">
                            <option value="">All offline nodes</option>
                            <?php foreach ($nodes as $node): ?>
                                <option value="<?php echo e($node['node_uid']); ?>"><?php echo e($node['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Version</label>
                        <input type="text" name="version" class="form-control" placeholder="e.g. 1.1.1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Package (ZIP)</label>
                        <input type="file" name="package" class="form-control" accept=".zip" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Queue update</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent update packages</h5></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>ID</th><th>Version</th><th>Target</th><th>Status</th><th>Queued</th></tr></thead>
                    <tbody>
                    <?php if (!$packages): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No packages queued.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($packages as $package): ?>
                        <tr>
                            <td><?php echo (int) $package['id']; ?></td>
                            <td><?php echo e($package['version']); ?></td>
                            <td class="small"><?php echo $package['node_uid'] ? e($package['node_uid']) : 'All nodes'; ?></td>
                            <td><span class="badge bg-secondary"><?php echo e($package['status']); ?></span></td>
                            <td class="small text-muted"><?php echo e($package['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="nodeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage offline node</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="nodeUid">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Display name</label>
                        <input type="text" id="nodeName" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select id="nodeStatus" class="form-select">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended (locked)</option>
                            <option value="retired">Retired (disabled)</option>
                        </select>
                    </div>
                </div>
                <h6 class="text-muted">Permissions</h6>
                <div class="row g-2 mb-3">
                    <?php
                    $labels = [
                        'student_edit' => 'Student editing',
                        'question_edit' => 'Question editing',
                        'exam_edit' => 'Exam management',
                        'exam_access' => 'Allow students to take exams',
                        'results_view' => 'View results',
                        'results_edit' => 'Edit/publish/delete results',
                        'file_updates' => 'Allow signed file updates',
                        'resync_requested' => 'Request full re-sync now',
                    ];
                    foreach ($labels as $key => $label): ?>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input policy-toggle" type="checkbox" id="policy_<?php echo $key; ?>" data-key="<?php echo $key; ?>">
                                <label class="form-check-label" for="policy_<?php echo $key; ?>"><?php echo e($label); ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="alert alert-warning py-2 mb-0 small">
                    Retiring a node only stops synchronisation and locks it. It never deletes files or data on the offline server.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveNode"><i class="bi bi-check2 me-1"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="registerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add offline exam hall</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="registerFormWrap">
                    <p class="text-muted small">Give the hall a name. You will get a pairing code to paste into the installer on that computer.</p>
                    <label class="form-label">Hall name</label>
                    <input type="text" id="registerName" class="form-control" placeholder="e.g. Campus A hall">
                </div>
                <div id="registerResult" class="d-none">
                    <p class="small">Copy this code into the installer on the offline computer (step: Pair with the online server). It is shown only once.</p>
                    <textarea id="pairingCode" class="form-control font-monospace" rows="6" readonly></textarea>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="copyPairing">Copy code</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="registerNode">Create pairing</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

const nodeModal = new bootstrap.Modal(document.getElementById('nodeModal'));
const registerModal = new bootstrap.Modal(document.getElementById('registerModal'));

document.getElementById('addNodeBtn').addEventListener('click', () => {
    document.getElementById('registerFormWrap').classList.remove('d-none');
    document.getElementById('registerResult').classList.add('d-none');
    document.getElementById('registerName').value = '';
    document.getElementById('registerNode').classList.remove('d-none');
    registerModal.show();
});

document.getElementById('registerNode').addEventListener('click', () => {
    const name = document.getElementById('registerName').value.trim();
    if (!name) { showToast('Enter a hall name', 'error'); return; }
    const form = new FormData();
    form.append('csrf_token', CSRF_TOKEN);
    form.append('action', 'register');
    form.append('name', name);
    showLoading();
    fetch(APP_URL + '/ajax/admin/save_node.php', { method: 'POST', body: form })
        .then(r => r.json())
        .then(data => {
            hideLoading();
            if (!data.success) throw new Error(data.message || 'Could not register');
            document.getElementById('pairingCode').value = data.pairing || '';
            document.getElementById('registerFormWrap').classList.add('d-none');
            document.getElementById('registerResult').classList.remove('d-none');
            document.getElementById('registerNode').classList.add('d-none');
            showToast(data.message || 'Registered');
        })
        .catch(err => { hideLoading(); showToast(err.message, 'error'); });
});

document.getElementById('copyPairing').addEventListener('click', () => {
    const box = document.getElementById('pairingCode');
    box.select();
    navigator.clipboard.writeText(box.value).then(() => showToast('Copied')).catch(() => document.execCommand('copy'));
});

document.querySelectorAll('.manage-node').forEach(button => {
    button.addEventListener('click', () => {
        const node = JSON.parse(button.dataset.node);
        const policy = JSON.parse(node.policy_json || '{}');
        document.getElementById('nodeUid').value = node.node_uid;
        document.getElementById('nodeName').value = node.name;
        document.getElementById('nodeStatus').value = node.status;
        document.querySelectorAll('.policy-toggle').forEach(toggle => {
            const key = toggle.dataset.key;
            let value = policy[key];
            if (key === 'resync_requested') value = !!value;
            else if (value === undefined) value = true;
            toggle.checked = !!value;
        });
        nodeModal.show();
    });
});

document.getElementById('saveNode').addEventListener('click', () => {
    const policy = {};
    document.querySelectorAll('.policy-toggle').forEach(toggle => {
        policy[toggle.dataset.key] = toggle.checked ? 1 : 0;
    });
    const form = new FormData();
    form.append('csrf_token', CSRF_TOKEN);
    form.append('node_uid', document.getElementById('nodeUid').value);
    form.append('name', document.getElementById('nodeName').value);
    form.append('status', document.getElementById('nodeStatus').value);
    form.append('policy', JSON.stringify(policy));
    showLoading();
    fetch(APP_URL + '/ajax/admin/save_node.php', { method: 'POST', body: form })
        .then(r => r.json())
        .then(data => {
            hideLoading();
            if (!data.success) throw new Error(data.message || 'Save failed');
            showToast(data.message || 'Saved');
            setTimeout(() => location.reload(), 600);
        })
        .catch(err => { hideLoading(); showToast(err.message, 'error'); });
});

document.querySelectorAll('.node-action').forEach(button => {
    button.addEventListener('click', () => {
        const action = button.dataset.action;
        const uid = button.dataset.uid;
        const labels = { suspend: 'Suspend this node? Local users will be locked out.', activate: 'Activate this node?', retire: 'Retire this node? Synchronisation stops and the node is locked. No data is deleted.' };
        if (!confirm(labels[action])) return;
        const form = new FormData();
        form.append('csrf_token', CSRF_TOKEN);
        form.append('node_uid', uid);
        form.append('action', action);
        fetch(APP_URL + '/ajax/admin/save_node.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Action failed');
                showToast(data.message || 'Updated');
                setTimeout(() => location.reload(), 600);
            })
            .catch(err => showToast(err.message, 'error'));
    });
});

document.getElementById('packageForm').addEventListener('submit', event => {
    event.preventDefault();
    const form = new FormData(event.target);
    showLoading();
    fetch(APP_URL + '/ajax/admin/upload_package.php', { method: 'POST', body: form })
        .then(r => r.json())
        .then(data => {
            hideLoading();
            if (!data.success) throw new Error(data.message || 'Upload failed');
            showToast(data.message || 'Package queued');
            setTimeout(() => location.reload(), 800);
        })
        .catch(err => { hideLoading(); showToast(err.message, 'error'); });
});

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
