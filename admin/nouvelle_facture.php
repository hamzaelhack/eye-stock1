<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set current page for navbar
$currentPage = 'factures';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer la liste des distributeurs
$distributeurs = $conn->query("
    SELECT 
        d.id, 
        d.name as company_name, 
        d.address, 
        u.username,
        d.phone,
        d.created_at
    FROM distributors d
    JOIN users u ON d.user_id = u.id
    ORDER BY d.name
")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la liste des produits
$produits = $conn->query("
    SELECT id, name, sell_price, quantity 
    FROM products 
    WHERE quantity > 0 
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

// Get owner info
$ownerInfo = $conn->query("SELECT * FROM owner_info LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Si aucune information n'existe, créer une entrée par défaut
if (!$ownerInfo) {
    $conn->exec("
        INSERT INTO owner_info (
            company_name, address, phone, email, 
            nif, nic, art, rc
        ) VALUES (
            'Eye Stock SARL',
            '123 Rue des Opticiens, Alger',
            '+213 555 123 456',
            'contact@eyestock.dz',
            '123456789',
            '987654321',
            '12345678912345',
            'RC-12345-B-12'
        )
    ");
    $ownerInfo = $conn->query("SELECT * FROM owner_info LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Facture - Eye Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
        }
        .print-only {
            display: none;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Nouvelle Facture</h2>
            <a href="factures.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>

        <?php if (!$ownerInfo || empty($ownerInfo['nif'])): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                Veuillez configurer les informations de votre entreprise avant de générer des factures.
                <a href="owner_info.php" class="alert-link">Configurer maintenant</a>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form id="factureForm" class="needs-validation" novalidate>
                            <!-- Sélection du distributeur -->
                            <div class="mb-4">
                                <label class="form-label">Distributeur</label>
                                <select class="form-select" name="distributeur" required>
                                    <option value="">Sélectionner un distributeur</option>
                                    <?php foreach ($distributeurs as $dist): ?>
                                        <option value="<?= $dist['id'] ?>" 
                                                data-company="<?= htmlspecialchars($dist['company_name'] ?? $dist['username']) ?>"
                                                data-address="<?= htmlspecialchars($dist['address'] ?? '') ?>"
                                                data-nif="<?= htmlspecialchars($dist['nif'] ?? '') ?>"
                                                data-nic="<?= htmlspecialchars($dist['nic'] ?? '') ?>"
                                                data-art="<?= htmlspecialchars($dist['art'] ?? '') ?>">
                                            <?= htmlspecialchars($dist['company_name'] ?? $dist['username']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Liste des produits -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Produits</h5>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="ajouterLigne()">
                                        <i class="bi bi-plus"></i> Ajouter un produit
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="produitsTable">
                                        <thead>
                                            <tr>
                                                <th>Produit</th>
                                                <th>Quantité</th>
                                                <th>Prix unitaire</th>
                                                <th>Total HT</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Les lignes de produits seront ajoutées ici -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Total HT:</strong></td>
                                                <td><strong id="totalHT">0.00 DA</strong></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end">TVA (19%):</td>
                                                <td id="totalTVA">0.00 DA</td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Total TTC:</strong></td>
                                                <td><strong id="totalTTC">0.00 DA</strong></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-file-text"></i> Générer la facture
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template de facture pour l'impression -->
    <div id="invoiceTemplate" class="print-only">
        <!-- Le contenu de la facture sera injecté ici -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store products data
        const products = <?= json_encode($produits) ?>;
        const ownerInfo = <?= json_encode($ownerInfo) ?>;

        function ajouterLigne() {
            const tbody = document.querySelector('#produitsTable tbody');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <select class="form-select" onchange="updatePrix(this)" required>
                        <option value="">Sélectionner un produit</option>
                        ${products.map(p => `
                            <option value="${p.id}" 
                                    data-price="${p.sell_price}"
                                    data-max="${p.quantity}">
                                ${p.name}
                            </option>
                        `).join('')}
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control" min="1" value="1" 
                           onchange="updateTotal(this)" required>
                </td>
                <td class="prix">0.00 DA</td>
                <td class="total">0.00 DA</td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="supprimerLigne(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        }

        function updatePrix(select) {
            const tr = select.closest('tr');
            const option = select.selectedOptions[0];
            const prix = option.dataset.price;
            const max = option.dataset.max;
            
            tr.querySelector('input[type="number"]').max = max;
            tr.querySelector('.prix').textContent = Number(prix).toFixed(2) + ' DA';
            updateTotal(tr.querySelector('input[type="number"]'));
        }

        function updateTotal(input) {
            const tr = input.closest('tr');
            const prix = parseFloat(tr.querySelector('.prix').textContent);
            const quantite = parseInt(input.value);
            const total = prix * quantite;
            
            tr.querySelector('.total').textContent = total.toFixed(2) + ' DA';
            calculateTotals();
        }

        function supprimerLigne(button) {
            button.closest('tr').remove();
            calculateTotals();
        }

        function calculateTotals() {
            const totals = Array.from(document.querySelectorAll('.total'))
                .map(td => parseFloat(td.textContent))
                .reduce((sum, val) => sum + (isNaN(val) ? 0 : val), 0);

            document.getElementById('totalHT').textContent = totals.toFixed(2) + ' DA';
            document.getElementById('totalTVA').textContent = (totals * 0.19).toFixed(2) + ' DA';
            document.getElementById('totalTTC').textContent = (totals * 1.19).toFixed(2) + ' DA';
        }

        // Gestionnaire de soumission du formulaire
        document.getElementById('factureForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Récupérer les informations du distributeur
            const select = document.querySelector('select[name="distributeur"]');
            const option = select.selectedOptions[0];
            const distributeur = {
                id: select.value,
                company: option.dataset.company,
                address: option.dataset.address,
                nif: option.dataset.nif,
                nic: option.dataset.nic,
                art: option.dataset.art
            };

            // Récupérer les produits
            const produits = Array.from(document.querySelectorAll('#produitsTable tbody tr')).map(tr => ({
                id: tr.querySelector('select').value,
                name: tr.querySelector('select option:checked').text,
                quantity: parseInt(tr.querySelector('input').value),
                price: parseFloat(tr.querySelector('.prix').textContent),
                total: parseFloat(tr.querySelector('.total').textContent)
            }));

            // Générer la facture
            generateInvoice(distributeur, produits);
        });

        function generateInvoice(distributeur, produits) {
            // Générer un numéro de facture unique
            const invoiceNumber = 'FA' + new Date().getFullYear() + 
                                String(distributeur.id).padStart(4, '0') + 
                                Math.random().toString(36).substr(2, 5).toUpperCase();

            const totalHT = produits.reduce((sum, p) => sum + p.total, 0);
            const totalTVA = totalHT * 0.19;
            const totalTTC = totalHT * 1.19;

            // Créer le contenu de la facture
            const invoiceContent = `
                <div class="container my-5">
                    <div class="row mb-4">
                        <div class="col-6">
                            <h1 class="mb-3">FACTURE</h1>
                            <p><strong>N° ${invoiceNumber}</strong></p>
                            <p>Date: ${new Date().toLocaleDateString('fr-FR')}</p>
                        </div>
                        <div class="col-6 text-end">
                            <h2>${ownerInfo.company_name || 'Eye Stock'}</h2>
                            <p>${ownerInfo.address || ''}</p>
                            <p>Tél: ${ownerInfo.phone || ''}</p>
                            <p>Email: ${ownerInfo.email || ''}</p>
                            <p>NIF: ${ownerInfo.nif || ''}</p>
                            <p>RC: ${ownerInfo.rc || ''}</p>
                            <p>NIC: ${ownerInfo.nic || ''}</p>
                            <p>Art: ${ownerInfo.art || ''}</p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <h5>Client:</h5>
                            <p><strong>${distributeur.company}</strong></p>
                            <p>Adresse: ${distributeur.address}</p>
                            <p>NIF: ${distributeur.nif}</p>
                            <p>NIC: ${distributeur.nic}</p>
                            <p>Art.: ${distributeur.art}</p>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Désignation</th>
                                <th>Quantité</th>
                                <th>Prix unitaire HT</th>
                                <th>Total HT</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${produits.map(p => `
                                <tr>
                                    <td>${p.name}</td>
                                    <td>${p.quantity}</td>
                                    <td>${p.price.toFixed(2)} DA</td>
                                    <td>${p.total.toFixed(2)} DA</td>
                                </tr>
                            `).join('')}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total HT:</strong></td>
                                <td>${totalHT.toFixed(2)} DA</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">TVA (19%):</td>
                                <td>${totalTVA.toFixed(2)} DA</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total TTC:</strong></td>
                                <td><strong>${totalTTC.toFixed(2)} DA</strong></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="row mt-5">
                        <div class="col-12">
                            <p><strong>Arrêtée la présente facture à la somme de:</strong></p>
                            <p>${totalTTC.toFixed(2)} Dinars Algériens</p>
                        </div>
                    </div>

                    <div class="row mt-5">
                        <div class="col-6">
                            <p>Mode de paiement: _______________</p>
                        </div>
                        <div class="col-6 text-end">
                            <p>Signature et cachet</p>
                            <br><br><br>
                        </div>
                    </div>
                </div>
            `;

            // Injecter le contenu et imprimer
            document.getElementById('invoiceTemplate').innerHTML = invoiceContent;
            window.print();
        }
    </script>
</body>
</html>
