<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isDistributor()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$productId = $data['product_id'] ?? null;
$quantity = $data['quantity'] ?? null;

if (!$productId || !$quantity) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    // Update product quantity
    $stmt = $conn->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
    $stmt->execute([$quantity, $productId]);
    
    // Create restock record
    $stmt = $conn->prepare('INSERT INTO orders (user_id, status, total_amount) VALUES (?, ?, ?)');
    $stmt->execute([$_SESSION['user_id'], 'restock', 0]);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Réapprovisionnement effectué avec succès'
    ]);
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du réapprovisionnement'
    ]);
}
?>
