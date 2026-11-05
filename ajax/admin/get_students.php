<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Ensure user is admin
if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get parameters
$department = isset($_GET['department']) ? intval($_GET['department']) : 0;
$level = isset($_GET['level']) ? intval($_GET['level']) : 0;
$semester = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
$session = isset($_GET['session']) ? intval($_GET['session']) : 0;
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

try {
    $db = getDB();
    
    // Build query to get students not already assigned to this exam
    $sql = "SELECT s.id, s.matric_number, s.first_name, s.last_name, s.email, 
                   d.dept_name, l.level_name
            FROM students s
            LEFT JOIN departments d ON s.department_id = d.id
            LEFT JOIN levels l ON s.level_id = l.id
            WHERE s.status = 1";
    $params = [];
    
    if ($department > 0) {
        $sql .= " AND s.department_id = ?";
        $params[] = $department;
    }
    if ($level > 0) {
        $sql .= " AND s.level_id = ?";
        $params[] = $level;
    }
    if ($semester > 0) {
        $sql .= " AND s.semester_id = ?";
        $params[] = $semester;
    }
    if ($session > 0) {
        $sql .= " AND s.session_id = ?";
        $params[] = $session;
    }
    if ($exam_id > 0) {
        $sql .= " AND s.id NOT IN (SELECT student_id FROM exam_students WHERE exam_id = ?)";
        $params[] = $exam_id;
    }
    
    $sql .= " ORDER BY s.last_name, s.first_name LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Students loaded successfully', 
        'students' => $students,
        'count' => count($students)
    ]);
    
} catch (PDOException $e) {
    error_log("Get students error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>