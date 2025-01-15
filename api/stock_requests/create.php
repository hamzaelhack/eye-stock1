<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Vérifier l'authentification
if (!isLoggedIn() || !isDistributor()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

try {
    // Récupérer les données
    $productId = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    $notes = $_POST['notes'] ?? '';

    if (!$productId || !$quantity || $quantity <= 0) {
        throw new Exception('Données invalides');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Vérifier si le produit existe et a assez de stock
    $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ? AND quantity > 0");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Produit non disponible');
    }

    if ($quantity > $product['quantity']) {
        throw new Exception('Quantité demandée supérieure au stock disponible');
    }

    // Créer la demande
    $stmt = $conn->prepare("
        INSERT INTO stock_requests (user_id, product_id, quantity, notes)
        VALUES (?, ?, ?, ?)
    ");

    if (!$stmt->execute([$_SESSION['user_id'], $productId, $quantity, $notes])) {
        throw new Exception('Erreur lors de la création de la demande');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Demande créée avec succès'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
