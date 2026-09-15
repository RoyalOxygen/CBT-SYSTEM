<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isAdminLoggedIn()) {
    jsonResponse(false, 'Unauthorized');
}

$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_GET['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    jsonResponse(false, 'Invalid token');
}

$student_id = intval($_GET['id'] ?? 0);
if ($student_id <= 0) {
    jsonResponse(false, 'Invalid student ID');
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.*, d.dept_name, l.level_name, sem.semester_name, ses.session_name
        FROM students s
        LEFT JOIN departments d ON s.department_id = d.id
        LEFT JOIN levels l ON s.level_id = l.id
        LEFT JOIN semesters sem ON s.semester_id = sem.id
        LEFT JOIN sessions ses ON s.session_id = ses.id
        WHERE s.id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        jsonResponse(false, 'Student not found');
    }
    
    // Build HTML for edit form
    $html = '
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Matric Number *</label>
            <input type="text" name="matric_number" class="form-control" value="' . e($student['matric_number']) . '" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="' . e($student['email']) . '">
        </div>
        <div class="col-md-4">
            <label class="form-label">First Name *</label>
            <input type="text" name="first_name" class="form-control" value="' . e($student['first_name']) . '" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Last Name *</label>
            <input type="text" name="last_name" class="form-control" value="' . e($student['last_name']) . '" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Other Name</label>
            <input type="text" name="other_name" class="form-control" value="' . e($student['other_name']) . '">
        </div>
        <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="tel" name="phone" class="form-control" value="' . e($student['phone']) . '">
        </div>
        <div class="col-md-6">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-select">
                <option value="">Select</option>
                <option value="Male" ' . ($student['gender'] === 'Male' ? 'selected' : '') . '>Male</option>
                <option value="Female" ' . ($student['gender'] === 'Female' ? 'selected' : '') . '>Female</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-control" value="' . e($student['date_of_birth']) . '">
        </div>
        <div class="col-md-6">
            <label class="form-label">New Photo (optional)</label>
            <input type="file" name="photo" class="form-control" accept="image/*">
            ' . ($student['photo'] ? '<small class="text-muted">Current: <img src="' . APP_URL . '/assets/uploads/student_photos/' . e($student['photo']) . '" style="height:30px;"></small>' : '') . '
        </div>
    </div>';
    
    jsonResponse(true, '', ['html' => $html, 'student' => $student]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error');
}
?>