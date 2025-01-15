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
    $requestIds = $data['request_ids'] ?? [];
    $invoiceNumber = $data['invoice_number'] ?? null;

    if (empty($requestIds) || !$invoiceNumber) {
        throw new Exception('Données invalides');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Début de la transaction
    $conn->beginTransaction();

    // Mettre à jour le statut des demandes
    $placeholders = str_repeat('?,', count($requestIds) - 1) . '?';
    $stmt = $conn->prepare("
        UPDATE stock_requests 
        SET invoice_generated = ?, 
            invoice_number = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id IN ($placeholders)
    ");

    $params = array_merge([1, $invoiceNumber], $requestIds);
    if (!$stmt->execute($params)) {
        throw new Exception('Erreur lors de la mise à jour des demandes');
    }

    // Valider la transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Facture générée avec succès'
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
