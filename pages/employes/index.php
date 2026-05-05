<?php
$page_title = 'Employés';
require_once __DIR__ . '/../../includes/header.php';
$auth->requirePermission('employes', 'view');

// Vérifier si la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialisation de la base de données
try {
    $db = Database::getInstance();
    // Obtenir la connexion PDO de Database
    $pdo = $db->getConnection();
} catch (Exception $e) {
    die('Erreur de connexion à la base de données: ' . $e->getMessage());
}

// Filtres avec valeurs par défaut
$search = isset($_GET['search']) ? $_GET['search'] : '';
$service_id = isset($_GET['service_id']) ? $_GET['service_id'] : '';
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Validation des paramètres
if ($per_page < 0) $per_page = 10;
if ($page < 1) $page = 1;

// Calcul de l'offset
$offset = ($page - 1) * $per_page;

// Récupérer tous les employés (sans pagination pour compter)
$sql_conditions = [];
$params = [];

if (!empty($search)) {
    $sql_conditions[] = "(e.matricule LIKE :search1 OR e.nom LIKE :search2 OR e.prenom LIKE :search3 OR e.mail LIKE :search4)";
    $params[':search1'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
    $params[':search3'] = '%' . $search . '%';
    $params[':search4'] = '%' . $search . '%';
}

if (!empty($service_id) && is_numeric($service_id)) {
    $sql_conditions[] = "e.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

// Construire la requête de comptage
$count_sql = "SELECT COUNT(*) as total FROM employes e INNER JOIN services s ON e.service_id = s.id";
if (!empty($sql_conditions)) {
    $count_sql .= " WHERE " . implode(" AND ", $sql_conditions);
}

try {
    $stmt = $pdo->prepare($count_sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $result = $stmt->fetch();
    $total_count = $result ? $result['total'] : 0;
} catch (PDOException $e) {
    $total_count = 0;
    error_log("Erreur SQL (count): " . $e->getMessage());
}

// Calcul du nombre total de pages
$total_pages = ($per_page > 0 && $total_count > 0) ? ceil($total_count / $per_page) : 1;
if ($total_pages < 1) $total_pages = 1;
if ($page > $total_pages) $page = $total_pages;

// Requête pour les données avec pagination
$sql = "SELECT e.*, s.nom as service_nom
        FROM employes e
        INNER JOIN services s ON e.service_id = s.id";
        
if (!empty($sql_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $sql_conditions);
}

$sql .= " ORDER BY e.nom ASC, e.prenom ASC";

// Ajouter la pagination si nécessaire
if ($per_page > 0) {
    $sql .= " LIMIT :limit OFFSET :offset";
}

try {
    $stmt = $pdo->prepare($sql);
    
    // Bind des paramètres de recherche
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind des paramètres de pagination
    if ($per_page > 0) {
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $employes = $stmt->fetchAll();
} catch (PDOException $e) {
    $employes = [];
    error_log("Erreur SQL (data): " . $e->getMessage());
}

// Liste des services pour le filtre
try {
    $stmt = $pdo->prepare("SELECT * FROM services ORDER BY nom");
    $stmt->execute();
    $services = $stmt->fetchAll();
} catch (Exception $e) {
    $services = [];
    error_log("Erreur lors de la récupération des services: " . $e->getMessage());
}

// Fonction pour générer les URLs de pagination
function getPaginationUrl($page, $per_page, $search, $service_id) {
    $url = 'index.php?page=' . $page;
    if ($per_page != 10) {
        $url .= '&per_page=' . $per_page;
    }
    if (!empty($search)) {
        $url .= '&search=' . urlencode($search);
    }
    if (!empty($service_id)) {
        $url .= '&service_id=' . $service_id;
    }
    return $url;
}

// Définir BASE_URL s'il n'existe pas
if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}
?>

<?php 
// Inclure la navbar
$navbar_path = __DIR__ . '/../../includes/navbar.php';
if (file_exists($navbar_path)) {
    require_once $navbar_path;
} else {
    echo '<div class="alert alert-danger">Fichier navbar.php introuvable</div>';
}
?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-people"></i> Employés</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Employés</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    
                    
                    <!-- Bouton Nouvel employé -->
                    <a href="create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nouvel employé
                    </a>

<!-- Boutons d'export -->
                    <a href="export_pdf.php?search=<?php echo urlencode($search); ?>&service_id=<?php echo urlencode($service_id); ?>&title=Liste des employés&include_filters=true" 
                       class="btn btn-success" target="_blank">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                    <a href="export_csv.php?search=<?php echo urlencode($search); ?>&service_id=<?php echo urlencode($service_id); ?>" 
                       class="btn btn-secondary">
                        <i class="bi bi-file-earmark-excel"></i> Export CSV
                    </a>

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-list-ul"></i> Liste des employés
                        <span class="badge bg-info ms-2">Total: <?php echo $total_count; ?></span>
                    </div>
                    <div class="text-muted">
                        Page <?php echo $page; ?> sur <?php echo $total_pages; ?>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="mb-3" id="filterForm">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="dynamicSearch" 
                                           placeholder="Recherche (matricule, nom, prénom, email)..." 
                                           value="<?php echo htmlspecialchars($search); ?>"
                                           name="search">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="service_id" id="serviceFilter">
                                    <option value="">Tous les services</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" 
                                                <?php echo $service_id == $service['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($service['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="per_page" id="perPageSelect">
                                    <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10 par page</option>
                                    <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50 par page</option>
                                    <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100 par page</option>
                                    <option value="500" <?php echo $per_page == 500 ? 'selected' : ''; ?>>500 par page</option>
                                    <option value="1000" <?php echo $per_page == 1000 ? 'selected' : ''; ?>>1000 par page</option>
                                    <option value="0" <?php echo $per_page == 0 ? 'selected' : ''; ?>>Tous</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-filter"></i> Filtrer
                                    </button>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="page" id="pageInput" value="<?php echo $page; ?>">
                    </form>

                    <?php if (!empty($search) || !empty($service_id) || $per_page != 10): ?>
                    <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Filtres actifs:</strong>
                        <?php 
                        $filters = [];
                        if (!empty($search)) $filters[] = "Recherche: " . htmlspecialchars($search);
                        if (!empty($service_id)) {
                            foreach ($services as $service) {
                                if ($service['id'] == $service_id) {
                                    $filters[] = "Service: " . htmlspecialchars($service['nom']);
                                    break;
                                }
                            }
                        }
                        if ($per_page != 10) $filters[] = "Affichage: " . ($per_page == 0 ? "Tous" : $per_page . " par page");
                        echo implode(" | ", $filters);
                        ?>
                        <a href="index.php" class="btn btn-sm btn-outline-info ms-3">
                            <i class="bi bi-x"></i> Réinitialiser
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th width="100">Matricule</th>
                                    <th width="150">Nom</th>
                                    <th width="150">Prénom</th>
                                    <th width="150">Service</th>
                                    <th width="200">Email</th>
                                    <th width="120">Téléphone</th>
                                    <th width="100">Statut</th>
                                    <th width="150" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($employes) > 0): ?>
                                    <?php foreach ($employes as $employe): ?>
                                        <tr>
                                            <td><code class="text-primary"><?php echo htmlspecialchars($employe['matricule']); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($employe['nom']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($employe['prenom']); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <i class="bi bi-building"></i> <?php echo htmlspecialchars($employe['service_nom']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($employe['mail'])): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($employe['mail']); ?>" class="text-decoration-none">
                                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($employe['mail']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($employe['tel1'])): ?>
                                                    <a href="tel:<?php echo htmlspecialchars($employe['tel1']); ?>" class="text-decoration-none">
                                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($employe['tel1']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($employe['actif']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle"></i> Actif
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="bi bi-x-circle"></i> Inactif
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center action-buttons">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="view.php?id=<?php echo $employe['id']; ?>"
                                                       class="btn btn-outline-info" title="Voir détails">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $employe['id']; ?>"
                                                       class="btn btn-outline-warning" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $employe['id']; ?>"
                                                       class="btn btn-outline-danger" title="Supprimer"
                                                       onclick="return confirm('Voulez-vous vraiment supprimer l\'employé <?php echo addslashes($employe['nom'] . ' ' . $employe['prenom']); ?> ?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <div class="py-3">
                                                <i class="bi bi-people display-6 text-muted"></i>
                                                <h5 class="mt-3">Aucun employé trouvé</h5>
                                                <p class="text-muted">Essayez de modifier vos critères de recherche</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1 && $per_page > 0): ?>
                    <nav aria-label="Pagination">
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Affichage de <strong><?php echo min($offset + 1, $total_count); ?></strong> à 
                                <strong><?php echo min($offset + $per_page, $total_count); ?></strong> sur 
                                <strong><?php echo $total_count; ?></strong> employés
                            </div>
                            
                            <ul class="pagination mb-0">
                                <!-- Premier -->
                                <li class="page-item <?php echo $page == 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo getPaginationUrl(1, $per_page, $search, $service_id); ?>"
                                       title="Première page">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                </li>
                                <!-- Précédent -->
                                <li class="page-item <?php echo $page == 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo getPaginationUrl($page - 1, $per_page, $search, $service_id); ?>"
                                       title="Page précédente">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <!-- Pages -->
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo getPaginationUrl($i, $per_page, $search, $service_id); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor;
                                
                                if ($end_page < $total_pages) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                ?>
                                
                                <!-- Suivant -->
                                <li class="page-item <?php echo $page == $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo getPaginationUrl($page + 1, $per_page, $search, $service_id); ?>"
                                       title="Page suivante">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <!-- Dernier -->
                                <li class="page-item <?php echo $page == $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo getPaginationUrl($total_pages, $per_page, $search, $service_id); ?>"
                                       title="Dernière page">
                                        <i class="bi bi-chevron-double-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </nav>
                    <?php elseif ($per_page == 0 && $total_count > 0): ?>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle me-2"></i>
                            Affichage de tous les <strong><?php echo $total_count; ?></strong> employés
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles personnalisés */
.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
}

.table th {
    background-color: #2c3e50;
    color: white;
    font-weight: 600;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(102, 126, 234, 0.05);
}

.badge {
    padding: 5px 10px;
    font-weight: 500;
}

.action-buttons .btn-group {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.action-buttons .btn {
    border-radius: 4px !important;
    margin: 0 2px;
}

.pagination .page-item.active .page-link {
    background-color: #667eea;
    border-color: #667eea;
}

.pagination .page-link {
    color: #667eea;
}

.pagination .page-link:hover {
    background-color: rgba(102, 126, 234, 0.1);
    color: #5a67d8;
}

#dynamicSearch:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
}

/* Responsive */
@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        text-align: center;
    }
    
    .action-buttons .btn-group {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        margin: 2px 0;
    }
    
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<?php
// Inclure le footer
$footer_path = __DIR__ . '/../../includes/footer.php';
if (file_exists($footer_path)) {
    require_once $footer_path;
} else {
    echo '<div class="alert alert-danger mt-3">Fichier footer.php introuvable</div>';
}
?>

<script>
$(document).ready(function() {
    // Recherche dynamique avec debounce
    let searchTimer;
    $('#dynamicSearch').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            $('#pageInput').val(1); // Retour à la première page
            $('#filterForm').submit();
        }, 800);
    });

    // Changement du nombre d'éléments par page
    $('#perPageSelect').change(function() {
        $('#pageInput').val(1);
        $('#filterForm').submit();
    });

    // Changement du filtre service
    $('#serviceFilter').change(function() {
        $('#pageInput').val(1);
        $('#filterForm').submit();
    });

    // Soumission du formulaire avec touche Entrée
    $('#dynamicSearch').keypress(function(e) {
        if (e.which == 13) {
            e.preventDefault();
            $('#pageInput').val(1);
            $('#filterForm').submit();
        }
    });
});
</script>