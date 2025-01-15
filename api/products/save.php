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
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les données du formulaire
    $productId = $_POST['product_id'] ?? null;
    $data = [
        'name' => $_POST['name'] ?? '',
        'category_id' => $_POST['category_id'] ?? '',
        'buy_price' => $_POST['buy_price'] ?? 0,
        'sell_price' => $_POST['sell_price'] ?? 0,
        'quantity' => $_POST['quantity'] ?? 0,
        'min_quantity' => $_POST['min_quantity'] ?? 5,
        'notes' => $_POST['notes'] ?? null
    ];

    // Validation des données
    if (empty($data['name']) || empty($data['category_id'])) {
        throw new Exception('Le nom et la catégorie sont obligatoires');
    }

    // Traitement de l'image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            throw new Exception('Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
        }

        if ($_FILES['image']['size'] > $maxSize) {
            throw new Exception('L\'image ne doit pas dépasser 5MB.');
        }

        // Créer le dossier s'il n'existe pas
        $uploadDir = '../../uploads/products/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $extension;
        $uploadPath = $uploadDir . $newFileName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            throw new Exception('Erreur lors du téléchargement de l\'image.');
        }

        $data['image'] = $newFileName;
    }

    if ($productId) {
        // Mise à jour d'un produit existant
        $sql = "UPDATE products SET 
                name = :name,
                category_id = :category_id,
                buy_price = :buy_price,
                sell_price = :sell_price,
                quantity = :quantity,
                min_quantity = :min_quantity,
                notes = :notes";
        
        if (isset($data['image'])) {
            $sql .= ", image = :image";
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id', $productId);
    } else {
        // Création d'un nouveau produit
        $columns = ['name', 'category_id', 'buy_price', 'sell_price', 'quantity', 'min_quantity', 'notes'];
        $values = [':name', ':category_id', ':buy_price', ':sell_price', ':quantity', ':min_quantity', ':notes'];
        
        if (isset($data['image'])) {
            $columns[] = 'image';
            $values[] = ':image';
        }
        
        $sql = "INSERT INTO products (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $values) . ")";
        
        $stmt = $conn->prepare($sql);
    }

    // Bind all parameters
    foreach ($data as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }

    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de l\'enregistrement du produit.');
    }

    echo json_encode([
        'success' => true,
        'message' => $productId ? 'Produit mis à jour avec succès' : 'Produit ajouté avec succès',
        'id' => $productId ?? $conn->lastInsertId()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
