<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/');
}

$db = new Database();
$pdo = $db->getConnection();

// Create categories table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch(PDOException $e) {
    die("Erreur de création de la table: " . $e->getMessage());
}

// Get all categories
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de récupération des catégories: " . $e->getMessage());
}

// Handle category deletion
if (isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        redirect('/admin/categories.php');
    } catch(PDOException $e) {
        die("Erreur de suppression: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eye-Stock - Gestion des Catégories</title>
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
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center mb-4 md:mb-0">
                    <i class="fas fa-tags mr-3 text-blue-600"></i>
                    Gestion des Catégories
                </h1>
                <a href="add_category.php" class="w-full md:w-auto px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter une catégorie
                </a>
            </div>

            <!-- Categories List -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-lg overflow-hidden">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nom
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Description
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date de création
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    Aucune catégorie trouvée
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($category['description'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            <?php 
                                            $created_at = isset($category['created_at']) ? $category['created_at'] : date('Y-m-d H:i:s');
                                            echo date('d/m/Y H:i', strtotime($created_at)); 
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="edit_category.php?id=<?php echo $category['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="" method="POST" class="inline-block" 
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" name="delete_category" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
