<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

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
    
    $matric_number = sanitize($_POST['matric_number'] ?? '');
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $other_name = sanitize($_POST['other_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    
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
        "date_of_birth = ?"
    ];
    $params = [$matric_number, $first_name, $last_name, $other_name, $email, $phone, $gender, $date_of_birth];
    
    if ($photo_path) {
        $updates[] = "photo = ?";
        $params[] = $photo_path;
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