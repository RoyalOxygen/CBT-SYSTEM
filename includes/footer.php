<?php if (!defined('CBT_SYSTEM')) die('Direct access not permitted'); ?>
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
<?php $isStudentPage = strpos((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/student/') !== false; ?>
        </div>
<?php if ($isStudentPage): ?>
    </div>
    <footer class="site-footer bg-white border-top py-3">
            <div class="container-fluid text-center">
            <?php echo powered_by_credit(); ?>
        </div>
    </footer>
<?php else: ?>
    <footer class="site-footer bg-white border-top py-3">
        <div class="container-fluid text-center">
            <?php echo powered_by_credit(); ?>
        </div>
    </footer>
    </div>
</div>
<?php endif; ?>

<script src="<?php echo APP_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo APP_URL; ?>/assets/js/jquery-3.7.1.min.js"></script>
<?php if (!$isStudentPage): ?>
<script src="<?php echo APP_URL; ?>/assets/js/admin.js"></script>
<script>
(function () {
    var sidebar = document.getElementById('sidebar');
    var toggle = document.getElementById('sidebarToggle');
    var closeBtn = document.getElementById('sidebarClose');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar || !toggle) return;

    function setOpen(open) {
        sidebar.classList.toggle('active', open);
        document.body.classList.toggle('sidebar-open', open);
        if (backdrop) backdrop.classList.toggle('show', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    toggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        setOpen(!sidebar.classList.contains('active'));
    });
    if (closeBtn) closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        setOpen(false);
    });
    if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth < 992) setOpen(false);
        });
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) setOpen(false);
    });
})();
</script>
<?php endif; ?>
<script>
window.APP_URL = window.APP_URL || '<?php echo APP_URL; ?>';
window.CSRF_TOKEN_NAME = window.CSRF_TOKEN_NAME || '<?php echo CSRF_TOKEN_NAME; ?>';
window.CSRF_TOKEN = window.CSRF_TOKEN || '<?php echo generateCSRFToken(); ?>';
<<<<<<< HEAD
=======
=======
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
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36

// Toast notification
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
<<<<<<< HEAD
        container.style.bottom = document.querySelector('.site-footer') ? '72px' : '20px';
=======
<<<<<<< HEAD
        container.style.bottom = document.querySelector('.site-footer') ? '72px' : '20px';
=======
        container.style.bottom = '20px';
>>>>>>> 8cf9559f70483e81ff43fbb996cf1fd85a0e394c
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
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
<<<<<<< HEAD
</html>
=======
</html>
>>>>>>> d4cb62fcac2514a16b9152443c4440f47b4e3b36
