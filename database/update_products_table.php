<?php
require_once '../config/config.php';
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Add status column if it doesn't exist
    $conn->exec("ALTER TABLE products ADD COLUMN status VARCHAR(20) DEFAULT 'active' NOT NULL");
    
    echo "Successfully added status column to products table\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "Status column already exists\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
