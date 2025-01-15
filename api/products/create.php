<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$quantity = $_POST['quantity'] ?? 0;
$price = $_POST['price'] ?? 0;
$category = $_POST['category'] ?? '';

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Le nom du produit est requis']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare('
        INSERT INTO products (name, description, quantity, price, category) 
        VALUES (?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([$name, $description, $quantity, $price, $category]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Produit ajouté avec succès'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'ajout du produit'
    ]);
}
?>
