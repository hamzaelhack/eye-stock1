<?php
require_once '../config/config.php';
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // إضافة فئات تجريبية
    $categories = [
        ['Lentilles', 'Lentilles de contact'],
        ['Montures', 'Montures de lunettes'],
        ['Verres', 'Verres correcteurs'],
        ['Accessoires', 'Accessoires pour lunettes']
    ];

    foreach ($categories as $cat) {
        try {
            $stmt = $conn->prepare("INSERT INTO categories (category, description) VALUES (?, ?)");
            $stmt->execute($cat);
            echo "Ajout de la catégorie: {$cat[0]}<br>";
        } catch (PDOException $e) {
            if (!strpos($e->getMessage(), 'UNIQUE constraint failed')) {
                throw $e;
            }
        }
    }

    // إضافة منتجات تجريبية
    $products = [
        ['Lentilles', 'Lentilles Journalières', 'Lentilles de contact à usage quotidien', 15.00, 25.00, 100, 20],
        ['Lentilles', 'Lentilles Mensuelles', 'Lentilles de contact à remplacement mensuel', 30.00, 45.00, 50, 10],
        ['Montures', 'Ray-Ban Classic', 'Monture Ray-Ban classique', 80.00, 150.00, 20, 5],
        ['Montures', 'Oakley Sport', 'Monture Oakley pour le sport', 100.00, 180.00, 15, 3],
        ['Verres', 'Verre Unifocal', 'Verre correcteur unifocal', 40.00, 70.00, 30, 10],
        ['Verres', 'Verre Progressif', 'Verre correcteur progressif', 120.00, 200.00, 25, 8],
        ['Accessoires', 'Étui à Lunettes', 'Étui de protection pour lunettes', 5.00, 12.00, 200, 50],
        ['Accessoires', 'Spray Nettoyant', 'Spray nettoyant pour verres', 3.00, 8.00, 150, 30]
    ];

    foreach ($products as $prod) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO products (category, name, description, buy_price, sell_price, quantity, min_quantity) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute($prod);
            echo "Ajout du produit: {$prod[1]}<br>";
        } catch (PDOException $e) {
            echo "Erreur lors de l'ajout du produit {$prod[1]}: " . $e->getMessage() . "<br>";
        }
    }

    // إضافة معلومات الشركة
    try {
        $stmt = $conn->prepare("
            INSERT INTO owner_info (
                company_name, address, phone, email, 
                nif, nic, art, rc
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ownerData = [
            'Eye Stock SARL',
            '123 Rue des Opticiens, Alger',
            '+213 555 123 456',
            'contact@eyestock.dz',
            '123456789',
            '987654321',
            '12345678912345',
            'RC-12345-B-12'
        ];
        $stmt->execute($ownerData);
        echo "Ajout des informations de l'entreprise<br>";
    } catch (PDOException $e) {
        echo "Erreur lors de l'ajout des informations de l'entreprise: " . $e->getMessage() . "<br>";
    }

    echo "<br>Initialisation terminée avec succès!";

} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
