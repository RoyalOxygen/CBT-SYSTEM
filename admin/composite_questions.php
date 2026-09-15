<?php
/**
 * CBT System - Composite Questions Management
 * Manage main questions and their nested sub-questions
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Composite Questions';
$db = getDB();

$examId = intval($_GET['exam_id'] ?? 0);

// Get exam
$exam = null;
if ($examId) {
    $stmt = $db->prepare("SELECT ce.*, d.dept_name, l.level_name FROM composite_exams ce 
        LEFT JOIN departments d ON ce.department_id = d.id 
        LEFT JOIN levels l ON ce.level_id = l.id WHERE ce.id = ?");
    $stmt->execute([$examId]);
    $exam = $stmt->fetch();
}

// Get all composite exams for selector
$exams = $db->query("SELECT id, exam_code, exam_title FROM composite_exams ORDER BY created_at DESC")->fetchAll();

// Get questions with sub-questions
$questions = [];
if ($examId) {
    $stmt = $db->prepare("SELECT * FROM composite_questions WHERE composite_exam_id = ? AND status = 1 ORDER BY question_order, id");
    $stmt->execute([$examId]);
    $questions = $stmt->fetchAll();
    
    // Get sub-questions for each
    foreach ($questions as &$q) {
        $subStmt = $db->prepare("SELECT * FROM composite_sub_questions WHERE composite_question_id = ? ORDER BY sub_order, sub_question_label");
        $subStmt->execute([$q['id']]);
        $q['sub_questions'] = $subStmt->fetchAll();
        
        foreach ($q['sub_questions'] as &$sq) {
            $optStmt = $db->prepare("SELECT * FROM composite_sub_options WHERE composite_sub_question_id = ? ORDER BY option_order, option_label");
            $optStmt->execute([$sq['id']]);
            $sq['options'] = $optStmt->fetchAll();
        }
    }
    unset($q, $sq);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Composite Questions</h4>
        <small class="text-muted">Main question with nested sub-questions</small>
    </div>
    <div>
        <a href="composite_exams.php" class="btn btn-secondary me-2">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
        <?php if ($examId): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
            <i class="bi bi-plus-lg me-1"></i>Add Main Question
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Exam Selector -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Select Composite Exam</label>
                <select name="exam_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Select Composite Exam --</option>
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
                    <span class="badge bg-primary"><?php echo count($questions); ?> main questions</span>
                    <?php 
                    $totalSub = 0;
                    foreach ($questions as $q) $totalSub += count($q['sub_questions']);
                    ?>
                    <span class="badge bg-info"><?php echo $totalSub; ?> sub-questions</span>
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($examId && $exam): ?>
<!-- Questions List -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (empty($questions)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-diagram-3 fs-1"></i>
                <p class="mt-2">No questions yet. Add your first composite question!</p>
            </div>
        <?php else: ?>
            <div class="accordion" id="compositeQuestionsAccordion">
                <?php foreach ($questions as $index => $q): ?>
                <div class="accordion-item mb-3 border rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#cq<?php echo $q['id']; ?>">
                            <span class="badge bg-primary me-2">Q<?php echo $index + 1; ?></span>
                            <span class="me-2 text-truncate" style="max-width: 500px;">
                                <?php echo e(substr(strip_tags($q['question_text']), 0, 80)); ?>...
                            </span>
                            <span class="badge bg-info ms-auto me-2"><?php echo count($q['sub_questions']); ?> sub-Qs</span>
                            <span class="badge bg-success"><?php echo $q['marks']; ?> marks</span>
                        </button>
                    </h2>
                    <div id="cq<?php echo $q['id']; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                         data-bs-parent="#compositeQuestionsAccordion">
                        <div class="accordion-body">
                            <!-- Main Question -->
                            <div class="alert alert-primary">
                                <strong><i class="bi bi-question-circle me-1"></i>Main Question:</strong>
                                <p class="mb-0 mt-1"><?php echo nl2br(e($q['question_text'])); ?></p>
                                <?php if ($q['question_image']): ?>
                                    <img src="<?php echo APP_URL; ?>/assets/uploads/question_images/<?php echo e($q['question_image']); ?>" 
                                         class="img-fluid mt-2 rounded" style="max-height:150px;">
                                <?php endif; ?>
                            </div>
                            
                            <!-- Sub-Questions -->
                            <h6 class="mt-3 mb-3">Sub-Questions:</h6>
                            <?php foreach ($q['sub_questions'] as $sq): ?>
                                <div class="card mb-2 border">
                                    <div class="card-body py-3">
                                        <div class="d-flex align-items-start">
                                            <span class="badge bg-secondary me-2"><?php echo e($sq['sub_question_label']); ?></span>
                                            <div class="flex-grow-1">
                                                <p class="mb-2 fw-semibold"><?php echo e($sq['sub_question_text']); ?></p>
                                                <div class="row g-2">
                                                    <?php foreach ($sq['options'] as $opt): ?>
                                                    <div class="col-md-6">
                                                        <div class="p-2 rounded <?php echo $sq['correct_answer'] === $opt['option_label'] ? 'bg-success text-white' : 'bg-light'; ?> small">
                                                            <strong><?php echo e($opt['option_label']); ?>.</strong> <?php echo e($opt['option_text']); ?>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="mt-3">
                                <button class="btn btn-sm btn-primary" onclick="editCompositeQuestion(<?php echo $q['id']; ?>)">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteCompositeQuestion(<?php echo $q['id']; ?>)">
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
<div class="modal fade" id="addQuestionModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Composite Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCompositeQuestionForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="composite_exam_id" value="<?php echo $examId; ?>">
                <div class="modal-body">
                    <!-- Main Question Section -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-question-circle me-2"></i>Main Question</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Main Question Text *</label>
                                <textarea name="question_text" class="form-control" rows="3" 
                                          placeholder="e.g., Read the following passage and answer the questions below..." required></textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Question Image (Optional)</label>
                                    <input type="file" name="question_image" class="form-control" accept="image/*">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Marks (for entire question)</label>
                                    <input type="number" name="marks" class="form-control" value="4" min="0.5" step="0.5">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sub-Questions Section -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="bi bi-list-nested me-2"></i>Sub-Questions</h6>
                        <button type="button" class="btn btn-sm btn-success" onclick="addSubQuestion()">
                            <i class="bi bi-plus-lg me-1"></i>Add Sub-Question
                        </button>
                    </div>
                    
                    <div id="subQuestionsContainer">
                        <!-- Sub-questions will be added here dynamically -->
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

<script>
let subQuestionCount = 0;

function addSubQuestion() {
    subQuestionCount++;
    const labels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    const label = labels[subQuestionCount - 1] || ('Q' + subQuestionCount);
    
    const html = `
        <div class="card mb-3 border" id="subQuestion_${subQuestionCount}">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span><strong>Sub-Question ${label}</strong></span>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeSubQuestion(${subQuestionCount})">
                    <i class="bi bi-x"></i> Remove
                </button>
            </div>
            <div class="card-body">
                <input type="hidden" name="sub_questions[${subQuestionCount}][label]" value="${label}">
                
                <div class="mb-3">
                    <label class="form-label">Sub-Question Text *</label>
                    <textarea name="sub_questions[${subQuestionCount}][text]" class="form-control" rows="2" 
                              placeholder="e.g., What is the main idea of the passage?" required></textarea>
                </div>
                
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Option A *</label>
                        <input type="text" name="sub_questions[${subQuestionCount}][options][A]" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Option B *</label>
                        <input type="text" name="sub_questions[${subQuestionCount}][options][B]" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Option C</label>
                        <input type="text" name="sub_questions[${subQuestionCount}][options][C]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Option D</label>
                        <input type="text" name="sub_questions[${subQuestionCount}][options][D]" class="form-control form-control-sm">
                    </div>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small">Correct Answer *</label>
                        <select name="sub_questions[${subQuestionCount}][correct_answer]" class="form-select form-select-sm" required>
                            <option value="">Select</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Marks</label>
                        <input type="number" name="sub_questions[${subQuestionCount}][marks]" class="form-control form-control-sm" value="1" min="0.5" step="0.5">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('subQuestionsContainer').insertAdjacentHTML('beforeend', html);
}

function removeSubQuestion(id) {
    const el = document.getElementById('subQuestion_' + id);
    if (el) el.remove();
}

// Initialize with one sub-question
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('subQuestionsContainer')) {
        addSubQuestion();
        addSubQuestion();
    }
});

document.getElementById('addCompositeQuestionForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const subQuestions = document.querySelectorAll('#subQuestionsContainer .card');
    if (subQuestions.length === 0) {
        showToast('Please add at least one sub-question', 'error');
        return;
    }
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalHtml = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/add_composite_question.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('addQuestionModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Error: ' + error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHtml;
    }
});

function deleteCompositeQuestion(id) {
    if (!confirm('Delete this composite question and all its sub-questions?')) return;
    
    fetch(APP_URL + '/ajax/admin/delete_composite_question.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ question_id: id })
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 500);
    })
    .catch(() => showToast('Failed to delete', 'error'));
}

function editCompositeQuestion(id) {
    showToast('Edit functionality can be added here', 'info');
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>