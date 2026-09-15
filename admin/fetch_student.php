<?php
/**
 * CBT System - Fetch Student from CMS
 * Extract student data from TSU CMS verification system
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Fetch Student from CMS';
$db = getDB();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.cms-result-card {
    border-left: 4px solid #e8c84c;
    transition: all 0.3s ease;
}
.cms-result-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.student-photo-lg {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid #e8c84c;
}
.student-photo-placeholder-lg {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid #e8c84c;
    margin: 0 auto;
}
.student-photo-placeholder-lg i {
    font-size: 60px;
    color: white;
}
.field-group {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px 15px;
    margin-bottom: 10px;
}
.field-group label {
    font-size: 0.7rem;
    text-transform: uppercase;
    color: #6c757d;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
    display: block;
}
.field-group .value {
    font-weight: 600;
    color: #1a1a2e;
}
.field-group .value.text-muted-custom {
    color: #6c757d;
    font-weight: 400;
}
.field-group .value.auto-assigned {
    color: #1a73e8;
}
.field-group .value.missing {
    color: #dc3545;
}
.progress-step {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    border-radius: 8px;
    transition: all 0.3s;
}
.progress-step.active {
    background: #e8f0fe;
    border-left: 3px solid #1a73e8;
}
.progress-step.completed {
    background: #e6f4ea;
    border-left: 3px solid #34a853;
}
.progress-step .step-number {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
    background: #e9ecef;
    color: #6c757d;
}
.progress-step.active .step-number {
    background: #1a73e8;
    color: white;
}
.progress-step.completed .step-number {
    background: #34a853;
    color: white;
}
.progress-step .step-label {
    font-size: 0.85rem;
    font-weight: 500;
}
.progress-step .step-status {
    margin-left: auto;
    font-size: 0.75rem;
}
.match-badge {
    font-size: 0.65rem;
    padding: 2px 10px;
    border-radius: 12px;
}
.match-badge.found {
    background: #e6f4ea;
    color: #34a853;
}
.match-badge.not-found {
    background: #fce8e6;
    color: #ea4335;
}
.match-badge.auto {
    background: #e8f0fe;
    color: #1a73e8;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-cloud-download me-2" style="color: var(--tsu-gold);"></i>Fetch Student from CMS</h4>
    <a href="students.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Students
    </a>
</div>

<div class="row">
    <!-- Search Form -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-search me-2"></i>Search Student</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i>
                    Enter a matric number or JAMB number to fetch student data from the TSU CMS verification portal.
                </div>
                
                <form id="fetchStudentForm">
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Matric Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                            <input type="text" name="matricno" id="matricno" class="form-control form-control-lg" 
                                   placeholder="e.g., TSU/FED/CS/20/1009" required autocomplete="off">
                        </div>
                        <div class="form-text">Enter the full matric number or JAMB number as it appears in the CMS</div>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <button type="submit" class="btn btn-primary w-100" id="fetchBtn">
                                <i class="bi bi-cloud-download me-1"></i> Fetch Data
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="reset" class="btn btn-secondary w-100">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Clear
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Progress Steps -->
                <div class="mt-4">
                    <div class="progress-step" id="step1">
                        <span class="step-number">1</span>
                        <span class="step-label">Enter Matric Number</span>
                        <span class="step-status text-muted">Pending</span>
                    </div>
                    <div class="progress-step" id="step2">
                        <span class="step-number">2</span>
                        <span class="step-label">Fetch from CMS</span>
                        <span class="step-status text-muted">Pending</span>
                    </div>
                    <div class="progress-step" id="step3">
                        <span class="step-number">3</span>
                        <span class="step-label">Review & Import</span>
                        <span class="step-status text-muted">Pending</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Tips -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body">
                <h6 class="mb-2"><i class="bi bi-lightbulb me-2" style="color: var(--tsu-gold);"></i>How It Works</h6>
                <ul class="small text-muted mb-0">
                    <li>Enter the matric number or JAMB number and click "Fetch Data"</li>
                    <li>The system uses cURL to look up the student on the CMS verify portal</li>
                    <li>Name, photo, department, and level are extracted from the CMS response</li>
                    <li>Department and Level are automatically mapped to local records</li>
                    <li>Click "Import Student" to save the student and photo to the database</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Results Area -->
    <div class="col-lg-7">
        <div id="resultArea">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-person fs-1 d-block mb-3" style="color: #dadce0;"></i>
                    <h6>No Student Data Loaded</h6>
                    <p class="mb-0 small">Enter a matric number and click "Fetch Data" to load student information from the CMS.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const APP_URL = '<?php echo APP_URL; ?>';
const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';
const CSRF_TOKEN_NAME = '<?php echo CSRF_TOKEN_NAME; ?>';

// Update progress step
function updateStep(step, status) {
    const stepEl = document.getElementById('step' + step);
    if (!stepEl) return;
    
    stepEl.classList.remove('active', 'completed');
    const statusEl = stepEl.querySelector('.step-status');
    
    if (status === 'active') {
        stepEl.classList.add('active');
        if (statusEl) statusEl.textContent = 'In Progress...';
    } else if (status === 'completed') {
        stepEl.classList.add('completed');
        if (statusEl) statusEl.innerHTML = '<i class="bi bi-check-circle text-success"></i> Done';
    } else {
        if (statusEl) statusEl.textContent = 'Pending';
    }
}

// Reset all steps
function resetSteps() {
    for (let i = 1; i <= 3; i++) {
        updateStep(i, 'pending');
    }
}

// Fetch student data
document.getElementById('fetchStudentForm').addEventListener('reset', function() {
    resetSteps();
    document.getElementById('resultArea').innerHTML = `
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-person fs-1 d-block mb-3" style="color: #dadce0;"></i>
                <h6>No Student Data Loaded</h6>
                <p class="mb-0 small">Enter a matric number and click "Fetch Data" to load student information from the CMS.</p>
            </div>
        </div>
    `;
});

document.getElementById('fetchStudentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const matricno = document.getElementById('matricno').value.trim();
    if (!matricno) {
        showToast('Please enter a matric number', 'error');
        return;
    }
    
    const fetchBtn = document.getElementById('fetchBtn');
    const originalHtml = fetchBtn.innerHTML;
    fetchBtn.disabled = true;
    fetchBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Fetching...';
    
    resetSteps();
    updateStep(1, 'completed');
    updateStep(2, 'active');
    
    try {
        const response = await fetch(APP_URL + '/ajax/admin/fetch_cms_student.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({ matricno: matricno })
        });

        const raw = await response.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (parseError) {
            throw new Error('Invalid response from server');
        }

        if (data.success && data.student) {
            updateStep(2, 'completed');
            updateStep(3, 'active');
            displayStudentData(data.student);
            showToast('Student data fetched successfully!', 'success');
        } else {
            updateStep(2, 'pending');
            const message = data.message || 'Failed to fetch student data';
            showToast(message, 'error');
            document.getElementById('resultArea').innerHTML = `
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-exclamation-triangle fs-1 d-block mb-3" style="color: #dc3545;"></i>
                        <h6 class="text-danger">${escapeHtml(message)}</h6>
                        <p class="mb-0 small text-muted">Please check the matric number and try again.</p>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Fetch error:', error);
        showToast('An error occurred: ' + error.message, 'error');
        updateStep(2, 'pending');
    } finally {
        fetchBtn.disabled = false;
        fetchBtn.innerHTML = originalHtml;
    }
});

// Display student data
function displayStudentData(student) {
    // Check if department and level were found
    const deptFound = student.department_id > 0;
    const levelFound = student.level_id > 0;
    
    let html = `
    <div class="card border-0 shadow-sm cms-result-card">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-person-check me-2" style="color: var(--tsu-success);"></i>Student Data Fetched</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Student Photo -->
                <div class="col-md-4 text-center mb-3 mb-md-0">
                    ${student.photo_data ? 
                        `<img src="${student.photo_data}" class="student-photo-lg" alt="Student Photo">` :
                        (student.photo ? 
                            `<img src="${student.photo}" class="student-photo-lg" alt="Student Photo" onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\\'student-photo-placeholder-lg mx-auto\\'><i class=\\'bi bi-person-fill\\'></i></div>'">` :
                            `<div class="student-photo-placeholder-lg mx-auto">
                                <i class="bi bi-person-fill"></i>
                            </div>`
                        )
                    }
                    <div class="mt-2">
                        <span class="badge bg-${student.exists ? 'warning' : 'success'}">
                            ${student.exists ? '⚠️ Already Exists' : '✅ New Student'}
                        </span>
                    </div>
                </div>
                
                <!-- Student Details -->
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-12">
                            <div class="field-group">
                                <label>Matric Number</label>
                                <div class="value">${escapeHtml(student.matric_number)}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="field-group">
                                <label>First Name</label>
                                <div class="value">${escapeHtml(student.first_name)}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="field-group">
                                <label>Last Name</label>
                                <div class="value">${escapeHtml(student.last_name)}</div>
                            </div>
                        </div>
                        ${student.other_name ? `
                        <div class="col-12">
                            <div class="field-group">
                                <label>Other Name</label>
                                <div class="value">${escapeHtml(student.other_name)}</div>
                            </div>
                        </div>
                        ` : ''}
                        ${student.email ? `
                        <div class="col-12">
                            <div class="field-group">
                                <label>Email</label>
                                <div class="value">${escapeHtml(student.email)}</div>
                            </div>
                        </div>
                        ` : ''}
                        ${student.programme ? `
                        <div class="col-12">
                            <div class="field-group">
                                <label>Programme</label>
                                <div class="value">${escapeHtml(student.programme)}</div>
                            </div>
                        </div>
                        ` : ''}
                        <div class="col-6">
                            <div class="field-group">
                                <label>Department</label>
                                <div class="value ${deptFound ? 'auto-assigned' : 'missing'}">
                                    ${escapeHtml(student.department || 'N/A')}
                                    ${deptFound ? ' <span class="match-badge found">✓ Found</span>' : ' <span class="match-badge not-found">✗ Not Found</span>'}
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="field-group">
                                <label>Level</label>
                                <div class="value ${levelFound ? 'auto-assigned' : 'missing'}">
                                    ${escapeHtml(student.level || 'N/A')}
                                    ${levelFound ? ' <span class="match-badge found">✓ Found</span>' : ' <span class="match-badge not-found">✗ Not Found</span>'}
                                </div>
                            </div>
                        </div>
                        ${student.session ? `
                        <div class="col-6">
                            <div class="field-group">
                                <label>Latest Session</label>
                                <div class="value">${escapeHtml(student.session)}</div>
                            </div>
                        </div>
                        ` : ''}
                        ${student.address ? `
                        <div class="col-6">
                            <div class="field-group">
                                <label>Address</label>
                                <div class="value">${escapeHtml(student.address)}</div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            
            <hr>
            
            <!-- Auto-mapping info -->
            <div class="alert alert-info small">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Auto-Mapping:</strong> 
                Department and Level have been automatically mapped to existing entries in the system.
                ${!deptFound ? ' <span class="text-danger">Department could not be mapped automatically. Please check the department name.</span>' : ''}
                ${!levelFound ? ' <span class="text-danger">Level could not be mapped automatically. Please check the level.</span>' : ''}
            </div>
            
            <!-- Import Form -->
            <form id="importStudentForm">
                <input type="hidden" name="${escapeHtml(CSRF_TOKEN_NAME)}" value="${escapeHtml(CSRF_TOKEN)}">
                <input type="hidden" name="matric_number" value="${escapeHtml(student.matric_number)}">
                <input type="hidden" name="first_name" value="${escapeHtml(student.first_name)}">
                <input type="hidden" name="last_name" value="${escapeHtml(student.last_name)}">
                <input type="hidden" name="other_name" value="${escapeHtml(student.other_name || '')}">
                <input type="hidden" name="email" value="${escapeHtml(student.email || '')}">
                <input type="hidden" name="photo" value="${escapeHtml(student.photo || '')}">
                <input type="hidden" name="photo_data" value="${student.photo_data || ''}">
                <input type="hidden" name="programme" value="${escapeHtml(student.programme || '')}">
                <input type="hidden" name="department_id" value="${student.department_id || 0}">
                <input type="hidden" name="level_id" value="${student.level_id || 0}">
                <input type="hidden" name="semester_id" value="${student.semester_id || 0}">
                <input type="hidden" name="session_id" value="${student.session_id || 0}">
                
                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="auto_password" value="1" id="autoPassword" checked>
                            <label class="form-check-label" for="autoPassword">
                                Auto-generate password (matric number as default)
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success w-100" id="importBtn" ${student.exists ? 'disabled' : ''}>
                            <i class="bi bi-download me-1"></i> 
                            ${student.exists ? 'Student Already Exists' : 'Import Student to Database'}
                        </button>
                        ${student.exists ? `<small class="text-muted d-block mt-1">This student is already in the database.</small>` : ''}
                        ${!deptFound || !levelFound ? `<small class="text-warning d-block mt-1">⚠️ Department or Level could not be mapped automatically. Please check before importing.</small>` : ''}
                    </div>
                </div>
            </form>
        </div>
    </div>
    `;
    
    document.getElementById('resultArea').innerHTML = html;
    
    // Handle import form submission
    document.getElementById('importStudentForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const importBtn = document.getElementById('importBtn');
        const originalHtml = importBtn.innerHTML;
        importBtn.disabled = true;
        importBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importing...';

        const formData = new FormData(this);
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 20000);
        let imported = false;

        try {
            const response = await fetch(APP_URL + '/ajax/admin/import_cms_student.php', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: formData,
                signal: controller.signal
            });

            const raw = await response.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch (parseError) {
                throw new Error('Invalid response from server');
            }

            if (data.success) {
                imported = true;
                updateStep(3, 'completed');
                showToast(data.message, 'success');
                importBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Imported Successfully';
                importBtn.className = 'btn btn-success w-100';
                importBtn.disabled = true;
                setTimeout(() => {
                    window.location.href = APP_URL + '/admin/students.php';
                }, 1500);
            } else {
                showToast(data.message || 'Import failed', 'error');
            }
        } catch (error) {
            console.error('Import error:', error);
            const message = error.name === 'AbortError'
                ? 'Import timed out. Please try again.'
                : ('An error occurred: ' + error.message);
            showToast(message, 'error');
        } finally {
            clearTimeout(timeoutId);
            if (!imported) {
                importBtn.disabled = false;
                importBtn.innerHTML = originalHtml;
            }
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>