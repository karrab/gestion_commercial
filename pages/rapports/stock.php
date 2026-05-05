<?php
$page_title = 'Rapport état du stock';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('rapports', 'view');
$db = Database::getInstance();

$filtre = $_GET['filtre'] ?? 'tous';
$per_page = $_GET['per_page'] ?? 50;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $per_page;
$search = trim($_GET['search'] ?? '');

// Déterminer les valeurs valides pour per_page
$valid_per_page = [10, 50, 100, 200, 500, 0]; // 0 = tous
if (!in_array($per_page, $valid_per_page)) {
    $per_page = 50;
}

// DEBUG: Pour le diagnostic
error_log("=== DEBUT REQUETE STOCK ===");
error_log("Filtre: $filtre, Per_page: $per_page, Page: $page, Search: '$search'");

// ===========================================
// SOLUTION SIMPLE ET EFFICACE POUR LA RECHERCHE
// ===========================================
$articles = [];
$total_count = 0;
$stats = [
    'total_articles' => 0,
    'rupture' => 0,
    'faible' => 0,
    'normal' => 0,
    'eleve' => 0,
    'config' => 0,
    'total_stock' => 0
];

try {
    // 1. D'abord, récupérer TOUS les articles actifs
    $sql_base = "SELECT * FROM articles WHERE actif = 1";
    $stmt_base = $db->query($sql_base);
    $all_articles = $stmt_base->fetchAll();
    
    // 2. Filtrer localement en PHP (plus simple et plus fiable)
    $filtered_articles = [];
    
    if (!empty($search)) {
        $search_lower = strtolower($search);
        foreach ($all_articles as $article) {
            $code_lower = strtolower($article['code_article']);
            $designation_lower = strtolower($article['designation']);
            
            // Recherche insensible à la casse en PHP
            if (strpos($code_lower, $search_lower) !== false || 
                strpos($designation_lower, $search_lower) !== false) {
                $filtered_articles[] = $article;
            }
        }
    } else {
        $filtered_articles = $all_articles;
    }
    
    // 3. Appliquer le filtre d'état
    $filtered_by_state = [];
    foreach ($filtered_articles as $article) {
        $qte = $article['qte_disponible'];
        $min = $article['stock_min'];
        $max = $article['stock_max'];
        
        $stock_configure = ($min > 0 || $max > 0);
        $include = false;

        if ($filtre == 'tous') {
            $include = true;
        } elseif ($filtre == 'config' && !$stock_configure) {
            $include = true;
        } elseif ($filtre == 'rupture' && $stock_configure && $qte <= 0) {
            $include = true;
        } elseif ($filtre == 'faible' && $stock_configure && $qte > 0 && $qte <= $min) {
            $include = true;
        } elseif ($filtre == 'normal' && $stock_configure && $qte > $min && $qte < $max) {
            $include = true;
        } elseif ($filtre == 'eleve' && $stock_configure && $qte >= $max && $max > 0) {
            $include = true;
        }

        if ($include) {
            // Config. Stock en priorité absolue
            if (!$stock_configure) {
                $article['etat_stock'] = 'config';
            } elseif ($qte <= 0) {
                $article['etat_stock'] = 'rupture';
            } elseif ($qte > 0 && $qte <= $min) {
                $article['etat_stock'] = 'faible';
            } elseif ($max > 0 && $qte >= $max) {
                $article['etat_stock'] = 'eleve';
            } else {
                $article['etat_stock'] = 'normal';
            }
            
            $article['diff_min'] = $qte - $min;
            $article['diff_max'] = $max - $qte;
            
            $filtered_by_state[] = $article;
        }
    }
    
    // 4. Trier les articles
    usort($filtered_by_state, function($a, $b) {
        $order = ['rupture' => 1, 'faible' => 2, 'normal' => 3, 'eleve' => 4, 'config' => 5];
        $order_a = $order[$a['etat_stock']] ?? 5;
        $order_b = $order[$b['etat_stock']] ?? 5;
        
        if ($order_a !== $order_b) {
            return $order_a - $order_b;
        }
        
        return $a['qte_disponible'] <=> $b['qte_disponible'];
    });
    
    // 5. Pagination
    $total_count = count($filtered_by_state);
    
    if ($per_page > 0) {
        $start = ($page - 1) * $per_page;
        $articles = array_slice($filtered_by_state, $start, $per_page);
    } else {
        $articles = $filtered_by_state;
    }
    
    // 6. Calculer les statistiques
    $stats['total_articles'] = count($filtered_by_state);
    $stats['total_stock'] = array_sum(array_column($filtered_by_state, 'qte_disponible'));
    
    foreach ($filtered_by_state as $article) {
        $qte = $article['qte_disponible'];
        $min = $article['stock_min'];
        $max = $article['stock_max'];

        if ($min == 0 && $max == 0) {
            $stats['config']++;
        } elseif ($qte <= 0) {
            $stats['rupture']++;
        } elseif ($qte > 0 && $qte <= $min) {
            $stats['faible']++;
        } elseif ($qte > $min && $qte < $max) {
            $stats['normal']++;
        } elseif ($max > 0 && $qte >= $max) {
            $stats['eleve']++;
        }
    }
    
    error_log("Total articles: " . $stats['total_articles']);
    error_log("Articles trouvés: " . count($articles));
    
    // DEBUG: Vérifier Switch
    if (!empty($search) && stripos($search, 'switch') !== false) {
        error_log("=== RECHERCHE SWITCH ===");
        error_log("Terme: $search");
        error_log("Articles: " . count($articles));
        foreach ($articles as $a) {
            error_log("- " . $a['code_article'] . ": " . $a['designation']);
        }
    }
    
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    // En cas d'erreur, on continue avec des tableaux vides
}

