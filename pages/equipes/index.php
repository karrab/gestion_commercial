<?php
$page_title = 'Équipes';
require_once __DIR__ . '/../../includes/header.php';
$auth->requirePermission('equipes', 'view');

$db = Database::getInstance();

// Récupération de la liste des equipes
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM equipes_inventaire WHERE 1=1";
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
$equipes = $stmt->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-person-workspace"></i> Équipes</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Équipes</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/pages/equipes/create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nouveau equipe
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list-ul"></i> Liste des equipes
                </div>
                <div class="card-body">
                    <!-- Formulaire de recherche -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Rechercher un equipe..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                                    <?php if (!empty($search)): ?>
                                        <a href="<?php echo BASE_URL; ?>/pages/equipes/index.php" class="btn btn-secondary"><i class="bi bi-x"></i> Réinitialiser</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="equipesTable">
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
                                <?php if (count($equipes) > 0): ?>
                                    <?php foreach ($equipes as $equipe): ?>
                                        <tr>
                                            <td><?php echo $equipe['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($equipe['nom']); ?></strong></td>
                                            <td><?php echo htmlspecialchars(substr($equipe['notes'] ?? '', 0, 50)); ?><?php echo strlen($equipe['notes'] ?? '') > 50 ? '...' : ''; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($equipe['created_at'])); ?></td>
                                            <td class="text-center action-buttons no-print">
                                                <a href="<?php echo BASE_URL; ?>/pages/equipes/view.php?id=<?php echo $equipe['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/pages/equipes/edit.php?id=<?php echo $equipe['id']; ?>"
                                                   class="btn btn-sm btn-warning" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/pages/equipes/delete.php?id=<?php echo $equipe['id']; ?>"
                                                   class="btn btn-sm btn-danger delete-confirm" title="Supprimer"
                                                   onclick="return confirm('Voulez-vous vraiment supprimer ce equipe ?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <button onclick="printÉquipe(<?php echo $equipe['id']; ?>)"
                                                        class="btn btn-sm btn-secondary" title="Imprimer">
                                                    <i class="bi bi-printer"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucun equipe trouvé
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
    initDataTable('#equipesTable');
});

function printÉquipe(id) {
    window.open('<?php echo BASE_URL; ?>/pages/equipes/print.php?id=' + id, '_blank');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
