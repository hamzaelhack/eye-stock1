<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/number_to_french.php';

// Set current page for navbar
$currentPage = 'factures';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get owner info
try {
    $stmt = $conn->query("SELECT * FROM owner_info LIMIT 1");
    $ownerInfo = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Create owner_info table if it doesn't exist
    $conn->exec("
        CREATE TABLE IF NOT EXISTS owner_info (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            company_name TEXT,
            address TEXT,
            phone TEXT,
            email TEXT,
            nif TEXT,
            nic TEXT,
            art TEXT,
            rc TEXT
        )
    ");
    $ownerInfo = null;
}

// Get requests grouped by distributor
try {
    $stmt = $conn->query("
        SELECT 
            sr.id as request_id,
            sr.quantity,
            sr.status,
            sr.invoice_generated,
            sr.invoice_number,
            p.name as product_name,
            p.sell_price,
            u.id as distributor_id,
            u.username,
            u.company_name,
            u.address,
            u.nif,
            u.nic,
            u.art
        FROM stock_requests sr
        JOIN products p ON sr.product_id = p.id
        JOIN users u ON sr.user_id = u.id
        WHERE sr.status = 'approved'
        ORDER BY u.id, sr.id
    ");
    
    $requestsByDistributor = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $distributorId = $row['distributor_id'];
        if (!isset($requestsByDistributor[$distributorId])) {
            $requestsByDistributor[$distributorId] = [
                'distributor' => [
                    'id' => $row['distributor_id'],
                    'username' => $row['username'],
                    'company' => $row['company_name'] ?? $row['username'],
                    'address' => $row['address'],
                    'nif' => $row['nif'],
                    'nic' => $row['nic'],
                    'art' => $row['art']
                ],
                'requests' => []
            ];
        }
        $requestsByDistributor[$distributorId]['requests'][] = $row;
    }
} catch (PDOException $e) {
    error_log("Error fetching requests: " . $e->getMessage());
    $requestsByDistributor = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Génération de Factures - Eye Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @media print {
            #mainContent, #navbar {
                display: none !important;
            }
            .print-section {
                display: block !important;
                margin: 0;
                padding: 2cm;
            }
            @page {
                size: A4;
                margin: 0;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }
        
        .print-section {
            display: none;
            background: white;
            font-size: 12pt;
            min-height: 29.7cm;
            width: 21cm;
            margin: 0 auto;
        }
        
        .invoice-header {
            border-bottom: 2px solid #000;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        
        .invoice-title {
            font-size: 24pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .client-info {
            border: 1px solid #000;
            padding: 15px;
            margin-bottom: 30px;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .invoice-table th,
        .invoice-table td {
            border: 1px solid #000;
            padding: 8px;
        }
        
        .amount-in-words {
            border: 1px solid #000;
            padding: 15px;
            margin-bottom: 30px;
            font-style: italic;
        }
        
        .signature-section {
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div id="navbar">
        <?php include '../includes/navbar.php'; ?>
    </div>

    <!-- Main Content -->
    <div id="mainContent" class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Génération de Factures</h2>
            <div class="d-flex gap-2">
                <a href="nouvelle_facture.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Nouvelle Facture
                </a>
                <a href="owner_info.php" class="btn btn-primary">
                    <i class="bi bi-gear"></i> Paramètres de l'entreprise
                </a>
            </div>
        </div>

        <?php if (!$ownerInfo || empty($ownerInfo['nif'])): ?>
            <div class="alert alert-warning mb-4">
                <i class="bi bi-exclamation-triangle"></i>
                Veuillez configurer les informations de votre entreprise avant de générer des factures.
                <a href="owner_info.php" class="alert-link">Configurer maintenant</a>
            </div>
        <?php endif; ?>

        <?php if (empty($requestsByDistributor)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                Aucune demande en attente de facturation.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($requestsByDistributor as $distributorId => $data): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($data['distributor']['company']) ?></h5>
                                <p class="card-text">
                                    <small class="text-muted">
                                        NIF: <?= htmlspecialchars($data['distributor']['nif']) ?><br>
                                        Demandes: <?= count($data['requests']) ?>
                                    </small>
                                </p>
                                <button onclick="generateInvoice(<?= htmlspecialchars(json_encode($data)) ?>)" 
                                        class="btn btn-primary">
                                    <i class="bi bi-file-text"></i> Générer la facture
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Print Section -->
    <div class="print-section">
        <!-- Invoice content will be injected here -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ownerInfo = <?= json_encode($ownerInfo ?? []); ?>;

        function generateInvoice(data) {
            const invoiceNumber = 'FA' + new Date().getFullYear() + 
                                String(data.distributor.id).padStart(4, '0') + 
                                Math.random().toString(36).substr(2, 5).toUpperCase();

            const totalHT = data.requests.reduce((sum, request) => 
                sum + (request.quantity * request.sell_price), 0);
            const totalTVA = totalHT * 0.19;
            const totalTTC = totalHT * 1.19;

            // Fetch amount in words
            fetch('get_amount_in_words.php?amount=' + totalTTC)
                .then(response => response.text())
                .then(amountInWords => {
                    const invoiceContent = `
                        <div class="container">
                            <div class="invoice-title">FACTURE</div>
                            
                            <div class="invoice-header">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>N° ${invoiceNumber}</strong><br>
                                        Date: ${new Date().toLocaleDateString('fr-FR')}
                                    </div>
                                    <div class="col-6 text-end">
                                        <strong>${ownerInfo.company_name || 'Eye Stock'}</strong><br>
                                        ${ownerInfo.address || ''}<br>
                                        Tél: ${ownerInfo.phone || ''}<br>
                                        Email: ${ownerInfo.email || ''}<br>
                                        NIF: ${ownerInfo.nif || ''}<br>
                                        RC: ${ownerInfo.rc || ''}<br>
                                        NIC: ${ownerInfo.nic || ''}<br>
                                        Art: ${ownerInfo.art || ''}
                                    </div>
                                </div>
                            </div>

                            <div class="client-info">
                                <h5>CLIENT</h5>
                                <strong>${data.distributor.company}</strong><br>
                                Adresse: ${data.distributor.address}<br>
                                NIF: ${data.distributor.nif}<br>
                                NIC: ${data.distributor.nic}<br>
                                Art.: ${data.distributor.art}
                            </div>

                            <table class="invoice-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40%">Désignation</th>
                                        <th style="width: 15%" class="text-center">Quantité</th>
                                        <th style="width: 20%" class="text-end">Prix unitaire HT</th>
                                        <th style="width: 25%" class="text-end">Total HT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.requests.map(request => `
                                        <tr>
                                            <td>${request.product_name}</td>
                                            <td class="text-center">${request.quantity}</td>
                                            <td class="text-end">${Number(request.sell_price).toFixed(2)} DA</td>
                                            <td class="text-end">${(request.quantity * request.sell_price).toFixed(2)} DA</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end">Total HT:</td>
                                        <td class="text-end">${totalHT.toFixed(2)} DA</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end">TVA (19%):</td>
                                        <td class="text-end">${totalTVA.toFixed(2)} DA</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total TTC:</strong></td>
                                        <td class="text-end"><strong>${totalTTC.toFixed(2)} DA</strong></td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div class="amount-in-words">
                                <strong>Arrêtée la présente facture à la somme de:</strong><br>
                                ${amountInWords} Dinars Algériens
                            </div>

                            <div class="signature-section">
                                <div class="row">
                                    <div class="col-6">
                                        Mode de paiement: _______________<br>
                                        Date: ${new Date().toLocaleDateString('fr-FR')}
                                    </div>
                                    <div class="col-6 text-end">
                                        <p>Signature et cachet</p>
                                        <br><br>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    const printSection = document.querySelector('.print-section');
                    printSection.innerHTML = invoiceContent;
                    printSection.style.display = 'block';

                    // Wait for content to load
                    setTimeout(() => {
                        window.print();
                        // Update invoice status after printing
                        updateInvoiceStatus(data.requests.map(r => r.request_id), invoiceNumber);
                    }, 500);
                });
        }

        function updateInvoiceStatus(requestIds, invoiceNumber) {
            fetch('update_invoice_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    request_ids: requestIds,
                    invoice_number: invoiceNumber
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
