<?php
/**
 * CBT System - Import Student from CMS
 * Saves the fetched student data to the database
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (ob_get_level()) {
    ob_end_clean();
}
ob_start();
header('Content-Type: application/json');

function cmsImportRespond($payload) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode($payload);
    exit;
}

if (!isAdminLoggedIn()) {
    cmsImportRespond(['success' => false, 'message' => 'Unauthorized access']);
}

$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST[CSRF_TOKEN_NAME] ?? '';
if (!validateCSRFToken($csrf_token)) {
    cmsImportRespond(['success' => false, 'message' => 'Invalid security token']);
}

$matric_number = isset($_POST['matric_number']) ? sanitize($_POST['matric_number']) : '';
$first_name = isset($_POST['first_name']) ? sanitize($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? sanitize($_POST['last_name']) : '';
$other_name = isset($_POST['other_name']) ? sanitize($_POST['other_name']) : '';
$email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
$photo_url = isset($_POST['photo']) ? trim($_POST['photo']) : '';
$photo_data = isset($_POST['photo_data']) ? trim($_POST['photo_data']) : '';
$department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
$level_id = isset($_POST['level_id']) ? intval($_POST['level_id']) : 0;
$semester_id = isset($_POST['semester_id']) ? intval($_POST['semester_id']) : 0;
$session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
$auto_password = !empty($_POST['auto_password']) ? 1 : 0;

if (empty($matric_number)) {
    cmsImportRespond(['success' => false, 'message' => 'Matric number is required']);
}

if (empty($first_name) || empty($last_name)) {
    cmsImportRespond(['success' => false, 'message' => 'First name and last name are required']);
}

if ($department_id <= 0) {
    cmsImportRespond(['success' => false, 'message' => 'Department could not be mapped. Please check the department name.']);
}

if ($level_id <= 0) {
    cmsImportRespond(['success' => false, 'message' => 'Level could not be mapped. Please check the level.']);
}

if ($semester_id <= 0) {
    cmsImportRespond(['success' => false, 'message' => 'Semester is required']);
}

if ($session_id <= 0) {
    cmsImportRespond(['success' => false, 'message' => 'Session is required']);
}

try {
    $db = getDB();

    $checkStmt = $db->prepare("SELECT id FROM students WHERE matric_number = ?");
    $checkStmt->execute([$matric_number]);
    if ($checkStmt->fetch()) {
        cmsImportRespond(['success' => false, 'message' => 'Student with this matric number already exists']);
    }

    if (!empty($email)) {
        $checkStmt = $db->prepare("SELECT id FROM students WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            cmsImportRespond(['success' => false, 'message' => 'Email already exists']);
        }
    }

    $photo_path = null;
    try {
        if (!empty($photo_data) && strpos($photo_data, 'data:image') === 0) {
            $photo_path = saveStudentPhotoFromData($photo_data, $matric_number);
        }
        if (!$photo_path && !empty($photo_url)) {
            $photo_path = downloadStudentPhoto($photo_url, $matric_number);
        }
    } catch (Exception $photoError) {
        error_log("CMS photo save skipped: " . $photoError->getMessage());
        $photo_path = null;
    }

    $password = $auto_password ? $matric_number : generatePassword(8);
    $hashed_password = hashPassword($password);

    $stmt = $db->prepare("
        INSERT INTO students (
            matric_number, password, first_name, last_name, other_name,
            email, photo, department_id, level_id, semester_id, session_id,
            status, created_at
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            1, NOW()
        )
    ");

    $stmt->execute([
        $matric_number,
        $hashed_password,
        $first_name,
        $last_name,
        $other_name,
        $email,
        $photo_path,
        $department_id,
        $level_id,
        $semester_id,
        $session_id
    ]);

    $student_id = $db->lastInsertId();

    logActivity('admin', $_SESSION['admin_id'], 'import_cms_student',
               "Imported student from CMS: $matric_number");

    cmsImportRespond([
        'success' => true,
        'message' => 'Student imported successfully! ' . ($auto_password ? "Password: $password" : ''),
        'student_id' => $student_id,
        'password' => $auto_password ? $password : null
    ]);

} catch (PDOException $e) {
    error_log("Import CMS student error: " . $e->getMessage());
    cmsImportRespond(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Import CMS student error: " . $e->getMessage());
    cmsImportRespond(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function cmsPhotoUploadDir() {
    $uploadDir = __DIR__ . '/../../assets/uploads/student_photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    return $uploadDir;
}

function saveStudentPhotoFromData($dataUri, $matric_number) {
    if (!preg_match('#^data:(image/(jpeg|png|gif|webp));base64,(.+)$#s', $dataUri, $match)) {
        return null;
    }

    $mimeType = $match[1];
    $imageData = base64_decode($match[3], true);
    if ($imageData === false || $imageData === '') {
        return null;
    }

    $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($mimeToExt[$mimeType])) {
        return null;
    }

    $filename = 'cms_' . preg_replace('/[^a-zA-Z0-9]/', '_', $matric_number) . '_' . time() . '.' . $mimeToExt[$mimeType];
    $filepath = cmsPhotoUploadDir() . $filename;

    if (file_put_contents($filepath, $imageData) === false) {
        error_log("Failed to save CMS photo from preview data: $filepath");
        return null;
    }

    return $filename;
}

function downloadStudentPhoto($url, $matric_number) {
    if (empty($url)) {
        return null;
    }

    $url = resolveCmsPhotoUrl($url);
    if ($url === '') {
        return null;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Referer: https://cms.tsuniversity.edu.ng/verify/search.php',
            'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
        ],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 8,
    ]);

    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError || $httpCode !== 200 || empty($imageData)) {
        error_log("Failed to download photo: $url (HTTP $httpCode) $curlError");
        return null;
    }

    if (!function_exists('finfo_open')) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_buffer($finfo, $imageData);
    finfo_close($finfo);

    $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($mimeToExt[$mimeType])) {
        error_log("Invalid image type: $mimeType");
        return null;
    }

    $filename = 'cms_' . preg_replace('/[^a-zA-Z0-9]/', '_', $matric_number) . '_' . time() . '.' . $mimeToExt[$mimeType];
    $filepath = cmsPhotoUploadDir() . $filename;

    if (file_put_contents($filepath, $imageData) === false) {
        error_log("Failed to save image: $filepath");
        return null;
    }

    return $filename;
}

function resolveCmsPhotoUrl($src) {
    $src = trim($src);
    if ($src === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $src)) {
        return $src;
    }
    $src = preg_replace('#^(\.\./)+#', '', $src);
    $src = ltrim($src, '/');
    return 'https://cms.tsuniversity.edu.ng/' . $src;
}
