<?php
/**
 * CBT System - Student Results
 * Exam Submission Acknowledgment Page
 * Only shows confirmation message without displaying results
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/student_auth.php';

$pageTitle = 'Exam Submitted';
$db = getDB();

$studentId = $_SESSION['student_id'];

// Get student info for sidebar
$stmt = $db->prepare("SELECT s.*, d.dept_name, l.level_name FROM students s LEFT JOIN departments d ON s.department_id = d.id LEFT JOIN levels l ON s.level_id = l.id WHERE s.id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

// Check for just submitted exam
$justSubmittedExamId = isset($_GET['submitted']) ? intval($_GET['submitted']) : 0; 
$submittedExam = null;

if ($justSubmittedExamId) {
    $examStmt = $db->prepare("SELECT e.*, ea.id as attempt_id, ea.score, ea.percentage, ea.total_questions, ea.answered_count, ea.correct_count, ea.status
                              FROM exams e 
                              JOIN exam_attempts ea ON ea.exam_id = e.id 
                              WHERE e.id = ? AND ea.student_id = ? AND ea.status IN ('submitted', 'auto_submitted')
                              ORDER BY ea.id DESC LIMIT 1");
    $examStmt->execute([$justSubmittedExamId, $studentId]);
    $submittedExam = $examStmt->fetch();
}

require_once __DIR__ . '/../includes/student_header.php';
?>

<style>
/* Success Page Styles */
.success-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border-radius: 20px;
    padding: 60px 40px;
    margin-bottom: 30px;
    color: white;
    border-bottom: 4px solid #e8c84c;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.success-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 500px;
    height: 500px;
    border-radius: 50%;
    background: rgba(232, 200, 76, 0.05);
}
.success-hero .checkmark-wrapper {
    display: inline-block;
    margin: 0 auto 20px;
}
.success-hero .checkmark-circle {
    width: 100px;
    height: 100px;
    position: relative;
    display: inline-block;
}
.success-hero .checkmark {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: block;
    stroke-width: 2;
    stroke: #34a853;
    stroke-miterlimit: 10;
    animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
}
.success-hero .checkmark__circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 2;
    stroke-miterlimit: 10;
    stroke: #34a853;
    fill: none;
    animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}
.success-hero .checkmark__check {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
}
@keyframes stroke { 100% { stroke-dashoffset: 0; } }
@keyframes scale { 0%, 100% { transform: none; } 50% { transform: scale3d(1.1, 1.1, 1); } }
@keyframes fill { 100% { box-shadow: inset 0px 0px 0px 30px #34a853; } }

.success-hero h2 {
    font-weight: 700;
    font-size: 2rem;
    margin-bottom: 10px;
}
.success-hero .subtext {
    font-size: 1.1rem;
    opacity: 0.8;
    max-width: 600px;
    margin: 0 auto 20px;
}
.success-hero .exam-info {
    background: rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 20px 30px;
    display: inline-block;
    border: 1px solid rgba(255,255,255,0.1);
}
.success-hero .exam-info .label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.7;
}
.success-hero .exam-info .value {
    font-weight: 600;
    font-size: 1.1rem;
}

.success-animation {
    animation: fadeInUp 0.6s ease-out;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Info Cards */
.info-card {
    background: white;
    border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.04);
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    overflow: hidden;
    transition: all 0.3s ease;
}
.info-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}
.info-card .card-header-custom {
    padding: 16px 24px;
    border-bottom: 1px solid #f0f0f0;
    font-weight: 600;
    color: #1a1a2e;
    background: white;
}
.info-card .card-body-custom {
    padding: 20px 24px;
}
.info-card .icon-box {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 12px;
}
.info-card .icon-box.blue { background: #e8f0fe; color: #1a73e8; }
.info-card .icon-box.green { background: #e6f4ea; color: #34a853; }
.info-card .icon-box.gold { background: #fef9e7; color: #e8c84c; }

/* Sidebar matching dashboard */
.student-sidebar-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 20px;
}
.student-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 16px;
    border: 3px solid #e8c84c;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.student-avatar-placeholder {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    border: 3px solid #e8c84c;
}
.student-avatar-placeholder i {
    font-size: 40px;
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .success-hero {
        padding: 40px 20px;
    }
    .success-hero h2 {
        font-size: 1.5rem;
    }
    .success-hero .exam-info {
        padding: 15px 20px;
        width: 100%;
    }
}
</style>

<div class="container py-4">
    <!-- Success Hero -->
    <div class="success-hero success-animation">
        <div class="checkmark-wrapper">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
        </div>
        
        <h2>Exam Submitted Successfully! 🎉</h2>
        <p class="subtext">
            Your exam has been submitted successfully. You can now return to your dashboard.
        </p>
        
        <?php if ($submittedExam): ?>
        <div class="exam-info">
            <div class="row g-3">
                <div class="col-sm-4">
                    <div class="label">Exam Code</div>
                    <div class="value"><?php echo e($submittedExam['exam_code']); ?></div>
                </div>
                <div class="col-sm-4">
                    <div class="label">Status</div>
                    <div class="value">
                        <span class="badge bg-success"><?php echo ucfirst(str_replace('_', ' ', $submittedExam['status'] ?? 'Submitted')); ?></span>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="label">Submitted At</div>
                    <div class="value"><?php echo date('h:i A', time()); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Action Cards -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="info-card success-animation" style="animation-delay: 0.1s;">
                <div class="card-body-custom text-center">
                    <div class="icon-box blue"><i class="bi bi-house-fill"></i></div>
                    <h6 class="fw-bold">Go to Dashboard</h6>
                    <p class="text-muted small mb-3">Return to your student dashboard to see available exams.</p>
                    <a href="dashboard.php" class="btn btn-primary w-100">
                        <i class="bi bi-house me-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="info-card success-animation" style="animation-delay: 0.2s;">
                <div class="card-body-custom text-center">
                    <div class="icon-box green"><i class="bi bi-fingerprint"></i></div>
                    <h6 class="fw-bold">Biometric Enrollment</h6>
                    <p class="text-muted small mb-3">Enroll or verify your biometric identification.</p>
                    <a href="biometric_enroll.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-fingerprint me-1"></i> Biometric
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="info-card success-animation" style="animation-delay: 0.3s;">
                <div class="card-body-custom text-center">
                    <div class="icon-box gold"><i class="bi bi-box-arrow-right"></i></div>
                    <h6 class="fw-bold">Logout</h6>
                    <p class="text-muted small mb-3">Securely logout from the student portal.</p>
                    <a href="logout.php" class="btn btn-outline-danger w-100">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>