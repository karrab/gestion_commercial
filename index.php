<?php
$page_title = 'Tableau de bord';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();

// Statistiques générales
// Nombre total d'articles
$db->prepare("SELECT COUNT(*) as total FROM articles WHERE actif = 1");
$total_articles = $db->fetch()['total'];

// Nombre d'entrées ce mois
$db->prepare("SELECT COUNT(*) as total FROM entrees
              WHERE MONTH(date) = MONTH(CURRENT_DATE())
              AND YEAR(date) = YEAR(CURRENT_DATE())");
$entrees_mois = $db->fetch()['total'];

// Nombre de sorties ce mois
$db->prepare("SELECT COUNT(*) as total FROM sorties
              WHERE MONTH(date) = MONTH(CURRENT_DATE())
              AND YEAR(date) = YEAR(CURRENT_DATE())");
$sorties_mois = $db->fetch()['total'];

// Articles en alerte stock (uniquement ceux avec stock_min ou stock_max configuré)
$db->prepare("SELECT COUNT(*) as total FROM articles
              WHERE actif = 1
              AND (stock_min > 0 OR stock_max > 0)
              AND (qte_disponible < stock_min OR qte_disponible > stock_max)");
$articles_stock_faible = $db->fetch()['total'];
$articles_rupture = 0; // inclus dans le compteur unifié ci-dessus

// Valeur totale du stock (approximative)
$db->prepare("SELECT SUM(qte_disponible) as total FROM articles WHERE actif = 1");
$valeur_stock = $db->fetch()['total'] ?? 0;

// Articles les plus utilisés (sorties)
$db->prepare("SELECT a.code_article, a.designation, SUM(ls.qte_sortie) as total_sortie
              FROM articles a
              INNER JOIN ligne_sorties ls ON a.id = ls.article_id
              GROUP BY a.id
              ORDER BY total_sortie DESC
              LIMIT 5");
$top_articles_sortis = $db->fetchAll();

// Dernières entrées
$db->prepare("SELECT e.id, e.date, f.nom_complet as fournisseur, COUNT(le.id) as nb_articles
              FROM entrees e
              INNER JOIN fournisseurs f ON e.fournisseur_id = f.id
              LEFT JOIN ligne_entrees le ON e.id = le.entree_id
              GROUP BY e.id
              ORDER BY e.date DESC, e.id DESC
              LIMIT 5");
$dernieres_entrees = $db->fetchAll();

// Dernières sorties
$db->prepare("SELECT s.id, s.date, ser.nom as service, CONCAT(emp.nom, ' ', emp.prenom) as employe
              FROM sorties s
              INNER JOIN services ser ON s.service_id = ser.id
              INNER JOIN employes emp ON s.employe_id = emp.id
              ORDER BY s.date DESC, s.id DESC
              LIMIT 5");
$dernieres_sorties = $db->fetchAll();

// Articles en alerte stock
$db->prepare("SELECT code_article, designation, qte_disponible, stock_min, stock_max
              FROM articles
              WHERE actif = 1 AND (qte_disponible < stock_min OR qte_disponible > stock_max)
              AND (stock_min > 0 OR stock_max > 0)
              ORDER BY qte_disponible ASC
              LIMIT 10");
$articles_alerte = $db->fetchAll();
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <!-- En-tête du tableau de bord -->
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-speedometer2"></i> Tableau de bord</h2>
            <p class="text-muted">Vue d'ensemble de la gestion du stock</p>
        </div>
    </div>

    <!-- Cartes de statistiques -->
    <div class="row mb-4">
        <!-- Total articles -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card primary">
                <div class="card-body position-relative">
                    <i class="bi bi-box-seam stat-icon"></i>
                    <p class="stat-value text-primary"><?php echo number_format($total_articles, 0, ',', ' '); ?></p>
                    <p class="stat-label">Articles actifs</p>
                    <a href="<?php echo BASE_URL; ?>/pages/articles/index.php" class="stretched-link"></a>
                </div>
            </div>
        </div>

        <!-- Entrées du mois -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card success">
                <div class="card-body position-relative">
                    <i class="bi bi-box-arrow-in-down stat-icon"></i>
                    <p class="stat-value text-success"><?php echo number_format($entrees_mois, 0, ',', ' '); ?></p>
                    <p class="stat-label">Entrées ce mois</p>
                    <a href="<?php echo BASE_URL; ?>/pages/entrees/index.php" class="stretched-link"></a>
                </div>
            </div>
        </div>

        <!-- Sorties du mois -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card warning">
                <div class="card-body position-relative">
                    <i class="bi bi-box-arrow-up stat-icon"></i>
                    <p class="stat-value text-warning"><?php echo number_format($sorties_mois, 0, ',', ' '); ?></p>
                    <p class="stat-label">Sorties ce mois</p>
                    <a href="<?php echo BASE_URL; ?>/pages/sorties/index.php" class="stretched-link"></a>
                </div>
            </div>
        </div>

        <!-- Stock faible -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card stat-card danger">
                <div class="card-body position-relative">
                    <i class="bi bi-exclamation-triangle stat-icon"></i>
                    <p class="stat-value text-danger"><?php echo number_format($articles_stock_faible + $articles_rupture, 0, ',', ' '); ?></p>
                    <p class="stat-label">Alertes stock</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques et tableaux -->
    <div class="row">
        <!-- Articles les plus sortis -->
        <div class="col-xl-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> Articles les plus utilisés
                </div>
                <div class="card-body">
                    <?php if (count($top_articles_sortis) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Désignation</th>
                                        <th class="text-end">Quantité sortie</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_articles_sortis as $article): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($article['code_article']); ?></code></td>
                                            <td><?php echo htmlspecialchars($article['designation']); ?></td>
                                            <td class="text-end">
                                                <span class="badge bg-primary"><?php echo number_format($article['total_sortie'], 0, ',', ' '); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">Aucune donnée disponible</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Alertes stock -->
        <div class="col-xl-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-exclamation-circle"></i> Alertes de stock
                </div>
                <div class="card-body">
                    <?php if (count($articles_alerte) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Désignation</th>
                                        <th class="text-end">Stock</th>
                                        <th class="text-center">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($articles_alerte as $article): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($article['code_article']); ?></code></td>
                                            <td><?php echo htmlspecialchars($article['designation']); ?></td>
                                            <td class="text-end"><?php echo number_format($article['qte_disponible'], 0, ',', ' '); ?></td>
                                            <td class="text-center">
                                                <?php if ($article['qte_disponible'] <= 0): ?>
                                                    <span class="stock-alert critical">Rupture</span>
                                                <?php elseif ($article['qte_disponible'] < $article['stock_min']): ?>
                                                    <span class="stock-alert low">Stock faible</span>
                                                <?php else: ?>
                                                    <span class="stock-alert low">Stock élevé</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle"></i> Aucune alerte de stock !
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Derniers mouvements -->
    <div class="row">
        <!-- Dernières entrées -->
        <div class="col-xl-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-arrow-in-down"></i> Dernières entrées</span>
                    <?php if ($auth->hasPermission('entrees', 'view')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/entrees/index.php" class="btn btn-sm btn-outline-primary">
                            Voir tout <i class="bi bi-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (count($dernieres_entrees) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Fournisseur</th>
                                        <th class="text-center">Articles</th>
                                        <th class="text-center no-print">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dernieres_entrees as $entree): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($entree['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($entree['fournisseur']); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?php echo $entree['nb_articles']; ?></span>
                                            </td>
                                            <td class="text-center no-print">
                                                <?php if ($auth->hasPermission('entrees', 'view')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/entrees/view.php?id=<?php echo $entree['id']; ?>"
                                                       class="btn btn-sm btn-outline-primary" title="Voir">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">Aucune entrée récente</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Dernières sorties -->
        <div class="col-xl-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-arrow-up"></i> Dernières sorties</span>
                    <?php if ($auth->hasPermission('sorties', 'view')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/sorties/index.php" class="btn btn-sm btn-outline-primary">
                            Voir tout <i class="bi bi-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (count($dernieres_sorties) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Service</th>
                                        <th>Employé</th>
                                        <th class="text-center no-print">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dernieres_sorties as $sortie): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($sortie['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($sortie['service']); ?></td>
                                            <td><?php echo htmlspecialchars($sortie['employe']); ?></td>
                                            <td class="text-center no-print">
                                                <?php if ($auth->hasPermission('sorties', 'view')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/sorties/view.php?id=<?php echo $sortie['id']; ?>"
                                                       class="btn btn-sm btn-outline-primary" title="Voir">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">Aucune sortie récente</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>