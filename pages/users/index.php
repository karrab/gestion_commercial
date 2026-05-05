<?php
$page_title = 'Utilisateurs';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('users', 'view');
$db = Database::getInstance();

// Filtres
$search = $_GET['search'] ?? '';
$role_id = $_GET['role_id'] ?? '';
$actif = $_GET['actif'] ?? '';

$sql = "SELECT u.*, r.nom as role_nom
        FROM users u
        INNER JOIN roles r ON u.role_id = r.id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.nom LIKE :search OR u.prenom LIKE :search OR u.login LIKE :search OR u.mail LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($role_id)) {
    $sql .= " AND u.role_id = :role_id";
    $params[':role_id'] = $role_id;
}

if ($actif !== '') {
    $sql .= " AND u.actif = :actif";
    $params[':actif'] = $actif;
}

$sql .= " ORDER BY u.nom, u.prenom";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$users = $stmt->fetchAll();

// Liste des rôles pour filtre
$db->prepare("SELECT id, nom FROM roles ORDER BY nom");
$roles = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-people-fill"></i> Gestion des utilisateurs</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Utilisateurs</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('users', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/users/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouvel utilisateur
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
                    <i class="bi bi-list-ul"></i> Liste des utilisateurs
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search"
                                       placeholder="🔍 Rechercher (nom, prénom, login, email)..."
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="role_id">
                                    <option value="">Tous les rôles</option>
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?php echo $r['id']; ?>" <?php echo $role_id == $r['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($r['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="actif">
                                    <option value="">Tous les statuts</option>
                                    <option value="1" <?php echo $actif === '1' ? 'selected' : ''; ?>>Actifs</option>
                                    <option value="0" <?php echo $actif === '0' ? 'selected' : ''; ?>>Inactifs</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Rechercher
                                </button>
                                <?php if (!empty($search) || !empty($role_id) || $actif !== ''): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/users/index.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Réinitialiser
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="usersTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="8%">ID</th>
                                    <th width="15%">Nom</th>
                                    <th width="15%">Prénom</th>
                                    <th width="15%">Login</th>
                                    <th width="18%">Email</th>
                                    <th width="12%">Rôle</th>
                                    <th width="8%" class="text-center">Statut</th>
                                    <th width="9%" class="text-center no-sort no-export">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><strong>#<?php echo $user['id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($user['nom']); ?></td>
                                            <td><?php echo htmlspecialchars($user['prenom']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($user['login']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="mailto:<?php echo htmlspecialchars($user['mail']); ?>">
                                                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['mail']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <i class="bi bi-shield-check"></i> <?php echo htmlspecialchars($user['role_nom']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($user['actif']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle"></i> Actif
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-x-circle"></i> Inactif
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center action-buttons">
                                                <a href="<?php echo BASE_URL; ?>/pages/users/view.php?id=<?php echo $user['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($auth->hasPermission('users', 'update')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/users/edit.php?id=<?php echo $user['id']; ?>"
                                                       class="btn btn-sm btn-warning" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($auth->hasPermission('users', 'delete') && $user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/users/delete.php?id=<?php echo $user['id']; ?>"
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Supprimer cet utilisateur ?')"
                                                       title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                            <p class="mt-2">Aucun utilisateur trouvé</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <i class="bi bi-info-circle"></i> Total: <strong><?php echo count($users); ?></strong> utilisateur(s)
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    initDataTable('#usersTable');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
