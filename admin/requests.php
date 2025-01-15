<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/');
}

$db = new Database();
$pdo = $db->getConnection();

// Handle request approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve' || $action === 'reject') {
        try {
            $pdo->beginTransaction();
            
            // Update request status
            $stmt = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?");
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            $stmt->execute([$status, $request_id]);
            
            if ($action === 'approve') {
                // Get request details
                $stmt = $pdo->prepare("
                    SELECT r.quantity, r.product_id, p.quantity as current_stock 
                    FROM requests r 
                    JOIN products p ON r.product_id = p.id 
                    WHERE r.id = ?
                ");
                $stmt->execute([$request_id]);
                $request = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update product quantity
                if ($request) {
                    $new_quantity = $request['current_stock'] - $request['quantity'];
                    if ($new_quantity >= 0) {
                        $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                        $stmt->execute([$new_quantity, $request['product_id']]);
                    }
                }
            }
            
            $pdo->commit();
            $success = ($action === 'approve') ? "Demande approuvée avec succès." : "Demande rejetée.";
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur lors du traitement de la demande.";
        }
    }
}

// Fetch all requests with product and distributor details
try {
    $query = "SELECT r.*, p.name as product_name, p.quantity as available_quantity, 
                     u.username as distributor_name
              FROM requests r 
              JOIN products p ON r.product_id = p.id 
              JOIN users u ON r.distributor_id = u.id 
              ORDER BY 
                CASE r.status 
                    WHEN 'pending' THEN 1 
                    WHEN 'approved' THEN 2 
                    ELSE 3 
                END,
                r.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching requests: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eye-Stock - Gestion des Demandes</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }
        .status-approved {
            background-color: #DEF7EC;
            color: #03543F;
        }
        .status-rejected {
            background-color: #FDE8E8;
            color: #9B1C1C;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar fixed h-screen w-64 bg-blue-900 text-white">
            <div class="p-6">
                <h2 class="text-2xl font-bold flex items-center">
                    <i class="fas fa-eye mr-2"></i>
                    Eye-Stock
                </h2>
            </div>
            <nav class="mt-6">
                <a href="dashboard.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Tableau de bord
                </a>
                <a href="requests.php" class="flex items-center py-3 px-6 bg-blue-800 text-white">
                    <i class="fas fa-inbox mr-2"></i>
                    Demandes
                </a>
                <a href="../logout.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800 mt-auto">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Déconnexion
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8">
            <!-- Top Bar -->
            <div class="flex justify-between items-center mb-8 bg-white rounded-lg shadow-sm p-4">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-inbox mr-2"></i>
                    Gestion des Demandes
                </h1>
            </div>

            <?php if (isset($success)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p><?php echo $success; ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>

            <!-- Requests Table -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Distributeur
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Produit
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Quantité
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stock Disponible
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    Aucune demande pour le moment
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($request['distributor_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($request['product_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($request['quantity']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($request['available_quantity']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                            $statusClass = match($request['status']) {
                                                'pending' => 'status-pending',
                                                'approved' => 'status-approved',
                                                'rejected' => 'status-rejected',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            $statusText = match($request['status']) {
                                                'pending' => 'En attente',
                                                'approved' => 'Approuvé',
                                                'rejected' => 'Rejeté',
                                                default => 'Inconnu'
                                            };
                                        ?>
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <form method="POST" class="inline-block">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" name="action" value="approve" 
                                                        class="text-green-600 hover:text-green-900 mr-3">
                                                    <i class="fas fa-check"></i> Approuver
                                                </button>
                                                <button type="submit" name="action" value="reject" 
                                                        class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-times"></i> Rejeter
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
