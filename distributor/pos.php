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
              HAVING SUM(r.quantity) > 0
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
    <title>Eye-Stock - Point de Vente</title>
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
            .cart-section {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                z-index: 40;
                transform: translateY(100%);
                transition: 0.3s;
            }
            .cart-section.active {
                transform: translateY(0);
            }
            .cart-toggle {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 30;
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
            <a href="pos.php" class="flex items-center py-3 px-6 bg-blue-700 text-white">
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
            <a href="../logout.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800 mt-auto">
                <i class="fas fa-sign-out-alt mr-2"></i>
                Déconnexion
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content ml-64 flex flex-col md:flex-row h-screen">
        <!-- Products Section -->
        <div class="w-full md:w-2/3 p-4 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-sm p-4">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center mb-4">
                    <i class="fas fa-cash-register mr-3 text-blue-600"></i>
                    Point de Vente
                </h1>
                
                <!-- Search and Filter -->
                <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4 mb-4">
                    <div class="flex-1">
                        <input type="text" id="search" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="Rechercher un produit...">
                    </div>
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

                <!-- Products Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" 
                            data-category="<?php echo $product['category_id']; ?>"
                            data-id="<?php echo $product['id']; ?>"
                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                            data-price="<?php echo $product['sell_price']; ?>"
                            data-available="<?php echo $product['available_quantity']; ?>">
                            <div class="border rounded-lg p-4 cursor-pointer hover:shadow-md transition-shadow">
                                <div class="aspect-w-16 aspect-h-9 mb-4">
                                    <img src="<?php echo isset($product['image']) && file_exists('../uploads/products/' . $product['image']) 
                                        ? '../uploads/products/' . htmlspecialchars($product['image']) 
                                        : '../assets/images/no-image.png'; ?>" 
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        class="object-cover w-full h-32 rounded-lg">
                                </div>
                                <h3 class="font-semibold text-gray-800 mb-2">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h3>
                                <div class="flex justify-between items-center">
                                    <span class="text-green-600 font-medium price">
                                        <?php echo formatPriceDZD($product['sell_price']); ?>
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        Stock: <?php echo $product['available_quantity']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Cart Toggle Button (Mobile) -->
        <button id="cart-toggle" class="cart-toggle md:hidden fixed bottom-4 right-4 bg-blue-600 text-white p-4 rounded-full shadow-lg">
            <i class="fas fa-shopping-cart"></i>
            <span id="cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-6 h-6 rounded-full flex items-center justify-center">0</span>
        </button>

        <!-- Cart Section -->
        <div class="cart-section w-full md:w-1/3 bg-white border-l border-gray-200 flex flex-col">
            <div class="p-4 border-b border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Panier</h2>
                    <button id="close-cart" class="md:hidden text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Customer Info -->
                <div class="mb-4">
                    <input type="text" id="customer-name" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                        placeholder="Nom du client">
                </div>
            </div>

            <!-- Cart Items -->
            <div class="flex-1 p-4 overflow-y-auto">
                <div id="cart-items" class="space-y-4">
                    <!-- Cart items will be added here dynamically -->
                </div>
            </div>

            <!-- Cart Total -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-gray-600">Total:</span>
                    <span class="text-2xl font-bold text-blue-600 price" id="cart-total">0,00 DA</span>
                </div>
                
                <!-- Action Buttons -->
                <div class="grid grid-cols-2 gap-4">
                    <button id="clear-cart" 
                        class="px-4 py-3 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                        <i class="fas fa-trash-alt mr-2"></i>
                        Vider
                    </button>
                    <button id="complete-sale" 
                        class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-check mr-2"></i>
                        Valider
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Add to Cart Modal -->
    <div id="add-to-cart-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-xl font-semibold mb-4" id="modal-product-name"></h3>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Quantité disponible: <span id="modal-available-quantity"></span>
                </label>
                <input type="number" id="modal-quantity" min="1" value="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex justify-end space-x-4">
                <button id="modal-cancel" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Annuler
                </button>
                <button id="modal-add" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Ajouter au panier
                </button>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const productsGrid = document.getElementById('products-grid');
        const cartItems = document.getElementById('cart-items');
        const cartTotal = document.getElementById('cart-total');
        const modal = document.getElementById('add-to-cart-modal');
        const modalProductName = document.getElementById('modal-product-name');
        const modalAvailableQuantity = document.getElementById('modal-available-quantity');
        const modalQuantity = document.getElementById('modal-quantity');
        const modalAdd = document.getElementById('modal-add');
        const modalCancel = document.getElementById('modal-cancel');
        const clearCartBtn = document.getElementById('clear-cart');
        const completeSaleBtn = document.getElementById('complete-sale');
        const searchInput = document.getElementById('search');
        const categoryFilter = document.getElementById('category-filter');
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.querySelector('.mobile-sidebar');
        const cartToggle = document.getElementById('cart-toggle');
        const cartSection = document.querySelector('.cart-section');
        const closeCart = document.getElementById('close-cart');

        // Cart state
        let cart = [];
        let selectedProduct = null;

        // Mobile menu toggle
        mobileMenuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            sidebar.classList.toggle('active');
        });

        cartToggle.addEventListener('click', (e) => {
            e.preventDefault();
            cartSection.classList.add('active');
        });

        closeCart.addEventListener('click', (e) => {
            e.preventDefault();
            cartSection.classList.remove('active');
        });

        // Update cart count badge
        function updateCartCount() {
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cart-count').textContent = count;
        }

        // Format price in DZD
        function formatPrice(price) {
            return new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(price) + ' DA';
        }

        // Update cart total
        function updateCartTotal() {
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            cartTotal.textContent = formatPrice(total);
            updateCartCount();
        }

        // Render cart items
        function renderCart() {
            cartItems.innerHTML = cart.map((item, index) => `
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <h4 class="font-medium text-gray-800">${item.name}</h4>
                        <div class="text-sm text-gray-500">
                            ${formatPrice(item.price)} × ${item.quantity}
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="font-medium price">${formatPrice(item.price * item.quantity)}</span>
                        <button onclick="removeFromCart(${index})" class="text-red-500 hover:text-red-700 p-2">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            updateCartTotal();
        }

        // Add to cart
        function addToCart(product, quantity) {
            cart.push({
                id: product.dataset.id,
                name: product.dataset.name,
                price: parseFloat(product.dataset.price),
                quantity: quantity
            });
            renderCart();
            if (window.innerWidth < 768) {
                cartSection.classList.add('active');
            }
        }

        // Remove from cart
        function removeFromCart(index) {
            cart.splice(index, 1);
            renderCart();
        }

        // Show add to cart modal
        function showAddToCartModal(product) {
            selectedProduct = product;
            modalProductName.textContent = product.dataset.name;
            modalAvailableQuantity.textContent = product.dataset.available;
            modalQuantity.max = product.dataset.available;
            modalQuantity.value = 1;
            modal.classList.remove('hidden');
        }

        // Hide add to cart modal
        function hideAddToCartModal() {
            modal.classList.add('hidden');
            selectedProduct = null;
        }

        // Filter products
        function filterProducts() {
            const searchTerm = searchInput.value.toLowerCase();
            const categoryId = categoryFilter.value;
            
            document.querySelectorAll('.product-card').forEach(card => {
                const name = card.dataset.name.toLowerCase();
                const category = card.dataset.category;
                const matchesSearch = name.includes(searchTerm);
                const matchesCategory = categoryId === 'all' || category === categoryId;
                
                card.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
            });
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', () => {
            // Product click event
            productsGrid.addEventListener('click', (e) => {
                const productCard = e.target.closest('.product-card');
                if (productCard) {
                    e.preventDefault();
                    showAddToCartModal(productCard);
                }
            });

            // Modal events
            modalAdd.addEventListener('click', (e) => {
                e.preventDefault();
                const quantity = parseInt(modalQuantity.value);
                if (quantity > 0 && quantity <= selectedProduct.dataset.available) {
                    addToCart(selectedProduct, quantity);
                    hideAddToCartModal();
                }
            });

            modalCancel.addEventListener('click', (e) => {
                e.preventDefault();
                hideAddToCartModal();
            });

            // Cart events
            clearCartBtn.addEventListener('click', (e) => {
                e.preventDefault();
                cart = [];
                renderCart();
            });

            // Search and filter events
            searchInput.addEventListener('input', filterProducts);
            categoryFilter.addEventListener('change', filterProducts);

            // Complete sale event
            completeSaleBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                
                if (cart.length === 0) {
                    alert('Le panier est vide');
                    return;
                }

                const customerName = document.getElementById('customer-name').value.trim();

                if (!customerName) {
                    alert('Veuillez saisir le nom du client');
                    return;
                }

                // Disable button to prevent double submission
                completeSaleBtn.disabled = true;
                completeSaleBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Traitement...';

                const saleData = {
                    customer_name: customerName,
                    items: cart,
                    total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
                };

                try {
                    const response = await fetch('pos_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(saleData)
                    });

                    const result = await response.json();

                    if (response.ok && result.invoice_number) {
                        // Clear cart and form
                        cart = [];
                        renderCart();
                        document.getElementById('customer-name').value = '';
                        
                        // Close cart on mobile
                        if (window.innerWidth < 768) {
                            cartSection.classList.remove('active');
                        }
                        
                        // Redirect to invoice
                        window.location.href = `generate_invoice.php?invoice_number=${result.invoice_number}`;
                    } else {
                        throw new Error(result.error || 'Erreur lors de l\'enregistrement de la vente');
                    }
                } catch (error) {
                    alert(error.message);
                } finally {
                    // Re-enable button
                    completeSaleBtn.disabled = false;
                    completeSaleBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Valider';
                }
            });

            // Close modals when clicking outside
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    hideAddToCartModal();
                }
            });

            // Handle keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    hideAddToCartModal();
                    if (window.innerWidth < 768) {
                        cartSection.classList.remove('active');
                        sidebar.classList.remove('active');
                    }
                }
            });
        });
    </script>
</body>
</html>
