<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../install/guard.php';
cbt_redirect_to_installer(dirname(__DIR__), '../install/');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

if (isStudentLoggedIn()) {
    header('Location: ' . APP_URL . '/student/dashboard.php');
    exit;
}

if (!face_login_enabled()) {
    header('Location: ' . APP_URL . '/student/login.php');
    exit;
}

$csrf_token = generateCSRFToken();
$prefillMatric = $_GET['matric'] ?? ($_COOKIE['cbt_remembered_matric'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Login - <?php echo APP_NAME; ?></title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/font/bootstrap-icons.css">
    <style>
        :root { --tsu-indigo: #6366f1; --tsu-indigo-dark: #4f46e5; --tsu-navy: #1e2749; }
        body { background: #f4f5f7; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .login-card { background: white; border-radius: 16px; border-top: 4px solid var(--tsu-indigo); box-shadow: 0 10px 35px rgba(30, 39, 73, 0.08); width: 100%; max-width: 420px; overflow: hidden; }
        .login-header { padding: 28px 28px 12px; text-align: center; }
        .login-header h3 { margin: 0; font-weight: 700; font-size: 1.1rem; color: var(--tsu-navy); }
        .login-body { padding: 8px 28px 28px; }
        .camera-frame { position: relative; background: #111; border-radius: 12px; overflow: hidden; aspect-ratio: 4/3; }
        .camera-frame video { width: 100%; height: 100%; object-fit: cover; }
        .scan-overlay { position: absolute; inset: 18%; border: 2px solid rgba(99,102,241,.85); border-radius: 12px; pointer-events: none; }
        .btn-login { background: var(--tsu-indigo); border: none; border-radius: 8px; padding: 13px; font-weight: 600; color: #fff; }
        .btn-login:hover { background: var(--tsu-indigo-dark); color: #fff; }
        .form-label { font-size: 0.85rem; font-weight: 700; color: var(--tsu-indigo-dark); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <img src="../assets/logo.png" alt="<?php echo e(APP_NAME); ?>" style="width:72px;height:72px;object-fit:contain;margin-bottom:10px;">
            <h3>Face verification</h3>
            <p class="text-muted small mb-0">Look at the camera. Matric number is optional when 1:N match succeeds.</p>
        </div>
        <div class="login-body">
            <div class="mb-3">
                <label class="form-label">Matric number (optional)</label>
                <input id="matric" class="form-control" placeholder="TSU/SCI/21/04882" value="<?php echo e($prefillMatric); ?>" style="text-transform:uppercase">
            </div>
            <div class="camera-frame mb-3">
                <video id="cam" autoplay playsinline muted></video>
                <div class="scan-overlay"></div>
            </div>
            <p id="status" class="small text-center text-muted">Preparing camera...</p>
            <button id="verifyBtn" class="btn btn-login w-100 mb-3" disabled>Verify face and continue</button>
            <div class="text-center">
                <a href="<?php echo APP_URL; ?>/student/login.php" class="text-decoration-none small">Use matric number instead</a>
            </div>
        </div>
    </div>
    <script src="../assets/js/face.js"></script>
    <script>
    (async function () {
        const video = document.getElementById('cam');
        const status = document.getElementById('status');
        const btn = document.getElementById('verifyBtn');
        try {
            await CBTFace.startCamera(video);
            status.textContent = 'Camera open. Align your face, then verify.';
            btn.disabled = false;
        } catch (err) {
            status.textContent = err.message || 'Camera permission denied.';
        }
        btn.addEventListener('click', async function () {
            btn.disabled = true;
            status.textContent = 'Sending live frame to ArcFace...';
            const snap = CBTFace.snapshot(video);
            const body = new FormData();
            body.append('<?php echo CSRF_TOKEN_NAME; ?>', '<?php echo $csrf_token; ?>');
            body.append('matric_number', document.getElementById('matric').value.trim().toUpperCase());
            body.append('snapshot', snap);
            try {
                const res = await fetch('<?php echo APP_URL; ?>/ajax/student/face_login.php', { method: 'POST', body: body, credentials: 'same-origin' });
                const json = await res.json();
                if (json.success) {
                    CBTFace.stopCamera();
                    window.location = json.redirect;
                } else {
                    status.textContent = json.message || 'Verification failed.';
                    btn.disabled = false;
                }
            } catch (e) {
                status.textContent = 'Could not reach the face login service.';
                btn.disabled = false;
            }
        });
    })();
    </script>
</body>
</html>
