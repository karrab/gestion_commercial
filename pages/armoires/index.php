<?php
$page_title = 'Armoires';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

// Récupération de la liste des armoires
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM armoires WHERE 1=1";
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
$armoires = $stmt->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-archive"></i> Armoires</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Armoires</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <!-- AJOUT: Bouton Export PDF -->
                    <a href="<?php echo BASE_URL; ?>/pages/armoires/export_pdf.php?search=<?php echo urlencode($search); ?>" 
                       class="btn btn-success me-2">
                        <i class="bi bi-file-earmark-pdf"></i> Export PDF
                    </a>
                    
                    <a href="<?php echo BASE_URL; ?>/pages/armoires/create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nouveau armoire
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Le reste du code reste inchangé -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list-ul"></i> Liste des armoires
                </div>
                <div class="card-body">
                    <!-- Formulaire de recherche -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Rechercher un armoire..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                                    <?php if (!empty($search)): ?>
                                        <a href="<?php echo BASE_URL; ?>/pages/armoires/index.php" class="btn btn-secondary"><i class="bi bi-x"></i> Réinitialiser</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="armoiresTable">
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
                                <?php if (count($armoires) > 0): ?>
                                    <?php foreach ($armoires as $armoire): ?>
                                        <tr>
                                            <td><?php echo $armoire['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($armoire['nom']); ?></strong></td>
                                            <td><?php echo htmlspecialchars(substr($armoire['notes'] ?? '', 0, 50)); ?><?php echo strlen($armoire['notes'] ?? '') > 50 ? '...' : ''; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($armoire['created_at'])); ?></td>
                                            <td class="text-center action-buttons no-print">
                                                <a href="<?php echo BASE_URL; ?>/pages/armoires/view.php?id=<?php echo $armoire['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/pages/armoires/edit.php?id=<?php echo $armoire['id']; ?>"
                                                   class="btn btn-sm btn-warning" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/pages/armoires/delete.php?id=<?php echo $armoire['id']; ?>"
                                                   class="btn btn-sm btn-danger delete-confirm" title="Supprimer"
                                                   onclick="return confirm('Voulez-vous vraiment supprimer ce armoire ?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <button onclick="printArmoire(<?php echo $armoire['id']; ?>)"
                                                        class="btn btn-sm btn-secondary" title="Imprimer">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucun armoire trouvé
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
    initDataTable('#armoiresTable');
});

function printArmoire(id) {
    window.open('<?php echo BASE_URL; ?>/pages/armoires/print.php?id=' + id, '_blank');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>