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
    $oldCategory = $_POST['old_category'] ?? null;
    $newCategory = $_POST['category'] ?? null;

    if (empty($newCategory)) {
        throw new Exception('Le nom de la catégorie est requis');
    }

    // Vérifier si la catégorie existe déjà
    if (!$oldCategory) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE category = ?");
        $stmt->execute([$newCategory]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Cette catégorie existe déjà');
        }
    }

    // Début de la transaction
    $conn->beginTransaction();

    if ($oldCategory) {
        // Mise à jour de la catégorie dans la table categories
        $stmt = $conn->prepare("UPDATE categories SET category = ? WHERE category = ?");
        if (!$stmt->execute([$newCategory, $oldCategory])) {
            throw new Exception('Erreur lors de la mise à jour de la catégorie');
        }

        // Mise à jour de la catégorie dans la table products
        $stmt = $conn->prepare("UPDATE products SET category = ? WHERE category = ?");
        if (!$stmt->execute([$newCategory, $oldCategory])) {
            throw new Exception('Erreur lors de la mise à jour des produits');
        }
    } else {
        // Insertion de la nouvelle catégorie
        $stmt = $conn->prepare("INSERT INTO categories (category) VALUES (?)");
        if (!$stmt->execute([$newCategory])) {
            throw new Exception('Erreur lors de l\'ajout de la catégorie');
        }
    }

    // Valider la transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $oldCategory ? 'Catégorie mise à jour avec succès' : 'Catégorie ajoutée avec succès'
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
