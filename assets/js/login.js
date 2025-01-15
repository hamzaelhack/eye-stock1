document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const errorMessage = document.getElementById('error-message');
    const loadingSpinner = document.getElementById('loading-spinner');

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Hide error message and show loading spinner
        errorMessage.classList.add('hidden');
        loadingSpinner.classList.remove('hidden');
        
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('api/auth/login.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Add success animation
                showToast('Connexion réussie', 'success');
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            } else {
                showError(data.message || 'Erreur de connexion');
            }
        } catch (error) {
            showError('Une erreur est survenue. Veuillez réessayer.');
        } finally {
            loadingSpinner.classList.add('hidden');
        }
    });

    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.classList.remove('hidden');
        errorMessage.classList.add('fade-in');
    }

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
});
