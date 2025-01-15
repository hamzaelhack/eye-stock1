<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set current page for navbar
$currentPage = 'distributors';

// Verify if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Initialize messages
$message = '';
$messageType = '';

// Récupérer la liste des distributeurs
try {
    $stmt = $conn->prepare("
        SELECT d.*, u.username, 
               (SELECT COUNT(*) FROM requests r WHERE r.distributor_id = d.id) as total_requests,
               (SELECT COUNT(*) FROM requests r WHERE r.distributor_id = d.id AND r.status = 'pending') as pending_requests
        FROM distributors d
        JOIN users u ON d.user_id = u.id
        ORDER BY d.name ASC
    ");
    $stmt->execute();
    $distributors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des distributeurs: " . $e->getMessage();
    $messageType = 'danger';
}

// Traitement de l'ajout/modification d'un distributeur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $address = trim($_POST['address']);
                $phone = trim($_POST['phone']);
                $username = trim($_POST['username']);
                $password = trim($_POST['password']);

                try {
                    // Vérifier si l'utilisateur existe déjà
                    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->rowCount() > 0) {
                        throw new Exception("Ce nom d'utilisateur existe déjà.");
                    }

                    $conn->beginTransaction();

                    // Créer l'utilisateur
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'distributor')");
                    $stmt->execute([$username, $hashedPassword]);
                    $userId = $conn->lastInsertId();

                    // Créer le distributeur
                    $stmt = $conn->prepare("INSERT INTO distributors (user_id, name, address, phone) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$userId, $name, $address, $phone]);

                    $conn->commit();
                    header('Location: distributors.php?success=1');
                    exit;

                } catch (Exception $e) {
                    $conn->rollBack();
                    $message = $e->getMessage();
                    $messageType = 'danger';
                }
                break;

            case 'edit':
                $id = $_POST['distributor_id'];
                $name = trim($_POST['name']);
                $address = trim($_POST['address']);
                $phone = trim($_POST['phone']);
                $password = trim($_POST['password']);

                try {
                    $conn->beginTransaction();

                    // Mettre à jour le distributeur
                    $stmt = $conn->prepare("UPDATE distributors SET name = ?, address = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$name, $address, $phone, $id]);

                    // Mettre à jour le mot de passe si fourni
                    if (!empty($password)) {
                        $stmt = $conn->prepare("
                            UPDATE users SET password = ? 
                            WHERE id = (SELECT user_id FROM distributors WHERE id = ?)
                        ");
                        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
                    }

                    $conn->commit();
                    header('Location: distributors.php?success=2');
                    exit;

                } catch (Exception $e) {
                    $conn->rollBack();
                    $message = $e->getMessage();
                    $messageType = 'danger';
                }
                break;

            case 'delete':
                $id = $_POST['distributor_id'];
                try {
                    $conn->beginTransaction();

                    // Récupérer l'ID de l'utilisateur
                    $stmt = $conn->prepare("SELECT user_id FROM distributors WHERE id = ?");
                    $stmt->execute([$id]);
                    $userId = $stmt->fetchColumn();

                    // Supprimer les demandes associées
                    $stmt = $conn->prepare("DELETE FROM requests WHERE distributor_id = ?");
                    $stmt->execute([$id]);

                    // Supprimer le distributeur
                    $stmt = $conn->prepare("DELETE FROM distributors WHERE id = ?");
                    $stmt->execute([$id]);

                    // Supprimer l'utilisateur
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$userId]);

                    $conn->commit();
                    header('Location: distributors.php?success=3');
                    exit;

                } catch (Exception $e) {
                    $conn->rollBack();
                    $message = $e->getMessage();
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Gérer les messages de succès
if (isset($_GET['success'])) {
    $messageType = 'success';
    switch ($_GET['success']) {
        case '1':
            $message = 'Distributeur ajouté avec succès.';
            break;
        case '2':
            $message = 'Distributeur modifié avec succès.';
            break;
        case '3':
            $message = 'Distributeur supprimé avec succès.';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Distributeurs - Eye Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .distributor-card {
            transition: all 0.3s ease;
            border: none;
            background: #fff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .distributor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .card-header {
            background: linear-gradient(45deg, #2193b0, #6dd5ed);
            color: white;
            border: none;
            padding: 1.5rem 1rem;
        }
        .stats-badge {
            font-size: 0.85rem;
            padding: 0.5rem 0.8rem;
            border-radius: 20px;
            margin: 0.2rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .distributor-info {
            padding: 0.5rem 0;
        }
        .distributor-info i {
            width: 20px;
            color: #2193b0;
        }
        .action-buttons {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .action-button {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            margin-left: 5px;
            transition: all 0.2s ease;
        }
        .action-button:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="bi bi-people-fill me-2"></i>
                Gestion des Distributeurs
            </h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDistributorModal">
                <i class="bi bi-plus-circle me-2"></i> Nouveau Distributeur
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($distributors)): ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <h4>Aucun distributeur trouvé</h4>
                <p>Commencez par ajouter un nouveau distributeur</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDistributorModal">
                    <i class="bi bi-plus-circle me-2"></i> Ajouter un distributeur
                </button>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <?php foreach ($distributors as $distributor): ?>
                    <div class="col">
                        <div class="card distributor-card h-100">
                            <div class="card-header position-relative">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-building me-2"></i>
                                    <?= htmlspecialchars($distributor['name']) ?>
                                </h5>
                                <div class="action-buttons">
                                    <button class="action-button" onclick="editDistributor(<?= htmlspecialchars(json_encode($distributor)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="action-button" onclick="deleteDistributor(<?= $distributor['id'] ?>, '<?= htmlspecialchars($distributor['name']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="distributor-info">
                                    <p class="mb-2">
                                        <i class="bi bi-person"></i>
                                        <span class="ms-2"><?= htmlspecialchars($distributor['username']) ?></span>
                                    </p>
                                    <?php if ($distributor['phone']): ?>
                                        <p class="mb-2">
                                            <i class="bi bi-telephone"></i>
                                            <span class="ms-2"><?= htmlspecialchars($distributor['phone']) ?></span>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($distributor['address']): ?>
                                        <p class="mb-2">
                                            <i class="bi bi-geo-alt"></i>
                                            <span class="ms-2"><?= htmlspecialchars($distributor['address']) ?></span>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <span class="stats-badge bg-primary">
                                        <i class="bi bi-box-seam"></i>
                                        <?= $distributor['total_requests'] ?> demandes
                                    </span>
                                    <?php if ($distributor['pending_requests'] > 0): ?>
                                        <span class="stats-badge bg-warning">
                                            <i class="bi bi-clock"></i>
                                            <?= $distributor['pending_requests'] ?> en attente
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal d'ajout de distributeur -->
    <div class="modal fade" id="addDistributorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau Distributeur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom du distributeur</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom d'utilisateur</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de modification -->
    <div class="modal fade" id="editDistributorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le Distributeur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="distributor_id" id="edit_distributor_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom du distributeur</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" name="address" id="edit_address" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="phone" id="edit_phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                            <input type="password" name="password" class="form-control">
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

    <!-- Modal de suppression -->
    <div class="modal fade" id="deleteDistributorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le distributeur <strong id="deleteDistributorName"></strong> ?</p>
                    <p class="text-danger">Cette action est irréversible.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="distributor_id" id="deleteDistributorId">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editDistributor(distributor) {
            document.getElementById('edit_distributor_id').value = distributor.id;
            document.getElementById('edit_name').value = distributor.name;
            document.getElementById('edit_address').value = distributor.address;
            document.getElementById('edit_phone').value = distributor.phone;
            
            new bootstrap.Modal(document.getElementById('editDistributorModal')).show();
        }

        function deleteDistributor(id, name) {
            document.getElementById('deleteDistributorId').value = id;
            document.getElementById('deleteDistributorName').textContent = name;
            
            new bootstrap.Modal(document.getElementById('deleteDistributorModal')).show();
        }
    </script>
</body>
</html>
