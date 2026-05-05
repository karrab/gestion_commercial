<?php
$page_title = 'Articles';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('articles', 'view');
$db = Database::getInstance();

$search = $_GET['search'] ?? '';
$alerte_stock = $_GET['alerte_stock'] ?? '';
$actif_filter = $_GET['actif'] ?? '';

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

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$articles = $stmt->fetchAll();
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
                    
                    <!-- Boutons d'export SIMPLIFIÉS -->
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
                    <!-- Filtres -->
                    <form method="GET" class="mb-3" id="filterForm">
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
                                        $stock_status = 'good';
                                        $stock_label = 'Normal';
                                        $stock_class = 'bg-success';

                                        if ($article['qte_disponible'] <= 0) {
                                            $stock_status = 'critical';
                                            $stock_label = 'Rupture';
                                            $stock_class = 'bg-danger';
                                        } elseif ($article['stock_min'] > 0 && $article['qte_disponible'] < $article['stock_min']) {
                                            $stock_status = 'low';
                                            $stock_label = 'Faible';
                                            $stock_class = 'bg-warning';
                                        } elseif ($article['stock_max'] > 0 && $article['qte_disponible'] > $article['stock_max']) {
                                            $stock_status = 'high';
                                            $stock_label = 'Élevé';
                                            $stock_class = 'bg-info';
                                        }
                                        
                                        $actif_class = $article['actif'] ? 'bg-success' : 'bg-secondary';
                                        $actif_label = $article['actif'] ? 'Actif' : 'Inactif';
                                        
                                        // Formater les nombres sans virgules et zéros après
                                        $stock_initial = intval($article['stock_initial']);
                                        $qte_entree = intval($article['qte_entree']);
                                        $qte_sortie = intval($article['qte_sortie']);
                                        $qte_retour = intval($article['qte_retour']);
                                        $qte_retour_frs = intval($article['qte_retour_frs']); // NOUVELLE VARIABLE
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
                                            <td class="text-end text-warning"><?php echo number_format($qte_retour_frs, 0, ',', ' '); ?></td> <!-- NOUVELLE COLONNE -->
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
                                        <td colspan="13" class="text-center text-muted"> <!-- colspan changé de 12 à 13 -->
                                            <i class="bi bi-inbox"></i> Aucun article trouvé
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
    // Configuration de DataTables
    var table = $('#articlesTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json"
        },
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tous"]],
        "order": [[1, 'asc']],
        "columnDefs": [
            {
                "targets": [12], // Colonne Actions (indice augmenté de 11 à 12)
                "orderable": false,
                "searchable": false
            },
            {
                "targets": [2, 3, 4, 5, 6, 7, 8, 9], // Colonnes numériques (indices mis à jour)
                "type": "num-fmt",
                "className": "dt-body-right"
            },
            {
                "targets": [0], // Colonne Code
                "className": "dt-body-left"
            },
            {
                "targets": [10, 11], // Colonnes Statut Stock et Actif (indices mis à jour)
                "className": "dt-body-center"
            }
        ],
        "stateSave": false,
        "responsive": true,
        "autoWidth": false,
        "searching": true,
        "processing": true,
        "serverSide": false,
        "deferRender": true
    });
    
    // Recherche instantanée (à la frappe)
    $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
    });
    
    // Filtres des selectboxes
    $('#alerteStockSelect, #actifSelect').on('change', function() {
        // Ces filtres nécessitent un rechargement de page pour être appliqués côté serveur
        $('#filterForm').submit();
    });
    
    // Empêcher l'envoi du formulaire par Enter sur la zone de recherche
    $('#searchInput').on('keypress', function(e) {
        if (e.which == 13) {
            e.preventDefault();
            table.search(this.value).draw();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>