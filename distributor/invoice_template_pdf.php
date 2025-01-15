<?php
// This template is used for PDF generation
$css = '
<style>
    body {
        font-family: DejaVu Sans, sans-serif;
        color: #1a202c;
        line-height: 1.5;
    }
    .invoice-header {
        padding: 20px;
        border-bottom: 1px solid #e2e8f0;
    }
    .invoice-header:after {
        content: "";
        display: table;
        clear: both;
    }
    .invoice-info {
        float: left;
        width: 50%;
    }
    .company-info {
        float: right;
        width: 50%;
        text-align: right;
    }
    .client-info {
        padding: 20px;
        background-color: #f7fafc;
        margin: 20px 0;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    th {
        background-color: #f7fafc;
        text-align: left;
        padding: 12px;
        font-weight: bold;
        color: #4a5568;
        font-size: 0.875rem;
        text-transform: uppercase;
    }
    td {
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
    }
    .text-right {
        text-align: right;
    }
    .total-section {
        margin-top: 20px;
        text-align: right;
    }
    .footer {
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
        font-size: 0.875rem;
        color: #4a5568;
    }
</style>
';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?= $css ?>
</head>
<body>
    <div class="invoice-header">
        <div class="invoice-info">
            <h1 style="font-size: 24px; color: #2d3748; margin-bottom: 10px;">FACTURE</h1>
            <p>N° <?= htmlspecialchars($invoice_number) ?></p>
            <p>Date: <?= date('d/m/Y', strtotime($sale['created_at'])) ?></p>
        </div>
        <div class="company-info">
            <h2 style="font-size: 20px; color: #2d3748; margin-bottom: 10px;"><?= htmlspecialchars($sale['distributor_name']) ?></h2>
        </div>
    </div>

    <div class="client-info">
        <h3 style="font-size: 16px; color: #2d3748; margin-bottom: 10px;">Information Client</h3>
        <p><strong>Nom:</strong> <?= htmlspecialchars($sale['client_name'] ?: 'Client non spécifié') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Produit</th>
                <th class="text-right" style="width: 20%;">Quantité</th>
                <th class="text-right" style="width: 20%;">Prix unitaire</th>
                <th class="text-right" style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="text-right"><?= $item['quantity'] ?></td>
                <td class="text-right"><?= formatPriceDZD($item['price']) ?></td>
                <td class="text-right"><?= formatPriceDZD($item['price'] * $item['quantity']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-section">
        <table style="width: 300px; margin-left: auto;">
            <tr>
                <td style="border: none;"><strong>Total</strong></td>
                <td style="border: none;" class="text-right">
                    <strong><?= formatPriceDZD($sale['total_amount']) ?></strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p><strong>Conditions de paiement:</strong> Paiement à la livraison</p>
        <p style="margin-top: 20px;">Merci de votre confiance!</p>
    </div>
</body>
</html>
