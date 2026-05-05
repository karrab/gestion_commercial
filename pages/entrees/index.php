<?php
$page_title = 'Entrées';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('entrees', 'view');
$db = Database::getInstance();

$search = $_GET['search'] ?? '';
$fournisseur_id = $_GET['fournisseur_id'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$per_page = $_GET['per_page'] ?? '10'; // Nouveau paramètre pour le nombre d'éléments par page

// Validation et conversion de per_page
$per_page_options = ['10', '50', '100', '500', 'all'];
if (!in_array($per_page, $per_page_options)) {
    $per_page = '10';
}

$sql = "SELECT e.*, f.nom_complet as fournisseur, COUNT(le.id) as nb_articles
        FROM entrees e
        INNER JOIN fournisseurs f ON e.fournisseur_id = f.id
        LEFT JOIN ligne_entrees le ON e.id = le.entree_id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (e.notes LIKE ? OR f.nom_complet LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (!empty($fournisseur_id)) {
    $sql .= " AND e.fournisseur_id = ?";
    $params[] = $fournisseur_id;
}

if (!empty($date_debut)) {
    $sql .= " AND e.date >= ?";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND e.date <= ?";
    $params[] = $date_fin;
}

$sql .= " GROUP BY e.id ORDER BY e.date DESC, e.id DESC";

// Exécuter la requête sans pagination pour le comptage total
$stmt = $db->getConnection()->prepare($sql);
$stmt->execute($params);
$entrees = $stmt->fetchAll();
$total_results = count($entrees);

// Appliquer la pagination si nécessaire (sauf si "all" est sélectionné)
if ($per_page !== 'all') {
    $per_page_int = (int)$per_page;
    $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($current_page - 1) * $per_page_int;
    
    // Ajouter LIMIT pour la pagination
    $sql .= " LIMIT ? OFFSET ?";

    // Exécuter la requête paginée (LIMIT et OFFSET bindés en PARAM_INT)
    $stmt = $db->getConnection()->prepare($sql);
    $pos = 1;
    foreach ($params as $val) {
        $stmt->bindValue($pos++, $val, PDO::PARAM_STR);
    }
    $stmt->bindValue($pos++, $per_page_int, PDO::PARAM_INT);
    $stmt->bindValue($pos,   $offset,       PDO::PARAM_INT);
    $stmt->execute();
    $entrees = $stmt->fetchAll();
    
    // Calculer le nombre total de pages
    $total_pages = ceil($total_results / $per_page_int);
}

// Liste fournisseurs pour filtre
$db->prepare("SELECT id, nom_complet FROM fournisseurs WHERE actif = 1 ORDER BY nom_complet");
$fournisseurs = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-box-arrow-in-down"></i> Entrées au Stock Magasin</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Entrées</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('entrees', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/entrees/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouvelle entrée
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list-ul"></i> Liste des entrées
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-2">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
    <select class="form-select select2" id="selectFournisseur" name="fournisseur_id" data-placeholder="Sélectionner Fournisseur...">
        <option value="">Tous les fournisseurs</option>
        <?php foreach ($fournisseurs as $f): ?>
            <option value="<?php echo $f['id']; ?>" <?php echo $fournisseur_id == $f['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($f['nom_complet']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_debut" value="<?php echo $date_debut; ?>" placeholder="Date début">
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_fin" value="<?php echo $date_fin; ?>" placeholder="Date fin">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                                <?php if (!empty($search) || !empty($fournisseur_id) || !empty($date_debut) || !empty($date_fin)): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/entrees/index.php" class="btn btn-secondary"><i class="bi bi-x"></i></a>
                                <?php endif; ?>
                            </div>
                            <!-- Sélecteur de pagination -->
                            <div class="col-md-2">
                                <select class="form-select" name="per_page" onchange="this.form.submit()" style="max-width: 80px;">
                                    <option value="10" <?php echo $per_page == '10' ? 'selected' : ''; ?>>10</option>
                                    <option value="50" <?php echo $per_page == '50' ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $per_page == '100' ? 'selected' : ''; ?>>100</option>
                                    <option value="500" <?php echo $per_page == '500' ? 'selected' : ''; ?>>500</option>
                                    <option value="all" <?php echo $per_page == 'all' ? 'selected' : ''; ?>>Tous</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    <!-- Affichage des résultats -->
                    <div class="mb-3">
                        <span class="text-muted">
                            <?php if ($per_page === 'all'): ?>
                                Affichage de tous les résultats (<?php echo $total_results; ?>)
                            <?php else: ?>
                                Affichage de 
                                <?php 
                                $start = (($current_page ?? 1) - 1) * $per_page_int + 1;
                                $end = min($total_results, ($current_page ?? 1) * $per_page_int);
                                echo $start . ' à ' . $end . ' sur ' . $total_results . ' résultats';
                                ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="entreesTable">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Date</th>
                                    <th>Fournisseur</th>
                                    <th class="text-center">Nombre des Articles</th>
                                    <th>Fichier</th>
                                    <th class="text-center no-sort no-export">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($entrees) > 0): ?>
                                    <?php foreach ($entrees as $entree): ?>
                                        <tr>
                                            <td><strong>#<?php echo $entree['id']; ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($entree['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($entree['fournisseur']); ?></td>
                                            <td class="text-center"><span class="badge bg-info"><?php echo $entree['nb_articles']; ?></span></td>
                                            <td>
                                                <?php if (!empty($entree['fichier'])): ?>
                                                    <a href="<?php echo UPLOAD_ENTREES_URL . '/' . $entree['fichier']; ?>"
                                                       target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center action-buttons no-print">
                                                <a href="<?php echo BASE_URL; ?>/pages/entrees/view.php?id=<?php echo $entree['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir"><i class="bi bi-eye"></i></a>
                                                <a href="<?php echo BASE_URL; ?>/pages/entrees/pdf.php?id=<?php echo $entree['id']; ?>"
                                                   class="btn btn-sm btn-secondary" title="PDF" target="_blank"><i class="bi bi-file-pdf"></i></a>
                                                <?php if ($auth->hasPermission('entrees', 'update')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/entrees/edit.php?id=<?php echo $entree['id']; ?>"
                                                       class="btn btn-sm btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                                <?php endif; ?>
                                                <?php if ($auth->hasPermission('entrees', 'delete')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/entrees/delete.php?id=<?php echo $entree['id']; ?>"
                                                       class="btn btn-sm btn-danger delete-confirm" title="Supprimer"
                                                       onclick="return confirm('Voulez-vous vraiment supprimer cette entrée ?');"><i class="bi bi-trash"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucune entrée trouvée
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination en bas -->
                    <?php if ($per_page !== 'all' && isset($total_pages) && $total_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <span class="text-muted">
                                Page <?php echo $current_page; ?> sur <?php echo $total_pages; ?>
                            </span>
                        </div>
                        
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <!-- Lien précédent -->
                                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" 
                                       href="?<?php 
                                       $params = $_GET;
                                       $params['page'] = max(1, $current_page - 1);
                                       echo http_build_query($params);
                                       ?>">
                                        &laquo; Précédent
                                    </a>
                                </li>
                                
                                <!-- Pages -->
                                <?php 
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                        <a class="page-link" 
                                           href="?<?php 
                                           $params = $_GET;
                                           $params['page'] = $i;
                                           echo http_build_query($params);
                                           ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Lien suivant -->
                                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" 
                                       href="?<?php 
                                       $params = $_GET;
                                       $params['page'] = min($total_pages, $current_page + 1);
                                       echo http_build_query($params);
                                       ?>">
                                        Suivant &raquo;
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#selectFournisseur').select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        placeholder: 'Tous les fournisseurs'
    });
});
</script>