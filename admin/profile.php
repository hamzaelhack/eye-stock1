<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set current page for navbar
$currentPage = 'profile';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les informations de l'utilisateur
$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.username,
        u.email,
        u.phone,
        u.role,
        CASE 
            WHEN u.role = 'distributor' THEN d.name 
            ELSE NULL 
        END as company_name,
        CASE 
            WHEN u.role = 'distributor' THEN d.address 
            ELSE NULL 
        END as company_address,
        CASE 
            WHEN u.role = 'distributor' THEN d.phone 
            ELSE NULL 
        END as company_phone
    FROM users u
    LEFT JOIN distributors d ON u.id = d.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Si l'utilisateur n'a pas d'email ou de téléphone, utiliser des valeurs par défaut
if (empty($user['email'])) {
    $user['email'] = $user['username'] . '@eyestock.dz';
    // Mettre à jour dans la base de données
    $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->execute([$user['email'], $_SESSION['user_id']]);
}

if (empty($user['phone'])) {
    $user['phone'] = '+213 555 000000';
    // Mettre à jour dans la base de données
    $stmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
    $stmt->execute([$user['phone'], $_SESSION['user_id']]);
}

// Récupérer les statistiques de l'utilisateur
if ($user['role'] === 'distributor') {
    // Pour les distributeurs
    $stats = $conn->prepare("
        SELECT 
            COUNT(DISTINCT r.id) as total_requests,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
            SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
        FROM requests r
        WHERE r.distributor_id = (SELECT id FROM distributors WHERE user_id = ?)
    ");
    $stats->execute([$_SESSION['user_id']]);
    $userStats = $stats->fetch(PDO::FETCH_ASSOC);

    // Initialiser les statistiques à 0 si null
    $userStats['total_requests'] = $userStats['total_requests'] ?? 0;
    $userStats['approved_requests'] = $userStats['approved_requests'] ?? 0;
    $userStats['pending_requests'] = $userStats['pending_requests'] ?? 0;
    $userStats['rejected_requests'] = $userStats['rejected_requests'] ?? 0;
} else {
    // Pour les administrateurs
    $stats = $conn->prepare("
        SELECT 
            COUNT(DISTINCT sm.id) as total_movements,
            SUM(CASE WHEN sm.type = 'in' THEN 1 ELSE 0 END) as stock_ins,
            SUM(CASE WHEN sm.type = 'out' THEN 1 ELSE 0 END) as stock_outs
        FROM stock_movements sm
        WHERE sm.user_id = ?
    ");
    $stats->execute([$_SESSION['user_id']]);
    $userStats = $stats->fetch(PDO::FETCH_ASSOC);

    // Initialiser les statistiques à 0 si null
    $userStats['total_movements'] = $userStats['total_movements'] ?? 0;
    $userStats['stock_ins'] = $userStats['stock_ins'] ?? 0;
    $userStats['stock_outs'] = $userStats['stock_outs'] ?? 0;
}

// Traitement du formulaire
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            // Vérifier si l'email existe déjà
            if ($_POST['email'] !== $user['email']) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$_POST['email'], $_SESSION['user_id']]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Cet email est déjà utilisé');
                }
            }

            // Mettre à jour les informations de l'utilisateur
            $stmt = $conn->prepare("
                UPDATE users 
                SET username = ?, email = ?, phone = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['username'],
                $_POST['email'],
                $_POST['phone'],
                $_SESSION['user_id']
            ]);

            // Si c'est un distributeur, mettre à jour les informations de l'entreprise
            if ($user['role'] === 'distributor') {
                $stmt = $conn->prepare("
                    UPDATE distributors 
                    SET name = ?, address = ?, phone = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $_POST['company_name'],
                    $_POST['company_address'],
                    $_POST['company_phone'],
                    $_SESSION['user_id']
                ]);
            }

            $message = 'Profil mis à jour avec succès';
            $messageType = 'success';

            // Recharger les informations
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Eye Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            border: none;
            border-radius: 10px;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="bg-white text-primary rounded-circle p-3 mb-3">
                        <i class="bi bi-person-circle" style="font-size: 3rem;"></i>
                    </div>
                </div>
                <div class="col">
                    <h1 class="mb-0"><?= htmlspecialchars($user['username']) ?></h1>
                    <p class="mb-0">
                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($user['email']) ?>
                        <?php if ($user['role'] === 'distributor'): ?>
                            <br>
                            <i class="bi bi-building"></i> <?= htmlspecialchars($user['company_name']) ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-4">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <?php if ($user['role'] === 'distributor'): ?>
                <!-- Statistiques pour les distributeurs -->
                <div class="col-md-3">
                    <div class="card stat-card bg-primary text-white text-center h-100">
                        <div class="card-body">
                            <i class="bi bi-boxes stat-icon"></i>
                            <h3 class="card-title"><?= $userStats['total_requests'] ?></h3>
                            <p class="card-text">Demandes Totales</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-success text-white text-center h-100">
                        <div class="card-body">
                            <i class="bi bi-check-circle stat-icon"></i>
                            <h3 class="card-title"><?= $userStats['approved_requests'] ?></h3>
                            <p class="card-text">Demandes Approuvées</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-warning text-dark text-center h-100">
                        <div class="card-body">
                            <i class="bi bi-clock stat-icon"></i>
                            <h3 class="card-title"><?= $userStats['pending_requests'] ?></h3>
                            <p class="card-text">Demandes en Attente</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-danger text-white text-center h-100">
                        <div class="card-body">
                            <i class="bi bi-x-circle stat-icon"></i>
                            <h3 class="card-title"><?= $userStats['rejected_requests'] ?></h3>
                            <p class="card-text">Demandes Rejetées</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Statistiques pour les administrateurs -->
                <div class="col-md-4">
                    <div class="card stat-card bg-primary text-white text-center h-100">
                        <div class="card-body">
                            <i class="bi bi-arrow-left-right stat-icon"></i>
                            <h3 class="card-title"><?= $userStats['total_movements'] ?></h3>
                            <p class="card-text">Mouvements Totaux</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-success text-white text-center h-100">
                        <div class="card-body">
                            <i class="bi bi-box-arrow-in-right stat-icon"></i>
                            <h3 class="card-title"><?= $userStats['stock_ins'] ?></h3>
                            <p class="card-text">Entrées de Stock</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-danger text-white text-center h-100">
                        <div class="card-body">
                            <i class="bi bi-box-arrow-right stat-icon"></i>
                            <h3 class="card-title"><?= $userStats['stock_outs'] ?></h3>
                            <p class="card-text">Sorties de Stock</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Modifier mon profil</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label">Nom d'utilisateur</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($user['phone']) ?>">
                            </div>

                            <?php if ($user['role'] === 'distributor'): ?>
                                <hr>
                                <h6 class="mb-3">Informations de l'entreprise</h6>

                                <div class="mb-3">
                                    <label class="form-label">Nom de l'entreprise</label>
                                    <input type="text" name="company_name" class="form-control" 
                                           value="<?= htmlspecialchars($user['company_name']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Adresse de l'entreprise</label>
                                    <textarea name="company_address" class="form-control" rows="2" required><?= htmlspecialchars($user['company_address']) ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Téléphone de l'entreprise</label>
                                    <input type="text" name="company_phone" class="form-control" 
                                           value="<?= htmlspecialchars($user['company_phone']) ?>" required>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Enregistrer
                                </button>
                                <a href="settings.php" class="btn btn-outline-primary">
                                    <i class="bi bi-gear"></i> Paramètres
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activation de la validation des formulaires Bootstrap
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
