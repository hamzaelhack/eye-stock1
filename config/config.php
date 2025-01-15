<?php
session_start();

define('BASE_PATH', dirname(__DIR__));
define('SITE_URL', 'http://localhost/eye-stock');

// French language translations
$LANG = [
    'dashboard' => 'Tableau de bord',
    'products' => 'Produits',
    'orders' => 'Commandes',
    'users' => 'Utilisateurs',
    'inventory' => 'Inventaire',
    'login' => 'Connexion',
    'logout' => 'Déconnexion',
    'welcome' => 'Bienvenue',
    'add_product' => 'Ajouter un produit',
    'edit_product' => 'Modifier le produit',
    'delete_product' => 'Supprimer le produit',
    'product_name' => 'Nom du produit',
    'product_description' => 'Description',
    'product_price' => 'Prix',
    'product_quantity' => 'Quantité',
    'product_category' => 'Catégorie',
    'save' => 'Enregistrer',
    'cancel' => 'Annuler',
    'admin_panel' => 'Panneau administrateur',
    'distributor_panel' => 'Panneau distributeur'
];

function redirect($path) {
    header("Location: " . SITE_URL . $path);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isDistributor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'distributor';
}
?>
