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
    $category = $data['category'] ?? null;

    if (empty($category)) {
        throw new Exception('Catégorie non spécifiée');
    }

    // Début de la transaction
    $conn->beginTransaction();

    // Mettre à jour les produits (définir category à NULL)
    $stmt = $conn->prepare("UPDATE products SET category = NULL WHERE category = ?");
    if (!$stmt->execute([$category])) {
        throw new Exception('Erreur lors de la suppression de la catégorie');
    }

    // Valider la transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Catégorie supprimée avec succès'
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
