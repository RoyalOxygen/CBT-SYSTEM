<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) jsonResponse(false, 'Invalid token');
if (!isStudentLoggedIn()) jsonResponse(false, 'Not authenticated');

// Refresh session activity to keep student session alive
refreshStudentSessionActivity();

try {
    $db = getDB();
    $studentId = $_SESSION['student_id'];
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    
    if (strlen($new) < 6) jsonResponse(false, 'Password must be at least 6 characters');
    
    // Verify current
    $stmt = $db->prepare("SELECT password FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if (!$student || !verifyPassword($current, $student['password'])) {
        jsonResponse(false, 'Current password is incorrect');
    }
    
    $db->prepare("UPDATE students SET password = ? WHERE id = ?")->execute([hashPassword($new), $studentId]);
    logActivity('student', $studentId, 'change_password', 'Password changed');
    
    jsonResponse(true, 'Password changed successfully');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
