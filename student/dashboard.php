<?php
/**
 * CBT System - Student Dashboard
 * Redesigned with Taraba State University theme
 * Unified exam list (regular + composite) with matching colors
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/student_auth.php';

$pageTitle = 'Dashboard';
$db = getDB();

// Check for password change
if (!empty($_SESSION['force_password_change'])) {
    unset($_SESSION['force_password_change']);
    redirect(APP_URL . '/student/dashboard.php?action=change_password', 'Please change your password for security', 'warning');
}

// Get student info
$stmt = $db->prepare("SELECT s.*, d.dept_name, l.level_name, sem.semester_name, ses.session_name FROM students s LEFT JOIN departments d ON s.department_id = d.id LEFT JOIN levels l ON s.level_id = l.id LEFT JOIN semesters sem ON s.semester_id = sem.id LEFT JOIN sessions ses ON s.session_id = ses.id WHERE s.id = ?");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch();

// =====================================================
// REGULAR EXAMS
// =====================================================
$examsStmt = $db->prepare("
    SELECT 
        'regular' AS exam_type,
        e.id as exam_id,
        e.exam_code,
        e.exam_title,
        e.duration,
        e.fingerprint_required,
        e.shuffle_questions,
        e.shuffle_options,
        e.exam_date,
        e.start_time,
        eb.id as batch_id,
        eb.batch_code,
        eb.batch_name,
        eb.batch_date,
        eb.start_time as batch_start_time,
        eb.end_time as batch_end_time,
        eb.duration as batch_duration,
        eb.biometric_required,
        es.status as assignment_status,
        es.assigned_at,
        ea.id as attempt_id,
        ea.status as attempt_status,
        ea.submission_method,
        ea.end_time,
        ea.score,
        ea.percentage,
        NULL as total_questions,
        NULL as total_sub_questions_count
    FROM exam_students es
    JOIN exams e ON e.id = es.exam_id
    LEFT JOIN exam_batches eb ON eb.id = es.batch_id
    LEFT JOIN exam_attempts ea ON ea.exam_id = e.id AND ea.student_id = es.student_id
    WHERE es.student_id = ? 
        AND es.status IN ('assigned', 'started')
        AND e.status IN ('published', 'running')
    ORDER BY es.assigned_at DESC
");
$examsStmt->execute([$_SESSION['student_id']]);
$availableExams = $examsStmt->fetchAll();

$resultsStmt = $db->prepare("
    SELECT r.*, e.exam_code, e.exam_title, ea.submission_method
    FROM results r 
    JOIN exams e ON r.exam_id = e.id 
    LEFT JOIN exam_attempts ea ON ea.id = r.attempt_id
    WHERE r.student_id = ? AND r.published = 1 
    ORDER BY r.published_at DESC 
    LIMIT 10
");
$resultsStmt->execute([$_SESSION['student_id']]);
$pastResults = $resultsStmt->fetchAll();

$submittedStmt = $db->prepare("
    SELECT ea.*, e.exam_code, e.exam_title, 'regular' AS exam_type
    FROM exam_attempts ea
    JOIN exams e ON e.id = ea.exam_id
    WHERE ea.student_id = ? 
        AND ea.status IN ('submitted', 'auto_submitted', 'timed_out')
        AND NOT EXISTS (SELECT 1 FROM results WHERE attempt_id = ea.id AND published = 1)
    ORDER BY ea.end_time DESC
");
$submittedStmt->execute([$_SESSION['student_id']]);
$submittedExams = $submittedStmt->fetchAll();

// =====================================================
// COMPOSITE EXAMS
// =====================================================
$compositeStmt = $db->prepare("
    SELECT 
        'composite' AS exam_type,
        ce.id as exam_id,
        ce.exam_code,
        ce.exam_title,
        ce.duration,
        NULL as fingerprint_required,
        ce.shuffle_questions,
        NULL as shuffle_options,
        ce.exam_date,
        ce.start_time,
        NULL as batch_id,
        NULL as batch_code,
        NULL as batch_name,
        NULL as batch_date,
        NULL as batch_start_time,
        NULL as batch_end_time,
        NULL as batch_duration,
        NULL as biometric_required,
        ces.status as assignment_status,
        ces.assigned_at,
        cea.id as attempt_id,
        cea.status as attempt_status,
        cea.submission_method,
        cea.end_time,
        cea.score,
        cea.percentage,
        (SELECT COUNT(*) FROM composite_questions WHERE composite_exam_id = ce.id AND status = 1) as total_questions,
        (SELECT COUNT(*) FROM composite_sub_questions csq 
            JOIN composite_questions cq ON csq.composite_question_id = cq.id 
            WHERE cq.composite_exam_id = ce.id) as total_sub_questions_count
    FROM composite_exam_students ces
    JOIN composite_exams ce ON ce.id = ces.composite_exam_id
    LEFT JOIN composite_exam_attempts cea ON cea.composite_exam_id = ce.id AND cea.student_id = ces.student_id
    WHERE ces.student_id = ? 
        AND ces.status IN ('assigned', 'started')
        AND ce.status IN ('published', 'running')
    ORDER BY ces.assigned_at DESC
");
$compositeStmt->execute([$_SESSION['student_id']]);
$availableCompositeExams = $compositeStmt->fetchAll();

$compositeResultsStmt = $db->prepare("
    SELECT r.*, ce.exam_code, ce.exam_title, cea.submission_method
    FROM composite_exam_results r 
    JOIN composite_exams ce ON r.composite_exam_id = ce.id 
    LEFT JOIN composite_exam_attempts cea ON cea.id = r.attempt_id
    WHERE r.student_id = ? AND r.published = 1 
    ORDER BY r.published_at DESC 
    LIMIT 10
");
$compositeResultsStmt->execute([$_SESSION['student_id']]);
$pastCompositeResults = $compositeResultsStmt->fetchAll();

$submittedCompositeStmt = $db->prepare("
    SELECT cea.*, ce.exam_code, ce.exam_title, 'composite' AS exam_type
    FROM composite_exam_attempts cea
    JOIN composite_exams ce ON ce.id = cea.composite_exam_id
    WHERE cea.student_id = ? 
        AND cea.status IN ('submitted', 'auto_submitted', 'timed_out')
        AND NOT EXISTS (SELECT 1 FROM composite_exam_results WHERE attempt_id = cea.id AND published = 1)
    ORDER BY cea.end_time DESC
");
$submittedCompositeStmt->execute([$_SESSION['student_id']]);
$submittedCompositeExams = $submittedCompositeStmt->fetchAll();

// =====================================================
// MERGE ALL EXAMS INTO ONE LIST
// =====================================================
$allExams = array_merge($availableExams, $availableCompositeExams);
usort($allExams, function($a, $b) {
    return strtotime($b['assigned_at'] ?? '1970-01-01') - strtotime($a['assigned_at'] ?? '1970-01-01');
});

$allPending = array_merge($submittedExams, $submittedCompositeExams);
usort($allPending, function($a, $b) {
    return strtotime($b['end_time'] ?? '1970-01-01') - strtotime($a['end_time'] ?? '1970-01-01');
});

$totalAvailableExams = count($allExams);
$totalPublishedResults = count($pastResults) + count($pastCompositeResults);
$totalPendingResults = count($allPending);
$totalExamsTaken = $totalPublishedResults + $totalPendingResults;

require_once __DIR__ . '/../includes/student_header.php';
?>

<style>
/* ===== Taraba State University Theme ===== */
:root {
    --tsu-navy: #1a1a2e;
    --tsu-dark: #16213e;
    --tsu-blue: #0f3460;
    --tsu-gold: #e8c84c;
    --tsu-gold-light: #f5d742;
    --tsu-primary: #1a73e8;
    --tsu-success: #34a853;
    --tsu-danger: #ea4335;
    --tsu-warning: #fbbc04;
}

