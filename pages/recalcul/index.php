<?php
$page_title = 'Recalcul du stock';
require_once __DIR__ . '/../../includes/header.php';

$auth->requireAdmin();
$db = Database::getInstance();

$success_message = '';
$error_message = '';

// Recuperation des filtres
$article_id  = isset($_POST['article_id'])  ? intval($_POST['article_id'])        : 0;
$date_debut  = isset($_POST['date_debut'])  ? trim($_POST['date_debut'])           : '';
$date_fin    = isset($_POST['date_fin'])    ? trim($_POST['date_fin'])             : '';

// Validation des dates
if (!empty($date_debut) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_debut)) $date_debut = '';
if (!empty($date_fin)   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_fin))   $date_fin   = '';
if (!empty($date_debut) && !empty($date_fin) && $date_debut > $date_fin) {
    [$date_debut, $date_fin] = [$date_fin, $date_debut];
}

// Conditions de date interpolées directement (dates validées par regex, article_id castéen int)
$cond_e = ''; $cond_s = ''; $cond_r = ''; $cond_rf = '';

if (!empty($date_debut)) {
    $cond_e  .= " AND e.date >= '$date_debut'";
    $cond_s  .= " AND s.date >= '$date_debut'";
    $cond_r  .= " AND r.date >= '$date_debut'";
    $cond_rf .= " AND rf.date >= '$date_debut'";
}
if (!empty($date_fin)) {
    $cond_e  .= " AND e.date <= '$date_fin'";
    $cond_s  .= " AND s.date <= '$date_fin'";
    $cond_r  .= " AND r.date <= '$date_fin'";
    $cond_rf .= " AND rf.date <= '$date_fin'";
}

$article_where = $article_id > 0 ? "WHERE a.id = $article_id" : '';

$has_date_filter = !empty($date_debut) || !empty($date_fin);

// Sous-requetes communes
$sub_entrees = "SELECT SUM(le.qte_entree) FROM ligne_entrees le" .
    ($has_date_filter ? " JOIN entrees e ON le.entree_id = e.id" : "") .
    " WHERE le.article_id = a.id" . $cond_e;

$sub_sorties = "SELECT SUM(ls.qte_sortie) FROM ligne_sorties ls" .
    ($has_date_filter ? " JOIN sorties s ON ls.sortie_id = s.id" : "") .
    " WHERE ls.article_id = a.id" . $cond_s;

$sub_retours = "SELECT SUM(lr.qte_retour) FROM ligne_retours lr" .
    ($has_date_filter ? " JOIN retours r ON lr.retour_id = r.id" : "") .
    " WHERE lr.article_id = a.id" . $cond_r;

$sub_retours_frs = "SELECT SUM(lrf.qte) FROM ligne_retour_fournisseur lrf" .
    ($has_date_filter ? " JOIN retour_fournisseur rf ON lrf.retour_fournisseur_id = rf.id" : "") .
    " WHERE lrf.article_id = a.id" . $cond_rf;

// --- RECALCUL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_recalcul'])) {
    try {
        $db->beginTransaction();

        $sql_update = "UPDATE articles a
                SET a.qte_entree      = COALESCE(($sub_entrees), 0),
                    a.qte_sortie      = COALESCE(($sub_sorties), 0),
                    a.qte_retour      = COALESCE(($sub_retours), 0),
                    a.qte_retour_frs  = COALESCE(($sub_retours_frs), 0),
                    a.qte_disponible  = a.stock_initial
                                      + COALESCE(($sub_entrees), 0)
                                      - COALESCE(($sub_sorties), 0)
                                      + COALESCE(($sub_retours), 0)
                                      - COALESCE(($sub_retours_frs), 0)
                $article_where";

        $stmt = $db->getConnection()->prepare($sql_update);
        $stmt->execute();
        $rows_affected = $stmt->rowCount();

        $log_detail = "Recalcul stock";
        if ($article_id > 0) $log_detail .= " - article #$article_id";
        if ($has_date_filter) $log_detail .= " - periode " . ($date_debut ?: '...') . " au " . ($date_fin ?: '...');
        $log_detail .= " - $rows_affected article(s)";

        $auth->logTrace($auth->getUserId(), 'recalcul', 'execute', 'articles', $article_id ?: 0, $log_detail);

        $db->commit();
        $success_message = "Recalcul effectue avec succes ! $rows_affected article(s) mis a jour.";

    } catch (Exception $e) {
        $db->rollback();
        $error_message = 'Erreur lors du recalcul: ' . $e->getMessage();
    }
}

