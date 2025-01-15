<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Récupérer les données du formulaire
    $data = [
        'category_id' => $_POST['category_id'] ?? '',
        'name' => $_POST['name'] ?? '',
        'buy_price' => $_POST['buy_price'] ?? 0,
        'sell_price' => $_POST['sell_price'] ?? 0,
        'quantity' => $_POST['quantity'] ?? 0,
        'min_quantity' => $_POST['min_quantity'] ?? 5,
        'notes' => $_POST['notes'] ?? '',
        'status' => 'active',
        'created_by' => getCurrentUserId(),
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Validation des données
    if (empty($data['name']) || empty($data['category_id'])) {
        throw new Exception('Le nom et la catégorie sont obligatoires');
    }

    if ($data['buy_price'] < 0 || $data['sell_price'] < 0) {
        throw new Exception('Les prix ne peuvent pas être négatifs');
    }

    if ($data['quantity'] < 0) {
        throw new Exception('La quantité ne peut pas être négative');
    }

    // Gérer l'upload de l'image
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/products/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileInfo = pathinfo($_FILES['image']['name']);
        $extension = strtolower($fileInfo['extension']);
        
        // Vérifier l'extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Format d\'image non autorisé');
        }

        // Générer un nom unique
        $imageName = uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $imageName;

        // Déplacer le fichier
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            throw new Exception('Erreur lors de l\'upload de l\'image');
        }

        $imagePath = $imageName;
    }

    // Préparer la requête SQL
    $sql = "INSERT INTO products (category_id, name, buy_price, sell_price, quantity, min_quantity, image, notes, status, created_by, created_at) 
            VALUES (:category_id, :name, :buy_price, :sell_price, :quantity, :min_quantity, :image, :notes, :status, :created_by, :created_at)";
    
    $stmt = $conn->prepare($sql);
    $data['image'] = $imagePath;
    
    // Exécuter la requête
    if (!$stmt->execute($data)) {
        throw new Exception('Erreur lors de l\'ajout du produit');
    }

    // Récupérer l'ID du produit inséré
    $productId = $conn->lastInsertId();

    // Retourner la réponse
    echo json_encode([
        'success' => true,
        'message' => 'Produit ajouté avec succès',
        'product_id' => $productId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
