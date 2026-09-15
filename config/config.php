<?php
// Session configuration - keep student sessions alive as long as possible
// (only ends on explicit logout or a new login)
define('STUDENT_SESSION_TIMEOUT', 2592000); // 30 days - students stay logged in as long as possible
$sessionLifetime = STUDENT_SESSION_TIMEOUT;

if (session_status() === PHP_SESSION_NONE) {
    // Use a persistent cookie so student sessions survive browser restarts
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    // Match PHP's garbage collector lifetime to the longest session window
    ini_set('session.gc_maxlifetime', $sessionLifetime);
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 0 in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Timezone
date_default_timezone_set('Africa/Lagos');

// Application constants
define('APP_NAME', 'OXYGEN CBT SYSTEM');
define('APP_URL', 'http://localhost/cbt-system'); // Change to your actual URL
define('APP_VERSION', '1.0.0');

// Security constants - IMPORTANT: These must match what's used in functions.php
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 1800); // 30 minutes - used for admin sessions
define('BCRYPT_COST', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Upload constants
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);

// Exam defaults
define('DEFAULT_QUESTION_LIMIT', 50);
define('DEFAULT_PASS_MARK', 40);

// Grading scale
$GRADING_SCALE = [
    ['grade' => 'A', 'min' => 70, 'max' => 100, 'remark' => 'Excellent', 'point' => 5.0],
    ['grade' => 'B', 'min' => 60, 'max' => 69, 'remark' => 'Very Good', 'point' => 4.0],
    ['grade' => 'C', 'min' => 50, 'max' => 59, 'remark' => 'Good', 'point' => 3.0],
    ['grade' => 'D', 'min' => 45, 'max' => 49, 'remark' => 'Pass', 'point' => 2.0],
    ['grade' => 'E', 'min' => 40, 'max' => 44, 'remark' => 'Pass', 'point' => 1.0],
    ['grade' => 'F', 'min' => 0, 'max' => 39, 'remark' => 'Fail', 'point' => 0.0],
];