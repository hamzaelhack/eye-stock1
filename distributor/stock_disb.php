<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !isDistributor()) {
    redirect('/');
}

$db = new Database();
$pdo = $db->getConnection();
$distributor_id = $_SESSION['user_id'];

// Get distributor's approved products
try {
    $query = "SELECT 
                p.*, 
                SUM(r.quantity) as available_quantity
              FROM products p 
              INNER JOIN requests r ON p.id = r.product_id 
              WHERE r.distributor_id = :distributor_id 
              AND r.status = 'approved'
              GROUP BY p.id, p.name, p.description, p.image, p.quantity, p.sell_price
              ORDER BY p.name";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['distributor_id' => $distributor_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de récupération des produits: " . $e->getMessage());
}

// Get categories
try {
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $categories = [];
}

function formatPriceDZD($price) {
    return number_format($price, 2, ',', ' ') . ' DA';
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eye-Stock - Mon Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .price {
            font-feature-settings: "tnum";
            font-variant-numeric: tabular-nums;
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
        .product-card {
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
            <a href="pos.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800">
                <i class="fas fa-cash-register mr-2"></i>
                Point de Vente
            </a>
            <a href="request_stock.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800">
                <i class="fas fa-shopping-cart mr-2"></i>
                Demande de Produits
            </a>
            <a href="stock_disb.php" class="flex items-center py-3 px-6 bg-blue-700 text-white">
                <i class="fas fa-box mr-2"></i>
                Stock
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
                    <i class="fas fa-boxes mr-3 text-blue-600"></i>
                    Mon Stock
                </h1>
                <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4 w-full md:w-auto">
                    <input type="text" id="search" 
                        class="w-full md:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        placeholder="Rechercher un produit...">
                    <select id="category-filter" 
                        class="w-full md:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="all">Toutes les Catégories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-category="<?php echo $product['category_id']; ?>">
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="aspect-w-16 aspect-h-9 mb-4">
                                <img src="<?php echo isset($product['image']) && file_exists('../uploads/products/' . $product['image']) 
                                    ? '../uploads/products/' . htmlspecialchars($product['image']) 
                                    : '../assets/images/no-image.png'; ?>" 
                                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                                    class="object-cover w-full h-48 rounded-lg">
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h3>
                            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                <?php echo htmlspecialchars($product['description']); ?>
                            </p>
                            <div class="flex flex-col space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Prix de vente:</span>
                                    <span class="text-green-600 font-medium price">
                                        <?php echo formatPriceDZD($product['sell_price']); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Stock disponible:</span>
                                    <span class="<?php echo ($product['available_quantity'] > 0) ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                                        <?php echo $product['available_quantity']; ?> unités
                                    </span>
                                </div>
                                <button onclick="requestStock(<?php echo $product['id']; ?>)" 
                                    class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>
                                    Demander du stock
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Request Stock Modal -->
    <div id="request-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-xl font-semibold mb-4">Demander du Stock</h3>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="request-quantity">
                    Quantité à demander:
                </label>
                <input type="number" id="request-quantity" min="1" value="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex justify-end space-x-4">
                <button id="cancel-request" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Annuler
                </button>
                <button id="confirm-request" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Confirmer
                </button>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.querySelector('.mobile-sidebar');
        const searchInput = document.getElementById('search');
        const categoryFilter = document.getElementById('category-filter');
        const modal = document.getElementById('request-modal');
        const quantityInput = document.getElementById('request-quantity');
        const cancelButton = document.getElementById('cancel-request');
        const confirmButton = document.getElementById('confirm-request');

        let selectedProductId = null;

        // Mobile menu toggle
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
                    if (modal.classList.contains('hidden')) {
                        sidebar.classList.remove('active');
                    } else {
                        hideModal();
                    }
                }
            });
        });

        // Filter products
        function filterProducts() {
            const searchTerm = searchInput.value.toLowerCase();
            const categoryId = categoryFilter.value;
            
            document.querySelectorAll('.product-card').forEach(card => {
                const name = card.querySelector('h3').textContent.toLowerCase();
                const category = card.dataset.category;
                const matchesSearch = name.includes(searchTerm);
                const matchesCategory = categoryId === 'all' || category === categoryId;
                
                card.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
            });
        }

        searchInput.addEventListener('input', filterProducts);
        categoryFilter.addEventListener('change', filterProducts);

        // Modal functions
        function showModal() {
            modal.classList.remove('hidden');
            quantityInput.focus();
        }

        function hideModal() {
            modal.classList.add('hidden');
            selectedProductId = null;
            quantityInput.value = 1;
        }

        function requestStock(productId) {
            selectedProductId = productId;
            showModal();
        }

        // Modal event listeners
        cancelButton.addEventListener('click', (e) => {
            e.preventDefault();
            hideModal();
        });

        confirmButton.addEventListener('click', async (e) => {
            e.preventDefault();
            const quantity = parseInt(quantityInput.value);
            
            if (quantity < 1) {
                alert('Veuillez saisir une quantité valide');
                return;
            }

            try {
                const response = await fetch('request_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: selectedProductId,
                        quantity: quantity
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    alert('Demande envoyée avec succès');
                    hideModal();
                } else {
                    throw new Error(result.error || 'Erreur lors de l\'envoi de la demande');
                }
            } catch (error) {
                alert(error.message);
            }
        });

        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                hideModal();
            }
        });
    </script>
</body>
</html>
