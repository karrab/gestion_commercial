<?php
$page_title = 'Détails bureau';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('bureaux', 'view');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

$sql = "SELECT b.*,
               s.nom as service_nom,
               e.nom as employe_nom, e.prenom as employe_prenom, e.matricule
        FROM bureaux b
        LEFT JOIN services s ON b.service_id = s.id
        LEFT JOIN employes e ON b.employe_id = e.id
        WHERE b.id = :id";

$db->prepare($sql);
$db->bind(':id', $id);
$bureau = $db->fetch();

if (!$bureau) {
    $_SESSION['error'] = 'Bureau introuvable.';
    header('Location: ' . BASE_URL . '/pages/bureaux/index.php');
    exit;
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-door-open"></i> Détails du bureau</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/bureaux/index.php">Bureaux</a></li>
                    <li class="breadcrumb-item active">Détails</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-info-circle"></i> Informations du bureau</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Code local</th>
                            <td><strong><?php echo htmlspecialchars($bureau['code_local']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Bâtiment</th>
                            <td><?php echo htmlspecialchars($bureau['batiment']); ?></td>
                        </tr>
                        <tr>
                            <th>Étage</th>
                            <td><?php echo htmlspecialchars($bureau['etage']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-people"></i> Affectation</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Service</th>
                            <td>
                                <?php if ($bureau['service_nom']): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/services/view.php?id=<?php echo $bureau['service_id']; ?>">
                                        <?php echo htmlspecialchars($bureau['service_nom']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Non affecté</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Employé</th>
                            <td>
                                <?php if ($bureau['employe_nom']): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/employes/view.php?id=<?php echo $bureau['employe_id']; ?>">
                                        <?php echo htmlspecialchars($bureau['employe_nom'] . ' ' . $bureau['employe_prenom']); ?>
                                        <small class="text-muted">(<?php echo htmlspecialchars($bureau['matricule']); ?>)</small>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Non affecté</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php if (!empty($bureau['notes'])): ?>
                <div class="card">
                    <div class="card-header"><i class="bi bi-sticky"></i> Notes</div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($bureau['notes'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-gear"></i> Actions</div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($auth->hasPermission('bureaux', 'update')): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/bureaux/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Modifier
                            </a>
                        <?php endif; ?>
                        <?php if ($auth->hasPermission('bureaux', 'delete')): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/bureaux/delete.php?id=<?php echo $id; ?>" class="btn btn-danger"
                               onclick="return confirm('Voulez-vous vraiment supprimer ce bureau ?');">
                                <i class="bi bi-trash"></i> Supprimer
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>/pages/bureaux/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="bi bi-clock-history"></i> Informations</div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?php echo $bureau['id']; ?></p>
                    <p><strong>Créé le:</strong><br><?php echo date('d/m/Y à H:i', strtotime($bureau['created_at'])); ?></p>
                    <p class="mb-0"><strong>Modifié le:</strong><br><?php echo date('d/m/Y à H:i', strtotime($bureau['updated_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
