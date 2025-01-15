<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !isDistributor()) {
    redirect('/');
}

$db = new Database();
$pdo = $db->getConnection();

// Get distributor's ID
$distributor_id = $_SESSION['user_id'];

// Get total sales count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE distributor_id = ?");
$stmt->execute([$distributor_id]);
$total_sales = $stmt->fetchColumn();

// Get total sales amount
$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE distributor_id = ?");
$stmt->execute([$distributor_id]);
$total_amount = $stmt->fetchColumn() ?: 0;

// Get total products in stock
$stmt = $pdo->prepare("SELECT COUNT(*) FROM distributor_stock WHERE distributor_id = ? AND quantity > 0");
$stmt->execute([$distributor_id]);
$products_in_stock = $stmt->fetchColumn();

// Get out of stock products count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM distributor_stock WHERE distributor_id = ? AND quantity = 0");
$stmt->execute([$distributor_id]);
$out_of_stock_count = $stmt->fetchColumn();

// Get recent sales with their items
$stmt = $pdo->prepare("
    SELECT s.*, si.quantity, si.price, p.name as product_name 
    FROM sales s 
    JOIN sale_items si ON s.id = si.sale_id
    JOIN products p ON si.product_id = p.id 
    WHERE s.distributor_id = ? 
    ORDER BY s.created_at DESC 
    LIMIT 5
");
$stmt->execute([$distributor_id]);
$recent_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get low stock products (quantity less than 5)
$stmt = $pdo->prepare("
    SELECT ds.*, p.name as product_name 
    FROM distributor_stock ds 
    JOIN products p ON ds.product_id = p.id 
    WHERE ds.distributor_id = ? AND ds.quantity <= 5 
    ORDER BY ds.quantity ASC 
    LIMIT 5
");
$stmt->execute([$distributor_id]);
$low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eye-Stock - Tableau de Bord</title>
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
            <a href="dashboard.php" class="flex items-center py-3 px-6 bg-blue-700 text-white">
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
            <a href="stock_disb.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800">
                <i class="fas fa-box mr-2"></i>
                Stock
            </a>
            <a href="invoices.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800">
                <i class="fas fa-file-invoice mr-2"></i>
                Historique des Factures
            </a>

            <a href="../logout.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800 mt-auto">
                <i class="fas fa-sign-out-alt mr-2"></i>
                Déconnexion
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content ml-64 p-4 min-h-screen">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Total Sales Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Ventes Totales</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_sales); ?></p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Amount Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Montant Total</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_amount, 2); ?> DA</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Products in Stock Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Produits en Stock</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($products_in_stock); ?></p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <i class="fas fa-boxes text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Out of Stock Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Rupture de Stock</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($out_of_stock_count); ?></p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-full">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Sales and Low Stock -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Sales -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Ventes Récentes</h3>
                    <a href="pos.php" class="text-sm text-blue-600 hover:text-blue-800">
                        Voir tout <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <?php if (empty($recent_sales)): ?>
                    <p class="text-gray-500 text-center py-4">Aucune vente récente</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantité</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Prix</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_sales as $sale): ?>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right"><?php echo number_format($sale['quantity']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right"><?php echo number_format($sale['price'], 2); ?> DA</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Low Stock Products -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Stock Faible (<5)</h3>
                    <a href="stock_disb.php" class="text-sm text-blue-600 hover:text-blue-800">
                        Voir tout <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <?php if (empty($low_stock_products)): ?>
                    <p class="text-gray-500 text-center py-4">Aucun produit en stock faible</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_products as $product): ?>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right"><?php echo number_format($product['quantity']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
