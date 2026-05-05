<?php
/**
 * Page de liste des inventaires avec recherche et filtres
 */

$page_title = 'Gestion des inventaires';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'view');

$db = Database::getInstance();

// Paramètres de recherche et filtrage
$search = $_GET['search'] ?? '';
$etat_filter = $_GET['etat'] ?? '';
$annee_filter = $_GET['annee'] ?? '';

// Construire la requête SQL
$sql = "SELECT i.*,
               ei.nom as equipe_nom,
               u.nom as user_nom, u.prenom as user_prenom,
               COUNT(li.id) as nb_articles,
               SUM(CASE WHEN li.ecart < 0 THEN 1 ELSE 0 END) as nb_manquants,
               SUM(CASE WHEN li.ecart > 0 THEN 1 ELSE 0 END) as nb_excedents,
               SUM(CASE WHEN li.ecart = 0 THEN 1 ELSE 0 END) as nb_conformes
        FROM inventaires i
        LEFT JOIN equipes_inventaire ei ON i.equipe_id = ei.id
        LEFT JOIN users u ON i.user_id = u.id
        LEFT JOIN ligne_inventaires li ON i.id = li.inventaire_id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (i.reference LIKE :search OR ei.nom LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($etat_filter)) {
    if ($etat_filter == 'en_cours_reinitialise') {
        $sql .= " AND i.etat = 'en_cours' AND i.reinitialise = 1";
    } elseif ($etat_filter == 'en_cours') {
        $sql .= " AND i.etat = 'en_cours' AND i.reinitialise = 0";
    } else {
        $sql .= " AND i.etat = :etat";
        $params[':etat'] = $etat_filter;
    }
}

if (!empty($annee_filter)) {
    $sql .= " AND YEAR(i.date_debut) = :annee";
    $params[':annee'] = $annee_filter;
}

$sql .= " GROUP BY i.id ORDER BY i.created_at DESC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$inventaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les années disponibles pour le filtre
$stmt_annees = $db->getConnection()->query("SELECT DISTINCT YEAR(date_debut) as annee FROM inventaires ORDER BY annee DESC");
$annees = $stmt_annees->fetchAll(PDO::FETCH_COLUMN);

// Statistiques globales
$stats = [
    'total' => count($inventaires),
    'en_cours' => 0,
    'en_cours_reinitialise' => 0,
    'valide' => 0,
    'cloture' => 0
];

