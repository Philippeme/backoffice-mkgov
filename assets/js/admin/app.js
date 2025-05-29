// MK Gov Admin Application JavaScript
import '../dashboard';
import '../../styles/admin/app.scss';

// Bootstrap
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// Chart.js
import Chart from 'chart.js/auto';
window.Chart = Chart;

// Leaflet for maps
import L from 'leaflet';
window.L = L;

class MKGovAdmin {
    constructor() {
        this.init();
    }

    init() {
        this.initializeGlobalEventListeners();
        this.initializeTooltips();
        this.initializePopovers();
        this.initializeModals();
        this.initializeFormValidation();
        this.initializeSidebar();
        this.initializeTheme();
        this.initializeNotifications();
    }

    initializeGlobalEventListeners() {
        // Prevent double form submissions
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.dataset.submitted === 'true') {
                e.preventDefault();
                return false;
            }
            form.dataset.submitted = 'true';
            
            // Re-enable after 5 seconds as a safety net
            setTimeout(() => {
                form.dataset.submitted = 'false';
            }, 5000);
        });

        // Confirm delete actions
        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-confirm-delete]') || e.target.closest('[data-confirm-delete]')) {
                const element = e.target.matches('[data-confirm-delete]') ? e.target : e.target.closest('[data-confirm-delete]');
                const message = element.dataset.confirmDelete || 'Are you sure you want to delete this item?';
                
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        // Auto-hide alerts
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            setTimeout(() => {
                if (alert.parentNode) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        });

        // Search input enhancement
        document.querySelectorAll('input[type="search"], input[name*="search"]').forEach(input => {
            input.addEventListener('input', this.debounce(function() {
                if (this.value.length >= 3 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 500));
        });

        // Table row click handling
        document.querySelectorAll('tr[data-href]').forEach(row => {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function(e) {
                if (!e.target.matches('input, button, a, .btn')) {
                    window.location.href = this.dataset.href;
                }
            });
        });
    }

    initializeTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    initializePopovers() {
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }

    initializeModals() {
        // Auto-focus first input in modals
        document.addEventListener('shown.bs.modal', function(e) {
            const firstInput = e.target.querySelector('input:not([type="hidden"]):not([readonly]):not([disabled])');
            if (firstInput) {
                firstInput.focus();
            }
        });

        // Clear form data when modal closes
        document.addEventListener('hidden.bs.modal', function(e) {
            const form = e.target.querySelector('form');
            if (form && form.dataset.clearOnClose !== 'false') {
                form.reset();
                form.classList.remove('was-validated');
            }
        });
    }

    initializeFormValidation() {
        // Bootstrap form validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Real-time validation
        document.querySelectorAll('input[required], textarea[required], select[required]').forEach(input => {
            input.addEventListener('blur', function() {
                this.checkValidity();
                this.classList.toggle('is-valid', this.validity.valid);
                this.classList.toggle('is-invalid', !this.validity.valid);
            });
        });

        // Password strength indicator
        document.querySelectorAll('input[type="password"][data-strength]').forEach(input => {
            const strengthMeter = document.createElement('div');
            strengthMeter.className = 'password-strength-meter mt-1';
            strengthMeter.innerHTML = `
                <div class="strength-bar">
                    <div class="strength-fill"></div>
                </div>
                <small class="strength-text text-muted">Password strength</small>
            `;
            input.parentNode.appendChild(strengthMeter);

            input.addEventListener('input', function() {
                const strength = this.calculatePasswordStrength(this.value);
                const fill = strengthMeter.querySelector('.strength-fill');
                const text = strengthMeter.querySelector('.strength-text');
                
                fill.style.width = strength.percentage + '%';
                fill.className = `strength-fill bg-${strength.class}`;
                text.textContent = strength.label;
            }.bind(this));
        });
    }

    initializeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const mainContent = document.querySelector('.main-content');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                document.body.classList.toggle('sidebar-open');
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992 && 
                !sidebar.contains(e.target) && 
                !e.target.matches('.sidebar-toggle') &&
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            }
        });

        // Handle dropdown menus in sidebar
        document.querySelectorAll('.nav-dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const parent = this.parentElement;
                const dropdown = parent.querySelector('.nav-dropdown');
                
                // Close other dropdowns
                document.querySelectorAll('.nav-item.open').forEach(item => {
                    if (item !== parent) {
                        item.classList.remove('open');
                    }
                });
                
                parent.classList.toggle('open');
            });
        });
    }

    initializeTheme() {
        // Dark mode toggle (if implemented)
        const themeToggle = document.querySelector('[data-theme-toggle]');
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                this.querySelector('i').className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            });
            
            // Load saved theme
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    }

    initializeNotifications() {
        // Check for new notifications periodically
        if (window.MKGovAdmin?.routes?.notifications) {
            setInterval(() => {
                this.checkNotifications();
            }, 60000); // Check every minute
        }
    }

    async checkNotifications() {
        try {
            const response = await fetch(window.MKGovAdmin.routes.notifications);
            const data = await response.json();
            
            if (data.unread_count > 0) {
                this.updateNotificationBadge(data.unread_count);
            }
        } catch (error) {
            console.error('Error checking notifications:', error);
        }
    }

    updateNotificationBadge(count) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline' : 'none';
        }
    }

    calculatePasswordStrength(password) {
        let score = 0;
        
        // Length
        if (password.length >= 8) score += 1;
        if (password.length >= 12) score += 1;
        
        // Character types
        if (/[a-z]/.test(password)) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;
        
        // Common patterns (negative score)
        if (/^(.)\1+$/.test(password)) score -= 2; // Repeated characters
        if (/^(012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/.test(password)) score -= 1;
        
        score = Math.max(0, Math.min(5, score));
        
        const levels = [
            { class: 'danger', label: 'Very Weak', percentage: 20 },
            { class: 'danger', label: 'Weak', percentage: 40 },
            { class: 'warning', label: 'Fair', percentage: 60 },
            { class: 'info', label: 'Good', percentage: 80 },
            { class: 'success', label: 'Strong', percentage: 100 }
        ];
        
        return levels[score] || levels[0];
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    showToast(message, type = 'info', duration = 5000) {
        const toastContainer = document.querySelector('.toast-container') || this.createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, { delay: duration });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '11';
        document.body.appendChild(container);
        return container;
    }

    // Utility methods
    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    formatDate(date, options = {}) {
        const defaultOptions = { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Intl.DateTimeFormat('en-US', { ...defaultOptions, ...options }).format(new Date(date));
    }

    formatCurrency(amount, currency = 'XAF') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: currency === 'XAF' ? 0 : 2
        }).format(amount);
    }

    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showToast('Copied to clipboard!', 'success', 2000);
        } catch (err) {
            console.error('Failed to copy: ', err);
            this.showToast('Failed to copy to clipboard', 'danger', 3000);
        }
    }

    downloadFile(url, filename) {
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.mkGovAdmin = new MKGovAdmin();
});

// Export for use in other modules
export default MKGovAdmin;