<?php
/**
 * CBT System - Browser installation wizard
 */

define('CBT_SYSTEM', true);
define('CBT_SYNC_CONTEXT', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/lib.php';

$already = install_is_complete();
$req = install_requirements();
$csrf = install_csrf_token();
$defaults = [
    'site_name' => 'OXYGEN CBT SYSTEM',
    'node_name' => '',
    'app_url' => $req['app_url'],
    'db_host' => 'localhost',
    'db_name' => 'cbt_system',
    'db_user' => 'root',
    'admin_username' => 'admin',
    'admin_name' => 'System Administrator',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install CBT System</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f3f5fb; min-height: 100vh; }
        .wizard-wrap { max-width: 860px; margin: 32px auto 64px; }
        .step { display: none; }
        .step.active { display: block; }
        .role-card { cursor: pointer; border: 2px solid #e9ecef; border-radius: 16px; padding: 22px; height: 100%; background: #fff; }
        .role-card.selected { border-color: #4c6ef5; box-shadow: 0 0 0 4px rgba(76,110,245,.12); }
        .check-ok { color: #198754; }
        .check-bad { color: #dc3545; }
        .log-box { background: #0f172a; color: #e2e8f0; border-radius: 12px; padding: 16px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; min-height: 90px; }
    </style>
</head>
<body>
<div class="wizard-wrap">
    <div class="text-center mb-4">
        <i class="bi bi-laptop-fill fs-1 text-primary"></i>
        <h1 class="h3 mt-2 mb-1">CBT System installer</h1>
        <p class="text-muted mb-0">Click through the steps. No command line is required.</p>
    </div>

    <?php if ($already): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h5">Already installed</h2>
                <p class="text-muted">This copy is already set up. Open the admin portal to continue, or add another exam hall from Offline Nodes on the online server.</p>
                <a class="btn btn-primary" href="<?php echo htmlspecialchars($req['app_url'] . '/admin/', ENT_QUOTES, 'UTF-8'); ?>">Open admin login</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <div class="progress mb-4" style="height:8px">
                    <div id="progressBar" class="progress-bar" style="width:25%"></div>
                </div>

                <form id="installForm">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="role" id="roleInput" value="hub">

                    <div class="step active" data-step="1">
                        <h2 class="h4 mb-3">Choose this computer's role</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="role-card selected" data-role="hub">
                                    <h3 class="h5"><i class="bi bi-cloud-fill me-2 text-primary"></i>Online server</h3>
                                    <p class="mb-0 small text-muted">The main CBT, reachable on the internet. It stores shared data and controls offline exam halls.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="role-card" data-role="leaf">
                                    <h3 class="h5"><i class="bi bi-building me-2 text-primary"></i>Offline exam hall</h3>
                                    <p class="mb-0 small text-muted">A local server used during exams. It works without internet and syncs in the background when a connection is available.</p>
                                </div>
                            </div>
                        </div>
                        <p class="small text-muted mt-3 mb-0">Install the online server first. Then open Offline Nodes there, add this hall, and paste the pairing details here.</p>
                    </div>

                    <div class="step" data-step="2">
                        <h2 class="h4 mb-3">Server checklist</h2>
                        <ul class="list-group mb-3">
                            <li class="list-group-item d-flex justify-content-between">PHP 8.0+ (<?php echo htmlspecialchars($req['php_version'], ENT_QUOTES, 'UTF-8'); ?>)
                                <span class="<?php echo $req['php_ok'] ? 'check-ok' : 'check-bad'; ?>"><?php echo $req['php_ok'] ? 'OK' : 'Missing'; ?></span></li>
                            <?php foreach ($req['extensions'] as $name => $ok): ?>
                                <li class="list-group-item d-flex justify-content-between">PHP extension: <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="<?php echo $ok ? 'check-ok' : ($name === 'zip' ? 'text-muted' : 'check-bad'); ?>"><?php echo $ok ? 'OK' : ($name === 'zip' ? 'Optional' : 'Missing'); ?></span></li>
                            <?php endforeach; ?>
                            <?php foreach ($req['writable'] as $path => $ok): ?>
                                <li class="list-group-item d-flex justify-content-between">Writable: <?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="<?php echo $ok ? 'check-ok' : 'check-bad'; ?>"><?php echo $ok ? 'OK' : 'Not writable'; ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (!$req['ok']): ?>
                            <div class="alert alert-danger">Fix the missing items, then refresh this page.</div>
                        <?php endif; ?>
                    </div>

                    <div class="step" data-step="3">
                        <h2 class="h4 mb-3">Site and database</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Institution / site name</label>
                                <input class="form-control" name="site_name" value="<?php echo htmlspecialchars($defaults['site_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">This node name</label>
                                <input class="form-control" name="node_name" placeholder="e.g. Main campus hall">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Public URL of this copy</label>
                                <input class="form-control" name="app_url" value="<?php echo htmlspecialchars($defaults['app_url'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Database host</label>
                                <input class="form-control" name="db_host" value="<?php echo htmlspecialchars($defaults['db_host'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Database name</label>
                                <input class="form-control" name="db_name" value="<?php echo htmlspecialchars($defaults['db_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Database user</label>
                                <input class="form-control" name="db_user" value="<?php echo htmlspecialchars($defaults['db_user'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Database password</label>
                                <input class="form-control" type="password" name="db_pass" autocomplete="new-password">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Admin username</label>
                                <input class="form-control" name="admin_username" value="<?php echo htmlspecialchars($defaults['admin_username'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Admin password</label>
                                <input class="form-control" type="password" name="admin_password" minlength="8" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm password</label>
                                <input class="form-control" type="password" name="admin_password2" minlength="8" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Admin full name</label>
                                <input class="form-control" name="admin_name" value="<?php echo htmlspecialchars($defaults['admin_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Admin email (optional)</label>
                                <input class="form-control" type="email" name="admin_email">
                            </div>
                        </div>

                        <div id="leafPairing" class="mt-4 d-none">
                            <h3 class="h6">Pair with the online server</h3>
                            <p class="small text-muted">On the online server open Admin &gt; Offline Nodes, click Add exam hall, then paste the pairing code here.</p>
                            <textarea class="form-control" name="pairing" rows="6" placeholder="Paste pairing code"></textarea>
                        </div>
                    </div>

                    <div class="step" data-step="4">
                        <h2 class="h4 mb-3">Install</h2>
                        <p class="text-muted">This creates the database tables, writes local settings, and prepares background sync. Existing tables are kept.</p>
                        <div id="installStatus" class="log-box mb-3">Ready.</div>
                        <div id="installResult" class="d-none"></div>
                    </div>
                </form>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary" id="prevBtn" disabled>Back</button>
                    <button type="button" class="btn btn-primary" id="nextBtn" <?php echo $req['ok'] ? '' : 'disabled'; ?>>Continue</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
const steps = Array.from(document.querySelectorAll('.step'));
let current = 0;
const roleInput = document.getElementById('roleInput');
const nextBtn = document.getElementById('nextBtn');
const prevBtn = document.getElementById('prevBtn');
const leafPairing = document.getElementById('leafPairing');
const progressBar = document.getElementById('progressBar');

function showStep(index) {
    current = index;
    steps.forEach((el, i) => el.classList.toggle('active', i === index));
    prevBtn.disabled = index === 0;
    nextBtn.textContent = index === steps.length - 1 ? 'Install now' : 'Continue';
    progressBar.style.width = (((index + 1) / steps.length) * 100) + '%';
    if (leafPairing) leafPairing.classList.toggle('d-none', roleInput.value !== 'leaf');
}

document.querySelectorAll('.role-card').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        roleInput.value = card.dataset.role;
        if (leafPairing) leafPairing.classList.toggle('d-none', roleInput.value !== 'leaf');
    });
});

prevBtn.addEventListener('click', () => {
    if (current > 0) showStep(current - 1);
});

nextBtn.addEventListener('click', async () => {
    if (current < steps.length - 1) {
        if (current === 2) {
            const form = document.getElementById('installForm');
            const pass = form.admin_password.value;
            if (pass.length < 8) { alert('Admin password must be at least 8 characters.'); return; }
            if (pass !== form.admin_password2.value) { alert('Passwords do not match.'); return; }
            if (roleInput.value === 'leaf' && !form.pairing.value.trim()) {
                alert('Paste the pairing details from the online server.');
                return;
            }
        }
        showStep(current + 1);
        return;
    }
    const form = document.getElementById('installForm');
    const status = document.getElementById('installStatus');
    const result = document.getElementById('installResult');
    nextBtn.disabled = true;
    prevBtn.disabled = true;
    status.textContent = 'Installing... this may take a minute.';
    const data = new FormData(form);
    try {
        const res = await fetch('run.php', { method: 'POST', body: data });
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Install failed');
        status.textContent = 'Finished.';
        const esc = (value) => String(value || '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
        const note = json.note ? '<p class="small text-muted">' + esc(json.note) + '</p>' : '';
        result.className = 'alert alert-success';
        result.innerHTML = '<strong>Installation complete.</strong><p class="mb-2">Role: ' + esc(json.role) + ' &mdash; ' + esc(json.name) + '</p>'
            + note
            + '<a class="btn btn-primary me-2" href="' + esc(json.admin_url) + '">Open admin login</a>'
            + '<a class="btn btn-outline-secondary" href="' + esc(json.student_url) + '">Student login</a>';
        nextBtn.classList.add('d-none');
    } catch (err) {
        status.textContent = 'Failed.';
        result.className = 'alert alert-danger';
        result.textContent = err.message;
        nextBtn.disabled = false;
        prevBtn.disabled = false;
    }
});
</script>
</body>
</html>
