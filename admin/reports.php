<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set current page for navbar
$currentPage = 'reports';

// Verify if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get date range from request
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch statistics
try {
    // Products statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_products,
            SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock,
            SUM(quantity) as total_stock,
            SUM(quantity * sell_price) as stock_value
        FROM products
    ");
    $stmt->execute();
    $productsStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Top 5 products with low stock
    $stmt = $conn->prepare("
        SELECT name, quantity, sell_price
        FROM products
        WHERE quantity > 0
        ORDER BY quantity ASC
        LIMIT 5
    ");
    $stmt->execute();
    $lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Out of stock products
    $stmt = $conn->prepare("
        SELECT name, quantity, sell_price
        FROM products
        WHERE quantity <= 0
        ORDER BY name
    ");
    $stmt->execute();
    $outOfStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Distributor statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT d.id) as total_distributors,
            COUNT(DISTINCT r.id) as total_requests,
            COUNT(DISTINCT CASE WHEN r.status = 'pending' THEN r.id END) as pending_requests
        FROM distributors d
        LEFT JOIN requests r ON d.id = r.distributor_id
    ");
    $stmt->execute();
    $distributorStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Top 5 distributors by request count
    $stmt = $conn->prepare("
        SELECT 
            d.name,
            COUNT(r.id) as request_count,
            SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending_count
        FROM distributors d
        LEFT JOIN requests r ON d.id = r.distributor_id
        GROUP BY d.id, d.name
        ORDER BY request_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $topDistributors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent activity
    $stmt = $conn->prepare("
        SELECT 
            'request' as type,
            r.created_at,
            d.name as distributor_name,
            p.name as product_name,
            r.quantity,
            r.status
        FROM requests r
        JOIN distributors d ON r.distributor_id = d.id
        JOIN products p ON r.product_id = p.id
        WHERE r.created_at BETWEEN :start_date AND :end_date
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([
        ':start_date' => $startDate . ' 00:00:00',
        ':end_date' => $endDate . ' 23:59:59'
    ]);
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .activity-item {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .activity-item::before {
            content: '';
            position: absolute;
            left: -21px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #0d6efd;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h2 class="mb-4">Tableau de Bord</h2>
                
                <!-- Date Range Filter -->
                <form class="mb-4" method="GET">
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label class="col-form-label">Période :</label>
                        </div>
                        <div class="col-auto">
                            <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
                        </div>
                        <div class="col-auto">
                            <label class="col-form-label">à</label>
                        </div>
                        <div class="col-auto">
                            <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <!-- Products Stats -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card h-100 stat-card border-primary">
                    <div class="card-body">
                        <h5 class="card-title text-primary">Produits</h5>
                        <p class="card-text">
                            <i class="bi bi-box-seam"></i> Total: <?= number_format($productsStats['total_products']) ?><br>
                            <i class="bi bi-exclamation-triangle"></i> Rupture: <?= number_format($productsStats['out_of_stock']) ?><br>
                            <i class="bi bi-boxes"></i> Stock: <?= number_format($productsStats['total_stock']) ?><br>
                            <i class="bi bi-currency-dollar"></i> Valeur: <?= number_format($productsStats['stock_value'], 2) ?> DA
                        </p>
                    </div>
                </div>
            </div>

            <!-- Distributors Stats -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card h-100 stat-card border-success">
                    <div class="card-body">
                        <h5 class="card-title text-success">Distributeurs</h5>
                        <p class="card-text">
                            <i class="bi bi-people"></i> Total: <?= number_format($distributorStats['total_distributors']) ?><br>
                            <i class="bi bi-card-checklist"></i> Demandes: <?= number_format($distributorStats['total_requests']) ?><br>
                            <i class="bi bi-hourglass-split"></i> En attente: <?= number_format($distributorStats['pending_requests']) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card h-100 stat-card border-warning">
                    <div class="card-body">
                        <h5 class="card-title text-warning">Stock Faible</h5>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($lowStockProducts as $product): ?>
                                <li>
                                    <small>
                                        <?= htmlspecialchars($product['name']) ?>
                                        <span class="badge bg-warning text-dark"><?= $product['quantity'] ?></span>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Out of Stock Alert -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card h-100 stat-card border-danger">
                    <div class="card-body">
                        <h5 class="card-title text-danger">Rupture de Stock</h5>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($outOfStockProducts as $product): ?>
                                <li>
                                    <small>
                                        <?= htmlspecialchars($product['name']) ?>
                                        <span class="badge bg-danger">0</span>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <!-- Top Distributors -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top 5 Distributeurs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Distributeur</th>
                                        <th>Demandes</th>
                                        <th>En Attente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topDistributors as $distributor): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($distributor['name']) ?></td>
                                            <td><?= $distributor['request_count'] ?></td>
                                            <td>
                                                <?php if ($distributor['pending_count'] > 0): ?>
                                                    <span class="badge bg-warning">
                                                        <?= $distributor['pending_count'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">0</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Activité Récente</h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-timeline">
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="activity-item">
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                    </small>
                                    <div>
                                        <strong><?= htmlspecialchars($activity['distributor_name']) ?></strong>
                                        a demandé <?= $activity['quantity'] ?> 
                                        <?= htmlspecialchars($activity['product_name']) ?>
                                        <span class="badge bg-<?= $activity['status'] === 'pending' ? 'warning' : 'success' ?>">
                                            <?= $activity['status'] === 'pending' ? 'En attente' : 'Validé' ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="row">
            <div class="col">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary" onclick="exportToPDF()">
                        <i class="bi bi-file-pdf"></i> Exporter en PDF
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="exportToExcel()">
                        <i class="bi bi-file-excel"></i> Exporter en Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jsPDF for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- SheetJS for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        // PDF Export
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add content to PDF
            doc.setFontSize(20);
            doc.text('Rapport Eye Stock', 20, 20);
            
            doc.setFontSize(12);
            doc.text(`Période: ${document.querySelector('[name="start_date"]').value} à ${document.querySelector('[name="end_date"]').value}`, 20, 30);
            
            // Add statistics
            doc.setFontSize(16);
            doc.text('Statistiques', 20, 50);
            
            doc.setFontSize(12);
            let y = 60;
            document.querySelectorAll('.stat-card').forEach(card => {
                const title = card.querySelector('.card-title').textContent;
                const stats = card.querySelector('.card-text').textContent.replace(/\n/g, ', ');
                doc.text(`${title}: ${stats}`, 20, y);
                y += 10;
            });
            
            // Save the PDF
            doc.save('eye-stock-report.pdf');
        }

        // Excel Export
        function exportToExcel() {
            const wb = XLSX.utils.book_new();
            
            // Create statistics worksheet
            const statsData = [];
            document.querySelectorAll('.stat-card').forEach(card => {
                const title = card.querySelector('.card-title').textContent;
                const stats = card.querySelector('.card-text').textContent.split('\n').map(s => s.trim());
                statsData.push([title, ...stats]);
            });
            const statsWS = XLSX.utils.aoa_to_sheet(statsData);
            XLSX.utils.book_append_sheet(wb, statsWS, "Statistiques");
            
            // Create top distributors worksheet
            const distributorsData = [['Distributeur', 'Demandes', 'En Attente']];
            document.querySelectorAll('table tbody tr').forEach(row => {
                const cells = Array.from(row.cells).map(cell => cell.textContent.trim());
                distributorsData.push(cells);
            });
            const distributorsWS = XLSX.utils.aoa_to_sheet(distributorsData);
            XLSX.utils.book_append_sheet(wb, distributorsWS, "Top Distributeurs");
            
            // Save the Excel file
            XLSX.writeFile(wb, 'eye-stock-report.xlsx');
        }
    </script>
</body>
</html>
