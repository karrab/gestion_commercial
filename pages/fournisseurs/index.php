<?php
$page_title = 'Fournisseurs';
require_once __DIR__ . '/../../includes/header.php';
$auth->requirePermission('fournisseurs', 'view');

$db = Database::getInstance();
$search = $_GET['search'] ?? '';

// Pagination
$perPageOptions = [10, 50, 100, 200, 500, 1000];
$perPage = isset($_GET['perPage']) && in_array((int)$_GET['perPage'], $perPageOptions) ? (int)$_GET['perPage'] : 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Paramètres communs
$params = [];
$where = "WHERE 1=1";
if (!empty($search)) {
    $where .= " AND (nom_complet LIKE :search1 OR matricule_fiscal LIKE :search2 OR ville LIKE :search3 OR pays LIKE :search4)";
    $params[':search1'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
    $params[':search3'] = '%' . $search . '%';
    $params[':search4'] = '%' . $search . '%';
}

// Total
$stmtCount = $db->getConnection()->prepare("SELECT COUNT(*) FROM fournisseurs $where");
foreach ($params as $k => $v) $stmtCount->bindValue($k, $v);
$stmtCount->execute();
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;

// Données paginées
$stmtData = $db->getConnection()->prepare(
    "SELECT * FROM fournisseurs $where ORDER BY nom_complet ASC LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) $stmtData->bindValue($k, $v);
$stmtData->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtData->execute();
$fournisseurs = $stmtData->fetchAll();

// Construction de la query string de base (sans page)
$queryBase = http_build_query(array_filter(['search' => $search, 'perPage' => $perPage]));
$queryBase = $queryBase ? '&' . $queryBase : '';
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-truck"></i> Fournisseurs</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Fournisseurs</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nouveau fournisseur
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list-ul"></i> Liste des fournisseurs
                </div>
                <div class="card-body">
                    <!-- Recherche -->
                    <form method="GET" class="mb-3">
                        <input type="hidden" name="perPage" value="<?php echo $perPage; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search"
                                           placeholder="Rechercher par nom, matricule fiscal, ville ou pays..."
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Rechercher
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/index.php" class="btn btn-secondary">
                                            <i class="bi bi-x"></i> Effacer
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Barre : total + par page -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i>
                            Total : <strong><?php echo number_format($total, 0, ',', ' '); ?></strong> fournisseur(s)
                        </small>
                        <div class="d-flex align-items-center gap-2">
                            <label class="mb-0 small">Par page :</label>
                            <select class="form-select form-select-sm" style="width:90px;"
                                    onchange="window.location='?page=1&perPage='+this.value+'<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>'">
                                <?php foreach ($perPageOptions as $opt): ?>
                                    <option value="<?php echo $opt; ?>" <?php echo $perPage == $opt ? 'selected' : ''; ?>>
                                        <?php echo $opt; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Nom / Raison sociale</th>
                                    <th>Matricule Fiscal</th>
                                    <th>Ville</th>
                                    <th>Pays</th>
                                    <th>Téléphone</th>
                                    <th>Statut</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($fournisseurs) > 0): ?>
                                    <?php foreach ($fournisseurs as $fournisseur): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($fournisseur['nom_complet']); ?></strong></td>
                                            <td>
                                                <?php if (!empty($fournisseur['matricule_fiscal'])): ?>
                                                    <span class="badge bg-light text-dark" style="font-family:monospace;border:1px solid #dee2e6;">
                                                        <i class="bi bi-upc-scan"></i> <?php echo htmlspecialchars($fournisseur['matricule_fiscal']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($fournisseur['ville'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($fournisseur['pays'] ?? '-'); ?></td>
                                            <td>
                                                <?php if (!empty($fournisseur['tel1'])): ?>
                                                    <a href="tel:<?php echo htmlspecialchars($fournisseur['tel1']); ?>" class="text-decoration-none">
                                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($fournisseur['tel1']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($fournisseur['actif']): ?>
                                                    <span class="badge bg-success">Actif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/view.php?id=<?php echo $fournisseur['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir"><i class="bi bi-eye"></i></a>
                                                <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/edit.php?id=<?php echo $fournisseur['id']; ?>"
                                                   class="btn btn-sm btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                                <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/delete.php?id=<?php echo $fournisseur['id']; ?>"
                                                   class="btn btn-sm btn-danger" title="Supprimer"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce fournisseur ?\nCette action est irréversible.');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            Aucun fournisseur trouvé
                                            <?php if (!empty($search)): ?>
                                                <br><small>Essayez d'autres termes de recherche</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Page <?php echo $page; ?> sur <?php echo $totalPages; ?>
                            (<?php echo number_format($total, 0, ',', ' '); ?> résultat(s))
                        </small>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=1<?php echo $queryBase; ?>"><i class="bi bi-chevron-bar-left"></i></a>
                                </li>
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $queryBase; ?>"><i class="bi bi-chevron-left"></i></a>
                                </li>
                                <?php
                                $start = max(1, $page - 2);
                                $end   = min($totalPages, $page + 2);
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $queryBase; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $queryBase; ?>"><i class="bi bi-chevron-right"></i></a>
                                </li>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo $queryBase; ?>"><i class="bi bi-chevron-bar-right"></i></a>
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