// --- APERCU DES ECARTS ---
$cond_e2 = ''; $cond_s2 = ''; $cond_r2 = ''; $cond_rf2 = '';

if (!empty($date_debut)) {
    $cond_e2  .= " AND e2.date >= '$date_debut'";
    $cond_s2  .= " AND s2.date >= '$date_debut'";
    $cond_r2  .= " AND r2.date >= '$date_debut'";
    $cond_rf2 .= " AND rf2.date >= '$date_debut'";
}
if (!empty($date_fin)) {
    $cond_e2  .= " AND e2.date <= '$date_fin'";
    $cond_s2  .= " AND s2.date <= '$date_fin'";
    $cond_r2  .= " AND r2.date <= '$date_fin'";
    $cond_rf2 .= " AND rf2.date <= '$date_fin'";
}

$sub_e2 = "SELECT SUM(le2.qte_entree) FROM ligne_entrees le2" .
    ($has_date_filter ? " JOIN entrees e2 ON le2.entree_id = e2.id" : "") .
    " WHERE le2.article_id = a.id" . $cond_e2;

$sub_s2 = "SELECT SUM(ls2.qte_sortie) FROM ligne_sorties ls2" .
    ($has_date_filter ? " JOIN sorties s2 ON ls2.sortie_id = s2.id" : "") .
    " WHERE ls2.article_id = a.id" . $cond_s2;

$sub_r2 = "SELECT SUM(lr2.qte_retour) FROM ligne_retours lr2" .
    ($has_date_filter ? " JOIN retours r2 ON lr2.retour_id = r2.id" : "") .
    " WHERE lr2.article_id = a.id" . $cond_r2;

$sub_rf2 = "SELECT SUM(lrf2.qte) FROM ligne_retour_fournisseur lrf2" .
    ($has_date_filter ? " JOIN retour_fournisseur rf2 ON lrf2.retour_fournisseur_id = rf2.id" : "") .
    " WHERE lrf2.article_id = a.id" . $cond_rf2;

$article_where2 = $article_id > 0 ? "AND a.id = $article_id" : "";

$sql_ecarts = "SELECT a.id, a.code_article, a.designation, a.qte_disponible,
                      COALESCE(($sub_e2), 0)  as calc_entree,
                      COALESCE(($sub_s2), 0)  as calc_sortie,
                      COALESCE(($sub_r2), 0)  as calc_retour,
                      COALESCE(($sub_rf2), 0) as calc_retour_frs,
                      (a.stock_initial
                       + COALESCE(($sub_e2), 0)
                       - COALESCE(($sub_s2), 0)
                       + COALESCE(($sub_r2), 0)
                       - COALESCE(($sub_rf2), 0)) as calc_disponible
               FROM articles a
               WHERE (
                   ABS(a.qte_disponible - (a.stock_initial + COALESCE(($sub_e2), 0) - COALESCE(($sub_s2), 0) + COALESCE(($sub_r2), 0) - COALESCE(($sub_rf2), 0))) > 0.01
                   OR ABS(a.qte_entree - COALESCE(($sub_e2), 0)) > 0.01
                   OR ABS(a.qte_sortie - COALESCE(($sub_s2), 0)) > 0.01
                   OR ABS(COALESCE(a.qte_retour, 0) - COALESCE(($sub_r2), 0)) > 0.01
                   OR ABS(COALESCE(a.qte_retour_frs, 0) - COALESCE(($sub_rf2), 0)) > 0.01
               )
               $article_where2
               ORDER BY a.code_article";

$stmt = $db->getConnection()->prepare($sql_ecarts);
$stmt->execute();
$articles_avec_ecarts = $stmt->fetchAll();

