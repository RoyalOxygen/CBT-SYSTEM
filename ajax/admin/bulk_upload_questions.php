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
    $examId = intval($_POST['exam_id'] ?? 0);
    if (!$examId) jsonResponse(false, 'Exam ID required');
    
    if (empty($_FILES['csv_file'])) jsonResponse(false, 'CSV file required');
    
    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
    fgetcsv($handle); // skip header
    
    $imported = 0;
    $orderStmt = $db->prepare("SELECT MAX(question_order) FROM questions WHERE exam_id = ?");
    $orderStmt->execute([$examId]);
    $order = ($orderStmt->fetchColumn() ?? 0) + 1;
    
    $stmt = $db->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, option_e, correct_answer, marks, question_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) < 7) continue;
        $stmt->execute([$examId, $data[0], $data[1], $data[2], $data[3] ?? null, $data[4] ?? null, $data[5] ?? null, strtoupper($data[6]), floatval($data[7] ?? 1), $order++]);
        $imported++;
    }
    fclose($handle);
    
    logActivity('admin', $_SESSION['admin_id'], 'bulk_import_questions', "Imported $imported questions to exam $examId");
    jsonResponse(true, "Imported $imported questions");
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
