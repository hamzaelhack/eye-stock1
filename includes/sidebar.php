<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar fixed h-screen w-64 bg-blue-900 text-white">
    <div class="p-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-eye mr-2"></i>
            Eye-Stock
        </h2>
    </div>
    
    <nav class="mt-6">
        <div class="px-4">
            <ul class="space-y-2">
                <?php if (isDistributor()): ?>
                    <li>
                        <a href="/eye-stock/distributor/dashboard.php" 
                           class="flex items-center px-4 py-3 rounded-lg <?php echo $current_page === 'dashboard.php' ? 'bg-blue-800' : 'hover:bg-blue-800'; ?>">
                            <i class="fas fa-home mr-3"></i>
                            Tableau de Bord
                        </a>
                    </li>
                    <li>
                        <a href="/eye-stock/distributor/request_stock.php" 
                           class="flex items-center px-4 py-3 rounded-lg <?php echo $current_page === 'request_stock.php' ? 'bg-blue-800' : 'hover:bg-blue-800'; ?>">
                            <i class="fas fa-shopping-cart mr-3"></i>
                            Demander des Produits
                        </a>
                    </li>
                    <li>
                        <a href="/eye-stock/distributor/stock_disb.php" 
                           class="flex items-center px-4 py-3 rounded-lg <?php echo $current_page === 'stock_disb.php' ? 'bg-blue-800' : 'hover:bg-blue-800'; ?>">
                            <i class="fas fa-box mr-3"></i>
                            Mon Stock
                        </a>
                    </li>
                <?php endif; ?>
                
                <li class="mt-auto">
                    <a href="/eye-stock/logout.php" 
                       class="flex items-center px-4 py-3 text-red-300 hover:bg-blue-800 rounded-lg">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        Déconnexion
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>
