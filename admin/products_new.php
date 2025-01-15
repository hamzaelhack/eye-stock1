<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set current page for navbar
$currentPage = 'products';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les produits avec filtrage
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : '';

$query = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($searchTerm) {
    $query .= " AND (name LIKE ? OR category_id IN (SELECT id FROM categories WHERE name LIKE ?) OR notes LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

if ($categoryFilter) {
    $query .= " AND category_id = ?";
    $params[] = $categoryFilter;
}

if ($stockFilter) {
    switch($stockFilter) {
        case 'out':
            $query .= " AND quantity <= 0";
            break;
        case 'low':
            $query .= " AND quantity > 0 AND quantity <= COALESCE(min_quantity, 5)";
            break;
        case 'in':
            $query .= " AND quantity > COALESCE(min_quantity, 5)";
            break;
    }
}

$query .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les catégories depuis la table categories
$stmt = $conn->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Produits - Eye Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .price {
            font-size: 1.25rem;
            color: #198754;
        }
        .purchase-price {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .profit {
            font-size: 0.9rem;
            color: #0d6efd;
        }
        .quantity-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <!-- Filtres -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Rechercher..." 
                           value="<?= htmlspecialchars($searchTerm) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select id="categoryFilter" class="form-select">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars($category['id']) ?>" 
                            <?= $categoryFilter === $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="stockFilter" class="form-select">
                    <option value="">Tous les stocks</option>
                    <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Rupture de stock</option>
                    <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Stock faible</option>
                    <option value="in" <?= $stockFilter === 'in' ? 'selected' : '' ?>>En stock</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="bi bi-plus-circle"></i> Ajouter un Produit
                </button>
            </div>
        </div>

        <!-- Liste des produits -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4 mb-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($product['image'])): ?>
                            <img src="../uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                                 class="card-img-top product-image" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                 style="height: 200px;">
                                <i class="bi bi-image text-secondary" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title text-truncate" title="<?= htmlspecialchars($product['name']) ?>">
                                <?= htmlspecialchars($product['name']) ?>
                            </h5>
                            
                            <div class="mb-2">
                                <span class="badge bg-primary"><?= htmlspecialchars($product['category_id']) ?></span>
                                <?php
                                $minQuantity = isset($product['min_quantity']) ? $product['min_quantity'] : 5;
                                if ($product['quantity'] <= 0) {
                                    echo '<span class="badge bg-danger">Rupture de stock</span>';
                                } elseif ($product['quantity'] <= $minQuantity) {
                                    echo '<span class="badge bg-warning">Stock faible</span>';
                                } else {
                                    echo '<span class="badge bg-success">En stock</span>';
                                }
                                ?>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Prix d'achat:</small>
                                <span class="fw-bold"><?= number_format($product['buy_price'], 2) ?> DA</span>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Prix de vente:</small>
                                <span class="fw-bold"><?= number_format($product['sell_price'], 2) ?> DA</span>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted">Quantité:</small>
                                <span class="fw-bold"><?= $product['quantity'] ?></span>
                            </div>

                            <?php if (!empty($product['notes'])): ?>
                                <p class="card-text small text-muted mb-3">
                                    <?= nl2br(htmlspecialchars($product['notes'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="updateStock(<?= $product['id'] ?>)">
                                    <i class="bi bi-box-seam"></i> Stock
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                        onclick="editProduct(<?= $product['id'] ?>)">
                                    <i class="bi bi-pencil"></i> Modifier
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteProduct(<?= $product['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal d'ajout/modification de produit -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Ajouter un Produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="productForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="productId">
                        
                        <div class="mb-3">
                            <label class="form-label">Nom du produit</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['id']) ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Prix d'achat</label>
                                    <input type="number" name="buy_price" class="form-control" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Prix de vente</label>
                                    <input type="number" name="sell_price" class="form-control" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Quantité</label>
                                    <input type="number" name="quantity" class="form-control" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Quantité minimale</label>
                                    <input type="number" name="min_quantity" class="form-control" min="0" value="5">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" id="productImage" class="form-control" accept="image/*">
                            <div id="imagePreview" class="mt-2"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de mise à jour du stock -->
    <div class="modal fade" id="stockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mise à jour du stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="stockForm">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="stockProductId">
                        
                        <div class="mb-3">
                            <label class="form-label">Stock actuel</label>
                            <input type="number" id="currentStock" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Opération</label>
                            <select name="operation" class="form-select" required>
                                <option value="add">Ajouter au stock</option>
                                <option value="set">Définir le stock</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Quantité</label>
                            <input type="number" name="quantity" class="form-control" required min="0">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>

                        <div id="stockHistory" class="mt-3">
                            <h6 class="border-bottom pb-2">Historique récent</h6>
                            <div id="stockHistoryContent" class="small">
                                <!-- L'historique sera chargé ici -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour mettre à jour l'URL avec les paramètres de filtrage
        function updateURL(params) {
            const url = new URL(window.location.href);
            Object.keys(params).forEach(key => {
                if (params[key]) {
                    url.searchParams.set(key, params[key]);
                } else {
                    url.searchParams.delete(key);
                }
            });
            history.pushState({}, '', url);
            location.reload();
        }

        // Écouteurs d'événements pour les filtres
        document.getElementById('searchInput')?.addEventListener('input', debounce(function(e) {
            updateURL({ search: e.target.value });
        }, 500));

        document.getElementById('categoryFilter')?.addEventListener('change', function(e) {
            updateURL({ category: e.target.value });
        });

        document.getElementById('stockFilter')?.addEventListener('change', function(e) {
            updateURL({ stock: e.target.value });
        });

        // Fonction debounce pour limiter les appels lors de la recherche
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Gestion des formulaires
        document.getElementById('productForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enregistrement...';

                const response = await fetch('../api/products/save.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Erreur lors de l\'enregistrement');
                }

                // Afficher le message de succès
                alert('Produit enregistré avec succès');
                
                // Fermer le modal et recharger la page
                const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                modal.hide();
                location.reload();

            } catch (error) {
                alert(error.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Enregistrer';
            }
        });

        // Gestion du formulaire de stock
        document.getElementById('stockForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const productId = formData.get('product_id');
            const operation = formData.get('operation');
            const quantity = parseInt(formData.get('quantity'));
            const currentStock = parseInt(document.getElementById('currentStock').value);

            try {
                const response = await fetch('../api/products/update_stock.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        type: operation === 'add' ? 'in' : 'out',
                        quantity: operation === 'add' ? quantity : Math.abs(currentStock - quantity),
                        reason: formData.get('notes')
                    })
                });

                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    throw new Error(result.message || 'Une erreur est survenue');
                }
            } catch (error) {
                alert(error.message);
            }
        });

        // Prévisualisation de l'image
        document.getElementById('productImage')?.addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <img src="${e.target.result}" 
                             class="img-thumbnail" 
                             style="max-height: 200px; width: auto;">`;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });

        // Fonctions pour éditer et supprimer
        function editProduct(id) {
            fetch(`../api/products/get.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message);
                    }

                    const product = data.product;
                    const form = document.getElementById('productForm');
                    
                    // Remplir le formulaire
                    form.querySelector('[name="product_id"]').value = product.id;
                    form.querySelector('[name="name"]').value = product.name;
                    form.querySelector('[name="category_id"]').value = product.category_id;
                    form.querySelector('[name="buy_price"]').value = product.buy_price;
                    form.querySelector('[name="sell_price"]').value = product.sell_price;
                    form.querySelector('[name="quantity"]').value = product.quantity;
                    form.querySelector('[name="min_quantity"]').value = product.min_quantity;
                    form.querySelector('[name="notes"]').value = product.notes || '';

                    // Afficher l'image si elle existe
                    const imagePreview = document.getElementById('imagePreview');
                    if (product.image) {
                        imagePreview.innerHTML = `
                            <img src="../uploads/products/${product.image}" 
                                 class="img-thumbnail" 
                                 style="max-height: 200px; width: auto;">`;
                    } else {
                        imagePreview.innerHTML = '';
                    }

                    // Mettre à jour le titre du modal
                    document.getElementById('modalTitle').textContent = 'Modifier le Produit';
                    
                    // Afficher le modal
                    const modal = new bootstrap.Modal(document.getElementById('addProductModal'));
                    modal.show();
                })
                .catch(error => alert(error.message));
        }

        function updateStock(id) {
            fetch(`../api/products/get.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message);
                    }

                    const product = data.product;
                    document.getElementById('stockProductId').value = product.id;
                    document.getElementById('currentStock').value = product.quantity;
                    
                    // Charger l'historique
                    loadStockHistory(product.id);
                    
                    const modal = new bootstrap.Modal(document.getElementById('stockModal'));
                    modal.show();
                })
                .catch(error => alert(error.message));
        }

        function deleteProduct(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
                fetch('../api/products/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(result => {
                    if (!result.success) {
                        throw new Error(result.message);
                    }
                    alert('Produit supprimé avec succès');
                    location.reload();
                })
                .catch(error => alert(error.message));
            }
        }

        // Fonction pour charger l'historique des mouvements
        async function loadStockHistory(productId) {
            try {
                const response = await fetch(`../api/products/stock_history.php?product_id=${productId}`);
                const data = await response.json();
                
                const historyContent = document.getElementById('stockHistoryContent');
                if (data.movements && data.movements.length > 0) {
                    historyContent.innerHTML = data.movements.map(movement => `
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge ${movement.type === 'in' ? 'bg-success' : 'bg-danger'}">
                                    ${movement.type === 'in' ? '+' : '-'}${movement.quantity}
                                </span>
                                ${movement.reason ? `<small class="text-muted d-block">${movement.reason}</small>` : ''}
                                ${movement.distributor_name ? `<small class="text-muted d-block">Par: ${movement.distributor_name}</small>` : ''}
                            </div>
                            <small class="text-muted">${new Date(movement.created_at).toLocaleString()}</small>
                        </div>
                    `).join('');
                } else {
                    historyContent.innerHTML = '<p class="text-muted small">Aucun historique disponible</p>';
                }

            } catch (error) {
                console.error('Erreur lors du chargement de l\'historique:', error);
                document.getElementById('stockHistoryContent').innerHTML = 
                    '<p class="text-danger small">Erreur lors du chargement de l\'historique</p>';
            }
        }
    </script>
</body>
</html>
