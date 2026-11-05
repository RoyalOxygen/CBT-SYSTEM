<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../install/guard.php';
cbt_redirect_to_installer(dirname(__DIR__), '../install/');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

<<<<<<< HEAD
=======
<<<<<<< HEAD
=======
// If already logged in, redirect to dashboard
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
if (isAdminLoggedIn()) {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}

$error = '';
<<<<<<< HEAD
$prefillUsername = '';
=======
<<<<<<< HEAD
$prefillUsername = '';
=======
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    if (!validateCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh the page.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
<<<<<<< HEAD
        $prefillUsername = $username;
=======
<<<<<<< HEAD
        $prefillUsername = $username;
=======
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id, username, password, full_name, email, role, status, photo FROM admins WHERE username = ? AND status = 1");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                
                if ($admin && verifyPassword($password, $admin['password'])) {
<<<<<<< HEAD
=======
<<<<<<< HEAD
=======
                    // Set session
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['admin_photo'] = $admin['photo'];
                    $_SESSION['last_activity'] = time();
                    
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
                    $updateStmt = $db->prepare("UPDATE admins SET last_login = NOW(), login_ip = ? WHERE id = ?");
                    $updateStmt->execute([getClientIP(), $admin['id']]);
                    
                    logActivity('admin', $admin['id'], 'login', 'Admin logged in successfully');
                    
<<<<<<< HEAD
=======
=======
                    // Update last login
                    $updateStmt = $db->prepare("UPDATE admins SET last_login = NOW(), login_ip = ? WHERE id = ?");
                    $updateStmt->execute([getClientIP(), $admin['id']]);
                    
                    // Log activity
                    logActivity('admin', $admin['id'], 'login', 'Admin logged in successfully');
                    
                    // Redirect to dashboard or previous page
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
                    $redirect = $_SESSION['redirect_after_login'] ?? APP_URL . '/admin/dashboard.php';
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $redirect");
                    exit;
                } else {
                    $error = 'Invalid username or password';
                    logActivity('admin', 0, 'login_failed', "Failed login attempt for username: $username from IP: " . getClientIP());
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'System error. Please try again later.';
            }
        }
    }
}
<<<<<<< HEAD
$csrf_token = generateCSRFToken();
=======
<<<<<<< HEAD

$csrf_token = generateCSRFToken();
=======
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
    <title>Admin Login - <?php echo e(institution_name()); ?></title>
    <?php echo student_favicon_tags(); ?>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --tsu-indigo: #6366f1;
            --tsu-indigo-dark: #4f46e5;
            --tsu-navy: #1e2749;
        }
        html, body { min-height: 100%; }
        body {
            background: #f4f5f7;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
<<<<<<< HEAD
=======
=======
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
        .login-wrap {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 24px 12px 72px;
        }
        .site-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1030;
            width: 100%;
            margin: 0;
            background: #f4f5f7;
            border: 0;
            padding: 12px;
        }
        .login-card {
            background: white;
            border-radius: 16px;
            border-top: 4px solid var(--tsu-indigo);
            box-shadow: 0 10px 35px rgba(30, 39, 73, 0.08);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            padding: 32px 30px 20px;
            text-align: center;
        }
        .login-logo {
            width: 88px;
            height: 88px;
            object-fit: contain;
            margin: 0 auto 16px;
            display: block;
        }
        .login-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--tsu-navy);
            letter-spacing: 0.3px;
            line-height: 1.35;
        }
        .login-header .portal-label {
            display: inline-block;
            margin-top: 10px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #eef2ff;
            color: var(--tsu-indigo-dark);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }
        .login-body {
            padding: 8px 30px 32px;
            border-top: 1px solid #eef0f4;
            margin-top: 8px;
            padding-top: 24px;
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--tsu-indigo-dark);
            margin-bottom: 6px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 0.95rem;
            border: 1px solid #c7d2fe;
        }
        .form-control::placeholder {
            color: #8b93a7;
        }
        .form-control:focus {
            border-color: var(--tsu-indigo);
            box-shadow: 0 0 0 0.2rem rgba(99,102,241,0.15);
        }
        .password-field {
            position: relative;
        }
        .password-field .form-control {
            padding-right: 46px;
        }
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #6c757d;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .password-toggle:hover,
        .password-toggle:focus {
            color: var(--tsu-indigo-dark);
            background: #eef2ff;
        }
        .btn-login {
            background: var(--tsu-indigo);
            border: none;
            border-radius: 8px;
            padding: 13px;
            font-weight: 600;
            font-size: 1rem;
            color: #fff;
            transition: all 0.2s;
        }
        .btn-login:hover {
            background: var(--tsu-indigo-dark);
            color: #fff;
        }
        .login-footer-links a {
            color: #6c757d;
            font-size: 0.82rem;
        }
        .powered-by-credit {
            text-align: center;
            margin: 0;
            color: #6c757d;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        @media (max-width: 576px) {
            .login-header {
                padding: 25px 20px 15px;
            }
            .login-body {
                padding-left: 20px;
                padding-right: 20px;
            }
<<<<<<< HEAD
=======
=======
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-header h3 {
            margin: 0;
            font-weight: 600;
        }
        .login-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
        }
    </style>
