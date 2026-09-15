<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Dashboard';

try {
    $db = getDB();
    
    // Count statistics using proper queries
    $stats = [
        'total_students' => $db->query("SELECT COUNT(*) FROM students WHERE status = 1")->fetchColumn(),
        'total_exams' => $db->query("SELECT COUNT(*) FROM exams")->fetchColumn(),
        'active_exams' => $db->query("SELECT COUNT(*) FROM exams WHERE status IN ('published', 'running')")->fetchColumn(),
        'total_questions' => $db->query("SELECT COUNT(*) FROM questions WHERE status = 1")->fetchColumn(),
        'today_exams' => $db->query("SELECT COUNT(*) FROM exams WHERE exam_date = CURDATE() AND status IN ('published', 'running')")->fetchColumn(),
        'active_attempts' => $db->query("SELECT COUNT(*) FROM exam_attempts WHERE status = 'in_progress'")->fetchColumn(),
        'biometric_enrolled' => $db->query("SELECT COUNT(*) FROM students WHERE fingerprint_enrolled = 1")->fetchColumn(),
        'published_results' => $db->query("SELECT COUNT(*) FROM results WHERE published = 1")->fetchColumn(),
    ];
    
    // Recent exams
    $recentExams = $db->query("SELECT e.*, d.dept_name, l.level_name FROM exams e LEFT JOIN departments d ON e.department_id = d.id LEFT JOIN levels l ON e.level_id = l.id ORDER BY e.created_at DESC LIMIT 5")->fetchAll();
    
    // Recent activity
    $recentActivity = $db->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10")->fetchAll();
    
    // Exams happening today
    $todayExamsList = $db->query("SELECT e.*, d.dept_name, (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id AND status = 'in_progress') as active_students FROM exams e LEFT JOIN departments d ON e.department_id = d.id WHERE e.exam_date = CURDATE() AND e.status IN ('published', 'running') ORDER BY e.start_time")->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $stats = array_fill_keys(['total_students', 'total_exams', 'active_exams', 'total_questions', 'today_exams', 'active_attempts', 'biometric_enrolled', 'published_results'], 0);
    $recentExams = [];
    $recentActivity = [];
    $todayExamsList = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div class="ms-3">
                    <h6 class="text-muted mb-0">Total Students</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['total_students']); ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                    <i class="bi bi-journal-bookmark-fill fs-4"></i>
                </div>
                <div class="ms-3">
                    <h6 class="text-muted mb-0">Active Exams</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['active_exams']); ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3">
                    <i class="bi bi-question-circle-fill fs-4"></i>
                </div>
                <div class="ms-3">
                    <h6 class="text-muted mb-0">Questions</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['total_questions']); ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="bg-info bg-opacity-10 text-info rounded-circle p-3">
                    <i class="bi bi-fingerprint fs-4"></i>
                </div>
                <div class="ms-3">
                    <h6 class="text-muted mb-0">Biometric Enrolled</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['biometric_enrolled']); ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Today's Exams -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Today's Exams</h5>
                <a href="exams.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($todayExamsList)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-calendar-x fs-1"></i>
                        <p class="mt-2">No exams scheduled for today</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr><th>Exam</th><th>Time</th><th>Department</th><th>Status</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todayExamsList as $exam): ?>
                                <tr>
                                    <td><?php echo e($exam['exam_code']); ?></td>
                                    <td><?php echo date('h:i A', strtotime($exam['start_time'])); ?></td>
                                    <td><?php echo e($exam['dept_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo getExamStatusBadge($exam['status']); ?></td>
                                    <td><a href="monitoring.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-info"><i class="bi bi-tv"></i> Monitor</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Quick Overview</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <span><i class="bi bi-calendar-event me-2 text-primary"></i>Exams Today</span>
                    <span class="badge bg-primary rounded-pill"><?php echo $stats['today_exams']; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <span><i class="bi bi-person-workspace me-2 text-success"></i>Active Students</span>
                    <span class="badge bg-success rounded-pill"><?php echo $stats['active_attempts']; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <span><i class="bi bi-file-earmark-text me-2 text-warning"></i>Total Exams</span>
                    <span class="badge bg-warning rounded-pill"><?php echo $stats['total_exams']; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-check-circle me-2 text-info"></i>Published Results</span>
                    <span class="badge bg-info rounded-pill"><?php echo $stats['published_results']; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <!-- Recent Exams -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Exams</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($recentExams as $exam): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo e($exam['exam_title']); ?></h6>
                        <small class="text-muted"><?php echo e($exam['dept_name'] ?? 'N/A'); ?> | <?php echo formatDate($exam['exam_date']); ?></small>
                    </div>
                    <?php echo getExamStatusBadge($exam['status']); ?>
                </div>
                <?php endforeach; ?>
                <?php if (empty($recentExams)): ?>
                    <div class="list-group-item text-center text-muted py-4">No exams found</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Recent Activity</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($recentActivity as $activity): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-<?php echo $activity['user_type'] === 'admin' ? 'primary' : 'success'; ?>">
                            <?php echo ucfirst($activity['user_type']); ?>
                        </span>
                        <small class="text-muted"><?php echo formatDateTime($activity['created_at']); ?></small>
                    </div>
                    <p class="mb-0 mt-1"><?php echo e($activity['action']); ?></p>
                </div>
                <?php endforeach; ?>
                <?php if (empty($recentActivity)): ?>
                    <div class="list-group-item text-center text-muted py-4">No recent activity</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>