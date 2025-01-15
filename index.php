<?php
require_once 'config/config.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/distributor/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eye-Stock - Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="min-h-screen gradient-primary flex items-center justify-center p-4">
    <div class="glass-effect w-full max-w-md p-8 rounded-2xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Eye-Stock</h1>
            <p class="text-gray-200">Système de gestion d'inventaire</p>
        </div>
        
        <form id="loginForm" class="space-y-6">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-user text-gray-400"></i>
                </div>
                <input type="text" name="username" required 
                    class="modern-input pl-10" 
                    placeholder="Nom d'utilisateur">
            </div>
            
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-lock text-gray-400"></i>
                </div>
                <input type="password" name="password" required 
                    class="modern-input pl-10" 
                    placeholder="Mot de passe">
            </div>
            
            <button type="submit" 
                class="btn-modern btn-primary-modern w-full flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i>
                <?php echo $LANG['login']; ?>
            </button>
        </form>
        
        <div id="error-message" class="mt-4 text-red-100 text-center hidden"></div>
        
        <!-- Loading Spinner -->
        <div id="loading-spinner" class="hidden mt-4 flex justify-center">
            <div class="loading-spinner"></div>
        </div>
    </div>
    
    <script src="assets/js/login.js"></script>
</body>
</html>
