<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
node_require('question_edit');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
<<<<<<< HEAD
if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) jsonResponse(false, 'Invalid token');
if (!isAdminLoggedIn()) jsonResponse(false, 'Not authenticated');

@set_time_limit(240);
@ini_set('max_execution_time', '240');

try {
    $db = getDB();
    $examId = intval($_POST['exam_id'] ?? 0);
    if (!$examId) jsonResponse(false, 'Exam is required');

    $examStmt = $db->prepare("SELECT id FROM exams WHERE id = ?");
    $examStmt->execute([$examId]);
    if (!$examStmt->fetch()) jsonResponse(false, 'Exam not found');

    $questionCount = intval($_POST['question_count'] ?? 0);
    if ($questionCount < 1) jsonResponse(false, 'Question count is required');
    if ($questionCount > 200) jsonResponse(false, 'Question count cannot exceed 200');

    $marks = floatval($_POST['marks'] ?? 1);
    if ($marks <= 0) $marks = 1;

    if (empty($_FILES['document']) || ($_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        jsonResponse(false, 'PDF, Word, or Excel file is required');
    }

    $file = $_FILES['document'];
    $maxSize = defined('MAX_DOCUMENT_UPLOAD_SIZE') ? MAX_DOCUMENT_UPLOAD_SIZE : 20971520;
    if ($file['size'] > $maxSize) {
        jsonResponse(false, 'File is too large. Maximum size is 20MB');
    }

    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $allowedExt = ['pdf', 'docx', 'doc', 'xlsx', 'xls', 'odt', 'ods'];
    if (!in_array($ext, $allowedExt, true)) {
        jsonResponse(false, 'Unsupported file type. Upload PDF, DOCX, DOC, XLSX, or XLS');
    }

    $mime = gemini_mime_from_upload($file);
    $extracted = gemini_extract_questions($file['tmp_name'], $mime, basename((string) $file['name']), $questionCount);
    if (empty($extracted['ok'])) {
        jsonResponse(false, $extracted['error'] ?? 'Gemini extraction failed');
    }

    $rows = $extracted['questions'] ?? [];
    $imported = 0;
    $skipped = 0;
    $errors = [];

    $orderStmt = $db->prepare("SELECT MAX(question_order) FROM questions WHERE exam_id = ?");
    $orderStmt->execute([$examId]);
    $order = (int) ($orderStmt->fetchColumn() ?? 0) + 1;

    $stmt = $db->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, option_e, correct_answer, marks, question_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $db->beginTransaction();
    foreach ($rows as $row) {
        if ($imported >= $questionCount) {
            break;
        }
        if (!is_array($row)) {
            $skipped++;
            continue;
        }
        $text = trim((string) ($row['question_text'] ?? ''));
        $a = trim((string) ($row['option_a'] ?? ''));
        $b = trim((string) ($row['option_b'] ?? ''));
        $c = trim((string) ($row['option_c'] ?? ''));
        $d = trim((string) ($row['option_d'] ?? ''));
        $e = trim((string) ($row['option_e'] ?? ''));
        $answer = strtoupper(trim((string) ($row['correct_answer'] ?? '')));
        if (strlen($answer) > 1) {
            $answer = substr($answer, 0, 1);
        }
        if ($text === '' || $a === '' || $b === '') {
            $skipped++;
            $errors[] = 'Skipped a question missing text or options A/B';
            continue;
        }
        if (!in_array($answer, ['A', 'B', 'C', 'D', 'E'], true)) {
            $skipped++;
            $errors[] = 'Skipped a question without a marked correct answer';
            continue;
        }
        if ($answer === 'C' && $c === '') { $skipped++; continue; }
        if ($answer === 'D' && $d === '') { $skipped++; continue; }
        if ($answer === 'E' && $e === '') { $skipped++; continue; }

        $stmt->execute([
            $examId,
            $text,
            $a,
            $b,
            $c !== '' ? $c : null,
            $d !== '' ? $d : null,
            $e !== '' ? $e : null,
            $answer,
            $marks,
            $order++,
        ]);
        $imported++;
    }
    $db->commit();

    if ($imported === 0) {
        jsonResponse(false, 'No complete questions with a marked correct answer were found', [
            'imported' => 0,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 8),
        ]);
    }

    logActivity('admin', $_SESSION['admin_id'], 'bulk_import_questions', "Imported $imported questions to exam $examId via Gemini");
    $msg = "Imported $imported questions";
    if ($skipped > 0) {
        $msg .= " ($skipped skipped)";
    }
    jsonResponse(true, $msg, [
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => array_slice($errors, 0, 8),
    ]);
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
=======
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
>>>>>>> c2f3db94eeb05dddf4e2a299051e40ec27455b93
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