</head>
<body>
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
    <div class="login-wrap">
    <div class="login-card">
        <div class="login-header">
            <?php $loginLogo = institution_logo_url(); ?>
            <?php if ($loginLogo !== ''): ?>
            <img src="<?php echo e($loginLogo); ?>"
                 alt="<?php echo e(institution_name()); ?> Logo" class="login-logo">
            <?php endif; ?>
            <h3><?php echo e(institution_name()); ?><br>ADMIN LOGIN</h3>
            <span class="portal-label">Staff portal</span>
<<<<<<< HEAD
=======
=======
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-laptop-fill" style="font-size: 3rem;"></i>
            <h3 class="mt-2"><?php echo APP_NAME; ?></h3>
            <p class="mb-0">Admin Portal</p>
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo e($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrf_token; ?>">
                
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control"
                           placeholder="Enter your username"
                           value="<?php echo e($prefillUsername); ?>"
                           required autofocus autocomplete="username">
                </div>
                
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <div class="password-field">
                        <input type="password" name="password" id="password" class="form-control"
                               placeholder="Enter your password" required autocomplete="current-password">
                        <button class="password-toggle" type="button" id="togglePassword" aria-label="Show password">
<<<<<<< HEAD
=======
=======
                <?php echo csrfField(); ?>
                
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required id="password">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
                <button type="submit" class="btn btn-login w-100 mb-3">
                    Login
                </button>
                
                <div class="text-center login-footer-links">
                    <a href="<?php echo student_login_url(); ?>" class="text-decoration-none">
                        <i class="bi bi-mortarboard-fill me-1"></i>Student Login
                    </a>
                    <span class="text-muted mx-2">|</span>
                    <a href="<?php echo APP_URL; ?>/" class="text-decoration-none">
                        <i class="bi bi-house me-1"></i>Home
                    </a>
<<<<<<< HEAD
=======
=======
                <button type="submit" class="btn btn-primary w-100 btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </button>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <a href="<?php echo student_login_url(); ?>" class="text-decoration-none">
                        <i class="bi bi-mortarboard-fill me-1"></i>Student Login
                    </a>
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
                </div>
            </form>
        </div>
    </div>
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
    </div>
    <footer class="site-footer">
        <?php echo powered_by_credit(); ?>
    </footer>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const toggle = document.getElementById('togglePassword');
            if (!password || !toggle) return;
            toggle.addEventListener('click', function() {
                const icon = toggle.querySelector('i');
                const showing = password.type === 'text';
                password.type = showing ? 'password' : 'text';
                icon.classList.toggle('bi-eye', showing);
                icon.classList.toggle('bi-eye-slash', !showing);
                toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            });
        });
    </script>
</body>
</html>
<<<<<<< HEAD
=======
=======
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = event.currentTarget.querySelector('i');
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
