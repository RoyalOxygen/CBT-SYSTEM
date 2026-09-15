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

$exam_id = intval($_POST['exam_id'] ?? 0);
$batch_name = sanitize($_POST['batch_name'] ?? '');
$batch_date = $_POST['batch_date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$duration = intval($_POST['duration'] ?? 0);
$capacity = !empty($_POST['capacity']) ? intval($_POST['capacity']) : null;
$venue = sanitize($_POST['venue'] ?? '');
$biometric_required = isset($_POST['biometric_required']) ? 1 : 0;

if ($exam_id <= 0) {
    jsonResponse(false, 'Invalid exam ID');
}

if (empty($batch_name)) {
    jsonResponse(false, 'Batch name is required');
}

if (empty($batch_date)) {
    jsonResponse(false, 'Batch date is required');
}

if (empty($start_time)) {
    jsonResponse(false, 'Start time is required');
}

if (empty($end_time)) {
    jsonResponse(false, 'End time is required');
}

if ($duration <= 0) {
    jsonResponse(false, 'Valid duration is required');
}

try {
    $db = getDB();
    
    // Generate batch code
    $batch_code = 'BCH-' . $exam_id . '-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $batch_name), 0, 3)) . '-' . date('Ymd');
    
    // Check if exam exists
    $examStmt = $db->prepare("SELECT id FROM exams WHERE id = ?");
    $examStmt->execute([$exam_id]);
    if (!$examStmt->fetch()) {
        jsonResponse(false, 'Exam not found');
    }
    
    // Insert batch
    $stmt = $db->prepare("
        INSERT INTO exam_batches (exam_id, batch_code, batch_name, batch_date, start_time, end_time, duration, capacity, venue, biometric_required, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $exam_id, $batch_code, $batch_name, $batch_date, $start_time, $end_time, 
        $duration, $capacity, $venue, $biometric_required
    ]);
    
    $batch_id = $db->lastInsertId();
    
    // Update exam to have batches
    $updateStmt = $db->prepare("UPDATE exams SET has_batches = 1 WHERE id = ?");
    $updateStmt->execute([$exam_id]);
    
    logActivity('admin', $_SESSION['admin_id'], 'create_batch', 
               "Created batch '$batch_name' for exam ID: $exam_id");
    
    jsonResponse(true, 'Batch created successfully', ['batch_id' => $batch_id, 'batch_code' => $batch_code]);
    
} catch (PDOException $e) {
    error_log("Create batch error: " . $e->getMessage());
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>