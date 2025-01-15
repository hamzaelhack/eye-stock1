<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !isDistributor()) {
    redirect('/');
}

if (!isset($_GET['invoice_number'])) {
    redirect('pos.php');
}

$db = new Database();
$pdo = $db->getConnection();
$invoice_number = $_GET['invoice_number'];
$distributor_id = $_SESSION['user_id'];

try {
    // Get sale and distributor information
    $query = "SELECT s.*, u.username as distributor_name
              FROM sales s
              JOIN users u ON s.distributor_id = u.id
              WHERE s.invoice_number = :invoice_number AND s.distributor_id = :distributor_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['invoice_number' => $invoice_number, 'distributor_id' => $distributor_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        redirect('pos.php');
    }

    // Get sale items
    $query = "SELECT si.*, p.name as product_name
              FROM sale_items si
              JOIN products p ON si.product_id = p.id
              WHERE si.sale_id = :sale_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['sale_id' => $sale['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Erreur de récupération des données: " . $e->getMessage());
}

function formatPriceDZD($price) {
    return number_format($price, 2, ',', ' ') . ' DA';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture <?= htmlspecialchars($invoice_number) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .print-content {
                padding: 20px;
            }
            @page {
                margin: 0.5cm;
            }
        }
        .invoice-box {
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            padding: 30px;
            border-radius: 8px;
            max-width: 800px;
            margin: auto;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Actions Bar -->
    <div class="fixed top-0 left-0 right-0 bg-white shadow-md p-4 z-50 no-print">
        <div class="container mx-auto flex justify-between items-center">
            <a href="invoices.php" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-2"></i>Retour
            </a>
            <div class="space-x-4">
                <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-print mr-2"></i>Imprimer
                </button>
            </div>
        </div>
    </div>

    <!-- Invoice Content -->
    <div class="container mx-auto py-20 px-4 print-content">
        <div class="invoice-box">
            <!-- Header -->
            <div class="border-b pb-8">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">FACTURE</h1>
                        <p class="text-gray-600 mt-2">N° <?= htmlspecialchars($invoice_number) ?></p>
                        <p class="text-gray-600">Date: <?= date('d/m/Y', strtotime($sale['created_at'])) ?></p>
                    </div>
                    <div class="text-right">
                        <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($sale['distributor_name']) ?></h2>
                    </div>
                </div>
            </div>

            <!-- Client Info -->
            <div class="py-8 border-b">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Information Client</h3>
                <p class="text-gray-700">
                    <strong>Nom:</strong> <?= htmlspecialchars($sale['customer_name']) ?>
                </p>
                <?php if (!empty($sale['customer_phone'])): ?>
                <p class="text-gray-700 mt-1">
                    <strong>Téléphone:</strong> <?= htmlspecialchars($sale['customer_phone']) ?>
                </p>
                <?php endif; ?>
            </div>

            <!-- Items Table -->
            <div class="py-8">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-3 text-gray-600">Produit</th>
                            <th class="text-right py-3 text-gray-600">Quantité</th>
                            <th class="text-right py-3 text-gray-600">Prix unitaire</th>
                            <th class="text-right py-3 text-gray-600">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr class="border-b">
                            <td class="py-4 text-gray-800"><?= htmlspecialchars($item['product_name']) ?></td>
                            <td class="py-4 text-right text-gray-800"><?= $item['quantity'] ?></td>
                            <td class="py-4 text-right text-gray-800"><?= formatPriceDZD($item['price']) ?></td>
                            <td class="py-4 text-right text-gray-800"><?= formatPriceDZD($item['price'] * $item['quantity']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="py-4 text-right font-bold">Total</td>
                            <td class="py-4 text-right font-bold"><?= formatPriceDZD($sale['total_amount']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer -->
            <div class="pt-8 border-t text-gray-600">
                <p class="mb-2"><strong>Conditions de paiement:</strong> Paiement à la livraison</p>
                <p class="text-center mt-8 font-semibold">Merci de votre confiance!</p>
            </div>
        </div>
    </div>

    <script>
        // Automatically open print dialog when download is requested
        <?php if (isset($_GET['download'])): ?>
        window.onload = function() {
            window.print();
        };
        <?php endif; ?>
    </script>
</body>
</html>
