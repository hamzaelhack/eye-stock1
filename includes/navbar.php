<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

$currentPage = $currentPage ?? '';
$userName = getCurrentUserName();
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand fw-bold text-primary" href="<?php echo SITE_URL; ?>">
            Eye Stock
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Main Navigation -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active fw-bold' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i>
                        Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'products' ? 'active fw-bold' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/admin/products_new.php">
                        <i class="bi bi-box me-1"></i>
                        Produits
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'categories' ? 'active fw-bold' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/admin/categories.php">
                        <i class="bi bi-tags me-1"></i>
                        Catégories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'users' ? 'active fw-bold' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/admin/users.php">
                        <i class="bi bi-people me-1"></i>
                        Utilisateurs
                    </a>
                </li>
            </ul>

            <!-- User Menu -->
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo htmlspecialchars($userName); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/settings.php">
                            <i class="bi bi-gear me-2"></i>
                            Paramètres
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
