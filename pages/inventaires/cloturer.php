<?php
/**
 * Clôture d'un inventaire (passage en état "cloture")
 */

require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();

// Vérifier que l'utilisateur est admin
if (!$auth->isAdmin()) {
    $_SESSION['error'] = 'Seuls les administrateurs peuvent clôturer un inventaire.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

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

// Vérifier que l'inventaire est réinitialisé (en_cours avec reinitialise=1)
if (!($inventaire['etat'] == 'en_cours' && $inventaire['reinitialise'] == 1)) {
    $_SESSION['error'] = 'Seuls les inventaires réinitialisés peuvent être clôturés.';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
    exit;
}

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql = "UPDATE inventaires SET etat = 'cloture' WHERE id = :id";
        
        $conn = $db->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $_SESSION['success'] = "Inventaire {$inventaire['reference']} clôturé avec succès.";
        header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
    }
}

$page_title = 'Clôturer inventaire';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-lock"></i> Clôturer un inventaire</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
                    <li class="breadcrumb-item active">Clôturer</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 offset-lg-3">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-lock"></i> Confirmation de clôture
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>ATTENTION !</strong> La clôture est irréversible.
                    </div>

                    <p>Inventaire à clôturer :</p>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h5><i class="bi bi-clipboard"></i> <?php echo htmlspecialchars($inventaire['reference']); ?></h5>
                            <p class="mb-0"><strong>Date :</strong> <?php echo date('d/m/Y', strtotime($inventaire['date_debut'])); ?></p>
                            <p class="mb-0"><strong>État :</strong> <span class="badge bg-warning">Réinitialisé</span></p>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <strong>Après clôture :</strong>
                        <ul class="mb-0">
                            <li>L'inventaire sera définitivement figé</li>
                            <li>Aucune modification ne sera possible</li>
                            <li>Les données seront archivées</li>
                            <li><strong>Tous les boutons d'action seront cachés</strong></li>
                            <li><strong>Cette action est IRRÉVERSIBLE</strong></li>
                        </ul>
                    </div>

                    <form method="POST">
                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-lock"></i> Clôturer définitivement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>