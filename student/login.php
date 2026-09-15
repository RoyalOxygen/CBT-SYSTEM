<?php
/**
 * CBT System - Student Login
 * One-Way Login: Only Matric Number Required
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

if (isStudentLoggedIn()) {
    header('Location: ' . APP_URL . '/student/dashboard.php');
    exit;
}

$error = '';
$success = '';

// "Remember Me" cookie name (stores matric number only - this is a
// passwordless login system, so there's no password to remember/store).
define('REMEMBER_MATRIC_COOKIE', 'cbt_remembered_matric');

// Auto-login from "Remember Me" cookie if the PHP session has expired.
// Keeps the student logged in as long as possible unless explicitly logged out
// or a new login replaces the session.
$rememberedMatric = $_COOKIE[REMEMBER_MATRIC_COOKIE] ?? '';
if (!isStudentLoggedIn() && !empty($rememberedMatric)) {
    if (autoLoginFromRememberCookie($rememberedMatric)) {
        $redirect = $_SESSION['redirect_after_login'] ?? APP_URL . '/student/dashboard.php';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matric = sanitize($_POST['matric_number'] ?? '');
    $rememberMe = isset($_POST['remember_me']);
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    
    if (!validateCSRFToken($token)) {
        $error = 'Invalid security token. Please refresh the page.';
    } elseif (empty($matric)) {
        $error = 'Please enter your matric number';
    } else {
        try {
            $db = getDB();
            
            // Check if student exists and is active
            $stmt = $db->prepare("
                SELECT id, matric_number, first_name, last_name, other_name, email, photo, 
                       fingerprint_enrolled, status, department_id, level_id, semester_id, session_id
                FROM students 
                WHERE matric_number = ? AND status = 1
            ");
            $stmt->execute([$matric]);
            $student = $stmt->fetch();
            
            if ($student) {
                // Login successful - no password required
                // Clear any existing session data so a new login
                // fully replaces any prior session (new login kills old session)
                session_unset();
                session_regenerate_id(true);
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['matric_number'] = $student['matric_number'];
                $_SESSION['student_name'] = $student['last_name'] . ', ' . $student['first_name'];
                $_SESSION['student_first_name'] = $student['first_name'];
                $_SESSION['student_last_name'] = $student['last_name'];
                $_SESSION['fingerprint_enrolled'] = $student['fingerprint_enrolled'];
                $_SESSION['last_activity'] = time();

                // Remember Me: store just the matric number in a cookie so
                // it's pre-filled next visit. No password is ever stored.
                if ($rememberMe) {
                    setcookie(REMEMBER_MATRIC_COOKIE, $student['matric_number'], time() + (30 * 86400), '/', '', isset($_SERVER['HTTPS']), true);
                } else {
                    setcookie(REMEMBER_MATRIC_COOKIE, '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                }
                
                // Get department name
                if ($student['department_id']) {
                    $deptStmt = $db->prepare("SELECT dept_name FROM departments WHERE id = ?");
                    $deptStmt->execute([$student['department_id']]);
                    $dept = $deptStmt->fetch();
                    $_SESSION['student_department'] = $dept['dept_name'] ?? 'N/A';
                }
                
                // Get level name
                if ($student['level_id']) {
                    $levelStmt = $db->prepare("SELECT level_name FROM levels WHERE id = ?");
                    $levelStmt->execute([$student['level_id']]);
                    $level = $levelStmt->fetch();
                    $_SESSION['student_level'] = $level['level_name'] ?? 'N/A';
                }
                
                // Update login info
                $db->prepare("UPDATE students SET last_login = NOW(), login_ip = ?, login_status = 1 WHERE id = ?")
                    ->execute([getClientIP(), $student['id']]);
                
                logActivity('student', $student['id'], 'login', 'Student logged in (one-way)');
                
                // Check for redirect after login
                $redirect = $_SESSION['redirect_after_login'] ?? APP_URL . '/student/dashboard.php';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            } else {
                // Check if student exists but is inactive
                $checkStmt = $db->prepare("SELECT status FROM students WHERE matric_number = ?");
                $checkStmt->execute([$matric]);
                $existing = $checkStmt->fetch();
                
                if ($existing && $existing['status'] == 0) {
                    $error = 'Your account is inactive. Please contact the administrator.';
                } else {
                    $error = 'Invalid matric number. Please check and try again.';
                }
                logActivity('student', 0, 'login_failed', "Failed login attempt: $matric");
            }
        } catch (PDOException $e) {
            error_log("Student login error: " . $e->getMessage());
            $error = 'System error. Please try again later.';
        }
    }
}

// Generate a new token for the form
$csrf_token = generateCSRFToken();

// Pre-fill matric number from a prior POST (validation error) or, failing
// that, from the "Remember Me" cookie.
$prefillMatric = $_POST['matric_number'] ?? ($_COOKIE[REMEMBER_MATRIC_COOKIE] ?? '');
$rememberChecked = isset($_POST['remember_me']) || !empty($_COOKIE[REMEMBER_MATRIC_COOKIE]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - <?php echo APP_NAME; ?></title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/font/bootstrap-icons.css">
    <style>
        :root {
            --tsu-indigo: #6366f1;
            --tsu-indigo-dark: #4f46e5;
            --tsu-navy: #1e2749;
        }
        body {
            background: #f4f5f7;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        .form-check-input:checked {
            background-color: var(--tsu-indigo);
            border-color: var(--tsu-indigo);
        }
        .form-check-label {
            font-size: 0.9rem;
            color: #4b5468;
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
        @media (max-width: 576px) {
            .login-header {
                padding: 25px 20px 15px;
            }
            .login-body {
                padding-left: 20px;
                padding-right: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <img src="../assets/logo.png"
                 alt="<?php echo e(APP_NAME); ?> Logo" class="login-logo">
            <h3>TARABA STATE UNIVERSITY CBT<br>EXAMS LOGIN</h3>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo e($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?php echo e($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrf_token; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Matric Number</label>
                    <input type="text" name="matric_number" class="form-control"
                           placeholder="Click here to enter your Matric Number"
                           value="<?php echo e($prefillMatric); ?>"
                           required autofocus>
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" name="remember_me" id="rememberMe" value="1" <?php echo $rememberChecked ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="rememberMe">Remember Me</label>
                </div>
                
                <button type="submit" class="btn btn-login w-100 mb-3">
                    Login
                </button>
                
                <div class="text-center login-footer-links">
                    <a href="<?php echo APP_URL; ?>/admin/index.php" class="text-decoration-none">
                        <i class="bi bi-shield-lock me-1"></i>Admin Login
                    </a>
                    <span class="text-muted mx-2">|</span>
                    <a href="<?php echo APP_URL; ?>/" class="text-decoration-none">
                        <i class="bi bi-house me-1"></i>Home
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Select all text when clicking the matric number field (handy when
        // it's pre-filled via Remember Me and the student wants to retype).
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.querySelector('input[name="matric_number"]');
            if (input) {
                input.addEventListener('click', function() {
                    this.select();
                });
            }
        });
    </script>
</body>
</html>