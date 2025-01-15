<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$productId = $_POST['productId'] ?? '';
$name = $_POST['name'] ?? '';
$category = $_POST['category'] ?? '';
$buy_price = $_POST['buy_price'] ?? 0;
$sell_price = $_POST['sell_price'] ?? 0;
$quantity = $_POST['quantity'] ?? 0;
$notes = $_POST['notes'] ?? '';

if (empty($productId) || empty($name) || empty($category)) {
    echo json_encode(['success' => false, 'message' => 'Le nom et la catégorie sont obligatoires']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/products/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileInfo = pathinfo($_FILES['image']['name']);
        $extension = strtolower($fileInfo['extension']);
        
        // Vérifier l'extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Format d\'image non autorisé');
        }

        // Générer un nom unique
        $imageName = uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $imageName;

        // Get old image
        $stmt = $conn->prepare('SELECT image FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $oldProduct = $stmt->fetch(PDO::FETCH_ASSOC);

        // Delete old image if exists
        if ($oldProduct && $oldProduct['image']) {
            $oldImagePath = $uploadDir . $oldProduct['image'];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        // Upload new image
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            throw new Exception('Erreur lors de l\'upload de l\'image');
        }

        $imagePath = $imageName;
    }

    // Update product
    if ($imagePath !== null) {
        $stmt = $conn->prepare('
            UPDATE products 
            SET name = ?, category = ?, buy_price = ?, sell_price = ?, quantity = ?, notes = ?, image = ?
            WHERE id = ?
        ');
        $stmt->execute([$name, $category, $buy_price, $sell_price, $quantity, $notes, $imagePath, $productId]);
    } else {
        $stmt = $conn->prepare('
            UPDATE products 
            SET name = ?, category = ?, buy_price = ?, sell_price = ?, quantity = ?, notes = ?
            WHERE id = ?
        ');
        $stmt->execute([$name, $category, $buy_price, $sell_price, $quantity, $notes, $productId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Produit mis à jour avec succès'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour du produit: ' . $e->getMessage()
    ]);
}
