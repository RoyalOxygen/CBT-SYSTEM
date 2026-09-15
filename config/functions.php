<?php
/**
 * CBT System - Helper Functions
 */

// Prevent direct access
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

require_once __DIR__ . '/database.php';

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF token as hidden input field
 */
function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCSRFToken() . '">';
}

/**
 * Check if user is logged in as admin
 */
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0 && 
           isset($_SESSION['admin_role']) && 
           time() - ($_SESSION['last_activity'] ?? 0) < SESSION_TIMEOUT;
}

/**
 * Check if student is logged in
 * Student sessions persist for a very long time (STUDENT_SESSION_TIMEOUT)
 * and are only ended by explicit logout or a new login.
 */
function isStudentLoggedIn(): bool {
    if (!isset($_SESSION['student_id']) || $_SESSION['student_id'] <= 0) {
        return false;
    }
    // Students: keep session alive as long as possible unless logged out or new login
    $timeout = defined('STUDENT_SESSION_TIMEOUT') ? STUDENT_SESSION_TIMEOUT : SESSION_TIMEOUT;
    return time() - ($_SESSION['last_activity'] ?? 0) < $timeout;
}

/**
 * Refresh student session activity timestamp to extend the session lifetime.
 * Call this on every student-authenticated request (page loads and AJAX).
 */
