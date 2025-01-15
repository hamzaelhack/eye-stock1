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
$stmt = $conn->query("SELECT * FROM owner_info LIMIT 1");
$ownerInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Si pas d'informations, créer une entrée vide
if (!$ownerInfo) {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS owner_info (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            company_name TEXT,
            address TEXT,
            phone TEXT,
            email TEXT,
            nif TEXT,
            nic TEXT,
            art TEXT,
            rc TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $conn->exec("INSERT INTO owner_info (company_name) VALUES ('Eye Stock')");
    $ownerInfo = [
        'company_name' => 'Eye Stock',
        'address' => '',
        'phone' => '',
        'email' => '',
        'nif' => '',
        'nic' => '',
        'art' => '',
        'rc' => ''
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informations de l'Entreprise - Eye Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Informations de l'Entreprise</h2>
                        
                        <form id="ownerForm">
                            <div class="mb-3">
                                <label class="form-label">Nom de l'entreprise</label>
                                <input type="text" name="company_name" class="form-control" 
                                       value="<?= htmlspecialchars($ownerInfo['company_name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Adresse</label>
                                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($ownerInfo['address']) ?></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Téléphone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?= htmlspecialchars($ownerInfo['phone']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?= htmlspecialchars($ownerInfo['email']) ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">NIF</label>
                                    <input type="text" name="nif" class="form-control" 
                                           value="<?= htmlspecialchars($ownerInfo['nif']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">NIC</label>
                                    <input type="text" name="nic" class="form-control" 
                                           value="<?= htmlspecialchars($ownerInfo['nic']) ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Article d'imposition</label>
                                    <input type="text" name="art" class="form-control" 
                                           value="<?= htmlspecialchars($ownerInfo['art']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Registre de Commerce</label>
                                    <input type="text" name="rc" class="form-control" 
                                           value="<?= htmlspecialchars($ownerInfo['rc']) ?>">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="factures.php" class="btn btn-secondary">Retour</a>
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('ownerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enregistrement...';

                const response = await fetch('../api/owner/update.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Erreur lors de l\'enregistrement');
                }

                alert('Informations mises à jour avec succès');
                window.location.href = 'factures.php';

            } catch (error) {
                alert(error.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Enregistrer';
            }
        });
    </script>
</body>
</html>
