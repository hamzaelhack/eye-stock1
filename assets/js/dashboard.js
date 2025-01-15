document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.querySelector('.search-bar');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Hover effects for buttons
    document.querySelectorAll('button').forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.classList.add('hover-scale');
        });
        
        button.addEventListener('mouseleave', function() {
            this.classList.remove('hover-scale');
        });
    });

    // Mobile responsive menu
    const menuButton = document.createElement('button');
    menuButton.className = 'fixed p-4 bg-gray-800 text-white rounded-r lg:hidden z-50';
    menuButton.style.top = '1rem';
    menuButton.innerHTML = '☰';
    
    document.body.appendChild(menuButton);
    
    menuButton.addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar-modern');
        sidebar.classList.toggle('active');
    });

    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Add loading animation for data fetching
    function showLoading() {
        const loading = document.createElement('div');
        loading.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        loading.id = 'loading-overlay';
        loading.innerHTML = '<div class="loading-spinner"></div>';
        document.body.appendChild(loading);
    }

    function hideLoading() {
        const loading = document.getElementById('loading-overlay');
        if (loading) {
            loading.remove();
        }
    }

    // Toast notification function
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-modern toast-${type} fade-in flex items-center gap-2`;
        
        const icon = document.createElement('i');
        icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        toast.appendChild(icon);
        
        const text = document.createElement('span');
        text.textContent = message;
        toast.appendChild(text);
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.remove('fade-in');
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }

    // Add click handlers for action buttons
    document.querySelectorAll('button').forEach(button => {
        if (button.querySelector('.fa-eye')) {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                const orderId = row.querySelector('td:first-child').textContent.replace('#', '');
                showToast('Affichage des détails de la commande #' + orderId);
                // Add your view order logic here
            });
        }
        
        if (button.querySelector('.fa-edit')) {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                const orderId = row.querySelector('td:first-child').textContent.replace('#', '');
                showToast('Modification de la commande #' + orderId);
                // Add your edit order logic here
            });
        }
    });
});
