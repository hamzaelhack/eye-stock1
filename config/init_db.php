<?php
require_once 'config.php';
require_once 'database.php';

try {
    // Delete existing database file
    $dbFile = __DIR__ . '/../database/eye_stock.db';
    if (file_exists($dbFile)) {
        // Close any existing connections
        $db = null;
        $conn = null;
        
        // Force delete the file
        chmod($dbFile, 0777);
        if (!unlink($dbFile)) {
            throw new Exception("Impossible de supprimer la base de données existante.");
        }
    }

    // Wait a moment to ensure file is deleted
    sleep(1);

    // Create new database connection
    $db = new Database();
    $conn = $db->getConnection();

    // Enable foreign keys
    $conn->exec('PRAGMA foreign_keys = ON');

    // Drop tables if they exist
    $conn->exec("DROP TABLE IF EXISTS products");
    $conn->exec("DROP TABLE IF EXISTS users");

    // Create users table
    $conn->exec("CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create products table
    $conn->exec("CREATE TABLE products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category TEXT NOT NULL,
        name TEXT NOT NULL,
        buy_price DECIMAL(10,2) NOT NULL DEFAULT 0,
        sell_price DECIMAL(10,2) NOT NULL DEFAULT 0,
        quantity INTEGER NOT NULL DEFAULT 0,
        image TEXT,
        notes TEXT,
        status TEXT NOT NULL DEFAULT 'active',
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");

    // Insert default admin user
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(['admin', $hashedPassword, 'admin']);
    $adminId = $conn->lastInsertId();

    // Add sample products
    $sampleProducts = [
        ['Lunettes Classic', 'Lunettes de vue', 50.00, 150.00, 10, 'Monture classique en acétate', $adminId],
        ['Lunettes Sport', 'Lunettes de soleil', 45.00, 120.00, 15, 'Monture légère pour le sport', $adminId],
        ['Lentilles Journalières', 'Lentilles de contact', 15.00, 35.00, 100, 'Pack de 30 lentilles', $adminId]
    ];

    $stmt = $conn->prepare("INSERT INTO products (name, category, buy_price, sell_price, quantity, notes, created_by) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($sampleProducts as $product) {
        $stmt->execute($product);
    }

    echo "Base de données initialisée avec succès!";

} catch(Exception $e) {
    echo "Erreur d'initialisation de la base de données: " . $e->getMessage();
    // Delete database file if initialization failed
    if (file_exists($dbFile)) {
        @unlink($dbFile);
    }
} finally {
    // Close connections
    $stmt = null;
    $conn = null;
    $db = null;
}
