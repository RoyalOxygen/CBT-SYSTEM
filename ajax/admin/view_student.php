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
    
    // Get student info
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
    
    // Get assigned exams (exams assigned but not yet taken)
    $assignedStmt = $db->prepare("
        SELECT es.id as assignment_id, es.status as assignment_status, es.assigned_at,
               e.id as exam_id, e.exam_code, e.exam_title, e.duration, e.exam_date, e.start_time,
               eb.id as batch_id, eb.batch_name, eb.batch_date, eb.start_time as batch_start_time, eb.end_time as batch_end_time
        FROM exam_students es
        JOIN exams e ON es.exam_id = e.id
        LEFT JOIN exam_batches eb ON es.batch_id = eb.id
        WHERE es.student_id = ? 
        AND es.status IN ('assigned', 'started')
        AND NOT EXISTS (SELECT 1 FROM exam_attempts ea WHERE ea.exam_id = e.id AND ea.student_id = ?)
        ORDER BY es.assigned_at DESC
    ");
    $assignedStmt->execute([$student_id, $student_id]);
    $assignedExams = $assignedStmt->fetchAll();
    
    // Get written exams (attempts with results or submitted)
    $writtenStmt = $db->prepare("
        SELECT ea.*, e.exam_code, e.exam_title, e.duration,
               r.score as result_score, r.percentage as result_percentage, r.grade as result_grade, 
               r.remark as result_remark, r.published,
               CASE 
                   WHEN ea.status = 'submitted' THEN 'Submitted'
                   WHEN ea.status = 'auto_submitted' THEN 'Auto-Submitted'
                   WHEN ea.status = 'timed_out' THEN 'Timed Out'
                   ELSE ea.status
               END as attempt_status_label
        FROM exam_attempts ea
        JOIN exams e ON ea.exam_id = e.id
        LEFT JOIN results r ON r.attempt_id = ea.id
        WHERE ea.student_id = ?
        ORDER BY ea.end_time DESC
    ");
    $writtenStmt->execute([$student_id]);
    $writtenExams = $writtenStmt->fetchAll();
    
    // Get pending exams (submitted but not published)
    $pendingStmt = $db->prepare("
        SELECT ea.*, e.exam_code, e.exam_title, e.duration,
               r.id as result_id, r.published,
               CASE 
                   WHEN ea.status = 'submitted' THEN 'Submitted'
                   WHEN ea.status = 'auto_submitted' THEN 'Auto-Submitted'
                   WHEN ea.status = 'timed_out' THEN 'Timed Out'
                   ELSE ea.status
               END as attempt_status_label
        FROM exam_attempts ea
        JOIN exams e ON ea.exam_id = e.id
        JOIN results r ON r.attempt_id = ea.id
        WHERE ea.student_id = ? AND r.published = 0
        ORDER BY ea.end_time DESC
    ");
    $pendingStmt->execute([$student_id]);
    $pendingExams = $pendingStmt->fetchAll();
    
    // Get total stats
    $totalWritten = count($writtenExams);
    $totalPending = count($pendingExams);
    $totalAssigned = count($assignedExams);
    
    // Build HTML
    $html = '
    <div class="row">
        <!-- Student Info -->
        <div class="col-md-4 text-center mb-4">
            ' . ($student['photo'] ? '<img src="' . APP_URL . '/assets/uploads/student_photos/' . e($student['photo']) . '" class="rounded-circle mb-3" style="width:120px;height:120px;object-fit:cover;border:4px solid #e8c84c;">' : '
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width:120px;height:120px;border:4px solid #e8c84c;">
                <i class="bi bi-person-fill fs-1"></i>
            </div>') . '
            <h5>' . e($student['first_name'] . ' ' . $student['last_name']) . '</h5>
            <p class="text-muted">' . e($student['matric_number']) . '</p>
            <p class="small">' . e($student['dept_name'] ?? 'N/A') . ' | ' . e($student['level_name'] ?? 'N/A') . '</p>
            <hr>
            <div class="row">
                <div class="col-4">
                    <span class="badge bg-primary d-block p-2">' . $totalAssigned . '</span>
                    <small>Assigned</small>
                </div>
                <div class="col-4">
                    <span class="badge bg-success d-block p-2">' . $totalWritten . '</span>
                    <small>Written</small>
                </div>
                <div class="col-4">
                    <span class="badge bg-warning text-dark d-block p-2">' . $totalPending . '</span>
                    <small>Pending</small>
                </div>
            </div>
        </div>
        
        <!-- Exam Details -->
        <div class="col-md-8">
            <ul class="nav nav-tabs student-view-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#assignedExams" type="button">
                        <i class="bi bi-journal-text me-1"></i> Assigned <span class="badge bg-primary">' . $totalAssigned . '</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#writtenExams" type="button">
                        <i class="bi bi-check-circle me-1"></i> Written <span class="badge bg-success">' . $totalWritten . '</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pendingExams" type="button">
                        <i class="bi bi-hourglass-split me-1"></i> Pending <span class="badge bg-warning text-dark">' . $totalPending . '</span>
                    </button>
                </li>
            </ul>
            <div class="tab-content mt-3" style="max-height:400px;overflow-y:auto;">
                <!-- Assigned Exams -->
                <div class="tab-pane fade show active" id="assignedExams">
                    ' . (empty($assignedExams) ? '<p class="text-muted text-center py-3">No assigned exams</p>' : '') . '
                    ' . implode('', array_map(function($exam) {
                        $statusColor = $exam['assignment_status'] === 'assigned' ? 'assigned' : 'started';
                        $statusLabel = $exam['assignment_status'] === 'assigned' ? 'Assigned' : 'Started';
                        $dateInfo = '';
                        if ($exam['batch_id']) {
                            $dateInfo = '<i class="bi bi-calendar me-1"></i>' . date('M d, Y', strtotime($exam['batch_date'])) . ' at ' . date('h:i A', strtotime($exam['batch_start_time']));
                        } else {
                            $dateInfo = '<i class="bi bi-calendar me-1"></i>' . date('M d, Y', strtotime($exam['exam_date'])) . ' at ' . date('h:i A', strtotime($exam['start_time']));
                        }
                        return '<div class="exam-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="exam-code">' . e($exam['exam_code']) . '</span>
                                    <div class="exam-title">' . e($exam['exam_title']) . '</div>
                                    <div class="small text-muted">' . $dateInfo . ' | ' . $exam['duration'] . ' min</div>
                                </div>
                                <div>
                                    <span class="exam-status ' . $statusColor . '">' . $statusLabel . '</span>
                                </div>
                            </div>
                        </div>';
                    }, $assignedExams)) . '
                </div>
                
                <!-- Written Exams -->
                <div class="tab-pane fade" id="writtenExams">
                    ' . (empty($writtenExams) ? '<p class="text-muted text-center py-3">No written exams</p>' : '') . '
                    ' . implode('', array_map(function($exam) {
                        $score = isset($exam['result_score']) ? floatval($exam['result_score']) : null;
                        $percentage = isset($exam['result_percentage']) ? floatval($exam['result_percentage']) : null;
                        $grade = $exam['result_grade'] ?? 'N/A';
                        $hasResult = $score !== null;
                        $pass = $percentage !== null && $percentage >= 40;
                        $statusLabel = $exam['attempt_status_label'] ?? $exam['status'];
                        $statusColor = $exam['status'] === 'submitted' ? 'submitted' : ($exam['status'] === 'auto_submitted' ? 'auto_submitted' : 'timed_out');
                        
                        return '<div class="exam-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="exam-code">' . e($exam['exam_code']) . '</span>
                                    <div class="exam-title">' . e($exam['exam_title']) . '</div>
                                    <div class="small text-muted">
                                        <i class="bi bi-clock me-1"></i>Completed: ' . date('M d, Y h:i A', strtotime($exam['end_time'])) . ' | ' . $exam['duration'] . ' min
                                    </div>
                                </div>
                                <div class="text-end">
                                    ' . ($hasResult ? '
                                    <div>
                                        <span class="exam-score ' . ($pass ? 'pass' : 'fail') . '">' . number_format($score, 1) . '/' . number_format($exam['total_marks'] ?? 0, 1) . '</span>
                                        <br>
                                        <span class="badge bg-' . ($pass ? 'success' : 'danger') . '">' . $grade . '</span>
                                        <br>
                                        <small>' . number_format($percentage, 1) . '%</small>
                                    </div>
                                    ' : '
                                    <div>
                                        <span class="exam-status ' . $statusColor . '">' . $statusLabel . '</span>
                                    </div>
                                    ') . '
                                </div>
                            </div>
                        </div>';
                    }, $writtenExams)) . '
                </div>
                
                <!-- Pending Exams -->
                <div class="tab-pane fade" id="pendingExams">
                    ' . (empty($pendingExams) ? '<p class="text-muted text-center py-3">No pending exams</p>' : '') . '
                    ' . implode('', array_map(function($exam) {
                        $statusColor = $exam['status'] === 'submitted' ? 'submitted' : ($exam['status'] === 'auto_submitted' ? 'auto_submitted' : 'timed_out');
                        $statusLabel = $exam['attempt_status_label'] ?? $exam['status'];
                        
                        return '<div class="exam-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="exam-code">' . e($exam['exam_code']) . '</span>
                                    <div class="exam-title">' . e($exam['exam_title']) . '</div>
                                    <div class="small text-muted">
                                        <i class="bi bi-clock me-1"></i>Submitted: ' . date('M d, Y h:i A', strtotime($exam['end_time'])) . '
                                    </div>
                                </div>
                                <div>
                                    <span class="exam-status ' . $statusColor . '">' . $statusLabel . '</span>
                                    <br>
                                    <small class="text-muted">Awaiting publication</small>
                                </div>
                            </div>
                        </div>';
                    }, $pendingExams)) . '
                </div>
            </div>
        </div>
    </div>';
    
    jsonResponse(true, '', ['html' => $html]);
    
} catch (PDOException $e) {
    error_log("View student error: " . $e->getMessage());
    jsonResponse(false, 'Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("View student error: " . $e->getMessage());
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
?>