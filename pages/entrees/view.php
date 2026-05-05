<?php
$page_title = 'Détails de l\'entrée';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération de l'entrée avec les informations du fournisseur
$db->prepare("SELECT e.*, f.nom_complet as fournisseur, u.nom as user_nom, u.prenom as user_prenom 
              FROM entrees e 
              LEFT JOIN fournisseurs f ON e.fournisseur_id = f.id
              LEFT JOIN users u ON e.user_id = u.id
              WHERE e.id = ?");
$db->bind(1, $id);
$entree = $db->fetch();

if (!$entree) {
    $_SESSION['error'] = 'Entrée introuvable.';
    header('Location: ' . BASE_URL . '/pages/entrees/index.php');
    exit;
}

// Récupération des lignes d'entrée
$db->prepare("SELECT * FROM ligne_entrees WHERE entree_id = ?");
$db->bind(1, $id);
$lignes = $db->fetchAll();

// Calcul des totaux
$total_articles = count($lignes);
$total_qte = 0;
foreach ($lignes as $ligne) {
    $total_qte += $ligne['qte_entree'];
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-box-arrow-in-down"></i> Détails de l'entrée</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/entrees/index.php">Entrées</a></li>
                            <li class="breadcrumb-item active">Détails</li>
                        </ol>
                    </nav>
                </div>
                <div class="no-print">
                    <?php if ($auth->hasPermission('entrees', 'update')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/entrees/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Modifier
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>/pages/entrees/pdf.php?id=<?php echo $id; ?>" class="btn btn-secondary" target="_blank">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                    <?php if ($auth->hasPermission('entrees', 'delete')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/entrees/delete.php?id=<?php echo $id; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette entrée ? Cette action est irréversible.');">
                            <i class="bi bi-trash"></i> Supprimer
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informations principales -->
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Informations générales
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">ID</th>
                            <td><?php echo $entree['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <td><?php echo date('d/m/Y', strtotime($entree['date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Fournisseur</th>
                            <td><?php echo htmlspecialchars($entree['fournisseur'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Fichier joint</th>
                            <td>
                                <?php if (!empty($entree['fichier'])): ?>
                                    <a href="<?php echo UPLOAD_ENTREES_URL . '/' . $entree['fichier']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download"></i> Télécharger
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Aucun fichier</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td><?php echo nl2br(htmlspecialchars($entree['notes'] ?? '-')); ?></td>
                        </tr>
                        <tr>
                            <th>Créé par</th>
                            <td><?php echo htmlspecialchars(($entree['user_nom'] ?? '') . ' ' . ($entree['user_prenom'] ?? '')); ?></td>
                        </tr>
                        <tr>
                            <th>Date de création</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($entree['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Dernière modification</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($entree['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Liste des articles -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-box-seam"></i> Articles (<?php echo $total_articles; ?>)
                </div>
                <div class="card-body">
                    <?php if ($total_articles > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Code Article</th>
                                        <th>Désignation</th>
                                        <th class="text-center">Quantité</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lignes as $ligne): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($ligne['code_article']); ?></code></td>
                                            <td><?php echo htmlspecialchars($ligne['designation']); ?></td>
                                            <td class="text-center"><?php echo preg_replace('/,00$/', '', number_format($ligne['qte_entree'], 2, ',', ' ')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">Aucun article dans cette entrée.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> Statistiques
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5 class="text-primary"><?php echo $total_articles; ?></h5>
                        <small class="text-muted">Article(s)</small>
                    </div>
                    <div class="mb-3">
                        <h5 class="text-success"><?php echo preg_replace('/,00$/', '', number_format($total_qte, 2, ',', ' ')); ?></h5>
                        <small class="text-muted">Quantité totale</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
