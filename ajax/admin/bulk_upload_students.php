<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
node_require('student_edit');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) jsonResponse(false, 'Invalid token');
if (!isAdminLoggedIn()) jsonResponse(false, 'Not authenticated');

try {
    $db = getDB();
    
    if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, 'Please upload a valid CSV file');
    }
    
    $departmentId = intval($_POST['department_id'] ?? 0);
    $levelId = intval($_POST['level_id'] ?? 0);
    $semesterId = intval($_POST['semester_id'] ?? 0);
    $sessionId = intval($_POST['session_id'] ?? 0);
    
    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
    if (!$handle) jsonResponse(false, 'Cannot read CSV file');
    
    // Skip header
    $header = fgetcsv($handle);
    
    $imported = 0;
    $failed = 0;
    $results = [];
    $row = 1;
    
    while (($data = fgetcsv($handle)) !== false) {
        $row++;
        $matric = blankToNull(sanitize(trim($data[0] ?? '')));
        $firstName = blankToNull(sanitize(trim($data[1] ?? '')));
        $lastName = blankToNull(sanitize(trim($data[2] ?? '')));
        $otherName = blankToNull(sanitize(trim($data[3] ?? '')));
        $email = blankToNull(sanitize(trim($data[4] ?? '')));
        $phone = blankToNull(sanitize(trim($data[5] ?? '')));
        $gender = blankToNull(sanitize(trim($data[6] ?? '')));
        $dob = blankToNull(sanitize(trim($data[7] ?? '')));
        if ($gender !== null && !in_array($gender, ['Male', 'Female'], true)) {
            $gender = null;
        }
        
        if ($matric === null) {
            $failed++;
            $results[] = ['row' => $row, 'matric' => '', 'status' => 'failed', 'message' => 'Matric number is required'];
            continue;
        }
        
        // Check if exists
        $check = $db->prepare("SELECT id FROM students WHERE matric_number = ?");
        $check->execute([$matric]);
        if ($check->fetch()) {
            $failed++;
            $results[] = ['row' => $row, 'matric' => $matric, 'status' => 'failed', 'message' => 'Matric already exists'];
            continue;
        }
        
        try {
            $password = hashPassword($matric);
            $stmt = $db->prepare("INSERT INTO students (matric_number, password, first_name, last_name, other_name, email, phone, gender, date_of_birth, department_id, level_id, semester_id, session_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $matric,
                $password,
                $firstName,
                $lastName,
                $otherName,
                $email,
                $phone,
                $gender,
                $dob,
                optionalId($departmentId),
                optionalId($levelId),
                optionalId($semesterId),
                optionalId($sessionId)
            ]);
            $imported++;
            $results[] = ['row' => $row, 'matric' => $matric, 'status' => 'success', 'message' => 'Imported'];
        } catch (PDOException $e) {
            $failed++;
            $results[] = ['row' => $row, 'matric' => $matric, 'status' => 'failed', 'message' => $e->getMessage()];
        }
    }
    
    fclose($handle);
    logActivity('admin', $_SESSION['admin_id'], 'bulk_upload', "Imported $imported students, $failed failed");
    
    jsonResponse(true, "Import complete: $imported imported, $failed failed", ['imported' => $imported, 'failed' => $failed, 'results' => $results]);
    
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
