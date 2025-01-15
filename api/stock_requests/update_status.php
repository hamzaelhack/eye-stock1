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
    // Récupérer les données
    $data = json_decode(file_get_contents('php://input'), true);
    $requestId = $data['request_id'] ?? null;
    $status = $data['status'] ?? null;

    if (!$requestId || !in_array($status, ['approved', 'rejected'])) {
        throw new Exception('Données invalides');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Début de la transaction
    $conn->beginTransaction();

    // Récupérer les informations de la demande
    $stmt = $conn->prepare("
        SELECT sr.*, p.quantity as current_stock
        FROM stock_requests sr
        JOIN products p ON sr.product_id = p.id
        WHERE sr.id = ? AND sr.status = 'pending'
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception('Demande non trouvée ou déjà traitée');
    }

    // Si approuvé, vérifier et mettre à jour le stock
    if ($status === 'approved') {
        if ($request['current_stock'] < $request['quantity']) {
            throw new Exception('Stock insuffisant pour approuver cette demande');
        }

        // Mettre à jour le stock
        $stmt = $conn->prepare("
            UPDATE products 
            SET quantity = quantity - ?
            WHERE id = ? AND quantity >= ?
        ");
        if (!$stmt->execute([$request['quantity'], $request['product_id'], $request['quantity']])) {
            throw new Exception('Erreur lors de la mise à jour du stock');
        }
    }

    // Mettre à jour le statut de la demande
    $stmt = $conn->prepare("UPDATE stock_requests SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    if (!$stmt->execute([$status, $requestId])) {
        throw new Exception('Erreur lors de la mise à jour du statut');
    }

    // Valider la transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour avec succès'
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
