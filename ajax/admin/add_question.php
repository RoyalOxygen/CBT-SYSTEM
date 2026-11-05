<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
node_require('question_edit');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) jsonResponse(false, 'Invalid token');
if (!isAdminLoggedIn()) jsonResponse(false, 'Not authenticated');

try {
    $db = getDB();
    
    $examId = intval($_POST['exam_id'] ?? 0);
    if (!$examId) jsonResponse(false, 'Exam ID required');
    
    if (empty($_POST['question_text']) || empty($_POST['option_a']) || empty($_POST['option_b']) || empty($_POST['correct_answer'])) {
        jsonResponse(false, 'Question text, options A & B, and correct answer are required');
    }
<<<<<<< HEAD

    $correctAnswer = strtoupper(trim((string) $_POST['correct_answer']));
    if (!in_array($correctAnswer, ['A', 'B', 'C', 'D', 'E'], true)) {
        jsonResponse(false, 'Correct answer must be A, B, C, D, or E');
    }

    $optionC = trim((string) ($_POST['option_c'] ?? ''));
    $optionD = trim((string) ($_POST['option_d'] ?? ''));
    $optionE = trim((string) ($_POST['option_e'] ?? ''));
    if ($correctAnswer === 'C' && $optionC === '') jsonResponse(false, 'Option C is required when C is the correct answer');
    if ($correctAnswer === 'D' && $optionD === '') jsonResponse(false, 'Option D is required when D is the correct answer');
    if ($correctAnswer === 'E' && $optionE === '') jsonResponse(false, 'Option E is required when E is the correct answer');
    
    $imagePath = null;
    if (!empty($_FILES['question_image']) && ($_FILES['question_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && $_FILES['question_image']['size'] > 0) {
        if (!is_dir(QUESTION_IMAGE_PATH)) {
            mkdir(QUESTION_IMAGE_PATH, 0775, true);
        }
        $upload = uploadFile($_FILES['question_image'], QUESTION_IMAGE_PATH);
        if (!$upload['success']) {
            jsonResponse(false, 'Image upload failed: ' . $upload['error']);
        }
        $imagePath = $upload['path'];
=======
    
    // Handle image upload
    $imagePath = null;
    if (!empty($_FILES['question_image']) && $_FILES['question_image']['size'] > 0) {
        $upload = uploadFile($_FILES['question_image'], QUESTION_IMAGE_PATH);
        if ($upload['success']) $imagePath = $upload['path'];
>>>>>>> c2f3db94eeb05dddf4e2a299051e40ec27455b93
    }
    
    // Get max order
    $orderStmt = $db->prepare("SELECT MAX(question_order) FROM questions WHERE exam_id = ?");
    $orderStmt->execute([$examId]);
    $maxOrder = $orderStmt->fetchColumn() ?? 0;
    
    $stmt = $db->prepare("INSERT INTO questions 
        (exam_id, question_text, question_image, option_a, option_b, option_c, option_d, option_e, correct_answer, marks, question_order) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $examId,
<<<<<<< HEAD
        trim((string) $_POST['question_text']),
        $imagePath,
        trim((string) $_POST['option_a']),
        trim((string) $_POST['option_b']),
        $optionC !== '' ? $optionC : null,
        $optionD !== '' ? $optionD : null,
        $optionE !== '' ? $optionE : null,
        $correctAnswer,
=======
        $_POST['question_text'],
        $imagePath,
        $_POST['option_a'],
        $_POST['option_b'],
        $_POST['option_c'] ?? null,
        $_POST['option_d'] ?? null,
        $_POST['option_e'] ?? null,
        $_POST['correct_answer'],
>>>>>>> c2f3db94eeb05dddf4e2a299051e40ec27455b93
        floatval($_POST['marks'] ?? 1),
        $maxOrder + 1
    ]);
    
    logActivity('admin', $_SESSION['admin_id'], 'add_question', "Added question to exam ID: $examId");
    jsonResponse(true, 'Question added successfully');
    
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
