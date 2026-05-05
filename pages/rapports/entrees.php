[file content begin]
<?php
$page_title = 'Rapport des entrées';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('rapports', 'view');
$db = Database::getInstance();

$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$fournisseur_id = $_GET['fournisseur_id'] ?? '';
$code_article = $_GET['code_article'] ?? '';
$designation = $_GET['designation'] ?? '';

// Requête pour les lignes détaillées des articles
$sql_detail = "SELECT e.id as entree_id, e.date, f.nom_complet as fournisseur,
                      le.code_article, le.designation, le.qte_entree
               FROM entrees e
               INNER JOIN fournisseurs f ON e.fournisseur_id = f.id
               INNER JOIN ligne_entrees le ON e.id = le.entree_id
               WHERE e.date BETWEEN :date_debut AND :date_fin";

$params_detail = [':date_debut' => $date_debut, ':date_fin' => $date_fin];

if (!empty($fournisseur_id)) {
    $sql_detail .= " AND e.fournisseur_id = :fournisseur_id";
    $params_detail[':fournisseur_id'] = $fournisseur_id;
}

if (!empty($code_article)) {
    $sql_detail .= " AND le.code_article LIKE :code_article";
    $params_detail[':code_article'] = "%$code_article%";
}

if (!empty($designation)) {
    $sql_detail .= " AND le.designation LIKE :designation";
    $params_detail[':designation'] = "%$designation%";
}

$sql_detail .= " ORDER BY e.date DESC, le.code_article";

$stmt_detail = $db->getConnection()->prepare($sql_detail);
foreach ($params_detail as $key => $value) {
    $stmt_detail->bindValue($key, $value);
}
$stmt_detail->execute();
$lignes_detail = $stmt_detail->fetchAll();

// Calcul des totaux pour le détail
$total_qte_detail = 0;
$total_articles_detail = count($lignes_detail);
foreach ($lignes_detail as $ligne) {
    $total_qte_detail += $ligne['qte_entree'];
}

// Pour les filtres
$db->prepare("SELECT id, nom_complet FROM fournisseurs ORDER BY nom_complet");
$fournisseurs = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <h2><i class="bi bi-bar-chart"></i> Rapport des entrées - Détail des articles</h2>

    <form method="GET" class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">Date début</label>
                    <input type="date" class="form-control" name="date_debut" value="<?php echo $date_debut; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date fin</label>
                    <input type="date" class="form-control" name="date_fin" value="<?php echo $date_fin; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fournisseur</label>
                    <select class="form-select" name="fournisseur_id">
                        <option value="">Tous les fournisseurs</option>
                        <?php foreach ($fournisseurs as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo $fournisseur_id == $f['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($f['nom_complet']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Code article</label>
                    <input type="text" class="form-control" name="code_article" value="<?php echo htmlspecialchars($code_article); ?>" 
                           placeholder="Code article...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Désignation</label>
                    <input type="text" class="form-control" name="designation" value="<?php echo htmlspecialchars($designation); ?>" 
                           placeholder="Désignation...">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6 d-flex align-items-end">
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Filtrer
                        </button>
                        <a href="entrees.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                        </a>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="export_entree_detail_pdf.php?<?php echo http_build_query($_GET); ?>" 
                           class="btn btn-success" target="_blank">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Détail des articles entrés -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-table"></i> Détail des articles entrés</h5>
            <span class="badge bg-primary">
                <?php echo $total_articles_detail; ?> ligne(s) - <?php echo number_format($total_qte_detail, 0, '', ' '); ?> unité(s)
            </span>
        </div>
        <div class="card-body">
            <?php if (empty($lignes_detail)): ?>
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> Aucun article trouvé pour les critères de recherche sélectionnés.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Fournisseur</th>
                                <th>Code Article</th>
                                <th>Désignation</th>
                                <th class="text-end">Quantité entrée</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lignes_detail as $ligne): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($ligne['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($ligne['fournisseur']); ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($ligne['code_article']); ?></td>
                                    <td><?php echo htmlspecialchars($ligne['designation']); ?></td>
                                    <td class="text-end"><?php echo number_format($ligne['qte_entree'], 0, '', ' '); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold table-active">
                                <td colspan="3" class="text-end">TOTAL:</td>
                                <td><?php echo $total_articles_detail; ?> article(s)</td>
                                <td class="text-end"><?php echo number_format($total_qte_detail, 0, '', ' '); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="alert alert-secondary mb-0">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Période :</strong> 
                            <?php echo date('d/m/Y', strtotime($date_debut)); ?> au <?php echo date('d/m/Y', strtotime($date_fin)); ?>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button class="btn btn-outline-secondary" onclick="window.print()">
                                <i class="bi bi-printer"></i> Imprimer
                            </button>
                            <a href="export_entree_detail_pdf.php?<?php echo http_build_query($_GET); ?>" 
                               class="btn btn-success" target="_blank">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
[file content end]