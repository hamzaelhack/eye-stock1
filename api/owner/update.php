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
    $companyName = $_POST['company_name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $nif = $_POST['nif'] ?? '';
    $nic = $_POST['nic'] ?? '';
    $art = $_POST['art'] ?? '';
    $rc = $_POST['rc'] ?? '';

    if (empty($companyName)) {
        throw new Exception('Le nom de l\'entreprise est requis');
    }

    // Mettre à jour ou insérer les informations
    $stmt = $conn->prepare("
        UPDATE owner_info 
        SET company_name = ?,
            address = ?,
            phone = ?,
            email = ?,
            nif = ?,
            nic = ?,
            art = ?,
            rc = ?,
            updated_at = datetime('now')
        WHERE id = 1
    ");

    if (!$stmt->execute([$companyName, $address, $phone, $email, $nif, $nic, $art, $rc])) {
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
