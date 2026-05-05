<?php
/**
 * Réinitialisation d'un inventaire (retour en état "en_cours")
 */

require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();

if (!$auth->isAdmin()) {
    $_SESSION['error'] = 'Seuls les administrateurs peuvent réinitialiser un inventaire.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

$db = Database::getInstance();
$id = intval($_GET['id'] ?? 0);

$db->prepare("SELECT * FROM inventaires WHERE id = :id");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

// Vérifier que l'inventaire est validé
if ($inventaire['etat'] !== 'valide') {
    $_SESSION['error'] = 'Seuls les inventaires "Validés" peuvent être réinitialisés.';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
    exit;
}

// Vérifier que l'inventaire n'a pas déjà été réinitialisé
if ($inventaire['reinitialise'] == 1) {
    $_SESSION['error'] = 'Cet inventaire a déjà été réinitialisé.';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
    exit;
}

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = $db->getConnection();
        $conn->beginTransaction();
        
        // Mettre à jour les stocks des articles avec les quantités physiques
        $sql_update_articles = "
            UPDATE articles a
            INNER JOIN ligne_inventaires li ON a.id = li.article_id
            SET a.stock_initial = li.qte_physique,
                a.qte_disponible = li.qte_physique,
                a.qte_entree = 0,
                a.qte_sortie = 0,
                a.qte_retour = 0,
                a.updated_at = NOW()
            WHERE li.inventaire_id = :inventaire_id
            AND li.qte_physique IS NOT NULL";
        
        $stmt_articles = $conn->prepare($sql_update_articles);
        $stmt_articles->execute([':inventaire_id' => $id]);
        $nb_articles_updated = $stmt_articles->rowCount();
        
        // Mettre à jour l'inventaire
        $sql = "UPDATE inventaires 
                SET etat = 'en_cours',
                    user_validation_id = NULL,
                    date_validation = NULL,
                    reinitialise = 1,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $conn->commit();
        
        // Enregistrer la trace
        if (method_exists($auth, 'logTrace')) {
            $auth->logTrace(
                $auth->getUserId(),
                'inventaires',
                'reinitialiser_stock',
                'articles',
                $id,
                "Réinitialisation inventaire {$inventaire['reference']} - {$nb_articles_updated} articles mis à jour"
            );
        }
        
        $_SESSION['success'] = "Inventaire {$inventaire['reference']} réinitialisé en \"En cours\".<br>" .
                              "Stocks mis à jour pour {$nb_articles_updated} article(s) : stock_initial = qte_disponible = qte_physique";
        header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
        exit;
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        $_SESSION['error'] = 'Erreur lors de la réinitialisation : ' . $e->getMessage();
        error_log('Erreur réinitialisation inventaire: ' . $e->getMessage());
    }
}

// Calculer le nombre d'articles pour affichage dans la confirmation
$db->prepare("SELECT COUNT(*) as nb_articles FROM ligne_inventaires WHERE inventaire_id = :id");
$db->bind(':id', $id);
$count_result = $db->fetch();
$nb_articles = $count_result['nb_articles'] ?? 0;

$page_title = 'Réinitialiser inventaire';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-arrow-counterclockwise"></i> Réinitialiser un inventaire</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
                    <li class="breadcrumb-item active">Réinitialiser</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 offset-lg-3">
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <i class="bi bi-arrow-counterclockwise"></i> Confirmation de réinitialisation
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Réinitialisation des stocks :</strong> Cette action va mettre à jour les stocks avec les quantités physiques de l'inventaire.
                    </div>

                    <p>Inventaire à réinitialiser :</p>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h5><i class="bi bi-clipboard"></i> <?php echo htmlspecialchars($inventaire['reference']); ?></h5>
                            <p class="mb-1"><strong>Date :</strong> <?php echo date('d/m/Y', strtotime($inventaire['date_debut'])); ?></p>
                            <p class="mb-0"><strong>Nombre d'articles :</strong> <?php echo $nb_articles; ?></p>
                            <p class="mb-0"><strong>État actuel :</strong> 
                                <span class="badge bg-info">
                                    Validé
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <strong>ATTENTION : Cette action est irréversible pour les stocks !</strong>
                        <ul class="mb-0 mt-2">
                            <li>L'inventaire repassera en état "En cours (réinitialisé)"</li>
                            <li><strong>Les stocks seront mis à jour : stock_initial = qte_disponible = qte_physique</strong></li>
                            <li>Tous les mouvements (entrées, sorties, retours) seront remis à zéro</li>
                            <li>Les quantités physiques ne pourront plus être modifiées</li>
                            <li>Les articles ne pourront plus être ajoutés ou supprimés</li>
                            <li>Seul un administrateur pourra clôturer cet inventaire</li>
                        </ul>
                    </div>

                    <div class="alert alert-danger mt-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Conséquences sur les stocks :</strong>
                        <ul class="mb-0">
                            <li>Le stock initial sera remplacé par la quantité physique comptée</li>
                            <li>La quantité disponible sera ajustée à la quantité physique</li>
                            <li>Tous les mouvements précédents seront effacés (entrées, sorties, retours)</li>
                            <li>Cette action ne peut pas être annulée</li>
                        </ul>
                    </div>

                    <form method="POST">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmStock" required>
                            <label class="form-check-label" for="confirmStock">
                                Je confirme avoir compris que les stocks seront définitivement modifiés
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-warning" id="submitBtn">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser et mettre à jour les stocks
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheckbox = document.getElementById('confirmStock');
    const submitBtn = document.getElementById('submitBtn');
    
    // Désactiver le bouton au chargement
    submitBtn.disabled = true;
    
    // Activer/désactiver le bouton selon la case à cocher
    confirmCheckbox.addEventListener('change', function() {
        submitBtn.disabled = !this.checked;
    });
    
    // Confirmation supplémentaire au clic
    submitBtn.addEventListener('click', function(e) {
        if (!confirm('⚠️ ATTENTION !\n\nVous allez réinitialiser les stocks avec les quantités physiques.\n\n• stock_initial = qte_physique\n• qte_disponible = qte_physique\n• Tous les mouvements seront effacés\n\nCette action est IRREVERSIBLE !\n\nConfirmez-vous la réinitialisation ?')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>