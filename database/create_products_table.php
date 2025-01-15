<?php
require_once 'database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Create products table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            quantity INTEGER NOT NULL DEFAULT 0,
            image TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert sample products
    $sampleProducts = [
        [
            'name' => 'Lunettes de Soleil Classic',
            'description' => 'Lunettes de soleil classiques avec protection UV',
            'quantity' => 50,
            'image' => 'sunglasses1.jpg'
        ],
        [
            'name' => 'Lunettes de Vue Modern',
            'description' => 'Monture moderne pour lunettes de vue',
            'quantity' => 30,
            'image' => 'glasses1.jpg'
        ],
        [
            'name' => 'Lentilles de Contact',
            'description' => 'Lentilles de contact mensuelles',
            'quantity' => 100,
            'image' => 'contacts1.jpg'
        ]
    ];
    
    $stmt = $conn->prepare("
        INSERT OR IGNORE INTO products (name, description, quantity, image)
        VALUES (:name, :description, :quantity, :image)
    ");
    
    foreach ($sampleProducts as $product) {
        $stmt->execute($product);
    }
    
    echo "Products table created and sample data inserted successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
