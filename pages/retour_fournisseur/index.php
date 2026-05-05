<?php
$page_title = 'Retours Fournisseur';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('retour_fournisseur', 'view');
$db = Database::getInstance();
$retourFournisseur = new RetourFournisseur();

// Récupération des paramètres de filtrage
$fournisseur_id = $_GET['fournisseur_id'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['perPage']) && in_array($_GET['perPage'], PER_PAGE_OPTIONS) ? (int)$_GET['perPage'] : DEFAULT_PER_PAGE;

// Construire les filtres
$filters = [];
if (!empty($fournisseur_id)) $filters['fournisseur_id'] = $fournisseur_id;
if (!empty($date_debut)) $filters['date_debut'] = $date_debut;
if (!empty($date_fin)) $filters['date_fin'] = $date_fin;

// Récupérer les données
$retours = $retourFournisseur->getAll($page, $perPage, $filters);
$total = $retourFournisseur->count($filters);
$totalPages = ceil($total / $perPage);

// Récupérer la liste des fournisseurs pour le filtre
$db->prepare("SELECT id, nom_complet FROM fournisseurs ORDER BY nom_complet");
$fournisseurs = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-box-arrow-left"></i> Retours Fournisseur</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Retours Fournisseur</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('retour_fournisseur', 'create')): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nouveau retour
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

   <!-- Filtres -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel"></i> Filtres de recherche
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="fournisseur_id" class="form-label">Fournisseur</label>
                    <select class="form-select" id="fournisseur_id" name="fournisseur_id">
                        <option value="">Tous les fournisseurs</option>
                        <?php foreach ($fournisseurs as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo $fournisseur_id == $f['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($f['nom_complet']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label for="date_debut" class="form-label">Date début</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut"
                           value="<?php echo htmlspecialchars($date_debut); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label for="date_fin" class="form-label">Date fin</label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin"
                           value="<?php echo htmlspecialchars($date_fin); ?>">
                </div>

                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filtrer
                    </button>
                    <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/index.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Réinitialiser
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Liste des retours -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <span>
            <i class="bi bi-list"></i> Liste des retours fournisseur
            <span class="badge bg-secondary ms-2"><?php echo number_format($total, 0, ',', ' '); ?> résultat(s)</span>
        </span>
        <div class="d-flex align-items-center">
            <label for="perPage" class="form-label me-2 mb-0">Par page:</label>
            <select id="perPage" name="perPage" class="form-select form-select-sm d-inline-block" style="width: 80px;" onchange="changePerPage(this.value)">
                <?php foreach (PER_PAGE_OPTIONS as $option): ?>
                    <option value="<?php echo $option; ?>" <?php echo $perPage == $option ? 'selected' : ''; ?>>
                        <?php echo $option; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="card-body p-3">
        <div class="table-responsive" style="max-height: calc(100vh - 350px); padding: 0 4px;">
            <table class="table table-hover table-striped mb-0" style="margin-bottom: 0;">
                <thead class="table-light sticky-top" style="top: -3px;">
                    <tr>
                        <th width="8%" style="padding: 12px 8px;">N°</th>
                        <th width="12%" style="padding: 12px 8px;">Date</th>
                        <th width="22%" style="padding: 12px 8px;">Fournisseur</th>
                        <th width="18%" style="padding: 12px 8px;">Contact</th>
                        <th class="text-center" width="8%" style="padding: 12px 8px;">Nb Articles</th>
                        <th width="8%" class="text-center" style="padding: 12px 8px;">Fichier</th>
                        <th width="24%" class="text-center" style="padding: 12px 8px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($retours)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5" style="padding: 20px 8px;">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                <span class="text-muted">Aucun retour fournisseur trouvé</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($retours as $retour): ?>
                            <tr>
                                <td style="padding: 12px 8px;">
                                    <strong><?php echo $retour['id']; ?></strong>
                                </td>
                                <td style="padding: 12px 8px;"><?php echo date('d/m/Y', strtotime($retour['date'])); ?></td>
                                <td style="padding: 12px 8px;">
                                    <strong><?php echo htmlspecialchars($retour['fournisseur']); ?></strong>
                                </td>
                                <td style="padding: 12px 8px;">
                                    <small>
                                        <?php if (!empty($retour['fournisseur_tel'])): ?>
                                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($retour['fournisseur_tel']); ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td class="text-center" style="padding: 12px 8px;">
                                    <span class="badge bg-info"><?php echo $retour['nb_articles']; ?></span>
                                </td>
                                <td class="text-center" style="padding: 12px 8px;">
                                    <?php if (!empty($retour['fichier'])): ?>
                                        <a href="<?php echo BASE_URL; ?>/uploads/retour_fournisseur/<?php echo rawurlencode($retour['fichier']); ?>"
                                           target="_blank" class="btn btn-sm btn-outline-primary" title="Télécharger">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center" style="padding: 12px 8px;">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if ($auth->hasPermission('retour_fournisseur', 'view')): ?>
                                        <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/view.php?id=<?php echo $retour['id']; ?>"
                                           class="btn btn-info" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($auth->hasPermission('retour_fournisseur', 'pdf')): ?>
                                        <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/pdf.php?id=<?php echo $retour['id']; ?>"
                                           class="btn btn-secondary" title="PDF" target="_blank">
                                            <i class="bi bi-file-pdf"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($auth->hasPermission('retour_fournisseur', 'update')): ?>
                                        <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/edit.php?id=<?php echo $retour['id']; ?>"
                                           class="btn btn-warning" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($auth->hasPermission('retour_fournisseur', 'delete')): ?>
                                        <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/delete.php?id=<?php echo $retour['id']; ?>"
                                           class="btn btn-danger" title="Supprimer"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce retour fournisseur ?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function changePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('perPage', value);
    window.location.href = url.toString();
}
</script>
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Pagination">
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=1&perPage=<?php echo $perPage; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) ? '&' . http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) : ''; ?>">
                                <i class="bi bi-chevron-bar-left"></i>
                            </a>
                        </li>
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&perPage=<?php echo $perPage; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) ? '&' . http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) : ''; ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&perPage=<?php echo $perPage; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) ? '&' . http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo min($totalPages, $page + 1); ?>&perPage=<?php echo $perPage; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) ? '&' . http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) : ''; ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $totalPages; ?>&perPage=<?php echo $perPage; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) ? '&' . http_build_query(array_diff_key($_GET, ['page' => '', 'perPage' => ''])) : ''; ?>">
                                <i class="bi bi-chevron-bar-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div class="text-center mt-2">
                    <small class="text-muted">
                        Page <?php echo $page; ?> sur <?php echo $totalPages; ?>
                        (<?php echo number_format($total, 0, ',', ' '); ?> résultat(s))
                    </small>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
function changePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('perPage', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

// Initialiser Select2 pour le select fournisseur (comme dans create.php)
$(document).ready(function() {
    $('#fournisseur_id').select2();
});
</script>