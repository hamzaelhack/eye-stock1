<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !isDistributor()) {
    redirect('/');
}

// Create uploads directory if it doesn't exist
$uploadsDir = $_SERVER['DOCUMENT_ROOT'] . '/eye-stock/uploads/products';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

$db = new Database();
$pdo = $db->getConnection();

// Get all available products with categories
try {
    // First, let's get just the products
    $stmt = $pdo->prepare("SELECT * FROM products WHERE quantity > 0 ORDER BY name");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Then get categories separately
    try {
        // Drop the existing categories table and recreate it
        $pdo->exec("DROP TABLE IF EXISTS categories");
        
        // Create categories table with proper structure
        $pdo->exec("
            CREATE TABLE categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL
            )
        ");
        
        // Insert default categories
        $pdo->exec("
            INSERT INTO categories (name) VALUES 
            ('Lentilles'),
            ('Montures'),
            ('Accessoires')
        ");
        
        // Add category_id to products if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN category_id INTEGER DEFAULT 1");
        } catch(PDOException $e) {
            // Column might already exist, ignore error
        }
        
        // Add image column to products if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN image TEXT DEFAULT 'default.jpg'");
        } catch(PDOException $e) {
            // Column might already exist, ignore error
        }
        
        // Get categories
        $stmt = $pdo->prepare("SELECT id, name FROM categories");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Erreur lors de la création de la table categories: " . $e->getMessage());
    }

    // Now get category names for each product
    foreach ($products as &$product) {
        if (isset($product['category_id'])) {
            foreach ($categories as $category) {
                if ($category['id'] == $product['category_id']) {
                    $product['category_name'] = $category['name'] ?? 'Non catégorisé';
                    break;
                }
            }
        }
        if (!isset($product['category_name'])) {
            $product['category_name'] = 'Non catégorisé';
        }
    }
    unset($product); // Break the reference

} catch(PDOException $e) {
    die("Erreur lors de la récupération des produits: " . $e->getMessage());
}

// Create requests table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            distributor_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id),
            FOREIGN KEY (distributor_id) REFERENCES users(id)
        )
    ");
} catch(PDOException $e) {
    // Table might already exist, continue
}

// Handle product request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'], $_POST['quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $distributor_id = $_SESSION['user_id'];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Check if product exists and has enough quantity
        $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ? AND quantity >= ?");
        $stmt->execute([$product_id, $quantity]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product && $quantity > 0) {
            // Insert request
            $stmt = $pdo->prepare("
                INSERT INTO requests (product_id, distributor_id, quantity, status, created_at) 
                VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP)
            ");
            if ($stmt->execute([$product_id, $distributor_id, $quantity])) {
                $pdo->commit();
                $success = "Votre demande a été soumise avec succès.";
            } else {
                throw new PDOException("Erreur lors de l'insertion de la demande");
            }
        } else {
            $pdo->rollBack();
            $error = "Quantité invalide ou produit non disponible.";
        }
    } catch(PDOException $e) {
        $pdo->rollBack();
        $error = "Erreur lors de la soumission de la demande: " . $e->getMessage();
    }
}

// Get user's pending requests
try {
    $stmt = $pdo->prepare("
        SELECT r.*, p.name as product_name, p.quantity as available_quantity 
        FROM requests r 
        JOIN products p ON r.product_id = p.id 
        WHERE r.distributor_id = ? 
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des demandes: " . $e->getMessage();
    $pending_requests = [];
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eye-Stock - Demande de Produits</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-50">
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>

        <main class="flex-1 ml-64 p-8">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        Demande de Produits
                    </h1>
                    <p class="text-gray-600 mt-2">Sélectionnez les produits que vous souhaitez commander</p>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Request Form -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="category-filter">
                            Filtrer par Catégorie
                        </label>
                        <select id="category-filter" class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all">Toutes les Catégories</option><?php 
                            foreach ($categories as $category): 
                                $categoryId = htmlspecialchars($category['id'] ?? '');
                                $categoryName = htmlspecialchars($category['name'] ?? 'Non catégorisé');
                            ?><option value="<?php echo $categoryId; ?>"><?php echo $categoryName; ?></option><?php 
                            endforeach; 
                            ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card" data-category="<?php echo $product['category_id'] ?? 'all'; ?>">
                                <div class="border rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                                    <!-- Product Image -->
                                    <div class="aspect-w-16 aspect-h-9 relative">
                                        <?php
                                        $imagePath = '../uploads/products/' . ($product['image'] ?? '');
                                        $defaultImage = '../assets/images/no-image.png';
                                        
                                        // Check if image exists
                                        if (!empty($product['image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/eye-stock/uploads/products/' . $product['image'])) {
                                            $displayImage = $imagePath;
                                        } else {
                                            $displayImage = $defaultImage;
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($displayImage); ?>" 
                                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                                            class="object-cover w-full h-48 rounded-t-lg"
                                            onerror="this.src='<?php echo htmlspecialchars($defaultImage); ?>'">
                                    </div>
                                    <div class="p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 class="text-lg font-semibold text-gray-800">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </h3>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($product['category_name']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <p class="text-gray-600">
                                                <?php echo htmlspecialchars($product['description'] ?? ''); ?>
                                            </p>
                                            <div class="flex justify-between items-center mt-2">
                                                <p class="text-sm text-gray-500">
                                                    Stock disponible: <span class="font-semibold"><?php echo $product['quantity']; ?></span> unités
                                                </p>
                                                <?php if ($product['quantity'] < 10): ?>
                                                    <span class="text-xs text-red-600 font-medium">
                                                        Stock faible
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <form action="" method="POST" class="space-y-3">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <div class="flex items-center space-x-2">
                                                <div class="relative flex-1">
                                                    <input type="number" name="quantity" 
                                                        min="1" max="<?php echo $product['quantity']; ?>" 
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                        placeholder="Quantité"
                                                        required>
                                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                        <span class="text-gray-500 text-sm">unités</span>
                                                    </div>
                                                </div>
                                                <button type="submit" 
                                                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center">
                                                    <i class="fas fa-shopping-cart mr-2"></i>
                                                    Demander
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Requests -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Demandes Récentes</h2>
                    <?php if (empty($pending_requests)): ?>
                        <p class="text-gray-500 text-center py-4">Aucune demande récente</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Produit
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Quantité
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Statut
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($pending_requests as $request): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo htmlspecialchars($request['product_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo $request['quantity']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php echo $request['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                        ($request['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>">
                                                    <?php echo ucfirst($request['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('category-filter').addEventListener('change', function() {
            const selectedCategory = this.value;
            const productCards = document.querySelectorAll('.product-card');
            
            productCards.forEach(card => {
                if (selectedCategory === 'all' || card.dataset.category === selectedCategory) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
