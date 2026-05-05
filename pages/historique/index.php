<?php
$page_title = 'Historique des actions';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('traces', 'view');
$db = Database::getInstance();

// Filtres
$search = $_GET['search'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$module = $_GET['module'] ?? '';
$action = $_GET['action'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

// Pagination
$per_page = $_GET['per_page'] ?? 50;
$current_page = $_GET['page'] ?? 1;
$offset = ($current_page - 1) * $per_page;

// Requête pour le comptage total
$count_sql = "SELECT COUNT(*) as total
        FROM traces t
        INNER JOIN users u ON t.user_id = u.id
        WHERE 1=1";
$count_params = [];

// Copie des paramètres de filtres pour le comptage
if (!empty($search)) {
    $count_sql .= " AND (t.description LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search OR t.module LIKE :search OR t.action LIKE :search OR t.table_name LIKE :search OR t.ip_address LIKE :search)";
    $count_params[':search'] = '%' . $search . '%';
}

if (!empty($user_id)) {
    $count_sql .= " AND t.user_id = :user_id";
    $count_params[':user_id'] = $user_id;
}

if (!empty($module)) {
    $count_sql .= " AND t.module = :module";
    $count_params[':module'] = $module;
}

if (!empty($action)) {
    $count_sql .= " AND t.action = :action";
    $count_params[':action'] = $action;
}

if (!empty($date_debut)) {
    $count_sql .= " AND DATE(t.created_at) >= :date_debut";
    $count_params[':date_debut'] = $date_debut;
}

if (!empty($date_fin)) {
    $count_sql .= " AND DATE(t.created_at) <= :date_fin";
    $count_params[':date_fin'] = $date_fin;
}

// Exécution du comptage
$count_stmt = $db->getConnection()->prepare($count_sql);
foreach ($count_params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_count = $count_stmt->fetch()['total'];
$total_pages = $per_page > 0 ? ceil($total_count / $per_page) : 1;

// Requête principale avec pagination
$sql = "SELECT t.*,
               u.nom as user_nom, u.prenom as user_prenom, u.login as user_login
        FROM traces t
        INNER JOIN users u ON t.user_id = u.id
        WHERE 1=1";
$params = [];

// Ajout des mêmes filtres que pour le comptage
if (!empty($search)) {
    $sql .= " AND (t.description LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search OR t.module LIKE :search OR t.action LIKE :search OR t.table_name LIKE :search OR t.ip_address LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($user_id)) {
    $sql .= " AND t.user_id = :user_id";
    $params[':user_id'] = $user_id;
}

if (!empty($module)) {
    $sql .= " AND t.module = :module";
    $params[':module'] = $module;
}

if (!empty($action)) {
    $sql .= " AND t.action = :action";
    $params[':action'] = $action;
}

if (!empty($date_debut)) {
    $sql .= " AND DATE(t.created_at) >= :date_debut";
    $params[':date_debut'] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND DATE(t.created_at) <= :date_fin";
    $params[':date_fin'] = $date_fin;
}

$sql .= " ORDER BY t.created_at DESC";

// Ajout de la pagination seulement si pas "tous"
if ($per_page !== 'all') {
    $sql .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = (int)$per_page;
    $params[':offset'] = (int)$offset;
}

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$traces = $stmt->fetchAll();

// Listes pour filtres
$db->prepare("SELECT id, nom, prenom, login FROM users ORDER BY nom, prenom");
$users = $db->fetchAll();

$db->prepare("SELECT DISTINCT module FROM traces ORDER BY module");
$modules = $db->fetchAll();

$db->prepare("SELECT DISTINCT action FROM traces ORDER BY action");
$actions = $db->fetchAll();

// Actions avec icônes et couleurs
$action_config = [
    'create' => ['icon' => 'bi-plus-circle', 'color' => 'success', 'label' => 'Création'],
    'update' => ['icon' => 'bi-pencil', 'color' => 'warning', 'label' => 'Modification'],
    'delete' => ['icon' => 'bi-trash', 'color' => 'danger', 'label' => 'Suppression'],
    'view' => ['icon' => 'bi-eye', 'color' => 'info', 'label' => 'Consultation'],
    'login' => ['icon' => 'bi-box-arrow-in-right', 'color' => 'primary', 'label' => 'Connexion'],
    'logout' => ['icon' => 'bi-box-arrow-right', 'color' => 'secondary', 'label' => 'Déconnexion'],
    'export' => ['icon' => 'bi-download', 'color' => 'info', 'label' => 'Export'],
    'import' => ['icon' => 'bi-upload', 'color' => 'info', 'label' => 'Import'],
    'validate' => ['icon' => 'bi-check-circle', 'color' => 'success', 'label' => 'Validation'],
    'close' => ['icon' => 'bi-lock', 'color' => 'dark', 'label' => 'Clôture']
];
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-clock-history"></i> Historique des actions</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Historique</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <!-- Bouton d'export PDF -->
                    <a href="export_pdf.php?<?php echo http_build_query($_GET); ?>" 
                       class="btn btn-danger btn-sm" target="_blank">
                        <i class="bi bi-file-earmark-pdf"></i> Exporter en PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-list-ul"></i> Journal des actions
                        <small>Total: <?php echo $total_count; ?> actions</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <!-- Sélecteur "par page" en haut à droite -->
                        <form method="GET" class="d-flex align-items-center me-2" id="perPageForm">
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                            <input type="hidden" name="module" value="<?php echo htmlspecialchars($module); ?>">
                            <input type="hidden" name="action" value="<?php echo htmlspecialchars($action); ?>">
                            <input type="hidden" name="date_debut" value="<?php echo htmlspecialchars($date_debut); ?>">
                            <input type="hidden" name="date_fin" value="<?php echo htmlspecialchars($date_fin); ?>">
                            
                            <label class="me-2 text-white small">Affichage:</label>
                            <select class="form-select form-select-sm" name="per_page" style="width: auto;" onchange="this.form.submit()">
                                <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="50" <?php echo $per_page == 50 || $per_page === '' ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                                <option value="500" <?php echo $per_page == 500 ? 'selected' : ''; ?>>500</option>
                                <option value="1000" <?php echo $per_page == 1000 ? 'selected' : ''; ?>>1000</option>
                                <option value="all" <?php echo $per_page == 'all' ? 'selected' : ''; ?>>Tous</option>
                            </select>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="mb-3">
                        <div class="row g-2 mb-2">
                            
                            <div class="col-md-2">
                                <select class="form-select form-select-sm" name="user_id">
                                    <option value="">Tous les utilisateurs</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" <?php echo $user_id == $u['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['nom'] . ' ' . $u['prenom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select form-select-sm" name="module">
                                    <option value="">Tous les modules</option>
                                    <?php foreach ($modules as $m): ?>
                                        <option value="<?php echo $m['module']; ?>" <?php echo $module == $m['module'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($m['module']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select form-select-sm" name="action">
                                    <option value="">Toutes les actions</option>
                                    <?php foreach ($actions as $a): ?>
                                        <option value="<?php echo $a['action']; ?>" <?php echo $action == $a['action'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($a['action']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <input type="date" class="form-control form-control-sm" name="date_debut"
                                       value="<?php echo $date_debut; ?>" placeholder="Début">
                            </div>
                            <div class="col-md-1">
                                <input type="date" class="form-control form-select-sm" name="date_fin"
                                       value="<?php echo $date_fin; ?>" placeholder="Fin">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary btn-sm w-100" name="apply_filters">
                                    <i class="bi bi-search"></i> Rechercher
                                </button>
                            </div>
                        </div>
                        
                        <?php if (!empty($search) || !empty($user_id) || !empty($module) || !empty($action) || !empty($date_debut) || !empty($date_fin)): ?>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <a href="<?php echo BASE_URL; ?>/pages/historique/index.php" class="btn btn-sm btn-secondary">
                                        <i class="bi bi-x-circle"></i> Réinitialiser les filtres
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm table-bordered" id="tracesTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="12%">Date & Heure</th>
                                    <th width="12%">Utilisateur</th>
                                    <th width="10%">Module</th>
                                    <th width="10%">Action</th>
                                    <th width="35%">Description</th>
                                    <th width="8%">Table</th>
                                    <th width="8%">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($traces) > 0): ?>
                                    <?php foreach ($traces as $trace): ?>
                                        <?php
                                        $act = $trace['action'];
                                        $config = $action_config[$act] ?? ['icon' => 'bi-circle', 'color' => 'secondary', 'label' => $act];
                                        ?>
                                        <tr>
                                            <td><small>#<?php echo $trace['id']; ?></small></td>
                                            <td>
                                                <small>
                                                    <?php echo date('d/m/Y', strtotime($trace['created_at'])); ?><br>
                                                    <span class="text-muted"><?php echo date('H:i:s', strtotime($trace['created_at'])); ?></span>
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    <i class="bi bi-person text-primary"></i>
                                                    <strong><?php echo htmlspecialchars($trace['user_nom'] . ' ' . $trace['user_prenom']); ?></strong><br>
                                                    <span class="text-muted"><?php echo htmlspecialchars($trace['user_login']); ?></span>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($trace['module']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $config['color']; ?>">
                                                    <i class="<?php echo $config['icon']; ?>"></i>
                                                    <?php echo $config['label']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($trace['description'] ?? '-'); ?></small>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($trace['table_name']); ?></small>
                                            </td>
                                            <td>
                                                <small class="text-muted font-monospace"><?php echo htmlspecialchars($trace['ip_address'] ?? '-'); ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                            <p class="mt-2">Aucune action enregistrée</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination en bas du tableau -->
                    <?php if ($total_pages > 1 && $per_page !== 'all'): ?>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="text-muted small">
                                Affichage de <strong><?php echo min($per_page, count($traces)); ?></strong> actions sur <strong><?php echo $total_count; ?></strong> total
                            </div>
                        </div>
                        <div class="col-md-6">
                            <nav aria-label="Pagination" class="float-end">
                                <ul class="pagination pagination-sm mb-0">
                                    <!-- Page précédente -->
                                    <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" 
                                           href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>">
                                            &laquo; Précédent
                                        </a>
                                    </li>
                                    
                                    <!-- Première page -->
                                    <?php if ($current_page > 3): ?>
                                        <li class="page-item">
                                            <a class="page-link" 
                                               href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">
                                                1
                                            </a>
                                        </li>
                                        <?php if ($current_page > 4): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Pages autour de la page courante -->
                                    <?php 
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++): 
                                    ?>
                                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                            <a class="page-link" 
                                               href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Dernière page -->
                                    <?php if ($current_page < $total_pages - 2): ?>
                                        <?php if ($current_page < $total_pages - 3): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" 
                                               href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                                <?php echo $total_pages; ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Page suivante -->
                                    <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" 
                                           href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>">
                                            Suivant &raquo;
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-muted">
                    <i class="bi bi-info-circle"></i>
                    Page <strong><?php echo $current_page; ?></strong> sur <strong><?php echo $total_pages; ?></strong>
                    <?php if ($per_page !== 'all' && count($traces) >= $per_page): ?>
                        <span class="text-warning ms-3">
                            <i class="bi bi-exclamation-triangle"></i>
                            Limite de <?php echo $per_page; ?> actions par page atteinte.
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Info -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>À propos de l'historique :</strong>
                L'historique enregistre automatiquement toutes les actions importantes des utilisateurs
                (créations, modifications, suppressions, connexions, etc.).
                Ces données sont conservées pour l'audit et la traçabilité.
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    initDataTable('#tracesTable', {
        order: [[0, 'desc']], // Trier par ID décroissant (plus récent en premier)
        pageLength: <?php echo $per_page === 'all' ? -1 : $per_page; ?>,
        lengthMenu: [[10, 50, 100, 500, 1000, -1], [10, 50, 100, 500, 1000, "Tous"]],
        paging: <?php echo $per_page === 'all' ? 'false' : 'true'; ?>,
        info: true,
        language: {
            paginate: {
                previous: "&laquo;",
                next: "&raquo;"
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
