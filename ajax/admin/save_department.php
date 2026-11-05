<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isAdminLoggedIn() || $_SESSION['admin_role'] !== 'super_admin') {
    jsonResponse(false, 'Access denied');
}

$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST[CSRF_TOKEN_NAME] ?? '';
if (!validateCSRFToken($csrf_token)) {
    jsonResponse(false, 'Invalid security token');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$id = intval($_POST['id'] ?? 0);
$name = trim(sanitize($_POST['dept_name'] ?? ''));
$code = strtoupper(trim(sanitize($_POST['dept_code'] ?? '')));
$facultyId = optionalId($_POST['faculty_id'] ?? 0);
$status = !empty($_POST['status']) ? 1 : 0;

if ($name === '') {
    jsonResponse(false, 'Department name is required');
}
if (!$facultyId) {
    jsonResponse(false, 'Select a faculty');
}

try {
    $db = getDB();
    academic_ensure_schema($db);

    $facultyStmt = $db->prepare('SELECT id, faculty_name FROM faculties WHERE id = ?');
    $facultyStmt->execute([$facultyId]);
    $faculty = $facultyStmt->fetch();
    if (!$faculty) {
        jsonResponse(false, 'Faculty not found');
    }

    $check = $db->prepare('SELECT id FROM departments WHERE dept_name = ? AND id != ?');
    $check->execute([$name, $id]);
    if ($check->fetch()) {
        jsonResponse(false, 'A department with this name already exists');
    }

    if ($code === '') {
        $code = academic_make_code($name);
    }
    $code = academic_unique_code($db, 'departments', 'dept_code', $code, $id > 0 ? $id : null);

    if ($id > 0) {
        $stmt = $db->prepare('SELECT id FROM departments WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            jsonResponse(false, 'Department not found');
        }
        $db->prepare('UPDATE departments SET dept_code = ?, dept_name = ?, faculty_id = ?, faculty = ?, status = ? WHERE id = ?')
            ->execute([$code, $name, $facultyId, $faculty['faculty_name'], $status, $id]);
        logActivity('admin', $_SESSION['admin_id'], 'update_department', 'Updated department: ' . $name);
        jsonResponse(true, 'Department updated successfully');
    }

    $db->prepare('INSERT INTO departments (dept_code, dept_name, faculty_id, faculty, status) VALUES (?, ?, ?, ?, ?)')
        ->execute([$code, $name, $facultyId, $faculty['faculty_name'], $status]);
    logActivity('admin', $_SESSION['admin_id'], 'add_department', 'Added department: ' . $name);
    jsonResponse(true, 'Department added successfully');
} catch (PDOException $e) {
    error_log('save_department: ' . $e->getMessage());
    jsonResponse(false, 'Database error. Please try again.');
}