function refreshStudentSessionActivity(): void {
    if (isset($_SESSION['student_id'])) {
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Attempt to auto-login a student from a valid remember-cookie.
 * Used when the PHP session has expired but the student still has a
 * "Remember Me" cookie, keeping them logged in as long as possible.
 */
function autoLoginFromRememberCookie(string $matric): bool {
    if (empty($matric)) {
        return false;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT id, matric_number, first_name, last_name, other_name, email, photo,
                   fingerprint_enrolled, status, department_id, level_id
            FROM students
            WHERE matric_number = ? AND status = 1
        ");
        $stmt->execute([$matric]);
        $student = $stmt->fetch();

        if (!$student) {
            return false;
        }

        // Clear any existing session, then start fresh (new login kills old session)
        session_unset();
        session_regenerate_id(true);

        $_SESSION['student_id'] = $student['id'];
        $_SESSION['matric_number'] = $student['matric_number'];
        $_SESSION['student_name'] = $student['last_name'] . ', ' . $student['first_name'];
        $_SESSION['student_first_name'] = $student['first_name'];
        $_SESSION['student_last_name'] = $student['last_name'];
        $_SESSION['fingerprint_enrolled'] = $student['fingerprint_enrolled'];
        $_SESSION['last_activity'] = time();

        if ($student['department_id']) {
            $deptStmt = $db->prepare("SELECT dept_name FROM departments WHERE id = ?");
            $deptStmt->execute([$student['department_id']]);
            $dept = $deptStmt->fetch();
            $_SESSION['student_department'] = $dept['dept_name'] ?? 'N/A';
        }

        if ($student['level_id']) {
            $levelStmt = $db->prepare("SELECT level_name FROM levels WHERE id = ?");
            $levelStmt->execute([$student['level_id']]);
            $level = $levelStmt->fetch();
            $_SESSION['student_level'] = $level['level_name'] ?? 'N/A';
        }

        $db->prepare("UPDATE students SET last_login = NOW(), login_ip = ?, login_status = 1 WHERE id = ?")
            ->execute([getClientIP(), $student['id']]);

        logActivity('student', $student['id'], 'login', 'Student auto-logged in via remember cookie');

        return true;
    } catch (PDOException $e) {
        error_log("Auto-login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Require admin login
 */
function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        session_destroy();
        header('Location: ' . APP_URL . '/admin/index.php');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Require student login
 */
function requireStudentLogin(): void {
    if (!isStudentLoggedIn()) {
        session_destroy();
        header('Location: ' . APP_URL . '/student/login.php');
        exit;
    }
    refreshStudentSessionActivity();
}

/**
 * Check admin role access
 */
function hasAdminRole(array $allowedRoles): bool {
    return isAdminLoggedIn() && in_array($_SESSION['admin_role'], $allowedRoles);
}

/**
 * Sanitize input
 */
function sanitize(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Clean output
 */
function e(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Hash password
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

/**
 * Verify password
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Generate random password
 */
function generatePassword(int $length = 10): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * Generate unique exam code
 */
function generateExamCode(string $prefix = 'EXM'): string {
    return $prefix . date('Y') . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Generate unique batch code
 */
function generateBatchCode(int $examId, string $batchName): string {
    return 'BCH-' . $examId . '-' . strtoupper(substr(str_replace(' ', '', $batchName), 0, 3)) . '-' . date('Ymd');
}

/**
 * Format date
 */
function formatDate(string $date, string $format = 'M d, Y'): string {
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime(string $datetime, string $format = 'M d, Y h:i A'): string {
    return date($format, strtotime($datetime));
}

/**
 * Format time duration
 */
function formatDuration(int $minutes): string {
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . 'h ' . ($mins > 0 ? $mins . 'm' : '');
}

/**
 * Get grade from percentage
 */
function getGrade(float $percentage): array {
    global $GRADING_SCALE;
    foreach ($GRADING_SCALE as $grade) {
        if ($percentage >= $grade['min'] && $percentage <= $grade['max']) {
            return $grade;
        }
    }
    return ['grade' => 'F', 'remark' => 'Fail'];
}

/**
 * Get exam status badge
 */
function getExamStatusBadge(string $status): string {
    $badges = [
        'draft' => '<span class="badge bg-secondary">Draft</span>',
        'published' => '<span class="badge bg-info">Published</span>',
        'running' => '<span class="badge bg-success">Running</span>',
        'ended' => '<span class="badge bg-warning text-dark">Ended</span>',
        'archived' => '<span class="badge bg-dark">Archived</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Get attempt status badge
 */
function getAttemptStatusBadge(string $status): string {
    $badges = [
        'in_progress' => '<span class="badge bg-primary">In Progress</span>',
        'submitted' => '<span class="badge bg-success">Submitted</span>',
        'auto_submitted' => '<span class="badge bg-warning text-dark">Auto-Submitted</span>',
        'timed_out' => '<span class="badge bg-danger">Timed Out</span>',
        'reset' => '<span class="badge bg-secondary">Reset</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Upload file helper
 */
function uploadFile(array $file, string $destination, array $allowedTypes = ALLOWED_IMAGE_TYPES, int $maxSize = MAX_UPLOAD_SIZE): array {
    $result = ['success' => false, 'path' => '', 'error' => ''];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'Upload failed with error code: ' . $file['error'];
        return $result;
    }
    
    if ($file['size'] > $maxSize) {
        $result['error'] = 'File size exceeds maximum allowed (' . ($maxSize / 1024 / 1024) . 'MB)';
        return $result;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $result['error'] = 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes);
        return $result;
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $filepath = $destination . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $result['success'] = true;
        $result['path'] = $filename;
    } else {
        $result['error'] = 'Failed to move uploaded file';
    }
    
    return $result;
}

/**
 * Send JSON response
 */
function jsonResponse(bool $success, string $message = '', array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

/**
 * Log activity
 */
function logActivity(string $userType, int $userId, string $action, string $description = ''): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO activity_logs (user_type, user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userType,
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

/**
 * Get client IP address
 */
function getClientIP(): string {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            return $_SERVER[$header];
        }
    }
    return '0.0.0.0';
}

/**
 * Paginate results
 */
function paginate(PDO $db, string $query, array $params = [], int $page = 1, int $perPage = DEFAULT_PAGE_SIZE): array {
    $offset = ($page - 1) * $perPage;
    
    // Get total count
    $countQuery = preg_replace('/SELECT\s+.*?\s+FROM\s+/i', 'SELECT COUNT(*) as total FROM ', $query, 1);
    $countQuery = preg_replace('/\s+LIMIT\s+\d+\s+OFFSET\s+\d+/i', '', $countQuery);
    $countQuery = preg_replace('/\s+ORDER\s+BY\s+.*$/i', '', $countQuery);
    
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'] ?? 0;
    
    // Get paginated results
    $query .= " LIMIT $perPage OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    return [
        'data' => $results,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => ceil($total / $perPage)
    ];
}

/**
 * Shuffle array preserving keys
 */
function shuffleAssoc(array $array): array {
    $keys = array_keys($array);
    shuffle($keys);
    $shuffled = [];
    foreach ($keys as $key) {
        $shuffled[$key] = $array[$key];
    }
    return $shuffled;
}

/**
 * Export data to CSV
 */
function exportToCSV(array $data, array $headers, string $filename): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Calculate time remaining in seconds
 */
function getTimeRemaining(int $startTime, int $durationMinutes, int $extensionMinutes = 0): int {
    $endTime = $startTime + (($durationMinutes + $extensionMinutes) * 60);
    return max(0, $endTime - time());
}

/**
 * Format seconds to MM:SS
 */
function formatTimer(int $seconds): string {
    $mins = floor($seconds / 60);
    $secs = $seconds % 60;
    return sprintf('%02d:%02d', $mins, $secs);
}

/**
 * Get setting value
 */
function getSetting(string $key, $default = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Set setting value
 */
function setSetting(string $key, string $value, string $group = 'general'): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $value, $group]);
    } catch (PDOException $e) {
        error_log("Setting save error: " . $e->getMessage());
    }
}

/**
 * Display toast notification
 */
function showToast(string $message, string $type = 'success'): string {
    return "<script>showToast('" . addslashes($message) . "', '$type');</script>";
}

/**
 * Redirect with message
 */
function redirect(string $url, string $message = '', string $type = 'success'): void {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

/**
 * Get flash message
 */
function getFlashMessage(): array {
    $message = $_SESSION['flash_message'] ?? '';
    $type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    return ['message' => $message, 'type' => $type];
}
