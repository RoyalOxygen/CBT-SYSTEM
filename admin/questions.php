<?php
/**
 * CBT System - Question Management
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Question Management';
$db = getDB();

// Get selected exam
$examId = intval($_GET['exam_id'] ?? 0);
$exam = null;
if ($examId) {
    $stmt = $db->prepare("SELECT e.*, d.dept_name, l.level_name FROM exams e LEFT JOIN departments d ON e.department_id = d.id LEFT JOIN levels l ON e.level_id = l.id WHERE e.id = ?");
    $stmt->execute([$examId]);
    $exam = $stmt->fetch();
}

// Get all exams for dropdown
$exams = $db->query("SELECT id, exam_code, exam_title FROM exams ORDER BY created_at DESC")->fetchAll();

// Get questions for selected exam
$questions = [];
if ($examId) {
    $stmt = $db->prepare("SELECT * FROM questions WHERE exam_id = ? AND status = 1 ORDER BY question_order, id");
    $stmt->execute([$examId]);
    $questions = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Questions</h4>
    <div>
        <a href="?exam_id=<?php echo $examId; ?>&import=1" class="btn btn-info me-2">
            <i class="bi bi-upload me-1"></i>Bulk Import
        </a>
        <?php if ($examId): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
            <i class="bi bi-plus-lg me-1"></i>Add Question
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Exam Selector -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Select Exam</label>
                <select name="exam_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Select Exam --</option>
                    <?php foreach ($exams as $e): ?>
                        <option value="<?php echo $e['id']; ?>" <?php echo $examId == $e['id'] ? 'selected' : ''; ?>>
                            <?php echo e($e['exam_code'] . ' - ' . $e['exam_title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($exam): ?>
            <div class="col-md-6 d-flex align-items-end">
                <div>
                    <span class="badge bg-primary"><?php echo count($questions); ?> questions</span>
                    <span class="badge bg-info">Limit: <?php echo $exam['question_limit']; ?></span>
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (isset($_GET['import']) && $examId): ?>
<!-- Bulk Import -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-upload me-2"></i>Bulk Import Questions</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>CSV Format:</strong> question_text, option_a, option_b, option_c, option_d, option_e, correct_answer, marks<br>
            <small>correct_answer should be A, B, C, D, or E</small>
        </div>
        <form id="importQuestionsForm" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">
            <div class="row g-3">
                <div class="col-md-8">
                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-cloud-upload me-1"></i>Import
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($examId && $exam): ?>
<!-- Questions List -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (empty($questions)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-question-circle fs-1"></i>
                <p class="mt-2">No questions yet. Add your first question!</p>
            </div>
        <?php else: ?>
            <div class="accordion" id="questionsAccordion">
                <?php foreach ($questions as $index => $q): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#q<?php echo $q['id']; ?>">
                            <span class="badge bg-primary me-2">Q<?php echo $index + 1; ?></span>
                            <span class="me-2"><?php echo e(substr(strip_tags($q['question_text']), 0, 80)); ?>...</span>
                            <span class="badge bg-success ms-auto"><?php echo $q['marks']; ?> marks</span>
                        </button>
                    </h2>
                    <div id="q<?php echo $q['id']; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" data-bs-parent="#questionsAccordion">
                        <div class="accordion-body">
                            <p><?php echo nl2br(e($q['question_text'])); ?></p>
                            <?php if ($q['question_image']): ?>
                                <img src="<?php echo APP_URL; ?>/assets/uploads/question_images/<?php echo e($q['question_image']); ?>" class="img-fluid mb-3 rounded" style="max-height:200px;">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="p-2 rounded <?php echo $q['correct_answer'] === 'A' ? 'bg-success text-white' : 'bg-light'; ?> mb-2">
                                        <strong>A.</strong> <?php echo e($q['option_a']); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-2 rounded <?php echo $q['correct_answer'] === 'B' ? 'bg-success text-white' : 'bg-light'; ?> mb-2">
                                        <strong>B.</strong> <?php echo e($q['option_b']); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-2 rounded <?php echo $q['correct_answer'] === 'C' ? 'bg-success text-white' : 'bg-light'; ?> mb-2">
                                        <strong>C.</strong> <?php echo e($q['option_c'] ?: '-'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-2 rounded <?php echo $q['correct_answer'] === 'D' ? 'bg-success text-white' : 'bg-light'; ?> mb-2">
                                        <strong>D.</strong> <?php echo e($q['option_d'] ?: '-'); ?>
                                    </div>
                                </div>
                                <?php if ($q['option_e']): ?>
                                <div class="col-md-6">
                                    <div class="p-2 rounded <?php echo $q['correct_answer'] === 'E' ? 'bg-success text-white' : 'bg-light'; ?> mb-2">
                                        <strong>E.</strong> <?php echo e($q['option_e']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-3">
                                <button class="btn btn-sm btn-primary" onclick="editQuestion(<?php echo $q['id']; ?>)">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteQuestion(<?php echo $q['id']; ?>)">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addQuestionForm" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Question Text *</label>
                            <textarea name="question_text" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Question Image (optional)</label>
                            <input type="file" name="question_image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Option A *</label>
                            <input type="text" name="option_a" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Option B *</label>
                            <input type="text" name="option_b" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Option C</label>
                            <input type="text" name="option_c" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Option D</label>
                            <input type="text" name="option_d" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Option E</label>
                            <input type="text" name="option_e" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correct Answer *</label>
                            <select name="correct_answer" class="form-select" required>
                                <option value="">Select</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Marks</label>
                            <input type="number" name="marks" class="form-control" value="1" min="0.5" max="100" step="0.5">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Question</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
<?php if ($examId): ?>
document.getElementById('addQuestionForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/add_question.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('addQuestionModal')).hide();
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('An error occurred', 'error');
    }
});

<?php if (isset($_GET['import'])): ?>
document.getElementById('importQuestionsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/bulk_upload_questions.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast(`Imported ${data.imported} questions`, 'success');
            setTimeout(() => window.location.href = '?exam_id=<?php echo $examId; ?>', 1000);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Import failed', 'error');
    }
});
<?php endif; ?>

async function deleteQuestion(id) {
    if (!confirm('Delete this question?')) return;
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/delete_question.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ question_id: id })
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Question deleted', 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Failed to delete', 'error');
    }
}

async function editQuestion(id) {
    showToast('Edit question ID: ' + id + ' (implement edit modal)', 'info');
}
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
