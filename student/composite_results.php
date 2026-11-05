<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/student_auth.php';

$pageTitle = 'Composite Exam Submitted';
$db = getDB();
$studentId = $_SESSION['student_id'];

$submittedExamId = intval($_GET['submitted'] ?? 0);
$submittedExam = null;

if ($submittedExamId) {
    $stmt = $db->prepare("SELECT e.*, a.status as attempt_status, r.published
        FROM composite_exams e
        JOIN composite_exam_attempts a ON a.composite_exam_id = e.id AND a.student_id = ?
        LEFT JOIN composite_exam_results r ON r.attempt_id = a.id
        WHERE e.id = ?
        ORDER BY a.id DESC LIMIT 1");
    $stmt->execute([$studentId, $submittedExamId]);
    $submittedExam = $stmt->fetch();
}

require_once __DIR__ . '/../includes/student_header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg text-center" style="border-radius: 20px; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%); padding: 50px 30px; color: white;">
                    <div style="font-size: 5rem; margin-bottom: 20px;">
                        <i class="bi bi-check-circle-fill" style="color: #34a853;"></i>
                    </div>
                    <h2 class="mb-2">Composite Exam Submitted!</h2>
                    <p class="mb-0 opacity-75">Your answers have been saved successfully.</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($submittedExam): ?>
                    <div class="alert alert-info">
                        <strong>Exam:</strong> <?php echo e($submittedExam['exam_code'] . ' - ' . $submittedExam['exam_title']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning small">
                        <i class="bi bi-info-circle me-1"></i>
                        Your results will be published by the administrator after review.
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="dashboard.php" class="btn btn-primary px-4">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>