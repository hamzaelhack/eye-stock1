<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    
    if ($productId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid product ID']);
        exit;
    }

    try {
        // Get product details
        $stmt = $conn->prepare("
            SELECT name, quantity 
            FROM products 
            WHERE id = ?
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            exit;
        }

        // Get stock movements
        $stmt = $conn->prepare("
            SELECT 
                sm.*,
                u.username as user_name,
                CASE 
                    WHEN sm.reference_type = 'request' THEN (
                        SELECT r.distributor_name 
                        FROM requests r 
                        WHERE r.id = sm.reference_id
                    )
                    ELSE NULL 
                END as distributor_name
            FROM stock_movements sm
            LEFT JOIN users u ON sm.user_id = u.id
            WHERE sm.product_id = ?
            ORDER BY sm.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$productId]);
        $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'product' => $product,
            'movements' => $movements
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Record stock movement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required = ['product_id', 'type', 'quantity'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit;
        }
    }

    try {
        $conn->beginTransaction();

        // Insert stock movement
        $stmt = $conn->prepare("
            INSERT INTO stock_movements (
                product_id, type, quantity, reason, 
                reference_id, reference_type, user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['product_id'],
            $data['type'],
            $data['quantity'],
            $data['reason'] ?? null,
            $data['reference_id'] ?? null,
            $data['reference_type'] ?? null,
            $_SESSION['user_id'] ?? null
        ]);

        // Update product quantity
        $stmt = $conn->prepare("
            UPDATE products 
            SET quantity = quantity + ? 
            WHERE id = ?
        ");
        $quantity = $data['type'] === 'in' ? $data['quantity'] : -$data['quantity'];
        $stmt->execute([$quantity, $data['product_id']]);

        $conn->commit();
        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
