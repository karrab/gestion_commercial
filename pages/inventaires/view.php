<?php
/**
 * Page de visualisation détaillée d'un inventaire
 * Permet la saisie des quantités physiques en temps réel (si état = en_cours et non réinitialisé)
 */

$page_title = 'Détails inventaire';
require_once __DIR__ . '/../../includes/header.php';

// Vérifier les permissions
$auth->requirePermission('inventaires', 'view');

// Récupérer l'inventaire
$db = Database::getInstance();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT i.*,
               ei.nom as equipe_nom,
               u.nom as user_nom, u.prenom as user_prenom,
               uv.nom as valideur_nom, uv.prenom as valideur_prenom
        FROM inventaires i
        LEFT JOIN equipes_inventaire ei ON i.equipe_id = ei.id
        LEFT JOIN users u ON i.user_id = u.id
        LEFT JOIN users uv ON i.user_validation_id = uv.id
        WHERE i.id = :id";

$db->prepare($sql);
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

// Ajouter la référence si elle n'existe pas (anciens inventaires)
if (empty($inventaire['reference'])) {
    $inventaire['reference'] = 'INV-' . $id;
}

// Récupérer les lignes d'inventaire
$db->prepare("SELECT * FROM ligne_inventaires WHERE inventaire_id = :id ORDER BY qte_theorique DESC");
$db->bind(':id', $id);
$lignes = $db->fetchAll();

// Calculer $est_reinitialise tôt pour conditionner la requête suivante
$est_reinitialise = ($inventaire['etat'] == 'en_cours' && $inventaire['reinitialise'] == 1);

// Récupérer les articles non inclus UNIQUEMENT pour les inventaires en cours non réinitialisés
// (nécessaire uniquement pour le modal d'ajout d'article)
$articles_non_inclus = [];
if ($inventaire['etat'] == 'en_cours' && !$est_reinitialise) {
    $sql_non_inclus = "SELECT a.id, a.code_article, a.designation, a.qte_disponible
                      FROM articles a
                      WHERE a.actif = 1
                      AND NOT EXISTS (
                          SELECT 1 FROM ligne_inventaires li
                          WHERE li.inventaire_id = :inventaire_id AND li.article_id = a.id
                      )
                      ORDER BY a.code_article";
    $db->prepare($sql_non_inclus);
    $db->bind(':inventaire_id', $id);
    $articles_non_inclus = $db->fetchAll();
}

// Calculer les statistiques
$total_articles = count($lignes);
$total_ecarts = 0;
$ecarts_positifs = 0;
$ecarts_negatifs = 0;
$articles_ok = 0;
$sum_theorique = 0;
$sum_physique = 0;

foreach ($lignes as $ligne) {
    $sum_theorique += floatval($ligne['qte_theorique']);
    $sum_physique += floatval($ligne['qte_physique']);

    $ecart = floatval($ligne['ecart']);
    if ($ecart > 0) {
        $ecarts_positifs++;
    } elseif ($ecart < 0) {
        $ecarts_negatifs++;
    } else {
        $articles_ok++;
    }

    $total_ecarts += abs($ecart);
}