// Calculer le nombre total de pages
$total_pages = ($per_page > 0 && $per_page > 0) ? ceil($total_count / $per_page) : 1;

// Fonctions pour générer les URLs
function buildUrl($params = []) {
    $currentParams = [
        'filtre' => $_GET['filtre'] ?? 'tous',
        'per_page' => $_GET['per_page'] ?? 50,
        'search' => $_GET['search'] ?? '',
        'page' => 1
    ];
    
    $mergedParams = array_merge($currentParams, $params);
    return '?' . http_build_query($mergedParams);
}

function buildPageUrl($pageNum) {
    $params = [
        'filtre' => $_GET['filtre'] ?? 'tous',
        'per_page' => $_GET['per_page'] ?? 50,
        'search' => $_GET['search'] ?? '',
        'page' => $pageNum
    ];
    return '?' . http_build_query($params);
}

function buildExportUrl() {
    $params = [
        'filtre' => $_GET['filtre'] ?? 'tous',
        'search' => $_GET['search'] ?? ''
    ];
    return '?' . http_build_query($params);
}

// Compter les articles par catégorie pour les filtres
$count_tous = $count_rupture = $count_faible = $count_normal = $count_eleve = $count_config = 0;

try {
    $stmt = $db->query("SELECT COUNT(*) as c FROM articles WHERE actif = 1");
    $count_tous = $stmt->fetch()['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM articles WHERE actif = 1 AND stock_min = 0 AND stock_max = 0");
    $count_config = $stmt->fetch()['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM articles WHERE actif = 1 AND (stock_min > 0 OR stock_max > 0) AND qte_disponible <= 0");
    $count_rupture = $stmt->fetch()['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM articles WHERE actif = 1 AND stock_min > 0 AND qte_disponible > 0 AND qte_disponible <= stock_min");
    $count_faible = $stmt->fetch()['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM articles WHERE actif = 1 AND stock_min > 0 AND stock_max > 0 AND qte_disponible > stock_min AND qte_disponible < stock_max");
    $count_normal = $stmt->fetch()['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM articles WHERE actif = 1 AND stock_max > 0 AND qte_disponible >= stock_max");
    $count_eleve = $stmt->fetch()['c'];
} catch (Exception $e) {
    error_log("Erreur comptage catégories: " . $e->getMessage());
}

error_log("=== FIN REQUETE STOCK ===");
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <!-- En-tête avec bouton Export PDF -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-bar-chart"></i> Rapport état du stock</h2>
        <a href="export_stock_pdf.php<?php echo buildExportUrl(); ?>" 
           class="btn btn-danger" 
           target="_blank"
           title="Exporter en PDF">
            <i class="bi bi-file-pdf-fill me-2"></i>
            Exporter en PDF
        </a>
    </div>

    <!-- Afficher les informations de recherche -->
    <?php if (!empty($search)): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <strong><i class="bi bi-search"></i> Recherche active:</strong> "<?php echo htmlspecialchars($search); ?>"
            <?php if ($total_count > 0): ?>
                <span class="badge bg-primary ms-2"><?php echo $total_count; ?> article(s) trouvé(s)</span>
            <?php else: ?>
                <span class="badge bg-warning ms-2">Aucun article trouvé</span>
            <?php endif; ?>
            <button type="button" class="btn-close" onclick="window.location.href='<?php echo buildUrl(['search' => '']); ?>'"></button>
        </div>
    <?php endif; ?>

    <!-- Statistiques globales -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 col-6 mb-3">
                            <div class="text-center">
                                <div class="fs-1 text-primary mb-1"><?php echo $stats['total_articles']; ?></div>
                                <div class="text-muted small">Total Articles</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="text-center">
                                <div class="fs-1 text-danger mb-1"><?php echo $stats['rupture']; ?></div>
                                <div class="text-muted small">En Rupture</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="text-center">
                                <div class="fs-1 text-warning mb-1"><?php echo $stats['faible']; ?></div>
                                <div class="text-muted small">Stock Faible</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="text-center">
                                <div class="fs-1 text-success mb-1"><?php echo $stats['normal']; ?></div>
                                <div class="text-muted small">Stock Normal</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="text-center">
                                <div class="fs-1 text-info mb-1"><?php echo $stats['eleve']; ?></div>
                                <div class="text-muted small">Stock Élevé</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="text-center">
                                <div class="fs-1 text-secondary mb-1"><?php echo $stats['config']; ?></div>
                                <div class="text-muted small">Config. Stock</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="text-center">
                                <div class="fs-1 text-dark mb-1"><?php echo $stats['total_stock']; ?></div>
                                <div class="text-muted small">Total en Stock</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo buildUrl(['filtre' => 'tous']); ?>" 
                           class="btn <?php echo $filtre == 'tous' ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">
                            <i class="bi bi-grid-3x3"></i> Tous 
                            <span class="badge bg-light text-dark ms-1"><?php echo $count_tous; ?></span>
                        </a>
                        <a href="<?php echo buildUrl(['filtre' => 'rupture']); ?>" 
                           class="btn <?php echo $filtre == 'rupture' ? 'btn-danger' : 'btn-outline-danger'; ?> btn-sm">
                            <i class="bi bi-exclamation-triangle"></i> Rupture
                            <span class="badge bg-light text-dark ms-1"><?php echo $count_rupture; ?></span>
                        </a>
                        <a href="<?php echo buildUrl(['filtre' => 'faible']); ?>" 
                           class="btn <?php echo $filtre == 'faible' ? 'btn-warning' : 'btn-outline-warning'; ?> btn-sm">
                            <i class="bi bi-exclamation-circle"></i> Faible
                            <span class="badge bg-light text-dark ms-1"><?php echo $count_faible; ?></span>
                        </a>
                        <a href="<?php echo buildUrl(['filtre' => 'normal']); ?>" 
                           class="btn <?php echo $filtre == 'normal' ? 'btn-success' : 'btn-outline-success'; ?> btn-sm">
                            <i class="bi bi-check-circle"></i> Normal
                            <span class="badge bg-light text-dark ms-1"><?php echo $count_normal; ?></span>
                        </a>
                        <a href="<?php echo buildUrl(['filtre' => 'eleve']); ?>"
                           class="btn <?php echo $filtre == 'eleve' ? 'btn-info' : 'btn-outline-info'; ?> btn-sm">
                            <i class="bi bi-arrow-up-circle"></i> Élevé
                            <span class="badge bg-light text-dark ms-1"><?php echo $count_eleve; ?></span>
                        </a>
                        <a href="<?php echo buildUrl(['filtre' => 'config']); ?>"
                           class="btn <?php echo $filtre == 'config' ? 'btn-secondary' : 'btn-outline-secondary'; ?> btn-sm">
                            <i class="bi bi-gear"></i> Config. Stock
                            <span class="badge bg-light text-dark ms-1"><?php echo $count_config; ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" id="searchForm" class="row g-2 align-items-center">
                        <div class="col-8">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" 
                                       name="search" 
                                       id="search" 
                                       class="form-control border-start-0" 
                                       placeholder="Code ou désignation..." 
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       autocomplete="off">
                                <?php if (!empty($search)): ?>
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="clearSearch()" title="Effacer">
                                        <i class="bi bi-x"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-4">
                            <select name="per_page" id="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                                <option value="200" <?php echo $per_page == 200 ? 'selected' : ''; ?>>200</option>
                                <option value="500" <?php echo $per_page == 500 ? 'selected' : ''; ?>>500</option>
                                <option value="0" <?php echo $per_page == 0 ? 'selected' : ''; ?>>Tous</option>
                            </select>
                        </div>
                        
                        <input type="hidden" name="filtre" value="<?php echo htmlspecialchars($filtre); ?>">
                        <input type="hidden" name="page" value="1">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte principale des résultats -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <!-- En-tête avec informations -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-check text-primary"></i> 
                        <?php if ($filtre == 'tous'): ?>
                            Tous les articles
                        <?php elseif ($filtre == 'rupture'): ?>
                            Articles en rupture
                        <?php elseif ($filtre == 'faible'): ?>
                            Articles en stock faible
                        <?php elseif ($filtre == 'normal'): ?>
                            Articles en stock normal
                        <?php elseif ($filtre == 'eleve'): ?>
                            Articles en stock élevé
                        <?php elseif ($filtre == 'config'): ?>
                            Articles non configurés (Config. Stock)
                        <?php endif; ?>
                    </h5>
                    <small class="text-muted">
                        <?php 
                        if ($per_page > 0 && $total_count > 0) {
                            $start = ($page - 1) * $per_page + 1;
                            $end = min($page * $per_page, $total_count);
                            echo "Affichage de $start à $end sur $total_count articles";
                        } elseif ($total_count > 0) {
                            echo "Affichage de tous les $total_count articles";
                        } else {
                            echo "Aucun article";
                        }
                        ?>
                    </small>
                </div>
            </div>

            <!-- Tableau -->
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Code</th>
                            <th>Désignation</th>
                            <th class="text-end">Stock Initial</th>
                            <th class="text-end">Entrées</th>
                            <th class="text-end">Sorties</th>
                            <th class="text-end">Retours</th>
                            <th class="text-end">Disponible</th>
                            <th class="text-end">Min</th>
                            <th class="text-end">Max</th>
                            <th class="text-center">État</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($articles)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        <p class="mb-0">
                                            <?php if (!empty($search)): ?>
                                                Aucun article ne correspond à votre recherche
                                            <?php else: ?>
                                                Aucun article à afficher.<br>
                                                Vérifiez les filtres appliqués
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($articles as $a): ?>
                                <?php
                                // Déterminer l'état du stock avec colorage des lignes
                                $etat_info = [
                                    'class' => '',
                                    'badge' => '',
                                    'icon' => '',
                                    'text' => ''
                                ];
                                
                                switch ($a['etat_stock']) {
                                    case 'config':
                                        $etat_info = [
                                            'class' => 'table-secondary',
                                            'badge' => 'secondary',
                                            'icon' => 'bi-gear-fill',
                                            'text' => 'Config. Stock'
                                        ];
                                        break;
                                    case 'rupture':
                                        $etat_info = [
                                            'class' => 'table-danger',
                                            'badge' => 'danger',
                                            'icon' => 'bi-exclamation-triangle-fill',
                                            'text' => 'Rupture'
                                        ];
                                        break;
                                    case 'faible':
                                        $etat_info = [
                                            'class' => 'table-warning',
                                            'badge' => 'warning',
                                            'icon' => 'bi-exclamation-circle-fill',
                                            'text' => 'Faible'
                                        ];
                                        break;
                                    case 'eleve':
                                        $etat_info = [
                                            'class' => 'table-info',
                                            'badge' => 'info',
                                            'icon' => 'bi-arrow-up-circle-fill',
                                            'text' => 'Élevé'
                                        ];
                                        break;
                                    default:
                                        $etat_info = [
                                            'class' => 'table-success',
                                            'badge' => 'success',
                                            'icon' => 'bi-check-circle-fill',
                                            'text' => 'Normal'
                                        ];
                                }
                                ?>
                                <tr class="<?php echo $etat_info['class']; ?>">
                                    <td class="fw-bold">
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                            <?php echo htmlspecialchars($a['code_article']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;" 
                                             title="<?php echo htmlspecialchars($a['designation']); ?>">
                                            <?php echo htmlspecialchars($a['designation']); ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-light text-dark">
                                            <?php echo number_format($a['stock_initial'], 0, ',', ' '); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-success bg-opacity-25 text-success">
                                            <?php echo number_format($a['qte_entree'], 0, ',', ' '); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-danger bg-opacity-25 text-danger">
                                            <?php echo number_format($a['qte_sortie'], 0, ',', ' '); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-info bg-opacity-25 text-info">
                                            <?php echo number_format($a['qte_retour'], 0, ',', ' '); ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold fs-6">
                                        <?php if ($a['etat_stock'] == 'config'): ?>
                                            <span class="text-secondary">
                                                <?php echo number_format($a['qte_disponible'], 0, ',', ' '); ?>
                                            </span>
                                        <?php elseif ($a['qte_disponible'] <= 0): ?>
                                            <span class="text-danger">
                                                <?php echo number_format($a['qte_disponible'], 0, ',', ' '); ?>
                                            </span>
                                        <?php elseif ($a['qte_disponible'] <= $a['stock_min']): ?>
                                            <span class="text-warning">
                                                <?php echo number_format($a['qte_disponible'], 0, ',', ' '); ?>
                                            </span>
                                        <?php elseif ($a['qte_disponible'] >= $a['stock_max']): ?>
                                            <span class="text-info">
                                                <?php echo number_format($a['qte_disponible'], 0, ',', ' '); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-success">
                                                <?php echo number_format($a['qte_disponible'], 0, ',', ' '); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($a['stock_min'] > 0): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-dark">
                                                <?php echo number_format($a['stock_min'], 0, ',', ' '); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($a['stock_max'] > 0): ?>
                                            <span class="badge bg-info bg-opacity-10 text-dark">
                                                <?php echo number_format($a['stock_max'], 0, ',', ' '); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-<?php echo $etat_info['badge']; ?> px-3 py-2">
                                            <i class="bi <?php echo $etat_info['icon']; ?> me-1"></i>
                                            <?php echo $etat_info['text']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($articles)): ?>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end">Totaux :</th>
                                <th class="text-end">
                                    <?php echo number_format(array_sum(array_column($articles, 'stock_initial')), 0, ',', ' '); ?>
                                </th>
                                <th class="text-end text-success">
                                    <?php echo number_format(array_sum(array_column($articles, 'qte_entree')), 0, ',', ' '); ?>
                                </th>
                                <th class="text-end text-danger">
                                    <?php echo number_format(array_sum(array_column($articles, 'qte_sortie')), 0, ',', ' '); ?>
                                </th>
                                <th class="text-end text-info">
                                    <?php echo number_format(array_sum(array_column($articles, 'qte_retour')), 0, ',', ' '); ?>
                                </th>
                                <th class="text-end fw-bold">
                                    <?php echo number_format(array_sum(array_column($articles, 'qte_disponible')), 0, ',', ' '); ?>
                                </th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Pagination améliorée -->
            <?php if ($per_page > 0 && $total_pages > 1): ?>
                <div class="mt-4">
                    <!-- Boutons Précédent/Suivant en haut -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <?php if ($page > 1): ?>
                                <a href="<?php echo buildPageUrl($page - 1); ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-chevron-left me-2"></i>
                                    Précédent
                                </a>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                    <i class="bi bi-chevron-left me-2"></i>
                                    Précédent
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-muted small">
                            Page <strong><?php echo $page; ?></strong> sur <strong><?php echo $total_pages; ?></strong>
                        </div>
                        
                        <div>
                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo buildPageUrl($page + 1); ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    Suivant
                                    <i class="bi bi-chevron-right ms-2"></i>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                    Suivant
                                    <i class="bi bi-chevron-right ms-2"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pagination numérotée -->
                    <nav aria-label="Navigation des pages">
                        <ul class="pagination justify-content-center pagination-sm mb-0">
                            <!-- Premier -->
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo buildPageUrl(1); ?>">
                                    <i class="bi bi-chevron-double-left"></i>
                                </a>
                            </li>
                            
                            <!-- Précédent -->
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo buildPageUrl($page - 1); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Pages -->
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildPageUrl($i); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Suivant -->
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo buildPageUrl($page + 1); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                            
                            <!-- Dernier -->
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo buildPageUrl($total_pages); ?>">
                                    <i class="bi bi-chevron-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Fonction simple pour effacer la recherche
function clearSearch() {
    document.getElementById('search').value = '';
    document.getElementById('searchForm').submit();
}

// Recherche simple sans timeout
document.getElementById('search')?.addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        this.form.submit();
    }
});

// Focus automatique
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search') && urlParams.get('search')) {
        document.getElementById('search')?.focus();
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>