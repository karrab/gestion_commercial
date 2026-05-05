<?php
$page_title = 'Détails du retour';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('retours', 'view');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération du retour avec les détails
$retour = $db->query("
    SELECT r.*, s.nom as service_nom, e.nom as employe_nom, e.prenom as employe_prenom,
           u.nom as user_nom, u.prenom as user_prenom
    FROM retours r
    INNER JOIN services s ON r.service_id = s.id
    INNER JOIN employes e ON r.employe_id = e.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
", [$id])->fetch();

if (!$retour) {
    $_SESSION['error'] = 'Retour introuvable.';
    header('Location: ' . BASE_URL . '/pages/retours/index.php');
    exit;
}

// Récupérer les lignes du retour
$lignes = $db->query("
    SELECT lr.*, a.code_article, a.designation 
    FROM ligne_retours lr
    LEFT JOIN articles a ON lr.article_id = a.id
    WHERE lr.retour_id = ?
    ORDER BY lr.created_at DESC
", [$id])->fetchAll();

// Calculer la quantité totale
$qte_totale = 0;
foreach ($lignes as $ligne) {
    $qte_totale += $ligne['qte_retour'];
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-box-arrow-in-up"></i> Détails du retour #<?php echo $id; ?></h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/retours/index.php">Retours</a></li>
                            <li class="breadcrumb-item active">Détails</li>
                        </ol>
                    </nav>
                </div>
                <div class="no-print">
                    <a href="<?php echo BASE_URL; ?>/pages/retours/pdf.php?id=<?php echo $id; ?>" class="btn btn-secondary" target="_blank">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                    <?php if ($auth->hasPermission('retours', 'update')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/retours/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Modifier
                        </a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                    <a href="<?php echo BASE_URL; ?>/pages/retours/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
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
                            <td>#<?php echo $retour['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <td><?php echo date('d/m/Y', strtotime($retour['date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Service</th>
                            <td><?php echo htmlspecialchars($retour['service_nom']); ?></td>
                        </tr>
                        <tr>
                            <th>Employé</th>
                            <td><?php echo htmlspecialchars($retour['employe_nom'] . ' ' . $retour['employe_prenom']); ?></td>
                        </tr>
                        <tr>
                            <th>Fichier joint</th>
                            <td>
                                <?php if (!empty($retour['fichier'])): ?>
                                    <a href="<?php echo BASE_URL; ?>/uploads/retours/<?php echo $retour['fichier']; ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download"></i> Télécharger
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Aucun fichier</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td><?php echo nl2br(htmlspecialchars($retour['notes'] ?? '-')); ?></td>
                        </tr>
                        <tr>
                            <th>Créé par</th>
                            <td><?php echo htmlspecialchars($retour['user_nom'] . ' ' . $retour['user_prenom']); ?></td>
                        </tr>
                        <tr>
                            <th>Date de création</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($retour['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Dernière modification</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($retour['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Liste des articles -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-box-seam"></i> Articles retournés (<?php echo count($lignes); ?>)
                </div>
                <div class="card-body">
                    <?php if (count($lignes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Code article</th>
                                        <th>Désignation</th>
                                        <th class="text-center">Quantité retournée</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lignes as $ligne): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($ligne['code_article']); ?></code></td>
                                            <td><?php echo htmlspecialchars($ligne['designation']); ?></td>
                                            <td class="text-center"><strong><?php echo number_format($ligne['qte_retour'], 2, ',', ' '); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-success">
                                        <td colspan="2" class="text-end"><strong>Total général</strong></td>
                                        <td class="text-center"><strong><?php echo number_format($qte_totale, 2, ',', ' '); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">Aucun article dans ce retour.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> Résumé
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5 class="text-primary"><?php echo count($lignes); ?></h5>
                        <small class="text-muted">Article(s) retourné(s)</small>
                    </div>
                    <div class="mb-3">
                        <h5 class="text-success"><?php echo number_format($qte_totale, 2, ',', ' '); ?></h5>
                        <small class="text-muted">Quantité totale</small>
                    </div>
                </div>
            </div>

            <div class="card no-print">
                <div class="card-header">
                    <i class="bi bi-link"></i> Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($auth->hasPermission('retours', 'update')): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/retours/edit.php?id=<?php echo $id; ?>"
                               class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Modifier ce retour
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo BASE_URL; ?>/pages/retours/pdf.php?id=<?php echo $id; ?>"
                           class="btn btn-secondary" target="_blank">
                            <i class="bi bi-file-pdf"></i> Générer le PDF
                        </a>
                        
                        <?php if ($auth->hasPermission('retours', 'delete')): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/retours/delete.php?id=<?php echo $id; ?>"
                               class="btn btn-danger delete-confirm"
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce retour ? Cette action est irréversible.');">
                                <i class="bi bi-trash"></i> Supprimer ce retour
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>