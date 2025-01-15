<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !isDistributor()) {
    http_response_code(403);
    exit(json_encode(['error' => 'Accès non autorisé']));
}

$db = new Database();
$pdo = $db->getConnection();
$distributor_id = $_SESSION['user_id'];

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    exit(json_encode(['error' => 'Données invalides']));
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Verify stock availability first
    foreach ($data['items'] as $item) {
        $query = "SELECT SUM(quantity) as available_quantity 
                 FROM requests 
                 WHERE distributor_id = :distributor_id 
                 AND product_id = :product_id 
                 AND status = 'approved'";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'distributor_id' => $distributor_id,
            'product_id' => $item['id']
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || $result['available_quantity'] < $item['quantity']) {
            throw new Exception("Stock insuffisant pour le produit ID: " . $item['id']);
        }
    }

    // Create sale record
    $invoice_number = 'INV-' . date('Ymd') . '-' . uniqid();
    
    $query = "INSERT INTO sales (distributor_id, customer_name, total_amount, invoice_number, created_at) 
              VALUES (:distributor_id, :customer_name, :total_amount, :invoice_number, datetime('now'))";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'distributor_id' => $distributor_id,
        'customer_name' => $data['customer_name'],
        'total_amount' => $data['total'],
        'invoice_number' => $invoice_number
    ]);
    
    $sale_id = $pdo->lastInsertId();

    // Insert sale items and update stock
    foreach ($data['items'] as $item) {
        // Insert sale item
        $query = "INSERT INTO sale_items (sale_id, product_id, quantity, price) 
                  VALUES (:sale_id, :product_id, :quantity, :price)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'sale_id' => $sale_id,
            'product_id' => $item['id'],
            'quantity' => $item['quantity'],
            'price' => $item['price']
        ]);

        // First get the request with available quantity
        $query = "SELECT id, quantity FROM requests 
                 WHERE distributor_id = :distributor_id 
                 AND product_id = :product_id 
                 AND status = 'approved' 
                 AND quantity > 0 
                 ORDER BY created_at ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'distributor_id' => $distributor_id,
            'product_id' => $item['id']
        ]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($request) {
            // Then update that specific request
            $query = "UPDATE requests 
                     SET quantity = quantity - :sold_quantity 
                     WHERE id = :request_id 
                     AND quantity >= :sold_quantity";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'sold_quantity' => $item['quantity'],
                'request_id' => $request['id']
            ]);
        }
    }

    // Commit transaction
    $pdo->commit();

    // Return invoice number for redirection
    header('Content-Type: application/json');
    echo json_encode(['invoice_number' => $invoice_number]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("Sale error: " . $e->getMessage());
    exit(json_encode(['error' => 'Erreur lors de l\'enregistrement de la vente: ' . $e->getMessage()]));
}
