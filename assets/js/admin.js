/**
 * CBT System - Admin Panel JavaScript
 */

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('mainToast');
    const toastMessage = document.getElementById('toastMessage');
    
    toast.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white');
    
    switch(type) {
        case 'success':
            toast.classList.add('bg-success', 'text-white');
            break;
        case 'error':
            toast.classList.add('bg-danger', 'text-white');
            break;
        case 'warning':
            toast.classList.add('bg-warning');
            break;
        case 'info':
            toast.classList.add('bg-info', 'text-white');
            break;
    }
    
    toastMessage.textContent = message;
    const bsToast = new bootstrap.Toast(toast, { delay: 4000 });
    bsToast.show();
}

// Loading overlay
function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('d-none');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('d-none');
}

// Sidebar toggle (mobile)
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        const html = document.documentElement;
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-bs-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        themeToggle.addEventListener('click', function() {
            const current = html.getAttribute('data-bs-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon(next);
        });
    }
    
    // Auto-hide alerts
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

function updateThemeIcon(theme) {
    const icon = document.querySelector('#themeToggle i');
    if (icon) {
        icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }
}

// AJAX helper
async function fetchJSON(url, options = {}) {
    const defaultOptions = {
        headers: {
            'X-CSRF-TOKEN': typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const response = await fetch(url, { ...defaultOptions, ...options });
    return response.json();
}

// Form validation helper
function validateForm(formId, rules = {}) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    let valid = true;
    
    for (const [field, rule] of Object.entries(rules)) {
        const input = form.querySelector(`[name="${field}"]`);
        if (!input) continue;
        
        if (rule.required && !input.value.trim()) {
            input.classList.add('is-invalid');
            valid = false;
        } else {
            input.classList.remove('is-invalid');
        }
        
        if (rule.minLength && input.value.length < rule.minLength) {
            input.classList.add('is-invalid');
            valid = false;
        }
        
        if (rule.pattern && !rule.pattern.test(input.value)) {
            input.classList.add('is-invalid');
            valid = false;
        }
    }
    
    return valid;
}

// Confirm action helper
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Number formatting
function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

// Date formatting
function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

// Print helper
function printPage() {
    window.print();
}

// Export to CSV helper
function exportToCSV(filename, headers, rows) {
    const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}
