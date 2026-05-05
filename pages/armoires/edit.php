<?php
$page_title = 'Modifier armoire';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération du armoire
$db->prepare("SELECT * FROM armoires WHERE id = :id");
$db->bind(':id', $id);
$armoire = $db->fetch();

if (!$armoire) {
    $_SESSION['error'] = 'Armoire introuvable.';
    header('Location: ' . BASE_URL . '/pages/armoires/index.php');
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

    // Vérifier l'unicité du nom (sauf pour le armoire actuel)
    $db->prepare("SELECT COUNT(*) as count FROM armoires WHERE nom = :nom AND id != :id");
    $db->bind(':nom', $nom);
    $db->bind(':id', $id);
    $result = $db->fetch();
    if ($result['count'] > 0) {
        $errors[] = 'Un armoire avec ce nom existe déjà.';
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE armoires SET nom = :nom, notes = :notes, updated_at = NOW() WHERE id = :id";
            $db->prepare($sql);
            $db->bind(':nom', $nom);
            $db->bind(':notes', $notes);
            $db->bind(':id', $id);

            if ($db->execute()) {
                $auth->logTrace($auth->getUserId(), 'armoires', 'update', 'armoires', $id, "Modification: $nom");

                $_SESSION['success'] = 'Armoire modifié avec succès.';
                header('Location: ' . BASE_URL . '/pages/armoires/index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la modification: ' . $e->getMessage();
        }
    }
} else {
    $nom = $armoire['nom'];
    $notes = $armoire['notes'];
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-archive"></i> Modifier le armoire</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/armoires/index.php">Armoires</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pencil"></i> Informations du armoire
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
                            <label for="nom" class="form-label required">Nom du armoire</label>
                            <input type="text" class="form-control" id="nom" name="nom" required
                                   value="<?php echo htmlspecialchars($nom); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/pages/armoires/index.php" class="btn btn-secondary">
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
                    <p><strong>ID:</strong> <?php echo $armoire['id']; ?></p>
                    <p><strong>Créé le:</strong><br><?php echo date('d/m/Y à H:i', strtotime($armoire['created_at'])); ?></p>
                    <p><strong>Modifié le:</strong><br><?php echo date('d/m/Y à H:i', strtotime($armoire['updated_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
