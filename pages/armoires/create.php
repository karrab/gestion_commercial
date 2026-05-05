<?php
$page_title = 'Nouveau armoire';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Validation
    $errors = [];
    if (empty($nom)) {
        $errors[] = 'Le nom est obligatoire.';
    }

    // Vérifier l'unicité du nom
    $db->prepare("SELECT COUNT(*) as count FROM armoires WHERE nom = :nom");
    $db->bind(':nom', $nom);
    $result = $db->fetch();
    if ($result['count'] > 0) {
        $errors[] = 'Un armoire avec ce nom existe déjà.';
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO armoires (nom, notes) VALUES (:nom, :notes)";
            $db->prepare($sql);
            $db->bind(':nom', $nom);
            $db->bind(':notes', $notes);

            if ($db->execute()) {
                $id = $db->lastInsertId();

                // Log de la trace
                $auth->logTrace($auth->getUserId(), 'armoires', 'create', 'armoires', $id, "Création: $nom");

                $_SESSION['success'] = 'Armoire créé avec succès.';
                header('Location: ' . BASE_URL . '/pages/armoires/index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la création: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-archive"></i> Nouveau armoire</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/armoires/index.php">Armoires</a></li>
                    <li class="breadcrumb-item active">Nouveau</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-plus-circle"></i> Informations du armoire
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
                                   value="<?php echo htmlspecialchars($nom ?? ''); ?>"
                                   placeholder="Ex: Armoire Informatique">
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4"
                                      placeholder="Notes ou description du armoire"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
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
                    <i class="bi bi-info-circle"></i> Information
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Les armoires permettent d'organiser les employés et les bureaux par département.
                    </p>
                    <p class="text-muted small">
                        Le nom du armoire doit être unique.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
