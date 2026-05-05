<?php
$page_title = 'Bureaux';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('bureaux', 'view');
$db = Database::getInstance();

// Paramètres de pagination
$per_page = isset($_GET['per_page']) && in_array($_GET['per_page'], [50, 100, 500, 1000, 0]) ? (int)$_GET['per_page'] : 50;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $per_page;

// Filtres
$search = $_GET['search'] ?? '';
$service_id = $_GET['service_id'] ?? '';

// Requête pour compter le total
$count_sql = "SELECT COUNT(*) as total
              FROM bureaux b
              LEFT JOIN services s ON b.service_id = s.id
              LEFT JOIN employes e ON b.employe_id = e.id
              WHERE 1=1";

$params = [];

if (!empty($search)) {
    $count_sql .= " AND (b.code_local LIKE :search OR b.batiment LIKE :search OR b.etage LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($service_id)) {
    $count_sql .= " AND b.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

// Exécution du comptage
$stmt_count = $db->getConnection()->prepare($count_sql);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value);
}
$stmt_count->execute();
$total_result = $stmt_count->fetch();
$total_items = $total_result['total'];

// Calcul du nombre de pages
if ($per_page > 0) {
    $total_pages = ceil($total_items / $per_page);
} else {
    $total_pages = 1;
    $per_page = $total_items; // Afficher tous les éléments
}

// Requête pour les données avec pagination
$sql = "SELECT b.*,
               s.nom as service_nom,
               e.nom as employe_nom, e.prenom as employe_prenom
        FROM bureaux b
        LEFT JOIN services s ON b.service_id = s.id
        LEFT JOIN employes e ON b.employe_id = e.id
        WHERE 1=1";

// Réutilisation des mêmes paramètres
if (!empty($search)) {
    $sql .= " AND (b.code_local LIKE :search OR b.batiment LIKE :search OR b.etage LIKE :search)";
}

if (!empty($service_id)) {
    $sql .= " AND b.service_id = :service_id";
}

$sql .= " ORDER BY b.code_local ASC";

// Ajout de la pagination si per_page > 0
if ($per_page > 0) {
    $sql .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = $per_page;
    $params[':offset'] = $offset;
}

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    if ($key === ':limit' || $key === ':offset') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$bureaux = $stmt->fetchAll();

