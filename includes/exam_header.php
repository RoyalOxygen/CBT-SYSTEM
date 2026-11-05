<?php
/**
 * CBT System - Student Exam Header Template (No Navbar)
 * For exam taking interface - clean and distraction-free
 */
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

$flash = getFlashMessage();
$pageTitle = $pageTitle ?? 'Exam';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title><?php echo e($pageTitle); ?> - <?php echo APP_NAME; ?></title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/font/bootstrap-icons.css">
    <style>
        /* Exam Mode Styles - No Navbar, No Sidebar */
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        /* Exam Header */
        .exam-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .exam-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .timer-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .timer-badge.warning {
            background: #ffc107;
            color: #000;
            animation: pulse 1s infinite;
        }
        
        .timer-badge.danger {
            background: #dc3545;
            color: white;
            animation: pulse 0.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .exam-info {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        /* Question Panel */
        .question-panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .question-text {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .question-image {
            max-width: 100%;
            max-height: 300px;
            margin: 15px 0;
            border-radius: 8px;
        }
        
        .option-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .option-item:hover {
            background: #e9ecef;
            border-color: #667eea;
        }
        
        .option-item.selected {
            background: #667eea;
            border-color: #667eea;
            color: white;
        }
        
        .option-item.selected input {
            accent-color: white;
        }
        
        /* Navigation Buttons */
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Question Palette */
        .palette-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
        }
        
        .palette-btn {
            padding: 8px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.85rem;
        }
        
        .palette-btn:hover {
            transform: scale(1.05);
        }
        
        .palette-btn.answered {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        
        .palette-btn.review {
            background: #ffc107;
            color: #000;
            border-color: #ffc107;
        }
        
        .palette-btn.current {
            background: #667eea;
            color: white;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102,126,234,0.4);
        }
        
        /* Submit Button */
        .submit-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.2s;
        }
        
        .submit-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        /* Fullscreen Warning */
        .fullscreen-warning {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.95);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 9998;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Toast Container */
        .toast-container {
            z-index: 9999;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .exam-header h4 {
                font-size: 1rem;
            }
            .timer-badge {
                font-size: 0.9rem;
                padding: 5px 12px;
            }
            .palette-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        /* Anti-cheat measures */
        body.exam-mode {
            user-select: none;
        }
        
        /* Auto-save indicator */
        .auto-save-indicator {
            font-size: 0.75rem;
            transition: all 0.3s;
        }
        
        .auto-save-indicator.saving {
            color: #ffc107;
        }
        
        .auto-save-indicator.saved {
            color: #28a745;
        }
        
        .auto-save-indicator.error {
            color: #dc3545;
        }
    </style>
</head>
<body class="exam-mode">
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="mainToast" class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay d-none">
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-white">Processing...</p>
        </div>
    </div>

    <!-- Fullscreen Warning -->
    <div id="fullscreenWarning" class="fullscreen-warning d-none">
        <div>
            <i class="bi bi-fullscreen fs-1 mb-3 d-block"></i>
            <h3>Fullscreen Required</h3>
            <p>This exam must be taken in fullscreen mode.</p>
            <button class="btn btn-primary btn-lg" onclick="enterFullscreen()">
                <i class="bi bi-fullscreen me-2"></i>Enter Fullscreen
            </button>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        const APP_URL = '<?php echo APP_URL; ?>';
        const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';
        
        // Toast notification function
        function showToast(message, type = 'success') {
            const toastEl = document.getElementById('mainToast');
            const toastBody = document.getElementById('toastMessage');
            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            
            toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info');
            
            if (type === 'success') {
                toastEl.classList.add('bg-success', 'text-white');
            } else if (type === 'error') {
                toastEl.classList.add('bg-danger', 'text-white');
            } else if (type === 'warning') {
                toastEl.classList.add('bg-warning');
            } else {
                toastEl.classList.add('bg-info', 'text-white');
            }
            
            toastBody.textContent = message;
            toast.show();
        }
        
        // Loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('d-none');
        }
        
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('d-none');
        }
        
        // Fullscreen functions
        function enterFullscreen() {
            const elem = document.documentElement;
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            }
            document.getElementById('fullscreenWarning').classList.add('d-none');
        }
        
        function toggleFullscreen() {
            if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
                enterFullscreen();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        }
        
        // Update save indicator
        function updateSaveIndicator(status) {
            const indicator = document.getElementById('saveIndicator');
            if (indicator) {
                if (status === 'saving') {
                    indicator.innerHTML = '<i class="bi bi-arrow-repeat spin me-1"></i>Saving...';
                    indicator.classList.add('saving');
                    indicator.classList.remove('saved', 'error');
                } else if (status === 'saved') {
                    indicator.innerHTML = '<i class="bi bi-check-circle me-1"></i>Saved';
                    indicator.classList.add('saved');
                    indicator.classList.remove('saving', 'error');
                    setTimeout(() => {
                        if (indicator.innerHTML.includes('Saved')) {
                            indicator.innerHTML = '<i class="bi bi-check-circle me-1"></i>Auto-save enabled';
                            indicator.classList.remove('saved');
                        }
                    }, 2000);
                } else if (status === 'error') {
                    indicator.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Save failed';
                    indicator.classList.add('error');
                    indicator.classList.remove('saving', 'saved');
                }
            }
        }
        
        // Disable right-click context menu
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Disable copy/paste/cut
        document.addEventListener('copy', function(e) {
            e.preventDefault();
            return false;
        });
        
        document.addEventListener('paste', function(e) {
            e.preventDefault();
            return false;
        });
        
        document.addEventListener('cut', function(e) {
            e.preventDefault();
            return false;
        });
        
        // =====================================================
        // REMOVED: beforeunload handler that showed "Changes you 
        // made may not be saved." dialog when leaving/submitting.
        // =====================================================
        
        // Track tab visibility
        let tabWarnings = 0;
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && typeof examPageInProgress !== 'undefined' && examPageInProgress) {
                tabWarnings++;
                if (tabWarnings >= 3) {
                    showToast('Multiple tab switches detected. Your exam will be submitted.', 'warning');
                    if (typeof forceAutoSubmit === 'function') {
                        setTimeout(forceAutoSubmit, 2000);
                    }
                } else {
                    showToast(`Warning ${tabWarnings}/3: Switching tabs is not allowed during the exam!`, 'warning');
                }
            }
        });
        
        // Disable F12, Ctrl+Shift+I, Ctrl+U
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F12' || 
                (e.ctrlKey && e.shiftKey && e.key === 'I') || 
                (e.ctrlKey && e.key === 'u') ||
                (e.ctrlKey && e.key === 'U')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Add spinning animation CSS
        const style = document.createElement('style');
        style.textContent = `
            .spin {
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>