<?php
$page_title = 'Détails retour fournisseur';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('retour_fournisseur', 'view');
$db = Database::getInstance();
$retourFournisseur = new RetourFournisseur();

$id = $_GET['id'] ?? 0;
$retour = $retourFournisseur->getById($id);

if (!$retour) {
    $_SESSION['error'] = 'Retour fournisseur introuvable.';
    header('Location: ' . BASE_URL . '/pages/retour_fournisseur/index.php');
    exit;
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-box-arrow-left"></i> Retour Fournisseur N°:<?php echo $retour['id']; ?></h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/index.php">Retours Fournisseur</a></li>
                            <li class="breadcrumb-item active">Détails</li>
                        </ol>
                    </nav>
                </div>
                <div class="btn-group">
                    <?php if ($auth->hasPermission('retour_fournisseur', 'pdf')): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/pdf.php?id=<?php echo $id; ?>" class="btn btn-secondary" target="_blank">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                    <?php endif; ?>
                    <?php if ($auth->hasPermission('retour_fournisseur', 'update')): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                    <?php endif; ?>
                    <?php if ($auth->hasPermission('retour_fournisseur', 'delete')): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/delete.php?id=<?php echo $id; ?>"
                       class="btn btn-danger"
                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce retour fournisseur ?');">
                        <i class="bi bi-trash"></i> Supprimer
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informations générales -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-info-circle"></i> Informations générales
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="40%">Numéro:</th>
                            <td><strong><?php echo $retour['id']; ?></strong></td>
                        </tr>
                        <tr>
                            <th>Date:</th>
                            <td><?php echo date('d/m/Y', strtotime($retour['date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Créé par:</th>
                            <td><?php echo htmlspecialchars($retour['user_nom'] . ' ' . $retour['user_prenom']); ?></td>
                        </tr>
                        <tr>
                            <th>Créé le:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($retour['created_at'])); ?></td>
                        </tr>
                        <?php if ($retour['created_at'] != $retour['updated_at']): ?>
                        <tr>
                            <th>Modifié le:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($retour['updated_at'])); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Informations fournisseur -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-truck"></i> Fournisseur
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="40%">Nom:</th>
                            <td><strong><?php echo htmlspecialchars($retour['fournisseur']); ?></strong></td>
                        </tr>
                        <?php if (!empty($retour['fournisseur_adresse'])): ?>
                        <tr>
                            <th>Adresse:</th>
                            <td><?php echo htmlspecialchars($retour['fournisseur_adresse']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($retour['fournisseur_ville'])): ?>
                        <tr>
                            <th>Ville:</th>
                            <td><?php echo htmlspecialchars($retour['fournisseur_ville']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($retour['fournisseur_tel'])): ?>
                        <tr>
                            <th>Téléphone:</th>
                            <td><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($retour['fournisseur_tel']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($retour['fournisseur_email'])): ?>
                        <tr>
                            <th>Email:</th>
                            <td><i class="bi bi-envelope"></i> <a href="mailto:<?php echo htmlspecialchars($retour['fournisseur_email']); ?>"><?php echo htmlspecialchars($retour['fournisseur_email']); ?></a></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Articles retournés -->
    <div class="card mb-3">
        <div class="card-header bg-danger text-white">
            <i class="bi bi-box-seam"></i> Articles retournés (<?php echo count($retour['lignes']); ?>)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%"></th>
                            <th width="15%">Code</th>
                            <th width="50%">Désignation</th>
                            <th width="15%" class="text-end">Quantité</th>
                            <th width="15%" class="text-end">Stock actuel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($retour['lignes'])): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="bi bi-inbox"></i> Aucun article
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($retour['lignes'] as $index => $ligne): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><code><?php echo htmlspecialchars($ligne['code_article']); ?></code></td>
                                    <td><?php echo htmlspecialchars($ligne['designation']); ?></td>
                                    <td class="text-end">
                                        <strong><?php echo number_format($ligne['qte'], 0, '', ' '); ?></strong>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-info"><?php echo number_format($ligne['stock_actuel'], 0, '', ' '); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Notes et fichier -->
    <div class="row">
        <?php if (!empty($retour['notes'])): ?>
        <div class="col-md-<?php echo !empty($retour['fichier']) ? '8' : '12'; ?>">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-sticky"></i> Notes / Motif du retour
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($retour['notes'])); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($retour['fichier'])): ?>
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-file-earmark"></i> Fichier joint
                </div>
                <div class="card-body">
                    <a href="<?php echo UPLOAD_URL; ?>/retour_fournisseur/<?php echo $retour['fichier']; ?>" 
                       class="btn btn-primary w-100" target="_blank">
                        <i class="bi bi-download"></i> Télécharger
                    </a>
                    <p class="mb-0 mt-2 small text-muted"><?php echo $retour['fichier']; ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Boutons d'action -->
    <div class="card">
        <div class="card-body">
            <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
            <?php if ($auth->hasPermission('retour_fournisseur', 'pdf')): ?>
            <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/pdf.php?id=<?php echo $id; ?>" class="btn btn-secondary" target="_blank">
                <i class="bi bi-file-pdf"></i> Générer PDF
            </a>
            <?php endif; ?>
            <?php if ($auth->hasPermission('retour_fournisseur', 'update')): ?>
            <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Modifier
            </a>
            <?php endif; ?>
            <?php if ($auth->hasPermission('retour_fournisseur', 'delete')): ?>
            <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/delete.php?id=<?php echo $id; ?>"
               class="btn btn-danger"
               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce retour fournisseur ?');">
                <i class="bi bi-trash"></i> Supprimer
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
