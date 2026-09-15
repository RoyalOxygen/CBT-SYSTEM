<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    if (!validateCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh the page.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id, username, password, full_name, email, role, status, photo FROM admins WHERE username = ? AND status = 1");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                
                if ($admin && verifyPassword($password, $admin['password'])) {
                    // Set session
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['admin_photo'] = $admin['photo'];
                    $_SESSION['last_activity'] = time();
                    
                    // Update last login
                    $updateStmt = $db->prepare("UPDATE admins SET last_login = NOW(), login_ip = ? WHERE id = ?");
                    $updateStmt->execute([getClientIP(), $admin['id']]);
                    
                    // Log activity
                    logActivity('admin', $admin['id'], 'login', 'Admin logged in successfully');
                    
                    // Redirect to dashboard or previous page
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
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
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-laptop-fill" style="font-size: 3rem;"></i>
            <h3 class="mt-2"><?php echo APP_NAME; ?></h3>
            <p class="mb-0">Admin Portal</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo e($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
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
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </button>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <a href="<?php echo APP_URL; ?>/student/login.php" class="text-decoration-none">
                        <i class="bi bi-mortarboard-fill me-1"></i>Student Login
                    </a>
                </div>
            </form>
        </div>
    </div>
    
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