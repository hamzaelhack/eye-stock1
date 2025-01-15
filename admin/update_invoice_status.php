<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data && isset($data['request_ids']) && isset($data['invoice_number'])) {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Start transaction
            $conn->beginTransaction();
            
            // Update each request
            $stmt = $conn->prepare("
                UPDATE stock_requests 
                SET invoice_generated = 1,
                    invoice_number = ?
                WHERE id = ?
            ");
            
            foreach ($data['request_ids'] as $requestId) {
                $stmt->execute([$data['invoice_number'], $requestId]);
            }
            
            // Commit transaction
            $conn->commit();
            
            echo json_encode(['success' => true]);
            
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data format']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
