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
    jsonResponse(false, 'Invalid faculty ID');
}

try {
    $db = getDB();
    academic_ensure_schema($db);

    $stmt = $db->prepare('SELECT faculty_name FROM faculties WHERE id = ?');
    $stmt->execute([$id]);
    $faculty = $stmt->fetch();
    if (!$faculty) {
        jsonResponse(false, 'Faculty not found');
    }

    $countStmt = $db->prepare('SELECT COUNT(*) FROM departments WHERE faculty_id = ?');
    $countStmt->execute([$id]);
    if ((int) $countStmt->fetchColumn() > 0) {
        jsonResponse(false, 'Move or delete departments in this faculty first');
    }

    $db->prepare('DELETE FROM faculties WHERE id = ?')->execute([$id]);
    logActivity('admin', $_SESSION['admin_id'], 'delete_faculty', 'Deleted faculty: ' . $faculty['faculty_name']);
    jsonResponse(true, 'Faculty deleted successfully');
} catch (PDOException $e) {
    error_log('delete_faculty: ' . $e->getMessage());
    jsonResponse(false, 'Database error. Please try again.');
}
