<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Récupérer les données
$data = json_decode(file_get_contents('php://input'), true);
$productId = $data['product_id'] ?? null;
$field = $data['field'] ?? null;
$value = $data['value'] ?? null;

// Valider les données
if (!$productId || !$field || !isset($value)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

// Liste des champs autorisés
$allowedFields = ['name', 'price', 'quantity'];
if (!in_array($field, $allowedFields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Champ non autorisé']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Nettoyer et valider la valeur selon le type de champ
    switch ($field) {
        case 'price':
            $value = str_replace(['€', ' '], '', $value);
            if (!is_numeric($value) || $value < 0) {
                throw new Exception('Prix invalide');
            }
            break;
            
        case 'quantity':
            $value = (int)str_replace([' unités', ' unité'], '', $value);
            if ($value < 0) {
                throw new Exception('Quantité invalide');
            }
            break;
            
        case 'name':
            $value = trim($value);
            if (empty($value)) {
                throw new Exception('Nom invalide');
            }
            break;
    }
    
    // Mettre à jour le produit
    $sql = "UPDATE products SET $field = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$value, $productId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Produit mis à jour avec succès',
            'data' => [
                'field' => $field,
                'value' => $value,
                'formatted_value' => $field === 'price' 
                    ? number_format($value, 2) . ' €' 
                    : ($field === 'quantity' ? $value . ' unités' : $value)
            ]
        ]);
    } else {
        throw new Exception('Aucune modification effectuée');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
}
