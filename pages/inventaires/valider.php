<?php
/**
 * Validation d'un inventaire (passage en état "valide")
 */

require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requirePermission('inventaires', 'update');

$db = Database::getInstance();
$id = intval($_GET['id'] ?? 0);

// Récupérer l'inventaire
$db->prepare("SELECT * FROM inventaires WHERE id = :id");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

if ($inventaire['etat'] !== 'en_cours') {
    $_SESSION['error'] = 'Seuls les inventaires "En cours" peuvent être validés.';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
    exit;
}

// Vérifier si l'inventaire a été réinitialisé
if ($inventaire['reinitialise'] == 1) {
    $_SESSION['error'] = 'Les inventaires réinitialisés ne peuvent pas être validés. Ils doivent être clôturés directement.';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
    exit;
}

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql = "UPDATE inventaires
                SET etat = 'valide',
                    user_validation_id = :user_id,
                    date_validation = NOW()
                WHERE id = :id";
        
        $conn = $db->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $auth->getUserId(),
            ':id' => $id
        ]);
        
        $_SESSION['success'] = "Inventaire {$inventaire['reference']} validé avec succès.";
        header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
    }
}

$page_title = 'Valider inventaire';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-check-circle"></i> Valider un inventaire</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
                    <li class="breadcrumb-item active">Valider</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 offset-lg-3">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-check-circle"></i> Confirmation de validation
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        La validation rendra cet inventaire non modifiable.
                    </div>

                    <p>Inventaire à valider :</p>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h5><i class="bi bi-clipboard"></i> <?php echo htmlspecialchars($inventaire['reference']); ?></h5>
                            <p class="mb-0"><strong>Date :</strong> <?php echo date('d/m/Y', strtotime($inventaire['date_debut'])); ?></p>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <strong>Après validation :</strong>
                        <ul class="mb-0">
                            <li>Les quantités physiques ne pourront plus être modifiées</li>
                            <li>Seul un administrateur pourra clôturer l'inventaire</li>
                            <li>Un administrateur pourra le réinitialiser si nécessaire</li>
                        </ul>
                    </div>

                    <form method="POST">
                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-info">
                                <i class="bi bi-check-circle"></i> Valider l'inventaire
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>