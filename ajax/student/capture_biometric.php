<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

// Ensure student is logged in
if (!isStudentLoggedIn()) {
    jsonResponse(false, 'Unauthorized access');
}

// Refresh session activity to keep student session alive
refreshStudentSessionActivity();

// Verify CSRF token
$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    jsonResponse(false, 'Invalid security token');
}

$studentId = $_SESSION['student_id'];

try {
    $db = getDB();
    
    // Check if already enrolled
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM fingerprint_templates WHERE student_id = ?");
    $checkStmt->execute([$studentId]);
    if ($checkStmt->fetchColumn() > 0) {
        jsonResponse(false, 'Biometric already enrolled');
    }
    
    // Save fingerprint template
    $stmt = $db->prepare("
        INSERT INTO fingerprint_templates (student_id, template_data, template_hash, quality_score, finger_position) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $studentId,
        $input['