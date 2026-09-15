<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!isAdminLoggedIn()) jsonResponse(false, 'Not authenticated');

try {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    $studentId = intval($data['student_id'] ?? 0);
    
    // Delete old template if exists
    $db->prepare("DELETE FROM fingerprint_templates WHERE student_id = ?")->execute([$studentId]);
    
    // Insert new template
    $stmt = $db->prepare("INSERT INTO fingerprint_templates 
        (student_id, template_data, template_hash, finger_position, quality_score, enrolled_by) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $studentId,
        $data['template_data'],
        $data['template_hash'],
        $data['finger_position'] ?? 'right_thumb',
        $data['quality_score'] ?? null,
        $_SESSION['admin_id']
    ]);
    
    // Update student
    $db->prepare("UPDATE students SET fingerprint_enrolled = 1 WHERE id = ?")->execute([$studentId]);
    
    logActivity('admin', $_SESSION['admin_id'], 'biometric_enroll', "Enrolled biometric for student $studentId");
    jsonResponse(true, 'Biometric enrolled successfully');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
