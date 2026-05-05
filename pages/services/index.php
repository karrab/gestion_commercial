<?php
$page_title = 'Services';
require_once __DIR__ . '/../../includes/header.php';
$auth->requirePermission('services', 'view');

$db = Database::getInstance();

// Récupération de la liste des services
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM services WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (nom LIKE :search OR notes LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY nom ASC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$services = $stmt->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-building"></i> Services</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Services</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/pages/services/create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nouveau service
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list-ul"></i> Liste des services
                </div>
                <div class="card-body">
                    <!-- Formulaire de recherche -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Rechercher un service..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                                    <?php if (!empty($search)): ?>
                                        <a href="<?php echo BASE_URL; ?>/pages/services/index.php" class="btn btn-secondary"><i class="bi bi-x"></i> Réinitialiser</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="servicesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Notes</th>
                                    <th>Date création</th>
                                    <th class="text-center no-sort no-export">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($services) > 0): ?>
                                    <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td><?php echo $service['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($service['nom']); ?></strong></td>
                                            <td><?php echo htmlspecialchars(substr($service['notes'] ?? '', 0, 50)); ?><?php echo strlen($service['notes'] ?? '') > 50 ? '...' : ''; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($service['created_at'])); ?></td>
                                            <td class="text-center action-buttons no-print">
                                                <a href="<?php echo BASE_URL; ?>/pages/services/view.php?id=<?php echo $service['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/pages/services/edit.php?id=<?php echo $service['id']; ?>"
                                                   class="btn btn-sm btn-warning" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/pages/services/delete.php?id=<?php echo $service['id']; ?>"
                                                   class="btn btn-sm btn-danger delete-confirm" title="Supprimer"
                                                   onclick="return confirm('Voulez-vous vraiment supprimer ce service ?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <button onclick="printService(<?php echo $service['id']; ?>)"
                                                        class="btn btn-sm btn-secondary" title="Imprimer">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucun service trouvé
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialisation DataTable
    initDataTable('#servicesTable');
});

function printService(id) {
    window.open('<?php echo BASE_URL; ?>/pages/services/print.php?id=' + id, '_blank');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
