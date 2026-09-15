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
    $examId = intval($_POST['composite_exam_id'] ?? 0);
    if (!$examId) jsonResponse(false, 'Exam ID required');
    
    $questionText = trim($_POST['question_text'] ?? '');
    if (empty($questionText)) jsonResponse(false, 'Main question text is required');
    
    $subQuestions = $_POST['sub_questions'] ?? [];
    if (empty($subQuestions)) jsonResponse(false, 'At least one sub-question is required');
    
    // Handle main question image
    $imagePath = null;
    if (!empty($_FILES['question_image']) && $_FILES['question_image']['size'] > 0) {
        $uploadDir = __DIR__ . '/../../assets/uploads/question_images/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $result = uploadFile($_FILES['question_image'], $uploadDir, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
        if ($result['success']) $imagePath = $result['path'];
    }
    
    // Get max order
    $orderStmt = $db->prepare("SELECT MAX(question_order) FROM composite_questions WHERE composite_exam_id = ?");
    $orderStmt->execute([$examId]);
    $maxOrder = $orderStmt->fetchColumn() ?? 0;
    
    // Start transaction
    $db->beginTransaction();
    
    // Insert main question
    $stmt = $db->prepare("INSERT INTO composite_questions 
        (composite_exam_id, question_text, question_image, marks, question_order, status) 
        VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->execute([
        $examId,
        $questionText,
        $imagePath,
        floatval($_POST['marks'] ?? 1),
        $maxOrder + 1
    ]);
    
    $questionId = $db->lastInsertId();
    
    // Insert sub-questions and their options
    $subOrder = 0;
    foreach ($subQuestions as $sq) {
        $subOrder++;
        $label = $sq['label'] ?? chr(64 + $subOrder); // A, B, C...
        $text = trim($sq['text'] ?? '');
        $correctAnswer = strtoupper($sq['correct_answer'] ?? '');
        $marks = floatval($sq['marks'] ?? 1);
        $options = $sq['options'] ?? [];
        
        if (empty($text) || empty($correctAnswer)) continue;
        
        // Insert sub-question
        $subStmt = $db->prepare("INSERT INTO composite_sub_questions 
            (composite_question_id, sub_question_label, sub_question_text, correct_answer, marks, sub_order) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $subStmt->execute([$questionId, $label, $text, $correctAnswer, $marks, $subOrder]);
        $subQuestionId = $db->lastInsertId();
        
        // Insert options
        $optionOrder = 0;
        foreach ($options as $optLabel => $optText) {
            $optText = trim($optText);
            if (empty($optText)) continue;
            $optionOrder++;
            
            $optStmt = $db->prepare("INSERT INTO composite_sub_options 
                (composite_sub_question_id, option_label, option_text, option_order) 
                VALUES (?, ?, ?, ?)");
            $optStmt->execute([$subQuestionId, $optLabel, $optText, $optionOrder]);
        }
    }
    
    $db->commit();
    
    logActivity('admin', $_SESSION['admin_id'], 'add_composite_question', 
               "Added composite question to exam ID: $examId");
    
    jsonResponse(true, 'Composite question added successfully');
    
} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("Add composite question error: " . $e->getMessage());
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>