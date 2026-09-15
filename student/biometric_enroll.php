<?php
/**
 * CBT System - Student Biometric Self-Enrollment
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/student_auth.php';

$pageTitle = 'Biometric Enrollment';
$db = getDB();

$studentId = $_SESSION['student_id'];

// Check if already enrolled
$stmt = $db->prepare("SELECT COUNT(*) FROM fingerprint_templates WHERE student_id = ?");
$stmt->execute([$studentId]);
$alreadyEnrolled = $stmt->fetchColumn() > 0;

// Get student info for header
$studentStmt = $db->prepare("SELECT s.*, d.dept_name, l.level_name FROM students s LEFT JOIN departments d ON s.department_id = d.id LEFT JOIN levels l ON s.level_id = l.id WHERE s.id = ?");
$studentStmt->execute([$studentId]);
$student = $studentStmt->fetch();

require_once __DIR__ . '/../includes/student_header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white text-center">
                    <h4 class="mb-0"><i class="bi bi-fingerprint me-2"></i>Biometric Enrollment</h4>
                </div>
                <div class="card-body text-center">
                    <?php if ($alreadyEnrolled): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill fs-1 d-block mb-2"></i>
                            <h5>Already Enrolled!</h5>
                            <p>Your fingerprint has been successfully enrolled in the system.</p>
                            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                        </div>
                    <?php else: ?>
                        <div class="biometric-container">
                            <div class="scanner-placeholder" id="scannerRing">
                                <i class="bi bi-fingerprint" style="font-size:4rem;color:var(--primary-color)"></i>
                            </div>
                            
                            <h5 class="mb-3">Enroll Your Fingerprint</h5>
                            <p class="text-muted" id="statusText">Click the button below to start fingerprint capture</p>
                            
                            <div class="progress mb-3 d-none" id="captureProgress" style="height:8px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-lg" id="captureBtn" onclick="captureFingerprint()">
                                    <i class="bi bi-fingerprint me-2"></i>Capture Fingerprint
                                </button>
                                <button class="btn btn-success btn-lg d-none" id="saveBtn" onclick="saveTemplate()">
                                    <i class="bi bi-check-lg me-2"></i>Save & Complete
                                </button>
                            </div>
                            
                            <div class="mt-4 alert alert-info text-start small">
                                <strong><i class="bi bi-info-circle me-1"></i>Note:</strong><br>
                                This is a simulated enrollment for demonstration. In production, this will integrate with your institution's fingerprint hardware (DigitalPersona, SecuGen, Mantra, etc.).
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let capturedTemplate = null;

async function captureFingerprint() {
    document.getElementById('captureBtn').disabled = true;
    document.getElementById('scannerRing').classList.add('scanning');
    document.getElementById('statusText').textContent = 'Place your finger on the scanner...';
    
    const progress = document.getElementById('captureProgress');
    progress.classList.remove('d-none');
    const bar = progress.querySelector('.progress-bar');
    
    let width = 0;
    const interval = setInterval(() => {
        width += 10;
        bar.style.width = width + '%';
        
        if (width >= 100) {
            clearInterval(interval);
            
            // Simulated template
            capturedTemplate = {
                template_data: JSON.stringify({
                    sdk: 'simulated',
                    version: '1.0',
                    data: btoa('student_template_' + Date.now() + '_' + Math.random().toString(36)),
                    quality: Math.floor(Math.random() * 20) + 80,
                    timestamp: new Date().toISOString()
                }),
                template_hash: 'student_hash_' + Date.now(),
                quality_score: Math.floor(Math.random() * 20) + 80,
                finger_position: 'right_thumb'
            };
            
            document.getElementById('statusText').innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Fingerprint captured! Quality: ' + capturedTemplate.quality_score + '%</span>';
            document.getElementById('scannerRing').classList.remove('scanning');
            document.getElementById('captureBtn').classList.add('d-none');
            document.getElementById('saveBtn').classList.remove('d-none');
            progress.classList.add('d-none');
        }
    }, 200);
}

async function saveTemplate() {
    if (!capturedTemplate) return;
    
    try {
        const response = await fetch(APP_URL + '/ajax/student/capture_biometric.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(capturedTemplate)
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Biometric enrolled successfully!', 'success');
            setTimeout(() => location.href = 'dashboard.php', 1500);
        } else {
            showToast(data.message, 'error');
            document.getElementById('saveBtn').classList.add('d-none');
            document.getElementById('captureBtn').classList.remove('d-none').disabled = false;
        }
    } catch (error) {
        showToast('Failed to save', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
