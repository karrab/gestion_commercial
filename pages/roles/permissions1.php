<?php
$page_title = 'Gérer les permissions';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('roles', 'update');
$db = Database::getInstance();

$role_id = $_GET['id'] ?? 0;

// Récupérer le rôle
$db->prepare("SELECT * FROM roles WHERE id = :id");
$db->bind(':id', $role_id);
$role = $db->fetch();

if (!$role) {
    $_SESSION['error'] = 'Rôle introuvable.';
    header('Location: ' . BASE_URL . '/pages/roles/index.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $selected_permissions = $_POST['permissions'] ?? [];

        $db->getConnection()->beginTransaction();

        // Supprimer toutes les permissions actuelles du rôle
        $sql = "DELETE FROM role_permissions WHERE role_id = :role_id";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bindValue(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->execute();

        // Ajouter les nouvelles permissions
        if (!empty($selected_permissions)) {
            $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)";
            $stmt = $db->getConnection()->prepare($sql);

            foreach ($selected_permissions as $permission_id) {
                $stmt->bindValue(':role_id', $role_id, PDO::PARAM_INT);
                $stmt->bindValue(':permission_id', $permission_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }

        $db->getConnection()->commit();

        $_SESSION['success'] = 'Permissions mises à jour avec succès !';
        header('Location: ' . BASE_URL . '/pages/roles/permissions.php?id=' . $role_id);
        exit;

    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        $_SESSION['error'] = 'Erreur lors de la mise à jour : ' . $e->getMessage();
    }
}

// Récupérer toutes les permissions
$sql = "SELECT * FROM permissions ORDER BY module, action";
$db->prepare($sql);
$all_permissions = $db->fetchAll();

// Récupérer les permissions actuelles du rôle
$sql = "SELECT permission_id FROM role_permissions WHERE role_id = :role_id";
$db->prepare($sql);
$db->bind(':role_id', $role_id);
$current_permissions = $db->fetchAll();
$current_permission_ids = array_column($current_permissions, 'permission_id');

// Grouper par module
$permissions_by_module = [];
foreach ($all_permissions as $perm) {
    $module = $perm['module'];
    if (!isset($permissions_by_module[$module])) {
        $permissions_by_module[$module] = [];
    }
    $permissions_by_module[$module][] = $perm;
}

// Icônes et config
$module_icons = [
    'dashboard' => 'bi-speedometer2',
    'articles' => 'bi-box-seam',
    'services' => 'bi-building',
    'employes' => 'bi-people',
    'fournisseurs' => 'bi-truck',
    'entrees' => 'bi-box-arrow-in-down',
    'sorties' => 'bi-box-arrow-up',
    'retours' => 'bi-box-arrow-in-up',
    'bureaux' => 'bi-door-open',
    'inventaires' => 'bi-clipboard-check',
    'users' => 'bi-person-circle',
    'roles' => 'bi-shield-check',
    'permissions' => 'bi-key',
    'parametres' => 'bi-gear',
    'rapports' => 'bi-file-earmark-bar-graph',
    'traces' => 'bi-clock-history'
];

$action_config = [
    'view' => ['icon' => 'bi-eye', 'label' => 'Voir'],
    'create' => ['icon' => 'bi-plus-circle', 'label' => 'Créer'],
    'update' => ['icon' => 'bi-pencil', 'label' => 'Modifier'],
    'delete' => ['icon' => 'bi-trash', 'label' => 'Supprimer'],
    'export' => ['icon' => 'bi-download', 'label' => 'Exporter'],
    'import' => ['icon' => 'bi-upload', 'label' => 'Importer'],
    'validate' => ['icon' => 'bi-check-circle', 'label' => 'Valider'],
    'close' => ['icon' => 'bi-lock', 'label' => 'Clôturer']
];
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="bi bi-key"></i>
                        Gérer les permissions: <span class="text-primary"><?php echo htmlspecialchars($role['nom']); ?></span>
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/roles/index.php">Rôles</a></li>
                            <li class="breadcrumb-item active">Permissions</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/pages/roles/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST">
        <!-- Actions sticky top -->
        <div class="card mb-3 sticky-top shadow-sm" style="top: 70px; z-index: 100;">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button type="button" class="btn btn-sm btn-success" onclick="selectAll()">
                            <i class="bi bi-check-all"></i> Tout sélectionner
                        </button>
                        <button type="button" class="btn btn-sm btn-warning" onclick="deselectAll()">
                            <i class="bi bi-x-circle"></i> Tout désélectionner
                        </button>
                        <span class="badge bg-info ms-2">
                            <span id="selected-count"><?php echo count($current_permission_ids); ?></span> permission(s) sélectionnée(s)
                        </span>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Enregistrer les permissions
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions par module -->
        <div class="row">
            <?php foreach ($permissions_by_module as $module => $permissions): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <div>
                                <i class="<?php echo $module_icons[$module] ?? 'bi-box'; ?>"></i>
                                <strong><?php echo ucfirst($module); ?></strong>
                            </div>
                            <button type="button" class="btn btn-sm btn-light"
                                    onclick="toggleModule('<?php echo $module; ?>')">
                                <i class="bi bi-check-square"></i>
                            </button>
                        </div>
                        <div class="card-body p-2">
                            <div class="list-group list-group-flush">
                                <?php foreach ($permissions as $perm): ?>
                                    <?php
                                    $action = $perm['action'];
                                    $config = $action_config[$action] ?? ['icon' => 'bi-circle', 'label' => $action];
                                    $is_checked = in_array($perm['id'], $current_permission_ids);
                                    ?>
                                    <label class="list-group-item list-group-item-action p-2 cursor-pointer"
                                           style="cursor: pointer;">
                                        <div class="form-check">
                                            <input class="form-check-input permission-checkbox"
                                                   type="checkbox"
                                                   name="permissions[]"
                                                   value="<?php echo $perm['id']; ?>"
                                                   data-module="<?php echo $module; ?>"
                                                   <?php echo $is_checked ? 'checked' : ''; ?>
                                                   onchange="updateCount()">
                                            <label class="form-check-label w-100">
                                                <i class="<?php echo $config['icon']; ?> text-primary"></i>
                                                <strong><?php echo $config['label']; ?></strong>
                                                <small class="text-muted d-block"><?php echo htmlspecialchars($perm['nom']); ?></small>
                                            </label>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Submit button at bottom -->
        <div class="row mt-3 mb-5">
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-save"></i> Enregistrer les permissions
                </button>
                <a href="<?php echo BASE_URL; ?>/pages/roles/index.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-x-circle"></i> Annuler
                </a>
            </div>
        </div>
    </form>
</div>

<script>
function selectAll() {
    $('.permission-checkbox').prop('checked', true);
    updateCount();
}

function deselectAll() {
    $('.permission-checkbox').prop('checked', false);
    updateCount();
}

function toggleModule(module) {
    const checkboxes = $(`.permission-checkbox[data-module="${module}"]`);
    const allChecked = checkboxes.length === checkboxes.filter(':checked').length;

    if (allChecked) {
        checkboxes.prop('checked', false);
    } else {
        checkboxes.prop('checked', true);
    }

    updateCount();
}

function updateCount() {
    const count = $('.permission-checkbox:checked').length;
    $('#selected-count').text(count);
}

// Update count on page load
$(document).ready(function() {
    updateCount();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
