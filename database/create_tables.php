<?php
require_once '../config/config.php';
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/create_distributors_tables.sql');
    $conn->exec($sql);

    echo "Tables created successfully!";
} catch(PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
