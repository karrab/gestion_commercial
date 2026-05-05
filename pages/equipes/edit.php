<?php
$page_title = 'Modifier equipe';
require_once __DIR__ . '/../../includes/header.php';
$auth->requirePermission('equipes', 'update');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération du equipe
$db->prepare("SELECT * FROM equipes_inventaire WHERE id = :id");
$db->bind(':id', $id);
$equipe = $db->fetch();

if (!$equipe) {
    $_SESSION['error'] = 'Équipe introuvable.';
    header('Location: ' . BASE_URL . '/pages/equipes/index.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];
    if (empty($nom)) {
        $errors[] = 'Le nom est obligatoire.';
    }

    // Vérifier l'unicité du nom (sauf pour le equipe actuel)
    $db->prepare("SELECT COUNT(*) as count FROM equipes_inventaire WHERE nom = :nom AND id != :id");
    $db->bind(':nom', $nom);
    $db->bind(':id', $id);
    $result = $db->fetch();
    if ($result['count'] > 0) {
        $errors[] = 'Un equipe avec ce nom existe déjà.';
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE equipes_inventaire SET nom = :nom, notes = :notes, updated_at = NOW() WHERE id = :id";
            $db->prepare($sql);
            $db->bind(':nom', $nom);
            $db->bind(':notes', $notes);
            $db->bind(':id', $id);

            if ($db->execute()) {
                $auth->logTrace($auth->getUserId(), 'equipes', 'update', 'equipes', $id, "Modification: $nom");

                $_SESSION['success'] = 'Équipe modifié avec succès.';
                header('Location: ' . BASE_URL . '/pages/equipes/index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la modification: ' . $e->getMessage();
        }
    }
} else {
    $nom = $equipe['nom'];
    $notes = $equipe['notes'];
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-person-workspace"></i> Modifier le equipe</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/equipes/index.php">Équipes</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pencil"></i> Informations du equipe
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="nom" class="form-label required">Nom du equipe</label>
                            <input type="text" class="form-control" id="nom" name="nom" required
                                   value="<?php echo htmlspecialchars($nom); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/pages/equipes/index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Informations
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?php echo $equipe['id']; ?></p>
                    <p><strong>Créé le:</strong><br><?php echo date('d/m/Y à H:i', strtotime($equipe['created_at'])); ?></p>
                    <p><strong>Modifié le:</strong><br><?php echo date('d/m/Y à H:i', strtotime($equipe['updated_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