// Article selectionne (pour affichage)
$article_selectionne = null;
if ($article_id > 0) {
    $db->prepare("SELECT id, code_article, designation FROM articles WHERE id = :id");
    $db->bind(':id', $article_id);
    $article_selectionne = $db->fetch();
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-arrow-clockwise"></i> Recalcul du stock</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Recalcul du stock</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Colonne gauche : infos + ecarts -->
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-info-circle"></i> Informations</div>
                <div class="card-body">
                    <h5>Formule appliquee :</h5>
                    <code class="d-block bg-light p-3">
                        Stock disponible = Stock initial + Total entrees - Total sorties + Total retours employe - Total retours fournisseur
                    </code>
                    <?php if ($has_date_filter): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-calendar-range"></i>
                        <strong>Filtre de periode actif :</strong>
                        seuls les mouvements
                        <?php if (!empty($date_debut)): ?>du <strong><?php echo date('d/m/Y', strtotime($date_debut)); ?></strong><?php endif; ?>
                        <?php if (!empty($date_fin)): ?>au <strong><?php echo date('d/m/Y', strtotime($date_fin)); ?></strong><?php endif; ?>
                        sont pris en compte.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (count($articles_avec_ecarts) > 0): ?>
                <div class="card">
                    <div class="card-header bg-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?php echo count($articles_avec_ecarts); ?> article(s) avec ecarts detectes
                        <?php if ($has_date_filter): ?><small>(sur la periode selectionnee)</small><?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Designation</th>
                                        <th class="text-end">Stock actuel</th>
                                        <th class="text-end">Stock calcule</th>
                                        <th class="text-end">Ecart</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($articles_avec_ecarts as $a): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($a['code_article']); ?></td>
                                            <td><?php echo htmlspecialchars($a['designation']); ?></td>
                                            <td class="text-end"><?php echo number_format($a['qte_disponible'], 2, ',', ' '); ?></td>
                                            <td class="text-end"><?php echo number_format($a['calc_disponible'], 2, ',', ' '); ?></td>
                                            <td class="text-end <?php echo ($a['qte_disponible'] - $a['calc_disponible']) != 0 ? 'text-danger fw-bold' : ''; ?>">
                                                <?php echo number_format($a['qte_disponible'] - $a['calc_disponible'], 2, ',', ' '); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    Aucun ecart detecte<?php if ($article_selectionne): ?> pour cet article<?php endif; ?><?php if ($has_date_filter): ?> sur la periode selectionnee<?php endif; ?>. Les stocks sont coherents.
                </div>
            <?php endif; ?>
        </div>

        <!-- Colonne droite : formulaire -->
        <div class="col-md-4">
            <div class="card bg-danger text-white mb-3">
                <div class="card-header"><i class="bi bi-exclamation-triangle"></i> <strong>ATTENTION</strong></div>
                <div class="card-body">
                    <p class="mb-0">Cette operation va <strong>ecraser les valeurs de stock</strong> des articles selectionnes. Verifiez les ecarts avant de lancer.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="bi bi-sliders"></i> Options de recalcul</div>
                <div class="card-body">
                    <form method="POST" id="recalculForm">

                        <!-- Article -->
                        <div class="mb-3">
                            <label for="article_id" class="form-label">
                                <i class="bi bi-box-seam"></i> Article
                            </label>
                            <select class="form-select" id="article_id" name="article_id">
                                <option value="">Tous les articles</option>
                                <?php if ($article_selectionne): ?>
                                    <option value="<?php echo $article_selectionne['id']; ?>" selected>
                                        <?php echo htmlspecialchars($article_selectionne['code_article'] . ' - ' . $article_selectionne['designation']); ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">Laisser vide pour recalculer tous les articles.</div>
                        </div>

                        <!-- Date debut -->
                        <div class="mb-3">
                            <label for="date_debut" class="form-label">
                                <i class="bi bi-calendar"></i> Date debut
                            </label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut"
                                   value="<?php echo htmlspecialchars($date_debut); ?>">
                        </div>

                        <!-- Date fin -->
                        <div class="mb-3">
                            <label for="date_fin" class="form-label">
                                <i class="bi bi-calendar"></i> Date fin
                            </label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin"
                                   value="<?php echo htmlspecialchars($date_fin); ?>">
                            <div class="form-text">Laisser les dates vides pour inclure tous les mouvements.</div>
                        </div>

                        <hr>

                        <!-- Bouton apercu -->
                        <div class="d-grid mb-2">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bi bi-eye"></i> Apercu des ecarts
                            </button>
                        </div>

                        <!-- Bouton recalcul -->
                        <div class="d-grid">
                            <button type="submit" name="confirm_recalcul" value="1"
                                    class="btn btn-danger btn-lg"
                                    onclick="return confirm('Confirmer le recalcul du stock ?\nCette action modifie les donnees.');">
                                <i class="bi bi-arrow-clockwise"></i> Recalculer
                            </button>
                        </div>
                    </form>
                    <hr>
                    <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

$(document).ready(function() {
    $('#article_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Tous les articles',
        allowClear: true,
        ajax: {
            url: BASE_URL + '/api/articles.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { search: params.term || '' };
            },
            processResults: function(data) {
                if (!Array.isArray(data)) return { results: [] };
                return { results: data };
            },
            cache: true
        },
        minimumInputLength: 0
    });
});
</script>
