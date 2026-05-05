<?php
$page_title = 'Retours';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('retours', 'view');
$db = Database::getInstance();

$search = $_GET['search'] ?? '';
$service_id = $_GET['service_id'] ?? '';
$employe_id = $_GET['employe_id'] ?? ''; // Nouveau filtre employé
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

// Construction dynamique de la requête SQL
$sql = "SELECT r.*,
               s.nom as service_nom,
               e.nom as employe_nom, e.prenom as employe_prenom,
               COUNT(lr.id) as nb_articles,
               u.nom as user_nom, u.prenom as user_prenom
        FROM retours r
        INNER JOIN services s ON r.service_id = s.id
        INNER JOIN employes e ON r.employe_id = e.id
        LEFT JOIN ligne_retours lr ON r.id = lr.retour_id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE 1=1";

// Préparer les paramètres
$params = [];

if (!empty($search)) {
    $sql .= " AND (r.notes LIKE :search1 OR s.nom LIKE :search2 OR e.nom LIKE :search3 OR e.prenom LIKE :search4)";
    $params[':search1'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
    $params[':search3'] = '%' . $search . '%';
    $params[':search4'] = '%' . $search . '%';
}

if (!empty($service_id)) {
    $sql .= " AND r.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

if (!empty($employe_id)) {
    $sql .= " AND r.employe_id = :employe_id";
    $params[':employe_id'] = $employe_id;
}

if (!empty($date_debut)) {
    $sql .= " AND r.date >= :date_debut";
    $params[':date_debut'] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND r.date <= :date_fin";
    $params[':date_fin'] = $date_fin;
}

$sql .= " GROUP BY r.id ORDER BY r.date DESC, r.id DESC";

// Préparer et exécuter la requête
$db->prepare($sql);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$retours = $db->fetchAll();

// Charger les employés si un service est sélectionné
$employes = [];
if (!empty($service_id)) {
    $db->prepare("SELECT id, nom, prenom FROM employes WHERE service_id = ? ORDER BY nom, prenom");
    $db->bind(1, $service_id);
    $employes = $db->fetchAll();
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-box-arrow-in-up"></i> Retours de matériel</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Retours</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('retours', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/retours/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouveau retour
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list-ul"></i> Liste des retours
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            
                            <div class="col-md-2">
                                <select class="form-select" name="service_id" id="service_id">
                                    <option value="">Tous les services</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="employe_id" id="employe_id">
                                    <option value="">Tous les employés</option>
                                    <?php foreach ($employes as $e): ?>
                                        <option value="<?php echo $e['id']; ?>" <?php echo $employe_id == $e['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($e['nom'] . ' ' . $e['prenom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_debut" value="<?php echo $date_debut; ?>" placeholder="Date début">
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date_fin" value="<?php echo $date_fin; ?>" placeholder="Date fin">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                                <?php if (!empty($search) || !empty($service_id) || !empty($employe_id) || !empty($date_debut) || !empty($date_fin)): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/retours/index.php" class="btn btn-secondary"><i class="bi bi-x"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="retoursTable">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Date</th>
                                    <th>Service</th>
                                    <th>Employé</th>
                                    <th class="text-center">Nombre d'articles</th>
                                    <th>Fichier</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($retours) > 0): ?>
                                    <?php foreach ($retours as $retour): ?>
                                        <tr>
                                            <td><strong>#<?php echo $retour['id']; ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($retour['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($retour['service_nom']); ?></td>
                                            <td><?php echo htmlspecialchars($retour['employe_nom'] . ' ' . $retour['employe_prenom']); ?></td>
                                            <td class="text-center"><span class="badge bg-info"><?php echo (int)$retour['nb_articles']; ?></span></td>
                                            <td>
                                                <?php if (!empty($retour['fichier'])): ?>
                                                    <a href="<?php echo BASE_URL; ?>/uploads/retours/<?php echo $retour['fichier']; ?>"
                                                       target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?php echo BASE_URL; ?>/pages/retours/view.php?id=<?php echo $retour['id']; ?>"
                                                   class="btn btn-sm btn-info" title="Voir"><i class="bi bi-eye"></i></a>
                                                <a href="<?php echo BASE_URL; ?>/pages/retours/pdf.php?id=<?php echo $retour['id']; ?>"
                                                   class="btn btn-sm btn-secondary" title="PDF" target="_blank"><i class="bi bi-file-pdf"></i></a>
                                                <?php if ($auth->hasPermission('retours', 'update')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/retours/edit.php?id=<?php echo $retour['id']; ?>"
                                                       class="btn btn-sm btn-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                                <?php endif; ?>
                                                <?php if ($auth->hasPermission('retours', 'delete')): ?>
                                                    <a href="<?php echo BASE_URL; ?>/pages/retours/delete.php?id=<?php echo $retour['id']; ?>"
                                                       class="btn btn-sm btn-danger delete-confirm" title="Supprimer"
                                                       onclick="return confirm('Voulez-vous vraiment supprimer ce retour ?');"><i class="bi bi-trash"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="bi bi-inbox"></i> Aucun retour trouvé
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
// Définir BASE_URL pour JavaScript
const BASE_URL = '<?php echo BASE_URL; ?>';

$(document).ready(function() {
    // Initialiser Select2 avec AJAX pour les services
    initServiceSelect('#service_id', 'Tous les services');
    
    // Initialiser Select2 pour les employés (sera mis à jour selon le service)
    $('#employe_id').select2({
        placeholder: 'Tous les employés',
        allowClear: true,
        language: {
            noResults: function() {
                return 'Aucun employé trouvé';
            },
            searching: function() {
                return 'Recherche en cours...';
            }
        }
    });
    
    // Charger le service sélectionné si présent dans l'URL
    <?php if (!empty($service_id)): ?>
    setTimeout(function() {
        $('#service_id').val('<?php echo $service_id; ?>').trigger('change');
    }, 100);
    <?php endif; ?>
    
    // Chargement dynamique des employés selon le service sélectionné
    $('#service_id').on('change', function() {
        var serviceId = $(this).val();
        var employeSelect = $('#employe_id');
        
        // Réinitialiser le Select2 des employés
        employeSelect.empty().append('<option value="">Tous les employés</option>');
        
        if (serviceId) {
            // Appel AJAX pour récupérer les employés du service
            $.ajax({
                url: BASE_URL + '/api/getemployebyservice.php',
                type: 'GET',
                data: { service_id: serviceId },
                dataType: 'json',
                success: function(data) {
                    if (data && data.length > 0) {
                        $.each(data, function(index, employe) {
                            var option = new Option(employe.text || (employe.nom + ' ' + employe.prenom), employe.id, false, false);
                            employeSelect.append(option);
                        });
                        
                        // Rafraîchir Select2
                        employeSelect.trigger('change');
                        
                        // Définir la valeur de l'employé si elle existe dans l'URL
                        <?php if (!empty($employe_id)): ?>
                        setTimeout(function() {
                            employeSelect.val('<?php echo $employe_id; ?>').trigger('change');
                        }, 100);
                        <?php endif; ?>
                    }
                },
                error: function() {
                    console.log('Erreur lors du chargement des employés');
                }
            });
        } else {
            employeSelect.trigger('change');
        }
    });
});
</script>