<?php
/**
 * Get students not yet assigned to a composite exam
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

header('Content-Type: application/json');

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$department = intval($_GET['department'] ?? 0);
$level = intval($_GET['level'] ?? 0);
$semester = intval($_GET['semester'] ?? 0);
$session = intval($_GET['session'] ?? 0);
$compositeExamId = intval($_GET['composite_exam_id'] ?? 0);

try {
    $db = getDB();
    
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
    if ($compositeExamId > 0) {
        // Exclude students already assigned to this composite exam
        $sql .= " AND s.id NOT IN (SELECT student_id FROM composite_exam_students WHERE composite_exam_id = ?)";
        $params[] = $compositeExamId;
    }
    
    $sql .= " ORDER BY s.last_name, s.first_name LIMIT 200";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'count' => count($students)
    ]);
    
} catch (PDOException $e) {
    error_log("Get composite students error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>