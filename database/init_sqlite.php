<?php

$dbPath = __DIR__ . '/inventory.sqlite';

try {
    // Create new SQLite database
    $db = new SQLite3($dbPath);
    
    // Create users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'user',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create distributors table
    $db->exec("
        CREATE TABLE IF NOT EXISTS distributors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            address TEXT,
            phone TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    // Create requests table
    $db->exec("
        CREATE TABLE IF NOT EXISTS requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            distributor_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (distributor_id) REFERENCES distributors(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ");
    
    // Insert default admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $db->exec("
        INSERT OR IGNORE INTO users (username, password, role) 
        VALUES ('admin', '$adminPassword', 'admin')
    ");
    
    // Insert sample distributors
    $distributorPassword = password_hash('dist123', PASSWORD_DEFAULT);
    $db->exec("
        INSERT OR IGNORE INTO users (username, password, role) 
        VALUES 
        ('distributor1', '$distributorPassword', 'distributor'),
        ('distributor2', '$distributorPassword', 'distributor'),
        ('distributor3', '$distributorPassword', 'distributor')
    ");
    
    // Link distributors to users
    $db->exec("
        INSERT OR IGNORE INTO distributors (user_id, name, address, phone)
        SELECT id, 
               'Distributeur ' || username, 
               'Adresse ' || id, 
               '0123456789' || id
        FROM users 
        WHERE role = 'distributor'
    ");
    
    echo "Database initialized successfully!\n";
    echo "Default admin credentials:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n\n";
    echo "Sample distributor credentials:\n";
    echo "Username: distributor1 (or distributor2, distributor3)\n";
    echo "Password: dist123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
