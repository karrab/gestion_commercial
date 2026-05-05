<?php
$page_title = 'Articles';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('articles', 'view');
$db = Database::getInstance();

$search = $_GET['search'] ?? '';
$alerte_stock = $_GET['alerte_stock'] ?? '';
$actif_filter = $_GET['actif'] ?? '';

// Paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$perPageOptions = [10, 50, 100, 200, 500, 1000, 'all'];

if ($perPage === 0 || $perPage === 'all' || !in_array($perPage, $perPageOptions)) {
    $perPage = 'all';
}

// Requête pour compter le total des articles
$countSql = "SELECT COUNT(*) as total FROM articles WHERE 1=1";
$countParams = [];

if (!empty($search)) {
    $countSql .= " AND (code_article LIKE :search_code OR designation LIKE :search_designation)";
    $countParams[':search_code'] = '%' . $search . '%';
    $countParams[':search_designation'] = '%' . $search . '%';
}

if ($alerte_stock == 'faible') {
    $countSql .= " AND qte_disponible < stock_min AND stock_min > 0";
} elseif ($alerte_stock == 'rupture') {
    $countSql .= " AND qte_disponible <= 0";
}

if ($actif_filter !== '') {
    $countSql .= " AND actif = :actif";
    $countParams[':actif'] = (int)$actif_filter;
}

$countStmt = $db->getConnection()->prepare($countSql);
foreach ($countParams as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalArticles = $countStmt->fetch()['total'];

// Calcul de la pagination
$totalPages = ($perPage === 'all') ? 1 : ceil($totalArticles / $perPage);
$page = max(1, min($page, $totalPages));
$offset = ($perPage === 'all') ? 0 : ($page - 1) * $perPage;

// Requête principale avec pagination
$sql = "SELECT * FROM articles WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (code_article LIKE :search_code OR designation LIKE :search_designation)";
    $params[':search_code'] = '%' . $search . '%';
    $params[':search_designation'] = '%' . $search . '%';
}

if ($alerte_stock == 'faible') {
    $sql .= " AND qte_disponible < stock_min AND stock_min > 0";
} elseif ($alerte_stock == 'rupture') {
    $sql .= " AND qte_disponible <= 0";
}

if ($actif_filter !== '') {
    $sql .= " AND actif = :actif";
    $params[':actif'] = (int)$actif_filter;
}

$sql .= " ORDER BY designation ASC";

if ($perPage !== 'all') {
    $sql .= " LIMIT :offset, :per_page";
}

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

if ($perPage !== 'all') {
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $perPage, PDO::PARAM_INT);
}

$stmt->execute();
$articles = $stmt->fetchAll();

