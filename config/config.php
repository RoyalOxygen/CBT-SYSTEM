<?php
require_once __DIR__ . '/node.php';

// Session configuration - keep student sessions alive as long as possible
// (only ends on explicit logout or a new login)
define('STUDENT_SESSION_TIMEOUT', 2592000); // 30 days - students stay logged in as long as possible
$sessionLifetime = STUDENT_SESSION_TIMEOUT;

<<<<<<< HEAD
function cbt_request_is_https(): bool {
    $forwarded = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    if ($forwarded === 'https') {
        return true;
    }
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function cbt_request_app_url(): ?string {
    if (PHP_SAPI === 'cli') {
        return null;
    }
    $forwardedHost = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
    if ($forwardedHost !== '') {
        $host = trim(explode(',', $forwardedHost)[0]);
    } else {
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '')));
    }
    if ($host === '') {
        return null;
    }
    $https = cbt_request_is_https();
    if (!$https && (str_contains($host, 'monkeycode-ai.live') || str_contains($host, 'monkeycode-ai.online'))) {
        $https = true;
    }
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = preg_replace('#/(admin|student|ajax|sync|install)(/.*)?$#', '', $script);
    $dir = preg_replace('#/index\.php$#', '', (string) $dir);
    $dir = rtrim((string) $dir, '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

=======
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
if (session_status() === PHP_SESSION_NONE) {
    // Use a persistent cookie so student sessions survive browser restarts
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => '/',
        'domain' => '',
<<<<<<< HEAD
        'secure' => cbt_request_is_https(),
=======
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
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
if (!defined('APP_URL')) {
<<<<<<< HEAD
    $detectedAppUrl = cbt_request_app_url();
    $configuredAppUrl = NODE_APP_URL ?: 'http://localhost/cbt-system';
    if ($detectedAppUrl) {
        $detectedHost = strtolower((string) parse_url($detectedAppUrl, PHP_URL_HOST));
        $configuredHost = strtolower((string) parse_url($configuredAppUrl, PHP_URL_HOST));
        if ($detectedHost !== '' && ($configuredHost === '' || $configuredHost === 'localhost' || $configuredHost === '127.0.0.1' || $detectedHost !== $configuredHost)) {
            $configuredAppUrl = $detectedAppUrl;
        }
    }
    define('APP_URL', $configuredAppUrl);
=======
    define('APP_URL', NODE_APP_URL ?: 'http://localhost/cbt-system'); // Override in config/node.local.php
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
}
define('APP_VERSION', '1.1.0');

// Security constants - IMPORTANT: These must match what's used in functions.php
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 1800); // 30 minutes - used for admin sessions
define('BCRYPT_COST', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Upload constants
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
<<<<<<< HEAD
define('MAX_DOCUMENT_UPLOAD_SIZE', 20971520); // 20MB for Groq question import
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('QUESTION_IMAGE_PATH', dirname(__DIR__) . '/assets/uploads/question_images/');
=======
<<<<<<< HEAD
define('MAX_DOCUMENT_UPLOAD_SIZE', 20971520); // 20MB for Groq question import
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('QUESTION_IMAGE_PATH', dirname(__DIR__) . '/assets/uploads/question_images/');
=======
<<<<<<< HEAD
define('MAX_DOCUMENT_UPLOAD_SIZE', 20971520); // 20MB for Groq question import
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('QUESTION_IMAGE_PATH', dirname(__DIR__) . '/assets/uploads/question_images/');
=======
<<<<<<< HEAD
define('MAX_DOCUMENT_UPLOAD_SIZE', 20971520); // 20MB for Gemini question import
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('QUESTION_IMAGE_PATH', dirname(__DIR__) . '/assets/uploads/question_images/');
=======
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
>>>>>>> c2f3db94eeb05dddf4e2a299051e40ec27455b93
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1

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