// Définir l'état config en fonction de l'état et de la réinitialisation
if ($est_reinitialise) {
    $etat_config = [
        'badge' => 'bg-warning text-dark',
        'label' => 'Réinitialisé',
        'icon' => 'bi-arrow-repeat',
        'description' => 'Inventaire réinitialisé. Les quantités sont figées. Seul un administrateur peut clôturer.'
    ];
} else {
    $etat_config = match($inventaire['etat']) {
        'en_cours' => [
            'badge' => 'bg-warning text-dark',
            'label' => 'En cours',
            'icon' => 'bi-hourglass-split',
            'description' => 'Vous pouvez saisir les quantités physiques. Les modifications sont enregistrées automatiquement.'
        ],
        'valide' => [
            'badge' => 'bg-info',
            'label' => 'Validé',
            'icon' => 'bi-check-circle',
            'description' => 'Inventaire validé. Seul un administrateur peut le réinitialiser.'
        ],
        'cloture' => [
            'badge' => 'bg-success',
            'label' => 'Clôturé',
            'icon' => 'bi-lock',
            'description' => 'Inventaire clôturé. Aucune modification possible.'
        ],
        default => [
            'badge' => 'bg-secondary',
            'label' => $inventaire['etat'],
            'icon' => 'bi-question-circle',
            'description' => ''
        ]
    };
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2>
                        <i class="bi bi-clipboard-check"></i>
                        Inventaire: <strong><?php echo htmlspecialchars($inventaire['reference']); ?></strong>
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($inventaire['reference']); ?></li>
                        </ol>
                    </nav>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge <?php echo $etat_config['badge']; ?> fs-4">
                        <i class="<?php echo $etat_config['icon']; ?>"></i>
                        <?php echo $etat_config['label']; ?>
                        <?php if ($est_reinitialise): ?>
                            <i class="bi bi-arrow-repeat ms-1" title="Réinitialisé"></i>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerte état -->
    <?php if (!empty($etat_config['description'])): ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-<?php echo $inventaire['etat'] == 'en_cours' ? 'warning' : ($inventaire['etat'] == 'valide' ? 'info' : 'success'); ?> mb-0">
                    <i class="<?php echo $etat_config['icon']; ?>"></i>
                    <strong><?php echo $etat_config['label']; ?> :</strong>
                    <?php echo $etat_config['description']; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Messages d'information selon l'état -->
    <?php if ($inventaire['etat'] == 'valide'): ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i>
                    <strong>Inventaire validé :</strong>
                    Les stocks ont été mis à jour. Seul un administrateur peut réinitialiser cet inventaire.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($est_reinitialise): ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Inventaire réinitialisé :</strong>
                    Les quantités physiques sont figées. Seul un administrateur peut clôturer cet inventaire.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($inventaire['etat'] == 'cloture'): ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-success mb-0">
                    <i class="bi bi-lock"></i>
                    <strong>Inventaire clôturé :</strong>
                    Cet inventaire est définitivement figé. Aucune modification n'est possible.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card border-left-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total articles</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $total_articles; ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-box-seam" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card border-left-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Articles OK</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $articles_ok; ?></div>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card border-left-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Écarts positifs</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $ecarts_positifs; ?></div>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-arrow-up-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card border-left-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Écarts négatifs</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $ecarts_negatifs; ?></div>
                        </div>
                        <div class="text-danger">
                            <i class="bi bi-arrow-down-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Colonne principale: Table des articles -->
        <div class="col-lg-9">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap">
                    <span><i class="bi bi-table"></i> Articles inventoriés (<?php echo $total_articles; ?>)</span>
                    <div class="mt-2 mt-md-0">
                        <?php if ($inventaire['etat'] == 'en_cours' && !$est_reinitialise && $auth->hasPermission('inventaires', 'update')): ?>
                            <!-- Bouton pour ajouter un article individuel -->
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAjoutArticle">
                                <i class="bi bi-plus-circle"></i> Ajouter un article
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($inventaire['etat'] == 'en_cours' && count($lignes) > 0 && !$est_reinitialise): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/generer_ecarts.php?id=<?php echo $id; ?>"
                               class="btn btn-light btn-sm mt-2 mt-md-0 ms-2"
                               onclick="return confirm('Calculer les écarts pour tous les articles ?');">
                                <i class="bi bi-calculator"></i> Recalculer les écarts
                            </a>
                        <?php endif; ?>
                        
                        <!-- Bouton d'exportation -->
                        <button type="button" class="btn btn-success btn-sm ms-2" id="btn-export-excel">
                            <i class="bi bi-file-earmark-excel"></i> Exporter liste
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($inventaire['etat'] == 'en_cours' && !$est_reinitialise && count($lignes) > 0): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Mode saisie activé :</strong>
                            Entrez les quantités comptées réellement. Les modifications sont enregistrées automatiquement dès que vous quittez le champ.
                        </div>
                    <?php endif; ?>

                    <?php if ($est_reinitialise): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Mode lecture seule :</strong>
                            Cet inventaire a été réinitialisé. Les quantités ne peuvent plus être modifiées.
                        </div>
                    <?php endif; ?>

                    <!-- Recherche live par code article ou désignation -->
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="search-articles" class="form-control"
                                   placeholder="Rechercher par code article ou désignation...">
                            <button type="button" class="btn btn-outline-secondary" id="btn-clear-search" title="Effacer">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <small class="text-muted" id="search-count"></small>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover align-middle" id="table-articles">
                            <thead class="table-light">
                                <tr>
                                    <th width="15%">Code article</th>
                                    <th width="35%">Désignation</th>
                                    <th width="13%" class="text-end">Qté théorique</th>
                                    <th width="15%" class="text-end">Qté physique</th>
                                    <th width="13%" class="text-end">Écart</th>
                                    <th width="9%" class="text-center">État</th>
                                    <?php if ($inventaire['etat'] == 'en_cours' && !$est_reinitialise): ?>
                                        <th width="5%" class="text-center">Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($lignes) > 0): ?>
                                    <?php foreach ($lignes as $ligne): ?>
                                        <?php
                                        $ecart = floatval($ligne['ecart']);
                                        $ecart_class = '';
                                        $ecart_icon = '';
                                        $row_class = '';

                                        if ($ecart < 0) {
                                            $ecart_class = 'text-danger fw-bold';
                                            $ecart_icon = 'bi-arrow-down-circle text-danger';
                                            $row_class = 'table-danger-light';
                                        } elseif ($ecart > 0) {
                                            $ecart_class = 'text-success fw-bold';
                                            $ecart_icon = 'bi-arrow-up-circle text-success';
                                            $row_class = 'table-warning-light';
                                        } else {
                                            $ecart_class = 'text-muted';
                                            $ecart_icon = 'bi-check-circle text-success';
                                            $row_class = '';
                                        }
                                        ?>
                                        <tr class="<?php echo $row_class; ?>"
                                            data-ligne-id="<?php echo $ligne['id']; ?>"
                                            data-article-id="<?php echo $ligne['article_id']; ?>"
                                            data-code="<?php echo strtolower(htmlspecialchars($ligne['code_article'])); ?>"
                                            data-design="<?php echo strtolower(htmlspecialchars($ligne['designation'])); ?>">
                                            <td><strong><?php echo htmlspecialchars($ligne['code_article']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($ligne['designation']); ?></td>
                                            <td class="text-end">
                                                <span class="badge bg-secondary"><?php echo number_format($ligne['qte_theorique'], 2, ',', ' '); ?></span>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($inventaire['etat'] == 'en_cours' && !$est_reinitialise): ?>
                                                    <!-- Mode saisie uniquement si non réinitialisé -->
                                                    <input type="number"
                                                           class="form-control form-control-sm text-end qte-physique"
                                                           data-ligne-id="<?php echo $ligne['id']; ?>"
                                                           value="<?php echo $ligne['qte_physique']; ?>"
                                                           step="0.01" min="0"
                                                           style="min-width: 100px;">
                                                <?php else: ?>
                                                    <!-- Lecture seule pour inventaires validés, clôturés ou réinitialisés -->
                                                    <span class="badge bg-primary"><?php echo number_format($ligne['qte_physique'], 2, ',', ' '); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end ecart-cell <?php echo $ecart_class; ?>">
                                                <?php
                                                if ($ecart != 0) {
                                                    echo ($ecart > 0 ? '+' : '') . number_format($ecart, 2, ',', ' ');
                                                } else {
                                                    echo '0';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-center status-cell">
                                                <i class="<?php echo $ecart_icon; ?>"></i>
                                            </td>
                                            <?php if ($inventaire['etat'] == 'en_cours' && !$est_reinitialise): ?>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-danger btn-supprimer-article" 
                                                            data-ligne-id="<?php echo $ligne['id']; ?>"
                                                            data-article-code="<?php echo htmlspecialchars($ligne['code_article']); ?>"
                                                            title="Supprimer cet article">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>

                                    <!-- Ligne de total -->
                                    <tr class="table-secondary fw-bold">
                                        <td colspan="2" class="text-end">TOTAL :</td>
                                        <td class="text-end"><?php echo number_format($sum_theorique, 2, ',', ' '); ?></td>
                                        <td class="text-end"><?php echo number_format($sum_physique, 2, ',', ' '); ?></td>
                                        <td class="text-end <?php echo ($sum_physique - $sum_theorique) < 0 ? 'text-danger' : (($sum_physique - $sum_theorique) > 0 ? 'text-success' : 'text-muted'); ?>">
                                            <?php
                                            $ecart_total = $sum_physique - $sum_theorique;
                                            echo ($ecart_total > 0 ? '+' : '') . number_format($ecart_total, 2, ',', ' ');
                                            ?>
                                        </td>
                                        <td></td>
                                        <?php if ($inventaire['etat'] == 'en_cours' && !$est_reinitialise): ?>
                                            <td></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo ($inventaire['etat'] == 'en_cours' && !$est_reinitialise) ? '7' : '6'; ?>" class="text-center text-muted py-5">
                                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                            <p class="mt-3">Aucun article dans cet inventaire</p>
                                            <?php if ($inventaire['etat'] == 'en_cours' && !$est_reinitialise && $auth->hasPermission('inventaires', 'update')): ?>
                                                <div class="mt-3">
                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjoutArticle">
                                                        <i class="bi bi-plus-circle"></i> Ajouter un article
                                                    </button>
                                                    <div class="btn-group mt-2" role="group">
                                                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/ajouter_articles.php?id=<?php echo $id; ?>&type=all"
                                                           class="btn btn-outline-primary"
                                                           onclick="return confirm('Ajouter tous les articles à l\'inventaire ?');">
                                                            <i class="bi bi-box"></i> Ajouter tous les articles
                                                        </a>
                                                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/ajouter_articles.php?id=<?php echo $id; ?>&type=active"
                                                           class="btn btn-outline-success"
                                                           onclick="return confirm('Ajouter les articles actifs à l\'inventaire ?');">
                                                            <i class="bi bi-check-circle"></i> Ajouter les articles actifs
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-2 px-1" id="pagination-container">
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-sm btn-outline-secondary" id="btn-prev" disabled>
                                    <i class="bi bi-chevron-left"></i> Précédent
                                </button>
                                <span class="text-muted small" id="pagination-info"></span>
                                <button class="btn btn-sm btn-outline-secondary" id="btn-next" disabled>
                                    Suivant <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label class="form-label mb-0 small text-muted" for="per-page-select">Par page :</label>
                                <select class="form-select form-select-sm" id="per-page-select" style="width:auto;">
                                    <option value="20" selected>20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="200">200</option>
                                    <option value="500">500</option>
                                    <option value="1000">1000</option>
                                    <option value="0">Tous</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne latérale: Informations et actions -->
        <div class="col-lg-3">
            <!-- Informations générales -->
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">
                    <i class="bi bi-info-circle"></i> Informations
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong><i class="bi bi-tag"></i> Référence :</strong><br>
                        <span class="text-primary"><?php echo htmlspecialchars($inventaire['reference']); ?></span>
                    </p>
                    <hr>
                    <p class="mb-2">
                        <strong><i class="bi bi-calendar-event"></i> Date :</strong><br>
                        <?php echo date('d/m/Y', strtotime($inventaire['date_debut'])); ?>
                    </p>
                    <p class="mb-2">
                        <strong><i class="bi bi-clock"></i> Créé le :</strong><br>
                        <?php echo date('d/m/Y H:i', strtotime($inventaire['created_at'])); ?>
                    </p>
                    <hr>
                    <p class="mb-2">
                        <strong><i class="bi bi-people"></i> Équipe :</strong><br>
                        <?php echo $inventaire['equipe_nom'] ? htmlspecialchars($inventaire['equipe_nom']) : '<span class="text-muted">Aucune</span>'; ?>
                    </p>
                    <p class="mb-2">
                        <strong><i class="bi bi-person-badge"></i> Créé par :</strong><br>
                        <?php echo htmlspecialchars($inventaire['user_nom'] . ' ' . $inventaire['user_prenom']); ?>
                    </p>
                    <?php if ($inventaire['etat'] != 'en_cours' && $inventaire['user_validation_id']): ?>
                        <hr>
                        <p class="mb-2">
                            <strong><i class="bi bi-person-check"></i> Validé par :</strong><br>
                            <?php echo htmlspecialchars($inventaire['valideur_nom'] . ' ' . $inventaire['valideur_prenom']); ?>
                        </p>
                        <?php if ($inventaire['date_validation']): ?>
                            <p class="mb-0">
                                <strong><i class="bi bi-calendar-check"></i> Date validation :</strong><br>
                                <?php echo date('d/m/Y H:i', strtotime($inventaire['date_validation'])); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="card mb-3">
                <div class="card-header bg-warning">
                    <i class="bi bi-gear"></i> Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php 
                        // Déterminer si l'inventaire a été réinitialisé
                        $est_reinitialise = ($inventaire['etat'] == 'en_cours' && $inventaire['reinitialise'] == 1);
                        
                        if ($inventaire['etat'] == 'en_cours' && $auth->hasPermission('inventaires', 'update')): ?>
                            <?php if (!$est_reinitialise): ?>
                                <!-- Inventaire en cours normal -->
                                <a href="<?php echo BASE_URL; ?>/pages/inventaires/edit.php?id=<?php echo $id; ?>"
                                   class="btn btn-warning">
                                    <i class="bi bi-pencil"></i> Modifier
                                </a>
                                <a href="<?php echo BASE_URL; ?>/pages/inventaires/valider.php?id=<?php echo $id; ?>"
                                   class="btn btn-info"
                                   onclick="return confirm('Valider cet inventaire ?\\n\\nLes stocks seront mis à jour avec les quantités physiques.');">
                                    <i class="bi bi-check-circle"></i> Valider
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($inventaire['etat'] == 'valide' && $auth->isAdmin()): ?>
                            <!-- Inventaire validé : on montre Réinitialiser, on cache Clôturer -->
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/reinitialiser.php?id=<?php echo $id; ?>"
                               class="btn btn-outline-warning"
                               onclick="return confirm('Réinitialiser en \"En cours\" ?\\n\\nAttention: Après réinitialisation, les quantités seront figées et seul l\\'administrateur pourra clôturer.');">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                            </a>
                        <?php endif; ?>

                        <?php if ($est_reinitialise && $auth->isAdmin()): ?>
                            <!-- Inventaire réinitialisé (en_cours avec reinitialise=1) : on montre Clôturer -->
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/cloturer.php?id=<?php echo $id; ?>"
                               class="btn btn-success"
                               onclick="return confirm('Clôturer cet inventaire réinitialisé ?\\n\\nCette action est irréversible.');">
                                <i class="bi bi-lock"></i> Clôturer
                            </a>
                        <?php endif; ?>

                        <?php if ($inventaire['etat'] == 'en_cours' && $auth->hasPermission('inventaires', 'delete') && !$est_reinitialise): ?>
                            <hr>
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/delete.php?id=<?php echo $id; ?>"
                               class="btn btn-danger"
                               onclick="return confirm('Supprimer cet inventaire ?\\n\\nCette action est irréversible.');">
                                <i class="bi bi-trash"></i> Supprimer
                            </a>
                        <?php endif; ?>

                        <?php if ($inventaire['etat'] == 'cloture'): ?>
                            <!-- Inventaire clôturé : aucun bouton d'action sauf Retour -->
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Cet inventaire est clôturé. Aucune action n'est possible.
                            </div>
                        <?php endif; ?>

                        <hr>
                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
            </div>

            <!-- Résumé des écarts -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-bar-chart"></i> Résumé
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-box-seam"></i> Total articles :</span>
                        <strong><?php echo $total_articles; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-success"><i class="bi bi-check-circle"></i> OK :</span>
                        <strong class="text-success"><?php echo $articles_ok; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-warning"><i class="bi bi-arrow-up-circle"></i> Excédents :</span>
                        <strong class="text-warning"><?php echo $ecarts_positifs; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-danger"><i class="bi bi-arrow-down-circle"></i> Manquants :</span>
                        <strong class="text-danger"><?php echo $ecarts_negatifs; ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span><strong>Total écarts abs. :</strong></span>
                        <strong class="<?php echo $total_ecarts > 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo number_format($total_ecarts, 2, ',', ' '); ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un article -->
<?php if ($inventaire['etat'] == 'en_cours' && !$est_reinitialise && $auth->hasPermission('inventaires', 'update')): ?>
<div class="modal fade" id="modalAjoutArticle" tabindex="-1" aria-labelledby="modalAjoutArticleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAjoutArticleLabel">
                    <i class="bi bi-plus-circle"></i> Ajouter un article à l'inventaire
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Sélectionnez un article à ajouter à l'inventaire. Seuls les articles non encore inclus sont affichés.
                </div>
                
                <!-- Barre de recherche -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchArticle" class="form-control" placeholder="Rechercher par code ou désignation...">
                        <button class="btn btn-outline-secondary" type="button" id="btnResetSearch">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Liste des articles disponibles -->
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover" id="tableArticlesDisponibles">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">Code article</th>
                                <th width="50%">Désignation</th>
                                <th width="15%" class="text-end">Stock disponible</th>
                                <th width="15%" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($articles_non_inclus) > 0): ?>
                                <?php foreach ($articles_non_inclus as $article): ?>
                                    <tr data-article-id="<?php echo $article['id']; ?>">
                                        <td><strong><?php echo htmlspecialchars($article['code_article']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($article['designation']); ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-secondary"><?php echo number_format($article['qte_disponible'], 2, ',', ' '); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-success btn-ajouter-article"
                                                    data-article-id="<?php echo $article['id']; ?>"
                                                    data-article-code="<?php echo htmlspecialchars($article['code_article']); ?>"
                                                    data-article-designation="<?php echo htmlspecialchars($article['designation']); ?>"
                                                    data-article-qte="<?php echo $article['qte_disponible']; ?>">
                                                <i class="bi bi-plus-circle"></i> Ajouter
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                                        <p class="mt-2">Tous les articles sont déjà dans l'inventaire</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-warning mt-3">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Note :</strong> L'article sera ajouté avec la quantité théorique égale au stock disponible et la quantité physique à 0.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal pour l'exportation -->
<div class="modal fade" id="modalExport" tabindex="-1" aria-labelledby="modalExportLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalExportLabel">
                    <i class="bi bi-file-earmark-excel"></i> Exporter la liste des articles
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Cette fonctionnalité permet d'exporter la liste des articles pour faciliter le comptage physique.
                </div>
                
                <form id="form-export" method="POST" action="<?php echo BASE_URL; ?>/pages/inventaires/export_inventaire.php">
                    <input type="hidden" name="inventaire_id" value="<?php echo $id; ?>">
                    
                    <div class="mb-3">
                        <label for="format" class="form-label">Format d'exportation</label>
                        <select class="form-select" id="format" name="format">
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="csv">CSV (.csv)</option>
                            <option value="pdf">PDF (.pdf)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="filtre" class="form-label">Filtrer les articles</label>
                        <select class="form-select" id="filtre" name="filtre">
                            <option value="tous">Tous les articles</option>
                            <option value="avec_ecart">Avec écart seulement</option>
                            <option value="positifs">Écarts positifs (excédents)</option>
                            <option value="negatifs">Écarts négatifs (manquants)</option>
                            <option value="conformes">Articles conformes</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Colonnes à inclure</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="col_code" name="colonnes[]" value="code" checked>
                            <label class="form-check-label" for="col_code">Code article</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="col_designation" name="colonnes[]" value="designation" checked>
                            <label class="form-check-label" for="col_designation">Désignation</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="col_qte_theorique" name="colonnes[]" value="qte_theorique" checked>
                            <label class="form-check-label" for="col_qte_theorique">Qté théorique</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="col_comptage1" name="colonnes[]" value="comptage1" checked>
                            <label class="form-check-label" for="col_comptage1">Comptage 1</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="col_comptage2" name="colonnes[]" value="comptage2" checked>
                            <label class="form-check-label" for="col_comptage2">Comptage 2</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="col_comptage3" name="colonnes[]" value="comptage3" checked>
                            <label class="form-check-label" for="col_comptage3">Comptage 3</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="col_moyenne" name="colonnes[]" value="moyenne">
                            <label class="form-check-label" for="col_moyenne">Moyenne des comptages</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="col_ecart" name="colonnes[]" value="ecart">
                            <label class="form-check-label" for="col_ecart">Écart</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="col_observation" name="colonnes[]" value="observation">
                            <label class="form-check-label" for="col_observation">Observation</label>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Note :</strong> Les colonnes "Comptage 1", "Comptage 2" et "Comptage 3" seront vides dans le fichier exporté pour être remplies manuellement lors du comptage physique.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Annuler
                </button>
                <button type="submit" form="form-export" class="btn btn-success">
                    <i class="bi bi-download"></i> Exporter
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary { border-left: 4px solid #0d6efd; }
.border-left-success { border-left: 4px solid #198754; }
.border-left-warning { border-left: 4px solid #ffc107; }
.border-left-danger { border-left: 4px solid #dc3545; }
.text-xs { font-size: 0.75rem; }
.table-danger-light { background-color: #f8d7da; }
.table-warning-light { background-color: #fff3cd; }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
// Définir la constante BASE_URL pour JavaScript
const BASE_URL = '<?php echo BASE_URL; ?>';

// JavaScript pour la gestion de l'inventaire
$(document).ready(function() {

    // ── Pagination ────────────────────────────────────────────────────────────
    var currentPage = 1;
    var perPage = 20;

    function getFilteredRows() {
        return $('#table-articles tbody tr').not('.filter-hidden');
    }

    function renderPagination() {
        var rows  = getFilteredRows();
        var total = rows.length;

        // Tout masquer d'abord
        rows.addClass('page-hidden').hide();

        if (perPage === 0 || total === 0) {
            rows.removeClass('page-hidden').show();
            var totalPages = 1;
            currentPage = 1;
            $('#pagination-info').text(total > 0 ? total + ' article(s)' : '');
            $('#btn-prev').prop('disabled', true);
            $('#btn-next').prop('disabled', true);
            return;
        }

        var totalPages = Math.ceil(total / perPage);
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        var start = (currentPage - 1) * perPage;
        var end   = start + perPage;

        rows.each(function(i) {
            if (i >= start && i < end) {
                $(this).removeClass('page-hidden').show();
            }
        });

        $('#pagination-info').text('Page ' + currentPage + ' / ' + totalPages + ' — ' + total + ' article(s)');
        $('#btn-prev').prop('disabled', currentPage <= 1);
        $('#btn-next').prop('disabled', currentPage >= totalPages);
    }

    $('#btn-prev').on('click', function() {
        if (currentPage > 1) { currentPage--; renderPagination(); }
    });

    $('#btn-next').on('click', function() {
        var totalPages = Math.ceil(getFilteredRows().length / perPage);
        if (currentPage < totalPages) { currentPage++; renderPagination(); }
    });

    $('#per-page-select').on('change', function() {
        perPage = parseInt($(this).val());
        currentPage = 1;
        renderPagination();
    });

    // ── Recherche live par code article ou désignation ────────────────────────
    function updateSearchCount() {
        var filtered = getFilteredRows().length;
        var total    = $('#table-articles tbody tr').length;
        if ($('#search-articles').val().trim() !== '') {
            $('#search-count').text(filtered + ' résultat(s) sur ' + total + ' article(s)');
        } else {
            $('#search-count').text('');
        }
    }

    $('#search-articles').on('input', function() {
        var term = $(this).val().trim().toLowerCase();
        $('#table-articles tbody tr').each(function() {
            var code   = ($(this).attr('data-code')   || '').toLowerCase();
            var design = ($(this).attr('data-design') || '').toLowerCase();
            var match  = (code.indexOf(term) !== -1 || design.indexOf(term) !== -1);
            if (match) {
                $(this).removeClass('filter-hidden');
            } else {
                $(this).addClass('filter-hidden').hide();
            }
        });
        currentPage = 1;
        renderPagination();
        updateSearchCount();
    });

    $('#btn-clear-search').on('click', function() {
        $('#search-articles').val('').trigger('input').focus();
    });

    // Initialiser la pagination
    renderPagination();

    // ── Fonctionnalités de saisie (uniquement inventaire en cours non réinitialisé) ──
    <?php if ($inventaire['etat'] == 'en_cours' && !$est_reinitialise): ?>

    // Sélection focus automatique sur premier input
    $('.qte-physique').first().focus().select();

    // Mise à jour automatique des quantités physiques
    $('.qte-physique').on('change', function() {
        const input = $(this);
        const ligneId = input.data('ligne-id');
        const qte = input.val();
        const row = input.closest('tr');

        console.log('Changement détecté - Ligne ID:', ligneId, ', Qté:', qte);

        // Désactiver pendant traitement
        input.prop('disabled', true);
        input.removeClass('is-valid is-invalid border-success border-danger');
        input.addClass('border-warning bg-warning bg-opacity-10');

        // Afficher indicateur de chargement dans la cellule état
        row.find('.status-cell').html('<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>');

        $.ajax({
            url: BASE_URL + '/pages/inventaires/update_qte_physique.php',
            method: 'POST',
            dataType: 'json',
            data: {
                ligne_id: ligneId,
                qte_physique: qte
            },
            success: function(response) {
                console.log('Réponse serveur:', response);

                if (response.success) {
                    // Mise à jour réussie
                    input.removeClass('border-warning bg-warning bg-opacity-10');
                    input.addClass('is-valid border-success');

                    // Mettre à jour l'écart affiché
                    const ecart = parseFloat(response.ecart);
                    const ecartCell = row.find('.ecart-cell');

                    let ecartText = ecart.toFixed(2).replace('.', ',');
                    if (ecart > 0) ecartText = '+' + ecartText;
                    else if (ecart === 0) ecartText = '0';

                    ecartCell.text(ecartText);
                    ecartCell.removeClass('text-danger text-success text-muted fw-bold');

                    if (ecart < 0) {
                        ecartCell.addClass('text-danger fw-bold');
                    } else if (ecart > 0) {
                        ecartCell.addClass('text-success fw-bold');
                    } else {
                        ecartCell.addClass('text-muted');
                    }

                    // Mettre à jour l'icône d'état
                    const icon = ecart < 0 ? '<i class="bi-arrow-down-circle text-danger"></i>' :
                                (ecart > 0 ? '<i class="bi-arrow-up-circle text-success"></i>' :
                                '<i class="bi-check-circle text-success"></i>');
                    row.find('.status-cell').html(icon);

                    // Mettre à jour la couleur de la ligne
                    row.removeClass('table-danger-light table-warning-light');
                    if (ecart < 0) row.addClass('table-danger-light');
                    else if (ecart > 0) row.addClass('table-warning-light');

                    // Enlever le feedback après 1.5 secondes
                    setTimeout(() => {
                        input.removeClass('is-valid border-success');
                        input.prop('disabled', false);
                    }, 1500);

                } else {
                    // Erreur retournée par le serveur
                    input.removeClass('border-warning bg-warning bg-opacity-10');
                    input.addClass('is-invalid border-danger');
                    row.find('.status-cell').html('<i class="bi-x-circle text-danger"></i>');

                    console.error('Erreur:', response.error);
                    alert('❌ Erreur: ' + (response.error || 'Erreur inconnue'));

                    input.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                // Erreur Ajax
                input.removeClass('border-warning bg-warning bg-opacity-10');
                input.addClass('is-invalid border-danger');
                row.find('.status-cell').html('<i class="bi-x-circle text-danger"></i>');

                console.error('Erreur Ajax:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });

                alert('❌ Erreur de communication avec le serveur.\n\n' +
                      'Status: ' + status + '\n' +
                      'Error: ' + error + '\n\n' +
                      'Réponse: ' + xhr.responseText.substring(0, 200));

                input.prop('disabled', false);
            }
        });
    });

    // Touche Entrée pour passer au champ suivant
    $('.qte-physique').on('keypress', function(e) {
        if (e.which === 13) { // Entrée
            e.preventDefault();
            $(this).trigger('change');

            // Focus sur le prochain input
            const nextInput = $(this).closest('tr').next('tr').find('.qte-physique');
            if (nextInput.length) {
                setTimeout(() => {
                    nextInput.focus().select();
                }, 200);
            }
        }
    });

    // Focus automatique au clic sur la ligne
    $('tr[data-ligne-id]').on('click', function(e) {
        if (!$(e.target).hasClass('qte-physique') && !$(e.target).hasClass('btn-supprimer-article')) {
            $(this).find('.qte-physique').focus().select();
        }
    });

    // ============================================
    // FONCTIONNALITÉ D'AJOUT D'ARTICLE UN PAR UN
    // ============================================

    // Recherche dans les articles disponibles
    $('#searchArticle').on('keyup', function() {
        const searchText = $(this).val().toLowerCase();
        $('#tableArticlesDisponibles tbody tr').each(function() {
            const code = $(this).find('td:first-child').text().toLowerCase();
            const designation = $(this).find('td:nth-child(2)').text().toLowerCase();
            
            if (code.includes(searchText) || designation.includes(searchText)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Réinitialiser la recherche
    $('#btnResetSearch').on('click', function() {
        $('#searchArticle').val('').trigger('keyup');
    });

    // Ajouter un article à l'inventaire
    $(document).on('click', '.btn-ajouter-article', function() {
        const button = $(this);
        const articleId = button.data('article-id');
        const articleCode = button.data('article-code');
        const articleDesignation = button.data('article-designation');
        const articleQte = button.data('article-qte');
        
        if (!confirm(`Ajouter l'article "${articleCode} - ${articleDesignation}" à l'inventaire ?`)) {
            return;
        }
        
        button.prop('disabled', true);
        button.html('<span class="spinner-border spinner-border-sm" role="status"></span>');
        
        $.ajax({
            url: BASE_URL + '/pages/inventaires/ajouter_article.php',
            method: 'POST',
            dataType: 'json',
            data: {
                inventaire_id: <?php echo $id; ?>,
                article_id: articleId,
                code_article: articleCode,
                designation: articleDesignation,
                qte_theorique: articleQte
            },
            success: function(response) {
                if (response.success) {
                    // Supprimer la ligne du tableau des articles disponibles
                    button.closest('tr').remove();
                    
                    // Ajouter la nouvelle ligne au tableau principal
                    const newRow = `
                        <tr class="" data-ligne-id="${response.ligne_id}" data-article-id="${articleId}">
                            <td><strong>${articleCode}</strong></td>
                            <td>${articleDesignation}</td>
                            <td class="text-end">
                                <span class="badge bg-secondary">${parseFloat(articleQte).toFixed(2).replace('.', ',')}</span>
                            </td>
                            <td class="text-end">
                                <input type="number"
                                       class="form-control form-control-sm text-end qte-physique"
                                       data-ligne-id="${response.ligne_id}"
                                       value="0"
                                       step="0.01" min="0"
                                       style="min-width: 100px;">
                            </td>
                            <td class="text-end ecart-cell text-danger fw-bold">
                                -${parseFloat(articleQte).toFixed(2).replace('.', ',')}
                            </td>
                            <td class="text-center status-cell">
                                <i class="bi-arrow-down-circle text-danger"></i>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-danger btn-supprimer-article" 
                                        data-ligne-id="${response.ligne_id}"
                                        data-article-code="${articleCode}"
                                        title="Supprimer cet article">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    
                    // Insérer la nouvelle ligne avant la ligne de total
                    const tbody = $('#table-articles tbody');
                    const totalRow = tbody.find('tr.table-secondary');
                    
                    if (totalRow.length) {
                        totalRow.before(newRow);
                    } else {
                        tbody.html(newRow);
                    }
                    
                    // Mettre à jour les statistiques
                    updateStatistics();
                    
                    // Fermer le modal si plus d'articles disponibles
                    if ($('#tableArticlesDisponibles tbody tr:visible').length === 0) {
                        $('#modalAjoutArticle').modal('hide');
                    }
                    
                    // Afficher un message de succès
                    showToast('success', 'Article ajouté avec succès !');
                    
                } else {
                    alert('❌ Erreur: ' + (response.error || 'Erreur inconnue'));
                    button.prop('disabled', false);
                    button.html('<i class="bi bi-plus-circle"></i> Ajouter');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur Ajax:', error);
                alert('❌ Erreur de communication avec le serveur');
                button.prop('disabled', false);
                button.html('<i class="bi bi-plus-circle"></i> Ajouter');
            }
        });
    });

    // Supprimer un article de l'inventaire
    $(document).on('click', '.btn-supprimer-article', function() {
        const button = $(this);
        const ligneId = button.data('ligne-id');
        const articleCode = button.data('article-code');
        
        if (!confirm(`Supprimer l'article "${articleCode}" de l'inventaire ?`)) {
            return;
        }
        
        button.prop('disabled', true);
        button.html('<span class="spinner-border spinner-border-sm" role="status"></span>');
        
        $.ajax({
            url: BASE_URL + '/pages/inventaires/supprimer_article.php',
            method: 'POST',
            dataType: 'json',
            data: {
                ligne_id: ligneId,
                inventaire_id: <?php echo $id; ?>
            },
            success: function(response) {
                if (response.success) {
                    // Supprimer la ligne du tableau
                    const row = button.closest('tr');
                    const articleId = row.data('article-id');
                    const articleCode = row.find('td:first-child strong').text();
                    const articleDesignation = row.find('td:nth-child(2)').text();
                    const qteTheorique = parseFloat(row.find('td:nth-child(3) .badge').text().replace(',', '.'));
                    
                    // Ajouter l'article à la liste des articles disponibles
                    const newRow = `
                        <tr data-article-id="${articleId}">
                            <td><strong>${articleCode}</strong></td>
                            <td>${articleDesignation}</td>
                            <td class="text-end">
                                <span class="badge bg-secondary">${qteTheorique.toFixed(2).replace('.', ',')}</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-success btn-ajouter-article"
                                        data-article-id="${articleId}"
                                        data-article-code="${articleCode}"
                                        data-article-designation="${articleDesignation}"
                                        data-article-qte="${qteTheorique}">
                                    <i class="bi bi-plus-circle"></i> Ajouter
                                </button>
                            </td>
                        </tr>
                    `;
                    
                    $('#tableArticlesDisponibles tbody').append(newRow);
                    
                    // Supprimer la ligne du tableau principal
                    row.remove();
                    
                    // Mettre à jour les statistiques
                    updateStatistics();
                    
                    // Afficher un message de succès
                    showToast('success', 'Article supprimé avec succès !');
                    
                } else {
                    alert('❌ Erreur: ' + (response.error || 'Erreur inconnue'));
                    button.prop('disabled', false);
                    button.html('<i class="bi bi-trash"></i>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur Ajax:', error);
                alert('❌ Erreur de communication avec le serveur');
                button.prop('disabled', false);
                button.html('<i class="bi bi-trash"></i>');
            }
        });
    });

    // Fonction pour mettre à jour les statistiques
    function updateStatistics() {
        // Recharger la page pour mettre à jour les statistiques
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }

    // Fonction pour afficher un toast
    function showToast(type, message) {
        // Créer un élément toast simple
        const toast = $(`
            <div class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'}"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `);
        
        // Ajouter au conteneur
        $('.toast-container').remove();
        $('body').append('<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
        $('.toast-container').append(toast);
        
        // Afficher le toast
        const bsToast = new bootstrap.Toast(toast[0]);
        bsToast.show();
        
        // Supprimer après 3 secondes
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
    <?php endif; // fin section en_cours uniquement ?>

    // ── Export et téléchargement (toujours actifs) ────────────────────────────
    $('#btn-export-excel').on('click', function() {
        $('#modalExport').modal('show');
    });
    
    // Télécharger un fichier exemple
    $('#btn-download-example').on('click', function() {
        window.location.href = '<?php echo BASE_URL; ?>/pages/inventaires/export_exemple.php';
    });
});
</script>

<!-- Conteneur pour les toasts -->
<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>