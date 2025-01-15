<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Récupérer les données de filtrage
$data = json_decode(file_get_contents('php://input'), true);
$search = $data['search'] ?? '';
$category = $data['category'] ?? '';
$stock = $data['stock'] ?? '';
$status = $data['status'] ?? '';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Construire la requête SQL
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];
    
    // Ajouter les conditions de filtrage
    if (!empty($search)) {
        $sql .= " AND (name LIKE ? OR description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    if (!empty($stock)) {
        switch ($stock) {
            case 'out':
                $sql .= " AND quantity <= 0";
                break;
            case 'low':
                $sql .= " AND quantity > 0 AND quantity < 10";
                break;
            case 'in':
                $sql .= " AND quantity >= 10";
                break;
        }
    }
    
    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    // Préparer et exécuter la requête
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour le tableau
    foreach ($products as &$product) {
        $product['price'] = number_format($product['price'], 2) . ' €';
        $product['image_url'] = !empty($product['image']) 
            ? '../uploads/products/' . $product['image'] 
            : '../assets/images/default-product.png';
            
        // Déterminer le statut du stock
        if ($product['quantity'] <= 0) {
            $product['stock_status'] = [
                'text' => 'Rupture',
                'class' => 'bg-red-100 text-red-800'
            ];
        } elseif ($product['quantity'] < 10) {
            $product['stock_status'] = [
                'text' => 'Faible',
                'class' => 'bg-yellow-100 text-yellow-800'
            ];
        } else {
            $product['stock_status'] = [
                'text' => 'En Stock',
                'class' => 'bg-green-100 text-green-800'
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'total' => count($products),
            'filtered' => count($products)
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des produits: ' . $e->getMessage()
    ]);
}
