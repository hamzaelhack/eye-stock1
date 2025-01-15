<?php
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Vérifier si les colonnes existent déjà
    $result = $conn->query("PRAGMA table_info(users)");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    $existingColumns = array_column($columns, 'name');

    // Ajouter la colonne email si elle n'existe pas
    if (!in_array('email', $existingColumns)) {
        $conn->exec("ALTER TABLE users ADD COLUMN email TEXT");
    }

    // Ajouter la colonne phone si elle n'existe pas
    if (!in_array('phone', $existingColumns)) {
        $conn->exec("ALTER TABLE users ADD COLUMN phone TEXT");
    }

    // Mettre à jour les utilisateurs existants avec des valeurs par défaut
    $conn->exec("
        UPDATE users 
        SET email = username || '@eyestock.dz',
            phone = '+213 555 000000'
        WHERE email IS NULL OR phone IS NULL
    ");

    echo "Table users mise à jour avec succès!";

} catch (PDOException $e) {
    die("Erreur lors de la mise à jour de la table users: " . $e->getMessage());
}
?>
