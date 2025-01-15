<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$productId = $data['id'] ?? ''; // Changed from 'product_id' to 'id'

if (empty($productId)) {
    echo json_encode(['success' => false, 'message' => 'ID du produit requis']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get product image before deletion
    $stmt = $conn->prepare('SELECT image FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete product
    $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    
    // Delete image file if exists
    if ($product && $product['image']) {
        $imagePath = '../../uploads/products/' . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Produit supprimé avec succès'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la suppression du produit'
    ]);
}
