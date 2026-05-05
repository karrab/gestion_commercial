<?php
$page_title = 'Sorties';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('sorties', 'view');
$db = Database::getInstance();

// Filtres
$search = $_GET['search'] ?? '';
$service_id = $_GET['service_id'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$length = $_GET['length'] ?? '10';
$employe_id_filter = $_GET['employe_id'] ?? '';

$sql = "SELECT s.*,
               serv.nom as service_nom,
               emp.nom as employe_nom, emp.prenom as employe_prenom,
               (SELECT COUNT(*) FROM ligne_sorties WHERE sortie_id = s.id) as nb_articles,
               (SELECT SUM(qte_sortie) FROM ligne_sorties WHERE sortie_id = s.id) as qte_totale
        FROM sorties s
        INNER JOIN services serv ON s.service_id = serv.id
        INNER JOIN employes emp ON s.employe_id = emp.id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (serv.nom LIKE :search_serv OR emp.nom LIKE :search_emp_nom OR emp.prenom LIKE :search_emp_prenom OR s.id LIKE :search_id)";
    $params[':search_serv'] = '%' . $search . '%';
    $params[':search_emp_nom'] = '%' . $search . '%';
    $params[':search_emp_prenom'] = '%' . $search . '%';
    $params[':search_id'] = '%' . $search . '%';
}

