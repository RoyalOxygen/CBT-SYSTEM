<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isAdminLoggedIn()) {
    jsonResponse(false, 'Unauthorized');
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    jsonResponse(false, 'Invalid security token');
}

$student_id = intval($input['student_id'] ?? 0);
if ($student_id <= 0) {
    jsonResponse(false, 'Invalid student ID');
}

try {
    $db = getDB();
    
    // Generate new password
    $new_password = generatePassword(8);
    $hashed_password = hashPassword($new_password);
    
    $stmt = $db->prepare("UPDATE students SET password = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $student_id]);
    
    // Get student details
    $stmt = $db->prepare("SELECT matric_number, first_name, last_name FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    logActivity('admin', $_SESSION['admin_id'], 'reset_student_password', "Reset password for: " . ($student['matric_number'] ?? $student_id));
    
    jsonResponse(true, 'Password reset successfully', ['password' => $new_password]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error');
}
?>