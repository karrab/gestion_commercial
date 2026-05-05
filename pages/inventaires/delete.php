<?php
/**
 * Suppression d'un inventaire
 */

require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requirePermission('inventaires', 'delete');

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

// Vérifier que l'inventaire est en cours
if ($inventaire['etat'] !== 'en_cours') {
    $_SESSION['error'] = 'Seuls les inventaires "En cours" peuvent être supprimés.';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
    exit;
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = $db->getConnection();
        $conn->beginTransaction();
        
        // Supprimer les lignes d'inventaire (CASCADE devrait le faire automatiquement)
        $conn->prepare("DELETE FROM ligne_inventaires WHERE inventaire_id = :id")->execute([':id' => $id]);
        
        // Supprimer l'inventaire
        $conn->prepare("DELETE FROM inventaires WHERE id = :id")->execute([':id' => $id]);
        
        $conn->commit();
        
        $_SESSION['success'] = "Inventaire {$inventaire['reference']} supprimé avec succès.";
        header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = 'Erreur lors de la suppression : ' . $e->getMessage();
    }
}

$page_title = 'Supprimer inventaire';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-danger"><i class="bi bi-exclamation-triangle"></i> Supprimer un inventaire</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
                    <li class="breadcrumb-item active">Supprimer</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 offset-lg-3">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-trash"></i> Confirmation de suppression
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>ATTENTION !</strong> Cette action est irréversible.
                    </div>

                    <p>Vous êtes sur le point de supprimer l'inventaire suivant :</p>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h5><i class="bi bi-clipboard"></i> <?php echo htmlspecialchars($inventaire['reference']); ?></h5>
                            <p class="mb-1"><strong>Date :</strong> <?php echo date('d/m/Y', strtotime($inventaire['date'])); ?></p>
                            <p class="mb-0"><strong>État :</strong> <span class="badge bg-warning text-dark">En cours</span></p>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <strong>Conséquences :</strong>
                        <ul class="mb-0">
                            <li>L'inventaire sera définitivement supprimé</li>
                            <li>Toutes les lignes d'inventaire seront supprimées</li>
                            <li>Cette action ne peut pas être annulée</li>
                        </ul>
                    </div>

                    <form method="POST">
                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Confirmer la suppression
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
