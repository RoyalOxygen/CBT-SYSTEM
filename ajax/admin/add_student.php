<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

// Ensure user is admin
if (!isAdminLoggedIn()) {
    jsonResponse(false, 'Unauthorized access');
}

// Verify CSRF token
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST[CSRF_TOKEN_NAME] ?? '';
if (!validateCSRFToken($csrf_token)) {
    jsonResponse(false, 'Invalid security token');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

try {
    $db = getDB();
    
    // Sanitize inputs
    $matric_number = sanitize($_POST['matric_number'] ?? '');
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $other_name = sanitize($_POST['other_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $department_id = intval($_POST['department_id'] ?? 0);
    $level_id = intval($_POST['level_id'] ?? 0);
    $semester_id = intval($_POST['semester_id'] ?? 0);
    $session_id = intval($_POST['session_id'] ?? 0);
    $auto_password = isset($_POST['auto_password']);
    
    // Validate required fields
    if (empty($matric_number)) {
        jsonResponse(false, 'Matric number is required');
    }
    if (empty($first_name) || empty($last_name)) {
        jsonResponse(false, 'First name and last name are required');
    }
    if ($department_id <= 0) {
        jsonResponse(false, 'Department is required');
    }
    if ($level_id <= 0) {
        jsonResponse(false, 'Level is required');
    }
    if ($semester_id <= 0) {
        jsonResponse(false, 'Semester is required');
    }
    if ($session_id <= 0) {
        jsonResponse(false, 'Session is required');
    }
    
    // Check if matric number already exists
    $checkStmt = $db->prepare("SELECT id FROM students WHERE matric_number = ?");
    $checkStmt->execute([$matric_number]);
    if ($checkStmt->fetch()) {
        jsonResponse(false, 'Matric number already exists');
    }
    
    // Check if email already exists (if provided)
    if (!empty($email)) {
        $checkStmt = $db->prepare("SELECT id FROM students WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            jsonResponse(false, 'Email already exists');
        }
    }
    
    // Generate password
    if ($auto_password) {
        $password = $matric_number; // Default password is matric number
    } else {
        $password = generatePassword();
    }
    $hashed_password = hashPassword($password);
    
    // Handle photo upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../assets/uploads/student_photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $result = uploadFile($_FILES['photo'], $uploadDir, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
        if ($result['success']) {
            $photo_path = $result['path'];
        } else {
            jsonResponse(false, 'Photo upload failed: ' . $result['error']);
        }
    }
    
    // Insert student
    $stmt = $db->prepare("
        INSERT INTO students (
            matric_number, password, first_name, last_name, other_name, 
            email, phone, gender, date_of_birth, department_id, 
            level_id, semester_id, session_id, photo, status, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, 1, NOW()
        )
    ");
    
    $stmt->execute([
        $matric_number, $hashed_password, $first_name, $last_name, $other_name,
        $email, $phone, $gender, $date_of_birth, $department_id,
        $level_id, $semester_id, $session_id, $photo_path
    ]);
    
    $student_id = $db->lastInsertId();
    
    // Log activity
    logActivity('admin', $_SESSION['admin_id'], 'add_student', "Added student: $matric_number");
    
    jsonResponse(true, 'Student added successfully. ' . ($auto_password ? "Password: $password" : ''), [
        'student_id' => $student_id,
        'password' => $auto_password ? $password : null
    ]);
    
} catch (PDOException $e) {
    error_log("Add student error: " . $e->getMessage());
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>