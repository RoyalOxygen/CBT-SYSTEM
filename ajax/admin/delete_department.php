<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isAdminLoggedIn() || $_SESSION['admin_role'] !== 'super_admin') {
    jsonResponse(false, 'Access denied');
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    jsonResponse(false, 'Invalid security token');
}

$id = intval($input['id'] ?? 0);
if ($id <= 0) {
    jsonResponse(false, 'Invalid department ID');
}

try {
    $db = getDB();
    academic_ensure_schema($db);

    $stmt = $db->prepare('SELECT dept_name FROM departments WHERE id = ?');
    $stmt->execute([$id]);
    $dept = $stmt->fetch();
    if (!$dept) {
        jsonResponse(false, 'Department not found');
    }

    $studentCount = $db->prepare('SELECT COUNT(*) FROM students WHERE department_id = ?');
    $studentCount->execute([$id]);
    if ((int) $studentCount->fetchColumn() > 0) {
        jsonResponse(false, 'Move or reassign students in this department first');
    }

    $examCount = $db->prepare('SELECT COUNT(*) FROM exams WHERE department_id = ?');
    $examCount->execute([$id]);
    $compositeCount = $db->prepare('SELECT COUNT(*) FROM composite_exams WHERE department_id = ?');
    $compositeCount->execute([$id]);
    if ((int) $examCount->fetchColumn() > 0 || (int) $compositeCount->fetchColumn() > 0) {
        jsonResponse(false, 'Reassign exams in this department first');
    }

    $db->prepare('DELETE FROM departments WHERE id = ?')->execute([$id]);
    logActivity('admin', $_SESSION['admin_id'], 'delete_department', 'Deleted department: ' . $dept['dept_name']);
    jsonResponse(true, 'Department deleted successfully');
} catch (PDOException $e) {
    error_log('delete_department: ' . $e->getMessage());
    jsonResponse(false, 'Database error. Please try again.');
}
