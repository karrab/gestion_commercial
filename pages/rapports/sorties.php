<?php
$page_title = 'Rapport des sorties';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('rapports', 'view');
$db = Database::getInstance();

$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$service_id = $_GET['service_id'] ?? '';
$employe_id = $_GET['employe_id'] ?? '';
$code_article = $_GET['code_article'] ?? '';
$designation = $_GET['designation'] ?? '';

// Récupérer les détails des sorties avec les articles
$sql = "SELECT s.id as sortie_id, s.date, s.service_id, s.employe_id, s.notes,
               serv.nom as service_nom,
               emp.nom as employe_nom, emp.prenom as employe_prenom,
               ls.code_article, ls.designation as article_designation, ls.qte_sortie,
               a.designation as article_designation_full
        FROM sorties s
        INNER JOIN services serv ON s.service_id = serv.id
        INNER JOIN employes emp ON s.employe_id = emp.id
        INNER JOIN ligne_sorties ls ON s.id = ls.sortie_id
        LEFT JOIN articles a ON ls.article_id = a.id
        WHERE s.date BETWEEN :date_debut AND :date_fin";

$params = [':date_debut' => $date_debut, ':date_fin' => $date_fin];

if (!empty($service_id)) {
    $sql .= " AND s.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

if (!empty($employe_id)) {
    $sql .= " AND s.employe_id = :employe_id";
    $params[':employe_id'] = $employe_id;
}

if (!empty($code_article)) {
    $sql .= " AND ls.code_article LIKE :code_article";
    $params[':code_article'] = '%' . $code_article . '%';
}

if (!empty($designation)) {
    $sql .= " AND (ls.designation LIKE :designation OR a.designation LIKE :designation)";
    $params[':designation'] = '%' . $designation . '%';
}

$sql .= " ORDER BY s.date DESC, s.id DESC, ls.created_at ASC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$lignes_sorties = $stmt->fetchAll();

// Calculer le total des quantités
$total_qte = 0;
foreach ($lignes_sorties as $ligne) {
    $total_qte += $ligne['qte_sortie'];
}

// Récupérer tous les services
$services = $db->query("SELECT id, nom FROM services ORDER BY nom")->fetchAll();

// Récupérer les employés selon le service sélectionné
$employes = [];
if (!empty($service_id)) {
    $stmt = $db->getConnection()->prepare("SELECT id, nom, prenom FROM employes WHERE service_id = :service_id ORDER BY nom, prenom");
    $stmt->bindValue(':service_id', $service_id);
    $stmt->execute();
    $employes = $stmt->fetchAll();
} else {
    $employes = $db->query("SELECT id, nom, prenom FROM employes ORDER BY nom, prenom")->fetchAll();
}

// Récupérer les codes articles distincts pour le filtre
$codes_articles = $db->query("
    SELECT DISTINCT ls.code_article 
    FROM ligne_sorties ls
    INNER JOIN sorties s ON ls.sortie_id = s.id
    WHERE s.date BETWEEN :date_debut AND :date_fin
    ORDER BY ls.code_article
", [':date_debut' => $date_debut, ':date_fin' => $date_fin])->fetchAll();

// Récupérer les désignations distinctes pour le filtre
$designations = $db->query("
    SELECT DISTINCT COALESCE(a.designation, ls.designation) as designation
    FROM ligne_sorties ls
    LEFT JOIN articles a ON ls.article_id = a.id
    INNER JOIN sorties s ON ls.sortie_id = s.id
    WHERE s.date BETWEEN :date_debut AND :date_fin
    AND COALESCE(a.designation, ls.designation) IS NOT NULL
    ORDER BY COALESCE(a.designation, ls.designation)
", [':date_debut' => $date_debut, ':date_fin' => $date_fin])->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <h2><i class="bi bi-bar-chart"></i> Rapport des sorties</h2>

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
                    <label class="form-label">Service</label>
                    <select class="form-select" name="service_id" id="service_id">
                        <option value="">Tous</option>
                        <?php foreach ($services as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $service_id == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Employé</label>
                    <select class="form-select" name="employe_id" id="employe_id">
                        <option value="">Tous</option>
                        <?php foreach ($employes as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>" <?php echo $employe_id == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['nom'] . ' ' . $emp['prenom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Code article</label>
                    <select class="form-select select2-code" name="code_article" id="code_article" data-placeholder="Rechercher un code article...">
                        <option value="">Tous</option>
                        <?php foreach ($codes_articles as $code): ?>
                            <option value="<?php echo htmlspecialchars($code['code_article']); ?>" 
                                <?php echo $code_article == $code['code_article'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($code['code_article']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Désignation</label>
                    <select class="form-select select2-designation" name="designation" id="designation" data-placeholder="Rechercher une désignation...">
                        <option value="">Toutes</option>
                        <?php foreach ($designations as $des): ?>
                            <option value="<?php echo htmlspecialchars($des['designation']); ?>" 
                                <?php echo $designation == $des['designation'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($des['designation']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-8">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Appliquer les filtres
                    </button>
                    <a href="sorties.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Réinitialiser
                    </a>
                </div>
                <div class="col-md-4 text-end">
                    <a href="export_sortie_pdf.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success" target="_blank">
                        <i class="bi bi-file-earmark-pdf"></i> Exporter PDF
                    </a>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">Liste détaillée des articles sortis</h5>
                <div class="text-muted">
                    <small><?php echo count($lignes_sorties); ?> article(s) trouvé(s)</small>
                </div>
            </div>

            <?php if (empty($lignes_sorties)): ?>
                <div class="alert alert-info">
                    Aucune sortie trouvée pour les critères sélectionnés.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Service demandeur</th>
                                <th>Employé demandeur</th>
                                <th>Code article</th>
                                <th>Désignation</th>
                                <th class="text-end">Quantité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lignes_sorties as $ligne): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($ligne['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($ligne['service_nom']); ?></td>
                                    <td><?php echo htmlspecialchars($ligne['employe_nom'] . ' ' . $ligne['employe_prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($ligne['code_article']); ?></td>
                                    <td>
                                        <?php 
                                        $designation = !empty($ligne['article_designation_full']) ? 
                                            $ligne['article_designation_full'] : 
                                            $ligne['article_designation'];
                                        echo htmlspecialchars($designation);
                                        ?>
                                    </td>
                                    <td class="text-end"><?php echo (int)$ligne['qte_sortie']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end fw-bold">Total général :</td>
                                <td class="text-end fw-bold"><?php echo (int)$total_qte; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Select2 CSS/JS chargés via header.php et footer.php -->

<script>
// URL de l'API existante
const apiUrl = '../../api/getemployebyservice.php';

// Initialiser Select2 pour les menus déroulants
$(document).ready(function() {
    // Initialiser Select2 pour le code article
    $('#code_article').select2({
        theme: 'bootstrap-5',
        placeholder: 'Rechercher un code article...',
        allowClear: true,
        width: '100%'
    });
    
    // Initialiser Select2 pour la désignation
    $('#designation').select2({
        theme: 'bootstrap-5',
        placeholder: 'Rechercher une désignation...',
        allowClear: true,
        width: '100%'
    });
});

// Charger les employés dynamiquement quand on change de service
document.getElementById('service_id').addEventListener('change', function() {
    const serviceId = this.value;
    const employeSelect = document.getElementById('employe_id');
    
    // Sauvegarder l'employé actuellement sélectionné
    const currentEmployeId = employeSelect.value;
    
    // Réinitialiser la liste des employés
    employeSelect.innerHTML = '<option value="">Tous</option>';
    
    // Construire l'URL avec le paramètre service_id
    const url = serviceId ? `${apiUrl}?service_id=${serviceId}` : `${apiUrl}`;
    
    // Utiliser Fetch API pour appeler l'API existante
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .then(data => {
            if (data && Array.isArray(data)) {
                data.forEach(employe => {
                    const option = document.createElement('option');
                    option.value = employe.id;
                    option.textContent = employe.nom + ' ' + employe.prenom;
                    
                    // Si c'est l'employé précédemment sélectionné, le marquer comme sélectionné
                    if (currentEmployeId && currentEmployeId == employe.id) {
                        option.selected = true;
                    }
                    
                    employeSelect.appendChild(option);
                });
                
                // Si l'employé précédemment sélectionné n'est pas dans la nouvelle liste,
                // mais qu'il existe toujours (cas où on a sélectionné un employé d'un autre service),
                // alors on doit le récupérer et l'ajouter
                if (currentEmployeId && !employeSelect.value) {
                    fetch(`../../api/getemployebyservice.php?employe_id=${currentEmployeId}`)
                        .then(response => response.json())
                        .then(employeData => {
                            if (employeData && employeData.length > 0) {
                                const emp = employeData[0];
                                const option = document.createElement('option');
                                option.value = emp.id;
                                option.textContent = emp.nom + ' ' + emp.prenom;
                                option.selected = true;
                                employeSelect.appendChild(option);
                            }
                        });
                }
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des employés:', error);
            loadEmployesWithXHR(url, employeSelect, currentEmployeId);
        });
});

// Fonction de secours avec XMLHttpRequest
function loadEmployesWithXHR(url, employeSelect, currentEmployeId) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data && Array.isArray(data)) {
                        data.forEach(employe => {
                            const option = document.createElement('option');
                            option.value = employe.id;
                            option.textContent = employe.nom + ' ' + employe.prenom;
                            
                            if (currentEmployeId && currentEmployeId == employe.id) {
                                option.selected = true;
                            }
                            
                            employeSelect.appendChild(option);
                        });
                    }
                } catch (e) {
                    console.error('Erreur de parsing JSON:', e);
                }
            } else {
                console.error('Erreur XHR:', xhr.status, xhr.statusText);
            }
        }
    };
    xhr.send();
}

// Au chargement de la page, si un employé est sélectionné mais n'est pas dans la liste actuelle
// (cas où on a filtré par un employé qui n'est pas dans le service sélectionné)
document.addEventListener('DOMContentLoaded', function() {
    const employeSelect = document.getElementById('employe_id');
    const serviceId = document.getElementById('service_id').value;
    const currentEmployeId = employeSelect.value;
    
    // Si un employé est sélectionné mais pas dans la liste actuelle
    if (currentEmployeId && !employeSelect.value) {
        // Récupérer les infos de cet employé
        fetch(`../../api/getemployebyservice.php?employe_id=${currentEmployeId}`)
            .then(response => response.json())
            .then(employeData => {
                if (employeData && employeData.length > 0) {
                    const emp = employeData[0];
                    const option = document.createElement('option');
                    option.value = emp.id;
                    option.textContent = emp.nom + ' ' + emp.prenom;
                    option.selected = true;
                    employeSelect.appendChild(option);
                }
            });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>