<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
node_require('student_edit');

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
    $matric_number = blankToNull(sanitize($_POST['matric_number'] ?? ''));
    $first_name = blankToNull(sanitize($_POST['first_name'] ?? ''));
    $last_name = blankToNull(sanitize($_POST['last_name'] ?? ''));
    $other_name = blankToNull(sanitize($_POST['other_name'] ?? ''));
    $email = blankToNull(sanitize($_POST['email'] ?? ''));
    $phone = blankToNull(sanitize($_POST['phone'] ?? ''));
    $gender = blankToNull(sanitize($_POST['gender'] ?? ''));
    $date_of_birth = blankToNull($_POST['date_of_birth'] ?? '');
    $department_id = optionalId($_POST['department_id'] ?? 0);
    $level_id = optionalId($_POST['level_id'] ?? 0);
    $semester_id = optionalId($_POST['semester_id'] ?? 0);
    $session_id = optionalId($_POST['session_id'] ?? 0);
    $auto_password = isset($_POST['auto_password']);
    
    if ($gender !== null && !in_array($gender, ['Male', 'Female'], true)) {
        $gender = null;
    }

    // Validate required fields
    if ($matric_number === null) {
        jsonResponse(false, 'Matric number is required');
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
    
    $password = null;
    $hashed_password = null;
    if ($auto_password) {
        $password = $matric_number;
        $hashed_password = hashPassword($password);
    }
    
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
    
    jsonResponse(true, 'Student added successfully' . ($auto_password && $password !== null ? ". Password: $password" : ''), [
        'student_id' => $student_id,
        'password' => $auto_password ? $password : null
    ]);
    
} catch (PDOException $e) {
    error_log("Add student error: " . $e->getMessage());
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>