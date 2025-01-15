<?php
require_once '../config/config.php';
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/create_distributors_tables.sql');
    
    // Split SQL file into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $conn->exec($statement);
        }
    }
    
    echo "Tables updated successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
