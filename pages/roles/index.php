<?php
$page_title = 'Rôles';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('roles', 'view');
$db = Database::getInstance();

// Récupérer tous les rôles avec le nombre d'utilisateurs et de permissions
$sql = "SELECT r.*,
               (SELECT COUNT(*) FROM users WHERE role_id = r.id) as nb_users,
               (SELECT COUNT(*) FROM role_permissions WHERE role_id = r.id) as nb_permissions
        FROM roles r
        ORDER BY r.nom";

$db->prepare($sql);
$roles = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-shield-check"></i> Gestion des rôles</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Rôles</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('roles', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/roles/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouveau rôle
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-list-ul"></i> Liste des rôles
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="rolesTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="10%">ID</th>
                                    <th width="25%">Nom du rôle</th>
                                    <th width="35%">Description</th>
                                    <th width="10%" class="text-center">Utilisateurs</th>
                                    <th width="10%" class="text-center">Permissions</th>
                                    <th width="10%" class="text-center no-sort no-export">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($roles) > 0): ?>
                                    <?php foreach ($roles as $role): ?>
                                        <tr>
                                            <td><strong>#<?php echo $role['id']; ?></strong></td>
                                            <td>
                                                <i class="bi bi-shield-check text-primary"></i>
                                                <strong><?php echo htmlspecialchars($role['nom']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($role['description'] ?? '-'); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-info">
                                                    <i class="bi bi-people"></i> <?php echo $role['nb_users']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success">
                                                    <i class="bi bi-key"></i> <?php echo $role['nb_permissions']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center action-buttons">
                                                <a href="<?php echo BASE_URL; ?>/pages/roles/view.php?id=<?php echo $role['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($auth->hasPermission('roles', 'update')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/roles/edit.php?id=<?php echo $role['id']; ?>"
                                                       class="btn btn-sm btn-warning" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>/pages/roles/permissions.php?id=<?php echo $role['id']; ?>"
                                                       class="btn btn-sm btn-primary" title="Gérer permissions">
                                                        <i class="bi bi-key"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                            <p class="mt-2">Aucun rôle trouvé</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <i class="bi bi-info-circle"></i> Total: <strong><?php echo count($roles); ?></strong> rôle(s)
                </div>
            </div>
        </div>
    </div>

    <!-- Info cards -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Rôles et permissions :</strong>
                Chaque rôle peut avoir plusieurs permissions. Cliquez sur
                <i class="bi bi-key"></i> pour gérer les permissions d'un rôle.
            </div>
        </div>
        <div class="col-md-6">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Attention :</strong>
                La modification des permissions d'un rôle affecte immédiatement tous les utilisateurs ayant ce rôle.
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    initDataTable('#rolesTable');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
