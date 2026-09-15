<?php
/**
 * CBT System - Biometric Enrollment (Admin)
 * Web-based fingerprint capture with SDK integration points
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Biometric Enrollment';
$db = getDB();

// Get students without biometric
$unenrolledStudents = $db->query("SELECT s.id, s.matric_number, s.first_name, s.last_name, s.photo, d.dept_name 
    FROM students s LEFT JOIN departments d ON s.department_id = d.id 
    WHERE s.fingerprint_enrolled = 0 AND s.status = 1 ORDER BY s.created_at DESC LIMIT 50")->fetchAll();

// Get recently enrolled
$enrolledStudents = $db->query("SELECT s.id, s.matric_number, s.first_name, s.last_name, s.photo, d.dept_name, ft.enrolled_at 
    FROM students s LEFT JOIN departments d ON s.department_id = d.id 
    JOIN fingerprint_templates ft ON s.id = ft.student_id 
    WHERE s.fingerprint_enrolled = 1 ORDER BY ft.enrolled_at DESC LIMIT 20")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <!-- Student Search -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-search me-2"></i>Find Student</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" id="searchStudent" class="form-control" placeholder="Search by matric number or name..." onkeyup="searchStudents(this.value)">
                </div>
                <div id="searchResults" style="max-height:400px;overflow-y:auto;"></div>
                
                <hr>
                
                <h6 class="text-muted">Unenrolled Students</h6>
                <div style="max-height:300px;overflow-y:auto;">
                    <?php foreach ($unenrolledStudents as $s): ?>
                    <div class="d-flex align-items-center p-2 rounded hover-bg cursor-pointer" onclick="selectStudent(<?php echo $s['id']; ?>, '<?php echo e($s['matric_number']); ?>', '<?php echo e($s['first_name'] . ' ' . $s['last_name']); ?>')">
                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:36px;height:36px;">
                            <i class="bi bi-person"></i>
                        </div>
                        <div>
                            <strong class="small"><?php echo e($s['matric_number']); ?></strong><br>
                            <small class="text-muted"><?php echo e($s['last_name'] . ', ' . $s['first_name']); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($unenrolledStudents)): ?>
                        <p class="text-muted small text-center">All students enrolled!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Biometric Capture Area -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-fingerprint me-2"></i>Fingerprint Capture</h5>
            </div>
            <div class="card-body text-center">
                <!-- Selected Student Info -->
                <div id="selectedStudentInfo" class="mb-3 d-none">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width:60px;height:60px;font-size:1.5rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h6 id="studentName">Student Name</h6>
                    <p class="text-muted mb-0" id="studentMatric">MATRIC NO</p>
                    <input type="hidden" id="selectedStudentId">
                </div>
                
                <!-- Scanner Interface -->
                <div id="scannerInterface" class="mb-3">
                    <div class="scanner-ring mx-auto mb-3 d-flex align-items-center justify-content-center" id="scannerRing">
                        <i class="bi bi-fingerprint" style="font-size:3rem;color:var(--primary-color)"></i>
                    </div>
                    <p class="text-muted" id="scannerStatus">Select a student to begin enrollment</p>
                    <div class="progress d-none" id="captureProgress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div>
                    </div>
                </div>
                
                <!-- SDK Selection -->
                <div class="mb-3">
                    <label class="form-label small text-muted">Fingerprint SDK</label>
                    <select id="sdkSelector" class="form-select form-select-sm">
                        <option value="simulated">Simulated (Demo Mode)</option>
                        <option value="digitalpersona">DigitalPersona U.are.U</option>
                        <option value="secugen">SecuGen Hamster</option>
                        <option value="mantra">Mantra MFS100</option>
                        <option value="webauthn">WebAuthn API</option>
                    </select>
                    <small class="text-muted">Select 'Simulated' for testing without hardware</small>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" id="captureBtn" onclick="captureFingerprint()" disabled>
                        <i class="bi bi-fingerprint me-1"></i>Capture Fingerprint
                    </button>
                    <button class="btn btn-success d-none" id="saveBtn" onclick="saveTemplate()">
                        <i class="bi bi-check-lg me-1"></i>Save Template
                    </button>
                    <button class="btn btn-secondary" id="verifyBtn" onclick="verifyFingerprint()" disabled>
                        <i class="bi bi-shield-check me-1"></i>Verify
                    </button>
                </div>
                
                <!-- Integration Guide -->
                <div class="mt-3 text-start">
                    <a href="#" class="small text-decoration-none" data-bs-toggle="collapse" data-bs-target="#sdkGuide">
                        <i class="bi bi-info-circle me-1"></i>SDK Integration Guide
                    </a>
                    <div class="collapse mt-2" id="sdkGuide">
                        <div class="alert alert-info small">
                            <strong>Real SDK Integration:</strong><br>
                            To use actual fingerprint hardware, implement the vendor's Web SDK in <code>biometric.js</code>. Each SDK provides browser plugins that expose JavaScript APIs for device communication. The template data should be sent to the server for secure storage.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recently Enrolled -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Recently Enrolled</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($enrolledStudents as $s): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <?php if ($s['photo']): ?>
                                <img src="<?php echo APP_URL; ?>/assets/uploads/student_photos/<?php echo e($s['photo']); ?>" class="rounded-circle me-2" width="36" height="36" style="object-fit:cover;">
                            <?php else: ?>
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:36px;height:36px;">
                                    <i class="bi bi-fingerprint"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <strong class="small"><?php echo e($s['matric_number']); ?></strong><br>
                                <small class="text-muted"><?php echo e($s['last_name'] . ', ' . $s['first_name']); ?></small>
                            </div>
                        </div>
                        <small class="text-muted"><?php echo formatDateTime($s['enrolled_at']); ?></small>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($enrolledStudents)): ?>
                        <div class="list-group-item text-muted text-center py-4">No enrollments yet</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let capturedTemplate = null;

function selectStudent(id, matric, name) {
    document.getElementById('selectedStudentId').value = id;
    document.getElementById('studentName').textContent = name;
    document.getElementById('studentMatric').textContent = matric;
    document.getElementById('selectedStudentInfo').classList.remove('d-none');
    document.getElementById('captureBtn').disabled = false;
    document.getElementById('verifyBtn').disabled = false;
    document.getElementById('scannerStatus').textContent = 'Ready to capture fingerprint';
    document.getElementById('saveBtn').classList.add('d-none');
    capturedTemplate = null;
}

async function searchStudents(query) {
    if (query.length < 2) {
        document.getElementById('searchResults').innerHTML = '';
        return;
    }
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/search_students.php?q=' + encodeURIComponent(query), {
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('searchResults').innerHTML = data.students.map(s => `
                <div class="d-flex align-items-center p-2 rounded hover-bg cursor-pointer" onclick="selectStudent(${s.id}, '${s.matric_number}', '${s.first_name} ${s.last_name}')">
                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:36px;height:36px;">
                        <i class="bi bi-person"></i>
                    </div>
                    <div>
                        <strong class="small">${s.matric_number}</strong><br>
                        <small class="text-muted">${s.last_name}, ${s.first_name}</small>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

async function captureFingerprint() {
    const sdk = document.getElementById('sdkSelector').value;
    const studentId = document.getElementById('selectedStudentId').value;
    
    if (!studentId) {
        showToast('Please select a student first', 'error');
        return;
    }
    
    document.getElementById('captureBtn').disabled = true;
    document.getElementById('scannerStatus').textContent = 'Place finger on scanner...';
    
    if (sdk === 'simulated') {
        // Simulate capture process
        const progress = document.querySelector('#captureProgress');
        progress.classList.remove('d-none');
        const bar = progress.querySelector('.progress-bar');
        
        let width = 0;
        const interval = setInterval(() => {
            width += 10;
            bar.style.width = width + '%';
            
            if (width >= 100) {
                clearInterval(interval);
                
                // Generate simulated template
                capturedTemplate = {
                    template_data: JSON.stringify({
                        sdk: 'simulated',
                        version: '1.0',
                        data: btoa('simulated_template_' + Date.now() + '_' + Math.random().toString(36)),
                        quality: Math.floor(Math.random() * 30) + 70,
                        timestamp: new Date().toISOString()
                    }),
                    template_hash: 'hash_' + Date.now(),
                    quality_score: Math.floor(Math.random() * 30) + 70,
                    finger_position: 'right_thumb'
                };
                
                document.getElementById('scannerStatus').innerHTML = 
                    '<span class="text-success"><i class="bi bi-check-circle"></i> Fingerprint captured successfully! Quality: ' + capturedTemplate.quality_score + '%</span>';
                document.getElementById('saveBtn').classList.remove('d-none');
                progress.classList.add('d-none');
            }
        }, 200);
    } else {
        // Real SDK would be called here
        showToast('Real SDK integration required. Please configure ' + sdk + ' SDK in biometric.js', 'warning');
        document.getElementById('captureBtn').disabled = false;
    }
}

async function saveTemplate() {
    if (!capturedTemplate) {
        showToast('No fingerprint captured', 'error');
        return;
    }
    
    const studentId = document.getElementById('selectedStudentId').value;
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/enroll_biometric.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({
                student_id: studentId,
                ...capturedTemplate
            })
        });
        const data = await response.json();
        
        showToast(data.message, data.success ? 'success' : 'error');
        
        if (data.success) {
            document.getElementById('saveBtn').classList.add('d-none');
            document.getElementById('captureBtn').disabled = false;
            capturedTemplate = null;
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showToast('Failed to save template', 'error');
    }
}

async function verifyFingerprint() {
    const studentId = document.getElementById('selectedStudentId').value;
    if (!studentId) {
        showToast('Select a student first', 'error');
        return;
    }
    
    showToast('Verification: Place finger on scanner (simulated: always success)', 'info');
    
    setTimeout(() => {
        showToast('Verification successful! Match score: 94%', 'success');
    }, 2000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
