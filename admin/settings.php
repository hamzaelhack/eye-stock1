<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set current page for navbar
$currentPage = 'settings';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer les informations actuelles
$ownerInfo = $conn->query("SELECT * FROM owner_info LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Traitement du formulaire
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérifier si c'est une mise à jour des informations de l'entreprise
        if (isset($_POST['update_owner_info'])) {
            $stmt = $conn->prepare("
                UPDATE owner_info SET
                    company_name = ?,
                    address = ?,
                    phone = ?,
                    email = ?,
                    nif = ?,
                    nic = ?,
                    art = ?,
                    rc = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $_POST['company_name'],
                $_POST['address'],
                $_POST['phone'],
                $_POST['email'],
                $_POST['nif'],
                $_POST['nic'],
                $_POST['art'],
                $_POST['rc'],
                $ownerInfo['id']
            ]);

            $message = 'Informations mises à jour avec succès';
            $messageType = 'success';
            
            // Recharger les informations
            $ownerInfo = $conn->query("SELECT * FROM owner_info LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        }
        // Vérifier si c'est un changement de mot de passe
        elseif (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            // Vérifier le mot de passe actuel
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception('Mot de passe actuel incorrect');
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception('Les nouveaux mots de passe ne correspondent pas');
            }

            if (strlen($newPassword) < 6) {
                throw new Exception('Le nouveau mot de passe doit contenir au moins 6 caractères');
            }

            // Mettre à jour le mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);

            $message = 'Mot de passe mis à jour avec succès';
            $messageType = 'success';
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
    <title>Paramètres - Eye Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-4">
        <h2 class="mb-4">Paramètres</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Informations de l'entreprise -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informations de l'entreprise</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="update_owner_info" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label">Nom de l'entreprise</label>
                                <input type="text" name="company_name" class="form-control" 
                                       value="<?= htmlspecialchars($ownerInfo['company_name'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Adresse</label>
                                <textarea name="address" class="form-control" rows="2" required><?= htmlspecialchars($ownerInfo['address'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($ownerInfo['phone'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($ownerInfo['email'] ?? '') ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">NIF</label>
                                    <input type="text" name="nif" class="form-control" 
                                           value="<?= htmlspecialchars($ownerInfo['nif'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">NIC</label>
                                    <input type="text" name="nic" class="form-control" 
                                           value="<?= htmlspecialchars($ownerInfo['nic'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Article</label>
                                    <input type="text" name="art" class="form-control" 
                                           value="<?= htmlspecialchars($ownerInfo['art'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">RC</label>
                                    <input type="text" name="rc" class="form-control" 
                                           value="<?= htmlspecialchars($ownerInfo['rc'] ?? '') ?>" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Changement de mot de passe -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Changer le mot de passe</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label">Mot de passe actuel</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nouveau mot de passe</label>
                                <input type="password" name="new_password" class="form-control" 
                                       minlength="6" required>
                                <div class="form-text">
                                    Le mot de passe doit contenir au moins 6 caractères
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" name="confirm_password" class="form-control" 
                                       minlength="6" required>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-key"></i> Changer le mot de passe
                            </button>
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
