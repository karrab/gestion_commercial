<?php
$page_title = 'Traces d\'activité';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('rapports', 'view');
$db = Database::getInstance();

$user_id = $_GET['user_id'] ?? '';
$module = $_GET['module'] ?? '';
$date_debut = $_GET['date_debut'] ?? date('Y-m-d', strtotime('-7 days'));
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$limit = $_GET['limit'] ?? '50';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Options de limite disponibles
$limit_options = [
    '50' => '50 traces',
    '100' => '100 traces',
    '500' => '500 traces',
    '1000' => '1000 traces',
    '0' => 'Toutes les traces'
];

// Validation de la limite
if (!array_key_exists($limit, $limit_options)) {
    $limit = '50';
}

// Construire la requête de base
$sql_base = "FROM traces t
             INNER JOIN users u ON t.user_id = u.id
             WHERE t.created_at BETWEEN :date_debut AND :date_fin";

$params = [':date_debut' => $date_debut . ' 00:00:00', ':date_fin' => $date_fin . ' 23:59:59'];

if (!empty($user_id)) {
    $sql_base .= " AND t.user_id = :user_id";
    $params[':user_id'] = $user_id;
}

if (!empty($module)) {
    $sql_base .= " AND t.module = :module";
    $params[':module'] = $module;
}

// Requête pour compter le nombre total de traces
$count_sql = "SELECT COUNT(*) as total " . $sql_base;
$stmt_count = $db->getConnection()->prepare($count_sql);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value);
}
$stmt_count->execute();
$total_result = $stmt_count->fetch();
$total_traces = $total_result['total'];

// Configuration de la pagination
if ($limit == '0') {
    $limit_val = 0; // Toutes les traces
    $total_pages = 1;
    $offset = 0;
} else {
    $limit_val = intval($limit);
    $total_pages = ceil($total_traces / $limit_val);
    $page = min(max(1, $page), $total_pages); // S'assurer que la page est valide
    $offset = ($page - 1) * $limit_val;
}

// Requête pour récupérer les traces avec pagination
$sql = "SELECT t.*, u.nom as user_nom " . $sql_base . " ORDER BY t.created_at DESC";

if ($limit_val > 0) {
    $sql .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit_val;
    $params[':offset'] = $offset;
}

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$traces = $stmt->fetchAll();

// Récupérer tous les utilisateurs
$db->prepare("SELECT id, nom FROM users ORDER BY nom");
$users = $db->fetchAll();

// Récupérer tous les modules distincts de la table traces
$db->prepare("SELECT DISTINCT module FROM traces WHERE module IS NOT NULL AND module != '' ORDER BY module");
$modules = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <h2><i class="bi bi-clock-history"></i> Traces d'activité</h2>

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
                <div class="col-md-2">
                    <label class="form-label">Utilisateur</label>
                    <select class="form-select" name="user_id">
                        <option value="">Tous</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $user_id == $u['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Module</label>
                    <select class="form-select" name="module">
                        <option value="">Tous</option>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?php echo htmlspecialchars($m['module']); ?>" <?php echo $module == $m['module'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['module']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Traces par page</label>
                    <select class="form-select" name="limit">
                        <?php foreach ($limit_options as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $limit == $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" class="btn btn-outline-secondary w-100">Réinitialiser</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <?php if ($total_traces > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Date/Heure</th>
                                <th>Utilisateur</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Détails</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($traces as $t): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($t['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($t['user_nom']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($t['module']); ?></span></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($t['action']); ?></span></td>
                                    <td><?php echo htmlspecialchars($t['details'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($limit_val > 0 && $total_pages > 1): ?>
                    <nav aria-label="Pagination des traces">
                        <ul class="pagination justify-content-center mb-0 mt-3">
                            <?php
                            // Construire l'URL de base avec tous les paramètres sauf 'page'
                            $query_params = $_GET;
                            unset($query_params['page']);
                            $base_url = '?' . http_build_query($query_params) . '&page=';
                            
                            // Bouton Précédent
                            if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $base_url . ($page - 1); ?>" aria-label="Précédent">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&laquo;</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            // Afficher les numéros de page
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $base_url . '1'; ?>">1</a>
                                </li>
                                <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $base_url . $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $base_url . $total_pages; ?>">
                                        <?php echo $total_pages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Bouton Suivant -->
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $base_url . ($page + 1); ?>" aria-label="Suivant">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&raquo;</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <p class="text-muted small mb-0">
                        <?php if ($limit_val == 0): ?>
                            Affichage de toutes les traces (<?php echo $total_traces; ?> résultats)
                        <?php else: ?>
                            Page <?php echo $page; ?> sur <?php echo $total_pages; ?> - 
                            Affichage de <?php echo min($limit_val, count($traces)); ?> traces sur <?php echo $total_traces; ?> résultats
                            <?php if ($limit_val > 0): ?>
                                (<?php echo $offset + 1; ?> à <?php echo min($offset + $limit_val, $total_traces); ?>)
                            <?php endif; ?>
                        <?php endif; ?>
                    </p>
                    <div class="small text-muted">
                        <i class="bi bi-info-circle"></i> Les traces sont affichées par ordre chronologique décroissant
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> Aucune trace trouvée pour les critères sélectionnés.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>