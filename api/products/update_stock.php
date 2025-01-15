<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Vérifier l'authentification
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Récupérer les données
    $data = json_decode(file_get_contents('php://input'), true);
    
    $productId = $data['product_id'] ?? null;
    $type = $data['type'] ?? null; // 'in' ou 'out'
    $quantity = $data['quantity'] ?? null;
    $reason = $data['reason'] ?? null;
    $referenceId = $data['reference_id'] ?? null;
    $referenceType = $data['reference_type'] ?? null;

    if (!$productId || !$type || !$quantity) {
        throw new Exception('Données manquantes');
    }

    if (!in_array($type, ['in', 'out'])) {
        throw new Exception('Type de mouvement invalide');
    }

    // Début de la transaction
    $conn->beginTransaction();

    // Récupérer le produit actuel
    $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Produit non trouvé');
    }

    // Calculer la nouvelle quantité
    $quantityChange = $type === 'in' ? $quantity : -$quantity;
    $newQuantity = $product['quantity'] + $quantityChange;

    if ($newQuantity < 0) {
        throw new Exception('Stock insuffisant');
    }

    // Mettre à jour le stock
    $stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
    if (!$stmt->execute([$newQuantity, $productId])) {
        throw new Exception('Erreur lors de la mise à jour du stock');
    }

    // Enregistrer le mouvement
    $stmt = $conn->prepare("
        INSERT INTO stock_movements (
            product_id, type, quantity, reason, 
            reference_id, reference_type, user_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt->execute([
        $productId,
        $type,
        $quantity,
        $reason,
        $referenceId,
        $referenceType,
        $_SESSION['user_id'] ?? null
    ])) {
        throw new Exception('Erreur lors de l\'enregistrement du mouvement');
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