foreach ($inventaires as $inv) {
    if ($inv['etat'] == 'en_cours') {
        if ($inv['reinitialise'] == 1) {
            $stats['en_cours_reinitialise']++;
        } else {
            $stats['en_cours']++;
        }
    } else {
        $stats[$inv['etat']]++;
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2><i class="bi bi-clipboard-check"></i> Gestion des inventaires</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Inventaires</li>
                        </ol>
                    </nav>
                </div>
                <?php if ($auth->hasPermission('inventaires', 'create')): ?>
                    <div class="mt-2 mt-md-0">
                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouvel inventaire
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total']; ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-clipboard-check" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">En cours</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['en_cours']; ?></div>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-hourglass-split" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Réinitialisés</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['en_cours_reinitialise']; ?></div>
                            <small class="text-muted">(En cours)</small>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-arrow-repeat" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card border-left-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Validés</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['valide']; ?></div>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Clôturés</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['cloture']; ?></div>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-lock" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <i class="bi bi-funnel"></i> Recherche et filtres
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Recherche</label>
                    <input type="text" class="form-control" id="search" name="search"
                           placeholder="Référence, équipe..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label for="etat" class="form-label">État</label>
                    <select class="form-select" id="etat" name="etat">
                        <option value="">Tous les états</option>
                        <option value="en_cours" <?php echo $etat_filter == 'en_cours' ? 'selected' : ''; ?>>En cours (normaux)</option>
                        <option value="en_cours_reinitialise" <?php echo $etat_filter == 'en_cours_reinitialise' ? 'selected' : ''; ?>>En cours (réinitialisés)</option>
                        <option value="valide" <?php echo $etat_filter == 'valide' ? 'selected' : ''; ?>>Validé</option>
                        <option value="cloture" <?php echo $etat_filter == 'cloture' ? 'selected' : ''; ?>>Clôturé</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="annee" class="form-label">Année</label>
                    <select class="form-select" id="annee" name="annee">
                        <option value="">Toutes les années</option>
                        <?php foreach ($annees as $annee): ?>
                            <option value="<?php echo $annee; ?>" <?php echo $annee_filter == $annee ? 'selected' : ''; ?>>
                                <?php echo $annee; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filtrer
                    </button>
                </div>
            </form>
            <?php if (!empty($search) || !empty($etat_filter) || !empty($annee_filter)): ?>
                <div class="mt-3">
                    <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Réinitialiser les filtres
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Liste des inventaires -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-list"></i> Liste des inventaires (<?php echo count($inventaires); ?>)
        </div>
        <div class="card-body">
            <?php if (count($inventaires) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="11%">Référence</th>
                                <th width="9%">Date</th>
                                <th width="12%">Équipe</th>
                                <th width="12%">Préfixe(s)</th>
                                <th width="8%" class="text-center">Articles</th>
                                <th width="16%" class="text-center">Écarts</th>
                                <th width="9%" class="text-center">État</th>
                                <th width="12%">Créé par</th>
                                <th width="11%" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventaires as $inv): ?>
                                <?php
                                // Gestion des badges d'état avec prise en compte de la réinitialisation
                                $etat_badge = '';
                                $etat_label = '';
                                $etat_icon = '';
                                
                                if ($inv['etat'] == 'en_cours') {
                                    if ($inv['reinitialise'] == 1) {
                                        $etat_badge = 'bg-warning text-dark';
                                        $etat_label = 'Réinitialisé';
                                        $etat_icon = 'bi-arrow-repeat';
                                    } else {
                                        $etat_badge = 'bg-warning text-dark';
                                        $etat_label = 'En cours';
                                        $etat_icon = 'bi-hourglass-split';
                                    }
                                } elseif ($inv['etat'] == 'valide') {
                                    $etat_badge = 'bg-info';
                                    $etat_label = 'Validé';
                                    $etat_icon = 'bi-check-circle';
                                } elseif ($inv['etat'] == 'cloture') {
                                    $etat_badge = 'bg-success';
                                    $etat_label = 'Clôturé';
                                    $etat_icon = 'bi-lock';
                                } else {
                                    $etat_badge = 'bg-secondary';
                                    $etat_label = $inv['etat'];
                                    $etat_icon = 'bi-question-circle';
                                }
                                
                                // Filtrer selon le filtre d'état "en_cours_reinitialise"
                                if ($etat_filter == 'en_cours_reinitialise' && !($inv['etat'] == 'en_cours' && $inv['reinitialise'] == 1)) {
                                    continue;
                                }
                                if ($etat_filter == 'en_cours' && ($inv['etat'] != 'en_cours' || $inv['reinitialise'] == 1)) {
                                    continue;
                                }
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $inv['id']; ?>"
                                           class="text-decoration-none fw-bold">
                                            <?php echo htmlspecialchars($inv['reference']); ?>
                                            <?php if ($inv['reinitialise'] == 1): ?>
                                                <i class="bi bi-arrow-repeat text-warning ms-1" title="Réinitialisé"></i>
                                            <?php endif; ?>
                                        </a>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($inv['date_debut'])); ?></td>
                                    <td><?php echo $inv['equipe_nom'] ? htmlspecialchars($inv['equipe_nom']) : '<span class="text-muted">-</span>'; ?></td>
                                    <td>
                                        <?php if (!empty($inv['code_prefix'])): ?>
                                            <?php foreach (explode(',', $inv['code_prefix']) as $pfx): ?>
                                                <span class="badge bg-info text-dark me-1"><?php echo htmlspecialchars(trim($pfx)); ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?php echo $inv['nb_articles'] ?? 0; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success me-1" title="Conformes">
                                            <i class="bi bi-check-circle"></i> <?php echo $inv['nb_conformes'] ?? 0; ?>
                                        </span>
                                        <span class="badge bg-warning me-1" title="Excédents">
                                            <i class="bi bi-arrow-up-circle"></i> <?php echo $inv['nb_excedents'] ?? 0; ?>
                                        </span>
                                        <span class="badge bg-danger" title="Manquants">
                                            <i class="bi bi-arrow-down-circle"></i> <?php echo $inv['nb_manquants'] ?? 0; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $etat_badge; ?>">
                                            <i class="<?php echo $etat_icon; ?>"></i>
                                            <?php echo $etat_label; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($inv['user_prenom'] . ' ' . $inv['user_nom']); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $inv['id']; ?>"
                                               class="btn btn-outline-primary" title="Voir">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($inv['etat'] == 'en_cours' && $inv['reinitialise'] == 0 && $auth->hasPermission('inventaires', 'update')): ?>
                                                <a href="<?php echo BASE_URL; ?>/pages/inventaires/edit.php?id=<?php echo $inv['id']; ?>"
                                                   class="btn btn-outline-warning" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($inv['etat'] == 'en_cours' && $inv['reinitialise'] == 0 && $auth->hasPermission('inventaires', 'delete')): ?>
                                                <a href="<?php echo BASE_URL; ?>/pages/inventaires/delete.php?id=<?php echo $inv['id']; ?>"
                                                   class="btn btn-outline-danger" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                    <p class="mt-3 fs-5">Aucun inventaire trouvé</p>
                    <?php if ($auth->hasPermission('inventaires', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/create.php" class="btn btn-primary mt-2">
                            <i class="bi bi-plus-circle"></i> Créer le premier inventaire
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.border-left-primary { border-left: 4px solid #0d6efd; }
.border-left-warning { border-left: 4px solid #ffc107; }
.border-left-info { border-left: 4px solid #0dcaf0; }
.border-left-success { border-left: 4px solid #198754; }
.text-xs { font-size: 0.75rem; }
</style>

<script>
// Gestion du filtre d'état pour les inventaires réinitialisés
document.addEventListener('DOMContentLoaded', function() {
    const etatSelect = document.getElementById('etat');
    
    // Initialisation : si l'URL contient le filtre en_cours_reinitialise, on le sélectionne
    const urlParams = new URLSearchParams(window.location.search);
    const etatFilter = urlParams.get('etat');
    
    if (etatFilter === 'en_cours_reinitialise') {
        // On ne trouve pas cette option dans le select, on la crée
        const optionExists = Array.from(etatSelect.options).some(opt => opt.value === 'en_cours_reinitialise');
        
        if (!optionExists) {
            const option = document.createElement('option');
            option.value = 'en_cours_reinitialise';
            option.textContent = 'En cours (réinitialisés)';
            option.selected = true;
            
            // On ajoute l'option après "en_cours"
            const enCoursOption = Array.from(etatSelect.options).find(opt => opt.value === 'en_cours');
            if (enCoursOption) {
                etatSelect.insertBefore(option, enCoursOption.nextSibling);
            } else {
                etatSelect.appendChild(option);
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>