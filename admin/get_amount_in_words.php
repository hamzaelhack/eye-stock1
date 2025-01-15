<?php
require_once '../includes/number_to_french.php';

if (isset($_GET['amount'])) {
    $amount = floatval($_GET['amount']);
    echo numberToFrench($amount);
} else {
    echo "Montant non spécifié";
}
?>