/* Stats Cards */
.stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.04);
    height: 100%;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}
.stat-card .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}
.stat-card .stat-icon.gold { background: #fef9e7; color: var(--tsu-gold); }
.stat-card .stat-icon.blue { background: #e8f0fe; color: var(--tsu-primary); }
.stat-card .stat-icon.green { background: #e6f4ea; color: var(--tsu-success); }
.stat-card .stat-icon.red { background: #fce8e6; color: var(--tsu-danger); }

.stat-card .stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--tsu-navy);
    line-height: 1.2;
}
.stat-card .stat-label {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 500;
}

/* Section Headers */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
}
.section-header h5 {
    margin: 0;
    font-weight: 700;
    color: var(--tsu-navy);
    font-size: 1.05rem;
}
.section-header .badge-count {
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* ============================================
   UNIFIED EXAM LIST ITEM
   All exams use the same gold/navy theme
   ============================================ */
.exam-list-item {
    background: white;
    border-radius: 14px;
    border: 1px solid #e9ecef;
    border-left: 4px solid #dee2e6;
    padding: 16px 20px;
    margin-bottom: 12px;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}
.exam-list-item:hover {
    transform: translateX(4px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    border-color: #dee2e6;
}

/* Status-based left border colors (same for both exam types) */
.exam-list-item.available { border-left-color: var(--tsu-gold); }
.exam-list-item.upcoming { border-left-color: var(--tsu-primary); }
.exam-list-item.bio-pending { border-left-color: #f9ab00; }
.exam-list-item.submitted { border-left-color: #adb5bd; }
.exam-list-item.expired { border-left-color: #adb5bd; }
.exam-list-item.pending { border-left-color: var(--tsu-warning); }

/* Type Icon - unified gold theme */
.exam-type-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--tsu-gold) 0%, #f5d742 100%);
    color: var(--tsu-navy);
}
.exam-type-icon.pending {
    background: linear-gradient(135deg, #fbbc04 0%, #f9ab00 100%);
    color: white;
}

/* Content */
.exam-content {
    flex: 1;
    min-width: 240px;
}
.exam-content-header {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 6px;
}

/* Code badge - unified navy for both types */
.exam-code-badge {
    background: var(--tsu-navy);
    color: white;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Status badge */
.exam-status-badge {
    font-size: 0.7rem;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
}
.exam-status-badge.available { background: #e6f4ea; color: var(--tsu-success); }
.exam-status-badge.upcoming { background: #e8f0fe; color: var(--tsu-primary); }
.exam-status-badge.expired { background: #f1f3f4; color: #5f6368; }
.exam-status-badge.bio-pending { background: #fef9e7; color: #f9ab00; }
.exam-status-badge.submitted { background: #e6f4ea; color: var(--tsu-success); }
.exam-status-badge.pending { background: #fef9e7; color: #f9ab00; }

/* Type badge - unified gold theme for both types */
.exam-type-badge {
    font-size: 0.65rem;
    padding: 3px 10px;
    border-radius: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #fef9e7;
    color: #b8860b;
}

/* Title */
.exam-title {
    font-weight: 600;
    color: var(--tsu-navy);
    font-size: 1rem;
    margin-bottom: 4px;
    line-height: 1.35;
}

/* Meta */
.exam-meta {
    font-size: 0.78rem;
    color: #6c757d;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}
.exam-meta .meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}
.exam-meta .meta-item i {
    font-size: 0.85rem;
    opacity: 0.75;
}

.exam-status-message {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Action Button */
.exam-action {
    flex-shrink: 0;
}
.btn-start-exam {
    background: var(--tsu-primary);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}
.btn-start-exam:hover {
    background: #1557b0;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(26,115,232,0.3);
}
.btn-start-exam:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
    background: #adb5bd;
    color: white;
}

/* ============================================
   SIDEBAR CARDS
   ============================================ */
.sidebar-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 20px;
}
.sidebar-card .card-header-custom {
    padding: 16px 20px;
    border-bottom: 1px solid #f0f0f0;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--tsu-navy);
    background: white;
}
.sidebar-card .card-body-custom {
    padding: 16px 20px;
}

/* Student Profile Card */
.profile-card {
    text-align: center;
    padding: 24px 20px;
}
.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--tsu-gold);
    margin-bottom: 12px;
}
.profile-avatar-placeholder {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--tsu-navy) 0%, var(--tsu-blue) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    border: 4px solid var(--tsu-gold);
}
.profile-avatar-placeholder i {
    font-size: 40px;
    color: white;
}
.profile-name {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--tsu-navy);
}
.profile-matric {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 500;
}
.profile-level {
    font-size: 0.85rem;
    color: var(--tsu-primary);
    font-weight: 600;
}
.profile-dept {
    font-size: 0.8rem;
    color: #5f6368;
}
.profile-divider {
    border: none;
    border-top: 1px solid #f0f0f0;
    margin: 14px 0;
}
.profile-stats {
    display: flex;
    justify-content: space-around;
}
.profile-stat {
    text-align: center;
}
.profile-stat .value {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--tsu-navy);
}
.profile-stat .label {
    font-size: 0.65rem;
    color: #6c757d;
}

/* Result Items */
.result-item {
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}
.result-item:last-child {
    border-bottom: none;
}
.result-item .result-code {
    font-size: 0.7rem;
    color: #6c757d;
    font-weight: 600;
}
.result-item .result-title {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--tsu-navy);
}
.result-item .result-grade {
    font-weight: 700;
    font-size: 0.9rem;
}
.result-item .result-grade.A { color: var(--tsu-success); }
.result-item .result-grade.B { color: var(--tsu-success); }
.result-item .result-grade.C { color: var(--tsu-primary); }
.result-item .result-grade.D { color: var(--tsu-warning); }
.result-item .result-grade.E { color: #f9ab00; }
.result-item .result-grade.F { color: var(--tsu-danger); }

.result-progress {
    height: 3px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 6px;
}
.result-progress .fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 50px 20px;
    background: white;
    border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.04);
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}
.empty-state i {
    font-size: 3rem;
    color: #dadce0;
}
.empty-state h6 {
    margin-top: 16px;
    color: var(--tsu-navy);
}
.empty-state p {
    color: #6c757d;
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
    .stat-card .stat-value { font-size: 1.4rem; }
    .exam-list-item {
        flex-direction: column;
        align-items: stretch;
        padding: 14px 16px;
        gap: 12px;
    }
    .exam-list-item:hover { transform: none; }
    .exam-type-icon { width: 44px; height: 44px; font-size: 1.25rem; }
    .exam-action { width: 100%; }
    .btn-start-exam { width: 100%; justify-content: center; }
    .exam-content { min-width: 0; }
}
</style>

<div class="container">
    <!-- Alerts -->
    <?php if (empty($student['fingerprint_enrolled'])): ?>
    <div class="alert alert-warning d-flex align-items-center mb-4" style="border-left: 4px solid #f9ab00;">
        <i class="bi bi-exclamation-triangle-fill me-3 fs-4" style="color: #f9ab00;"></i>
        <div>
            <strong>Biometric enrollment required!</strong><br>
            You need to enroll your fingerprint before you can take exams. 
            <a href="biometric_enroll.php" class="alert-link fw-bold">Enroll now</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== Stats Row ===== -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card fade-in">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon gold"><i class="bi bi-journal-check"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $totalAvailableExams; ?></div>
                        <div class="stat-label">Available Exams</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card fade-in" style="animation-delay: 0.1s;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $totalPublishedResults; ?></div>
                        <div class="stat-label">Published Results</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card fade-in" style="animation-delay: 0.2s;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon blue"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $totalPendingResults; ?></div>
                        <div class="stat-label">Pending Results</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card fade-in" style="animation-delay: 0.3s;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon red"><i class="bi bi-fingerprint"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $student['fingerprint_enrolled'] ? '✓' : '✗'; ?></div>
                        <div class="stat-label">Biometric Status</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Main Content ===== -->
    <div class="row g-4">
        <!-- Left Column - Exams -->
        <div class="col-lg-8">

            <!-- ===================================================== -->
            <!-- ALL EXAMS (unified list) -->
            <!-- ===================================================== -->
            <?php if (!empty($allExams)): ?>
                <div class="section-header">
                    <h5><i class="bi bi-journal-check me-2" style="color: var(--tsu-gold);"></i>My Exams</h5>
                    <span class="badge-count" style="background: var(--tsu-gold); color: var(--tsu-navy);">
                        <?php echo count($allExams); ?> Available
                    </span>
                </div>

                <?php foreach ($allExams as $exam): 
                    $isComposite = ($exam['exam_type'] === 'composite');
                    $statusClass = '';
                    $statusLabel = '';
                    $canStart = false;
                    $statusMessage = '';
                    
                    $hasAttempt = !empty($exam['attempt_id']);
                    $attemptStatus = $exam['attempt_status'] ?? null;
                    $submissionMethod = $exam['submission_method'] ?? null;
                    $isBatchExam = (!$isComposite && !empty($exam['batch_id']));
                    
                    if ($hasAttempt && $attemptStatus && $attemptStatus !== 'in_progress') {
                        if ($attemptStatus === 'submitted') {
                            $statusClass = 'submitted';
                            $statusLabel = '<span class="exam-status-badge submitted"><i class="bi bi-check-circle me-1"></i>Submitted</span>';
                        } elseif ($attemptStatus === 'auto_submitted') {
                            $statusClass = 'submitted';
                            $statusLabel = '<span class="exam-status-badge submitted"><i class="bi bi-clock-history me-1"></i>Auto-Submitted</span>';
                        } elseif ($attemptStatus === 'timed_out') {
                            $statusClass = 'submitted';
                            $statusLabel = '<span class="exam-status-badge expired"><i class="bi bi-hourglass-end me-1"></i>Timed Out</span>';
                        }
                        $canStart = false;
                        $statusMessage = 'You have already taken this exam.';
                    } else {
                        if ($isBatchExam) {
                            $batchDate = $exam['batch_date'];
                            $startTime = $exam['batch_start_time'];
                            $endTime = $exam['batch_end_time'];
                            $biometricRequired = $exam['biometric_required'];
                            
                            if (!empty($batchDate) && !empty($startTime) && !empty($endTime)) {
                                try {
                                    $now = new DateTime();
                                    $startDateTime = new DateTime($batchDate . ' ' . $startTime);
                                    $endDateTime = new DateTime($batchDate . ' ' . $endTime);
                                    
                                    $isActive = ($now >= $startDateTime && $now <= $endDateTime);
                                    $isUpcoming = ($now < $startDateTime);
                                    
                                    if ($isActive && ($student['fingerprint_enrolled'] || !$biometricRequired)) {
                                        $statusClass = 'available';
                                        $statusLabel = '<span class="exam-status-badge available"><i class="bi bi-play-circle me-1"></i>Available Now</span>';
                                        $canStart = true;
                                    } elseif ($isActive && $biometricRequired && !$student['fingerprint_enrolled']) {
                                        $statusClass = 'bio-pending';
                                        $statusLabel = '<span class="exam-status-badge bio-pending"><i class="bi bi-fingerprint me-1"></i>Biometric Required</span>';
                                        $statusMessage = 'Please complete biometric verification first';
                                    } elseif ($isUpcoming) {
                                        $statusClass = 'upcoming';
                                        $statusLabel = '<span class="exam-status-badge upcoming"><i class="bi bi-calendar me-1"></i>Upcoming</span>';
                                        $statusMessage = 'Starts on ' . date('M d, Y', strtotime($batchDate)) . ' at ' . date('h:i A', strtotime($startTime));
                                    } else {
                                        $statusClass = 'expired';
                                        $statusLabel = '<span class="exam-status-badge expired"><i class="bi bi-clock me-1"></i>Expired</span>';
                                    }
                                } catch (Exception $e) {
                                    $statusClass = 'expired';
                                    $statusLabel = '<span class="exam-status-badge expired">Invalid Date</span>';
                                }
                            } else {
                                $statusClass = 'expired';
                                $statusLabel = '<span class="exam-status-badge expired">Not Scheduled</span>';
                            }
                        } else {
                            $examDate = $exam['exam_date'];
                            $startTime = $exam['start_time'];
                            $biometricRequired = $isComposite ? 0 : $exam['fingerprint_required'];
                            
                            if (!empty($examDate)) {
                                try {
                                    $now = new DateTime();
                                    $examDateTime = new DateTime($examDate . ' ' . ($startTime ?? '00:00:00'));
                                    
                                    $isAvailable = ($now >= $examDateTime);
                                    
                                    if ($isAvailable && ($student['fingerprint_enrolled'] || !$biometricRequired)) {
                                        $statusClass = 'available';
                                        $statusLabel = '<span class="exam-status-badge available"><i class="bi bi-play-circle me-1"></i>Available</span>';
                                        $canStart = true;
                                    } elseif ($isAvailable && $biometricRequired && !$student['fingerprint_enrolled']) {
                                        $statusClass = 'bio-pending';
                                        $statusLabel = '<span class="exam-status-badge bio-pending"><i class="bi bi-fingerprint me-1"></i>Biometric Required</span>';
                                        $statusMessage = 'Please complete biometric verification first';
                                    } elseif (!$isAvailable) {
                                        $statusClass = 'upcoming';
                                        $statusLabel = '<span class="exam-status-badge upcoming"><i class="bi bi-calendar me-1"></i>Upcoming</span>';
                                        $statusMessage = 'Available on ' . date('M d, Y', strtotime($examDate)) . ' at ' . date('h:i A', strtotime($startTime ?? '00:00'));
                                    } else {
                                        $statusClass = 'expired';
                                        $statusLabel = '<span class="exam-status-badge expired">Not Available</span>';
                                    }
                                } catch (Exception $e) {
                                    $statusClass = 'expired';
                                    $statusLabel = '<span class="exam-status-badge expired">Date Error</span>';
                                }
                            } else {
                                $statusClass = 'expired';
                                $statusLabel = '<span class="exam-status-badge expired">Not Scheduled</span>';
                            }
                        }
                    }
                ?>
                <div class="exam-list-item <?php echo $statusClass; ?> fade-in">
                    <!-- Type Icon (unified gold) -->
                    <div class="exam-type-icon">
                        <?php if ($isComposite): ?>
                            <i class="bi bi-diagram-3"></i>
                        <?php else: ?>
                            <i class="bi bi-journal-bookmark-fill"></i>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Content -->
                    <div class="exam-content">
                        <div class="exam-content-header">
                            <span class="exam-code-badge"><?php echo e($exam['exam_code']); ?></span>
                            <span class="exam-type-badge">
                                <?php if ($isComposite): ?>
                                    <i class="bi bi-diagram-3 me-1"></i>Composite
                                <?php else: ?>
                                    <i class="bi bi-journal-bookmark-fill me-1"></i>Regular
                                <?php endif; ?>
                            </span>
                            <?php echo $statusLabel; ?>
                        </div>
                        
                        <div class="exam-title"><?php echo e($exam['exam_title']); ?></div>
                        
                        <div class="exam-meta mt-1">
                            <span class="meta-item">
                                <i class="bi bi-clock"></i> <?php echo $exam['duration']; ?> min
                            </span>
                            
                            <?php if ($isBatchExam && !empty($exam['batch_name'])): ?>
                                <span class="meta-item">
                                    <i class="bi bi-collection"></i> <?php echo e($exam['batch_name']); ?>
                                </span>
                                <span class="meta-item">
                                    <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($exam['batch_date'])); ?>
                                </span>
                                <span class="meta-item">
                                    <i class="bi bi-clock"></i> <?php echo date('h:i A', strtotime($exam['batch_start_time'])); ?> - <?php echo date('h:i A', strtotime($exam['batch_end_time'])); ?>
                                </span>
                            <?php elseif (!empty($exam['exam_date'])): ?>
                                <span class="meta-item">
                                    <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($exam['exam_date'])); ?>
                                </span>
                                <?php if (!empty($exam['start_time'])): ?>
                                    <span class="meta-item">
                                        <i class="bi bi-clock"></i> <?php echo date('h:i A', strtotime($exam['start_time'])); ?>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($isComposite): ?>
                                <span class="meta-item">
                                    <i class="bi bi-diagram-3"></i> <?php echo $exam['total_questions']; ?> main / <?php echo $exam['total_sub_questions_count']; ?> sub-Q
                                </span>
                            <?php elseif (($isBatchExam && $exam['biometric_required']) || (!$isBatchExam && $exam['fingerprint_required'])): ?>
                                <span class="meta-item">
                                    <i class="bi bi-fingerprint"></i> Biometric required
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($statusMessage): ?>
                            <div class="exam-status-message">
                                <i class="bi bi-info-circle"></i> <?php echo e($statusMessage); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Action -->
                    <div class="exam-action">
                        <?php if ($canStart): ?>
                            <?php if ($isComposite): ?>
                                <a href="take_composite_exam.php?exam_id=<?php echo $exam['exam_id']; ?>" 
                                   class="btn-start-exam">
                                    <i class="bi bi-play-fill"></i> Start Exam
                                </a>
                            <?php else: ?>
                                <a href="take_exam.php?exam_id=<?php echo $exam['exam_id']; ?><?php echo $exam['batch_id'] ? '&batch_id=' . $exam['batch_id'] : ''; ?>" 
                                   class="btn-start-exam">
                                    <i class="bi bi-play-fill"></i> Start Exam
                                </a>
                            <?php endif; ?>
                        <?php elseif (!$hasAttempt && $statusMessage): ?>
                            <button class="btn-start-exam" disabled>
                                <i class="bi bi-clock"></i> Not Available
                            </button>
                        <?php else: ?>
                            <button class="btn-start-exam" disabled style="background: transparent; color: #6c757d; border: 1px solid #dee2e6;">
                                <i class="bi bi-lock"></i> Locked
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- ===================================================== -->
            <!-- PENDING RESULTS (unified list) -->
            <!-- ===================================================== -->
            <?php if (!empty($allPending)): ?>
            <div class="section-header" style="margin-top: 24px;">
                <h5>
                    <i class="bi bi-hourglass-split me-2" style="color: var(--tsu-warning);"></i>Pending Results
                </h5>
                <span class="badge-count" style="background: var(--tsu-warning); color: var(--tsu-navy);">
                    <?php echo count($allPending); ?> Waiting
                </span>
            </div>

            <?php foreach ($allPending as $submitted): 
                $isComposite = ($submitted['exam_type'] === 'composite');
                
                $statusIcon = '';
                $statusText = '';
                
                if ($submitted['status'] === 'submitted') {
                    $statusIcon = 'bi-check-circle';
                    $statusText = 'Submitted';
                } elseif ($submitted['status'] === 'auto_submitted') {
                    $statusIcon = 'bi-clock-history';
                    $statusText = 'Auto-Submitted';
                } else {
                    $statusIcon = 'bi-hourglass-end';
                    $statusText = 'Timed Out';
                }
            ?>
            <div class="exam-list-item pending fade-in">
                <!-- Icon -->
                <div class="exam-type-icon pending">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                
                <!-- Content -->
                <div class="exam-content">
                    <div class="exam-content-header">
                        <span class="exam-code-badge"><?php echo e($submitted['exam_code']); ?></span>
                        <span class="exam-type-badge">
                            <?php if ($isComposite): ?>
                                <i class="bi bi-diagram-3 me-1"></i>Composite
                            <?php else: ?>
                                <i class="bi bi-journal-bookmark-fill me-1"></i>Regular
                            <?php endif; ?>
                        </span>
                        <span class="exam-status-badge pending">
                            <i class="bi <?php echo $statusIcon; ?> me-1"></i><?php echo $statusText; ?>
                        </span>
                    </div>
                    
                    <div class="exam-title"><?php echo e($submitted['exam_title']); ?></div>
                    
                    <div class="exam-meta mt-1">
                        <span class="meta-item">
                            <i class="bi bi-calendar-check"></i> Completed: <?php echo formatDateTime($submitted['end_time']); ?>
                        </span>
                        <?php if (!empty($submitted['submission_method'])): ?>
                            <span class="meta-item">
                                <i class="bi bi-info-circle"></i> 
                                <?php echo $submitted['submission_method'] === 'manual' ? 'Manual Submission' : 'Auto Submission'; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="exam-status-message">
                        <i class="bi bi-info-circle"></i> Awaiting result publication by administrator
                    </div>
                </div>
                
                <!-- Action -->
                <div class="exam-action">
                    <span class="badge" style="background: #fef9e7; color: #f9ab00; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 0.75rem;">
                        <i class="bi bi-hourglass-split me-1"></i> Pending
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- Empty State -->
            <?php if (empty($allExams) && empty($allPending)): ?>
                <div class="empty-state fade-in">
                    <i class="bi bi-inbox"></i>
                    <h6>No Exams Available</h6>
                    <p>You don't have any exams assigned to you yet.<br>Check back later or contact your administrator.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ===== Right Sidebar ===== -->
        <div class="col-lg-4">
            <!-- Student Profile Card -->
            <div class="sidebar-card fade-in" style="animation-delay: 0.2s;">
                <div class="profile-card">
                    <?php if ($student['photo']): ?>
                        <img src="<?php echo APP_URL; ?>/assets/uploads/student_photos/<?php echo e($student['photo']); ?>" class="profile-avatar">
                    <?php else: ?>
                        <div class="profile-avatar-placeholder">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    <?php endif; ?>
                    <div class="profile-name"><?php echo strtoupper(e($student['last_name'] . ', ' . $student['first_name'])); ?></div>
                    <div class="profile-matric"><?php echo e($student['matric_number']); ?></div>
                    <div class="profile-level">LEVEL: <?php echo strtoupper(e($student['level_name'] ?? 'N/A')); ?></div>
                    <div class="profile-dept"><?php echo e($student['dept_name'] ?? 'N/A'); ?></div>
                    
                    <hr class="profile-divider">
                    
                    <div class="profile-stats">
                        <div class="profile-stat">
                            <div class="value"><?php echo $totalExamsTaken; ?></div>
                            <div class="label">Exams Taken</div>
                        </div>
                        <div class="profile-stat">
                            <div class="value">
                                <?php if ($student['fingerprint_enrolled']): ?>
                                    <span style="color: var(--tsu-success);">✓</span>
                                <?php else: ?>
                                    <span style="color: var(--tsu-danger);">✗</span>
                                <?php endif; ?>
                            </div>
                            <div class="label">Biometric</div>
                        </div>
                        <div class="profile-stat">
                            <div class="value"><?php echo $totalAvailableExams; ?></div>
                            <div class="label">Available</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Published Results -->
            <div class="sidebar-card fade-in" style="animation-delay: 0.3s;">
                <div class="card-header-custom">
                    <i class="bi bi-clipboard-check me-2" style="color: var(--tsu-gold);"></i>Published Results
                </div>
                <div class="card-body-custom">
                    <?php if (empty($pastResults) && empty($pastCompositeResults)): ?>
                        <div class="text-center py-3 text-muted">
                            <i class="bi bi-inbox fs-4 d-block mb-2" style="color: #dadce0;"></i>
                            <p class="mb-0 small">No published results yet</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $allResults = array_merge(
                            array_map(fn($r) => array_merge($r, ['type' => 'regular']), $pastResults),
                            array_map(fn($r) => array_merge($r, ['type' => 'composite']), $pastCompositeResults)
                        );
                        usort($allResults, fn($a, $b) => strtotime($b['published_at']) - strtotime($a['published_at']));
                        ?>
                        <?php foreach (array_slice($allResults, 0, 10) as $result): 
                            $grade = getGrade(floatval($result['percentage']));
                            $gradeClass = $grade['grade'];
                        ?>
                        <div class="result-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="result-code">
                                        <?php if ($result['type'] === 'composite'): ?>
                                            <i class="bi bi-diagram-3 me-1"></i>
                                        <?php endif; ?>
                                        <?php echo e($result['exam_code']); ?>
                                    </div>
                                    <div class="result-title"><?php echo mb_substr($result['exam_title'], 0, 30); ?></div>
                                </div>
                                <div class="result-grade <?php echo $gradeClass; ?>"><?php echo $grade['grade']; ?></div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span><?php echo number_format($result['percentage'], 1); ?>%</span>
                                <span><?php echo formatDate($result['published_at']); ?></span>
                            </div>
                            <div class="result-progress">
                                <div class="fill" style="width: <?php echo $result['percentage']; ?>%; background: <?php 
                                    echo $grade['grade'] <= 'C' ? 'var(--tsu-success)' : ($grade['grade'] === 'D' ? 'var(--tsu-warning)' : 'var(--tsu-danger)');
                                ?>;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($allResults) >= 10): ?>
                            <div class="text-center mt-3">
                                <a href="results.php" class="text-decoration-none small fw-bold" style="color: var(--tsu-primary);">
                                    View all results <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Exam Legend Card -->
            <div class="sidebar-card fade-in" style="animation-delay: 0.4s;">
                <div class="card-header-custom">
                    <i class="bi bi-info-circle me-2" style="color: var(--tsu-gold);"></i>Exam Types
                </div>
                <div class="card-body-custom">
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <div class="exam-type-icon" style="width: 32px; height: 32px; font-size: 0.9rem;">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">Regular Exams</div>
                            <div class="text-muted" style="font-size: 0.7rem;">Single-question multiple choice exams</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <div class="exam-type-icon" style="width: 32px; height: 32px; font-size: 0.9rem;">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">Composite Exams</div>
                            <div class="text-muted" style="font-size: 0.7rem;">Main questions with nested sub-questions</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header" style="border-bottom: 3px solid var(--tsu-gold);">
                <h5 class="modal-title"><i class="bi bi-person me-2" style="color: var(--tsu-gold);"></i>My Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <?php if ($student['photo']): ?>
                        <img src="<?php echo APP_URL; ?>/assets/uploads/student_photos/<?php echo e($student['photo']); ?>" class="rounded-circle" width="80" height="80" style="object-fit:cover; border: 3px solid var(--tsu-gold);">
                    <?php else: ?>
                        <div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:80px;height:80px;border: 3px solid var(--tsu-gold);">
                            <i class="bi bi-person fs-1"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <table class="table table-sm">
                    <tr><th width="40%">Matric Number</th><td><?php echo e($student['matric_number']); ?></td></tr>
                    <tr><th>Full Name</th><td><?php echo e($student['last_name'] . ', ' . $student['first_name'] . ' ' . ($student['other_name'] ?? '')); ?></td></tr>
                    <tr><th>Email</th><td><?php echo e($student['email'] ?? 'N/A'); ?></td></tr>
                    <tr><th>Phone</th><td><?php echo e($student['phone'] ?? 'N/A'); ?></td></tr>
                    <tr><th>Gender</th><td><?php echo e($student['gender'] ?? 'N/A'); ?></td></tr>
                    <tr><th>Department</th><td><?php echo e($student['dept_name'] ?? 'N/A'); ?></tr>
                    <tr><th>Level</th><td><?php echo e($student['level_name'] ?? 'N/A'); ?></td></tr>
                    <tr><th>Session</th><td><?php echo e($student['session_name'] ?? 'N/A'); ?></td></tr>
                    <tr><th>Biometric Status</th>
                        <td><?php echo $student['fingerprint_enrolled'] ? '<span class="badge bg-success">Enrolled</span>' : '<span class="badge bg-warning text-dark">Not Enrolled</span>'; ?></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Password Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header" style="border-bottom: 3px solid var(--tsu-gold);">
                <h5 class="modal-title"><i class="bi bi-key me-2" style="color: var(--tsu-gold);"></i>Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changePasswordForm">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required style="border-radius: 10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6" style="border-radius: 10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required style="border-radius: 10px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 25px;">Cancel</button>
                    <button type="submit" class="btn" style="background: var(--tsu-gold); color: var(--tsu-navy); border-radius: 25px; font-weight: 600;">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    const newPass = formData.get('new_password');
    const confirmPass = formData.get('confirm_password');
    
    if (newPass !== confirmPass) {
        if (typeof showToast !== 'undefined') {
            showToast('New passwords do not match', 'error');
        } else {
            alert('New passwords do not match');
        }
        return;
    }
    
    if (newPass.length < 6) {
        if (typeof showToast !== 'undefined') {
            showToast('Password must be at least 6 characters', 'error');
        } else {
            alert('Password must be at least 6 characters');
        }
        return;
    }
    
    try {
        const response = await fetch(APP_URL + '/ajax/student/change_password.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        if (typeof showToast !== 'undefined') {
            showToast(data.message, data.success ? 'success' : 'error');
        } else {
            alert(data.message);
        }
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('passwordModal')).hide();
            document.getElementById('changePasswordForm').reset();
        }
    } catch (error) {
        if (typeof showToast !== 'undefined') {
            showToast('Failed to change password', 'error');
        } else {
            alert('Failed to change password');
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>