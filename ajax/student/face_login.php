<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid method');
}

if (!face_login_enabled()) {
    jsonResponse(false, 'Face login is disabled on this server.');
}

$token = $_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!validateCSRFToken($token)) {
    jsonResponse(false, 'Invalid security token. Please refresh the page.');
}

$matric = strtoupper(sanitize($_POST['matric_number'] ?? $_POST['matric_no'] ?? ''));
$images = face_images_from_request();
if (!$images) {
    jsonResponse(false, 'Live face capture is required.');
}

try {
    sams_ensure_schema(getDB());
    $db = getDB();
    $student = null;
    $match = null;

    if ($matric !== '') {
        $stmt = $db->prepare("SELECT id, matric_number, first_name, last_name, other_name, email, photo, fingerprint_enrolled, face_enrolled, status, department_id, level_id FROM students WHERE matric_number = ?");
        $stmt->execute([$matric]);
        $student = $stmt->fetch();
        if (!$student) {
            logActivity('student', 0, 'face_login_failed', 'Unknown matric: ' . $matric);
            jsonResponse(false, 'Student account not found. Complete attendance enrollment first.');
        }
        if ((int) $student['status'] !== 1) {
            jsonResponse(false, 'Your account is inactive. Please contact the administrator.');
        }
        $match = face_match_student((int) $student['id'], $images[0]);
        if (empty($match['ok'])) {
            logActivity('student', (int) $student['id'], 'face_login_failed', $match['reason'] ?? 'mismatch');
            jsonResponse(false, $match['reason'] ?? 'Face does not match enrolled template.', ['score' => $match['score'] ?? 0]);
        }
    } else {
        $match = face_verify_one_n($images[0]);
        if (empty($match['ok']) || empty($match['student_id'])) {
            logActivity('student', 0, 'face_login_failed', $match['reason'] ?? 'unrecognized');
            jsonResponse(false, $match['reason'] ?? 'Face not recognized.');
        }
        $stmt = $db->prepare("SELECT id, matric_number, first_name, last_name, other_name, email, photo, fingerprint_enrolled, face_enrolled, status, department_id, level_id FROM students WHERE id = ?");
        $stmt->execute([(int) $match['student_id']]);
        $student = $stmt->fetch();
        if (!$student || (int) $student['status'] !== 1) {
            jsonResponse(false, 'Matched face has no active student account.');
        }
    }

    session_unset();
    session_regenerate_id(true);
    $_SESSION['student_id'] = $student['id'];
    $_SESSION['matric_number'] = $student['matric_number'];
    $_SESSION['student_name'] = studentDisplayName($student['last_name'] ?? null, $student['first_name'] ?? null);
    $_SESSION['student_first_name'] = $student['first_name'] ?? '';
    $_SESSION['student_last_name'] = $student['last_name'] ?? '';
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
    logActivity('student', (int) $student['id'], 'login', 'Student logged in (face)');

    $redirect = $_SESSION['redirect_after_login'] ?? APP_URL . '/student/dashboard.php';
    unset($_SESSION['redirect_after_login']);
    jsonResponse(true, 'Face verified', [
        'redirect' => $redirect,
        'score' => $match['score'] ?? 0,
    ]);
} catch (Throwable $e) {
    error_log('Face login error: ' . $e->getMessage());
    jsonResponse(false, 'Face verification failed. Check the face service URL.');
}
