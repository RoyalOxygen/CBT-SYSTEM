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
$name = trim(sanitize($_POST['faculty_name'] ?? ''));
$code = strtoupper(trim(sanitize($_POST['faculty_code'] ?? '')));
$status = !empty($_POST['status']) ? 1 : 0;

if ($name === '') {
    jsonResponse(false, 'Faculty name is required');
}

try {
    $db = getDB();
    academic_ensure_schema($db);

    $check = $db->prepare('SELECT id FROM faculties WHERE faculty_name = ? AND id != ?');
    $check->execute([$name, $id]);
    if ($check->fetch()) {
        jsonResponse(false, 'A faculty with this name already exists');
    }

    if ($code === '') {
        $code = academic_make_code($name);
    }
    $code = academic_unique_code($db, 'faculties', 'faculty_code', $code, $id > 0 ? $id : null);

    if ($id > 0) {
        $stmt = $db->prepare('SELECT id FROM faculties WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            jsonResponse(false, 'Faculty not found');
        }
        $db->prepare('UPDATE faculties SET faculty_code = ?, faculty_name = ?, status = ? WHERE id = ?')
            ->execute([$code, $name, $status, $id]);
        $db->prepare('UPDATE departments SET faculty = ? WHERE faculty_id = ?')->execute([$name, $id]);
        logActivity('admin', $_SESSION['admin_id'], 'update_faculty', 'Updated faculty: ' . $name);
        jsonResponse(true, 'Faculty updated successfully');
    }

    $db->prepare('INSERT INTO faculties (faculty_code, faculty_name, status) VALUES (?, ?, ?)')
        ->execute([$code, $name, $status]);
    logActivity('admin', $_SESSION['admin_id'], 'add_faculty', 'Added faculty: ' . $name);
    jsonResponse(true, 'Faculty added successfully');
} catch (PDOException $e) {
    error_log('save_faculty: ' . $e->getMessage());
    jsonResponse(false, 'Database error. Please try again.');
}
