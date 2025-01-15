<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/');
}

$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error = 'Le nom de la catégorie est requis';
    } else {
        try {
            // Check if category already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Cette catégorie existe déjà';
            } else {
                // Insert new category
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                if ($stmt->execute([$name])) {
                    $success = 'Catégorie ajoutée avec succès';
                    // Clear form data after successful submission
                    $name = '';
                } else {
                    $error = 'Erreur lors de l\'ajout de la catégorie';
                }
            }
        } catch (PDOException $e) {
            $error = 'Erreur de base de données: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eye-Stock - Ajouter une Catégorie</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        @media (max-width: 768px) {
            .mobile-sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                z-index: 50;
                transition: 0.3s;
            }
            .mobile-sidebar.active {
                left: 0;
            }
            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Mobile Menu Toggle -->
    <button id="mobile-menu-toggle" class="fixed top-4 left-4 z-50 p-2 bg-blue-600 text-white rounded-lg md:hidden">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <aside class="mobile-sidebar md:fixed md:left-0 md:w-64 md:h-screen bg-gradient-to-b from-blue-900 to-blue-800">
        <div class="p-6">
            <h2 class="text-2xl font-bold text-white flex items-center">
                <i class="fas fa-eye mr-2"></i>
                Eye-Stock
            </h2>
        </div>
        <nav class="mt-6">
            <a href="dashboard.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800">
                <i class="fas fa-tachometer-alt mr-2"></i>
                Tableau de bord
            </a>
            <a href="products.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800">
                <i class="fas fa-box mr-2"></i>
                Produits
            </a>
            <a href="categories.php" class="flex items-center py-3 px-6 bg-blue-700 text-white">
                <i class="fas fa-tags mr-2"></i>
                Catégories
            </a>
            <a href="distributors.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800">
                <i class="fas fa-users mr-2"></i>
                Distributeurs
            </a>
            <a href="../logout.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800 mt-auto">
                <i class="fas fa-sign-out-alt mr-2"></i>
                Déconnexion
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content ml-64 p-4 min-h-screen">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-plus-circle mr-3 text-blue-600"></i>
                        Ajouter une Catégorie
                    </h1>
                    <a href="categories.php" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Retour
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Nom de la catégorie <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="<?php echo htmlspecialchars($name ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4">
                        <a href="categories.php" 
                           class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Annuler
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Ajouter la catégorie
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.querySelector('.mobile-sidebar');

        document.addEventListener('DOMContentLoaded', () => {
            mobileMenuToggle.addEventListener('click', (e) => {
                e.preventDefault();
                sidebar.classList.toggle('active');
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', (e) => {
                if (window.innerWidth < 768 && 
                    !e.target.closest('.mobile-sidebar') && 
                    !e.target.closest('#mobile-menu-toggle') &&
                    sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });

            // Handle keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    sidebar.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
