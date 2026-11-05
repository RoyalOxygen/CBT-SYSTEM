<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($exam_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam ID']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM composite_exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exam) {
        echo json_encode(['success' => false, 'message' => 'Composite exam not found']);
        exit;
    }
    
    // Get dropdown options
    $departments = $db->query("SELECT id, dept_name FROM departments WHERE status = 1 ORDER BY dept_name")->fetchAll(PDO::FETCH_ASSOC);
    $levels = $db->query("SELECT id, level_name FROM levels WHERE status = 1 ORDER BY level_order")->fetchAll(PDO::FETCH_ASSOC);
    $semesters = $db->query("SELECT id, semester_name FROM semesters WHERE status = 1 ORDER BY semester_order")->fetchAll(PDO::FETCH_ASSOC);
    $sessions = $db->query("SELECT id, session_name FROM sessions WHERE status = 1 ORDER BY session_name DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Build HTML form
    $html = '<div class="row g-3">';
    
    // Exam Code and Title
    $html .= '
        <div class="col-md-6">
            <label class="form-label">Exam Code *</label>
            <input type="text" name="exam_code" class="form-control" value="' . htmlspecialchars($exam['exam_code']) . '" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Exam Title *</label>
            <input type="text" name="exam_title" class="form-control" value="' . htmlspecialchars($exam['exam_title']) . '" required>
        </div>';
    
    // Department
    $html .= '<div class="col-md-6">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-select">
            <option value="">All</option>';
    foreach ($departments as $d) {
        $selected = ($exam['department_id'] == $d['id']) ? 'selected' : '';
        $html .= '<option value="' . $d['id'] . '" ' . $selected . '>' . htmlspecialchars($d['dept_name']) . '</option>';
    }
    $html .= '</select></div>';
    
    // Level
    $html .= '<div class="col-md-6">
        <label class="form-label">Level</label>
        <select name="level_id" class="form-select">
            <option value="">All</option>';
    foreach ($levels as $l) {
        $selected = ($exam['level_id'] == $l['id']) ? 'selected' : '';
        $html .= '<option value="' . $l['id'] . '" ' . $selected . '>' . htmlspecialchars($l['level_name']) . '</option>';
    }
    $html .= '</select></div>';
    
    // Semester
    $html .= '<div class="col-md-6">
        <label class="form-label">Semester</label>
        <select name="semester_id" class="form-select">
            <option value="">Select</option>';
    foreach ($semesters as $s) {
        $selected = ($exam['semester_id'] == $s['id']) ? 'selected' : '';
        $html .= '<option value="' . $s['id'] . '" ' . $selected . '>' . htmlspecialchars($s['semester_name']) . '</option>';
    }
    $html .= '</select></div>';
    
    // Session
    $html .= '<div class="col-md-6">
        <label class="form-label">Session</label>
        <select name="session_id" class="form-select">
            <option value="">Select</option>';
    foreach ($sessions as $s) {
        $selected = ($exam['session_id'] == $s['id']) ? 'selected' : '';
        $html .= '<option value="' . $s['id'] . '" ' . $selected . '>' . htmlspecialchars($s['session_name']) . '</option>';
    }
    $html .= '</select></div>';
    
    // Date, Time, Duration
    $html .= '
        <div class="col-md-4">
            <label class="form-label">Exam Date *</label>
            <input type="date" name="exam_date" class="form-control" value="' . htmlspecialchars($exam['exam_date']) . '" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Start Time *</label>
            <input type="time" name="start_time" class="form-control" value="' . htmlspecialchars($exam['start_time']) . '" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Duration (min) *</label>
            <input type="number" name="duration" class="form-control" value="' . $exam['duration'] . '" min="5" max="300" required>
        </div>';
    
    // Question Limit, Pass Mark
    $html .= '
        <div class="col-md-6">
            <label class="form-label">Number of Main Questions</label>
            <input type="number" name="question_limit" class="form-control" value="' . $exam['question_limit'] . '" min="1" max="100">
        </div>
        <div class="col-md-6">
            <label class="form-label">Pass Mark (%)</label>
            <input type="number" name="pass_mark" class="form-control" value="' . $exam['pass_mark'] . '" min="0" max="100">
        </div>';
    
    // Instructions
    $html .= '
        <div class="col-12">
            <label class="form-label">Instructions</label>
            <textarea name="instruction" class="form-control" rows="2">' . htmlspecialchars($exam['instruction']) . '</textarea>
        </div>';
    
    // Checkboxes
    $html .= '
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="shuffle_questions" value="1" ' . ($exam['shuffle_questions'] ? 'checked' : '') . '>
                <label class="form-check-label">Shuffle Main Questions</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="shuffle_sub_questions" value="1" ' . ($exam['shuffle_sub_questions'] ? 'checked' : '') . '>
                <label class="form-check-label">Shuffle Sub-Questions</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="show_result" value="1" ' . ($exam['show_result'] ? 'checked' : '') . '>
                <label class="form-check-label">Show Result to Students</label>
            </div>
        </div>';
    
    $html .= '</div>';
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (PDOException $e) {
    error_log("Get composite exam error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>