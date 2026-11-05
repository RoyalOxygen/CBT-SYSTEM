<?php if (!defined('CBT_SYSTEM')) die('Direct access not permitted'); ?>
        </div>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script>
// Global variables
const APP_URL = '<?php echo APP_URL; ?>';
const CSRF_TOKEN_NAME = '<?php echo CSRF_TOKEN_NAME; ?>';
const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';

// Toast notification
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.bottom = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : (type === 'error' ? 'danger' : 'info')} border-0 show`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('data-bs-autohide', 'true');
    toast.style.minWidth = '250px';
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.getElementById('toast-container').appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

// Confirm dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Loading overlay
function showLoading() {
    let overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
        overlay.style.zIndex = '99999';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.innerHTML = '<div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status"></div>';
        document.body.appendChild(overlay);
    }
    overlay.classList.remove('d-none');
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.add('d-none');
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            setTimeout(() => bsAlert.close(), 5000);
        });
    }, 1000);
});
</script>
</body>
</html>