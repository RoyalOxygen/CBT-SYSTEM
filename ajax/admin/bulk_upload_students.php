<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
node_require('student_edit');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) jsonResponse(false, 'Invalid token');
if (!isAdminLoggedIn()) jsonResponse(false, 'Not authenticated');

@set_time_limit(240);
@ini_set('max_execution_time', '240');

function bulk_student_normalize_row(array $row): array {
    $firstName = blankToNull(sanitize(trim((string) ($row['first_name'] ?? ''))));
    $lastName = blankToNull(sanitize(trim((string) ($row['last_name'] ?? ''))));
    $otherName = blankToNull(sanitize(trim((string) ($row['other_name'] ?? ''))));
    $fullName = trim((string) ($row['full_name'] ?? ''));
    if ($firstName === null && $lastName === null && $fullName !== '') {
        $split = splitStudentName($fullName);
        $firstName = blankToNull(sanitize($split['first_name'] ?? ''));
        $lastName = blankToNull(sanitize($split['last_name'] ?? ''));
        $otherName = blankToNull(sanitize($split['other_name'] ?? ''));
    }
    $matric = blankToNull(sanitize(trim((string) ($row['matric_number'] ?? ''))));
    if ($matric === null) {
        $matric = blankToNull(sanitize(trim((string) ($row['reg_number'] ?? ''))));
    }
    $gender = blankToNull(sanitize(trim((string) ($row['gender'] ?? ''))));
    if ($gender !== null && !in_array($gender, ['Male', 'Female'], true)) {
        $gender = null;
    }
    return [
        'matric_number' => $matric,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'other_name' => $otherName,
        'email' => blankToNull(sanitize(trim((string) ($row['email'] ?? '')))),
        'phone' => blankToNull(sanitize(trim((string) ($row['phone'] ?? '')))),
        'gender' => $gender,
        'date_of_birth' => blankToNull(sanitize(trim((string) ($row['date_of_birth'] ?? '')))),
    ];
}

function bulk_student_insert(PDO $db, array $row, $departmentId, $levelId, $semesterId, $sessionId): array {
    if ($row['matric_number'] === null) {
        return ['ok' => false, 'message' => 'Matric or registration number is required'];
    }
    $check = $db->prepare("SELECT id FROM students WHERE matric_number = ?");
    $check->execute([$row['matric_number']]);
    if ($check->fetch()) {
        return ['ok' => false, 'message' => 'Matric already exists'];
    }
    $stmt = $db->prepare("INSERT INTO students (matric_number, password, first_name, last_name, other_name, email, phone, gender, date_of_birth, department_id, level_id, semester_id, session_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([
        $row['matric_number'],
        hashPassword($row['matric_number']),
        $row['first_name'],
        $row['last_name'],
        $row['other_name'],
        $row['email'],
        $row['phone'],
        $row['gender'],
        $row['date_of_birth'],
        optionalId($departmentId),
        optionalId($levelId),
        optionalId($semesterId),
        optionalId($sessionId),
    ]);
    return ['ok' => true, 'message' => 'Imported'];
}

try {
    $db = getDB();
    $method = strtolower(trim((string) ($_POST['upload_method'] ?? 'csv')));
    if ($method !== 'ai') {
        $method = 'csv';
    }
    $departmentId = intval($_POST['department_id'] ?? 0);
    $levelId = intval($_POST['level_id'] ?? 0);
    $semesterId = intval($_POST['semester_id'] ?? 0);
    $sessionId = intval($_POST['session_id'] ?? 0);

    $imported = 0;
    $failed = 0;
    $results = [];
    $rows = [];

    if ($method === 'ai') {
        if (empty($_FILES['document']) || ($_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            jsonResponse(false, 'Please upload a PDF or Word document');
        }
        $file = $_FILES['document'];
        $maxSize = defined('MAX_DOCUMENT_UPLOAD_SIZE') ? MAX_DOCUMENT_UPLOAD_SIZE : 20971520;
        if ($file['size'] > $maxSize) {
            jsonResponse(false, 'File is too large. Maximum size is 20MB');
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'docx', 'doc'], true)) {
            jsonResponse(false, 'Unsupported file type. Upload PDF or DOCX');
        }
        $extracted = groq_extract_students($file['tmp_name'], basename((string) $file['name']));
        if (empty($extracted['ok'])) {
            jsonResponse(false, $extracted['error'] ?? 'Groq extraction failed');
        }
        foreach (($extracted['students'] ?? []) as $item) {
            if (is_array($item)) {
                $rows[] = bulk_student_normalize_row($item);
            }
        }
    } else {
        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(false, 'Please upload a valid CSV file');
        }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            jsonResponse(false, 'Cannot read CSV file');
        }
        fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = bulk_student_normalize_row([
                'matric_number' => $data[0] ?? '',
                'first_name' => $data[1] ?? '',
                'last_name' => $data[2] ?? '',
                'other_name' => $data[3] ?? '',
                'email' => $data[4] ?? '',
                'phone' => $data[5] ?? '',
                'gender' => $data[6] ?? '',
                'date_of_birth' => $data[7] ?? '',
            ]);
        }
        fclose($handle);
    }

    $rowNum = 1;
    foreach ($rows as $row) {
        $rowNum++;
        try {
            $saved = bulk_student_insert($db, $row, $departmentId, $levelId, $semesterId, $sessionId);
            if (!empty($saved['ok'])) {
                $imported++;
                $results[] = ['row' => $rowNum, 'matric' => $row['matric_number'] ?? '', 'status' => 'success', 'message' => $saved['message']];
            } else {
                $failed++;
                $results[] = ['row' => $rowNum, 'matric' => $row['matric_number'] ?? '', 'status' => 'failed', 'message' => $saved['message']];
            }
        } catch (PDOException $e) {
            $failed++;
            $results[] = ['row' => $rowNum, 'matric' => $row['matric_number'] ?? '', 'status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    if ($imported === 0 && $failed === 0) {
        jsonResponse(false, $method === 'ai' ? 'No student records were found in the document' : 'The CSV file has no student rows');
    }

    logActivity('admin', $_SESSION['admin_id'], 'bulk_upload', "Imported $imported students, $failed failed via $method");
    jsonResponse(true, "Import complete: $imported imported, $failed failed", ['imported' => $imported, 'failed' => $failed, 'results' => $results]);
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
