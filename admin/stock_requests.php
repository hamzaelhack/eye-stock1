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
            $success = ($action === 'approve') ? "تم الموافقة على الطلب بنجاح." : "تم رفض الطلب.";
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = "حدث خطأ أثناء معالجة الطلب.";
        }
    }
}

// Fetch all requests with product and distributor details
try {
    $query = "SELECT r.*, p.name as product_name, p.description as product_description, 
                     p.quantity as available_quantity, p.image as product_image,
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

// Group requests by status
$grouped_requests = [
    'pending' => [],
    'approved' => [],
    'rejected' => []
];

foreach ($requests as $request) {
    $grouped_requests[$request['status']][] = $request;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eye-Stock - إدارة الطلبات</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f7fafc;
        }
        .request-card {
            transition: all 0.3s ease;
        }
        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .status-pending {
            background: linear-gradient(45deg, #fef3c7, #fef9e7);
            border-color: #92400e;
        }
        .status-approved {
            background: linear-gradient(45deg, #def7ec, #f0fff4);
            border-color: #03543f;
        }
        .status-rejected {
            background: linear-gradient(45deg, #fde8e8, #fff5f5);
            border-color: #9b1c1c;
        }
        .product-image {
            height: 120px;
            background-size: cover;
            background-position: center;
            border-radius: 8px;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="fixed right-0 w-64 h-screen bg-gradient-to-b from-blue-900 to-blue-800">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-eye ml-2"></i>
                    Eye-Stock
                </h2>
            </div>
            <nav class="mt-6">
                <a href="dashboard.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800">
                    <i class="fas fa-tachometer-alt ml-2"></i>
                    لوحة التحكم
                </a>
                <a href="stock_requests.php" class="flex items-center py-3 px-6 bg-blue-700 text-white">
                    <i class="fas fa-inbox ml-2"></i>
                    طلبات المخزون
                </a>
                <a href="../logout.php" class="flex items-center py-3 px-6 text-white hover:bg-blue-800 mt-auto">
                    <i class="fas fa-sign-out-alt ml-2"></i>
                    تسجيل الخروج
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-8 mr-64 p-8">
            <!-- Top Bar -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-inbox ml-3 text-blue-600"></i>
                    إدارة طلبات المخزون
                </h1>
            </div>

            <?php if (isset($success)): ?>
                <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg" role="alert">
                    <p class="font-medium"><?php echo $success; ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg" role="alert">
                    <p class="font-medium"><?php echo $error; ?></p>
                </div>
            <?php endif; ?>

            <!-- Pending Requests Section -->
            <?php if (!empty($grouped_requests['pending'])): ?>
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-clock ml-2 text-yellow-600"></i>
                        الطلبات قيد الانتظار
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($grouped_requests['pending'] as $request): ?>
                            <div class="request-card status-pending rounded-lg p-6 border-2">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($request['distributor_name']); ?></h3>
                                        <p class="text-sm text-gray-600">
                                            <?php echo date('Y/m/d H:i', strtotime($request['created_at'])); ?>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">
                                        قيد الانتظار
                                    </span>
                                </div>
                                
                                <?php if (isset($request['product_image']) && $request['product_image']): ?>
                                    <div class="product-image mb-4" style="background-image: url('../assets/images/products/<?php echo htmlspecialchars($request['product_image']); ?>')"></div>
                                <?php endif; ?>

                                <div class="space-y-2 mb-4">
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($request['product_name']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($request['product_description']); ?></p>
                                    <div class="flex justify-between text-sm">
                                        <span>الكمية المطلوبة: <?php echo htmlspecialchars($request['quantity']); ?></span>
                                        <span>المخزون المتاح: <?php echo htmlspecialchars($request['available_quantity']); ?></span>
                                    </div>
                                </div>

                                <form method="POST" class="flex justify-end space-x-2">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" name="action" value="reject" 
                                            class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors ml-2">
                                        <i class="fas fa-times ml-1"></i> رفض
                                    </button>
                                    <button type="submit" name="action" value="approve" 
                                            class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors">
                                        <i class="fas fa-check ml-1"></i> موافقة
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Approved Requests Section -->
            <?php if (!empty($grouped_requests['approved'])): ?>
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-check-circle ml-2 text-green-600"></i>
                        الطلبات الموافق عليها
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($grouped_requests['approved'] as $request): ?>
                            <div class="request-card status-approved rounded-lg p-6 border-2">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($request['distributor_name']); ?></h3>
                                        <p class="text-sm text-gray-600">
                                            <?php echo date('Y/m/d H:i', strtotime($request['created_at'])); ?>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                        تمت الموافقة
                                    </span>
                                </div>
                                
                                <?php if (isset($request['product_image']) && $request['product_image']): ?>
                                    <div class="product-image mb-4" style="background-image: url('../assets/images/products/<?php echo htmlspecialchars($request['product_image']); ?>')"></div>
                                <?php endif; ?>

                                <div class="space-y-2">
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($request['product_name']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($request['product_description']); ?></p>
                                    <div class="flex justify-between text-sm">
                                        <span>الكمية المطلوبة: <?php echo htmlspecialchars($request['quantity']); ?></span>
                                        <span>المخزون المتاح: <?php echo htmlspecialchars($request['available_quantity']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Rejected Requests Section -->
            <?php if (!empty($grouped_requests['rejected'])): ?>
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-times-circle ml-2 text-red-600"></i>
                        الطلبات المرفوضة
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($grouped_requests['rejected'] as $request): ?>
                            <div class="request-card status-rejected rounded-lg p-6 border-2">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($request['distributor_name']); ?></h3>
                                        <p class="text-sm text-gray-600">
                                            <?php echo date('Y/m/d H:i', strtotime($request['created_at'])); ?>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">
                                        مرفوض
                                    </span>
                                </div>
                                
                                <?php if (isset($request['product_image']) && $request['product_image']): ?>
                                    <div class="product-image mb-4" style="background-image: url('../assets/images/products/<?php echo htmlspecialchars($request['product_image']); ?>')"></div>
                                <?php endif; ?>

                                <div class="space-y-2">
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($request['product_name']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($request['product_description']); ?></p>
                                    <div class="flex justify-between text-sm">
                                        <span>الكمية المطلوبة: <?php echo htmlspecialchars($request['quantity']); ?></span>
                                        <span>المخزون المتاح: <?php echo htmlspecialchars($request['available_quantity']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($requests)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-gray-400 text-5xl mb-4"></i>
                    <p class="text-gray-500 text-lg">لا توجد طلبات حالياً</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
