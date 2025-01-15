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
    $distributorId = $_POST['distributor_id'] ?? null;
    $companyName = $_POST['company_name'] ?? '';
    $address = $_POST['address'] ?? '';
    $nif = $_POST['nif'] ?? '';
    $nic = $_POST['nic'] ?? '';
    $art = $_POST['art'] ?? '';

    if (!$distributorId) {
        throw new Exception('ID du distributeur manquant');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Vérifier si l'utilisateur existe et est un distributeur
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'distributor'");
    $stmt->execute([$distributorId]);
    if (!$stmt->fetch()) {
        throw new Exception('Distributeur non trouvé');
    }

    // Mettre à jour les informations
    $stmt = $conn->prepare("
        UPDATE users 
        SET company_name = ?,
            address = ?,
            nif = ?,
            nic = ?,
            art = ?
        WHERE id = ?
    ");

    if (!$stmt->execute([$companyName, $address, $nif, $nic, $art, $distributorId])) {
        throw new Exception('Erreur lors de la mise à jour des informations');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Informations mises à jour avec succès'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
