<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set current page for navbar
$currentPage = 'dashboard';

// Verify if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Fetch quick statistics
try {
    // Products count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products");
    $stmt->execute();
    $productsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Low stock products count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE quantity <= 5");
    $stmt->execute();
    $lowStockCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Distributors count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM distributors");
    $stmt->execute();
    $distributorsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Pending requests count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM requests WHERE status = 'pending'");
    $stmt->execute();
    $pendingRequestsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des statistiques: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Eye Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .dashboard-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .section-card {
            height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            cursor: pointer;
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
        }
        .stats-card {
            --gradient-start: #4e73df;
            --gradient-end: #224abe;
        }
        .products-card {
            --gradient-start: #1cc88a;
            --gradient-end: #169a6b;
        }
        .distributors-card {
            --gradient-start: #36b9cc;
            --gradient-end: #258391;
        }
        .invoices-card {
            --gradient-start: #f6c23e;
            --gradient-end: #dda20a;
        }
        .requests-card {
            --gradient-start: #e74a3b;
            --gradient-end: #be2617;
        }
        .settings-card {
            --gradient-start: #858796;
            --gradient-end: #60616f;
        }
        .users-card {
            --gradient-start: #f39c12;
            --gradient-end: #c47d0e;
        }
        .categories-card {
            --gradient-start: #6f42c1;
            --gradient-end: #532e94;
        }
        .quick-stats {
            font-size: 2rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <h2 class="mb-4">Tableau de Bord</h2>

        <!-- Quick Stats Row -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card h-100 bg-primary text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Produits</div>
                                <div class="quick-stats"><?= number_format($productsCount) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-box-seam card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card h-100 bg-warning text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Stock Faible</div>
                                <div class="quick-stats"><?= number_format($lowStockCount) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-exclamation-triangle card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card h-100 bg-success text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Distributeurs</div>
                                <div class="quick-stats"><?= number_format($distributorsCount) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card h-100 bg-danger text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Demandes en Attente</div>
                                <div class="quick-stats"><?= number_format($pendingRequestsCount) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-clock-history card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Sections Grid -->
        <div class="row">
            <!-- Statistiques -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="reports.php" class="text-decoration-none">
                    <div class="card dashboard-card section-card stats-card">
                        <i class="bi bi-graph-up card-icon"></i>
                        <h4>Statistiques</h4>
                        <p class="mb-0">Rapports et analyses détaillés</p>
                    </div>
                </a>
            </div>

            <!-- Produits -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="products.php" class="text-decoration-none">
                    <div class="card dashboard-card section-card products-card">
                        <i class="bi bi-box-seam card-icon"></i>
                        <h4>Produits</h4>
                        <p class="mb-0">Gestion des produits et stock</p>
                    </div>
                </a>
            </div>

            <!-- Distributeurs -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="distributors.php" class="text-decoration-none">
                    <div class="card dashboard-card section-card distributors-card">
                        <i class="bi bi-people card-icon"></i>
                        <h4>Distributeurs</h4>
                        <p class="mb-0">Gestion des distributeurs</p>
                    </div>
                </a>
            </div>

            <!-- Factures -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="factures.php" class="text-decoration-none">
                    <div class="card dashboard-card section-card invoices-card">
                        <i class="bi bi-receipt card-icon"></i>
                        <h4>Factures</h4>
                        <p class="mb-0">Gestion des factures</p>
                    </div>
                </a>
            </div>

            <!-- http://localhost/eye-stock/login.phpandes -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="requests.php" class="text-decoration-none">
                    <div class="card dashboard-card section-card requests-card">
                        <i class="bi bi-card-checklist card-icon"></i>
                        <h4>Demandes</h4>
                        <p class="mb-0">Gestion des demandes</p>
                    </div>
                </a>
            </div>

            <!-- Catégories -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="categories.php" class="text-decoration-none">
                    <div class="card dashboard-card section-card categories-card">
                        <i class="bi bi-tags card-icon"></i>
                        <h4>Catégories</h4>
                        <p class="mb-0">Gestion des catégories</p>
                    </div>
                </a>
            </div>

            <!-- Utilisateurs -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="users.php" class="text-decoration-none">
                    <div class="card dashboard-card section-card users-card">
                        <i class="bi bi-person-gear card-icon"></i>
                        <h4>Utilisateurs</h4>
                        <p class="mb-0">Gestion des utilisateurs</p>
                    </div>
                </a>
            </div>

            <!-- Paramètres -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="settings.php" class="text-decoration-none">
                    <div class="card dashboard-card section-card settings-card">
                        <i class="bi bi-gear card-icon"></i>
                        <h4>Paramètres</h4>
                        <p class="mb-0">Configuration du système</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
