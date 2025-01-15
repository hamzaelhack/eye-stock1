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
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['request_id']) || !isset($data['status'])) {
        throw new Exception('Données manquantes');
    }

    if (!in_array($data['status'], ['approved', 'rejected'])) {
        throw new Exception('Statut invalide');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Début de la transaction
    $conn->beginTransaction();

    // Récupérer les informations de la demande
    $stmt = $conn->prepare("
        SELECT r.*, p.quantity as current_stock
        FROM requests r
        JOIN products p ON r.product_id = p.id
        WHERE r.id = ? AND r.status = 'pending'
    ");
    $stmt->execute([$data['request_id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception('Demande non trouvée ou déjà traitée');
    }

    // Si la demande est approuvée, vérifier le stock
    if ($data['status'] === 'approved') {
        if ($request['quantity'] > $request['current_stock']) {
            throw new Exception('Stock insuffisant');
        }

        // Mettre à jour le stock
        $stmt = $conn->prepare("
            UPDATE products 
            SET quantity = quantity - ? 
            WHERE id = ?
        ");
        $stmt->execute([$request['quantity'], $request['product_id']]);

        // Enregistrer le mouvement de stock
        $stmt = $conn->prepare("
            INSERT INTO stock_movements (
                product_id, type, quantity, reason, 
                reference_id, reference_type, user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $request['product_id'],
            'out',
            $request['quantity'],
            'Demande approuvée #' . $request['id'],
            $request['id'],
            'request',
            $_SESSION['user_id']
        ]);
    }

    // Mettre à jour le statut de la demande
    $stmt = $conn->prepare("
        UPDATE requests 
        SET status = ? 
        WHERE id = ?
    ");
    $stmt->execute([$data['status'], $data['request_id']]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
