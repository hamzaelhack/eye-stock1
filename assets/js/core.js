document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const sidebar = document.querySelector('.sidebar-modern');
    
    if (mobileMenuButton && sidebar) {
        mobileMenuButton.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Profile Dropdown Toggle
    const profileButton = document.getElementById('profile-menu-button');
    const profileDropdown = document.getElementById('profile-dropdown');
    
    if (profileButton && profileDropdown) {
        profileButton.addEventListener('click', function() {
            profileDropdown.classList.toggle('hidden');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!profileButton.contains(event.target) && !profileDropdown.contains(event.target)) {
                profileDropdown.classList.add('hidden');
            }
        });
    }
    
    // Global Search
    const globalSearch = document.getElementById('global-search');
    const mobileSearch = document.getElementById('mobile-search');
    
    [globalSearch, mobileSearch].forEach(searchInput => {
        if (searchInput) {
            searchInput.addEventListener('input', debounce(function(e) {
                // Implement your search logic here
                console.log('Searching for:', e.target.value);
            }, 300));
        }
    });
    
    // Toast Notification System
    window.showToast = function(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-modern toast-${type} fade-in flex items-center gap-2`;
        
        const icon = document.createElement('i');
        icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        toast.appendChild(icon);
        
        const text = document.createElement('span');
        text.textContent = message;
        toast.appendChild(text);
        
        const toastContainer = document.getElementById('toast-container');
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.remove('fade-in');
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };
    
    // Loading Overlay System
    window.showLoading = function() {
        document.getElementById('loading-overlay').classList.remove('hidden');
    };
    
    window.hideLoading = function() {
        document.getElementById('loading-overlay').classList.add('hidden');
    };
    
    // Helper Functions
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Form Validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                
                // Show validation messages
                const invalidInputs = form.querySelectorAll(':invalid');
                invalidInputs.forEach(input => {
                    const errorMessage = input.getAttribute('data-error') || 'Ce champ est requis';
                    showInputError(input, errorMessage);
                });
            }
        });
    });
    
    function showInputError(input, message) {
        const errorDiv = input.nextElementSibling?.classList.contains('error-message') 
            ? input.nextElementSibling 
            : document.createElement('div');
            
        if (!input.nextElementSibling?.classList.contains('error-message')) {
            errorDiv.className = 'error-message text-sm text-red-600 mt-1';
            input.parentNode.insertBefore(errorDiv, input.nextSibling);
        }
        
        errorDiv.textContent = message;
        input.classList.add('border-red-500');
        
        input.addEventListener('input', function() {
            if (this.checkValidity()) {
                errorDiv.remove();
                this.classList.remove('border-red-500');
            }
        }, { once: true });
    }
    
    // Initialize Tooltips
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'absolute bg-gray-900 text-white text-sm rounded px-2 py-1 z-50';
            tooltip.textContent = this.getAttribute('data-tooltip');
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
            tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
            
            this.addEventListener('mouseleave', () => tooltip.remove(), { once: true });
        });
    });
});
