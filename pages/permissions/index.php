<?php
$page_title = 'Permissions';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('permissions', 'view');
$db = Database::getInstance();

// Récupérer toutes les permissions groupées par module
$sql = "SELECT * FROM permissions ORDER BY module, action";
$db->prepare($sql);
$all_permissions = $db->fetchAll();

// Grouper par module
$permissions_by_module = [];
foreach ($all_permissions as $perm) {
    $module = $perm['module'];
    if (!isset($permissions_by_module[$module])) {
        $permissions_by_module[$module] = [];
    }
    $permissions_by_module[$module][] = $perm;
}

// Statistiques
$total_permissions = count($all_permissions);
$total_modules = count($permissions_by_module);

// Icônes par module
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
    'traces' => 'bi-clock-history',
    'versions' => 'bi-tag'
];

// Actions avec icônes et couleurs
$action_config = [
    'view' => ['icon' => 'bi-eye', 'color' => 'info', 'label' => 'Voir'],
    'create' => ['icon' => 'bi-plus-circle', 'color' => 'success', 'label' => 'Créer'],
    'update' => ['icon' => 'bi-pencil', 'color' => 'warning', 'label' => 'Modifier'],
    'delete' => ['icon' => 'bi-trash', 'color' => 'danger', 'label' => 'Supprimer'],
    'export' => ['icon' => 'bi-download', 'color' => 'primary', 'label' => 'Exporter'],
    'import' => ['icon' => 'bi-upload', 'color' => 'primary', 'label' => 'Importer'],
    'validate' => ['icon' => 'bi-check-circle', 'color' => 'success', 'label' => 'Valider'],
    'close' => ['icon' => 'bi-lock', 'color' => 'secondary', 'label' => 'Clôturer']
];
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-key"></i> Gestion des permissions</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Permissions</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Permissions</div>
                            <div class="h3 mb-0 font-weight-bold"><?php echo $total_permissions; ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-key fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-left-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Modules</div>
                            <div class="h3 mb-0 font-weight-bold"><?php echo $total_modules; ?></div>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-collection fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions groupées par module -->
    <div class="row">
        <?php foreach ($permissions_by_module as $module => $permissions): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <i class="<?php echo $module_icons[$module] ?? 'bi-box'; ?>"></i>
                        <strong><?php echo ucfirst($module); ?></strong>
                        <span class="badge bg-light text-dark float-end"><?php echo count($permissions); ?></span>
                    </div>
                    <div class="card-body p-2">
                        <div class="list-group list-group-flush">
                            <?php foreach ($permissions as $perm): ?>
                                <?php
                                $action = $perm['action'];
                                $config = $action_config[$action] ?? ['icon' => 'bi-circle', 'color' => 'secondary', 'label' => $action];
                                ?>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-2">
                                    <div>
                                        <i class="<?php echo $config['icon']; ?> text-<?php echo $config['color']; ?>"></i>
                                        <strong><?php echo $config['label']; ?></strong>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($perm['nom']); ?></small>
                                    </div>
                                    <span class="badge bg-<?php echo $config['color']; ?>"><?php echo $action; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Info card -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Information :</strong>
                Les permissions sont attribuées aux rôles dans la page
                <a href="<?php echo BASE_URL; ?>/pages/roles/index.php" class="alert-link">
                    <i class="bi bi-shield-check"></i> Gestion des rôles
                </a>.
                Chaque utilisateur hérite des permissions de son rôle.
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary { border-left: 4px solid #0d6efd; }
.border-left-info { border-left: 4px solid #0dcaf0; }
.text-xs { font-size: 0.75rem; }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
