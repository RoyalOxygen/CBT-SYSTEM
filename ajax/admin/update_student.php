<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
node_require('student_edit');

if (!isAdminLoggedIn()) {
    jsonResponse(false, 'Unauthorized');
}

$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST[CSRF_TOKEN_NAME] ?? '';
if (!validateCSRFToken($csrf_token)) {
    jsonResponse(false, 'Invalid security token');
}

$student_id = intval($_POST['student_id'] ?? 0);
if ($student_id <= 0) {
    jsonResponse(false, 'Invalid student ID');
}

try {
    $db = getDB();
    
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
    $new_password = (string) ($_POST['password'] ?? '');
    $status = array_key_exists('status', $_POST) ? (intval($_POST['status']) ? 1 : 0) : null;

    if ($matric_number === null) {
        jsonResponse(false, 'Matric number is required');
    }
    if ($gender !== null && !in_array($gender, ['Male', 'Female'], true)) {
        $gender = null;
    }
    
    // Check if matric number exists for other student
    $checkStmt = $db->prepare("SELECT id FROM students WHERE matric_number = ? AND id != ?");
    $checkStmt->execute([$matric_number, $student_id]);
    if ($checkStmt->fetch()) {
        jsonResponse(false, 'Matric number already exists');
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
            
            // Delete old photo
            $stmt = $db->prepare("SELECT photo FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $old = $stmt->fetch();
            if ($old && $old['photo'] && file_exists($uploadDir . $old['photo'])) {
                unlink($uploadDir . $old['photo']);
            }
        }
    }
    
    // Build update query
    $updates = [
        "matric_number = ?",
        "first_name = ?",
        "last_name = ?",
        "other_name = ?",
        "email = ?",
        "phone = ?",
        "gender = ?",
        "date_of_birth = ?",
        "department_id = ?",
        "level_id = ?",
        "semester_id = ?",
        "session_id = ?"
    ];
    $params = [$matric_number, $first_name, $last_name, $other_name, $email, $phone, $gender, $date_of_birth, $department_id, $level_id, $semester_id, $session_id];
    
    if ($photo_path) {
        $updates[] = "photo = ?";
        $params[] = $photo_path;
    }
    if ($new_password !== '') {
        $updates[] = "password = ?";
        $params[] = hashPassword($new_password);
    }
    if ($status !== null) {
        $updates[] = "status = ?";
        $params[] = $status;
    }
    
    $params[] = $student_id;
    $stmt = $db->prepare("UPDATE students SET " . implode(", ", $updates) . " WHERE id = ?");
    $stmt->execute($params);
    
    logActivity('admin', $_SESSION['admin_id'], 'update_student', "Updated student ID: $student_id");
    
    jsonResponse(true, 'Student updated successfully');
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>