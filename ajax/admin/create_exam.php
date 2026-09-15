<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) jsonResponse(false, 'Invalid token');
if (!isAdminLoggedIn()) jsonResponse(false, 'Not authenticated');

try {
    $db = getDB();
    
    $required = ['exam_code', 'exam_title', 'exam_date', 'start_time', 'duration'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
    }
    
    // Check unique code
    $check = $db->prepare("SELECT id FROM exams WHERE exam_code = ?");
    $check->execute([$_POST['exam_code']]);
    if ($check->fetch()) jsonResponse(false, 'Exam code already exists');
    
    $stmt = $db->prepare("INSERT INTO exams 
        (exam_code, exam_title, department_id, level_id, semester_id, session_id, question_limit, exam_date, start_time, duration, fingerprint_required, shuffle_questions, shuffle_options, show_result, pass_mark, instruction, status, has_batches, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?)");
    
    $stmt->execute([
        sanitize($_POST['exam_code']),
        sanitize($_POST['exam_title']),
        !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
        !empty($_POST['level_id']) ? intval($_POST['level_id']) : null,
        !empty($_POST['semester_id']) ? intval($_POST['semester_id']) : null,
        !empty($_POST['session_id']) ? intval($_POST['session_id']) : null,
        intval($_POST['question_limit'] ?? DEFAULT_QUESTION_LIMIT),
        $_POST['exam_date'],
        $_POST['start_time'],
        intval($_POST['duration']),
        !empty($_POST['fingerprint_required']) ? 1 : 0,
        !empty($_POST['shuffle_questions']) ? 1 : 0,
        !empty($_POST['shuffle_options']) ? 1 : 0,
        !empty($_POST['show_result']) ? 1 : 0,
        intval($_POST['pass_mark'] ?? DEFAULT_PASS_MARK),
        sanitize($_POST['instruction'] ?? ''),
        !empty($_POST['has_batches']) ? 1 : 0,
        $_SESSION['admin_id']
    ]);
    
    $examId = $db->lastInsertId();
    logActivity('admin', $_SESSION['admin_id'], 'create_exam', "Created exam: {$_POST['exam_code']}");
    
    jsonResponse(true, 'Exam created successfully', ['exam_id' => $examId]);
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
