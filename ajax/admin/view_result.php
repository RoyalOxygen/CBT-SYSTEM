<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isAdminLoggedIn()) exit('Not authenticated');

$resultId = intval($_GET['result_id'] ?? 0);
$type = $_GET['type'] ?? 'regular';

try {
    $db = getDB();
    
    if ($type === 'composite') {
        $stmt = $db->prepare("
            SELECT r.*, ce.exam_code, ce.exam_title, ce.duration, ce.pass_mark,
                   s.matric_number, s.first_name, s.last_name, s.other_name, s.photo
            FROM composite_exam_results r
            JOIN composite_exams ce ON r.composite_exam_id = ce.id
            JOIN students s ON r.student_id = s.id
            WHERE r.id = ?
        ");
    } else {
        $stmt = $db->prepare("
            SELECT r.*, e.exam_code, e.exam_title, e.duration, e.pass_mark,
                   s.matric_number, s.first_name, s.last_name, s.other_name, s.photo
            FROM results r
            JOIN exams e ON r.exam_id = e.id
            JOIN students s ON r.student_id = s.id
            WHERE r.id = ?
        ");
    }
    
    $stmt->execute([$resultId]);
    $result = $stmt->fetch();
    
    if (!$result) exit('Result not found');
    
    $grade = getGrade(floatval($result['percentage']));
    $gradeColor = $grade['grade'] <= 'C' ? 'success' : ($grade['grade'] === 'D' ? 'warning' : 'danger');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Result Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; padding: 20px; }
        .result-card { background: white; border-radius: 16px; padding: 30px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .score-circle { width: 120px; height: 120px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; margin: 0 auto 20px; }
    </style>
</head>
<body>
    <div class="result-card text-center">
        <div class="mb-2">
            <?php if ($type === 'composite'): ?>
                <span class="badge" style="background: #9c27b0; padding: 6px 16px;">Composite Exam</span>
            <?php else: ?>
                <span class="badge bg-primary" style="padding: 6px 16px;">Regular Exam</span>
            <?php endif; ?>
        </div>
        
        <?php if ($result['photo']): ?>
            <img src="<?php echo APP_URL; ?>/assets/uploads/student_photos/<?php echo e($result['photo']); ?>" class="rounded-circle mb-3" style="width: 80px; height: 80px; object-fit: cover;">
        <?php endif; ?>
        
        <h5><?php echo e($result['last_name'] . ', ' . $result['first_name']); ?></h5>
        <p class="text-muted"><?php echo e($result['matric_number']); ?></p>
        <p class="text-muted small"><?php echo e($result['exam_code'] . ' - ' . $result['exam_title']); ?></p>
        
        <div class="score-circle bg-<?php echo $gradeColor; ?> bg-opacity-10 text-<?php echo $gradeColor; ?>">
            <?php echo number_format($result['percentage'], 0); ?>%
        </div>
        
        <h4 class="text-<?php echo $gradeColor; ?>">Grade: <?php echo $grade['grade']; ?></h4>
        <p class="text-muted"><?php echo $grade['remark']; ?></p>
        
        <hr>
        
        <div class="row text-center">
            <div class="col-4">
                <strong class="d-block"><?php echo $result['correct']; ?></strong>
                <small class="text-muted">Correct</small>
            </div>
            <div class="col-4">
                <strong class="d-block"><?php echo $result['wrong']; ?></strong>
                <small class="text-muted">Wrong</small>
            </div>
            <div class="col-4">
                <strong class="d-block"><?php echo ($type === 'composite' ? $result['total_sub_questions'] : $result['total_questions']) - $result['answered']; ?></strong>
                <small class="text-muted">Skipped</small>
            </div>
        </div>
        
        <hr>
        
        <div class="row text-center small">
            <div class="col-6"><strong>Score:</strong> <?php echo $result['score']; ?>/<?php echo $result['total_marks']; ?></div>
            <div class="col-6"><strong>Status:</strong> <?php echo $result['published'] ? 'Published' : 'Pending'; ?></div>
        </div>
        
        <button onclick="window.close()" class="btn btn-secondary mt-3">Close</button>
    </div>
</body>
</html>
<?php
} catch (PDOException $e) {
    exit('Error: ' . $e->getMessage());
}
?>