if (!empty($service_id)) {
    $sql .= " AND s.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

if (!empty($employe_id_filter)) {
    $sql .= " AND s.employe_id = :employe_id";
    $params[':employe_id'] = $employe_id_filter;
}

if (!empty($date_debut)) {
    $sql .= " AND s.date >= :date_debut";
    $params[':date_debut'] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND s.date <= :date_fin";
    $params[':date_fin'] = $date_fin;
}

$sql .= " ORDER BY s.date DESC, s.id DESC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$sorties = $stmt->fetchAll();

// Récupérer liste services pour le filtre
$db->prepare("SELECT id, nom FROM services ORDER BY nom");
$services = $db->fetchAll();

// Récupérer les employés si un service est sélectionné
$employes = [];
if (!empty($service_id)) {
    $db->prepare("SELECT e.id, e.matricule, e.nom, e.prenom 
                 FROM employes e 
                 WHERE e.service_id = :service_id AND e.actif = 1 
                 ORDER BY e.nom, e.prenom");
    $db->bind(':service_id', $service_id);
    $employes = $db->fetchAll();
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="text-primary fw-bold">
                        <i class="bi bi-box-arrow-up me-2"></i>Sorties de stock
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none"><i class="bi bi-house-door"></i> Accueil</a></li>
                            <li class="breadcrumb-item active text-muted">Sorties</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('sorties', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/sorties/create.php" class="btn btn-primary shadow-sm">
                            <i class="bi bi-plus-circle me-1"></i> Nouvelle sortie
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i> Liste des sorties
                    </h5>
                    <span class="badge bg-light text-primary fs-6">
                        <i class="bi bi-box-seam me-1"></i> <?php echo count($sorties); ?> enregistrement(s)
                    </span>
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <div class="card mb-4 border">
                        <div class="card-header bg-light py-3">
                            <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtres de recherche</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="mb-0">
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Recherche</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                                            <input type="text" class="form-control" name="search" placeholder="ID, Service, Employé..." value="<?php echo htmlspecialchars($search); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Service</label>
                                        <select class="form-select form-select-sm" name="service_id" id="service_id_filter">
                                            <option value="">Tous les services</option>
                                            <?php foreach ($services as $service): ?>
                                                <option value="<?php echo $service['id']; ?>" <?php echo $service_id == $service['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($service['nom']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Employé</label>
                                        <select class="form-select form-select-sm" name="employe_id" id="employe_id_filter">
                                            <option value="">Tous les employés</option>
                                            <?php if (!empty($service_id) && count($employes) > 0): ?>
                                                <?php foreach ($employes as $emp): ?>
                                                    <option value="<?php echo $emp['id']; ?>" <?php echo $employe_id_filter == $emp['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($emp['nom'] . ' ' . $emp['prenom']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Date début</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                            <input type="date" class="form-control" name="date_debut" value="<?php echo htmlspecialchars($date_debut); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Date fin</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                            <input type="date" class="form-control" name="date_fin" value="<?php echo htmlspecialchars($date_fin); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="btn-group w-100" role="group">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="bi bi-search me-1"></i> Appliquer
                                            </button>
                                            <?php if (!empty($search) || !empty($service_id) || !empty($employe_id_filter) || !empty($date_debut) || !empty($date_fin)): ?>
                                                <a href="<?php echo BASE_URL; ?>/pages/sorties/index.php" class="btn btn-outline-secondary btn-sm">
                                                    <i class="bi bi-x-circle me-1"></i> Effacer
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="sortiesTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="80">N° Sortie</th>
                                    <th width="100">Date</th>
                                    <th>Service demandeur</th>
                                    <th>Employé demandeur</th>
                                    <th width="80">Articles</th>
                                    <th width="80">Fichier</th>
                                    <th width="180" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($sorties) > 0): ?>
                                    <?php foreach ($sorties as $sortie): ?>
                                        <tr class="<?php echo $sortie['nb_articles'] == 0 ? 'table-warning' : ''; ?>">
                                            <td>
                                                <span class="badge bg-primary rounded-pill px-3 py-2">
                                                    <?php echo $sortie['id']; ?>
                                                </span>
                                            </td>
                                            <td data-order="<?php echo $sortie['date']; ?>">
                                                <span class="badge bg-light text-dark border">
                                                    <i class="bi bi-calendar me-1"></i><?php echo date('d/m/Y', strtotime($sortie['date'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-building text-primary me-2"></i>
                                                    <span><?php echo htmlspecialchars($sortie['service_nom']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-person text-success me-2"></i>
                                                    <span><?php echo htmlspecialchars($sortie['employe_nom'] . ' ' . $sortie['employe_prenom']); ?></span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary" title="<?php echo $sortie['qte_totale'] ?? 0; ?> articles au total">
                                                    <i class="bi bi-box me-1"></i><?php echo $sortie['nb_articles']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($sortie['fichier'])): ?>
                                                    <a href="<?php echo UPLOAD_SORTIES_URL . '/' . $sortie['fichier']; ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Télécharger">
                                                        <i class="bi bi-file-earmark-arrow-down"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border">Aucun</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?php echo BASE_URL; ?>/pages/sorties/view.php?id=<?php echo $sortie['id']; ?>" class="btn btn-info" title="Voir détails" data-bs-toggle="tooltip">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>/pages/sorties/pdf.php?id=<?php echo $sortie['id']; ?>" class="btn btn-danger" title="Générer PDF" data-bs-toggle="tooltip" target="_blank">
                                                        <i class="bi bi-file-pdf"></i>
                                                    </a>
                                                    <?php if ($auth->hasPermission('sorties', 'update')): ?>
                                                        <a href="<?php echo BASE_URL; ?>/pages/sorties/edit.php?id=<?php echo $sortie['id']; ?>" class="btn btn-warning" title="Modifier" data-bs-toggle="tooltip">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($auth->hasPermission('sorties', 'delete')): ?>
                                                        <a href="<?php echo BASE_URL; ?>/pages/sorties/delete.php?id=<?php echo $sortie['id']; ?>" class="btn btn-outline-danger delete-confirm" title="Supprimer" data-bs-toggle="tooltip"
                                                           onclick="return confirm('Voulez-vous vraiment supprimer cette sortie ? Le stock sera recalculé.');">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="bi bi-inbox display-4 text-muted"></i>
                                                <h5 class="mt-3 text-muted">Aucune sortie trouvée</h5>
                                                <p class="text-muted">Utilisez les filtres pour affiner votre recherche</p>
                                            </div>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

$(document).ready(function() {
    $('#service_id_filter').on('change', function() {
        const service_id = $(this).val();
        const employe_select = $('#employe_id_filter');

        employe_select.html('<option value="">Tous les employés</option>');

        if (service_id) {
            $.ajax({
                url: BASE_URL + '/api/getemployebyservice.php',
                data: { service_id: service_id },
                dataType: 'json',
                success: function(data) {
                    if (data && data.length > 0) {
                        data.forEach(function(employe) {
                            employe_select.append(new Option(employe.text, employe.id));
                        });
                    }
                }
            });
        }
    });
});
</script>