// Services pour filtre
$db->prepare("SELECT id, nom FROM services ORDER BY nom");
$services = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-door-open"></i> Bureaux</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Bureaux</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('bureaux', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/bureaux/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouveau bureau
                        </a>
                    <?php endif; ?>
                    
                    <!-- Bouton d'export PDF - Toujours visible si l'utilisateur peut voir la page -->
                    <button type="button" class="btn btn-danger" id="exportPdfBtn" data-bs-toggle="modal" data-bs-target="#exportPdfModal">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list-ul"></i> Liste des bureaux
                    <div class="float-end">
                        <span class="badge bg-secondary">
                            Total : <?php echo $total_items; ?> bureau<?php echo $total_items > 1 ? 'x' : ''; ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="mb-3">
                        <input type="hidden" name="page" value="1">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher (code, bâtiment, étage)..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="service_id">
                                    <option value="">Tous les services</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" <?php echo $service_id == $service['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($service['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="per_page" onchange="this.form.submit()">
                                    <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50 par page</option>
                                    <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100 par page</option>
                                    <option value="500" <?php echo $per_page == 500 ? 'selected' : ''; ?>>500 par page</option>
                                    <option value="1000" <?php echo $per_page == 1000 ? 'selected' : ''; ?>>1000 par page</option>
                                    <option value="0" <?php echo $per_page == 0 ? 'selected' : ''; ?>>Tous</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrer</button>
                                <?php if (!empty($search) || !empty($service_id) || $per_page != 50): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/bureaux/index.php" class="btn btn-secondary"><i class="bi bi-x"></i> Réinitialiser</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="bureauxTable">
                            <thead>
                                <tr>
                                    <th>Code local</th>
                                    <th>Bâtiment</th>
                                    <th>Étage</th>
                                    <th>Service</th>
                                    <th>Employé</th>
                                    <th class="text-center no-sort no-export">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($bureaux) > 0): ?>
                                    <?php foreach ($bureaux as $bureau): ?>
                                        <tr>
                                            <td><strong><?php echo $bureau['code_local'] ? htmlspecialchars($bureau['code_local']) : '<span class="text-muted">-</span>'; ?></strong></td>
                                            <td><?php echo $bureau['batiment'] ? htmlspecialchars($bureau['batiment']) : '<span class="text-muted">-</span>'; ?></td>
                                            <td><?php echo $bureau['etage'] ? htmlspecialchars($bureau['etage']) : '<span class="text-muted">-</span>'; ?></td>
                                            <td><?php echo $bureau['service_nom'] ? htmlspecialchars($bureau['service_nom']) : '<span class="text-muted">-</span>'; ?></td>
                                            <td><?php echo $bureau['employe_nom'] ? htmlspecialchars($bureau['employe_nom'] . ' ' . $bureau['employe_prenom']) : '<span class="text-muted">-</span>'; ?></td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="<?php echo BASE_URL; ?>/pages/bureaux/view.php?id=<?php echo $bureau['id']; ?>" class="btn btn-sm btn-info" title="Voir">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if ($auth->hasPermission('bureaux', 'update')): ?>
                                                        <a href="<?php echo BASE_URL; ?>/pages/bureaux/edit.php?id=<?php echo $bureau['id']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($auth->hasPermission('bureaux', 'delete')): ?>
                                                        <a href="<?php echo BASE_URL; ?>/pages/bureaux/delete.php?id=<?php echo $bureau['id']; ?>" class="btn btn-sm btn-danger" title="Supprimer"
                                                           onclick="return confirm('Voulez-vous vraiment supprimer ce bureau ?');">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucun bureau trouvé
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($per_page > 0 && $total_pages > 1): ?>
                    <nav aria-label="Pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" title="Première page">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" title="Page précédente">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="bi bi-chevron-double-left"></i></span>
                                </li>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="bi bi-chevron-left"></i></span>
                                </li>
                            <?php endif; ?>

                            <?php
                            // Affichage des numéros de page
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($end_page < $total_pages): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" title="Page suivante">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" title="Dernière page">
                                        <i class="bi bi-chevron-double-right"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="bi bi-chevron-right"></i></span>
                                </li>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="bi bi-chevron-double-right"></i></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                        <div class="text-center text-muted">
                            Page <?php echo $current_page; ?> sur <?php echo $total_pages; ?> 
                            | Affichage de <?php echo min($offset + 1, $total_items); ?> à <?php echo min($offset + $per_page, $total_items); ?> sur <?php echo $total_items; ?> bureaux
                        </div>
                    </nav>
                    <?php elseif ($per_page == 0 && $total_items > 0): ?>
                        <div class="text-center text-muted mt-3">
                            <i class="bi bi-info-circle"></i> Affichage de tous les <?php echo $total_items; ?> bureaux
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour configuration export PDF -->
<div class="modal fade" id="exportPdfModal" tabindex="-1" aria-labelledby="exportPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportPdfModalLabel">
                    <i class="bi bi-file-pdf"></i> Exporter vers PDF
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportPdfForm" method="GET" action="<?php echo BASE_URL; ?>/pages/bureaux/export_pdf.php" target="_blank">
                    <div class="mb-3">
                        <label for="pdfTitle" class="form-label">Titre du document</label>
                        <input type="text" class="form-control" id="pdfTitle" name="title" value="Liste des bureaux" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pdfOrientation" class="form-label">Orientation de la page</label>
                        <select class="form-select" id="pdfOrientation" name="orientation">
                            <option value="portrait">Portrait</option>
                            <option value="landscape">Paysage</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pdfFormat" class="form-label">Format de papier</label>
                        <select class="form-select" id="pdfFormat" name="format">
                            <option value="A4">A4</option>
                            <option value="A3">A3</option>
                            <option value="Letter">Letter</option>
                            <option value="Legal">Legal</option>
                        </select>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="includeHeader" name="include_header" checked>
                        <label class="form-check-label" for="includeHeader">
                            Inclure les en-têtes de colonnes
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="includeDate" name="include_date" checked>
                        <label class="form-check-label" for="includeDate">
                            Inclure la date d'export
                        </label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Filtres appliqués</label>
                        <div class="form-text">
                            L'export inclura les mêmes filtres que ceux actuellement appliqués :
                            <?php if (!empty($search)): ?>
                                <br>- Recherche : "<?php echo htmlspecialchars($search); ?>"
                            <?php endif; ?>
                            <?php if (!empty($service_id)): ?>
                                <?php 
                                // Trouver le nom du service sélectionné
                                $selected_service_name = '';
                                foreach ($services as $service) {
                                    if ($service['id'] == $service_id) {
                                        $selected_service_name = $service['nom'];
                                        break;
                                    }
                                }
                                ?>
                                <br>- Service : <?php echo htmlspecialchars($selected_service_name); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Champs cachés pour les filtres -->
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($service_id); ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="exportPdfForm" class="btn btn-danger">
                    <i class="bi bi-file-pdf"></i> Générer le PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    initDataTable('#bureauxTable');
    
    // Personnaliser le titre par défaut avec la date
    $('#exportPdfModal').on('show.bs.modal', function() {
        const now = new Date();
        const dateStr = now.toLocaleDateString('fr-FR');
        const timeStr = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        $('#pdfTitle').val('Liste des bureaux - ' + dateStr + ' ' + timeStr);
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>