// Construction de l'URL pour les liens de pagination
function buildPaginationUrl($page, $perPage) {
    $params = $_GET;
    $params['page'] = $page;
    $params['per_page'] = $perPage;
    return '?' . http_build_query($params);
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-box-seam"></i> Articles</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Articles</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($auth->hasPermission('articles', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/articles/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouvel article
                        </a>
                    <?php endif; ?>
                    
                    <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i> Exporter
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/articles/export_excel.php?search=<?php echo urlencode($search); ?>&alerte_stock=<?php echo urlencode($alerte_stock); ?>&actif=<?php echo urlencode($actif_filter); ?>">
                                <i class="bi bi-file-earmark-excel text-success"></i> Excel
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/articles/export_pdf.php?search=<?php echo urlencode($search); ?>&alerte_stock=<?php echo urlencode($alerte_stock); ?>&actif=<?php echo urlencode($actif_filter); ?>">
                                <i class="bi bi-file-earmark-pdf text-danger"></i> PDF
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list-ul"></i> Liste des articles
                </div>
                <div class="card-body">
                    <!-- Filtres et pagination en haut -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <!-- Formulaire de filtres à gauche -->
                        <form method="GET" id="filterForm" class="flex-grow-1">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="searchInput" name="search" placeholder="Rechercher (code ou désignation)..." value="<?php echo htmlspecialchars($search); ?>">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="alerte_stock" id="alerteStockSelect">
                                        <option value="">Tous les articles</option>
                                        <option value="faible" <?php echo $alerte_stock == 'faible' ? 'selected' : ''; ?>>Stock faible</option>
                                        <option value="rupture" <?php echo $alerte_stock == 'rupture' ? 'selected' : ''; ?>>En rupture</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="actif" id="actifSelect">
                                        <option value="">Tous les statuts</option>
                                        <option value="1" <?php echo $actif_filter === '1' ? 'selected' : ''; ?>>Actifs</option>
                                        <option value="0" <?php echo $actif_filter === '0' ? 'selected' : ''; ?>>Inactifs</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                                    <?php if (!empty($search) || !empty($alerte_stock) || $actif_filter !== ''): ?>
                                        <a href="<?php echo BASE_URL; ?>/pages/articles/index.php" class="btn btn-secondary ms-1"><i class="bi bi-x"></i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Pagination en haut à droite -->
                        <?php if ($totalArticles > 0): ?>
                        <div class="ms-3 d-flex align-items-center">
                            <span class="text-muted me-2">
                                <?php 
                                if ($perPage === 'all') {
                                    echo $totalArticles;
                                } else {
                                    $start = $offset + 1;
                                    $end = min($offset + $perPage, $totalArticles);
                                    echo "$start-$end";
                                }
                                ?> / <?php echo $totalArticles; ?>
                            </span>
                            <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href='<?php echo buildPaginationUrl(1, ''); ?>' + this.value">
                                <?php foreach ($perPageOptions as $option): ?>
                                    <option value="<?php echo $option; ?>" <?php echo ($perPage == $option) ? 'selected' : ''; ?>>
                                        <?php echo $option === 'all' ? 'Tous' : $option; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="articlesTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Désignation</th>
                                    <th class="text-end">Stock initial</th>
                                    <th class="text-end">Entrées</th>
                                    <th class="text-end">Sorties</th>
                                    <th class="text-end">Retour EMP</th>
                                    <th class="text-end">Retour fournisseur</th> 
                                    <th class="text-end">Disponible</th>
                                    <th class="text-end">Min</th>
                                    <th class="text-end">Max</th>
                                    <th class="text-center">Statut Stock</th>
                                    <th class="text-center">Actif</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($articles) > 0): ?>
                                    <?php foreach ($articles as $article): ?>
                                        <?php
                                        // Vérifier si le stock est configuré (min et max tous les deux à 0)
                                        $stock_configure = ($article['stock_min'] > 0 || $article['stock_max'] > 0);

                                        if (!$stock_configure) {
                                            $stock_status = 'unconfigured';
                                            $stock_label = 'Config. Stock';
                                            $stock_class = 'bg-secondary';
                                        } elseif ($article['qte_disponible'] <= 0) {
                                            $stock_status = 'critical';
                                            $stock_label = 'Rupture';
                                            $stock_class = 'bg-danger';
                                        } elseif ($article['stock_min'] > 0 && $article['qte_disponible'] < $article['stock_min']) {
                                            $stock_status = 'low';
                                            $stock_label = 'Faible';
                                            $stock_class = 'bg-warning text-dark';
                                        } elseif ($article['stock_max'] > 0 && $article['qte_disponible'] > $article['stock_max']) {
                                            $stock_status = 'high';
                                            $stock_label = 'Élevé';
                                            $stock_class = 'bg-info text-dark';
                                        } else {
                                            $stock_status = 'good';
                                            $stock_label = 'Normal';
                                            $stock_class = 'bg-success';
                                        }
                                        
                                        $actif_class = $article['actif'] ? 'bg-success' : 'bg-secondary';
                                        $actif_label = $article['actif'] ? 'Actif' : 'Inactif';
                                        
                                        $stock_initial = intval($article['stock_initial']);
                                        $qte_entree = intval($article['qte_entree']);
                                        $qte_sortie = intval($article['qte_sortie']);
                                        $qte_retour = intval($article['qte_retour']);
                                        $qte_retour_frs = intval($article['qte_retour_frs']);
                                        $qte_disponible = intval($article['qte_disponible']);
                                        $stock_min = intval($article['stock_min']);
                                        $stock_max = intval($article['stock_max']);
                                        ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($article['code_article']); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($article['designation']); ?></strong></td>
                                            <td class="text-end"><?php echo number_format($stock_initial, 0, ',', ' '); ?></td>
                                            <td class="text-end text-success"><?php echo number_format($qte_entree, 0, ',', ' '); ?></td>
                                            <td class="text-end text-danger"><?php echo number_format($qte_sortie, 0, ',', ' '); ?></td>
                                            <td class="text-end text-info"><?php echo number_format($qte_retour, 0, ',', ' '); ?></td>
                                            <td class="text-end text-warning"><?php echo number_format($qte_retour_frs, 0, ',', ' '); ?></td>
                                            <td class="text-end"><strong><?php echo number_format($qte_disponible, 0, ',', ' '); ?></strong></td>
                                            <td class="text-end"><?php echo number_format($stock_min, 0, ',', ' '); ?></td>
                                            <td class="text-end"><?php echo number_format($stock_max, 0, ',', ' '); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $stock_class; ?>"><?php echo $stock_label; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $actif_class; ?>"><?php echo $actif_label; ?></span>
                                            </td>
                                            <td class="text-center action-buttons no-print">
                                                <a href="<?php echo BASE_URL; ?>/pages/articles/view.php?id=<?php echo $article['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir"><i class="bi bi-eye"></i></a>
                                                <?php if ($auth->hasPermission('articles', 'update')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/articles/edit.php?id=<?php echo $article['id']; ?>"
                                                       class="btn btn-sm btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                                <?php endif; ?>
                                                <?php if ($auth->hasPermission('articles', 'delete')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/articles/delete.php?id=<?php echo $article['id']; ?>"
                                                       class="btn btn-sm btn-danger delete-confirm" title="Supprimer"
                                                       onclick="return confirm('Voulez-vous vraiment supprimer cet article ?');"><i class="bi bi-trash"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="13" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucun article trouvé
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination en bas (Précédent/Suivant) -->
                    <?php if ($totalArticles > 0 && $perPage !== 'all' && $totalPages > 1): ?>
                    <div class="d-flex justify-content-center mt-3">
                        <nav aria-label="Pagination">
                            <ul class="pagination">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildPaginationUrl($page - 1, $perPage); ?>" aria-label="Précédent">
                                        <span aria-hidden="true">&laquo;</span>
                                        <span class="sr-only">Précédent</span>
                                    </a>
                                </li>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                if ($startPage > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl(1, $perPage) . '">1</a></li>';
                                    if ($startPage > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $startPage; $i <= $endPage; $i++) {
                                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                                    echo '<a class="page-link" href="' . buildPaginationUrl($i, $perPage) . '">' . $i . '</a>';
                                    echo '</li>';
                                }
                                
                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl($totalPages, $perPage) . '">' . $totalPages . '</a></li>';
                                }
                                ?>
                                
                                <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildPaginationUrl($page + 1, $perPage); ?>" aria-label="Suivant">
                                        <span aria-hidden="true">&raquo;</span>
                                        <span class="sr-only">Suivant</span>
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

<script>
$(document).ready(function() {
    // Recherche instantanée (recharge la page avec le paramètre search)
    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $('#filterForm').submit();
        }, 500);
    });
    
    // Filtres des selectboxes
    $('#alerteStockSelect, #actifSelect').on('change', function() {
        $('#filterForm').submit();
    });
    
    // Empêcher l'envoi du formulaire par Enter sur la zone de recherche
    $('#searchInput').on('keypress', function(e) {
        if (e.which == 13) {
            e.preventDefault();
            $('#filterForm').submit();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>